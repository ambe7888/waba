import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import '../services/api_service.dart';

class SendTemplateScreen extends StatefulWidget {
  final String contactUid;
  final String contactName;

  const SendTemplateScreen({
    super.key,
    required this.contactUid,
    required this.contactName,
  });

  @override
  State<SendTemplateScreen> createState() => _SendTemplateScreenState();
}

class _SendTemplateScreenState extends State<SendTemplateScreen> {
  int _currentStep = 1;

  // Step 1: Template Selection
  List<Map<String, dynamic>> _templates = [];
  bool _isLoadingTemplates = true;
  Map<String, dynamic>? _selectedTemplate;

  // Step 2: Variables
  List<String> _bodyVariables = [];
  final Map<String, TextEditingController> _variableControllers = {};
  bool _requiresHeaderImage = false;
  File? _selectedHeaderImage;
  String? _headerImageUrl;
  final bool _isUploadingMedia = false;

  // Step 3: Schedule
  bool _sendImmediately = true;
  DateTime? _scheduledDate;
  TimeOfDay? _scheduledTime;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _fetchTemplates();
  }

  Future<void> _fetchTemplates() async {
    final list = await ApiService().fetchTemplates();
    if (mounted) {
      setState(() {
        _templates = list;
        _isLoadingTemplates = false;
      });
    }
  }

  void _parseTemplateRequirements(Map<String, dynamic> template) {
    try {
      final Map<String, dynamic> data = template['__data'] ?? {};
      final Map<String, dynamic> temp = data['template'] ?? {};
      final List components = temp['components'] ?? [];
      
      // Parse Body Variables
      final bodyComponent = components.firstWhere((c) => c['type'] == 'BODY', orElse: () => null);
      final String bodyText = bodyComponent?['text'] ?? '';
      
      final regExp = RegExp(r'\{\{(\d+)\}\}');
      final matches = regExp.allMatches(bodyText);
      _bodyVariables = matches.map((m) => m.group(0)!).toSet().toList();
      
      for (var v in _bodyVariables) {
        _variableControllers[v] = TextEditingController();
      }

      // Parse Header
      final headerComponent = components.firstWhere((c) => c['type'] == 'HEADER', orElse: () => null);
      if (headerComponent != null && headerComponent['format'] == 'IMAGE') {
        _requiresHeaderImage = true;
      } else {
        _requiresHeaderImage = false;
      }

    } catch (e) {
      _bodyVariables = [];
      _requiresHeaderImage = false;
    }
  }

  String _getTemplatePreview() {
    if (_selectedTemplate == null) return '';
    try {
      final Map<String, dynamic> data = _selectedTemplate!['__data'] ?? {};
      final Map<String, dynamic> temp = data['template'] ?? {};
      final List components = temp['components'] ?? [];
      final bodyComponent = components.firstWhere((c) => c['type'] == 'BODY', orElse: () => null);
      return bodyComponent?['text'] ?? '';
    } catch (e) {
      return '';
    }
  }

  Future<void> _pickImage() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.image,
    );

    if (result != null) {
      setState(() {
        _selectedHeaderImage = File(result.files.single.path!);
      });
      // Optionally upload immediately to get URL, or wait until send
    }
  }

  Future<void> _submitCampaign() async {
    if (_selectedTemplate == null) return;

    setState(() {
      _isSubmitting = true;
    });

    final Map<String, dynamic> payload = {};
    for (var v in _bodyVariables) {
      final index = v.replaceAll('{{', '').replaceAll('}}', '');
      payload['field_$index'] = _variableControllers[v]!.text;
    }

    if (_requiresHeaderImage && _selectedHeaderImage != null) {
      String? fileName = await ApiService().uploadTempMedia(_selectedHeaderImage!, 'whatsapp_image');
      if (fileName != null) {
        payload['header_image'] = fileName;
      } else {
        setState(() {
          _isSubmitting = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur lors de l'upload de l'image.")));
        return;
      }
    }

    if (!_sendImmediately && _scheduledDate != null && _scheduledTime != null) {
      final DateTime scheduled = DateTime(
        _scheduledDate!.year,
        _scheduledDate!.month,
        _scheduledDate!.day,
        _scheduledTime!.hour,
        _scheduledTime!.minute,
      );
      // Format as YYYY-MM-DD HH:MM:SS
      final String scheduledAtStr = scheduled.toIso8601String().split('T').join(' ').substring(0, 19);
      payload['scheduled_at'] = scheduledAtStr;
    }

    bool success = await ApiService().sendTemplateMessage(
      widget.contactUid,
      _selectedTemplate!['_uid'],
      payload,
    );

    if (mounted) {
      setState(() {
        _isSubmitting = false;
      });
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Modèle envoyé avec succès !'), backgroundColor: Colors.green),
        );
        Navigator.pop(context);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Erreur lors de l'envoi."), backgroundColor: Colors.red),
        );
      }
    }
  }

  Widget _buildStepIndicator() {
    return Container(
      padding: EdgeInsets.symmetric(vertical: 20, horizontal: 16),
      margin: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.08)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          _buildStepCircle(1, 'Modèle'),
          Expanded(child: Divider(color: _currentStep >= 2 ? Color(0xFF10B981) : Colors.grey.withValues(alpha: 0.3), thickness: 2)),
          _buildStepCircle(2, 'Variables'),
          Expanded(child: Divider(color: _currentStep >= 3 ? Color(0xFF10B981) : Colors.grey.withValues(alpha: 0.3), thickness: 2)),
          _buildStepCircle(3, 'Envoi'),
        ],
      ),
    );
  }

  Widget _buildStepCircle(int step, String label) {
    bool isActive = _currentStep >= step;
    bool isCurrent = _currentStep == step;

    return Row(
      children: [
        Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: isActive ? Color(0xFF10B981) : Colors.grey.withValues(alpha: 0.1),
          ),
          child: Center(
            child: Text(
              step.toString(),
              style: TextStyle(
                color: isActive ? Colors.white : Colors.grey,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ),
        if (isCurrent) ...[
          SizedBox(width: 8),
          Text(
            label,
            style: TextStyle(
              color: Color(0xFF10B981),
              fontWeight: FontWeight.bold,
              fontSize: 13,
            ),
          )
        ]
      ],
    );
  }

  Widget _buildStep1() {
    return _isLoadingTemplates
        ? Center(child: CircularProgressIndicator(color: Color(0xFF10B981)))
        : _templates.isEmpty
            ? Center(child: Text('Aucun modèle approuvé.'))
            : GridView.builder(
                padding: EdgeInsets.symmetric(horizontal: 16),
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  mainAxisSpacing: 16,
                  crossAxisSpacing: 16,
                  childAspectRatio: 0.75,
                ),
                itemCount: _templates.length,
                itemBuilder: (context, index) {
                  final template = _templates[index];
                  final isSelected = _selectedTemplate == template;
                  final category = template['category'] ?? 'Utility';
                  
                  return GestureDetector(
                    onTap: () {
                      setState(() {
                        _selectedTemplate = template;
                        _parseTemplateRequirements(template);
                      });
                    },
                    child: Container(
                      padding: EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: isSelected ? Color(0xFF10B981) : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.1),
                          width: isSelected ? 2 : 1,
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Container(
                                padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: Color(0xFF10B981).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  category.toUpperCase(),
                                  style: TextStyle(color: Color(0xFF10B981), fontSize: 10, fontWeight: FontWeight.bold),
                                ),
                              ),
                              Icon(Icons.language, size: 14, color: Colors.grey),
                            ],
                          ),
                          SizedBox(height: 12),
                          Text(
                            template['template_name'] ?? 'Nom du modèle',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          SizedBox(height: 8),
                          Expanded(
                            child: Text(
                              _getPreviewText(template),
                              style: TextStyle(fontSize: 12, color: Colors.grey),
                              maxLines: 4,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('✓ Approuvé', style: TextStyle(color: Color(0xFF10B981), fontSize: 11, fontWeight: FontWeight.bold)),
                              if (isSelected) Icon(Icons.check_circle, color: Color(0xFF10B981), size: 18),
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                },
              );
  }

  String _getPreviewText(Map<String, dynamic> template) {
    try {
      final Map<String, dynamic> data = template['__data'] ?? {};
      final Map<String, dynamic> temp = data['template'] ?? {};
      final List components = temp['components'] ?? [];
      final bodyComponent = components.firstWhere((c) => c['type'] == 'BODY', orElse: () => null);
      return bodyComponent?['text'] ?? '';
    } catch (e) {
      return '';
    }
  }

  Widget _buildStep2() {
    if (_bodyVariables.isEmpty && !_requiresHeaderImage) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Color(0xFF10B981).withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.auto_awesome, color: Color(0xFF10B981), size: 48),
            ),
            SizedBox(height: 24),
            Text(
              'Aucune variable dynamique',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 8),
            Text(
              'Ce modèle est prêt à être envoyé tel quel.',
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return SingleChildScrollView(
      padding: EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Preview Box
          Container(
            padding: EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.08)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('APERÇU DU MODÈLE', style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
                    Icon(Icons.visibility_outlined, color: Color(0xFF10B981), size: 16),
                  ],
                ),
                SizedBox(height: 12),
                Text(
                  _getTemplatePreview(),
                  style: TextStyle(fontSize: 14),
                ),
              ],
            ),
          ),
          SizedBox(height: 24),
          
          if (_requiresHeaderImage) ...[
            Text("IMAGE D'EN-TÊTE", style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
            SizedBox(height: 8),
            GestureDetector(
              onTap: _pickImage,
              child: Container(
                width: double.infinity,
                height: 120,
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.1)),
                ),
                child: _selectedHeaderImage != null
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Image.file(_selectedHeaderImage!, fit: BoxFit.cover),
                      )
                    : Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.image_outlined, size: 32, color: Colors.grey),
                          SizedBox(height: 8),
                          Text('Sélectionner une image', style: TextStyle(color: Colors.grey)),
                        ],
                      ),
              ),
            ),
            SizedBox(height: 24),
          ],

          if (_bodyVariables.isNotEmpty) ...[
            Text('VARIABLES DU MESSAGE', style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
            SizedBox(height: 8),
            Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.08)),
              ),
              child: Column(
                children: _bodyVariables.map((v) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 16.0),
                    child: TextField(
                      controller: _variableControllers[v],
                      decoration: InputDecoration(
                        labelText: 'Valeur pour $v',
                        filled: true,
                        fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.04),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildStep3() {
    return SingleChildScrollView(
      padding: EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Lancement & Programmation', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          SizedBox(height: 8),
          Text('Choisissez quand envoyer ce modèle.', style: TextStyle(color: Colors.grey)),
          SizedBox(height: 24),

          // Send Immediately Option
          GestureDetector(
            onTap: () => setState(() => _sendImmediately = true),
            child: Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: _sendImmediately ? Color(0xFF10B981).withValues(alpha: 0.1) : Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: _sendImmediately ? Color(0xFF10B981) : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.1),
                  width: _sendImmediately ? 2 : 1,
                ),
              ),
              child: Row(
                children: [
                  Container(
                    padding: EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: _sendImmediately ? Color(0xFF10B981) : Colors.grey.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(Icons.send_rounded, color: _sendImmediately ? Colors.white : Colors.grey, size: 20),
                  ),
                  SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Envoyer immédiatement', style: TextStyle(fontWeight: FontWeight.bold)),
                        SizedBox(height: 4),
                        Text('Traiter et envoyer ce modèle dès maintenant.', style: TextStyle(fontSize: 12, color: Colors.grey)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          SizedBox(height: 16),

          // Schedule Option
          GestureDetector(
            onTap: () => setState(() => _sendImmediately = false),
            child: Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: !_sendImmediately ? Color(0xFF10B981).withValues(alpha: 0.1) : Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: !_sendImmediately ? Color(0xFF10B981) : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.1),
                  width: !_sendImmediately ? 2 : 1,
                ),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      Container(
                        padding: EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: !_sendImmediately ? Color(0xFF10B981) : Colors.grey.withValues(alpha: 0.1),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(Icons.calendar_month_rounded, color: !_sendImmediately ? Colors.white : Colors.grey, size: 20),
                      ),
                      SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Programmer pour plus tard', style: TextStyle(fontWeight: FontWeight.bold)),
                            SizedBox(height: 4),
                            Text('Choisir une date et une heure spécifiques', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          ],
                        ),
                      ),
                    ],
                  ),
                  if (!_sendImmediately) ...[
                    SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () async {
                              final date = await showDatePicker(
                                context: context,
                                initialDate: DateTime.now(),
                                firstDate: DateTime.now(),
                                lastDate: DateTime.now().add(Duration(days: 365)),
                              );
                              if (date != null) setState(() => _scheduledDate = date);
                            },
                            icon: Icon(Icons.calendar_today, size: 16),
                            label: Text(_scheduledDate != null ? "${_scheduledDate!.day}/${_scheduledDate!.month}/${_scheduledDate!.year}" : 'Date'),
                          ),
                        ),
                        SizedBox(width: 8),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () async {
                              final time = await showTimePicker(
                                context: context,
                                initialTime: TimeOfDay.now(),
                              );
                              if (time != null) setState(() => _scheduledTime = time);
                            },
                            icon: Icon(Icons.access_time, size: 16),
                            label: Text(_scheduledTime != null ? _scheduledTime!.format(context) : 'Heure'),
                          ),
                        ),
                      ],
                    ),
                  ]
                ],
              ),
            ),
          ),
          
          SizedBox(height: 24),
          Container(
            padding: EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.blue.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline, color: Colors.blue),
                SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Les modèles WhatsApp ne peuvent pas être modifiés une fois envoyés au client.',
                    style: TextStyle(color: Colors.blue.shade700, fontSize: 12),
                  ),
                ),
              ],
            ),
          )
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).colorScheme.surface,
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.surface,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: Theme.of(context).colorScheme.onSurface),
          onPressed: () {
            if (_currentStep > 1) {
              setState(() => _currentStep--);
            } else {
              Navigator.pop(context);
            }
          },
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Envoyer un Modèle', style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 18)),
            Text('Sélectionnez un modèle approuvé...', style: TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
      ),
      body: Column(
        children: [
          _buildStepIndicator(),
          Expanded(
            child: _currentStep == 1
                ? _buildStep1()
                : _currentStep == 2
                    ? _buildStep2()
                    : _buildStep3(),
          ),
          
          // Bottom Navigation Bar
          Container(
            padding: EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              boxShadow: [
                BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: Offset(0, -5))
              ],
            ),
            child: Row(
              children: [
                if (_currentStep > 1)
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        padding: EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: () => setState(() => _currentStep--),
                      child: Text('Précédent', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold)),
                    ),
                  ),
                if (_currentStep > 1) SizedBox(width: 16),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Color(0xFF10B981),
                      padding: EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                    onPressed: () {
                      if (_currentStep == 1) {
                        if (_selectedTemplate == null) {
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Veuillez sélectionner un modèle')));
                          return;
                        }
                        setState(() => _currentStep++);
                      } else if (_currentStep == 2) {
                        setState(() => _currentStep++);
                      } else if (_currentStep == 3) {
                        if (!_sendImmediately && (_scheduledDate == null || _scheduledTime == null)) {
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Veuillez sélectionner une date et une heure')));
                          return;
                        }
                        _submitCampaign();
                      }
                    },
                    child: _isSubmitting 
                        ? SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Text(
                            _currentStep < 3 ? 'Suivant' : 'Envoyer',
                            style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

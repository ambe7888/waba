import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'package:intl/intl.dart';
import 'dart:io';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../models/contact.dart';

class CreateCampaignScreen extends StatefulWidget {
  const CreateCampaignScreen({super.key});

  @override
  State<CreateCampaignScreen> createState() => _CreateCampaignScreenState();
}

class _CreateCampaignScreenState extends State<CreateCampaignScreen> {
  int _currentStep = 0;
  final int _totalSteps = 5;

  // Step 1: Info
  final _titleController = TextEditingController();
  final _descController = TextEditingController();

  // Step 2: Template
  List<Map<String, dynamic>> _templates = [];
  bool _isLoadingTemplates = true;
  Map<String, dynamic>? _selectedTemplate;

  // Step 3: Variables
  List<String> _bodyVariables = [];
  final Map<String, TextEditingController> _variableControllers = {};
  bool _requiresHeaderImage = false;
  File? _selectedHeaderImage;
  String? _headerImageUrl;

  // Step 4: Audience
  String _audienceMode = 'all'; // 'all', 'specific', 'audiences', 'groups'
  List<Contact> _contacts = [];
  List<Contact> _filteredContacts = [];
  List<Map<String, dynamic>> _audiences = [];
  List<Map<String, dynamic>> _groups = [];
  
  final List<String> _selectedContactIds = [];
  String? _selectedAudienceUid;
  final List<String> _selectedGroupIds = [];
  
  bool _isLoadingAudienceData = true;
  final _searchContactsController = TextEditingController();

  // Step 5: Timeline
  bool _sendImmediately = true;
  DateTime? _scheduledDate;
  TimeOfDay? _scheduledTime;
  
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _fetchInitialData();
    _searchContactsController.addListener(_onSearchContacts);
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descController.dispose();
    _searchContactsController.dispose();
    for (var c in _variableControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _fetchInitialData() async {
    try {
      final templates = await ApiService().fetchTemplates();
      final contactsRes = await ApiService().fetchContacts(page: 1); // simplify for now
      final audiences = await ApiService().fetchAudiences();
      final groups = await ApiService().fetchContactGroups();

      if (mounted) {
        setState(() {
          _templates = templates;
          _contacts = contactsRes['contacts'] as List<Contact>? ?? [];
          _filteredContacts = _contacts;
          _audiences = audiences;
          _groups = groups;
          _isLoadingTemplates = false;
          _isLoadingAudienceData = false;
        });
      }
    } catch (e) {
      debugPrint("Error fetching initial campaign data: $e");
      if (mounted) {
        setState(() {
          _isLoadingTemplates = false;
          _isLoadingAudienceData = false;
        });
      }
    }
  }

  void _onSearchContacts() {
    final query = _searchContactsController.text.toLowerCase();
    setState(() {
      _filteredContacts = _contacts.where((c) {
        return c.name.toLowerCase().contains(query) || c.phoneNumber.contains(query);
      }).toList();
    });
  }

  void _parseTemplate(Map<String, dynamic> template) {
    try {
      final Map<String, dynamic> data = template['__data'] ?? {};
      final Map<String, dynamic> temp = data['template'] ?? {};
      final List components = temp['components'] ?? [];
      
      final bodyComponent = components.firstWhere((c) => c['type'] == 'BODY', orElse: () => null);
      final String bodyText = bodyComponent?['text'] ?? '';
      
      final regExp = RegExp(r'\{\{(\d+)\}\}');
      final matches = regExp.allMatches(bodyText);
      _bodyVariables = matches.map((m) => m.group(0)!).toSet().toList();
      
      _variableControllers.clear();
      for (var v in _bodyVariables) {
        _variableControllers[v] = TextEditingController();
      }

      final headerComponent = components.firstWhere((c) => c['type'] == 'HEADER', orElse: () => null);
      if (headerComponent != null && headerComponent['format'] == 'IMAGE') {
        _requiresHeaderImage = true;
      } else {
        _requiresHeaderImage = false;
      }
      _selectedHeaderImage = null;
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
    FilePickerResult? result = await FilePicker.platform.pickFiles(type: FileType.image);
    if (result != null && result.files.single.path != null) {
      setState(() {
        _selectedHeaderImage = File(result.files.single.path!);
      });
    }
  }

  Future<void> _submitCampaign() async {
    if (_titleController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Le titre est requis.')));
      return;
    }
    if (_selectedTemplate == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner un modèle.')));
      return;
    }
    // Validation d'audience
    if (_audienceMode == 'specific' && _selectedContactIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner au moins un contact.')));
      return;
    }
    if (_audienceMode == 'audiences' && _selectedAudienceUid == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner une audience.')));
      return;
    }
    if (_audienceMode == 'groups' && _selectedGroupIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner au moins un groupe.')));
      return;
    }

    setState(() => _isSubmitting = true);
    
    // Prepare API Data
    // We will call ApiService().createCampaign(...) here once we update it
    
    // Mock success for now until ApiService is updated
    await Future.delayed(const Duration(seconds: 2));
    
    if (mounted) {
      setState(() => _isSubmitting = false);
      Navigator.pop(context, true);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Campagne créée avec succès!')));
    }
  }

  void _nextStep() {
    if (_currentStep < _totalSteps - 1) {
      // Validations before moving next
      if (_currentStep == 0 && _titleController.text.trim().isEmpty) {
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Le titre est obligatoire.')));
         return;
      }
      if (_currentStep == 1 && _selectedTemplate == null) {
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez choisir un modèle.')));
         return;
      }
      
      setState(() => _currentStep++);
    }
  }

  void _prevStep() {
    if (_currentStep > 0) {
      setState(() => _currentStep--);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Create Campaign', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 18)),
            const Text('Follow the steps to launch your campaign', style: TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
      ),
      body: Column(
        children: [
          _buildStepperHeader(),
          Expanded(
            child: _buildCurrentStepView(),
          ),
          _buildBottomBar(),
        ],
      ),
    );
  }

  Widget _buildStepperHeader() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: List.generate(_totalSteps, (index) {
          final isPast = index < _currentStep;
          final isCurrent = index == _currentStep;
          return Row(
            children: [
              CircleAvatar(
                radius: 12,
                backgroundColor: isPast || isCurrent ? ThemeService.primaryColor : Colors.grey.shade200,
                child: isPast 
                  ? const Icon(Icons.check, size: 16, color: Colors.white)
                  : Text('${index + 1}', style: TextStyle(color: isCurrent ? Colors.white : Colors.black54, fontSize: 12, fontWeight: FontWeight.bold)),
              ),
              if (index < _totalSteps - 1)
                Container(
                  width: 20,
                  height: 2,
                  color: isPast ? ThemeService.primaryColor : Colors.grey.shade200,
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                ),
            ],
          );
        }),
      ),
    );
  }

  Widget _buildCurrentStepView() {
    switch (_currentStep) {
      case 0: return _buildStep1();
      case 1: return _buildStep2();
      case 2: return _buildStep3();
      case 3: return _buildStep4();
      case 4: return _buildStep5();
      default: return const SizedBox.shrink();
    }
  }

  Widget _buildStep1() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(Icons.campaign, color: ThemeService.primaryColor),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Campaign Information', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                  const Text('Give your campaign an identifiable name to get started.', style: TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        const Text('Campaign Name *', style: TextStyle(fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        TextField(
          controller: _titleController,
          decoration: InputDecoration(
            hintText: 'E.g. Summer Sale 2024 - Newsletter',
            filled: true,
            fillColor: Colors.grey.shade50,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
          ),
        ),
        const SizedBox(height: 16),
        const Text('Internal Description', style: TextStyle(fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        TextField(
          controller: _descController,
          maxLines: 3,
          decoration: InputDecoration(
            hintText: 'What is the goal of this campaign?',
            filled: true,
            fillColor: Colors.grey.shade50,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
          ),
        ),
        const SizedBox(height: 24),
        const Text('Delivery Channel', style: TextStyle(fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            border: Border.all(color: ThemeService.primaryColor, width: 2),
            borderRadius: BorderRadius.circular(12),
            color: Colors.white,
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: ThemeService.primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
                child: Icon(Icons.wechat, color: ThemeService.primaryColor),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('WhatsApp Broadcast', style: TextStyle(fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                    const Text('Official API templates for higher conversions.', style: TextStyle(fontSize: 12, color: Colors.grey)),
                  ],
                ),
              ),
              Icon(Icons.check_circle, color: ThemeService.primaryColor),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStep2() {
    if (_isLoadingTemplates) return const Center(child: CircularProgressIndicator());
    if (_templates.isEmpty) return const Center(child: Text("Aucun modèle approuvé."));

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: ThemeService.primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
              child: Icon(Icons.phone_android, color: ThemeService.primaryColor),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('WhatsApp Configuration', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                  const Text('Choose the account and template for campaign.', style: TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Text('${_templates.length} APPROVED TEMPLATES', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
        const SizedBox(height: 16),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            crossAxisSpacing: 8,
            mainAxisSpacing: 8,
            childAspectRatio: 0.75,
          ),
          itemCount: _templates.length,
          itemBuilder: (context, index) {
            final t = _templates[index];
            final title = t['template_name'] ?? 'Unknown';
            final lang = t['language'] ?? '';
            final category = t['category'] ?? '';
            final status = t['status'] ?? '';
            final isSelected = _selectedTemplate != null && _selectedTemplate!['_uid'] == t['_uid'];

            return GestureDetector(
              onTap: () {
                setState(() => _selectedTemplate = t);
                _parseTemplate(t);
              },
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  border: Border.all(color: isSelected ? ThemeService.primaryColor : Colors.grey.shade300, width: isSelected ? 2 : 1),
                  borderRadius: BorderRadius.circular(12),
                  color: isSelected ? ThemeService.primaryColor.withValues(alpha: 0.05) : Colors.white,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: ThemeService.primaryColor.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(4)),
                          child: Text(category, style: TextStyle(fontSize: 10, color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
                        ),
                        Row(
                          children: [
                            const Icon(Icons.language, size: 12, color: Colors.grey),
                            const SizedBox(width: 2),
                            Text(lang, style: const TextStyle(fontSize: 10, color: Colors.grey)),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14), maxLines: 2, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 8),
                    const Spacer(),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Icon(Icons.description, size: 14, color: Colors.grey),
                        Text(status, style: TextStyle(color: status == 'APPROVED' ? Colors.green : Colors.grey, fontSize: 12, fontWeight: FontWeight.bold)),
                      ],
                    )
                  ],
                ),
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildStep3() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text('Variable Mapping', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const Text('Map your template placeholders to contact data.', style: TextStyle(color: Colors.grey)),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(12)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('TEMPLATE PREVIEW', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(color: ThemeService.primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(4)),
                    child: Row(
                      children: [
                        Icon(Icons.visibility, size: 14, color: ThemeService.primaryColor),
                        const SizedBox(width: 4),
                        Text('Preview Template', style: TextStyle(fontSize: 12, color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(_getTemplatePreview(), style: const TextStyle(fontSize: 14)),
            ],
          ),
        ),
        const SizedBox(height: 24),
        if (_requiresHeaderImage) ...[
          Text('HEADER IMAGE', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          GestureDetector(
            onTap: _pickImage,
            child: Container(
              height: 120,
              width: double.infinity,
              decoration: BoxDecoration(
                border: Border.all(color: Colors.grey.shade300, style: BorderStyle.solid),
                borderRadius: BorderRadius.circular(12),
                color: Colors.grey.shade50,
              ),
              child: _selectedHeaderImage != null
                  ? ClipRRect(borderRadius: BorderRadius.circular(12), child: Image.file(_selectedHeaderImage!, fit: BoxFit.cover))
                  : Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.add_photo_alternate, size: 40, color: ThemeService.primaryColor),
                        const SizedBox(height: 8),
                        const Text('Upload image from gallery', style: TextStyle(color: Colors.grey)),
                      ],
                    ),
            ),
          ),
          const SizedBox(height: 24),
        ],
        if (_bodyVariables.isNotEmpty) ...[
          Text('BODY VARIABLES', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          const Text('Enter static values for placeholders.', style: TextStyle(color: Colors.grey, fontSize: 12)),
          const SizedBox(height: 12),
          ..._bodyVariables.map((v) {
            return Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade200), borderRadius: BorderRadius.circular(12)),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('VARIABLE PLACEHOLDER: $v', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _variableControllers[v],
                    decoration: InputDecoration(
                      hintText: 'Enter fixed text...',
                      filled: true,
                      fillColor: Colors.grey.shade50,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                    ),
                  ),
                ],
              ),
            );
          }),
        ] else ...[
          const Center(child: Padding(
            padding: EdgeInsets.all(24.0),
            child: Text("Aucune variable dans ce modèle.", style: TextStyle(color: Colors.grey)),
          )),
        ]
      ],
    );
  }

  Widget _buildStep4() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text('Choose who will receive this campaign message.', style: TextStyle(color: Colors.grey)),
        const SizedBox(height: 16),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          crossAxisSpacing: 8,
          mainAxisSpacing: 8,
          childAspectRatio: 1.2,
          children: [
            _buildAudienceCard('all', 'ALL CONTACTS', 'Send to everyone', Icons.contact_mail),
            _buildAudienceCard('specific', 'SPECIFIC CONTACTS', 'Handpick individuals', Icons.person),
            _buildAudienceCard('audiences', 'TARGET SEGMENTS', 'Pre-defined segments', Icons.pie_chart),
            _buildAudienceCard('groups', 'SEGMENT BY TAGS', 'Target specific groups', Icons.local_offer),
          ],
        ),
        const SizedBox(height: 24),
        if (_audienceMode == 'specific') ...[
          TextField(
            controller: _searchContactsController,
            decoration: InputDecoration(
              hintText: 'Search contacts...',
              prefixIcon: const Icon(Icons.search),
              filled: true,
              fillColor: Colors.grey.shade50,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
            ),
          ),
          const SizedBox(height: 8),
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _filteredContacts.length,
            itemBuilder: (context, index) {
              final c = _filteredContacts[index];
              return CheckboxListTile(
                title: Text(c.name),
                subtitle: Text(c.phoneNumber),
                value: _selectedContactIds.contains(c.uid),
                activeColor: ThemeService.primaryColor,
                onChanged: (val) {
                  setState(() {
                    if (val == true) {
                      _selectedContactIds.add(c.uid);
                    } else {
                      _selectedContactIds.remove(c.uid);
                    }
                  });
                },
              );
            },
          ),
        ] else if (_audienceMode == 'audiences') ...[
          if (_audiences.isEmpty) const Text("Aucune audience disponible."),
          ..._audiences.map((a) {
            final isSelected = _selectedAudienceUid == a['_uid'];
            return RadioListTile<String>(
              title: Text(a['title'] ?? 'Sans nom'),
              subtitle: Text("Créé le ${a['created_at']}"),
              value: a['_uid'],
              groupValue: _selectedAudienceUid,
              activeColor: ThemeService.primaryColor,
              onChanged: (val) {
                setState(() => _selectedAudienceUid = val);
              },
            );
          }),
        ] else if (_audienceMode == 'groups') ...[
          if (_groups.isEmpty) const Text("Aucun groupe disponible."),
          ..._groups.map((g) {
            final uid = g['_id']?.toString() ?? g['_uid']?.toString() ?? '';
            return CheckboxListTile(
              title: Text(g['title'] ?? 'Sans nom'),
              subtitle: Text(g['description'] ?? ''),
              value: _selectedGroupIds.contains(uid),
              activeColor: ThemeService.primaryColor,
              onChanged: (val) {
                setState(() {
                  if (val == true) {
                    _selectedGroupIds.add(uid);
                  } else {
                    _selectedGroupIds.remove(uid);
                  }
                });
              },
            );
          }),
        ]
      ],
    );
  }

  Widget _buildAudienceCard(String mode, String title, String subtitle, IconData icon) {
    final isSelected = _audienceMode == mode;
    return GestureDetector(
      onTap: () => setState(() => _audienceMode = mode),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          border: Border.all(color: isSelected ? ThemeService.primaryColor : Colors.grey.shade300, width: isSelected ? 2 : 1),
          borderRadius: BorderRadius.circular(12),
          color: isSelected ? ThemeService.primaryColor.withValues(alpha: 0.05) : Colors.white,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: isSelected ? ThemeService.primaryColor : Colors.grey, size: 28),
            const SizedBox(height: 8),
            Text(title, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: isSelected ? ThemeService.primaryColor : Colors.black87), textAlign: TextAlign.center),
            const SizedBox(height: 4),
            Text(subtitle, style: const TextStyle(fontSize: 10, color: Colors.grey), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildStep5() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: ThemeService.primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
              child: Icon(Icons.schedule, color: ThemeService.primaryColor),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Launch & Schedule', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                  const Text('Choose when to send out your campaign.', style: TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        GestureDetector(
          onTap: () => setState(() => _sendImmediately = true),
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              border: Border.all(color: _sendImmediately ? ThemeService.primaryColor : Colors.grey.shade300, width: _sendImmediately ? 2 : 1),
              borderRadius: BorderRadius.circular(12),
              color: _sendImmediately ? ThemeService.primaryColor.withValues(alpha: 0.05) : Colors.white,
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: ThemeService.primaryColor, borderRadius: BorderRadius.circular(8)),
                  child: const Icon(Icons.send, color: Colors.white, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Send Immediately', style: TextStyle(fontWeight: FontWeight.bold)),
                      const Text('Process and send this campaign right after you hit the launch button.', style: TextStyle(fontSize: 12, color: Colors.grey)),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            border: Border.all(color: !_sendImmediately ? ThemeService.primaryColor : Colors.grey.shade300, width: !_sendImmediately ? 2 : 1),
            borderRadius: BorderRadius.circular(12),
            color: !_sendImmediately ? ThemeService.primaryColor.withValues(alpha: 0.05) : Colors.white,
          ),
          child: Column(
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(color: Colors.grey.shade200, borderRadius: BorderRadius.circular(8)),
                    child: const Icon(Icons.calendar_today, color: Colors.black54, size: 20),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Schedule for Later', style: TextStyle(fontWeight: FontWeight.bold)),
                        Text('Pick a specific date and time', style: TextStyle(fontSize: 12, color: Colors.grey)),
                      ],
                    ),
                  ),
                  Switch(
                    value: !_sendImmediately,
                    activeThumbColor: ThemeService.primaryColor,
                    onChanged: (val) => setState(() => _sendImmediately = !val),
                  ),
                ],
              ),
              if (!_sendImmediately) ...[
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: Colors.black87,
                          side: BorderSide(color: Colors.grey.shade300),
                        ),
                        icon: const Icon(Icons.calendar_month),
                        label: Text(_scheduledDate != null ? DateFormat('yyyy-MM-dd').format(_scheduledDate!) : 'Date'),
                        onPressed: () async {
                          final date = await showDatePicker(
                            context: context,
                            initialDate: DateTime.now(),
                            firstDate: DateTime.now(),
                            lastDate: DateTime.now().add(const Duration(days: 365)),
                          );
                          if (date != null) setState(() => _scheduledDate = date);
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: Colors.black87,
                          side: BorderSide(color: Colors.grey.shade300),
                        ),
                        icon: const Icon(Icons.access_time),
                        label: Text(_scheduledTime != null ? _scheduledTime!.format(context) : 'Time'),
                        onPressed: () async {
                          final time = await showTimePicker(
                            context: context,
                            initialTime: TimeOfDay.now(),
                          );
                          if (time != null) setState(() => _scheduledTime = time);
                        },
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 24),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(color: ThemeService.primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
          child: Row(
            children: [
              Icon(Icons.check_circle_outline, color: ThemeService.primaryColor),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Ready to launch?', style: TextStyle(fontWeight: FontWeight.bold)),
                    Text('Please review all steps before proceeding. Campaigns cannot be edited once they are in the sending queue.', style: TextStyle(fontSize: 12, color: Colors.grey)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildBottomBar() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          TextButton(
            onPressed: _currentStep == 0 ? () => Navigator.pop(context) : _prevStep,
            style: TextButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              backgroundColor: Colors.grey.shade100,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: Text(_currentStep == 0 ? 'Cancel' : 'Back', style: const TextStyle(color: Colors.black87)),
          ),
          if (_currentStep == _totalSteps - 1)
            ElevatedButton(
              onPressed: _isSubmitting ? null : _submitCampaign,
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
                backgroundColor: ThemeService.primaryColor,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: _isSubmitting
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text('Publish', style: TextStyle(color: Colors.white)),
            )
          else
            ElevatedButton(
              onPressed: _nextStep,
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
                backgroundColor: ThemeService.primaryColor,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: const Text('Next Step', style: TextStyle(color: Colors.white)),
            ),
        ],
      ),
    );
  }
}

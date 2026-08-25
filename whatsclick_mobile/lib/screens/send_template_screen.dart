import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import '../services/api_service.dart';
import 'create_template_screen.dart';

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
  String _templateSearchQuery = '';
  String? _categoryFilter; // null = all, else 'MARKETING' / 'UTILITY' / ...

  List<Map<String, dynamic>> get _filteredTemplates {
    return _templates.where((t) {
      final category = (t['category'] ?? '').toString().toUpperCase();
      if (_categoryFilter != null && category != _categoryFilter) return false;
      if (_templateSearchQuery.trim().isEmpty) return true;
      final query = _templateSearchQuery.toLowerCase();
      final name = (t['template_name'] ?? '').toString().toLowerCase();
      return name.contains(query) || category.toLowerCase().contains(query);
    }).toList();
  }

  // Step 2: Variables
  List<String> _bodyVariables = [];
  final Map<String, TextEditingController> _variableControllers = {};
  // 'custom' = manually typed value (default); otherwise one of the
  // predefined dynamic_contact_* tag keys below. The backend resolves
  // these tag keys to the real contact field at send time — same
  // mechanism the web dashboard's "Choose or Write your own" selector
  // uses (see WhatsAppServiceEngine::setParameterValue).
  final Map<String, String> _variableTagSelection = {};
  bool _requiresHeaderImage = false;
  File? _selectedHeaderImage;
  String? _headerImageUrl;
  final bool _isUploadingMedia = false;

  // Header: TEXT variable (e.g. "Bonjour {{1}}")
  bool _requiresHeaderText = false;
  final TextEditingController _headerFieldController = TextEditingController();
  String _headerFieldTagSelection = 'custom';

  // Header: VIDEO / DOCUMENT
  bool _requiresHeaderVideo = false;
  File? _selectedHeaderVideo;
  bool _requiresHeaderDocument = false;
  File? _selectedHeaderDocument;
  final TextEditingController _headerDocumentNameController = TextEditingController();

  // Header: LOCATION
  bool _requiresLocation = false;
  final TextEditingController _locationLatController = TextEditingController();
  final TextEditingController _locationLngController = TextEditingController();
  final TextEditingController _locationNameController = TextEditingController();
  final TextEditingController _locationAddressController = TextEditingController();

  // BUTTONS: dynamic URL suffix + copy code
  final Map<int, TextEditingController> _dynamicUrlButtonControllers = {};
  final Map<int, String> _dynamicUrlButtonLabels = {};
  bool _requiresCopyCode = false;
  final TextEditingController _copyCodeController = TextEditingController();

  // Mirrors config('__tech.contact_data_mapping') — the predefined contact
  // field tags offered as an alternative to manual entry.
  static const Map<String, String> _predefinedTags = {
    'dynamic_contact_first_name': 'Prénom du contact',
    'dynamic_contact_last_name': 'Nom du contact',
    'dynamic_contact_wa_id': 'Téléphone du contact',
    'dynamic_contact_language_code': 'Code langue',
    'dynamic_contact_country': 'Pays du contact',
    'dynamic_contact_email': 'E-mail du contact',
  };

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

  @override
  void dispose() {
    for (var c in _variableControllers.values) {
      c.dispose();
    }
    for (var c in _dynamicUrlButtonControllers.values) {
      c.dispose();
    }
    _headerFieldController.dispose();
    _headerDocumentNameController.dispose();
    _locationLatController.dispose();
    _locationLngController.dispose();
    _locationNameController.dispose();
    _locationAddressController.dispose();
    _copyCodeController.dispose();
    super.dispose();
  }

  String? _templatesError;

  Future<void> _fetchTemplates() async {
    final list = await ApiService().fetchTemplates();
    if (mounted) {
      setState(() {
        _templates = list;
        _templatesError = list.isEmpty ? ApiService().lastFetchTemplatesError : null;
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
        _variableTagSelection[v] = 'custom';
      }

      // Parse Header
      final headerComponent = components.firstWhere((c) => c['type'] == 'HEADER', orElse: () => null);
      final String headerFormat = headerComponent?['format'] ?? '';
      _requiresHeaderImage = headerFormat == 'IMAGE';
      _requiresHeaderVideo = headerFormat == 'VIDEO';
      _requiresHeaderDocument = headerFormat == 'DOCUMENT';
      _requiresLocation = headerFormat == 'LOCATION';
      _requiresHeaderText = headerFormat == 'TEXT' &&
          (headerComponent?['text'] ?? '').toString().contains('{{1}}');
      _headerFieldController.clear();
      _headerFieldTagSelection = 'custom';
      _selectedHeaderVideo = null;
      _selectedHeaderDocument = null;
      _headerDocumentNameController.clear();
      _locationLatController.clear();
      _locationLngController.clear();
      _locationNameController.clear();
      _locationAddressController.clear();

      // Parse Buttons (dynamic URL suffix + copy code)
      _dynamicUrlButtonControllers.clear();
      _dynamicUrlButtonLabels.clear();
      _requiresCopyCode = false;
      _copyCodeController.clear();
      final buttonsComponent = components.firstWhere((c) => c['type'] == 'BUTTONS', orElse: () => null);
      final List buttons = buttonsComponent?['buttons'] ?? [];
      for (var i = 0; i < buttons.length; i++) {
        final btn = buttons[i];
        final String btnType = btn['type'] ?? '';
        if (btnType == 'URL' && (btn['url'] ?? '').toString().contains('{{1}}')) {
          _dynamicUrlButtonControllers[i] = TextEditingController();
          _dynamicUrlButtonLabels[i] = btn['text'] ?? 'Bouton ${i + 1}';
        } else if (btnType == 'COPY_CODE') {
          _requiresCopyCode = true;
        }
      }

    } catch (e) {
      _bodyVariables = [];
      _requiresHeaderImage = false;
      _requiresHeaderVideo = false;
      _requiresHeaderDocument = false;
      _requiresLocation = false;
      _requiresHeaderText = false;
      _dynamicUrlButtonControllers.clear();
      _dynamicUrlButtonLabels.clear();
      _requiresCopyCode = false;
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

  Future<void> _pickVideo() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(type: FileType.video);
    if (result != null) {
      setState(() {
        _selectedHeaderVideo = File(result.files.single.path!);
      });
    }
  }

  Future<void> _pickDocument() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
    );
    if (result != null) {
      setState(() {
        _selectedHeaderDocument = File(result.files.single.path!);
        if (_headerDocumentNameController.text.isEmpty) {
          _headerDocumentNameController.text = result.files.single.name;
        }
      });
    }
  }

  /// Returns a French error message describing the first missing required
  /// field, or null if everything the template needs has been provided.
  /// Mirrors the backend's required-field validation (see
  /// WhatsAppServiceEngine::sendTemplateMessageProcess) so the user gets a
  /// specific message instead of a generic send error.
  String? _findMissingFieldError() {
    for (var v in _bodyVariables) {
      final tag = _variableTagSelection[v] ?? 'custom';
      if (tag == 'custom' && (_variableControllers[v]?.text.trim().isEmpty ?? true)) {
        return 'Veuillez remplir la variable $v.';
      }
    }
    if (_requiresHeaderText &&
        _headerFieldTagSelection == 'custom' &&
        _headerFieldController.text.trim().isEmpty) {
      return "Veuillez remplir le texte de l'en-tête.";
    }
    if (_requiresHeaderImage && _selectedHeaderImage == null) {
      return "Veuillez sélectionner une image pour l'en-tête.";
    }
    if (_requiresHeaderVideo && _selectedHeaderVideo == null) {
      return "Veuillez sélectionner une vidéo pour l'en-tête.";
    }
    if (_requiresHeaderDocument && _selectedHeaderDocument == null) {
      return "Veuillez sélectionner un document pour l'en-tête.";
    }
    if (_requiresLocation) {
      if (_locationLatController.text.trim().isEmpty || _locationLngController.text.trim().isEmpty) {
        return 'Veuillez indiquer la latitude et la longitude.';
      }
      if (_locationNameController.text.trim().isEmpty || _locationAddressController.text.trim().isEmpty) {
        return 'Veuillez indiquer le nom et l\'adresse du lieu.';
      }
    }
    for (var entry in _dynamicUrlButtonControllers.entries) {
      if (entry.value.text.trim().isEmpty) {
        return 'Veuillez remplir la valeur du bouton "${_dynamicUrlButtonLabels[entry.key]}".';
      }
    }
    if (_requiresCopyCode) {
      final code = _copyCodeController.text.trim();
      if (code.isEmpty) {
        return 'Veuillez indiquer le code promo.';
      }
      if (!RegExp(r'^[a-zA-Z0-9_-]+$').hasMatch(code)) {
        return 'Le code promo ne doit contenir que des lettres, chiffres, tirets et underscores.';
      }
    }
    return null;
  }

  Future<void> _submitCampaign() async {
    if (_selectedTemplate == null) return;

    final missingFieldError = _findMissingFieldError();
    if (missingFieldError != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(missingFieldError), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    final Map<String, dynamic> payload = {};
    for (var v in _bodyVariables) {
      final index = v.replaceAll('{{', '').replaceAll('}}', '');
      final tag = _variableTagSelection[v] ?? 'custom';
      payload['field_$index'] = tag == 'custom' ? _variableControllers[v]!.text : tag;
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

    if (_requiresHeaderVideo) {
      if (_selectedHeaderVideo == null) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Veuillez sélectionner une vidéo pour l\'en-tête.')));
        return;
      }
      String? fileName = await ApiService().uploadTempMedia(_selectedHeaderVideo!, 'whatsapp_video');
      if (fileName == null) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur lors de l'upload de la vidéo.")));
        return;
      }
      payload['header_video'] = fileName;
    }

    if (_requiresHeaderDocument) {
      if (_selectedHeaderDocument == null) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Veuillez sélectionner un document pour l\'en-tête.')));
        return;
      }
      String? fileName = await ApiService().uploadTempMedia(_selectedHeaderDocument!, 'whatsapp_document');
      if (fileName == null) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur lors de l'upload du document.")));
        return;
      }
      payload['header_document'] = fileName;
      payload['header_document_name'] = _headerDocumentNameController.text.isNotEmpty
          ? _headerDocumentNameController.text
          : 'document';
    }

    if (_requiresHeaderText) {
      payload['header_field_1'] =
          _headerFieldTagSelection == 'custom' ? _headerFieldController.text : _headerFieldTagSelection;
    }

    if (_requiresLocation) {
      payload['location_latitude'] = _locationLatController.text;
      payload['location_longitude'] = _locationLngController.text;
      payload['location_name'] = _locationNameController.text;
      payload['location_address'] = _locationAddressController.text;
    }

    for (var entry in _dynamicUrlButtonControllers.entries) {
      payload['button_${entry.key}'] = entry.value.text;
    }

    if (_requiresCopyCode) {
      payload['copy_code'] = _copyCodeController.text;
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
        final serverError = ApiService().lastTemplateSendError;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(serverError ?? "Erreur lors de l'envoi."),
            backgroundColor: Colors.red,
          ),
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
    if (_isLoadingTemplates) {
      return Center(child: CircularProgressIndicator(color: Color(0xFF10B981)));
    }
    if (_templates.isEmpty) {
      if (_templatesError != null) {
        return Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.wifi_off_rounded, size: 48, color: Colors.grey),
                const SizedBox(height: 12),
                const Text('Impossible de charger les modèles.\nVérifiez votre connexion.', textAlign: TextAlign.center),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: () {
                    setState(() => _isLoadingTemplates = true);
                    _fetchTemplates();
                  },
                  icon: const Icon(Icons.refresh_rounded),
                  label: const Text('Réessayer'),
                ),
              ],
            ),
          ),
        );
      }
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.article_outlined, size: 48, color: Colors.grey),
              const SizedBox(height: 12),
              const Text('Aucun modèle approuvé.', textAlign: TextAlign.center),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: () {
                  Navigator.of(context).push<bool>(
                    MaterialPageRoute(builder: (_) => const CreateTemplateScreen()),
                  ).then((created) {
                    if (created == true) {
                      setState(() => _isLoadingTemplates = true);
                      _fetchTemplates();
                    }
                  });
                },
                icon: const Icon(Icons.add_rounded),
                label: const Text('Créer votre premier modèle'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          ),
        ),
      );
    }

    final filtered = _filteredTemplates;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          child: TextField(
            onChanged: (val) => setState(() => _templateSearchQuery = val),
            decoration: InputDecoration(
              hintText: 'Rechercher un modèle...',
              prefixIcon: Icon(Icons.search_rounded, color: Colors.grey, size: 20),
              filled: true,
              fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.04),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide.none,
              ),
              contentPadding: const EdgeInsets.symmetric(vertical: 0),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          child: Row(
            children: [
              _buildCategoryFilterChip(null, 'Tous'),
              const SizedBox(width: 8),
              _buildCategoryFilterChip('MARKETING', 'Marketing'),
              const SizedBox(width: 8),
              _buildCategoryFilterChip('UTILITY', 'Utilitaire'),
            ],
          ),
        ),
        Expanded(
          child: filtered.isEmpty
              ? Center(child: Text('Aucun modèle ne correspond à la recherche.', style: TextStyle(color: Colors.grey)))
              : GridView.builder(
                padding: EdgeInsets.symmetric(horizontal: 16),
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  mainAxisSpacing: 16,
                  crossAxisSpacing: 16,
                  childAspectRatio: 0.75,
                ),
                itemCount: filtered.length,
                itemBuilder: (context, index) {
                  final template = filtered[index];
                  final isSelected = _selectedTemplate == template;
                  final category = template['category'] ?? 'Utility';
                  final categoryColor = _categoryColor(category.toString());

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
                          color: isSelected ? categoryColor : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.1),
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
                                  color: categoryColor.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  category.toString().toUpperCase(),
                                  style: TextStyle(color: categoryColor, fontSize: 10, fontWeight: FontWeight.bold),
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
              ),
        ),
      ],
    );
  }

  /// Distinct color per WhatsApp template category, instead of one color
  /// for every card regardless of type. Matches the palette used on the
  /// campaign creation wizard's template step.
  static Color _categoryColor(String category) {
    switch (category.toUpperCase()) {
      case 'UTILITY':
        return Colors.blue;
      case 'MARKETING':
        return Colors.deepOrange;
      case 'AUTHENTICATION':
        return Colors.purple;
      default:
        return const Color(0xFF10B981);
    }
  }

  /// Category filter chip — same look as the campaign wizard's template step.
  Widget _buildCategoryFilterChip(String? value, String label) {
    final selected = _categoryFilter == value;
    final color = value == null ? const Color(0xFF10B981) : _categoryColor(value);
    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: () => setState(() => _categoryFilter = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: selected ? color.withValues(alpha: 0.15) : Colors.transparent,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: selected ? color : Colors.grey.withValues(alpha: 0.3)),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: selected ? FontWeight.bold : FontWeight.normal,
            color: selected ? color : Colors.grey,
          ),
        ),
      ),
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

  bool get _hasAnyStep2Requirement =>
      _bodyVariables.isNotEmpty ||
      _requiresHeaderImage ||
      _requiresHeaderVideo ||
      _requiresHeaderDocument ||
      _requiresHeaderText ||
      _requiresLocation ||
      _dynamicUrlButtonControllers.isNotEmpty ||
      _requiresCopyCode;

  Widget _buildStep2() {
    if (!_hasAnyStep2Requirement) {
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

          if (_requiresHeaderText) ...[
            Text("TEXTE D'EN-TÊTE", style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
            SizedBox(height: 8),
            _buildTagOrTextField(
              tagSelection: _headerFieldTagSelection,
              controller: _headerFieldController,
              onTagChanged: (val) => setState(() => _headerFieldTagSelection = val ?? 'custom'),
            ),
            SizedBox(height: 24),
          ],

          if (_requiresHeaderVideo) ...[
            Text('VIDÉO D\'EN-TÊTE', style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
            SizedBox(height: 8),
            _buildFilePickerBox(
              onTap: _pickVideo,
              icon: Icons.videocam_outlined,
              label: _selectedHeaderVideo != null ? _selectedHeaderVideo!.path.split(Platform.pathSeparator).last : 'Sélectionner une vidéo',
              hasFile: _selectedHeaderVideo != null,
            ),
            SizedBox(height: 24),
          ],

          if (_requiresHeaderDocument) ...[
            Text("DOCUMENT D'EN-TÊTE", style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
            SizedBox(height: 8),
            _buildFilePickerBox(
              onTap: _pickDocument,
              icon: Icons.description_outlined,
              label: _selectedHeaderDocument != null ? _selectedHeaderDocument!.path.split(Platform.pathSeparator).last : 'Sélectionner un document',
              hasFile: _selectedHeaderDocument != null,
            ),
            SizedBox(height: 8),
            TextField(
              controller: _headerDocumentNameController,
              decoration: InputDecoration(
                hintText: 'Nom du fichier (ex: Facture.pdf)',
                filled: true,
                fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.04),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
              ),
            ),
            SizedBox(height: 24),
          ],

          if (_requiresLocation) ...[
            Text('LOCALISATION', style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
            SizedBox(height: 8),
            Row(
              children: [
                Expanded(child: _buildSimpleTextField(_locationLatController, 'Latitude')),
                SizedBox(width: 12),
                Expanded(child: _buildSimpleTextField(_locationLngController, 'Longitude')),
              ],
            ),
            SizedBox(height: 12),
            _buildSimpleTextField(_locationNameController, 'Nom du lieu'),
            SizedBox(height: 12),
            _buildSimpleTextField(_locationAddressController, 'Adresse'),
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
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Valeur pour $v',
                            style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Colors.grey.shade600)),
                        const SizedBox(height: 6),
                        _buildTagOrTextField(
                          tagSelection: _variableTagSelection[v] ?? 'custom',
                          controller: _variableControllers[v]!,
                          onTagChanged: (val) => setState(() => _variableTagSelection[v] = val ?? 'custom'),
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ),
            SizedBox(height: 24),
          ],

          if (_dynamicUrlButtonControllers.isNotEmpty || _requiresCopyCode) ...[
            Text('BOUTONS', style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
            SizedBox(height: 8),
            Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.08)),
              ),
              child: Column(
                children: [
                  ..._dynamicUrlButtonControllers.entries.map((entry) => Padding(
                        padding: const EdgeInsets.only(bottom: 16.0),
                        child: _buildSimpleTextField(
                          entry.value,
                          '${_dynamicUrlButtonLabels[entry.key]} — suffixe de l\'URL',
                        ),
                      )),
                  if (_requiresCopyCode) _buildSimpleTextField(_copyCodeController, 'Code promo'),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  /// Dropdown to pick a predefined contact tag, or "custom" to reveal a free
  /// text field. Shared by body variables and the header text variable.
  Widget _buildTagOrTextField({
    required String tagSelection,
    required TextEditingController controller,
    required ValueChanged<String?> onTagChanged,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        DropdownButtonFormField<String>(
          initialValue: tagSelection,
          isExpanded: true,
          decoration: InputDecoration(
            filled: true,
            fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.04),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: BorderSide.none,
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          ),
          items: [
            const DropdownMenuItem(value: 'custom', child: Text('Valeur personnalisée...')),
            ..._predefinedTags.entries.map(
                (e) => DropdownMenuItem(value: e.key, child: Text(e.value, overflow: TextOverflow.ellipsis))),
          ],
          onChanged: onTagChanged,
        ),
        if (tagSelection == 'custom') ...[
          const SizedBox(height: 8),
          TextField(
            controller: controller,
            decoration: InputDecoration(
              hintText: 'Saisissez une valeur...',
              filled: true,
              fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.04),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
                borderSide: BorderSide.none,
              ),
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildSimpleTextField(TextEditingController controller, String hint) {
    return TextField(
      controller: controller,
      decoration: InputDecoration(
        hintText: hint,
        filled: true,
        fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.04),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
      ),
    );
  }

  Widget _buildFilePickerBox({
    required VoidCallback onTap,
    required IconData icon,
    required String label,
    required bool hasFile,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: EdgeInsets.symmetric(vertical: 20, horizontal: 16),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: hasFile ? Color(0xFF10B981) : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.1),
          ),
        ),
        child: Row(
          children: [
            Icon(icon, size: 22, color: hasFile ? Color(0xFF10B981) : Colors.grey),
            SizedBox(width: 12),
            Expanded(
              child: Text(
                label,
                style: TextStyle(color: hasFile ? null : Colors.grey),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (hasFile) Icon(Icons.check_circle, color: Color(0xFF10B981), size: 18),
          ],
        ),
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

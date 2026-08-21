import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'package:intl/intl.dart';
import 'dart:io';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../models/contact.dart';
import '../utils/date_format_utils.dart';
import 'create_audience_screen.dart';

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
  String? _categoryFilter; // null = all, 'MARKETING', 'UTILITY'

  List<Map<String, dynamic>> get _filteredTemplates {
    if (_categoryFilter == null) return _templates;
    return _templates
        .where((t) => (t['category']?.toString().toUpperCase() ?? '') == _categoryFilter)
        .toList();
  }

  Color _categoryColor(String? category) {
    switch ((category ?? '').toUpperCase()) {
      case 'UTILITY':
        return Colors.blue;
      case 'MARKETING':
        return Colors.deepOrange;
      case 'AUTHENTICATION':
        return Colors.purple;
      default:
        return ThemeService.primaryColor;
    }
  }

  String _categoryLabel(String? category) {
    switch ((category ?? '').toUpperCase()) {
      case 'UTILITY':
        return 'UTILITAIRE';
      case 'MARKETING':
        return 'MARKETING';
      case 'AUTHENTICATION':
        return 'AUTHENTIFICATION';
      default:
        return category ?? '';
    }
  }

  // Step 3: Variables
  List<String> _bodyVariables = [];
  final Map<String, TextEditingController> _variableControllers = {};
  final Map<String, String> _variableTagSelection = {};
  bool _requiresHeaderImage = false;
  File? _selectedHeaderImage;
  String? _headerImageUrl;

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

  // Mirrors config('__tech.contact_data_mapping') — same predefined contact
  // field tags offered when sending a single template (send_template_screen.dart).
  static const Map<String, String> _predefinedTags = {
    'dynamic_contact_first_name': 'Prénom du contact',
    'dynamic_contact_last_name': 'Nom du contact',
    'dynamic_contact_wa_id': 'Téléphone du contact',
    'dynamic_contact_language_code': 'Code langue',
    'dynamic_contact_country': 'Pays du contact',
    'dynamic_contact_email': 'E-mail du contact',
  };

  // Step 4: Audience
  String _audienceMode = 'all'; // 'all', 'specific', 'audiences', 'groups'
  List<Contact> _contacts = [];
  List<Contact> _filteredContacts = [];
  List<Map<String, dynamic>> _audiences = [];
  List<Map<String, dynamic>> _groups = [];
  List<Map<String, dynamic>> _labels = [];

  final List<String> _selectedContactIds = [];
  String? _selectedAudienceUid;
  final List<String> _selectedGroupIds = [];
  final List<String> _selectedLabelIds = [];

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

  Future<void> _fetchInitialData() async {
    try {
      // Each fetch is isolated so that one failing (e.g. audiences or
      // labels) doesn't wipe out data the others already loaded fine —
      // previously a single failure here left step 2 showing "Aucun
      // modèle approuvé disponible" even though templates had loaded.
      final results = await Future.wait<dynamic>([
        ApiService().fetchTemplates().catchError((e) {
          debugPrint('fetchTemplates error: $e');
          return <Map<String, dynamic>>[];
        }),
        // Full, unpaginated contact list — needed so "Contacts spécifiques"
        // can actually offer every contact, not just the first page.
        ApiService().fetchAllContactsSimple().catchError((e) {
          debugPrint('fetchAllContactsSimple error: $e');
          return <Contact>[];
        }),
        ApiService().fetchAudiences().catchError((e) {
          debugPrint('fetchAudiences error: $e');
          return <Map<String, dynamic>>[];
        }),
        ApiService().fetchContactGroups().catchError((e) {
          debugPrint('fetchContactGroups error: $e');
          return <Map<String, dynamic>>[];
        }),
        ApiService().fetchContactLabelsWithCounts().catchError((e) {
          debugPrint('fetchContactLabelsWithCounts error: $e');
          return <Map<String, dynamic>>[];
        }),
      ]);

      if (mounted) {
        setState(() {
          _templates = results[0] as List<Map<String, dynamic>>;
          _contacts = results[1] as List<Contact>;
          _filteredContacts = _contacts;
          _audiences = results[2] as List<Map<String, dynamic>>;
          _groups = results[3] as List<Map<String, dynamic>>;
          _labels = results[4] as List<Map<String, dynamic>>;
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

  Future<void> _openCreateAudience() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const CreateAudienceScreen()),
    );
    if (created == true && mounted) {
      final audiences = await ApiService().fetchAudiences().catchError((e) => <Map<String, dynamic>>[]);
      if (mounted) {
        setState(() {
          _audiences = audiences;
          // Sorted ascending by id, so the newest one (just created) is last.
          if (audiences.isNotEmpty) {
            _selectedAudienceUid = audiences.last['_uid'];
          }
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
      _variableTagSelection.clear();
      for (var v in _bodyVariables) {
        _variableControllers[v] = TextEditingController();
        _variableTagSelection[v] = 'custom';
      }

      final headerComponent = components.firstWhere((c) => c['type'] == 'HEADER', orElse: () => null);
      final String headerFormat = headerComponent?['format'] ?? '';
      _requiresHeaderImage = headerFormat == 'IMAGE';
      _requiresHeaderVideo = headerFormat == 'VIDEO';
      _requiresHeaderDocument = headerFormat == 'DOCUMENT';
      _requiresLocation = headerFormat == 'LOCATION';
      _requiresHeaderText = headerFormat == 'TEXT' &&
          (headerComponent?['text'] ?? '').toString().contains('{{1}}');
      _selectedHeaderImage = null;
      _selectedHeaderVideo = null;
      _selectedHeaderDocument = null;
      _headerFieldController.clear();
      _headerFieldTagSelection = 'custom';
      _headerDocumentNameController.clear();
      _locationLatController.clear();
      _locationLngController.clear();
      _locationNameController.clear();
      _locationAddressController.clear();

      // Parse Buttons (dynamic URL suffix + copy code)
      for (var c in _dynamicUrlButtonControllers.values) {
        c.dispose();
      }
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
    FilePickerResult? result = await FilePicker.platform.pickFiles(type: FileType.image);
    if (result != null && result.files.single.path != null) {
      setState(() {
        _selectedHeaderImage = File(result.files.single.path!);
      });
    }
  }

  Future<void> _pickVideo() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(type: FileType.video);
    if (result != null && result.files.single.path != null) {
      setState(() => _selectedHeaderVideo = File(result.files.single.path!));
    }
  }

  Future<void> _pickDocument() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
    );
    if (result != null && result.files.single.path != null) {
      setState(() {
        _selectedHeaderDocument = File(result.files.single.path!);
        if (_headerDocumentNameController.text.isEmpty) {
          _headerDocumentNameController.text = result.files.single.name;
        }
      });
    }
  }

  /// Returns a French error message describing the first missing/invalid
  /// required field, or null if everything the template needs is filled in.
  /// Mirrors send_template_screen.dart's validation so both screens behave
  /// consistently.
  String? _findMissingCampaignFieldError() {
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
    if (_audienceMode == 'groups' && _selectedGroupIds.isEmpty && _selectedLabelIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner au moins un groupe ou une étiquette.')));
      return;
    }
    final missingFieldError = _findMissingCampaignFieldError();
    if (missingFieldError != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(missingFieldError), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    String? headerImageFileName;
    if (_requiresHeaderImage && _selectedHeaderImage != null) {
      headerImageFileName = await ApiService().uploadTempMedia(_selectedHeaderImage!, 'whatsapp_image');
      if (headerImageFileName == null) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Erreur lors de l'upload de l'image.")));
        return;
      }
    }
    String? headerVideoFileName;
    if (_requiresHeaderVideo && _selectedHeaderVideo != null) {
      headerVideoFileName = await ApiService().uploadTempMedia(_selectedHeaderVideo!, 'whatsapp_video');
      if (headerVideoFileName == null) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Erreur lors de l'upload de la vidéo.")));
        return;
      }
    }
    String? headerDocumentFileName;
    if (_requiresHeaderDocument && _selectedHeaderDocument != null) {
      headerDocumentFileName = await ApiService().uploadTempMedia(_selectedHeaderDocument!, 'whatsapp_document');
      if (headerDocumentFileName == null) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Erreur lors de l'upload du document.")));
        return;
      }
    }

    // Call real API
    final result = await ApiService().createCampaign(
      title: _titleController.text.trim(),
      templateUid: _selectedTemplate!['_uid'],
      audienceMode: _audienceMode,
      contactUids: _selectedContactIds.toList(),
      audienceUid: _selectedAudienceUid,
      groupUids: _selectedGroupIds.toList(),
      labelIds: _selectedLabelIds.toList(),
      sendImmediately: _sendImmediately,
      scheduledDate: _scheduledDate,
      scheduledTime: _scheduledTime,
      bodyVariables: {
        for (var v in _bodyVariables)
          v.replaceAll('{{', '').replaceAll('}}', ''):
              (_variableTagSelection[v] ?? 'custom') == 'custom'
                  ? _variableControllers[v]!.text
                  : _variableTagSelection[v]!
      },
      headerFieldValue: _requiresHeaderText
          ? (_headerFieldTagSelection == 'custom' ? _headerFieldController.text : _headerFieldTagSelection)
          : null,
      headerImageFileName: headerImageFileName,
      headerVideoFileName: headerVideoFileName,
      headerDocumentFileName: headerDocumentFileName,
      headerDocumentName: _requiresHeaderDocument ? _headerDocumentNameController.text : null,
      locationLatitude: _requiresLocation ? _locationLatController.text : null,
      locationLongitude: _requiresLocation ? _locationLngController.text : null,
      locationName: _requiresLocation ? _locationNameController.text : null,
      locationAddress: _requiresLocation ? _locationAddressController.text : null,
      dynamicUrlButtons: _dynamicUrlButtonControllers.isNotEmpty
          ? _dynamicUrlButtonControllers.map((k, v) => MapEntry(k, v.text))
          : null,
      copyCode: _requiresCopyCode ? _copyCodeController.text : null,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (result['reaction'] == 1) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Campagne créée avec succès!')));
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message'] ?? 'Erreur lors de la création de la campagne')));
      }
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
    final isDark = ThemeService().isDark;

    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: isDark ? Colors.white : Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Créer une campagne', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 18)),
            Text('Suivez les étapes pour lancer votre campagne', style: TextStyle(color: isDark ? Colors.white54 : Colors.grey, fontSize: 12)),
          ],
        ),
      ),
      body: Column(
        children: [
          _buildStepperHeader(isDark),
          Expanded(
            child: _buildCurrentStepView(isDark),
          ),
          _buildBottomBar(isDark),
        ],
      ),
    );
  }

  Widget _buildStepperHeader(bool isDark) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? const Color(0xFF334155) : Colors.grey.shade200),
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
                backgroundColor: isPast || isCurrent ? ThemeService.primaryColor : (isDark ? Colors.white12 : Colors.grey.shade200),
                child: isPast 
                  ? const Icon(Icons.check, size: 16, color: Colors.white)
                  : Text('${index + 1}', style: TextStyle(color: isCurrent ? Colors.white : (isDark ? Colors.white54 : Colors.black54), fontSize: 12, fontWeight: FontWeight.bold)),
              ),
              if (index < _totalSteps - 1)
                Container(
                  width: 20,
                  height: 2,
                  color: isPast ? ThemeService.primaryColor : (isDark ? Colors.white12 : Colors.grey.shade200),
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                ),
            ],
          );
        }),
      ),
    );
  }

  Widget _buildCurrentStepView(bool isDark) {
    switch (_currentStep) {
      case 0: return _buildStep1(isDark);
      case 1: return _buildStep2(isDark);
      case 2: return _buildStep3(isDark);
      case 3: return _buildStep4(isDark);
      case 4: return _buildStep5(isDark);
      default: return const SizedBox.shrink();
    }
  }

  Widget _buildStep1(bool isDark) {
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
                  Text('Informations de la campagne', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                  Text('Donnez un nom identifiable à votre campagne.', style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        Text('Nom de la campagne *', style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
        const SizedBox(height: 8),
        TextField(
          controller: _titleController,
          style: TextStyle(color: isDark ? Colors.white : Colors.black87),
          decoration: InputDecoration(
            hintText: 'Ex: Promo Été 2024 - Offre Spéciale',
            hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.grey),
            filled: true,
            fillColor: isDark ? ThemeService.darkCard : Colors.grey.shade50,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
          ),
        ),
        const SizedBox(height: 16),
        Text('Description interne', style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
        const SizedBox(height: 8),
        TextField(
          controller: _descController,
          maxLines: 3,
          style: TextStyle(color: isDark ? Colors.white : Colors.black87),
          decoration: InputDecoration(
            hintText: 'Quel est l\'objectif de cette campagne ?',
            hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.grey),
            filled: true,
            fillColor: isDark ? ThemeService.darkCard : Colors.grey.shade50,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
          ),
        ),
        const SizedBox(height: 24),
        Text('Canal de diffusion', style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            border: Border.all(color: ThemeService.primaryColor, width: 2),
            borderRadius: BorderRadius.circular(12),
            color: isDark ? ThemeService.darkCard : Colors.white,
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
                    Text('Diffusion WhatsApp', style: TextStyle(fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                    Text('Modèles API officiels pour un meilleur taux de conversion.', style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey)),
                  ],
                ),
              ),
              Icon(Icons.check_circle, color: ThemeService.primaryColor),
            ],
          ),
        ),
        const SizedBox(height: 24),
        _buildMetaCampaignInfoBlock(isDark),
      ],
    );
  }

  Widget _buildMetaCampaignInfoBlock(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.blue.withValues(alpha: isDark ? 0.12 : 0.06),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.blue.withValues(alpha: 0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.info_outline_rounded, color: Colors.blue, size: 20),
              const SizedBox(width: 8),
              Text('À savoir sur l\'envoi via Meta',
                  style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
            ],
          ),
          const SizedBox(height: 12),
          _buildInfoBullet(
            isDark,
            'Chaque message envoyé via un modèle est facturé par Meta selon sa catégorie : Utilitaire, Marketing ou Authentification. Les tarifs varient selon le pays du destinataire.',
          ),
          _buildInfoBullet(
            isDark,
            'Les modèles Marketing coûtent généralement plus cher que les modèles Utilitaires, et ne sont envoyés qu\'aux contacts n\'ayant pas refusé ce type de message (opt-out).',
          ),
          _buildInfoBullet(
            isDark,
            'La qualité de votre numéro WhatsApp (évaluée par Meta selon les blocages/plaintes reçus) influence votre capacité d\'envoi quotidienne et peut limiter ou suspendre vos campagnes en cas de baisse.',
          ),
          _buildInfoBullet(
            isDark,
            'Un modèle rejeté, une mauvaise qualité, ou un dépassement de limite peuvent bloquer l\'envoi de la campagne — vérifiez le statut de votre modèle avant de continuer.',
          ),
        ],
      ),
    );
  }

  Widget _buildInfoBullet(bool isDark, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 5),
            child: Container(
              width: 4,
              height: 4,
              decoration: const BoxDecoration(color: Colors.blue, shape: BoxShape.circle),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(text,
                style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white70 : Colors.black87, height: 1.4)),
          ),
        ],
      ),
    );
  }

  Widget _buildStep2(bool isDark) {
    if (_isLoadingTemplates) return const Center(child: CircularProgressIndicator());
    if (_templates.isEmpty) return Center(child: Text("Aucun modèle approuvé disponible.", style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)));

    final filtered = _filteredTemplates;

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
                  Text('Configuration WhatsApp', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                  Text('Choisissez le modèle approuvé pour la campagne.', style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('${filtered.length} MODÈLES APPROUVÉS', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
            Row(
              children: [
                _buildCategoryFilterChip(null, 'Tous', isDark),
                const SizedBox(width: 6),
                _buildCategoryFilterChip('MARKETING', 'Marketing', isDark),
                const SizedBox(width: 6),
                _buildCategoryFilterChip('UTILITY', 'Utilitaire', isDark),
              ],
            ),
          ],
        ),
        const SizedBox(height: 16),
        if (filtered.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 24),
            child: Center(
              child: Text('Aucun modèle dans cette catégorie.',
                  style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
            ),
          )
        else
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
              childAspectRatio: 0.75,
            ),
            itemCount: filtered.length,
            itemBuilder: (context, index) {
              final t = filtered[index];
              final title = t['template_name'] ?? 'Inconnu';
              final lang = t['language'] ?? '';
              final category = (t['category'] ?? '').toString();
              final status = t['status'] ?? '';
              final catColor = _categoryColor(category);
              final catLabel = _categoryLabel(category);
              final isSelected = _selectedTemplate != null && _selectedTemplate!['_uid'] == t['_uid'];

              return GestureDetector(
                onTap: () {
                  setState(() => _selectedTemplate = t);
                  _parseTemplate(t);
                },
                child: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    border: Border.all(
                      color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white10 : Colors.grey.shade300),
                      width: isSelected ? 2 : 1,
                    ),
                    borderRadius: BorderRadius.circular(12),
                    color: isSelected
                        ? ThemeService.primaryColor.withValues(alpha: 0.08)
                        : (isDark ? ThemeService.darkCard : Colors.white),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(color: catColor.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(4)),
                            child: Text(catLabel, style: TextStyle(fontSize: 10, color: catColor, fontWeight: FontWeight.bold)),
                          ),
                          Row(
                            children: [
                              Icon(Icons.language, size: 12, color: isDark ? Colors.white54 : Colors.grey),
                              const SizedBox(width: 2),
                              Text(lang, style: TextStyle(fontSize: 10, color: isDark ? Colors.white54 : Colors.grey)),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(title,
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: isDark ? Colors.white : Colors.black),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis),
                      const SizedBox(height: 8),
                      Expanded(
                        child: Text(
                          _getTemplatePreviewText(t),
                          style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey.shade600),
                          maxLines: 4,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Icon(Icons.description, size: 14, color: isDark ? Colors.white54 : Colors.grey),
                          Text(status == 'APPROVED' ? 'Approuvé' : status,
                              style: TextStyle(
                                  color: status == 'APPROVED' ? Colors.green : (isDark ? Colors.white54 : Colors.grey),
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold)),
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

  /// Body text preview for a template card — was missing entirely, so
  /// every card in step 2 showed just the title with a blank gap below it.
  String _getTemplatePreviewText(Map<String, dynamic> template) {
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

  Widget _buildCategoryFilterChip(String? value, String label, bool isDark) {
    final selected = _categoryFilter == value;
    final color = value == null ? ThemeService.primaryColor : _categoryColor(value);
    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: () => setState(() => _categoryFilter = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: selected ? color.withValues(alpha: 0.15) : Colors.transparent,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: selected ? color : (isDark ? Colors.white24 : Colors.grey.shade300)),
        ),
        child: Text(label,
            style: TextStyle(
                fontSize: 11,
                fontWeight: selected ? FontWeight.bold : FontWeight.normal,
                color: selected ? color : (isDark ? Colors.white54 : Colors.grey.shade700))),
      ),
    );
  }

  Widget _buildStep3(bool isDark) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text('Variables du modèle', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
        Text('Associez vos variables aux données.', style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: isDark ? ThemeService.darkCard : Colors.grey.shade50, borderRadius: BorderRadius.circular(12)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('APERÇU DU MODÈLE', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(color: ThemeService.primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(4)),
                    child: Row(
                      children: [
                        Icon(Icons.visibility, size: 14, color: ThemeService.primaryColor),
                        const SizedBox(width: 4),
                        Text('Aperçu', style: TextStyle(fontSize: 12, color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(_getTemplatePreview(), style: TextStyle(fontSize: 14, color: isDark ? Colors.white : Colors.black87)),
            ],
          ),
        ),
        const SizedBox(height: 24),
        if (_requiresHeaderImage) ...[
          Text('IMAGE D\'EN-TÊTE', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          GestureDetector(
            onTap: _pickImage,
            child: Container(
              height: 120,
              width: double.infinity,
              decoration: BoxDecoration(
                border: Border.all(color: isDark ? const Color(0xFF334155) : Colors.grey.shade300, style: BorderStyle.solid),
                borderRadius: BorderRadius.circular(12),
                color: isDark ? ThemeService.darkCard : Colors.grey.shade50,
              ),
              child: _selectedHeaderImage != null
                  ? ClipRRect(borderRadius: BorderRadius.circular(12), child: Image.file(_selectedHeaderImage!, fit: BoxFit.cover))
                  : Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.add_photo_alternate, size: 40, color: ThemeService.primaryColor),
                        const SizedBox(height: 8),
                        Text('Ajouter une image', style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
                      ],
                    ),
            ),
          ),
          const SizedBox(height: 24),
        ],
        if (_requiresHeaderText) ...[
          Text('TEXTE D\'EN-TÊTE', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          _buildTagOrTextField(
            isDark: isDark,
            tagSelection: _headerFieldTagSelection,
            controller: _headerFieldController,
            onTagChanged: (val) => setState(() => _headerFieldTagSelection = val ?? 'custom'),
          ),
          const SizedBox(height: 24),
        ],
        if (_requiresHeaderVideo) ...[
          Text('VIDÉO D\'EN-TÊTE', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          _buildFilePickerBox(
            isDark: isDark,
            onTap: _pickVideo,
            icon: Icons.videocam_outlined,
            label: _selectedHeaderVideo != null
                ? _selectedHeaderVideo!.path.split(Platform.pathSeparator).last
                : 'Sélectionner une vidéo',
            hasFile: _selectedHeaderVideo != null,
          ),
          const SizedBox(height: 24),
        ],
        if (_requiresHeaderDocument) ...[
          Text('DOCUMENT D\'EN-TÊTE', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          _buildFilePickerBox(
            isDark: isDark,
            onTap: _pickDocument,
            icon: Icons.description_outlined,
            label: _selectedHeaderDocument != null
                ? _selectedHeaderDocument!.path.split(Platform.pathSeparator).last
                : 'Sélectionner un document',
            hasFile: _selectedHeaderDocument != null,
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _headerDocumentNameController,
            style: TextStyle(color: isDark ? Colors.white : Colors.black87),
            decoration: InputDecoration(
              hintText: 'Nom du fichier (ex: Facture.pdf)',
              hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.grey),
              filled: true,
              fillColor: isDark ? ThemeService.darkCard : Colors.grey.shade50,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
            ),
          ),
          const SizedBox(height: 24),
        ],
        if (_requiresLocation) ...[
          Text('LOCALISATION', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(child: _buildSimpleTextField(isDark, _locationLatController, 'Latitude')),
              const SizedBox(width: 12),
              Expanded(child: _buildSimpleTextField(isDark, _locationLngController, 'Longitude')),
            ],
          ),
          const SizedBox(height: 12),
          _buildSimpleTextField(isDark, _locationNameController, 'Nom du lieu'),
          const SizedBox(height: 12),
          _buildSimpleTextField(isDark, _locationAddressController, 'Adresse'),
          const SizedBox(height: 24),
        ],
        if (_bodyVariables.isNotEmpty) ...[
          Text('VARIABLES DU CORPS', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          Text('Choisissez un tag prédéfini ou saisissez une valeur fixe.', style: TextStyle(color: isDark ? Colors.white54 : Colors.grey, fontSize: 12)),
          const SizedBox(height: 12),
          ..._bodyVariables.map((v) {
            return Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(border: Border.all(color: isDark ? const Color(0xFF334155) : Colors.grey.shade200), borderRadius: BorderRadius.circular(12)),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('VARIABLE: $v', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
                  const SizedBox(height: 12),
                  _buildTagOrTextField(
                    isDark: isDark,
                    tagSelection: _variableTagSelection[v] ?? 'custom',
                    controller: _variableControllers[v]!,
                    onTagChanged: (val) => setState(() => _variableTagSelection[v] = val ?? 'custom'),
                  ),
                ],
              ),
            );
          }),
        ] else if (!_requiresHeaderText &&
            !_requiresHeaderVideo &&
            !_requiresHeaderDocument &&
            !_requiresLocation &&
            _dynamicUrlButtonControllers.isEmpty &&
            !_requiresCopyCode) ...[
          Center(child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Text("Aucune variable dans ce modèle.", style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
          )),
        ],
        if (_dynamicUrlButtonControllers.isNotEmpty || _requiresCopyCode) ...[
          const SizedBox(height: 8),
          Text('BOUTONS', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          ..._dynamicUrlButtonControllers.entries.map((entry) => Padding(
                padding: const EdgeInsets.only(bottom: 16.0),
                child: _buildSimpleTextField(
                    isDark, entry.value, '${_dynamicUrlButtonLabels[entry.key]} — suffixe de l\'URL'),
              )),
          if (_requiresCopyCode) _buildSimpleTextField(isDark, _copyCodeController, 'Code promo'),
        ],
      ],
    );
  }

  /// Dropdown to pick a predefined contact tag, or "custom" to reveal a free
  /// text field. Shared by body variables and the header text variable.
  Widget _buildTagOrTextField({
    required bool isDark,
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
          style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 14),
          dropdownColor: isDark ? ThemeService.darkCard : Colors.white,
          decoration: InputDecoration(
            filled: true,
            fillColor: isDark ? ThemeService.darkCard : Colors.grey.shade50,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
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
            style: TextStyle(color: isDark ? Colors.white : Colors.black87),
            decoration: InputDecoration(
              hintText: 'Valeur fixe...',
              hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.grey),
              filled: true,
              fillColor: isDark ? ThemeService.darkCard : Colors.grey.shade50,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildSimpleTextField(bool isDark, TextEditingController controller, String hint) {
    return TextField(
      controller: controller,
      style: TextStyle(color: isDark ? Colors.white : Colors.black87),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.grey),
        filled: true,
        fillColor: isDark ? ThemeService.darkCard : Colors.grey.shade50,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
      ),
    );
  }

  Widget _buildFilePickerBox({
    required bool isDark,
    required VoidCallback onTap,
    required IconData icon,
    required String label,
    required bool hasFile,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
        decoration: BoxDecoration(
          color: isDark ? ThemeService.darkCard : Colors.grey.shade50,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: hasFile ? ThemeService.primaryColor : (isDark ? const Color(0xFF334155) : Colors.grey.shade300),
          ),
        ),
        child: Row(
          children: [
            Icon(icon, size: 22, color: hasFile ? ThemeService.primaryColor : Colors.grey),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                label,
                style: TextStyle(color: hasFile ? (isDark ? Colors.white : Colors.black87) : Colors.grey),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (hasFile) Icon(Icons.check_circle, color: ThemeService.primaryColor, size: 18),
          ],
        ),
      ),
    );
  }

  Widget _buildStep4(bool isDark) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text('Choisissez qui recevra le message de cette campagne.', style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
        const SizedBox(height: 16),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          crossAxisSpacing: 8,
          mainAxisSpacing: 8,
          childAspectRatio: 1.2,
          children: [
            _buildAudienceCard('all', 'TOUS LES CONTACTS', 'Envoyer à tout le monde', Icons.contact_mail, isDark),
            _buildAudienceCard('specific', 'CONTACTS SPÉCIFIQUES', 'Sélectionner des contacts', Icons.person, isDark),
            _buildAudienceCard('audiences', 'AUDIENCE', 'Segments enregistrés', Icons.pie_chart, isDark),
            _buildAudienceCard('groups', 'GROUPES & ÉTIQUETTES', 'Cibler par groupe', Icons.local_offer, isDark),
          ],
        ),
        const SizedBox(height: 24),
        if (_audienceMode == 'all') ...[
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: ThemeService.primaryColor.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Icon(Icons.groups_rounded, color: ThemeService.primaryColor),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Cette campagne ciblera tous vos contacts',
                          style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                      const SizedBox(height: 4),
                      Text(
                        _isLoadingAudienceData
                            ? 'Chargement du nombre de contacts...'
                            : '${_contacts.length} contact${_contacts.length > 1 ? 's' : ''} ciblé${_contacts.length > 1 ? 's' : ''} au total',
                        style: TextStyle(fontSize: 13, color: ThemeService.primaryColor, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ] else if (_audienceMode == 'specific') ...[
          TextField(
            controller: _searchContactsController,
            style: TextStyle(color: isDark ? Colors.white : Colors.black87),
            decoration: InputDecoration(
              hintText: 'Rechercher un contact...',
              hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.grey),
              prefixIcon: const Icon(Icons.search),
              filled: true,
              fillColor: isDark ? ThemeService.darkCard : Colors.grey.shade50,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
            ),
          ),
          const SizedBox(height: 8),
          if (_isLoadingAudienceData)
            const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
          else ...[
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _filteredContacts.length,
              itemBuilder: (context, index) {
                final c = _filteredContacts[index];
                return CheckboxListTile(
                  title: Text(c.name, style: TextStyle(color: isDark ? Colors.white : Colors.black87)),
                  subtitle: Text(c.phoneNumber, style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
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
            if (_selectedContactIds.isNotEmpty) ...[
              const SizedBox(height: 8),
              _buildTargetedCountBar(isDark, _selectedContactIds.length),
            ],
          ],
        ] else if (_audienceMode == 'audiences') ...[
          Align(
            alignment: Alignment.centerRight,
            child: TextButton.icon(
              onPressed: _openCreateAudience,
              icon: const Icon(Icons.add_circle_outline, size: 18),
              label: const Text('Créer une audience'),
              style: TextButton.styleFrom(foregroundColor: ThemeService.primaryColor),
            ),
          ),
          if (_audiences.isEmpty) Text("Aucune audience enregistrée disponible.", style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
          ..._audiences.map((a) {
            return RadioListTile<String>(
              title: Text(a['title'] ?? 'Sans nom', style: TextStyle(color: isDark ? Colors.white : Colors.black87)),
              subtitle: Text("Créé le ${formatDate(a['created_at'])}", style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
              value: a['_uid'],
              groupValue: _selectedAudienceUid,
              activeColor: ThemeService.primaryColor,
              onChanged: (val) {
                setState(() => _selectedAudienceUid = val);
              },
            );
          }),
        ] else if (_audienceMode == 'groups') ...[
          if (_isLoadingAudienceData)
            const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
          else ...[
            if (_groups.isEmpty && _labels.isEmpty)
              Text("Aucun groupe ni étiquette disponible.", style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)),
            if (_groups.isNotEmpty) ...[
              Text('GROUPES', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
              const SizedBox(height: 8),
              ..._groups.map((g) {
                final uid = g['_id']?.toString() ?? g['_uid']?.toString() ?? '';
                final count = g['total_contacts'] ?? 0;
                final selected = _selectedGroupIds.contains(uid);
                return _buildSelectableTile(
                  isDark: isDark,
                  selected: selected,
                  leading: Icon(Icons.folder_rounded, color: selected ? ThemeService.primaryColor : Colors.grey, size: 22),
                  title: g['title'] ?? 'Sans nom',
                  subtitle: (g['description'] ?? '').toString().isNotEmpty ? g['description'] : null,
                  count: count,
                  onTap: () {
                    setState(() {
                      if (selected) {
                        _selectedGroupIds.remove(uid);
                      } else {
                        _selectedGroupIds.add(uid);
                      }
                    });
                  },
                );
              }),
              const SizedBox(height: 16),
            ],
            if (_labels.isNotEmpty) ...[
              Text('ÉTIQUETTES', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
              const SizedBox(height: 8),
              ..._labels.map((l) {
                final id = l['_id']?.toString() ?? '';
                final count = l['total_contacts'] ?? 0;
                final selected = _selectedLabelIds.contains(id);
                final bgColor = _parseHexColor(l['bg_color']) ?? ThemeService.primaryColor;
                return _buildSelectableTile(
                  isDark: isDark,
                  selected: selected,
                  leading: Container(
                    width: 14,
                    height: 14,
                    decoration: BoxDecoration(color: bgColor, shape: BoxShape.circle),
                  ),
                  title: l['title'] ?? 'Sans nom',
                  count: count,
                  onTap: () {
                    setState(() {
                      if (selected) {
                        _selectedLabelIds.remove(id);
                      } else {
                        _selectedLabelIds.add(id);
                      }
                    });
                  },
                );
              }),
            ],
            if (_selectedGroupIds.isNotEmpty || _selectedLabelIds.isNotEmpty) ...[
              const SizedBox(height: 8),
              _buildTargetedCountBar(
                isDark,
                _groups.where((g) => _selectedGroupIds.contains(g['_id']?.toString() ?? g['_uid']?.toString() ?? '')).fold<int>(0, (sum, g) => sum + ((g['total_contacts'] ?? 0) as int)) +
                    _labels.where((l) => _selectedLabelIds.contains(l['_id']?.toString() ?? '')).fold<int>(0, (sum, l) => sum + ((l['total_contacts'] ?? 0) as int)),
                approximate: true,
              ),
            ],
          ],
        ]
      ],
    );
  }

  Color? _parseHexColor(String? hex) {
    if (hex == null || hex.isEmpty) return null;
    var value = hex.replaceAll('#', '');
    if (value.length == 6) value = 'FF$value';
    return Color(int.tryParse(value, radix: 16) ?? 0xFF10B981);
  }

  Widget _buildTargetedCountBar(bool isDark, int count, {bool approximate = false}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: ThemeService.primaryColor.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        children: [
          Icon(Icons.groups_rounded, color: ThemeService.primaryColor, size: 18),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              approximate
                  ? '~$count contact${count > 1 ? 's' : ''} ciblé${count > 1 ? 's' : ''} (les doublons entre groupes/étiquettes ne sont comptés qu\'une fois à l\'envoi)'
                  : '$count contact${count > 1 ? 's' : ''} ciblé${count > 1 ? 's' : ''}',
              style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: ThemeService.primaryColor),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSelectableTile({
    required bool isDark,
    required bool selected,
    required Widget leading,
    required String title,
    String? subtitle,
    required int count,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: selected ? ThemeService.primaryColor.withValues(alpha: 0.08) : (isDark ? ThemeService.darkCard : Colors.white),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: selected ? ThemeService.primaryColor : (isDark ? const Color(0xFF334155) : Colors.grey.shade300),
            width: selected ? 1.5 : 1,
          ),
        ),
        child: Row(
          children: [
            leading,
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: TextStyle(fontWeight: FontWeight.w600, color: isDark ? Colors.white : Colors.black87)),
                  if (subtitle != null)
                    Text(subtitle, style: TextStyle(fontSize: 11.5, color: isDark ? Colors.white54 : Colors.grey)),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: (isDark ? Colors.white : Colors.black).withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text('$count', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: isDark ? Colors.white70 : Colors.black54)),
            ),
            const SizedBox(width: 8),
            Icon(
              selected ? Icons.check_circle_rounded : Icons.circle_outlined,
              color: selected ? ThemeService.primaryColor : Colors.grey,
              size: 20,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAudienceCard(String mode, String title, String subtitle, IconData icon, bool isDark) {
    final isSelected = _audienceMode == mode;
    return GestureDetector(
      onTap: () => setState(() => _audienceMode = mode),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          border: Border.all(color: isSelected ? ThemeService.primaryColor : (isDark ? const Color(0xFF334155) : Colors.grey.shade300), width: isSelected ? 2 : 1),
          borderRadius: BorderRadius.circular(12),
          color: isSelected ? ThemeService.primaryColor.withValues(alpha: 0.08) : (isDark ? ThemeService.darkCard : Colors.white),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white54 : Colors.grey), size: 28),
            const SizedBox(height: 8),
            Text(title, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white : Colors.black87)), textAlign: TextAlign.center),
            const SizedBox(height: 4),
            Text(subtitle, style: TextStyle(fontSize: 10, color: isDark ? Colors.white54 : Colors.grey), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildStep5(bool isDark) {
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
                  Text('Lancement & Programmation', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                  Text('Choisissez quand envoyer votre campagne.', style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey)),
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
              border: Border.all(color: _sendImmediately ? ThemeService.primaryColor : (isDark ? const Color(0xFF334155) : Colors.grey.shade300), width: _sendImmediately ? 2 : 1),
              borderRadius: BorderRadius.circular(12),
              color: _sendImmediately ? ThemeService.primaryColor.withValues(alpha: 0.08) : (isDark ? ThemeService.darkCard : Colors.white),
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
                      Text('Envoyer immédiatement', style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                      Text('Traiter et envoyer la campagne immédiatement après le lancement.', style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey)),
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
            border: Border.all(color: !_sendImmediately ? ThemeService.primaryColor : (isDark ? const Color(0xFF334155) : Colors.grey.shade300), width: !_sendImmediately ? 2 : 1),
            borderRadius: BorderRadius.circular(12),
            color: !_sendImmediately ? ThemeService.primaryColor.withValues(alpha: 0.08) : (isDark ? ThemeService.darkCard : Colors.white),
          ),
          child: Column(
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(color: isDark ? Colors.white12 : Colors.grey.shade200, borderRadius: BorderRadius.circular(8)),
                    child: Icon(Icons.calendar_today, color: isDark ? Colors.white70 : Colors.black54, size: 20),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Programmer pour plus tard', style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                        Text('Choisir une date et une heure précises', style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey)),
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
                          backgroundColor: isDark ? const Color(0xFF1E293B) : Colors.white,
                          foregroundColor: isDark ? Colors.white : Colors.black87,
                          side: BorderSide(color: isDark ? const Color(0xFF334155) : Colors.grey.shade300),
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
                          backgroundColor: isDark ? const Color(0xFF1E293B) : Colors.white,
                          foregroundColor: isDark ? Colors.white : Colors.black87,
                          side: BorderSide(color: isDark ? const Color(0xFF334155) : Colors.grey.shade300),
                        ),
                        icon: const Icon(Icons.access_time),
                        label: Text(_scheduledTime != null ? _scheduledTime!.format(context) : 'Heure'),
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
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Prêt à lancer ?', style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                    Text('Veuillez vérifier toutes les étapes avant de continuer. Les campagnes ne peuvent plus être modifiées une fois en cours d\'envoi.', style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildBottomBar(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : Colors.white,
        border: Border(top: BorderSide(color: isDark ? const Color(0xFF334155) : Colors.grey.shade200)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          TextButton(
            onPressed: _currentStep == 0 ? () => Navigator.pop(context) : _prevStep,
            style: TextButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              backgroundColor: isDark ? const Color(0xFF1E293B) : Colors.grey.shade100,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: Text(_currentStep == 0 ? 'Annuler' : 'Retour', style: TextStyle(color: isDark ? Colors.white : Colors.black87)),
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
                  : const Text('Publier', style: TextStyle(color: Colors.white)),
            )
          else
            ElevatedButton(
              onPressed: _nextStep,
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
                backgroundColor: ThemeService.primaryColor,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: const Text('Étape suivante', style: TextStyle(color: Colors.white)),
            ),
        ],
      ),
    );
  }
}

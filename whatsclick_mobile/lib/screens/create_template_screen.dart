import 'dart:io';
import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class CreateTemplateScreen extends StatefulWidget {
  const CreateTemplateScreen({super.key});

  @override
  State<CreateTemplateScreen> createState() => _CreateTemplateScreenState();
}

class _CreateTemplateScreenState extends State<CreateTemplateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _bodyController = TextEditingController();
  final _footerController = TextEditingController();
  final _headerController = TextEditingController();
  final _bodyFocusNode = FocusNode();

  String _selectedCategory = 'MARKETING';
  String _selectedLanguage = 'fr';
  String _headerType = 'NONE'; // NONE, TEXT, MEDIA

  // Media header
  String _mediaHeaderType = 'image'; // image, video, document
  File? _selectedMediaFile;

  // Interactive Buttons — Meta allows up to 10 total, max 2 URL, max 1 phone
  // number (matches the web dashboard's template wizard).
  static const int _maxButtons = 10;
  static const int _maxUrlButtons = 2;
  static const int _maxPhoneButtons = 1;
  final List<Map<String, dynamic>> _messageButtons = [];

  bool _isSubmitting = false;

  final List<Map<String, String>> _languages = [
    {'code': 'fr', 'label': '🇫🇷 Français'},
    {'code': 'en', 'label': '🇺🇸 English (US)'},
    {'code': 'ar', 'label': '🇦🇪 العربية'},
    {'code': 'es', 'label': '🇪🇸 Español'},
    {'code': 'pt_BR', 'label': '🇧🇷 Português (BR)'},
  ];

  @override
  void dispose() {
    _nameController.dispose();
    _bodyController.dispose();
    _footerController.dispose();
    _headerController.dispose();
    _bodyFocusNode.dispose();
    super.dispose();
  }

  Future<void> _pickMediaFile() async {
    FileType type;
    List<String>? extensions;
    switch (_mediaHeaderType) {
      case 'video':
        type = FileType.video;
        break;
      case 'document':
        type = FileType.custom;
        extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        break;
      default:
        type = FileType.image;
    }
    final result = await FilePicker.platform.pickFiles(type: type, allowedExtensions: extensions);
    if (result != null && result.files.single.path != null) {
      setState(() => _selectedMediaFile = File(result.files.single.path!));
    }
  }

  // WhatsApp formatting: wraps the current selection (or inserts markers at
  // the cursor if nothing is selected) with the given markdown characters.
  void _applyFormatting(String marker) {
    final text = _bodyController.text;
    final selection = _bodyController.selection;
    final start = selection.start > -1 ? selection.start : text.length;
    final end = selection.end > -1 ? selection.end : text.length;
    final selectedText = text.substring(start, end);

    final newText = text.replaceRange(start, end, '$marker$selectedText$marker');
    setState(() {
      _bodyController.text = newText;
      _bodyController.selection = selectedText.isEmpty
          ? TextSelection.collapsed(offset: start + marker.length)
          : TextSelection(baseOffset: start, extentOffset: end + marker.length * 2);
    });
    _bodyFocusNode.requestFocus();
  }

  void _insertVariable() {
    final text = _bodyController.text;
    final selection = _bodyController.selection;
    // Count existing variables to know which number to insert
    final regex = RegExp(r'\{\{(\d+)\}\}');
    final matches = regex.allMatches(text);
    int nextVar = matches.length + 1;

    final newText = text.replaceRange(
      selection.start > -1 ? selection.start : text.length,
      selection.end > -1 ? selection.end : text.length,
      '{{$nextVar}}',
    );

    setState(() {
      _bodyController.text = newText;
      _bodyController.selection = TextSelection.collapsed(
        offset: (selection.start > -1 ? selection.start : text.length) + 5,
      );
    });
  }

  void _insertStopKeyword() {
    final text = _footerController.text;
    final newText = '$text${text.isEmpty ? '' : ' '}Désinscription: STOP';
    setState(() {
      _footerController.text = newText;
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_bodyController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Le corps du message est requis.'), backgroundColor: Colors.red),
      );
      return;
    }
    if (_headerType == 'TEXT' && _headerController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Le texte d'en-tête est requis."), backgroundColor: Colors.red),
      );
      return;
    }
    if (_headerType == 'MEDIA' && _selectedMediaFile == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Veuillez sélectionner un fichier pour l'en-tête média."), backgroundColor: Colors.red),
      );
      return;
    }
    for (var btn in _messageButtons) {
      if ((btn['text'] ?? '').toString().trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Veuillez remplir le texte de chaque bouton.'), backgroundColor: Colors.red),
        );
        return;
      }
      if (btn['type'] == 'URL_BUTTON' && (btn['url'] ?? '').toString().trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Veuillez remplir l'URL de chaque bouton lien."), backgroundColor: Colors.red),
        );
        return;
      }
      if (btn['type'] == 'PHONE_NUMBER' && (btn['phone_number'] ?? '').toString().trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Veuillez remplir le numéro de chaque bouton téléphone.'), backgroundColor: Colors.red),
        );
        return;
      }
    }

    setState(() => _isSubmitting = true);

    String? uploadedMediaFileName;
    if (_headerType == 'MEDIA' && _selectedMediaFile != null) {
      uploadedMediaFileName =
          await ApiService().uploadTempMedia(_selectedMediaFile!, 'whatsapp_$_mediaHeaderType');
      if (uploadedMediaFileName == null) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Erreur lors de l'upload du fichier d'en-tête."), backgroundColor: Colors.red),
        );
        return;
      }
    }

    final payload = {
      'template_name': _nameController.text.trim().toLowerCase().replaceAll(' ', '_'),
      'language_code': _selectedLanguage,
      'category': _selectedCategory,
      'template_type': 'header', // required for the backend API
      'template_body': _bodyController.text.trim(),
      if (_footerController.text.trim().isNotEmpty) 'template_footer': _footerController.text.trim(),
      if (_headerType == 'TEXT' && _headerController.text.trim().isNotEmpty) ...{
        'media_header_type': 'text',
        'header_text_body': _headerController.text.trim(),
      },
      if (_headerType == 'MEDIA' && uploadedMediaFileName != null) ...{
        'media_header_type': _mediaHeaderType,
        'uploaded_media_file_name': uploadedMediaFileName,
      },
      if (_messageButtons.isNotEmpty) 'message_buttons': _messageButtons,
    };

    final result = await ApiService().createTemplate(payload);
    if (mounted) {
      setState(() => _isSubmitting = false);
      if (result != null && result['reaction'] == 1) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Modèle créé avec succès !'), backgroundColor: Colors.green),
        );
        Navigator.pop(context, true);
      } else {
        final msg = result?['message'] ?? 'Impossible de créer le modèle.';
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    final bgColor = isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB);
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final borderColor = isDark ? Colors.white10 : Colors.grey.shade200;
    final textColor = isDark ? Colors.white : const Color(0xFF111827);
    final subtitleColor = isDark ? Colors.white54 : const Color(0xFF6B7280);

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        backgroundColor: cardColor,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, color: textColor, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Créer un modèle', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 18)),
            Text('Créez un modèle personnalisé pour vos campagnes', style: TextStyle(color: subtitleColor, fontSize: 12)),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: CircleAvatar(
              backgroundColor: ThemeService.primaryColor.withValues(alpha: 0.1),
              child: Icon(Icons.smart_toy_rounded, color: ThemeService.primaryColor),
            ),
          )
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Section 1: Name, Language, Category
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Nom du modèle', textColor),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _nameController,
                    decoration: _buildInputDecoration('ex. message_bienvenue', isDark),
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) return 'Nom requis';
                      if (!RegExp(r'^[a-z0-9_]+$').hasMatch(v.trim())) return 'Lettres minuscules, chiffres et _ uniquement';
                      return null;
                    },
                  ),
                  const SizedBox(height: 6),
                  Text('Lettres minuscules, chiffres et tirets bas uniquement.', style: TextStyle(fontSize: 12, color: subtitleColor)),

                  const SizedBox(height: 24),

                  _buildSectionTitle('Langue du modèle', textColor),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    initialValue: _selectedLanguage,
                    decoration: _buildInputDecoration('', isDark),
                    icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                    items: _languages.map((l) => DropdownMenuItem(value: l['code'], child: Text(l['label']!))).toList(),
                    onChanged: (v) => setState(() => _selectedLanguage = v!),
                  ),
                  const SizedBox(height: 6),
                  Text('La langue dans laquelle ce modèle est rédigé.', style: TextStyle(fontSize: 12, color: subtitleColor)),

                  const SizedBox(height: 24),

                  _buildSectionTitle('Catégorie du modèle', textColor),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(child: _buildCategoryCard('UTILITY', Icons.notifications_active_outlined, 'Utilitaire', isDark)),
                      const SizedBox(width: 12),
                      Expanded(child: _buildCategoryCard('MARKETING', Icons.grid_view_rounded, 'Marketing', isDark)),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text('Classez votre modèle selon son objectif principal.', style: TextStyle(fontSize: 12, color: subtitleColor)),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 2: Header
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('En-tête du modèle', textColor),
                  const SizedBox(height: 6),
                  Text('Ajoutez un en-tête optionnel pour mettre votre modèle en valeur.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      _buildToggleButton('NONE', 'AUCUN', _headerType, (v) => setState(() => _headerType = v), isDark),
                      const SizedBox(width: 12),
                      _buildToggleButton('TEXT', 'TEXTE', _headerType, (v) => setState(() => _headerType = v), isDark),
                      const SizedBox(width: 12),
                      _buildToggleButton('MEDIA', 'MÉDIA', _headerType, (v) => setState(() => _headerType = v), isDark),
                    ],
                  ),
                  if (_headerType == 'TEXT') ...[
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _headerController,
                      maxLength: 60,
                      decoration: _buildInputDecoration('Saisissez le texte d\'en-tête...', isDark),
                    ),
                  ],
                  if (_headerType == 'MEDIA') ...[
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        _buildToggleButton('image', 'IMAGE', _mediaHeaderType,
                            (v) => setState(() { _mediaHeaderType = v; _selectedMediaFile = null; }), isDark),
                        const SizedBox(width: 12),
                        _buildToggleButton('video', 'VIDÉO', _mediaHeaderType,
                            (v) => setState(() { _mediaHeaderType = v; _selectedMediaFile = null; }), isDark),
                        const SizedBox(width: 12),
                        _buildToggleButton('document', 'DOCUMENT', _mediaHeaderType,
                            (v) => setState(() { _mediaHeaderType = v; _selectedMediaFile = null; }), isDark),
                      ],
                    ),
                    const SizedBox(height: 12),
                    InkWell(
                      onTap: _pickMediaFile,
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
                        decoration: BoxDecoration(
                          color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: _selectedMediaFile != null ? ThemeService.primaryColor : borderColor,
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              _mediaHeaderType == 'image'
                                  ? Icons.image_outlined
                                  : _mediaHeaderType == 'video'
                                      ? Icons.videocam_outlined
                                      : Icons.description_outlined,
                              color: _selectedMediaFile != null ? ThemeService.primaryColor : subtitleColor,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                _selectedMediaFile != null
                                    ? _selectedMediaFile!.path.split(Platform.pathSeparator).last
                                    : 'Sélectionner un fichier...',
                                style: TextStyle(
                                  color: _selectedMediaFile != null ? textColor : subtitleColor,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            if (_selectedMediaFile != null)
                              Icon(Icons.check_circle, color: ThemeService.primaryColor, size: 18),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Ce fichier sert d\'exemple à Meta pour la validation du modèle.',
                      style: TextStyle(fontSize: 12, color: subtitleColor),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 3: Body
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Corps du message', textColor),
                  const SizedBox(height: 6),
                  Text('C\'est le contenu principal de votre message. Utilisez {{1}} pour ajouter des variables.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 4),
                  Text('Les variables doivent être des nombres entiers entourés de doubles accolades (par exemple {{1}}, {{2}}).', style: TextStyle(fontSize: 12, color: subtitleColor)),
                  const SizedBox(height: 16),
                  
                  // Rich Text Editor Simulation
                  Container(
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: borderColor),
                    ),
                    child: Column(
                      children: [
                        // Text Area
                        TextFormField(
                          controller: _bodyController,
                          focusNode: _bodyFocusNode,
                          maxLines: 6,
                          maxLength: 1024, // Meta's real body length limit
                          buildCounter: (context, {required currentLength, required isFocused, maxLength}) => null,
                          onChanged: (_) => setState(() {}),
                          decoration: InputDecoration(
                            hintText: 'Tapez votre message ici...',
                            hintStyle: TextStyle(color: subtitleColor),
                            border: InputBorder.none,
                            contentPadding: const EdgeInsets.all(16),
                          ),
                        ),
                        // Bottom Toolbar
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            border: Border(top: BorderSide(color: borderColor)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        width: 8,
                                        height: 8,
                                        decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle),
                                      ),
                                      const SizedBox(width: 8),
                                      Text('ÉDITEUR DE TEXTE', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: subtitleColor)),
                                    ],
                                  ),
                                  InkWell(
                                    onTap: _insertVariable,
                                    borderRadius: BorderRadius.circular(8),
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                      decoration: BoxDecoration(
                                        border: Border.all(color: borderColor),
                                        borderRadius: BorderRadius.circular(8),
                                        color: isDark ? const Color(0xFF1E293B) : Colors.white,
                                      ),
                                      child: Row(
                                        children: [
                                          Icon(Icons.add_rounded, size: 16, color: ThemeService.primaryColor),
                                          const SizedBox(width: 4),
                                          Text('AJOUTER VARIABLE', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              // Formatting shortcuts, bottom-right: WhatsApp
                              // markdown — *bold*, _italic_, ~strikethrough~,
                              // ```monospace```.
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  _buildFormatButton(Icons.format_bold_rounded, '*', textColor, isDark),
                                  const SizedBox(width: 4),
                                  _buildFormatButton(Icons.format_italic_rounded, '_', textColor, isDark),
                                  const SizedBox(width: 4),
                                  _buildFormatButton(Icons.format_strikethrough_rounded, '~', textColor, isDark),
                                  const SizedBox(width: 4),
                                  _buildFormatButton(Icons.code_rounded, '```', textColor, isDark),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Exemple : Bonjour {{1}}, bienvenue dans votre boutique. ${_bodyController.text.length} / 1024',
                    style: TextStyle(fontSize: 11, color: subtitleColor),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 4: Footer
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Pied de page du modèle (optionnel)', textColor),
                  const SizedBox(height: 6),
                  Text('Ajoutez un court texte de pied de page en bas de votre message.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _footerController,
                    maxLength: 60,
                    decoration: _buildInputDecoration('Saisissez le texte du pied de page...', isDark),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Text('INSÉRER UN MOT-CLÉ DE DÉSINSCRIPTION :', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: textColor)),
                      const SizedBox(width: 8),
                      InkWell(
                        onTap: _insertStopKeyword,
                        borderRadius: BorderRadius.circular(16),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            border: Border.all(color: ThemeService.primaryColor),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Text('+STOP', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Text('Recommandé : ajoutez un message de désinscription dans le pied de page pour rester conforme.\nLe pied de page est limité à 60 caractères.', style: TextStyle(fontSize: 12, color: subtitleColor, height: 1.5)),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 5: Interactive Buttons (Real)
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Boutons interactifs', textColor),
                  const SizedBox(height: 6),
                  Text(
                    'Ajoutez jusqu\'à 10 boutons (max 2 liens URL, max 1 appel téléphonique) — règles Meta.',
                    style: TextStyle(fontSize: 13, color: subtitleColor),
                  ),
                  const SizedBox(height: 16),
                  
                  ..._messageButtons.asMap().entries.map((entry) {
                    int index = entry.key;
                    Map<String, dynamic> btn = entry.value;
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: isDark ? Colors.white10 : Colors.black.withValues(alpha: 0.03),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: borderColor),
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: DropdownButtonFormField<String>(
                                  initialValue: btn['type'],
                                  decoration: _buildInputDecoration('Type', isDark).copyWith(contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                                  items: const [
                                    DropdownMenuItem(value: 'QUICK_REPLY', child: Text('Réponse Rapide')),
                                    DropdownMenuItem(value: 'URL_BUTTON', child: Text('Lien URL')),
                                    DropdownMenuItem(value: 'PHONE_NUMBER', child: Text('Appel Téléphonique')),
                                  ],
                                  onChanged: (v) {
                                    if (v == 'URL_BUTTON' && btn['type'] != 'URL_BUTTON' && _urlButtonCount() >= _maxUrlButtons) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(content: Text('Maximum $_maxUrlButtons boutons lien URL (règle Meta).')),
                                      );
                                      return;
                                    }
                                    if (v == 'PHONE_NUMBER' && btn['type'] != 'PHONE_NUMBER' && _phoneButtonCount() >= _maxPhoneButtons) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(content: Text('Maximum $_maxPhoneButtons bouton appel téléphonique (règle Meta).')),
                                      );
                                      return;
                                    }
                                    setState(() {
                                      btn['type'] = v!;
                                      if (v == 'QUICK_REPLY') {
                                        btn.remove('url');
                                        btn.remove('phone_number');
                                      } else if (v == 'URL_BUTTON') {
                                        btn['url'] = '';
                                        btn.remove('phone_number');
                                      } else if (v == 'PHONE_NUMBER') {
                                        btn['phone_number'] = '';
                                        btn.remove('url');
                                      }
                                    });
                                  },
                                ),
                              ),
                              IconButton(
                                icon: const Icon(Icons.delete_outline, color: Colors.red),
                                onPressed: () => setState(() => _messageButtons.removeAt(index)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          TextFormField(
                            initialValue: btn['text'],
                            decoration: _buildInputDecoration('Texte du bouton (ex: Oui, Visiter...)', isDark).copyWith(contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                            maxLength: 25,
                            onChanged: (v) => btn['text'] = v,
                          ),
                          if (btn['type'] == 'URL_BUTTON') ...[
                            const SizedBox(height: 8),
                            TextFormField(
                              initialValue: btn['url'],
                              decoration: _buildInputDecoration('https://...', isDark).copyWith(contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                              onChanged: (v) => btn['url'] = v,
                            ),
                          ],
                          if (btn['type'] == 'PHONE_NUMBER') ...[
                            const SizedBox(height: 8),
                            TextFormField(
                              initialValue: btn['phone_number'],
                              decoration: _buildInputDecoration('+1234567890', isDark).copyWith(contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                              keyboardType: TextInputType.phone,
                              onChanged: (v) => btn['phone_number'] = v,
                            ),
                          ],
                        ],
                      ),
                    );
                  }).toList(),

                  if (_messageButtons.length < _maxButtons)
                    OutlinedButton.icon(
                      onPressed: () {
                        setState(() {
                          _messageButtons.add({'type': 'QUICK_REPLY', 'text': ''});
                        });
                      },
                      icon: const Icon(Icons.add),
                      label: const Text('Ajouter un bouton'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: ThemeService.primaryColor,
                        side: BorderSide(color: ThemeService.primaryColor),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    )
                  else
                    Text(
                      'Nombre maximum de boutons atteint (10).',
                      style: TextStyle(fontSize: 12, color: subtitleColor),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 100), // Space for bottom buttons
          ],
        ),
      ),
      bottomSheet: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E293B) : Colors.white,
          border: Border(top: BorderSide(color: borderColor)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, -4),
            )
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => Navigator.pop(context),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6),
                    side: BorderSide.none,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: Text('Annuler', style: TextStyle(color: textColor, fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: ThemeService.primaryColor,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 0,
                  ),
                  child: _isSubmitting 
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Soumettre le modèle', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  int _urlButtonCount() => _messageButtons.where((b) => b['type'] == 'URL_BUTTON').length;
  int _phoneButtonCount() => _messageButtons.where((b) => b['type'] == 'PHONE_NUMBER').length;

  Widget _buildFormatButton(IconData icon, String marker, Color textColor, bool isDark) {
    return InkWell(
      onTap: () => _applyFormatting(marker),
      borderRadius: BorderRadius.circular(6),
      child: Padding(
        padding: const EdgeInsets.all(6),
        child: Icon(icon, color: textColor, size: 18),
      ),
    );
  }

  Widget _buildCardContainer(bool isDark, Color cardColor, Color borderColor, Widget child) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
      ),
      child: child,
    );
  }

  Widget _buildSectionTitle(String title, Color textColor) {
    return Text(
      title,
      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor),
    );
  }

  InputDecoration _buildInputDecoration(String hint, bool isDark) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: isDark ? Colors.white54 : const Color(0xFF9CA3AF)),
      filled: true,
      fillColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF3F4F6),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: ThemeService.primaryColor.withValues(alpha: 0.5)),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
    );
  }

  Widget _buildCategoryCard(String value, IconData icon, String label, bool isDark) {
    final isSelected = _selectedCategory == value;
    final borderColor = isDark ? Colors.white10 : Colors.grey.shade200;

    return InkWell(
      onTap: () => setState(() => _selectedCategory = value),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF0F172A) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? ThemeService.primaryColor : borderColor,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: isSelected ? ThemeService.primaryColor.withValues(alpha: 0.1) : (isDark ? const Color(0xFF1E293B) : const Color(0xFFF3F4F6)),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Icon(
                icon, 
                color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white54 : const Color(0xFF9CA3AF)),
                size: 24,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              label,
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white70 : const Color(0xFF6B7280)),
              ),
            ),
          ],
        ),
      ),
    );
  }


  Widget _buildToggleButton(String value, String label, String groupValue, Function(String) onChanged, bool isDark) {
    final isSelected = groupValue == value;
    final borderColor = isDark ? Colors.white10 : Colors.grey.shade200;

    return InkWell(
      onTap: () => onChanged(value),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF0F172A) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? ThemeService.primaryColor : borderColor,
            width: isSelected ? 2 : 1,
          ),
        ),
        alignment: Alignment.center,
        child: Text(
          label,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 13,
            color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white70 : const Color(0xFF6B7280)),
          ),
        ),
      ),
    );
  }
}

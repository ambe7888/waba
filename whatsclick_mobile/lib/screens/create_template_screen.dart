import 'package:flutter/material.dart';
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

  String _selectedCategory = 'MARKETING';
  String _selectedLanguage = 'fr';
  String _headerType = 'NONE'; // NONE, TEXT, MEDIA
  
  // Interactive Buttons
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
    super.dispose();
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

    setState(() => _isSubmitting = true);

    final payload = {
      'template_name': _nameController.text.trim().toLowerCase().replaceAll(' ', '_'),
      'language_code': _selectedLanguage,
      'category': _selectedCategory,
      'template_type': 'header', // required for the backend API
      'template_body': _bodyController.text.trim(),
      if (_footerController.text.trim().isNotEmpty) 'template_footer': _footerController.text.trim(),
      if (_headerType == 'TEXT' && _headerController.text.trim().isNotEmpty) ...{
        'media_header_type': 'text',
        'template_header': _headerController.text.trim(),
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
            Text('Create Template', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 18)),
            Text('Create a custom template for your cam...', style: TextStyle(color: subtitleColor, fontSize: 12)),
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
                  _buildSectionTitle('Template Name', textColor),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _nameController,
                    decoration: _buildInputDecoration('e.g. welcome_message', isDark),
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) return 'Nom requis';
                      if (!RegExp(r'^[a-z0-9_]+$').hasMatch(v.trim())) return 'Lettres minuscules, chiffres et _ uniquement';
                      return null;
                    },
                  ),
                  const SizedBox(height: 6),
                  Text('Lowercase letters, numbers and underscores only.', style: TextStyle(fontSize: 12, color: subtitleColor)),
                  
                  const SizedBox(height: 24),
                  
                  _buildSectionTitle('Template Language', textColor),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    initialValue: _selectedLanguage,
                    decoration: _buildInputDecoration('', isDark),
                    icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                    items: _languages.map((l) => DropdownMenuItem(value: l['code'], child: Text(l['label']!))).toList(),
                    onChanged: (v) => setState(() => _selectedLanguage = v!),
                  ),
                  const SizedBox(height: 6),
                  Text('The language this template is written in.', style: TextStyle(fontSize: 12, color: subtitleColor)),

                  const SizedBox(height: 24),

                  _buildSectionTitle('Template Category', textColor),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(child: _buildCategoryCard('UTILITY', Icons.notifications_active_outlined, 'Utility', isDark)),
                      const SizedBox(width: 12),
                      Expanded(child: _buildCategoryCard('MARKETING', Icons.grid_view_rounded, 'Marketing', isDark)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(child: _buildCategoryCard('AUTHENTICATION', Icons.lock_outline_rounded, 'Authentication', isDark)),
                      const SizedBox(width: 12),
                      const Expanded(child: SizedBox()), // Empty space for alignment
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text('Classify your template based on its primary purpose.', style: TextStyle(fontSize: 12, color: subtitleColor)),
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
                  _buildSectionTitle('Template Header', textColor),
                  const SizedBox(height: 6),
                  Text('Add an optional header to your template to make it stand out.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      _buildToggleButton('NONE', 'NONE', _headerType, (v) => setState(() => _headerType = v), isDark),
                      const SizedBox(width: 12),
                      _buildToggleButton('TEXT', 'TEXT', _headerType, (v) => setState(() => _headerType = v), isDark),
                      const SizedBox(width: 12),
                      _buildToggleButton('MEDIA', 'MEDIA', _headerType, (v) => setState(() => _headerType = v), isDark),
                    ],
                  ),
                  if (_headerType == 'TEXT') ...[
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _headerController,
                      decoration: _buildInputDecoration('Enter text header...', isDark),
                    ),
                  ]
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
                  _buildSectionTitle('Message Body', textColor),
                  const SizedBox(height: 6),
                  Text('This is the main content of your message. Use {{1}} to add variables.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 4),
                  Text('Variable parameters must be whole numbers with two sets of curly brackets (for example, {{1}}, {{2}}).', style: TextStyle(fontSize: 12, color: subtitleColor)),
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
                        // Toolbar
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: isDark ? const Color(0xFF1E293B) : Colors.white,
                            borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                            border: Border(bottom: BorderSide(color: borderColor)),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.format_bold_rounded, color: textColor, size: 20),
                              const SizedBox(width: 16),
                              Icon(Icons.format_italic_rounded, color: textColor, size: 20),
                              const SizedBox(width: 16),
                              Icon(Icons.format_strikethrough_rounded, color: textColor, size: 20),
                              const SizedBox(width: 16),
                              Icon(Icons.format_underlined_rounded, color: textColor, size: 20),
                            ],
                          ),
                        ),
                        // Text Area
                        TextFormField(
                          controller: _bodyController,
                          maxLines: 6,
                          decoration: InputDecoration(
                            hintText: 'Type your message here...',
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
                          child: Row(
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
                                  Text('RICH TEXT EDITOR', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: subtitleColor)),
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
                                      Text('ADD VARIABLE', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text('Example: Hello {{1}}, welcome to your store. 0 / 1600', style: TextStyle(fontSize: 11, color: subtitleColor)),
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
                  _buildSectionTitle('Template Footer (Optional)', textColor),
                  const SizedBox(height: 6),
                  Text('Add a small footer text at the bottom of your message.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _footerController,
                    maxLength: 60,
                    decoration: _buildInputDecoration('Enter footer text...', isDark),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Text('QUICK INSERT OPT-OUT KEYWORDS:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: textColor)),
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
                  Text('Recommended: Add an opt-out message in the footer to stay compliant.\nFooter text is limited to 60 characters.', style: TextStyle(fontSize: 12, color: subtitleColor, height: 1.5)),
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
                  _buildSectionTitle('Interactive Buttons', textColor),
                  const SizedBox(height: 6),
                  Text('Add up to 3 interactive buttons (Quick Replies or Call to Action).', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 16),
                  
                  ..._messageButtons.asMap().entries.map((entry) {
                    int index = entry.key;
                    Map<String, dynamic> btn = entry.value;
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: isDark ? Colors.white10 : Colors.black.withOpacity(0.03),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: borderColor),
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: DropdownButtonFormField<String>(
                                  value: btn['type'],
                                  decoration: _buildInputDecoration('Type', isDark).copyWith(contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                                  items: const [
                                    DropdownMenuItem(value: 'QUICK_REPLY', child: Text('Réponse Rapide')),
                                    DropdownMenuItem(value: 'URL_BUTTON', child: Text('Lien URL')),
                                    DropdownMenuItem(value: 'PHONE_NUMBER', child: Text('Appel Téléphonique')),
                                  ],
                                  onChanged: (v) {
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

                  if (_messageButtons.length < 3)
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
                  child: Text('Discard Changes', style: TextStyle(color: textColor, fontWeight: FontWeight.bold, fontSize: 15)),
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
                      : const Text('Submit Template', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
            ],
          ),
        ),
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

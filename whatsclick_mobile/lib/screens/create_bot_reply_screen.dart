import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class CreateBotReplyScreen extends StatefulWidget {
  final Map<String, dynamic>? reply;
  final Map<String, dynamic> triggerTypes;

  const CreateBotReplyScreen({super.key, this.reply, required this.triggerTypes});

  @override
  State<CreateBotReplyScreen> createState() => _CreateBotReplyScreenState();
}

class _CreateBotReplyScreenState extends State<CreateBotReplyScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _triggerController;
  late TextEditingController _replyController;
  
  late String _selectedTriggerType;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    final isEdit = widget.reply != null;
    _nameController = TextEditingController(text: isEdit ? widget.reply!['name'] : '');
    _triggerController = TextEditingController(text: isEdit ? widget.reply!['reply_trigger'] : '');
    _replyController = TextEditingController(text: isEdit ? widget.reply!['reply_text'] : '');
    
    _selectedTriggerType = isEdit ? (widget.reply!['trigger_type'] ?? 'is') : 'is';
  }

  @override
  void dispose() {
    _nameController.dispose();
    _triggerController.dispose();
    _replyController.dispose();
    super.dispose();
  }

  void _insertVariable() {
    final text = _replyController.text;
    final selection = _replyController.selection;
    // For bot replies, variables like @name might be used depending on backend
    // Here we just insert a generic placeholder if needed or let the user type
    final newText = text.replaceRange(
      selection.start > -1 ? selection.start : text.length,
      selection.end > -1 ? selection.end : text.length,
      '{{name}}', // Example generic variable
    );

    setState(() {
      _replyController.text = newText;
      _replyController.selection = TextSelection.collapsed(
        offset: (selection.start > -1 ? selection.start : text.length) + 8,
      );
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final name = _nameController.text.trim();
    final trigger = _triggerController.text.trim();
    final text = _replyController.text.trim();

    if (name.isEmpty || text.isEmpty || (_selectedTriggerType != 'welcome' && trigger.isEmpty)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez remplir tous les champs obligatoires')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    Map<String, dynamic>? res;
    if (widget.reply != null) {
      res = await ApiService().updateBotReply(
        uid: widget.reply!['_uid'],
        name: name,
        triggerType: _selectedTriggerType,
        replyTrigger: _selectedTriggerType == 'welcome' ? null : trigger,
        replyText: text,
      );
    } else {
      res = await ApiService().createBotReply(
        name: name,
        triggerType: _selectedTriggerType,
        replyTrigger: _selectedTriggerType == 'welcome' ? null : trigger,
        replyText: text,
      );
    }

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (res != null && res['reaction'] == 1) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Réponse auto enregistrée avec succès !'), backgroundColor: Colors.green),
        );
        Navigator.pop(context, true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res?['message'] ?? 'Erreur lors de l\'enregistrement'), backgroundColor: Colors.red),
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
    final isEdit = widget.reply != null;

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
            Text(isEdit ? 'Edit Bot Reply' : 'Create Bot Reply', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 18)),
            Text('Automate your customer service', style: TextStyle(color: subtitleColor, fontSize: 12)),
          ],
        ),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Section 1: Rule Details
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Rule Information', textColor),
                  const SizedBox(height: 16),
                  
                  _buildLabel('Rule Name', textColor),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _nameController,
                    decoration: _buildInputDecoration('e.g. Welcome Greeting', isDark),
                  ),
                  const SizedBox(height: 24),
                  
                  _buildLabel('Trigger Type', textColor),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    initialValue: _selectedTriggerType,
                    decoration: _buildInputDecoration('', isDark),
                    icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                    items: widget.triggerTypes.entries.map((e) {
                      return DropdownMenuItem<String>(
                        value: e.key,
                        child: Text(e.value['title'] ?? e.key),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) setState(() => _selectedTriggerType = val);
                    },
                  ),
                  
                  if (_selectedTriggerType != 'welcome') ...[
                    const SizedBox(height: 24),
                    _buildLabel('Trigger Keyword / Phrase', textColor),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _triggerController,
                      decoration: _buildInputDecoration('e.g. hello, pricing, help', isDark),
                    ),
                    const SizedBox(height: 6),
                    Text('The exact phrase or keyword to trigger this reply.', style: TextStyle(fontSize: 12, color: subtitleColor)),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 2: Message Body
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Reply Message', textColor),
                  const SizedBox(height: 6),
                  Text('This is the automated message sent to the user.', style: TextStyle(fontSize: 13, color: subtitleColor)),
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
                            ],
                          ),
                        ),
                        // Text Area
                        TextFormField(
                          controller: _replyController,
                          maxLines: 6,
                          decoration: InputDecoration(
                            hintText: 'Type your automated response here...',
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
                                  Text('TEXT EDITOR', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: subtitleColor)),
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
                      : const Text('Save Reply', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
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
    return Text(title, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor));
  }
  
  Widget _buildLabel(String label, Color textColor) {
    return Text(label, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: textColor));
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
}

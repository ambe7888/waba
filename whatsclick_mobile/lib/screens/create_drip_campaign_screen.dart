import 'package:flutter/material.dart';
import '../services/theme_service.dart';

class CreateDripCampaignScreen extends StatefulWidget {
  const CreateDripCampaignScreen({super.key});

  @override
  State<CreateDripCampaignScreen> createState() => _CreateDripCampaignScreenState();
}

class _CreateDripCampaignScreenState extends State<CreateDripCampaignScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _messageController = TextEditingController();
  
  String _selectedGroup = 'all';
  String _delayType = 'days';
  int _delayValue = 1;

  bool _isSubmitting = false;

  @override
  void dispose() {
    _titleController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isSubmitting = true);
    
    // Simuler un appel réseau
    Future.delayed(const Duration(seconds: 1), () {
      if (mounted) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Campagne créée (Mode Simulation) !'), backgroundColor: Colors.green),
        );
        Navigator.pop(context, true);
      }
    });
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
            Text('Create Drip Campaign', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 18)),
            Text('Engage users automatically over time', style: TextStyle(color: subtitleColor, fontSize: 12)),
          ],
        ),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Section 1: Campaign Details
            _buildCardContainer(
              isDark, cardColor, borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Campaign Details', textColor),
                  const SizedBox(height: 16),
                  
                  _buildLabel('Campaign Title', textColor),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _titleController,
                    decoration: _buildInputDecoration('e.g. Onboarding Sequence Day 1', isDark),
                    validator: (v) => (v == null || v.isEmpty) ? 'Titre requis' : null,
                  ),
                  const SizedBox(height: 24),
                  
                  _buildLabel('Target Audience (Group)', textColor),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    initialValue: _selectedGroup,
                    decoration: _buildInputDecoration('', isDark),
                    icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                    items: const [
                      DropdownMenuItem(value: 'all', child: Text('Tous les contacts')),
                      DropdownMenuItem(value: 'new_leads', child: Text('Nouveaux Leads')),
                      DropdownMenuItem(value: 'customers', child: Text('Clients Actifs')),
                    ],
                    onChanged: (val) {
                      if (val != null) setState(() => _selectedGroup = val);
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 2: Timing
            _buildCardContainer(
              isDark, cardColor, borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Schedule & Timing', textColor),
                  const SizedBox(height: 6),
                  Text('When should this message be sent after the user joins the group?', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 16),
                  
                  Row(
                    children: [
                      Expanded(
                        flex: 1,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildLabel('Wait', textColor),
                            const SizedBox(height: 8),
                            Container(
                              decoration: BoxDecoration(
                                color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF3F4F6),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                                children: [
                                  IconButton(
                                    icon: Icon(Icons.remove, color: ThemeService.primaryColor),
                                    onPressed: () {
                                      if (_delayValue > 0) setState(() => _delayValue--);
                                    },
                                  ),
                                  Text('$_delayValue', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor)),
                                  IconButton(
                                    icon: Icon(Icons.add, color: ThemeService.primaryColor),
                                    onPressed: () => setState(() => _delayValue++),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        flex: 2,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildLabel('Time Unit', textColor),
                            const SizedBox(height: 8),
                            DropdownButtonFormField<String>(
                              initialValue: _delayType,
                              decoration: _buildInputDecoration('', isDark),
                              icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                              items: const [
                                DropdownMenuItem(value: 'minutes', child: Text('Minutes')),
                                DropdownMenuItem(value: 'hours', child: Text('Heures')),
                                DropdownMenuItem(value: 'days', child: Text('Jours')),
                                DropdownMenuItem(value: 'months', child: Text('Mois')),
                              ],
                              onChanged: (val) {
                                if (val != null) setState(() => _delayType = val);
                              },
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 3: Message
            _buildCardContainer(
              isDark, cardColor, borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Message Content', textColor),
                  const SizedBox(height: 6),
                  Text('Write the message you want to send in this drip campaign.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 16),
                  
                  Container(
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: borderColor),
                    ),
                    child: Column(
                      children: [
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
                            ],
                          ),
                        ),
                        TextFormField(
                          controller: _messageController,
                          maxLines: 5,
                          validator: (v) => (v == null || v.isEmpty) ? 'Message requis' : null,
                          decoration: InputDecoration(
                            hintText: 'Type your message here...',
                            hintStyle: TextStyle(color: subtitleColor),
                            border: InputBorder.none,
                            contentPadding: const EdgeInsets.all(16),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 100),
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
                  child: Text('Discard', style: TextStyle(color: textColor, fontWeight: FontWeight.bold, fontSize: 15)),
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
                      : const Text('Create Campaign', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
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

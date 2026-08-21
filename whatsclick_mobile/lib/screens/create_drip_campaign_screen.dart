import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

/// One timed step of a drip campaign: after [delayValue] [delayType] from
/// subscription, send either a free-text message or an existing bot reply.
class _DripStep {
  int delayValue;
  String delayType; // 'minutes' | 'hours' | 'days'
  bool useBotReply;
  String? selectedBotReplyId;
  final TextEditingController messageController;

  _DripStep({
    this.delayValue = 1,
    this.delayType = 'days',
    this.useBotReply = false,
    this.selectedBotReplyId,
    String initialMessage = '',
  }) : messageController = TextEditingController(text: initialMessage);

  void dispose() => messageController.dispose();
}

class CreateDripCampaignScreen extends StatefulWidget {
  const CreateDripCampaignScreen({super.key});

  @override
  State<CreateDripCampaignScreen> createState() => _CreateDripCampaignScreenState();
}

class _CreateDripCampaignScreenState extends State<CreateDripCampaignScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();

  final List<_DripStep> _steps = [_DripStep()];

  List<Map<String, dynamic>> _botReplies = [];
  bool _isLoadingBotReplies = true;

  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadBotReplies();
  }

  Future<void> _loadBotReplies() async {
    final data = await ApiService().fetchBotReplies();
    if (mounted) {
      setState(() {
        _botReplies = data != null
            ? List<Map<String, dynamic>>.from(data['bot_replies'] ?? [])
            : [];
        _isLoadingBotReplies = false;
      });
    }
  }

  @override
  void dispose() {
    _titleController.dispose();
    for (final step in _steps) {
      step.dispose();
    }
    super.dispose();
  }

  void _addStep() {
    setState(() => _steps.add(_DripStep()));
  }

  void _removeStep(int index) {
    setState(() {
      _steps[index].dispose();
      _steps.removeAt(index);
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    // Every step must have a message: either free text or a selected bot reply.
    for (int i = 0; i < _steps.length; i++) {
      final step = _steps[i];
      final missingMessage = step.useBotReply
          ? (step.selectedBotReplyId == null)
          : step.messageController.text.trim().isEmpty;
      if (missingMessage) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Étape ${i + 1} : message manquant')),
        );
        return;
      }
    }

    setState(() => _isSubmitting = true);

    final campaignUid = await ApiService().createDripCampaign(_titleController.text.trim());
    if (campaignUid == null) {
      if (mounted) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(ApiService().lastDripCampaignError ?? 'Impossible de créer la campagne'),
            backgroundColor: Colors.red,
          ),
        );
      }
      return;
    }

    for (final step in _steps) {
      final success = await ApiService().storeDripCampaignStep(
        campaignUid,
        delayValue: step.delayValue,
        delayType: step.delayType,
        customMessage: step.useBotReply ? null : step.messageController.text.trim(),
        botReplyId: step.useBotReply ? step.selectedBotReplyId : null,
      );
      if (!success && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(ApiService().lastDripCampaignError ?? 'Erreur lors de l\'ajout d\'une étape'),
            backgroundColor: Colors.orange,
          ),
        );
      }
    }

    if (mounted) {
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Campagne Drip créée avec succès !'), backgroundColor: Colors.green),
      );
      Navigator.pop(context, true);
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
            Text('Créer une campagne Drip', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 18)),
            Text('Envoyez des messages automatiquement dans le temps', style: TextStyle(color: subtitleColor, fontSize: 12)),
          ],
        ),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Section 1: Campaign title
            _buildCardContainer(
              isDark, cardColor, borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Détails de la campagne', textColor),
                  const SizedBox(height: 16),
                  _buildLabel('Titre de la campagne', textColor),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _titleController,
                    decoration: _buildInputDecoration('ex. Séquence de bienvenue', isDark),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Titre requis' : null,
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: ThemeService.primaryColor.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.info_outline_rounded, size: 18, color: ThemeService.primaryColor),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Un contact est inscrit à cette campagne quand une réponse bot configurée pour ça se déclenche — pas depuis cet écran.',
                            style: TextStyle(fontSize: 12, color: textColor.withValues(alpha: 0.8)),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 2: Steps
            _buildCardContainer(
              isDark, cardColor, borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Étapes de la séquence', textColor),
                  const SizedBox(height: 6),
                  Text('Chaque étape envoie un message après un délai depuis l\'inscription.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.orange.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.orange.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.warning_amber_rounded, size: 18, color: Colors.orange),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Fenêtre de 24h WhatsApp : un message libre ne peut être envoyé que dans les 24h suivant le dernier message du contact. '
                            'Pour une étape programmée au-delà de ce délai, utilisez une réponse auto liée à un modèle approuvé — sinon l\'envoi échouera.',
                            style: TextStyle(fontSize: 12, color: textColor.withValues(alpha: 0.85), height: 1.4),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  ...List.generate(_steps.length, (index) => _buildStepCard(index, isDark, cardColor, borderColor, textColor, subtitleColor)),
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                    onPressed: _addStep,
                    icon: const Icon(Icons.add_rounded),
                    label: const Text('Ajouter une étape'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: ThemeService.primaryColor,
                      side: BorderSide(color: ThemeService.primaryColor),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      minimumSize: const Size(double.infinity, 0),
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
                      : const Text('Créer la campagne', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStepCard(int index, bool isDark, Color cardColor, Color borderColor, Color textColor, Color subtitleColor) {
    final step = _steps[index];
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: borderColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  color: ThemeService.primaryColor.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: Text('${index + 1}',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
              ),
              const SizedBox(width: 8),
              Text('Étape ${index + 1}', style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
              const Spacer(),
              if (_steps.length > 1)
                IconButton(
                  icon: const Icon(Icons.delete_outline_rounded, color: Colors.red, size: 20),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                  onPressed: () => _removeStep(index),
                ),
            ],
          ),
          const SizedBox(height: 12),

          Row(
            children: [
              Expanded(
                flex: 1,
                child: Container(
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF1E293B) : Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: borderColor),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      IconButton(
                        icon: Icon(Icons.remove, color: ThemeService.primaryColor, size: 18),
                        padding: EdgeInsets.zero,
                        onPressed: () => setState(() {
                          if (step.delayValue > 0) step.delayValue--;
                        }),
                      ),
                      Text('${step.delayValue}', style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
                      IconButton(
                        icon: Icon(Icons.add, color: ThemeService.primaryColor, size: 18),
                        padding: EdgeInsets.zero,
                        onPressed: () => setState(() => step.delayValue++),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                flex: 2,
                child: DropdownButtonFormField<String>(
                  initialValue: step.delayType,
                  decoration: _buildInputDecoration('', isDark).copyWith(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
                  icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                  items: const [
                    DropdownMenuItem(value: 'minutes', child: Text('Minutes après')),
                    DropdownMenuItem(value: 'hours', child: Text('Heures après')),
                    DropdownMenuItem(value: 'days', child: Text('Jours après')),
                  ],
                  onChanged: (v) => setState(() => step.delayType = v ?? 'days'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          Row(
            children: [
              Text('Message', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: textColor)),
              const Spacer(),
              Text('Réponse auto', style: TextStyle(fontSize: 11.5, color: subtitleColor)),
              Switch(
                value: step.useBotReply,
                activeThumbColor: ThemeService.primaryColor,
                onChanged: (v) => setState(() => step.useBotReply = v),
              ),
            ],
          ),
          if (step.useBotReply) ...[
            if (_isLoadingBotReplies)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
              )
            else if (_botReplies.isEmpty)
              Text('Aucune réponse auto disponible. Créez-en une depuis "Réponses Automatiques".',
                  style: TextStyle(fontSize: 12, color: subtitleColor))
            else
              DropdownButtonFormField<String>(
                initialValue: step.selectedBotReplyId,
                decoration: _buildInputDecoration('Choisir une réponse auto', isDark),
                icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                items: _botReplies.map((r) {
                  return DropdownMenuItem(
                    value: r['_uid']?.toString(),
                    child: Text(r['name']?.toString() ?? 'Sans nom', overflow: TextOverflow.ellipsis),
                  );
                }).toList(),
                onChanged: (v) => setState(() => step.selectedBotReplyId = v),
              ),
          ] else
            TextFormField(
              controller: step.messageController,
              maxLines: 3,
              decoration: _buildInputDecoration('Tapez le message de cette étape...', isDark),
            ),
        ],
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

import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

/// "Paramètres IA" — lets a non-technical vendor configure their own AI
/// assistant. Business info is collected as a few guided fields instead of
/// one blank textarea (the web version's approach), then assembled into the
/// single free-text `open_ai_input_training_data` string the backend
/// actually stores and feeds to the AI at answer time.
class AiSettingsScreen extends StatefulWidget {
  const AiSettingsScreen({super.key});

  @override
  State<AiSettingsScreen> createState() => _AiSettingsScreenState();
}

class _AiSettingsScreenState extends State<AiSettingsScreen> {
  bool _isLoading = true;
  bool _loadFailed = false;
  bool _isFeatureAvailable = true;
  Map<String, dynamic> _aiCredits = {};

  // Assistant
  bool _enableOpenAiBot = false;
  bool _useExistingChatHistory = false;
  final _botNameController = TextEditingController();
  bool _savingAssistant = false;

  // Guided business info -> combined into open_ai_input_training_data
  final _presentationController = TextEditingController();
  final _produitsController = TextEditingController();
  final _horairesController = TextEditingController();
  final _faqController = TextEditingController();

  // Timing restrictions
  bool _enableBotTimingRestrictions = false;
  String _botStartTiming = '';
  String _botEndTiming = '';
  bool _enableAiBotTimingRestrictions = false;
  bool _savingTiming = false;

  // FlowiseAI (advanced/optional)
  bool _enableFlowiseAiBot = false;
  bool _flowiseUrlConfigured = false;
  bool _editingFlowise = false;
  final _flowiseUrlController = TextEditingController();
  final _flowiseTokenController = TextEditingController();
  bool _savingFlowise = false;

  static const _sectionPresentation = "Présentation de l'entreprise :";
  static const _sectionProduits = 'Produits, services et tarifs :';
  static const _sectionHoraires = 'Horaires et adresse :';
  static const _sectionFaq = 'Questions fréquentes (FAQ) :';

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _botNameController.dispose();
    _presentationController.dispose();
    _produitsController.dispose();
    _horairesController.dispose();
    _faqController.dispose();
    _flowiseUrlController.dispose();
    _flowiseTokenController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _loadFailed = false;
    });
    final data = await ApiService().fetchAiSettings();
    if (!mounted) return;
    if (data == null) {
      setState(() {
        _isLoading = false;
        _loadFailed = true;
      });
      return;
    }
    setState(() {
      _isFeatureAvailable = data['is_feature_available'] == true;
      _aiCredits = Map<String, dynamic>.from(data['ai_credits'] ?? {});
      _enableOpenAiBot = data['enable_open_ai_bot'] == true;
      _useExistingChatHistory = data['use_existing_chat_history'] == true;
      _botNameController.text = data['open_ai_bot_name']?.toString() ?? '';
      _parseTrainingData(data['open_ai_input_training_data']?.toString() ?? '');
      _enableBotTimingRestrictions = data['enable_bot_timing_restrictions'] == true;
      _botStartTiming = data['bot_start_timing']?.toString() ?? '';
      _botEndTiming = data['bot_end_timing']?.toString() ?? '';
      _enableAiBotTimingRestrictions = data['enable_ai_bot_timing_restrictions'] == true;
      _enableFlowiseAiBot = data['enable_flowise_ai_bot'] == true;
      _flowiseUrlConfigured = data['flowise_url_configured'] == true;
      _isLoading = false;
    });
  }

  void _parseTrainingData(String raw) {
    if (raw.isEmpty) return;
    const markers = [_sectionPresentation, _sectionProduits, _sectionHoraires, _sectionFaq];
    final hasAllMarkers = markers.every((m) => raw.contains(m));
    if (!hasAllMarkers) {
      // Free-form text (e.g. typed on the web) — don't guess a split, just
      // surface everything so nothing already saved gets lost.
      _presentationController.text = raw;
      return;
    }
    String extract(String marker, String? nextMarker) {
      final start = raw.indexOf(marker);
      if (start == -1) return '';
      final contentStart = start + marker.length;
      final end = nextMarker != null ? raw.indexOf(nextMarker, contentStart) : raw.length;
      final slice = (end == -1) ? raw.substring(contentStart) : raw.substring(contentStart, end);
      return slice.trim();
    }

    _presentationController.text = extract(_sectionPresentation, _sectionProduits);
    _produitsController.text = extract(_sectionProduits, _sectionHoraires);
    _horairesController.text = extract(_sectionHoraires, _sectionFaq);
    _faqController.text = extract(_sectionFaq, null);
  }

  String _buildTrainingData() {
    final parts = <String>[];
    void addIfNotEmpty(String marker, TextEditingController c) {
      final text = c.text.trim();
      if (text.isNotEmpty) parts.add('$marker\n$text');
    }

    addIfNotEmpty(_sectionPresentation, _presentationController);
    addIfNotEmpty(_sectionProduits, _produitsController);
    addIfNotEmpty(_sectionHoraires, _horairesController);
    addIfNotEmpty(_sectionFaq, _faqController);
    return parts.join('\n\n');
  }

  void _showResult(Map<String, dynamic> result) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(result['success'] == true
            ? (result['message']?.toString().isNotEmpty == true ? result['message'] : 'Enregistré avec succès.')
            : (result['message']?.toString() ?? 'Erreur lors de l\'enregistrement.')),
        backgroundColor: result['success'] == true ? const Color(0xFF10B981) : Colors.red,
      ),
    );
  }

  Future<void> _saveAssistant() async {
    final trainingData = _buildTrainingData();
    if (_enableOpenAiBot && trainingData.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Renseignez au moins une information sur votre entreprise avant d\'activer l\'assistant.')),
      );
      return;
    }
    setState(() => _savingAssistant = true);
    final result = await ApiService().saveAiSettings('open_ai_bot_setup', {
      'enable_open_ai_bot': _enableOpenAiBot,
      'use_existing_chat_history': _useExistingChatHistory,
      'open_ai_bot_name': _botNameController.text.trim(),
      'open_ai_bot_data_source_type': 'text',
      'open_ai_max_token': 1000,
      'open_ai_input_training_data': trainingData,
    });
    if (!mounted) return;
    setState(() => _savingAssistant = false);
    _showResult(result);
    if (result['success'] == true) _load();
  }

  Future<void> _saveTiming() async {
    setState(() => _savingTiming = true);
    final result = await ApiService().saveAiSettings('bot_timing_settings', {
      'enable_bot_timing_restrictions': _enableBotTimingRestrictions,
      'bot_start_timing': _botStartTiming,
      'bot_end_timing': _botEndTiming,
      'bot_timing_timezone': 'UTC',
      'enable_ai_bot_timing_restrictions': _enableAiBotTimingRestrictions,
    });
    if (!mounted) return;
    setState(() => _savingTiming = false);
    _showResult(result);
  }

  Future<void> _saveFlowise() async {
    if (_enableFlowiseAiBot && !_flowiseUrlConfigured && _flowiseUrlController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Renseignez l\'URL de votre bot Flowise avant d\'activer cette option.')),
      );
      return;
    }
    setState(() => _savingFlowise = true);
    final result = await ApiService().saveAiSettings('flowise_ai_bot_setup', {
      'enable_flowise_ai_bot': _enableFlowiseAiBot,
      if (_flowiseUrlController.text.trim().isNotEmpty) 'flowise_url': _flowiseUrlController.text.trim(),
      if (_flowiseTokenController.text.trim().isNotEmpty) 'flowise_access_token': _flowiseTokenController.text.trim(),
    });
    if (!mounted) return;
    setState(() => _savingFlowise = false);
    _showResult(result);
    if (result['success'] == true) _load();
  }

  Future<void> _pickTime(bool isStart) async {
    final current = isStart ? _botStartTiming : _botEndTiming;
    TimeOfDay initial = TimeOfDay.now();
    if (current.contains(':')) {
      final parts = current.split(':');
      final h = int.tryParse(parts[0]);
      final m = int.tryParse(parts[1]);
      if (h != null && m != null) initial = TimeOfDay(hour: h, minute: m);
    }
    final picked = await showTimePicker(context: context, initialTime: initial);
    if (picked == null) return;
    final formatted = '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';
    setState(() {
      if (isStart) {
        _botStartTiming = formatted;
      } else {
        _botEndTiming = formatted;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: const Text('Paramètres IA', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _loadFailed
              ? _buildLoadError(isDark)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _buildStatusBanner(isDark),
                      const SizedBox(height: 16),
                      if (!_isFeatureAvailable)
                        _buildUpgradeNotice(isDark)
                      else ...[
                        _buildAssistantSection(isDark),
                        const SizedBox(height: 16),
                        _buildBusinessInfoSection(isDark),
                        const SizedBox(height: 16),
                        _buildTimingSection(isDark),
                        const SizedBox(height: 16),
                        _buildFlowiseSection(isDark),
                      ],
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
    );
  }

  Widget _buildLoadError(bool isDark) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.error_outline_rounded, size: 40, color: isDark ? Colors.white38 : Colors.black26),
            const SizedBox(height: 12),
            Text('Impossible de charger les paramètres IA.',
                style: TextStyle(color: isDark ? Colors.white70 : Colors.black54)),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: _load, child: const Text('Réessayer')),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusBanner(bool isDark) {
    final displayCredits = _aiCredits['display_credits']?.toString() ?? '0';
    final online = _isFeatureAvailable && _enableOpenAiBot;
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF10B981), Color(0xFF047857)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              shape: BoxShape.circle,
            ),
            child: Icon(online ? Icons.smart_toy_rounded : Icons.smart_toy_outlined, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      margin: const EdgeInsets.only(right: 6),
                      decoration: BoxDecoration(
                        color: online ? Colors.lightGreenAccent : Colors.white54,
                        shape: BoxShape.circle,
                      ),
                    ),
                    Text(
                      online ? 'IA en ligne' : 'IA désactivée',
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  '$displayCredits crédits IA disponibles',
                  style: const TextStyle(color: Colors.white70, fontSize: 12.5, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildUpgradeNotice(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.red.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.red.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          const Icon(Icons.lock_rounded, color: Colors.red),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'Cette fonctionnalité n\'est pas disponible dans votre plan actuel. Veuillez mettre à niveau votre abonnement.',
              style: TextStyle(color: isDark ? Colors.white70 : Colors.black87, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  Widget _card(bool isDark, {required Widget child}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
      ),
      child: child,
    );
  }

  Widget _sectionTitle(String title, IconData icon, bool isDark, {String? subtitle}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 18, color: ThemeService.primaryColor),
            const SizedBox(width: 8),
            Text(title,
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: isDark ? Colors.white : Colors.black87)),
          ],
        ),
        if (subtitle != null) ...[
          const SizedBox(height: 4),
          Text(subtitle, style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : Colors.black54)),
        ],
      ],
    );
  }

  Widget _buildAssistantSection(bool isDark) {
    return _card(
      isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle('Assistant IA', Icons.settings_rounded, isDark,
              subtitle: 'Activez votre assistant et donnez-lui un nom.'),
          const SizedBox(height: 16),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: _enableOpenAiBot,
            onChanged: (v) => setState(() => _enableOpenAiBot = v),
            activeThumbColor: ThemeService.primaryColor,
            title: Text('Activer l\'assistant IA', style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 14)),
          ),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: _useExistingChatHistory,
            onChanged: (v) => setState(() => _useExistingChatHistory = v),
            activeThumbColor: ThemeService.primaryColor,
            title: Text('Se souvenir de la conversation', style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 14)),
            subtitle: const Text('L\'IA tient compte des échanges précédents avec le client.', style: TextStyle(fontSize: 11.5)),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _botNameController,
            decoration: const InputDecoration(
              labelText: 'Nom de votre assistant',
              hintText: 'ex. Assistant WhatsClick',
              border: OutlineInputBorder(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBusinessInfoSection(bool isDark) {
    return _card(
      isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle('Informations de votre entreprise', Icons.storefront_rounded, isDark,
              subtitle: 'Ce que l\'IA doit savoir pour répondre à vos clients.'),
          const SizedBox(height: 16),
          _labeledField('Présentation de l\'entreprise', _presentationController,
              hint: 'ex. Nous sommes une boulangerie à Yaoundé, ouverte depuis 2019...'),
          const SizedBox(height: 14),
          _labeledField('Produits, services et tarifs', _produitsController,
              hint: 'ex. Pain complet 500 FCFA, gâteaux sur commande à partir de 5000 FCFA...'),
          const SizedBox(height: 14),
          _labeledField('Horaires et adresse', _horairesController,
              hint: 'ex. Ouvert du lundi au samedi de 7h à 19h, situé à Bastos...'),
          const SizedBox(height: 14),
          _labeledField('Questions fréquentes (FAQ)', _faqController,
              hint: 'ex. Livrez-vous ? Oui, dans un rayon de 5km, frais de 500 FCFA...'),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _savingAssistant ? null : _saveAssistant,
              icon: _savingAssistant
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.save_rounded, size: 18),
              label: const Text('Enregistrer'),
              style: ElevatedButton.styleFrom(backgroundColor: ThemeService.primaryColor, foregroundColor: Colors.white),
            ),
          ),
        ],
      ),
    );
  }

  Widget _labeledField(String label, TextEditingController controller, {required String hint}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          maxLines: 3,
          decoration: InputDecoration(hintText: hint, border: const OutlineInputBorder(), isDense: true),
        ),
      ],
    );
  }

  Widget _buildTimingSection(bool isDark) {
    return _card(
      isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle('Restrictions horaires', Icons.access_time_rounded, isDark,
              subtitle: 'Limitez les réponses automatiques à certaines heures.'),
          const SizedBox(height: 8),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: _enableBotTimingRestrictions,
            onChanged: (v) => setState(() => _enableBotTimingRestrictions = v),
            activeThumbColor: ThemeService.primaryColor,
            title: Text('Activer les restrictions d\'horaires', style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 14)),
          ),
          if (_enableBotTimingRestrictions) ...[
            Row(
              children: [
                Expanded(child: _timeButton('Début', _botStartTiming, () => _pickTime(true), isDark)),
                const SizedBox(width: 12),
                Expanded(child: _timeButton('Fin', _botEndTiming, () => _pickTime(false), isDark)),
              ],
            ),
            const SizedBox(height: 8),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              value: _enableAiBotTimingRestrictions,
              onChanged: (v) => setState(() => _enableAiBotTimingRestrictions = v),
              activeThumbColor: ThemeService.primaryColor,
              title: Text('Appliquer à l\'IA', style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 14)),
            ),
          ],
          const SizedBox(height: 8),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: _savingTiming ? null : _saveTiming,
              icon: _savingTiming
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.save_rounded, size: 18),
              label: const Text('Enregistrer les horaires'),
              style: OutlinedButton.styleFrom(foregroundColor: ThemeService.primaryColor, side: BorderSide(color: ThemeService.primaryColor)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _timeButton(String label, String value, VoidCallback onTap, bool isDark) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
        decoration: BoxDecoration(
          border: Border.all(color: isDark ? Colors.white24 : Colors.grey.shade400),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Row(
          children: [
            Icon(Icons.schedule_rounded, size: 16, color: isDark ? Colors.white54 : Colors.black54),
            const SizedBox(width: 8),
            Text(value.isEmpty ? label : value, style: TextStyle(color: isDark ? Colors.white : Colors.black87)),
          ],
        ),
      ),
    );
  }

  Widget _buildFlowiseSection(bool isDark) {
    return _card(
      isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle('FlowiseAI (avancé)', Icons.hub_rounded, isDark),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: ThemeService.primaryColor.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(
              "Option avancée réservée aux automatisations poussées : connectez un workflow IA externe (FlowiseAI) capable d'exécuter des actions après une conversation — par exemple envoyer un email de suivi, enregistrer un rendez-vous dans un fichier Excel, ou déclencher toute autre automatisation personnalisée. Si vous ne savez pas ce que c'est, vous n'en avez probablement pas besoin.",
              style: TextStyle(fontSize: 12.5, height: 1.4, color: isDark ? Colors.white70 : Colors.black87),
            ),
          ),
          const SizedBox(height: 14),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: _enableFlowiseAiBot,
            onChanged: (v) => setState(() => _enableFlowiseAiBot = v),
            activeThumbColor: ThemeService.primaryColor,
            title: Text('Activer FlowiseAI', style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 14)),
          ),
          if (_flowiseUrlConfigured && !_editingFlowise) ...[
            Row(
              children: [
                Icon(Icons.check_circle_rounded, size: 16, color: ThemeService.primaryColor),
                const SizedBox(width: 6),
                Text('Paramètres FlowiseAI configurés', style: TextStyle(color: isDark ? Colors.white70 : Colors.black87, fontSize: 13)),
                const Spacer(),
                TextButton(onPressed: () => setState(() => _editingFlowise = true), child: const Text('Modifier')),
              ],
            ),
          ] else ...[
            const SizedBox(height: 8),
            TextField(
              controller: _flowiseUrlController,
              decoration: const InputDecoration(labelText: 'URL de votre bot Flowise', border: OutlineInputBorder(), isDense: true),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _flowiseTokenController,
              decoration: const InputDecoration(labelText: 'Jeton Bearer (facultatif)', border: OutlineInputBorder(), isDense: true),
              obscureText: true,
            ),
          ],
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: _savingFlowise ? null : _saveFlowise,
              icon: _savingFlowise
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.save_rounded, size: 18),
              label: const Text('Enregistrer FlowiseAI'),
              style: OutlinedButton.styleFrom(foregroundColor: ThemeService.primaryColor, side: BorderSide(color: ThemeService.primaryColor)),
            ),
          ),
        ],
      ),
    );
  }
}

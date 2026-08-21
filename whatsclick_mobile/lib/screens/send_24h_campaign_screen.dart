import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

/// 3-step wizard for sending a free (non-template) mass message to contacts
/// within WhatsApp's active 24h customer-service window: pick or write the
/// message, review/trim the eligible audience, then send immediately.
class Send24hCampaignScreen extends StatefulWidget {
  final int eligibleCount;
  final List<Map<String, dynamic>> eligibleContacts;
  final DateTime windowStart;
  final DateTime windowEnd;

  const Send24hCampaignScreen({
    super.key,
    required this.eligibleCount,
    required this.eligibleContacts,
    required this.windowStart,
    required this.windowEnd,
  });

  @override
  State<Send24hCampaignScreen> createState() => _Send24hCampaignScreenState();
}

class _Send24hCampaignScreenState extends State<Send24hCampaignScreen> {
  int _step = 0;

  // Step 1: message
  bool _isLoadingPresets = true;
  List<Map<String, dynamic>> _presets = [];
  String? _selectedPresetUid;
  bool _writingCustomMessage = false;
  final _customMessageController = TextEditingController();

  // Step 2: audience
  late Set<String> _excludedContactUids;

  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _excludedContactUids = {};
    _loadPresets();
  }

  @override
  void dispose() {
    _customMessageController.dispose();
    super.dispose();
  }

  Future<void> _loadPresets() async {
    final presets = await ApiService().fetchNonTemplateMessagePresets();
    if (mounted) {
      setState(() {
        _presets = presets;
        _isLoadingPresets = false;
      });
    }
  }

  String _windowDayLabel(DateTime d) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final that = DateTime(d.year, d.month, d.day);
    if (that == today) return "aujourd'hui";
    if (that == today.subtract(const Duration(days: 1))) return 'hier';
    return '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}';
  }

  String _hhmm(DateTime d) => '${d.hour.toString().padLeft(2, '0')}h${d.minute.toString().padLeft(2, '0')}';

  bool get _canGoNextFromStep1 {
    if (_writingCustomMessage) return _customMessageController.text.trim().isNotEmpty;
    return _selectedPresetUid != null;
  }

  int get _selectedContactsCount => widget.eligibleContacts.length - _excludedContactUids.length;

  Future<void> _submit() async {
    setState(() => _isSubmitting = true);

    String? presetUid = _selectedPresetUid;

    if (_writingCustomMessage) {
      final text = _customMessageController.text.trim();
      final uniqueName = 'Campagne 24h ${DateTime.now().millisecondsSinceEpoch}';
      final created = await ApiService().createBotReply(
        name: uniqueName,
        triggerType: 'NT_CAMPAIGN_MESSAGE',
        replyText: text,
        messageType: 'simple',
        // processBotReplyCreate() defaults status to inactive (2) unless a
        // truthy 'status' is sent — harmless for a one-off send since the
        // preset lookup doesn't filter by status, but active is the
        // correct state if the user ever revisits it.
        advancedFields: const {'status': '1'},
      );
      // processBotReplyCreate() doesn't return the new record's uid in its
      // response — re-fetch the preset list and match on the name we just
      // used (guaranteed unique via the timestamp) to recover it.
      final createdOk = created != null && (created['reaction'] == 1 || created['reaction'] == 21);
      String? createdUid;
      if (createdOk) {
        final refreshed = await ApiService().fetchNonTemplateMessagePresets();
        createdUid = refreshed.firstWhere(
          (p) => p['name']?.toString() == uniqueName,
          orElse: () => {},
        )['_uid']?.toString();
      }
      if (createdUid == null) {
        if (mounted) {
          setState(() => _isSubmitting = false);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Échec de la création du message.'), backgroundColor: Colors.red),
          );
        }
        return;
      }
      presetUid = createdUid;
    }

    final selectedUids = widget.eligibleContacts
        .where((c) => !_excludedContactUids.contains(c['_uid']?.toString()))
        .map((c) => c['_uid'].toString())
        .toList();

    final result = await ApiService().createCampaign(
      title: 'Message 24h ${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year}',
      presetMessageUid: presetUid,
      audienceMode: 'specific',
      contactUids: selectedUids,
      sendImmediately: true,
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (result['reaction'] == 1) {
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Campagne envoyée !'), backgroundColor: Colors.green),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']?.toString() ?? "Erreur lors de l'envoi."),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Message sans modèle', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
          onPressed: () {
            if (_step > 0) {
              setState(() => _step--);
            } else {
              Navigator.of(context).pop();
            }
          },
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
            child: Row(
              children: List.generate(3, (i) {
                final active = i <= _step;
                return Expanded(
                  child: Container(
                    height: 4,
                    margin: EdgeInsets.only(right: i < 2 ? 6 : 0),
                    decoration: BoxDecoration(
                      color: active ? ThemeService.primaryColor : (isDark ? Colors.white12 : Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                );
              }),
            ),
          ),
          Expanded(
            child: IndexedStack(
              index: _step,
              children: [
                _buildStep1(isDark),
                _buildStep2(isDark),
                _buildStep3(isDark),
              ],
            ),
          ),
        ],
      ),
      bottomSheet: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E293B) : Colors.white,
          border: Border(top: BorderSide(color: isDark ? Colors.white10 : Colors.grey.shade200)),
        ),
        child: SafeArea(
          child: SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: ThemeService.primaryColor,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 15),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: _isSubmitting
                  ? null
                  : () {
                      if (_step == 0) {
                        if (!_canGoNextFromStep1) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Choisissez ou écrivez un message.')),
                          );
                          return;
                        }
                        setState(() => _step = 1);
                      } else if (_step == 1) {
                        if (_selectedContactsCount == 0) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Sélectionnez au moins un contact.')),
                          );
                          return;
                        }
                        setState(() => _step = 2);
                      } else {
                        _submit();
                      }
                    },
              child: _isSubmitting
                  ? const SizedBox(
                      width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : Text(
                      _step == 2 ? 'Envoyer maintenant' : 'Suivant',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                    ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStep1(bool isDark) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 100),
      children: [
        Text('Quel message envoyer ?',
            style: TextStyle(
                fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? Colors.white : const Color(0xFF1E293B))),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: _buildToggleChip(isDark, 'Message prédéfini', !_writingCustomMessage,
                  () => setState(() => _writingCustomMessage = false)),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildToggleChip(
                  isDark, 'Écrire un message', _writingCustomMessage, () => setState(() => _writingCustomMessage = true)),
            ),
          ],
        ),
        const SizedBox(height: 16),
        if (_writingCustomMessage)
          TextField(
            controller: _customMessageController,
            maxLines: 5,
            onChanged: (_) => setState(() {}),
            style: TextStyle(color: isDark ? Colors.white : Colors.black87),
            decoration: InputDecoration(
              hintText: 'Tapez votre message...',
              filled: true,
              fillColor: isDark ? const Color(0xFF1E293B) : Colors.white,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: isDark ? Colors.white10 : Colors.grey.shade200),
              ),
            ),
          )
        else if (_isLoadingPresets)
          const Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator()))
        else if (_presets.isEmpty)
          Text('Aucun message prédéfini. Écrivez-en un directement.',
              style: TextStyle(color: isDark ? Colors.white54 : Colors.grey))
        else
          ..._presets.map((p) {
            final uid = p['_uid']?.toString() ?? '';
            final selected = _selectedPresetUid == uid;
            return GestureDetector(
              onTap: () => setState(() => _selectedPresetUid = uid),
              child: Container(
                margin: const EdgeInsets.only(bottom: 10),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: selected
                      ? ThemeService.primaryColor.withValues(alpha: 0.08)
                      : (isDark ? const Color(0xFF1E293B) : Colors.white),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: selected ? ThemeService.primaryColor : (isDark ? Colors.white10 : Colors.grey.shade200),
                    width: selected ? 1.5 : 1,
                  ),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(p['name']?.toString() ?? 'Sans nom',
                              style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                          const SizedBox(height: 4),
                          Text(p['reply_text']?.toString() ?? '',
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : Colors.black54)),
                        ],
                      ),
                    ),
                    Icon(selected ? Icons.check_circle_rounded : Icons.circle_outlined,
                        color: selected ? ThemeService.primaryColor : Colors.grey, size: 20),
                  ],
                ),
              ),
            );
          }),
      ],
    );
  }

  Widget _buildStep2(bool isDark) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 100),
      children: [
        Text('Qui recevra ce message ?',
            style: TextStyle(
                fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? Colors.white : const Color(0xFF1E293B))),
        const SizedBox(height: 6),
        Text(
          'Seuls les contacts ayant écrit entre ${_windowDayLabel(widget.windowStart)} à '
          '${_hhmm(widget.windowStart)} et ${_windowDayLabel(widget.windowEnd)} à ${_hhmm(widget.windowEnd)} '
          'peuvent recevoir un message sans modèle. Décochez ceux à exclure.',
          style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : Colors.black54, height: 1.4),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: ThemeService.primaryColor.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text('$_selectedContactsCount / ${widget.eligibleContacts.length} sélectionné(s)',
              style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12.5)),
        ),
        const SizedBox(height: 12),
        ...widget.eligibleContacts.map((c) {
          final uid = c['_uid']?.toString() ?? '';
          final name = c['name']?.toString() ?? '';
          final phone = c['wa_id']?.toString() ?? '';
          final included = !_excludedContactUids.contains(uid);
          return CheckboxListTile(
            title: Text(name.isNotEmpty ? name : phone, style: TextStyle(color: isDark ? Colors.white : Colors.black87)),
            subtitle: name.isNotEmpty ? Text(phone, style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)) : null,
            value: included,
            activeColor: ThemeService.primaryColor,
            onChanged: (val) {
              setState(() {
                if (val == true) {
                  _excludedContactUids.remove(uid);
                } else {
                  _excludedContactUids.add(uid);
                }
              });
            },
          );
        }),
      ],
    );
  }

  Widget _buildStep3(bool isDark) {
    final messagePreview = _writingCustomMessage
        ? _customMessageController.text.trim()
        : (_presets.firstWhere((p) => p['_uid']?.toString() == _selectedPresetUid, orElse: () => {})['reply_text']
                ?.toString() ??
            '');

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 100),
      children: [
        Text('Résumé',
            style: TextStyle(
                fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? Colors.white : const Color(0xFF1E293B))),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: isDark ? const Color(0xFF1E293B) : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.message_rounded, size: 16, color: ThemeService.primaryColor),
                  const SizedBox(width: 8),
                  Text('Message', style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                ],
              ),
              const SizedBox(height: 8),
              Text(messagePreview.isNotEmpty ? messagePreview : 'Pas de texte.',
                  style: TextStyle(color: isDark ? Colors.white70 : Colors.black87, height: 1.4)),
              const SizedBox(height: 16),
              Divider(color: isDark ? Colors.white10 : Colors.grey.shade200),
              const SizedBox(height: 16),
              Row(
                children: [
                  Icon(Icons.people_alt_rounded, size: 16, color: ThemeService.primaryColor),
                  const SizedBox(width: 8),
                  Text('$_selectedContactsCount destinataire(s)',
                      style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.orange.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.orange.withValues(alpha: 0.3)),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(Icons.info_outline_rounded, size: 18, color: Colors.orange),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  "L'envoi est immédiat et ne peut pas être annulé une fois lancé.",
                  style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white70 : Colors.black87),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildToggleChip(bool isDark, String label, bool selected, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: selected ? ThemeService.primaryColor.withValues(alpha: 0.1) : (isDark ? const Color(0xFF1E293B) : Colors.white),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: selected ? ThemeService.primaryColor : (isDark ? Colors.white10 : Colors.grey.shade300),
            width: selected ? 2 : 1,
          ),
        ),
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w600,
            color: selected ? ThemeService.primaryColor : (isDark ? Colors.white70 : Colors.black54),
          ),
        ),
      ),
    );
  }
}

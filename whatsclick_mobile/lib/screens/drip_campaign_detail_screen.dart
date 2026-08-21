import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

/// Detail/journey view for a single drip campaign: subscriber count, status,
/// and the ordered step sequence — with the ability to add, edit, or delete
/// a step, and to delete the campaign itself.
class DripCampaignDetailScreen extends StatefulWidget {
  final String campaignUid;

  const DripCampaignDetailScreen({super.key, required this.campaignUid});

  @override
  State<DripCampaignDetailScreen> createState() => _DripCampaignDetailScreenState();
}

class _DripCampaignDetailScreenState extends State<DripCampaignDetailScreen> {
  bool _isLoading = true;
  bool _changed = false;
  Map<String, dynamic>? _campaign;
  List<Map<String, dynamic>> _steps = [];
  String? _error;

  List<Map<String, dynamic>> _botReplies = [];
  bool _botRepliesLoaded = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    final data = await ApiService().fetchDripCampaignDetail(widget.campaignUid);
    if (!mounted) return;
    if (data == null) {
      setState(() {
        _error = ApiService().lastDripCampaignError ?? 'Erreur lors du chargement.';
        _isLoading = false;
      });
      return;
    }
    setState(() {
      _campaign = Map<String, dynamic>.from(data['campaign'] ?? {});
      _steps = List<Map<String, dynamic>>.from(data['steps'] ?? []);
      _isLoading = false;
    });
  }

  Future<void> _ensureBotRepliesLoaded() async {
    if (_botRepliesLoaded) return;
    final data = await ApiService().fetchBotReplies();
    if (!mounted) return;
    setState(() {
      _botReplies = data != null ? List<Map<String, dynamic>>.from(data['bot_replies'] ?? []) : [];
      _botRepliesLoaded = true;
    });
  }

  String _formatDelay(dynamic value, dynamic type) {
    final unit = switch (type?.toString()) {
      'minutes' => 'min',
      'hours' => 'h',
      'days' => 'j',
      _ => (type ?? '').toString(),
    };
    return '$value$unit';
  }

  String _stepPreview(Map<String, dynamic> step) {
    if (step['bot_reply'] != null) {
      final reply = step['bot_reply'] as Map;
      return reply['reply_text']?.toString().isNotEmpty == true
          ? reply['reply_text'].toString()
          : (reply['name']?.toString() ?? 'Réponse auto');
    }
    if (step['template'] != null) {
      return 'Modèle : ${(step['template'] as Map)['template_name'] ?? ''}';
    }
    return step['custom_message']?.toString().isNotEmpty == true
        ? step['custom_message'].toString()
        : 'Pas de message.';
  }

  Future<void> _confirmDeleteStep(Map<String, dynamic> step) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Supprimer cette étape ?'),
        content: const Text('Cette action est définitive.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Supprimer', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    final success = await ApiService().deleteDripCampaignStep(step['_uid']);
    if (!mounted) return;
    if (success) {
      _changed = true;
      setState(() => _steps.removeWhere((s) => s['_uid'] == step['_uid']));
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Étape supprimée.'), backgroundColor: Colors.green),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ApiService().lastDripCampaignError ?? 'Échec de la suppression.'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _confirmDeleteCampaign() async {
    final title = _campaign?['title'] ?? 'cette campagne';
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Supprimer la campagne ?'),
        content: Text('« $title » et toutes ses étapes seront supprimées définitivement.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Supprimer', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    final success = await ApiService().deleteDripCampaign(widget.campaignUid);
    if (!mounted) return;
    if (success) {
      Navigator.of(context).pop(true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ApiService().lastDripCampaignError ?? 'Échec de la suppression.'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _openStepEditor({Map<String, dynamic>? existingStep}) async {
    await _ensureBotRepliesLoaded();
    if (!mounted) return;

    int delayValue = existingStep?['delay_value'] ?? 1;
    String delayType = existingStep?['delay_type']?.toString() ?? 'days';
    bool useBotReply = existingStep?['bot_reply'] != null;
    String? selectedBotReplyId = existingStep?['bot_reply']?['_id']?.toString();
    final messageController =
        TextEditingController(text: existingStep?['custom_message']?.toString() ?? '');
    bool isSaving = false;

    final isDark = ThemeService().isDark;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) {
          Future<void> save() async {
            final missingMessage =
                useBotReply ? selectedBotReplyId == null : messageController.text.trim().isEmpty;
            if (missingMessage) {
              ScaffoldMessenger.of(ctx).showSnackBar(
                const SnackBar(content: Text('Message manquant')),
              );
              return;
            }
            setSheetState(() => isSaving = true);

            final bool success;
            if (existingStep != null) {
              success = await ApiService().updateDripCampaignStep(
                existingStep['_uid'],
                delayValue: delayValue,
                delayType: delayType,
                customMessage: useBotReply ? null : messageController.text.trim(),
                botReplyId: useBotReply ? selectedBotReplyId : null,
              );
            } else {
              success = await ApiService().storeDripCampaignStep(
                widget.campaignUid,
                delayValue: delayValue,
                delayType: delayType,
                customMessage: useBotReply ? null : messageController.text.trim(),
                botReplyId: useBotReply ? selectedBotReplyId : null,
              );
            }

            if (!ctx.mounted) return;
            if (success) {
              Navigator.pop(ctx);
              _changed = true;
              _load();
            } else {
              setSheetState(() => isSaving = false);
              ScaffoldMessenger.of(ctx).showSnackBar(
                SnackBar(content: Text(ApiService().lastDripCampaignError ?? 'Échec de l\'enregistrement.')),
              );
            }
          }

          return Padding(
            padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
            child: Container(
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
              ),
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      existingStep != null ? 'Modifier l\'étape' : 'Ajouter une étape',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: isDark ? Colors.white : const Color(0xFF1E293B),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: Container(
                            decoration: BoxDecoration(
                              color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                              children: [
                                IconButton(
                                  icon: Icon(Icons.remove, color: ThemeService.primaryColor, size: 18),
                                  onPressed: () => setSheetState(() {
                                    if (delayValue > 0) delayValue--;
                                  }),
                                ),
                                Text('$delayValue',
                                    style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        color: isDark ? Colors.white : Colors.black87)),
                                IconButton(
                                  icon: Icon(Icons.add, color: ThemeService.primaryColor, size: 18),
                                  onPressed: () => setSheetState(() => delayValue++),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          flex: 2,
                          child: DropdownButtonFormField<String>(
                            initialValue: delayType,
                            decoration: InputDecoration(
                              filled: true,
                              fillColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(10),
                                borderSide: BorderSide.none,
                              ),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            ),
                            items: const [
                              DropdownMenuItem(value: 'minutes', child: Text('Minutes après')),
                              DropdownMenuItem(value: 'hours', child: Text('Heures après')),
                              DropdownMenuItem(value: 'days', child: Text('Jours après')),
                            ],
                            onChanged: (v) => setSheetState(() => delayType = v ?? 'days'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Text('Message',
                            style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: isDark ? Colors.white : Colors.black87)),
                        const Spacer(),
                        Text('Réponse auto', style: TextStyle(fontSize: 11.5, color: Colors.grey)),
                        Switch(
                          value: useBotReply,
                          activeThumbColor: ThemeService.primaryColor,
                          onChanged: (v) => setSheetState(() => useBotReply = v),
                        ),
                      ],
                    ),
                    if (useBotReply)
                      _botReplies.isEmpty
                          ? Text('Aucune réponse auto disponible.',
                              style: TextStyle(fontSize: 12, color: Colors.grey))
                          : DropdownButtonFormField<String>(
                              initialValue: selectedBotReplyId,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: BorderSide.none,
                                ),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              ),
                              items: _botReplies.map((r) {
                                return DropdownMenuItem(
                                  value: r['_id']?.toString(),
                                  child: Text(r['name']?.toString() ?? 'Sans nom',
                                      overflow: TextOverflow.ellipsis),
                                );
                              }).toList(),
                              onChanged: (v) => setSheetState(() => selectedBotReplyId = v),
                            )
                    else
                      TextField(
                        controller: messageController,
                        maxLines: 3,
                        decoration: InputDecoration(
                          hintText: 'Tapez le message de cette étape...',
                          filled: true,
                          fillColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(10),
                            borderSide: BorderSide.none,
                          ),
                        ),
                      ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: isSaving ? null : save,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: ThemeService.primaryColor,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: isSaving
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : Text(existingStep != null ? 'Enregistrer' : 'Ajouter'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
    messageController.dispose();
  }

  void _openStepActions(Map<String, dynamic> step) {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.edit_rounded),
              title: const Text('Modifier'),
              onTap: () {
                Navigator.pop(ctx);
                _openStepEditor(existingStep: step);
              },
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline_rounded, color: Colors.red),
              title: const Text('Supprimer', style: TextStyle(color: Colors.red)),
              onTap: () {
                Navigator.pop(ctx);
                _confirmDeleteStep(step);
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop) Navigator.of(context).pop(_changed);
      },
      child: Scaffold(
        backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
        appBar: AppBar(
          title: Text(_campaign?['title']?.toString() ?? 'Campagne Drip',
              style: const TextStyle(fontWeight: FontWeight.bold)),
          backgroundColor: Colors.transparent,
          elevation: 0,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
            onPressed: () => Navigator.of(context).pop(_changed),
          ),
          actions: [
            if (_campaign != null)
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'delete') _confirmDeleteCampaign();
                },
                itemBuilder: (ctx) => [
                  const PopupMenuItem(
                    value: 'delete',
                    child: Text('Supprimer la campagne', style: TextStyle(color: Colors.red)),
                  ),
                ],
              ),
          ],
        ),
        body: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(_error!, style: const TextStyle(color: Colors.red)),
                        const SizedBox(height: 12),
                        ElevatedButton(onPressed: _load, child: const Text('Réessayer')),
                      ],
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: _load,
                    child: ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        _buildHeaderCard(isDark),
                        const SizedBox(height: 20),
                        Text('Parcours',
                            style: TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                                color: isDark ? Colors.white : const Color(0xFF1E293B))),
                        const SizedBox(height: 12),
                        if (_steps.isEmpty)
                          Text('Aucune étape pour le moment.', style: TextStyle(color: Colors.grey.shade500))
                        else
                          ..._steps.asMap().entries.map((entry) => _buildStepCard(entry.key, entry.value, isDark)),
                        const SizedBox(height: 12),
                        OutlinedButton.icon(
                          onPressed: () => _openStepEditor(),
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
                        const SizedBox(height: 40),
                      ],
                    ),
                  ),
      ),
    );
  }

  Widget _buildHeaderCard(bool isDark) {
    final status = _campaign?['status'];
    final isActive = status == 1 || status == '1';
    final subscribersCount = _campaign?['subscribers_count'] ?? 0;
    final activeSubscribersCount = _campaign?['active_subscribers_count'] ?? 0;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade100),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: (isActive ? const Color(0xFF10B981) : Colors.grey).withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    isActive ? 'Active' : 'Désactivée',
                    style: TextStyle(
                        color: isActive ? const Color(0xFF10B981) : Colors.grey,
                        fontWeight: FontWeight.bold,
                        fontSize: 11),
                  ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Icon(Icons.people_alt_rounded, size: 16, color: Colors.grey.shade500),
                    const SizedBox(width: 6),
                    Text('$subscribersCount abonné(s)',
                        style: TextStyle(fontSize: 13, color: isDark ? Colors.white70 : Colors.black87)),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(Icons.play_circle_outline_rounded, size: 16, color: Colors.grey.shade500),
                    const SizedBox(width: 6),
                    Text('$activeSubscribersCount en cours',
                        style: TextStyle(fontSize: 13, color: isDark ? Colors.white70 : Colors.black87)),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepCard(int index, Map<String, dynamic> step, bool isDark) {
    return GestureDetector(
      onTap: () => _openStepActions(step),
      onLongPress: () => _openStepActions(step),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E293B) : Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade100),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 28,
              height: 28,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Text('${index + 1}',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '${_formatDelay(step['delay_value'], step['delay_type'])} après inscription',
                    style: TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.bold,
                        color: isDark ? Colors.white : const Color(0xFF1E293B)),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _stepPreview(step),
                    style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : Colors.black54),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            Icon(Icons.more_horiz_rounded, size: 18, color: Colors.grey.withValues(alpha: 0.6)),
          ],
        ),
      ),
    );
  }
}

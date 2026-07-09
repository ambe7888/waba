import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class BotRepliesScreen extends StatefulWidget {
  const BotRepliesScreen({super.key});

  @override
  State<BotRepliesScreen> createState() => _BotRepliesScreenState();
}

class _BotRepliesScreenState extends State<BotRepliesScreen> {
  bool _isLoading = true;
  List<dynamic> _replies = [];
  Map<String, dynamic> _triggerTypes = {};
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadBotReplies();
  }

  Future<void> _loadBotReplies() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await ApiService().fetchBotReplies();
      if (data != null && mounted) {
        setState(() {
          _replies = data['bot_replies'] ?? [];
          _triggerTypes = data['trigger_types'] ?? {};
          _isLoading = false;
        });
      } else if (mounted) {
        setState(() {
          _error = 'Erreur lors du chargement des réponses auto.';
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Erreur de connexion.';
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _toggleStatus(dynamic reply) async {
    final uid = reply['_uid'];
    final success = await ApiService().toggleBotReplyStatus(uid);
    if (success) {
      _loadBotReplies();
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Impossible de modifier le statut')),
        );
      }
    }
  }

  Future<void> _deleteReply(dynamic reply) async {
    final bool? confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer'),
        content: Text('Voulez-vous vraiment supprimer "${reply['name']}" ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      final success = await ApiService().deleteBotReply(reply['_uid']);
      if (success) {
        _loadBotReplies();
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Impossible de supprimer la réponse')),
          );
        }
      }
    }
  }

  void _showFormDialog({dynamic reply}) {
    final isEdit = reply != null;
    final nameController = TextEditingController(text: isEdit ? reply['name'] : '');
    final triggerController = TextEditingController(text: isEdit ? reply['reply_trigger'] : '');
    final replyController = TextEditingController(text: isEdit ? reply['reply_text'] : '');
    String selectedTriggerType = isEdit ? (reply['trigger_type'] ?? 'is') : 'is';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) {
          final isDark = ThemeService().isDark;
          return Container(
            decoration: BoxDecoration(
              color: isDark ? ThemeService.darkCard : Colors.white,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
            ),
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(context).viewInsets.bottom + 20,
              top: 20,
              left: 20,
              right: 20,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        isEdit ? 'Modifier la réponse auto' : 'Créer une réponse auto',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: nameController,
                    decoration: const InputDecoration(labelText: 'Nom de la règle'),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: selectedTriggerType,
                    decoration: const InputDecoration(labelText: 'Type de déclencheur'),
                    items: _triggerTypes.entries.map((e) {
                      return DropdownMenuItem<String>(
                        value: e.key,
                        child: Text(e.value['title'] ?? e.key),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) {
                        setDialogState(() {
                          selectedTriggerType = val;
                        });
                      }
                    },
                  ),
                  if (selectedTriggerType != 'welcome') ...[
                    const SizedBox(height: 12),
                    TextField(
                      controller: triggerController,
                      decoration: const InputDecoration(
                        labelText: 'Mot clé / Phrase de déclenchement',
                        hintText: 'ex: bonjour',
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  TextField(
                    controller: replyController,
                    maxLines: 4,
                    decoration: const InputDecoration(
                      labelText: 'Texte de réponse',
                      hintText: 'Saisissez la réponse automatique...',
                    ),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () async {
                      final name = nameController.text.trim();
                      final trigger = triggerController.text.trim();
                      final text = replyController.text.trim();

                      if (name.isEmpty || text.isEmpty || (selectedTriggerType != 'welcome' && trigger.isEmpty)) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Veuillez remplir tous les champs obligatoires')),
                        );
                        return;
                      }

                      Navigator.pop(context);
                      setState(() => _isLoading = true);

                      Map<String, dynamic>? res;
                      if (isEdit) {
                        res = await ApiService().updateBotReply(
                          uid: reply['_uid'],
                          name: name,
                          triggerType: selectedTriggerType,
                          replyTrigger: selectedTriggerType == 'welcome' ? null : trigger,
                          replyText: text,
                        );
                      } else {
                        res = await ApiService().createBotReply(
                          name: name,
                          triggerType: selectedTriggerType,
                          replyTrigger: selectedTriggerType == 'welcome' ? null : trigger,
                          replyText: text,
                        );
                      }

                      setState(() => _isLoading = false);
                      if (res != null && res['reaction'] == 1) {
                        _loadBotReplies();
                      } else {
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(res?['message'] ?? 'Erreur lors de l\'enregistrement')),
                          );
                        }
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ThemeService.primaryColor,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: const Text('Enregistrer', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Réponses Automatiques (Bot)'),
        backgroundColor: Colors.transparent,
        elevation: 0,
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
                      ElevatedButton(
                        onPressed: _loadBotReplies,
                        child: const Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : _replies.isEmpty
                  ? const Center(
                      child: Text(
                        'Aucune réponse automatique configurée.',
                        style: TextStyle(color: Colors.grey),
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _replies.length,
                      itemBuilder: (context, index) {
                        final reply = _replies[index];
                        final String triggerType = reply['trigger_type'] ?? '';
                        final triggerTitle = _triggerTypes[triggerType]?['title'] ?? triggerType;
                        final isActive = reply['status'] == 1 || reply['status'] == null || reply['status'] == '1';

                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        reply['name'] ?? 'Sans nom',
                                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    Switch(
                                      value: isActive,
                                      activeColor: ThemeService.primaryColor,
                                      onChanged: (val) => _toggleStatus(reply),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: ThemeService.primaryColor.withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(
                                        triggerTitle,
                                        style: TextStyle(
                                          fontSize: 10,
                                          fontWeight: FontWeight.bold,
                                          color: ThemeService.primaryColor,
                                        ),
                                      ),
                                    ),
                                    if (triggerType != 'welcome' && reply['reply_trigger'] != null) ...[
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          '"${reply['reply_trigger']}"',
                                          style: const TextStyle(
                                            fontSize: 12,
                                            fontStyle: FontStyle.italic,
                                            fontWeight: FontWeight.w500,
                                          ),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                                const SizedBox(height: 12),
                                Text(
                                  reply['reply_text'] ?? '',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: 13,
                                    color: isDark ? Colors.white70 : Colors.black87,
                                  ),
                                ),
                                const SizedBox(height: 12),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.end,
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.edit_outlined, size: 20, color: Colors.blue),
                                      onPressed: () => _showFormDialog(reply: reply),
                                    ),
                                    IconButton(
                                      icon: const Icon(Icons.delete_outline_rounded, size: 20, color: Colors.red),
                                      onPressed: () => _deleteReply(reply),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: ThemeService.primaryColor,
        onPressed: () => _showFormDialog(),
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }
}

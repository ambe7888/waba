import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'create_bot_reply_screen.dart';

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

  void _showFormScreen({dynamic reply}) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => CreateBotReplyScreen(
          reply: reply,
          triggerTypes: _triggerTypes,
        ),
      ),
    ).then((created) {
      if (created == true) _loadBotReplies();
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    
    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Réponses Automatiques', style: TextStyle(fontWeight: FontWeight.bold)),
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
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.smart_toy_rounded,
                              size: 64, color: Colors.grey.withValues(alpha: 0.4)),
                          const SizedBox(height: 16),
                          Text('Aucune réponse automatique',
                              style: TextStyle(color: Colors.grey.shade500, fontSize: 16)),
                          const SizedBox(height: 20),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: ThemeService.primaryColor,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            icon: const Icon(Icons.add_rounded),
                            label: const Text('Créer une règle', style: TextStyle(fontWeight: FontWeight.bold)),
                            onPressed: _showFormScreen,
                          )
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadBotReplies,
                      child: ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 80),
                        itemCount: _replies.length,
                        itemBuilder: (context, index) {
                          final reply = _replies[index];
                          final String triggerType = reply['trigger_type'] ?? '';
                          final triggerTitle = _triggerTypes[triggerType]?['title'] ?? triggerType;
                          final isActive = reply['status'] == 1 || reply['status'] == null || reply['status'] == '1';

                          return Container(
                            margin: const EdgeInsets.only(bottom: 16),
                            decoration: BoxDecoration(
                              color: isDark ? const Color(0xFF1E293B) : Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade100),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.04),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                )
                              ],
                            ),
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(10),
                                        decoration: BoxDecoration(
                                          color: ThemeService.primaryColor.withValues(alpha: 0.1),
                                          shape: BoxShape.circle,
                                        ),
                                        child: Icon(Icons.smart_toy_rounded, color: ThemeService.primaryColor, size: 22),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              reply['name'] ?? 'Sans nom',
                                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                            const SizedBox(height: 6),
                                            Row(
                                              children: [
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                                  decoration: BoxDecoration(
                                                    color: ThemeService.primaryColor.withValues(alpha: 0.1),
                                                    borderRadius: BorderRadius.circular(6),
                                                  ),
                                                  child: Text(
                                                    triggerTitle,
                                                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: ThemeService.primaryColor),
                                                  ),
                                                ),
                                                if (triggerType != 'welcome' && reply['reply_trigger'] != null) ...[
                                                  const SizedBox(width: 8),
                                                  Expanded(
                                                    child: Text(
                                                      '"${reply['reply_trigger']}"',
                                                      style: TextStyle(
                                                        fontSize: 12,
                                                        fontStyle: FontStyle.italic,
                                                        fontWeight: FontWeight.w500,
                                                        color: isDark ? Colors.white54 : Colors.grey.shade600,
                                                      ),
                                                      overflow: TextOverflow.ellipsis,
                                                    ),
                                                  ),
                                                ],
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                      Switch(
                                        value: isActive,
                                        activeThumbColor: ThemeService.primaryColor,
                                        onChanged: (val) => _toggleStatus(reply),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 16),
                                  Container(
                                    width: double.infinity,
                                    padding: const EdgeInsets.all(12),
                                    decoration: BoxDecoration(
                                      color: isDark ? Colors.black12 : const Color(0xFFF8FAFC),
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                                    ),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            const Icon(Icons.reply_rounded, size: 16, color: Colors.grey),
                                            const SizedBox(width: 8),
                                            Text('Réponse', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey.shade600)),
                                          ],
                                        ),
                                        const SizedBox(height: 8),
                                        Text(
                                          reply['reply_text'] ?? '',
                                          maxLines: 3,
                                          overflow: TextOverflow.ellipsis,
                                          style: TextStyle(fontSize: 14, color: isDark ? Colors.white : const Color(0xFF1F2937), height: 1.4),
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.end,
                                    children: [
                                      TextButton.icon(
                                        icon: const Icon(Icons.edit_rounded, size: 18),
                                        label: const Text('Modifier'),
                                        style: TextButton.styleFrom(foregroundColor: Colors.blue),
                                        onPressed: () => _showFormScreen(reply: reply),
                                      ),
                                      const SizedBox(width: 8),
                                      TextButton.icon(
                                        icon: const Icon(Icons.delete_rounded, size: 18),
                                        label: const Text('Supprimer'),
                                        style: TextButton.styleFrom(foregroundColor: Colors.red),
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
                    ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: ThemeService.primaryColor,
        onPressed: () => _showFormScreen(),
        child: const Icon(Icons.add_rounded, color: Colors.white, size: 28),
      ),
    );
  }
}

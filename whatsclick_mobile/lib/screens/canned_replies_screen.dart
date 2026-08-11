import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class CannedRepliesScreen extends StatefulWidget {
  const CannedRepliesScreen({super.key});

  @override
  State<CannedRepliesScreen> createState() => _CannedRepliesScreenState();
}

class _CannedRepliesScreenState extends State<CannedRepliesScreen> {
  List<Map<String, dynamic>> _replies = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadReplies();
  }

  Future<void> _loadReplies() async {
    setState(() {
      _isLoading = true;
    });
    final list = await ApiService().fetchCannedReplies();
    if (mounted) {
      setState(() {
        _replies = list;
        _isLoading = false;
      });
    }
  }

  void _showAddEditDialog({Map<String, dynamic>? reply}) {
    final shortcutController = TextEditingController(text: reply?['shortcut']);
    final messageController = TextEditingController(text: reply?['message']);
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (ctx) {
        final isDark = ThemeService().isDark;
        return AlertDialog(
          backgroundColor: Theme.of(context).colorScheme.surface,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Text(
            reply == null ? 'Ajouter une réponse rapide' : 'Modifier la réponse rapide',
            style: TextStyle(fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface, fontSize: 16),
          ),
          content: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextFormField(
                  controller: shortcutController,
                  style: TextStyle(color: Theme.of(context).colorScheme.onSurface),
                  decoration: InputDecoration(
                    labelText: 'Raccourci',
                    hintText: 'ex: /hello',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Veuillez saisir un raccourci';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: messageController,
                  maxLines: 4,
                  style: TextStyle(color: Theme.of(context).colorScheme.onSurface),
                  decoration: InputDecoration(
                    labelText: 'Message',
                    hintText: 'Saisissez le texte complet...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Veuillez saisir un message';
                    }
                    return null;
                  },
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2DD4BF),
                foregroundColor: Colors.white,
              ),
              onPressed: () async {
                if (formKey.currentState!.validate()) {
                  Navigator.pop(ctx);
                  setState(() {
                    _isLoading = true;
                  });
                  final success = await ApiService().saveCannedReply(
                    uid: reply?['_uid'],
                    shortcut: shortcutController.text.trim(),
                    message: messageController.text.trim(),
                  );
                  _loadReplies();
                }
              },
              child: const Text('Enregistrer'),
            ),
          ],
        );
      },
    );
  }

  void _deleteReply(Map<String, dynamic> reply) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Supprimer'),
        content: Text('Voulez-vous vraiment supprimer le raccourci "${reply['shortcut']}" ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            onPressed: () async {
              Navigator.pop(ctx);
              setState(() {
                _isLoading = true;
              });
              final success = await ApiService().deleteCannedReply(reply['_uid']);
              _loadReplies();
            },
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: const Text('Réponses Rapides', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: const Color(0xFF2DD4BF),
        foregroundColor: Colors.white,
        onPressed: () => _showAddEditDialog(),
        child: const Icon(Icons.add),
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: Theme.of(context).colorScheme.primary))
          : _replies.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.flash_off_rounded, size: 64, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.16)),
                      const SizedBox(height: 16),
                      Text(
                        'Aucune réponse rapide enregistrée.',
                        style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39), fontSize: 15),
                      ),
                    ],
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _replies.length,
                  itemBuilder: (context, index) {
                    final reply = _replies[index];
                    return Card(
                      color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      child: ListTile(
                        leading: const Icon(Icons.flash_on_rounded, color: Color(0xFFF59E0B)),
                        title: Text(
                          reply['shortcut'] ?? '',
                          style: TextStyle(fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface),
                        ),
                        subtitle: Text(
                          reply['message'] ?? '',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.55)),
                        ),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            IconButton(
                              icon: const Icon(Icons.edit_rounded, color: Colors.blue),
                              onPressed: () => _showAddEditDialog(reply: reply),
                            ),
                            IconButton(
                              icon: const Icon(Icons.delete_rounded, color: Colors.red),
                              onPressed: () => _deleteReply(reply),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}

import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:file_picker/file_picker.dart';
import 'package:record/record.dart';
import 'package:path_provider/path_provider.dart';
import '../services/api_service.dart';
import '../services/fcm_service.dart';
import '../models/contact.dart';
import '../models/chat_message.dart';
import '../config/app_config.dart';
import 'contact_info_drawer.dart';

class ChatBoxScreen extends StatefulWidget {
  final Contact contact;

  const ChatBoxScreen({super.key, required this.contact});

  @override
  State<ChatBoxScreen> createState() => _ChatBoxScreenState();
}

class _ChatBoxScreenState extends State<ChatBoxScreen> {
  final _messageController = TextEditingController();
  final _scrollController = ScrollController();
  final _searchController = TextEditingController();
  List<ChatMessage> _messages = [];
  bool _isLoading = true;
  Timer? _pollingTimer;
  StreamSubscription? _fcmSubscription;

  // Search State
  bool _isSearching = false;
  String _searchQuery = '';

  // Emoji Row State
  bool _showEmojiRow = false;
  final List<String> _quickEmojis = [
    '😀', '😂', '😍', '👍', '🙏', '❤️', '🎉', '🔥', '👏', '😢', '🤔', '😎', '🌟', '🙌', '💡', '🚀', '💯'
  ];

  // Recording State
  bool _isRecording = false;
  int _recordingSeconds = 0;
  Timer? _recordingTimer;
  final _audioRecorder = AudioRecorder();
  String? _localRecordingPath;

  // Design constants

  static const _accentColor = Color(0xFF2DD4BF);
          // Deep dark

  @override
  void initState() {
    super.initState();
    _loadMessages();

    // Listen to real-time incoming messages via FCM
    _fcmSubscription = FcmService().onMessage.listen((_) {
      _loadMessages(silent: true);
    });
    
    // Optimized polling interval
    _pollingTimer = Timer.periodic(
      const Duration(seconds: pollingIntervalSeconds),
      (_) => _loadMessages(silent: true),
    );
  }

  Future<void> _loadMessages({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _isLoading = true;
      });
    }

    final messages = await ApiService().fetchMessages(widget.contact.uid);
    if (messages == null) {
      if (mounted && _isLoading) {
        setState(() {
          _isLoading = false;
        });
      }
      return;
    }

    // Sort by date descending (newest first for reversed ListView)
    final orderedMessages = List<ChatMessage>.from(messages);
    orderedMessages.sort((a, b) {
      DateTime? dtA = DateTime.tryParse(a.timestamp);
      DateTime? dtB = DateTime.tryParse(b.timestamp);
      if (dtA == null && dtB == null) return 0;
      if (dtA == null) return 1;
      if (dtB == null) return -1;
      return dtB.compareTo(dtA);
    });

    // Merge API messages with existing
    final Map<String, ChatMessage> mergedMap = {};
    for (var m in _messages) {
      if (!m.isIncoming && m.status == 'initialize') continue;
      mergedMap[m.uid] = m;
    }
    for (var m in orderedMessages) {
      mergedMap[m.uid] = m;
    }

    final mergedList = mergedMap.values.toList();
    mergedList.sort((a, b) {
      DateTime? dtA = DateTime.tryParse(a.timestamp);
      DateTime? dtB = DateTime.tryParse(b.timestamp);
      if (dtA == null && dtB == null) return 0;
      if (dtA == null) return 1;
      if (dtB == null) return -1;
      return dtB.compareTo(dtA);
    });

    // Keep local sending messages not yet reflected in API
    final localSendingMessages = _messages.where((m) {
      if (m.isIncoming) return false;
      if (m.status != 'initialize') return false;
      bool existsInApi = orderedMessages.any((apiMsg) => apiMsg.uid == m.uid);
      if (existsInApi) return false;
      bool existsSimilar = orderedMessages.any((apiMsg) =>
          !apiMsg.isIncoming &&
          apiMsg.body == m.body &&
          DateTime.tryParse(apiMsg.timestamp) != null &&
          DateTime.tryParse(m.timestamp) != null &&
          DateTime.tryParse(apiMsg.timestamp)!.difference(DateTime.tryParse(m.timestamp)!).inSeconds.abs() < 15);
      return !existsSimilar;
    }).toList();

    final combinedMessages = <ChatMessage>[];
    combinedMessages.addAll(localSendingMessages);
    combinedMessages.addAll(mergedList);

    // FIX: Compare including status to detect read receipt changes
    bool hasChanged = _messages.length != combinedMessages.length;
    if (!hasChanged) {
      for (int i = 0; i < _messages.length; i++) {
        if (_messages[i].hasChangedFrom(combinedMessages[i])) {
          hasChanged = true;
          break;
        }
      }
    }

    if (hasChanged || !silent) {
      if (mounted) {
        setState(() {
          _messages = combinedMessages;
          _isLoading = false;
        });
        _scrollToBottom();
      }
    } else {
      if (mounted && _isLoading) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _startAggressivePolling() {
    int count = 0;
    Timer.periodic(Duration(milliseconds: aggressivePollingIntervalMs), (timer) {
      if (!mounted || count >= aggressivePollingMaxCount) {
        timer.cancel();
        return;
      }
      count++;
      _loadMessages(silent: true);
    });
  }

  Future<void> _handleSend() async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;

    _messageController.clear();
    setState(() {
      _showEmojiRow = false;
    });

    final tempMsg = ChatMessage(
      uid: UniqueKey().toString(),
      body: text,
      isIncoming: false,
      timestamp: DateTime.now().toIso8601String(),
    );

    setState(() {
      _messages.insert(0, tempMsg);
    });
    _scrollToBottom();

    final success = await ApiService().sendMessage(widget.contact.uid, text);
    if (!success) {
      _loadMessages(silent: true);
    } else {
      _startAggressivePolling();
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          0.0,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  // Animated status icon
  Widget _buildStatusIcon(String status) {
    switch (status) {
      case 'failed':
        return Icon(Icons.error_outline, size: 14, color: Color(0xFFEF4444));
      case 'initialize':
        return Icon(Icons.access_time_rounded, size: 13, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31));
      case 'accepted':
      case 'sent':
        return Icon(Icons.done_rounded, size: 14, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.47));
      case 'delivered':
        return Icon(Icons.done_all_rounded, size: 14, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.47));
      case 'read':
        return Icon(Icons.done_all_rounded, size: 14, color: _accentColor);
      default:
        return Icon(Icons.done_rounded, size: 14, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.47));
    }
  }

  // Voice Recording functions
  Future<void> _startRecording() async {
    try {
      if (await _audioRecorder.hasPermission()) {
        final tempDir = await getTemporaryDirectory();
        final path = '${tempDir.path}/recorded_audio_${DateTime.now().millisecondsSinceEpoch}.m4a';
        _localRecordingPath = path;

        await _audioRecorder.start(
          const RecordConfig(encoder: AudioEncoder.aacLc),
          path: path,
        );

        setState(() {
          _isRecording = true;
          _recordingSeconds = 0;
          _showEmojiRow = false;
        });

        _recordingTimer?.cancel();
        _recordingTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
          setState(() {
            _recordingSeconds++;
          });
        });
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Permission micro refusée.')),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur lors du démarrage de l\'enregistrement: $e')),
        );
      }
    }
  }

  Future<void> _cancelRecording() async {
    _recordingTimer?.cancel();
    try {
      await _audioRecorder.stop();
      if (_localRecordingPath != null) {
        final file = File(_localRecordingPath!);
        if (await file.exists()) {
          await file.delete();
        }
      }
    } catch (e) {
      debugPrint('Error cancelling recording: $e');
    }
    setState(() {
      _isRecording = false;
      _localRecordingPath = null;
    });
  }

  Future<void> _sendVoiceNote() async {
    _recordingTimer?.cancel();
    final path = await _audioRecorder.stop();
    setState(() {
      _isRecording = false;
    });

    if (path == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Erreur: Aucun fichier enregistré.')),
        );
      }
      return;
    }

    final file = File(path);
    if (!await file.exists()) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Fichier enregistré introuvable.')),
        );
      }
      return;
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Envoi de la note vocale...'),
          duration: Duration(seconds: 2),
        ),
      );
    }

    try {
      final uploadedFileName = await ApiService().uploadTempMedia(file, 'whatsapp_audio');
      if (uploadedFileName == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Erreur lors du téléchargement de la note vocale sur le serveur')),
          );
        }
        return;
      }

      final durationString = '${_recordingSeconds ~/ 60}:${(_recordingSeconds % 60).toString().padLeft(2, '0')}';
      final tempMsg = ChatMessage(
        uid: UniqueKey().toString(),
        body: 'Note vocale ($durationString)',
        isIncoming: false,
        timestamp: DateTime.now().toIso8601String(),
        type: 'audio',
        status: 'initialize',
      );
      setState(() {
        _messages.insert(0, tempMsg);
      });
      _scrollToBottom();

      final success = await ApiService().sendMediaMessage(
        widget.contact.uid,
        'audio',
        uploadedFileName,
        caption: 'Note vocale',
        originalFilename: 'voice_note.m4a',
      );

      if (success) {
        _startAggressivePolling();
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Erreur lors de l\'envoi de la note vocale')),
          );
        }
        _loadMessages(silent: true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur lors de l\'envoi de la note vocale: $e')),
        );
      }
    }
  }

  // Quick replies Bottom Sheet
  void _showQuickRepliesSheet() async {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.6,
          maxChildSize: 0.9,
          minChildSize: 0.4,
          builder: (_, controller) {
            return Container(
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: FutureBuilder<List<Map<String, dynamic>>>(
                future: ApiService().fetchQuickReplies(widget.contact.uid),
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return Center(child: CircularProgressIndicator(color: Theme.of(context).colorScheme.primary));
                  }
                  if (snapshot.hasError || !snapshot.hasData || snapshot.data!.isEmpty) {
                    return Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.flash_off, size: 56, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.16)),
                        SizedBox(height: 16),
                        Text(
                          'Aucune réponse rapide disponible.',
                          style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39), fontSize: 15),
                        ),
                        SizedBox(height: 16),
                        TextButton(
                          onPressed: () => Navigator.pop(context),
                          child: Text('Fermer', style: TextStyle(color: Theme.of(context).colorScheme.primary)),
                        ),
                      ],
                    );
                  }

                  final replies = snapshot.data!;
                  return Column(
                    children: [
                      Container(
                        padding: EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          border: Border(bottom: BorderSide(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.06))),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.flash_on_rounded, color: _accentColor, size: 22),
                            SizedBox(width: 8),
                            Text(
                              'Réponses Rapides',
                              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.onSurface),
                            ),
                            Spacer(),
                            IconButton(
                              icon: Icon(Icons.close_rounded, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.47)),
                              onPressed: () => Navigator.pop(context),
                            ),
                          ],
                        ),
                      ),
                      Expanded(
                        child: ListView.builder(
                          controller: controller,
                          itemCount: replies.length,
                          itemBuilder: (context, index) {
                            final reply = replies[index];
                            return ListTile(
                              leading: Container(
                                width: 40,
                                height: 40,
                                decoration: BoxDecoration(
                                  color: Theme.of(context).colorScheme.primary.withAlpha(30),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(Icons.smart_toy_rounded, color: Theme.of(context).colorScheme.primary, size: 20),
                              ),
                              title: Text(
                                reply['name'] ?? 'Nom inconnu',
                                style: TextStyle(fontWeight: FontWeight.w600, color: Theme.of(context).colorScheme.onSurface, fontSize: 14),
                              ),
                              subtitle: Text(
                                'Déclencher la réponse automatique',
                                style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31), fontSize: 12),
                              ),
                              onTap: () async {
                                final localContext = context;
                                Navigator.pop(localContext);
                                showDialog(
                                  context: localContext,
                                  barrierDismissible: false,
                                  builder: (context) => Center(child: CircularProgressIndicator(color: Theme.of(context).colorScheme.primary)),
                                );
                                final success = await ApiService().sendQuickReply(widget.contact.uid, reply['_id']);
                                if (!localContext.mounted) return;
                                Navigator.pop(localContext);
                                if (success) {
                                  ScaffoldMessenger.of(localContext).showSnackBar(
                                    const SnackBar(content: Text('Réponse rapide du bot déclenchée')),
                                  );
                                  _startAggressivePolling();
                                } else {
                                  ScaffoldMessenger.of(localContext).showSnackBar(
                                    const SnackBar(content: Text('Erreur lors du déclenchement du bot')),
                                  );
                                }
                              },
                            );
                          },
                        ),
                      ),
                    ],
                  );
                },
              ),
            );
          },
        );
      },
    );
  }

  // Template selection Bottom Sheet
  void _showTemplatesSheet() async {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.7,
          maxChildSize: 0.9,
          minChildSize: 0.4,
          builder: (_, controller) {
            return Container(
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: FutureBuilder<List<Map<String, dynamic>>>(
                future: ApiService().fetchTemplates(),
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return Center(child: CircularProgressIndicator(color: Theme.of(context).colorScheme.primary));
                  }
                  if (snapshot.hasError || !snapshot.hasData || snapshot.data!.isEmpty) {
                    return Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.description_outlined, size: 56, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.16)),
                        SizedBox(height: 16),
                        Text(
                          'Aucun modèle approuvé trouvé.',
                          style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39), fontSize: 15),
                        ),
                        SizedBox(height: 16),
                        TextButton(
                          onPressed: () => Navigator.pop(context),
                          child: Text('Fermer', style: TextStyle(color: Theme.of(context).colorScheme.primary)),
                        ),
                      ],
                    );
                  }

                  final templates = snapshot.data!;
                  return Column(
                    children: [
                      Container(
                        padding: EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          border: Border(bottom: BorderSide(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.06))),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.near_me_rounded, color: _accentColor, size: 22),
                            SizedBox(width: 8),
                            Text(
                              'Modèles Meta',
                              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.onSurface),
                            ),
                            Spacer(),
                            IconButton(
                              icon: Icon(Icons.close_rounded, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.47)),
                              onPressed: () => Navigator.pop(context),
                            ),
                          ],
                        ),
                      ),
                      Expanded(
                        child: ListView.builder(
                          controller: controller,
                          itemCount: templates.length,
                          itemBuilder: (context, index) {
                            final template = templates[index];
                            final String bodyText = _getTemplateBodyText(template);
                            final String category = template['category'] ?? 'utility';
                            
                            return ListTile(
                              title: Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      template['template_name'] ?? 'Nom du modèle',
                                      style: TextStyle(fontWeight: FontWeight.w600, color: Theme.of(context).colorScheme.onSurface, fontSize: 14),
                                    ),
                                  ),
                                  _buildCategoryBadge(category),
                                ],
                              ),
                              subtitle: Text(
                                bodyText.isNotEmpty ? bodyText : 'Pas de texte d\'aperçu',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31), fontSize: 12),
                              ),
                              trailing: Text(
                                (template['language'] ?? 'FR').toUpperCase(),
                                style: TextStyle(fontWeight: FontWeight.w500, color: _accentColor, fontSize: 11),
                              ),
                              onTap: () {
                                Navigator.pop(context);
                                _handleSendTemplate(template);
                              },
                            );
                          },
                        ),
                      ),
                    ],
                  );
                },
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildCategoryBadge(String category) {
    Color bgColor;
    Color textColor;
    String label = category.toUpperCase();
    
    switch (category.toLowerCase()) {
      case 'marketing':
        bgColor = Color(0xFFEF4444).withAlpha(30);
        textColor = Color(0xFFFCA5A5);
        break;
      case 'utility':
      case 'utilitaire':
        bgColor = Color(0xFF3B82F6).withAlpha(30);
        textColor = Color(0xFF93C5FD);
        break;
      case 'authentication':
      case 'authentification':
        bgColor = Color(0xFF8B5CF6).withAlpha(30);
        textColor = Color(0xFFC4B5FD);
        break;
      default:
        bgColor = Theme.of(context).colorScheme.onSurface.withOpacity(0.06);
        textColor = Theme.of(context).colorScheme.onSurface.withOpacity(0.59);
    }
    
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(color: textColor, fontSize: 9, fontWeight: FontWeight.w700),
      ),
    );
  }

  String _getTemplateBodyText(Map<String, dynamic> template) {
    try {
      final Map<String, dynamic> data = template['__data'] ?? {};
      final Map<String, dynamic> temp = data['template'] ?? {};
      final List components = temp['components'] ?? [];
      final bodyComponent = components.firstWhere((c) => c['type'] == 'BODY', orElse: () => null);
      return bodyComponent?['text'] ?? '';
    } catch (e) {
      return '';
    }
  }

  List<String> _getTemplateVariables(String bodyText) {
    final regExp = RegExp(r'\{\{(\d+)\}\}');
    final matches = regExp.allMatches(bodyText);
    return matches.map((m) => m.group(0)!).toSet().toList();
  }

  void _handleSendTemplate(Map<String, dynamic> template) {
    final String uid = template['_uid'];
    final String name = template['template_name'];
    final String bodyText = _getTemplateBodyText(template);
    final variables = _getTemplateVariables(bodyText);

    if (variables.isEmpty) {
      _sendTemplateMessage(uid, {});
    } else {
      _showTemplateVariablesDialog(uid, name, variables, bodyText);
    }
  }

  void _showTemplateVariablesDialog(String templateUid, String templateName, List<String> variables, String bodyText) {
    final controllers = <String, TextEditingController>{};
    for (var v in variables) {
      controllers[v] = TextEditingController();
    }

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          backgroundColor: Theme.of(context).colorScheme.surface,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Text(
            'Variables pour $templateName',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.onSurface),
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.onSurface.withOpacity(0.04),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    'Aperçu :\n$bodyText',
                    style: TextStyle(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39)),
                  ),
                ),
                SizedBox(height: 16),
                ...variables.map((v) {
                  return Padding(
                    padding: EdgeInsets.only(bottom: 12.0),
                    child: TextField(
                      controller: controllers[v],
                      style: TextStyle(color: Theme.of(context).colorScheme.onSurface, fontSize: 14),
                      decoration: InputDecoration(
                        labelText: 'Valeur pour $v',
                        labelStyle: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39), fontSize: 13),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: BorderSide(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.12)),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: BorderSide(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.12)),
                        ),
                        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),
                  );
                }),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text('Annuler', style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39))),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: Theme.of(context).colorScheme.primary,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () {
                Navigator.pop(context);
                final Map<String, String> values = {};
                for (var v in variables) {
                  final index = v.replaceAll('{{', '').replaceAll('}}', '');
                  values['field_$index'] = controllers[v]!.text;
                }
                _sendTemplateMessage(templateUid, values);
              },
              child: Text('Envoyer', style: TextStyle(color: Theme.of(context).colorScheme.onSurface, fontWeight: FontWeight.w600)),
            ),
          ],
        );
      },
    );
  }

  Future<void> _sendTemplateMessage(String templateUid, Map<String, dynamic> variables) async {
    final localContext = context;
    showDialog(
      context: localContext,
      barrierDismissible: false,
      builder: (context) => Center(child: CircularProgressIndicator(color: Theme.of(context).colorScheme.primary)),
    );

    final success = await ApiService().sendTemplateMessage(widget.contact.uid, templateUid, variables);

    if (!localContext.mounted) return;
    Navigator.pop(localContext);
    if (success) {
      ScaffoldMessenger.of(localContext).showSnackBar(
        const SnackBar(content: Text('Modèle WhatsApp envoyé avec succès')),
      );
      _loadMessages();
      _startAggressivePolling();
    } else {
      ScaffoldMessenger.of(localContext).showSnackBar(
        const SnackBar(content: Text('Erreur lors de l\'envoi du modèle')),
      );
    }
  }

  // Phone Call function
  Future<void> _makePhoneCall() async {
    final cleanPhone = widget.contact.phoneNumber.replaceAll(RegExp(r'[^0-9+]'), '');
    final Uri launchUri = Uri(scheme: 'tel', path: cleanPhone);
    try {
      if (await canLaunchUrl(launchUri)) {
        await launchUrl(launchUri);
      } else {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Impossible de lancer l\'appel pour le numéro: $cleanPhone')),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur d\'appel: $e')),
      );
    }
  }

  bool _is24hWindowActive() {
    if (_messages.isEmpty) return false;
    final incomingMessages = _messages.where((m) => m.isIncoming).toList();
    if (incomingMessages.isEmpty) return false;
    
    final lastIncoming = incomingMessages.first;
    try {
      final parsedTime = DateTime.parse(lastIncoming.timestamp);
      final diff = DateTime.now().difference(parsedTime.toLocal());
      return diff.inHours < 24;
    } catch (e) {
      return false;
    }
  }

  // Attachments Menu
  void _showAttachmentMenu() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          padding: EdgeInsets.symmetric(vertical: 24, horizontal: 16),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Partager du contenu',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.onSurface),
              ),
              SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildAttachmentItem(Icons.headset_rounded, 'Audio', Color(0xFFF59E0B), () => _pickAndSendMedia('audio')),
                  _buildAttachmentItem(Icons.insert_drive_file_rounded, 'Doc', Color(0xFF6366F1), () => _pickAndSendMedia('document')),
                  _buildAttachmentItem(Icons.photo_rounded, 'Image', Color(0xFF8B5CF6), () => _pickAndSendMedia('image')),
                  _buildAttachmentItem(Icons.video_collection_rounded, 'Vidéo', Color(0xFFEC4899), () => _pickAndSendMedia('video')),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildAttachmentItem(IconData icon, String label, Color color, VoidCallback onTap) {
    return GestureDetector(
      onTap: () {
        Navigator.pop(context);
        onTap();
      },
      child: Column(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: color.withAlpha(25),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: color, size: 26),
          ),
          SizedBox(height: 8),
          Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.63))),
        ],
      ),
    );
  }

  Future<void> _pickAndSendMedia(String type) async {
    FileType fileType = FileType.any;
    List<String>? allowedExtensions;
    
    if (type == 'image') {
      fileType = FileType.image;
    } else if (type == 'audio') {
      fileType = FileType.audio;
    } else if (type == 'video') {
      fileType = FileType.video;
    } else if (type == 'document') {
      fileType = FileType.custom;
      allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];
    }

    try {
      final result = await FilePicker.platform.pickFiles(
        type: fileType,
        allowedExtensions: allowedExtensions,
      );

      if (result == null || result.files.single.path == null) return;
      
      final filePath = result.files.single.path!;
      final file = File(filePath);
      final originalFilename = result.files.single.name;

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Téléchargement de $originalFilename en cours...'),
            duration: const Duration(seconds: 2),
          ),
        );
      }

      String uploadType;
      if (type == 'image') {
        uploadType = 'whatsapp_image';
      } else if (type == 'audio') {
        uploadType = 'whatsapp_audio';
      } else if (type == 'video') {
        uploadType = 'whatsapp_video';
      } else {
        uploadType = 'whatsapp_document';
      }

      final uploadedFileName = await ApiService().uploadTempMedia(file, uploadType);
      if (uploadedFileName == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Erreur lors du téléchargement du fichier sur le serveur')),
          );
        }
        return;
      }

      final tempMsg = ChatMessage(
        uid: UniqueKey().toString(),
        body: originalFilename,
        isIncoming: false,
        timestamp: DateTime.now().toIso8601String(),
        type: type,
        status: 'initialize',
      );
      setState(() {
        _messages.insert(0, tempMsg);
      });
      _scrollToBottom();

      final success = await ApiService().sendMediaMessage(
        widget.contact.uid,
        type,
        uploadedFileName,
        caption: type == 'image' ? 'Image envoyée' : (type == 'video' ? 'Vidéo envoyée' : originalFilename),
        originalFilename: originalFilename,
      );

      if (success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('$originalFilename envoyé avec succès')),
          );
        }
        _startAggressivePolling();
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Erreur lors de l\'envoi de $originalFilename')),
          );
        }
        _loadMessages(silent: true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur lors de la sélection du fichier: $e')),
        );
      }
    }
  }

  @override
  void dispose() {
    _fcmSubscription?.cancel();
    _pollingTimer?.cancel();
    _recordingTimer?.cancel();
    _audioRecorder.dispose();
    _messageController.dispose();
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final filteredMessages = _searchQuery.isEmpty
        ? _messages
        : _messages.where((m) => m.body.toLowerCase().contains(_searchQuery.toLowerCase())).toList();

    final isWindowActive = _is24hWindowActive();

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      endDrawerEnableOpenDragGesture: false,
      endDrawer: ContactInfoDrawer(
        contact: widget.contact,
        onUpdate: () {
          _loadMessages(silent: true);
        },
      ),
      appBar: _buildAppBar(isWindowActive),
      body: Column(
        children: [
          // Chat Messages
          Expanded(
            child: _isLoading
                ? Center(
                    child: SizedBox(
                      width: 36,
                      height: 36,
                      child: CircularProgressIndicator(
                        color: Theme.of(context).colorScheme.primary,
                        strokeWidth: 3,
                        strokeCap: StrokeCap.round,
                      ),
                    ),
                  )
                : filteredMessages.isEmpty
                    ? Center(
                        child: Text(
                          _searchQuery.isNotEmpty
                              ? 'Aucun message ne correspond à votre recherche.'
                              : 'Aucun message dans cette conversation.',
                          style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31)),
                          textAlign: TextAlign.center,
                        ),
                      )
                    : ListView.builder(
                        controller: _scrollController,
                        reverse: true,
                        padding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        itemCount: filteredMessages.length,
                        itemBuilder: (context, index) {
                          final message = filteredMessages[index];
                          return _buildMessageBubble(message);
                        },
                      ),
          ),

          // 24h Window Warning
          if (!isWindowActive && _messages.isNotEmpty)
            GestureDetector(
              onTap: _showTemplatesSheet,
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                decoration: BoxDecoration(
                  color: Color(0xFFF59E0B).withAlpha(15),
                  border: Border(top: BorderSide(color: Color(0xFFF59E0B).withAlpha(30))),
                ),
                child: Row(
                  children: [
                    Icon(Icons.warning_amber_rounded, color: Color(0xFFF59E0B), size: 18),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Fenêtre 24h expirée. Envoyez un modèle Meta.',
                        style: TextStyle(
                          fontSize: 11,
                          color: Color(0xFFFDE68A).withAlpha(200),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                    Icon(Icons.arrow_forward_ios_rounded, size: 12, color: Color(0xFFF59E0B).withAlpha(120)),
                  ],
                ),
              ),
            ),

          // Emoji Row
          if (_showEmojiRow)
            Container(
              height: 48,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                border: Border(top: BorderSide(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.04))),
              ),
              padding: EdgeInsets.symmetric(horizontal: 8),
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                itemCount: _quickEmojis.length,
                itemBuilder: (context, index) {
                  return GestureDetector(
                    onTap: () {
                      final text = _messageController.text;
                      final selection = _messageController.selection;
                      final newText = text.replaceRange(
                        selection.start >= 0 ? selection.start : text.length,
                        selection.end >= 0 ? selection.end : text.length,
                        _quickEmojis[index],
                      );
                      _messageController.text = newText;
                      _messageController.selection = TextSelection.collapsed(
                        offset: (selection.start >= 0 ? selection.start : text.length) + _quickEmojis[index].length,
                      );
                    },
                    child: Padding(
                      padding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      child: Text(_quickEmojis[index], style: TextStyle(fontSize: 24)),
                    ),
                  );
                },
              ),
            ),

          // Input Bar
          _buildInputBar(),
        ],
      ),
    );
  }

  PreferredSizeWidget _buildAppBar(bool isWindowActive) {
    return AppBar(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      elevation: 0,
      title: _isSearching
          ? TextField(
              controller: _searchController,
              style: TextStyle(color: Theme.of(context).colorScheme.onSurface, fontSize: 15),
              decoration: InputDecoration(
                hintText: 'Rechercher un message...',
                hintStyle: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31)),
                border: InputBorder.none,
              ),
              autofocus: true,
              onChanged: (val) {
                setState(() {
                  _searchQuery = val.trim();
                });
              },
            )
          : Row(
              children: [
                // Avatar
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [Theme.of(context).colorScheme.primary, _accentColor],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Center(
                    child: Text(
                      widget.contact.name.isNotEmpty ? widget.contact.name[0].toUpperCase() : 'C',
                      style: TextStyle(color: Theme.of(context).colorScheme.onSurface, fontWeight: FontWeight.w700, fontSize: 16),
                    ),
                  ),
                ),
                SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              widget.contact.name,
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          SizedBox(width: 6),
                          Container(
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: isWindowActive ? _accentColor : Theme.of(context).colorScheme.onSurface.withOpacity(0.16),
                              shape: BoxShape.circle,
                            ),
                          ),
                        ],
                      ),
                      Row(
                        children: [
                          Text(
                            widget.contact.phoneNumber,
                            style: TextStyle(fontSize: 11, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39)),
                            overflow: TextOverflow.ellipsis,
                          ),
                          SizedBox(width: 6),
                          Container(
                            padding: EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                            decoration: BoxDecoration(
                              color: isWindowActive ? Theme.of(context).colorScheme.primary.withAlpha(40) : Theme.of(context).colorScheme.onSurface.withOpacity(0.06),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              isWindowActive ? '24h ●' : '24h ○',
                              style: TextStyle(
                                fontSize: 9,
                                color: isWindowActive ? _accentColor : Theme.of(context).colorScheme.onSurface.withOpacity(0.31),
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
      actions: [
        if (_isSearching)
          IconButton(
            icon: Icon(Icons.close_rounded),
            onPressed: () {
              setState(() {
                _isSearching = false;
                _searchQuery = '';
                _searchController.clear();
              });
            },
          )
        else ...[
          IconButton(
            icon: Icon(Icons.phone_rounded, size: 20),
            tooltip: 'Appeler',
            onPressed: _makePhoneCall,
          ),
          IconButton(
            icon: Icon(Icons.search_rounded, size: 20),
            onPressed: () {
              setState(() {
                _isSearching = true;
              });
            },
          ),
          IconButton(
            icon: Icon(Icons.send_rounded, size: 20),
            tooltip: 'Modèles Meta',
            onPressed: _showTemplatesSheet,
          ),
        ],
        Builder(
          builder: (context) => IconButton(
            icon: Icon(Icons.person_rounded, size: 20),
            tooltip: 'Contact',
            onPressed: () {
              Scaffold.of(context).openEndDrawer();
            },
          ),
        ),
      ],
    );
  }

  Widget _buildMessageBubble(ChatMessage message) {
    // System message
    if (message.isSystemMessage) {
      return Center(
        child: Container(
          margin: EdgeInsets.symmetric(vertical: 8, horizontal: 16),
          padding: EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            color: Color(0xFFF59E0B).withAlpha(15),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Color(0xFFF59E0B).withAlpha(25)),
          ),
          child: Text(
            message.body,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 12,
              color: Color(0xFFFDE68A),
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      );
    }

    final isOutgoing = !message.isIncoming;

    return Align(
      alignment: isOutgoing ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: EdgeInsets.symmetric(vertical: 3),
        padding: EdgeInsets.all(10),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.78,
        ),
        decoration: BoxDecoration(
          color: isOutgoing ? Theme.of(context).colorScheme.primary : (Theme.of(context).brightness == Brightness.dark ? Color(0xFF1E293B) : Theme.of(context).colorScheme.surface),
          borderRadius: BorderRadius.only(
            topLeft: Radius.circular(16),
            topRight: Radius.circular(16),
            bottomLeft: isOutgoing ? Radius.circular(16) : Radius.circular(4),
            bottomRight: isOutgoing ? Radius.circular(4) : Radius.circular(16),
          ),
          border: Border.all(
            color: isOutgoing ? Theme.of(context).colorScheme.primary.withAlpha(30) : Theme.of(context).colorScheme.onSurface.withOpacity(0.03),
            width: 0.5,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            // Media preview
            if (message.mediaUrl != null &&
                (message.mediaUrl!.toLowerCase().endsWith('.jpg') ||
                    message.mediaUrl!.toLowerCase().endsWith('.png') ||
                    message.mediaUrl!.toLowerCase().endsWith('.jpeg') ||
                    message.mediaUrl!.toLowerCase().endsWith('.gif') ||
                    message.type == 'image'))
              Padding(
                padding: EdgeInsets.only(bottom: 6),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Image.network(
                    message.mediaUrl!,
                    fit: BoxFit.cover,
                    loadingBuilder: (context, child, progress) {
                      if (progress == null) return child;
                      return Container(
                        height: 150,
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.onSurface.withOpacity(0.04),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Center(child: CircularProgressIndicator(strokeWidth: 2, color: Theme.of(context).colorScheme.primary)),
                      );
                    },
                    errorBuilder: (context, err, stack) {
                      return Container(
                        padding: EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.onSurface.withOpacity(0.04),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.broken_image_rounded, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.24), size: 20),
                            SizedBox(width: 8),
                            Text('Image indisponible', style: TextStyle(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31))),
                          ],
                        ),
                      );
                    },
                  ),
                ),
              ),

            // Audio bubble
            if (message.type == 'audio')
              VoicePlayBubble(message: message)
            else
              Text(
                message.body,
                style: TextStyle(fontSize: 14.5, color: Theme.of(context).colorScheme.onSurface),
              ),

            SizedBox(height: 4),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  message.timestamp.length >= 16
                      ? message.timestamp.substring(11, 16)
                      : message.timestamp,
                  style: TextStyle(
                    fontSize: 10,
                    color: Theme.of(context).colorScheme.onSurface.withOpacity(0.24),
                  ),
                ),
                if (isOutgoing) ...[
                  SizedBox(width: 4),
                  _buildStatusIcon(message.status),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInputBar() {
    return SafeArea(
      child: Container(
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          border: Border(top: BorderSide(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.03))),
        ),
        padding: EdgeInsets.symmetric(horizontal: 8.0, vertical: 6.0),
        child: _isRecording
            ? Row(
                children: [
                  Icon(Icons.fiber_manual_record, color: Color(0xFFEF4444), size: 16),
                  SizedBox(width: 8),
                  Text(
                    'Enregistrement... ${_recordingSeconds ~/ 60}:${(_recordingSeconds % 60).toString().padLeft(2, '0')}',
                    style: TextStyle(color: Color(0xFFFCA5A5), fontWeight: FontWeight.w500, fontSize: 13),
                  ),
                  Spacer(),
                  TextButton(
                    onPressed: _cancelRecording,
                    child: Text('Annuler', style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39), fontSize: 13)),
                  ),
                  SizedBox(width: 4),
                  GestureDetector(
                    onTap: _sendVoiceNote,
                    child: Container(
                      padding: EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [Theme.of(context).colorScheme.primary, Color(0xFF0F766E)],
                        ),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(Icons.check_rounded, color: Colors.white, size: 20),
                    ),
                  ),
                ],
              )
            : Row(
                children: [
                  IconButton(
                    icon: Icon(
                      _showEmojiRow ? Icons.keyboard_rounded : Icons.sentiment_satisfied_alt_rounded,
                      color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39),
                      size: 22,
                    ),
                    onPressed: () {
                      setState(() {
                        _showEmojiRow = !_showEmojiRow;
                      });
                    },
                  ),
                  IconButton(
                    icon: Icon(Icons.attach_file_rounded, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39), size: 22),
                    onPressed: _showAttachmentMenu,
                  ),
                  Expanded(
                    child: Container(
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.04)),
                      ),
                      child: TextField(
                        controller: _messageController,
                        minLines: 1,
                        maxLines: 5,
                        style: TextStyle(color: Theme.of(context).colorScheme.onSurface, fontSize: 14),
                        decoration: InputDecoration(
                          hintText: 'Taper un message...',
                          hintStyle: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.24), fontSize: 14),
                          border: InputBorder.none,
                          enabledBorder: InputBorder.none,
                          focusedBorder: InputBorder.none,
                          contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          suffixIcon: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                icon: Icon(Icons.flash_on_rounded, color: Color(0xFFF59E0B), size: 20),
                                tooltip: 'Réponses rapides',
                                onPressed: _showQuickRepliesSheet,
                              ),
                              IconButton(
                                icon: Icon(Icons.mic_rounded, color: _accentColor, size: 20),
                                tooltip: 'Note vocale',
                                onPressed: _startRecording,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                  SizedBox(width: 6),
                  GestureDetector(
                    onTap: _handleSend,
                    child: Container(
                      padding: EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [Theme.of(context).colorScheme.primary, Color(0xFF0F766E)],
                        ),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(Icons.send_rounded, color: Colors.white, size: 20),
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}

// Custom Voice note component
class VoicePlayBubble extends StatefulWidget {
  final ChatMessage message;
  const VoicePlayBubble({super.key, required this.message});

  @override
  State<VoicePlayBubble> createState() => _VoicePlayBubbleState();
}

class _VoicePlayBubbleState extends State<VoicePlayBubble> {
  bool _isPlaying = false;
  double _sliderValue = 0.0;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _togglePlay() {
    if (_isPlaying) {
      _timer?.cancel();
      setState(() {
        _isPlaying = false;
      });
    } else {
      setState(() {
        _isPlaying = true;
      });
      _timer = Timer.periodic(const Duration(milliseconds: 100), (timer) {
        setState(() {
          _sliderValue += 0.04;
          if (_sliderValue >= 1.0) {
            _sliderValue = 0.0;
            _isPlaying = false;
            _timer?.cancel();
          }
        });
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    const accentColor = Color(0xFF2DD4BF);

    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          GestureDetector(
            onTap: _togglePlay,
            child: Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary.withAlpha(40),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                _isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded,
                color: accentColor,
                size: 20,
              ),
            ),
          ),
          SizedBox(width: 8),
          Icon(Icons.mic_rounded, color: accentColor.withAlpha(150), size: 16),
          SizedBox(width: 4),
          Expanded(
            child: SizedBox(
              width: 140,
              child: SliderTheme(
                data: SliderTheme.of(context).copyWith(
                  thumbShape: SliderComponentShape.noThumb,
                  trackHeight: 3,
                  overlayShape: SliderComponentShape.noOverlay,
                  activeTrackColor: accentColor,
                  inactiveTrackColor: Theme.of(context).colorScheme.onSurface.withOpacity(0.1),
                ),
                child: Slider(
                  value: _sliderValue,
                  onChanged: (val) {},
                ),
              ),
            ),
          ),
          SizedBox(width: 8),
          Text(
            widget.message.body.contains('(')
                ? widget.message.body.split('(').last.replaceAll(')', '')
                : '0:05',
            style: TextStyle(fontSize: 11, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39)),
          ),
        ],
      ),
    );
  }
}

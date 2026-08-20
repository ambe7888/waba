import 'dart:async';
import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:file_picker/file_picker.dart';
import 'package:record/record.dart';
import 'package:path_provider/path_provider.dart';
import 'package:audioplayers/audioplayers.dart';
import 'package:video_player/video_player.dart';
import 'package:share_plus/share_plus.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
import 'package:gal/gal.dart';
import '../services/api_service.dart';
import '../services/fcm_service.dart';
import '../models/contact.dart';
import '../models/chat_message.dart';
import '../config/app_config.dart';
import 'contact_info_drawer.dart';
import 'order_creation_sheet.dart';
import 'canned_replies_screen.dart';
import 'send_template_screen.dart';

class ChatBoxScreen extends StatefulWidget {
  final Contact contact;
  final bool openTemplatePicker;

  const ChatBoxScreen({
    super.key,
    required this.contact,
    this.openTemplatePicker = false,
  });

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
  bool _isFirstLoad = true; // Track first load for auto-scroll
  int _lastMessageCount = 0; // Track new message detection

  // Reply-to state: the message the composer is currently quoting
  ChatMessage? _replyingTo;

  // Older-messages pagination ("load older messages" bar, like the web
  // dashboard): 0 once there's nothing older left to fetch.
  int _olderMessagesNextPage = 0;
  bool _isLoadingOlderMessages = false;

  // Media download/share/gallery state
  bool _isDownloadingMedia = false;

  // Search State
  bool _isSearching = false;
  String _searchQuery = '';

  // Emoji Row State
  bool _showEmojiRow = false;

  // Recording State
  bool _isRecording = false;
  int _recordingSeconds = 0;
  Timer? _recordingTimer;
  final _audioRecorder = AudioRecorder();
  String? _localRecordingPath;

  // Canned Replies State
  List<Map<String, dynamic>> _cannedReplies = [];
  List<Map<String, dynamic>> _filteredCannedReplies = [];
  bool _showCannedSuggestions = false;

  // Design constants

  static const _accentColor = Color(0xFF2DD4BF);
  static const _chatBgLight = Color(0xFFF3F6FA);
  // Deep dark

  void _showChatNotice(String message,
      {BuildContext? targetContext, Duration? duration}) {
    final ctx = targetContext ?? context;
    ScaffoldMessenger.of(ctx).showSnackBar(
      SnackBar(
        backgroundColor: const Color(0xFFE5E7EB),
        content: Text(
          message,
          style: const TextStyle(
              color: Colors.black87, fontWeight: FontWeight.w600),
        ),
        behavior: SnackBarBehavior.floating,
        duration: duration ?? const Duration(seconds: 2),
      ),
    );
  }

  void _showTemplatesSheet() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => SendTemplateScreen(
          contactUid: widget.contact.uid,
          contactName: widget.contact.name,
        ),
      ),
    );
  }

  @override
  void initState() {
    super.initState();
    // Instant preloading of preview message for zero wait time
    if (widget.contact.lastMessage != null && widget.contact.lastMessage!.isNotEmpty) {
      _messages = [
        ChatMessage(
          uid: 'preview_${widget.contact.uid}',
          body: widget.contact.lastMessage!,
          isIncoming: true,
          timestamp: widget.contact.lastMessageTime ?? DateTime.now().toIso8601String(),
          status: 'delivered',
        )
      ];
      _isLoading = false;
      _loadMessages(silent: true);
    } else {
      _loadMessages();
    }

    if (widget.openTemplatePicker) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _showTemplatesSheet();
        }
      });
    }

    // Listen to real-time incoming messages via FCM
    _fcmSubscription = FcmService().onMessage.listen((_) {
      _loadMessages(silent: true);
    });

    // Optimized polling interval
    _pollingTimer = Timer.periodic(
      const Duration(seconds: pollingIntervalSeconds),
      (_) => _loadMessages(silent: true),
    );
    _loadCannedReplies();
    _messageController.addListener(_onMessageTextChanged);
  }

  Future<void> _loadCannedReplies() async {
    final list = await ApiService().fetchCannedReplies();
    final quickBots = await ApiService().fetchQuickReplies(widget.contact.uid);
    final botManagement = await ApiService().fetchBotReplies();

    final List<Map<String, dynamic>> combined = [];

    // 1. Add active bot quick replies (e.g. shipping, return, etc.)
    if (quickBots.isNotEmpty) {
      for (final bot in quickBots) {
        final botName = (bot['name'] ?? bot['trigger'] ?? bot['title'] ?? 'Bot').toString();
        combined.add({
          'is_bot': true,
          'bot_id': bot['_id'] ?? bot['id'] ?? bot['_uid'],
          'shortcut': '/${botName.replaceAll(' ', '_')}',
          'name': botName,
          'message': bot['reply_text'] ?? bot['message_body'] ?? bot['message'] ?? '',
        });
      }
    }

    // 2. Add bot management replies if any
    if (botManagement != null && botManagement['bot_replies'] is List) {
      for (final bot in (botManagement['bot_replies'] as List)) {
        final botId = bot['_id'] ?? bot['id'] ?? bot['_uid'];
        final botName = (bot['name'] ?? bot['name_or_trigger'] ?? bot['trigger'] ?? bot['title'] ?? '').toString();
        final botMsg = (bot['reply_text'] ?? bot['reply'] ?? bot['message'] ?? '').toString();
        if (botId != null && botName.isNotEmpty && !combined.any((c) => c['bot_id'] == botId)) {
          combined.add({
            'is_bot': true,
            'bot_id': botId,
            'shortcut': '/${botName.replaceAll(' ', '_')}',
            'name': botName,
            'message': botMsg,
          });
        }
      }
    }

    // 3. Add canned replies / notes
    for (final cr in list) {
      final sc = cr['shortcut']?.toString() ?? 'Note';
      combined.add({
        'is_bot': false,
        'shortcut': sc.startsWith('/') ? sc : '/$sc',
        'name': sc,
        'message': cr['message'] ?? '',
      });
    }

    if (mounted) {
      setState(() {
        _cannedReplies = combined;
      });
    }
  }

  void _onMessageTextChanged() {
    final text = _messageController.text;
    if (text.startsWith('/')) {
      final query = text.substring(1).toLowerCase();
      final filtered = _cannedReplies.where((reply) {
        final shortcut = reply['shortcut']?.toString().toLowerCase() ?? '';
        final name = reply['name']?.toString().toLowerCase() ?? '';
        final msg = reply['message']?.toString().toLowerCase() ?? '';
        return shortcut.contains(query) || name.contains(query) || msg.contains(query);
      }).toList();

      setState(() {
        _filteredCannedReplies = filtered;
        _showCannedSuggestions = filtered.isNotEmpty;
      });
    } else {
      if (_showCannedSuggestions) {
        setState(() {
          _showCannedSuggestions = false;
        });
      }
    }
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
      final localTs = DateTime.tryParse(m.timestamp);
      bool existsSimilar = orderedMessages.any((apiMsg) {
        if (apiMsg.isIncoming || apiMsg.body != m.body) {
          return false;
        }
        final apiTs = DateTime.tryParse(apiMsg.timestamp);
        if (apiTs == null || localTs == null) {
          return false;
        }
        // Reconcile only if API message is around/after local optimistic one.
        final diff = apiTs.difference(localTs).inSeconds;
        return diff >= -2 && diff <= 60;
      });
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
        // Detect if a truly new message arrived (count increased)
        final newMsgArrived = combinedMessages.length > _lastMessageCount;
        final wasAtBottom = _isAtBottom();
        
        final wasFirstLoad = _isFirstLoad;
        setState(() {
          _messages = combinedMessages;
          _isLoading = false;
          _lastMessageCount = combinedMessages.length;
          // Only the very first fetch establishes whether older messages
          // exist — later silent polls always re-fetch just page 1 and
          // would otherwise stomp on pagination progress from
          // _loadOlderMessages.
          if (wasFirstLoad) {
            _olderMessagesNextPage = ApiService().lastMessagesNextPage;
          }
        });

        // Scroll to bottom only on first load, or if user is already at bottom and a new message arrives
        if (_isFirstLoad || (newMsgArrived && wasAtBottom)) {
          _scrollToBottom();
          _isFirstLoad = false;
        }
      }
    } else {
      if (mounted && _isLoading) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  /// "Load older messages" bar shown at the top of the conversation
  /// (mirrors the web dashboard's own older-messages control) instead of
  /// relying purely on scroll-up infinite loading.
  Widget _buildLoadOlderMessagesBar() {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Center(
        child: InkWell(
          onTap: _isLoadingOlderMessages ? null : _loadOlderMessages,
          borderRadius: BorderRadius.circular(20),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: isDark ? const Color(0xFF1E293B) : Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.1)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.06),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (_isLoadingOlderMessages)
                  const SizedBox(
                    width: 14,
                    height: 14,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                else
                  Icon(Icons.history_rounded, size: 16, color: _accentColor),
                const SizedBox(width: 8),
                Text(
                  _isLoadingOlderMessages
                      ? 'Chargement...'
                      : 'Voir les messages plus anciens',
                  style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.75)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// Fetches and appends the next older page of messages. Appending to the
  /// *end* of [_messages] (which, since the list is newest-first and the
  /// ListView is reversed, renders further *up* the screen) never shifts
  /// the messages the user is currently looking at.
  Future<void> _loadOlderMessages() async {
    if (_isLoadingOlderMessages || _olderMessagesNextPage == 0) return;
    setState(() => _isLoadingOlderMessages = true);

    final result = await ApiService()
        .fetchOlderMessages(widget.contact.uid, _olderMessagesNextPage);

    if (!mounted) return;
    if (result == null) {
      setState(() => _isLoadingOlderMessages = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Erreur lors du chargement des anciens messages')),
      );
      return;
    }

    final older = List<ChatMessage>.from(result['messages'] as List<ChatMessage>);
    older.sort((a, b) {
      final dtA = DateTime.tryParse(a.timestamp);
      final dtB = DateTime.tryParse(b.timestamp);
      if (dtA == null || dtB == null) return 0;
      return dtB.compareTo(dtA);
    });

    setState(() {
      final existingUids = _messages.map((m) => m.uid).toSet();
      for (final m in older) {
        if (!existingUids.contains(m.uid)) {
          _messages.add(m);
        }
      }
      _olderMessagesNextPage = result['nextPage'] as int;
      _isLoadingOlderMessages = false;
    });
  }

  void _startAggressivePolling() {
    int count = 0;
    Timer.periodic(Duration(milliseconds: aggressivePollingIntervalMs),
        (timer) {
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

    final replyTarget = _replyingTo;
    _messageController.clear();
    setState(() {
      _showEmojiRow = false;
      _replyingTo = null;
    });

    final tempMsg = ChatMessage(
      uid: UniqueKey().toString(),
      body: text,
      isIncoming: false,
      timestamp: DateTime.now().toIso8601String(),
      repliedToUid: replyTarget?.uid,
    );

    setState(() {
      _messages.insert(0, tempMsg);
    });
    _scrollToBottom(); // Always scroll after user sends

    final success = await ApiService().sendMessage(
      widget.contact.uid,
      text,
      replyToWamid: replyTarget?.wamid,
    );
    if (!success) {
      _loadMessages(silent: true);
    } else {
      _loadMessages(silent: true);
      _startAggressivePolling();
    }
  }

  /// Send a single emoji as a quoted reply to [message] (quick-react).
  /// The Cloud API's native reaction type isn't reliably usable for
  /// business-initiated reactions in every account tier, and this app
  /// already renders customer reactions as emoji-only reply bubbles
  /// (see backend's incoming-reaction handling), so we mirror that same
  /// representation here for consistency instead of a separate code path.
  Future<void> _sendEmojiReaction(ChatMessage message, String emoji) async {
    final tempMsg = ChatMessage(
      uid: UniqueKey().toString(),
      body: emoji,
      isIncoming: false,
      timestamp: DateTime.now().toIso8601String(),
      repliedToUid: message.uid,
    );
    setState(() {
      _messages.insert(0, tempMsg);
    });
    _scrollToBottom();

    final success = await ApiService().sendMessage(
      widget.contact.uid,
      emoji,
      replyToWamid: message.wamid,
    );
    if (success) {
      _startAggressivePolling();
    }
    _loadMessages(silent: true);
  }

  void _setReplyTarget(ChatMessage message) {
    setState(() {
      _replyingTo = message;
    });
  }

  ChatMessage? _findRepliedToMessage(String repliedToUid) {
    try {
      return _messages.firstWhere((m) => m.uid == repliedToUid);
    } catch (_) {
      return null;
    }
  }

  /// Returns true if the user is near the bottom of the chat (position <= 100px in reversed list)
  bool _isAtBottom() {
    if (!_scrollController.hasClients) return true;
    return _scrollController.position.pixels <= 100.0;
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
        return Icon(Icons.access_time_rounded,
            size: 13,
            color: Theme.of(context)
                .colorScheme
                .onSurface
                .withValues(alpha: 0.31));
      case 'accepted':
      case 'sent':
        return Icon(Icons.done_rounded,
            size: 14,
            color: Theme.of(context)
                .colorScheme
                .onSurface
                .withValues(alpha: 0.47));
      case 'delivered':
        return Icon(Icons.done_all_rounded,
            size: 14,
            color: Theme.of(context)
                .colorScheme
                .onSurface
                .withValues(alpha: 0.47));
      case 'read':
        return Icon(Icons.done_all_rounded, size: 14, color: _accentColor);
      default:
        return Icon(Icons.done_rounded,
            size: 14,
            color: Theme.of(context)
                .colorScheme
                .onSurface
                .withValues(alpha: 0.47));
    }
  }

  // Voice Recording functions
  Future<void> _startRecording() async {
    try {
      if (await _audioRecorder.hasPermission()) {
        final tempDir = await getTemporaryDirectory();
        final path =
            '${tempDir.path}/recorded_audio_${DateTime.now().millisecondsSinceEpoch}.m4a';
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
          SnackBar(
              content:
                  Text('Erreur lors du démarrage de l\'enregistrement: $e')),
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
      _recordingSeconds = 0;
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
      final uploadedFileName =
          await ApiService().uploadTempMedia(file, 'whatsapp_audio');
      if (uploadedFileName == null) {
        if (mounted) {
          final errDetail = ApiService().lastUploadError ?? 'Erreur inconnue';
          _showChatNotice('Erreur envoi vocal : $errDetail',
              duration: const Duration(seconds: 5));
        }
        return;
      }

      final durationString =
          '${_recordingSeconds ~/ 60}:${(_recordingSeconds % 60).toString().padLeft(2, '0')}';
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
            const SnackBar(
                content: Text('Erreur lors de l\'envoi de la note vocale')),
          );
        }
        _loadMessages(silent: true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
              content: Text('Erreur lors de l\'envoi de la note vocale: $e')),
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
                    return Center(
                        child: CircularProgressIndicator(
                            color: Theme.of(context).colorScheme.primary));
                  }
                  if (snapshot.hasError ||
                      !snapshot.hasData ||
                      snapshot.data!.isEmpty) {
                    return Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.smart_toy_outlined,
                            size: 56,
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withValues(alpha: 0.16)),
                        SizedBox(height: 16),
                        Text(
                          'Aucune réponse rapide disponible.',
                          style: TextStyle(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.39),
                              fontSize: 15),
                        ),
                        SizedBox(height: 16),
                        TextButton(
                          onPressed: () => Navigator.pop(context),
                          child: Text('Fermer',
                              style: TextStyle(
                                  color:
                                      Theme.of(context).colorScheme.primary)),
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
                          border: Border(
                              bottom: BorderSide(
                                  color: Theme.of(context)
                                      .colorScheme
                                      .onSurface
                                      .withValues(alpha: 0.06))),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.smart_toy_rounded,
                                color: _accentColor, size: 22),
                            SizedBox(width: 8),
                            Text(
                              'Réponses Rapides',
                              style: TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w700,
                                  color:
                                      Theme.of(context).colorScheme.onSurface),
                            ),
                            Spacer(),
                            IconButton(
                              icon: Icon(Icons.close_rounded,
                                  color: Theme.of(context)
                                      .colorScheme
                                      .onSurface
                                      .withValues(alpha: 0.47)),
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
                                  color: Theme.of(context)
                                      .colorScheme
                                      .primary
                                      .withAlpha(30),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(Icons.smart_toy_rounded,
                                    color:
                                        Theme.of(context).colorScheme.primary,
                                    size: 20),
                              ),
                              title: Text(
                                reply['name'] ?? 'Nom inconnu',
                                style: TextStyle(
                                    fontWeight: FontWeight.w600,
                                    color:
                                        Theme.of(context).colorScheme.onSurface,
                                    fontSize: 14),
                              ),
                              subtitle: Text(
                                'Déclencher la réponse automatique',
                                style: TextStyle(
                                    color: Theme.of(context)
                                        .colorScheme
                                        .onSurface
                                        .withValues(alpha: 0.31),
                                    fontSize: 12),
                              ),
                              onTap: () async {
                                final currentContext = context;
                                Navigator.pop(currentContext); // Close BottomSheet
                                
                                showDialog(
                                  context: context,
                                  barrierDismissible: false,
                                  builder: (_) => Center(
                                      child: CircularProgressIndicator(
                                          color: Theme.of(context).colorScheme.primary)),
                                );
                                
                                bool success = false;
                                try {
                                  success = await ApiService().sendQuickReply(
                                      widget.contact.uid, reply['_id']);
                                } finally {
                                  if (mounted) {
                                    Navigator.pop(context); // Close dialog
                                  }
                                }

                                if (mounted) {
                                  if (success) {
                                    _showChatNotice('Réponse auto du bot déclenchée');
                                    _startAggressivePolling();
                                  } else {
                                    _showChatNotice('Erreur lors du déclenchement du bot');
                                  }
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

  Future<void> _sendTemplate(String templateUid, List<String> variables) async {
    final localContext = context;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => Center(
          child: CircularProgressIndicator(
              color: Theme.of(context).colorScheme.primary)),
    );

    final Map<String, dynamic> varMap = {};
    for (int i = 0; i < variables.length; i++) {
      varMap['variable_${i + 1}'] = variables[i];
    }
    final success = await ApiService()
        .sendTemplateMessage(widget.contact.uid, templateUid, varMap);

    if (!localContext.mounted) return;
    Navigator.pop(localContext);
    if (success) {
      _showChatNotice('Modèle WhatsApp envoyé avec succès',
          targetContext: localContext);
      _loadMessages();
      _startAggressivePolling();
    } else {
      _showChatNotice('Erreur lors de l\'envoi du modèle',
          targetContext: localContext);
    }
  }

  // Phone Call function
  Future<void> _makePhoneCall() async {
    final cleanPhone =
        widget.contact.phoneNumber.replaceAll(RegExp(r'[^0-9+]'), '');
    final Uri launchUri = Uri(scheme: 'tel', path: cleanPhone);
    try {
      if (await canLaunchUrl(launchUri)) {
        await launchUrl(launchUri);
      } else {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
              content: Text(
                  'Impossible de lancer l\'appel pour le numéro: $cleanPhone')),
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
          padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Partager du contenu',
                style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w700,
                    color: Theme.of(context).colorScheme.onSurface),
              ),
              const SizedBox(height: 24),
              GridView.count(
                crossAxisCount: 3,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: 16,
                crossAxisSpacing: 12,
                childAspectRatio: 1.1,
                children: [
                  _buildAttachmentItem(
                      Icons.headset_rounded,
                      'Audio',
                      const Color(0xFFF59E0B),
                      () => _pickAndSendMedia('audio')),
                  _buildAttachmentItem(
                      Icons.insert_drive_file_rounded,
                      'Doc',
                      const Color(0xFF6366F1),
                      () => _pickAndSendMedia('document')),
                  _buildAttachmentItem(
                      Icons.photo_rounded,
                      'Image',
                      const Color(0xFF8B5CF6),
                      () => _pickAndSendMedia('image')),
                  _buildAttachmentItem(
                      Icons.video_collection_rounded,
                      'Vidéo',
                      const Color(0xFFEC4899),
                      () => _pickAndSendMedia('video')),
                  _buildAttachmentItem(Icons.shopping_bag_rounded, 'Produit',
                      const Color(0xFF10B981), _showProductPicker),
                  _buildAttachmentItem(Icons.receipt_long_rounded, 'Commande',
                      const Color(0xFF3B82F6), _showOrderCreation),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  void _showOrderCreation({dynamic initialOrder}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => OrderCreationSheet(
        contactUid: widget.contact.uid,
        contactName: widget.contact.name.isNotEmpty
            ? widget.contact.name
            : widget.contact.phoneNumber,
        initialOrder: initialOrder,
        onOrderCreated: () {
          _loadMessages(silent: true);
        },
      ),
    );
  }

  void _showProductPicker() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.75,
          maxChildSize: 0.95,
          minChildSize: 0.5,
          builder: (_, controller) {
            return _ProductPickerSheet(
              scrollController: controller,
              contactUid: widget.contact.uid,
              contactName: widget.contact.name,
              onProductSent: () {
                _loadMessages(silent: true);
              },
            );
          },
        );
      },
    );
  }

  Widget _buildAttachmentItem(
      IconData icon, String label, Color color, VoidCallback onTap) {
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
          Text(label,
              style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: Theme.of(context)
                      .colorScheme
                      .onSurface
                      .withValues(alpha: 0.63))),
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
        withData: true,
      );

      if (result == null || result.files.isEmpty) return;

      final picked = result.files.single;
      final originalFilename = picked.name;
      File? file;

      if (picked.path != null && picked.path!.isNotEmpty) {
        file = File(picked.path!);
      } else if (picked.bytes != null && picked.bytes!.isNotEmpty) {
        final tempDir = await getTemporaryDirectory();
        final extension = (picked.extension ?? '').trim();
        final safeExt = extension.isNotEmpty ? '.$extension' : '';
        final tempPath =
            '${tempDir.path}/picked_${DateTime.now().millisecondsSinceEpoch}$safeExt';
        file = File(tempPath);
        await file.writeAsBytes(Uint8List.fromList(picked.bytes!), flush: true);
      }

      if (file == null || !await file.exists()) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Fichier inaccessible sur cet appareil.')),
          );
        }
        return;
      }

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

      final uploadedFileName =
          await ApiService().uploadTempMedia(file, uploadType);
      if (uploadedFileName == null) {
        if (mounted) {
          final errDetail = ApiService().lastUploadError ?? 'Erreur inconnue';
          _showChatNotice('Erreur envoi $originalFilename : $errDetail',
              duration: const Duration(seconds: 5));
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
        caption: '',
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
            SnackBar(
                content: Text('Erreur lors de l\'envoi de $originalFilename')),
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
    _messageController.removeListener(_onMessageTextChanged);
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
        : _messages
            .where((m) =>
                m.body.toLowerCase().contains(_searchQuery.toLowerCase()))
            .toList();

    final isWindowActive = _is24hWindowActive();

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (bool didPop, dynamic result) {
        if (didPop) return;
        Navigator.pop(context, _messages.isNotEmpty ? _messages.first.body : null);
      },
      child: Scaffold(
        backgroundColor: Theme.of(context).brightness == Brightness.dark
            ? Theme.of(context).scaffoldBackgroundColor
            : _chatBgLight,
        endDrawerEnableOpenDragGesture: false,
        endDrawer: ContactInfoDrawer(
          contact: widget.contact,
          onUpdate: () {
            _loadMessages(silent: true);
          },
          onBlockedStatusChanged: () {
            setState(() {});
          },
          onEditOrder: (order) {
            _showOrderCreation(initialOrder: order);
          },
        ),
        appBar: _buildAppBar(isWindowActive),
      body: Container(
        decoration: BoxDecoration(
          image: DecorationImage(
            image: const AssetImage('assets/images/whatsapp_bg.png'),
            fit: BoxFit.cover,
            opacity: Theme.of(context).brightness == Brightness.dark ? 0.25 : 0.4,
          ),
        ),
        child: Column(
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
                          style: TextStyle(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.31)),
                          textAlign: TextAlign.center,
                        ),
                      )
                    : ListView.builder(
                        controller: _scrollController,
                        reverse: true,
                        padding:
                            EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        // Extra trailing item = "load older messages" bar.
                        // Since the list is reversed, the last index renders
                        // at the very top of the screen — exactly where
                        // older history belongs.
                        itemCount: filteredMessages.length +
                            (_searchQuery.isEmpty && _olderMessagesNextPage != 0 ? 1 : 0),
                        itemBuilder: (context, index) {
                          if (index == filteredMessages.length) {
                            return _buildLoadOlderMessagesBar();
                          }
                          final message = filteredMessages[index];
                          return _buildMessageBubble(message);
                        },
                      ),
          ),

          // (Old 24h Window Warning removed, moved to bottom banner)          // Canned Replies Suggestions Overlay
          if (_showCannedSuggestions)
            Container(
              constraints: const BoxConstraints(maxHeight: 200),
              margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.15),
                    blurRadius: 8,
                    offset: const Offset(0, -2),
                  ),
                ],
                border: Border.all(
                    color: Theme.of(context)
                        .colorScheme
                        .onSurface
                        .withValues(alpha: 0.08)),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: ListView.builder(
                  shrinkWrap: true,
                  padding: EdgeInsets.zero,
                  itemCount: _filteredCannedReplies.length,
                  itemBuilder: (context, index) {
                    final reply = _filteredCannedReplies[index];
                    final isBot = reply['is_bot'] == true;
                    final titleText = reply['shortcut'] ?? reply['name'] ?? '';
                    final messageText = reply['message'] ?? '';

                    return ListTile(
                      dense: true,
                      leading: Icon(
                        isBot ? Icons.smart_toy_rounded : Icons.flash_on_rounded,
                        color: isBot ? const Color(0xFF2DD4BF) : const Color(0xFFF59E0B),
                        size: 18,
                      ),
                      title: Text(
                        titleText,
                        style: const TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                      subtitle: Text(
                        messageText.isNotEmpty ? messageText : (reply['name'] ?? ''),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 12),
                      ),
                      onTap: () async {
                        final botId = reply['bot_id'];
                        final botIdInt = botId != null ? (int.tryParse(botId.toString()) ?? 0) : 0;

                        if (isBot && botIdInt > 0) {
                          setState(() {
                            _messageController.clear();
                            _showCannedSuggestions = false;
                          });
                          _confirmBotReply(titleText, messageText, botIdInt);
                        } else {
                          setState(() {
                            _messageController.text = messageText.isNotEmpty ? messageText : titleText;
                            _messageController.selection = TextSelection.fromPosition(
                              TextPosition(offset: _messageController.text.length),
                            );
                            _showCannedSuggestions = false;
                          });
                        }
                      },
                    );
                  },
                ),
              ),
            ),

          // MAIN INPUT BAR
          if (widget.contact.isBlocked)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
              decoration: const BoxDecoration(
                color: Color(0xFFFEE2E2),
                border: Border(top: BorderSide(color: Color(0xFFFCA5A5))),
              ),
              child: const Text(
                'Ce contact est bloqué.\nVous ne pouvez plus envoyer de messages.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Color(0xFFDC2626),
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
            )
          else if (!isWindowActive)
            _build24hWindowClosedBanner()
          else
            _buildInputBar(),
        ],
      ),
      ), // Close Container
    ), // Close Scaffold
    ); // Close PopScope
  }

  PreferredSizeWidget _buildAppBar(bool isWindowActive) {
    return AppBar(
      leading: IconButton(
        icon: const Icon(Icons.arrow_back),
        onPressed: () {
          Navigator.pop(context, _messages.isNotEmpty ? _messages.first.body : null);
        },
      ),
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      elevation: 0,
      title: _isSearching
          ? TextField(
              controller: _searchController,
              style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurface, fontSize: 15),
              decoration: InputDecoration(
                hintText: 'Rechercher un message...',
                hintStyle: TextStyle(
                    color: Theme.of(context)
                        .colorScheme
                        .onSurface
                        .withValues(alpha: 0.31)),
                border: InputBorder.none,
              ),
              autofocus: true,
              onChanged: (val) {
                setState(() {
                  _searchQuery = val.trim();
                });
              },
            )
          : Builder(
              builder: (context) => InkWell(
                onTap: () {
                  Scaffold.of(context).openEndDrawer();
                },
                borderRadius: BorderRadius.circular(8),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4.0),
                  child: Row(
                    children: [
                // Avatar
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        Theme.of(context).colorScheme.primary,
                        _accentColor
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Center(
                    child: Text(
                      widget.contact.name.isNotEmpty
                          ? widget.contact.name[0].toUpperCase()
                          : 'C',
                      style: TextStyle(
                          color: Theme.of(context).colorScheme.onSurface,
                          fontWeight: FontWeight.w700,
                          fontSize: 16),
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
                              style: TextStyle(
                                  fontSize: 15, fontWeight: FontWeight.w700, decoration: widget.contact.isBlocked ? TextDecoration.lineThrough : null),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (widget.contact.isBlocked) ...[
                            SizedBox(width: 4),
                            Icon(Icons.block_rounded, color: Colors.red, size: 14),
                          ],
                          SizedBox(width: 6),
                          Container(
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: isWindowActive
                                  ? _accentColor
                                  : Theme.of(context)
                                      .colorScheme
                                      .onSurface
                                      .withValues(alpha: 0.16),
                              shape: BoxShape.circle,
                            ),
                          ),
                        ],
                      ),
                          Text(
                            widget.contact.phoneNumber,
                            style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w500,
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurface
                                    .withValues(alpha: 0.6)),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ), // Closes Column
                    ), // Closes Expanded
                  ], // Closes Row's children
                ), // Closes Row
              ), // Closes Padding
            ), // Closes InkWell
          ), // Closes Builder
      actions: [
        if (_isSearching)
          IconButton(
            icon: const Icon(Icons.close_rounded),
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
            icon: const Icon(Icons.search_rounded, size: 20),
            tooltip: 'Rechercher',
            onPressed: () {
              setState(() {
                _isSearching = true;
              });
            },
          ),
          IconButton(
            icon: Icon(Icons.call_rounded, size: 20),
            tooltip: 'Appeler',
            onPressed: () {
              launchUrl(Uri.parse('tel:${widget.contact.phoneNumber}'));
            },
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

  TextSpan _parseHtmlToTextSpan(String text, TextStyle baseStyle) {
    final List<TextSpan> children = [];
    final tagRegExp = RegExp(r'(<[^>]+>)');
    final parts = text.split(tagRegExp);
    final matches = tagRegExp.allMatches(text).toList();

    bool isBold = false;
    bool isItalic = false;
    bool isStrikethrough = false;
    bool isCode = false;

    for (int i = 0; i < parts.length; i++) {
      final part = parts[i];
      if (part.isNotEmpty) {
        TextStyle currentStyle = baseStyle;
        if (isBold) {
          currentStyle = currentStyle.copyWith(fontWeight: FontWeight.bold);
        }
        if (isItalic) {
          currentStyle = currentStyle.copyWith(fontStyle: FontStyle.italic);
        }
        if (isStrikethrough) {
          currentStyle =
              currentStyle.copyWith(decoration: TextDecoration.lineThrough);
        }
        if (isCode) {
          currentStyle = currentStyle.copyWith(
            fontFamily: 'monospace',
            backgroundColor: baseStyle.color?.withValues(alpha: 0.08),
          );
        }
        children.add(TextSpan(text: part, style: currentStyle));
      }

      if (i < matches.length) {
        final tag = matches[i].group(0) ?? '';
        if (tag == '<strong>') {
          isBold = true;
        } else if (tag == '</strong>') {
          isBold = false;
        } else if (tag == '<em>') {
          isItalic = true;
        } else if (tag == '</em>') {
          isItalic = false;
        } else if (tag == '<del>') {
          isStrikethrough = true;
        } else if (tag == '</del>') {
          isStrikethrough = false;
        } else if (tag == '<code>' || tag.startsWith('<span')) {
          isCode = true;
        } else if (tag == '</code>' || tag == '</span>') {
          isCode = false;
        }
      }
    }

    return TextSpan(
        children: children.isEmpty
            ? [TextSpan(text: text, style: baseStyle)]
            : children);
  }

  /// Quoted-message preview shown inside a bubble when the message is a
  /// reply, mirroring WhatsApp's own reply blocks (used for both incoming
  /// and outgoing bubbles).
  Widget _buildQuotedReplyBlock(String repliedToUid, bool isDark, Color textColor) {
    final quoted = _findRepliedToMessage(repliedToUid);
    final label = quoted == null
        ? 'Message'
        : (quoted.isIncoming ? widget.contact.name : 'Vous');
    final snippet = quoted == null
        ? 'Message indisponible'
        : (quoted.type != null && quoted.type != 'text'
            ? _mediaTypeLabel(quoted.type!)
            : quoted.body);

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
      decoration: BoxDecoration(
        color: isDark
            ? Colors.black.withValues(alpha: 0.18)
            : Colors.black.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(8),
        border: Border(
          left: BorderSide(color: _accentColor, width: 3),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: _accentColor,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            snippet,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 12,
              color: textColor.withValues(alpha: 0.75),
            ),
          ),
        ],
      ),
    );
  }

  String _mediaTypeLabel(String type) {
    switch (type) {
      case 'image':
        return '📷 Photo';
      case 'video':
        return '🎥 Vidéo';
      case 'audio':
        return '🎤 Note vocale';
      case 'document':
        return '📄 Document';
      default:
        return type;
    }
  }

  Widget _buildMessageBubble(ChatMessage message) {
    // System message
    if (message.isSystemMessage) {
      return Center(
        child: Container(
          margin: EdgeInsets.symmetric(vertical: 8, horizontal: 16),
          padding: EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            color: Color(0xFFE5E7EB),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Color(0xFFCBD5E1)),
          ),
          child: Text(
            message.body,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 12,
              color: Colors.black87,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      );
    }

    final isOutgoing = !message.isIncoming;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = Theme.of(context).colorScheme.primary;
    final outgoingColor = isDark ? primaryColor : const Color(0xFFB9E5C9);
    final incomingColor = isDark ? const Color(0xFF1E293B) : const Color(0xFFE9EDEE);
    final bubbleColor = isOutgoing ? outgoingColor : incomingColor;
    final textColor = isDark ? Colors.white : const Color(0xFF1F2937);
    final msgType = message.type ?? 'text';

    return Align(
      alignment: isOutgoing ? Alignment.centerRight : Alignment.centerLeft,
      child: GestureDetector(
        onLongPressStart: (details) {
          _showMessageActionsPopup(context, message, details.globalPosition);
        },
        child: Container(
          margin: const EdgeInsets.symmetric(vertical: 3),
          padding: const EdgeInsets.all(10),
          constraints: BoxConstraints(
            maxWidth: MediaQuery.of(context).size.width * 0.78,
          ),
          decoration: BoxDecoration(
          color: bubbleColor,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: isOutgoing
                ? const Radius.circular(16)
                : const Radius.circular(4),
            bottomRight: isOutgoing
                ? const Radius.circular(4)
                : const Radius.circular(16),
          ),
          border: Border.all(
            color: isOutgoing
                ? (isDark
                    ? primaryColor.withAlpha(60)
                    : const Color(0xFFA8D5A2))
                : Theme.of(context)
                    .colorScheme
                    .onSurface
                    .withValues(alpha: 0.06),
            width: 0.5,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 3,
              offset: const Offset(0, 1),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            if (message.repliedToUid != null)
              _buildQuotedReplyBlock(message.repliedToUid!, isDark, textColor),
            if (message.referral != null)
              _buildReferralWidget(message.referral!, textColor),
            // IMAGE
            if (msgType == 'image' ||
                (message.mediaUrl != null &&
                    (message.mediaUrl!.toLowerCase().endsWith('.jpg') ||
                        message.mediaUrl!.toLowerCase().endsWith('.png') ||
                        message.mediaUrl!.toLowerCase().endsWith('.jpeg') ||
                        message.mediaUrl!.toLowerCase().endsWith('.gif'))))
              _buildImageContent(message, textColor),

            // AUDIO
            if (msgType == 'audio')
              VoicePlayBubble(message: message)

            // VIDEO
            else if (msgType == 'video')
              VideoBubble(
                message: message,
                onExpand: () => _showFullVideoModal(message),
              )

            // DOCUMENT
            else if (msgType == 'document')
              _buildMediaTile(
                  Icons.insert_drive_file_rounded,
                  message.body.isNotEmpty ? message.body : 'Document',
                  message,
                  textColor,
                  const Color(0xFF6366F1))

            // TEXT (default)
            else if (msgType != 'image')
              RichText(
                text: _parseHtmlToTextSpan(
                  message.body,
                  TextStyle(fontSize: 14.5, color: textColor),
                ),
              ),

            // Caption glued directly under image/video (single block, like
            // WhatsApp) instead of the caption being silently dropped.
            if ((msgType == 'image' || msgType == 'video') && message.body.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: RichText(
                  text: _parseHtmlToTextSpan(message.body, TextStyle(fontSize: 14.5, color: textColor)),
                ),
              ),

            const SizedBox(height: 4),
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
                    color: isOutgoing
                        ? (isDark
                            ? Colors.white.withAlpha(160)
                            : const Color(0xFF1A3C34).withValues(alpha: 0.55))
                        : Theme.of(context)
                            .colorScheme
                            .onSurface
                            .withValues(alpha: 0.24),
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
      ),
    );
  }

  /// Guesses a file extension for a downloaded media message from its URL,
  /// falling back to the message type.
  String _guessMediaExtension(ChatMessage message) {
    final url = message.mediaUrl ?? '';
    final dotIndex = url.lastIndexOf('.');
    if (dotIndex != -1 && dotIndex > url.lastIndexOf('/')) {
      final ext = url.substring(dotIndex);
      if (ext.length <= 6) return ext;
    }
    switch (message.type) {
      case 'image':
        return '.jpg';
      case 'video':
        return '.mp4';
      case 'audio':
        return '.m4a';
      default:
        return '';
    }
  }

  /// Downloads (if not already cached) and either saves a media message's
  /// file to the device's public gallery ([share] = false, images/videos)
  /// or opens the OS share sheet ([share] = true). Reuses the same disk
  /// cache the chat bubbles/full-screen viewer already populated via
  /// [CachedNetworkImage] — [DefaultCacheManager.getSingleFile] only hits
  /// the network if the file isn't cached yet, so "download" here is
  /// usually just copying an already-fetched file into the gallery.
  Future<void> _downloadOrShareMedia(ChatMessage message, {required bool share}) async {
    final url = message.mediaUrl;
    if (url == null || url.isEmpty || _isDownloadingMedia) return;

    setState(() => _isDownloadingMedia = true);
    try {
      final file = await DefaultCacheManager().getSingleFile(url);

      if (share) {
        await Share.shareXFiles([XFile(file.path)]);
      } else if (message.type == 'image') {
        await Gal.putImage(file.path, album: 'WhatsClick');
        if (mounted) _showChatNotice('Enregistré dans la galerie');
      } else if (message.type == 'video') {
        await Gal.putVideo(file.path, album: 'WhatsClick');
        if (mounted) _showChatNotice('Enregistré dans la galerie');
      } else {
        // Documents/audio aren't gallery media — keep them in app storage.
        final docsDir = await getApplicationDocumentsDirectory();
        final savedDir = Directory('${docsDir.path}/WhatsClick Media');
        if (!await savedDir.exists()) {
          await savedDir.create(recursive: true);
        }
        final ext = _guessMediaExtension(message);
        final fileName = 'whatsclick_${DateTime.now().millisecondsSinceEpoch}$ext';
        await file.copy('${savedDir.path}/$fileName');
        if (mounted) _showChatNotice('Fichier enregistré');
      }
    } on GalException catch (e) {
      if (mounted) {
        _showChatNotice(e.type == GalExceptionType.accessDenied
            ? 'Accès à la galerie refusé. Autorisez-le dans les paramètres.'
            : 'Échec de l\'enregistrement dans la galerie');
      }
    } catch (e) {
      if (mounted) _showChatNotice('Échec du téléchargement');
    } finally {
      if (mounted) setState(() => _isDownloadingMedia = false);
    }
  }

  /// 3-dot menu (download/share) shown in the top-right of the fullscreen
  /// image/video viewer.
  Widget _buildFullMediaMenuButton(ChatMessage message) {
    return CircleAvatar(
      backgroundColor: Colors.black.withValues(alpha: 0.6),
      child: PopupMenuButton<String>(
        icon: _isDownloadingMedia
            ? const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
              )
            : const Icon(Icons.more_vert_rounded, color: Colors.white, size: 22),
        onSelected: (value) {
          if (value == 'download') {
            _downloadOrShareMedia(message, share: false);
          } else if (value == 'share') {
            _downloadOrShareMedia(message, share: true);
          }
        },
        itemBuilder: (context) => [
          const PopupMenuItem(
            value: 'download',
            child: Row(
              children: [
                Icon(Icons.download_rounded, size: 18),
                SizedBox(width: 10),
                Text('Télécharger'),
              ],
            ),
          ),
          const PopupMenuItem(
            value: 'share',
            child: Row(
              children: [
                Icon(Icons.share_rounded, size: 18),
                SizedBox(width: 10),
                Text('Partager'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showFullImageModal(ChatMessage message) {
    final imageUrl = message.mediaUrl!;
    showDialog(
      context: context,
      builder: (context) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(12),
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            InteractiveViewer(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: CachedNetworkImage(
                  imageUrl: imageUrl,
                  fit: BoxFit.contain,
                  errorWidget: (_, __, ___) => const Center(
                    child: Text('Impossible de charger l\'image', style: TextStyle(color: Colors.white)),
                  ),
                ),
              ),
            ),
            Positioned(
              right: 8,
              top: 8,
              child: Row(
                children: [
                  _buildFullMediaMenuButton(message),
                  const SizedBox(width: 8),
                  CircleAvatar(
                    backgroundColor: Colors.black.withValues(alpha: 0.6),
                    child: IconButton(
                      icon: const Icon(Icons.close_rounded, color: Colors.white, size: 22),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showFullVideoModal(ChatMessage message) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(12),
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            _FullScreenVideoPlayer(url: message.mediaUrl!),
            Positioned(
              right: 8,
              top: 8,
              child: Row(
                children: [
                  _buildFullMediaMenuButton(message),
                  const SizedBox(width: 8),
                  CircleAvatar(
                    backgroundColor: Colors.black.withValues(alpha: 0.6),
                    child: IconButton(
                      icon: const Icon(Icons.close_rounded, color: Colors.white, size: 22),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildImageContent(ChatMessage message, Color textColor) {
    if (message.mediaUrl == null || message.mediaUrl!.isEmpty) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Row(
          children: [
            Icon(Icons.image_not_supported_rounded,
                color: textColor.withValues(alpha: 0.4), size: 20),
            const SizedBox(width: 8),
            Text('Image en cours de traitement...',
                style: TextStyle(
                    fontSize: 12, color: textColor.withValues(alpha: 0.5))),
          ],
        ),
      );
    }
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: GestureDetector(
        onTap: () => _showFullImageModal(message),
        child: Container(
          width: 220,
          height: 220,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.black.withValues(alpha: 0.1)),
          ),
          clipBehavior: Clip.antiAlias,
          child: CachedNetworkImage(
            imageUrl: message.mediaUrl!,
            width: 220,
            height: 220,
            fit: BoxFit.cover,
            placeholder: (context, url) => Container(
              width: 220,
              height: 220,
              color: Colors.black12,
              child: const Center(
                  child: CircularProgressIndicator(strokeWidth: 2)),
            ),
            errorWidget: (context, url, err) {
              return Padding(
                padding: const EdgeInsets.all(6),
                child: Row(
                  children: [
                    Icon(Icons.broken_image_rounded,
                        color: textColor.withValues(alpha: 0.4), size: 20),
                    const SizedBox(width: 8),
                    Text('Image indisponible',
                        style: TextStyle(
                            fontSize: 12,
                            color: textColor.withValues(alpha: 0.5))),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _buildMediaTile(IconData icon, String label, ChatMessage message,
      Color textColor, Color accentColor) {
    return GestureDetector(
      onTap: () {
        if (message.mediaUrl != null && message.mediaUrl!.isNotEmpty) {
          launchUrl(Uri.parse(message.mediaUrl!),
              mode: LaunchMode.externalApplication);
        }
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        decoration: BoxDecoration(
          color: accentColor.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: accentColor.withValues(alpha: 0.25)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: accentColor, size: 22),
            const SizedBox(width: 10),
            Flexible(
              child: Text(
                label,
                style: TextStyle(
                    fontSize: 13,
                    color: textColor,
                    fontWeight: FontWeight.w500),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            const SizedBox(width: 8),
            if (message.mediaUrl != null && message.mediaUrl!.isNotEmpty)
              Icon(Icons.open_in_new_rounded, color: accentColor, size: 14),
          ],
        ),
      ),
    );
  }

  Widget _buildShortcutPill(String label, VoidCallback onTap, bool isDark,
      {bool isHighlight = false}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: isHighlight
              ? Theme.of(context).colorScheme.primary.withValues(alpha: 0.18)
              : (isDark ? const Color(0xFF1E293B) : const Color(0xFFE2E8F0)),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isHighlight
                ? Theme.of(context).colorScheme.primary.withValues(alpha: 0.4)
                : (isDark ? Colors.white12 : Colors.black12),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontFamily: 'Inter',
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: isHighlight
                ? Theme.of(context).colorScheme.primary
                : (isDark ? const Color(0xFFCBD5E1) : const Color(0xFF334155)),
          ),
        ),
      ),
    );
  }

  Widget _build24hWindowClosedBanner() {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      width: double.infinity,
      padding: EdgeInsets.only(
        left: 16, 
        right: 16, 
        top: 16, 
        bottom: MediaQuery.of(context).padding.bottom > 0 
            ? MediaQuery.of(context).padding.bottom + 8 
            : 16
      ),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : const Color(0xFFF3F4F6),
        border: Border(
          top: BorderSide(
            color: isDark ? const Color(0xFF334155) : const Color(0xFFE5E7EB),
          ),
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            'La fenêtre de service de 24 heures est fermée.',
            style: TextStyle(
              fontSize: 14, 
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Pour reprendre la conversation, vous devez envoyer un modèle de message.',
            style: TextStyle(
              fontSize: 12, 
              color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton.icon(
              onPressed: _showTemplatesSheet,
              icon: const Icon(Icons.dashboard_customize_rounded, color: Colors.white, size: 20),
              label: const Text(
                'Envoyer un modèle',
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF00A884), // WhatsApp-like green
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                elevation: 0,
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Round icon button used for the attach/mic buttons flanking the
  /// composer field — a slightly lighter/whiter circle against the gray
  /// field background so they stand out subtly.
  /// Flat icon button (no background/circle) for the attach/mic buttons
  /// flanking the composer field — icons sit directly on the gray field,
  /// matching WhatsApp's own composer.
  Widget _buildComposerIconButton({
    required IconData icon,
    required VoidCallback onPressed,
    required bool isDark,
    Color? color,
    String? tooltip,
  }) {
    return IconButton(
      icon: Icon(
        icon,
        size: 22,
        color: color ??
            Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.55),
      ),
      tooltip: tooltip,
      padding: EdgeInsets.zero,
      constraints: const BoxConstraints(minWidth: 40, minHeight: 40),
      onPressed: onPressed,
    );
  }

  Widget _buildReplyPreviewBar(bool isDark) {
    final target = _replyingTo!;
    final label = target.isIncoming ? widget.contact.name : 'Vous';
    final snippet = target.type != null && target.type != 'text'
        ? _mediaTypeLabel(target.type!)
        : target.body;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : const Color(0xFFF1F5F9),
        borderRadius: BorderRadius.circular(10),
        border: Border(left: BorderSide(color: _accentColor, width: 3)),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Réponse à $label',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: _accentColor),
                ),
                const SizedBox(height: 2),
                Text(
                  snippet,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                      fontSize: 12,
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)),
                ),
              ],
            ),
          ),
          IconButton(
            icon: Icon(Icons.close_rounded,
                size: 18, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            onPressed: () => setState(() => _replyingTo = null),
          ),
        ],
      ),
    );
  }

  Widget _buildInputBar() {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return SafeArea(
      child: Container(
        // Transparent so the chat wallpaper (applied to the whole screen's
        // body) stays visible behind the composer too, instead of being
        // cut off by a solid background right above the input row.
        decoration: BoxDecoration(
          color: Colors.transparent,
          border: Border(
              top: BorderSide(
                  color: Theme.of(context)
                      .colorScheme
                      .onSurface
                      .withValues(alpha: 0.08))),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 6.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Quick Shortcuts Bar (WhatsMine Agent design)
            if (!_isRecording)
              Container(
                height: 28,
                margin: const EdgeInsets.only(bottom: 6),
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  children: [
            // Dynamic shortcuts from bot quick replies loaded in _cannedReplies
            ..._cannedReplies
                .where((r) => r['is_bot'] == true)
                .take(3)
                .map((r) {
              final shortcut = r['shortcut']?.toString() ?? '';
              final name = r['name']?.toString() ?? shortcut;
              return Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  _buildShortcutPill(name, () {
                    final msg = r['message']?.toString() ?? '';
                    final botId = r['bot_id'];
                    final botIdInt = botId != null ? (int.tryParse(botId.toString()) ?? 0) : 0;
                    if (botIdInt > 0) {
                      _confirmBotReply(name, msg, botIdInt);
                    } else {
                      setState(() {
                        _messageController.text = msg.isNotEmpty ? msg : shortcut;
                        _messageController.selection = TextSelection.fromPosition(
                          TextPosition(offset: _messageController.text.length),
                        );
                      });
                    }
                  }, isDark, isHighlight: false),
                  const SizedBox(width: 6),
                ],
              );
            }),
            _buildShortcutPill(
                '📄 Envoyer un modèle', _showTemplatesSheet, isDark,
                isHighlight: true),
                  ],
                ),
              ),

            // Reply preview (quoted message above the input field)
            if (_replyingTo != null && !_isRecording)
              Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: _buildReplyPreviewBar(isDark),
              ),

            // Emoji Picker Row
            if (_showEmojiRow && !_isRecording)
              Container(
                height: 38,
                margin: const EdgeInsets.only(bottom: 6),
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: ['👍', '❤️', '😂', '🔥', '🙏', '👋', '😊', '🎉', '💯', '👏', '🎁', '🚀', '⭐', '✔️', '👀'].map((emoji) {
                    return InkWell(
                      onTap: () {
                        setState(() {
                          _messageController.text += emoji;
                          _messageController.selection = TextSelection.fromPosition(
                            TextPosition(offset: _messageController.text.length),
                          );
                        });
                      },
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        margin: const EdgeInsets.only(right: 4),
                        decoration: BoxDecoration(
                          color: isDark ? const Color(0xFF1E293B) : const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(emoji, style: const TextStyle(fontSize: 20)),
                      ),
                    );
                  }).toList(),
                ),
              ),

            _isRecording
                ? Row(
                    children: [
                      Icon(Icons.fiber_manual_record,
                          color: Color(0xFFEF4444), size: 16),
                      SizedBox(width: 8),
                      Text(
                        'Enregistrement... ${_recordingSeconds ~/ 60}:${(_recordingSeconds % 60).toString().padLeft(2, '0')}',
                        style: TextStyle(
                            color: Color(0xFFFCA5A5),
                            fontWeight: FontWeight.w500,
                            fontSize: 13),
                      ),
                      Spacer(),
                      TextButton(
                        onPressed: _cancelRecording,
                        child: Text('Annuler',
                            style: TextStyle(
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurface
                                    .withValues(alpha: 0.39),
                                fontSize: 13)),
                      ),
                      SizedBox(width: 4),
                      GestureDetector(
                        onTap: _sendVoiceNote,
                        child: Container(
                          padding: EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                Theme.of(context).colorScheme.primary,
                                Color(0xFF0F766E)
                              ],
                            ),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(Icons.check_rounded,
                              color: Colors.white, size: 20),
                        ),
                      ),
                    ],
                  )
                : Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Expanded(
                        child: Container(
                          constraints: const BoxConstraints(minHeight: 44),
                          padding: const EdgeInsets.symmetric(horizontal: 4),
                          decoration: BoxDecoration(
                            color: isDark
                                ? const Color(0xFF1E293B)
                                : const Color(0xFFE9EDEF),
                            borderRadius: BorderRadius.circular(24),
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              _buildComposerIconButton(
                                icon: Icons.attach_file_rounded,
                                onPressed: _showAttachmentMenu,
                                isDark: isDark,
                              ),
                              Expanded(
                                child: TextField(
                                  controller: _messageController,
                                  minLines: 1,
                                  maxLines: 5,
                                  style: TextStyle(
                                      color: Theme.of(context).colorScheme.onSurface,
                                      fontSize: 14),
                                  decoration: InputDecoration(
                                    // Emoji toggle lives here, at the field's
                                    // natural leading position, instead of
                                    // as a separate outer button.
                                    prefixIcon: IconButton(
                                      icon: Icon(
                                        _showEmojiRow
                                            ? Icons.keyboard_rounded
                                            : Icons.sentiment_satisfied_alt_rounded,
                                        color: Theme.of(context)
                                            .colorScheme
                                            .onSurface
                                            .withValues(alpha: 0.39),
                                        size: 22,
                                      ),
                                      padding: EdgeInsets.zero,
                                      onPressed: () {
                                        setState(() {
                                          _showEmojiRow = !_showEmojiRow;
                                        });
                                      },
                                    ),
                                    prefixIconConstraints:
                                        const BoxConstraints(minWidth: 36, minHeight: 36),
                                    hintText: "Message ou '/' pour réponses rapides...",
                                    hintStyle: TextStyle(
                                        color: Theme.of(context)
                                            .colorScheme
                                            .onSurface
                                            .withValues(alpha: 0.3),
                                        fontSize: 13),
                                    border: InputBorder.none,
                                    enabledBorder: InputBorder.none,
                                    focusedBorder: InputBorder.none,
                                    isDense: true,
                                    contentPadding: const EdgeInsets.symmetric(vertical: 12),
                                  ),
                                ),
                              ),
                              _buildComposerIconButton(
                                icon: Icons.mic_rounded,
                                onPressed: _startRecording,
                                isDark: isDark,
                                color: _accentColor,
                                tooltip: 'Note vocale',
                              ),
                            ],
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
                              colors: [
                                Theme.of(context).colorScheme.primary,
                                Color(0xFF0F766E)
                              ],
                            ),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(Icons.arrow_upward_rounded,
                              color: Colors.white, size: 22),
                        ),
                      ),
                    ],
                  ),
          ],
        ),
      ),
    );
  }

  Widget _buildReferralWidget(Map<String, dynamic> referral, Color textColor) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final String title =
        referral['headline'] ?? referral['title'] ?? 'Publicité Facebook Ads';
    final String body = referral['body'] ?? referral['description'] ?? '';
    final String? imageUrl = referral['image_url'] ?? referral['thumbnail_url'];
    final String? sourceUrl = referral['source_url'];
    final String mediaType =
        referral['media_type']?.toString().toLowerCase() ?? 'image';
    final bool isVideo = mediaType == 'video';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: isDark
            ? Colors.white.withValues(alpha: 0.06)
            : Colors.black.withValues(alpha: 0.04),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: isDark
              ? Colors.white.withValues(alpha: 0.1)
              : Colors.black.withValues(alpha: 0.06),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              const Icon(
                Icons.campaign_rounded,
                color: Color(0xFF1877F2),
                size: 16,
              ),
              const SizedBox(width: 6),
              const Text(
                'Provenance Facebook Ads',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF1877F2),
                  letterSpacing: 0.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (imageUrl != null && imageUrl.isNotEmpty) ...[
                Stack(
                  alignment: Alignment.center,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(6),
                      child: Image.network(
                        imageUrl,
                        width: 56,
                        height: 56,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                      ),
                    ),
                    if (isVideo)
                      Container(
                        width: 24,
                        height: 24,
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.55),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.play_arrow_rounded,
                          color: Colors.white,
                          size: 16,
                        ),
                      ),
                  ],
                ),
                const SizedBox(width: 8),
              ],
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: textColor,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (body.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        body,
                        style: TextStyle(
                          fontSize: 11,
                          color: textColor.withValues(alpha: 0.7),
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          if (sourceUrl != null && sourceUrl.isNotEmpty) ...[
            const SizedBox(height: 6),
            InkWell(
              onTap: () => launchUrl(Uri.parse(sourceUrl),
                  mode: LaunchMode.externalApplication),
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
                decoration: BoxDecoration(
                  color: const Color(0xFF1877F2).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      isVideo ? 'Voir la vidéo' : 'Voir la publicité',
                      style: const TextStyle(
                        fontSize: 9.5,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1877F2),
                      ),
                    ),
                    const SizedBox(width: 4),
                    const Icon(
                      Icons.open_in_new_rounded,
                      size: 10,
                      color: Color(0xFF1877F2),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  void _showMessageActionsPopup(BuildContext context, ChatMessage message, Offset tapPosition) {
    final screenSize = MediaQuery.of(context).size;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    // Calculate position
    double top = tapPosition.dy;
    double left = tapPosition.dx;
    const double menuWidth = 220;
    const double menuHeight = 250; 

    if (top + menuHeight > screenSize.height) {
      top = top - menuHeight - 20;
    }
    if (left + menuWidth > screenSize.width) {
      left = screenSize.width - menuWidth - 20;
    }
    if (top < 0) top = 20;
    if (left < 0) left = 20;

    showDialog(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.1),
      builder: (context) {
        return Stack(
          children: [
            Positioned(
              top: top,
              left: left,
              child: Material(
                color: Colors.transparent,
                child: Container(
                  width: menuWidth,
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF2D3748) : Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.15),
                        blurRadius: 15,
                        offset: const Offset(0, 5),
                      ),
                    ],
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Emojis Row
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: ['❤️', '👍', '😂', '😮', '😢', '🙏']
                              .map((emoji) => GestureDetector(
                                    onTap: () {
                                      Navigator.pop(context);
                                      _sendEmojiReaction(message, emoji);
                                    },
                                    child: Text(emoji, style: const TextStyle(fontSize: 22)),
                                  ))
                              .toList(),
                        ),
                      ),
                      Divider(height: 1, color: isDark ? Colors.white24 : Colors.black12),
                      _buildPopupMenuItem(
                        icon: Icons.copy_rounded,
                        text: 'Copier',
                        isDark: isDark,
                        onTap: () {
                          Navigator.pop(context);
                          Clipboard.setData(ClipboardData(text: message.body));
                          _showChatNotice('Message copié');
                        },
                      ),
                      Divider(height: 1, color: isDark ? Colors.white24 : Colors.black12),
                      _buildPopupMenuItem(
                        icon: Icons.reply_rounded,
                        text: 'Répondre',
                        isDark: isDark,
                        onTap: () {
                          Navigator.pop(context);
                          _setReplyTarget(message);
                        },
                      ),
                      Divider(height: 1, color: isDark ? Colors.white24 : Colors.black12),
                      _buildPopupMenuItem(
                        icon: Icons.info_outline_rounded,
                        text: 'Infos',
                        isDark: isDark,
                        onTap: () {
                          Navigator.pop(context);
                          _showMessageInfo(message);
                        },
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildPopupMenuItem({
    required IconData icon,
    required String text,
    required bool isDark,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        child: Row(
          children: [
            Icon(icon, size: 20, color: isDark ? Colors.white70 : Colors.black87),
            const SizedBox(width: 12),
            Text(
              text,
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: isDark ? Colors.white : Colors.black87,
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showMessageInfo(ChatMessage message) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Infos du message', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildInfoRow(Icons.person_outline, 'Agent', message.vendorName ?? 'Inconnu'),
              const SizedBox(height: 12),
              _buildInfoRow(Icons.access_time_rounded, 'Envoyé le', message.createdAt ?? 'Inconnu'),
              const SizedBox(height: 12),
              _buildInfoRow(
                  message.isIncoming ? Icons.call_received_rounded : Icons.check_circle_outline_rounded, 
                  'Statut', 
                  message.status.toUpperCase()),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Fermer', style: TextStyle(fontWeight: FontWeight.bold)),
            ),
          ],
        );
      },
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Colors.grey),
        const SizedBox(width: 8),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
            Text(value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
          ],
        ),
      ],
    );
  }

  void _confirmBotReply(String name, String msg, int botIdInt) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirmer l\'envoi'),
        content: Text('Voulez-vous envoyer la réponse auto "$name" ?\n\n$msg'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _showChatNotice('Envoi de la réponse auto...');
              ApiService().sendQuickReply(widget.contact.uid, botIdInt).then((sent) {
                if (mounted) {
                  if (sent) {
                    _showChatNotice('Réponse auto envoyée : $name');
                    _loadMessages(silent: true);
                  } else {
                    _showChatNotice('Erreur lors de l\'envoi de la réponse auto');
                  }
                }
              });
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Theme.of(context).colorScheme.primary,
              foregroundColor: Colors.white,
            ),
            child: const Text('Envoyer'),
          ),
        ],
      ),
    );
  }

}

/// Inline video bubble with a small play icon; tapping opens the fullscreen
/// player (via [onExpand]) instead of launching an external app.
class VideoBubble extends StatefulWidget {
  final ChatMessage message;
  final VoidCallback? onExpand;
  const VideoBubble({super.key, required this.message, this.onExpand});

  @override
  State<VideoBubble> createState() => _VideoBubbleState();
}

class _VideoBubbleState extends State<VideoBubble> {
  VideoPlayerController? _controller;
  bool _initFailed = false;

  @override
  void initState() {
    super.initState();
    final url = widget.message.mediaUrl;
    if (url != null && url.isNotEmpty) {
      _controller = VideoPlayerController.networkUrl(Uri.parse(url))
        ..initialize().then((_) {
          if (mounted) setState(() {});
        }).catchError((_) {
          if (mounted) setState(() => _initFailed = true);
        });
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.message.mediaUrl == null || widget.message.mediaUrl!.isEmpty) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Text('Vidéo en cours de traitement...',
            style: TextStyle(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5))),
      );
    }

    final controller = _controller;
    final ready = controller != null && controller.value.isInitialized;

    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: GestureDetector(
        onTap: widget.onExpand,
        child: Container(
          width: 220,
          height: 220,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            color: Colors.black12,
            border: Border.all(color: Colors.black.withValues(alpha: 0.1)),
          ),
          clipBehavior: Clip.antiAlias,
          child: Stack(
            alignment: Alignment.center,
            children: [
              if (ready)
                FittedBox(
                  fit: BoxFit.cover,
                  child: SizedBox(
                    width: controller.value.size.width,
                    height: controller.value.size.height,
                    child: VideoPlayer(controller),
                  ),
                )
              else if (_initFailed)
                const Icon(Icons.broken_image_rounded, color: Colors.white54, size: 32)
              else
                const CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.45),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 28),
              ),
              if (widget.onExpand != null)
                Positioned(
                  right: 6,
                  top: 6,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.45),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.fullscreen_rounded, color: Colors.white, size: 16),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Fullscreen, autoplaying, tap-to-pause video player with a scrub bar.
class _FullScreenVideoPlayer extends StatefulWidget {
  final String url;
  const _FullScreenVideoPlayer({required this.url});

  @override
  State<_FullScreenVideoPlayer> createState() => _FullScreenVideoPlayerState();
}

class _FullScreenVideoPlayerState extends State<_FullScreenVideoPlayer> {
  late VideoPlayerController _controller;
  bool _ready = false;

  @override
  void initState() {
    super.initState();
    _controller = VideoPlayerController.networkUrl(Uri.parse(widget.url))
      ..initialize().then((_) {
        if (mounted) {
          setState(() => _ready = true);
          _controller.play();
        }
      });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!_ready) {
      return const SizedBox(
        width: 200,
        height: 200,
        child: Center(child: CircularProgressIndicator(color: Colors.white)),
      );
    }
    return GestureDetector(
      onTap: () {
        setState(() {
          _controller.value.isPlaying ? _controller.pause() : _controller.play();
        });
      },
      child: AspectRatio(
        aspectRatio: _controller.value.aspectRatio,
        child: Stack(
          alignment: Alignment.bottomCenter,
          children: [
            VideoPlayer(_controller),
            if (!_controller.value.isPlaying)
              const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 64),
            VideoProgressIndicator(_controller, allowScrubbing: true),
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
  final AudioPlayer _player = AudioPlayer();
  PlayerState _playerState = PlayerState.stopped;
  Duration _duration = Duration.zero;
  Duration _position = Duration.zero;

  @override
  void initState() {
    super.initState();
    _player.onPlayerStateChanged.listen((state) {
      if (mounted) setState(() => _playerState = state);
    });
    _player.onDurationChanged.listen((d) {
      if (mounted) setState(() => _duration = d);
    });
    _player.onPositionChanged.listen((p) {
      if (mounted) setState(() => _position = p);
    });
    _player.onPlayerComplete.listen((_) {
      if (mounted) setState(() => _position = Duration.zero);
    });
  }

  @override
  void dispose() {
    _player.dispose();
    super.dispose();
  }

  Future<void> _togglePlay() async {
    final url = widget.message.mediaUrl;
    if (url == null || url.isEmpty) return;

    if (_playerState == PlayerState.playing) {
      await _player.pause();
    } else {
      await _player.play(UrlSource(url));
    }
  }

  String _formatDuration(Duration d) {
    final m = d.inMinutes.remainder(60).toString().padLeft(1, '0');
    final s = d.inSeconds.remainder(60).toString().padLeft(2, '0');
    return '$m:$s';
  }

  @override
  Widget build(BuildContext context) {
    const accentColor = Color(0xFF2DD4BF);
    final isPlaying = _playerState == PlayerState.playing;
    final hasUrl =
        widget.message.mediaUrl != null && widget.message.mediaUrl!.isNotEmpty;
    final total = _duration.inMilliseconds > 0
        ? _duration.inMilliseconds.toDouble()
        : 1.0;
    final current = _position.inMilliseconds.clamp(0, total.toInt()).toDouble();

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          GestureDetector(
            onTap: hasUrl ? _togglePlay : null,
            child: Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary.withAlpha(40),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded,
                color: hasUrl ? accentColor : Colors.grey,
                size: 20,
              ),
            ),
          ),
          const SizedBox(width: 8),
          Icon(Icons.mic_rounded, color: accentColor.withAlpha(150), size: 16),
          const SizedBox(width: 4),
          Expanded(
            child: SizedBox(
              width: 140,
              child: SliderTheme(
                data: SliderTheme.of(context).copyWith(
                  thumbShape:
                      const RoundSliderThumbShape(enabledThumbRadius: 6),
                  trackHeight: 3,
                  overlayShape: SliderComponentShape.noOverlay,
                  activeTrackColor: accentColor,
                  inactiveTrackColor: Theme.of(context)
                      .colorScheme
                      .onSurface
                      .withValues(alpha: 0.1),
                  thumbColor: accentColor,
                ),
                child: Slider(
                  value: current,
                  min: 0,
                  max: total,
                  onChanged: hasUrl
                      ? (val) {
                          _player.seek(Duration(milliseconds: val.toInt()));
                        }
                      : null,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Text(
            isPlaying || _position > Duration.zero
                ? _formatDuration(_position)
                : _duration > Duration.zero
                    ? _formatDuration(_duration)
                    : '0:00',
            style: TextStyle(
                fontSize: 11,
                color: Theme.of(context)
                    .colorScheme
                    .onSurface
                    .withValues(alpha: 0.55)),
          ),
        ],
      ),
    );
  }
}

class _ProductPickerSheet extends StatefulWidget {
  final ScrollController scrollController;
  final String contactUid;
  final String contactName;
  final VoidCallback onProductSent;

  const _ProductPickerSheet({
    required this.scrollController,
    required this.contactUid,
    required this.contactName,
    required this.onProductSent,
  });

  @override
  State<_ProductPickerSheet> createState() => _ProductPickerSheetState();
}

class _ProductPickerSheetState extends State<_ProductPickerSheet> {
  final _searchController = TextEditingController();
  List<Map<String, dynamic>> _products = [];
  bool _isLoading = true;
  bool _isSending = false;
  String _searchQuery = '';
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _loadProducts() async {
    setState(() {
      _isLoading = true;
    });
    final products = await ApiService().fetchProducts(search: _searchQuery);
    if (mounted) {
      setState(() {
        _products = products;
        _isLoading = false;
      });
    }
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      if (mounted) {
        setState(() {
          _searchQuery = query;
        });
        _loadProducts();
      }
    });
  }

  void _confirmAndSendProduct(Map<String, dynamic> product) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Envoyer le produit'),
        content: Text(
            'Voulez-vous envoyer "${product['name']}" à ${widget.contactName} ?'),
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
              Navigator.pop(ctx);
              setState(() {
                _isSending = true;
              });
              final success = await ApiService()
                  .sendProductMessage(widget.contactUid, product['_uid'] ?? '');
              if (mounted) {
                setState(() {
                  _isSending = false;
                });
                if (success) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Produit envoyé avec succès')),
                  );
                  widget.onProductSent();
                  Navigator.pop(context); // Close bottom sheet
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                        content: Text('Échec de l\'envoi du produit')),
                  );
                }
              }
            },
            child: const Text('Envoyer'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              border: Border(
                  bottom: BorderSide(
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.06))),
            ),
            child: Row(
              children: [
                const Icon(Icons.shopping_bag_rounded,
                    color: Color(0xFF10B981), size: 22),
                const SizedBox(width: 8),
                Text(
                  'Sélectionner un produit',
                  style: TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w700,
                      color: Theme.of(context).colorScheme.onSurface),
                ),
                const Spacer(),
                IconButton(
                  icon: Icon(Icons.close_rounded,
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.47)),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          // Search Input
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Rechercher un produit...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          _onSearchChanged('');
                        },
                      )
                    : null,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: BorderSide(
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.12)),
                ),
                contentPadding:
                    const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
              ),
            ),
          ),
          // Content
          Expanded(
            child: Stack(
              children: [
                if (_isLoading)
                  Center(
                      child: CircularProgressIndicator(
                          color: Theme.of(context).colorScheme.primary))
                else if (_products.isEmpty)
                  Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.shopping_bag_outlined,
                            size: 56,
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withValues(alpha: 0.16)),
                        const SizedBox(height: 16),
                        Text(
                          'Aucun produit trouvé',
                          style: TextStyle(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.39),
                              fontSize: 15),
                        ),
                      ],
                    ),
                  )
                else
                  GridView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    controller: widget.scrollController,
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                      childAspectRatio: 0.72,
                    ),
                    itemCount: _products.length,
                    itemBuilder: (context, index) {
                      final product = _products[index];
                      final String? imageUrl = product['image_url'];
                      final double price = double.tryParse(product['price']?.toString() ?? '0') ?? 0;

                      return GestureDetector(
                        onTap: _isSending ? null : () => _confirmAndSendProduct(product),
                        child: Container(
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.04),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              )
                            ],
                          ),
                          clipBehavior: Clip.antiAlias,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Image
                              Expanded(
                                child: Container(
                                  width: double.infinity,
                                  color: Colors.black12,
                                  child: (imageUrl != null && imageUrl.isNotEmpty)
                                      ? Image.network(
                                          imageUrl,
                                          fit: BoxFit.cover,
                                          errorBuilder: (context, err, stack) => const Icon(Icons.shopping_cart, color: Colors.grey, size: 40),
                                        )
                                      : const Icon(Icons.shopping_cart, color: Colors.grey, size: 40),
                                ),
                              ),
                              // Info
                              Padding(
                                padding: const EdgeInsets.all(10.0),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      product['name'] ?? 'Produit sans nom',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                          fontWeight: FontWeight.w700,
                                          color: Theme.of(context).colorScheme.onSurface,
                                          fontSize: 13),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      '${price.toStringAsFixed(0)} CFA',
                                      style: const TextStyle(
                                          fontWeight: FontWeight.w800,
                                          color: Color(0xFF10B981),
                                          fontSize: 14),
                                    ),
                                    const SizedBox(height: 8),
                                    SizedBox(
                                      width: double.infinity,
                                      height: 32,
                                      child: ElevatedButton(
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: const Color(0xFF2DD4BF),
                                          foregroundColor: Colors.white,
                                          elevation: 0,
                                          padding: EdgeInsets.zero,
                                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                        ),
                                        onPressed: _isSending ? null : () => _confirmAndSendProduct(product),
                                        child: const Text('Envoyer', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                if (_isSending)
                  Container(
                    color: Colors.black12,
                    child: const Center(child: CircularProgressIndicator()),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

}

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class TicketDetailScreen extends StatefulWidget {
  final String ticketUid;
  final String subject;

  const TicketDetailScreen({
    super.key,
    required this.ticketUid,
    required this.subject,
  });

  @override
  State<TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends State<TicketDetailScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _ticket;
  List<dynamic> _replies = [];
  String? _error;
  
  final _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _isSending = false;

  @override
  void initState() {
    super.initState();
    _fetchDetails();
  }

  Future<void> _fetchDetails() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await ApiService().fetchSupportTicketDetails(widget.ticketUid);
      if (mounted) {
        setState(() {
          if (data != null && data['ticket'] != null) {
            _ticket = data['ticket'];
            _replies = _ticket?['replies'] ?? [];
          } else {
            _error = 'Ticket introuvable';
          }
          _isLoading = false;
        });
        _scrollToBottom();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Erreur: $e';
          _isLoading = false;
        });
      }
    }
  }

  void _scrollToBottom() {
    if (_scrollController.hasClients) {
      Future.delayed(const Duration(milliseconds: 100), () {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      });
    }
  }

  Future<void> _sendReply() async {
    final msg = _messageController.text.trim();
    if (msg.isEmpty) return;

    setState(() {
      _isSending = true;
    });

    final success = await ApiService().replyToSupportTicket(widget.ticketUid, msg);

    if (mounted) {
      setState(() {
        _isSending = false;
      });
      if (success) {
        _messageController.clear();
        _fetchDetails();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Erreur lors de l'envoi")),
        );
      }
    }
  }

  Widget _buildMessageBubble(String sender, String message, String time, bool isMe) {
    final isDark = ThemeService().isDark;
    
    return Align(
      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 16),
        padding: const EdgeInsets.all(12),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
        decoration: BoxDecoration(
          color: isMe 
              ? ThemeService.primaryColor 
              : (isDark ? const Color(0xFF1E293B) : Colors.white),
          borderRadius: BorderRadius.circular(16).copyWith(
            bottomRight: isMe ? const Radius.circular(0) : const Radius.circular(16),
            bottomLeft: !isMe ? const Radius.circular(0) : const Radius.circular(16),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 5,
              offset: const Offset(0, 2),
            )
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (!isMe) ...[
              Text(
                sender,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: ThemeService.primaryColor,
                ),
              ),
              const SizedBox(height: 4),
            ],
            Text(
              message,
              style: TextStyle(
                fontSize: 14,
                color: isMe ? Colors.white : (isDark ? Colors.white : Colors.black87),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              time,
              style: TextStyle(
                fontSize: 10,
                color: isMe ? Colors.white70 : Colors.grey,
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final date = DateTime.parse(dateStr).toLocal();
      return DateFormat('dd/MM/yyyy HH:mm').format(date);
    } catch (_) {
      return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : const Color(0xFFF1F5F9),
      appBar: AppBar(
        backgroundColor: isDark ? ThemeService.darkCard : Colors.white,
        elevation: 1,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Ticket de support',
              style: TextStyle(fontSize: 14, color: Colors.grey),
            ),
            Text(
              widget.subject,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: isDark ? Colors.white : Colors.black87,
              ),
            ),
          ],
        ),
        iconTheme: IconThemeData(color: isDark ? Colors.white : Colors.black87),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: Colors.red)))
              : Column(
                  children: [
                    Expanded(
                      child: ListView(
                        controller: _scrollController,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        children: [
                          // Original ticket message
                          _buildMessageBubble(
                            'Vous',
                            _ticket?['description'] ?? '',
                            _formatDate(_ticket?['created_at']),
                            true,
                          ),
                          
                          // Replies
                          ..._replies.map((reply) {
                            // Check if reply is from vendor or admin
                            final isMe = reply['users__id'] == _ticket?['vendor_user']?['users__id'];
                            final senderName = isMe ? 'Vous' : (reply['user']?['first_name'] ?? 'Support');
                            
                            return _buildMessageBubble(
                              senderName,
                              reply['message'] ?? '',
                              _formatDate(reply['created_at']),
                              isMe,
                            );
                          }).toList(),
                        ],
                      ),
                    ),
                    
                    // Input Area
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(
                        color: isDark ? ThemeService.darkCard : Colors.white,
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.05),
                            offset: const Offset(0, -2),
                            blurRadius: 5,
                          ),
                        ],
                      ),
                      child: SafeArea(
                        child: Row(
                          children: [
                            Expanded(
                              child: TextField(
                                controller: _messageController,
                                maxLines: 4,
                                minLines: 1,
                                style: TextStyle(color: isDark ? Colors.white : Colors.black87),
                                decoration: InputDecoration(
                                  hintText: 'Écrire une réponse...',
                                  hintStyle: const TextStyle(color: Colors.grey),
                                  filled: true,
                                  fillColor: isDark ? ThemeService.darkSurface : const Color(0xFFF1F5F9),
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(24),
                                    borderSide: BorderSide.none,
                                  ),
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            CircleAvatar(
                              backgroundColor: ThemeService.primaryColor,
                              radius: 24,
                              child: _isSending
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                    )
                                  : IconButton(
                                      icon: const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                                      onPressed: _sendReply,
                                    ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }
}

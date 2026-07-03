import re

file_path = r'c:\xampp\htdocs\waba\whatsclick_mobile\lib\screens\ticket_detail_screen.dart'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Imports
if 'import \'dart:io\';' not in content:
    content = content.replace("import 'package:flutter/material.dart';", "import 'package:flutter/material.dart';\nimport 'dart:io';\nimport 'package:file_picker/file_picker.dart';")

# 2. Variables and Pick File method
if '_selectedFile' not in content:
    content = content.replace("  final ScrollController _scrollController = ScrollController();", "  final ScrollController _scrollController = ScrollController();\n  File? _selectedFile;\n\n  Future<void> _pickFile() async {\n    try {\n      final result = await FilePicker.platform.pickFiles();\n      if (result != null && result.files.single.path != null) {\n        setState(() {\n          _selectedFile = File(result.files.single.path!);\n        });\n      }\n    } catch (e) {\n      if (mounted) {\n        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));\n      }\n    }\n  }\n")

# 3. _sendReply update
old_send_reply = """  Future<void> _sendReply() async {
    final msg = _messageController.text.trim();
    if (msg.isEmpty) return;

    setState(() {
      _isSending = true;
    });

    final success = await ApiService().replyToSupportTicket(widget.ticketUid, msg);

    if (mounted) {
      if (success) {
        _messageController.clear();
        await _fetchDetails();
      } else {"""
new_send_reply = """  Future<void> _sendReply() async {
    final msg = _messageController.text.trim();
    if (msg.isEmpty && _selectedFile == null) return;

    setState(() {
      _isSending = true;
    });

    final success = await ApiService().replyToSupportTicket(
      widget.ticketUid, 
      msg.isEmpty ? 'Pièce jointe' : msg,
      attachment: _selectedFile,
    );

    if (mounted) {
      if (success) {
        _messageController.clear();
        _selectedFile = null;
        await _fetchDetails();
      } else {"""
content = content.replace(old_send_reply, new_send_reply)

# 4. _buildMessageBubble signature
content = content.replace("Widget _buildMessageBubble(String sender, String message, String time, bool isMe) {", "Widget _buildMessageBubble(String sender, String message, String time, bool isMe, {Map<String, dynamic>? attachment}) {")

# 5. _buildMessageBubble content
old_bubble_content = """            Text(
              message,
              style: TextStyle(
                fontSize: 14,
                color: isMe ? Colors.white : (isDark ? Colors.white : Colors.black87),
              ),
            ),
            const SizedBox(height: 6),"""
new_bubble_content = """            Text(
              message,
              style: TextStyle(
                fontSize: 14,
                color: isMe ? Colors.white : (isDark ? Colors.white : Colors.black87),
              ),
            ),
            if (attachment != null) ...[
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: isMe ? Colors.white24 : Colors.black12,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.attach_file, size: 16, color: isMe ? Colors.white : (isDark ? Colors.white70 : Colors.black54)),
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text(
                        attachment['file_name'] ?? 'Fichier',
                        style: TextStyle(
                          fontSize: 12,
                          color: isMe ? Colors.white : (isDark ? Colors.white70 : Colors.black87),
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 6),"""
content = content.replace(old_bubble_content, new_bubble_content)

# 6. build method map updates
old_call_vous = """                           _buildMessageBubble(
                             'Vous',
                             _ticket?['description'] ?? '',
                             _formatDate(_ticket?['created_at']),
                             true,
                           ),"""
new_call_vous = """                           _buildMessageBubble(
                             'Vous',
                             _ticket?['description'] ?? '',
                             _formatDate(_ticket?['created_at']),
                             true,
                             attachment: _ticket?['__data']?['attachment'],
                           ),"""
content = content.replace(old_call_vous, new_call_vous)

old_call_reply = """                            return _buildMessageBubble(
                              senderName,
                              reply['message'] ?? '',
                              _formatDate(reply['created_at']),
                              isMe,
                            );"""
new_call_reply = """                            return _buildMessageBubble(
                              senderName,
                              reply['message'] ?? '',
                              _formatDate(reply['created_at']),
                              isMe,
                              attachment: reply['__data']?['attachment'],
                            );"""
content = content.replace(old_call_reply, new_call_reply)

# 7. Input Area Update
old_input = """                      child: SafeArea(
                        child: Row(
                          children: [
                            Expanded("""
new_input = """                      child: SafeArea(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (_selectedFile != null)
                              Container(
                                margin: const EdgeInsets.only(bottom: 8),
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                decoration: BoxDecoration(
                                  color: isDark ? ThemeService.darkSurface : const Color(0xFFF1F5F9),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  children: [
                                    const Icon(Icons.attach_file, size: 20, color: Colors.grey),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(
                                        _selectedFile!.path.split('/').last,
                                        style: TextStyle(color: isDark ? Colors.white70 : Colors.black87),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    IconButton(
                                      icon: const Icon(Icons.close, size: 20, color: Colors.red),
                                      onPressed: () => setState(() => _selectedFile = null),
                                      padding: EdgeInsets.zero,
                                      constraints: const BoxConstraints(),
                                    ),
                                  ],
                                ),
                              ),
                            Row(
                              children: [
                                IconButton(
                                  icon: const Icon(Icons.attach_file, color: Colors.grey),
                                  onPressed: _pickFile,
                                  padding: EdgeInsets.zero,
                                ),
                                const SizedBox(width: 4),
                                Expanded("""
content = content.replace(old_input, new_input)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patcher finished successfully!")

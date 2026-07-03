import re

file_path = r'c:\xampp\htdocs\waba\whatsclick_mobile\lib\screens\ticket_detail_screen.dart'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Variables
content = content.replace("  File? _selectedFile;", "  List<File> _selectedFiles = [];")

# 2. Pick File method
old_pick = """  Future<void> _pickFile() async {
    try {
      final result = await FilePicker.platform.pickFiles();
      if (result != null && result.files.single.path != null) {
        setState(() {
          _selectedFile = File(result.files.single.path!);
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));
      }
    }
  }"""
new_pick = """  Future<void> _pickFile() async {
    try {
      final result = await FilePicker.platform.pickFiles(allowMultiple: true);
      if (result != null) {
        setState(() {
          _selectedFiles.addAll(
            result.files.where((f) => f.path != null).map((f) => File(f.path!))
          );
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));
      }
    }
  }"""
content = content.replace(old_pick, new_pick)

# 3. _sendReply update
old_send = """  Future<void> _sendReply() async {
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
new_send = """  Future<void> _sendReply() async {
    final msg = _messageController.text.trim();
    if (msg.isEmpty && _selectedFiles.isEmpty) return;

    setState(() {
      _isSending = true;
    });

    final success = await ApiService().replyToSupportTicket(
      widget.ticketUid, 
      msg.isEmpty ? 'Pièce(s) jointe(s)' : msg,
      attachments: _selectedFiles,
    );

    if (mounted) {
      if (success) {
        _messageController.clear();
        _selectedFiles.clear();
        await _fetchDetails();
      } else {"""
content = content.replace(old_send, new_send)

# 4. _buildMessageBubble signature
content = content.replace("Widget _buildMessageBubble(String sender, String message, String time, bool isMe, {Map<String, dynamic>? attachment}) {", "Widget _buildMessageBubble(String sender, String message, String time, bool isMe, {List<dynamic>? attachments}) {")

# 5. _buildMessageBubble content
old_bubble = """            if (attachment != null) ...[
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
            ],"""
new_bubble = """            if (attachments != null && attachments.isNotEmpty) ...[
              const SizedBox(height: 6),
              ...attachments.map((attachment) => Container(
                margin: const EdgeInsets.only(bottom: 4),
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
              )).toList(),
            ],"""
content = content.replace(old_bubble, new_bubble)

# 6. build method map updates
old_call_vous = """                             attachment: _ticket?['__data']?['attachment'],"""
new_call_vous = """                             attachments: _ticket?['__data']?['attachments'] ?? (_ticket?['__data']?['attachment'] != null ? [_ticket!['__data']['attachment']] : null),"""
content = content.replace(old_call_vous, new_call_vous)

old_call_reply = """                               attachment: reply['__data']?['attachment'],"""
new_call_reply = """                               attachments: reply['__data']?['attachments'] ?? (reply['__data']?['attachment'] != null ? [reply['__data']['attachment']] : null),"""
content = content.replace(old_call_reply, new_call_reply)

# 7. Input Area Update
old_input = """                            if (_selectedFile != null)
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
                                      icon: const Icon(Icons.close,
                                          size: 20, color: Colors.red),
                                      onPressed: () =>
                                          setState(() => _selectedFile = null),
                                      padding: EdgeInsets.zero,
                                      constraints: const BoxConstraints(),
                                    ),
                                  ],
                                ),
                              ),"""
new_input = """                            if (_selectedFiles.isNotEmpty)
                              Container(
                                margin: const EdgeInsets.only(bottom: 8),
                                width: double.infinity,
                                child: Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: _selectedFiles.map((file) => Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                    decoration: BoxDecoration(
                                      color: isDark ? ThemeService.darkSurface : const Color(0xFFF1F5F9),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        const Icon(Icons.attach_file, size: 16, color: Colors.grey),
                                        const SizedBox(width: 4),
                                        Flexible(
                                          child: Text(
                                            file.path.split('/').last,
                                            style: TextStyle(fontSize: 12, color: isDark ? Colors.white70 : Colors.black87),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        GestureDetector(
                                          onTap: () {
                                            setState(() {
                                              _selectedFiles.remove(file);
                                            });
                                          },
                                          child: const Icon(Icons.close, size: 16, color: Colors.red),
                                        ),
                                      ],
                                    ),
                                  )).toList(),
                                ),
                              ),"""

# We need to account for dart format having split lines in the old_input
# Let's write a regex or simpler replace
import re
content = re.sub(r'if \(_selectedFile != null\).*?Row\(\s*children: \[', new_input + "\n                            Row(\n                              children: [", content, flags=re.DOTALL)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patch applied to ticket_detail_screen.dart")

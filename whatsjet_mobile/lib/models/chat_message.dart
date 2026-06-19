class ChatMessage {
  final String uid;
  final String body;
  final bool isIncoming;
  final String timestamp;
  final String? type; // text, image, document, video, audio, etc.
  final String? mediaUrl;
  final bool isSystemMessage;
  final String status;

  ChatMessage({
    required this.uid,
    required this.body,
    required this.isIncoming,
    required this.timestamp,
    this.type = 'text',
    this.mediaUrl,
    this.isSystemMessage = false,
    this.status = 'initialize',
  });

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    var incomingRaw = json['is_incoming'] ?? json['is_incoming_message'];
    bool incoming = incomingRaw == 1 || incomingRaw == true || incomingRaw == '1' || incomingRaw == 'true';
    
    var systemRaw = json['is_system_message'];
    bool system = systemRaw == 1 || systemRaw == true || systemRaw == '1' || systemRaw == 'true';

    return ChatMessage(
      uid: json['uid'] ?? json['_uid'] ?? '',
      body: json['message'] ?? json['body'] ?? '',
      isIncoming: incoming,
      timestamp: json['created_at'] ?? json['timestamp'] ?? '',
      type: json['type'] ?? 'text',
      mediaUrl: json['media_url'] ?? json['file_url'],
      isSystemMessage: system,
      status: json['status'] ?? 'initialize',
    );
  }

  /// Check if this message has meaningfully changed compared to another
  bool hasChangedFrom(ChatMessage other) {
    return uid != other.uid ||
        body != other.body ||
        type != other.type ||
        status != other.status ||
        mediaUrl != other.mediaUrl;
  }
}

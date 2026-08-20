import 'dart:convert';

class ChatMessage {
  final String uid;
  final String body;
  final bool isIncoming;
  final String timestamp;
  final String? type; // text, image, document, video, audio, etc.
  final String? mediaUrl;
  final bool isSystemMessage;
  final String status;
  final Map<String, dynamic>? referral;
  final String? wamid;
  final String? repliedToUid;

  ChatMessage({
    required this.uid,
    required this.body,
    required this.isIncoming,
    required this.timestamp,
    this.type = 'text',
    this.mediaUrl,
    this.isSystemMessage = false,
    this.status = 'initialize',
    this.referral,
    this.wamid,
    this.repliedToUid,
  });

  String? get vendorName => null;
  String? get createdAt => timestamp;

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    var incomingRaw = json['is_incoming'] ?? json['is_incoming_message'];
    bool incoming = incomingRaw == 1 || incomingRaw == true || incomingRaw == '1' || incomingRaw == 'true';
    
    var systemRaw = json['is_system_message'];
    bool system = systemRaw == 1 || systemRaw == true || systemRaw == '1' || systemRaw == 'true';

    // Resolve type and media url. The backend stores media info inside
    // __data['media_values'] (link/type/caption), not as top-level fields.
    String resolvedType = json['type'] ?? 'text';
    String? resolvedMediaUrl = json['media_url'] ?? json['file_url'];
    String resolvedBody = json['message'] ?? json['body'] ?? '';
    Map<String, dynamic>? resolvedReferral;

    dynamic dataField = json['__data'];
    // __data can arrive as a JSON string or a pre-decoded Map
    if (dataField is String && dataField.isNotEmpty) {
      try {
        dataField = Map<String, dynamic>.from(
          const JsonDecoder().convert(dataField) as Map,
        );
      } catch (_) {
        dataField = null;
      }
    }
    if (dataField is Map) {
      // Parse referral data if exists
      final dynamic refVal = dataField['referral'];
      if (refVal is Map) {
        resolvedReferral = Map<String, dynamic>.from(refVal);
        // Strip the referral prefix that Laravel's message accessor prepends
        // Pattern: "📢 *[Provenance Pub Facebook]*\n...\n--------------------------------\n"
        final sepIndex = resolvedBody.indexOf('--------------------------------');
        if (sepIndex != -1) {
          resolvedBody = resolvedBody.substring(sepIndex + '--------------------------------'.length).trim();
        }
      }

      final dynamic mediaValues = dataField['media_values'];
      if (mediaValues is Map) {
        final String? mvType = mediaValues['type']?.toString();
        final String? mvLink = mediaValues['link']?.toString();
        final String? mvCaption = mediaValues['caption']?.toString();
        if (mvType != null && mvType.isNotEmpty) {
          // Normalize sticker to image for rendering
          resolvedType = mvType == 'sticker' ? 'image' : mvType;
        }
        if (mvLink != null && mvLink.isNotEmpty) {
          resolvedMediaUrl = mvLink;
        }
        // Use caption as body text when message is empty
        if (resolvedBody.isEmpty && mvCaption != null && mvCaption.isNotEmpty) {
          resolvedBody = mvCaption;
        }
      }
    }

    return ChatMessage(
      uid: json['uid']?.toString() ?? json['_uid']?.toString() ?? '',
      body: resolvedBody,
      isIncoming: incoming,
      timestamp: json['created_at']?.toString() ?? json['timestamp']?.toString() ?? '',
      type: resolvedType,
      mediaUrl: resolvedMediaUrl,
      isSystemMessage: system,
      status: json['status']?.toString() ?? 'initialize',
      referral: resolvedReferral,
      wamid: json['wamid']?.toString(),
      repliedToUid: json['replied_to_whatsapp_message_logs__uid']?.toString(),
    );
  }

  /// Check if this message has meaningfully changed compared to another
  bool hasChangedFrom(ChatMessage other) {
    return uid != other.uid ||
        body != other.body ||
        type != other.type ||
        status != other.status ||
        mediaUrl != other.mediaUrl ||
        referral != other.referral;
  }
}

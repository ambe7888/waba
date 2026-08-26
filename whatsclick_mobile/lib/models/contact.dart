class ContactLabel {
  final int id;
  final String uid;
  final String title;
  final String? textColor;
  final String? bgColor;

  ContactLabel({required this.id, required this.uid, required this.title, this.textColor, this.bgColor});

  factory ContactLabel.fromJson(Map<String, dynamic> json) {
    return ContactLabel(
      id: json['id'] ?? json['_id'] ?? 0,
      uid: json['uid'] ?? json['_uid'] ?? '',
      title: json['title'] ?? '',
      textColor: json['text_color'],
      bgColor: json['bg_color'],
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContactLabel && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;
}

class Contact {
  final int? id;
  final String uid;
  final String name;
  String get fullName => name;
  final String phoneNumber;
  final String? avatar;
  final int unreadCount;
  final String? lastMessage;
  final String? lastMessageTime;
  final List<ContactLabel> labels;
  final String? lastMessageStatus;
  /// Whether the last message came from the contact. Only outgoing
  /// messages get delivery ticks, same as WhatsApp.
  final bool lastMessageIsIncoming;
  /// 'text', 'image', 'video', 'audio', 'voice', 'document', 'sticker',
  /// 'location', 'system', 'template', 'interactive' - drives the small
  /// icon shown before the preview text.
  final String? lastMessageType;
  final bool? isAiBotActive;
  final String? status;
  final String? assignedUserName;
  final bool isBlocked;

  Contact({
    this.id,
    required this.uid,
    required this.name,
    required this.phoneNumber,
    this.avatar,
    this.unreadCount = 0,
    this.lastMessage,
    this.lastMessageTime,
    this.labels = const [],
    this.lastMessageStatus,
    this.lastMessageIsIncoming = true,
    this.lastMessageType,
    this.isAiBotActive = true,
    this.status = 'Open',
    this.assignedUserName,
    this.isBlocked = false,
  });

  factory Contact.fromJson(Map<String, dynamic> json) {
    String? lastMsg;
    String? lastMsgTime;
    String? lastMsgStatus;
    bool lastMsgIncoming = true;
    String? lastMsgType;
    if (json['last_message'] is Map) {
      final lm = json['last_message'];
      // preview_text is built server-side and is the only field that also
      // covers template and media sends, whose `message` column is empty.
      lastMsg = lm['preview_text'] ?? lm['message'] ?? lm['message_body'];
      lastMsgTime = lm['created_at'] ?? lm['updated_at'];
      lastMsgStatus = lm['status'];
      lastMsgType = lm['preview_type']?.toString();
      final rawIncoming = lm['is_incoming_message'];
      lastMsgIncoming =
          rawIncoming == 1 || rawIncoming == true || rawIncoming == '1' || rawIncoming == 'true';
    } else {
      lastMsg = json['last_message'] ?? json['last_message_body'] ?? json['lastMessage'];
      lastMsgTime = json['updated_at'] ?? json['last_message_time'];
    }

    final labelsList = json['labels'] as List?;
    final parsedLabels = labelsList != null
        ? labelsList.map((l) => ContactLabel.fromJson(Map<String, dynamic>.from(l as Map))).toList()
        : <ContactLabel>[];

    return Contact(
      id: int.tryParse(json['id']?.toString() ?? json['_id']?.toString() ?? ''),
      uid: json['uid']?.toString() ?? json['_uid']?.toString() ?? '',
      name: json['full_name']?.toString() ?? json['name']?.toString() ?? json['first_name']?.toString() ?? json['wa_name']?.toString() ?? json['wa_id']?.toString() ?? 'Inconnu',
      phoneNumber: json['phone_number']?.toString() ?? json['wa_id']?.toString() ?? json['phoneNumber']?.toString() ?? '',
      avatar: json['avatar']?.toString() ?? json['avatar_url']?.toString(),
      unreadCount: int.tryParse(json['unread_messages_count']?.toString() ?? json['unread_count']?.toString() ?? json['unreadCount']?.toString() ?? '') ?? 0,
      lastMessage: lastMsg?.toString(),
      lastMessageTime: lastMsgTime?.toString(),
      labels: parsedLabels,
      lastMessageStatus: lastMsgStatus?.toString(),
      lastMessageIsIncoming: lastMsgIncoming,
      lastMessageType: lastMsgType,
      isAiBotActive: json['is_ai_bot_active'] ?? json['is_bot_active'] ?? (json['disable_ai_bot'] == 0),
      status: json['status']?.toString() ?? 'Open',
      assignedUserName: json['assigned_user_name']?.toString() ?? json['assigned_to']?['name']?.toString(),
      isBlocked: json['wa_blocked_at'] != null,
    );
  }

  /// Get unique set of label titles from this contact
  Set<String> get labelTitles => labels.map((l) => l.title).toSet();

  Contact copyWith({
    String? uid,
    String? name,
    String? phoneNumber,
    String? avatar,
    int? unreadCount,
    String? lastMessage,
    String? lastMessageTime,
    List<ContactLabel>? labels,
    String? lastMessageStatus,
    bool? lastMessageIsIncoming,
    String? lastMessageType,
    bool? isAiBotActive,
    String? status,
    String? assignedUserName,
    bool? isBlocked,
  }) {
    return Contact(
      id: id,
      uid: uid ?? this.uid,
      name: name ?? this.name,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      avatar: avatar ?? this.avatar,
      unreadCount: unreadCount ?? this.unreadCount,
      lastMessage: lastMessage ?? this.lastMessage,
      lastMessageTime: lastMessageTime ?? this.lastMessageTime,
      labels: labels ?? this.labels,
      lastMessageStatus: lastMessageStatus ?? this.lastMessageStatus,
      lastMessageIsIncoming: lastMessageIsIncoming ?? this.lastMessageIsIncoming,
      lastMessageType: lastMessageType ?? this.lastMessageType,
      isAiBotActive: isAiBotActive ?? this.isAiBotActive,
      status: status ?? this.status,
      assignedUserName: assignedUserName ?? this.assignedUserName,
      isBlocked: isBlocked ?? this.isBlocked,
    );
  }
}

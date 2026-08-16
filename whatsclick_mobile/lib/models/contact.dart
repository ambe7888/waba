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
  final bool? isAiBotActive;
  final String? status;
  final String? assignedUserName;
  final bool isBlocked;

  Contact({
    required this.uid,
    required this.name,
    required this.phoneNumber,
    this.avatar,
    this.unreadCount = 0,
    this.lastMessage,
    this.lastMessageTime,
    this.labels = const [],
    this.lastMessageStatus,
    this.isAiBotActive = true,
    this.status = 'Open',
    this.assignedUserName,
    this.isBlocked = false,
  });

  factory Contact.fromJson(Map<String, dynamic> json) {
    String? lastMsg;
    String? lastMsgTime;
    String? lastMsgStatus;
    if (json['last_message'] is Map) {
      lastMsg = json['last_message']['message'] ?? json['last_message']['message_body'];
      lastMsgTime = json['last_message']['created_at'] ?? json['last_message']['updated_at'];
      lastMsgStatus = json['last_message']['status'];
    } else {
      lastMsg = json['last_message'] ?? json['last_message_body'] ?? json['lastMessage'];
      lastMsgTime = json['updated_at'] ?? json['last_message_time'];
    }

    final labelsList = json['labels'] as List?;
    final parsedLabels = labelsList != null
        ? labelsList.map((l) => ContactLabel.fromJson(Map<String, dynamic>.from(l as Map))).toList()
        : <ContactLabel>[];

    return Contact(
      uid: json['uid']?.toString() ?? json['_uid']?.toString() ?? '',
      name: json['full_name']?.toString() ?? json['name']?.toString() ?? json['first_name']?.toString() ?? json['wa_name']?.toString() ?? json['wa_id']?.toString() ?? 'Inconnu',
      phoneNumber: json['phone_number']?.toString() ?? json['wa_id']?.toString() ?? json['phoneNumber']?.toString() ?? '',
      avatar: json['avatar']?.toString() ?? json['avatar_url']?.toString(),
      unreadCount: int.tryParse(json['unread_messages_count']?.toString() ?? json['unread_count']?.toString() ?? json['unreadCount']?.toString() ?? '') ?? 0,
      lastMessage: lastMsg?.toString(),
      lastMessageTime: lastMsgTime?.toString(),
      labels: parsedLabels,
      lastMessageStatus: lastMsgStatus?.toString(),
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
    bool? isAiBotActive,
    String? status,
    String? assignedUserName,
    bool? isBlocked,
  }) {
    return Contact(
      uid: uid ?? this.uid,
      name: name ?? this.name,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      avatar: avatar ?? this.avatar,
      unreadCount: unreadCount ?? this.unreadCount,
      lastMessage: lastMessage ?? this.lastMessage,
      lastMessageTime: lastMessageTime ?? this.lastMessageTime,
      labels: labels ?? this.labels,
      lastMessageStatus: lastMessageStatus ?? this.lastMessageStatus,
      isAiBotActive: isAiBotActive ?? this.isAiBotActive,
      status: status ?? this.status,
      assignedUserName: assignedUserName ?? this.assignedUserName,
      isBlocked: isBlocked ?? this.isBlocked,
    );
  }
}

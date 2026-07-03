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
  final String phoneNumber;
  final String? avatar;
  final int unreadCount;
  final String? lastMessage;
  final String? lastMessageTime;
  final List<ContactLabel> labels;
  final String? lastMessageStatus;

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
        ? labelsList.map((l) => ContactLabel.fromJson(l as Map<String, dynamic>)).toList()
        : <ContactLabel>[];

    return Contact(
      uid: json['uid'] ?? json['_uid'] ?? '',
      name: json['full_name'] ?? json['name'] ?? json['first_name'] ?? json['wa_name'] ?? json['wa_id'] ?? 'Inconnu',
      phoneNumber: json['phone_number'] ?? json['wa_id'] ?? json['phoneNumber'] ?? '',
      avatar: json['avatar'] ?? json['avatar_url'],
      unreadCount: json['unread_messages_count'] ?? json['unread_count'] ?? json['unreadCount'] ?? 0,
      lastMessage: lastMsg,
      lastMessageTime: lastMsgTime,
      labels: parsedLabels,
      lastMessageStatus: lastMsgStatus,
    );
  }

  /// Get unique set of label titles from this contact
  Set<String> get labelTitles => labels.map((l) => l.title).toSet();
}

import 'dart:convert';
import 'lib/models/contact.dart';

void main() {
  String jsonStr = '''{
    "_id": 419832,
    "_uid": "5108d29b-8728-4de5-8371-327eb23765ec",
    "status": 1,
    "first_name": "yoannchakala",
    "last_name": " ",
    "wa_id": "2250501147886",
    "unread_messages_count": 1,
    "full_name": "yoannchakala  ",
    "last_message": {
      "_id": 798622,
      "_uid": "8e405ae2-521b-4580-ab4d-63cb75d3a9e9",
      "status": "received",
      "message": "Comment allez vous"
    }
  }''';
  
  try {
    Map<String, dynamic> json = jsonDecode(jsonStr);
    Contact contact = Contact.fromJson(json);
    print("Contact parsed successfully! Name: \${contact.name}, Unread: \${contact.unreadCount}, Status: \${contact.status}");
  } catch (e, stack) {
    print("Exception: \$e");
    print(stack);
  }
}

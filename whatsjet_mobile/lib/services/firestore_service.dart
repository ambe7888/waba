import 'package:cloud_firestore/cloud_firestore.dart';
import '../models/chat_message.dart';

class FirestoreService {
  static final FirestoreService _instance = FirestoreService._internal();
  factory FirestoreService() => _instance;
  FirestoreService._internal();

  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  /// Écoute en temps réel les messages d'un contact spécifique.
  /// La collection est "chats/{contactUid}/messages"
  Stream<List<ChatMessage>> streamContactMessages(String contactUid) {
    return _firestore
        .collection('chats')
        .doc(contactUid)
        .collection('messages')
        .orderBy('timestamp', descending: true)
        .snapshots()
        .map((snapshot) {
      return snapshot.docs.map((doc) {
        final data = doc.data();
        
        // Firestore convertit certaines dates, on s'assure d'avoir une String
        String timestampStr = '';
        if (data['timestamp'] is Timestamp) {
          timestampStr = (data['timestamp'] as Timestamp).toDate().toIso8601String();
        } else {
          timestampStr = data['timestamp']?.toString() ?? DateTime.now().toIso8601String();
        }

        return ChatMessage(
          uid: data['uid'] ?? doc.id,
          body: data['message'] ?? '',
          isIncoming: data['is_incoming'] == true || data['is_incoming'] == 1,
          timestamp: timestampStr,
          type: data['type'] ?? 'text',
          status: data['status'] ?? 'sent',
          isSystemMessage: data['is_system_message'] == true,
          mediaUrl: data['media_url'],
        );
      }).toList();
    });
  }

  /// Écrire un message local dans Firestore en attendant la confirmation du webhook.
  /// Cela permet d'avoir un "Optimistic UI" super rapide !
  Future<void> saveOptimisticMessage(String contactUid, ChatMessage message) async {
    await _firestore
        .collection('chats')
        .doc(contactUid)
        .collection('messages')
        .doc(message.uid)
        .set({
      'uid': message.uid,
      'message': message.body,
      'is_incoming': message.isIncoming,
      'timestamp': message.timestamp,
      'type': message.type,
      'status': message.status,
      'is_system_message': message.isSystemMessage,
      'media_url': message.mediaUrl,
    }, SetOptions(merge: true));
  }
}

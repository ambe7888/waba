import 'dart:async';
import 'dart:convert';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'api_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {}
  // Only manually show notification if OS hasn't already shown it
  // (Android automatically shows notifications when app is in background if message.notification != null)
  if (message.notification == null) {
    await FcmService._showLocalNotification(message);
  }
}

/// Handles taps on notification action buttons that don't need to open the
/// app UI (mark as read / inline reply) - runs in a separate background
/// isolate, same constraints as [firebaseMessagingBackgroundHandler].
@pragma('vm:entry-point')
void notificationTapBackground(NotificationResponse response) {
  _handleBackgroundNotificationAction(response);
}

Future<void> _handleBackgroundNotificationAction(NotificationResponse response) async {
  if (response.payload == null) return;
  try {
    final data = jsonDecode(response.payload!);
    final contactUid = (data['contact_uid'] ?? '').toString();
    if (contactUid.isEmpty) return;

    await ApiService().init();

    if (response.actionId == 'mark_read') {
      await ApiService().fetchMessages(contactUid);
    } else if (response.actionId == 'reply') {
      final replyText = response.input?.trim() ?? '';
      if (replyText.isEmpty) return;
      await ApiService().sendMessage(contactUid, replyText);
    } else {
      return;
    }

    final notificationId = contactUid.hashCode.remainder(100000);
    await FcmService._localNotifications.cancel(notificationId);
  } catch (e) {
    if (kDebugMode) print('Error handling background notification action: $e');
  }
}

class FcmService {
  static final FcmService _instance = FcmService._internal();
  factory FcmService() => _instance;
  FcmService._internal();

  FirebaseMessaging? _firebaseMessaging;
  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  // Static stream controller for notification taps. Carries {'type', 'contact_uid', 'uid'}
  // so listeners can route to the right screen for every notification kind
  // (message/support_ticket/resource/campaign), not just chat messages.
  static final StreamController<Map<String, String>> _notificationTapStreamController =
      StreamController<Map<String, String>>.broadcast();
  static Stream<Map<String, String>> get onNotificationTap => _notificationTapStreamController.stream;

  // Track whether Firebase was initialized successfully
  bool _isFirebaseAvailable = false;
  bool get isFirebaseAvailable => _isFirebaseAvailable;

  void setFirebaseAvailable(bool value) {
    _isFirebaseAvailable = value;
  }

  // Broadcast stream for incoming foreground messages
  final _messageStreamController = StreamController<RemoteMessage>.broadcast();
  Stream<RemoteMessage> get onMessage => _messageStreamController.stream;

  // Notification channel for Android
  static const AndroidNotificationChannel _channel = AndroidNotificationChannel(
    'whatsclick_messages',
    'Messages WhatsClick',
    description: 'Notifications pour les messages WhatsApp reçus',
    importance: Importance.high,
    playSound: true,
    enableVibration: true,
    showBadge: true,
  );

  /// Initialize local notifications plugin — call once at app startup
  static Future<void> initializeLocalNotifications() async {
    const androidSettings = AndroidInitializationSettings('ic_launcher_foreground');
    const darwinSettings = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );
    const initSettings = InitializationSettings(
      android: androidSettings,
      iOS: darwinSettings,
    );

    await _localNotifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        if (kDebugMode) print('Notification tapped: ${response.payload}');
        // Action buttons (mark as read / reply) don't open the app - they're
        // handled entirely in the background isolate, see notificationTapBackground.
        if (response.actionId != null && response.actionId!.isNotEmpty) return;
        if (response.payload != null) {
          try {
            final data = jsonDecode(response.payload!);
            _notificationTapStreamController.add({
              'type': (data['type'] ?? '').toString(),
              'contact_uid': (data['contact_uid'] ?? '').toString(),
              'uid': (data['uid'] ?? '').toString(),
            });
          } catch (e) {
            if (kDebugMode) print('Error parsing notification payload: $e');
          }
        }
      },
      onDidReceiveBackgroundNotificationResponse: notificationTapBackground,
    );

    // Create the Android notification channel
    final androidPlugin = _localNotifications
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
    if (androidPlugin != null) {
      await androidPlugin.createNotificationChannel(_channel);
    }
  }

  /// Show a visible local notification from an FCM message
  static Future<void> _showLocalNotification(RemoteMessage message) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final dnd = prefs.getBool('notifications_dnd') ?? false;
      if (dnd) {
        if (kDebugMode) print('Notification ignored due to DND mode.');
        return;
      }

      final sound = prefs.getBool('notifications_sound') ?? true;
      final filter = prefs.getString('notifications_filter') ?? 'all';

      final notification = message.notification;
      final data = message.data;

      if (filter == 'new_chats') {
        final isNewChat = data['is_new_chat'] == 'true' || data['is_new_chat'] == '1' || data['is_new'] == '1';
        if (!isNewChat) {
          if (kDebugMode) print('Notification ignored because it is not a new chat.');
          return;
        }
      }

      final title = notification?.title ?? data['title'] ?? 'Nouveau message';
      final body = notification?.body ?? data['body'] ?? 'Vous avez reçu un nouveau message';
      final contactUid = data['contact_uid'] ?? data['contactUid'] ?? '';
      final type = (data['type'] ?? (contactUid.isNotEmpty ? 'message' : '')).toString();
      final targetUid = (data['uid'] ?? '').toString();
      final isMessage = contactUid.isNotEmpty;

      // Generated colored-initial avatar, same hash formula as the in-app
      // contact list, so the notification icon matches what's shown in-app.
      final avatarBytes = isMessage ? await _generateAvatarBytes(title) : null;

      final androidDetails = AndroidNotificationDetails(
        'whatsclick_messages',
        'Messages WhatsClick',
        channelDescription: 'Notifications pour les messages WhatsApp reçus',
        importance: Importance.high,
        priority: Priority.high,
        playSound: sound,
        enableVibration: true,
        showWhen: true,
        icon: 'ic_launcher_foreground',
        color: const Color(0xFF198754),
        styleInformation: const BigTextStyleInformation(''),
        largeIcon: avatarBytes != null ? ByteArrayAndroidBitmap(avatarBytes) : null,
        actions: isMessage
            ? [
                const AndroidNotificationAction(
                  'mark_read',
                  'Marquer comme lu',
                  showsUserInterface: false,
                  cancelNotification: true,
                ),
                const AndroidNotificationAction(
                  'reply',
                  'Répondre',
                  showsUserInterface: false,
                  cancelNotification: true,
                  inputs: [AndroidNotificationActionInput(label: 'Votre réponse...')],
                ),
              ]
            : null,
      );

      final darwinDetails = DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: sound,
      );

      final details = NotificationDetails(
        android: androidDetails,
        iOS: darwinDetails,
      );

      // Use a consistent ID so a second notification about the same
      // contact/resource/ticket/campaign overwrites the first instead of
      // piling up in the status bar.
      final String idSource = contactUid.isNotEmpty
          ? contactUid
          : (targetUid.isNotEmpty ? targetUid : '$title|$body');
      final int notificationId = idSource.hashCode.remainder(100000);

      await _localNotifications.show(
        notificationId,
        title,
        body,
        details,
        payload: jsonEncode({
          'type': type,
          'contact_uid': contactUid,
          'uid': targetUid,
        }),
      );
    } catch (e) {
      if (kDebugMode) print('Error showing local notification: $e');
    }
  }

  /// Renders a colored-circle initials avatar as PNG bytes, for use as a
  /// notification's largeIcon. Uses the same hue-from-name-hash formula as
  /// the in-app contact avatar (see home_screen.dart's _buildAvatar) so the
  /// generated "logo" matches what the vendor already sees in the app.
  static Future<Uint8List?> _generateAvatarBytes(String name) async {
    try {
      final trimmed = name.trim();
      final initials = trimmed.isNotEmpty
          ? trimmed
              .split(' ')
              .map((e) => e.isNotEmpty ? e[0] : '')
              .take(2)
              .join()
              .toUpperCase()
          : 'C';
      final hash = trimmed.hashCode;
      final color = HSLColor.fromAHSL(1, (hash % 360).toDouble(), 0.8, 0.45).toColor();

      const double size = 128;
      final recorder = ui.PictureRecorder();
      final canvas = Canvas(recorder);
      canvas.drawCircle(const Offset(size / 2, size / 2), size / 2, Paint()..color = color);

      final textPainter = TextPainter(
        text: TextSpan(
          text: initials,
          style: const TextStyle(color: Colors.white, fontSize: 52, fontWeight: FontWeight.w700),
        ),
        textDirection: TextDirection.ltr,
      );
      textPainter.layout();
      textPainter.paint(
        canvas,
        Offset((size - textPainter.width) / 2, (size - textPainter.height) / 2),
      );

      final picture = recorder.endRecording();
      final image = await picture.toImage(size.toInt(), size.toInt());
      final byteData = await image.toByteData(format: ui.ImageByteFormat.png);
      return byteData?.buffer.asUint8List();
    } catch (e) {
      if (kDebugMode) print('Error generating notification avatar: $e');
      return null;
    }
  }

  bool _isInitialized = false;

  Future<void> init() async {
    if (_isInitialized) return;
    
    // Skip FCM initialization if Firebase is not available
    if (!_isFirebaseAvailable) {
      if (kDebugMode) print('⚠️ FCM skipped: Firebase is not available');
      return;
    }

    try {
      _isInitialized = true;
      _firebaseMessaging = FirebaseMessaging.instance;

      // Request permissions (important for Android 13+ and iOS)
      NotificationSettings settings = await _firebaseMessaging!.requestPermission(
        alert: true,
        announcement: false,
        badge: true,
        carPlay: false,
        criticalAlert: false,
        provisional: false,
        sound: true,
      );

      if (settings.authorizationStatus == AuthorizationStatus.authorized) {
        if (kDebugMode) print('Notification permissions granted.');
      }

      // Retrieve device FCM Token
      String? token = await _firebaseMessaging!.getToken();
      if (token != null) {
        if (kDebugMode) print('FCM Token retrieved: $token');
        await registerTokenOnBackend(token);
      }

      // Handle token refreshes
      _firebaseMessaging!.onTokenRefresh.listen((newToken) async {
        await registerTokenOnBackend(newToken);
      });

      // Register background message handler
      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

      // Foreground message listener — show local notification + notify stream
      FirebaseMessaging.onMessage.listen((RemoteMessage message) {
        if (kDebugMode) {
          print('Foreground notification received: ${message.notification?.title}');
        }
        // Show a visible system notification
        _showLocalNotification(message);
        // Notify listeners (HomeScreen, ChatBoxScreen) to refresh data
        _messageStreamController.add(message);
      });

      // Handle notification tap when app is in background (not terminated)
      FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
        if (kDebugMode) {
          print('Notification opened app: ${message.data}');
        }
        final contactUid = message.data['contact_uid'] ?? message.data['contactUid'] ?? '';
        final type = message.data['type'] ?? (contactUid.isNotEmpty ? 'message' : '');
        if (contactUid.isNotEmpty || (message.data['uid'] ?? '').isNotEmpty) {
          _notificationTapStreamController.add({
            'type': type.toString(),
            'contact_uid': contactUid.toString(),
            'uid': (message.data['uid'] ?? '').toString(),
          });
        }
        _messageStreamController.add(message);
      });

      // Check if the app was opened by a message when terminated
      _firebaseMessaging!.getInitialMessage().then((RemoteMessage? initialMessage) {
        if (initialMessage != null) {
          final contactUid = initialMessage.data['contact_uid'] ?? initialMessage.data['contactUid'] ?? '';
          final type = initialMessage.data['type'] ?? (contactUid.isNotEmpty ? 'message' : '');
          final uid = (initialMessage.data['uid'] ?? '').toString();
          if (contactUid.isNotEmpty || uid.isNotEmpty) {
            Future.delayed(const Duration(milliseconds: 1500), () {
              _notificationTapStreamController.add({
                'type': type.toString(),
                'contact_uid': contactUid.toString(),
                'uid': uid,
              });
            });
          }
        }
      });

    } catch (e) {
      if (kDebugMode) print('FCM initialization error: $e');
    }
  }

  Future<void> registerTokenOnBackend(String token) async {
    final apiService = ApiService();
    if (apiService.isAuthenticated) {
      bool success = await apiService.registerDeviceToken(
        token,
        'whatsjet_mobile_device',
        'android',
      );
      if (kDebugMode) print('Token registered on backend success: $success');
    }
  }
}


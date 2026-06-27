import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'api_service.dart';

/// Top-level handler for background FCM messages (must be top-level function)
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {}
  await FcmService._showLocalNotification(message);
}

class FcmService {
  static final FcmService _instance = FcmService._internal();
  factory FcmService() => _instance;
  FcmService._internal();

  FirebaseMessaging? _firebaseMessaging;
  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

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
        // Future: navigate to the specific chat using payload data
      },
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
    final notification = message.notification;
    final data = message.data;

    final title = notification?.title ?? data['title'] ?? 'Nouveau message';
    final body = notification?.body ?? data['body'] ?? 'Vous avez reçu un nouveau message';
    final contactUid = data['contact_uid'] ?? data['contactUid'] ?? '';

    const androidDetails = AndroidNotificationDetails(
      'whatsclick_messages',
      'Messages WhatsClick',
      channelDescription: 'Notifications pour les messages WhatsApp reçus',
      importance: Importance.high,
      priority: Priority.high,
      playSound: true,
      enableVibration: true,
      showWhen: true,
      icon: 'ic_launcher_foreground',
      color: Color(0xFF0D9488),
      styleInformation: BigTextStyleInformation(''),
    );

    const darwinDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const details = NotificationDetails(
      android: androidDetails,
      iOS: darwinDetails,
    );

    await _localNotifications.show(
      DateTime.now().millisecondsSinceEpoch.remainder(100000),
      title,
      body,
      details,
      payload: jsonEncode({'contact_uid': contactUid}),
    );
  }

  Future<void> init() async {
    // Skip FCM initialization if Firebase is not available
    if (!_isFirebaseAvailable) {
      if (kDebugMode) print('⚠️ FCM skipped: Firebase is not available');
      return;
    }

    try {
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
        _messageStreamController.add(message);
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


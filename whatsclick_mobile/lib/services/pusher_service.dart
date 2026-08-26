import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';
import '../config/app_config.dart';
import 'api_service.dart';

class PusherService {
  static final PusherService _instance = PusherService._internal();
  factory PusherService() => _instance;
  PusherService._internal();

  PusherChannelsFlutter? _pusher;
  
  final _newMessageController = StreamController<Map<String, dynamic>>.broadcast();
  Stream<Map<String, dynamic>> get onNewMessage => _newMessageController.stream;

  bool _isConnected = false;

  /// Whether the realtime channel is currently up.
  ///
  /// Screens use this to slow their fallback polling down, never to switch
  /// it off: this reports the socket state, which is not the same thing as
  /// events actually arriving. A wrong channel name or a failing authorizer
  /// leaves this true while nothing is ever delivered - which is precisely
  /// how realtime was broken here before, unnoticed.
  bool get isConnected => _isConnected;

  Future<void> init(String vendorUid) async {
    if (_pusher != null) return;

    try {
      _pusher = PusherChannelsFlutter.getInstance();
      await _pusher!.init(
        apiKey: configItems['services']['pusher']['apiKey'],
        cluster: configItems['services']['pusher']['cluster'],
        onEvent: _onEvent,
        onConnectionStateChange: (currentState, previousState) {
          _isConnected = currentState.toUpperCase() == 'CONNECTED';
          if (kDebugMode) {
            print('Pusher connection: $previousState -> $currentState');
          }
        },
        onError: (message, code, error) {
          _isConnected = false;
          if (kDebugMode) print('Pusher error [$code]: $message');
        },
        // Required for a private channel to subscribe at all - without an
        // authorizer, the native SDK can't sign the subscription request
        // and it silently fails (no error surfaces to onEvent/onError).
        onAuthorizer: _authorizeChannel,
      );
      await _pusher!.connect();
      // Must match the server side exactly: the backend broadcasts on
      // PrivateChannel('vendor-channel.'.$vendorUid) (VendorChannelBroadcast.php),
      // which Pusher client-side names 'private-vendor-channel.{uid}' - not
      // 'private-vendor.{uid}'.
      await _pusher!.subscribe(
        channelName: 'private-vendor-channel.$vendorUid',
      );
      if (kDebugMode) {
        print('Pusher connected and subscribed to private-vendor-channel.$vendorUid');
      }
    } catch (e) {
      if (kDebugMode) {
        print('Pusher initialization error: $e');
      }
    }
  }

  /// Signs the private channel subscription via the app's own auth token,
  /// through the same Laravel broadcasting-auth route the web dashboard uses.
  Future<Map<String, dynamic>> _authorizeChannel(
      String channelName, String socketId, dynamic options) async {
    final auth = await ApiService().authorizePusherChannel(channelName, socketId);
    return auth ?? {};
  }

  void _onEvent(PusherEvent event) {
    if (kDebugMode) {
      print('Pusher event: ${event.eventName} => ${event.data}');
    }

    // The backend only ever broadcasts one custom event name (see
    // VendorChannelBroadcast::broadcastAs()) for both new incoming messages
    // and message status updates (sent/delivered/read) - the payload's
    // `isNewIncomingMessage`/`message_status` fields distinguish which, but
    // every listener here just triggers a silent reload either way, so no
    // need to branch on that here.
    if (event.eventName == 'VendorChannelBroadcast') {
      try {
        final dataStr = event.data?.toString() ?? '{}';
        final data = jsonDecode(dataStr);
        _newMessageController.add(data);
      } catch (e) {
        if (kDebugMode) {
          print('Pusher event parse error: $e');
        }
      }
    }
  }

  Future<void> disconnect() async {
    try {
      await _pusher?.disconnect();
      _pusher = null;
      _isConnected = false;
    } catch (e) {
      if (kDebugMode) {
        print('Pusher disconnect error: $e');
      }
    }
  }
}

import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:audioplayers/audioplayers.dart';

/// Service singleton pour la gestion centralisée de la lecture des notes vocales.
///
/// Avantages :
/// - Une seule instance native [AudioPlayer] pour toute l'application (pas de fuite de décodeurs Android).
/// - Empêche la lecture simultanée de deux notes vocales (comportement identique à WhatsApp).
/// - Arrêt automatique lors du départ de la conversation.
class VoicePlayerService extends ChangeNotifier {
  static final VoicePlayerService _instance = VoicePlayerService._internal();
  factory VoicePlayerService() => _instance;

  final AudioPlayer _player = AudioPlayer();

  String? _currentUrl;
  PlayerState _playerState = PlayerState.stopped;
  Duration _duration = Duration.zero;
  Duration _position = Duration.zero;
  Timer? _progressPoller;

  String? get currentUrl => _currentUrl;
  PlayerState get playerState => _playerState;
  Duration get duration => _duration;
  Duration get position => _position;

  bool isPlaying(String? url) =>
      _currentUrl == url && _playerState == PlayerState.playing;
  bool isPaused(String? url) =>
      _currentUrl == url && _playerState == PlayerState.paused;
  bool isActive(String? url) =>
      _currentUrl == url && url != null && url.isNotEmpty;

  VoicePlayerService._internal() {
    _player.onPlayerStateChanged.listen((state) {
      _playerState = state;
      if (state == PlayerState.playing) {
        _startProgressPolling();
      } else {
        _progressPoller?.cancel();
      }
      notifyListeners();
    });

    _player.onDurationChanged.listen((d) {
      _duration = d;
      notifyListeners();
    });

    _player.onPositionChanged.listen((p) {
      _position = p;
      notifyListeners();
    });

    _player.onPlayerComplete.listen((_) {
      _progressPoller?.cancel();
      _position = Duration.zero;
      _playerState = PlayerState.completed;
      notifyListeners();
    });
  }

  void _startProgressPolling() {
    _progressPoller?.cancel();
    _progressPoller =
        Timer.periodic(const Duration(milliseconds: 200), (_) async {
      if (_playerState != PlayerState.playing) return;
      try {
        final pos = await _player.getCurrentPosition();
        final dur = await _player.getDuration();
        bool changed = false;
        if (pos != null &&
            (pos.inMilliseconds - _position.inMilliseconds).abs() > 100) {
          _position = pos;
          changed = true;
        }
        if (dur != null && dur > Duration.zero && dur != _duration) {
          _duration = dur;
          changed = true;
        }
        if (changed) {
          notifyListeners();
        }
      } catch (_) {}
    });
  }

  /// Démarre ou met en pause la note vocale ciblée
  Future<void> togglePlay(String url) async {
    if (url.isEmpty) return;

    if (_currentUrl == url) {
      if (_playerState == PlayerState.playing) {
        await _player.pause();
      } else if (_playerState == PlayerState.paused) {
        await _player.resume();
      } else {
        // Redémarrer depuis le début
        _position = Duration.zero;
        await _player.play(UrlSource(url));
      }
    } else {
      // Nouvelle note vocale : on arrête la précédente et on lance la nouvelle
      _progressPoller?.cancel();
      await _player.stop();
      _currentUrl = url;
      _position = Duration.zero;
      _duration = Duration.zero;
      await _player.play(UrlSource(url));
    }
  }

  /// Avancer / reculer dans la lecture
  Future<void> seek(Duration position) async {
    _position = position;
    await _player.seek(position);
    notifyListeners();
  }

  /// Arrête la lecture et réinitialise l'état
  Future<void> stop() async {
    _progressPoller?.cancel();
    try {
      await _player.stop();
    } catch (_) {}
    _currentUrl = null;
    _position = Duration.zero;
    _duration = Duration.zero;
    _playerState = PlayerState.stopped;
    notifyListeners();
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import '../services/theme_service.dart';

/// Hosts Meta's WhatsApp Embedded Signup flow (a Facebook-hosted popup —
/// there is no native mobile equivalent) inside a WebView, pointed at the
/// short-lived signed-token bridge page the backend generates. The bridge
/// page runs the exact same Facebook JS SDK flow already proven on the web
/// dashboard; this screen's only job is to notice when that page navigates
/// to its "done" URL and report success back.
///
/// Facebook's login/business-picker step opens as a real popup
/// (`window.open`, with the finish event delivered via
/// `window.opener.postMessage`) — `webview_flutter` has no popup support at
/// all, so that step used to silently fall back to a top-level navigation
/// with no `opener`, stranding the flow after the user finished on
/// Facebook's side. `flutter_inappwebview`'s `onCreateWindow` gives the
/// popup a real native window/opener relationship, which is what the SDK's
/// postMessage handshake actually needs.
class WhatsAppEmbeddedSignupScreen extends StatefulWidget {
  final String signupUrl;

  const WhatsAppEmbeddedSignupScreen({super.key, required this.signupUrl});

  @override
  State<WhatsAppEmbeddedSignupScreen> createState() => _WhatsAppEmbeddedSignupScreenState();
}

class _WhatsAppEmbeddedSignupScreenState extends State<WhatsAppEmbeddedSignupScreen> {
  bool _isLoading = true;
  bool _isDone = false;

  static final _settings = InAppWebViewSettings(
    javaScriptEnabled: true,
    javaScriptCanOpenWindowsAutomatically: true,
    supportMultipleWindows: true,
    useShouldOverrideUrlLoading: true,
  );

  void _finish({required bool success}) {
    if (_isDone) return;
    _isDone = true;
    Navigator.of(context).pop(success);
  }

  bool _isDoneUrl(String? url) {
    return url != null && url.contains('/whatsapp-embedded-signup-mobile/') && url.endsWith('/done');
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop) _finish(success: false);
      },
      child: Scaffold(
        backgroundColor: isDark ? ThemeService.darkSurface : Colors.white,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          leading: IconButton(
            icon: Icon(Icons.close_rounded, color: isDark ? Colors.white : Colors.black87),
            onPressed: () => _finish(success: false),
          ),
          title: Text(
            'Connexion WhatsApp',
            style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontWeight: FontWeight.bold, fontSize: 16),
          ),
        ),
        body: Stack(
          children: [
            InAppWebView(
              initialUrlRequest: URLRequest(url: WebUri(widget.signupUrl)),
              initialSettings: _settings,
              onLoadStart: (controller, url) {
                if (_isDoneUrl(url?.toString())) {
                  _finish(success: true);
                  return;
                }
                if (mounted) setState(() => _isLoading = true);
              },
              onLoadStop: (controller, url) {
                if (mounted) setState(() => _isLoading = false);
              },
              shouldOverrideUrlLoading: (controller, navigationAction) async {
                if (_isDoneUrl(navigationAction.request.url?.toString())) {
                  _finish(success: true);
                  return NavigationActionPolicy.CANCEL;
                }
                return NavigationActionPolicy.ALLOW;
              },
              onCreateWindow: (controller, createWindowAction) async {
                if (!mounted) return false;
                await Navigator.of(context).push(
                  MaterialPageRoute(
                    fullscreenDialog: true,
                    builder: (_) => _EmbeddedSignupPopupScreen(windowId: createWindowAction.windowId),
                  ),
                );
                return true;
              },
            ),
            if (_isLoading) const Center(child: CircularProgressIndicator()),
          ],
        ),
      ),
    );
  }
}

/// The Facebook login / business-picker popup Meta's SDK opens via
/// `window.open`. Bound to the parent webview's [windowId] so the native
/// layer wires up a real `window.opener`, letting Meta's postMessage
/// handshake reach the parent page and complete the flow there.
class _EmbeddedSignupPopupScreen extends StatefulWidget {
  final int windowId;

  const _EmbeddedSignupPopupScreen({required this.windowId});

  @override
  State<_EmbeddedSignupPopupScreen> createState() => _EmbeddedSignupPopupScreenState();
}

class _EmbeddedSignupPopupScreenState extends State<_EmbeddedSignupPopupScreen> {
  bool _isLoading = true;

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.close_rounded, color: isDark ? Colors.white : Colors.black87),
          onPressed: () => Navigator.of(context).maybePop(),
        ),
        title: Text(
          'Connexion Facebook',
          style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontWeight: FontWeight.bold, fontSize: 16),
        ),
      ),
      body: Stack(
        children: [
          InAppWebView(
            windowId: widget.windowId,
            initialSettings: InAppWebViewSettings(javaScriptEnabled: true),
            onLoadStart: (controller, url) {
              if (mounted) setState(() => _isLoading = true);
            },
            onLoadStop: (controller, url) {
              if (mounted) setState(() => _isLoading = false);
            },
            onCloseWindow: (controller) {
              if (mounted) Navigator.of(context).maybePop();
            },
          ),
          if (_isLoading) const Center(child: CircularProgressIndicator()),
        ],
      ),
    );
  }
}

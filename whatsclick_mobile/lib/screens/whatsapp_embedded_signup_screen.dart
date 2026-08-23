import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../services/theme_service.dart';

/// Hosts Meta's WhatsApp Embedded Signup flow (a Facebook-hosted popup —
/// there is no native mobile equivalent) inside a WebView, pointed at the
/// short-lived signed-token bridge page the backend generates. The bridge
/// page runs the exact same Facebook JS SDK flow already proven on the web
/// dashboard; this screen's only job is to notice when that page navigates
/// to its "done" URL and report success back.
class WhatsAppEmbeddedSignupScreen extends StatefulWidget {
  final String signupUrl;

  const WhatsAppEmbeddedSignupScreen({super.key, required this.signupUrl});

  @override
  State<WhatsAppEmbeddedSignupScreen> createState() => _WhatsAppEmbeddedSignupScreenState();
}

class _WhatsAppEmbeddedSignupScreenState extends State<WhatsAppEmbeddedSignupScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  bool _isDone = false;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (mounted) setState(() => _isLoading = true);
          },
          onPageFinished: (_) {
            if (mounted) setState(() => _isLoading = false);
          },
          onNavigationRequest: (request) {
            if (request.url.contains('/whatsapp-embedded-signup-mobile/') && request.url.endsWith('/done')) {
              _finish(success: true);
              return NavigationDecision.prevent;
            }
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.signupUrl));
  }

  void _finish({required bool success}) {
    if (_isDone) return;
    _isDone = true;
    Navigator.of(context).pop(success);
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
            WebViewWidget(controller: _controller),
            if (_isLoading) const Center(child: CircularProgressIndicator()),
          ],
        ),
      ),
    );
  }
}

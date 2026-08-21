import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:share_plus/share_plus.dart';
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
import 'package:gal/gal.dart';
import '../services/theme_service.dart';
import '../config/app_config.dart';

class QRCodeScreen extends StatefulWidget {
  final String vendorUid;
  final String phoneNumber;

  const QRCodeScreen({
    super.key,
    required this.vendorUid,
    required this.phoneNumber,
  });

  @override
  State<QRCodeScreen> createState() => _QRCodeScreenState();
}

class _QRCodeScreenState extends State<QRCodeScreen> {
  bool _isBusy = false;

  String get _cleanPhone => widget.phoneNumber.replaceAll(RegExp(r'[^0-9]'), '');

  // Same server-generated QR (with the WhatsApp logo watermark) the web
  // dashboard displays — avoids re-implementing QR generation client-side
  // and guarantees the image matches exactly.
  String get _qrImageUrl => '${baseUrl}whatsapp-qr/${widget.vendorUid}/$_cleanPhone';
  String get _whatsappLink => 'https://wa.me/$_cleanPhone';

  Future<void> _copyToClipboard(String value, String label) async {
    await Clipboard.setData(ClipboardData(text: value));
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('$label copié !'), backgroundColor: Colors.green),
    );
  }

  Future<void> _downloadQr() async {
    if (_isBusy) return;
    setState(() => _isBusy = true);
    try {
      final file = await DefaultCacheManager().getSingleFile(_qrImageUrl);
      await Gal.putImage(file.path, album: 'WhatsClick');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Code QR enregistré dans la galerie'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Erreur lors de l'enregistrement du code QR."), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isBusy = false);
    }
  }

  Future<void> _shareQr() async {
    if (_isBusy) return;
    setState(() => _isBusy = true);
    try {
      final file = await DefaultCacheManager().getSingleFile(_qrImageUrl);
      await Share.shareXFiles([XFile(file.path)], text: _whatsappLink);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Erreur lors du partage du code QR.'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isBusy = false);
    }
  }

  Future<void> _openWhatsApp() async {
    final uri = Uri.parse('https://api.whatsapp.com/send?phone=$_cleanPhone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    final onSurface = Theme.of(context).colorScheme.onSurface;

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withAlpha(30),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(Icons.qr_code_2_rounded,
                  color: ThemeService.primaryColor, size: 20),
            ),
            const SizedBox(width: 10),
            const Text(
              'Mon Code QR',
              style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.5),
            ),
          ],
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const Text(
              'Scannez ce code QR pour discuter',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white, // QR codes always need high contrast
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.1),
                    blurRadius: 10,
                    spreadRadius: 2,
                  ),
                ],
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.network(
                  _qrImageUrl,
                  width: 250,
                  height: 250,
                  fit: BoxFit.contain,
                  loadingBuilder: (context, child, progress) {
                    if (progress == null) return child;
                    return const SizedBox(
                      width: 250,
                      height: 250,
                      child: Center(child: CircularProgressIndicator(color: ThemeService.primaryColor)),
                    );
                  },
                  errorBuilder: (context, error, stack) => SizedBox(
                    width: 250,
                    height: 250,
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.error_outline_rounded, color: Colors.grey, size: 40),
                          const SizedBox(height: 8),
                          Text('Code QR indisponible', style: TextStyle(color: Colors.grey.shade600)),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                '+$_cleanPhone',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: ThemeService.primaryColor,
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Download / Share buttons
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _isBusy ? null : _downloadQr,
                    icon: const Icon(Icons.download_rounded, size: 18),
                    label: const Text('Télécharger'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      side: BorderSide(color: onSurface.withValues(alpha: 0.2)),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _isBusy ? null : _shareQr,
                    icon: const Icon(Icons.share_rounded, size: 18),
                    label: const Text('Partager'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ThemeService.primaryColor,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 28),

            // QR image URL
            Align(
              alignment: Alignment.centerLeft,
              child: Text("URL de l'image QR",
                  style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: onSurface.withValues(alpha: 0.6))),
            ),
            const SizedBox(height: 6),
            _buildCopyableField(_qrImageUrl, onSurface, isDark, () => _copyToClipboard(_qrImageUrl, "L'URL de l'image")),
            const SizedBox(height: 16),

            // WhatsApp link
            Align(
              alignment: Alignment.centerLeft,
              child: Text('Lien WhatsApp',
                  style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: onSurface.withValues(alpha: 0.6))),
            ),
            const SizedBox(height: 6),
            _buildCopyableField(_whatsappLink, onSurface, isDark, () => _copyToClipboard(_whatsappLink, 'Le lien')),
            const SizedBox(height: 16),

            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _openWhatsApp,
                icon: const Icon(Icons.chat_rounded, size: 18),
                label: const Text('WhatsApp maintenant'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCopyableField(String value, Color onSurface, bool isDark, VoidCallback onCopy) {
    return Container(
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : Colors.grey.shade50,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: onSurface.withValues(alpha: 0.12)),
      ),
      child: Row(
        children: [
          Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
              child: Text(
                value,
                style: TextStyle(fontSize: 12.5, color: onSurface.withValues(alpha: 0.8)),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
          InkWell(
            onTap: onCopy,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                border: Border(left: BorderSide(color: onSurface.withValues(alpha: 0.12))),
              ),
              child: Icon(Icons.copy_rounded, size: 16, color: ThemeService.primaryColor),
            ),
          ),
        ],
      ),
    );
  }
}

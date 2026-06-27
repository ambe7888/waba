import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../services/theme_service.dart';

class QRCodeScreen extends StatelessWidget {
  final String vendorUid;
  final String phoneNumber;

  const QRCodeScreen({
    super.key,
    required this.vendorUid,
    required this.phoneNumber,
  });

  @override
  Widget build(BuildContext context) {
    final qrData = 'https://wa.me/$phoneNumber'; // Standard WhatsApp link
    final isDark = ThemeService().isDark;

    return Scaffold(
      appBar: AppBar(
        title: Text('Mon Code QR'),
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              'Scannez ce code QR pour discuter',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 32),
            Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white, // QR codes always need high contrast
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 10,
                    spreadRadius: 2,
                  ),
                ],
              ),
              child: QrImageView(
                data: qrData,
                version: QrVersions.auto,
                size: 250.0,
                backgroundColor: Colors.white,
                foregroundColor: Colors.black,
              ),
            ),
            SizedBox(height: 32),
            Text(
              'Numéro : +$phoneNumber',
              style: TextStyle(
                fontSize: 16,
                color: isDark ? Colors.white70 : Colors.black54,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

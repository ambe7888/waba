import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class QrScreen extends StatefulWidget {
  const QrScreen({super.key});

  @override
  State<QrScreen> createState() => _QrScreenState();
}

class _QrScreenState extends State<QrScreen> {
  bool _isLoading = true;
  String? _phoneNumber;
  String? _error;

  String _normalizePhone(String? raw) {
    if (raw == null || raw.isEmpty) return '';
    final normalized = raw.replaceAll(RegExp(r'[^0-9+]'), '');
    if (normalized.startsWith('+')) {
      return normalized.substring(1);
    }
    return normalized;
  }

  @override
  void initState() {
    super.initState();
    _fetchPhoneNumber();
  }

  Future<void> _fetchPhoneNumber() async {
    try {
      final data = await ApiService().fetchDashboardStats();
      final rawPhone = (data?['whatsapp_phone_number'] ??
              data?['vendorDashboardData']?['whatsapp_phone_number'] ??
              data?['vendorInfo']?['mobile_number'] ??
              data?['vendorInfo']?['phone'])
          ?.toString();
      final normalizedPhone = _normalizePhone(rawPhone);

      if (mounted) {
        setState(() {
          _phoneNumber = normalizedPhone.isEmpty ? null : normalizedPhone;
          _error = normalizedPhone.isEmpty ? 'Numéro WhatsApp non configuré pour ce compte.' : null;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Impossible de charger le QR Code';
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    
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
              child: Icon(Icons.qr_code_rounded, color: ThemeService.primaryColor, size: 20),
            ),
            const SizedBox(width: 10),
            const Text(
              'Code QR',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, letterSpacing: -0.5),
            ),
          ],
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : _error != null || _phoneNumber == null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_error ?? 'Numéro WhatsApp non configuré', style: TextStyle(color: Colors.red)),
                      SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchPhoneNumber,
                        child: Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'Scannez ce QR Code',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: isDark ? Colors.white : Colors.black87,
                          ),
                        ),
                        SizedBox(height: 16),
                        Text(
                          'Laissez les utilisateurs scanner ce code pour lancer une conversation WhatsApp avec vous.',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 16,
                            color: isDark ? Colors.white70 : Colors.black54,
                          ),
                        ),
                        SizedBox(height: 48),
                        Container(
                          padding: EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: [
                              BoxShadow(
                                color: isDark ? Colors.black.withOpacity(0.3) : Colors.black.withOpacity(0.1),
                                blurRadius: 20,
                                offset: Offset(0, 10),
                              ),
                            ],
                          ),
                          child: QrImageView(
                            data: 'https://wa.me/$_phoneNumber',
                            version: QrVersions.auto,
                            size: 250.0,
                            backgroundColor: Colors.white,
                            eyeStyle: const QrEyeStyle(
                              eyeShape: QrEyeShape.square,
                              color: Colors.black,
                            ),
                            dataModuleStyle: const QrDataModuleStyle(
                              dataModuleShape: QrDataModuleShape.square,
                              color: Colors.black,
                            ),
                          ),
                        ),
                        SizedBox(height: 48),
                        Text(
                          '+${_phoneNumber ?? ''}',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w600,
                            letterSpacing: 2,
                            color: ThemeService.primaryColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
    );
  }
}

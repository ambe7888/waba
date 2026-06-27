import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'login_screen.dart';
import 'qr_code_screen.dart';

class AccountScreen extends StatefulWidget {
  const AccountScreen({super.key});

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen> {
  // We can fetch user details from Dashboard API or store them during login
  // For now, we will use placeholders or fetch stats to get vendor info
  Map<String, dynamic>? _vendorInfo;

  @override
  void initState() {
    super.initState();
    _loadVendorInfo();
  }

  Future<void> _loadVendorInfo() async {
    final stats = await ApiService().fetchDashboardStats();
    if (mounted && stats != null) {
      setState(() {
        _vendorInfo = stats['vendorInfo'];
      });
    }
  }

  void _logout() async {
    await ApiService().logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  void _showQrCode() {
    // Determine the phone number. We fallback to '000000000' if not available.
    String phone = '000000000';
    if (_vendorInfo != null && _vendorInfo!['whatsapp_number'] != null) {
      phone = _vendorInfo!['whatsapp_number'];
    }

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => QRCodeScreen(
          vendorUid: _vendorInfo?['uid'] ?? '',
          phoneNumber: phone,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Scaffold(
      appBar: AppBar(
        title: Text('Compte'),
      ),
      body: ListView(
        padding: EdgeInsets.all(16),
        children: [
          // Profil Header
          Center(
            child: CircleAvatar(
              radius: 40,
              backgroundColor: ThemeService.primaryColor.withOpacity(0.2),
              child: Icon(Icons.business, size: 40, color: ThemeService.primaryColor),
            ),
          ),
          SizedBox(height: 16),
          Center(
            child: Text(
              _vendorInfo?['title'] ?? 'Mon Entreprise',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
          ),
          if (_vendorInfo?['whatsapp_number'] != null) ...[
            SizedBox(height: 8),
            Center(
              child: Text(
                '+${_vendorInfo!['whatsapp_number']}',
                style: TextStyle(fontSize: 16, color: isDark ? Colors.white70 : Colors.black54),
              ),
            ),
          ],
          SizedBox(height: 32),

          // Actions
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: Icon(Icons.qr_code, color: Colors.blue),
                  title: Text('Mon Code QR'),
                  trailing: Icon(Icons.chevron_right),
                  onTap: _showQrCode,
                ),
                const Divider(height: 1),
                ListTile(
                  leading: Icon(isDark ? Icons.light_mode : Icons.dark_mode, color: Colors.orange),
                  title: Text(isDark ? 'Mode Clair' : 'Mode Sombre'),
                  trailing: Switch(
                    value: isDark,
                    onChanged: (value) {
                      ThemeService().toggleTheme();
                    },
                    activeColor: ThemeService.primaryColor,
                  ),
                ),
                const Divider(height: 1),
                ListTile(
                  leading: Icon(Icons.logout, color: Colors.red),
                  title: Text('Déconnexion', style: TextStyle(color: Colors.red)),
                  onTap: _logout,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../config/app_config.dart';
import 'package:url_launcher/url_launcher.dart';
import 'support_tickets_screen.dart';
import 'resource_list_screen.dart';
import 'login_screen.dart';
import 'qr_code_screen.dart';
import 'canned_replies_screen.dart';
import 'notification_settings_screen.dart';
import 'contact_groups_screen.dart';
import 'templates_admin_screen.dart';
import 'bot_replies_screen.dart';
import 'drip_campaigns_settings_screen.dart';

class AccountScreen extends StatefulWidget {
  const AccountScreen({super.key});

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen> {
  Map<String, dynamic>? _vendorInfo;
  int _roleId = 3; // default: agent
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    // Load role first from cache (instant)
    _roleId = await ApiService().getUserRoleId();
    if (mounted) setState(() {});

    // Then fetch vendor info for display
    final stats = await ApiService().fetchDashboardStats();
    if (mounted && stats != null) {
      setState(() {
        _vendorInfo = stats['vendorInfo'] ?? stats;
        _isLoading = false;
      });
    } else if (mounted) {
      setState(() => _isLoading = false);
    }
  }

  void _logout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Êtes-vous sûr de vouloir vous déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Déconnexion',
                style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    await ApiService().logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  void _showQrCode() {
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
    final bool isAdmin = _roleId == 2;

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
              child: Icon(Icons.person_rounded,
                  color: ThemeService.primaryColor, size: 20),
            ),
            const SizedBox(width: 10),
            const Text(
              'Compte',
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
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Profil Header
          Center(
            child: CircleAvatar(
              radius: 40,
              backgroundColor: ThemeService.primaryColor.withValues(alpha: 0.2),
              child: Icon(Icons.business,
                  size: 40, color: ThemeService.primaryColor),
            ),
          ),
          const SizedBox(height: 16),
          Center(
            child: Text(
              _vendorInfo?['title'] ?? 'Mon Entreprise',
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
          ),
          if (_vendorInfo?['whatsapp_number'] != null) ...[
            const SizedBox(height: 8),
            Center(
              child: Text(
                '+${_vendorInfo!['whatsapp_number']}',
                style: TextStyle(
                    fontSize: 16,
                    color: isDark ? Colors.white70 : Colors.black54),
              ),
            ),
          ],
          const SizedBox(height: 20),

          // ── Abonnement ──────────────────────────────────────────────────
          Builder(builder: (_) {
            final sub =
                _vendorInfo?['current_subscription'] as Map<String, dynamic>?;
            if (sub == null) return const SizedBox.shrink();

            final planTitle = sub['title']?.toString() ?? 'Plan';
            final endsAt = sub['ends_at']?.toString();
            final isExpired = sub['is_expired'] == true;
            final isFree = sub['is_free'] == true;

            String endsLabel = '';
            if (endsAt != null) {
              try {
                final d = DateTime.parse(endsAt).toLocal();
                endsLabel =
                    '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year}';
              } catch (_) {}
            }

            final Color statusColor = isExpired
                ? const Color(0xFFEF4444)
                : isFree
                    ? Colors.orange
                    : ThemeService.primaryColor;

            final String statusLabel =
                isExpired ? 'Expiré' : isFree ? 'Gratuit' : 'Actif';

            return Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                borderRadius: BorderRadius.circular(16),
                border:
                    Border.all(color: statusColor.withValues(alpha: 0.25)),
                boxShadow: [
                  BoxShadow(
                    color: statusColor.withValues(alpha: 0.08),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(Icons.workspace_premium_rounded,
                        color: statusColor, size: 24),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          planTitle,
                          style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: isDark
                                  ? Colors.white
                                  : const Color(0xFF1E293B)),
                        ),
                        const SizedBox(height: 2),
                        if (endsLabel.isNotEmpty)
                          Text(
                            isExpired
                                ? 'Expiré le $endsLabel'
                                : 'Expire le $endsLabel',
                            style: TextStyle(
                                fontSize: 12,
                                color: isExpired
                                    ? const Color(0xFFEF4444)
                                    : (isDark
                                        ? Colors.white54
                                        : Colors.black54)),
                          )
                        else
                          Text(
                            'Pas de date de fin',
                            style: TextStyle(
                                fontSize: 12,
                                color: isDark
                                    ? Colors.white38
                                    : Colors.black38),
                          ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      statusLabel,
                      style: TextStyle(
                          color: statusColor,
                          fontWeight: FontWeight.w700,
                          fontSize: 12),
                    ),
                  ),
                ],
              ),
            );
          }),

          // ── Actions ─────────────────────────────────────────────────────
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.qr_code, color: Colors.blue),
                  title: const Text('Mon Code QR'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: _showQrCode,
                ),
                const Divider(height: 1),
                ListTile(
                  leading:
                      const Icon(Icons.folder_shared_rounded, color: Colors.teal),
                  title: const Text('Ressources partagées'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => const ResourceListScreen()),
                    );
                  },
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.support_agent_rounded,
                      color: Colors.deepPurple),
                  title: const Text('Assistance'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => const SupportTicketsScreen()),
                    );
                  },
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.group_work_rounded,
                      color: Colors.purple),
                  title: const Text('Groupes de Contacts'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => const ContactGroupsScreen()),
                    );
                  },
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.notifications_active_rounded,
                      color: Colors.blue),
                  title: const Text('Paramètres de Notifications'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) =>
                              const NotificationSettingsScreen()),
                    );
                  },
                ),
                const Divider(height: 1),
                ListTile(
                  leading: Icon(
                      isDark ? Icons.light_mode : Icons.dark_mode,
                      color: Colors.orange),
                  title: Text(isDark ? 'Mode Clair' : 'Mode Sombre'),
                  trailing: Switch(
                    value: isDark,
                    onChanged: (value) {
                      ThemeService().toggleTheme();
                    },
                    activeThumbColor: ThemeService.primaryColor,
                  ),
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.system_update_rounded,
                      color: Colors.blueAccent),
                  title: const Text('Mise à jour de l\'application'),
                  subtitle: const Text('Télécharger la version APK'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () async {
                    final url =
                        Uri.parse('${baseUrl}downloads/whatsclick.apk');
                    if (await canLaunchUrl(url)) {
                      await launchUrl(url,
                          mode: LaunchMode.externalApplication);
                    } else {
                      if (!mounted) return;
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                            content: Text('Impossible d\'ouvrir le lien')),
                      );
                    }
                  },
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.logout, color: Colors.red),
                  title: const Text('Déconnexion',
                      style: TextStyle(color: Colors.red)),
                  onTap: _logout,
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 4.0),
            child: Text(
              'Fonctionnalités & Paramètres',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: Colors.blueAccent,
                letterSpacing: 0.5,
              ),
            ),
          ),
          const SizedBox(height: 8),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.message_rounded,
                      color: Colors.indigo),
                  title:
                      const Text('Modèles de messages (Templates)'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) =>
                              const TemplatesAdminScreen()),
                    );
                  },
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.smart_toy_rounded,
                      color: Colors.teal),
                  title: const Text('Réponses Automatiques (Bot)'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => const BotRepliesScreen()),
                    );
                  },
                ),
                if (isAdmin) ...[
                  const Divider(height: 1),
                  ListTile(
                    leading: const Icon(Icons.water_drop_rounded,
                        color: Colors.lightBlue),
                    title: const Text('Campagnes Goutte à Goutte'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                            builder: (_) =>
                                const DripCampaignsSettingsScreen()),
                      );
                    },
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

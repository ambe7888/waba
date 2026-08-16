import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../config/app_config.dart';
import 'package:url_launcher/url_launcher.dart';
import 'support_tickets_screen.dart';
import 'resource_list_screen.dart';
import 'login_screen.dart';
import 'qr_code_screen.dart';
import 'notification_settings_screen.dart';
import 'templates_admin_screen.dart';
import 'bot_replies_screen.dart';
import 'drip_campaigns_settings_screen.dart';
import 'profile_screen.dart';

class AccountScreen extends StatefulWidget {
  const AccountScreen({super.key});

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen> {
  Map<String, dynamic>? _vendorInfo;
  Map<String, dynamic>? _stats;
  int _roleId = 3; // default: agent
  bool _canManageBot = false;
  bool _canManageTemplates = false;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadRoleAndPermissions();
    _loadData();
  }

  Future<void> _loadRoleAndPermissions() async {
    final roleId = await ApiService().getUserRoleId();
    final canManageBot = await ApiService().hasPermission('manage_bot_replies');
    final canManageTemplates = await ApiService().hasPermission('manage_templates');
    
    if (mounted) {
      setState(() {
        _roleId = roleId;
        _canManageBot = canManageBot;
        _canManageTemplates = canManageTemplates;
      });
    }
  }

  Future<void> _loadData() async {
    // Then fetch vendor info for display
    final stats = await ApiService().fetchDashboardStats();
    if (mounted && stats != null) {
      setState(() {
        _stats = stats;
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
    
    String userName = 'Profil';
    String userEmail = '';
    final vendorUserData = _stats?['vendorUserData'];
    if (vendorUserData != null) {
      userName = vendorUserData['first_name']?.toString() ?? '';
      final lastName = vendorUserData['last_name']?.toString() ?? '';
      if (lastName.isNotEmpty) userName += ' $lastName';
      if (userName.trim().isEmpty) userName = vendorUserData['full_name']?.toString() ?? '';
      userEmail = vendorUserData['email']?.toString() ?? '';
    }

    return Scaffold(
      backgroundColor: Colors.transparent,
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
              'Paramètres',
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
          // Business Info Card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDark ? const Color(0xFF1E293B) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: ThemeService.primaryColor.withValues(alpha: 0.2)),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: ThemeService.primaryColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(Icons.business_rounded, color: ThemeService.primaryColor, size: 28),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _vendorInfo?['title'] ?? 'Mon Entreprise',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      if (userEmail.isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Text(
                          userEmail,
                          style: TextStyle(
                              fontSize: 13,
                              color: isDark ? Colors.white54 : Colors.black54),
                        ),
                      ],
                      if (_vendorInfo?['whatsapp_number'] != null) ...[
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            Icon(Icons.phone_rounded, size: 12, color: ThemeService.primaryColor),
                            const SizedBox(width: 4),
                            Text(
                              '+${_vendorInfo!['whatsapp_number']}',
                              style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                  color: ThemeService.primaryColor),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
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

          // ── Général ────────────────────────────────────────────────
          _buildSectionTitle('Général'),
          Container(
            decoration: BoxDecoration(
              color: isDark ? const Color(0xFF1E293B) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
            ),
            child: Column(
              children: [
                _buildSettingsTile(
                  icon: Icons.person_outline_rounded,
                  title: 'Mon Profil',
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => ProfileScreen(userData: _stats?['vendorUserData'])),
                    );
                  },
                  isDark: isDark,
                ),
                _buildSettingsTile(
                  icon: Icons.qr_code,
                  title: 'Code QR',
                  onTap: _showQrCode,
                  isDark: isDark,
                ),
                _buildSettingsTile(
                  icon: Icons.notifications_none_rounded,
                  title: 'Notifications',
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) =>
                              const NotificationSettingsScreen()),
                    );
                  },
                  isDark: isDark,
                ),
                _buildSettingsTile(
                  icon: isDark ? Icons.light_mode : Icons.dark_mode,
                  title: isDark ? 'Mode Clair' : 'Mode Sombre',
                  onTap: () {
                    ThemeService().toggleTheme();
                  },
                  trailing: Switch(
                    value: isDark,
                    onChanged: (value) {
                      ThemeService().toggleTheme();
                    },
                    activeThumbColor: ThemeService.primaryColor,
                  ),
                  isDark: isDark,
                ),
              ],
            ),
          ),

          // ── Aide ────────────────────────────────────────────────
          _buildSectionTitle('Aide'),
          Container(
            decoration: BoxDecoration(
              color: isDark ? const Color(0xFF1E293B) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
            ),
            child: Column(
              children: [
                _buildSettingsTile(
                  icon: Icons.folder_open_rounded,
                  title: 'Ressources',
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => const ResourceListScreen()),
                    );
                  },
                  isDark: isDark,
                ),
                _buildSettingsTile(
                  icon: Icons.support_agent_rounded,
                  title: 'Assistance',
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => const SupportTicketsScreen()),
                    );
                  },
                  isDark: isDark,
                ),
              ],
            ),
          ),

          // ── Outils ────────────────────────────────────────────────
          if (isAdmin || _canManageBot || _canManageTemplates) ...[
            _buildSectionTitle('Outils'),
            Container(
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
              ),
              child: Column(
                children: [
                  if (isAdmin || _canManageBot)
                    _buildSettingsTile(
                      icon: Icons.smart_toy_rounded,
                      title: 'Réponses Automatiques',
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                              builder: (_) => const BotRepliesScreen()),
                        );
                      },
                      isDark: isDark,
                    ),
                  if (isAdmin || _canManageTemplates)
                    _buildSettingsTile(
                      icon: Icons.message_rounded,
                      title: 'Modèles de messages',
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                              builder: (_) => const TemplatesAdminScreen()),
                        );
                      },
                      isDark: isDark,
                    ),
                  if (isAdmin)
                    _buildSettingsTile(
                      icon: Icons.water_drop_rounded,
                      title: 'Campagnes Goutte à Goutte',
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                              builder: (_) => const DripCampaignsSettingsScreen()),
                        );
                      },
                      isDark: isDark,
                    ),
                ],
              ),
            ),
          ],

          // ── Application ────────────────────────────────────────────────
          _buildSectionTitle('Application'),
          Container(
            decoration: BoxDecoration(
              color: isDark ? const Color(0xFF1E293B) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
            ),
            child: Column(
              children: [
                _buildSettingsTile(
                  icon: Icons.system_update_rounded,
                  title: 'Mise à jour',
                  onTap: () async {
                    final url = Uri.parse('${baseUrl}downloads/whatsclick.apk');
                    if (await canLaunchUrl(url)) {
                      await launchUrl(url, mode: LaunchMode.externalApplication);
                    } else {
                      if (!mounted) return;
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Impossible d\'ouvrir le lien')),
                      );
                    }
                  },
                  isDark: isDark,
                ),
                _buildSettingsTile(
                  icon: Icons.logout,
                  title: 'Déconnexion',
                  iconColor: Colors.red,
                  onTap: _logout,
                  isDark: isDark,
                ),
              ],
            ),
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 8, top: 24, bottom: 8),
      child: Text(
        title.toUpperCase(),
        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 1),
      ),
    );
  }

  Widget _buildSettingsTile({
    required IconData icon,
    required String title,
    String? subtitle,
    Widget? trailing,
    required VoidCallback onTap,
    Color iconColor = Colors.grey,
    bool isDark = false,
  }) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        decoration: BoxDecoration(
          border: Border(bottom: BorderSide(color: isDark ? Colors.white10 : Colors.grey.withValues(alpha: 0.1))),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: isDark ? Colors.white10 : Colors.grey.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: isDark ? Colors.white24 : Colors.grey.shade200),
              ),
              child: Icon(icon, color: iconColor, size: 20),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w500)),
                  if (subtitle != null) ...[
                    const SizedBox(height: 2),
                    Text(subtitle, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                  ]
                ],
              ),
            ),
            trailing ?? const Icon(Icons.chevron_right, color: Colors.grey, size: 20),
          ],
        ),
      ),
    );
  }
}

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
import 'agents_screen.dart';
import 'ai_settings_screen.dart';
import 'shop_settings_screen.dart';
import 'orders_management_screen.dart';
import 'whatsapp_api_details_screen.dart';

class AccountScreen extends StatefulWidget {
  // Shared with MainLayoutScreen/HomeScreen so the update check only ever
  // hits the network once per launch instead of three times independently.
  // Still optional so this screen keeps working if ever opened standalone.
  final ValueNotifier<Map<String, dynamic>?>? updateInfoNotifier;

  const AccountScreen({super.key, this.updateInfoNotifier});

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen> {
  Map<String, dynamic>? _vendorInfo;
  Map<String, dynamic>? _stats;
  int _roleId = 3; // default: agent
  bool _canManageBot = false;
  bool _canManageTemplates = false;
  bool _canManageTeam = false;
  bool _isLoading = true;
  // Same check HomeScreen runs at launch to pop the "Mise à jour dispo"
  // dialog — kept here too so dismissing that dialog with "Plus tard"
  // still leaves a visible trace: a badge on the "Mise à jour" tile below.
  Map<String, dynamic>? _updateInfo;

  @override
  void initState() {
    super.initState();
    _loadRoleAndPermissions();
    _loadData();
    if (widget.updateInfoNotifier != null) {
      _updateInfo = widget.updateInfoNotifier!.value;
      widget.updateInfoNotifier!.addListener(_onSharedUpdateInfoChanged);
    } else {
      _checkForUpdate();
    }
  }

  @override
  void dispose() {
    widget.updateInfoNotifier?.removeListener(_onSharedUpdateInfoChanged);
    super.dispose();
  }

  void _onSharedUpdateInfoChanged() {
    if (mounted) {
      setState(() => _updateInfo = widget.updateInfoNotifier!.value);
    }
  }

  /// Fallback used only when this screen is opened without a shared
  /// notifier (e.g. in isolation) - MainLayoutScreen normally supplies one
  /// so this never runs in the app's real navigation flow.
  Future<void> _checkForUpdate() async {
    final updateInfo = await ApiService().checkForUpdate();
    if (mounted) {
      setState(() => _updateInfo = updateInfo);
    }
  }

  Future<void> _loadRoleAndPermissions() async {
    final roleId = await ApiService().getUserRoleId();
    final canManageBot = await ApiService().hasPermission('manage_bot_replies');
    final canManageTemplates = await ApiService().hasPermission('manage_templates');
    final canManageTeam = await ApiService().hasPermission('administrative');

    if (mounted) {
      setState(() {
        _roleId = roleId;
        _canManageBot = canManageBot;
        _canManageTemplates = canManageTemplates;
        _canManageTeam = canManageTeam;
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
    if (!mounted) return;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(
        child: CircularProgressIndicator(
          color: ThemeService.primaryColor,
        ),
      ),
    );

    await ApiService().logout();

    if (!mounted) return;
    Navigator.of(context).pop(); // Dismiss loading dialog
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  void _showQrCode() {
    // The real configured WhatsApp API number lives under whatsapp_setup,
    // not vendorInfo (vendorInfo only has title/id/uid/logo — it never had
    // a whatsapp_number field, so this always silently fell back below).
    final phone = _stats?['whatsapp_setup']?['phone_number']?.toString();
    if (phone == null || phone.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Aucun numéro WhatsApp configuré pour ce compte.")),
      );
      return;
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
    return ListenableBuilder(
      listenable: ThemeService(),
      builder: (context, _) {
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
                  iconColor: const Color(0xFF3B82F6),
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
                  iconColor: const Color(0xFF8B5CF6),
                  onTap: _showQrCode,
                  isDark: isDark,
                ),
                _buildSettingsTile(
                  icon: Icons.notifications_none_rounded,
                  title: 'Notifications',
                  iconColor: const Color(0xFFF59E0B),
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
                  iconColor: const Color(0xFF6366F1),
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

          // ── Outils ────────────────────────────────────────────────
          if (isAdmin || _canManageBot || _canManageTemplates || _canManageTeam) ...[
            _buildSectionTitle('Outils'),
            Container(
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
              ),
              child: Column(
                children: [
                  if (isAdmin || _canManageTeam)
                    _buildSettingsTile(
                      icon: Icons.groups_rounded,
                      title: 'Agents',
                      iconColor: const Color(0xFF7C3AED),
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const AgentsScreen()),
                        );
                      },
                      isDark: isDark,
                    ),
                  if (isAdmin)
                    _buildSettingsTile(
                      icon: Icons.facebook_rounded,
                      title: 'Paramètres WhatsApp API',
                      iconColor: const Color(0xFF1877F2),
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const WhatsAppApiDetailsScreen()),
                        );
                      },
                      isDark: isDark,
                    ),
                  if (isAdmin || _canManageBot)
                    _buildSettingsTile(
                      icon: Icons.psychology_alt_rounded,
                      title: 'Paramètres IA',
                      iconColor: const Color(0xFF6366F1),
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const AiSettingsScreen()),
                        );
                      },
                      isDark: isDark,
                    ),
                  if (isAdmin || _canManageBot)
                    _buildSettingsTile(
                      icon: Icons.smart_toy_rounded,
                      title: 'Réponses Automatiques',
                      iconColor: const Color(0xFF10B981),
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
                      iconColor: const Color(0xFFEC4899),
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
                      iconColor: const Color(0xFF0EA5E9),
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

          // ── Boutique ────────────────────────────────────────────────
          if (isAdmin) ...[
            _buildSectionTitle('Boutique'),
            Container(
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
              ),
              child: Column(
                children: [
                  _buildSettingsTile(
                    icon: Icons.storefront_rounded,
                    title: 'Paramètres boutique',
                    iconColor: const Color(0xFFF59E0B),
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const ShopSettingsScreen()),
                      );
                    },
                    isDark: isDark,
                  ),
                  _buildSettingsTile(
                    icon: Icons.receipt_long_rounded,
                    title: 'Gestion des commandes',
                    iconColor: const Color(0xFF059669),
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const OrdersManagementScreen()),
                      );
                    },
                    isDark: isDark,
                  ),
                ],
              ),
            ),
          ],

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
                  iconColor: const Color(0xFF14B8A6),
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
                  iconColor: const Color(0xFF06B6D4),
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
                  subtitle: _updateInfo != null
                      ? 'Nouvelle version ${_updateInfo!['version']} disponible'
                      : null,
                  showBadge: _updateInfo != null,
                  iconColor: const Color(0xFF22C55E),
                  onTap: () async {
                    final apkUrl = _updateInfo?['apk_url']?.toString() ??
                        '${baseUrl}downloads/whatsclick-latest.apk';
                    final url = Uri.parse(apkUrl);
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
          const SizedBox(height: 20),
          Center(
            child: Text(
              'Version $version',
              style: TextStyle(
                fontSize: 11,
                color: isDark ? Colors.white24 : Colors.black26,
              ),
            ),
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  },
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
    bool showBadge = false,
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
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: iconColor.withValues(alpha: isDark ? 0.18 : 0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: iconColor.withValues(alpha: isDark ? 0.3 : 0.2)),
                  ),
                  child: Icon(icon, color: iconColor, size: 20),
                ),
                if (showBadge)
                  Positioned(
                    right: -3,
                    top: -3,
                    child: Container(
                      width: 12,
                      height: 12,
                      decoration: BoxDecoration(
                        color: Colors.redAccent,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: isDark ? const Color(0xFF1E293B) : Colors.white,
                          width: 2,
                        ),
                      ),
                    ),
                  ),
              ],
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

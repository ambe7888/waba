import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'manage_subscription_screen.dart';
import 'manage_waba_screen.dart';
import 'notifications_screen.dart';
import 'account_screen.dart';
import 'profile_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen>
    with SingleTickerProviderStateMixin {
  bool _isLoading = true;
  Map<String, dynamic>? _stats;
  String? _error;
  int _roleId = 3;
  String _firstName = '';
  late TabController _tabController;
  int _currentTabIndex = 0;
  int _unreadNotificationsCount = 0;

  // Filter for template category
  final String _selectedTemplateCategory = 'TOUS';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        setState(() => _currentTabIndex = _tabController.index);
      }
    });
    _fetchDashboardStats();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _fetchDashboardStats() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      _roleId = await ApiService().getUserRoleId();
      final data = await ApiService().fetchDashboardStats();
      final notifData = await ApiService().fetchNotifications();
      
      if (mounted) {
        setState(() {
          _stats = data;
          _unreadNotificationsCount = notifData['unreadCount'] ?? 0;
          final vendorUserData = data?['vendorUserData'];
          if (vendorUserData != null) {
            String name = vendorUserData['first_name']?.toString() ?? '';
            if (name.isEmpty) name = vendorUserData['full_name']?.toString() ?? '';
            if (name.isEmpty && vendorUserData['profile'] != null) {
              name = vendorUserData['profile']['first_name']?.toString() ?? '';
              if (name.isEmpty) name = vendorUserData['profile']['full_name']?.toString() ?? '';
            }
            if (name.isEmpty) {
              name = vendorUserData['email']?.toString() ?? '';
              if (name.contains('@')) name = name.split('@').first;
            }
            _firstName = name;
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Erreur lors du chargement du tableau de bord';
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    final isAdmin = _roleId == 2;

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.asset(
                'assets/icon/launcher_icon.png',
                width: 32,
                height: 32,
                fit: BoxFit.cover,
              ),
            ),
            const SizedBox(width: 10),
            const Text(
              'WhatsClick',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.5,
              ),
            ),
          ],
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        actions: [
          IconButton(
            icon: Icon(
              isDark ? Icons.light_mode_rounded : Icons.dark_mode_rounded,
              color: isDark ? Colors.amber : const Color(0xFF4B5563),
            ),
            onPressed: () {
              ThemeService().toggleTheme();
            },
          ),
          IconButton(
            icon: Stack(
              clipBehavior: Clip.none,
              children: [
                Icon(
                  Icons.notifications_none_rounded,
                  color: isDark ? Colors.white : Colors.black87,
                ),
                if (_unreadNotificationsCount > 0)
                  Positioned(
                    right: -2,
                    top: -2,
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(
                        color: Colors.red,
                        shape: BoxShape.circle,
                      ),
                      child: Text(
                        _unreadNotificationsCount > 9 ? '9+' : _unreadNotificationsCount.toString(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            onPressed: () async {
              await Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const NotificationsScreen()),
              );
              // Refresh on return
              _fetchDashboardStats();
            },
          ),
          GestureDetector(
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => ProfileScreen(userData: _stats?['vendorUserData'])),
            ),
            child: Container(
              margin: const EdgeInsets.only(right: 16, left: 4),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [ThemeService.primaryColor, const Color(0xFF10B981)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(10),
                boxShadow: [
                  BoxShadow(
                    color: ThemeService.primaryColor.withValues(alpha: 0.3),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  )
                ],
              ),
              width: 38,
              height: 38,
              child: Center(
                child: Text(
                  _firstName.isNotEmpty ? _firstName[0].toUpperCase() : 'W',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_error!, style: const TextStyle(color: Colors.red)),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchDashboardStats,
                        child: const Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Fixed Header greeting
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Bonjour${_firstName.isNotEmpty ? ', $_firstName' : ''} 👋',
                                style: TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.w800,
                                  color: isDark ? Colors.white : Colors.black87,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Aperçu de vos performances marketing WhatsApp',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: isDark ? Colors.white54 : Colors.black54,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: RefreshIndicator(
                        onRefresh: _fetchDashboardStats,
                        child: ListView(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [
                            // 1. Carte Abonnement (WAPI Style)
                            if (isAdmin) _buildWapiSubscriptionCard(isDark),
                            if (isAdmin) const SizedBox(height: 16),

                            // 2. Carte WhatsApp API
                            _buildWabaCard(isDark),
                            const SizedBox(height: 24),

                            // 3. Onglets : Général à gauche, Abonnement à droite
                            if (isAdmin)
                              Container(
                                height: 44,
                                padding: const EdgeInsets.all(4),
                                decoration: BoxDecoration(
                                  color: isDark
                                      ? Colors.white.withValues(alpha: 0.05)
                                      : Colors.black.withValues(alpha: 0.04),
                                  borderRadius: BorderRadius.circular(22),
                                  border: Border.all(
                                    color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0),
                                  ),
                                ),
                                child: TabBar(
                                  controller: _tabController,
                                  indicator: BoxDecoration(
                                    borderRadius: BorderRadius.circular(18),
                                    color: ThemeService.primaryColor,
                                    boxShadow: [
                                      BoxShadow(
                                        color: ThemeService.primaryColor.withValues(alpha: 0.3),
                                        blurRadius: 8,
                                        offset: const Offset(0, 2),
                                      ),
                                    ],
                                  ),
                                  indicatorSize: TabBarIndicatorSize.tab,
                                  labelColor: Colors.white,
                                  unselectedLabelColor: isDark ? Colors.white60 : Colors.black54,
                                  labelStyle: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13,
                                  ),
                                  dividerColor: Colors.transparent,
                                  onTap: (i) => setState(() => _currentTabIndex = i),
                                  tabs: const [
                                    Tab(text: 'Général'),
                                    Tab(text: 'Abonnement'),
                                  ],
                                ),
                              ),
                            if (isAdmin) const SizedBox(height: 16),

                            // Tab Content
                            (!isAdmin || _currentTabIndex == 0)
                                ? _buildGeneralTab(isDark)
                                : _buildSubscriptionTab(isDark),

                      const SizedBox(height: 28),

                      // 4. Section Statistiques des Campagnes (Hors onglets)
                      _buildCampaignStatsSection(isDark),
                      const SizedBox(height: 24),

                      // 5. Section Statistiques des Modèles / Templates (Hors onglets)
                      _buildTemplateStatsSection(isDark),
                      const SizedBox(height: 24),

                      // 6. Section Statistiques des Ventes (Hors onglets)
                      _buildOrderStatsSection(isDark),
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
              ),
            ],
          ),
    );
  }

  // ── 1. Carte Abonnement Style WAPI (Bordures Sombre + Jours Restants) ─────
  Widget _buildWapiSubscriptionCard(bool isDark) {
    final sub = _stats?['current_subscription'] ?? _stats?['vendorUserData']?['current_subscription'];
    final isExpired = sub?['is_expired'] == true;
    final isFree = sub?['is_free'] == true;
    
    String planTitle = sub?['title']?.toString() ?? 'Aucun abonnement';
    if (isFree || planTitle.toLowerCase().contains('gratuit')) {
      planTitle = 'Aucun abonnement';
    } else if (isExpired) {
      planTitle = 'Abonnement expiré';
    }

    final billingCycle = sub?['billing_cycle']?.toString() ?? 'Mensuel';
    final endsAt = sub?['ends_at']?.toString() ?? '';
    final remainingDays = (sub?['remaining_days'] as num?)?.toInt() ?? 0;
    final totalDays = (sub?['total_days'] as num?)?.toInt() ?? 30;
    final progress = (sub?['progress'] as num?)?.toDouble() ?? (totalDays > 0 ? (remainingDays / totalDays) : 1.0);
    
    final rawPrice = sub?['price']?.toString() ?? '0';
    String price = '0 CFA';
    if (rawPrice != '0' && !isFree) {
      try {
        final val = double.parse(rawPrice).toInt();
        final str = val.toString();
        String result = '';
        for (int i = 0; i < str.length; i++) {
          if (i > 0 && (str.length - i) % 3 == 0) {
            result += ' ';
          }
          result += str[i];
        }
        price = '$result CFA';
      } catch (_) {
        price = '$rawPrice CFA';
      }
    }

    String formattedNextPayment = 'Date non spécifiée';
    String rawDate = '';
    if (endsAt.isNotEmpty) {
      try {
        final dt = DateTime.parse(endsAt);
        rawDate = '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';
        formattedNextPayment = '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
      } catch (_) {
        formattedNextPayment = endsAt;
        rawDate = endsAt;
      }
    }

    final String cycleLabel = billingCycle.toLowerCase().contains('annuel') ? '/année' : '/mois';
    final String billedLabel = isFree ? 'Aucun accès' : (billingCycle.toLowerCase().contains('annuel') ? 'Paiement annuel' : 'Paiement mensuel');

    // Status Colors
    final Color statusColor = isExpired ? const Color(0xFFDC2626) : const Color(0xFF10B981);
    final String statusText = isExpired ? 'Expiré' : (isFree ? 'Aucun' : 'Actif');

    // Progress Bar Color based on remaining days
    Color progressColor = const Color(0xFF10B981); // Green
    if (remainingDays <= 5 && !isFree) {
      progressColor = const Color(0xFFDC2626); // Red
    } else if (remainingDays <= 15 && !isFree) {
      progressColor = const Color(0xFFF59E0B); // Yellow/Orange
    }

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? const Color(0xFF334155) : const Color(0xFFE5E7EB),
          width: 1.5,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Row 1: Icon, Title, Status
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Orange Crown Icon
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: Colors.transparent,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFF97316), width: 1.5),
                ),
                child: const Center(
                  child: Icon(Icons.workspace_premium_rounded, color: Color(0xFFF97316), size: 28),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            planTitle,
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                              color: isDark ? Colors.white : const Color(0xFF111827),
                              height: 1.2,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Status Badge
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.transparent,
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: statusColor, width: 1.2),
                          ),
                          child: Text(
                            statusText,
                            style: TextStyle(
                              color: statusColor,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      isFree 
                        ? 'AUCUN ABONNEMENT ACTIF' 
                        : (isExpired ? 'ABONNEMENT EXPIRÉ DEPUIS: $rawDate' : 'PROCHAIN RENOUV.: $rawDate ($remainingDays JOURS)'),
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFFF97316),
                        letterSpacing: 0.5,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          
          const SizedBox(height: 16),
          Divider(color: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6), thickness: 1.5),
          const SizedBox(height: 16),

          // Row 2: Price & Billing Details
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              // Price
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    price == '0' ? 'Gratuit' : price,
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w900,
                      color: isDark ? Colors.white : const Color(0xFF111827),
                      height: 1,
                    ),
                  ),
                  if (price != '0') ...[
                    const SizedBox(width: 4),
                    Text(
                      ' $cycleLabel',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: isDark ? Colors.white70 : const Color(0xFF4B5563),
                      ),
                    ),
                  ],
                ],
              ),
              // Bullet Points
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.circle, size: 5, color: isDark ? Colors.white70 : const Color(0xFF4B5563)),
                      const SizedBox(width: 6),
                      Text(
                        billedLabel,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                          color: isDark ? Colors.white70 : const Color(0xFF374151),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  if (!isFree)
                  Row(
                    children: [
                      Icon(Icons.circle, size: 5, color: isDark ? Colors.white70 : const Color(0xFF4B5563)),
                      const SizedBox(width: 6),
                      Text(
                        'Relance activée',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                          color: isDark ? Colors.white70 : const Color(0xFF374151),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),

          if (!isFree) ...[
            const SizedBox(height: 16),
            Divider(color: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6), thickness: 1.5),
            const SizedBox(height: 16),

            // Row 3: Progress Bar
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Renouvellement dans',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: isDark ? Colors.white : const Color(0xFF111827),
                  ),
                ),
                Text(
                  '$remainingDays jours',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: statusColor,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: LinearProgressIndicator(
                value: progress.clamp(0.0, 1.0),
                minHeight: 10,
                backgroundColor: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6),
                valueColor: AlwaysStoppedAnimation<Color>(progressColor),
              ),
            ),
          ],
          
          const SizedBox(height: 24),

          // Action Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => ManageSubscriptionScreen(
                    subscriptionData: sub != null
                        ? Map<String, dynamic>.from(sub as Map)
                        : {},
                    statsData: _stats ?? {},
                  ),
                ),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF10B981), // Solid WAPI Green
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                elevation: 0,
              ),
              child: Text(
                isFree ? 'Choisir un abonnement' : 'Gérer mon abonnement',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── 2. Carte WhatsApp API ────────────────────────────────────────────────
  Widget _buildWabaCard(bool isDark) {
    final wabaSetup = _stats?['whatsapp_setup'] as Map?;
    final vendorInfo = _stats?['vendorInfo'] as Map?;

    final phone = wabaSetup?['phone_number']?.toString().isNotEmpty == true
        ? wabaSetup!['phone_number'].toString()
        : vendorInfo?['whatsapp_phone_number']?.toString() ?? '';
    
    final phoneId = wabaSetup?['phone_number_id']?.toString() ?? vendorInfo?['whatsapp_phone_number_id']?.toString() ?? '';
    final wabaId = wabaSetup?['waba_id']?.toString() ?? vendorInfo?['whatsapp_waba_id']?.toString() ?? '';

    final isConnected = wabaSetup?['is_connected'] == true || phone.isNotEmpty;

    // Status Colors
    final Color statusColor = isConnected ? const Color(0xFF10B981) : const Color(0xFFDC2626);
    final String statusText = isConnected ? 'Connecté' : 'Déconnecté';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? const Color(0xFF334155) : const Color(0xFFE5E7EB),
          width: 1.5,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Row 1: Icon, Title, Status
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Green Icon Box
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: Colors.transparent,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFF10B981), width: 1.5),
                ),
                child: const Center(
                  child: Icon(Icons.security_rounded, color: Color(0xFF10B981), size: 26),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            'WhatsApp API',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                              color: isDark ? Colors.white : const Color(0xFF111827),
                              height: 1.2,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Status Badge
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.transparent,
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: statusColor, width: 1.2),
                          ),
                          child: Text(
                            statusText,
                            style: TextStyle(
                              color: statusColor,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      isConnected ? 'VOTRE NUMÉRO WHATSAPP EST SÉCURISÉ' : 'VEUILLEZ CONNECTER VOTRE API WHATSAPP',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: isConnected ? const Color(0xFF10B981) : const Color(0xFFDC2626),
                        letterSpacing: 0.5,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          
          const SizedBox(height: 16),
          Divider(color: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6), thickness: 1.5),
          const SizedBox(height: 12),

          // Details section
          if (isConnected) ...[
            _buildApiDetailRow('Numéro de Tél', '+$phone', isDark),
            const SizedBox(height: 8),
            _buildApiDetailRow('ID du Numéro', phoneId, isDark),
            const SizedBox(height: 8),
            _buildApiDetailRow('WABA ID', wabaId, isDark),
            const SizedBox(height: 16),
            Divider(color: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6), thickness: 1.5),
            const SizedBox(height: 16),
          ],

          // Action Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => ManageWabaScreen(
                    wabaData: Map<String, dynamic>.from(wabaSetup ?? vendorInfo ?? {}),
                  ),
                ),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: isConnected ? const Color(0xFF10B981) : const Color(0xFFF97316),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                elevation: 0,
              ),
              child: const Text(
                'Gérer WhatsApp API',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildApiDetailRow(String label, String value, bool isDark) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w500,
            color: isDark ? Colors.white70 : const Color(0xFF4B5563),
          ),
        ),
        Text(
          value.isNotEmpty ? value : 'N/A',
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: isDark ? Colors.white : const Color(0xFF111827),
          ),
        ),
      ],
    );
  }

  // ── 3A. Onglet Général ───────────────────────────────────────────────────
  Widget _buildGeneralTab(bool isDark) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                'Contacts Totaux',
                Icons.contacts_rounded,
                const Color(0xFF6C63FF),
                _stats?['totalContacts']?.toString() ?? '0',
                isDark,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard(
                'Messages Envoyés',
                Icons.send_rounded,
                Colors.green,
                _stats?['totalMessagesProcessed']?.toString() ?? '0',
                isDark,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                'Reçus aujourd\'hui',
                Icons.chat_bubble_outline_rounded,
                Colors.teal,
                _stats?['messagesReceivedTodayCount']?.toString() ?? '0',
                isDark,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard(
                'Messages Non Lus',
                Icons.mark_chat_unread_rounded,
                Colors.redAccent,
                _stats?['unreadMessagesCount']?.toString() ?? '0',
                isDark,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                'Contacts Actifs (24h)',
                Icons.timer_rounded,
                Colors.orange,
                _stats?['activeContacts24hCount']?.toString() ?? '0',
                isDark,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard(
                'Agents Actifs',
                Icons.support_agent_rounded,
                Colors.blue,
                _stats?['agents']?.length?.toString() ?? _stats?['activeTeamMembers']?.toString() ?? '0',
                isDark,
              ),
            ),
          ],
        ),
      ],
    );
  }

  // ── 3B. Onglet Abonnement ────────────────────────────────────────────────
  Widget _buildSubscriptionTab(bool isDark) {
    final sub = _stats?['current_subscription'] ?? _stats?['vendorUserData']?['current_subscription'];
    final limits = sub?['limits'] as Map? ?? {};
    
    // Usage counts
    final cContacts = _stats?['totalContacts']?.toString() ?? '0';
    final cCampaigns = _stats?['totalCampaigns']?.toString() ?? '0';
    final cBotReplies = _stats?['totalBotReplies']?.toString() ?? '0';
    final cDrip = _stats?['totalDripCampaigns']?.toString() ?? '0';
    final cBotFlows = _stats?['totalBotFlows']?.toString() ?? '0';
    final cAgents = _stats?['agents']?.length?.toString() ?? _stats?['activeTeamMembers']?.toString() ?? '0';

    // Limits
    String getLimit(String key) {
      final lim = limits[key];
      if (lim == null) return '0';
      if (lim == -1) return '∞';
      return lim.toString();
    }

    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard('Contacts', Icons.contacts_rounded, const Color(0xFF6C63FF), '$cContacts / ${getLimit("contacts")}', isDark),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard('Campagnes', Icons.campaign_rounded, Colors.pinkAccent, '$cCampaigns / ${getLimit("campaigns")}', isDark),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildStatCard('Rép. du Bot', Icons.smart_toy_rounded, Colors.green, '$cBotReplies / ${getLimit("bot_replies")}', isDark),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard('Drip Camp.', Icons.water_drop_rounded, Colors.teal, '$cDrip / ${getLimit("drip_campaigns")}', isDark),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildStatCard('Flux du Bot', Icons.account_tree_rounded, Colors.purple, '$cBotFlows / ${getLimit("bot_flows")}', isDark),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard('Agents', Icons.support_agent_rounded, Colors.blue, '$cAgents / ${getLimit("system_users")}', isDark),
            ),
          ],
        ),
      ],
    );
  }

  // ── 4. Section Statistiques des Campagnes ───────────────────────────────
  Widget _buildCampaignStatsSection(bool isDark) {
    final cStats = _stats?['campaign_stats'] as Map? ?? {};
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader('Statistiques des Campagnes', Icons.campaign_rounded, Colors.pinkAccent, isDark),
        const SizedBox(height: 12),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          clipBehavior: Clip.none,
          child: Row(
            children: [
              _buildScrollableCard('Campagnes', _stats?['totalCampaigns']?.toString() ?? '0', Icons.campaign_rounded, Colors.pinkAccent, isDark),
              _buildScrollableCard('Terminé', cStats['completed']?.toString() ?? '0', Icons.check_circle_rounded, Colors.green, isDark),
              _buildScrollableCard('En cours', cStats['processing']?.toString() ?? '0', Icons.sync_rounded, Colors.blue, isDark),
              _buildScrollableCard('Programmé', cStats['scheduled']?.toString() ?? '0', Icons.schedule_rounded, Colors.orange, isDark),
              _buildScrollableCard('Archivés', cStats['archived']?.toString() ?? '0', Icons.archive_rounded, Colors.grey, isDark),
              _buildScrollableCard('Msg Envoyés', _stats?['totalMessagesProcessed']?.toString() ?? '0', Icons.send_rounded, Colors.teal, isDark),
            ],
          ),
        ),
      ],
    );
  }

  // ── 5. Section Statistiques des Modèles (Templates) ──────────────────────
  Widget _buildTemplateStatsSection(bool isDark) {
    final tStats = _stats?['template_stats'] as Map? ?? {};
    final approved = tStats['approved']?.toString() ?? '0';
    final rejected = tStats['rejected']?.toString() ?? '0';
    final pending = tStats['pending']?.toString() ?? '0';
    final utilityCount = tStats['utility']?.toString() ?? '0';
    final marketingCount = tStats['marketing']?.toString() ?? '0';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader('Statistiques des Modèles', Icons.grid_view_rounded, Colors.purple, isDark),
        const SizedBox(height: 12),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          clipBehavior: Clip.none,
          child: Row(
            children: [
              _buildScrollableCard('Modèles', _stats?['totalTemplates']?.toString() ?? '0', Icons.file_copy_rounded, Colors.purple, isDark),
              _buildScrollableCard('Marketing', marketingCount, Icons.mark_email_read_rounded, Colors.pinkAccent, isDark),
              _buildScrollableCard('Utilitaire', utilityCount, Icons.build_rounded, Colors.teal, isDark),
              _buildScrollableCard('Approuvé', approved, Icons.check_circle_rounded, Colors.green, isDark),
              _buildScrollableCard('En attente', pending, Icons.hourglass_top_rounded, Colors.amber, isDark),
              _buildScrollableCard('Rejeté', rejected, Icons.cancel_rounded, Colors.red, isDark),
            ],
          ),
        ),
      ],
    );
  }

  // ── Helper UI Elements ───────────────────────────────────────────────────
  Widget _buildSectionHeader(String title, IconData icon, Color color, bool isDark) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(6),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: color, size: 16),
        ),
        const SizedBox(width: 10),
        Text(
          title,
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w700,
            color: isDark ? Colors.white : Colors.black87,
          ),
        ),
      ],
    );
  }

  Widget _buildStatCard(String title, IconData icon, Color color, String value, bool isDark) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: color.withValues(alpha: isDark ? 0.4 : 0.25),
          width: 1.5,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 16),
          ),
          const SizedBox(height: 10),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: isDark ? Colors.white : Colors.black87,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            title,
            style: TextStyle(fontSize: 11, color: isDark ? Colors.white54 : Colors.black54),
          ),
        ],
      ),
    );
  }

  Widget _buildFeatureRow(String label, bool enabled, bool isDark, {bool isLast = false}) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Row(
            children: [
              Icon(
                enabled ? Icons.check_circle_rounded : Icons.cancel_rounded,
                color: enabled ? Colors.green : Colors.grey,
                size: 18,
              ),
              const SizedBox(width: 10),
              Text(
                label,
                style: TextStyle(
                  fontSize: 13,
                  color: isDark ? Colors.white : Colors.black87,
                ),
              ),
            ],
          ),
        ),
        if (!isLast)
          Divider(
            height: 1,
            color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0),
          ),
      ],
    );
  }

  Widget _buildScrollableCard(String title, String value, IconData icon, Color color, bool isDark) {
    return Container(
      width: 120,
      margin: const EdgeInsets.only(right: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: color.withValues(alpha: isDark ? 0.4 : 0.25),
          width: 1.5,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 16),
          ),
          const SizedBox(height: 10),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: isDark ? Colors.white : Colors.black87,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            title,
            style: TextStyle(fontSize: 11, color: isDark ? Colors.white54 : Colors.black54),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  // ── 6. Section Statistiques des Ventes ───────────────────────────────
  Widget _buildOrderStatsSection(bool isDark) {
    final oStats = _stats?['order_stats'] as Map? ?? {};
    if (oStats.isEmpty) return const SizedBox.shrink(); // E-commerce non activé / pas dispo

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader('Statistiques des Ventes', Icons.shopping_cart_rounded, Colors.indigo, isDark),
        const SizedBox(height: 12),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          clipBehavior: Clip.none,
          child: Row(
            children: [
              _buildScrollableCard('Commandes', _stats?['ordersCount']?.toString() ?? '0', Icons.shopping_cart_rounded, Colors.indigo, isDark),
              _buildScrollableCard('Aujourd\'hui', _stats?['ordersTodayCount']?.toString() ?? '0', Icons.today_rounded, Colors.blue, isDark),
              _buildScrollableCard('En attente', oStats['pending']?.toString() ?? '0', Icons.pending_actions_rounded, Colors.amber, isDark),
              _buildScrollableCard('Finalisé', oStats['completed']?.toString() ?? '0', Icons.check_circle_rounded, Colors.green, isDark),
            ],
          ),
        ),
      ],
    );
  }
}

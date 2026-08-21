import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../config/app_config.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class ManageSubscriptionScreen extends StatefulWidget {
  final Map<String, dynamic> subscriptionData;
  final Map<String, dynamic> statsData;

  const ManageSubscriptionScreen({
    super.key,
    required this.subscriptionData,
    required this.statsData,
  });

  @override
  State<ManageSubscriptionScreen> createState() => _ManageSubscriptionScreenState();
}

class _ManageSubscriptionScreenState extends State<ManageSubscriptionScreen> {
  bool _isLoadingHistory = true;
  List<Map<String, dynamic>> _history = [];

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    final data = await ApiService().fetchSubscriptionHistory();
    if (mounted) {
      setState(() {
        _history = data;
        _isLoadingHistory = false;
      });
    }
  }

  Future<void> _openInvoice(String subscriptionUid) async {
    final url = Uri.parse('${baseUrl}vendor-console/subscription/invoice/$subscriptionUid/print');
    final opened = await launchUrl(url, mode: LaunchMode.externalApplication);
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir le navigateur.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final subscriptionData = widget.subscriptionData;
    final isDark = ThemeService().isDark;

    final isExpired = subscriptionData['is_expired'] == true;
    final isFree = subscriptionData['is_free'] == true;
    final features = subscriptionData['features'] as Map? ?? {};
    
    String title = subscriptionData['title']?.toString() ?? 'Aucun abonnement';
    if (isFree || title.toLowerCase().contains('gratuit')) {
      title = 'Aucun abonnement';
    } else if (isExpired) {
      title = 'Abonnement expiré';
    }

    final remainingDays = (subscriptionData['remaining_days'] as num?)?.toInt() ?? 0;
    final totalDays = (subscriptionData['total_days'] as num?)?.toInt() ?? 30;
    final progress = (subscriptionData['progress'] as num?)?.toDouble() ?? (totalDays > 0 ? (remainingDays / totalDays) : 1.0);
    
    final rawPrice = subscriptionData['price']?.toString() ?? '0';
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
    
    final billingCycle = subscriptionData['billing_cycle']?.toString() ?? 'Mensuel';
    final endsAt = subscriptionData['ends_at']?.toString() ?? '';

    String rawDate = '';
    if (endsAt.isNotEmpty) {
      try {
        final dt = DateTime.parse(endsAt);
        rawDate = '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';
      } catch (_) {
        rawDate = endsAt;
      }
    }

    final String cycleLabel = billingCycle.toLowerCase().contains('annuel') ? '/année' : '/mois';
    final String billedLabel = isFree ? 'Aucun accès' : (billingCycle.toLowerCase().contains('annuel') ? 'Paiement annuel' : 'Paiement mensuel');

    final Color statusColor = isExpired ? const Color(0xFFDC2626) : const Color(0xFF10B981);
    final String statusText = isExpired ? 'Expiré' : (isFree ? 'Aucun' : 'Actif');

    Color progressColor = const Color(0xFF10B981);
    if (remainingDays <= 5 && !isFree) {
      progressColor = const Color(0xFFDC2626);
    } else if (remainingDays <= 15 && !isFree) {
      progressColor = const Color(0xFFF59E0B);
    }

    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded,
              color: isDark ? Colors.white : Colors.black87),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'Détails Abonnement',
          style: TextStyle(
            color: isDark ? Colors.white : Colors.black87,
            fontSize: 18,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // ── Carte Abonnement Style WAPI ──────────────────────────────────────────
          Container(
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
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
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
                                  title,
                                  style: TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w900,
                                    color: isDark ? Colors.white : const Color(0xFF111827),
                                    height: 1.2,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
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

                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          price,
                          style: TextStyle(
                            fontSize: 28,
                            fontWeight: FontWeight.w900,
                            color: isDark ? Colors.white : const Color(0xFF111827),
                            height: 1,
                          ),
                        ),
                        if (rawPrice != '0') ...[
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
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: isDark ? const Color(0xFF1E293B) : const Color(0xFFF3F4F6),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        billedLabel,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: isDark ? Colors.white70 : const Color(0xFF4B5563),
                        ),
                      ),
                    ),
                  ],
                ),

                if (!isFree) ...[
                  const SizedBox(height: 20),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Progression du cycle',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white70 : const Color(0xFF6B7280),
                        ),
                      ),
                      Text(
                        '${(progress * 100).toInt()}%',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                          color: isDark ? Colors.white : const Color(0xFF111827),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: progress,
                      backgroundColor: isDark ? const Color(0xFF334155) : const Color(0xFFE5E7EB),
                      valueColor: AlwaysStoppedAnimation<Color>(progressColor),
                      minHeight: 6,
                    ),
                  ),
                ],
              ],
            ),
          ),

          // ── Fonctionnalités incluses ────────────────────────────────────
          if (features.isNotEmpty) ...[
            const SizedBox(height: 24),
            _sectionTitle('Fonctionnalités incluses', isDark),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: isDark
                      ? Colors.white.withValues(alpha: 0.05)
                      : Colors.black.withValues(alpha: 0.05),
                ),
              ),
              child: Column(
                children: [
                  _featureRow(
                    'Gestion des commandes',
                    features['manage_orders'] == true,
                    isDark,
                  ),
                  _featureRow('Bot IA', features['ai_bot'] == true, isDark),
                  _featureRow('Campagnes', features['campaigns'] == true, isDark),
                  _featureRow(
                    'Réponses pré-enregistrées',
                    features['canned_replies'] == true,
                    isDark,
                    isLast: true,
                  ),
                ],
              ),
            ),
          ],

          // ── Historique des Factures & Abonnements ──────────────────────
          const SizedBox(height: 24),
          _sectionTitle('Historique des Factures & Abonnements', isDark),
          const SizedBox(height: 12),
          _buildHistorySection(isDark),
        ],
      ),
    );
  }

  Widget _sectionTitle(String title, bool isDark) {
    return Text(
      title,
      style: TextStyle(
        fontSize: 15,
        fontWeight: FontWeight.w700,
        color: isDark ? Colors.white : Colors.black87,
      ),
    );
  }

  Widget _featureRow(String label, bool enabled, bool isDark, {bool isLast = false}) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Row(
            children: [
              Icon(
                enabled ? Icons.check_circle_rounded : Icons.cancel_rounded,
                color: enabled ? Colors.green : Colors.grey,
                size: 20,
              ),
              const SizedBox(width: 12),
              Text(
                label,
                style: TextStyle(
                  fontSize: 14,
                  color: isDark ? Colors.white : Colors.black87,
                ),
              ),
            ],
          ),
        ),
        if (!isLast)
          Divider(
            height: 1,
            color: isDark ? Colors.white10 : Colors.black.withValues(alpha: 0.05),
          ),
      ],
    );
  }

  Widget _buildHistorySection(bool isDark) {
    if (_isLoadingHistory) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 24),
        child: Center(child: CircularProgressIndicator()),
      );
    }

    if (_history.isEmpty) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isDark ? Colors.white.withValues(alpha: 0.05) : Colors.black.withValues(alpha: 0.05),
          ),
        ),
        child: Column(
          children: [
            Icon(Icons.receipt_long_rounded, size: 40, color: Colors.grey.withValues(alpha: 0.5)),
            const SizedBox(height: 12),
            Text(
              'Aucun abonnement trouvé',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: isDark ? Colors.white70 : Colors.black87,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Vos abonnements apparaîtront ici une fois que vous en aurez souscrit un.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : Colors.black54),
            ),
          ],
        ),
      );
    }

    return Column(
      children: _history.map((sub) => _buildInvoiceCard(sub, isDark)).toList(),
    );
  }

  Widget _buildInvoiceCard(Map<String, dynamic> sub, bool isDark) {
    final bool isActive = sub['is_active'] == true;
    final bool isExpired = sub['is_expired'] == true;

    Color statusColor = const Color(0xFFD97706);
    IconData statusIcon = Icons.schedule_rounded;
    if (isActive) {
      statusColor = const Color(0xFF059669);
      statusIcon = Icons.check_circle_rounded;
    } else if (isExpired) {
      statusColor = const Color(0xFFDC2626);
      statusIcon = Icons.cancel_rounded;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? Colors.white.withValues(alpha: 0.05) : Colors.black.withValues(alpha: 0.05),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: (isActive ? const Color(0xFF10B981) : Colors.grey).withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.badge_rounded,
                    size: 17, color: isActive ? const Color(0xFF10B981) : Colors.grey.shade600),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      sub['plan_title']?.toString() ?? '',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: isDark ? Colors.white : const Color(0xFF1E293B),
                      ),
                    ),
                    Text(
                      sub['freq_title']?.toString() ?? '',
                      style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.black54),
                    ),
                  ],
                ),
              ),
              Text(
                sub['charges']?.toString() ?? '',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w800,
                  color: isDark ? Colors.white : const Color(0xFF1E293B),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Divider(height: 1, color: isDark ? Colors.white10 : Colors.black.withValues(alpha: 0.05)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _dateColumn(isDark, 'Début', sub['created_at']?.toString() ?? '-'),
              ),
              Expanded(
                child: _dateColumn(isDark, 'Expire le', sub['ends_at']?.toString() ?? '-',
                    valueColor: isExpired ? const Color(0xFFDC2626) : null),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(statusIcon, size: 12, color: statusColor),
                    const SizedBox(width: 4),
                    Text(
                      sub['status']?.toString() ?? '',
                      style: TextStyle(color: statusColor, fontSize: 11.5, fontWeight: FontWeight.w700),
                    ),
                  ],
                ),
              ),
              const Spacer(),
              OutlinedButton.icon(
                onPressed: () => _openInvoice(sub['_uid']),
                icon: const Icon(Icons.receipt_long_rounded, size: 15),
                label: const Text('Facture', style: TextStyle(fontSize: 12.5)),
                style: OutlinedButton.styleFrom(
                  foregroundColor: isDark ? Colors.white70 : const Color(0xFF475569),
                  side: BorderSide(color: isDark ? Colors.white24 : const Color(0xFFE2E8F0)),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _dateColumn(bool isDark, String label, String value, {Color? valueColor}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 11, color: isDark ? Colors.white38 : Colors.black45)),
        const SizedBox(height: 2),
        Text(
          value,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w600,
            color: valueColor ?? (isDark ? Colors.white70 : Colors.black87),
          ),
        ),
      ],
    );
  }
}

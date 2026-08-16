import 'package:flutter/material.dart';
import '../services/theme_service.dart';

class ManageSubscriptionScreen extends StatelessWidget {
  final Map<String, dynamic> subscriptionData;
  final Map<String, dynamic> statsData;

  const ManageSubscriptionScreen({
    super.key,
    required this.subscriptionData,
    required this.statsData,
  });

  @override
  Widget build(BuildContext context) {
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

          // ── Limites et Utilisation ────────────────────────────────────
          const SizedBox(height: 24),
          _sectionTitle('Limites et Utilisation', isDark),
          const SizedBox(height: 12),
          Builder(builder: (context) {
            final limits = subscriptionData['limits'] as Map? ?? {};
            String getLimit(String key) {
              final lim = limits[key];
              if (lim == null) return '0';
              if (lim == -1) return '∞';
              return lim.toString();
            }

            final cContacts = statsData['totalContacts']?.toString() ?? '0';
            final cCampaigns = statsData['totalCampaigns']?.toString() ?? '0';
            final cBotReplies = statsData['totalBotReplies']?.toString() ?? '0';
            final cDrip = statsData['totalDripCampaigns']?.toString() ?? '0';
            final cBotFlows = statsData['totalBotFlows']?.toString() ?? '0';
            final cAgents = (statsData['agents'] as List?)?.length.toString() ?? statsData['activeTeamMembers']?.toString() ?? '0';

            return GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 1.1,
              children: [
                _usageTile('Contacts', '$cContacts / ${getLimit("contacts")}', Icons.contacts_rounded, const Color(0xFF6C63FF), isDark),
                _usageTile('Campagnes', '$cCampaigns / ${getLimit("campaigns")}', Icons.campaign_rounded, Colors.pinkAccent, isDark),
                _usageTile('Rép. du Bot', '$cBotReplies / ${getLimit("bot_replies")}', Icons.smart_toy_rounded, Colors.green, isDark),
                _usageTile('Drip Camp.', '$cDrip / ${getLimit("drip_campaigns")}', Icons.water_drop_rounded, Colors.teal, isDark),
                _usageTile('Flux du Bot', '$cBotFlows / ${getLimit("bot_flows")}', Icons.account_tree_rounded, Colors.purple, isDark),
                _usageTile('Agents', '$cAgents / ${getLimit("system_users")}', Icons.support_agent_rounded, Colors.blue, isDark),
              ],
            );
          }),
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

  Widget _usageTile(String label, String value, IconData icon, Color color, bool isDark) {
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
        mainAxisAlignment: MainAxisAlignment.center,
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
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: isDark ? Colors.white : Colors.black87,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(fontSize: 11, color: isDark ? Colors.white54 : Colors.black54),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

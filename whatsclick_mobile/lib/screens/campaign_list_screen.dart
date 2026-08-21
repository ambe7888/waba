import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'campaign_info_screen.dart';
import 'create_campaign_screen.dart';

// ─────────────────────────────────────────────────────────────────────────────
// Main merged campaigns screen (used for the bottom nav tab)
// Admins see: list + FAB to create + tap → dashboard
// Agents see:  list in read-only mode
// ─────────────────────────────────────────────────────────────────────────────
class CampaignListScreen extends StatefulWidget {
  const CampaignListScreen({super.key});

  @override
  State<CampaignListScreen> createState() => _CampaignListScreenState();
}

class _CampaignListScreenState extends State<CampaignListScreen> {
  bool _isLoading = true;
  int _roleId = 3;
  bool _isAdmin = false;
  bool _canManageCampaigns = false;
  List<Map<String, dynamic>> _campaigns = [];
  Map<String, dynamic>? _globalStats;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadRoleAndPermissions();
    _fetchStats();
    _fetchCampaigns();
  }

  Future<void> _loadRoleAndPermissions() async {
    final roleId = await ApiService().getUserRoleId();
    final canManageCampaigns = await ApiService().hasPermission('manage_campaigns');
    if (mounted) {
      setState(() {
        _roleId = roleId;
        _isAdmin = _roleId == 2;
        _canManageCampaigns = canManageCampaigns;
      });
    }
  }

  Future<void> _fetchStats() async {
    try {
      final stats = await ApiService().fetchDashboardStats();
      if (mounted && stats != null) {
        setState(() {
          _globalStats = stats;
        });
      }
    } catch (e) {
      // Ignore if stats fail
    }
  }

  Future<void> _fetchCampaigns() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final data = await ApiService().fetchCampaigns();
      if (mounted) {
        setState(() {
          _campaigns = data;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Erreur de chargement des campagnes';
          _isLoading = false;
        });
      }
    }
  }

  // ── Status helpers ──────────────────────────────────────────────────────────
  Color _statusColor(String? s) {
    switch ((s ?? '').toLowerCase()) {
      case 'executed':
        return Colors.green;
      case 'processing':
        return Colors.orange;
      case 'scheduled':
      case 'upcoming':
        return Colors.blue;
      case 'aborted':
      case 'failed':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  IconData _statusIcon(String? s) {
    switch ((s ?? '').toLowerCase()) {
      case 'executed':
        return Icons.check_circle_rounded;
      case 'processing':
        return Icons.sync_rounded;
      case 'scheduled':
      case 'upcoming':
        return Icons.schedule_rounded;
      case 'aborted':
      case 'failed':
        return Icons.cancel_rounded;
      default:
        return Icons.help_outline_rounded;
    }
  }

  // ── Dashboard ───────────────────────────────────────────────────────────────
  void _openDashboard(Map<String, dynamic> campaign) {
    Navigator.push(
      context,
      MaterialPageRoute(
          builder: (_) => CampaignInfoScreen(campaign: campaign)),
    );
  }

  String _statusLabel(String? s) {
    switch ((s ?? '').toLowerCase()) {
      case 'executed':
        return 'Exécutée';
      case 'processing':
        return 'En cours';
      case 'scheduled':
      case 'upcoming':
        return 'Planifiée';
      case 'aborted':
        return 'Annulée';
      case 'failed':
        return 'Échouée';
      default:
        return s ?? 'Inconnu';
    }
  }

  // ── Create wizard ───────────────────────────────────────────────────────────
  void _showCreateWizard() {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => const CreateCampaignScreen()),
    ).then((_) {
      _fetchCampaigns();
    });
  }

  @override
  Widget build(BuildContext context) {
    final onSurface = Theme.of(context).colorScheme.onSurface;
    final surfaceCard = Theme.of(context).colorScheme.surface;

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withAlpha(20),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                'C',
                style: TextStyle(
                    color: ThemeService.primaryColor,
                    fontSize: 20,
                    fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Campagnes',
                    style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.teal)),
                Text('Gérez toutes vos campagnes.',
                    style: TextStyle(
                        fontSize: 12,
                        color: onSurface.withValues(alpha: 0.5))),
              ],
            ),
          ],
        ),
      ),
      floatingActionButton: (_isAdmin || _canManageCampaigns)
          ? FloatingActionButton(
              onPressed: _showCreateWizard,
              backgroundColor: ThemeService.primaryColor,
              child: const Icon(Icons.add, color: Colors.white, size: 28),
            )
          : null,
      body: _isLoading
          ? Center(
              child:
                  CircularProgressIndicator(color: ThemeService.primaryColor))
          : _error != null
              ? _buildError(onSurface)
              : RefreshIndicator(
                  onRefresh: () async {
                    await _fetchStats();
                    await _fetchCampaigns();
                  },
                  color: ThemeService.primaryColor,
                  child: CustomScrollView(
                    slivers: [
                      SliverToBoxAdapter(
                        child: _buildHeaderStats(surfaceCard, onSurface),
                      ),
                      SliverToBoxAdapter(
                        child: _buildSearchBar(surfaceCard, onSurface),
                      ),
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 8),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Campagnes',
                                  style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.teal)),
                              Row(
                                children: [
                                  const Text('Plus récentes',
                                      style: TextStyle(
                                          color: Colors.teal,
                                          fontWeight: FontWeight.w600)),
                                  const SizedBox(width: 4),
                                  const Icon(Icons.swap_vert_rounded,
                                      color: Colors.teal, size: 18),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                      if (_campaigns.isEmpty)
                        SliverFillRemaining(
                          child: _buildEmpty(onSurface),
                        )
                      else
                        SliverList(
                          delegate: SliverChildBuilderDelegate(
                            (_, i) => _buildCard(
                                _campaigns[i], surfaceCard, onSurface),
                            childCount: _campaigns.length,
                          ),
                        ),
                      SliverToBoxAdapter(
                        child: SizedBox(height: _isAdmin ? 90 : 20),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildHeaderStats(Color surfaceCard, Color onSurface) {
    if (_globalStats == null) return const SizedBox.shrink();
    
    final launched = _globalStats?['totalCampaigns'] ?? 0;
    final sent = _globalStats?['totalMessagesSent'] ?? 0;
    final delivered = _globalStats?['totalDeliveredMessages'] ?? 0;
    final read = _globalStats?['totalMessagesRead'] ?? 0;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Wrap(
        spacing: 10,
        runSpacing: 10,
        children: [
          _statCardUI(
              title: 'CAMPAGNES\nLANCÉES',
              value: '$launched',
              subtitle: 'Total des campagnes',
              icon: Icons.campaign_rounded,
              color: Colors.orange,
              surfaceCard: surfaceCard,
              onSurface: onSurface),
          _statCardUI(
              title: 'MESSAGES\nENVOYÉS',
              value: '$sent',
              subtitle: 'Total sortant',
              icon: Icons.send_outlined,
              color: Colors.blue,
              surfaceCard: surfaceCard,
              onSurface: onSurface),
          _statCardUI(
              title: 'MESSAGES\nLIVRÉS',
              value: '$delivered',
              subtitle: 'Livrés avec succès',
              icon: Icons.check_circle_outline,
              color: Colors.green,
              surfaceCard: surfaceCard,
              onSurface: onSurface),
          _statCardUI(
              title: 'MESSAGES\nLUS',
              value: '$read',
              subtitle: 'Lus par les destinataires',
              icon: Icons.visibility_outlined,
              color: Colors.purple,
              surfaceCard: surfaceCard,
              onSurface: onSurface),
        ],
      ),
    );
  }

  Widget _statCardUI({
    required String title,
    required String value,
    required String subtitle,
    required IconData icon,
    required Color color,
    required Color surfaceCard,
    required Color onSurface,
  }) {
    // We want 2 items per row, so width is (screen width - padding) / 2
    return LayoutBuilder(builder: (context, constraints) {
      return Container(
        width: (MediaQuery.of(context).size.width - 42) / 2,
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: surfaceCard,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withAlpha(50)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(5),
              blurRadius: 10,
              offset: const Offset(0, 4),
            )
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: onSurface.withValues(alpha: 0.6),
                        height: 1.2),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              value,
              style: TextStyle(
                  fontSize: 22, fontWeight: FontWeight.bold, color: color),
            ),
            const SizedBox(height: 4),
            Text(
              subtitle,
              style: TextStyle(
                  fontSize: 10, color: onSurface.withValues(alpha: 0.5)),
            ),
          ],
        ),
      );
    });
  }

  Widget _buildSearchBar(Color surfaceCard, Color onSurface) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Expanded(
            child: Container(
              height: 45,
              decoration: BoxDecoration(
                color: onSurface.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(8),
              ),
              child: TextField(
                decoration: InputDecoration(
                  hintText: 'Rechercher une campagne...',
                  hintStyle:
                      TextStyle(color: onSurface.withValues(alpha: 0.4)),
                  prefixIcon: Icon(Icons.search,
                      color: onSurface.withValues(alpha: 0.4)),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Container(
            height: 45,
            width: 45,
            decoration: BoxDecoration(
              color: onSurface.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(Icons.filter_alt_outlined,
                color: onSurface.withValues(alpha: 0.6)),
          ),
        ],
      ),
    );
  }

  Widget _buildError(Color onSurface) => Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline_rounded,
                size: 48, color: onSurface.withValues(alpha: 0.3)),
            const SizedBox(height: 12),
            Text(_error!,
                style: TextStyle(color: onSurface.withValues(alpha: 0.5))),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _fetchCampaigns,
              style: ElevatedButton.styleFrom(
                  backgroundColor: ThemeService.primaryColor),
              child: const Text('Réessayer',
                  style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      );

  Widget _buildEmpty(Color onSurface) => Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.campaign_outlined,
                size: 64, color: onSurface.withValues(alpha: 0.2)),
            const SizedBox(height: 16),
            Text('Aucune campagne trouvée',
                style: TextStyle(
                    fontSize: 16, color: onSurface.withValues(alpha: 0.4))),
            if (_isAdmin) ...[
              const SizedBox(height: 24),
              ElevatedButton.icon(
                icon: const Icon(Icons.add_rounded),
                label: const Text('Créer une campagne'),
                onPressed: _showCreateWizard,
              ),
            ],
          ],
        ),
      );

  Widget _buildCard(
      Map<String, dynamic> c, Color surfaceCard, Color onSurface) {
    final title = c['title'] ?? c['campaign_name'] ?? 'Sans titre';
    final scheduledAt = c['scheduled_at']?.toString() ?? '';
    
    // Convert status to styling
    Color iconBg = Colors.teal.withAlpha(20);
    Color iconColor = Colors.teal;
    IconData leadingIcon = Icons.send_outlined;
    
    if (c['status'] == 'failed' || c['status'] == 'aborted') {
      iconBg = Colors.orange.withAlpha(20);
      iconColor = Colors.orange;
    } else if (c['status'] == 'executed') {
      iconBg = Colors.blue.withAlpha(20);
      iconColor = Colors.blue;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12, left: 16, right: 16),
      decoration: BoxDecoration(
        color: surfaceCard,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: onSurface.withValues(alpha: 0.1)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(2),
            blurRadius: 8,
            offset: const Offset(0, 2),
          )
        ],
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        onTap: () => _openDashboard(c), // Now everyone can tap (we have details view)
        leading: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: iconBg,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(leadingIcon, color: iconColor),
        ),
        title: Text(
          title,
          style: const TextStyle(
              fontWeight: FontWeight.w700, fontSize: 15),
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 4.0),
          child: Text(
            scheduledAt,
            style: TextStyle(
                fontSize: 12, color: onSurface.withValues(alpha: 0.5)),
          ),
        ),
        trailing: Icon(Icons.more_vert, color: onSurface.withValues(alpha: 0.5)),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

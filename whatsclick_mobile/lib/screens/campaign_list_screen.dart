import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
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
  final _searchController = TextEditingController();
  String? _statusFilter;
  bool _sortNewestFirst = true;
  bool _showingArchived = false;

  List<Map<String, dynamic>> get _filteredCampaigns {
    final query = _searchController.text.trim().toLowerCase();
    final list = _campaigns.where((c) {
      if (_statusFilter != null &&
          (c['status']?.toString().toLowerCase() ?? '') != _statusFilter) {
        return false;
      }
      if (query.isEmpty) return true;
      final title = (c['title'] ?? c['campaign_name'] ?? '').toString().toLowerCase();
      final templateName = (c['template_name'] ?? '').toString().toLowerCase();
      return title.contains(query) || templateName.contains(query);
    }).toList();

    DateTime? parseDate(Map<String, dynamic> c) =>
        DateTime.tryParse((c['updated_at'] ?? c['created_at'] ?? '').toString());

    final indexed = list.asMap().entries.toList();
    indexed.sort((a, b) {
      final da = parseDate(a.value);
      final db = parseDate(b.value);
      int cmp;
      if (da == null && db == null) {
        cmp = 0;
      } else if (da == null) {
        cmp = 1;
      } else if (db == null) {
        cmp = -1;
      } else {
        cmp = _sortNewestFirst ? db.compareTo(da) : da.compareTo(db);
      }
      if (cmp != 0) return cmp;
      return a.key.compareTo(b.key);
    });
    return indexed.map((e) => e.value).toList();
  }

  @override
  void initState() {
    super.initState();
    _loadRoleAndPermissions();
    _fetchStats();
    _fetchCampaigns();
    _searchController.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
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

  Future<void> _archiveCampaign(Map<String, dynamic> campaign) async {
    final uid = campaign['_uid']?.toString() ?? '';
    if (uid.isEmpty) return;
    final actionLabel = _showingArchived ? 'Désarchiver' : 'Archiver';
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('$actionLabel la campagne'),
        content: Text(_showingArchived
            ? '$actionLabel "${campaign['title'] ?? 'cette campagne'}" ? Elle réapparaîtra dans la liste principale.'
            : '$actionLabel "${campaign['title'] ?? 'cette campagne'}" ? Elle ne sera plus affichée dans la liste.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Annuler')),
          ElevatedButton(onPressed: () => Navigator.pop(context, true), child: Text(actionLabel)),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    final success = await ApiService().archiveCampaign(uid);
    if (!mounted) return;
    if (success) {
      setState(() {
        _campaigns.removeWhere((c) => c['_uid'] == uid);
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_showingArchived ? 'Campagne désarchivée.' : 'Campagne archivée.'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Erreur lors de l'archivage."), backgroundColor: Colors.red),
      );
    }
  }

  Future<void> _fetchCampaigns() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final data = await ApiService().fetchCampaigns(showArchived: _showingArchived);
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
                Text(_showingArchived ? 'Campagnes archivées' : 'Campagnes',
                    style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.teal)),
                Text(
                    _showingArchived
                        ? 'Campagnes mises de côté.'
                        : 'Gérez toutes vos campagnes.',
                    style: TextStyle(
                        fontSize: 12,
                        color: onSurface.withValues(alpha: 0.5))),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: Icon(
              _showingArchived ? Icons.unarchive_outlined : Icons.archive_outlined,
              color: onSurface.withValues(alpha: 0.7),
            ),
            tooltip: _showingArchived ? 'Voir les campagnes actives' : 'Voir les campagnes archivées',
            onPressed: () {
              setState(() => _showingArchived = !_showingArchived);
              _fetchCampaigns();
            },
          ),
        ],
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
                              InkWell(
                                borderRadius: BorderRadius.circular(8),
                                onTap: () => setState(
                                    () => _sortNewestFirst = !_sortNewestFirst),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 4, vertical: 4),
                                  child: Row(
                                    children: [
                                      Text(
                                          _sortNewestFirst
                                              ? 'Plus récentes'
                                              : 'Plus anciennes',
                                          style: const TextStyle(
                                              color: Colors.teal,
                                              fontWeight: FontWeight.w600)),
                                      const SizedBox(width: 4),
                                      Icon(
                                          _sortNewestFirst
                                              ? Icons.arrow_downward_rounded
                                              : Icons.arrow_upward_rounded,
                                          color: Colors.teal,
                                          size: 18),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      if (_filteredCampaigns.isEmpty)
                        SliverFillRemaining(
                          child: _buildEmpty(onSurface),
                        )
                      else
                        SliverList(
                          delegate: SliverChildBuilderDelegate(
                            (_, i) => _buildCard(
                                _filteredCampaigns[i], surfaceCard, onSurface),
                            childCount: _filteredCampaigns.length,
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
              decoration: BoxDecoration(
                color: surfaceCard,
                borderRadius: BorderRadius.circular(30),
                border: Border.all(
                    color: onSurface.withValues(alpha: 0.12), width: 1),
              ),
              clipBehavior: Clip.hardEdge,
              child: TextField(
                controller: _searchController,
                style: TextStyle(color: onSurface, fontSize: 14),
                decoration: InputDecoration(
                  hintText: 'Rechercher une campagne...',
                  hintStyle:
                      TextStyle(color: onSurface.withValues(alpha: 0.31), fontSize: 14),
                  prefixIcon: Icon(Icons.search_rounded,
                      color: onSurface.withValues(alpha: 0.31), size: 20),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: Icon(Icons.clear_rounded,
                              color: onSurface.withValues(alpha: 0.31), size: 18),
                          onPressed: () => _searchController.clear(),
                        )
                      : null,
                  border: InputBorder.none,
                  enabledBorder: InputBorder.none,
                  focusedBorder: InputBorder.none,
                  contentPadding:
                      const EdgeInsets.symmetric(vertical: 12, horizontal: 0),
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          InkWell(
            onTap: _showCampaignFilterSheet,
            borderRadius: BorderRadius.circular(14),
            child: Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  padding: const EdgeInsets.all(13),
                  decoration: BoxDecoration(
                    color: surfaceCard,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                        color: onSurface.withValues(alpha: 0.12), width: 1),
                  ),
                  child: Icon(Icons.filter_list_rounded,
                      size: 20, color: onSurface.withValues(alpha: 0.7)),
                ),
                if (_statusFilter != null)
                  Positioned(
                    top: -2,
                    right: -2,
                    child: Container(
                      width: 10,
                      height: 10,
                      decoration: const BoxDecoration(
                        color: Colors.teal,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showCampaignFilterSheet() {
    const statuses = ['executed', 'processing', 'scheduled', 'aborted', 'failed'];
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final surfaceCard = Theme.of(context).scaffoldBackgroundColor;
            final onSurface = Theme.of(context).colorScheme.onSurface;
            return Container(
              padding: const EdgeInsets.only(bottom: 24),
              decoration: BoxDecoration(
                color: surfaceCard,
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(20),
                  topRight: Radius.circular(20),
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Filtrer les campagnes',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        IconButton(
                          icon: const Icon(Icons.close_rounded),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildFilterStatusTile(
                          label: 'Toutes les campagnes',
                          icon: Icons.all_inbox_rounded,
                          color: Colors.grey,
                          selected: _statusFilter == null,
                          onSurface: onSurface,
                          onTap: () {
                            setModalState(() {});
                            setState(() => _statusFilter = null);
                            Navigator.pop(context);
                          },
                        ),
                        ...statuses.map((s) => _buildFilterStatusTile(
                              label: _statusLabel(s),
                              icon: _statusIcon(s),
                              color: _statusColor(s),
                              selected: _statusFilter == s,
                              onSurface: onSurface,
                              onTap: () {
                                setModalState(() {});
                                setState(() => _statusFilter = s);
                                Navigator.pop(context);
                              },
                            )),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildFilterStatusTile({
    required String label,
    required IconData icon,
    required Color color,
    required bool selected,
    required Color onSurface,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: selected ? color.withValues(alpha: 0.1) : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: selected ? color : onSurface.withValues(alpha: 0.12),
            width: selected ? 1.5 : 1,
          ),
        ),
        child: Row(
          children: [
            Icon(icon, size: 18, color: color),
            const SizedBox(width: 10),
            Expanded(
              child: Text(label,
                  style: TextStyle(
                      fontSize: 14,
                      fontWeight: selected ? FontWeight.w600 : FontWeight.normal)),
            ),
            if (selected) Icon(Icons.check_circle_rounded, size: 18, color: color),
          ],
        ),
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
            Icon(_showingArchived ? Icons.archive_outlined : Icons.campaign_outlined,
                size: 64, color: onSurface.withValues(alpha: 0.2)),
            const SizedBox(height: 16),
            Text(_showingArchived ? 'Aucune campagne archivée' : 'Aucune campagne trouvée',
                style: TextStyle(
                    fontSize: 16, color: onSurface.withValues(alpha: 0.4))),
            if (_isAdmin && !_showingArchived) ...[
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

  /// Was displayed as the raw ISO string (e.g. "2026-08-21T13:27:22.000000Z")
  /// with no parsing at all — hence the "0000000Z" the date column showed.
  String _formatDate(dynamic raw) {
    if (raw == null) return '';
    final parsed = DateTime.tryParse(raw.toString());
    if (parsed == null) return '';
    return DateFormat('dd/MM/yyyy à HH:mm').format(parsed.toLocal());
  }

  Widget _buildCard(
      Map<String, dynamic> c, Color surfaceCard, Color onSurface) {
    final title = c['title'] ?? c['campaign_name'] ?? 'Sans titre';
    final scheduledAt = _formatDate(c['scheduled_at']);
    
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
        trailing: PopupMenuButton<String>(
          icon: Icon(Icons.more_vert, color: onSurface.withValues(alpha: 0.5)),
          onSelected: (value) {
            if (value == 'details') {
              _openDashboard(c);
            } else if (value == 'archive') {
              _archiveCampaign(c);
            }
          },
          itemBuilder: (context) => [
            const PopupMenuItem(
              value: 'details',
              child: Row(
                children: [
                  Icon(Icons.visibility_outlined, size: 18),
                  SizedBox(width: 10),
                  Text('Voir les détails'),
                ],
              ),
            ),
            PopupMenuItem(
              value: 'archive',
              child: Row(
                children: [
                  Icon(_showingArchived ? Icons.unarchive_outlined : Icons.archive_outlined, size: 18),
                  const SizedBox(width: 10),
                  Text(_showingArchived ? 'Désarchiver' : 'Archiver'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

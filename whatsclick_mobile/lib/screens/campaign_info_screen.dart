import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class CampaignInfoScreen extends StatefulWidget {
  final Map<String, dynamic> campaign;
  const CampaignInfoScreen({super.key, required this.campaign});

  @override
  State<CampaignInfoScreen> createState() => _CampaignInfoScreenState();
}

class _CampaignInfoScreenState extends State<CampaignInfoScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  Map<String, dynamic>? _stats;
  List<Map<String, dynamic>> _contacts = [];
  bool _isLoadingStats = true;
  bool _isLoadingContacts = true;
  Timer? _pollTimer;
  final _searchController = TextEditingController();

  // Message log filter — see ApiService.fetchCampaignContacts for the
  // logType/logStatus contract.
  String _logType = 'executed';
  String _logStatus = 'all';

  List<Map<String, dynamic>> get _filteredContacts {
    final query = _searchController.text.trim().toLowerCase();
    if (query.isEmpty) return _contacts;
    return _contacts.where((c) {
      final name = (c['full_name'] ?? c['first_name'] ?? '').toString().toLowerCase();
      final phone = (c['phone_with_country_code'] ?? c['contact_wa_id'] ?? c['phone_number'] ?? '').toString();
      return name.contains(query) || phone.contains(query);
    }).toList();
  }

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadStats();
    _loadContacts();
    _searchController.addListener(() => setState(() {}));
    // "Mise à jour instantanée" — poll for fresh stats/log data while the
    // screen is open, since campaign delivery status changes asynchronously
    // as WhatsApp sends webhook status updates.
    _pollTimer = Timer.periodic(const Duration(seconds: 12), (_) {
      _loadStats(silent: true);
      _loadContacts(silent: true);
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _pollTimer?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadStats({bool silent = false}) async {
    final uid = widget.campaign['_uid'] ?? widget.campaign['uid'] ?? '';
    if (uid.isEmpty) {
      if (mounted) setState(() => _isLoadingStats = false);
      return;
    }
    if (!silent && mounted) setState(() => _isLoadingStats = true);
    final data = await ApiService().fetchCampaignDashboard(uid);
    if (mounted) {
      setState(() {
        _stats = data;
        _isLoadingStats = false;
      });
    }
  }

  Future<void> _loadContacts({bool silent = false}) async {
    final uid = widget.campaign['_uid'] ?? widget.campaign['uid'] ?? '';
    if (uid.isEmpty) {
      if (mounted) setState(() => _isLoadingContacts = false);
      return;
    }
    if (!silent && mounted) setState(() => _isLoadingContacts = true);
    final data = await ApiService()
        .fetchCampaignContacts(uid, logType: _logType, logStatus: _logStatus);
    if (mounted) {
      setState(() {
        _contacts = data;
        _isLoadingContacts = false;
      });
    }
  }

  void _selectFilter(String logType, String logStatus) {
    if (_logType == logType && _logStatus == logStatus) return;
    setState(() {
      _logType = logType;
      _logStatus = logStatus;
    });
    _loadContacts();
  }

  Color _statusColor(String? s) {
    switch ((s ?? '').toLowerCase()) {
      case 'executed':
      case 'delivered':
      case 'processed':
      case '4': // queue status code: Processed
        return Colors.green;
      case 'read':
      case 'played':
        return Colors.purple;
      case 'processing':
      case '1': // queue status code: In Queue
      case '3': // queue status code: Processing
      case '6': // queue status code: Processed but Response Awaited
        return Colors.orange;
      case 'scheduled':
      case 'upcoming':
      case 'sent':
      case 'initialize':
        return Colors.blue;
      case 'aborted':
      case 'failed':
      case '2': // queue status code: Failed
      case '7': // queue status code: Aborted
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final onSurface = Theme.of(context).colorScheme.onSurface;
    final surfaceCard = Theme.of(context).colorScheme.surface;
    final isDark = ThemeService().isDark;

    return Scaffold(
      // This screen is pushed as its own standalone route (not nested
      // inside main_layout_screen's tab host), so a transparent
      // background here has nothing behind it to show through —
      // it rendered as a solid black screen.
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: onSurface),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Informations de la campagne',
          style: TextStyle(
              fontSize: 18, fontWeight: FontWeight.bold, color: onSurface),
        ),
        bottom: TabBar(
          controller: _tabController,
          labelColor: ThemeService.primaryColor,
          unselectedLabelColor: onSurface.withValues(alpha: 0.5),
          indicatorColor: ThemeService.primaryColor,
          tabs: const [
            Tab(text: 'Aperçu'),
            Tab(text: 'Messages'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildOverviewTab(surfaceCard, onSurface),
          _buildMessagesTab(surfaceCard, onSurface),
        ],
      ),
    );
  }

  // ── OVERVIEW TAB ────────────────────────────────────────────────────────────
  Widget _buildOverviewTab(Color surfaceCard, Color onSurface) {
    if (_isLoadingStats) {
      return Center(
          child: CircularProgressIndicator(color: ThemeService.primaryColor));
    }

    final sent = _stats?['total_message_logs'] ?? 0;
    final delivered = _stats?['successful_deliveries'] ?? 0;
    final read = _stats?['read_deliveries'] ?? 0;
    final failed = _stats?['failed_deliveries'] ?? 0;
    
    final name = widget.campaign['title'] ?? 'N/A';
    final template = widget.campaign['template_name'] ?? 'N/A';
    final lang = widget.campaign['template_language'] ?? 'N/A';
    final status = widget.campaign['status'] ?? 'N/A';
    final date = widget.campaign['created_at'] ?? 'N/A';

    return RefreshIndicator(
      color: ThemeService.primaryColor,
      onRefresh: () => _loadStats(),
      child: SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Delivery Mastery Section
          Text('Performance de livraison',
              style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: onSurface)),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: surfaceCard,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: onSurface.withValues(alpha: 0.1)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Progression de livraison',
                        style: TextStyle(
                            fontWeight: FontWeight.w600,
                            color: onSurface.withValues(alpha: 0.7))),
                    Text('${sent > 0 ? (delivered / sent * 100).toStringAsFixed(1) : 0}%',
                        style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: ThemeService.primaryColor)),
                  ],
                ),
                const SizedBox(height: 8),
                LinearProgressIndicator(
                  value: sent > 0 ? (delivered / sent) : 0,
                  backgroundColor: onSurface.withValues(alpha: 0.1),
                  valueColor: AlwaysStoppedAnimation(ThemeService.primaryColor),
                  minHeight: 8,
                  borderRadius: BorderRadius.circular(4),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          
          // Performance Funnel
          Text('Entonnoir de performance',
              style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: onSurface)),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              _buildFunnelCard('ENVOYÉS', '$sent', Colors.blue, surfaceCard, onSurface),
              _buildFunnelCard('LIVRÉS', '$delivered', Colors.green, surfaceCard, onSurface),
              _buildFunnelCard('LUS', '$read', Colors.purple, surfaceCard, onSurface),
              _buildFunnelCard('ÉCHOUÉS', '$failed', Colors.red, surfaceCard, onSurface),
            ],
          ),
          const SizedBox(height: 24),

          // Configuration
          Text('Configuration',
              style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: onSurface)),
          const SizedBox(height: 12),
          Container(
            decoration: BoxDecoration(
              color: surfaceCard,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: onSurface.withValues(alpha: 0.1)),
            ),
            child: Column(
              children: [
                _buildConfigRow('Nom', name, onSurface),
                _buildConfigRow('Langue', lang, onSurface),
                _buildConfigRow('Date', date.toString().split(' ')[0], onSurface),
                _buildConfigRow('Modèle', template, onSurface),
                _buildConfigRow('Statut', status, onSurface, isStatus: true),
              ],
            ),
          ),
        ],
      ),
      ),
    );
  }

  Widget _buildFunnelCard(String title, String value, Color color, Color surfaceCard, Color onSurface) {
    return LayoutBuilder(builder: (context, constraints) {
      return Container(
        width: (MediaQuery.of(context).size.width - 44) / 2,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: surfaceCard,
          borderRadius: BorderRadius.circular(12),
          border: Border(
            left: BorderSide(color: color, width: 4),
            top: BorderSide(color: color.withAlpha(50)),
            right: BorderSide(color: color.withAlpha(50)),
            bottom: BorderSide(color: color.withAlpha(50)),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title,
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: onSurface.withValues(alpha: 0.5))),
            const SizedBox(height: 4),
            Text(value,
                style: TextStyle(
                    fontSize: 20, fontWeight: FontWeight.bold, color: color)),
          ],
        ),
      );
    });
  }

  Widget _buildConfigRow(String label, String value, Color onSurface, {bool isStatus = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: onSurface.withValues(alpha: 0.6))),
          isStatus
              ? Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _statusColor(value).withAlpha(30),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(value.toUpperCase(),
                      style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: _statusColor(value))),
                )
              : Text(value,
                  style: TextStyle(
                      fontWeight: FontWeight.w600, color: onSurface)),
        ],
      ),
    );
  }

  String _messageStatusLabel(String status) {
    switch (status.toLowerCase()) {
      case 'delivered':
        return 'Livré';
      case 'read':
        return 'Lu';
      case 'failed':
        return 'Échoué';
      case 'sent':
        return 'Envoyé';
      case 'initialize':
        return 'Initialisation';
      case 'played':
        return 'Écouté';
      default:
        return status;
    }
  }

  // ── MESSAGES TAB ────────────────────────────────────────────────────────────
  Widget _buildMessagesTab(Color surfaceCard, Color onSurface) {
    return Column(
      children: [
        // Search bar
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Container(
            height: 45,
            decoration: BoxDecoration(
              color: onSurface.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(8),
            ),
            child: TextField(
              controller: _searchController,
              style: TextStyle(color: onSurface, fontSize: 14),
              decoration: InputDecoration(
                hintText: 'Rechercher un contact...',
                hintStyle: TextStyle(color: onSurface.withValues(alpha: 0.4)),
                prefixIcon: Icon(Icons.search, color: onSurface.withValues(alpha: 0.4)),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: Icon(Icons.clear, size: 18, color: onSurface.withValues(alpha: 0.4)),
                        onPressed: () => _searchController.clear(),
                      )
                    : null,
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ),
        // Status filter chips
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _buildLogFilterChip('Tous', 'executed', 'all', onSurface),
                const SizedBox(width: 8),
                _buildLogFilterChip('En file d\'attente', 'queue', 'all', onSurface),
                const SizedBox(width: 8),
                _buildLogFilterChip('Livrés', 'executed', 'delivered', onSurface),
                const SizedBox(width: 8),
                _buildLogFilterChip('Lus', 'executed', 'read', onSurface),
                const SizedBox(width: 8),
                _buildLogFilterChip('Échoués', 'executed', 'failed', onSurface),
              ],
            ),
          ),
        ),
        const SizedBox(height: 8),
        // List
        Expanded(
          child: _isLoadingContacts
              ? Center(child: CircularProgressIndicator(color: ThemeService.primaryColor))
              : RefreshIndicator(
                  color: ThemeService.primaryColor,
                  onRefresh: () => _loadContacts(),
                  child: _filteredContacts.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [
                            SizedBox(
                              height: 300,
                              child: Center(
                                child: Text('Aucun contact trouvé',
                                    style: TextStyle(color: onSurface.withValues(alpha: 0.5))),
                              ),
                            ),
                          ],
                        )
                      : ListView.builder(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: _filteredContacts.length,
                          itemBuilder: (context, index) {
                            final contact = _filteredContacts[index];
                            final name = contact['full_name'] ?? contact['first_name'] ?? 'Inconnu';
                            final phone = contact['phone_with_country_code'] ??
                                contact['contact_wa_id'] ??
                                contact['phone_number'] ??
                                'N/A';
                            final statusCode = (contact['status'] ?? '').toString();
                            final displayStatus = _logType == 'queue'
                                ? (contact['formatted_status'] ?? statusCode).toString()
                                : _messageStatusLabel(statusCode.isEmpty ? 'Envoyé' : statusCode);
                            final date = contact['updated_at'] ?? contact['messaged_at'] ?? '';

                            final sColor = _statusColor(statusCode);

                            return Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              decoration: BoxDecoration(
                                color: surfaceCard,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: onSurface.withValues(alpha: 0.05)),
                              ),
                              child: ListTile(
                                leading: CircleAvatar(
                                  backgroundColor: ThemeService.primaryColor.withAlpha(20),
                                  child: Icon(Icons.person_outline, color: ThemeService.primaryColor),
                                ),
                                title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
                                subtitle: Text(phone, style: TextStyle(color: onSurface.withValues(alpha: 0.5), fontSize: 12)),
                                trailing: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: sColor.withAlpha(20),
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(displayStatus.toUpperCase(),
                                          style: TextStyle(fontSize: 10, color: sColor, fontWeight: FontWeight.bold)),
                                    ),
                                    if (date.toString().isNotEmpty) ...[
                                      const SizedBox(height: 4),
                                      Text(date.toString().split(' ')[0],
                                          style: TextStyle(fontSize: 10, color: onSurface.withValues(alpha: 0.4))),
                                    ]
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                ),
        ),
      ],
    );
  }

  Widget _buildLogFilterChip(String label, String logType, String logStatus, Color onSurface) {
    final selected = _logType == logType && _logStatus == logStatus;
    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: () => _selectFilter(logType, logStatus),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? ThemeService.primaryColor.withValues(alpha: 0.15) : Colors.transparent,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: selected ? ThemeService.primaryColor : onSurface.withValues(alpha: 0.15)),
        ),
        child: Text(label,
            style: TextStyle(
                fontSize: 12,
                fontWeight: selected ? FontWeight.bold : FontWeight.normal,
                color: selected ? ThemeService.primaryColor : onSurface.withValues(alpha: 0.6))),
      ),
    );
  }
}

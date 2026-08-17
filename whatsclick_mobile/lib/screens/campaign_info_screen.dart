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

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadStats();
    _loadContacts();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadStats() async {
    final uid = widget.campaign['_uid'] ?? widget.campaign['uid'] ?? '';
    if (uid.isEmpty) {
      if(mounted) setState(() => _isLoadingStats = false);
      return;
    }
    if(mounted) setState(() => _isLoadingStats = true);
    final data = await ApiService().fetchCampaignDashboard(uid);
    if (mounted) {
      setState(() {
        _stats = data;
        _isLoadingStats = false;
      });
    }
  }

  Future<void> _loadContacts() async {
    final uid = widget.campaign['_uid'] ?? widget.campaign['uid'] ?? '';
    if (uid.isEmpty) {
      if (mounted) setState(() => _isLoadingContacts = false);
      return;
    }
    if (mounted) setState(() => _isLoadingContacts = true);
    // Fetch queue contacts
    final data = await ApiService().fetchCampaignContacts(uid, logType: 'queue');
    if (mounted) {
      setState(() {
        _contacts = data;
        _isLoadingContacts = false;
      });
    }
  }

  Color _statusColor(String? s) {
    switch ((s ?? '').toLowerCase()) {
      case 'executed':
      case 'delivered':
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

  @override
  Widget build(BuildContext context) {
    final onSurface = Theme.of(context).colorScheme.onSurface;
    final surfaceCard = Theme.of(context).colorScheme.surface;

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: onSurface),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Campaign Information',
          style: TextStyle(
              fontSize: 18, fontWeight: FontWeight.bold, color: onSurface),
        ),
        bottom: TabBar(
          controller: _tabController,
          labelColor: ThemeService.primaryColor,
          unselectedLabelColor: onSurface.withValues(alpha: 0.5),
          indicatorColor: ThemeService.primaryColor,
          tabs: const [
            Tab(text: 'Overview'),
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

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Delivery Mastery Section
          Text('Delivery Mastery',
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
                    Text('Delivery Progress',
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
          Text('Performance Funnel',
              style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: onSurface)),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              _buildFunnelCard('SENT', '$sent', Colors.blue, surfaceCard, onSurface),
              _buildFunnelCard('DELIVERED', '$delivered', Colors.green, surfaceCard, onSurface),
              _buildFunnelCard('READ', '$read', Colors.purple, surfaceCard, onSurface),
              _buildFunnelCard('FAILED', '$failed', Colors.red, surfaceCard, onSurface),
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
                _buildConfigRow('Name', name, onSurface),
                _buildConfigRow('Language', lang, onSurface),
                _buildConfigRow('Date', date.toString().split(' ')[0], onSurface),
                _buildConfigRow('Template', template, onSurface),
                _buildConfigRow('Status', status, onSurface, isStatus: true),
              ],
            ),
          ),
        ],
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

  // ── MESSAGES TAB ────────────────────────────────────────────────────────────
  Widget _buildMessagesTab(Color surfaceCard, Color onSurface) {
    if (_isLoadingContacts) {
      return Center(
          child: CircularProgressIndicator(color: ThemeService.primaryColor));
    }

    return Column(
      children: [
        // Search bar
        Padding(
          padding: const EdgeInsets.all(16),
          child: Container(
            height: 45,
            decoration: BoxDecoration(
              color: onSurface.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(8),
            ),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Search contacts...',
                hintStyle: TextStyle(color: onSurface.withValues(alpha: 0.4)),
                prefixIcon: Icon(Icons.search, color: onSurface.withValues(alpha: 0.4)),
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ),
        // List
        Expanded(
          child: _contacts.isEmpty
              ? Center(
                  child: Text('Aucun contact trouvÃ©',
                      style: TextStyle(color: onSurface.withValues(alpha: 0.5))))
              : ListView.builder(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: _contacts.length,
                  itemBuilder: (context, index) {
                    final contact = _contacts[index];
                    final name = contact['full_name'] ?? contact['first_name'] ?? 'Inconnu';
                    final phone = contact['phone_number'] ?? 'N/A';
                    final status = contact['status'] ?? 'Sent'; // Default mock status if none
                    final date = contact['updated_at'] ?? '';
                    
                    final sColor = _statusColor(status);

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
                              child: Text(status.toString().toUpperCase(), style: TextStyle(fontSize: 10, color: sColor, fontWeight: FontWeight.bold)),
                            ),
                            if (date.toString().isNotEmpty) ...[
                               const SizedBox(height: 4),
                               Text(date.toString().split(' ')[0], style: TextStyle(fontSize: 10, color: onSurface.withValues(alpha: 0.4))),
                            ]
                          ],
                        ),
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }
}

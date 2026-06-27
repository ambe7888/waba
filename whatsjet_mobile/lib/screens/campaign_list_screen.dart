import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class CampaignListScreen extends StatefulWidget {
  const CampaignListScreen({super.key});

  @override
  State<CampaignListScreen> createState() => _CampaignListScreenState();
}

class _CampaignListScreenState extends State<CampaignListScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _campaigns = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchCampaigns();
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

  Color _statusColor(String? status) {
    switch ((status ?? '').toLowerCase()) {
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

  IconData _statusIcon(String? status) {
    switch ((status ?? '').toLowerCase()) {
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

  String _statusLabel(String? status) {
    switch ((status ?? '').toLowerCase()) {
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
        return status ?? 'Inconnu';
    }
  }

  @override
  Widget build(BuildContext context) {
    final onSurface = Theme.of(context).colorScheme.onSurface;
    final surfaceCard = Theme.of(context).colorScheme.surface;

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withAlpha(30),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(Icons.campaign_rounded, color: ThemeService.primaryColor, size: 20),
            ),
            SizedBox(width: 10),
            Text(
              'Campagnes',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded, size: 22),
            onPressed: _fetchCampaigns,
          ),
        ],
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: ThemeService.primaryColor))
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error_outline_rounded, size: 48, color: onSurface.withOpacity(0.3)),
                      SizedBox(height: 12),
                      Text(_error!, style: TextStyle(color: onSurface.withOpacity(0.5))),
                      SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchCampaigns,
                        style: ElevatedButton.styleFrom(backgroundColor: ThemeService.primaryColor),
                        child: Text('Réessayer', style: TextStyle(color: Colors.white)),
                      ),
                    ],
                  ),
                )
              : _campaigns.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.campaign_outlined, size: 64, color: onSurface.withOpacity(0.2)),
                          SizedBox(height: 16),
                          Text(
                            'Aucune campagne trouvée',
                            style: TextStyle(fontSize: 16, color: onSurface.withOpacity(0.4)),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _fetchCampaigns,
                      color: ThemeService.primaryColor,
                      child: ListView.builder(
                        padding: EdgeInsets.all(12),
                        itemCount: _campaigns.length,
                        itemBuilder: (context, index) {
                          final c = _campaigns[index];
                          final title = c['title'] ?? c['campaign_name'] ?? 'Sans titre';
                          final status = c['status']?.toString();
                          final scheduledAt = c['scheduled_at']?.toString();
                          final total = c['total_message_logs'] ?? c['total'] ?? '';
                          final statusColor = _statusColor(status);

                          return Container(
                            margin: EdgeInsets.only(bottom: 10),
                            decoration: BoxDecoration(
                              color: surfaceCard,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: onSurface.withOpacity(0.06)),
                            ),
                            child: ListTile(
                              contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              leading: Container(
                                width: 42,
                                height: 42,
                                decoration: BoxDecoration(
                                  color: statusColor.withAlpha(30),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(_statusIcon(status), color: statusColor, size: 22),
                              ),
                              title: Text(
                                title,
                                style: TextStyle(
                                  fontWeight: FontWeight.w600,
                                  fontSize: 14,
                                  color: onSurface,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                              subtitle: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  if (scheduledAt != null && scheduledAt.isNotEmpty) ...[
                                    SizedBox(height: 3),
                                    Row(
                                      children: [
                                        Icon(Icons.schedule_rounded, size: 12, color: onSurface.withOpacity(0.4)),
                                        SizedBox(width: 4),
                                        Text(
                                          scheduledAt.length > 16 ? scheduledAt.substring(0, 16) : scheduledAt,
                                          style: TextStyle(fontSize: 11, color: onSurface.withOpacity(0.4)),
                                        ),
                                      ],
                                    ),
                                  ],
                                  if (total != null && total.toString().isNotEmpty) ...[
                                    SizedBox(height: 2),
                                    Row(
                                      children: [
                                        Icon(Icons.people_outline_rounded, size: 12, color: onSurface.withOpacity(0.4)),
                                        SizedBox(width: 4),
                                        Text(
                                          '$total destinataires',
                                          style: TextStyle(fontSize: 11, color: onSurface.withOpacity(0.4)),
                                        ),
                                      ],
                                    ),
                                  ],
                                ],
                              ),
                              trailing: Container(
                                padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: statusColor.withAlpha(25),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: statusColor.withAlpha(60)),
                                ),
                                child: Text(
                                  _statusLabel(status),
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: statusColor,
                                  ),
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
    );
  }
}

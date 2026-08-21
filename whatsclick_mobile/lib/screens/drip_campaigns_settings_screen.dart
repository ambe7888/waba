import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'create_drip_campaign_screen.dart';
import 'drip_campaign_detail_screen.dart';

class DripCampaignsSettingsScreen extends StatefulWidget {
  const DripCampaignsSettingsScreen({super.key});

  @override
  State<DripCampaignsSettingsScreen> createState() =>
      _DripCampaignsSettingsScreenState();
}

class _DripCampaignsSettingsScreenState
    extends State<DripCampaignsSettingsScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _campaigns = [];
  final Set<String> _togglingUids = {};

  @override
  void initState() {
    super.initState();
    _fetchCampaigns();
  }

  Future<void> _fetchCampaigns() async {
    setState(() => _isLoading = true);
    final data = await ApiService().fetchDripCampaigns();
    if (mounted) {
      setState(() {
        _campaigns = data;
        _isLoading = false;
      });
    }
  }

  String _formatDelay(dynamic value, dynamic type) {
    final unit = switch (type?.toString()) {
      'minutes' => 'min',
      'hours' => 'h',
      'days' => 'j',
      _ => (type ?? '').toString(),
    };
    return '$value$unit';
  }

  /// Short preview of a campaign's step intervals, e.g. "5min → 1h → 1j".
  String? _stepsPreview(Map<String, dynamic> campaign) {
    final steps = campaign['steps'];
    if (steps is! List || steps.isEmpty) return null;
    return steps.map((s) => _formatDelay(s['delay_value'], s['delay_type'])).join(' → ');
  }

  Future<void> _toggleStatus(String uid, dynamic currentStatus) async {
    setState(() {
      _togglingUids.add(uid);
    });

    final success = await ApiService().toggleDripCampaign(uid);

    if (mounted) {
      if (success) {
        setState(() {
          final index = _campaigns.indexWhere((c) => c['_uid'] == uid);
          if (index != -1) {
            final isCurrentlyActive = currentStatus == 1 || currentStatus == '1' || currentStatus == true;
            _campaigns[index]['status'] = isCurrentlyActive ? 2 : 1;
          }
        });
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Erreur lors de la modification du statut')),
        );
      }
      setState(() {
        _togglingUids.remove(uid);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      backgroundColor:
          isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: const Text('Campagnes Goutte à Goutte'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchCampaigns,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(
              child:
                  CircularProgressIndicator(color: ThemeService.primaryColor))
          : _campaigns.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(32.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.water_drop_outlined,
                            size: 64,
                            color: isDark ? Colors.white30 : Colors.black26),
                        const SizedBox(height: 16),
                        Text(
                          'Aucune campagne goutte à goutte trouvée.',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 16,
                            color: isDark ? Colors.white60 : Colors.black54,
                          ),
                        ),
                        const SizedBox(height: 20),
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: ThemeService.primaryColor,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          icon: const Icon(Icons.add_rounded),
                          label: const Text('Créer une campagne', style: TextStyle(fontWeight: FontWeight.bold)),
                          onPressed: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(builder: (_) => const CreateDripCampaignScreen()),
                            );
                          },
                        )
                      ],
                    ),
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _campaigns.length,
                  itemBuilder: (context, index) {
                    final campaign = _campaigns[index];
                    final uid = campaign['_uid'];
                    final title = campaign['title'] ?? 'Sans titre';
                    final status = campaign['status'];
                    final isActive = status == 1 || status == '1' || status == true || status == 'active';
                    final isToggling = _togglingUids.contains(uid);
                    final stepsPreview = _stepsPreview(campaign);

                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                      color: isDark
                          ? ThemeService.darkCard
                          : ThemeService.lightCard,
                      clipBehavior: Clip.antiAlias,
                      child: InkWell(
                        onTap: () async {
                          final changed = await Navigator.of(context).push<bool>(
                            MaterialPageRoute(
                              builder: (_) => DripCampaignDetailScreen(campaignUid: uid),
                            ),
                          );
                          if (changed == true) _fetchCampaigns();
                        },
                        child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: (isActive ? Colors.green : Colors.grey)
                                    .withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Icon(
                                Icons.water_drop_rounded,
                                color: isActive ? Colors.green : Colors.grey,
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    title,
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    isActive ? 'Active' : 'Désactivée',
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w500,
                                      color: isActive
                                          ? Colors.green
                                          : Colors.redAccent,
                                    ),
                                  ),
                                  if (stepsPreview != null) ...[
                                    const SizedBox(height: 6),
                                    Row(
                                      children: [
                                        Icon(Icons.schedule_rounded,
                                            size: 13,
                                            color: isDark ? Colors.white38 : Colors.black38),
                                        const SizedBox(width: 4),
                                        Expanded(
                                          child: Text(
                                            stepsPreview,
                                            style: TextStyle(
                                              fontSize: 12,
                                              color: isDark ? Colors.white54 : Colors.black54,
                                            ),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ],
                              ),
                            ),
                            if (isToggling)
                              const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: ThemeService.primaryColor),
                              )
                            else
                              Switch(
                                value: isActive,
                                activeThumbColor: ThemeService.primaryColor,
                                onChanged: (val) => _toggleStatus(uid, status),
                              ),
                          ],
                        ),
                        ),
                      ),
                    );
                  },
                ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: ThemeService.primaryColor,
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const CreateDripCampaignScreen()),
          );
        },
        child: const Icon(Icons.add_rounded, color: Colors.white, size: 28),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../config/app_config.dart';
import 'package:fl_chart/fl_chart.dart';
import 'contacts_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _stats;
  String? _error;

  DateTimeRange? _selectedDateRange;
  String? _selectedAgentId;
  List<dynamic> _agents = [];
  List<String?> _selectedLabelUids = [null, null, null];
  bool? _botActive;                   // optimistic bot switch state
  int _roleId = 3;
  String _firstName = '';

  @override
  void initState() {
    super.initState();
    _fetchDashboardStats();
  }

  Future<void> _fetchDashboardStats() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      _roleId = await ApiService().getUserRoleId();
      
      String? startStr;
      String? endStr;
      if (_selectedDateRange != null) {
        startStr = _selectedDateRange!.start.toIso8601String().substring(0, 10);
        endStr = _selectedDateRange!.end.toIso8601String().substring(0, 10);
      }

      final data = await ApiService().fetchDashboardStats(
        startDate: startStr,
        endDate: endStr,
        agentId: _selectedAgentId,
      );
      if (mounted) {
        setState(() {
          _stats = data;
          // Preserve agents list between refreshes so admin always sees agents
          final newAgents = data?['agents'] as List? ?? [];
          if (newAgents.isNotEmpty) {
            _agents = newAgents;
          }
          // Initialise single label selection
          final List<dynamic> labelStats = data?['label_date_stats'] ?? [];
          if (labelStats.isNotEmpty) {
            for (int i = 0; i < 3; i++) {
              if (_selectedLabelUids[i] == null) {
                // Pre-select different labels if available
                if (i < labelStats.length) {
                  _selectedLabelUids[i] = labelStats[i]['label_uid'];
                } else {
                  _selectedLabelUids[i] = labelStats[0]['label_uid'];
                }
              }
            }
          }
          // Sync bot active state
          _botActive = data?['ai_credits']?['bot_active'] as bool? ?? false;

          final vendorUserData = data?['vendorUserData'];
          if (vendorUserData != null) {
            String name = vendorUserData['first_name'] ?? '';
            if (name.isEmpty) {
               name = vendorUserData['full_name'] ?? vendorUserData['email'] ?? '';
            }
            _firstName = name;
          }
          
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Erreur de chargement des statistiques';
          _isLoading = false;
        });
      }
    }
  }

  Color _parseColor(String? colorHex) {
    if (colorHex == null || colorHex.isEmpty) return Colors.grey;
    String hex = colorHex.replaceAll('#', '');
    if (hex.length == 6) {
      hex = 'FF$hex';
    }
    return Color(int.parse(hex, radix: 16));
  }

  Widget _buildFilterBar() {
    final isDark = ThemeService().isDark;
    final isAdmin = _roleId == 2;
    
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Date Range Picker Button
              Expanded(
                child: InkWell(
                  onTap: _selectDateRange,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    decoration: BoxDecoration(
                      color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: _selectedDateRange != null 
                            ? ThemeService.primaryColor 
                            : (isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.05)),
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.calendar_today_rounded, 
                          color: _selectedDateRange != null ? ThemeService.primaryColor : Colors.grey,
                          size: 16,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _selectedDateRange == null 
                                ? 'Filtrer par date' 
                                : '${_selectedDateRange!.start.toIso8601String().substring(0, 10)} à ${_selectedDateRange!.end.toIso8601String().substring(0, 10)}',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: _selectedDateRange != null 
                                  ? ThemeService.primaryColor 
                                  : (isDark ? Colors.white70 : Colors.black87),
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (_selectedDateRange != null)
                          GestureDetector(
                            onTap: () {
                              setState(() {
                                _selectedDateRange = null;
                              });
                              _fetchDashboardStats();
                            },
                            child: const Padding(
                              padding: EdgeInsets.only(left: 4.0),
                              child: Icon(Icons.close, size: 14, color: Colors.grey),
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              ),
              if (isAdmin && _agents.isNotEmpty) ...[
                const SizedBox(width: 10),
                // Agent Dropdown Filter
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(
                      color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: _selectedAgentId != null 
                            ? ThemeService.primaryColor 
                            : (isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.05)),
                      ),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _selectedAgentId,
                        hint: Text(
                          'Par agent',
                          style: TextStyle(fontSize: 11, color: isDark ? Colors.white70 : Colors.black87),
                        ),
                        dropdownColor: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                        isExpanded: true,
                        icon: const Icon(Icons.arrow_drop_down, size: 20),
                        items: [
                          DropdownMenuItem<String>(
                            value: null,
                            child: Text(
                              'Tous les agents',
                              style: TextStyle(fontSize: 11, color: isDark ? Colors.white70 : Colors.black87),
                            ),
                          ),
                          DropdownMenuItem<String>(
                            value: 'unassigned',
                            child: Text(
                              'Non assignés',
                              style: TextStyle(fontSize: 11, color: isDark ? Colors.white70 : Colors.black87),
                            ),
                          ),
                          ..._agents.map((agent) {
                            final name = '${agent['first_name'] ?? ''} ${agent['last_name'] ?? ''}'.trim();
                            return DropdownMenuItem<String>(
                              value: agent['_id']?.toString(),
                              child: Text(
                                name.isNotEmpty ? name : (agent['email'] ?? 'Agent'),
                                style: TextStyle(fontSize: 11, color: isDark ? Colors.white70 : Colors.black87),
                              ),
                            );
                          }).toList(),
                        ],
                        onChanged: (val) {
                          setState(() {
                            _selectedAgentId = val;
                          });
                          _fetchDashboardStats();
                        },
                      ),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Future<void> _selectDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 30)),
      initialDateRange: _selectedDateRange,
    );
    if (picked != null) {
      setState(() {
        _selectedDateRange = picked;
      });
      _fetchDashboardStats();
    }
  }

  Widget _buildLabelStatsCard() {
    final isDark = ThemeService().isDark;
    final List<dynamic> labelStats = _stats?['label_date_stats'] ?? [];
    if (labelStats.isEmpty) return const SizedBox.shrink();

    final isCustomDateActive = _selectedDateRange != null;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
      child: Container(
        decoration: BoxDecoration(
          color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: isDark ? Colors.black.withOpacity(0.2) : Colors.black.withOpacity(0.05),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
          border: Border.all(
            color: isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.05),
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.label_outline_rounded, color: ThemeService.primaryColor, size: 22),
                  const SizedBox(width: 8),
                  Text(
                    'Statistiques des Étiquettes',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                      color: isDark ? Colors.white : Colors.black87,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              
              // 3 lines for label stats
              ...List.generate(3, (index) {
                final currentSelectedUid = _selectedLabelUids[index] ?? labelStats[0]['label_uid'];
                final selectedLabel = labelStats.firstWhere(
                  (l) => l['label_uid'] == currentSelectedUid,
                  orElse: () => labelStats[0],
                );
                final selectedLabelUid = selectedLabel['label_uid'] as String;
                final labelColorHex = selectedLabel['label_color'] ?? '#808080';
                final labelBgColor = _parseColor(labelColorHex);

                return Padding(
                  padding: const EdgeInsets.only(bottom: 12.0),
                  child: Row(
                    children: [
                      // Label Picker Dropdown
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8),
                        decoration: BoxDecoration(
                          color: labelBgColor.withOpacity(0.15),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: labelBgColor.withOpacity(0.4)),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: currentSelectedUid,
                            dropdownColor: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                            icon: Icon(Icons.arrow_drop_down, size: 16, color: labelBgColor),
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: labelBgColor,
                            ),
                            items: labelStats.map<DropdownMenuItem<String>>((l) {
                              return DropdownMenuItem<String>(
                                value: l['label_uid'],
                                child: Text(l['label_title'] ?? 'Sans nom'),
                              );
                            }).toList(),
                            onChanged: (val) {
                              // Create new list reference to force Flutter rebuild
                              final newUids = List<String?>.from(_selectedLabelUids);
                              newUids[index] = val;
                              setState(() {
                                _selectedLabelUids = newUids;
                              });
                            },
                          ),
                        ),
                      ),
                      const Spacer(),
                      // Period count columns
                      if (isCustomDateActive) ...[
                        _buildPeriodCountColumn('Période', selectedLabel['count_total'] ?? 0, selectedLabelUid, 'custom'),
                      ] else ...[
                        _buildPeriodCountColumn('Auj.', selectedLabel['count_today'] ?? 0, selectedLabelUid, 'today'),
                        const SizedBox(width: 8),
                        _buildPeriodCountColumn('Hier', selectedLabel['count_yesterday'] ?? 0, selectedLabelUid, 'yesterday'),
                        const SizedBox(width: 8),
                        _buildPeriodCountColumn('Avant-hier', selectedLabel['count_day_before'] ?? 0, selectedLabelUid, 'day_before'),
                      ],
                    ],
                  ),
                );
              }),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPeriodCountColumn(String periodName, dynamic count, String labelUid, String dateFilter) {
    final isDark = ThemeService().isDark;
    final int cnt = int.tryParse(count.toString()) ?? 0;
    
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              periodName,
              style: TextStyle(
                fontSize: 9,
                fontWeight: FontWeight.w500,
                color: isDark ? Colors.white60 : Colors.black54,
              ),
            ),
            Text(
              cnt.toString(),
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: isDark ? Colors.white : Colors.black87,
              ),
            ),
          ],
        ),
        const SizedBox(width: 4),
        IconButton(
          icon: Icon(Icons.remove_red_eye_outlined, size: 18, color: cnt > 0 ? ThemeService.primaryColor : Colors.grey),
          tooltip: 'Voir les contacts',
          padding: const EdgeInsets.all(4),
          constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
          onPressed: cnt > 0 ? () => _viewLabeledContacts(labelUid, dateFilter) : null,
        ),
      ],
    );
  }

  void _viewLabeledContacts(String labelUid, String dateFilter) {
    String? startStr;
    String? endStr;
    if (dateFilter == 'custom' && _selectedDateRange != null) {
      startStr = _selectedDateRange!.start.toIso8601String().substring(0, 10);
      endStr = _selectedDateRange!.end.toIso8601String().substring(0, 10);
    }
    
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ContactsScreen(
          initialLabelFilter: labelUid,
          initialLabelDateFilter: dateFilter,
          initialStartDate: startStr,
          initialEndDate: endStr,
          initialAssignedFilter: _selectedAgentId,
        ),
      ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    final isDark = ThemeService().isDark;
    return Container(
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: isDark ? Colors.black.withOpacity(0.2) : Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
        border: Border.all(
          color: isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.05),
          width: 1,
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withOpacity(0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 26),
            ),
            const Spacer(),
            Text(
              value,
              style: TextStyle(
                fontSize: 26,
                fontWeight: FontWeight.w800,
                color: isDark ? Colors.white : Colors.black87,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              title,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: isDark ? Colors.white70 : Colors.black54,
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.asset(
                'assets/icon/launcher_icon.png',
                width: 36,
                height: 36,
                fit: BoxFit.cover,
              ),
            ),
            const SizedBox(width: 10),
            const Text(
              'WhatsClick',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, letterSpacing: -0.5),
            ),
          ],
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_error!, style: TextStyle(color: Colors.red)),
                      SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchDashboardStats,
                        child: Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _fetchDashboardStats,
                  child: CustomScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    slivers: [
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _firstName.isNotEmpty
                                    ? 'Bienvenue, $_firstName 👋'
                                    : 'Bienvenue 👋',
                                style: TextStyle(
                                  fontSize: 26,
                                  fontWeight: FontWeight.w800,
                                  color: isDark ? Colors.white : Colors.black87,
                                  letterSpacing: -0.5,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                '${DateTime.now().day} ${[
                                  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                                  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
                                ][DateTime.now().month - 1]} ${DateTime.now().year}',
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w500,
                                  color: isDark ? Colors.white60 : Colors.black54,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: (isDark ? ThemeService.darkCard : ThemeService.lightCard).withOpacity(0.5),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.05),
                                  ),
                                ),
                                child: Row(
                                  children: [
                                    Icon(
                                      Icons.info_outline,
                                      color: isDark ? Colors.white70 : Colors.black54,
                                      size: 20,
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            'Compte : ${_stats?['vendorInfo']?['title'] ?? ''}',
                                            style: TextStyle(
                                              fontSize: 13,
                                              fontWeight: FontWeight.w600,
                                              color: isDark ? Colors.white70 : Colors.black87,
                                            ),
                                          ),
                                          if (_stats?['ai_credits']?['is_enabled'] == true) ...[
                                            const SizedBox(height: 4),
                                            Text(
                                              'Crédits IA : ${_stats?['ai_credits']?['display_credits'] ?? '0'}',
                                              style: TextStyle(
                                                fontSize: 12,
                                                fontWeight: FontWeight.w500,
                                                color: isDark ? Colors.white60 : Colors.black54,
                                              ),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                     if (_roleId == 2) ...[
                                      const SizedBox(width: 8),
                                      Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(
                                            'Bot IA',
                                            style: TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.w600,
                                              color: isDark ? Colors.white70 : Colors.black87,
                                            ),
                                          ),
                                          const SizedBox(width: 4),
                                          Switch(
                                            value: _botActive ?? (_stats?['ai_credits']?['bot_active'] ?? false),
                                            activeColor: ThemeService.primaryColor,
                                            onChanged: (value) async {
                                              // Optimistic update: toggle immediately
                                              setState(() => _botActive = value);
                                              final success = await ApiService().toggleBotReply();
                                              if (success) {
                                                if (mounted) {
                                                  ScaffoldMessenger.of(context).showSnackBar(
                                                    SnackBar(
                                                      content: Text(value ? 'IA Activée' : 'IA Désactivée'),
                                                      backgroundColor: value ? Colors.green : Colors.orange,
                                                      duration: const Duration(seconds: 2),
                                                    ),
                                                  );
                                                }
                                              } else {
                                                // Revert if API failed
                                                setState(() => _botActive = !value);
                                                if (mounted) {
                                                  ScaffoldMessenger.of(context).showSnackBar(
                                                    const SnackBar(content: Text('Échec de la modification du statut du Bot')),
                                                  );
                                                }
                                              }
                                            },
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
                      ),
                      SliverToBoxAdapter(
                        child: _buildFilterBar(),
                      ),
                      SliverToBoxAdapter(
                        child: _buildLabelStatsCard(),
                      ),
                      SliverToBoxAdapter(
                        child: _buildMessageHistoryChart(),
                      ),
                      SliverPadding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        sliver: SliverGrid.count(
                          crossAxisCount: 2,
                          crossAxisSpacing: 16,
                          mainAxisSpacing: 16,
                          childAspectRatio: 0.9,
                          children: [
                            _buildStatCard('Contacts', _stats?['totalContacts']?.toString() ?? '0', Icons.people, Colors.blue),
                            _buildStatCard('Campagnes', _stats?['totalCampaigns']?.toString() ?? '0', Icons.campaign, Colors.orange),
                            _buildStatCard('Modèles', _stats?['totalTemplates']?.toString() ?? '0', Icons.file_copy, Colors.purple),
                            _buildStatCard('Msgs en attente', _stats?['messagesInQueue']?.toString() ?? '0', Icons.pending, Colors.amber),
                            _buildStatCard('Msgs envoyés', _stats?['totalMessagesProcessed']?.toString() ?? '0', Icons.send, Colors.green),
                            _buildStatCard('Actifs (24h)', _stats?['activeContacts24hCount']?.toString() ?? '0', Icons.timer, Colors.red),
                            _buildStatCard('Messages non lus', _stats?['unreadMessagesCount']?.toString() ?? '0', Icons.mark_chat_unread, Colors.redAccent),
                            _buildStatCard('Reçus aujourd\'hui', _stats?['messagesReceivedTodayCount']?.toString() ?? '0', Icons.chat_bubble_outline, Colors.teal),
                          ],
                        ),
                      ),
                      const SliverToBoxAdapter(
                        child: SizedBox(height: 32),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildMessageHistoryChart() {
    final isDark = ThemeService().isDark;
    final List<dynamic> rawHistory = _stats?['messageHistory'] ?? [];
    if (rawHistory.isEmpty) return const SizedBox.shrink();

    final barGroups = <BarChartGroupData>[];
    double maxVal = 10;
    
    for (int i = 0; i < rawHistory.length; i++) {
      final item = rawHistory[i];
      final incoming = double.tryParse(item['incoming']?.toString() ?? '0') ?? 0;
      final outgoing = double.tryParse(item['outgoing']?.toString() ?? '0') ?? 0;
      
      if (incoming > maxVal) maxVal = incoming;
      if (outgoing > maxVal) maxVal = outgoing;

      barGroups.add(
        BarChartGroupData(
          x: i,
          barRods: [
            BarChartRodData(
              toY: incoming,
              color: Colors.blue,
              width: 8,
              borderRadius: BorderRadius.circular(4),
            ),
            BarChartRodData(
              toY: outgoing,
              color: Colors.green,
              width: 8,
              borderRadius: BorderRadius.circular(4),
            ),
          ],
        ),
      );
    }

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.05)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                'Historique des messages (7j)',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: isDark ? Colors.white : Colors.black87,
                ),
              ),
              const Spacer(),
              Container(
                width: 10,
                height: 10,
                color: Colors.blue,
              ),
              const SizedBox(width: 4),
              Text('Reçus', style: TextStyle(fontSize: 11, color: isDark ? Colors.white60 : Colors.black54)),
              const SizedBox(width: 8),
              Container(
                width: 10,
                height: 10,
                color: Colors.green,
              ),
              const SizedBox(width: 4),
              Text('Envoyés', style: TextStyle(fontSize: 11, color: isDark ? Colors.white60 : Colors.black54)),
            ],
          ),
          const SizedBox(height: 24),
          SizedBox(
            height: 200,
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                maxY: maxVal * 1.2,
                barTouchData: BarTouchData(enabled: true),
                titlesData: FlTitlesData(
                  show: true,
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (double value, TitleMeta meta) {
                        final idx = value.toInt();
                        if (idx >= 0 && idx < rawHistory.length) {
                          return Padding(
                            padding: const EdgeInsets.only(top: 6),
                            child: Text(
                              rawHistory[idx]['label']?.toString() ?? '',
                              style: TextStyle(
                                color: isDark ? Colors.white60 : Colors.black54,
                                fontSize: 10,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          );
                        }
                        return const SizedBox.shrink();
                      },
                    ),
                  ),
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 30,
                      getTitlesWidget: (double value, TitleMeta meta) {
                        return Text(
                          value.toInt().toString(),
                          style: TextStyle(
                            color: isDark ? Colors.white60 : Colors.black54,
                            fontSize: 10,
                          ),
                        );
                      },
                    ),
                  ),
                  topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                ),
                gridData: FlGridData(
                  show: true,
                  drawVerticalLine: false,
                  getDrawingHorizontalLine: (value) => FlLine(
                    color: isDark ? Colors.white10 : Colors.black12,
                    strokeWidth: 1,
                  ),
                ),
                borderData: FlBorderData(show: false),
                barGroups: barGroups,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../config/app_config.dart';
import 'package:fl_chart/fl_chart.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _stats;
  String? _error;

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
      final data = await ApiService().fetchDashboardStats();
      if (mounted) {
        setState(() {
          _stats = data;
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
                                _stats?['vendorUserData']?['first_name'] != null
                                    ? 'Bienvenue, ${_stats!['vendorUserData']['first_name']} 👋'
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
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
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

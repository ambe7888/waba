import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../config/app_config.dart';

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
                                      child: Text(
                                        'Compte : ${_stats?['vendorInfo']?['title'] ?? ''}',
                                        style: TextStyle(
                                          fontSize: 13,
                                          fontWeight: FontWeight.w600,
                                          color: isDark ? Colors.white70 : Colors.black87,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
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
}

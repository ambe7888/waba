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
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(icon, color: color, size: 24),
            ),
            const Spacer(),
            Text(
              value,
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              title,
              style: TextStyle(
                fontSize: 12,
                color: ThemeService().isDark ? Colors.white70 : Colors.black54,
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Tableau de bord'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_error!, style: const TextStyle(color: Colors.red)),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchDashboardStats,
                        child: const Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _fetchDashboardStats,
                  child: GridView.count(
                    padding: const EdgeInsets.all(16),
                    crossAxisCount: 2,
                    crossAxisSpacing: 16,
                    mainAxisSpacing: 16,
                    children: [
                      _buildStatCard('Contacts', _stats?['totalContacts']?.toString() ?? '0', Icons.people, Colors.blue),
                      _buildStatCard('Campagnes', _stats?['totalCampaigns']?.toString() ?? '0', Icons.campaign, Colors.orange),
                      _buildStatCard('Modèles', _stats?['totalTemplates']?.toString() ?? '0', Icons.file_copy, Colors.purple),
                      _buildStatCard('Msgs en attente', _stats?['messagesInQueue']?.toString() ?? '0', Icons.pending, Colors.amber),
                      _buildStatCard('Msgs envoyés', _stats?['totalMessagesProcessed']?.toString() ?? '0', Icons.send, Colors.green),
                      _buildStatCard('Actifs (24h)', _stats?['activeContacts24hCount']?.toString() ?? '0', Icons.timer, Colors.red),
                    ],
                  ),
                ),
    );
  }
}

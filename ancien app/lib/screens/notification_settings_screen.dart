import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/theme_service.dart';

class NotificationSettingsScreen extends StatefulWidget {
  const NotificationSettingsScreen({super.key});

  @override
  State<NotificationSettingsScreen> createState() => _NotificationSettingsScreenState();
}

class _NotificationSettingsScreenState extends State<NotificationSettingsScreen> {
  bool _dndEnabled = false;
  bool _soundEnabled = true;
  String _filterType = 'all'; // 'all' or 'new_chats'

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    final prefs = await SharedPreferences.getInstance();
    if (mounted) {
      setState(() {
        _dndEnabled = prefs.getBool('notifications_dnd') ?? false;
        _soundEnabled = prefs.getBool('notifications_sound') ?? true;
        _filterType = prefs.getString('notifications_filter') ?? 'all';
      });
    }
  }

  Future<void> _saveSetting(String key, dynamic value) async {
    final prefs = await SharedPreferences.getInstance();
    if (value is bool) {
      await prefs.setBool(key, value);
    } else if (value is String) {
      await prefs.setString(key, value);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: const Text('Notifications', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            child: Column(
              children: [
                SwitchListTile(
                  title: const Text('Ne pas déranger (Silencieux)', style: TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: const Text('Désactiver temporairement les notifications push.'),
                  value: _dndEnabled,
                  onChanged: (value) async {
                    setState(() {
                      _dndEnabled = value;
                    });
                    await _saveSetting('notifications_dnd', value);
                  },
                  activeColor: const Color(0xFF2DD4BF),
                ),
                const Divider(height: 1),
                SwitchListTile(
                  title: const Text('Sonnerie active', style: TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: const Text('Émettre un signal sonore pour chaque alerte.'),
                  value: _soundEnabled,
                  onChanged: (value) async {
                    setState(() {
                      _soundEnabled = value;
                    });
                    await _saveSetting('notifications_sound', value);
                  },
                  activeColor: const Color(0xFF2DD4BF),
                ),
                const Divider(height: 1),
                ListTile(
                  title: const Text('Filtre des notifications', style: TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: Text(_filterType == 'all'
                      ? 'Tous les nouveaux messages'
                      : 'Uniquement les nouvelles conversations'),
                  trailing: DropdownButton<String>(
                    value: _filterType,
                    dropdownColor: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                    items: const [
                      DropdownMenuItem(
                        value: 'all',
                        child: Text('Tous les messages'),
                      ),
                      DropdownMenuItem(
                        value: 'new_chats',
                        child: Text('Nouveaux chats uniquement'),
                      ),
                    ],
                    onChanged: (value) async {
                      if (value != null) {
                        setState(() {
                          _filterType = value;
                        });
                        await _saveSetting('notifications_filter', value);
                      }
                    },
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

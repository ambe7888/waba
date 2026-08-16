import 'package:flutter/material.dart';
import '../services/api_service.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _notifications = [];

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<void> _fetchNotifications() async {
    final data = await ApiService().fetchNotifications();
    if (mounted) {
      setState(() {
        _isLoading = false;
        if (data['success']) {
          _notifications = data['notifications'];
        }
      });
      // Marquer comme lu une fois affichées
      if (data['unreadCount'] > 0) {
        await ApiService().markNotificationsAsRead();
      }
    }
  }

  IconData _getIconForType(String type) {
    switch (type) {
      case 'success':
        return Icons.check_circle;
      case 'warning':
        return Icons.warning_rounded;
      case 'danger':
        return Icons.error_rounded;
      case 'info':
      default:
        return Icons.info_rounded;
    }
  }

  Color _getColorForType(String type) {
    switch (type) {
      case 'success':
        return Colors.green;
      case 'warning':
        return Colors.orange;
      case 'danger':
        return Colors.red;
      case 'info':
      default:
        return Colors.blue;
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        centerTitle: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _notifications.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.notifications_off_rounded, size: 80, color: Colors.grey.withValues(alpha: 0.5)),
                      const SizedBox(height: 16),
                      Text(
                        'Aucune notification',
                        style: TextStyle(
                          fontSize: 18,
                          color: isDark ? Colors.grey[400] : Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _notifications.length,
                  separatorBuilder: (context, index) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final notif = _notifications[index];
                    final type = notif['type'] ?? 'info';
                    final isRead = notif['is_read'] == true;

                    return Container(
                      decoration: BoxDecoration(
                        color: isDark ? Colors.grey[850] : Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.05),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          )
                        ],
                        border: isRead ? null : Border.all(color: _getColorForType(type).withValues(alpha: 0.5), width: 1.5),
                      ),
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        leading: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: _getColorForType(type).withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            _getIconForType(type),
                            color: _getColorForType(type),
                            size: 24,
                          ),
                        ),
                        title: Text(
                          notif['title'] ?? '',
                          style: TextStyle(
                            fontWeight: isRead ? FontWeight.w600 : FontWeight.w800,
                            fontSize: 16,
                          ),
                        ),
                        subtitle: Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text(
                            notif['message'] ?? '',
                            style: TextStyle(
                              color: isDark ? Colors.grey[400] : Colors.grey[600],
                              height: 1.4,
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}

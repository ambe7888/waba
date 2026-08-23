import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

/// "Gestion des commandes" — a global order list across all contacts,
/// mirroring the web's orders.blade.php page (which renders server-side;
/// this screen is the first mobile-native equivalent).
class OrdersManagementScreen extends StatefulWidget {
  const OrdersManagementScreen({super.key});

  @override
  State<OrdersManagementScreen> createState() => _OrdersManagementScreenState();
}

class _OrdersManagementScreenState extends State<OrdersManagementScreen> {
  bool _isLoading = true;
  bool _isFeatureAvailable = true;
  List<Map<String, dynamic>> _orders = [];
  final Set<String> _updatingUids = {};

  static const _statusLabels = {
    'validated': 'Nouvelle / Validée',
    'confirmed': 'Confirmée',
    'processing': 'En préparation',
    'shipped': 'En livraison',
    'delivered': 'Livrée',
    'cancelled': 'Annulée',
  };

  static const _statusColors = {
    'validated': Color(0xFF3B82F6),
    'confirmed': Color(0xFF8B5CF6),
    'processing': Color(0xFFF59E0B),
    'shipped': Color(0xFF06B6D4),
    'delivered': Color(0xFF10B981),
    'cancelled': Color(0xFFEF4444),
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _isLoading = true);
    final result = await ApiService().fetchAllOrders();
    if (!mounted) return;
    setState(() {
      _isFeatureAvailable = result['is_feature_available'] == true;
      _orders = List<Map<String, dynamic>>.from(result['orders'] ?? []);
      _isLoading = false;
    });
  }

  Future<void> _changeStatus(Map<String, dynamic> order, String newStatus) async {
    final uid = order['_uid']?.toString();
    if (uid == null || newStatus == order['status']) return;
    setState(() => _updatingUids.add(uid));
    final success = await ApiService().updateOrderStatus(uid, newStatus);
    if (!mounted) return;
    setState(() {
      _updatingUids.remove(uid);
      if (success) order['status'] = newStatus;
    });
    if (!success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible de mettre à jour le statut.'), backgroundColor: Colors.red),
      );
    }
  }

  Future<void> _confirmDelete(Map<String, dynamic> order) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Supprimer cette commande ?'),
        content: const Text('Cette action est irréversible.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Supprimer', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirm != true) return;
    final uid = order['_uid']?.toString();
    if (uid == null) return;
    final success = await ApiService().deleteOrder(uid);
    if (!mounted) return;
    if (success) {
      setState(() => _orders.removeWhere((o) => o['_uid'] == uid));
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Commande supprimée.'), backgroundColor: Color(0xFF10B981)),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible de supprimer cette commande.'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: const Text('Gestion des commandes', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : !_isFeatureAvailable
              ? _buildUpgradeNotice(isDark)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _orders.isEmpty
                      ? ListView(
                          padding: const EdgeInsets.all(16),
                          children: [
                            const SizedBox(height: 80),
                            Center(
                              child: Column(
                                children: [
                                  Icon(Icons.receipt_long_outlined, size: 48, color: isDark ? Colors.white24 : Colors.black26),
                                  const SizedBox(height: 12),
                                  Text('Aucune commande pour le moment.', style: TextStyle(color: isDark ? Colors.white54 : Colors.black45)),
                                ],
                              ),
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _orders.length,
                          itemBuilder: (context, index) => _buildOrderCard(_orders[index], isDark),
                        ),
                ),
    );
  }

  Widget _buildUpgradeNotice(bool isDark) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.red.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.red.withValues(alpha: 0.3)),
          ),
          child: Row(
            children: [
              const Icon(Icons.lock_rounded, color: Colors.red),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Cette fonctionnalité n\'est pas disponible dans votre plan actuel. Veuillez mettre à niveau votre abonnement.',
                  style: TextStyle(color: isDark ? Colors.white70 : Colors.black87, fontSize: 13),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildOrderCard(Map<String, dynamic> order, bool isDark) {
    final uid = order['_uid']?.toString() ?? '';
    final status = order['status']?.toString() ?? 'validated';
    final statusColor = _statusColors[status] ?? Colors.grey;
    final details = order['order_details'] as Map? ?? {};
    final items = (details['items'] as List?) ?? [];
    final totalPrice = details['total_price'];
    final currency = details['currency']?.toString() ?? 'CFA';
    final contact = order['contact'] as Map?;
    final contactName = contact != null
        ? '${contact['first_name'] ?? ''} ${contact['last_name'] ?? ''}'.trim()
        : '';
    final contactPhone = contact?['wa_id']?.toString() ?? '';
    final createdAt = order['created_at']?.toString() ?? '';
    final isUpdating = _updatingUids.contains(uid);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      contactName.isNotEmpty ? contactName : (contactPhone.isNotEmpty ? contactPhone : 'Commande #${uid.length >= 8 ? uid.substring(0, 8) : uid}'),
                      style: TextStyle(fontWeight: FontWeight.w700, color: isDark ? Colors.white : Colors.black87),
                    ),
                    if (createdAt.isNotEmpty)
                      Text(_formatDate(createdAt), style: TextStyle(fontSize: 11.5, color: isDark ? Colors.white54 : Colors.black45)),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
                child: Text(
                  _statusLabels[status] ?? status,
                  style: TextStyle(color: statusColor, fontWeight: FontWeight.w700, fontSize: 11),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          if (items.isNotEmpty)
            ...items.take(3).map((item) {
              final m = item is Map ? item : {};
              return Padding(
                padding: const EdgeInsets.only(bottom: 3),
                child: Text(
                  '• ${m['quantity'] ?? 1}x ${m['name'] ?? ''}',
                  style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white70 : Colors.black87),
                ),
              );
            }),
          const SizedBox(height: 8),
          Row(
            children: [
              Text(
                totalPrice != null ? '${_formatPrice(totalPrice)} $currency' : '',
                style: TextStyle(fontWeight: FontWeight.w800, color: ThemeService.primaryColor, fontSize: 14),
              ),
              const Spacer(),
              if (isUpdating)
                const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
              else
                DropdownButton<String>(
                  value: _statusLabels.containsKey(status) ? status : null,
                  hint: const Text('Statut', style: TextStyle(fontSize: 12)),
                  underline: const SizedBox.shrink(),
                  isDense: true,
                  style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white : Colors.black87),
                  items: _statusLabels.entries
                      .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                      .toList(),
                  onChanged: (v) {
                    if (v != null) _changeStatus(order, v);
                  },
                ),
              IconButton(
                icon: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 20),
                onPressed: () => _confirmDelete(order),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _formatPrice(dynamic price) {
    final value = double.tryParse(price.toString()) ?? 0;
    return value.toStringAsFixed(0);
  }

  String _formatDate(String raw) {
    try {
      final dt = DateTime.parse(raw).toLocal();
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year} ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return raw;
    }
  }
}

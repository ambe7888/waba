import 'dart:async';
import 'package:flutter/material.dart';
import '../models/contact.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'order_creation_sheet.dart';

/// Thousands separator without pulling in intl's NumberFormat, whose
/// non-default locale patterns need initializeDateFormatting() first —
/// this avoids that setup entirely for a plain "15 000" style grouping.
String _formatAmount(num value) {
  final rounded = value.round().toString();
  final buffer = StringBuffer();
  for (int i = 0; i < rounded.length; i++) {
    if (i > 0 && (rounded.length - i) % 3 == 0) buffer.write(' ');
    buffer.write(rounded[i]);
  }
  return buffer.toString();
}

/// "Gestion des commandes" — a real management center: KPI cards (total,
/// revenue, in-progress, delivered), status filters, and the order list
/// itself (status update, delete) across all contacts.
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
  String _statusFilter = 'all';

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

  static const _inProgressStatuses = {'validated', 'confirmed', 'processing', 'shipped'};

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

  double _orderTotal(Map<String, dynamic> order) {
    final details = order['order_details'] as Map? ?? {};
    return double.tryParse(details['total_price']?.toString() ?? '') ?? 0;
  }

  List<Map<String, dynamic>> get _filteredOrders {
    if (_statusFilter == 'all') return _orders;
    return _orders.where((o) => o['status']?.toString() == _statusFilter).toList();
  }

  int get _totalOrders => _orders.length;

  double get _totalRevenue => _orders
      .where((o) => o['status']?.toString() != 'cancelled')
      .fold(0.0, (sum, o) => sum + _orderTotal(o));

  int get _inProgressCount =>
      _orders.where((o) => _inProgressStatuses.contains(o['status']?.toString())).length;

  int get _deliveredCount => _orders.where((o) => o['status']?.toString() == 'delivered').length;

  double get _deliveredAmount => _orders
      .where((o) => o['status']?.toString() == 'delivered')
      .fold(0.0, (sum, o) => sum + _orderTotal(o));

  double get _totalFees => _orders
      .where((o) => o['status']?.toString() != 'cancelled')
      .fold(0.0, (sum, o) {
        final details = o['order_details'] as Map? ?? {};
        return sum + (double.tryParse(details['additional_fee']?.toString() ?? '') ?? 0);
      });

  int _countForStatus(String status) => _orders.where((o) => o['status']?.toString() == status).length;

  Future<void> _openCreateOrder() async {
    final contact = await showModalBottomSheet<Contact>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.75,
        maxChildSize: 0.95,
        minChildSize: 0.5,
        builder: (_, controller) => _ContactPickerSheet(scrollController: controller),
      ),
    );
    if (contact == null || !mounted) return;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => OrderCreationSheet(
        contactUid: contact.uid,
        contactName: contact.name.isNotEmpty ? contact.name : contact.phoneNumber,
        onOrderCreated: _load,
      ),
    );
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
      floatingActionButton: (!_isLoading && _isFeatureAvailable)
          ? FloatingActionButton.extended(
              onPressed: _openCreateOrder,
              backgroundColor: ThemeService.primaryColor,
              icon: const Icon(Icons.add_rounded, color: Colors.white),
              label: const Text('Nouvelle commande', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            )
          : null,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : !_isFeatureAvailable
              ? _buildUpgradeNotice(isDark)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _buildStatsGrid(isDark),
                      const SizedBox(height: 16),
                      _buildStatusFilterRow(isDark),
                      const SizedBox(height: 12),
                      if (_filteredOrders.isEmpty)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 60),
                          child: Center(
                            child: Column(
                              children: [
                                Icon(Icons.receipt_long_outlined, size: 48, color: isDark ? Colors.white24 : Colors.black26),
                                const SizedBox(height: 12),
                                Text(
                                  _orders.isEmpty ? 'Aucune commande pour le moment.' : 'Aucune commande dans ce statut.',
                                  style: TextStyle(color: isDark ? Colors.white54 : Colors.black45),
                                ),
                              ],
                            ),
                          ),
                        )
                      else
                        ..._filteredOrders.map((order) => _buildOrderCard(order, isDark)),
                    ],
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

  Widget _buildStatsGrid(bool isDark) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _statCard('Total commandes', _totalOrders.toString(), Icons.receipt_long_rounded,
                  const Color(0xFF6C63FF), isDark),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _statCard('Chiffre d\'affaires', '${_formatAmount(_totalRevenue)} CFA',
                  Icons.payments_rounded, const Color(0xFF10B981), isDark),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _statCard('Montant livré', '${_formatAmount(_deliveredAmount)} CFA',
                  Icons.local_shipping_rounded, const Color(0xFF06B6D4), isDark),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _statCard('Montant des frais', '${_formatAmount(_totalFees)} CFA',
                  Icons.request_quote_rounded, const Color(0xFF8B5CF6), isDark),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _statCard('En cours', _inProgressCount.toString(), Icons.pending_actions_rounded,
                  const Color(0xFFF59E0B), isDark),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _statCard('Livrées', _deliveredCount.toString(), Icons.check_circle_rounded,
                  const Color(0xFF10B981), isDark),
            ),
          ],
        ),
      ],
    );
  }

  Widget _statCard(String title, String value, IconData icon, Color color, bool isDark) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: isDark ? 0.4 : 0.25), width: 1.2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(8)),
                child: Icon(icon, color: color, size: 16),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  value,
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: isDark ? Colors.white : Colors.black87),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            title,
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: isDark ? Colors.white60 : Colors.black54),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildStatusFilterRow(bool isDark) {
    return SizedBox(
      height: 34,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: [
          _filterChip('Toutes', 'all', _totalOrders, isDark),
          const SizedBox(width: 8),
          ..._statusLabels.entries.expand((e) => [
                _filterChip(e.value, e.key, _countForStatus(e.key), isDark),
                const SizedBox(width: 8),
              ]),
        ],
      ),
    );
  }

  Widget _filterChip(String label, String value, int count, bool isDark) {
    final selected = _statusFilter == value;
    return InkWell(
      onTap: () => setState(() => _statusFilter = value),
      borderRadius: BorderRadius.circular(20),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? ThemeService.primaryColor : (isDark ? const Color(0xFF1E293B) : const Color(0xFFE2E8F0)),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Center(
          child: Text(
            '$label ($count)',
            style: TextStyle(
              fontSize: 12,
              fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
              color: selected ? Colors.white : (isDark ? const Color(0xFF94A3B8) : const Color(0xFF475569)),
            ),
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
                totalPrice != null ? '${_formatAmount(double.tryParse(totalPrice.toString()) ?? 0)} $currency' : '',
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

  String _formatDate(String raw) {
    try {
      final dt = DateTime.parse(raw).toLocal();
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year} ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return raw;
    }
  }
}

/// Contact picker used to start a manual order from the management screen
/// (rather than from an existing chat, which already knows the contact).
class _ContactPickerSheet extends StatefulWidget {
  final ScrollController scrollController;

  const _ContactPickerSheet({required this.scrollController});

  @override
  State<_ContactPickerSheet> createState() => _ContactPickerSheetState();
}

class _ContactPickerSheetState extends State<_ContactPickerSheet> {
  final _searchController = TextEditingController();
  List<Contact> _allContacts = [];
  bool _isLoading = true;
  String _searchQuery = '';
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    final contacts = await ApiService().fetchAllContactsSimple();
    if (mounted) {
      setState(() {
        _allContacts = contacts;
        _isLoading = false;
      });
    }
  }

  List<Contact> get _filteredContacts {
    if (_searchQuery.trim().isEmpty) return _allContacts;
    final q = _searchQuery.toLowerCase();
    return _allContacts.where((c) => c.name.toLowerCase().contains(q) || c.phoneNumber.contains(q)).toList();
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () {
      if (mounted) setState(() => _searchQuery = query);
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Container(
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkSurface : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              border: Border(bottom: BorderSide(color: isDark ? Colors.white10 : Colors.grey.shade200)),
            ),
            child: Row(
              children: [
                const Icon(Icons.person_search_rounded, color: Color(0xFF10B981), size: 22),
                const SizedBox(width: 8),
                Text('Choisir un client',
                    style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: isDark ? Colors.white : Colors.black87)),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.close_rounded),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Rechercher un contact...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
              ),
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _filteredContacts.isEmpty
                    ? Center(
                        child: Text('Aucun contact trouvé', style: TextStyle(color: isDark ? Colors.white54 : Colors.black45)),
                      )
                    : ListView.builder(
                        controller: widget.scrollController,
                        padding: const EdgeInsets.symmetric(horizontal: 8),
                        itemCount: _filteredContacts.length,
                        itemBuilder: (context, index) {
                          final contact = _filteredContacts[index];
                          return ListTile(
                            leading: CircleAvatar(
                              backgroundColor: ThemeService.primaryColor.withValues(alpha: 0.15),
                              child: Text(
                                contact.name.isNotEmpty ? contact.name[0].toUpperCase() : '?',
                                style: const TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold),
                              ),
                            ),
                            title: Text(contact.name, style: TextStyle(fontWeight: FontWeight.w600, color: isDark ? Colors.white : Colors.black87)),
                            subtitle: Text(contact.phoneNumber, style: TextStyle(color: isDark ? Colors.white54 : Colors.black45)),
                            onTap: () => Navigator.pop(context, contact),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}

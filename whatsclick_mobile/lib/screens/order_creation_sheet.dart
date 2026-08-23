import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class OrderCreationSheet extends StatefulWidget {
  final String contactUid;
  final String contactName;
  final VoidCallback? onOrderCreated;
  final Map<String, dynamic>? initialOrder;

  const OrderCreationSheet({
    super.key,
    required this.contactUid,
    required this.contactName,
    this.onOrderCreated,
    this.initialOrder,
  });

  @override
  State<OrderCreationSheet> createState() => _OrderCreationSheetState();
}

/// One line item in the order — either a catalog product or a free-typed
/// name/price. Mirrors the web conversation page's multi-item order form
/// (orderItems array), which the mobile form didn't have: it only ever
/// supported a single product per order.
class _OrderItem {
  Map<String, dynamic>? product;
  bool useCustom;
  final TextEditingController nameController;
  final TextEditingController priceController;
  final TextEditingController quantityController;

  _OrderItem({
    this.useCustom = false,
    String name = '',
    String price = '',
    String quantity = '1',
  })  : nameController = TextEditingController(text: name),
        priceController = TextEditingController(text: price),
        quantityController = TextEditingController(text: quantity);

  void dispose() {
    nameController.dispose();
    priceController.dispose();
    quantityController.dispose();
  }
}

class _OrderCreationSheetState extends State<OrderCreationSheet> {
  final _formKey = GlobalKey<FormState>();

  List<Map<String, dynamic>> _catalogProducts = [];
  bool _isLoadingProducts = true;

  final List<_OrderItem> _items = [];

  final _feeController = TextEditingController(text: '0');
  final _addressController = TextEditingController();
  final _dateController = TextEditingController();

  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadCatalog();
    if (widget.initialOrder != null) {
      final order = widget.initialOrder!;
      final details = order['order_details'] ?? {};
      final rawItems = details['items'] as List?;
      if (rawItems != null && rawItems.isNotEmpty) {
        for (final raw in rawItems) {
          final item = raw is Map ? raw : {};
          _items.add(_OrderItem(
            // order_details never persisted a product_id (only name/price/
            // quantity), so an edited order can't be re-linked to its
            // catalog entry — reopen every line as free text, same as the
            // single-item form already did before this change.
            useCustom: true,
            name: item['name']?.toString() ?? '',
            price: item['custom_price']?.toString() ?? item['price']?.toString() ?? '',
            quantity: item['quantity']?.toString() ?? '1',
          ));
        }
      }
      _feeController.text = details['additional_fee']?.toString() ?? '0';
      _addressController.text = details['delivery_address'] ?? '';
      _dateController.text = details['delivery_date'] ?? '';
    }
    if (_items.isEmpty) _items.add(_OrderItem());
  }

  @override
  void dispose() {
    for (final item in _items) {
      item.dispose();
    }
    _feeController.dispose();
    _addressController.dispose();
    _dateController.dispose();
    super.dispose();
  }

  Future<void> _loadCatalog() async {
    final prods = await ApiService().fetchProducts();
    if (mounted) {
      setState(() {
        _catalogProducts = prods;
        _isLoadingProducts = false;
        if (prods.isEmpty) {
          for (final item in _items) {
            item.useCustom = true;
          }
        }
      });
    }
  }

  Future<Map<String, dynamic>?> _openProductGridPicker() {
    return showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.75,
          maxChildSize: 0.95,
          minChildSize: 0.5,
          builder: (_, controller) => _OrderProductGridPicker(scrollController: controller),
        );
      },
    );
  }

  void _addItem() {
    setState(() => _items.add(_OrderItem(useCustom: _catalogProducts.isEmpty)));
  }

  void _removeItem(int index) {
    if (_items.length <= 1) return;
    setState(() {
      _items[index].dispose();
      _items.removeAt(index);
    });
  }

  double _itemSubtotal(_OrderItem item) {
    final qty = int.tryParse(item.quantityController.text) ?? 1;
    double unitPrice;
    if (item.useCustom || item.product == null) {
      unitPrice = double.tryParse(item.priceController.text) ?? 0.0;
    } else {
      unitPrice = double.tryParse(item.priceController.text.isNotEmpty
              ? item.priceController.text
              : (item.product!['price']?.toString() ?? '0')) ??
          0.0;
    }
    return qty * unitPrice;
  }

  double _calculateTotal() {
    final itemsTotal = _items.fold<double>(0.0, (sum, item) => sum + _itemSubtotal(item));
    final fee = double.tryParse(_feeController.text) ?? 0.0;
    return itemsTotal + fee;
  }

  Future<void> _submitOrder() async {
    if (!_formKey.currentState!.validate()) return;

    final items = _items.map((item) {
      final qty = int.tryParse(item.quantityController.text) ?? 1;
      if (item.useCustom || item.product == null) {
        final price = double.tryParse(item.priceController.text) ?? 0.0;
        return {
          'name': item.nameController.text.trim(),
          'quantity': qty,
          'custom_price': price,
        };
      }
      final String prodId = item.product?['_uid']?.toString() ?? item.product?['_id']?.toString() ?? '';
      final double price = double.tryParse(item.priceController.text) ??
          double.tryParse(item.product?['price']?.toString() ?? '0') ??
          0.0;
      return {
        if (prodId.isNotEmpty) 'product_id': prodId,
        'name': item.product?['name'] ?? 'Produit',
        'quantity': qty,
        'custom_price': price,
      };
    }).toList();

    final double fee = double.tryParse(_feeController.text) ?? 0.0;

    setState(() => _isSubmitting = true);

    final result = await ApiService().createManualOrder(
      contactId: widget.contactUid,
      items: items,
      additionalFee: fee,
      additionalFeeLabel: 'Livraison / Frais',
      deliveryAddress: _addressController.text.trim(),
      deliveryDate: _dateController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (result['success'] == true) {
      if (widget.initialOrder != null) {
        // Since there is no edit endpoint, we delete the old one
        await ApiService().deleteOrder(widget.initialOrder!['_uid']);
      }
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(widget.initialOrder != null ? '🛍️ Commande modifiée !' : '🛍️ Commande enregistrée pour ${widget.contactName} !'),
          backgroundColor: const Color(0xFF10B981),
        ),
      );
      widget.onOrderCreated?.call();
    } else {
      final msg = result['message'] ?? 'Erreur lors de la création de la commande.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(msg),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  Widget _buildItemCard(int index, _OrderItem item, bool isDark, Color primaryColor) {
    final hasCatalog = !_isLoadingProducts && _catalogProducts.isNotEmpty;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withValues(alpha: 0.03) : Colors.black.withValues(alpha: 0.02),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade300),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text('Produit ${index + 1}',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: isDark ? Colors.white54 : Colors.black45)),
              const Spacer(),
              if (_items.length > 1)
                InkWell(
                  onTap: () => _removeItem(index),
                  borderRadius: BorderRadius.circular(20),
                  child: const Padding(
                    padding: EdgeInsets.all(4),
                    child: Icon(Icons.close_rounded, color: Colors.redAccent, size: 18),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),

          // Catalogue vs Personnalisé toggle, per item
          if (hasCatalog) ...[
            Container(
              padding: const EdgeInsets.all(3),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: GestureDetector(
                      onTap: () => setState(() => item.useCustom = false),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: !item.useCustom ? primaryColor : Colors.transparent,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        alignment: Alignment.center,
                        child: Text('Catalogue',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: !item.useCustom ? Colors.white : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                            )),
                      ),
                    ),
                  ),
                  Expanded(
                    child: GestureDetector(
                      onTap: () => setState(() => item.useCustom = true),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: item.useCustom ? primaryColor : Colors.transparent,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        alignment: Alignment.center,
                        child: Text('Personnalisé',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: item.useCustom ? Colors.white : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                            )),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),
          ],

          // Product picker or custom name field
          if (!item.useCustom && hasCatalog)
            FormField<Map<String, dynamic>>(
              initialValue: item.product,
              validator: (val) {
                if (!item.useCustom && val == null) {
                  return 'Veuillez sélectionner un produit';
                }
                return null;
              },
              builder: (field) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    InkWell(
                      borderRadius: BorderRadius.circular(12),
                      onTap: () async {
                        final picked = await _openProductGridPicker();
                        if (picked == null) return;
                        setState(() {
                          item.product = picked;
                          if (picked['price'] != null) {
                            item.priceController.text = picked['price'].toString();
                          }
                        });
                        field.didChange(picked);
                      },
                      child: Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          border: Border.all(color: field.hasError ? Colors.red : (isDark ? Colors.white24 : Colors.grey.shade400)),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: item.product == null
                            ? Row(
                                children: [
                                  Icon(Icons.inventory_2_rounded, color: isDark ? Colors.white54 : Colors.black45),
                                  const SizedBox(width: 10),
                                  Text('Choisir un produit dans le catalogue', style: TextStyle(color: isDark ? Colors.white70 : Colors.black87)),
                                ],
                              )
                            : Row(
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(6),
                                    child: (item.product!['image_url'] != null && item.product!['image_url'].toString().isNotEmpty)
                                        ? Image.network(item.product!['image_url'], width: 38, height: 38, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const Icon(Icons.image, size: 38, color: Colors.grey))
                                        : Container(width: 38, height: 38, color: Colors.black12, child: const Icon(Icons.image, size: 18, color: Colors.grey)),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(item.product!['name'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontWeight: FontWeight.w700, color: isDark ? Colors.white : Colors.black87)),
                                        Text('${double.tryParse(item.product!['price']?.toString() ?? '0')?.toStringAsFixed(0) ?? 0} CFA', style: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF10B981), fontSize: 12)),
                                      ],
                                    ),
                                  ),
                                  Icon(Icons.swap_horiz_rounded, color: isDark ? Colors.white54 : Colors.black45, size: 20),
                                ],
                              ),
                      ),
                    ),
                    if (field.hasError)
                      Padding(
                        padding: const EdgeInsets.only(top: 6, left: 4),
                        child: Text(field.errorText ?? '', style: const TextStyle(color: Colors.red, fontSize: 12)),
                      ),
                  ],
                );
              },
            )
          else
            TextFormField(
              controller: item.nameController,
              decoration: InputDecoration(
                labelText: 'Nom du produit / service',
                hintText: 'ex: T-Shirt Coton Noir',
                prefixIcon: const Icon(Icons.card_giftcard_rounded),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                isDense: true,
              ),
              validator: (val) {
                if ((item.useCustom || !hasCatalog) && (val == null || val.trim().isEmpty)) {
                  return 'Saisissez le nom du produit';
                }
                return null;
              },
            ),
          const SizedBox(height: 10),

          // Quantity & unit price
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: item.quantityController,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: 'Quantité',
                    prefixIcon: const Icon(Icons.numbers_rounded),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    isDense: true,
                  ),
                  onChanged: (_) => setState(() {}),
                  validator: (val) {
                    if (val == null || int.tryParse(val) == null || int.parse(val) <= 0) {
                      return 'Min 1';
                    }
                    return null;
                  },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: TextFormField(
                  controller: item.priceController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: InputDecoration(
                    labelText: 'Prix unitaire (CFA)',
                    prefixIcon: const Icon(Icons.payments_rounded),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    isDense: true,
                  ),
                  onChanged: (_) => setState(() {}),
                  validator: (val) {
                    if (val == null || double.tryParse(val) == null || double.parse(val) < 0) {
                      return 'Prix valide requis';
                    }
                    return null;
                  },
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    final primaryColor = const Color(0xFF2DD4BF);

    return Container(
      padding: EdgeInsets.only(
        top: 20,
        left: 20,
        right: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkSurface : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: primaryColor.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(Icons.shopping_bag_rounded, color: primaryColor, size: 24),
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Enregistrer une commande',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                          ),
                          Text(
                            'Client: ${widget.contactName}',
                            style: TextStyle(
                              fontSize: 13,
                              color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  IconButton(
                    icon: const Icon(Icons.close_rounded),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // Product line items
              for (int i = 0; i < _items.length; i++) _buildItemCard(i, _items[i], isDark, primaryColor),

              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: _addItem,
                  icon: const Icon(Icons.add_circle_outline_rounded, size: 18),
                  label: const Text('Ajouter un produit', style: TextStyle(fontWeight: FontWeight.bold)),
                  style: TextButton.styleFrom(foregroundColor: primaryColor),
                ),
              ),
              const SizedBox(height: 8),

              // Shipping / Additional Fee
              TextFormField(
                controller: _feeController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: 'Frais de livraison / Additionnels (CFA)',
                  prefixIcon: const Icon(Icons.local_shipping_rounded),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onChanged: (_) => setState(() {}),
              ),
              const SizedBox(height: 14),

              // Delivery Address
              TextFormField(
                controller: _addressController,
                decoration: InputDecoration(
                  labelText: 'Adresse de livraison (optionnelle)',
                  hintText: 'ex: Cocody Angré 8ème tranche',
                  prefixIcon: const Icon(Icons.location_on_rounded),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 20),

              // Total Price Card Summary
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: primaryColor.withValues(alpha: 0.3)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'TOTAL DE LA COMMANDE',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    Text(
                      '${_calculateTotal().toStringAsFixed(0)} CFA',
                      style: TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 18,
                        color: primaryColor,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Submit Button
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton.icon(
                  onPressed: _isSubmitting ? null : _submitOrder,
                  icon: _isSubmitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Icon(Icons.check_circle_rounded),
                  label: Text(
                    _isSubmitting ? 'Enregistrement en cours...' : 'Valider et enregistrer la commande',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Image-grid product picker for order creation — mirrors the "Sélectionner
/// un produit" grid used to send a product in chat, but selects instead of
/// sending: tapping a card pops this sheet with that product as the result.
class _OrderProductGridPicker extends StatefulWidget {
  final ScrollController scrollController;

  const _OrderProductGridPicker({required this.scrollController});

  @override
  State<_OrderProductGridPicker> createState() => _OrderProductGridPickerState();
}

class _OrderProductGridPickerState extends State<_OrderProductGridPicker> {
  final _searchController = TextEditingController();
  List<Map<String, dynamic>> _products = [];
  bool _isLoading = true;
  String _searchQuery = '';
  Timer? _debounce;

  List<Map<String, dynamic>> _categories = [];
  // null = "Toutes", 'uncategorized' = sans catégorie, else a category _uid.
  String? _categoryFilter;

  @override
  void initState() {
    super.initState();
    _loadProducts();
    _loadCategories();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _loadProducts() async {
    setState(() => _isLoading = true);
    final products = await ApiService().fetchProducts(search: _searchQuery, categoryUid: _categoryFilter);
    if (mounted) {
      setState(() {
        _products = products;
        _isLoading = false;
      });
    }
  }

  Future<void> _loadCategories() async {
    final categories = await ApiService().fetchCategories();
    if (mounted) setState(() => _categories = categories);
  }

  Widget _categoryFilterChip({required String label, required String? value}) {
    final selected = _categoryFilter == value;
    return GestureDetector(
      onTap: () {
        setState(() => _categoryFilter = value);
        _loadProducts();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFF10B981) : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(20),
        ),
        alignment: Alignment.center,
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w700,
            color: selected ? Colors.white : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
          ),
        ),
      ),
    );
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      if (mounted) {
        setState(() => _searchQuery = query);
        _loadProducts();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              border: Border(bottom: BorderSide(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.06))),
            ),
            child: Row(
              children: [
                const Icon(Icons.shopping_bag_rounded, color: Color(0xFF10B981), size: 22),
                const SizedBox(width: 8),
                Text(
                  'Sélectionner un produit',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.onSurface),
                ),
                const Spacer(),
                IconButton(
                  icon: Icon(Icons.close_rounded, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.47)),
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
                hintText: 'Rechercher un produit...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          _onSearchChanged('');
                        },
                      )
                    : null,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: BorderSide(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.12)),
                ),
                contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
              ),
            ),
          ),
          if (_categories.isNotEmpty)
            SizedBox(
              height: 36,
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                scrollDirection: Axis.horizontal,
                children: [
                  _categoryFilterChip(label: 'Toutes', value: null),
                  const SizedBox(width: 6),
                  _categoryFilterChip(label: 'Sans catégorie', value: 'uncategorized'),
                  const SizedBox(width: 6),
                  for (final c in _categories) ...[
                    _categoryFilterChip(label: c['name']?.toString() ?? '', value: c['_uid']?.toString()),
                    const SizedBox(width: 6),
                  ],
                ],
              ),
            ),
          const SizedBox(height: 8),
          Expanded(
            child: _isLoading
                ? Center(child: CircularProgressIndicator(color: Theme.of(context).colorScheme.primary))
                : _products.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.shopping_bag_outlined, size: 56, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.16)),
                            const SizedBox(height: 16),
                            Text(
                              'Aucun produit trouvé',
                              style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.39), fontSize: 15),
                            ),
                          ],
                        ),
                      )
                    : GridView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        controller: widget.scrollController,
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                          childAspectRatio: 0.78,
                        ),
                        itemCount: _products.length,
                        itemBuilder: (context, index) {
                          final product = _products[index];
                          final String? imageUrl = product['image_url'];
                          final double price = double.tryParse(product['price']?.toString() ?? '0') ?? 0;

                          return GestureDetector(
                            onTap: () => Navigator.pop(context, product),
                            child: Container(
                              decoration: BoxDecoration(
                                color: Theme.of(context).cardColor,
                                borderRadius: BorderRadius.circular(16),
                                boxShadow: [
                                  BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10, offset: const Offset(0, 4)),
                                ],
                              ),
                              clipBehavior: Clip.antiAlias,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Container(
                                      width: double.infinity,
                                      color: Colors.black12,
                                      child: (imageUrl != null && imageUrl.isNotEmpty)
                                          ? Image.network(imageUrl, fit: BoxFit.cover, errorBuilder: (context, err, stack) => const Icon(Icons.shopping_cart, color: Colors.grey, size: 40))
                                          : const Icon(Icons.shopping_cart, color: Colors.grey, size: 40),
                                    ),
                                  ),
                                  Padding(
                                    padding: const EdgeInsets.all(10.0),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          product['name'] ?? 'Produit sans nom',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: TextStyle(fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.onSurface, fontSize: 13),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          '${price.toStringAsFixed(0)} CFA',
                                          style: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF10B981), fontSize: 14),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}

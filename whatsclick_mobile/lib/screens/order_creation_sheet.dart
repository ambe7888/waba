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

class _OrderCreationSheetState extends State<OrderCreationSheet> {
  final _formKey = GlobalKey<FormState>();

  List<Map<String, dynamic>> _catalogProducts = [];
  bool _isLoadingProducts = true;
  Map<String, dynamic>? _selectedProduct;

  final _customNameController = TextEditingController();
  final _priceController = TextEditingController();
  final _quantityController = TextEditingController(text: '1');
  final _feeController = TextEditingController(text: '0');
  final _addressController = TextEditingController();
  final _dateController = TextEditingController();

  bool _isSubmitting = false;
  bool _useCustomProduct = false;

  @override
  void initState() {
    super.initState();
    _loadCatalog();
    if (widget.initialOrder != null) {
      final order = widget.initialOrder!;
      final details = order['order_details'] ?? {};
      final items = details['items'] as List?;
      if (items != null && items.isNotEmpty) {
        final item = items[0];
        _customNameController.text = item['name'] ?? '';
        _priceController.text = item['custom_price']?.toString() ?? item['price']?.toString() ?? '';
        _quantityController.text = item['quantity']?.toString() ?? '1';
        _useCustomProduct = true;
      }
      _feeController.text = details['additional_fee']?.toString() ?? '0';
      _addressController.text = details['delivery_address'] ?? '';
      _dateController.text = details['delivery_date'] ?? '';
    }
  }

  @override
  void dispose() {
    _customNameController.dispose();
    _priceController.dispose();
    _quantityController.dispose();
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
          _useCustomProduct = true;
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

  double _calculateTotal() {
    final qty = int.tryParse(_quantityController.text) ?? 1;
    double unitPrice = 0.0;
    if (_useCustomProduct || _selectedProduct == null) {
      unitPrice = double.tryParse(_priceController.text) ?? 0.0;
    } else {
      unitPrice = double.tryParse(_priceController.text.isNotEmpty
              ? _priceController.text
              : (_selectedProduct!['price']?.toString() ?? '0')) ??
          0.0;
    }
    final fee = double.tryParse(_feeController.text) ?? 0.0;
    return (qty * unitPrice) + fee;
  }

  Future<void> _submitOrder() async {
    if (!_formKey.currentState!.validate()) return;

    final String prodId = _selectedProduct?['_uid'] ?? _selectedProduct?['_id'] ?? '';
    final String prodName = _useCustomProduct || _selectedProduct == null
        ? _customNameController.text.trim()
        : (_selectedProduct!['name'] ?? 'Produit');
    final double price = double.tryParse(_priceController.text) ??
        double.tryParse(_selectedProduct?['price']?.toString() ?? '0') ??
        0.0;
    final int qty = int.tryParse(_quantityController.text) ?? 1;
    final double fee = double.tryParse(_feeController.text) ?? 0.0;

    setState(() => _isSubmitting = true);

    final items = [
      {
        if (prodId.isNotEmpty) 'product_id': prodId,
        'name': prodName,
        'quantity': qty,
        'custom_price': price,
      }
    ];

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

              // Catalog vs Custom Toggle
              if (!_isLoadingProducts && _catalogProducts.isNotEmpty) ...[
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: GestureDetector(
                          onTap: () => setState(() => _useCustomProduct = false),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            decoration: BoxDecoration(
                              color: !_useCustomProduct ? primaryColor : Colors.transparent,
                              borderRadius: BorderRadius.circular(10),
                              boxShadow: !_useCustomProduct
                                  ? [const BoxShadow(color: Colors.black12, blurRadius: 4, offset: Offset(0, 2))]
                                  : null,
                            ),
                            alignment: Alignment.center,
                            child: Text(
                              'Catalogue',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: !_useCustomProduct ? Colors.white : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                              ),
                            ),
                          ),
                        ),
                      ),
                      Expanded(
                        child: GestureDetector(
                          onTap: () => setState(() => _useCustomProduct = true),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            decoration: BoxDecoration(
                              color: _useCustomProduct ? primaryColor : Colors.transparent,
                              borderRadius: BorderRadius.circular(10),
                              boxShadow: _useCustomProduct
                                  ? [const BoxShadow(color: Colors.black12, blurRadius: 4, offset: Offset(0, 2))]
                                  : null,
                            ),
                            alignment: Alignment.center,
                            child: Text(
                              'Personnalisé',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: _useCustomProduct ? Colors.white : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
              ],

              // Product Picker — opens the same image-grid picker used to
              // send a product in the chat, instead of a plain text dropdown.
              if (!_useCustomProduct && _catalogProducts.isNotEmpty) ...[
                FormField<Map<String, dynamic>>(
                  initialValue: _selectedProduct,
                  validator: (val) {
                    if (!_useCustomProduct && val == null) {
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
                              _selectedProduct = picked;
                              if (picked['price'] != null) {
                                _priceController.text = picked['price'].toString();
                              }
                            });
                            field.didChange(picked);
                          },
                          child: Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              border: Border.all(color: field.hasError ? Colors.red : (isDark ? Colors.white24 : Colors.grey.shade400)),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: _selectedProduct == null
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
                                        child: (_selectedProduct!['image_url'] != null && _selectedProduct!['image_url'].toString().isNotEmpty)
                                            ? Image.network(_selectedProduct!['image_url'], width: 40, height: 40, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const Icon(Icons.image, size: 40, color: Colors.grey))
                                            : Container(width: 40, height: 40, color: Colors.black12, child: const Icon(Icons.image, size: 20, color: Colors.grey)),
                                      ),
                                      const SizedBox(width: 10),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(_selectedProduct!['name'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontWeight: FontWeight.w700, color: isDark ? Colors.white : Colors.black87)),
                                            Text('${double.tryParse(_selectedProduct!['price']?.toString() ?? '0')?.toStringAsFixed(0) ?? 0} CFA', style: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF10B981), fontSize: 12.5)),
                                          ],
                                        ),
                                      ),
                                      Icon(Icons.swap_horiz_rounded, color: isDark ? Colors.white54 : Colors.black45),
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
                ),
                const SizedBox(height: 14),
              ],

              // Custom Product Name Field
              if (_useCustomProduct || _catalogProducts.isEmpty) ...[
                TextFormField(
                  controller: _customNameController,
                  decoration: InputDecoration(
                    labelText: 'Nom du produit / service',
                    hintText: 'ex: T-Shirt Coton Noir',
                    prefixIcon: const Icon(Icons.card_giftcard_rounded),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  validator: (val) {
                    if ((_useCustomProduct || _catalogProducts.isEmpty) && (val == null || val.trim().isEmpty)) {
                      return 'Saisissez le nom du produit';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 14),
              ],

              // Quantity & Unit Price Row
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _quantityController,
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: 'Quantité',
                        prefixIcon: const Icon(Icons.numbers_rounded),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
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
                      controller: _priceController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: 'Prix unitaire (CFA)',
                        prefixIcon: const Icon(Icons.payments_rounded),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
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
              const SizedBox(height: 14),

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

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _loadProducts() async {
    setState(() => _isLoading = true);
    final products = await ApiService().fetchProducts(search: _searchQuery);
    if (mounted) {
      setState(() {
        _products = products;
        _isLoading = false;
      });
    }
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

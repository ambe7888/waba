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

              // Product Picker Dropdown
              if (!_useCustomProduct && _catalogProducts.isNotEmpty) ...[
                DropdownButtonFormField<Map<String, dynamic>>(
                  initialValue: _selectedProduct,
                  decoration: InputDecoration(
                    labelText: 'Sélectionner un produit',
                    prefixIcon: const Icon(Icons.inventory_2_rounded),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  isExpanded: true,
                  items: _catalogProducts.map((p) {
                    final img = p['image_url'];
                    return DropdownMenuItem<Map<String, dynamic>>(
                      value: p,
                      child: Row(
                        children: [
                          if (img != null && img.toString().isNotEmpty) ...[
                            ClipRRect(
                              borderRadius: BorderRadius.circular(4),
                              child: Image.network(img, width: 30, height: 30, fit: BoxFit.cover, errorBuilder: (_,__,___) => const Icon(Icons.image, size: 30, color: Colors.grey)),
                            ),
                            const SizedBox(width: 10),
                          ],
                          Expanded(
                            child: Text(
                              p['name'] ?? '',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                            ),
                          ),
                          Text(
                            '${double.tryParse(p['price']?.toString() ?? '0')?.toStringAsFixed(0) ?? 0} CFA',
                            style: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF10B981), fontSize: 13),
                          ),
                        ],
                      ),
                    );
                  }).toList(),
                  onChanged: (val) {
                    setState(() {
                      _selectedProduct = val;
                      if (val != null && val['price'] != null) {
                        _priceController.text = val['price'].toString();
                      }
                    });
                  },
                  validator: (val) {
                    if (!_useCustomProduct && val == null) {
                      return 'Veuillez sélectionner un produit';
                    }
                    return null;
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

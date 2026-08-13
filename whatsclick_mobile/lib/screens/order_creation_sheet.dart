import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class OrderCreationSheet extends StatefulWidget {
  final String contactUid;
  final String contactName;
  final VoidCallback? onOrderCreated;

  const OrderCreationSheet({
    super.key,
    required this.contactUid,
    required this.contactName,
    this.onOrderCreated,
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
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('🛍️ Commande enregistrée pour ${widget.contactName} !'),
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
                Row(
                  children: [
                    ChoiceChip(
                      label: const Text('Depuis le catalogue'),
                      selected: !_useCustomProduct,
                      selectedColor: primaryColor.withValues(alpha: 0.2),
                      onSelected: (selected) {
                        if (selected) {
                          setState(() {
                            _useCustomProduct = false;
                          });
                        }
                      },
                    ),
                    const SizedBox(width: 10),
                    ChoiceChip(
                      label: const Text('Produit personnalisé'),
                      selected: _useCustomProduct,
                      selectedColor: primaryColor.withValues(alpha: 0.2),
                      onSelected: (selected) {
                        if (selected) {
                          setState(() {
                            _useCustomProduct = true;
                          });
                        }
                      },
                    ),
                  ],
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
                  items: _catalogProducts.map((p) {
                    return DropdownMenuItem<Map<String, dynamic>>(
                      value: p,
                      child: Text('${p['name']} (${p['price']} CFA)'),
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

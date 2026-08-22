import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

/// "Paramètres boutique" — catalog ID / Shopify / WooCommerce integration,
/// plus a lightweight product catalog manager (list, add, delete). Order
/// management (a global sales dashboard across all contacts) is a separate,
/// larger feature and intentionally out of scope here — this screen is
/// shop *settings* and the product catalog specifically.
class ShopSettingsScreen extends StatefulWidget {
  const ShopSettingsScreen({super.key});

  @override
  State<ShopSettingsScreen> createState() => _ShopSettingsScreenState();
}

class _ShopSettingsScreenState extends State<ShopSettingsScreen> {
  bool _isLoading = true;
  bool _loadFailed = false;
  bool _isFeatureAvailable = true;

  String _integration = 'none';
  bool _shopifyConfigured = false;
  bool _woocommerceConfigured = false;
  final _catalogIdController = TextEditingController();
  final _shopifyUrlController = TextEditingController();
  final _woocommerceUrlController = TextEditingController();
  bool _savingSettings = false;
  bool _syncing = false;

  bool _isLoadingProducts = true;
  List<Map<String, dynamic>> _products = [];

  @override
  void initState() {
    super.initState();
    _load();
    _loadProducts();
  }

  @override
  void dispose() {
    _catalogIdController.dispose();
    _shopifyUrlController.dispose();
    _woocommerceUrlController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _loadFailed = false;
    });
    final data = await ApiService().fetchShopSettings();
    if (!mounted) return;
    if (data == null) {
      setState(() {
        _isLoading = false;
        _loadFailed = true;
      });
      return;
    }
    setState(() {
      _isFeatureAvailable = data['is_feature_available'] == true;
      _integration = data['ecommerce_integration']?.toString() ?? 'none';
      _catalogIdController.text = data['whatsapp_catalog_id']?.toString() ?? '';
      _shopifyUrlController.text = data['shopify_shop_url']?.toString() ?? '';
      _shopifyConfigured = data['shopify_configured'] == true;
      _woocommerceUrlController.text = data['woocommerce_shop_url']?.toString() ?? '';
      _woocommerceConfigured = data['woocommerce_configured'] == true;
      _isLoading = false;
    });
  }

  Future<void> _loadProducts() async {
    setState(() => _isLoadingProducts = true);
    final products = await ApiService().fetchProducts();
    if (!mounted) return;
    setState(() {
      _products = products;
      _isLoadingProducts = false;
    });
  }

  void _showMessage(String message, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: isError ? Colors.red : const Color(0xFF10B981)),
    );
  }

  Future<void> _saveSettings() async {
    setState(() => _savingSettings = true);
    final result = await ApiService().saveShopSettings({
      'ecommerce_integration': _integration,
      'whatsapp_catalog_id': _catalogIdController.text.trim(),
      'shopify_shop_url': _shopifyUrlController.text.trim(),
      'woocommerce_shop_url': _woocommerceUrlController.text.trim(),
    });
    if (!mounted) return;
    setState(() => _savingSettings = false);
    _showMessage(
      result['success'] == true
          ? (result['message']?.toString().isNotEmpty == true ? result['message'] : 'Enregistré avec succès.')
          : (result['message']?.toString() ?? 'Erreur lors de l\'enregistrement.'),
      isError: result['success'] != true,
    );
  }

  Future<void> _sync() async {
    if (_integration == 'none') {
      _showMessage('Choisissez une intégration (Shopify ou WooCommerce) avant de synchroniser.', isError: true);
      return;
    }
    setState(() => _syncing = true);
    final result = await ApiService().syncProducts(_integration);
    if (!mounted) return;
    setState(() => _syncing = false);
    _showMessage(
      result['message']?.toString() ?? (result['success'] == true ? 'Synchronisation réussie.' : 'Échec de la synchronisation.'),
      isError: result['success'] != true,
    );
    if (result['success'] == true) _loadProducts();
  }

  Future<void> _confirmDeleteProduct(Map<String, dynamic> product) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Supprimer ce produit ?'),
        content: Text(product['name']?.toString() ?? ''),
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
    final uid = product['_uid']?.toString();
    if (uid == null) return;
    final success = await ApiService().deleteProduct(uid);
    if (!mounted) return;
    if (success) {
      setState(() => _products.removeWhere((p) => p['_uid'] == uid));
      _showMessage('Produit supprimé.');
    } else {
      _showMessage('Impossible de supprimer ce produit.', isError: true);
    }
  }

  Future<void> _openAddProductSheet() async {
    final nameController = TextEditingController();
    final priceController = TextEditingController();
    final descController = TextEditingController();
    final imageUrlController = TextEditingController();
    final linkController = TextEditingController();
    bool submitting = false;

    final isDark = ThemeService().isDark;
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1E293B) : Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(16))),
      builder: (sheetContext) {
        return StatefulBuilder(builder: (sheetContext, setSheetState) {
          return Padding(
            padding: EdgeInsets.only(
              left: 20, right: 20, top: 20,
              bottom: MediaQuery.of(sheetContext).viewInsets.bottom + 20,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Ajouter un produit',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                  const SizedBox(height: 16),
                  TextField(controller: nameController, decoration: const InputDecoration(labelText: 'Nom du produit *', border: OutlineInputBorder())),
                  const SizedBox(height: 12),
                  TextField(
                    controller: priceController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(labelText: 'Prix *', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: descController,
                    maxLines: 2,
                    decoration: const InputDecoration(labelText: 'Description', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: imageUrlController,
                    decoration: const InputDecoration(labelText: 'URL de l\'image (facultatif)', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: linkController,
                    decoration: const InputDecoration(labelText: 'Lien direct (facultatif)', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: submitting
                          ? null
                          : () async {
                              final name = nameController.text.trim();
                              final price = priceController.text.trim();
                              if (name.isEmpty || price.isEmpty) {
                                ScaffoldMessenger.of(sheetContext).showSnackBar(
                                  const SnackBar(content: Text('Le nom et le prix sont obligatoires.')),
                                );
                                return;
                              }
                              setSheetState(() => submitting = true);
                              final result = await ApiService().addProduct(
                                name: name,
                                price: price,
                                description: descController.text.trim(),
                                imageUrl: imageUrlController.text.trim(),
                                directLink: linkController.text.trim(),
                              );
                              if (!sheetContext.mounted) return;
                              if (result['success'] == true) {
                                Navigator.pop(sheetContext);
                                _showMessage('Produit ajouté.');
                                _loadProducts();
                              } else {
                                setSheetState(() => submitting = false);
                                ScaffoldMessenger.of(sheetContext).showSnackBar(
                                  SnackBar(content: Text(result['message']?.toString() ?? 'Erreur.'), backgroundColor: Colors.red),
                                );
                              }
                            },
                      style: ElevatedButton.styleFrom(backgroundColor: ThemeService.primaryColor, foregroundColor: Colors.white),
                      child: submitting
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Text('Ajouter'),
                    ),
                  ),
                ],
              ),
            ),
          );
        });
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: const Text('Paramètres boutique', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _loadFailed
              ? _buildLoadError(isDark)
              : RefreshIndicator(
                  onRefresh: () async {
                    await Future.wait([_load(), _loadProducts()]);
                  },
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      if (!_isFeatureAvailable) _buildUpgradeNotice(isDark) else ...[
                        _buildIntegrationSection(isDark),
                        const SizedBox(height: 16),
                        _buildProductsSection(isDark),
                      ],
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
    );
  }

  Widget _buildLoadError(bool isDark) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.error_outline_rounded, size: 40, color: isDark ? Colors.white38 : Colors.black26),
            const SizedBox(height: 12),
            Text('Impossible de charger les paramètres boutique.', style: TextStyle(color: isDark ? Colors.white70 : Colors.black54)),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: _load, child: const Text('Réessayer')),
          ],
        ),
      ),
    );
  }

  Widget _buildUpgradeNotice(bool isDark) {
    return Container(
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
    );
  }

  Widget _card(bool isDark, {required Widget child}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
      ),
      child: child,
    );
  }

  Widget _buildIntegrationSection(bool isDark) {
    return _card(
      isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.storefront_rounded, size: 18, color: ThemeService.primaryColor),
              const SizedBox(width: 8),
              Text('Boutique & Catalogue',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: isDark ? Colors.white : Colors.black87)),
            ],
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _catalogIdController,
            decoration: const InputDecoration(
              labelText: 'ID du catalogue WhatsApp',
              hintText: 'ex. 128392193892182',
              border: OutlineInputBorder(),
              isDense: true,
            ),
          ),
          const SizedBox(height: 16),
          Text('Source des produits', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: isDark ? Colors.white : Colors.black87)),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              _integrationChip('Aucune (manuel)', 'none', isDark),
              _integrationChip('Shopify', 'shopify', isDark),
              _integrationChip('WooCommerce', 'woocommerce', isDark),
            ],
          ),
          if (_integration == 'shopify') ...[
            const SizedBox(height: 14),
            TextField(
              controller: _shopifyUrlController,
              decoration: InputDecoration(
                labelText: 'URL de la boutique Shopify',
                hintText: 'ex. maboutique.myshopify.com',
                border: const OutlineInputBorder(),
                isDense: true,
                suffixIcon: _shopifyConfigured ? const Icon(Icons.check_circle_rounded, color: ThemeService.primaryColor) : null,
              ),
            ),
          ],
          if (_integration == 'woocommerce') ...[
            const SizedBox(height: 14),
            TextField(
              controller: _woocommerceUrlController,
              decoration: InputDecoration(
                labelText: 'URL de la boutique WooCommerce',
                hintText: 'ex. https://maboutique.com',
                border: const OutlineInputBorder(),
                isDense: true,
                suffixIcon: _woocommerceConfigured ? const Icon(Icons.check_circle_rounded, color: ThemeService.primaryColor) : null,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Les clés API WooCommerce se configurent sur le site web (Paramètres > Boutique).',
              style: TextStyle(fontSize: 11.5, color: isDark ? Colors.white54 : Colors.black45),
            ),
          ],
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _savingSettings ? null : _saveSettings,
                  icon: _savingSettings
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.save_rounded, size: 18),
                  label: const Text('Enregistrer'),
                  style: ElevatedButton.styleFrom(backgroundColor: ThemeService.primaryColor, foregroundColor: Colors.white),
                ),
              ),
              if (_integration != 'none') ...[
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _syncing ? null : _sync,
                    icon: _syncing
                        ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                        : const Icon(Icons.sync_rounded, size: 18),
                    label: const Text('Synchroniser'),
                    style: OutlinedButton.styleFrom(foregroundColor: ThemeService.primaryColor, side: BorderSide(color: ThemeService.primaryColor)),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _integrationChip(String label, String value, bool isDark) {
    final selected = _integration == value;
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => setState(() => _integration = value),
      selectedColor: ThemeService.primaryColor.withValues(alpha: 0.15),
      labelStyle: TextStyle(
        color: selected ? ThemeService.primaryColor : (isDark ? Colors.white70 : Colors.black87),
        fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
      ),
      side: BorderSide(color: selected ? ThemeService.primaryColor : (isDark ? Colors.white24 : Colors.grey.shade400)),
    );
  }

  Widget _buildProductsSection(bool isDark) {
    return _card(
      isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.inventory_2_rounded, size: 18, color: ThemeService.primaryColor),
              const SizedBox(width: 8),
              Expanded(
                child: Text('Produits (${_products.length})',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: isDark ? Colors.white : Colors.black87)),
              ),
              TextButton.icon(
                onPressed: _openAddProductSheet,
                icon: const Icon(Icons.add_rounded, size: 18),
                label: const Text('Ajouter'),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (_isLoadingProducts)
            const Padding(padding: EdgeInsets.symmetric(vertical: 20), child: Center(child: CircularProgressIndicator(strokeWidth: 2)))
          else if (_products.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Center(
                child: Text('Aucun produit pour le moment.', style: TextStyle(color: isDark ? Colors.white54 : Colors.black45)),
              ),
            )
          else
            ..._products.map((p) => _buildProductTile(p, isDark)),
        ],
      ),
    );
  }

  Widget _buildProductTile(Map<String, dynamic> product, bool isDark) {
    final imageUrl = product['image_url']?.toString();
    final price = double.tryParse(product['price']?.toString() ?? '') ?? 0;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: (imageUrl != null && imageUrl.isNotEmpty)
                ? Image.network(imageUrl, width: 44, height: 44, fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => _productPlaceholder())
                : _productPlaceholder(),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(product['name']?.toString() ?? '', style: TextStyle(fontWeight: FontWeight.w700, color: isDark ? Colors.white : Colors.black87)),
                const SizedBox(height: 2),
                Text('${price.toStringAsFixed(0)} CFA', style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : Colors.black54)),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 20),
            onPressed: () => _confirmDeleteProduct(product),
          ),
        ],
      ),
    );
  }

  Widget _productPlaceholder() {
    return Container(
      width: 44,
      height: 44,
      color: Colors.grey.withValues(alpha: 0.2),
      child: const Icon(Icons.image_not_supported_outlined, size: 18, color: Colors.grey),
    );
  }
}

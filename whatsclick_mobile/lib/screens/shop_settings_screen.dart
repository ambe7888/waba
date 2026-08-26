import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

/// "Paramètres boutique" — catalog ID / Shopify / WooCommerce integration,
/// plus a lightweight product catalog manager (list, add, delete). Order
/// management now lives in its own screen (OrdersManagementScreen) reached
/// from the Boutique section in Account, not here — this screen stays shop
/// *settings* and the product catalog specifically.
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

  List<Map<String, dynamic>> _categories = [];
  // null = "Toutes", 'uncategorized' = no category assigned, else a category _uid.
  String? _categoryFilter;

  @override
  void initState() {
    super.initState();
    _load();
    _loadCategories();
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
    final products = await ApiService().fetchProducts(categoryUid: _categoryFilter);
    if (!mounted) return;
    setState(() {
      _products = products;
      _isLoadingProducts = false;
    });
  }

  Future<void> _loadCategories() async {
    final categories = await ApiService().fetchCategories();
    if (!mounted) return;
    setState(() => _categories = categories);
  }

  Future<void> _openManageCategoriesSheet() async {
    final newNameController = TextEditingController();
    final isDark = ThemeService().isDark;
    bool submitting = false;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1E293B) : Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(16))),
      builder: (sheetContext) {
        return StatefulBuilder(builder: (sheetContext, setSheetState) {
          Future<void> addCategory() async {
            final name = newNameController.text.trim();
            if (name.isEmpty) return;
            setSheetState(() => submitting = true);
            final result = await ApiService().addCategory(name);
            if (!sheetContext.mounted) return;
            if (result['success'] == true) {
              newNameController.clear();
              await _loadCategories();
              setSheetState(() => submitting = false);
            } else {
              setSheetState(() => submitting = false);
              ScaffoldMessenger.of(sheetContext).showSnackBar(
                SnackBar(content: Text(result['message']?.toString() ?? 'Erreur.'), backgroundColor: Colors.red),
              );
            }
          }

          Future<void> deleteCategory(Map<String, dynamic> category) async {
            final confirm = await showDialog<bool>(
              context: sheetContext,
              builder: (ctx) => AlertDialog(
                title: const Text('Supprimer cette catégorie ?'),
                content: Text('Les produits de "${category['name']}" deviendront non catégorisés.'),
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
            final uid = category['_uid']?.toString();
            if (uid == null) return;
            final success = await ApiService().deleteCategory(uid);
            if (!sheetContext.mounted) return;
            if (success) {
              if (_categoryFilter == uid) {
                _categoryFilter = null;
                _loadProducts();
              }
              await _loadCategories();
              setSheetState(() {});
            }
          }

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
                  Text('Catégories de produits',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? Colors.white : Colors.black87)),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: newNameController,
                          decoration: const InputDecoration(labelText: 'Nouvelle catégorie', border: OutlineInputBorder()),
                          onSubmitted: (_) => addCategory(),
                        ),
                      ),
                      const SizedBox(width: 10),
                      IconButton.filled(
                        onPressed: submitting ? null : addCategory,
                        icon: submitting
                            ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : const Icon(Icons.add_rounded),
                        style: IconButton.styleFrom(backgroundColor: ThemeService.primaryColor, foregroundColor: Colors.white),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  if (_categories.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: Text('Aucune catégorie pour le moment.', style: TextStyle(color: isDark ? Colors.white54 : Colors.black45)),
                    )
                  else
                    ..._categories.map((c) => Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          decoration: BoxDecoration(
                            color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: Text(
                                  '${c['name']} (${c['products_count'] ?? 0})',
                                  style: TextStyle(fontWeight: FontWeight.w600, color: isDark ? Colors.white : Colors.black87),
                                ),
                              ),
                              IconButton(
                                icon: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 20),
                                onPressed: () => deleteCategory(c),
                              ),
                            ],
                          ),
                        )),
                ],
              ),
            ),
          );
        });
      },
    );
    newNameController.dispose();
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
      _loadCategories();
    } else {
      _showMessage('Impossible de supprimer ce produit.', isError: true);
    }
  }

  Future<void> _openAddProductSheet() async {
    final nameController = TextEditingController();
    final priceController = TextEditingController();
    final descController = TextEditingController();
    final linkController = TextEditingController();
    File? pickedImage;
    bool submitting = false;
    String? selectedCategoryUid = (_categoryFilter != null && _categoryFilter != 'uncategorized') ? _categoryFilter : null;

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
                  // A URL text field forced people to already have a hosted
                  // image somewhere else first — most vendors just have the
                  // photo on their phone. Pick an actual file instead.
                  InkWell(
                    onTap: () async {
                      final result = await FilePicker.platform.pickFiles(type: FileType.image);
                      final path = result?.files.single.path;
                      if (path != null) setSheetState(() => pickedImage = File(path));
                    },
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        border: Border.all(color: isDark ? Colors.white24 : Colors.grey.shade400),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        children: [
                          if (pickedImage != null)
                            ClipRRect(
                              borderRadius: BorderRadius.circular(6),
                              child: Image.file(pickedImage!, width: 40, height: 40, fit: BoxFit.cover),
                            )
                          else
                            Icon(Icons.add_photo_alternate_outlined, color: isDark ? Colors.white54 : Colors.black54),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              pickedImage != null ? 'Image sélectionnée' : 'Choisir une image (facultatif)',
                              style: TextStyle(color: isDark ? Colors.white70 : Colors.black87, fontSize: 13),
                            ),
                          ),
                          if (pickedImage != null)
                            IconButton(
                              icon: const Icon(Icons.close_rounded, size: 18),
                              onPressed: () => setSheetState(() => pickedImage = null),
                            ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (_categories.isNotEmpty) ...[
                    DropdownButtonFormField<String?>(
                      initialValue: selectedCategoryUid,
                      decoration: const InputDecoration(labelText: 'Catégorie (facultatif)', border: OutlineInputBorder()),
                      items: [
                        const DropdownMenuItem<String?>(value: null, child: Text('Aucune')),
                        ..._categories.map((c) => DropdownMenuItem<String?>(
                              value: c['_uid']?.toString(),
                              child: Text(c['name']?.toString() ?? ''),
                            )),
                      ],
                      onChanged: (val) => setSheetState(() => selectedCategoryUid = val),
                    ),
                    const SizedBox(height: 12),
                  ],
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
                                imageFile: pickedImage,
                                directLink: linkController.text.trim(),
                                categoryUid: selectedCategoryUid,
                              );
                              if (!sheetContext.mounted) return;
                              if (result['success'] == true) {
                                Navigator.pop(sheetContext);
                                _showMessage('Produit ajouté.');
                                _loadProducts();
                                _loadCategories();
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
    nameController.dispose();
    priceController.dispose();
    descController.dispose();
    linkController.dispose();
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
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(child: _integrationCard('Manuel', 'none', Icons.edit_note_rounded, const Color(0xFF64748B), isDark)),
              const SizedBox(width: 10),
              Expanded(child: _integrationCard('Shopify', 'shopify', Icons.shopping_bag_rounded, const Color(0xFF95BF47), isDark)),
              const SizedBox(width: 10),
              Expanded(child: _integrationCard('WooCommerce', 'woocommerce', Icons.storefront_rounded, const Color(0xFF7F54B3), isDark)),
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

  Widget _integrationCard(String label, String value, IconData icon, Color brandColor, bool isDark) {
    final selected = _integration == value;
    return InkWell(
      onTap: () => setState(() => _integration = value),
      borderRadius: BorderRadius.circular(12),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
        decoration: BoxDecoration(
          color: selected ? brandColor.withValues(alpha: isDark ? 0.18 : 0.1) : (isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB)),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: selected ? brandColor : (isDark ? Colors.white12 : Colors.grey.shade300), width: selected ? 1.6 : 1),
        ),
        child: Column(
          children: [
            Icon(icon, color: selected ? brandColor : (isDark ? Colors.white54 : Colors.black45), size: 26),
            const SizedBox(height: 8),
            Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 12,
                fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
                color: selected ? brandColor : (isDark ? Colors.white70 : Colors.black87),
              ),
            ),
          ],
        ),
      ),
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
                onPressed: _openManageCategoriesSheet,
                icon: const Icon(Icons.sell_outlined, size: 18),
                label: const Text('Catégories'),
              ),
              TextButton.icon(
                onPressed: _openAddProductSheet,
                icon: const Icon(Icons.add_rounded, size: 18),
                label: const Text('Ajouter'),
              ),
            ],
          ),
          if (_categories.isNotEmpty) ...[
            const SizedBox(height: 4),
            SizedBox(
              height: 34,
              child: ListView(
                scrollDirection: Axis.horizontal,
                children: [
                  _categoryChip(label: 'Toutes', value: null, isDark: isDark),
                  const SizedBox(width: 6),
                  _categoryChip(label: 'Sans catégorie', value: 'uncategorized', isDark: isDark),
                  const SizedBox(width: 6),
                  for (final c in _categories) ...[
                    _categoryChip(label: c['name']?.toString() ?? '', value: c['_uid']?.toString(), isDark: isDark),
                    const SizedBox(width: 6),
                  ],
                ],
              ),
            ),
          ],
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

  Widget _categoryChip({required String label, required String? value, required bool isDark}) {
    final selected = _categoryFilter == value;
    return GestureDetector(
      onTap: () {
        setState(() => _categoryFilter = value);
        _loadProducts();
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? ThemeService.primaryColor : (isDark ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9)),
          borderRadius: BorderRadius.circular(20),
        ),
        alignment: Alignment.center,
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w700,
            color: selected ? Colors.white : (isDark ? Colors.white70 : Colors.black87),
          ),
        ),
      ),
    );
  }

  Widget _buildProductTile(Map<String, dynamic> product, bool isDark) {
    final imageUrl = product['image_url']?.toString();
    final price = double.tryParse(product['price']?.toString() ?? '') ?? 0;
    final categoryName = (product['category'] is Map) ? product['category']['name']?.toString() : null;
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
                Row(
                  children: [
                    Text('${price.toStringAsFixed(0)} CFA', style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : Colors.black54)),
                    if (categoryName != null && categoryName.isNotEmpty) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: ThemeService.primaryColor.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(categoryName, style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: ThemeService.primaryColor)),
                      ),
                    ],
                  ],
                ),
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

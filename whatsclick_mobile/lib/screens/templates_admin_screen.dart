import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class TemplatesAdminScreen extends StatefulWidget {
  const TemplatesAdminScreen({super.key});

  @override
  State<TemplatesAdminScreen> createState() => _TemplatesAdminScreenState();
}

class _TemplatesAdminScreenState extends State<TemplatesAdminScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  List<Map<String, dynamic>> _allTemplates = [];
  List<Map<String, dynamic>> _filteredTemplates = [];
  bool _isLoading = true;
  bool _isSyncing = false;
  String _searchQuery = '';
  String _selectedCategory = 'ALL';
  // Separate "simple" (non-Meta) from "meta" templates by checking presence of status
  bool _showMetaOnly = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      setState(() => _showMetaOnly = _tabController.index == 0);
      _filterTemplates();
    });
    _loadTemplates();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadTemplates() async {
    setState(() => _isLoading = true);
    final templates = await ApiService().fetchTemplates();
    if (mounted) {
      setState(() {
        _allTemplates = templates;
        _filterTemplates();
        _isLoading = false;
      });
    }
  }

  void _filterTemplates() {
    setState(() {
      _filteredTemplates = _allTemplates.where((t) {
        final matchesSearch = (t['template_name'] ?? '')
            .toString()
            .toLowerCase()
            .contains(_searchQuery.toLowerCase());
        final category = t['category'] ?? '';
        final matchesCategory = _selectedCategory == 'ALL' || category == _selectedCategory;
        // Tab 0 → Meta templates (have a 'status' from Meta API)
        // Tab 1 → Simple templates (no Meta status)
        final isMeta = (t['status'] != null && t['status'].toString().isNotEmpty);
        final matchesTab = _showMetaOnly ? isMeta : !isMeta;
        return matchesSearch && matchesCategory && matchesTab;
      }).toList();
    });
  }

  Future<void> _syncTemplates() async {
    setState(() => _isSyncing = true);
    final success = await ApiService().syncTemplates();
    if (mounted) {
      setState(() => _isSyncing = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success
              ? 'Modèles synchronisés depuis Meta !'
              : 'Échec de la synchronisation.'),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
      if (success) _loadTemplates();
    }
  }

  void _showCreateTemplateSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => CreateTemplateSheet(isMeta: _showMetaOnly),
    ).then((created) {
      if (created == true) _loadTemplates();
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Modèles de messages', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          if (_showMetaOnly)
            _isSyncing
                ? const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 16),
                    child: Center(
                        child: SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))),
                  )
                : IconButton(
                    icon: const Icon(Icons.sync_rounded),
                    tooltip: 'Synchroniser depuis Meta',
                    onPressed: _syncTemplates,
                  ),
        ],
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(icon: Icon(Icons.cloud_rounded), text: 'Meta'),
            Tab(icon: Icon(Icons.message_outlined), text: 'Simples'),
          ],
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Rechercher un modèle...',
                prefixIcon: const Icon(Icons.search_rounded),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
              ),
              onChanged: (value) {
                _searchQuery = value;
                _filterTemplates();
              },
            ),
          ),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            child: Row(
              children: [
                _categoryChip('ALL', 'Tous'),
                _categoryChip('MARKETING', 'Marketing'),
                _categoryChip('UTILITY', 'Utilitaires'),
                _categoryChip('AUTHENTICATION', 'Authentification'),
              ],
            ),
          ),
          const SizedBox(height: 4),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _filteredTemplates.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.message_rounded, size: 64, color: Colors.grey.withValues(alpha: 0.4)),
                            const SizedBox(height: 16),
                            Text('Aucun modèle trouvé',
                                style: TextStyle(color: Colors.grey.shade500, fontSize: 16)),
                            const SizedBox(height: 20),
                            ElevatedButton.icon(
                              icon: const Icon(Icons.add_rounded),
                              label: const Text('Créer un modèle'),
                              onPressed: _showCreateTemplateSheet,
                            )
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadTemplates,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(12),
                          itemCount: _filteredTemplates.length,
                          itemBuilder: (ctx, i) => _buildTemplateCard(isDark, _filteredTemplates[i]),
                        ),
                      ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showCreateTemplateSheet,
        icon: const Icon(Icons.add_rounded),
        label: const Text('Nouveau modèle'),
      ),
    );
  }

  Widget _buildTemplateCard(bool isDark, Map<String, dynamic> template) {
    final String name = template['template_name'] ?? 'Sans nom';
    final String status = template['status'] ?? '';
    final String language = template['language'] ?? '';
    final String category = template['category'] ?? '';
    final Map<String, dynamic> rawData = template['__data']?['template'] ?? {};
    final List components = rawData['components'] ?? [];

    String bodyText = '';
    for (var comp in components) {
      if (comp['type'] == 'BODY') { bodyText = comp['text'] ?? ''; break; }
    }

    Color statusColor = Colors.grey;
    if (status == 'APPROVED') statusColor = Colors.green;
    if (status == 'PENDING') statusColor = Colors.orange;
    if (status == 'REJECTED') statusColor = Colors.red;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ExpansionTile(
        leading: Container(
          width: 6,
          height: 40,
          decoration: BoxDecoration(color: statusColor, borderRadius: BorderRadius.circular(4)),
        ),
        title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Text(
          [if (category.isNotEmpty) category, if (language.isNotEmpty) language].join(' • '),
          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
        ),
        trailing: status.isNotEmpty
            ? Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.13),
                    borderRadius: BorderRadius.circular(20)),
                child: Text(status, style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 11)),
              )
            : null,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Aperçu du corps :', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                const SizedBox(height: 8),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: isDark ? Colors.white12 : Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    bodyText.isNotEmpty ? bodyText : 'Pas de texte dans le corps du modèle.',
                    style: const TextStyle(fontSize: 14),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _categoryChip(String code, String label) {
    final isSelected = _selectedCategory == code;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: isSelected,
        onSelected: (selected) {
          if (selected) {
            setState(() { _selectedCategory = code; _filterTemplates(); });
          }
        },
      ),
    );
  }
}

// ──────────────────────────────────────────────────
// Create Template Sheet
// ──────────────────────────────────────────────────
class CreateTemplateSheet extends StatefulWidget {
  final bool isMeta;
  const CreateTemplateSheet({super.key, required this.isMeta});

  @override
  State<CreateTemplateSheet> createState() => _CreateTemplateSheetState();
}

class _CreateTemplateSheetState extends State<CreateTemplateSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _bodyController = TextEditingController();
  final _footerController = TextEditingController();
  final _headerController = TextEditingController();

  String _selectedCategory = 'MARKETING';
  String _selectedLanguage = 'fr';
  bool _isSubmitting = false;

  final List<String> _categories = ['MARKETING', 'UTILITY', 'AUTHENTICATION'];
  final List<Map<String, String>> _languages = [
    {'code': 'fr', 'label': 'Français'},
    {'code': 'en', 'label': 'English'},
    {'code': 'ar', 'label': 'العربية'},
    {'code': 'es', 'label': 'Español'},
    {'code': 'pt_BR', 'label': 'Português (BR)'},
  ];

  @override
  void dispose() {
    _nameController.dispose();
    _bodyController.dispose();
    _footerController.dispose();
    _headerController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isSubmitting = true);

    final payload = {
      'template_name': _nameController.text.trim().toLowerCase().replaceAll(' ', '_'),
      'language_code': _selectedLanguage,
      'category': _selectedCategory,
      'template_type': 'header',
      'template_body': _bodyController.text.trim(),
      if (_footerController.text.trim().isNotEmpty) 'template_footer': _footerController.text.trim(),
      if (_headerController.text.trim().isNotEmpty) ...{
        'media_header_type': 'text',
        'template_header': _headerController.text.trim(),
      },
    };

    final result = await ApiService().createTemplate(payload);
    if (mounted) {
      setState(() => _isSubmitting = false);
      if (result != null && result['reaction'] == 1) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Modèle créé avec succès !'), backgroundColor: Colors.green),
        );
        Navigator.pop(context, true);
      } else {
        final msg = result?['message'] ?? 'Impossible de créer le modèle.';
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
        top: 20, left: 16, right: 16,
      ),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: FractionallySizedBox(
        heightFactor: 0.92,
        child: Form(
          key: _formKey,
          child: ListView(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    widget.isMeta ? 'Nouveau Modèle Meta' : 'Nouveau Modèle Simple',
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  IconButton(icon: const Icon(Icons.close_rounded), onPressed: () => Navigator.pop(context)),
                ],
              ),
              if (widget.isMeta)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.blue.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.info_outline_rounded, color: Colors.blue, size: 18),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Le modèle sera soumis à Meta pour approbation. Cela peut prendre 24-48h.',
                          style: TextStyle(fontSize: 12, color: Colors.blue),
                        ),
                      ),
                    ],
                  ),
                ),
              const Divider(height: 24),

              // Nom
              const Text('Nom du modèle', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 4),
              const Text('Lettres minuscules, chiffres et underscores uniquement.',
                  style: TextStyle(fontSize: 11, color: Colors.grey)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _nameController,
                decoration: InputDecoration(
                  hintText: 'ex: bienvenue_client',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return 'Nom requis';
                  if (!RegExp(r'^[a-z0-9_]+$').hasMatch(v.trim()))
                    return 'Lettres minuscules, chiffres et _ uniquement';
                  return null;
                },
              ),
              const SizedBox(height: 16),

              // Catégorie + Langue
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Catégorie', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          value: _selectedCategory,
                          decoration: InputDecoration(
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          ),
                          items: _categories.map((c) =>
                            DropdownMenuItem(value: c, child: Text(c, style: const TextStyle(fontSize: 13)))).toList(),
                          onChanged: (v) => setState(() => _selectedCategory = v!),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Langue', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          value: _selectedLanguage,
                          decoration: InputDecoration(
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          ),
                          items: _languages.map((l) =>
                            DropdownMenuItem(value: l['code'], child: Text(l['label']!, style: const TextStyle(fontSize: 13)))).toList(),
                          onChanged: (v) => setState(() => _selectedLanguage = v!),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // En-tête (optionnel)
              const Text('En-tête (optionnel)', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _headerController,
                decoration: InputDecoration(
                  hintText: 'Titre ou en-tête du message',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),

              // Corps
              const Text('Corps du message *', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 4),
              const Text('Utilisez {{1}}, {{2}} pour les variables.', style: TextStyle(fontSize: 11, color: Colors.grey)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _bodyController,
                maxLines: 5,
                decoration: InputDecoration(
                  hintText: 'Bonjour {{1}}, votre commande est confirmée !',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  alignLabelWithHint: true,
                ),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Corps requis' : null,
              ),
              const SizedBox(height: 16),

              // Pied de page
              const Text('Pied de page (optionnel)', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _footerController,
                decoration: InputDecoration(
                  hintText: 'Ex: Ne pas répondre à ce message.',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 24),

              SizedBox(
                width: double.infinity,
                height: 52,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  onPressed: _isSubmitting ? null : _submit,
                  child: _isSubmitting
                      ? const CircularProgressIndicator(color: Colors.white)
                      : Text(
                          widget.isMeta ? 'Soumettre à Meta' : 'Créer le modèle',
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}

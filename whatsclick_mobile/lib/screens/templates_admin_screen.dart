import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'create_template_screen.dart';

class TemplatesAdminScreen extends StatefulWidget {
  const TemplatesAdminScreen({super.key});

  @override
  State<TemplatesAdminScreen> createState() => _TemplatesAdminScreenState();
}

class _TemplatesAdminScreenState extends State<TemplatesAdminScreen> {
  List<Map<String, dynamic>> _allTemplates = [];
  List<Map<String, dynamic>> _filteredTemplates = [];
  bool _isLoading = true;
  bool _isSyncing = false;
  
  String _searchQuery = '';
  String _selectedCategory = 'ALL';
  String _selectedStatus = 'ALL';

  @override
  void initState() {
    super.initState();
    _loadTemplates();
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
        // 1. Seulement les modèles Meta
        final isMeta = (t['status'] != null && t['status'].toString().isNotEmpty);
        if (!isMeta) return false;

        // 2. Recherche texte
        final matchesSearch = (t['template_name'] ?? '')
            .toString()
            .toLowerCase()
            .contains(_searchQuery.toLowerCase());

        // 3. Catégorie
        final category = t['category'] ?? '';
        final matchesCategory = _selectedCategory == 'ALL' || category == _selectedCategory;

        // 4. Statut
        final status = t['status'] ?? '';
        final matchesStatus = _selectedStatus == 'ALL' || status == _selectedStatus;

        return matchesSearch && matchesCategory && matchesStatus;
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
          content: Text(success ? 'Modèles synchronisés !' : 'Échec de la synchronisation.'),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
      if (success) _loadTemplates();
    }
  }

  void _showCreateTemplateScreen() {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const CreateTemplateScreen()),
    ).then((created) {
      if (created == true) _loadTemplates();
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    
    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Modèles Meta', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          _isSyncing
              ? const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16),
                  child: Center(
                      child: SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: ThemeService.primaryColor))),
                )
              : IconButton(
                  icon: Icon(Icons.sync_rounded, color: ThemeService.primaryColor),
                  tooltip: 'Synchroniser',
                  onPressed: _syncTemplates,
                ),
        ],
      ),
      body: Column(
        children: [
          // Barre de recherche et filtre de statut
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    height: 48,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF1E293B) : Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.03),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        )
                      ],
                    ),
                    child: TextField(
                      decoration: const InputDecoration(
                        hintText: 'Rechercher un modèle...',
                        prefixIcon: Icon(Icons.search_rounded, color: Colors.grey),
                        border: InputBorder.none,
                        contentPadding: EdgeInsets.symmetric(vertical: 12),
                      ),
                      onChanged: (val) {
                        _searchQuery = val;
                        _filterTemplates();
                      },
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Container(
                  height: 48,
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF1E293B) : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.03),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      )
                    ],
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: _selectedStatus,
                      icon: const Icon(Icons.filter_list_rounded, size: 20, color: Colors.grey),
                      dropdownColor: isDark ? const Color(0xFF1E293B) : Colors.white,
                      items: const [
                        DropdownMenuItem(value: 'ALL', child: Text('Tous', style: TextStyle(fontSize: 14))),
                        DropdownMenuItem(value: 'APPROVED', child: Text('Approuvé', style: TextStyle(fontSize: 14, color: Colors.green))),
                        DropdownMenuItem(value: 'PENDING', child: Text('En attente', style: TextStyle(fontSize: 14, color: Colors.orange))),
                        DropdownMenuItem(value: 'REJECTED', child: Text('Rejeté', style: TextStyle(fontSize: 14, color: Colors.red))),
                      ],
                      onChanged: (val) {
                        if (val != null) {
                          setState(() {
                            _selectedStatus = val;
                            _filterTemplates();
                          });
                        }
                      },
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Catégories (Filtres horizontaux)
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              children: [
                _buildCategoryChip('ALL', 'Tous', isDark),
                _buildCategoryChip('MARKETING', 'Marketing', isDark),
                _buildCategoryChip('UTILITY', 'Utility', isDark),
                _buildCategoryChip('AUTHENTICATION', 'Auth', isDark),
              ],
            ),
          ),
          const SizedBox(height: 8),

          // Liste des modèles
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _filteredTemplates.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.dashboard_customize_rounded,
                                size: 64, color: Colors.grey.withValues(alpha: 0.4)),
                            const SizedBox(height: 16),
                            Text('Aucun modèle trouvé',
                                style: TextStyle(color: Colors.grey.shade500, fontSize: 16)),
                            const SizedBox(height: 20),
                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: ThemeService.primaryColor,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              icon: const Icon(Icons.add_rounded),
                              label: const Text('Créer un modèle', style: TextStyle(fontWeight: FontWeight.bold)),
                              onPressed: _showCreateTemplateScreen,
                            )
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadTemplates,
                        child: ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 80),
                          itemCount: _filteredTemplates.length,
                          itemBuilder: (ctx, i) => _buildTemplateCard(isDark, _filteredTemplates[i]),
                        ),
                      ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: ThemeService.primaryColor,
        onPressed: _showCreateTemplateScreen,
        child: const Icon(Icons.add_rounded, color: Colors.white, size: 28),
      ),
    );
  }

  Widget _buildCategoryChip(String code, String label, bool isDark) {
    final isSelected = _selectedCategory == code;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: GestureDetector(
        onTap: () {
          setState(() {
            _selectedCategory = code;
            _filterTemplates();
          });
        },
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: isSelected 
                ? ThemeService.primaryColor 
                : (isDark ? const Color(0xFF1E293B) : Colors.white),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white10 : Colors.grey.shade300),
            ),
            boxShadow: [
              if (isSelected)
                BoxShadow(color: ThemeService.primaryColor.withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 2))
            ]
          ),
          child: Text(
            label,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 13,
              color: isSelected ? Colors.white : (isDark ? Colors.white70 : Colors.black87),
            ),
          ),
        ),
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
      if (comp['type'] == 'BODY') {
        bodyText = comp['text'] ?? '';
        break;
      }
    }

    Color statusColor = Colors.grey;
    if (status == 'APPROVED') statusColor = Colors.green;
    if (status == 'PENDING') statusColor = Colors.orange;
    if (status == 'REJECTED') statusColor = Colors.red;

    IconData catIcon = Icons.message_rounded;
    if (category == 'MARKETING') catIcon = Icons.campaign_rounded;
    if (category == 'UTILITY') catIcon = Icons.notifications_active_rounded;
    if (category == 'AUTHENTICATION') catIcon = Icons.security_rounded;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade100),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 4),
          )
        ],
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        collapsedShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        leading: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: ThemeService.primaryColor.withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(catIcon, color: ThemeService.primaryColor, size: 22),
        ),
        title: Text(
          name, 
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: isDark ? Colors.white10 : Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  language.toUpperCase(),
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: isDark ? Colors.white70 : Colors.black54),
                ),
              ),
              const SizedBox(width: 8),
              if (status.isNotEmpty)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    status,
                    style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 10),
                  ),
                ),
            ],
          ),
        ),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDark ? Colors.black12 : const Color(0xFFF8FAFC),
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(16)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.segment_rounded, size: 16, color: Colors.grey),
                    const SizedBox(width: 8),
                    Text('Contenu du message', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey.shade600)),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF1E293B) : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                  ),
                  child: Text(
                    bodyText.isNotEmpty ? bodyText : 'Pas de texte dans le corps du modèle.',
                    style: TextStyle(fontSize: 14, color: isDark ? Colors.white : const Color(0xFF1F2937), height: 1.5),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../config/app_config.dart';
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
    // Every status (APPROVED/PENDING/REJECTED/…), not just approved — this
    // is the management screen, so a freshly created pending template must
    // be visible here even though it can't be sent yet.
    final templates = await ApiService().fetchAllTemplates();
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

        final status = t['status'] ?? '';
        final matchesStatus = _selectedStatus == 'ALL' || status == _selectedStatus;

        return matchesSearch && matchesCategory && matchesStatus;
      }).toList();
    });
  }

  Future<void> _syncTemplates() async {
    setState(() => _isSyncing = true);
    final result = await ApiService().syncTemplates();
    if (mounted) {
      setState(() => _isSyncing = false);
      // 1 = synced with real changes; 14 = reached the server fine but
      // nothing new to sync — that's not a failure, just show it as info
      // instead of a scary red "failed" banner. Anything else is a real
      // error, and we show the backend's actual message, not a generic one.
      final isRealFailure = result.reaction != 1 && result.reaction != 14;
      final message = result.message?.isNotEmpty == true
          ? result.message!
          : (result.reaction == 1 ? 'Modèles synchronisés !' : 'Échec de la synchronisation.');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: result.reaction == 1
              ? Colors.green
              : (isRealFailure ? Colors.red : Colors.blueGrey),
        ),
      );
      if (result.reaction == 1) _loadTemplates();
    }
  }

  void _showCreateTemplateScreen() {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const CreateTemplateScreen()),
    ).then((created) {
      if (created == true) _loadTemplates();
    });
  }

  Future<void> _openTemplateOnWeb(Map<String, dynamic> template) async {
    final uid = template['_uid']?.toString();
    if (uid == null) return;
    final url = Uri.parse('${baseUrl}vendor-console/whatsapp/templates/update/$uid');
    final opened = await launchUrl(url, mode: LaunchMode.externalApplication);
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir le navigateur.')),
      );
    }
  }

  Future<void> _confirmDelete(Map<String, dynamic> template) async {
    final name = template['template_name'] ?? 'ce modèle';
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Supprimer ce modèle ?'),
        content: Text(
          'Le modèle « $name » sera supprimé de votre compte WhatsApp Business. '
          'Cette action est définitive et ne peut pas être annulée.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Supprimer', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    final uid = template['_uid']?.toString();
    if (uid == null) return;

    final success = await ApiService().deleteTemplate(uid);
    if (!mounted) return;

    if (success) {
      // Optimistic removal: the backend already deletes the local record
      // in all cases (even if the Meta-side API call lags or fails), so the
      // template must disappear here immediately rather than waiting for a
      // future sync/refresh to catch up.
      setState(() {
        _allTemplates.removeWhere((t) => t['_uid']?.toString() == uid);
        _filterTemplates();
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Modèle « $name » supprimé.'), backgroundColor: Colors.green),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ApiService().lastTemplateDeleteError ?? 'Échec de la suppression.'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _openTemplateActions(Map<String, dynamic> template) {
    final isDark = ThemeService().isDark;
    final String name = template['template_name'] ?? 'Sans nom';
    final String status = template['status'] ?? '';
    final String language = template['language'] ?? '';
    final String category = template['category'] ?? '';
    final String bodyText = _bodyTextOf(template);

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.6,
        minChildSize: 0.35,
        maxChildSize: 0.9,
        expand: false,
        builder: (ctx, scrollController) => Container(
          decoration: BoxDecoration(
            color: isDark ? const Color(0xFF1E293B) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: ListView(
            controller: scrollController,
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.grey.withValues(alpha: 0.4),
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      name,
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: isDark ? Colors.white : const Color(0xFF1E293B),
                      ),
                    ),
                  ),
                  _statusBadge(status),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                '${category.isNotEmpty ? category : '—'} · ${language.toUpperCase()}',
                style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : Colors.black54),
              ),
              const SizedBox(height: 16),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: isDark ? Colors.black12 : const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                ),
                child: Text(
                  bodyText.isNotEmpty ? bodyText : 'Pas de texte dans le corps du modèle.',
                  style: TextStyle(fontSize: 14, height: 1.5, color: isDark ? Colors.white : const Color(0xFF1F2937)),
                ),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {
                    Navigator.pop(ctx);
                    _showEditConditions(template);
                  },
                  icon: const Icon(Icons.edit_rounded, size: 18),
                  label: const Text('Modifier'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: ThemeService.primaryColor,
                    side: BorderSide(color: ThemeService.primaryColor),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {
                    Navigator.pop(ctx);
                    _confirmDelete(template);
                  },
                  icon: const Icon(Icons.delete_outline_rounded, size: 18),
                  label: const Text('Supprimer'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.red,
                    side: const BorderSide(color: Colors.red),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// Editing a Meta template isn't a simple in-app form — Meta imposes real
  /// constraints (re-review, edit-frequency limits, some fields locked after
  /// creation), so this explains them upfront instead of the app silently
  /// letting an edit fail or get rejected on Meta's side.
  void _showEditConditions(Map<String, dynamic> template) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Avant de modifier'),
        content: const Text(
          'La modification d\'un modèle Meta se fait depuis la version web, '
          'et est soumise aux règles de WhatsApp :\n\n'
          '• Un modèle déjà approuvé repasse en « En attente » et doit être '
          're-validé par Meta après modification.\n'
          '• Le nombre de modifications est limité sur une période donnée ; '
          'des modifications trop fréquentes peuvent être bloquées.\n'
          '• Le nom, la langue et la catégorie ne peuvent pas être changés — '
          'il faut créer un nouveau modèle pour cela.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Annuler')),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _openTemplateOnWeb(template);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: ThemeService.primaryColor,
              foregroundColor: Colors.white,
            ),
            child: const Text('Continuer sur le web'),
          ),
        ],
      ),
    );
  }

  String _bodyTextOf(Map<String, dynamic> template) {
    final Map<String, dynamic> rawData = template['__data']?['template'] ?? {};
    final List components = rawData['components'] ?? [];
    for (var comp in components) {
      if (comp['type'] == 'BODY') return comp['text'] ?? '';
    }
    return '';
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
                _buildCategoryChip('UTILITY', 'Utilitaire', isDark),
              ],
            ),
          ),
          const SizedBox(height: 8),

          // Grille des modèles
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
                        child: GridView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 80),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            mainAxisSpacing: 14,
                            crossAxisSpacing: 14,
                            childAspectRatio: 0.72,
                          ),
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

  String _statusLabel(String status) {
    switch (status.toUpperCase()) {
      case 'APPROVED':
        return 'Approuvé';
      case 'PENDING':
        return 'En attente';
      case 'REJECTED':
        return 'Rejeté';
      case 'PAUSED':
        return 'En pause';
      case 'DISABLED':
        return 'Désactivé';
      case 'IN_APPEAL':
        return 'En appel';
      case 'PENDING_DELETION':
        return 'Suppression en attente';
      default:
        return status.isEmpty ? '—' : status;
    }
  }

  Color _statusColor(String status) {
    switch (status.toUpperCase()) {
      case 'APPROVED':
        return const Color(0xFF10B981);
      case 'PENDING':
        return Colors.orange;
      case 'REJECTED':
      case 'DISABLED':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Widget _statusBadge(String status) {
    final color = _statusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        _statusLabel(status),
        style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 10.5),
      ),
    );
  }

  Color _categoryColor(String category) {
    switch (category.toUpperCase()) {
      case 'UTILITY':
        return Colors.blue;
      case 'MARKETING':
        return Colors.deepOrange;
      case 'AUTHENTICATION':
        return Colors.purple;
      default:
        return ThemeService.primaryColor;
    }
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
    final String bodyText = _bodyTextOf(template);
    final categoryColor = _categoryColor(category);

    IconData catIcon = Icons.message_rounded;
    if (category == 'MARKETING') {
      catIcon = Icons.campaign_rounded;
    } else if (category == 'UTILITY') {
      catIcon = Icons.notifications_active_rounded;
    } else if (category == 'AUTHENTICATION') {
      catIcon = Icons.security_rounded;
    }

    return GestureDetector(
      onTap: () => _openTemplateActions(template),
      onLongPress: () => _openTemplateActions(template),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E293B) : Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade100),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 10,
              offset: const Offset(0, 4),
            )
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: categoryColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(catIcon, size: 11, color: categoryColor),
                      const SizedBox(width: 3),
                      Text(
                        language.toUpperCase(),
                        style: TextStyle(color: categoryColor, fontSize: 10, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
                Icon(Icons.more_horiz_rounded, size: 18, color: Colors.grey.withValues(alpha: 0.6)),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              name,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 6),
            Expanded(
              child: Text(
                bodyText.isNotEmpty ? bodyText : 'Pas de texte.',
                style: TextStyle(fontSize: 12, color: isDark ? Colors.white54 : Colors.grey.shade600),
                maxLines: 4,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            const SizedBox(height: 8),
            _statusBadge(status),
          ],
        ),
      ),
    );
  }
}

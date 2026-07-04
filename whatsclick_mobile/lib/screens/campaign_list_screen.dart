import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

// ─────────────────────────────────────────────────────────────────────────────
// Main merged campaigns screen (used for the bottom nav tab)
// Admins see: list + FAB to create + tap → dashboard
// Agents see:  list in read-only mode
// ─────────────────────────────────────────────────────────────────────────────
class CampaignListScreen extends StatefulWidget {
  const CampaignListScreen({super.key});

  @override
  State<CampaignListScreen> createState() => _CampaignListScreenState();
}

class _CampaignListScreenState extends State<CampaignListScreen> {
  bool _isLoading = true;
  bool _isAdmin = false;
  List<Map<String, dynamic>> _campaigns = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final roleId = await ApiService().getUserRoleId();
    if (mounted) setState(() => _isAdmin = (roleId == 2));
    await _fetchCampaigns();
  }

  Future<void> _fetchCampaigns() async {
    setState(() { _isLoading = true; _error = null; });
    try {
      final data = await ApiService().fetchCampaigns();
      if (mounted) setState(() { _campaigns = data; _isLoading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = 'Erreur de chargement des campagnes'; _isLoading = false; });
    }
  }

  // ── Status helpers ──────────────────────────────────────────────────────────
  Color _statusColor(String? s) {
    switch ((s ?? '').toLowerCase()) {
      case 'executed': return Colors.green;
      case 'processing': return Colors.orange;
      case 'scheduled': case 'upcoming': return Colors.blue;
      case 'aborted': case 'failed': return Colors.red;
      default: return Colors.grey;
    }
  }

  IconData _statusIcon(String? s) {
    switch ((s ?? '').toLowerCase()) {
      case 'executed': return Icons.check_circle_rounded;
      case 'processing': return Icons.sync_rounded;
      case 'scheduled': case 'upcoming': return Icons.schedule_rounded;
      case 'aborted': case 'failed': return Icons.cancel_rounded;
      default: return Icons.help_outline_rounded;
    }
  }

  String _statusLabel(String? s) {
    switch ((s ?? '').toLowerCase()) {
      case 'executed': return 'Exécutée';
      case 'processing': return 'En cours';
      case 'scheduled': case 'upcoming': return 'Planifiée';
      case 'aborted': return 'Annulée';
      case 'failed': return 'Échouée';
      default: return s ?? 'Inconnu';
    }
  }

  // ── Create wizard ───────────────────────────────────────────────────────────
  void _showCreateWizard() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const CreateCampaignWizardSheet(),
    ).then((created) { if (created == true) _fetchCampaigns(); });
  }

  // ── Dashboard ───────────────────────────────────────────────────────────────
  void _openDashboard(Map<String, dynamic> campaign) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => CampaignDashboardScreen(campaign: campaign)),
    );
  }

  // ── UI ──────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final onSurface = Theme.of(context).colorScheme.onSurface;
    final surfaceCard = Theme.of(context).colorScheme.surface;

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withAlpha(30),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(Icons.campaign_rounded, color: ThemeService.primaryColor, size: 20),
            ),
            const SizedBox(width: 10),
            const Text('Campagnes', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, size: 22),
            onPressed: _fetchCampaigns,
          ),
        ],
      ),
      floatingActionButton: _isAdmin
          ? FloatingActionButton.extended(
              onPressed: _showCreateWizard,
              icon: const Icon(Icons.add_rounded),
              label: const Text('Nouvelle campagne'),
              backgroundColor: ThemeService.primaryColor,
            )
          : null,
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: ThemeService.primaryColor))
          : _error != null
              ? _buildError(onSurface)
              : _campaigns.isEmpty
                  ? _buildEmpty(onSurface)
                  : RefreshIndicator(
                      onRefresh: _fetchCampaigns,
                      color: ThemeService.primaryColor,
                      child: ListView.builder(
                        padding: EdgeInsets.only(
                          left: 12, right: 12, top: 12,
                          bottom: _isAdmin ? 90 : 12, // space for FAB
                        ),
                        itemCount: _campaigns.length,
                        itemBuilder: (_, i) => _buildCard(_campaigns[i], surfaceCard, onSurface),
                      ),
                    ),
    );
  }

  Widget _buildError(Color onSurface) => Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.error_outline_rounded, size: 48, color: onSurface.withOpacity(0.3)),
        const SizedBox(height: 12),
        Text(_error!, style: TextStyle(color: onSurface.withOpacity(0.5))),
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: _fetchCampaigns,
          style: ElevatedButton.styleFrom(backgroundColor: ThemeService.primaryColor),
          child: const Text('Réessayer', style: TextStyle(color: Colors.white)),
        ),
      ],
    ),
  );

  Widget _buildEmpty(Color onSurface) => Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.campaign_outlined, size: 64, color: onSurface.withOpacity(0.2)),
        const SizedBox(height: 16),
        Text('Aucune campagne trouvée',
            style: TextStyle(fontSize: 16, color: onSurface.withOpacity(0.4))),
        if (_isAdmin) ...[
          const SizedBox(height: 24),
          ElevatedButton.icon(
            icon: const Icon(Icons.add_rounded),
            label: const Text('Créer une campagne'),
            onPressed: _showCreateWizard,
          ),
        ],
      ],
    ),
  );

  Widget _buildCard(Map<String, dynamic> c, Color surfaceCard, Color onSurface) {
    final title = c['title'] ?? c['campaign_name'] ?? 'Sans titre';
    final status = c['status']?.toString();
    final scheduledAt = c['scheduled_at']?.toString();
    final total = c['total_message_logs'] ?? c['total'] ?? '';
    final statusColor = _statusColor(status);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: surfaceCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: onSurface.withOpacity(0.06)),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        onTap: _isAdmin ? () => _openDashboard(c) : null,
        leading: Container(
          width: 42,
          height: 42,
          decoration: BoxDecoration(
            color: statusColor.withAlpha(30),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(_statusIcon(status), color: statusColor, size: 22),
        ),
        title: Text(
          title,
          style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14, color: onSurface),
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (scheduledAt != null && scheduledAt.isNotEmpty) ...[
              const SizedBox(height: 3),
              Row(children: [
                Icon(Icons.schedule_rounded, size: 12, color: onSurface.withOpacity(0.4)),
                const SizedBox(width: 4),
                Text(
                  scheduledAt.length > 16 ? scheduledAt.substring(0, 16) : scheduledAt,
                  style: TextStyle(fontSize: 11, color: onSurface.withOpacity(0.4)),
                ),
              ]),
            ],
            if (total != null && total.toString().isNotEmpty) ...[
              const SizedBox(height: 2),
              Row(children: [
                Icon(Icons.people_outline_rounded, size: 12, color: onSurface.withOpacity(0.4)),
                const SizedBox(width: 4),
                Text(
                  '$total destinataires',
                  style: TextStyle(fontSize: 11, color: onSurface.withOpacity(0.4)),
                ),
              ]),
            ],
          ],
        ),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: statusColor.withAlpha(25),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: statusColor.withAlpha(60)),
              ),
              child: Text(
                _statusLabel(status),
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: statusColor),
              ),
            ),
            if (_isAdmin) ...[
              const SizedBox(width: 4),
              Icon(Icons.chevron_right_rounded, size: 18, color: onSurface.withOpacity(0.3)),
            ],
          ],
        ),
      ),
    );
  }
}


// ─────────────────────────────────────────────────────────────────────────────
// Campaign Dashboard Screen
// ─────────────────────────────────────────────────────────────────────────────
class CampaignDashboardScreen extends StatefulWidget {
  final Map<String, dynamic> campaign;
  const CampaignDashboardScreen({super.key, required this.campaign});

  @override
  State<CampaignDashboardScreen> createState() => _CampaignDashboardScreenState();
}

class _CampaignDashboardScreenState extends State<CampaignDashboardScreen> {
  Map<String, dynamic>? _stats;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadStats();
  }

  Future<void> _loadStats() async {
    final uid = widget.campaign['_uid'] ?? widget.campaign['uid'] ?? '';
    if (uid.isEmpty) { setState(() => _isLoading = false); return; }
    setState(() => _isLoading = true);
    final data = await ApiService().fetchCampaignDashboard(uid);
    if (mounted) setState(() { _stats = data; _isLoading = false; });
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.campaign['title'] ?? widget.campaign['campaign_name'] ?? 'Campagne';

    return Scaffold(
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _loadStats),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _stats == null
              ? const Center(child: Text('Données non disponibles'))
              : _buildDashboard(),
    );
  }

  Widget _buildDashboard() {
    final s = _stats!;
    final sent = s['totalSent'] ?? s['total_message_logs'] ?? 0;
    final delivered = s['totalDelivered'] ?? s['delivered'] ?? 0;
    final read = s['totalRead'] ?? s['read'] ?? 0;
    final failed = s['totalFailed'] ?? s['failed'] ?? 0;
    final pending = s['totalPending'] ?? s['pending'] ?? 0;
    final expired = s['totalExpired'] ?? s['expired'] ?? 0;

    final total = (sent as num).toDouble();
    double rate(dynamic val) => total > 0 ? ((val as num).toDouble() / total).clamp(0.0, 1.0) : 0.0;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Summary cards grid
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 3,
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          childAspectRatio: 1.1,
          children: [
            _statCard('Envoyés', sent, Icons.send_rounded, Colors.blue),
            _statCard('Délivrés', delivered, Icons.done_all_rounded, Colors.green),
            _statCard('Lus', read, Icons.visibility_rounded, Colors.purple),
            _statCard('Échoués', failed, Icons.error_outline_rounded, Colors.red),
            _statCard('En attente', pending, Icons.hourglass_top_rounded, Colors.orange),
            _statCard('Expirés', expired, Icons.timer_off_rounded, Colors.grey),
          ],
        ),
        const SizedBox(height: 20),

        // Progress bars
        const Text('Taux de performance', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
        const SizedBox(height: 12),
        _progressRow('Taux de livraison', rate(delivered), Colors.green),
        _progressRow('Taux de lecture', rate(read), Colors.purple),
        _progressRow('Taux d\'échec', rate(failed), Colors.red),

        // Extra info
        if (widget.campaign['scheduled_at'] != null) ...[
          const SizedBox(height: 20),
          const Divider(),
          const SizedBox(height: 8),
          _infoRow(Icons.schedule_rounded, 'Planifiée le',
              widget.campaign['scheduled_at'].toString()),
          if (widget.campaign['status'] != null)
            _infoRow(Icons.info_outline_rounded, 'Statut',
                widget.campaign['status'].toString()),
        ],
      ],
    );
  }

  Widget _statCard(String label, dynamic value, IconData icon, Color color) {
    return Container(
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 6),
          Text(
            value.toString(),
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color),
          ),
          Text(label, style: const TextStyle(fontSize: 10), textAlign: TextAlign.center),
        ],
      ),
    );
  }

  Widget _progressRow(String label, double value, Color color) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: const TextStyle(fontSize: 13)),
              Text('${(value * 100).toStringAsFixed(1)}%',
                  style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 13)),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: value,
              minHeight: 8,
              backgroundColor: color.withValues(alpha: 0.15),
              valueColor: AlwaysStoppedAnimation<Color>(color),
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 16, color: Colors.grey),
          const SizedBox(width: 8),
          Text('$label : ', style: const TextStyle(color: Colors.grey, fontSize: 13)),
          Expanded(
            child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
          ),
        ],
      ),
    );
  }
}


// ─────────────────────────────────────────────────────────────────────────────
// Create Campaign Wizard (admin only)
// ─────────────────────────────────────────────────────────────────────────────
class CreateCampaignWizardSheet extends StatefulWidget {
  const CreateCampaignWizardSheet({super.key});

  @override
  State<CreateCampaignWizardSheet> createState() => _CreateCampaignWizardSheetState();
}

class _CreateCampaignWizardSheetState extends State<CreateCampaignWizardSheet> {
  int _step = 0;

  // Step 1 fields
  final _titleController = TextEditingController();
  DateTime? _scheduledAt;

  // Step 2 General
  String _audienceType = 'groups'; // 'groups' or 'contacts'

  // Step 2: Groups
  List<Map<String, dynamic>> _groups = [];
  List<String> _selectedGroupUids = [];
  bool _loadingGroups = true;

  // Step 2: Contacts
  List<Map<String, dynamic>> _contacts = [];
  List<Map<String, dynamic>> _filteredContacts = [];
  List<String> _selectedContactUids = [];
  bool _loadingContacts = true;
  final _contactsSearchController = TextEditingController();

  // Step 3
  List<Map<String, dynamic>> _templates = [];
  String? _selectedTemplateUid;
  List<TextEditingController> _varControllers = [];
  bool _loadingTemplates = true;

  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadGroups();
    _loadContacts();
    _loadTemplates();
    _contactsSearchController.addListener(_onSearchChanged);
  }

  @override
  void dispose() {
    _titleController.dispose();
    _contactsSearchController.dispose();
    for (var c in _varControllers) c.dispose();
    super.dispose();
  }

  Future<void> _loadGroups() async {
    final groups = await ApiService().fetchContactGroups();
    if (mounted) setState(() { _groups = groups; _loadingGroups = false; });
  }

  Future<void> _loadContacts() async {
    final contacts = await ApiService().fetchSimpleContactsList();
    if (mounted) {
      setState(() {
        _contacts = contacts;
        _filteredContacts = contacts;
        _loadingContacts = false;
      });
    }
  }

  void _onSearchChanged() {
    final query = _contactsSearchController.text.toLowerCase();
    setState(() {
      _filteredContacts = _contacts.where((c) {
        final name = (c['name'] ?? '').toString().toLowerCase();
        final waId = (c['wa_id'] ?? '').toString().toLowerCase();
        return name.contains(query) || waId.contains(query);
      }).toList();
    });
  }

  Future<void> _loadTemplates() async {
    final tpls = await ApiService().fetchTemplates();
    if (mounted) setState(() { _templates = tpls; _loadingTemplates = false; });
  }

  void _onTemplateSelected(String uid) {
    final tpl = _templates.firstWhere((t) => (t['_uid'] ?? t['uid']) == uid,
        orElse: () => {});
    final body = tpl['__data']?['template']?['components']
        ?.firstWhere((c) => c['type'] == 'BODY', orElse: () => {})['text'] ?? '';
    final vars = RegExp(r'\{\{(\d+)\}\}').allMatches(body.toString()).map((m) => m.group(1)!).toSet().toList()..sort();
    for (var c in _varControllers) c.dispose();
    setState(() {
      _selectedTemplateUid = uid;
      _varControllers = List.generate(vars.length, (_) => TextEditingController());
    });
  }

  Future<void> _submit() async {
    if (_selectedTemplateUid == null) return;
    if (_audienceType == 'groups' && _selectedGroupUids.isEmpty) return;
    if (_audienceType == 'contacts' && _selectedContactUids.isEmpty) return;
    setState(() => _isSubmitting = true);

    final vars = <String, String>{};
    for (int i = 0; i < _varControllers.length; i++) {
      vars['var_${i + 1}'] = _varControllers[i].text.trim();
    }

    final payload = {
      'title': _titleController.text.trim(),
      'template_uid': _selectedTemplateUid,
      'timezone': 'Africa/Douala', // Standard default expected by API
      if (_audienceType == 'groups') 'group_uids': _selectedGroupUids,
      if (_audienceType == 'contacts') 'contact_uids': _selectedContactUids,
      if (_scheduledAt != null) 'scheduled_at': _scheduledAt!.toIso8601String(),
      ...vars,
    };

    final result = await ApiService().scheduleCampaign(payload);
    if (mounted) {
      setState(() => _isSubmitting = false);
      final success = result != null && result['reaction'] == 1;
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Campagne créée !'), backgroundColor: Colors.green),
        );
        Navigator.pop(context, true);
      } else {
        final errMsg = result?['message'] ?? 'Erreur lors de la création.';
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(errMsg), backgroundColor: Colors.red),
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
        child: Column(
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  ['1. Informations', '2. Audience cible', '3. Modèle & Variables'][_step],
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                IconButton(icon: const Icon(Icons.close_rounded), onPressed: () => Navigator.pop(context)),
              ],
            ),
            // Step indicator
            Row(
              children: List.generate(3, (i) => Expanded(
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 3, vertical: 8),
                  height: 4,
                  decoration: BoxDecoration(
                    color: i <= _step ? ThemeService.primaryColor : Colors.grey.withValues(alpha: 0.3),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              )),
            ),
            const Divider(height: 12),
            Expanded(child: [_buildStep1(), _buildStep2(), _buildStep3()][_step]),
            const SizedBox(height: 12),
            // Navigation buttons
            Row(
              children: [
                if (_step > 0)
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => setState(() => _step--),
                      child: const Text('Retour'),
                    ),
                  ),
                if (_step > 0) const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ThemeService.primaryColor,
                      minimumSize: const Size(double.infinity, 50),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    onPressed: _isSubmitting ? null : () {
                      if (_step < 2) {
                        if (_step == 0 && _titleController.text.trim().isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Veuillez saisir un titre.')));
                          return;
                        }
                        if (_step == 1) {
                          if (_audienceType == 'groups' && _selectedGroupUids.isEmpty) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Sélectionnez au moins un groupe.')));
                            return;
                          }
                          if (_audienceType == 'contacts' && _selectedContactUids.isEmpty) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Sélectionnez au moins un contact.')));
                            return;
                          }
                        }
                        setState(() => _step++);
                      } else {
                        _submit();
                      }
                    },
                    child: _isSubmitting
                        ? const CircularProgressIndicator(color: Colors.white)
                        : Text(
                            _step < 2 ? 'Suivant →' : 'Lancer la campagne',
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                          ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  // ── Step 1: Title + Schedule ────────────────────────────────────────────────
  Widget _buildStep1() {
    return ListView(children: [
      const Text('Titre de la campagne *', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      TextField(
        controller: _titleController,
        decoration: InputDecoration(
          hintText: 'Ex: Promo Été 2025',
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      const SizedBox(height: 20),
      const Text('Date de planification (optionnel)', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      InkWell(
        onTap: () async {
          final now = DateTime.now();
          final date = await showDatePicker(
            context: context,
            initialDate: now.add(const Duration(hours: 1)),
            firstDate: now,
            lastDate: now.add(const Duration(days: 365)),
          );
          if (date != null && mounted) {
            final time = await showTimePicker(context: context, initialTime: TimeOfDay.now());
            if (time != null && mounted) {
              setState(() => _scheduledAt = DateTime(date.year, date.month, date.day, time.hour, time.minute));
            }
          }
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.grey.shade400),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            children: [
              const Icon(Icons.calendar_today_rounded, size: 18, color: Colors.grey),
              const SizedBox(width: 12),
              Text(
                _scheduledAt == null
                    ? 'Envoyer immédiatement'
                    : '${_scheduledAt!.day}/${_scheduledAt!.month}/${_scheduledAt!.year} ${_scheduledAt!.hour.toString().padLeft(2, '0')}:${_scheduledAt!.minute.toString().padLeft(2, '0')}',
                style: TextStyle(color: _scheduledAt == null ? Colors.grey : null),
              ),
            ],
          ),
        ),
      ),
    ]);
  }

  // ── Step 2: Target Audience selection ───────────────────────────────────────
  Widget _buildStep2() {
    final isDark = ThemeService().isDark;
    return Column(
      children: [
        // Audience type selector
        Container(
          margin: const EdgeInsets.only(bottom: 16),
          decoration: BoxDecoration(
            color: isDark ? Colors.white10 : Colors.grey.shade100,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: () => setState(() => _audienceType = 'groups'),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    decoration: BoxDecoration(
                      color: _audienceType == 'groups' ? ThemeService.primaryColor : Colors.transparent,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Center(
                      child: Text(
                        'Groupes',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: _audienceType == 'groups' ? Colors.white : (isDark ? Colors.white70 : Colors.black54),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
              Expanded(
                child: InkWell(
                  onTap: () => setState(() => _audienceType = 'contacts'),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    decoration: BoxDecoration(
                      color: _audienceType == 'contacts' ? ThemeService.primaryColor : Colors.transparent,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Center(
                      child: Text(
                        'Contacts',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: _audienceType == 'contacts' ? Colors.white : (isDark ? Colors.white70 : Colors.black54),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),

        // Display list based on type
        Expanded(
          child: _audienceType == 'groups'
              ? _buildGroupsList()
              : _buildContactsList(),
        ),
      ],
    );
  }

  Widget _buildGroupsList() {
    if (_loadingGroups) return const Center(child: CircularProgressIndicator());
    if (_groups.isEmpty) {
      return const Center(child: Text('Aucun groupe de contacts trouvé.'));
    }
    return ListView.builder(
      itemCount: _groups.length,
      itemBuilder: (_, i) {
        final g = _groups[i];
        final uid = (g['_uid'] ?? g['uid'] ?? '').toString();
        final name = g['title'] ?? g['name'] ?? 'Groupe $i';
        final count = g['total_contacts'] ?? g['contacts_count'] ?? '';
        final selected = _selectedGroupUids.contains(uid);
        return CheckboxListTile(
          value: selected,
          onChanged: (val) {
            setState(() {
              if (val == true) _selectedGroupUids.add(uid);
              else _selectedGroupUids.remove(uid);
            });
          },
          title: Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
          subtitle: count.toString().isNotEmpty ? Text('$count contacts') : null,
          secondary: CircleAvatar(
            backgroundColor: ThemeService.primaryColor.withValues(alpha: 0.15),
            child: Icon(Icons.group_rounded, color: ThemeService.primaryColor, size: 20),
          ),
        );
      },
    );
  }

  Widget _buildContactsList() {
    if (_loadingContacts) return const Center(child: CircularProgressIndicator());
    if (_contacts.isEmpty) {
      return const Center(child: Text('Aucun contact trouvé.'));
    }
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextField(
            controller: _contactsSearchController,
            decoration: InputDecoration(
              hintText: 'Rechercher un contact...',
              prefixIcon: const Icon(Icons.search_rounded),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16),
            ),
          ),
        ),
        Expanded(
          child: ListView.builder(
            itemCount: _filteredContacts.length,
            itemBuilder: (_, i) {
              final c = _filteredContacts[i];
              final uid = (c['_uid'] ?? c['uid'] ?? '').toString();
              final name = c['name'] ?? 'Sans nom';
              final waId = c['wa_id'] ?? '';
              final selected = _selectedContactUids.contains(uid);
              return CheckboxListTile(
                value: selected,
                onChanged: (val) {
                  setState(() {
                    if (val == true) _selectedContactUids.add(uid);
                    else _selectedContactUids.remove(uid);
                  });
                },
                title: Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text(waId),
                secondary: CircleAvatar(
                  backgroundColor: ThemeService.primaryColor.withValues(alpha: 0.15),
                  child: Icon(Icons.person_rounded, color: ThemeService.primaryColor, size: 20),
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  // ── Step 3: Template + Variables ────────────────────────────────────────────
  Widget _buildStep3() {
    if (_loadingTemplates) return const Center(child: CircularProgressIndicator());
    final approved = _templates.where((t) =>
      (t['status'] ?? '').toString().toUpperCase() == 'APPROVED').toList();

    return ListView(children: [
      const Text('Choisir un modèle *', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      if (approved.isEmpty)
        const Text('Aucun modèle approuvé disponible.', style: TextStyle(color: Colors.red))
      else
        ...approved.map((t) {
          final uid = (t['_uid'] ?? t['uid'] ?? '').toString();
          final name = t['template_name'] ?? t['name'] ?? 'Modèle';
          final selected = _selectedTemplateUid == uid;
          return Card(
            color: selected ? ThemeService.primaryColor.withValues(alpha: 0.1) : null,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
              side: BorderSide(
                color: selected ? ThemeService.primaryColor : Colors.transparent,
                width: 2,
              ),
            ),
            child: ListTile(
              title: Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
              subtitle: Text((t['category'] ?? '') + '  •  ' + (t['language'] ?? '')),
              trailing: selected ? Icon(Icons.check_circle_rounded, color: ThemeService.primaryColor) : null,
              onTap: () => _onTemplateSelected(uid),
            ),
          );
        }),
      if (_varControllers.isNotEmpty) ...[
        const SizedBox(height: 16),
        const Divider(),
        const SizedBox(height: 8),
        const Text('Variables du modèle', style: TextStyle(fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        ...List.generate(_varControllers.length, (i) => Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextField(
            controller: _varControllers[i],
            decoration: InputDecoration(
              labelText: 'Variable {{${i + 1}}}',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        )),
      ],
    ]);
  }
}

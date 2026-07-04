import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class CampaignsAdminScreen extends StatefulWidget {
  const CampaignsAdminScreen({super.key});

  @override
  State<CampaignsAdminScreen> createState() => _CampaignsAdminScreenState();
}

class _CampaignsAdminScreenState extends State<CampaignsAdminScreen> {
  List<Map<String, dynamic>> _campaigns = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadCampaigns();
  }

  Future<void> _loadCampaigns() async {
    setState(() => _isLoading = true);
    final campaigns = await ApiService().fetchCampaigns();
    if (mounted) {
      setState(() {
        _campaigns = campaigns;
        _isLoading = false;
      });
    }
  }

  void _showCreateCampaignWizard() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => const CreateCampaignWizardSheet(),
    ).then((value) {
      if (value == true) _loadCampaigns();
    });
  }

  void _openCampaignDashboard(Map<String, dynamic> campaign) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => CampaignDashboardScreen(campaign: campaign),
      ),
    );
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'executed': return Colors.green;
      case 'scheduled': return Colors.blue;
      case 'processing': return Colors.orange;
      case 'aborted': return Colors.red;
      default: return Colors.grey;
    }
  }

  IconData _statusIcon(String status) {
    switch (status) {
      case 'executed': return Icons.check_circle_outline_rounded;
      case 'scheduled': return Icons.schedule_rounded;
      case 'processing': return Icons.sync_rounded;
      case 'aborted': return Icons.cancel_outlined;
      default: return Icons.info_outline;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Campagnes', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadCampaigns,
            tooltip: 'Actualiser',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _campaigns.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.campaign_rounded, size: 64, color: Colors.grey.withValues(alpha: 0.4)),
                      const SizedBox(height: 16),
                      Text('Aucune campagne', style: TextStyle(color: Colors.grey.shade500, fontSize: 16)),
                      const SizedBox(height: 24),
                      ElevatedButton.icon(
                        icon: const Icon(Icons.add_rounded),
                        label: const Text('Créer une campagne'),
                        onPressed: _showCreateCampaignWizard,
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadCampaigns,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(12),
                    itemCount: _campaigns.length,
                    itemBuilder: (context, index) {
                      final campaign = _campaigns[index];
                      final String title = campaign['title'] ?? 'Campagne sans nom';
                      final String status = campaign['status'] ?? 'unknown';
                      final int totalContacts = campaign['total_contacts'] ?? 0;
                      final String scheduledAt = campaign['scheduled_at'] ?? '';
                      final String templateName = campaign['template_name'] ?? 'Modèle inconnu';
                      final Color sColor = _statusColor(status);
                      final IconData sIcon = _statusIcon(status);

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(14),
                          onTap: () => _openCampaignDashboard(campaign),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    CircleAvatar(
                                      backgroundColor: sColor.withValues(alpha: 0.12),
                                      child: Icon(sIcon, color: sColor, size: 20),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                          const SizedBox(height: 2),
                                          Text(templateName, style: TextStyle(fontSize: 13, color: Colors.grey.shade600)),
                                        ],
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: sColor.withValues(alpha: 0.13),
                                        borderRadius: BorderRadius.circular(20),
                                      ),
                                      child: Text(
                                        status.toUpperCase(),
                                        style: TextStyle(color: sColor, fontWeight: FontWeight.bold, fontSize: 11),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                Row(
                                  children: [
                                    _metaChip(Icons.people_outline_rounded, '$totalContacts contacts', Colors.indigo),
                                    const SizedBox(width: 8),
                                    if (scheduledAt.isNotEmpty)
                                      _metaChip(Icons.event_rounded, scheduledAt.substring(0, 10), Colors.teal),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.end,
                                  children: [
                                    TextButton.icon(
                                      icon: const Icon(Icons.bar_chart_rounded, size: 16),
                                      label: const Text('Voir le dashboard', style: TextStyle(fontSize: 12)),
                                      onPressed: () => _openCampaignDashboard(campaign),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showCreateCampaignWizard,
        icon: const Icon(Icons.add_rounded),
        label: const Text('Nouvelle campagne'),
      ),
    );
  }

  Widget _metaChip(IconData icon, String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 13, color: color),
          const SizedBox(width: 4),
          Text(label, style: TextStyle(fontSize: 12, color: color, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}

// ──────────────────────────────────────────────────
// Campaign Dashboard Screen
// ──────────────────────────────────────────────────
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
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() => _isLoading = true);
    final uid = widget.campaign['_uid'] ?? '';
    final data = await ApiService().fetchCampaignDashboard(uid);
    if (mounted) {
      setState(() {
        _stats = data;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    final String title = widget.campaign['title'] ?? 'Campagne';
    final String status = _stats?['campaignStatus'] ?? widget.campaign['status'] ?? '...';
    final String statusText = _stats?['statusText'] ?? status;

    Color statusColor = Colors.grey;
    if (status == 'executed') statusColor = Colors.green;
    if (status == 'processing') statusColor = Colors.orange;
    if (status == 'upcoming') statusColor = Colors.blue;
    if (status == 'aborted') statusColor = Colors.red;

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            Text(statusText, style: TextStyle(fontSize: 12, color: statusColor, fontWeight: FontWeight.w500)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadDashboard,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _stats == null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline_rounded, size: 48, color: Colors.grey),
                      const SizedBox(height: 12),
                      const Text('Impossible de charger les statistiques.'),
                      const SizedBox(height: 12),
                      ElevatedButton.icon(
                        onPressed: _loadDashboard,
                        icon: const Icon(Icons.refresh_rounded),
                        label: const Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadDashboard,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      // Status overview card
                      _buildStatusBanner(isDark, statusColor, statusText),
                      const SizedBox(height: 16),

                      // Stat grid
                      GridView.count(
                        crossAxisCount: 2,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                        childAspectRatio: 1.5,
                        children: [
                          _statCard('Envoyés', _stats!['totalSent'], Colors.blue, Icons.send_rounded),
                          _statCard('Délivrés', _stats!['totalDelivered'], Colors.green, Icons.done_all_rounded),
                          _statCard('Lus', _stats!['totalRead'], Colors.purple, Icons.visibility_rounded),
                          _statCard('Échoués', _stats!['totalFailed'], Colors.red, Icons.error_outline_rounded),
                          _statCard('En attente', _stats!['inQueueCount'], Colors.orange, Icons.hourglass_empty_rounded),
                          _statCard('Expirés', _stats!['expiredCount'], Colors.grey, Icons.timer_off_rounded),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Percentage breakdown
                      _buildPercentCard(isDark),

                      const SizedBox(height: 16),
                      // Time info
                      Card(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            children: [
                              const Icon(Icons.timer_rounded, color: Colors.teal),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text('Durée d\'exécution', style: TextStyle(fontWeight: FontWeight.bold)),
                                    Text(
                                      _stats?['timeTookFromScheduledAtFormatted'] ?? '--',
                                      style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildStatusBanner(bool isDark, Color color, String text) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [color.withValues(alpha: 0.15), color.withValues(alpha: 0.05)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(Icons.campaign_rounded, color: color, size: 40),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Statut actuel', style: TextStyle(fontSize: 12, color: Colors.grey)),
              Text(text, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _statCard(String label, dynamic value, Color color, IconData icon) {
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 18, color: color),
                const SizedBox(width: 6),
                Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500)),
              ],
            ),
            const Spacer(),
            Text(
              '${value ?? 0}',
              style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: color),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPercentCard(bool isDark) {
    final stats = _stats!;
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Taux de performance', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
            const SizedBox(height: 12),
            _percentRow('Taux de livraison', stats['totalDeliveredInPercent'] ?? '0%', Colors.green),
            _percentRow('Taux de lecture', stats['totalReadInPercent'] ?? '0%', Colors.purple),
            _percentRow('Taux d\'envoi', stats['totalSentInPercent'] ?? '0%', Colors.blue),
            _percentRow('Taux d\'échec', stats['totalFailedInPercent'] ?? '0%', Colors.red),
          ],
        ),
      ),
    );
  }

  Widget _percentRow(String label, String value, Color color) {
    // Parse percent value
    final double percent = double.tryParse(value.replaceAll('%', '')) ?? 0.0;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: const TextStyle(fontSize: 13)),
              Text(value, style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 13)),
            ],
          ),
          const SizedBox(height: 4),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: (percent / 100).clamp(0.0, 1.0),
              minHeight: 6,
              backgroundColor: color.withValues(alpha: 0.1),
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

// ──────────────────────────────────────────────────
// Create Campaign Wizard Sheet
// ──────────────────────────────────────────────────
class CreateCampaignWizardSheet extends StatefulWidget {
  const CreateCampaignWizardSheet({super.key});

  @override
  State<CreateCampaignWizardSheet> createState() => _CreateCampaignWizardSheetState();
}

class _CreateCampaignWizardSheetState extends State<CreateCampaignWizardSheet> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();

  List<Map<String, dynamic>> _contactGroups = [];
  List<Map<String, dynamic>> _templates = [];

  String? _selectedGroupId;
  Map<String, dynamic>? _selectedTemplate;

  Map<String, TextEditingController> _variableControllers = {};
  List<int> _bodyVariables = [];

  bool _isLoadingData = true;
  bool _isSubmitting = false;

  bool _scheduleForLater = false;
  DateTime? _selectedDate;
  TimeOfDay? _selectedTime;

  @override
  void initState() {
    super.initState();
    _loadWizardData();
  }

  @override
  void dispose() {
    _titleController.dispose();
    for (final c in _variableControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _loadWizardData() async {
    final groups = await ApiService().fetchContactGroups();
    final templates = await ApiService().fetchTemplates();

    if (mounted) {
      setState(() {
        _contactGroups = groups;
        _templates = templates.where((t) => t['status'] == 'APPROVED').toList();
        _isLoadingData = false;
      });
    }
  }

  void _onTemplateSelected(Map<String, dynamic>? template) {
    if (template == null) return;
    setState(() {
      _selectedTemplate = template;
      _variableControllers.clear();
      _bodyVariables.clear();

      final Map<String, dynamic> rawData = template['__data']?['template'] ?? {};
      final List components = rawData['components'] ?? [];
      String bodyText = '';
      for (var comp in components) {
        if (comp['type'] == 'BODY') {
          bodyText = comp['text'] ?? '';
          break;
        }
      }

      final regExp = RegExp(r'\{\{(\d+)\}\}');
      final matches = regExp.allMatches(bodyText);
      for (final match in matches) {
        final varNum = int.tryParse(match.group(1) ?? '');
        if (varNum != null && !_bodyVariables.contains(varNum)) {
          _bodyVariables.add(varNum);
          _variableControllers['field_$varNum'] = TextEditingController();
        }
      }
      _bodyVariables.sort();
    });
  }

  Future<void> _pickDateTime() async {
    final date = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (date == null || !mounted) return;
    final time = await showTimePicker(context: context, initialTime: TimeOfDay.now());
    if (time == null) return;
    setState(() { _selectedDate = date; _selectedTime = time; });
  }

  Future<void> _submitCampaign() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedGroupId == null || _selectedTemplate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez choisir un groupe et un modèle.')),
      );
      return;
    }
    setState(() => _isSubmitting = true);

    final Map<String, dynamic> payload = {
      'title': _titleController.text.trim(),
      'contact_group': [_selectedGroupId],
      'timezone': 'UTC',
      'template_uid': _selectedTemplate!['_uid'],
    };
    _variableControllers.forEach((key, c) { payload[key] = c.text.trim(); });

    if (_scheduleForLater && _selectedDate != null && _selectedTime != null) {
      final dt = DateTime(_selectedDate!.year, _selectedDate!.month, _selectedDate!.day,
          _selectedTime!.hour, _selectedTime!.minute);
      payload['schedule_at'] = dt.toIso8601String().substring(0, 16);
    }

    final response = await ApiService().scheduleCampaign(payload);
    if (mounted) {
      setState(() => _isSubmitting = false);
      if (response != null && response['reaction'] == 1) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Campagne créée avec succès !'), backgroundColor: Colors.green),
        );
        Navigator.pop(context, true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(response?['message'] ?? 'Erreur lors de la création.'), backgroundColor: Colors.red),
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
        heightFactor: 0.9,
        child: _isLoadingData
            ? const Center(child: CircularProgressIndicator())
            : Form(
                key: _formKey,
                child: ListView(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Nouvelle Campagne', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                        IconButton(icon: const Icon(Icons.close_rounded), onPressed: () => Navigator.pop(context)),
                      ],
                    ),
                    const Divider(),
                    const SizedBox(height: 12),

                    _sectionLabel('Nom de la campagne'),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _titleController,
                      decoration: InputDecoration(
                        hintText: 'Ex: Promo Soldes d\'été',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Veuillez saisir un nom' : null,
                    ),
                    const SizedBox(height: 16),

                    _sectionLabel('Groupe de contacts cible'),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      decoration: InputDecoration(border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                      hint: const Text('Sélectionner un groupe'),
                      value: _selectedGroupId,
                      items: _contactGroups.map((g) {
                        return DropdownMenuItem<String>(
                          value: g['_uid'].toString(),
                          child: Text(g['title'] ?? 'Sans titre'),
                        );
                      }).toList(),
                      onChanged: (val) => setState(() => _selectedGroupId = val),
                    ),
                    const SizedBox(height: 16),

                    _sectionLabel('Modèle de message (approuvé)'),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<Map<String, dynamic>>(
                      decoration: InputDecoration(border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                      hint: const Text('Sélectionner un modèle'),
                      value: _selectedTemplate,
                      items: _templates.map((t) {
                        return DropdownMenuItem<Map<String, dynamic>>(
                          value: t,
                          child: Text(t['template_name'] ?? 'Sans nom'),
                        );
                      }).toList(),
                      onChanged: _onTemplateSelected,
                    ),
                    const SizedBox(height: 16),

                    if (_bodyVariables.isNotEmpty) ...[
                      _sectionLabel('Variables du modèle', color: Colors.indigo),
                      const SizedBox(height: 8),
                      ..._bodyVariables.map((varNum) => Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: TextFormField(
                          controller: _variableControllers['field_$varNum'],
                          decoration: InputDecoration(
                            labelText: 'Variable {{$varNum}}',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          validator: (v) => (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
                        ),
                      )),
                      const SizedBox(height: 8),
                    ],

                    SwitchListTile(
                      title: const Text('Planifier pour plus tard', style: TextStyle(fontWeight: FontWeight.bold)),
                      value: _scheduleForLater,
                      onChanged: (val) => setState(() => _scheduleForLater = val),
                    ),

                    if (_scheduleForLater) ...[
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              icon: const Icon(Icons.date_range_rounded),
                              label: Text(_selectedDate == null
                                  ? 'Choisir Date'
                                  : '${_selectedDate!.day}/${_selectedDate!.month}/${_selectedDate!.year}'),
                              onPressed: _pickDateTime,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: OutlinedButton.icon(
                              icon: const Icon(Icons.access_time_rounded),
                              label: Text(_selectedTime == null
                                  ? 'Choisir Heure'
                                  : '${_selectedTime!.hour}:${_selectedTime!.minute.toString().padLeft(2, '0')}'),
                              onPressed: _pickDateTime,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                    ],

                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
                        onPressed: _isSubmitting ? null : _submitCampaign,
                        child: _isSubmitting
                            ? const CircularProgressIndicator(color: Colors.white)
                            : const Text('Lancer la Campagne', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
      ),
    );
  }

  Widget _sectionLabel(String text, {Color? color}) {
    return Text(text, style: TextStyle(fontWeight: FontWeight.bold, color: color));
  }
}

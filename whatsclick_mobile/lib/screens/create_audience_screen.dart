import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../models/contact.dart';

class CreateAudienceScreen extends StatefulWidget {
  const CreateAudienceScreen({super.key});

  @override
  State<CreateAudienceScreen> createState() => _CreateAudienceScreenState();
}

class _CreateAudienceScreenState extends State<CreateAudienceScreen> {
  final _titleController = TextEditingController();
  final _searchController = TextEditingController();

  String _mode = 'all'; // 'all', 'specific', 'groups'

  bool _isLoading = true;
  bool _isSubmitting = false;

  List<Contact> _contacts = [];
  List<Contact> _filteredContacts = [];
  final Set<int> _selectedContactIds = {};

  List<Map<String, dynamic>> _groups = [];
  List<Map<String, dynamic>> _labels = [];
  final Set<int> _selectedGroupIds = {};
  final Set<int> _selectedLabelIds = {};

  @override
  void initState() {
    super.initState();
    _loadData();
    _searchController.addListener(_filterContacts);
  }

  @override
  void dispose() {
    _titleController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    final results = await Future.wait<dynamic>([
      ApiService().fetchAllContactsSimple().catchError((e) => <Contact>[]),
      ApiService().fetchContactGroups().catchError((e) => <Map<String, dynamic>>[]),
      ApiService().fetchContactLabelsWithCounts().catchError((e) => <Map<String, dynamic>>[]),
    ]);
    if (mounted) {
      setState(() {
        _contacts = results[0] as List<Contact>;
        _filteredContacts = _contacts;
        _groups = results[1] as List<Map<String, dynamic>>;
        _labels = results[2] as List<Map<String, dynamic>>;
        _isLoading = false;
      });
    }
  }

  void _filterContacts() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredContacts = query.isEmpty
          ? _contacts
          : _contacts.where((c) {
              return c.name.toLowerCase().contains(query) || c.phoneNumber.contains(query);
            }).toList();
    });
  }

  Future<void> _submit() async {
    final title = _titleController.text.trim();
    if (title.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez donner un nom à l\'audience.')),
      );
      return;
    }
    if (_mode == 'specific' && _selectedContactIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Sélectionnez au moins un contact.')),
      );
      return;
    }
    if (_mode == 'groups' && _selectedGroupIds.isEmpty && _selectedLabelIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Sélectionnez au moins un groupe ou une étiquette.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final result = await ApiService().createAudience(
      title: title,
      isAllContacts: _mode == 'all',
      contacts: _mode == 'specific' ? _selectedContactIds.map((id) => id.toString()).toList() : null,
      groups: _mode == 'groups' ? _selectedGroupIds.map((id) => id.toString()).toList() : null,
      labels: _mode == 'groups' ? _selectedLabelIds.map((id) => id.toString()).toList() : null,
    );
    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (result != null) {
      Navigator.pop(context, true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ApiService().lastAudienceError ?? "Erreur lors de la création de l'audience."),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    final textColor = isDark ? Colors.white : Colors.black87;
    final subtitleColor = isDark ? Colors.white54 : Colors.grey;
    final cardColor = isDark ? ThemeService.darkCard : Colors.white;
    final borderColor = isDark ? Colors.white10 : Colors.grey.shade200;

    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: const Text('Nouvelle audience'),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeService.primaryColor))
          : Column(
              children: [
                // Fixed header — title + target-mode picker stay in place;
                // only the contact/group list below scrolls.
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Nom de l\'audience', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: textColor)),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _titleController,
                        style: TextStyle(color: textColor),
                        decoration: InputDecoration(
                          hintText: 'ex. Clients VIP',
                          hintStyle: TextStyle(color: subtitleColor),
                          filled: true,
                          fillColor: cardColor,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: borderColor)),
                          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: borderColor)),
                        ),
                      ),
                      const SizedBox(height: 20),
                      Text('Qui cibler ?', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: textColor)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(child: _buildModeCard('all', 'Tous les contacts', Icons.contact_mail, isDark)),
                          const SizedBox(width: 8),
                          Expanded(child: _buildModeCard('specific', 'Contacts spécifiques', Icons.person, isDark)),
                          const SizedBox(width: 8),
                          Expanded(child: _buildModeCard('groups', 'Groupes & étiquettes', Icons.local_offer, isDark)),
                        ],
                      ),
                      const SizedBox(height: 16),
                      if (_mode == 'specific') ...[
                        TextField(
                          controller: _searchController,
                          style: TextStyle(color: textColor),
                          decoration: InputDecoration(
                            hintText: 'Rechercher un contact...',
                            hintStyle: TextStyle(color: subtitleColor),
                            prefixIcon: const Icon(Icons.search),
                            filled: true,
                            fillColor: cardColor,
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: borderColor)),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text('${_selectedContactIds.length} contact(s) sélectionné(s)', style: TextStyle(fontSize: 12, color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 4),
                      ],
                    ],
                  ),
                ),
                // Scrollable content — contact list, groups/labels list, or
                // the "all contacts" summary, depending on the chosen mode.
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                    children: [
                      if (_mode == 'specific') ...[
                        ...List.generate(_filteredContacts.length, (index) {
                          final c = _filteredContacts[index];
                          if (c.id == null) return const SizedBox.shrink();
                          return CheckboxListTile(
                            title: Text(c.name, style: TextStyle(color: textColor)),
                            subtitle: Text(c.phoneNumber, style: TextStyle(color: subtitleColor)),
                            value: _selectedContactIds.contains(c.id),
                            activeColor: ThemeService.primaryColor,
                            onChanged: (val) {
                              setState(() {
                                if (val == true) {
                                  _selectedContactIds.add(c.id!);
                                } else {
                                  _selectedContactIds.remove(c.id);
                                }
                              });
                            },
                          );
                        }),
                      ] else if (_mode == 'groups') ...[
                        if (_groups.isEmpty && _labels.isEmpty)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            child: Text('Aucun groupe ni étiquette disponible.', style: TextStyle(color: subtitleColor)),
                          )
                        else ...[
                          if (_groups.isNotEmpty) ...[
                            Text('GROUPES', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
                            ..._groups.map((g) {
                              final id = int.tryParse(g['_id'].toString());
                              if (id == null) return const SizedBox.shrink();
                              return CheckboxListTile(
                                title: Text(g['title'] ?? '', style: TextStyle(color: textColor)),
                                subtitle: Text('${g['total_contacts'] ?? 0} contact(s)', style: TextStyle(color: subtitleColor)),
                                value: _selectedGroupIds.contains(id),
                                activeColor: ThemeService.primaryColor,
                                onChanged: (val) {
                                  setState(() {
                                    if (val == true) {
                                      _selectedGroupIds.add(id);
                                    } else {
                                      _selectedGroupIds.remove(id);
                                    }
                                  });
                                },
                              );
                            }),
                          ],
                          if (_labels.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Text('ÉTIQUETTES', style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
                            ..._labels.map((l) {
                              final id = int.tryParse(l['_id'].toString());
                              if (id == null) return const SizedBox.shrink();
                              return CheckboxListTile(
                                title: Text(l['title'] ?? '', style: TextStyle(color: textColor)),
                                subtitle: Text('${l['total_contacts'] ?? 0} contact(s)', style: TextStyle(color: subtitleColor)),
                                value: _selectedLabelIds.contains(id),
                                activeColor: ThemeService.primaryColor,
                                onChanged: (val) {
                                  setState(() {
                                    if (val == true) {
                                      _selectedLabelIds.add(id);
                                    } else {
                                      _selectedLabelIds.remove(id);
                                    }
                                  });
                                },
                              );
                            }),
                          ],
                        ],
                      ] else ...[
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          child: Text(
                            '${_contacts.length} contact${_contacts.length > 1 ? 's' : ''} au total seront ciblés.',
                            style: TextStyle(color: subtitleColor),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
      bottomSheet: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E293B) : Colors.white,
          border: Border(top: BorderSide(color: borderColor)),
        ),
        child: SafeArea(
          child: SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: ThemeService.primaryColor,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: _isSubmitting ? null : _submit,
              child: _isSubmitting
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text('Créer l\'audience', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildModeCard(String mode, String label, IconData icon, bool isDark) {
    final isSelected = _mode == mode;
    return GestureDetector(
      onTap: () => setState(() => _mode = mode),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
        decoration: BoxDecoration(
          color: isSelected ? ThemeService.primaryColor.withValues(alpha: 0.1) : (isDark ? ThemeService.darkCard : Colors.white),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white10 : Colors.grey.shade200),
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Column(
          children: [
            Icon(icon, color: isSelected ? ThemeService.primaryColor : Colors.grey, size: 20),
            const SizedBox(height: 6),
            Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: isSelected ? ThemeService.primaryColor : (isDark ? Colors.white70 : Colors.black54),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

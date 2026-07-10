import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';
import '../services/theme_service.dart';
import '../config/app_config.dart';
import '../models/contact.dart';
import 'chat_box_screen.dart';

class LabelContactsScreen extends StatefulWidget {
  final String labelUid;
  final String dateFilter;
  final String? startDate;
  final String? endDate;

  const LabelContactsScreen({
    super.key,
    required this.labelUid,
    required this.dateFilter,
    this.startDate,
    this.endDate,
  });

  @override
  State<LabelContactsScreen> createState() => _LabelContactsScreenState();
}

class _LabelContactsScreenState extends State<LabelContactsScreen> {
  List<Map<String, dynamic>> _contacts = [];
  List<Map<String, dynamic>> _filteredContacts = [];
  bool _isLoading = true;
  String? _error;
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadContacts();
    _searchController.addListener(_applySearch);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  String _periodLabel() {
    switch (widget.dateFilter) {
      case 'today': return "Aujourd'hui";
      case 'yesterday': return 'Hier';
      case 'day_before': return 'Avant-hier';
      case 'custom':
        if (widget.startDate != null && widget.endDate != null) {
          return '${widget.startDate} → ${widget.endDate}';
        }
        return 'Période personnalisée';
      default: return 'Toutes périodes';
    }
  }

  Future<void> _loadContacts() async {
    setState(() { _isLoading = true; _error = null; });
    try {
      final prefs = await _getPrefs();
      final token = prefs['token'] ?? '';
      final List<String> params = ['label_date_filter=${widget.dateFilter}'];
      if (widget.startDate != null) params.add('start_date=${widget.startDate}');
      if (widget.endDate != null) params.add('end_date=${widget.endDate}');
      final url = Uri.parse('${baseApiUrl}vendor/contact/by-label/${widget.labelUid}?${params.join('&')}');
      
      if (debug) debugPrint('LabelContactsScreen URL: $url');
      
      final response = await http.get(url, headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      }).timeout(const Duration(seconds: 20));

      if (debug) debugPrint('LabelContactsScreen response: ${response.body}');

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final list = body['data']?['contacts'] as List? ?? [];
          if (mounted) {
            setState(() {
              _contacts = List<Map<String, dynamic>>.from(list);
              _applySearch();
              _isLoading = false;
            });
          }
          return;
        }
      }
      if (mounted) setState(() { _error = 'Aucun contact trouvé'; _isLoading = false; });
    } catch (e) {
      if (debug) debugPrint('LabelContactsScreen error: $e');
      if (mounted) setState(() { _error = 'Erreur de chargement'; _isLoading = false; });
    }
  }

  Future<Map<String, String?>> _getPrefs() async {
    final prefs = await ApiService().getToken();
    return {'token': prefs};
  }

  void _applySearch() {
    final q = _searchController.text.toLowerCase();
    setState(() {
      _filteredContacts = _contacts.where((c) {
        final first = (c['first_name'] ?? '').toString().toLowerCase();
        final last = (c['last_name'] ?? '').toString().toLowerCase();
        final wa = (c['wa_id'] ?? '').toString().toLowerCase();
        return first.contains(q) || last.contains(q) || wa.contains(q);
      }).toList();
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Contacts étiquetés', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            Text(_periodLabel(), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w400)),
          ],
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: Column(
        children: [
          // Search bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: Container(
              decoration: BoxDecoration(
                color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: isDark ? Colors.white.withOpacity(0.06) : Colors.black.withOpacity(0.06)),
              ),
              child: TextField(
                controller: _searchController,
                style: TextStyle(color: isDark ? Colors.white : Colors.black87),
                decoration: InputDecoration(
                  hintText: 'Rechercher...',
                  hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.black38, fontSize: 13),
                  prefixIcon: Icon(Icons.search, color: isDark ? Colors.white38 : Colors.black38, size: 20),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                ),
              ),
            ),
          ),
          // Count badge
          if (!_isLoading && _error == null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: ThemeService.primaryColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      '${_filteredContacts.length} contact${_filteredContacts.length > 1 ? 's' : ''}',
                      style: const TextStyle(fontSize: 12, color: ThemeService.primaryColor, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
          // List
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _error != null && _contacts.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.people_outline, size: 48, color: isDark ? Colors.white24 : Colors.black26),
                            const SizedBox(height: 12),
                            Text(_error!, style: TextStyle(color: isDark ? Colors.white54 : Colors.black45)),
                            const SizedBox(height: 16),
                            ElevatedButton(onPressed: _loadContacts, child: const Text('Réessayer')),
                          ],
                        ),
                      )
                    : _filteredContacts.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.people_outline, size: 48, color: isDark ? Colors.white24 : Colors.black26),
                                const SizedBox(height: 12),
                                Text('Aucun contact', style: TextStyle(color: isDark ? Colors.white54 : Colors.black45)),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _loadContacts,
                            child: ListView.builder(
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
                              itemCount: _filteredContacts.length,
                              itemBuilder: (context, i) {
                                final c = _filteredContacts[i];
                                final name = '${c['first_name'] ?? ''} ${c['last_name'] ?? ''}'.trim();
                                final phone = c['wa_id'] ?? c['phone'] ?? '';
                                final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';

                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: Material(
                                    color: isDark ? ThemeService.darkCard : ThemeService.lightCard,
                                    borderRadius: BorderRadius.circular(12),
                                    child: InkWell(
                                      borderRadius: BorderRadius.circular(12),
                                      onTap: () {
                                        final contact = Contact(
                                          uid: c['_uid'] ?? '',
                                          name: name.isEmpty ? phone : name,
                                          phoneNumber: phone,
                                          profilePicUrl: null,
                                        );
                                        Navigator.push(context, MaterialPageRoute(
                                          builder: (_) => ChatBoxScreen(contact: contact),
                                        ));
                                      },
                                      child: ListTile(
                                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                                        leading: CircleAvatar(
                                          backgroundColor: ThemeService.primaryColor.withOpacity(0.12),
                                          child: Text(initial, style: const TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold)),
                                        ),
                                        title: Text(
                                          name.isEmpty ? phone : name,
                                          style: TextStyle(fontWeight: FontWeight.w600, color: isDark ? Colors.white : Colors.black87, fontSize: 14),
                                        ),
                                        subtitle: phone.isNotEmpty ? Text(phone, style: TextStyle(color: isDark ? Colors.white54 : Colors.black54, fontSize: 12)) : null,
                                        trailing: Icon(Icons.chat_bubble_outline_rounded, color: ThemeService.primaryColor.withOpacity(0.7), size: 18),
                                      ),
                                    ),
                                  ),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }
}

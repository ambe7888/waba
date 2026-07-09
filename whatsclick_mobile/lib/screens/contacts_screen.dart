import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/contact.dart';
import 'chat_box_screen.dart';
import '../services/theme_service.dart';
import 'package:url_launcher/url_launcher.dart';

class ContactsScreen extends StatefulWidget {
  final String? initialLabelFilter;
  final String? initialLabelDateFilter;
  final String? initialStartDate;
  final String? initialEndDate;
  final String? initialAssignedFilter;

  const ContactsScreen({
    super.key,
    this.initialLabelFilter,
    this.initialLabelDateFilter,
    this.initialStartDate,
    this.initialEndDate,
    this.initialAssignedFilter,
  });

  @override
  State<ContactsScreen> createState() => _ContactsScreenState();
}

class _ContactsScreenState extends State<ContactsScreen> {
  final _searchController = TextEditingController();
  List<Contact> _contacts = [];
  List<Contact> _filteredContacts = [];
  bool _isLoading = true;
  bool _isLoadingMore = false;
  int _nextPage = 0;
  final _scrollController = ScrollController();
  String? _error;

  String? _labelFilter;
  String? _labelDateFilter;
  String? _startDate;
  String? _endDate;
  String? _assignedFilter;

  @override
  void initState() {
    super.initState();
    _labelFilter = widget.initialLabelFilter;
    _labelDateFilter = widget.initialLabelDateFilter;
    _startDate = widget.initialStartDate;
    _endDate = widget.initialEndDate;
    _assignedFilter = widget.initialAssignedFilter;

    _loadContacts();
    _searchController.addListener(_applyFilters);
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
        _loadMoreContacts();
      }
    });
  }
  
  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadContacts() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final result = await ApiService().fetchContacts(
        page: 1,
        selectedLabel: _labelFilter,
        labelDateFilter: _labelDateFilter,
        startDate: _startDate,
        endDate: _endDate,
        assigned: _assignedFilter,
      );
      if (mounted) {
        setState(() {
          _contacts = result['contacts'] as List<Contact>;
          _nextPage = result['nextPage'] as int? ?? 0;
          _applyFilters();
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Erreur de chargement des contacts';
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _loadMoreContacts() async {
    if (_isLoadingMore || _nextPage == 0) return;
    setState(() => _isLoadingMore = true);
    try {
      final result = await ApiService().fetchContacts(
        page: _nextPage,
        selectedLabel: _labelFilter,
        labelDateFilter: _labelDateFilter,
        startDate: _startDate,
        endDate: _endDate,
        assigned: _assignedFilter,
      );
      if (mounted) {
        setState(() {
          final newContacts = result['contacts'] as List<Contact>;
          final Map<String, Contact> merged = {
            for (final existing in _contacts) existing.uid: existing,
            for (final fresh in newContacts) fresh.uid: fresh,
          };
          _contacts = merged.values.toList();
          _nextPage = result['nextPage'] as int? ?? 0;
          _applyFilters();
          _isLoadingMore = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingMore = false);
      }
    }
  }

  void _applyFilters() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredContacts = _contacts.where((contact) {
        return contact.name.toLowerCase().contains(query) ||
               contact.phoneNumber.contains(query) ||
               (contact.lastMessage?.toLowerCase().contains(query) ?? false);
      }).toList();
    });
  }

  Widget _buildAvatar(Contact contact) {
    final initials = contact.name.trim().isNotEmpty
        ? contact.name.trim().split(' ').map((e) => e.isNotEmpty ? e[0] : '').take(2).join().toUpperCase()
        : '?';

    return CircleAvatar(
      backgroundColor: ThemeService.primaryColor.withOpacity(0.2),
      foregroundColor: ThemeService.primaryColor,
      radius: 24,
      child: Text(
        initials,
        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
      ),
    );
  }

  void _openChat(Contact contact, {bool openTemplatePicker = false}) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => ChatBoxScreen(
          contact: contact,
          openTemplatePicker: openTemplatePicker,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withAlpha(30),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(Icons.contacts_rounded, color: ThemeService.primaryColor, size: 20),
            ),
            const SizedBox(width: 10),
            const Text(
              'Contacts',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, letterSpacing: -0.5),
            ),
          ],
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
      ),
      body: Column(
        children: [
          Padding(
            padding: EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              decoration: const InputDecoration(
                hintText: 'Rechercher un contact...',
                prefixIcon: Icon(Icons.search),
              ),
            ),
          ),
          if (_labelFilter != null || _labelDateFilter != null || _assignedFilter != null)
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: ThemeService.primaryColor.withOpacity(0.2)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      'Filtres actifs : ${_labelFilter != null ? "Étiquette" : ""} ${_labelDateFilter != null ? "($_labelDateFilter)" : ""}',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: ThemeService.primaryColor,
                      ),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, size: 18),
                    color: ThemeService.primaryColor,
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                    onPressed: () {
                      setState(() {
                        _labelFilter = null;
                        _labelDateFilter = null;
                        _startDate = null;
                        _endDate = null;
                        _assignedFilter = null;
                      });
                      _loadContacts();
                    },
                  ),
                ],
              ),
            ),
          Expanded(
            child: _isLoading
                ? Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(_error!, style: TextStyle(color: Colors.red)),
                            SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _loadContacts,
                              child: Text('Réessayer'),
                            ),
                          ],
                        ),
                      )
                    : _filteredContacts.isEmpty
                        ? Center(child: Text('Aucun contact trouvé'))
                        : ListView.builder(
                            controller: _scrollController,
                            itemCount: _filteredContacts.length + (_nextPage > 0 ? 1 : 0),
                            itemBuilder: (context, index) {
                              if (index == _filteredContacts.length) {
                                return const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()));
                              }
                              final contact = _filteredContacts[index];
                              return ListTile(
                                leading: _buildAvatar(contact),
                                title: Text(
                                  contact.name.isNotEmpty ? contact.name : contact.phoneNumber,
                                  style: TextStyle(fontWeight: FontWeight.w600),
                                ),
                                subtitle: Text(contact.phoneNumber),
                                trailing: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      icon: Icon(Icons.flight_takeoff_rounded, color: ThemeService.primaryColor),
                                      onPressed: () {
                                        _openChat(contact, openTemplatePicker: true);
                                      },
                                      tooltip: 'Envoyer un modèle',
                                    ),
                                    IconButton(
                                      icon: Icon(Icons.message_rounded, color: ThemeService.primaryColor),
                                      onPressed: () => _openChat(contact),
                                      tooltip: 'Envoyer un message',
                                    ),
                                    IconButton(
                                      icon: Icon(Icons.phone_rounded, color: Colors.green),
                                      onPressed: () async {
                                        final url = Uri.parse('tel:${contact.phoneNumber}');
                                        final messenger = ScaffoldMessenger.of(context);
                                        if (await canLaunchUrl(url)) {
                                          await launchUrl(url, mode: LaunchMode.externalApplication);
                                        } else if (mounted) {
                                          messenger.showSnackBar(
                                            const SnackBar(content: Text('Impossible de lancer l\'appel.')),
                                          );
                                        }
                                      },
                                      tooltip: 'Appeler',
                                    ),
                                  ],
                                ),
                                onTap: () => _openChat(contact),
                              );
                            },
                          ),
          ),
        ],
      ),
    );
  }
}

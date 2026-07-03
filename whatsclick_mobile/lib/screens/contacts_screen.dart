import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/contact.dart';
import 'chat_box_screen.dart';
import '../services/theme_service.dart';
import 'package:url_launcher/url_launcher.dart';

class ContactsScreen extends StatefulWidget {
  const ContactsScreen({super.key});

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

  @override
  void initState() {
    super.initState();
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
      final result = await ApiService().fetchContacts(page: 1);
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
      final result = await ApiService().fetchContacts(page: _nextPage);
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

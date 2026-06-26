import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/contact.dart';
import 'chat_box_screen.dart';
import '../services/theme_service.dart';

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
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadContacts();
    _searchController.addListener(_applyFilters);
  }

  Future<void> _loadContacts() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final result = await ApiService().fetchContacts(page: 1); // For simplicity, loading page 1 only right now
      if (mounted) {
        setState(() {
          _contacts = result['contacts'] as List<Contact>;
          _filteredContacts = _contacts;
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

  void _applyFilters() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredContacts = _contacts.where((contact) {
        return contact.name.toLowerCase().contains(query) ||
               contact.phoneNumber.contains(query);
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
        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
      ),
    );
  }

  void _openChat(Contact contact) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => ChatBoxScreen(contact: contact),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Répertoire Contacts'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
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
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(_error!, style: const TextStyle(color: Colors.red)),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _loadContacts,
                              child: const Text('Réessayer'),
                            ),
                          ],
                        ),
                      )
                    : _filteredContacts.isEmpty
                        ? const Center(child: Text('Aucun contact trouvé'))
                        : ListView.builder(
                            itemCount: _filteredContacts.length,
                            itemBuilder: (context, index) {
                              final contact = _filteredContacts[index];
                              return ListTile(
                                leading: _buildAvatar(contact),
                                title: Text(
                                  contact.name.isNotEmpty ? contact.name : contact.phoneNumber,
                                  style: const TextStyle(fontWeight: FontWeight.w600),
                                ),
                                subtitle: Text(contact.phoneNumber),
                                trailing: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.message_rounded, color: ThemeService.primaryColor),
                                      onPressed: () => _openChat(contact),
                                      tooltip: 'Envoyer un message',
                                    ),
                                    IconButton(
                                      icon: const Icon(Icons.phone_rounded, color: Colors.green),
                                      onPressed: () {
                                        // TODO: Direct Call via url_launcher tel:
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

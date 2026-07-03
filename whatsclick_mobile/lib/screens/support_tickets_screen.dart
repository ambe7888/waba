import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class SupportTicketsScreen extends StatefulWidget {
  const SupportTicketsScreen({super.key});

  @override
  State<SupportTicketsScreen> createState() => _SupportTicketsScreenState();
}

class _SupportTicketsScreenState extends State<SupportTicketsScreen> {
  bool _isLoading = true;
  List<dynamic> _tickets = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchTickets();
  }

  Future<void> _fetchTickets() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await ApiService().fetchSupportTickets();
      if (mounted) {
        setState(() {
          _tickets = data?['tickets'] ?? [];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Erreur de chargement des tickets';
          _isLoading = false;
        });
      }
    }
  }

  Color _getStatusColor(int status) {
    switch (status) {
      case 1: return Colors.green; // Ouvert / Actif
      case 2: return Colors.orange; // En attente
      case 3: return Colors.grey; // Fermé
      default: return Colors.blue;
    }
  }

  String _getStatusText(int status) {
    switch (status) {
      case 1: return 'Ouvert';
      case 2: return 'En attente';
      case 3: return 'Fermé';
      default: return 'Inconnu';
    }
  }

  void _showCreateTicketDialog() {
    final subjectController = TextEditingController();
    final descriptionController = TextEditingController();
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
                left: 16,
                right: 16,
                top: 24,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Nouveau Ticket', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  SizedBox(height: 16),
                  TextField(
                    controller: subjectController,
                    decoration: InputDecoration(
                      labelText: 'Sujet',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                  SizedBox(height: 16),
                  TextField(
                    controller: descriptionController,
                    maxLines: 4,
                    decoration: InputDecoration(
                      labelText: 'Description',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                  SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: isSubmitting
                          ? null
                          : () async {
                              final subject = subjectController.text.trim();
                              final desc = descriptionController.text.trim();
                              if (subject.isEmpty || desc.isEmpty) return;

                              setModalState(() => isSubmitting = true);
                              final success = await ApiService().createSupportTicket(subject, desc);
                              
                              if (mounted) {
                                Navigator.pop(context);
                                if (success) {
                                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Ticket créé avec succès')));
                                  _fetchTickets();
                                } else {
                                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur lors de la création')));
                                }
                              }
                            },
                      child: isSubmitting
                          ? CircularProgressIndicator(color: Colors.white)
                          : Text('Créer', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                    ),
                  ),
                  SizedBox(height: 24),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

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
              child: Icon(Icons.support_agent_rounded, color: ThemeService.primaryColor, size: 20),
            ),
            const SizedBox(width: 10),
            const Text(
              'Assistance',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, letterSpacing: -0.5),
            ),
          ],
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_error!, style: TextStyle(color: Colors.red)),
                      SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchTickets,
                        child: Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : _tickets.isEmpty
                  ? Center(child: Text('Aucun ticket trouvé'))
                  : RefreshIndicator(
                      onRefresh: _fetchTickets,
                      child: ListView.builder(
                        padding: EdgeInsets.all(16),
                        itemCount: _tickets.length,
                        itemBuilder: (context, index) {
                          final ticket = _tickets[index];
                          final status = ticket['status'] ?? 1;
                          
                          return Card(
                            margin: EdgeInsets.only(bottom: 12),
                            child: ListTile(
                              contentPadding: EdgeInsets.all(16),
                              title: Text(
                                ticket['subject'] ?? 'Sans sujet',
                                style: TextStyle(fontWeight: FontWeight.bold),
                              ),
                              subtitle: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  SizedBox(height: 8),
                                  Text(
                                    ticket['description'] ?? '',
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  SizedBox(height: 12),
                                  Row(
                                    children: [
                                      Container(
                                        padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: _getStatusColor(status).withOpacity(0.1),
                                          borderRadius: BorderRadius.circular(4),
                                          border: Border.all(color: _getStatusColor(status).withOpacity(0.5)),
                                        ),
                                        child: Text(
                                          _getStatusText(status),
                                          style: TextStyle(
                                            color: _getStatusColor(status),
                                            fontSize: 12,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                      SizedBox(width: 8),
                                      Text(
                                        'UID: ${ticket['_uid']}',
                                        style: TextStyle(
                                          fontSize: 12,
                                          color: isDark ? Theme.of(context).colorScheme.onSurface.withOpacity(0.54) : Colors.black54,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                              onTap: () {
                                // TODO: Navigate to ticket details
                              },
                            ),
                          );
                        },
                      ),
                    ),
      floatingActionButton: FloatingActionButton(
        onPressed: _showCreateTicketDialog,
        child: Icon(Icons.add),
        tooltip: 'Nouveau ticket',
      ),
    );
  }
}

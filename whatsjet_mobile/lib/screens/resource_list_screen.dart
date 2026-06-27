import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/resource.dart';

class ResourceListScreen extends StatefulWidget {
  const ResourceListScreen({super.key});

  @override
  State<ResourceListScreen> createState() => _ResourceListScreenState();
}

class _ResourceListScreenState extends State<ResourceListScreen> {
  final _searchController = TextEditingController();
  List<Resource> _resources = [];
  List<Resource> _filteredResources = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadResources();
    _searchController.addListener(_filterResources);
  }

  Future<void> _loadResources() async {
    setState(() {
      _isLoading = true;
    });

    final resources = await ApiService().fetchResources();

    setState(() {
      _resources = resources;
      _filteredResources = resources;
      _isLoading = false;
    });
  }

  void _filterResources() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredResources = _resources.where((resource) {
        return resource.title.toLowerCase().contains(query) ||
            (resource.description?.toLowerCase().contains(query) ?? false);
      }).toList();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF075E54);

    return Scaffold(
      appBar: AppBar(
        title: Text('Ressources & Bibliothèque'),
        backgroundColor: primaryColor,
        foregroundColor: Theme.of(context).colorScheme.onSurface,
      ),
      backgroundColor: Colors.grey.shade50,
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: EdgeInsets.all(12.0),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Rechercher un document...',
                prefixIcon: Icon(Icons.search, color: primaryColor),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: Icon(Icons.clear),
                        onPressed: () => _searchController.clear(),
                      )
                    : null,
                filled: true,
                fillColor: Theme.of(context).colorScheme.onSurface,
                contentPadding: EdgeInsets.symmetric(vertical: 0, horizontal: 16),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(30),
                  borderSide: BorderSide(color: Colors.grey.shade300),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(30),
                  borderSide: BorderSide(color: Colors.grey.shade200),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(30),
                  borderSide: BorderSide(color: primaryColor),
                ),
              ),
            ),
          ),

          // Resource List View
          Expanded(
            child: _isLoading
                ? Center(
                    child: CircularProgressIndicator(color: primaryColor),
                  )
                : _filteredResources.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.folder_open_outlined, size: 64, color: Colors.grey.shade400),
                            SizedBox(height: 16),
                            Text(
                              'Aucune ressource partagée.',
                              style: TextStyle(color: Colors.grey.shade600, fontSize: 16),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadResources,
                        color: primaryColor,
                        child: ListView.builder(
                          padding: EdgeInsets.all(12),
                          itemCount: _filteredResources.length,
                          itemBuilder: (context, index) {
                            final resource = _filteredResources[index];
                            return Card(
                              elevation: 1.5,
                              margin: EdgeInsets.only(bottom: 12),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Padding(
                                padding: EdgeInsets.all(16.0),
                                child: Row(
                                  children: [
                                    Container(
                                      padding: EdgeInsets.all(12),
                                      decoration: BoxDecoration(
                                        color: primaryColor.withAlpha(26),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Icon(
                                        Icons.insert_drive_file_outlined,
                                        color: primaryColor,
                                        size: 28,
                                      ),
                                    ),
                                    SizedBox(width: 16),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            resource.title,
                                            style: TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 16,
                                            ),
                                          ),
                                          if (resource.description != null &&
                                              resource.description!.isNotEmpty) ...[
                                            SizedBox(height: 4),
                                            Text(
                                              resource.description!,
                                              style: TextStyle(
                                                color: Colors.grey.shade600,
                                                fontSize: 13,
                                              ),
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ],
                                          SizedBox(height: 8),
                                          Text(
                                            'Nom : ${resource.fileName}',
                                            style: TextStyle(
                                              color: Colors.grey.shade500,
                                              fontSize: 12,
                                              fontStyle: FontStyle.italic,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    SizedBox(width: 8),
                                    IconButton(
                                      icon: Icon(Icons.file_download, color: primaryColor, size: 28),
                                      onPressed: () {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(
                                            content: Text('Téléchargement de : ${resource.fileName}'),
                                            action: SnackBarAction(
                                              label: 'Copier le lien',
                                              onPressed: () {
                                                // Normally, you would download/open the URL.
                                                // Sharing/copying is a fallback.
                                              },
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ],
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

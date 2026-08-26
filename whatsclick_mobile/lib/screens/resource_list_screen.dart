import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import '../services/api_service.dart';
import '../models/resource.dart';

/// Turns a Google Drive / YouTube share link into its embeddable player
/// URL, so the video plays inline instead of opening Drive/YouTube itself.
/// Falls back to the original url for anything else (loaded as-is in the
/// webview, which still works for many direct video/player pages).
String _toEmbedVideoUrl(String url) {
  final driveMatch = RegExp(r'drive\.google\.com/file/d/([a-zA-Z0-9_-]+)').firstMatch(url) ??
      RegExp(r'drive\.google\.com/open\?id=([a-zA-Z0-9_-]+)').firstMatch(url);
  if (driveMatch != null) {
    return 'https://drive.google.com/file/d/${driveMatch.group(1)}/preview';
  }

  final youtubeMatch = RegExp(r'youtu\.be/([a-zA-Z0-9_-]+)').firstMatch(url) ??
      RegExp(r'[?&]v=([a-zA-Z0-9_-]+)').firstMatch(url);
  if (youtubeMatch != null && url.contains('you')) {
    return 'https://www.youtube.com/embed/${youtubeMatch.group(1)}';
  }

  return url;
}

class _VideoPlayerScreen extends StatelessWidget {
  final String url;
  final String title;

  const _VideoPlayerScreen({required this.url, required this.title});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(title, style: const TextStyle(fontSize: 16)),
        leading: IconButton(
          icon: const Icon(Icons.close_rounded),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: InAppWebView(
        initialUrlRequest: URLRequest(url: WebUri(_toEmbedVideoUrl(url))),
        initialSettings: InAppWebViewSettings(
          javaScriptEnabled: true,
          mediaPlaybackRequiresUserGesture: false,
          allowsInlineMediaPlayback: true,
        ),
      ),
    );
  }
}

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

    if (!mounted) return;
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
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: primaryColor.withAlpha(30),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(Icons.menu_book_rounded, color: primaryColor, size: 20),
            ),
            const SizedBox(width: 10),
            const Text(
              'Ressources',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, letterSpacing: -0.5),
            ),
          ],
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
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
                                        color: (resource.fileName != null && resource.fileName!.isNotEmpty)
                                            ? primaryColor.withAlpha(26)
                                            : const Color(0xFFDC2626).withAlpha(26),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Icon(
                                        (resource.fileName != null && resource.fileName!.isNotEmpty)
                                            ? Icons.insert_drive_file_outlined
                                            : Icons.play_circle_fill_rounded,
                                        color: (resource.fileName != null && resource.fileName!.isNotEmpty)
                                            ? primaryColor
                                            : const Color(0xFFDC2626),
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
                                          if (resource.fileName != null && resource.fileName!.isNotEmpty) ...[
                                            SizedBox(height: 8),
                                            Text(
                                              'Nom : ${resource.fileName}',
                                              style: TextStyle(
                                                color: Colors.grey.shade500,
                                                fontSize: 12,
                                                fontStyle: FontStyle.italic,
                                              ),
                                            ),
                                          ] else if (resource.videoUrl != null && resource.videoUrl!.isNotEmpty) ...[
                                            SizedBox(height: 8),
                                            Text(
                                              'Vidéo',
                                              style: TextStyle(
                                                color: Colors.grey.shade500,
                                                fontSize: 12,
                                                fontStyle: FontStyle.italic,
                                              ),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                    SizedBox(width: 8),
                                    IconButton(
                                      icon: Icon(Icons.visibility, color: primaryColor, size: 28),
                                      onPressed: () {
                                        showDialog(
                                          context: context,
                                          builder: (BuildContext context) {
                                            return AlertDialog(
                                              title: Text(resource.title),
                                              content: SingleChildScrollView(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  mainAxisSize: MainAxisSize.min,
                                                  children: [
                                                    if (resource.description != null && resource.description!.isNotEmpty)
                                                      Html(data: resource.description!),
                                                    SizedBox(height: 16),
                                                    if (resource.videoUrl != null && resource.videoUrl!.isNotEmpty)
                                                      Padding(
                                                        padding: const EdgeInsets.only(bottom: 8),
                                                        child: ElevatedButton.icon(
                                                          onPressed: () {
                                                            Navigator.of(context).push(
                                                              MaterialPageRoute(
                                                                fullscreenDialog: true,
                                                                builder: (_) => _VideoPlayerScreen(
                                                                  url: resource.videoUrl!,
                                                                  title: resource.title,
                                                                ),
                                                              ),
                                                            );
                                                          },
                                                          icon: Icon(Icons.play_circle_fill_rounded, size: 20),
                                                          label: Text('Regarder la vidéo'),
                                                          style: ElevatedButton.styleFrom(
                                                            backgroundColor: const Color(0xFFDC2626),
                                                            foregroundColor: Colors.white,
                                                          ),
                                                        ),
                                                      ),
                                                    if (resource.fileName != null && resource.fileName!.isNotEmpty && resource.downloadUrl != null)
                                                      ElevatedButton.icon(
                                                        onPressed: () async {
                                                          final url = Uri.parse(resource.downloadUrl!);
                                                          if (await canLaunchUrl(url)) {
                                                            await launchUrl(url, mode: LaunchMode.externalApplication);
                                                          } else {
                                                            ScaffoldMessenger.of(context).showSnackBar(
                                                              SnackBar(content: Text('Impossible d\'ouvrir le lien')),
                                                            );
                                                          }
                                                        },
                                                        icon: Icon(Icons.download, size: 20),
                                                        label: Text('Télécharger le fichier joint'),
                                                        style: ElevatedButton.styleFrom(
                                                          backgroundColor: primaryColor,
                                                          foregroundColor: Colors.white,
                                                        ),
                                                      ),
                                                  ],
                                                ),
                                              ),
                                              actions: [
                                                TextButton(
                                                  child: Text('Fermer', style: TextStyle(color: primaryColor)),
                                                  onPressed: () {
                                                    Navigator.of(context).pop();
                                                  },
                                                ),
                                              ],
                                            );
                                          },
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

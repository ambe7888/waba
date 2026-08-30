import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

class UpdateDialog extends StatefulWidget {
  final Map<String, dynamic> updateInfo;

  const UpdateDialog({
    super.key,
    required this.updateInfo,
  });

  static Future<void> show(BuildContext context, Map<String, dynamic> updateInfo) async {
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (context) => UpdateDialog(updateInfo: updateInfo),
    );
  }

  @override
  State<UpdateDialog> createState() => _UpdateDialogState();
}

class _UpdateDialogState extends State<UpdateDialog> {
  bool _isDownloading = false;
  double _progress = 0.0;
  String _progressStatus = '';
  String? _errorMessage;
  http.Client? _httpClient;

  @override
  void dispose() {
    _httpClient?.close();
    super.dispose();
  }

  Future<void> _startDownloadAndInstall() async {
    final apkUrl = widget.updateInfo['apk_url']?.toString() ?? '';
    if (apkUrl.isEmpty) {
      setState(() {
        _errorMessage = 'URL de téléchargement invalide.';
      });
      return;
    }

    setState(() {
      _isDownloading = true;
      _progress = 0.0;
      _progressStatus = 'Initialisation...';
      _errorMessage = null;
    });

    try {
      _httpClient = http.Client();
      final request = http.Request('GET', Uri.parse(apkUrl));
      final response = await _httpClient!.send(request);

      if (response.statusCode != 200) {
        throw Exception('Erreur serveur (${response.statusCode})');
      }

      final contentLength = response.contentLength ?? 0;
      final tempDir = await getTemporaryDirectory();
      final filePath = '${tempDir.path}/whatsclick_update.apk';
      final file = File(filePath);

      if (await file.exists()) {
        await file.delete();
      }

      int downloadedBytes = 0;
      final sink = file.openWrite();

      await for (final chunk in response.stream) {
        if (!mounted) break;
        downloadedBytes += chunk.length;
        sink.add(chunk);

        if (contentLength > 0) {
          final progressVal = downloadedBytes / contentLength;
          final currentMb = (downloadedBytes / (1024 * 1024)).toStringAsFixed(1);
          final totalMb = (contentLength / (1024 * 1024)).toStringAsFixed(1);
          final percent = (progressVal * 100).toInt();

          setState(() {
            _progress = progressVal;
            _progressStatus = '$currentMb Mo / $totalMb Mo ($percent%)';
          });
        } else {
          final currentMb = (downloadedBytes / (1024 * 1024)).toStringAsFixed(1);
          setState(() {
            _progressStatus = '$currentMb Mo téléchargés...';
          });
        }
      }

      await sink.flush();
      await sink.close();

      if (!mounted) return;

      setState(() {
        _progress = 1.0;
        _progressStatus = 'Lancement de l\'installateur...';
      });

      // Lancement de l'installateur APK Android natif
      final result = await OpenFilex.open(filePath);

      if (!mounted) return;

      if (result.type != ResultType.done) {
        setState(() {
          _isDownloading = false;
          _errorMessage = 'Impossible d\'ouvrir l\'APK : ${result.message}';
        });
      } else {
        Navigator.of(context).pop();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isDownloading = false;
          _errorMessage = 'Erreur lors du téléchargement : $e';
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final version = widget.updateInfo['version'] ?? 'Nouvelle version';
    final changeLog = widget.updateInfo['change_log']?.toString() ?? '';

    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      title: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFF00B37E).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.system_update_rounded,
              color: Color(0xFF00B37E),
              size: 24,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              _isDownloading ? 'Mise à jour en cours...' : 'Mise à jour dispo ! 🚀',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
      content: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            if (!_isDownloading) ...[
              Text(
                'Une nouvelle version ($version) de WhatsClick est disponible.',
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
              ),
              if (changeLog.isNotEmpty) ...[
                const SizedBox(height: 12),
                const Text(
                  'Nouveautés :',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                ),
                const SizedBox(height: 4),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    changeLog,
                    style: TextStyle(
                      fontSize: 13,
                      color: theme.colorScheme.onSurface.withValues(alpha: 0.8),
                    ),
                  ),
                ),
              ],
            ] else ...[
              const Text(
                'Téléchargement du fichier d\'installation APK...',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
              ),
              const SizedBox(height: 16),
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: LinearProgressIndicator(
                  value: _progress > 0 ? _progress : null,
                  minHeight: 10,
                  backgroundColor: const Color(0xFF00B37E).withValues(alpha: 0.15),
                  valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF00B37E)),
                ),
              ),
              const SizedBox(height: 10),
              Center(
                child: Text(
                  _progressStatus,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF00B37E),
                  ),
                ),
              ),
            ],
            if (_errorMessage != null) ...[
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.red.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.red.withValues(alpha: 0.3)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.error_outline, color: Colors.red, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _errorMessage!,
                        style: const TextStyle(color: Colors.red, fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
      actions: [
        if (!_isDownloading) ...[
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(
              'Plus tard',
              style: TextStyle(color: theme.colorScheme.onSurface.withValues(alpha: 0.6)),
            ),
          ),
          ElevatedButton.icon(
            onPressed: _startDownloadAndInstall,
            icon: const Icon(Icons.download_rounded, size: 18),
            label: const Text('Installer la mise à jour'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF00B37E),
              foregroundColor: Colors.white,
              elevation: 0,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            ),
          ),
        ] else ...[
          TextButton(
            onPressed: () {
              _httpClient?.close();
              setState(() {
                _isDownloading = false;
                _progressStatus = 'Annulé';
              });
            },
            child: const Text('Annuler', style: TextStyle(color: Colors.red)),
          ),
        ],
      ],
    );
  }
}

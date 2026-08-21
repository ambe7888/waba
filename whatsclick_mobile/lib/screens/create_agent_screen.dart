import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../config/app_config.dart';
import '../services/theme_service.dart';

/// Agent creation has no mobile API endpoint — permissions/roles can only be
/// assigned from the web console. This screen exists so tapping "Ajouter un
/// agent" leads somewhere concrete instead of a dead end, and explains why.
class CreateAgentScreen extends StatelessWidget {
  const CreateAgentScreen({super.key});

  Future<void> _openWebConsole(BuildContext context) async {
    final url = Uri.parse('${baseUrl}vendor-console/users');
    final opened = await launchUrl(url, mode: LaunchMode.externalApplication);
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir le navigateur.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Ajouter un agent', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: ThemeService.primaryColor.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.person_add_alt_1_rounded,
                  color: ThemeService.primaryColor, size: 32),
            ),
            const SizedBox(height: 20),
            Text(
              'La création d\'un agent se fait sur le site web',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: isDark ? Colors.white : const Color(0xFF1E293B),
              ),
            ),
            const SizedBox(height: 10),
            Text(
              'Un nouvel agent doit recevoir des autorisations (accès aux contacts, '
              'aux campagnes, aux modèles, etc.). Cette étape n\'est disponible que '
              'depuis la version web, où vous pouvez définir précisément ce que '
              'l\'agent peut voir et faire.',
              style: TextStyle(
                fontSize: 14,
                height: 1.5,
                color: isDark ? Colors.white70 : Colors.black54,
              ),
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Marche à suivre',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: isDark ? Colors.white : const Color(0xFF1E293B),
                    ),
                  ),
                  const SizedBox(height: 12),
                  _buildStep(isDark, '1', 'Connectez-vous sur whats-click.com'),
                  _buildStep(isDark, '2', 'Ouvrez le Menu → « Membres de l\'équipe »'),
                  _buildStep(isDark, '3', 'Cliquez sur « Ajouter un Nouveau Membre »'),
                  _buildStep(isDark, '4', 'Renseignez ses informations et ses autorisations',
                      isLast: true),
                ],
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _openWebConsole(context),
                icon: const Icon(Icons.open_in_new_rounded, size: 18),
                label: const Text('Ouvrir la version web'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: ThemeService.primaryColor,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ),
            const SizedBox(height: 10),
            Text(
              'Une fois créé, l\'agent apparaîtra automatiquement ici. Vous pourrez '
              'ensuite modifier ses informations de base ou bloquer son accès '
              'directement depuis l\'application mobile.',
              style: TextStyle(
                fontSize: 12,
                color: isDark ? Colors.white38 : Colors.black45,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStep(bool isDark, String number, String text, {bool isLast = false}) {
    return Padding(
      padding: EdgeInsets.only(bottom: isLast ? 0 : 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 20,
            height: 20,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: ThemeService.primaryColor.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: Text(
              number,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: ThemeService.primaryColor,
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontSize: 13,
                height: 1.3,
                color: isDark ? Colors.white70 : Colors.black87,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

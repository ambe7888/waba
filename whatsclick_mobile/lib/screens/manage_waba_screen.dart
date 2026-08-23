import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'whatsapp_embedded_signup_screen.dart';

class ManageWabaScreen extends StatefulWidget {
  final Map<String, dynamic> wabaData;

  const ManageWabaScreen({
    super.key,
    required this.wabaData,
  });

  @override
  State<ManageWabaScreen> createState() => _ManageWabaScreenState();
}

class _ManageWabaScreenState extends State<ManageWabaScreen> {
  late Map<String, dynamic> _data;
  bool _isConnecting = false;

  @override
  void initState() {
    super.initState();
    _data = widget.wabaData;
  }

  Future<void> _connectWhatsApp() async {
    setState(() => _isConnecting = true);
    final result = await ApiService().fetchWhatsAppEmbeddedSignupUrl();
    if (!mounted) return;
    if (result.url == null) {
      setState(() => _isConnecting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result.message ?? 'Impossible de démarrer la connexion. Réessayez.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final success = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => WhatsAppEmbeddedSignupScreen(signupUrl: result.url!)),
    );

    if (!mounted) return;
    setState(() => _isConnecting = false);

    if (success == true) {
      final freshStats = await ApiService().fetchDashboardStats();
      if (!mounted) return;
      final freshWaba = freshStats?['whatsapp_setup'] as Map?;
      final freshVendor = freshStats?['vendorInfo'] as Map?;
      setState(() {
        _data = Map<String, dynamic>.from(freshWaba ?? freshVendor ?? _data);
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('WhatsApp connecté avec succès !'), backgroundColor: Color(0xFF10B981)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    // Données réelles issues du vendorInfo / whatsapp_setup
    final phone = _data['phone_number']?.toString() ?? _data['whatsapp_phone_number']?.toString() ?? '';
    final phoneId = _data['phone_number_id']?.toString() ?? _data['whatsapp_phone_number_id']?.toString() ?? '';
    final wabaId = _data['waba_id']?.toString() ?? _data['whatsapp_waba_id']?.toString() ?? '';
    final appId = _data['app_id']?.toString() ?? _data['whatsapp_app_id']?.toString() ?? '';
    final businessName = _data['title']?.toString() ?? '';
    final quality = _data['whatsapp_quality_rating']?.toString() ?? '';
    final status = _data['whatsapp_verification_status']?.toString() ?? '';
    final isActive = _data['is_connected'] == true || phone.isNotEmpty;

    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded,
              color: isDark ? Colors.white : Colors.black87),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'WhatsApp API',
          style: TextStyle(
            color: isDark ? Colors.white : Colors.black87,
            fontSize: 18,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: isDark ? ThemeService.darkCard : Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: isDark ? const Color(0xFF334155) : const Color(0xFFE5E7EB),
              width: 1.5,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Statut connexion
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: (isActive ? const Color(0xFF10B981) : const Color(0xFFDC2626)).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      Icons.security_rounded,
                      color: isActive ? const Color(0xFF10B981) : const Color(0xFFDC2626),
                      size: 28,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isActive ? 'Connecté' : 'Déconnecté',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w900,
                            color: isActive ? const Color(0xFF10B981) : const Color(0xFFDC2626),
                          ),
                        ),
                        Text(
                          'Statut de connexion WhatsApp API',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDark ? Colors.white54 : const Color(0xFF6B7280),
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),

              if (!isActive) ...[
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: _isConnecting ? null : _connectWhatsApp,
                    icon: _isConnecting
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Icon(Icons.facebook_rounded),
                    label: Text(_isConnecting ? 'Connexion en cours...' : 'Activer mon compte WhatsApp API'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF10B981),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Connexion sécurisée via Meta — aucune clé technique à saisir.',
                  style: TextStyle(fontSize: 11.5, color: isDark ? Colors.white38 : const Color(0xFF9CA3AF)),
                ),
              ],

              const SizedBox(height: 20),
              Divider(color: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6), thickness: 1.5),
              const SizedBox(height: 20),

              // Titre informations
              Text(
                'DÉTAILS DU COMPTE',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                  color: isDark ? Colors.white54 : const Color(0xFF6B7280),
                  letterSpacing: 1.0,
                ),
              ),
              const SizedBox(height: 16),

              // Informations du compte (2 par ligne)
              if (businessName.isEmpty && phone.isEmpty && wabaId.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  child: Center(
                    child: Text(
                      'Aucune information disponible.\nVeuillez configurer votre compte WhatsApp API.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: isDark ? Colors.white54 : const Color(0xFF6B7280),
                        fontSize: 13,
                        height: 1.6,
                      ),
                    ),
                  ),
                )
              else
                Wrap(
                  spacing: 16,
                  runSpacing: 20,
                  children: [
                    if (businessName.isNotEmpty)
                      _buildGridItem('Entreprise', businessName, Icons.business_rounded, isDark),
                    if (phone.isNotEmpty)
                      _buildGridItem('Numéro', '+$phone', Icons.phone_rounded, isDark),
                    if (phoneId.isNotEmpty)
                      _buildGridItem('ID Téléphone', phoneId, Icons.tag_rounded, isDark),
                    if (wabaId.isNotEmpty)
                      _buildGridItem('WABA ID', wabaId, Icons.account_balance_wallet_rounded, isDark),
                    if (appId.isNotEmpty)
                      _buildGridItem('APP ID', appId, Icons.apps_rounded, isDark),
                    if (status.isNotEmpty)
                      _buildGridItem('Statut', _localizeStatus(status), Icons.verified_rounded, isDark),
                    if (quality.isNotEmpty)
                      _buildGridItem('Qualité', _localizeQuality(quality), Icons.star_rounded, isDark),
                  ],
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildGridItem(String label, String value, IconData icon, bool isDark) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final screenWidth = MediaQuery.of(context).size.width;
        final itemWidth = (screenWidth - 32 - 40 - 16) / 2;

        return SizedBox(
          width: itemWidth > 0 ? itemWidth : 150,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(icon, size: 14, color: const Color(0xFF10B981)),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      label,
                      style: TextStyle(
                        fontSize: 11,
                        color: isDark ? Colors.white54 : const Color(0xFF6B7280),
                        fontWeight: FontWeight.w600,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                value,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: isDark ? Colors.white : const Color(0xFF111827),
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        );
      }
    );
  }

  String _localizeStatus(String status) {
    switch (status.toLowerCase()) {
      case 'verified':
        return 'Vérifié';
      case 'pending':
        return 'En attente';
      case 'rejected':
        return 'Rejeté';
      default:
        return status;
    }
  }

  String _localizeQuality(String quality) {
    switch (quality.toLowerCase()) {
      case 'high':
        return 'Élevée';
      case 'medium':
        return 'Moyenne';
      case 'low':
        return 'Faible';
      default:
        return quality;
    }
  }
}

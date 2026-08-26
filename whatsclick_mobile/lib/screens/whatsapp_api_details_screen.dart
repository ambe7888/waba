import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

/// Mirrors the web dashboard's "Tableau API Meta" + "Paramètres API" pages:
/// phone number, business profile, health/verification, payment status, and
/// the mandatory test contact number, all in one screen with a refresh
/// action equivalent to the web's "Actualiser" button.
class WhatsAppApiDetailsScreen extends StatefulWidget {
  /// When true, this screen was opened right after a successful Embedded
  /// Signup - the test contact number prompt is emphasized since it's
  /// required before campaigns can be tested.
  final bool justConnected;

  const WhatsAppApiDetailsScreen({super.key, this.justConnected = false});

  @override
  State<WhatsAppApiDetailsScreen> createState() => _WhatsAppApiDetailsScreenState();
}

class _WhatsAppApiDetailsScreenState extends State<WhatsAppApiDetailsScreen> {
  Map<String, dynamic>? _data;
  bool _isLoading = true;
  bool _isRefreshing = false;
  bool _isSavingTestContact = false;
  String? _loadError;
  final _testContactController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _testContactController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });
    final data = await ApiService().fetchWhatsAppApiDetails();
    if (!mounted) return;
    if (data == null) {
      setState(() {
        _isLoading = false;
        _loadError = 'Impossible de charger les informations. Vérifiez votre connexion.';
      });
      return;
    }
    setState(() {
      _data = data;
      _isLoading = false;
      _testContactController.text = data['testContactNumber']?.toString() ?? '';
    });
  }

  Future<void> _refresh() async {
    setState(() => _isRefreshing = true);
    final result = await ApiService().refreshWhatsAppApiDetails();
    if (!mounted) return;
    setState(() => _isRefreshing = false);
    if (result.data == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result.message ?? 'Échec de l\'actualisation. Réessayez.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    setState(() {
      _data = result.data;
      _testContactController.text = result.data!['testContactNumber']?.toString() ?? '';
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Informations actualisées'), backgroundColor: Color(0xFF10B981)),
    );
  }

  Future<void> _saveTestContact() async {
    final value = _testContactController.text.trim();
    if (value.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Le numéro de test est obligatoire.'), backgroundColor: Colors.red),
      );
      return;
    }
    setState(() => _isSavingTestContact = true);
    final result = await ApiService().saveWhatsAppTestContact(value);
    if (!mounted) return;
    setState(() => _isSavingTestContact = false);
    if (result.data == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result.message ?? 'Échec de l\'enregistrement. Vérifiez le numéro.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    setState(() => _data = result.data);
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Numéro de test enregistré'), backgroundColor: Color(0xFF10B981)),
    );
  }

  Future<void> _openUrl(String? url) async {
    if (url == null || url.isEmpty) return;
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, color: isDark ? Colors.white : Colors.black87),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'Paramètres WhatsApp API',
          style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 17, fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            icon: _isRefreshing
                ? SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: isDark ? Colors.white : Colors.black87),
                  )
                : Icon(Icons.refresh_rounded, color: isDark ? Colors.white : Colors.black87),
            tooltip: 'Actualiser',
            onPressed: _isRefreshing ? null : _refresh,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _loadError != null
              ? _buildErrorState(isDark)
              : RefreshIndicator(
                  onRefresh: _refresh,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      if (widget.justConnected) _buildJustConnectedBanner(isDark),
                      _buildTestContactCard(isDark),
                      const SizedBox(height: 16),
                      _buildAccountCard(isDark),
                      const SizedBox(height: 16),
                      _buildBusinessProfileCard(isDark),
                      const SizedBox(height: 16),
                      _buildHealthCard(isDark),
                      const SizedBox(height: 16),
                      _buildLinksCard(isDark),
                    ],
                  ),
                ),
    );
  }

  Widget _buildErrorState(bool isDark) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.wifi_off_rounded, size: 48, color: isDark ? Colors.white38 : Colors.black26),
            const SizedBox(height: 12),
            Text(_loadError!, textAlign: TextAlign.center, style: TextStyle(color: isDark ? Colors.white70 : Colors.black54)),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: _load, child: const Text('Réessayer')),
          ],
        ),
      ),
    );
  }

  Widget _buildJustConnectedBanner(bool isDark) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF10B981).withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.3)),
      ),
      child: const Row(
        children: [
          Icon(Icons.check_circle_rounded, color: Color(0xFF10B981)),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Connexion réussie ! Définissez maintenant votre numéro de test pour pouvoir tester vos campagnes.',
              style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF065F46)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _card({required bool isDark, required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: isDark ? ThemeService.darkCard : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? const Color(0xFF334155) : const Color(0xFFE5E7EB), width: 1.5),
      ),
      child: child,
    );
  }

  Widget _cardTitle(String text, bool isDark, {IconData? icon}) {
    return Row(
      children: [
        if (icon != null) ...[
          Icon(icon, size: 15, color: const Color(0xFF10B981)),
          const SizedBox(width: 6),
        ],
        Text(
          text,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w800,
            letterSpacing: 1.0,
            color: isDark ? Colors.white54 : const Color(0xFF6B7280),
          ),
        ),
      ],
    );
  }

  Widget _row(String label, String? value, bool isDark) {
    if (value == null || value.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(label, style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white54 : const Color(0xFF6B7280))),
          ),
          Expanded(
            child: Text(value, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: isDark ? Colors.white : const Color(0xFF111827))),
          ),
        ],
      ),
    );
  }

  Widget _buildTestContactCard(bool isDark) {
    final hasTestContact = (_data?['testContactNumber']?.toString().isNotEmpty ?? false);
    return _card(
      isDark: isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _cardTitle('NUMÉRO DE TEST', isDark, icon: Icons.science_rounded),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: (hasTestContact ? const Color(0xFF10B981) : Colors.red).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  hasTestContact ? 'Configuré' : 'Obligatoire',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    color: hasTestContact ? const Color(0xFF10B981) : Colors.red,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            'Numéro WhatsApp utilisé pour tester vos campagnes. Format : indicatif pays sans 0 ni +.',
            style: TextStyle(fontSize: 11.5, color: isDark ? Colors.white38 : const Color(0xFF9CA3AF)),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _testContactController,
                  keyboardType: TextInputType.phone,
                  style: TextStyle(color: isDark ? Colors.white : Colors.black87),
                  decoration: InputDecoration(
                    hintText: 'Ex : 22501020304',
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              ElevatedButton(
                onPressed: _isSavingTestContact ? null : _saveTestContact,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                child: _isSavingTestContact
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('Enregistrer'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAccountCard(bool isDark) {
    final d = _data ?? {};
    final isConnected = d['isConnected'] == true;
    return _card(
      isDark: isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _cardTitle('COMPTE WHATSAPP BUSINESS (WABA)', isDark, icon: Icons.business_rounded),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: (isConnected ? const Color(0xFF10B981) : Colors.red).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  isConnected ? 'Connecté' : 'Déconnecté',
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: isConnected ? const Color(0xFF10B981) : Colors.red),
                ),
              ),
            ],
          ),
          _row('Numéro', d['displayPhoneNumber']?.toString(), isDark),
          _row('Nom vérifié', d['verifiedName']?.toString(), isDark),
          _row('ID Téléphone', d['phoneNumberId']?.toString(), isDark),
          _row('WABA ID', d['wabaId']?.toString(), isDark),
          _row('Qualité', d['qualityRating']?.toString(), isDark),
          _row('Limite de messagerie', d['messagingLimitTier']?.toString(), isDark),
          _row('Connecté le', d['embeddedSignupDoneAt']?.toString(), isDark),
        ],
      ),
    );
  }

  Widget _buildBusinessProfileCard(bool isDark) {
    final profile = (_data?['businessProfile'] as Map?) ?? {};
    final photoUrl = profile['profilePictureUrl']?.toString();
    final websites = (profile['websites'] as List?)?.join(', ');
    return _card(
      isDark: isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardTitle('PROFIL D\'ENTREPRISE', isDark, icon: Icons.storefront_rounded),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 28,
                backgroundColor: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6),
                backgroundImage: (photoUrl != null && photoUrl.isNotEmpty) ? NetworkImage(photoUrl) : null,
                child: (photoUrl == null || photoUrl.isEmpty)
                    ? Icon(Icons.storefront_outlined, color: isDark ? Colors.white38 : Colors.black26)
                    : null,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _row('À propos', profile['about']?.toString(), isDark),
                    _row('Description', profile['description']?.toString(), isDark),
                    _row('Email', profile['email']?.toString(), isDark),
                    _row('Adresse', profile['address']?.toString(), isDark),
                    _row('Site web', websites, isDark),
                    if ((profile['about']?.toString().isEmpty ?? true) &&
                        (profile['description']?.toString().isEmpty ?? true) &&
                        (profile['email']?.toString().isEmpty ?? true))
                      Text(
                        'Aucune information de profil disponible. Actualisez après connexion.',
                        style: TextStyle(fontSize: 12, color: isDark ? Colors.white38 : const Color(0xFF9CA3AF)),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildHealthCard(bool isDark) {
    final health = _data?['health'] as Map?;
    return _card(
      isDark: isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardTitle('SANTÉ & CAPACITÉ D\'ENVOI', isDark, icon: Icons.favorite_rounded),
          if (health == null)
            Padding(
              padding: const EdgeInsets.only(top: 10),
              child: Text(
                'Aucune donnée de santé pour le moment. Appuyez sur Actualiser.',
                style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white38 : const Color(0xFF9CA3AF)),
              ),
            )
          else ...[
            _row('Peut envoyer un message', health['canSendMessage']?.toString(), isDark),
            _row('Dernière vérification', health['updatedAtFormatted']?.toString(), isDark),
          ],
        ],
      ),
    );
  }

  Widget _buildLinksCard(bool isDark) {
    final d = _data ?? {};
    final verifUrl = d['businessVerificationUrl']?.toString();
    final paymentUrl = d['paymentManagementUrl']?.toString();
    if ((verifUrl == null || verifUrl.isEmpty) && (paymentUrl == null || paymentUrl.isEmpty)) {
      return const SizedBox.shrink();
    }
    return _card(
      isDark: isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardTitle('GESTION META', isDark, icon: Icons.open_in_new_rounded),
          const SizedBox(height: 12),
          if (verifUrl != null && verifUrl.isNotEmpty)
            _linkButton('Vérification d\'entreprise', Icons.verified_user_rounded, () => _openUrl(verifUrl), isDark),
          if (paymentUrl != null && paymentUrl.isNotEmpty) ...[
            const SizedBox(height: 8),
            _linkButton('Gérer les paiements', Icons.credit_card_rounded, () => _openUrl(paymentUrl), isDark),
          ],
        ],
      ),
    );
  }

  Widget _linkButton(String label, IconData icon, VoidCallback onTap, bool isDark) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: onTap,
        icon: Icon(icon, size: 16),
        label: Text(label),
        style: OutlinedButton.styleFrom(
          foregroundColor: isDark ? Colors.white : Colors.black87,
          side: BorderSide(color: isDark ? const Color(0xFF334155) : const Color(0xFFE5E7EB)),
          padding: const EdgeInsets.symmetric(vertical: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
    );
  }
}

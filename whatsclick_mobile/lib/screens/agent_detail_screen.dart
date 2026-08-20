import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class AgentDetailScreen extends StatefulWidget {
  final String agentUid;
  final bool isCurrentUser;

  const AgentDetailScreen({super.key, required this.agentUid, this.isCurrentUser = false});

  @override
  State<AgentDetailScreen> createState() => _AgentDetailScreenState();
}

class _AgentDetailScreenState extends State<AgentDetailScreen> {
  bool _isLoading = true;
  bool _isSaving = false;
  bool _isDeleting = false;
  String? _error;
  bool _changed = false;

  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _mobileController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _allowLogin = true;
  String? _roleTitle;
  String? _createdAt;

  @override
  void initState() {
    super.initState();
    _loadDetail();
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _mobileController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _loadDetail() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    final data = await ApiService().fetchAgentDetail(widget.agentUid);
    if (!mounted) return;

    if (data == null) {
      setState(() {
        _error = 'Erreur lors du chargement de l\'agent.';
        _isLoading = false;
      });
      return;
    }

    final role = data['role'] as Map<String, dynamic>?;

    setState(() {
      _firstNameController.text = data['first_name']?.toString() ?? '';
      _lastNameController.text = data['last_name']?.toString() ?? '';
      _emailController.text = data['email']?.toString() ?? '';
      _mobileController.text = data['mobile_number']?.toString() ?? '';
      _allowLogin = (data['status'] == 1 || data['status'] == '1');
      _roleTitle = role?['title']?.toString();
      _createdAt = data['created_at']?.toString();
      _isLoading = false;
    });
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);

    final success = await ApiService().updateAgent(
      widget.agentUid,
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      email: _emailController.text.trim(),
      mobileNumber: _mobileController.text.trim(),
      password: _passwordController.text.trim().isEmpty ? null : _passwordController.text.trim(),
      status: _allowLogin,
    );

    if (!mounted) return;
    setState(() => _isSaving = false);

    if (success) {
      _changed = true;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Agent mis à jour avec succès'),
          backgroundColor: Color(0xFF10B981),
        ),
      );
      Navigator.of(context).pop(true);
    } else {
      final err = ApiService().lastAgentUpdateError ?? 'Impossible de mettre à jour cet agent';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(err), backgroundColor: Colors.red),
      );
    }
  }

  Future<void> _toggleLoginQuick(bool value) async {
    setState(() => _allowLogin = value);
    final success = await ApiService().toggleAgentStatus(widget.agentUid, value);
    if (!mounted) return;
    if (success) {
      _changed = true;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(value
              ? 'Connexion autorisée pour cet agent'
              : 'Connexion bloquée pour cet agent'),
          backgroundColor: value ? const Color(0xFF10B981) : Colors.orange,
        ),
      );
    } else {
      // revert on failure
      setState(() => _allowLogin = !value);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible de modifier le statut de connexion')),
      );
    }
  }

  Future<void> _delete() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer cet agent ?'),
        content: Text(
            'Voulez-vous vraiment supprimer ${_firstNameController.text} ${_lastNameController.text} ? Cette action est irréversible.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isDeleting = true);
    final success = await ApiService().deleteAgent(widget.agentUid);
    if (!mounted) return;
    setState(() => _isDeleting = false);

    if (success) {
      Navigator.of(context).pop(true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible de supprimer cet agent')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        Navigator.of(context).pop(_changed);
      },
      child: Scaffold(
        backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
        appBar: AppBar(
          title: const Text('Détails de l\'agent', style: TextStyle(fontWeight: FontWeight.bold)),
          backgroundColor: Colors.transparent,
          elevation: 0,
          actions: [
            if (!_isLoading && _error == null && !widget.isCurrentUser)
              IconButton(
                icon: _isDeleting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.red),
                      )
                    : const Icon(Icons.delete_outline_rounded, color: Colors.red),
                onPressed: _isDeleting ? null : _delete,
              ),
          ],
        ),
        body: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(_error!, style: const TextStyle(color: Colors.red)),
                        const SizedBox(height: 12),
                        ElevatedButton(onPressed: _loadDetail, child: const Text('Réessayer')),
                      ],
                    ),
                  )
                : Form(
                    key: _formKey,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                      children: [
                        // ── Login access card ─────────────────────────
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: (_allowLogin ? const Color(0xFF10B981) : const Color(0xFFEF4444))
                                .withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                                color: (_allowLogin ? const Color(0xFF10B981) : const Color(0xFFEF4444))
                                    .withValues(alpha: 0.25)),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                _allowLogin ? Icons.lock_open_rounded : Icons.lock_outline_rounded,
                                color: _allowLogin ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                                size: 26,
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Autoriser la connexion',
                                      style: TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.bold,
                                        color: isDark ? Colors.white : const Color(0xFF1E293B),
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      _allowLogin
                                          ? 'Cet agent peut se connecter à l\'application'
                                          : 'Cet agent ne peut plus se connecter',
                                      style: TextStyle(
                                          fontSize: 12,
                                          color: isDark ? Colors.white54 : Colors.black54),
                                    ),
                                  ],
                                ),
                              ),
                              Switch(
                                value: _allowLogin,
                                activeThumbColor: const Color(0xFF10B981),
                                onChanged: widget.isCurrentUser ? null : _toggleLoginQuick,
                              ),
                            ],
                          ),
                        ),
                        if (widget.isCurrentUser) ...[
                          const SizedBox(height: 8),
                          Text(
                            'Vous ne pouvez pas modifier votre propre accès.',
                            style: TextStyle(fontSize: 11.5, color: Colors.grey.shade500),
                          ),
                        ],

                        const SizedBox(height: 20),
                        _sectionTitle('Informations', isDark),
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isDark ? const Color(0xFF1E293B) : Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                          ),
                          child: Column(
                            children: [
                              if (_roleTitle != null) _infoRow('Rôle', _roleTitle!, isDark),
                              if (_createdAt != null) _infoRow('Membre depuis', _createdAt!, isDark),
                            ],
                          ),
                        ),

                        const SizedBox(height: 20),
                        _sectionTitle('Modifier les informations', isDark),
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isDark ? const Color(0xFF1E293B) : Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                          ),
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: TextFormField(
                                      controller: _firstNameController,
                                      decoration: const InputDecoration(labelText: 'Prénom'),
                                      validator: (v) =>
                                          (v == null || v.trim().isEmpty) ? 'Requis' : null,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: TextFormField(
                                      controller: _lastNameController,
                                      decoration: const InputDecoration(labelText: 'Nom'),
                                      validator: (v) =>
                                          (v == null || v.trim().isEmpty) ? 'Requis' : null,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              TextFormField(
                                controller: _emailController,
                                decoration: const InputDecoration(labelText: 'E-mail'),
                                keyboardType: TextInputType.emailAddress,
                                validator: (v) => (v == null || v.trim().isEmpty) ? 'Requis' : null,
                              ),
                              const SizedBox(height: 12),
                              TextFormField(
                                controller: _mobileController,
                                decoration: const InputDecoration(
                                  labelText: 'Numéro WhatsApp',
                                  helperText: 'Sans + ni 0 initial (ex: 33612345678)',
                                ),
                                keyboardType: TextInputType.phone,
                                validator: (v) {
                                  if (v == null || v.trim().isEmpty) return 'Requis';
                                  if (v.startsWith('0') || v.startsWith('+')) {
                                    return 'Sans préfixe 0 ou +';
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 12),
                              TextFormField(
                                controller: _passwordController,
                                decoration: const InputDecoration(
                                  labelText: 'Nouveau mot de passe (laisser vide si inchangé)',
                                ),
                                obscureText: true,
                                validator: (v) {
                                  if (v != null && v.isNotEmpty && v.length < 8) {
                                    return 'Minimum 8 caractères';
                                  }
                                  return null;
                                },
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 20),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: ThemeService.primaryColor,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape:
                                  RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            onPressed: _isSaving ? null : _save,
                            child: _isSaving
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                        strokeWidth: 2, color: Colors.white),
                                  )
                                : const Text('Enregistrer', style: TextStyle(fontWeight: FontWeight.bold)),
                          ),
                        ),
                      ],
                    ),
                  ),
      ),
    );
  }

  Widget _sectionTitle(String title, bool isDark) {
    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 8),
      child: Text(
        title.toUpperCase(),
        style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: Colors.grey.shade500,
            letterSpacing: 1),
      ),
    );
  }

  Widget _infoRow(String label, String value, bool isDark) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Text(label, style: TextStyle(fontSize: 13, color: Colors.grey.shade500)),
          const Spacer(),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: isDark ? Colors.white : const Color(0xFF1E293B)),
            ),
          ),
        ],
      ),
    );
  }
}

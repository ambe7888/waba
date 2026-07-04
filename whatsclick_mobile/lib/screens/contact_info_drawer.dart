import 'package:flutter/material.dart';
import '../models/contact.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class ContactInfoDrawer extends StatefulWidget {
  final Contact contact;
  final VoidCallback? onUpdate;

  const ContactInfoDrawer({super.key, required this.contact, this.onUpdate});

  @override
  State<ContactInfoDrawer> createState() => _ContactInfoDrawerState();
}

class _ContactInfoDrawerState extends State<ContactInfoDrawer> {
  bool _isLoading = true;
  bool _isSavingDetails = false;
  bool _isSavingNotes = false;
  bool _isSavingLabels = false;
  bool _isSavingAgent = false;
  bool _isTogglingBlock = false;

  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _notesController = TextEditingController();

  bool _enableAiBot = false;
  bool _enableReplyBot = false;
  bool _isBlocked = false;
  String? _assignedUserUid;

  List<Map<String, dynamic>> _allLabels = [];
  List<Map<String, dynamic>> _agents = [];
  List<Map<String, dynamic>> _customFields = [];
  List<Map<String, dynamic>> _allGroups = [];
  
  final Map<String, TextEditingController> _customFieldControllers = {};
  final Set<int> _selectedLabelIds = {};
  final Set<String> _selectedGroupUids = {};
  bool _isSavingGroups = false;

  // Design constants
  static const _primaryColor = Color(0xFF198754);
  static const _accentColor = Color(0xFF2DD4BF);

  final List<Map<String, dynamic>> _labelColorOptions = const [
    {'bg': '#3B82F6', 'text': '#FFFFFF', 'name': 'Bleu'},
    {'bg': '#10B981', 'text': '#FFFFFF', 'name': 'Vert'},
    {'bg': '#EF4444', 'text': '#FFFFFF', 'name': 'Rouge'},
    {'bg': '#F59E0B', 'text': '#FFFFFF', 'name': 'Orange'},
    {'bg': '#8B5CF6', 'text': '#FFFFFF', 'name': 'Violet'},
    {'bg': '#EC4899', 'text': '#FFFFFF', 'name': 'Rose'},
    {'bg': '#14B8A6', 'text': '#FFFFFF', 'name': 'Teal'},
    {'bg': '#6B7280', 'text': '#FFFFFF', 'name': 'Gris'},
  ];

  void _showDrawerNotice(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          message,
          style: const TextStyle(color: Colors.black87, fontWeight: FontWeight.w600),
        ),
        backgroundColor: const Color(0xFFE5E7EB),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  void initState() {
    super.initState();
    for (var label in widget.contact.labels) {
      _selectedLabelIds.add(label.id);
    }
    _loadContactData();
  }

  Future<void> _loadContactData() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final details = await ApiService().fetchContactDetails(widget.contact.uid);
      final labelsAndAgents = await ApiService().fetchLabelsAndAgents(widget.contact.uid);

      if (details != null) {
        _firstName = details['first_name'];
        _lastName = details['last_name'];
        _email = details['email'];
        _enableAiBot = (details['disable_ai_bot'] ?? 1) == 0;
        _enableReplyBot = (details['disable_reply_bot'] ?? 1) == 0;
        _isBlocked = details['wa_blocked_at'] != null;
        
        _firstNameController.text = _firstName ?? '';
        _lastNameController.text = _lastName ?? '';
        _emailController.text = _email ?? '';
        _notesController.text = details['__data']?['contact_notes'] ?? '';

        final List<dynamic> customFieldsList = details['vendorContactCustomFields'] ?? [];
        final List<dynamic> customValuesList = details['custom_field_values'] ?? [];

        _customFields = customFieldsList.map((f) => Map<String, dynamic>.from(f)).toList();

        for (var f in _customFields) {
          final fieldId = f['_id'];
          final fieldUid = f['_uid'] ?? '';
          final valueObj = customValuesList.firstWhere(
            (v) => v['contact_custom_fields__id'] == fieldId,
            orElse: () => null,
          );
          final val = valueObj != null ? valueObj['field_value']?.toString() ?? '' : '';
          
          if (!_customFieldControllers.containsKey(fieldUid)) {
            _customFieldControllers[fieldUid] = TextEditingController(text: val);
          } else {
            _customFieldControllers[fieldUid]!.text = val;
          }
        }
      }

      if (labelsAndAgents != null) {
        final List<dynamic> allLabelsList = labelsAndAgents['listOfAllLabels'] ?? [];
        final List<dynamic> agentsList = labelsAndAgents['vendorMessagingUsers'] ?? [];

        _allLabels = allLabelsList.map((l) => Map<String, dynamic>.from(l)).toList();
        _agents = agentsList.map((a) => Map<String, dynamic>.from(a)).toList();

        if (details != null && details['assigned_users__id'] != null) {
          final assignedId = details['assigned_users__id'].toString();
          final matchingAgent = _agents.firstWhere(
            (a) => a['_id'].toString() == assignedId,
            orElse: () => {},
          );
          if (matchingAgent.isNotEmpty) {
            _assignedUserUid = matchingAgent['_uid'];
          }
        }
      }

      // Fetch all groups
      final allGroups = await ApiService().fetchContactGroups();
      _allGroups = allGroups;

      if (details != null && details['groups'] != null) {
        final List<dynamic> assignedGroups = details['groups'] as List;
        _selectedGroupUids.clear();
        for (var g in assignedGroups) {
          final uid = g['uid'] ?? g['_uid'];
          if (uid != null) {
            _selectedGroupUids.add(uid.toString());
          }
        }
      }
    } catch (e) {
      debugPrint('Load Contact Data Error: $e');
    }

    setState(() {
      _isLoading = false;
    });
  }

  String? _firstName;
  String? _lastName;
  String? _email;

  Color? _parseColor(String? colorStr) {
    if (colorStr == null || colorStr.isEmpty) return null;
    try {
      if (colorStr.startsWith('#')) {
        String hex = colorStr.replaceAll('#', '');
        if (hex.length == 3) {
          hex = hex.split('').map((c) => c + c).join();
        }
        if (hex.length == 6) {
          hex = 'FF$hex';
        }
        return Color(int.parse(hex, radix: 16));
      }
    } catch (e) {
      debugPrint('Color parse error in drawer: $e');
    }
    return null;
  }

  Future<void> _saveBotSettings() async {
    final success = await ApiService().updateContactDetails(
      widget.contact.uid,
      enableAiBot: _enableAiBot,
      enableReplyBot: _enableReplyBot,
    );
    if (mounted) {
      _showDrawerNotice(success
          ? 'Paramètres des bots mis à jour.'
          : 'Erreur lors de la mise à jour des bots.');
    }
  }

  Future<void> _saveContactDetails() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isSavingDetails = true;
    });

    final Map<String, String> customFieldsPayload = {};
    _customFieldControllers.forEach((uid, controller) {
      customFieldsPayload[uid] = controller.text.trim();
    });

    final success = await ApiService().updateContactDetails(
      widget.contact.uid,
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      email: _emailController.text.trim(),
      enableAiBot: _enableAiBot,
      enableReplyBot: _enableReplyBot,
      customFields: customFieldsPayload,
    );

    setState(() {
      _isSavingDetails = false;
    });

    if (mounted) {
      _showDrawerNotice(success ? 'Informations mises à jour avec succès.' : 'Erreur de mise à jour.');
      if (success && widget.onUpdate != null) {
        widget.onUpdate!();
      }
    }
  }

  Future<void> _saveNotes() async {
    setState(() {
      _isSavingNotes = true;
    });

    final success = await ApiService().updateContactNotes(
      widget.contact.uid,
      _notesController.text.trim(),
    );

    setState(() {
      _isSavingNotes = false;
    });

    if (mounted) {
      _showDrawerNotice(success ? 'Notes mises à jour.' : 'Erreur de mise à jour des notes.');
      if (success && widget.onUpdate != null) {
        widget.onUpdate!();
      }
    }
  }

  Future<void> _toggleBlockStatus() async {
    setState(() {
      _isTogglingBlock = true;
    });

    bool success;
    if (_isBlocked) {
      success = await ApiService().unblockContact(widget.contact.uid);
    } else {
      success = await ApiService().blockContact(widget.contact.uid);
    }

    setState(() {
      _isTogglingBlock = false;
      if (success) {
        _isBlocked = !_isBlocked;
      }
    });

    if (mounted) {
      _showDrawerNotice(success
          ? (_isBlocked ? 'Contact bloqué.' : 'Contact débloqué.')
          : 'Erreur de modification du statut de blocage.');
      if (success && widget.onUpdate != null) {
        widget.onUpdate!();
      }
    }
  }

  Future<void> _assignAgent(String? agentUid) async {
    setState(() {
      _isSavingAgent = true;
    });

    final userUid = agentUid ?? 'no_one';
    final success = await ApiService().assignContactUser(widget.contact.uid, userUid);

    setState(() {
      _isSavingAgent = false;
      if (success) {
        _assignedUserUid = agentUid;
      }
    });

    if (mounted) {
      _showDrawerNotice(success ? 'Agent assigné avec succès.' : 'Erreur lors de l\'assignation de l\'agent.');
      if (success && widget.onUpdate != null) {
        widget.onUpdate!();
      }
    }
  }

  Future<void> _toggleLabel(int labelId, bool selected) async {
    setState(() {
      _isSavingLabels = true;
      if (selected) {
        _selectedLabelIds.add(labelId);
      } else {
        _selectedLabelIds.remove(labelId);
      }
    });

    final success = await ApiService().assignContactLabels(
      widget.contact.uid,
      _selectedLabelIds.toList(),
    );

    setState(() {
      _isSavingLabels = false;
    });

    if (!success) {
      setState(() {
        if (selected) {
          _selectedLabelIds.remove(labelId);
        } else {
          _selectedLabelIds.add(labelId);
        }
      });
    }

    if (mounted) {
      if (!success) {
        _showDrawerNotice('Erreur lors de la mise à jour des étiquettes.');
      } else {
        if (widget.onUpdate != null) {
          widget.onUpdate!();
        }
      }
    }
  }

  Future<void> _createNewGlobalLabel(String title, String textColor, String bgColor) async {
    setState(() {
      _isSavingLabels = true;
    });

    try {
      final newLabel = await ApiService().createContactLabel(
        title: title,
        textColor: textColor,
        bgColor: bgColor,
      );

      if (newLabel != null) {
        final labelId = newLabel['_id'] as int;
        
        setState(() {
          _allLabels.add(newLabel);
        });

        await _toggleLabel(labelId, true);
        _showDrawerNotice('Étiquette créée et assignée !');
      } else {
        _showDrawerNotice('Échec de la création de l\'étiquette');
        setState(() {
          _isSavingLabels = false;
        });
      }
    } catch (_) {
      _showDrawerNotice('Échec de la création de l\'étiquette');
      setState(() {
        _isSavingLabels = false;
      });
    }
  }

  Future<void> _showManageGroupsDialog() async {
    final isDark = ThemeService().isDark;
    final onSurface = Theme.of(context).colorScheme.onSurface;
    final tempSelectedGroups = Set<String>.from(_selectedGroupUids);

    final updated = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              backgroundColor: isDark ? ThemeService.darkCard : ThemeService.lightCard,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: Text(
                'Gérer les groupes',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 18,
                  color: isDark ? Colors.white : Colors.black87,
                ),
              ),
              content: _allGroups.isEmpty
                  ? Text(
                      'Aucun groupe de contacts disponible.',
                      style: TextStyle(color: onSurface.withOpacity(0.6)),
                    )
                  : SizedBox(
                      width: double.maxFinite,
                      child: ListView.builder(
                        shrinkWrap: true,
                        itemCount: _allGroups.length,
                        itemBuilder: (context, index) {
                          final group = _allGroups[index];
                          final title = group['title'] ?? 'Groupe';
                          final uid = group['_uid'] ?? '';
                          final isChecked = tempSelectedGroups.contains(uid);

                          return CheckboxListTile(
                            title: Text(
                              title,
                              style: TextStyle(color: isDark ? Colors.white : Colors.black87),
                            ),
                            value: isChecked,
                            activeColor: ThemeService.primaryColor,
                            onChanged: (val) {
                              setDialogState(() {
                                if (val == true) {
                                  tempSelectedGroups.add(uid);
                                } else {
                                  tempSelectedGroups.remove(uid);
                                }
                              });
                            },
                          );
                        },
                      ),
                    ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: Text(
                    'Annuler',
                    style: TextStyle(color: isDark ? Colors.white70 : Colors.black54),
                  ),
                ),
                ElevatedButton(
                  onPressed: () => Navigator.pop(context, true),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: ThemeService.primaryColor,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  child: const Text('Enregistrer', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                ),
              ],
            );
          },
        );
      },
    );

    if (updated == true) {
      setState(() => _isSavingGroups = true);
      final success = await ApiService().assignGroupsToContact(
        [widget.contact.uid],
        tempSelectedGroups.toList(),
      );

      if (success) {
        setState(() {
          _selectedGroupUids.clear();
          _selectedGroupUids.addAll(tempSelectedGroups);
          _isSavingGroups = false;
        });
        _showDrawerNotice('Groupes mis à jour avec succès');
      } else {
        setState(() => _isSavingGroups = false);
        _showDrawerNotice('Erreur lors de la mise à jour des groupes');
      }
    }
  }

  void _showCreateLabelDialog() {
    final titleController = TextEditingController();
    String selectedBgColor = '#3B82F6';
    String selectedTextColor = '#FFFFFF';

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) {
          final isDark = Theme.of(context).brightness == Brightness.dark;
          final dialogBg = isDark ? const Color(0xFF1E293B) : Colors.white;
          final txtColor = isDark ? Colors.white : Colors.black87;

          return AlertDialog(
            backgroundColor: dialogBg,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: Text(
              'Créer une étiquette',
              style: TextStyle(color: txtColor, fontWeight: FontWeight.bold, fontSize: 16),
            ),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextField(
                  controller: titleController,
                  style: TextStyle(color: txtColor, fontSize: 14),
                  decoration: InputDecoration(
                    labelText: 'Nom de l\'étiquette',
                    labelStyle: TextStyle(color: txtColor.withOpacity(0.6), fontSize: 12),
                    enabledBorder: UnderlineInputBorder(
                      borderSide: BorderSide(color: txtColor.withOpacity(0.2)),
                    ),
                    focusedBorder: const UnderlineInputBorder(
                      borderSide: BorderSide(color: _primaryColor, width: 2),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  'Couleur',
                  style: TextStyle(color: txtColor.withOpacity(0.6), fontSize: 12, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: _labelColorOptions.map((opt) {
                    final isSelected = selectedBgColor == opt['bg'];
                    final colorVal = _parseColor(opt['bg']) ?? Colors.grey;

                    return GestureDetector(
                      onTap: () {
                        setDialogState(() {
                          selectedBgColor = opt['bg'];
                          selectedTextColor = opt['text'];
                        });
                      },
                      child: Container(
                        width: 32,
                        height: 32,
                        decoration: BoxDecoration(
                          color: colorVal,
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: isSelected ? txtColor : Colors.transparent,
                            width: 2,
                          ),
                          boxShadow: [
                            if (isSelected)
                              BoxShadow(
                                color: colorVal.withOpacity(0.4),
                                blurRadius: 6,
                                spreadRadius: 1,
                              )
                          ],
                        ),
                        child: isSelected
                            ? const Icon(Icons.check, color: Colors.white, size: 16)
                            : null,
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: Text('Annuler', style: TextStyle(color: txtColor.withOpacity(0.6))),
              ),
              ElevatedButton(
                onPressed: () async {
                  final title = titleController.text.trim();
                  if (title.isEmpty) {
                    _showDrawerNotice('Le nom de l\'étiquette est obligatoire');
                    return;
                  }
                  Navigator.pop(context);
                  await _createNewGlobalLabel(title, selectedTextColor, selectedBgColor);
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: _primaryColor,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Text('Créer'),
              ),
            ],
          );
        },
      ),
    );
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _notesController.dispose();
    _customFieldControllers.forEach((_, c) => c.dispose());
    super.dispose();
  }

  Widget _buildSectionHeader(String title, IconData icon, {Widget? trailing}) {
    return Padding(
      padding: EdgeInsets.only(top: 20, bottom: 10, left: 4),
      child: Row(
        children: [
          Icon(icon, color: _accentColor, size: 18),
          SizedBox(width: 8),
          Expanded(
            child: Text(
              title,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurface,
                fontWeight: FontWeight.w700,
                fontSize: 14,
              ),
            ),
          ),
          if (trailing != null) trailing,
        ],
      ),
    );
  }

  InputDecoration _inputDecoration(String label) {
    final onSurface = Theme.of(context).colorScheme.onSurface;
    final cardColor = Theme.of(context).colorScheme.surface;
    return InputDecoration(
      labelText: label,
      labelStyle: TextStyle(color: onSurface.withOpacity(0.5), fontSize: 13),
      filled: true,
      fillColor: cardColor,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: onSurface.withOpacity(0.15)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: onSurface.withOpacity(0.15)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: _primaryColor, width: 1.5),
      ),
      contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    );
  }

  @override
  Widget build(BuildContext context) {
    final scaffoldBg = Theme.of(context).scaffoldBackgroundColor;
    final cardColor = Theme.of(context).colorScheme.surface;
    final onSurface = Theme.of(context).colorScheme.onSurface;
    return Drawer(
      width: MediaQuery.of(context).size.width * 0.85,
      backgroundColor: scaffoldBg,
      child: Scaffold(
        backgroundColor: scaffoldBg,
        appBar: AppBar(
          title: Text(
            'Options du Contact',
            style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700),
          ),
          backgroundColor: scaffoldBg,
          elevation: 0,
          leading: IconButton(
            icon: Icon(Icons.close_rounded),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: _isLoading
            ? Center(child: CircularProgressIndicator(color: _primaryColor))
            : SingleChildScrollView(
                padding: EdgeInsets.all(16.0),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Header Card
                      Container(
                        padding: EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: cardColor,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: onSurface.withOpacity(0.1)),
                        ),
                        child: Column(
                          children: [
                            // Avatar
                            Container(
                              width: 64,
                              height: 64,
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  colors: [_primaryColor, _accentColor],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                borderRadius: BorderRadius.circular(18),
                              ),
                              child: Center(
                                child: Text(
                                  widget.contact.name.isNotEmpty
                                      ? widget.contact.name[0].toUpperCase()
                                      : 'C',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w800,
                                    fontSize: 24,
                                  ),
                                ),
                              ),
                            ),
                            SizedBox(height: 12),
                            Text(
                              widget.contact.name,
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 18,
                                color: onSurface,
                              ),
                              textAlign: TextAlign.center,
                            ),
                            SizedBox(height: 4),
                            Text(
                              widget.contact.phoneNumber,
                              style: TextStyle(color: onSurface.withOpacity(0.5), fontSize: 13),
                            ),
                          ],
                        ),
                      ),

                      // Assignment Section
                      _buildSectionHeader('Assigner un agent', Icons.person_add_alt_1_rounded),
                      Container(
                        padding: EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                        decoration: BoxDecoration(
                          color: cardColor,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: onSurface.withOpacity(0.1)),
                        ),
                        child: _isSavingAgent
                            ? Center(
                                child: Padding(
                                  padding: EdgeInsets.all(12.0),
                                  child: CircularProgressIndicator(color: _primaryColor, strokeWidth: 2),
                                ),
                              )
                            : DropdownButtonFormField<String>(
                                value: _assignedUserUid,
                                dropdownColor: cardColor,
                                style: TextStyle(color: onSurface, fontSize: 14),
                                decoration: InputDecoration(
                                  border: InputBorder.none,
                                  hintText: 'Sélectionner un agent',
                                  hintStyle: TextStyle(color: onSurface.withOpacity(0.4)),
                                ),
                                items: [
                                  const DropdownMenuItem<String>(
                                    value: null,
                                    child: Text('Aucun agent'),
                                  ),
                                  ..._agents.map((agent) {
                                    return DropdownMenuItem<String>(
                                      value: agent['_uid'],
                                      child: Text(agent['full_name'] ?? agent['username'] ?? ''),
                                    );
                                  }),
                                ],
                                onChanged: (val) {
                                  setState(() {
                                    _assignedUserUid = val;
                                  });
                                },
                              ),
                      ),
                      SizedBox(height: 10),
                      ElevatedButton(
                        onPressed: _isSavingAgent ? null : () => _assignAgent(_assignedUserUid),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _primaryColor,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          padding: EdgeInsets.symmetric(vertical: 12),
                          elevation: 0,
                        ),
                        child: _isSavingAgent
                            ? SizedBox(
                                height: 18,
                                width: 18,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : Text('Appliquer l\'agent', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                      ),

                      // Details Section
                      _buildSectionHeader('Détails de base', Icons.info_outline_rounded),
                      TextFormField(
                        controller: _firstNameController,
                        style: TextStyle(color: onSurface, fontSize: 14),
                        decoration: _inputDecoration('Prénom'),
                      ),
                      SizedBox(height: 12),
                      TextFormField(
                        controller: _lastNameController,
                        style: TextStyle(color: onSurface, fontSize: 14),
                        decoration: _inputDecoration('Nom'),
                      ),
                      SizedBox(height: 12),
                      TextFormField(
                        controller: _emailController,
                        keyboardType: TextInputType.emailAddress,
                        style: TextStyle(color: onSurface, fontSize: 14),
                        decoration: _inputDecoration('Email'),
                      ),

                      // Custom Attributes
                      if (_customFields.isNotEmpty) ...[
                        _buildSectionHeader('Attributs personnalisés', Icons.bookmark_add_outlined),
                        ..._customFields.map((field) {
                          final uid = field['_uid'] ?? '';
                          final title = field['title'] ?? '';
                          return Padding(
                            padding: EdgeInsets.only(bottom: 12.0),
                            child: TextFormField(
                              controller: _customFieldControllers[uid],
                              style: TextStyle(color: onSurface, fontSize: 14),
                              decoration: _inputDecoration(title),
                            ),
                          );
                        }),
                      ],

                      // AI & Reply Bot Toggles
                      _buildSectionHeader('Configurations des robots', Icons.smart_toy_rounded),
                      Container(
                        decoration: BoxDecoration(
                          color: cardColor,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: onSurface.withOpacity(0.1)),
                        ),
                        child: Column(
                          children: [
                            SwitchListTile(
                              title: Text('Chatbot IA', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: onSurface)),
                              subtitle: Text('Réponses automatiques IA', style: TextStyle(fontSize: 11, color: onSurface.withOpacity(0.5))),
                              value: _enableAiBot,
                              activeColor: _primaryColor,
                              onChanged: (val) {
                                setState(() { _enableAiBot = val; });
                                _saveBotSettings();
                              },
                            ),
                            Divider(color: onSurface.withOpacity(0.1), height: 1),
                            SwitchListTile(
                              title: Text('Bot Réponse', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: onSurface)),
                              subtitle: Text('Réponses automatiques configurées', style: TextStyle(fontSize: 11, color: onSurface.withOpacity(0.5))),
                              value: _enableReplyBot,
                              activeColor: _primaryColor,
                              onChanged: (val) {
                                setState(() { _enableReplyBot = val; });
                                _saveBotSettings();
                              },
                            ),
                          ],
                        ),
                      ),

                      SizedBox(height: 14),
                      ElevatedButton(
                        onPressed: _isSavingDetails ? null : _saveContactDetails,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _primaryColor,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          padding: EdgeInsets.symmetric(vertical: 14),
                          elevation: 0,
                        ),
                        child: _isSavingDetails
                            ? SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : Text('Enregistrer', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                      ),

                      // Notes Section
                      _buildSectionHeader('Notes internes', Icons.note_alt_outlined),
                      TextFormField(
                        controller: _notesController,
                        maxLines: 4,
                        style: TextStyle(color: onSurface, fontSize: 14),
                        decoration: _inputDecoration('Saisir une note...'),
                      ),
                      SizedBox(height: 12),
                      ElevatedButton(
                        onPressed: _isSavingNotes ? null : _saveNotes,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _primaryColor,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          padding: EdgeInsets.symmetric(vertical: 14),
                          elevation: 0,
                        ),
                        child: _isSavingNotes
                            ? SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : Text('Mettre à jour les notes', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                      ),

                      // Labels Section
                      _buildSectionHeader(
                        'Étiquettes',
                        Icons.label_outline_rounded,
                        trailing: InkWell(
                          onTap: _showCreateLabelDialog,
                          borderRadius: BorderRadius.circular(4),
                          child: const Padding(
                            padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.add, color: _accentColor, size: 14),
                                SizedBox(width: 4),
                                Text(
                                  'Créer',
                                  style: TextStyle(
                                    color: _primaryColor,
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      _isSavingLabels
                          ? Center(
                              child: Padding(
                                padding: EdgeInsets.all(8.0),
                                child: CircularProgressIndicator(color: _primaryColor, strokeWidth: 2),
                              ),
                            )
                          : _allLabels.isEmpty
                              ? Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
                                  child: Text(
                                    'Aucune étiquette disponible. Cliquez sur "Créer" pour en ajouter une.',
                                    style: TextStyle(color: onSurface.withOpacity(0.5), fontSize: 12),
                                  ),
                                )
                              : Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: _allLabels.map((label) {
                                    final labelId = int.tryParse(label['_id']?.toString() ?? label['id']?.toString() ?? '0') ?? 0;
                                    final title = label['title'] ?? '';
                                    final isSelected = _selectedLabelIds.contains(labelId);
                                    final labelColor = _parseColor(label['bg_color']) ?? Color(0xFF64748B);

                                    return FilterChip(
                                      label: Text(
                                        title,
                                        style: TextStyle(
                                          color: isSelected ? Colors.white : onSurface.withOpacity(0.7),
                                          fontWeight: FontWeight.w600,
                                          fontSize: 12,
                                        ),
                                      ),
                                      selected: isSelected,
                                      selectedColor: labelColor.withAlpha(120),
                                      checkmarkColor: Colors.white,
                                      backgroundColor: cardColor,
                                      side: BorderSide(
                                        color: isSelected ? labelColor : onSurface.withOpacity(0.15),
                                        width: 1,
                                      ),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      onSelected: (selected) {
                                        _toggleLabel(labelId, selected);
                                      },
                                    );
                                  }).toList(),
                                ),

                      // Groupes Section
                      _buildSectionHeader(
                        'Groupes de Contacts',
                        Icons.group_work_rounded,
                        trailing: InkWell(
                          onTap: _showManageGroupsDialog,
                          borderRadius: BorderRadius.circular(4),
                          child: const Padding(
                            padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.edit, color: _accentColor, size: 14),
                                SizedBox(width: 4),
                                Text(
                                  'Gérer',
                                  style: TextStyle(
                                    color: _primaryColor,
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      _isSavingGroups
                          ? Center(
                              child: Padding(
                                padding: EdgeInsets.all(8.0),
                                child: CircularProgressIndicator(color: _primaryColor, strokeWidth: 2),
                              ),
                            )
                          : _selectedGroupUids.isEmpty
                              ? Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
                                  child: Text(
                                    'Ce contact n\'appartient à aucun groupe.',
                                    style: TextStyle(color: onSurface.withOpacity(0.5), fontSize: 12),
                                  ),
                                )
                              : Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: _allGroups
                                      .where((g) => _selectedGroupUids.contains(g['_uid'] ?? ''))
                                      .map((group) {
                                    final title = group['title'] ?? 'Groupe';

                                    return Chip(
                                      label: Text(
                                        title,
                                        style: TextStyle(
                                          color: onSurface.withOpacity(0.8),
                                          fontWeight: FontWeight.w600,
                                          fontSize: 12,
                                        ),
                                      ),
                                      backgroundColor: _primaryColor.withOpacity(0.1),
                                      side: BorderSide(
                                        color: _primaryColor.withOpacity(0.2),
                                        width: 1,
                                      ),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                    );
                                  }).toList(),
                                ),

                      // Block Contact
                      SizedBox(height: 24),
                      Divider(color: onSurface.withOpacity(0.1)),
                      SizedBox(height: 12),
                      ElevatedButton.icon(
                        onPressed: _isTogglingBlock ? null : _toggleBlockStatus,
                        icon: _isTogglingBlock
                            ? SizedBox(
                                height: 16,
                                width: 16,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : Icon(_isBlocked ? Icons.lock_open_rounded : Icons.block_rounded),
                        label: Text(_isBlocked ? 'Débloquer le contact' : 'Bloquer le contact'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _isBlocked
                              ? onSurface.withOpacity(0.08)
                              : Color(0xFFEF4444).withAlpha(30),
                          foregroundColor: _isBlocked ? onSurface.withOpacity(0.6) : Color(0xFFEF4444),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          padding: EdgeInsets.symmetric(vertical: 14),
                          elevation: 0,
                        ),
                      ),
                      SizedBox(height: 24),
                    ],
                  ),
                ),
              ),
      ),
    );
  }
}

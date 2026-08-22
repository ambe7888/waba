import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class CreateBotReplyScreen extends StatefulWidget {
  final Map<String, dynamic>? reply;
  final Map<String, dynamic> triggerTypes;

  // When true, this screen is being used to compose a one-off campaign
  // message (NT_CAMPAIGN_MESSAGE) instead of a real auto-reply: the
  // trigger-type/keyword fields and the Actions section (agent assignment,
  // labels, drip campaign subscription — all meaningless for a message sent
  // once, immediately) are hidden, and on success the created message's
  // name is returned so the caller can look it up by its (now guaranteed
  // unique) name.
  final bool isCampaignPresetMode;

  const CreateBotReplyScreen({
    super.key,
    this.reply,
    required this.triggerTypes,
    this.isCampaignPresetMode = false,
  });

  @override
  State<CreateBotReplyScreen> createState() => _CreateBotReplyScreenState();
}

class _CreateBotReplyScreenState extends State<CreateBotReplyScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _triggerController;
  late TextEditingController _replyController;
  final _replyFocusNode = FocusNode();

  late String _selectedTriggerType;
  bool _isSubmitting = false;

  // Drip campaign subscription action (same concept as the web bot reply
  // form's "Inscription à la campagne de relance" field): when this bot
  // reply triggers, the contact is auto-subscribed to the selected drip
  // campaign — this is how drip campaigns actually get their subscribers.
  List<Map<String, dynamic>> _dripCampaigns = [];
  String? _selectedDripCampaignId;
  bool _isLoadingDripCampaigns = true;
  bool _dripCampaignsFailed = false;

  // 'media' message type (pure media, no buttons) and the 'list' button
  // type still aren't supported from mobile.
  bool _hasUnsupportedAdvancedConfig = false;

  // ── Header + Interactive buttons (message_type: 'interactive') ────────
  // Mirrors the web dashboard's advanced bot editor: an optional header
  // (none/text/image/video/document), then either up to 3 quick-reply
  // buttons or a single CTA URL button (the "list" button type is
  // intentionally not offered on mobile), then an optional footer.
  bool _useInteractiveMode = false;
  String _headerType = ''; // '', 'text', 'image', 'video', 'document'
  late TextEditingController _headerTextController;
  File? _selectedHeaderMediaFile;
  String? _uploadedMediaFileName;
  bool _isUploadingMedia = false;
  String _interactiveType = 'button'; // 'button' | 'cta_url'
  late TextEditingController _button1Controller;
  late TextEditingController _button2Controller;
  late TextEditingController _button3Controller;
  late TextEditingController _ctaDisplayTextController;
  late TextEditingController _ctaUrlController;
  late TextEditingController _footerController;

  // The backend's update path never re-applies the header_type field for
  // existing interactive bots (only header_text/buttons/footer/body are
  // updatable — a limitation shared with the web dashboard, since the
  // header type select isn't part of the update payload server-side) —
  // so once a bot has an interactive header configured, its type is shown
  // read-only on edit instead of pretending a change would take effect.
  bool _headerTypeEditable = true;
  bool _existingMediaHeader = false;

  // ── Actions (bot_actions) ────────────────────────────────────────────
  // Mirrors the web dashboard's "Actions" fieldset: when this bot reply
  // triggers, these actions run against the contact.
  List<Map<String, dynamic>> _teamMembers = [];
  List<Map<String, dynamic>> _labels = [];
  Map<String, dynamic> _promotionalOptions = {};
  Map<String, dynamic> _aiBotOptions = {};
  Map<String, dynamic> _replyBotOptions = {};
  bool _isLoadingActionSupportData = true;
  bool _actionSupportDataFailed = false;

  String _assignedUserAction = 'no_action';
  final Set<String> _assignLabelIds = {};
  final Set<String> _unassignLabelIds = {};
  String _promotionalAction = 'no_action';
  String _aiBotAction = 'no_action';
  String _replyBotAction = 'no_action';

  @override
  void initState() {
    super.initState();
    final isEdit = widget.reply != null;
    _nameController = TextEditingController(text: isEdit ? widget.reply!['name'] : '');
    _triggerController = TextEditingController(text: isEdit ? widget.reply!['reply_trigger'] : '');
    _replyController = TextEditingController(text: isEdit ? widget.reply!['reply_text'] : '');
    _headerTextController = TextEditingController();
    _button1Controller = TextEditingController();
    _button2Controller = TextEditingController();
    _button3Controller = TextEditingController();
    _ctaDisplayTextController = TextEditingController();
    _ctaUrlController = TextEditingController();
    _footerController = TextEditingController();

    _selectedTriggerType = widget.isCampaignPresetMode
        ? 'NT_CAMPAIGN_MESSAGE'
        : (isEdit ? (widget.reply!['trigger_type'] ?? 'is') : 'is');
    _selectedDripCampaignId = isEdit
        ? widget.reply!['addon_drip_campaigns__id']?.toString()
        : null;

    if (isEdit) {
      final data = widget.reply!['__data'];
      final Map? dataMap = data is Map ? data : null;

      final Map? interactionMessage =
          dataMap != null && dataMap['interaction_message'] is Map
              ? dataMap['interaction_message'] as Map
              : null;
      final bool hasListButtons =
          interactionMessage != null && interactionMessage['list_data'] != null;

      _hasUnsupportedAdvancedConfig =
          dataMap != null && (dataMap['media_message'] != null || hasListButtons);

      if (interactionMessage != null && !hasListButtons) {
        _useInteractiveMode = true;
        final headerType = interactionMessage['header_type']?.toString() ?? '';
        _headerType = ['', 'text', 'image', 'video', 'document'].contains(headerType)
            ? headerType
            : '';
        _headerTypeEditable = false;
        _existingMediaHeader = ['image', 'video', 'document'].contains(_headerType);
        _headerTextController.text = interactionMessage['header_text']?.toString() ?? '';
        _footerController.text = interactionMessage['footer_text']?.toString() ?? '';
        _interactiveType = interactionMessage['interactive_type']?.toString() ?? 'button';

        final buttons = interactionMessage['buttons'];
        if (buttons is Map) {
          _button1Controller.text = buttons['1']?.toString() ?? '';
          _button2Controller.text = buttons['2']?.toString() ?? '';
          _button3Controller.text = buttons['3']?.toString() ?? '';
        }
        final ctaUrl = interactionMessage['cta_url'];
        if (ctaUrl is Map) {
          _ctaDisplayTextController.text = ctaUrl['display_text']?.toString() ?? '';
          _ctaUrlController.text = ctaUrl['url']?.toString() ?? '';
        }
      }

      final Map? botActions = dataMap != null && dataMap['bot_actions'] is Map
          ? dataMap['bot_actions'] as Map
          : null;
      if (botActions != null) {
        _assignedUserAction = botActions['assigned_users_id']?.toString() ?? 'no_action';
        _promotionalAction = botActions['promotional']?.toString() ?? 'no_action';
        _aiBotAction = botActions['ai_bot']?.toString() ?? 'no_action';
        _replyBotAction = botActions['reply_bot']?.toString() ?? 'no_action';
        final assignLabels = botActions['contact_labels'];
        if (assignLabels is List) {
          _assignLabelIds.addAll(assignLabels.map((e) => e.toString()));
        }
        final unassignLabels = botActions['unassign_contact_labels'];
        if (unassignLabels is List) {
          _unassignLabelIds.addAll(unassignLabels.map((e) => e.toString()));
        }
      }
    }

    if (!widget.isCampaignPresetMode) {
      _loadDripCampaigns();
      _loadActionSupportData();
    } else {
      _isLoadingDripCampaigns = false;
      _isLoadingActionSupportData = false;
    }
  }

  Future<void> _loadActionSupportData() async {
    setState(() {
      _isLoadingActionSupportData = true;
      _actionSupportDataFailed = false;
    });
    final data = await ApiService().fetchBotActionSupportData();
    if (mounted && data != null) {
      setState(() {
        _teamMembers = List<Map<String, dynamic>>.from(data['team_members'] ?? []);
        _labels = List<Map<String, dynamic>>.from(data['labels'] ?? []);
        _promotionalOptions = Map<String, dynamic>.from(data['promotional_options'] ?? {});
        _aiBotOptions = Map<String, dynamic>.from(data['ai_bot_options'] ?? {});
        _replyBotOptions = Map<String, dynamic>.from(data['reply_bot_options'] ?? {});
        _isLoadingActionSupportData = false;

        // Guard against a previously-assigned agent that no longer exists
        // (deleted/removed) — a dropdown value with no matching item throws.
        const builtInAssignValues = {'no_action', 'no_one', 'random'};
        if (!builtInAssignValues.contains(_assignedUserAction) &&
            !_teamMembers.any((m) => m['_id'].toString() == _assignedUserAction)) {
          _assignedUserAction = 'no_action';
        }
      });
    } else if (mounted) {
      setState(() {
        _isLoadingActionSupportData = false;
        _actionSupportDataFailed = true;
      });
    }
  }

  Future<void> _loadDripCampaigns() async {
    setState(() {
      _isLoadingDripCampaigns = true;
      _dripCampaignsFailed = false;
    });
    final campaigns = await ApiService().fetchDripCampaigns();
    if (mounted) {
      setState(() {
        _dripCampaigns = campaigns.where((c) {
          final status = c['status'];
          return status == 1 || status == '1';
        }).toList();
        _isLoadingDripCampaigns = false;
        _dripCampaignsFailed = ApiService().lastDripCampaignsError != null;
      });
    }
  }

  Future<void> _pickAndUploadHeaderMedia() async {
    final FileType pickType = _headerType == 'image'
        ? FileType.image
        : _headerType == 'video'
            ? FileType.video
            : FileType.any;
    final result = await FilePicker.platform.pickFiles(type: pickType);
    final path = result?.files.single.path;
    if (path == null) return;

    final file = File(path);
    setState(() {
      _selectedHeaderMediaFile = file;
      _uploadedMediaFileName = null;
      _isUploadingMedia = true;
    });

    final fileName = await ApiService().uploadTempMedia(file, 'whatsapp_$_headerType');
    if (!mounted) return;
    setState(() {
      _isUploadingMedia = false;
      _uploadedMediaFileName = fileName;
    });

    if (fileName == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ApiService().lastUploadError ?? 'Échec du téléversement du média'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _triggerController.dispose();
    _replyController.dispose();
    _headerTextController.dispose();
    _button1Controller.dispose();
    _button2Controller.dispose();
    _button3Controller.dispose();
    _ctaDisplayTextController.dispose();
    _ctaUrlController.dispose();
    _footerController.dispose();
    _replyFocusNode.dispose();
    super.dispose();
  }

  // WhatsApp formatting: wraps the current selection (or inserts markers at
  // the cursor if nothing is selected) with the given markdown characters.
  void _applyFormatting(String marker) {
    final text = _replyController.text;
    final selection = _replyController.selection;
    final start = selection.start > -1 ? selection.start : text.length;
    final end = selection.end > -1 ? selection.end : text.length;
    final selectedText = text.substring(start, end);

    final newText = text.replaceRange(start, end, '$marker$selectedText$marker');
    setState(() {
      _replyController.text = newText;
      _replyController.selection = selectedText.isEmpty
          ? TextSelection.collapsed(offset: start + marker.length)
          : TextSelection(baseOffset: start, extentOffset: end + marker.length * 2);
    });
    _replyFocusNode.requestFocus();
  }

  void _insertVariable() {
    final text = _replyController.text;
    final selection = _replyController.selection;

    // WhatsApp variables are numbered placeholders ({{1}}, {{2}}, ...),
    // not a repeated literal token — find the highest existing number in
    // the message and insert the next one, matching how the template
    // screens already number theirs.
    final matches = RegExp(r'\{\{(\d+)\}\}').allMatches(text);
    int nextNumber = 1;
    for (final m in matches) {
      final n = int.tryParse(m.group(1)!) ?? 0;
      if (n >= nextNumber) nextNumber = n + 1;
    }
    final variable = '{{$nextNumber}}';

    final insertStart = selection.start > -1 ? selection.start : text.length;
    final insertEnd = selection.end > -1 ? selection.end : text.length;
    final newText = text.replaceRange(insertStart, insertEnd, variable);

    setState(() {
      _replyController.text = newText;
      _replyController.selection = TextSelection.collapsed(
        offset: insertStart + variable.length,
      );
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final name = _nameController.text.trim();
    final trigger = _triggerController.text.trim();
    final text = _replyController.text.trim();

    // In campaign preset mode the trigger-type/keyword fields aren't shown
    // at all (see the `!widget.isCampaignPresetMode` guard around them
    // below) — _selectedTriggerType is forced to 'NT_CAMPAIGN_MESSAGE' and
    // trigger stays empty by design, so requiring it here always failed
    // validation even when every visible field was filled in.
    if (name.isEmpty ||
        text.isEmpty ||
        (!widget.isCampaignPresetMode &&
            _selectedTriggerType != 'welcome' &&
            trigger.isEmpty)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez remplir tous les champs obligatoires')),
      );
      return;
    }

    Map<String, dynamic>? advancedFields;
    String messageType = 'simple';
    if (_useInteractiveMode) {
      messageType = 'interactive';

      if (_headerType == 'text' && _headerTextController.text.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Veuillez renseigner le texte d\'en-tête')),
        );
        return;
      }
      final bool isNewMediaHeader =
          _headerTypeEditable && ['image', 'video', 'document'].contains(_headerType);
      if (isNewMediaHeader && _uploadedMediaFileName == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(_isUploadingMedia
              ? 'Le média est en cours de téléversement, veuillez patienter'
              : 'Veuillez sélectionner un média pour l\'en-tête')),
        );
        return;
      }
      if (_interactiveType == 'button' && _button1Controller.text.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Le bouton 1 est obligatoire')),
        );
        return;
      }
      if (_interactiveType == 'cta_url' &&
          (_ctaDisplayTextController.text.trim().isEmpty ||
              _ctaUrlController.text.trim().isEmpty)) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Le texte et l\'URL du bouton sont obligatoires')),
        );
        return;
      }
      final buttonLabels = [
        _button1Controller.text.trim(),
        _button2Controller.text.trim(),
        _button3Controller.text.trim(),
      ].where((b) => b.isNotEmpty).toList();
      if (_interactiveType == 'button' && buttonLabels.toSet().length != buttonLabels.length) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Les libellés des boutons doivent être uniques')),
        );
        return;
      }

      advancedFields = {
        'header_type': _headerType,
        if (_headerType == 'text') 'header_text': _headerTextController.text.trim(),
        if (isNewMediaHeader) 'uploaded_media_file_name': _uploadedMediaFileName ?? '',
        'interactive_type': _interactiveType,
        if (_interactiveType == 'button')
          'buttons': {
            '1': _button1Controller.text.trim(),
            if (_button2Controller.text.trim().isNotEmpty) '2': _button2Controller.text.trim(),
            if (_button3Controller.text.trim().isNotEmpty) '3': _button3Controller.text.trim(),
          },
        if (_interactiveType == 'cta_url') ...{
          'button_display_text': _ctaDisplayTextController.text.trim(),
          'button_url': _ctaUrlController.text.trim(),
        },
        if (_footerController.text.trim().isNotEmpty)
          'footer_text': _footerController.text.trim(),
      };
    }

    setState(() => _isSubmitting = true);

    // Actions/drip-campaign subscription don't apply to a one-off campaign
    // message — nothing "triggers" it, it's sent once, immediately.
    final botActions = widget.isCampaignPresetMode
        ? null
        : {
            'assigned_users_id': _assignedUserAction,
            'contact_labels': _assignLabelIds.toList(),
            'unassign_contact_labels': _unassignLabelIds.toList(),
            'promotional': _promotionalAction,
            'ai_bot': _aiBotAction,
            'reply_bot': _replyBotAction,
          };

    if (widget.isCampaignPresetMode) {
      // processBotReplyCreate() defaults status to inactive unless a truthy
      // 'status' is sent — this message should be usable right away.
      advancedFields = {...?advancedFields, 'status': '1'};
    }

    Map<String, dynamic>? res;
    if (widget.reply != null) {
      res = await ApiService().updateBotReply(
        uid: widget.reply!['_uid'],
        name: name,
        triggerType: _selectedTriggerType,
        replyTrigger: _selectedTriggerType == 'welcome' ? null : trigger,
        replyText: text,
        dripCampaignId: widget.isCampaignPresetMode ? null : _selectedDripCampaignId,
        botActions: botActions,
        messageType: messageType,
        advancedFields: advancedFields,
      );
    } else {
      res = await ApiService().createBotReply(
        name: name,
        triggerType: _selectedTriggerType,
        replyTrigger: _selectedTriggerType == 'welcome' ? null : trigger,
        replyText: text,
        dripCampaignId: widget.isCampaignPresetMode ? null : _selectedDripCampaignId,
        botActions: botActions,
        messageType: messageType,
        advancedFields: advancedFields,
      );
    }

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (res != null && res['reaction'] == 1) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Message enregistré avec succès !'), backgroundColor: Colors.green),
        );
        // processBotReplyCreate() doesn't return the new record's uid — the
        // name is unique (server-enforced) by the time this succeeds, so
        // hand it back for the caller to look the record up by.
        Navigator.pop(context, widget.isCampaignPresetMode ? {'success': true, 'name': name} : true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res?['message'] ?? 'Erreur lors de l\'enregistrement'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    final bgColor = isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB);
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final borderColor = isDark ? Colors.white10 : Colors.grey.shade200;
    final textColor = isDark ? Colors.white : const Color(0xFF111827);
    final subtitleColor = isDark ? Colors.white54 : const Color(0xFF6B7280);
    final isEdit = widget.reply != null;

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        backgroundColor: cardColor,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, color: textColor, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.isCampaignPresetMode
                  ? 'Composer le message'
                  : (isEdit ? 'Modifier la réponse auto' : 'Créer une réponse auto'),
              style: TextStyle(color: ThemeService.primaryColor, fontWeight: FontWeight.bold, fontSize: 18),
            ),
            Text(
              widget.isCampaignPresetMode
                  ? 'En-tête, corps, pied de page et boutons'
                  : 'Automatisez votre service client',
              style: TextStyle(color: subtitleColor, fontSize: 12),
            ),
          ],
        ),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (_hasUnsupportedAdvancedConfig) ...[
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.orange.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.orange.withValues(alpha: 0.4)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 20),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Ce bot utilise un message média seul ou un menu de type liste. Ces réglages ne sont pas modifiables depuis l\'app mobile pour le moment — utilisez le tableau de bord web pour les changer. Enregistrer ici ne les supprimera pas, mais vous ne pourrez pas les voir/éditer ici.',
                        style: TextStyle(fontSize: 12.5, color: textColor.withValues(alpha: 0.85)),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],
            // Section 1: Rule Details
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle(
                    widget.isCampaignPresetMode ? 'Nom du message' : 'Informations de la règle',
                    textColor,
                  ),
                  const SizedBox(height: 16),

                  _buildLabel(widget.isCampaignPresetMode ? 'Nom (pour le retrouver plus tard)' : 'Nom de la règle', textColor),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _nameController,
                    // backend validates name as max:200 — matching it here
                    // gives immediate feedback instead of a round-trip
                    // 422 that reads as a cryptic "must not be greater
                    // than 200 characters" error.
                    maxLength: 200,
                    decoration: _buildInputDecoration(
                      widget.isCampaignPresetMode ? 'ex. Promo weekend' : 'ex. Message de bienvenue',
                      isDark,
                    ),
                  ),
                  const SizedBox(height: 24),

                  if (!widget.isCampaignPresetMode) ...[
                    _buildLabel('Type de déclencheur', textColor),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      initialValue: _selectedTriggerType,
                      decoration: _buildInputDecoration('', isDark),
                      icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                      items: widget.triggerTypes.entries.map((e) {
                        return DropdownMenuItem<String>(
                          value: e.key,
                          child: Text(e.value['title'] ?? e.key),
                        );
                      }).toList(),
                      onChanged: (val) {
                        if (val != null) setState(() => _selectedTriggerType = val);
                      },
                    ),

                    if (_selectedTriggerType != 'welcome') ...[
                      const SizedBox(height: 24),
                      _buildLabel('Mot-clé / Phrase déclencheur', textColor),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _triggerController,
                        decoration: _buildInputDecoration('ex. bonjour, tarifs, aide', isDark),
                      ),
                      const SizedBox(height: 6),
                      Text('La phrase ou le mot-clé exact qui déclenche cette réponse.', style: TextStyle(fontSize: 12, color: subtitleColor)),
                    ],
                  ],
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 2: Message Body
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Message de réponse', textColor),
                  const SizedBox(height: 6),
                  Text('C\'est le message automatique envoyé à l\'utilisateur.', style: TextStyle(fontSize: 13, color: subtitleColor)),
                  const SizedBox(height: 16),

                  // Rich Text Editor Simulation
                  Container(
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: borderColor),
                    ),
                    child: Column(
                      children: [
                        // Text Area
                        TextFormField(
                          controller: _replyController,
                          focusNode: _replyFocusNode,
                          maxLines: 6,
                          decoration: InputDecoration(
                            hintText: 'Tapez votre réponse automatique ici...',
                            hintStyle: TextStyle(color: subtitleColor),
                            border: InputBorder.none,
                            contentPadding: const EdgeInsets.all(16),
                          ),
                        ),
                        // Bottom Toolbar
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            border: Border(top: BorderSide(color: borderColor)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        width: 8,
                                        height: 8,
                                        decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle),
                                      ),
                                      const SizedBox(width: 8),
                                      Text('ÉDITEUR DE TEXTE', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: subtitleColor)),
                                    ],
                                  ),
                                  InkWell(
                                    onTap: _insertVariable,
                                    borderRadius: BorderRadius.circular(8),
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                      decoration: BoxDecoration(
                                        border: Border.all(color: borderColor),
                                        borderRadius: BorderRadius.circular(8),
                                        color: isDark ? const Color(0xFF1E293B) : Colors.white,
                                      ),
                                      child: Row(
                                        children: [
                                          Icon(Icons.add_rounded, size: 16, color: ThemeService.primaryColor),
                                          const SizedBox(width: 4),
                                          Text('AJOUTER VARIABLE', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeService.primaryColor)),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              // Formatting shortcuts, bottom-right: WhatsApp
                              // markdown — *bold*, _italic_, ~strikethrough~,
                              // ```monospace```.
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  _buildFormatButton(Icons.format_bold_rounded, '*', textColor),
                                  const SizedBox(width: 4),
                                  _buildFormatButton(Icons.format_italic_rounded, '_', textColor),
                                  const SizedBox(width: 4),
                                  _buildFormatButton(Icons.format_strikethrough_rounded, '~', textColor),
                                  const SizedBox(width: 4),
                                  _buildFormatButton(Icons.code_rounded, '```', textColor),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section: Header + Interactive buttons + Footer
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: _buildSectionTitle('En-tête, boutons & pied de page', textColor),
                      ),
                      Switch(
                        value: _useInteractiveMode,
                        activeThumbColor: ThemeService.primaryColor,
                        onChanged: (v) => setState(() => _useInteractiveMode = v),
                      ),
                    ],
                  ),
                  Text(
                    'Ajoutez un en-tête (texte ou média) et des boutons interactifs à ce message.',
                    style: TextStyle(fontSize: 12, color: subtitleColor),
                  ),
                  if (_useInteractiveMode) ...[
                    const SizedBox(height: 20),

                    _buildLabel('Type d\'en-tête', textColor),
                    const SizedBox(height: 8),
                    if (!_headerTypeEditable) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: (isDark ? Colors.white : Colors.black).withValues(alpha: 0.05),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: borderColor),
                        ),
                        child: Text(
                          {
                                '': 'Aucun',
                                'text': 'Texte',
                                'image': 'Image',
                                'video': 'Vidéo',
                                'document': 'Document',
                              }[_headerType] ??
                              'Aucun',
                          style: TextStyle(fontSize: 13.5, color: textColor, fontWeight: FontWeight.w600),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Le type d\'en-tête ne peut plus être changé après la création (limitation du serveur).',
                        style: TextStyle(fontSize: 11.5, color: subtitleColor),
                      ),
                      if (_existingMediaHeader) ...[
                        const SizedBox(height: 6),
                        Text(
                          'Le média de cet en-tête n\'est pas modifiable depuis l\'app — utilisez le tableau de bord web pour le changer.',
                          style: TextStyle(fontSize: 11.5, color: subtitleColor),
                        ),
                      ],
                    ] else ...[
                      DropdownButtonFormField<String>(
                        initialValue: _headerType,
                        decoration: _buildInputDecoration('', isDark),
                        icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                        items: const [
                          DropdownMenuItem(value: '', child: Text('Aucun')),
                          DropdownMenuItem(value: 'text', child: Text('Texte')),
                          DropdownMenuItem(value: 'image', child: Text('Image')),
                          DropdownMenuItem(value: 'video', child: Text('Vidéo')),
                          DropdownMenuItem(value: 'document', child: Text('Document')),
                        ],
                        onChanged: (v) => setState(() {
                          _headerType = v ?? '';
                          _selectedHeaderMediaFile = null;
                          _uploadedMediaFileName = null;
                        }),
                      ),
                    ],
                    if (_headerType == 'text') ...[
                      const SizedBox(height: 16),
                      _buildLabel('Texte d\'en-tête', textColor),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _headerTextController,
                        maxLength: 60,
                        decoration: _buildInputDecoration('ex. Bienvenue !', isDark),
                      ),
                    ],
                    if (_headerTypeEditable) ...[
                      if (['image', 'video', 'document'].contains(_headerType)) ...[
                        const SizedBox(height: 16),
                        InkWell(
                          onTap: _isUploadingMedia ? null : _pickAndUploadHeaderMedia,
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF9FAFB),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: _uploadedMediaFileName != null
                                    ? const Color(0xFF10B981)
                                    : borderColor,
                              ),
                            ),
                            child: Row(
                              children: [
                                if (_isUploadingMedia)
                                  const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                else
                                  Icon(
                                    _uploadedMediaFileName != null
                                        ? Icons.check_circle_rounded
                                        : Icons.upload_file_rounded,
                                    color: _uploadedMediaFileName != null
                                        ? const Color(0xFF10B981)
                                        : subtitleColor,
                                  ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    _isUploadingMedia
                                        ? 'Téléversement en cours...'
                                        : _uploadedMediaFileName != null
                                            ? (_selectedHeaderMediaFile?.path.split('/').last ??
                                                'Média sélectionné')
                                            : 'Sélectionner un ${_headerType == 'image' ? 'image' : _headerType == 'video' ? 'vidéo' : 'document'}',
                                    style: TextStyle(fontSize: 13.5, color: textColor),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ],
                    const SizedBox(height: 20),

                    _buildLabel('Boutons interactifs', textColor),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: RadioListTile<String>(
                            value: 'button',
                            groupValue: _interactiveType,
                            contentPadding: EdgeInsets.zero,
                            dense: true,
                            title: const Text('Boutons rapides', style: TextStyle(fontSize: 13)),
                            onChanged: (v) => setState(() => _interactiveType = v ?? 'button'),
                          ),
                        ),
                        Expanded(
                          child: RadioListTile<String>(
                            value: 'cta_url',
                            groupValue: _interactiveType,
                            contentPadding: EdgeInsets.zero,
                            dense: true,
                            title: const Text('Bouton URL', style: TextStyle(fontSize: 13)),
                            onChanged: (v) => setState(() => _interactiveType = v ?? 'button'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    if (_interactiveType == 'button') ...[
                      TextFormField(
                        controller: _button1Controller,
                        maxLength: 20,
                        decoration: _buildInputDecoration('Bouton 1 (obligatoire)', isDark),
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _button2Controller,
                        maxLength: 20,
                        decoration: _buildInputDecoration('Bouton 2 (optionnel)', isDark),
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _button3Controller,
                        maxLength: 20,
                        decoration: _buildInputDecoration('Bouton 3 (optionnel)', isDark),
                      ),
                    ] else ...[
                      TextFormField(
                        controller: _ctaDisplayTextController,
                        maxLength: 20,
                        decoration: _buildInputDecoration('Texte du bouton', isDark),
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _ctaUrlController,
                        keyboardType: TextInputType.url,
                        decoration: _buildInputDecoration('https://exemple.com', isDark),
                      ),
                    ],
                    const SizedBox(height: 20),

                    _buildLabel('Pied de page (optionnel)', textColor),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _footerController,
                      maxLength: 60,
                      decoration: _buildInputDecoration('ex. Merci de votre confiance', isDark),
                    ),
                  ],
                ],
              ),
            ),
            if (!widget.isCampaignPresetMode) ...[
            const SizedBox(height: 16),

            // Section: Actions (bot_actions)
            _buildCardContainer(
              isDark,
              cardColor,
              borderColor,
              Theme(
                data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
                child: ExpansionTile(
                  tilePadding: EdgeInsets.zero,
                  childrenPadding: const EdgeInsets.only(top: 16),
                  iconColor: ThemeService.primaryColor,
                  collapsedIconColor: subtitleColor,
                  title: Row(
                    children: [
                      const Icon(Icons.bolt_rounded, color: Color(0xFF10B981), size: 20),
                      const SizedBox(width: 8),
                      _buildSectionTitle('Actions', textColor),
                    ],
                  ),
                  children: [
                    if (_isLoadingActionSupportData)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 16),
                        child: Center(child: CircularProgressIndicator()),
                      )
                    else if (_actionSupportDataFailed)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        child: Column(
                          children: [
                            Icon(Icons.cloud_off_rounded, color: Colors.red.shade300, size: 32),
                            const SizedBox(height: 8),
                            Text(
                              'Impossible de charger les agents, labels et options d\'action.',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 12.5, color: subtitleColor),
                            ),
                            if (ApiService().lastActionSupportDataError != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                ApiService().lastActionSupportDataError!,
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 11, color: Colors.red.shade300),
                              ),
                            ],
                            const SizedBox(height: 10),
                            OutlinedButton.icon(
                              onPressed: _loadActionSupportData,
                              icon: const Icon(Icons.refresh_rounded, size: 16),
                              label: const Text('Réessayer'),
                            ),
                          ],
                        ),
                      )
                    else ...[
                      _buildLabel('Attribution à un agent', textColor),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<String>(
                        initialValue: _assignedUserAction,
                        decoration: _buildInputDecoration('', isDark),
                        icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                        isExpanded: true,
                        items: [
                          const DropdownMenuItem(value: 'no_action', child: Text('Aucune action')),
                          const DropdownMenuItem(value: 'no_one', child: Text('Désassigner l\'agent')),
                          const DropdownMenuItem(value: 'random', child: Text('Agent aléatoire')),
                          ..._teamMembers.map((m) {
                            final label = (m['full_name']?.toString().trim().isNotEmpty == true)
                                ? m['full_name'].toString()
                                : 'Agent';
                            return DropdownMenuItem(
                              value: m['_id'].toString(),
                              child: Text(
                                m['is_current_user'] == true ? '$label (vous)' : label,
                                overflow: TextOverflow.ellipsis,
                              ),
                            );
                          }),
                        ],
                        onChanged: (v) => setState(() => _assignedUserAction = v ?? 'no_action'),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Assigne, désassigne, ou attribue au hasard un agent à ce contact quand le bot se déclenche.',
                        style: TextStyle(fontSize: 11.5, color: subtitleColor),
                      ),
                      const SizedBox(height: 20),

                      _buildLabel('Ajouter des labels', textColor),
                      const SizedBox(height: 8),
                      _buildLabelChips(
                        selectedIds: _assignLabelIds,
                        isDark: isDark,
                        textColor: textColor,
                        subtitleColor: subtitleColor,
                      ),
                      const SizedBox(height: 20),

                      _buildLabel('Retirer des labels', textColor),
                      const SizedBox(height: 8),
                      _buildLabelChips(
                        selectedIds: _unassignLabelIds,
                        isDark: isDark,
                        textColor: textColor,
                        subtitleColor: subtitleColor,
                      ),
                      const SizedBox(height: 20),

                      _buildLabel('Promotionnel', textColor),
                      const SizedBox(height: 8),
                      _buildActionOptionDropdown(
                        value: _promotionalAction,
                        options: _promotionalOptions,
                        isDark: isDark,
                        subtitleColor: subtitleColor,
                        onChanged: (v) => setState(() => _promotionalAction = v ?? 'no_action'),
                      ),
                      const SizedBox(height: 20),

                      _buildLabel('Bot IA', textColor),
                      const SizedBox(height: 8),
                      _buildActionOptionDropdown(
                        value: _aiBotAction,
                        options: _aiBotOptions,
                        isDark: isDark,
                        subtitleColor: subtitleColor,
                        onChanged: (v) => setState(() => _aiBotAction = v ?? 'no_action'),
                      ),
                      const SizedBox(height: 20),

                      _buildLabel('Bot de réponse', textColor),
                      const SizedBox(height: 8),
                      _buildActionOptionDropdown(
                        value: _replyBotAction,
                        options: _replyBotOptions,
                        isDark: isDark,
                        subtitleColor: subtitleColor,
                        onChanged: (v) => setState(() => _replyBotAction = v ?? 'no_action'),
                      ),
                      const SizedBox(height: 20),

                      Row(
                        children: [
                          Icon(Icons.water_drop_rounded, color: const Color(0xFF10B981), size: 16),
                          const SizedBox(width: 6),
                          _buildLabel('Inscription à la campagne de relance', textColor),
                        ],
                      ),
                      const SizedBox(height: 8),
                      if (_isLoadingDripCampaigns)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 8),
                          child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                        )
                      else if (_dripCampaignsFailed)
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Impossible de charger les campagnes goutte à goutte.',
                              style: TextStyle(fontSize: 12.5, color: subtitleColor),
                            ),
                            if (ApiService().lastDripCampaignsError != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Text(
                                  ApiService().lastDripCampaignsError!,
                                  style: TextStyle(fontSize: 11, color: Colors.red.shade300),
                                ),
                              ),
                            const SizedBox(height: 8),
                            OutlinedButton.icon(
                              onPressed: _loadDripCampaigns,
                              icon: const Icon(Icons.refresh_rounded, size: 16),
                              label: const Text('Réessayer'),
                            ),
                          ],
                        )
                      else if (_dripCampaigns.isEmpty)
                        Text(
                          'Aucune campagne active. Créez-en une dans Campagnes Goutte à Goutte pour l\'utiliser ici.',
                          style: TextStyle(fontSize: 12.5, color: subtitleColor),
                        )
                      else ...[
                        Text(
                          'Quand cette réponse automatique se déclenche, le contact sera automatiquement inscrit à la campagne sélectionnée.',
                          style: TextStyle(fontSize: 12, color: subtitleColor),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String?>(
                          initialValue: _selectedDripCampaignId,
                          decoration: _buildInputDecoration('', isDark),
                          icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
                          isExpanded: true,
                          items: [
                            const DropdownMenuItem<String?>(
                              value: null,
                              child: Text('Aucune action (ne pas abonner)'),
                            ),
                            ..._dripCampaigns.map((c) {
                              return DropdownMenuItem<String?>(
                                value: c['_id']?.toString(),
                                child: Text(
                                  c['title']?.toString() ?? 'Campagne sans titre',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              );
                            }),
                          ],
                          onChanged: (val) {
                            setState(() => _selectedDripCampaignId = val);
                          },
                        ),
                      ],
                    ],
                  ],
                ),
              ),
            ),
            ],
            const SizedBox(height: 100), // Space for bottom buttons
          ],
        ),
      ),
      bottomSheet: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E293B) : Colors.white,
          border: Border(top: BorderSide(color: borderColor)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, -4),
            )
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => Navigator.pop(context),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: isDark ? const Color(0xFF334155) : const Color(0xFFF3F4F6),
                    side: BorderSide.none,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: Text('Annuler', style: TextStyle(color: textColor, fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: ThemeService.primaryColor,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 0,
                  ),
                  child: _isSubmitting
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Enregistrer', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFormatButton(IconData icon, String marker, Color textColor) {
    return InkWell(
      onTap: () => _applyFormatting(marker),
      borderRadius: BorderRadius.circular(6),
      child: Padding(
        padding: const EdgeInsets.all(6),
        child: Icon(icon, color: textColor, size: 18),
      ),
    );
  }

  Widget _buildCardContainer(bool isDark, Color cardColor, Color borderColor, Widget child) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
      ),
      child: child,
    );
  }

  Widget _buildSectionTitle(String title, Color textColor) {
    return Text(title, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor));
  }

  Widget _buildLabel(String label, Color textColor) {
    return Text(label, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: textColor));
  }

  Widget _buildActionOptionDropdown({
    required String value,
    required Map<String, dynamic> options,
    required bool isDark,
    required Color subtitleColor,
    required ValueChanged<String?> onChanged,
  }) {
    return DropdownButtonFormField<String>(
      initialValue: value,
      decoration: _buildInputDecoration('', isDark),
      icon: Icon(Icons.keyboard_arrow_down_rounded, color: subtitleColor),
      isExpanded: true,
      items: [
        const DropdownMenuItem(value: 'no_action', child: Text('Aucune action')),
        ...options.entries.map((e) {
          final title = (e.value is Map ? e.value['title'] : null)?.toString() ?? e.key;
          return DropdownMenuItem(value: e.key, child: Text(title, overflow: TextOverflow.ellipsis));
        }),
      ],
      onChanged: onChanged,
    );
  }

  Widget _buildLabelChips({
    required Set<String> selectedIds,
    required bool isDark,
    required Color textColor,
    required Color subtitleColor,
  }) {
    if (_labels.isEmpty) {
      return Text(
        'Aucun label disponible. Créez des labels depuis la fiche contact ou le tableau de bord web.',
        style: TextStyle(fontSize: 12, color: subtitleColor),
      );
    }
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: _labels.map((label) {
        final id = label['_id'].toString();
        final selected = selectedIds.contains(id);
        return FilterChip(
          label: Text(label['title']?.toString() ?? ''),
          selected: selected,
          onSelected: (v) {
            setState(() {
              if (v) {
                selectedIds.add(id);
              } else {
                selectedIds.remove(id);
              }
            });
          },
          selectedColor: ThemeService.primaryColor.withValues(alpha: 0.2),
          checkmarkColor: ThemeService.primaryColor,
          labelStyle: TextStyle(
            color: selected ? ThemeService.primaryColor : textColor,
            fontWeight: selected ? FontWeight.bold : FontWeight.normal,
            fontSize: 12.5,
          ),
          backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF3F4F6),
          side: BorderSide(color: selected ? ThemeService.primaryColor : Colors.transparent),
        );
      }).toList(),
    );
  }

  InputDecoration _buildInputDecoration(String hint, bool isDark) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: isDark ? Colors.white54 : const Color(0xFF9CA3AF)),
      filled: true,
      fillColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF3F4F6),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: ThemeService.primaryColor.withValues(alpha: 0.5)),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
    );
  }
}

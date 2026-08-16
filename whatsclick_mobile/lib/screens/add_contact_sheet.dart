import 'package:flutter/material.dart';
import 'package:country_picker/country_picker.dart';
import '../services/api_service.dart';
import '../models/contact.dart';

class AddContactSheet extends StatefulWidget {
  final Contact? contactToEdit;
  final VoidCallback onSuccess;

  const AddContactSheet({
    super.key,
    this.contactToEdit,
    required this.onSuccess,
  });

  @override
  State<AddContactSheet> createState() => _AddContactSheetState();
}

class _AddContactSheetState extends State<AddContactSheet> {
  final _formKey = GlobalKey<FormState>();
  
  late TextEditingController _firstNameController;
  late TextEditingController _lastNameController;
  late TextEditingController _phoneController;
  late TextEditingController _emailController;
  
  String _languageCode = 'en'; // default
  Country _selectedCountry = CountryParser.parseCountryCode('US');
  
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    String fName = '';
    String lName = '';
    if (widget.contactToEdit != null) {
      final parts = widget.contactToEdit!.name.split(' ');
      if (parts.isNotEmpty) fName = parts.first;
      if (parts.length > 1) lName = parts.sublist(1).join(' ');
    }
    _firstNameController = TextEditingController(text: fName);
    _lastNameController = TextEditingController(text: lName);
    
    // For phone, if editing, we might have the full WA ID. 
    // Usually wa_id includes the country code. Extracting it perfectly is complex without libphonenumber, 
    // but we'll try to put the raw wa_id in the phone field if editing.
    String initialPhone = widget.contactToEdit?.phoneNumber ?? '';
    if (widget.contactToEdit != null && widget.contactToEdit!.phoneNumber.isNotEmpty) {
       // Just put everything in phone controller for edit mode for simplicity, 
       // unless we do complex parsing. Let's assume the user will adjust it.
       _phoneController = TextEditingController(text: initialPhone);
    } else {
       _phoneController = TextEditingController();
    }
    
    _emailController = TextEditingController();
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    // Format phone: remove spaces, +, and leading 0 from the typed number
    String rawPhone = _phoneController.text.replaceAll(' ', '').replaceAll('+', '');
    if (rawPhone.startsWith('0')) {
      rawPhone = rawPhone.substring(1);
    }
    
    // Final phone is country code + raw phone (unless editing and they typed the full number, but this ensures consistency)
    String finalPhone = '${_selectedCountry.phoneCode}$rawPhone';
    
    // If editing and they left the initial full wa_id in the box, we might duplicate the country code. 
    // Let's protect against that if rawPhone already starts with country code.
    if (rawPhone.startsWith(_selectedCountry.phoneCode) && rawPhone.length > _selectedCountry.phoneCode.length + 5) {
       finalPhone = rawPhone;
    }

    final data = {
      'first_name': _firstNameController.text.trim(),
      'last_name': _lastNameController.text.trim(),
      'email': _emailController.text.trim(),
      'phone_number': finalPhone,
      'language_code': _languageCode,
      'country': _selectedCountry.name,
    };

    bool success = false;
    if (widget.contactToEdit != null) {
      // Update
      data['contact_uid'] = widget.contactToEdit!.uid;
      // Depending on backend, update-process might need specific fields.
      // We pass the UID in the payload or URL. The ApiService updateContact uses URL /update-process,
      // so it probably expects contact_uid in body or URL.
      success = await ApiService().updateContact(data);
    } else {
      // Create
      success = await ApiService().createContact(data);
    }

    if (mounted) {
      setState(() => _isLoading = false);
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(widget.contactToEdit != null ? 'Contact mis à jour' : 'Contact ajouté avec succès'), backgroundColor: Colors.green),
        );
        widget.onSuccess();
        Navigator.pop(context);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Erreur lors de l'opération."), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
        left: 16,
        right: 16,
        top: 24,
      ),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      widget.contactToEdit != null ? 'Modifier le contact' : 'Nouveau contact',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    IconButton(
                      icon: Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                SizedBox(height: 16),
                
                // Phone Number with Country Picker
                Text('Numéro WhatsApp *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Colors.grey.shade700)),
                SizedBox(height: 8),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    GestureDetector(
                      onTap: () {
                        showCountryPicker(
                          context: context,
                          showPhoneCode: true,
                          onSelect: (Country country) {
                            setState(() {
                              _selectedCountry = country;
                            });
                          },
                        );
                      },
                      child: Container(
                        padding: EdgeInsets.symmetric(horizontal: 12, vertical: 15),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.1)),
                        ),
                        child: Row(
                          children: [
                            Text(_selectedCountry.flagEmoji, style: TextStyle(fontSize: 18)),
                            SizedBox(width: 8),
                            Text('+${_selectedCountry.phoneCode}', style: TextStyle(fontWeight: FontWeight.bold)),
                            Icon(Icons.arrow_drop_down, color: Colors.grey),
                          ],
                        ),
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _phoneController,
                        keyboardType: TextInputType.phone,
                        decoration: InputDecoration(
                          hintText: 'Numéro sans le 0 initial',
                          filled: true,
                          fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                          contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 15),
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) return 'Requis';
                          return null;
                        },
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 16),

                // Names
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Prénom', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Colors.grey.shade700)),
                          SizedBox(height: 8),
                          TextFormField(
                            controller: _firstNameController,
                            decoration: InputDecoration(
                              hintText: 'John',
                              filled: true,
                              fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Nom', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Colors.grey.shade700)),
                          SizedBox(height: 8),
                          TextFormField(
                            controller: _lastNameController,
                            decoration: InputDecoration(
                              hintText: 'Doe',
                              filled: true,
                              fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 16),

                // Email
                Text('Email', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Colors.grey.shade700)),
                SizedBox(height: 8),
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    hintText: 'contact@exemple.com',
                    filled: true,
                    fillColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
                SizedBox(height: 16),

                // Language
                Text('Langue', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Colors.grey.shade700)),
                SizedBox(height: 8),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: _languageCode,
                      isExpanded: true,
                      items: [
                        DropdownMenuItem(value: 'fr', child: Text('Français (fr)')),
                        DropdownMenuItem(value: 'en', child: Text('Anglais (en)')),
                        DropdownMenuItem(value: 'es', child: Text('Espagnol (es)')),
                        DropdownMenuItem(value: 'pt', child: Text('Portugais (pt)')),
                        DropdownMenuItem(value: 'ar', child: Text('Arabe (ar)')),
                      ],
                      onChanged: (val) {
                        if (val != null) setState(() => _languageCode = val);
                      },
                    ),
                  ),
                ),
                
                SizedBox(height: 32),
                
                // Submit button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Color(0xFF10B981), // WhatsApp like green
                      padding: EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                    child: _isLoading 
                        ? SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Text(
                            widget.contactToEdit != null ? 'Enregistrer les modifications' : 'Ajouter le contact',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
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

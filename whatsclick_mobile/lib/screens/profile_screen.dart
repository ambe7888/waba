import 'package:flutter/material.dart';
import '../services/theme_service.dart';

class ProfileScreen extends StatefulWidget {
  final Map<String, dynamic>? userData;

  const ProfileScreen({super.key, this.userData});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late TextEditingController _firstNameController;
  late TextEditingController _lastNameController;
  late TextEditingController _countryController;
  late TextEditingController _languageController;
  late TextEditingController _notesController;

  @override
  void initState() {
    super.initState();
    final data = widget.userData ?? {};
    _firstNameController = TextEditingController(text: data['first_name']?.toString() ?? '');
    _lastNameController = TextEditingController(text: data['last_name']?.toString() ?? '');
    
    // Country logic if available
    String country = '';
    if (data['country'] != null) {
      country = data['country']?.toString() ?? '';
    } else if (data['countries__id'] != null) {
      country = data['countries__id']?.toString() ?? '';
    }
    _countryController = TextEditingController(text: country);
    
    _languageController = TextEditingController(text: data['language']?.toString() ?? '');
    _notesController = TextEditingController(text: data['notes']?.toString() ?? '');
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _countryController.dispose();
    _languageController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _updateProfile() {
    // To be connected to actual update endpoint
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Mise à jour réussie'), backgroundColor: Colors.green),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    
    return Scaffold(
      backgroundColor: isDark ? ThemeService.darkSurface : const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Mon Profil'),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Center(
              child: Stack(
                alignment: Alignment.bottomRight,
                children: [
                  CircleAvatar(
                    radius: 50,
                    backgroundColor: ThemeService.primaryColor.withValues(alpha: 0.1),
                    child: Icon(Icons.person_rounded, size: 50, color: ThemeService.primaryColor),
                  ),
                  Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: ThemeService.primaryColor,
                      shape: BoxShape.circle,
                      border: Border.all(color: isDark ? ThemeService.darkSurface : Colors.white, width: 2),
                    ),
                    child: const Icon(Icons.edit_rounded, size: 16, color: Colors.white),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 32),
            _buildTextField('Prénom', _firstNameController, isDark),
            const SizedBox(height: 16),
            _buildTextField('Nom', _lastNameController, isDark),
            const SizedBox(height: 16),
            _buildDropdownField(
              'Pays',
              _countryController.text,
              ['France', 'Côte d\'Ivoire', 'Sénégal', 'Mali', 'Cameroun', 'Maroc', 'Canada', 'États-Unis', 'Autre'],
              (val) {
                if (val != null) _countryController.text = val;
                setState(() {});
              },
              isDark,
            ),
            const SizedBox(height: 16),
            _buildDropdownField(
              'Langue',
              _languageController.text,
              ['Anglais', 'Français'],
              (val) {
                if (val != null) _languageController.text = val;
                setState(() {});
              },
              isDark,
            ),
            const SizedBox(height: 16),
            _buildTextField('Notes', _notesController, isDark, maxLines: 3),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: ThemeService.primaryColor,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 2,
                ),
                onPressed: _updateProfile,
                child: const Text('Mise à jour', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDropdownField(String label, String value, List<String> items, ValueChanged<String?> onChanged, bool isDark) {
    return DropdownButtonFormField<String>(
      initialValue: items.contains(value) ? value : (items.isNotEmpty ? items.first : null),
      dropdownColor: isDark ? const Color(0xFF1E293B) : Colors.white,
      style: TextStyle(color: isDark ? Colors.white : Colors.black87),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(color: isDark ? Colors.white54 : Colors.black54),
        filled: true,
        fillColor: isDark ? const Color(0xFF1E293B) : Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: ThemeService.primaryColor, width: 2),
        ),
      ),
      items: items.map((String item) {
        return DropdownMenuItem<String>(
          value: item,
          child: Text(item),
        );
      }).toList(),
      onChanged: onChanged,
    );
  }

  Widget _buildTextField(String label, TextEditingController controller, bool isDark, {int maxLines = 1}) {
    return TextField(
      controller: controller,
      maxLines: maxLines,
      style: TextStyle(color: isDark ? Colors.white : Colors.black87),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(color: isDark ? Colors.white54 : Colors.black54),
        filled: true,
        fillColor: isDark ? const Color(0xFF1E293B) : Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: ThemeService.primaryColor, width: 2),
        ),
      ),
    );
  }
}

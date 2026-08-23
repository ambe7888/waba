import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  int _currentStep = 0;
  bool _isLoading = false;
  String? _errorMessage;

  // Controllers Step 1
  final _vendorTitleController = TextEditingController();

  // Controllers Step 2
  final _usernameController = TextEditingController();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _mobileController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  // State Step 3
  bool _agreeTerms = false;

  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  @override
  void dispose() {
    _vendorTitleController.dispose();
    _usernameController.dispose();
    _firstNameController.dispose();
    _lastNameController.dispose();
    _mobileController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  void _submitRegistration() async {
    if (!_agreeTerms) {
      setState(() {
        _errorMessage = 'Vous devez accepter les conditions d\'utilisation.';
      });
      return;
    }

    if (_passwordController.text != _confirmPasswordController.text) {
      setState(() {
        _errorMessage = 'Les mots de passe ne correspondent pas.';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final data = {
      'vendor_title': _vendorTitleController.text.trim(),
      'username': _usernameController.text.trim(),
      'first_name': _firstNameController.text.trim(),
      'last_name': _lastNameController.text.trim(),
      'mobile_number': _mobileController.text.trim(),
      'email': _emailController.text.trim(),
      'password': _passwordController.text,
      'password_confirmation': _confirmPasswordController.text,
      'terms_and_conditions': 'on', // as required by backend if accepted
    };

    final res = await ApiService().registerVendor(data);

    if (!mounted) return;

    if (res['success'] == true) {
      setState(() {
        _isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Inscription réussie!')),
      );
      Navigator.pop(context); // Go back to login
    } else {
      String errorText = res['message'] ?? 'Erreur lors de l\'inscription.';
      if (res['errors'] != null) {
        final errors = res['errors'] as Map<String, dynamic>;
        errorText = errors.values.map((e) => (e as List).join(', ')).join('\n');
      }

      setState(() {
        _isLoading = false;
        _errorMessage = errorText;
      });
    }
  }

  bool _validateStep(int step) {
    if (step == 0) {
      return _vendorTitleController.text.trim().isNotEmpty;
    } else if (step == 1) {
      return _usernameController.text.trim().isNotEmpty &&
          _firstNameController.text.trim().isNotEmpty &&
          _lastNameController.text.trim().isNotEmpty &&
          _mobileController.text.trim().isNotEmpty &&
          _emailController.text.trim().isNotEmpty &&
          _passwordController.text.isNotEmpty &&
          _confirmPasswordController.text.isNotEmpty;
    }
    return true;
  }

  void _nextStep() {
    if (!_validateStep(_currentStep)) {
      setState(() {
        _errorMessage = 'Veuillez remplir tous les champs obligatoires de cette étape.';
      });
      return;
    }
    setState(() => _errorMessage = null);

    if (_currentStep < 2) {
      setState(() => _currentStep += 1);
    } else {
      _submitRegistration();
    }
  }

  void _cancelStep() {
    if (_currentStep > 0) {
      setState(() => _currentStep -= 1);
    } else {
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final primaryColor = ThemeService.primaryColor;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF1E293B) : const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Créer un compte'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: Container(
        decoration: BoxDecoration(
          image: const DecorationImage(
            image: AssetImage('assets/images/whatsapp_bg.png'),
            fit: BoxFit.cover,
            opacity: 0.25,
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              if (_errorMessage != null)
                Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.red.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.red.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.error_outline, color: Colors.red.shade700),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _errorMessage!,
                            style: TextStyle(color: Colors.red.shade700),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              Expanded(
                child: AutofillGroup(
                  child: Stepper(
                    type: StepperType.horizontal,
                  currentStep: _currentStep,
                  onStepContinue: _nextStep,
                  onStepCancel: _cancelStep,
                  onStepTapped: (step) {
                    if (step < _currentStep || _validateStep(_currentStep)) {
                      setState(() {
                        _errorMessage = null;
                        _currentStep = step;
                      });
                    }
                  },
                  controlsBuilder: (context, details) {
                    return Padding(
                      padding: const EdgeInsets.only(top: 32.0),
                      child: Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              onPressed: _isLoading ? null : details.onStepCancel,
                              style: OutlinedButton.styleFrom(
                                foregroundColor: isDark ? Colors.white70 : Colors.grey.shade700,
                                padding: const EdgeInsets.symmetric(vertical: 16),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                              ),
                              child: Text(_currentStep == 0 ? 'Annuler' : 'Retour'),
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: ElevatedButton(
                              onPressed: _isLoading ? null : details.onStepContinue,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: primaryColor,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 16),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                              ),
                              child: _isLoading && _currentStep == 2
                                  ? const SizedBox(
                                      height: 20,
                                      width: 20,
                                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                    )
                                  : Text(_currentStep == 2 ? 'S\'inscrire' : 'Continuer'),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                  steps: [
                    Step(
                      title: const Text('Entreprise'),
                      isActive: _currentStep >= 0,
                      state: _currentStep > 0 ? StepState.complete : StepState.indexed,
                      content: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('Informations sur l\'entreprise', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                          const SizedBox(height: 16),
                          _buildTextField(_vendorTitleController, 'Nom de l\'entreprise', Icons.business, isDark),
                        ],
                      ),
                    ),
                    Step(
                      title: const Text('Admin'),
                      isActive: _currentStep >= 1,
                      state: _currentStep > 1 ? StepState.complete : StepState.indexed,
                      content: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('Informations Administrateur', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                          const SizedBox(height: 16),
                          _buildTextField(_usernameController, 'Nom d\'utilisateur', Icons.person_outline, isDark),
                          const SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(child: _buildTextField(_firstNameController, 'Prénom', Icons.badge_outlined, isDark)),
                              const SizedBox(width: 16),
                              Expanded(child: _buildTextField(_lastNameController, 'Nom', Icons.badge_outlined, isDark)),
                            ],
                          ),
                          const SizedBox(height: 16),
                          _buildTextField(_mobileController, 'Mobile (indicatif pays sans + ni 0, ex: 33600000000)', Icons.phone_outlined, isDark, keyboardType: TextInputType.phone, autofillHints: const [AutofillHints.telephoneNumber]),
                          const SizedBox(height: 16),
                          _buildTextField(_emailController, 'Email', Icons.email_outlined, isDark, keyboardType: TextInputType.emailAddress, autofillHints: const [AutofillHints.email]),
                          const SizedBox(height: 16),
                          _buildTextField(_passwordController, 'Mot de passe', Icons.lock_outline, isDark, isPassword: true, obscureText: _obscurePassword, onToggleObscure: () => setState(() => _obscurePassword = !_obscurePassword), autofillHints: const [AutofillHints.newPassword]),
                          const SizedBox(height: 16),
                          _buildTextField(_confirmPasswordController, 'Confirmer Mot de passe', Icons.lock_outline, isDark, isPassword: true, obscureText: _obscureConfirmPassword, onToggleObscure: () => setState(() => _obscureConfirmPassword = !_obscureConfirmPassword), autofillHints: const [AutofillHints.newPassword]),
                        ],
                      ),
                    ),
                    Step(
                      title: const Text('Validation'),
                      isActive: _currentStep >= 2,
                      content: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('Conditions d\'utilisation', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                          const SizedBox(height: 16),
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: isDark ? Colors.white10 : Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: isDark ? Colors.white24 : Colors.grey.shade300),
                            ),
                            child: CheckboxListTile(
                              value: _agreeTerms,
                              onChanged: (val) {
                                setState(() {
                                  _agreeTerms = val ?? false;
                                });
                              },
                              title: RichText(
                                text: TextSpan(
                                  style: TextStyle(fontSize: 14, color: isDark ? Colors.white : Colors.black),
                                  children: [
                                    const TextSpan(text: 'J\'accepte les '),
                                    TextSpan(
                                      text: 'conditions d\'utilisation',
                                      style: TextStyle(color: primaryColor, decoration: TextDecoration.underline),
                                      recognizer: TapGestureRecognizer()..onTap = () {
                                        launchUrl(Uri.parse('https://whats-click.com/terms-and-conditions'));
                                      },
                                    ),
                                    const TextSpan(text: ' et la '),
                                    TextSpan(
                                      text: 'politique de confidentialité',
                                      style: TextStyle(color: primaryColor, decoration: TextDecoration.underline),
                                      recognizer: TapGestureRecognizer()..onTap = () {
                                        launchUrl(Uri.parse('https://whats-click.com/privacy-policy'));
                                      },
                                    ),
                                    const TextSpan(text: '.'),
                                  ],
                                ),
                              ),
                              activeColor: primaryColor,
                              contentPadding: EdgeInsets.zero,
                              controlAffinity: ListTileControlAffinity.leading,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
        ),
      ),
    );
  }

  Widget _buildTextField(TextEditingController controller, String hint, IconData icon, bool isDark, {bool isPassword = false, bool obscureText = false, VoidCallback? onToggleObscure, TextInputType? keyboardType, Iterable<String>? autofillHints}) {
    return TextField(
      controller: controller,
      obscureText: isPassword ? obscureText : false,
      keyboardType: keyboardType,
      autofillHints: autofillHints,
      decoration: InputDecoration(
        hintText: hint,
        prefixIcon: Icon(icon),
        suffixIcon: isPassword
            ? IconButton(
                icon: Icon(obscureText ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                onPressed: onToggleObscure,
              )
            : null,
        filled: true,
        fillColor: isDark ? Colors.white10 : Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: isDark ? Colors.white24 : Colors.grey.shade300),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: isDark ? Colors.white24 : Colors.grey.shade300),
        ),
      ),
    );
  }
}

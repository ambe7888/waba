import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../services/fcm_service.dart';
import 'main_layout_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _otpController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _rememberMe = false;
  String? _errorMessage;
  bool _show2FAInput = false;
  String? _twoFactorUserId;

  late AnimationController _animController;
  late Animation<double> _fadeAnim;
  late Animation<Offset> _slideAnim;

  @override
  void initState() {
    super.initState();
    _loadSavedCredentials();

    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _fadeAnim = CurvedAnimation(parent: _animController, curve: Curves.easeOut);
    _slideAnim = Tween<Offset>(
      begin: Offset(0, 0.15),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _animController, curve: Curves.easeOutCubic));

    _animController.forward();
  }

  @override
  void dispose() {
    _animController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _loadSavedCredentials() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _rememberMe = prefs.getBool('remember_me') ?? false;
      if (_rememberMe) {
        _emailController.text = prefs.getString('saved_email') ?? '';
        _passwordController.text = prefs.getString('saved_password') ?? '';
      }
    });
  }

  Future<void> _saveCredentials() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('remember_me', _rememberMe);
    if (_rememberMe) {
      await prefs.setString('saved_email', _emailController.text.trim());
      await prefs.setString('saved_password', _passwordController.text.trim());
    } else {
      await prefs.remove('saved_email');
      await prefs.remove('saved_password');
    }
  }

  Future<void> _handleForgotPassword() async {
    final url = Uri.parse('https://wb.4adev.com/auth/forgot-password');
    try {
      final success = await launchUrl(url, mode: LaunchMode.externalApplication);
      if (!success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Impossible d\'ouvrir le lien de réinitialisation.')),
          );
        }
      }
    } catch (e) {
      debugPrint('Launch URL error: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur lors de l\'ouverture du lien: $e')),
        );
      }
    }
  }

  Future<void> _handleLogin() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();

    if (email.isEmpty || password.isEmpty) {
      setState(() {
        _errorMessage = 'Veuillez remplir tous les champs.';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final res = await ApiService().login(email, password);

    if (res['success'] == true) {
      if (res['two_factor'] == true) {
        setState(() {
          _isLoading = false;
          _show2FAInput = true;
          _twoFactorUserId = res['user_id'];
        });
        return;
      }

      TextInput.finishAutofillContext();
      await _saveCredentials();
      // Initialize FCM after successful login
      try {
        await FcmService().init();
      } catch (e) {
        debugPrint('FCM Init Error after login: $e');
      }

      if (mounted) {
        setState(() {
          _isLoading = false;
        });
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const MainLayoutScreen()),
        );
      }
    } else {
      setState(() {
        _isLoading = false;
        _errorMessage = 'Identifiants incorrects ou erreur serveur.';
      });
    }
  }

  Future<void> _handleVerifyOTP() async {
    final code = _otpController.text.trim();
    if (code.isEmpty || code.length < 6) {
      setState(() {
        _errorMessage = 'Veuillez saisir un code valide de 6 chiffres.';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final res = await ApiService().verifyTwoFactor(
      userId: _twoFactorUserId!,
      code: code,
    );

    if (res['success'] == true) {
      TextInput.finishAutofillContext();
      await _saveCredentials();
      try {
        await FcmService().init();
      } catch (e) {
        debugPrint('FCM Init Error after login: $e');
      }

      if (mounted) {
        setState(() {
          _isLoading = false;
        });
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const MainLayoutScreen()),
        );
      }
    } else {
      setState(() {
        _isLoading = false;
        _errorMessage = res['message'] ?? 'Code de vérification incorrect.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final primaryColor = colorScheme.primary;
    final accentColor = colorScheme.secondary;
    final surfaceDark = theme.scaffoldBackgroundColor;
    final surfaceCard = colorScheme.surface;
    final isDark = theme.brightness == Brightness.dark;
    final onSurface = colorScheme.onSurface;

    return Scaffold(
      backgroundColor: surfaceDark,
      body: Stack(
        children: [
          // Background gradient circles
          Positioned(
            top: -120,
            right: -80,
            child: Container(
              width: 300,
              height: 300,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    primaryColor.withAlpha(30),
                    primaryColor.withAlpha(0),
                  ],
                ),
              ),
            ),
          ),
          Positioned(
            bottom: -100,
            left: -60,
            child: Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    accentColor.withAlpha(20),
                    accentColor.withAlpha(0),
                  ],
                ),
              ),
            ),
          ),

          // Main content
          Center(
            child: SingleChildScrollView(
              padding: EdgeInsets.symmetric(horizontal: 28.0),
              child: FadeTransition(
                opacity: _fadeAnim,
                child: SlideTransition(
                  position: _slideAnim,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Logo
                      Center(
                        child: Image.asset(
                          'assets/icon/app_logo.png',
                          height: 200,
                          fit: BoxFit.contain,
                          errorBuilder: (context, error, stackTrace) {
                            return Container(
                              width: 100,
                              height: 100,
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  colors: [primaryColor, accentColor],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                borderRadius: BorderRadius.circular(24),
                                boxShadow: [
                                  BoxShadow(
                                    color: primaryColor.withAlpha(60),
                                    blurRadius: 30,
                                    offset: Offset(0, 10),
                                  ),
                                ],
                              ),
                              child: Icon(
                                Icons.chat_rounded,
                                size: 48,
                                color: Colors.white,
                              ),
                            );
                          },
                        ),
                      ),
                      SizedBox(height: 12),

                      // Title
                      Text(
                        'Bienvenue',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.w800,
                          color: onSurface,
                          letterSpacing: -0.5,
                        ),
                      ),
                      SizedBox(height: 4),
                      Text(
                        'Connectez-vous à votre espace WhatsClick',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 14,
                          color: onSurface.withOpacity(0.47),
                        ),
                      ),
                      SizedBox(height: 32),

                      // Error message
                      if (_errorMessage != null) ...[
                        Container(
                          padding: EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Color(0xFFEF4444).withAlpha(20),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Color(0xFFEF4444).withAlpha(60)),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.error_outline_rounded, color: Color(0xFFEF4444), size: 18),
                              SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  _errorMessage!,
                                  style: TextStyle(color: Color(0xFFFCA5A5), fontSize: 13),
                                ),
                              ),
                            ],
                          ),
                        ),
                        SizedBox(height: 16),
                      ],

                      if (_show2FAInput) ...[
                        // OTP 2FA Input field
                        Container(
                          decoration: BoxDecoration(
                            color: surfaceCard,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: onSurface.withOpacity(0.06)),
                          ),
                          child: TextField(
                            controller: _otpController,
                            keyboardType: TextInputType.number,
                            style: TextStyle(color: onSurface, fontSize: 14),
                            maxLength: 6,
                            decoration: InputDecoration(
                              labelText: 'Code d\'authentification (2FA)',
                              counterText: '',
                              labelStyle: TextStyle(color: onSurface.withOpacity(0.39), fontSize: 13),
                              prefixIcon: Icon(Icons.security, color: onSurface.withOpacity(0.31), size: 20),
                              border: InputBorder.none,
                              enabledBorder: InputBorder.none,
                              focusedBorder: InputBorder.none,
                              contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                            ),
                          ),
                        ),
                        SizedBox(height: 24),
                        // Validate 2FA Button
                        Container(
                          height: 52,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [primaryColor, Color(0xFF0F766E)],
                              begin: Alignment.centerLeft,
                              end: Alignment.centerRight,
                            ),
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(
                                color: primaryColor.withAlpha(60),
                                blurRadius: 16,
                                offset: Offset(0, 6),
                              ),
                            ],
                          ),
                          child: ElevatedButton(
                            onPressed: _isLoading ? null : _handleVerifyOTP,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.transparent,
                              shadowColor: Colors.transparent,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                            ),
                            child: _isLoading
                                ? SizedBox(
                                    height: 22,
                                    width: 22,
                                    child: CircularProgressIndicator(
                                      color: onSurface,
                                      strokeWidth: 2.5,
                                    ),
                                  )
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        'Valider',
                                        style: TextStyle(
                                          fontSize: 15,
                                          fontWeight: FontWeight.w700,
                                          color: onSurface,
                                          letterSpacing: 0.3,
                                        ),
                                      ),
                                      SizedBox(width: 8),
                                      Icon(Icons.check_circle_outline, color: Colors.white, size: 18),
                                    ],
                                  ),
                          ),
                        ),
                        SizedBox(height: 12),
                        // Cancel/Back Button
                        TextButton(
                          onPressed: () {
                            setState(() {
                              _show2FAInput = false;
                              _errorMessage = null;
                              _otpController.clear();
                            });
                          },
                          child: Text(
                            'Retour à la connexion',
                            style: TextStyle(color: accentColor, fontWeight: FontWeight.w600, fontSize: 13),
                          ),
                        ),
                      ] else ...[
                        AutofillGroup(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              // Email Field
                              Container(
                                decoration: BoxDecoration(
                                  color: surfaceCard,
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(color: onSurface.withOpacity(0.06)),
                                ),
                                child: TextField(
                                  controller: _emailController,
                                  autofillHints: const [AutofillHints.username, AutofillHints.email],
                                  keyboardType: TextInputType.emailAddress,
                                  style: TextStyle(color: onSurface, fontSize: 14),
                                  decoration: InputDecoration(
                                    labelText: 'Email ou nom d\'utilisateur',
                                    labelStyle: TextStyle(color: onSurface.withOpacity(0.39), fontSize: 13),
                                    prefixIcon: Icon(Icons.email_outlined, color: onSurface.withOpacity(0.31), size: 20),
                                    border: InputBorder.none,
                                    enabledBorder: InputBorder.none,
                                    focusedBorder: InputBorder.none,
                                    contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                                  ),
                                ),
                              ),
                              SizedBox(height: 14),

                              // Password Field
                              Container(
                                decoration: BoxDecoration(
                                  color: surfaceCard,
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(color: onSurface.withOpacity(0.06)),
                                ),
                                child: TextField(
                                  controller: _passwordController,
                                  obscureText: _obscurePassword,
                                  autofillHints: const [AutofillHints.password],
                                  style: TextStyle(color: onSurface, fontSize: 14),
                                  decoration: InputDecoration(
                                    labelText: 'Mot de passe',
                                    labelStyle: TextStyle(color: onSurface.withOpacity(0.39), fontSize: 13),
                                    prefixIcon: Icon(Icons.lock_outline_rounded, color: onSurface.withOpacity(0.31), size: 20),
                                    suffixIcon: IconButton(
                                      icon: Icon(
                                        _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                                        color: onSurface.withOpacity(0.31),
                                        size: 20,
                                      ),
                                      onPressed: () {
                                        setState(() {
                                          _obscurePassword = !_obscurePassword;
                                        });
                                      },
                                    ),
                                    border: InputBorder.none,
                                    enabledBorder: InputBorder.none,
                                    focusedBorder: InputBorder.none,
                                    contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        SizedBox(height: 14),

                        // Remember Me & Forgot Password
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Row(
                              children: [
                                SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: Checkbox(
                                    value: _rememberMe,
                                    activeColor: primaryColor,
                                    checkColor: onSurface,
                                    side: BorderSide(color: onSurface.withOpacity(0.24)),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                                    onChanged: (value) {
                                      setState(() {
                                        _rememberMe = value ?? false;
                                      });
                                    },
                                  ),
                                ),
                                SizedBox(width: 8),
                                Text(
                                  'Se souvenir',
                                  style: TextStyle(fontSize: 13, color: onSurface.withOpacity(0.55)),
                                ),
                              ],
                            ),
                            TextButton(
                              onPressed: _handleForgotPassword,
                              child: Text(
                                'Mot de passe oublié ?',
                                style: TextStyle(color: accentColor, fontWeight: FontWeight.w600, fontSize: 12),
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 20),

                        // Login Button
                        Container(
                          height: 52,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [primaryColor, Color(0xFF0F766E)],
                              begin: Alignment.centerLeft,
                              end: Alignment.centerRight,
                            ),
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(
                                color: primaryColor.withAlpha(60),
                                blurRadius: 16,
                                offset: Offset(0, 6),
                              ),
                            ],
                          ),
                          child: ElevatedButton(
                            onPressed: _isLoading ? null : _handleLogin,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.transparent,
                              shadowColor: Colors.transparent,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                            ),
                            child: _isLoading
                                ? SizedBox(
                                    height: 22,
                                    width: 22,
                                    child: CircularProgressIndicator(
                                      color: onSurface,
                                      strokeWidth: 2.5,
                                    ),
                                  )
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        'Se connecter',
                                        style: TextStyle(
                                          fontSize: 15,
                                          fontWeight: FontWeight.w700,
                                          color: onSurface,
                                          letterSpacing: 0.3,
                                        ),
                                      ),
                                      SizedBox(width: 8),
                                      Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 18),
                                    ],
                                  ),
                          ),
                        ),
                      ],
                      SizedBox(height: 48),
                      Text(
                        '© 2026 WhatsClick - ASAP Communication',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: onSurface.withOpacity(0.24), fontSize: 11),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

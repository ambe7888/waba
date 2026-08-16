import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter/foundation.dart';
import 'package:firebase_core/firebase_core.dart';
import 'firebase_options.dart';
import 'services/api_service.dart';
import 'services/fcm_service.dart';
import 'services/theme_service.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'screens/main_layout_screen.dart';
import 'screens/onboarding_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase for push notifications
  bool firebaseReady = false;
  try {
    await Firebase.initializeApp(
      options: DefaultFirebaseOptions.currentPlatform,
    );
    firebaseReady = true;
    if (kDebugMode) print('✅ Firebase initialized successfully');
  } catch (e) {
    if (kDebugMode) print('⚠️ Firebase initialization failed: $e');
  }

  FcmService().setFirebaseAvailable(firebaseReady);

  // Initialize ThemeService
  final themeService = ThemeService();
  await themeService.init();

  // Initialize local notifications plugin
  await FcmService.initializeLocalNotifications();

  // Initialize API service and load session token
  final apiService = ApiService();
  await apiService.init();

  // Check Onboarding
  final prefs = await SharedPreferences.getInstance();
  final hasSeenOnboarding = prefs.getBool('hasSeenOnboarding') ?? false;

  runApp(MyApp(hasSeenOnboarding: hasSeenOnboarding));
}

class MyApp extends StatelessWidget {
  final bool hasSeenOnboarding;
  const MyApp({super.key, required this.hasSeenOnboarding});

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: ThemeService(),
      builder: (context, _) {
        final themeService = ThemeService();
        
        // Dynamically update status bar based on theme
        SystemChrome.setSystemUIOverlayStyle(SystemUiOverlayStyle(
          statusBarColor: Colors.transparent,
          statusBarIconBrightness: themeService.isDark ? Brightness.light : Brightness.dark,
          systemNavigationBarColor: themeService.isDark ? ThemeService.darkSurface : ThemeService.lightSurface,
          systemNavigationBarIconBrightness: themeService.isDark ? Brightness.light : Brightness.dark,
        ));

        return MaterialApp(
          title: 'WhatsClick',
          debugShowCheckedModeBanner: false,
          themeMode: themeService.themeMode,
          theme: ThemeService.lightTheme,
          darkTheme: ThemeService.darkTheme,
          routes: {
            '/login': (context) => const LoginScreen(),
            '/register': (context) => const RegisterScreen(),
          },
          home: ApiService().isAuthenticated 
                ? const MainLayoutScreen() 
                : (hasSeenOnboarding ? const LoginScreen() : const OnboardingScreen()),
        );
      },
    );
  }
}

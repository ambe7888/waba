import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter/foundation.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:firebase_core/firebase_core.dart';
import 'firebase_options.dart';
import 'services/api_service.dart';
import 'services/fcm_service.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // 1. Initialize Firebase — wrapped in try-catch so the app starts
  //    even if google-services.json has invalid keys
  bool firebaseReady = false;
  try {
    await Firebase.initializeApp(
      options: DefaultFirebaseOptions.currentPlatform,
    );
    firebaseReady = true;
    if (kDebugMode) print('✅ Firebase initialized successfully');
  } catch (e) {
    if (kDebugMode) print('⚠️ Firebase initialization failed: $e');
    // App will continue without push notifications
  }

  // 2. Initialize local notifications (independent of Firebase)
  try {
    await FcmService.initializeLocalNotifications();
  } catch (e) {
    if (kDebugMode) print('⚠️ Local notifications init failed: $e');
  }

  // 3. Store Firebase availability for FCM service
  FcmService().setFirebaseAvailable(firebaseReady);

  // 4. Initialize API service and load session token
  try {
    final apiService = ApiService();
    await apiService.init();
  } catch (e) {
    if (kDebugMode) print('⚠️ API service init failed: $e');
  }

  // 5. Set system UI overlay style for premium feel
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.light,
    systemNavigationBarColor: Color(0xFF0F172A),
    systemNavigationBarIconBrightness: Brightness.light,
  ));

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    // Premium teal/emerald color palette
    const primaryColor = Color(0xFF0D9488);    // Teal 600
    const primaryDark = Color(0xFF0F766E);     // Teal 700
    const accentColor = Color(0xFF2DD4BF);     // Teal 300
    const surfaceDark = Color(0xFF0F172A);     // Slate 900
    const surfaceCard = Color(0xFF1E293B);     // Slate 800
    const surfaceLight = Color(0xFFF0FDFA);    // Teal 50

    return MaterialApp(
      title: 'WhatsClick',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        primaryColor: primaryColor,
        scaffoldBackgroundColor: surfaceDark,
        colorScheme: const ColorScheme.dark(
          primary: primaryColor,
          onPrimary: Colors.white,
          secondary: accentColor,
          onSecondary: surfaceDark,
          surface: surfaceCard,
          onSurface: Colors.white,
          error: Color(0xFFEF4444),
          onError: Colors.white,
        ),
        textTheme: GoogleFonts.interTextTheme(
          ThemeData.dark().textTheme,
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: surfaceDark,
          foregroundColor: Colors.white,
          elevation: 0,
          centerTitle: false,
          titleTextStyle: TextStyle(
            fontFamily: 'Inter',
            fontSize: 20,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
        ),
        cardTheme: CardThemeData(
          color: surfaceCard,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: surfaceCard,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide(color: Colors.white.withAlpha(25)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: primaryColor, width: 2),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          hintStyle: TextStyle(color: Colors.white.withAlpha(100)),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: primaryColor,
            foregroundColor: Colors.white,
            elevation: 0,
            padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
            textStyle: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.3,
            ),
          ),
        ),
        useMaterial3: true,
      ),
      home: ApiService().isAuthenticated ? const HomeScreen() : const LoginScreen(),
    );
  }
}

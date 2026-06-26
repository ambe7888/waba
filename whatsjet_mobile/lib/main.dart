import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'services/api_service.dart';
import 'services/fcm_service.dart';
import 'services/theme_service.dart';
import 'screens/login_screen.dart';
import 'screens/main_layout_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize ThemeService
  final themeService = ThemeService();
  await themeService.init();

  // Initialize local notifications plugin
  await FcmService.initializeLocalNotifications();

  // Initialize API service and load session token
  final apiService = ApiService();
  await apiService.init();

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

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
          home: ApiService().isAuthenticated ? const MainLayoutScreen() : const LoginScreen(),
        );
      },
    );
  }
}

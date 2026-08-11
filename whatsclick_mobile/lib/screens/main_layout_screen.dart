import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'dart:async';
import '../services/theme_service.dart';
import '../services/fcm_service.dart';
import '../services/api_service.dart';
import '../models/contact.dart';
import 'home_screen.dart';
import 'dashboard_screen.dart';
import 'contacts_screen.dart';
import 'campaign_list_screen.dart';
import 'account_screen.dart';
import 'chat_box_screen.dart';

class MainLayoutScreen extends StatefulWidget {
  const MainLayoutScreen({super.key});

  @override
  State<MainLayoutScreen> createState() => _MainLayoutScreenState();
}

class _MainLayoutScreenState extends State<MainLayoutScreen> {
  int _currentIndex = 1; // Default to Discussions (Chats)
  StreamSubscription<String>? _notificationTapSubscription;

  @override
  void initState() {
    super.initState();
    _notificationTapSubscription = FcmService.onNotificationTap.listen((contactUid) {
      if (mounted) {
        _handleNotificationTap(contactUid);
      }
    });
  }

  @override
  void dispose() {
    _notificationTapSubscription?.cancel();
    super.dispose();
  }

  void _handleNotificationTap(String contactUid) async {
    // Show a loading dialog
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(
        child: CircularProgressIndicator(
          color: ThemeService.primaryColor,
        ),
      ),
    );

    try {
      final contactData = await ApiService().fetchContactDetails(contactUid);
      if (mounted) {
        Navigator.of(context).pop(); // Dismiss loading dialog
      }

      Contact contact;
      if (contactData != null) {
        contact = Contact.fromJson(contactData);
      } else {
        contact = Contact(
          uid: contactUid,
          name: 'Contact',
          phoneNumber: '',
        );
      }

      if (mounted) {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => ChatBoxScreen(contact: contact),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        Navigator.of(context).pop(); // Dismiss loading dialog
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => ChatBoxScreen(
              contact: Contact(
                uid: contactUid,
                name: 'Contact',
                phoneNumber: '',
              ),
            ),
          ),
        );
      }
    }
  }

  // List of screens for the bottom navigation bar
  final List<Widget> _screens = [
    const DashboardScreen(),
    const HomeScreen(), // Discussions
    const ContactsScreen(),
    const CampaignListScreen(),
    const AccountScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: ThemeService(),
      builder: (context, _) {
        final isDark = ThemeService().isDark;
        
        return Scaffold(
          body: IndexedStack(
            index: _currentIndex,
            children: _screens,
          ),
          bottomNavigationBar: Container(
            decoration: BoxDecoration(
              boxShadow: [
                BoxShadow(
                  color: isDark ? Colors.black.withOpacity(0.3) : Colors.black.withOpacity(0.05),
                  blurRadius: 10,
                  offset: Offset(0, -5),
                ),
              ],
            ),
            child: BottomNavigationBar(
              currentIndex: _currentIndex,
              onTap: (index) {
                HapticFeedback.lightImpact();
                setState(() {
                  _currentIndex = index;
                });
              },
              type: BottomNavigationBarType.fixed,
              backgroundColor: isDark ? ThemeService.darkSurface : ThemeService.lightCard,
              selectedItemColor: ThemeService.primaryColor,
              unselectedItemColor: isDark ? Theme.of(context).colorScheme.onSurface.withOpacity(0.5) : Color(0xFF64748B),
              selectedLabelStyle: TextStyle(
                fontFamily: 'Inter',
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
              unselectedLabelStyle: TextStyle(
                fontFamily: 'Inter',
                fontWeight: FontWeight.w500,
                fontSize: 11,
              ),
              items: const [
                BottomNavigationBarItem(
                  icon: Icon(Icons.dashboard_outlined),
                  activeIcon: Icon(Icons.dashboard_rounded),
                  label: 'Tableau',
                ),
                BottomNavigationBarItem(
                  icon: Icon(Icons.chat_bubble_outline_rounded),
                  activeIcon: Icon(Icons.chat_bubble_rounded),
                  label: 'Discussions',
                ),
                BottomNavigationBarItem(
                  icon: Icon(Icons.people_outline_rounded),
                  activeIcon: Icon(Icons.people_rounded),
                  label: 'Contacts',
                ),
                BottomNavigationBarItem(
                  icon: Icon(Icons.campaign_outlined),
                  activeIcon: Icon(Icons.campaign_rounded),
                  label: 'Campagnes',
                ),
                BottomNavigationBarItem(
                  icon: Icon(Icons.person_outline_rounded),
                  activeIcon: Icon(Icons.person_rounded),
                  label: 'Compte',
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

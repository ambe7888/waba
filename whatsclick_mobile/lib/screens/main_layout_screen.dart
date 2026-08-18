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
  int _currentIndex = 0; // Default to Dashboard
  StreamSubscription<String>? _notificationTapSubscription;
  int _unreadConversations = 0;
  late final List<Widget> _screens;

  @override
  void initState() {
    super.initState();
    _screens = [
      const DashboardScreen(),
      HomeScreen(
        onUnreadCountChanged: (count) {
          if (mounted && _unreadConversations != count) {
            setState(() {
              _unreadConversations = count;
            });
          }
        },
      ),
      const ContactsScreen(),
      const CampaignListScreen(),
      const AccountScreen(),
    ];
    _notificationTapSubscription =
        FcmService.onNotificationTap.listen((contactUid) {
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

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: ThemeService(),
      builder: (context, _) {
        final isDark = ThemeService().isDark;

        return Scaffold(
          backgroundColor: isDark ? const Color(0xFF1E293B) : const Color(0xFFF8F9FA),
          body: Container(
            decoration: BoxDecoration(
              image: const DecorationImage(
                image: AssetImage('assets/images/whatsapp_bg.png'),
                fit: BoxFit.cover,
                opacity: 0.45,
              ),
            ),
            child: IndexedStack(
              index: _currentIndex,
              children: _screens,
            ),
          ),
          bottomNavigationBar: Container(
            decoration: BoxDecoration(
              color: isDark ? ThemeService.darkSurface : ThemeService.lightCard,
              boxShadow: [
                BoxShadow(
                  color: isDark
                      ? Colors.black.withValues(alpha: 0.3)
                      : Colors.black.withValues(alpha: 0.05),
                  blurRadius: 10,
                  offset: Offset(0, -5),
                ),
              ],
            ),
            child: SafeArea(
              child: SizedBox(
                height: 65,
                child: Stack(
                  children: [
                    // Sliding indicator
                    AnimatedPositioned(
                      duration: const Duration(milliseconds: 250),
                      curve: Curves.easeOutCubic,
                      top: 0,
                      left: _currentIndex * (MediaQuery.of(context).size.width / 5),
                      width: MediaQuery.of(context).size.width / 5,
                      height: 3,
                      child: Center(
                        child: Container(
                          width: 40,
                          height: 3,
                          decoration: BoxDecoration(
                            color: ThemeService.primaryColor,
                            borderRadius: const BorderRadius.only(
                              bottomLeft: Radius.circular(3),
                              bottomRight: Radius.circular(3),
                            ),
                          ),
                        ),
                      ),
                    ),
                    // Icons
                    Row(
                      children: List.generate(5, (index) {
                        final isSelected = _currentIndex == index;
                        final color = isSelected
                            ? ThemeService.primaryColor
                            : (isDark
                                ? Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)
                                : const Color(0xFF64748B));

                        IconData iconData;
                        String label;
                        switch (index) {
                          case 0:
                            iconData = isSelected ? Icons.grid_view_rounded : Icons.grid_view_rounded;
                            label = 'Tableau';
                            break;
                          case 1:
                            iconData = isSelected ? Icons.chat_bubble_rounded : Icons.chat_bubble_outline_rounded;
                            label = 'Discussions';
                            break;
                          case 2:
                            iconData = isSelected ? Icons.contact_page_rounded : Icons.contact_page_outlined;
                            label = 'Contacts';
                            break;
                          case 3:
                            iconData = isSelected ? Icons.campaign_rounded : Icons.campaign_outlined;
                            label = 'Campagnes';
                            break;
                          case 4:
                            iconData = isSelected ? Icons.settings_rounded : Icons.settings_outlined;
                            label = 'Compte';
                            break;
                          default:
                            iconData = Icons.help_outline;
                            label = '';
                        }

                        return Expanded(
                          child: GestureDetector(
                            behavior: HitTestBehavior.opaque,
                            onTap: () {
                              HapticFeedback.lightImpact();
                              setState(() => _currentIndex = index);
                            },
                            child: SizedBox(
                              height: 65,
                              child: Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Stack(
                                      clipBehavior: Clip.none,
                                      children: [
                                        Icon(iconData, color: color, size: 24),
                                        if (index == 1 && _unreadConversations > 0)
                                          Positioned(
                                            right: -4,
                                            top: -4,
                                            child: Container(
                                              padding: const EdgeInsets.all(4),
                                              decoration: BoxDecoration(
                                                color: ThemeService.primaryColor,
                                                shape: BoxShape.circle,
                                              ),
                                              child: Text(
                                                _unreadConversations > 99 ? '99+' : '$_unreadConversations',
                                                style: const TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 8,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                          ),
                                      ],
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      label,
                                      style: TextStyle(
                                        color: color,
                                        fontSize: 10,
                                        fontFamily: 'Inter',
                                        fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        );
                      }),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

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
import 'login_screen.dart';
import 'ticket_detail_screen.dart';
import 'resource_list_screen.dart';
import '../services/pusher_service.dart';

class MainLayoutScreen extends StatefulWidget {
  const MainLayoutScreen({super.key});

  @override
  State<MainLayoutScreen> createState() => _MainLayoutScreenState();
}

class _MainLayoutScreenState extends State<MainLayoutScreen> {
  int _currentIndex = 0; // Default to Dashboard
  StreamSubscription<Map<String, String>>? _notificationTapSubscription;
  StreamSubscription<void>? _unauthorizedSubscription;
  int _unreadConversations = 0;
  // The single source of truth for the update check - HomeScreen (dialog)
  // and AccountScreen (settings badge) both listen to this instead of each
  // independently calling checkForUpdate(), which used to fire the same
  // unauthenticated GET three times on every cold start.
  final ValueNotifier<Map<String, dynamic>?> _updateInfo = ValueNotifier(null);
  bool _updateAvailable = false;
  late final List<Widget> _screens;
  // Lets DashboardScreen (or anything else) request that the Discussions
  // tab apply a specific segment filter once it becomes visible. Wrapped
  // request object so re-requesting the same filter still fires the
  // listener — see PendingHomeFilterRequest in home_screen.dart.
  final ValueNotifier<PendingHomeFilterRequest?> _pendingHomeFilter =
      ValueNotifier(null);

  void navigateToTab(int index) {
    if (mounted) setState(() => _currentIndex = index);
  }

  void navigateToDiscussions({String filter = 'all'}) {
    _pendingHomeFilter.value = PendingHomeFilterRequest(filter);
    navigateToTab(1);
  }

  @override
  void initState() {
    super.initState();
    _screens = [
      DashboardScreen(
        onOpenContacts: () => navigateToTab(2),
        onOpenDiscussions: (filter) => navigateToDiscussions(filter: filter),
        onOpenCampaigns: () => navigateToTab(3),
      ),
      HomeScreen(
        pendingFilterNotifier: _pendingHomeFilter,
        updateInfoNotifier: _updateInfo,
        onOpenCampaigns: () => navigateToTab(3),
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
      AccountScreen(updateInfoNotifier: _updateInfo),
    ];
    _notificationTapSubscription =
        FcmService.onNotificationTap.listen((data) {
      if (mounted) {
        _handleNotificationTap(data);
      }
    });
    // Intercepteur 401 global : token expiré ou révoqué côté serveur.
    // Toute réponse 401 dans ApiService déclenche ce listener → logout
    // propre + redirection vers LoginScreen sans action utilisateur.
    _unauthorizedSubscription = ApiService.onUnauthorized.listen((_) {
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    });
    _checkUpdate();
    _initPusher();
  }

  Future<void> _initPusher() async {
    final vendorUid = await ApiService().getVendorUid();
    if (vendorUid.isNotEmpty) {
      await PusherService().init(vendorUid);
    }
  }

  Future<void> _checkUpdate() async {
    final updateInfo = await ApiService().checkForUpdate();
    if (!mounted) return;
    // Populating this notifier is what HomeScreen (dialog) and AccountScreen
    // (settings badge) are listening for.
    _updateInfo.value = updateInfo;
    setState(() => _updateAvailable = updateInfo != null);
  }

  @override
  void dispose() {
    _notificationTapSubscription?.cancel();
    _unauthorizedSubscription?.cancel();
    _pendingHomeFilter.dispose();
    _updateInfo.dispose();
    super.dispose();
  }

  void _handleNotificationTap(Map<String, String> data) async {
    final type = data['type'] ?? '';
    final uid = data['uid'] ?? '';
    final contactUid = data['contact_uid'] ?? '';

    if (type == 'support_ticket' && uid.isNotEmpty) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => TicketDetailScreen(ticketUid: uid, subject: ''),
        ),
      );
      return;
    }

    if (type == 'resource') {
      Navigator.of(context).push(
        MaterialPageRoute(builder: (_) => const ResourceListScreen()),
      );
      return;
    }

    if (type == 'campaign') {
      navigateToTab(3);
      return;
    }

    if (contactUid.isEmpty) return;

    _handleChatNotificationTap(contactUid);
  }

  void _handleChatNotificationTap(String contactUid) async {
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
              image: DecorationImage(
                image: const AssetImage('assets/images/whatsapp_bg.png'),
                fit: BoxFit.cover,
                // Kept lower in dark mode: the same pattern that reads as a
                // light texture on the pale surface turns muddy against the
                // dark one and starts competing with the cards.
                opacity: isDark ? 0.30 : 0.85,
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
                                        if (index == 4 && _updateAvailable)
                                          Positioned(
                                            right: -2,
                                            top: -2,
                                            child: Container(
                                              width: 10,
                                              height: 10,
                                              decoration: BoxDecoration(
                                                color: Colors.redAccent,
                                                shape: BoxShape.circle,
                                                border: Border.all(
                                                  color: isDark ? ThemeService.darkSurface : ThemeService.lightCard,
                                                  width: 1.5,
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

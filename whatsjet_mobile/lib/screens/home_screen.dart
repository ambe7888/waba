import 'package:flutter/material.dart';
import 'dart:async';
import 'package:firebase_messaging/firebase_messaging.dart';
import '../services/api_service.dart';
import '../services/fcm_service.dart';
import '../models/contact.dart';
import 'chat_box_screen.dart';
import 'resource_list_screen.dart';
import 'login_screen.dart';
import '../config/app_config.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with TickerProviderStateMixin {
  final _searchController = TextEditingController();
  List<Contact> _contacts = [];
  List<Contact> _filteredContacts = [];
  bool _isLoading = true;
  bool _isLoadingMore = false;
  int _nextPage = 0;
  late final StreamSubscription<RemoteMessage> _fcmSubscription;
  Timer? _pollingTimer;

  // Label filter state
  String? _selectedLabelFilter;
  List<ContactLabel> _allUniqueLabels = [];

  // Animation
  late AnimationController _fadeController;

  @override
  void initState() {
    super.initState();
    _fadeController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 400),
    );
    _loadContacts();
    _searchController.addListener(_applyFilters);

    // Initialize FCM for push notifications
    FcmService().init().catchError((e) {
      debugPrint('FCM Init Error: $e');
    });

    _fcmSubscription = FcmService().onMessage.listen((message) {
      if (mounted) {
        _loadContacts(silent: true);
      }
    });

    // Background polling every 5s for real-time feel
    _pollingTimer = Timer.periodic(
      const Duration(seconds: pollingIntervalSeconds),
      (_) => _loadContacts(silent: true),
    );
  }

  Future<void> _loadContacts({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _isLoading = true;
      });
    }

    final result = await ApiService().fetchContacts(page: 1);
    final List<Contact> loaded = result['contacts'] as List<Contact>;
    final int next = result['nextPage'] as int;

    // Extract unique labels from all contacts
    final Set<ContactLabel> labelsSet = {};
    for (var c in loaded) {
      labelsSet.addAll(c.labels);
    }

    if (mounted) {
      setState(() {
        _contacts = loaded;
        _nextPage = next;
        _allUniqueLabels = labelsSet.toList();
        _isLoading = false;
      });
      _applyFilters();
      if (!silent) {
        _fadeController.forward(from: 0);
      }
    }
  }

  Future<void> _loadMoreContacts() async {
    if (_isLoadingMore || _nextPage == 0) return;

    setState(() {
      _isLoadingMore = true;
    });

    final result = await ApiService().fetchContacts(page: _nextPage);
    final List<Contact> loaded = result['contacts'] as List<Contact>;
    final int next = result['nextPage'] as int;

    // Update unique labels
    final Set<ContactLabel> labelsSet = Set.from(_allUniqueLabels);
    for (var c in loaded) {
      labelsSet.addAll(c.labels);
    }

    setState(() {
      _contacts.addAll(loaded);
      _nextPage = next;
      _allUniqueLabels = labelsSet.toList();
      _isLoadingMore = false;
    });
    _applyFilters();
  }

  void _applyFilters() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredContacts = _contacts.where((contact) {
        // Text search filter
        final matchesSearch = query.isEmpty ||
            contact.name.toLowerCase().contains(query) ||
            contact.phoneNumber.contains(query);

        // Label filter
        final matchesLabel = _selectedLabelFilter == null ||
            (_selectedLabelFilter == '__unread' && contact.unreadCount > 0) ||
            contact.labels.any((l) => l.title == _selectedLabelFilter);

        return matchesSearch && matchesLabel;
      }).toList();
    });
  }

  void _selectLabelFilter(String? label) {
    setState(() {
      _selectedLabelFilter = _selectedLabelFilter == label ? null : label;
    });
    _applyFilters();
  }

  int get _totalUnreadCount =>
      _contacts.fold(0, (sum, c) => sum + c.unreadCount);

  Color _parseColor(String? colorStr) {
    if (colorStr == null || colorStr.isEmpty) return const Color(0xFF64748B);
    try {
      if (colorStr.startsWith('#')) {
        String hex = colorStr.replaceAll('#', '');
        if (hex.length == 3) {
          hex = hex.split('').map((c) => c + c).join();
        }
        if (hex.length == 6) {
          hex = 'FF$hex';
        }
        return Color(int.parse(hex, radix: 16));
      } else if (colorStr.startsWith('rgb')) {
        final regex = RegExp(r'\d+');
        final matches = regex.allMatches(colorStr).map((m) => int.parse(m.group(0)!)).toList();
        if (matches.length >= 3) {
          return Color.fromARGB(255, matches[0], matches[1], matches[2]);
        }
      }
    } catch (e) {
      debugPrint('Color parse error: $e');
    }
    return const Color(0xFF64748B);
  }

  String getRelativeTime(String? timestamp) {
    if (timestamp == null || timestamp.isEmpty) return '';
    try {
      final parsedDate = DateTime.parse(timestamp).toLocal();
      final difference = DateTime.now().difference(parsedDate);

      if (difference.inSeconds < 60) {
        return "à l'instant";
      } else if (difference.inMinutes < 60) {
        return "${difference.inMinutes}min";
      } else if (difference.inHours < 24) {
        return "${difference.inHours}h";
      } else if (difference.inDays < 7) {
        return "${difference.inDays}j";
      } else {
        final weeks = (difference.inDays / 7).floor();
        return "${weeks}sem";
      }
    } catch (e) {
      return '';
    }
  }

  Widget _buildAvatar(Contact contact) {
    final initials = contact.name.trim().isNotEmpty
        ? contact.name.trim().split(' ').map((e) => e.isNotEmpty ? e[0] : '').take(2).join().toUpperCase()
        : 'C';

    // Generate a gradient based on the contact name hash
    final hash = contact.name.hashCode;
    final gradientColors = [
      HSLColor.fromAHSL(1, (hash % 360).toDouble(), 0.6, 0.45).toColor(),
      HSLColor.fromAHSL(1, ((hash + 40) % 360).toDouble(), 0.5, 0.55).toColor(),
    ];

    return Container(
      width: 52,
      height: 52,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        gradient: LinearGradient(
          colors: gradientColors,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: contact.avatar != null && contact.avatar!.isNotEmpty
          ? ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Image.network(
                contact.avatar!,
                width: 52,
                height: 52,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Center(
                  child: Text(
                    initials,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 18,
                    ),
                  ),
                ),
              ),
            )
          : Center(
              child: Text(
                initials,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                  fontSize: 18,
                ),
              ),
            ),
    );
  }

  Widget _buildLabelChip(ContactLabel label, {bool compact = false}) {
    final bgColor = _parseColor(label.bgColor);
    final textColor = _parseColor(label.textColor);

    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 6 : 8,
        vertical: compact ? 2 : 3,
      ),
      decoration: BoxDecoration(
        color: bgColor.withAlpha(40),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label.title,
        style: TextStyle(
          color: textColor,
          fontSize: compact ? 9 : 10,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  Future<void> _handleLogout() async {
    await ApiService().logout();
    if (mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
    }
  }

  @override
  void dispose() {
    _searchController.dispose();
    _fcmSubscription.cancel();
    _pollingTimer?.cancel();
    _fadeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF0D9488);
    const accentColor = Color(0xFF2DD4BF);
    const surfaceDark = Color(0xFF0F172A);
    const surfaceCard = Color(0xFF1E293B);
    final totalUnread = _totalUnreadCount;

    return Scaffold(
      backgroundColor: surfaceDark,
      appBar: AppBar(
        backgroundColor: surfaceDark,
        elevation: 0,
        title: Row(
          children: [
            // Logo mark
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [primaryColor, accentColor],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.chat_rounded, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 10),
            const Text(
              'WhatsClick',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.5,
              ),
            ),
            if (totalUnread > 0) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: primaryColor,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '$totalUnread',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, size: 22),
            onPressed: _loadContacts,
          ),
        ],
      ),
      drawer: _buildDrawer(primaryColor, surfaceDark, accentColor),
      body: Column(
        children: [
          // Search Bar with glassmorphism effect
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: Container(
              decoration: BoxDecoration(
                color: surfaceCard,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.white.withAlpha(15)),
              ),
              child: TextField(
                controller: _searchController,
                style: const TextStyle(color: Colors.white, fontSize: 14),
                decoration: InputDecoration(
                  hintText: 'Rechercher un contact...',
                  hintStyle: TextStyle(color: Colors.white.withAlpha(80), fontSize: 14),
                  prefixIcon: Icon(Icons.search_rounded, color: Colors.white.withAlpha(80), size: 20),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: Icon(Icons.clear_rounded, color: Colors.white.withAlpha(80), size: 18),
                          onPressed: () => _searchController.clear(),
                        )
                      : null,
                  border: InputBorder.none,
                  enabledBorder: InputBorder.none,
                  focusedBorder: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 12, horizontal: 0),
                ),
              ),
            ),
          ),

          // Label Filter Bar
          if (_allUniqueLabels.isNotEmpty || _contacts.any((c) => c.unreadCount > 0))
            Container(
              height: 40,
              margin: const EdgeInsets.only(bottom: 8),
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  // "Tous" chip
                  _buildFilterChip(
                    label: 'Tous',
                    isSelected: _selectedLabelFilter == null,
                    color: primaryColor,
                    onTap: () => _selectLabelFilter(null),
                    count: _contacts.length,
                  ),
                  const SizedBox(width: 8),
                  // "Non lus" chip
                  if (_contacts.any((c) => c.unreadCount > 0))
                    _buildFilterChip(
                      label: 'Non lus',
                      isSelected: _selectedLabelFilter == '__unread',
                      color: const Color(0xFFF59E0B),
                      onTap: () => _selectLabelFilter('__unread'),
                      count: _contacts.where((c) => c.unreadCount > 0).length,
                      icon: Icons.mark_email_unread_rounded,
                    ),
                  if (_contacts.any((c) => c.unreadCount > 0))
                    const SizedBox(width: 8),
                  // Dynamic label chips
                  ..._allUniqueLabels.map((label) {
                    final color = _parseColor(label.bgColor);
                    final count = _contacts.where((c) => c.labels.any((l) => l.title == label.title)).length;
                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: _buildFilterChip(
                        label: label.title,
                        isSelected: _selectedLabelFilter == label.title,
                        color: color,
                        onTap: () => _selectLabelFilter(label.title),
                        count: count,
                      ),
                    );
                  }),
                ],
              ),
            ),

          // Contact List
          Expanded(
            child: _isLoading
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        SizedBox(
                          width: 40,
                          height: 40,
                          child: CircularProgressIndicator(
                            color: primaryColor,
                            strokeWidth: 3,
                            strokeCap: StrokeCap.round,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Chargement des conversations...',
                          style: TextStyle(color: Colors.white.withAlpha(120), fontSize: 14),
                        ),
                      ],
                    ),
                  )
                : _filteredContacts.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              _selectedLabelFilter != null ? Icons.filter_list_off_rounded : Icons.chat_bubble_outline_rounded,
                              size: 56,
                              color: Colors.white.withAlpha(40),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _selectedLabelFilter != null
                                  ? 'Aucun contact avec cette étiquette'
                                  : 'Aucune conversation trouvée',
                              style: TextStyle(color: Colors.white.withAlpha(100), fontSize: 15),
                            ),
                            if (_selectedLabelFilter != null) ...[
                              const SizedBox(height: 12),
                              TextButton(
                                onPressed: () => _selectLabelFilter(null),
                                child: const Text('Voir tous les contacts', style: TextStyle(color: primaryColor)),
                              ),
                            ],
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadContacts,
                        color: primaryColor,
                        backgroundColor: surfaceCard,
                        child: ListView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          itemCount: _filteredContacts.length + (_nextPage > 0 ? 1 : 0),
                          itemBuilder: (context, index) {
                            if (index == _filteredContacts.length) {
                              return _buildLoadMoreButton(primaryColor);
                            }
                            return _buildContactCard(
                              _filteredContacts[index],
                              index,
                              primaryColor,
                              surfaceCard,
                              accentColor,
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip({
    required String label,
    required bool isSelected,
    required Color color,
    required VoidCallback onTap,
    int? count,
    IconData? icon,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? color.withAlpha(40) : const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? color : Colors.white.withAlpha(20),
            width: 1.5,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 14, color: isSelected ? color : Colors.white.withAlpha(120)),
              const SizedBox(width: 4),
            ],
            if (isSelected && icon == null)
              Container(
                width: 6,
                height: 6,
                margin: const EdgeInsets.only(right: 6),
                decoration: BoxDecoration(
                  color: color,
                  shape: BoxShape.circle,
                ),
              ),
            Text(
              label,
              style: TextStyle(
                color: isSelected ? color : Colors.white.withAlpha(160),
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
              ),
            ),
            if (count != null) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                decoration: BoxDecoration(
                  color: isSelected ? color.withAlpha(50) : Colors.white.withAlpha(15),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '$count',
                  style: TextStyle(
                    color: isSelected ? color : Colors.white.withAlpha(100),
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildContactCard(
    Contact contact,
    int index,
    Color primaryColor,
    Color surfaceCard,
    Color accentColor,
  ) {
    final hasUnread = contact.unreadCount > 0;

    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => ChatBoxScreen(contact: contact),
              ),
            ).then((_) {
              _loadContacts(silent: true);
            });
          },
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            decoration: BoxDecoration(
              color: hasUnread ? primaryColor.withAlpha(12) : Colors.transparent,
              borderRadius: BorderRadius.circular(16),
              border: hasUnread
                  ? Border.all(color: primaryColor.withAlpha(30))
                  : null,
            ),
            child: Row(
              children: [
                // Avatar with online indicator
                Stack(
                  children: [
                    _buildAvatar(contact),
                    if (hasUnread)
                      Positioned(
                        right: 0,
                        bottom: 0,
                        child: Container(
                          width: 14,
                          height: 14,
                          decoration: BoxDecoration(
                            color: accentColor,
                            shape: BoxShape.circle,
                            border: Border.all(color: const Color(0xFF0F172A), width: 2),
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(width: 12),
                // Content
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              contact.name,
                              style: TextStyle(
                                fontWeight: hasUnread ? FontWeight.w700 : FontWeight.w600,
                                fontSize: 15,
                                color: Colors.white,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (contact.lastMessageTime != null)
                            Text(
                              getRelativeTime(contact.lastMessageTime),
                              style: TextStyle(
                                color: hasUnread ? accentColor : Colors.white.withAlpha(80),
                                fontSize: 11,
                                fontWeight: hasUnread ? FontWeight.w600 : FontWeight.w400,
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              contact.lastMessage ?? contact.phoneNumber,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 13,
                                color: hasUnread
                                    ? Colors.white.withAlpha(180)
                                    : Colors.white.withAlpha(100),
                                fontWeight: hasUnread ? FontWeight.w500 : FontWeight.w400,
                              ),
                            ),
                          ),
                          if (hasUnread)
                            Container(
                              margin: const EdgeInsets.only(left: 8),
                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                              decoration: BoxDecoration(
                                color: accentColor,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                '${contact.unreadCount}',
                                style: const TextStyle(
                                  color: Color(0xFF0F172A),
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                        ],
                      ),
                      // Labels row
                      if (contact.labels.isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 4,
                          runSpacing: 4,
                          children: contact.labels
                              .take(3)
                              .map((lbl) => _buildLabelChip(lbl, compact: true))
                              .toList(),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLoadMoreButton(Color primaryColor) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16.0, horizontal: 24.0),
      child: _isLoadingMore
          ? Center(
              child: SizedBox(
                width: 28,
                height: 28,
                child: CircularProgressIndicator(color: primaryColor, strokeWidth: 2.5),
              ),
            )
          : OutlinedButton(
              onPressed: _loadMoreContacts,
              style: OutlinedButton.styleFrom(
                side: BorderSide(color: primaryColor.withAlpha(100)),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
              child: Text(
                'Charger plus de conversations',
                style: TextStyle(color: primaryColor, fontWeight: FontWeight.w600, fontSize: 13),
              ),
            ),
    );
  }

  Widget _buildDrawer(Color primaryColor, Color surfaceDark, Color accentColor) {
    return Drawer(
      backgroundColor: surfaceDark,
      child: Column(
        children: [
          // Drawer Header
          Container(
            padding: const EdgeInsets.fromLTRB(20, 60, 20, 20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [primaryColor.withAlpha(40), surfaceDark],
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
              ),
            ),
            child: Row(
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [primaryColor, accentColor],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(Icons.person_rounded, color: Colors.white, size: 28),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'WhatsClick',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 18,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Connecté via Mobile',
                        style: TextStyle(color: Colors.white.withAlpha(120), fontSize: 12),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          _buildDrawerItem(Icons.chat_rounded, 'Discussions', primaryColor, () {
            Navigator.pop(context);
          }),
          _buildDrawerItem(Icons.folder_shared_rounded, 'Ressources Partagées', primaryColor, () {
            Navigator.pop(context);
            Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const ResourceListScreen()),
            );
          }),
          const Spacer(),
          Divider(color: Colors.white.withAlpha(20), height: 1),
          _buildDrawerItem(Icons.logout_rounded, 'Se déconnecter', const Color(0xFFEF4444), _handleLogout),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Widget _buildDrawerItem(IconData icon, String title, Color color, VoidCallback onTap) {
    return ListTile(
      leading: Icon(icon, color: color, size: 22),
      title: Text(
        title,
        style: TextStyle(
          color: color == const Color(0xFFEF4444) ? color : Colors.white.withAlpha(200),
          fontWeight: FontWeight.w500,
          fontSize: 14,
        ),
      ),
      onTap: onTap,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 20),
    );
  }
}

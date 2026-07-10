import 'package:flutter/material.dart';
import 'dart:async';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../services/fcm_service.dart';
import '../services/theme_service.dart';
import '../models/contact.dart';
import 'chat_box_screen.dart';
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
  Timer? _searchDebouncer;

  // Label filter state
  String? _selectedLabelFilter;
  List<ContactLabel> _allUniqueLabels = [];
  String _assignedFilter = 'all';

  // Notification badge counts
  int _unreadNewCount = 0;       // nouveaux (unassigned)
  int _unreadMyCount = 0;        // mes messages (assigned to me)

  // Animation
  late AnimationController _fadeController;

  int _parseNextPage(dynamic value) {
    if (value == null) return 0;
    if (value is int) return value;
    if (value is String) return int.tryParse(value) ?? 0;
    if (value is double) return value.toInt();
    return 0;
  }

  @override
  void initState() {
    super.initState();
    _fadeController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 400),
    );
    _loadContacts();
    _checkUpdate();
    _searchController.addListener(_onSearchChanged);
    

    // Initialize FCM for push notifications
    FcmService().init().catchError((e) {
      debugPrint('FCM Init Error: $e');
    });

    _fcmSubscription = FcmService().onMessage.listen((message) {
      if (mounted) {
        _loadContacts(silent: true);
      }
    });

    // Load badge counts on start
    _refreshBadgeCounts();

    // Background polling every 5s for real-time feel
    _pollingTimer = Timer.periodic(
      const Duration(seconds: pollingIntervalSeconds),
      (_) {
        _loadContacts(silent: true);
        _refreshBadgeCounts();
      },
    );
  }

  Future<void> _checkUpdate() async {
    try {
      final updateInfo = await ApiService().checkForUpdate();
      if (updateInfo != null && mounted) {
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (context) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: const Row(
              children: [
                Icon(Icons.system_update_rounded, color: Colors.teal),
                SizedBox(width: 8),
                Text('Mise à jour dispo ! 🚀'),
              ],
            ),
            content: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'Une nouvelle version (${updateInfo['version']}) de WhatsClick est disponible.',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  if (updateInfo['change_log'].toString().isNotEmpty) ...[
                    const Text('Nouveautés :', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 4),
                    Text(updateInfo['change_log']),
                  ],
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Plus tard'),
              ),
              ElevatedButton(
                onPressed: () async {
                  final url = Uri.parse(updateInfo['apk_url']);
                  if (await canLaunchUrl(url)) {
                    await launchUrl(url, mode: LaunchMode.externalApplication);
                  }
                  if (mounted) {
                    Navigator.of(context).pop();
                  }
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.teal,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Text('Mettre à jour'),
              ),
            ],
          ),
        );
      }
    } catch (_) {}
  }

  Future<void> _loadContacts({bool silent = false, bool reset = false}) async {
    if (_isLoadingMore && !reset) return;

    if (reset) {
      _nextPage = 0;
      _contacts.clear();
      _filteredContacts.clear();
      _isLoading = true;
    } else if (!silent && _nextPage == 0) {
      setState(() => _isLoading = true);
    } else if (_nextPage > 0) {
      setState(() => _isLoadingMore = true);
    }

    try {
      final result = await ApiService().fetchContacts(
        page: 1,
        assigned: _assignedFilter == 'all' ? null : _assignedFilter,
        search: _searchController.text,
      );
      final data = result;
      if (data.isEmpty) return;
      final List<Contact> loaded = data['contacts'] ?? [];
      final next = _parseNextPage(data['nextPage']);
      
      final labelsSet = <ContactLabel>{};
      for (final c in loaded) {
        labelsSet.addAll(c.labels);
      }

      setState(() {
        if (reset || _nextPage == 0) {
          _contacts = loaded;
        } else {
          final Map<String, Contact> merged = {
            for (final existing in _contacts) existing.uid: existing,
            for (final fresh in loaded) fresh.uid: fresh,
          };
          _contacts = merged.values.toList();
        }
        
        _nextPage = next;
        _allUniqueLabels = _contacts.expand((c) => c.labels).toSet().toList();
        _isLoading = false;
      });
      _applyFilters();
      if (!silent) {
        _fadeController.forward(from: 0);
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _isLoadingMore = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur de chargement: $e')),
        );
      }
    }
  }

  Future<void> _loadMoreContacts() async {
    if (_isLoadingMore || _nextPage == 0) return;

    setState(() {
      _isLoadingMore = true;
    });

    final result = await ApiService().fetchContacts(
      page: _nextPage,
      assigned: _assignedFilter == 'all' ? null : _assignedFilter,
      search: _searchController.text,
    );
    final List<Contact> loaded = result['contacts'] as List<Contact>;
    final int next = _parseNextPage(result['nextPage']);

    // Update unique labels
    final Set<ContactLabel> labelsSet = Set.from(_allUniqueLabels);
    for (var c in loaded) {
      labelsSet.addAll(c.labels);
    }

    setState(() {
      final Map<String, Contact> merged = {
        for (final existing in _contacts) existing.uid: existing,
        for (final fresh in loaded) fresh.uid: fresh,
      };
      _contacts = merged.values.toList();
      _nextPage = next;
      _allUniqueLabels = labelsSet.toList();
      _isLoadingMore = false;
    });
    _applyFilters();
  }

  void _onSearchChanged() {
    if (_searchDebouncer?.isActive ?? false) _searchDebouncer!.cancel();
    _searchDebouncer = Timer(const Duration(milliseconds: 500), () {
      _loadContacts(reset: true);
    });
  }

  void _applyFilters() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredContacts = _contacts.where((contact) {
        // Text search filter
        final matchesSearch = query.isEmpty ||
            contact.name.toLowerCase().contains(query) ||
          contact.phoneNumber.contains(query) ||
          (contact.lastMessage?.toLowerCase().contains(query) ?? false);

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
    if (colorStr == null || colorStr.isEmpty) return Color(0xFF64748B);
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
    return Color(0xFF64748B);
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
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
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
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurface,
                  fontWeight: FontWeight.w700,
                  fontSize: 18,
                ),
              ),
            ),
    );
  }

  void _selectAssignedFilter(String filter) {
    if (_assignedFilter == filter) return;
    setState(() {
      _assignedFilter = filter;
    });
    _loadContacts(reset: true);
  }

  // Fetch global unread counts for badge display
  Future<void> _refreshBadgeCounts() async {
    try {
      final counts = await ApiService().fetchUnreadCounts();
      if (mounted) {
        setState(() {
          _unreadNewCount = counts['unreadMessagesCount'] ?? 0;
          _unreadMyCount = counts['myAssignedUnreadMessagesCount'] ?? 0;
        });
      }
    } catch (_) {}
  }

  Widget _buildSegmentButton(String label, String filter) {
    final isSelected = _assignedFilter == filter;
    final isDark = ThemeService().isDark;

    // Use global badge counts from API
    int unreadCount = 0;
    if (filter == 'all' || filter == 'unassigned') {
      unreadCount = _unreadNewCount;
    } else if (filter == 'mine') {
      unreadCount = _unreadMyCount;
    }
    
    return Expanded(
      child: InkWell(
        onTap: () => _selectAssignedFilter(filter),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: isSelected 
                ? const Color(0xFF198754) 
                : (isDark ? ThemeService.darkCard : Colors.grey.withOpacity(0.1)),
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: isSelected 
                  ? const Color(0xFF198754) 
                  : (isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.05)),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: isSelected ? Colors.white : (isDark ? Colors.white70 : Colors.black87),
                ),
                overflow: TextOverflow.ellipsis,
              ),
              if (unreadCount > 0) ...[
                const SizedBox(width: 4),
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: const BoxDecoration(
                    color: Colors.red,
                    shape: BoxShape.circle,
                  ),
                  child: Text(
                    unreadCount.toString(),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 9,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ],
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
    _fcmSubscription.cancel();
    _pollingTimer?.cancel();
    _searchDebouncer?.cancel();
    _searchController.dispose();
    _fadeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF198754);
    const accentColor = Color(0xFF2DD4BF);
    final surfaceCard = Theme.of(context).colorScheme.surface;
    final totalUnread = _totalUnreadCount;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        title: Row(
          children: [
            // Page icon
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: primaryColor.withAlpha(30),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(Icons.forum_rounded, color: primaryColor, size: 20),
            ),
            SizedBox(width: 10),
            Text(
              'Discussions',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.5,
              ),
            ),
            if (totalUnread > 0) ...[
              SizedBox(width: 8),
              Container(
                padding: EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: primaryColor,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '$totalUnread',
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurface,
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
            icon: Icon(Icons.refresh_rounded, size: 22),
            onPressed: _loadContacts,
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar with glassmorphism effect
          Padding(
            padding: EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: Container(
              decoration: BoxDecoration(
                color: surfaceCard,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.06)),
              ),
              child: TextField(
                controller: _searchController,
                style: TextStyle(color: Theme.of(context).colorScheme.onSurface, fontSize: 14),
                decoration: InputDecoration(
                  hintText: 'Rechercher un contact...',
                  hintStyle: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31), fontSize: 14),
                  prefixIcon: Icon(Icons.search_rounded, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31), size: 20),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: Icon(Icons.clear_rounded, color: Theme.of(context).colorScheme.onSurface.withOpacity(0.31), size: 18),
                          onPressed: () => _searchController.clear(),
                        )
                      : null,
                  border: InputBorder.none,
                  enabledBorder: InputBorder.none,
                  focusedBorder: InputBorder.none,
                  contentPadding: EdgeInsets.symmetric(vertical: 12, horizontal: 0),
                ),
              ),
            ),
          ),

          // Segmented filter for assignments
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 4.0),
            child: Row(
              children: [
                _buildSegmentButton('Tous', 'all'),
                const SizedBox(width: 8),
                _buildSegmentButton('Mes messages', 'to-me'),
                const SizedBox(width: 8),
                _buildSegmentButton('Nouveaux', 'unassigned'),
              ],
            ),
          ),

          // Label Filter Bar
          if (_allUniqueLabels.isNotEmpty || _contacts.any((c) => c.unreadCount > 0))
            Container(
              height: 40,
              margin: EdgeInsets.only(bottom: 8),
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: EdgeInsets.symmetric(horizontal: 16),
                children: [
                  // "Tous" chip
                  _buildFilterChip(
                    label: 'Tous',
                    isSelected: _selectedLabelFilter == null,
                    color: primaryColor,
                    onTap: () => _selectLabelFilter(null),
                    count: _contacts.length,
                  ),
                  SizedBox(width: 8),
                  // "Non lus" chip
                  if (_contacts.any((c) => c.unreadCount > 0))
                    _buildFilterChip(
                      label: 'Non lus',
                      isSelected: _selectedLabelFilter == '__unread',
                      color: Color(0xFFF59E0B),
                      onTap: () => _selectLabelFilter('__unread'),
                      count: _contacts.where((c) => c.unreadCount > 0).length,
                      icon: Icons.mark_email_unread_rounded,
                    ),
                  if (_contacts.any((c) => c.unreadCount > 0))
                    SizedBox(width: 8),
                  // Dynamic label chips
                  ..._allUniqueLabels.map((label) {
                    final color = _parseColor(label.bgColor);
                    final count = _contacts.where((c) => c.labels.any((l) => l.title == label.title)).length;
                    return Padding(
                      padding: EdgeInsets.only(right: 8),
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
                        SizedBox(height: 16),
                        Text(
                          'Chargement des conversations...',
                          style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.47), fontSize: 14),
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
                              color: Theme.of(context).colorScheme.onSurface.withOpacity(0.16),
                            ),
                            SizedBox(height: 16),
                            Text(
                              _selectedLabelFilter != null
                                  ? 'Aucun contact avec cette étiquette'
                                  : 'Aucune conversation trouvée',
                              style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.39), fontSize: 15),
                            ),
                            if (_selectedLabelFilter != null) ...[
                              SizedBox(height: 12),
                              TextButton(
                                onPressed: () => _selectLabelFilter(null),
                                child: Text('Voir tous les contacts', style: TextStyle(color: primaryColor)),
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
                          padding: EdgeInsets.symmetric(horizontal: 12),
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
        padding: EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? color.withAlpha(40) : (Theme.of(context).brightness == Brightness.dark ? const Color(0xFF1E293B) : Theme.of(context).colorScheme.onSurface.withOpacity(0.05)),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? color : Theme.of(context).colorScheme.onSurface.withOpacity(0.08),
            width: 1.5,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 14, color: isSelected ? color : Theme.of(context).colorScheme.onSurface.withOpacity(0.47)),
              SizedBox(width: 4),
            ],
            if (isSelected && icon == null)
              Container(
                width: 6,
                height: 6,
                margin: EdgeInsets.only(right: 6),
                decoration: BoxDecoration(
                  color: color,
                  shape: BoxShape.circle,
                ),
              ),
            Text(
              label,
              style: TextStyle(
                color: isSelected ? color : Theme.of(context).colorScheme.onSurface.withOpacity(0.63),
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
              ),
            ),
            if (count != null) ...[
              SizedBox(width: 6),
              Container(
                padding: EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                decoration: BoxDecoration(
                  color: isSelected ? color.withAlpha(50) : Theme.of(context).colorScheme.onSurface.withOpacity(0.06),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '$count',
                  style: TextStyle(
                    color: isSelected ? color : Theme.of(context).colorScheme.onSurface.withOpacity(0.39),
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
      padding: EdgeInsets.only(bottom: 4),
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
            padding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
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
                            border: Border.all(color: Color(0xFF0F172A), width: 2),
                          ),
                        ),
                      ),
                  ],
                ),
                SizedBox(width: 12),
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
                                color: Theme.of(context).colorScheme.onSurface,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (contact.lastMessageTime != null)
                            Text(
                              getRelativeTime(contact.lastMessageTime),
                              style: TextStyle(
                                color: hasUnread ? accentColor : Theme.of(context).colorScheme.onSurface.withOpacity(0.31),
                                fontSize: 11,
                                fontWeight: hasUnread ? FontWeight.w600 : FontWeight.w400,
                              ),
                            ),
                        ],
                      ),
                      SizedBox(height: 4),
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
                                    ? Theme.of(context).colorScheme.onSurface.withOpacity(0.71)
                                    : Theme.of(context).colorScheme.onSurface.withOpacity(0.39),
                                fontWeight: hasUnread ? FontWeight.w500 : FontWeight.w400,
                              ),
                            ),
                          ),
                          if (hasUnread)
                            Container(
                              margin: EdgeInsets.only(left: 8),
                              padding: EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                              decoration: BoxDecoration(
                                color: accentColor,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                '${contact.unreadCount}',
                                style: TextStyle(
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
                        SizedBox(height: 6),
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
      padding: EdgeInsets.symmetric(vertical: 16.0, horizontal: 24.0),
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
                padding: EdgeInsets.symmetric(vertical: 12),
              ),
              child: Text(
                'Charger plus de conversations',
                style: TextStyle(color: primaryColor, fontWeight: FontWeight.w600, fontSize: 13),
              ),
            ),
    );
  }

}

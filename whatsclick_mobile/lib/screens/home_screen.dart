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
import 'notifications_screen.dart';
import '../config/app_config.dart';

class HomeScreen extends StatefulWidget {
  final Function(int)? onUnreadCountChanged;
  const HomeScreen({super.key, this.onUnreadCountChanged});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen>
    with TickerProviderStateMixin, WidgetsBindingObserver {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  List<Contact> _contacts = [];
  List<Contact> _filteredContacts = [];
  bool _isLoading = true;
  bool _isLoadingMore = false;
  int _nextPage = 0;

  late final StreamSubscription<RemoteMessage> _fcmSubscription;
  Timer? _pollingTimer;
  Timer? _searchDebouncer;

  // Label filter state
  final List<String> _selectedLabelFilters = [];
  List<ContactLabel> _allUniqueLabels = [];
  String _assignedFilter = 'all';
  
  // Date filter state
  DateTime? _filterStartDate;
  DateTime? _filterEndDate;

  // Notification badge counts
  int _unreadNewCount = 0; // nouveaux (unassigned)
  int _unreadMyCount = 0; // mes messages (assigned to me)

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
    WidgetsBinding.instance.addObserver(this);
    _fadeController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 400),
    );
    _loadContacts();
    _checkUpdate();
    _searchController.addListener(_onSearchChanged);
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
          _scrollController.position.maxScrollExtent - 200) {
        _loadMoreContacts();
      }
    });

    // Initialize FCM for push notifications
    FcmService().init().catchError((e) {
      debugPrint('FCM Init Error: $e');
    });

    _fcmSubscription = FcmService().onMessage.listen((message) {
      if (mounted) {
        // Optimistic instant update
        final data = message.data;
        final contactUid = data['contact_uid'] ?? data['contactUid'];
        if (contactUid != null) {
          final title = message.notification?.title ?? data['title'];
          final body = message.notification?.body ?? data['body'];
          
          setState(() {
            final idx = _contacts.indexWhere((c) => c.uid == contactUid);
            if (idx != -1) {
              final existing = _contacts[idx];
              final updated = existing.copyWith(
                lastMessage: body ?? existing.lastMessage,
                lastMessageTime: DateTime.now().toUtc().toIso8601String(),
                unreadCount: existing.unreadCount + 1,
              );
              _contacts.removeAt(idx);
              _contacts.insert(0, updated);
            }
          });
          _applyFilters();
        }

        _loadContacts(silent: true);
        _refreshBadgeCounts();
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

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive) {
      _pollingTimer?.cancel();
      _pollingTimer = null;
    } else if (state == AppLifecycleState.resumed) {
      _loadContacts(silent: true);
      _refreshBadgeCounts();
      _pollingTimer?.cancel();
      _pollingTimer = Timer.periodic(
        const Duration(seconds: pollingIntervalSeconds),
        (_) {
          _loadContacts(silent: true);
          _refreshBadgeCounts();
        },
      );
    }
  }

  Future<void> _checkUpdate() async {
    try {
      final updateInfo = await ApiService().checkForUpdate();
      if (updateInfo != null && mounted) {
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (context) => AlertDialog(
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
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
                    const Text('Nouveautés :',
                        style: TextStyle(fontWeight: FontWeight.w600)),
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
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8)),
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
        assigned: (_assignedFilter == 'all' || _assignedFilter == 'unread') ? null : _assignedFilter,
        search: _searchController.text,
      );
      final data = result;
      if (data.isEmpty) {
        if (mounted) setState(() { _isLoading = false; _isLoadingMore = false; });
        return;
      }
      final List<Contact> loaded = data['contacts'] ?? [];
      final next = _parseNextPage(data['nextPage']);

      final labelsSet = <ContactLabel>{};
      for (final c in loaded) {
        labelsSet.addAll(c.labels);
      }

      setState(() {
        if (reset || (!silent && _nextPage == 0)) {
          _contacts = loaded;
          _nextPage = next;
        } else {
          // If silent (refreshing page 1), we want the `loaded` contacts to be at the top in their exact order.
          // Then we append any existing contacts that aren't in the `loaded` list.
          final Map<String, Contact> newOrder = {
            for (final fresh in loaded) fresh.uid: fresh,
          };
          for (final existing in _contacts) {
            if (!newOrder.containsKey(existing.uid)) {
              newOrder[existing.uid] = existing;
            }
          }
          _contacts = newOrder.values.toList();
          if (!silent) _nextPage = next;
        }

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

    try {
      final result = await ApiService().fetchContacts(
        page: _nextPage,
        assigned: (_assignedFilter == 'all' || _assignedFilter == 'unread') ? null : _assignedFilter,
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
        if (loaded.isEmpty) {
          _nextPage = 0;
        }
        _allUniqueLabels = labelsSet.toList();
        _isLoadingMore = false;
      });
      _applyFilters();
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoadingMore = false;
          _nextPage = 0; // Stop looping if error occurs
        });
      }
    }
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
        final matchesLabel = _selectedLabelFilters.isEmpty ||
            (_selectedLabelFilters.contains('__unread') && contact.unreadCount > 0) ||
            contact.labels.any((l) => _selectedLabelFilters.contains(l.title));

        // Date filter
        bool matchesDate = true;
        if (_filterStartDate != null || _filterEndDate != null) {
          if (contact.lastMessageTime != null) {
            final msgDate = DateTime.tryParse(contact.lastMessageTime!)?.toLocal();
            if (msgDate != null) {
              final msgDay = DateTime(msgDate.year, msgDate.month, msgDate.day);
              if (_filterStartDate != null) {
                final startDay = DateTime(_filterStartDate!.year, _filterStartDate!.month, _filterStartDate!.day);
                if (msgDay.isBefore(startDay)) matchesDate = false;
              }
              if (_filterEndDate != null) {
                final endDay = DateTime(_filterEndDate!.year, _filterEndDate!.month, _filterEndDate!.day, 23, 59, 59);
                if (msgDay.isAfter(endDay)) matchesDate = false;
              }
            } else {
              matchesDate = false;
            }
          } else {
            matchesDate = false;
          }
        }

        return matchesSearch && matchesLabel && matchesDate;
      }).toList();

      // Sort by last message time descending
      _filteredContacts.sort((a, b) {
        if (a.lastMessageTime == null && b.lastMessageTime == null) return 0;
        if (a.lastMessageTime == null) return 1;
        if (b.lastMessageTime == null) return -1;
        
        final dateA = DateTime.tryParse(a.lastMessageTime!);
        final dateB = DateTime.tryParse(b.lastMessageTime!);
        
        if (dateA == null && dateB == null) return 0;
        if (dateA == null) return 1;
        if (dateB == null) return -1;
        
        return dateB.compareTo(dateA);
      });
    });
  }

  void _showFilterBottomSheet() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final surfaceCard = Theme.of(context).scaffoldBackgroundColor;
            return Container(
              height: MediaQuery.of(context).size.height * 0.75,
              decoration: BoxDecoration(
                color: surfaceCard,
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(20),
                  topRight: Radius.circular(20),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header
                  Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Filtrer les conversations',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close_rounded),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  // Scrollable Content
                  Expanded(
                    child: ListView(
                      padding: const EdgeInsets.all(16.0),
                      children: [
                        // Date Range
                        const Text(
                          'Période',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: InkWell(
                                onTap: () async {
                                  final date = await showDatePicker(
                                    context: context,
                                    initialDate: _filterStartDate ?? DateTime.now(),
                                    firstDate: DateTime(2020),
                                    lastDate: DateTime(2100),
                                  );
                                  if (date != null) {
                                    setModalState(() => _filterStartDate = date);
                                    setState(() {});
                                  }
                                },
                                child: Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    border: Border.all(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.15)),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Row(
                                    children: [
                                      const Icon(Icons.calendar_today_rounded, size: 16),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          _filterStartDate != null 
                                              ? "${_filterStartDate!.day.toString().padLeft(2,'0')}/${_filterStartDate!.month.toString().padLeft(2,'0')}/${_filterStartDate!.year}"
                                              : "Date début",
                                          style: const TextStyle(fontSize: 13),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                      if (_filterStartDate != null)
                                        InkWell(
                                          onTap: () {
                                            setModalState(() => _filterStartDate = null);
                                            setState(() {});
                                          },
                                          child: const Icon(Icons.close, size: 16),
                                        ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: InkWell(
                                onTap: () async {
                                  final date = await showDatePicker(
                                    context: context,
                                    initialDate: _filterEndDate ?? DateTime.now(),
                                    firstDate: DateTime(2020),
                                    lastDate: DateTime(2100),
                                  );
                                  if (date != null) {
                                    setModalState(() => _filterEndDate = date);
                                    setState(() {});
                                  }
                                },
                                child: Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    border: Border.all(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.15)),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Row(
                                    children: [
                                      const Icon(Icons.calendar_today_rounded, size: 16),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          _filterEndDate != null 
                                              ? "${_filterEndDate!.day.toString().padLeft(2,'0')}/${_filterEndDate!.month.toString().padLeft(2,'0')}/${_filterEndDate!.year}"
                                              : "Date fin",
                                          style: const TextStyle(fontSize: 13),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                      if (_filterEndDate != null)
                                        InkWell(
                                          onTap: () {
                                            setModalState(() => _filterEndDate = null);
                                            setState(() {});
                                          },
                                          child: const Icon(Icons.close, size: 16),
                                        ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                        
                        const SizedBox(height: 24),
                        
                        // Tags List
                        const Text(
                          'Étiquettes',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            FilterChip(
                              label: const Text('Toutes les étiquettes'),
                              selected: _selectedLabelFilters.isEmpty || (_selectedLabelFilters.length == 1 && _selectedLabelFilters.contains('__unread')),
                              selectedColor: ThemeService.primaryColor.withValues(alpha: 0.2),
                              labelStyle: TextStyle(
                                color: (_selectedLabelFilters.isEmpty || (_selectedLabelFilters.length == 1 && _selectedLabelFilters.contains('__unread')))
                                    ? ThemeService.primaryColor
                                    : Theme.of(context).colorScheme.onSurface,
                              ),
                              onSelected: (selected) {
                                if (selected) {
                                  setState(() {
                                    _selectedLabelFilters.removeWhere((l) => l != '__unread');
                                  });
                                  setModalState(() {});
                                }
                              },
                            ),
                            ..._allUniqueLabels.map((label) {
                              final isSelected = _selectedLabelFilters.contains(label.title);
                              final color = _parseColor(label.bgColor);
                              return FilterChip(
                                label: Text(label.title),
                                selected: isSelected,
                                selectedColor: color.withValues(alpha: 0.2),
                                labelStyle: TextStyle(
                                  color: isSelected ? color : Theme.of(context).colorScheme.onSurface,
                                ),
                                onSelected: (selected) {
                                  setState(() {
                                    if (selected) {
                                      _selectedLabelFilters.add(label.title);
                                      if (_assignedFilter == 'unread') {
                                        _assignedFilter = 'all';
                                      }
                                    } else {
                                      _selectedLabelFilters.remove(label.title);
                                    }
                                  });
                                  setModalState(() {});
                                },
                              );
                            }),
                          ],
                        ),
                      ],
                    ),
                  ),
                  // Bottom Actions
                  Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () {
                              setState(() {
                                _selectedLabelFilters.removeWhere((l) => l != '__unread');
                                _filterStartDate = null;
                                _filterEndDate = null;
                              });
                              setModalState(() {});
                              _applyFilters();
                              Navigator.pop(context);
                            },
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            child: const Text('Réinitialiser'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: () {
                              _applyFilters();
                              Navigator.pop(context);
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: ThemeService.primaryColor,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            child: const Text('Appliquer'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
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
        final matches = regex
            .allMatches(colorStr)
            .map((m) => int.parse(m.group(0)!))
            .toList();
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
      final now = DateTime.now();
      
      final today = DateTime(now.year, now.month, now.day);
      final yesterday = today.subtract(const Duration(days: 1));
      final msgDate = DateTime(parsedDate.year, parsedDate.month, parsedDate.day);
      
      if (msgDate == today) {
        return "${parsedDate.hour.toString().padLeft(2, '0')}:${parsedDate.minute.toString().padLeft(2, '0')}";
      } else if (msgDate == yesterday) {
        return "Hier";
      } else {
        final difference = today.difference(msgDate).inDays;
        if (difference < 7) {
          const days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
          return days[parsedDate.weekday - 1];
        } else {
          return "${parsedDate.day.toString().padLeft(2, '0')}/${parsedDate.month.toString().padLeft(2, '0')}/${parsedDate.year.toString().substring(2)}";
        }
      }
    } catch (e) {
      return '';
    }
  }

  Widget _buildAvatar(Contact contact) {
    final initials = contact.name.trim().isNotEmpty
        ? contact.name
            .trim()
            .split(' ')
            .map((e) => e.isNotEmpty ? e[0] : '')
            .take(2)
            .join()
            .toUpperCase()
        : 'C';

    // Generate a color based on the contact name hash
    final hash = contact.name.hashCode;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    // Colored text and border
    final color = HSLColor.fromAHSL(1, (hash % 360).toDouble(), 0.8, 0.45).toColor();
    // White background in light mode, dark surface in dark mode
    final bgColor = isDark ? const Color(0xFF1E293B) : Colors.white;

    return Container(
      width: 52,
      height: 52,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: bgColor,
        border: Border.all(color: color, width: 1.2),
      ),
      child: contact.avatar != null && contact.avatar!.isNotEmpty
          ? ClipOval(
              child: Image.network(
                contact.avatar!,
                width: 52,
                height: 52,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Center(
                  child: Text(
                    initials,
                    style: TextStyle(
                      color: color,
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
                  color: color,
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
      if (filter == 'unread') {
        if (!_selectedLabelFilters.contains('__unread')) {
           _selectedLabelFilters.add('__unread');
        }
      } else {
        _selectedLabelFilters.remove('__unread');
      }
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

    int unreadCount = 0;
    if (filter == 'all' || filter == 'unassigned') {
      unreadCount = _unreadNewCount;
    } else if (filter == 'mine') {
      unreadCount = _unreadMyCount;
    }

    final pillBg = isSelected
        ? ThemeService.primaryColor
        : (isDark ? const Color(0xFF1E293B) : const Color(0xFFE2E8F0));
    final pillTextColor = isSelected
        ? Colors.white
        : (isDark ? const Color(0xFF94A3B8) : const Color(0xFF475569));

    return InkWell(
      onTap: () => _selectAssignedFilter(filter),
      borderRadius: BorderRadius.circular(20),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: BoxDecoration(
          color: pillBg,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: TextStyle(
                fontFamily: 'Inter',
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                color: pillTextColor,
              ),
            ),
            if (unreadCount > 0) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                decoration: BoxDecoration(
                  color: isSelected
                      ? Colors.white.withValues(alpha: 0.3)
                      : ThemeService.primaryColor,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  unreadCount.toString(),
                  style: const TextStyle(
                    color: Colors.white,
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

  Widget _buildLabelChip(ContactLabel label, {bool compact = false}) {
    final bgColor = _parseColor(label.bgColor);
    final textColor = _parseColor(label.textColor).withValues(alpha: 1.0);

    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 6 : 8,
        vertical: compact ? 2 : 3,
      ),
      decoration: BoxDecoration(
        color: bgColor.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: bgColor.withValues(alpha: 0.3), width: 0.5),
      ),
      child: Text(
        label.title,
        style: TextStyle(
          color: textColor,
          fontSize: compact ? 10 : 11,
          fontWeight: FontWeight.w700,
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
    WidgetsBinding.instance.removeObserver(this);
    _fcmSubscription.cancel();
    _pollingTimer?.cancel();
    _searchDebouncer?.cancel();
    _searchController.dispose();
    _scrollController.dispose();
    _fadeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF198754);
    const accentColor = Color(0xFF2DD4BF);
    final surfaceCard = Theme.of(context).colorScheme.surface;
    final totalUnread = _totalUnreadCount;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) widget.onUnreadCountChanged?.call(totalUnread);
    });

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
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
            icon: const Icon(Icons.notifications_none_rounded, size: 24),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const NotificationsScreen()),
              );
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar with filter button
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: surfaceCard,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                          color: Theme.of(context)
                              .colorScheme
                              .onSurface
                              .withValues(alpha: 0.12),
                          width: 1),
                    ),
                    child: TextField(
                      controller: _searchController,
                      style: TextStyle(
                          color: Theme.of(context).colorScheme.onSurface,
                          fontSize: 14),
                      decoration: InputDecoration(
                        hintText: 'Rechercher un contact...',
                        hintStyle: TextStyle(
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withValues(alpha: 0.31),
                            fontSize: 14),
                        prefixIcon: Icon(Icons.search_rounded,
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withValues(alpha: 0.31),
                            size: 20),
                        suffixIcon: _searchController.text.isNotEmpty
                            ? IconButton(
                                icon: Icon(Icons.clear_rounded,
                                    color: Theme.of(context)
                                        .colorScheme
                                        .onSurface
                                        .withValues(alpha: 0.31),
                                    size: 18),
                                onPressed: () => _searchController.clear(),
                              )
                            : null,
                        border: InputBorder.none,
                        enabledBorder: InputBorder.none,
                        focusedBorder: InputBorder.none,
                        contentPadding:
                            const EdgeInsets.symmetric(vertical: 12, horizontal: 0),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                InkWell(
                  onTap: _showFilterBottomSheet,
                  borderRadius: BorderRadius.circular(14),
                  child: Stack(
                    clipBehavior: Clip.none,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(13),
                        decoration: BoxDecoration(
                          color: surfaceCard,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.12),
                              width: 1),
                        ),
                        child: Icon(
                          Icons.filter_list_rounded,
                          size: 22,
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                        ),
                      ),
                      if (_selectedLabelFilters.where((l) => l != '__unread').isNotEmpty || _filterStartDate != null || _filterEndDate != null)
                        Positioned(
                          right: -4,
                          top: -4,
                          child: InkWell(
                            onTap: () {
                              setState(() {
                                _selectedLabelFilters.removeWhere((l) => l != '__unread');
                                _filterStartDate = null;
                                _filterEndDate = null;
                              });
                              _applyFilters();
                            },
                            child: Container(
                              padding: const EdgeInsets.all(4),
                              decoration: const BoxDecoration(
                                color: Colors.red,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.close_rounded, size: 10, color: Colors.white),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Segmented filter pills
          Container(
            height: 34,
            margin: const EdgeInsets.only(bottom: 8),
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                _buildSegmentButton('Tous', 'all'),
                const SizedBox(width: 8),
                _buildSegmentButton('Non lu', 'unread'),
                const SizedBox(width: 8),
                _buildSegmentButton('Moi', 'to-me'),
                const SizedBox(width: 8),
                _buildSegmentButton('Non assigné', 'unassigned'),
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
                          style: TextStyle(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.47),
                              fontSize: 14),
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
                              _selectedLabelFilters.isNotEmpty
                                  ? Icons.filter_list_off_rounded
                                  : Icons.chat_bubble_outline_rounded,
                              size: 56,
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.16),
                            ),
                            SizedBox(height: 16),
                            Text(
                              _selectedLabelFilters.isNotEmpty
                                  ? 'Aucun contact avec cette étiquette'
                                  : 'Aucune conversation trouvée',
                              style: TextStyle(
                                  color: Theme.of(context)
                                      .colorScheme
                                      .onSurface
                                      .withValues(alpha: 0.39),
                                  fontSize: 15),
                            ),
                            if (_selectedLabelFilters.isNotEmpty) ...[
                              SizedBox(height: 12),
                              TextButton(
                                onPressed: () {
                                  setState(() {
                                    _selectedLabelFilters.clear();
                                    _applyFilters();
                                  });
                                },
                                child: Text('Voir tous les contacts',
                                    style: TextStyle(color: primaryColor)),
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
                          controller: _scrollController,
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: EdgeInsets.symmetric(horizontal: 12),
                          itemCount: _filteredContacts.length +
                              (_nextPage > 0 ? 1 : 0),
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
          color: isSelected
              ? color.withAlpha(40)
              : (Theme.of(context).brightness == Brightness.dark
                  ? const Color(0xFF1E293B)
                  : Theme.of(context)
                      .colorScheme
                      .onSurface
                      .withValues(alpha: 0.05)),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected
                ? color
                : Theme.of(context)
                    .colorScheme
                    .onSurface
                    .withValues(alpha: 0.08),
            width: 1.5,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon,
                  size: 14,
                  color: isSelected
                      ? color
                      : Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.47)),
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
                color: isSelected
                    ? color
                    : Theme.of(context)
                        .colorScheme
                        .onSurface
                        .withValues(alpha: 0.63),
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
              ),
            ),
            if (count != null) ...[
              SizedBox(width: 6),
              Container(
                padding: EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                decoration: BoxDecoration(
                  color: isSelected
                      ? color.withAlpha(50)
                      : Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.06),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '$count',
                  style: TextStyle(
                    color: isSelected
                        ? color
                        : Theme.of(context)
                            .colorScheme
                            .onSurface
                            .withValues(alpha: 0.39),
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
          onTap: () async {
            final result = await Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => ChatBoxScreen(contact: contact),
              ),
            );
            
            if (result != null && result is String) {
              setState(() {
                final idx = _contacts.indexWhere((c) => c.uid == contact.uid);
                if (idx != -1) {
                  final existing = _contacts[idx];
                  final updated = existing.copyWith(
                    lastMessage: result,
                    lastMessageTime: DateTime.now().toUtc().toIso8601String(),
                  );
                  _contacts.removeAt(idx);
                  _contacts.insert(0, updated);
                }
              });
              _applyFilters();
            }
            
            _loadContacts(silent: true);
          },
          child: Container(
            padding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            decoration: BoxDecoration(
              color:
                  hasUnread ? primaryColor.withAlpha(12) : Colors.transparent,
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
                            border:
                                Border.all(color: Color(0xFF0F172A), width: 2),
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
                                fontWeight: hasUnread
                                    ? FontWeight.w700
                                    : FontWeight.w600,
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
                                color: hasUnread
                                    ? accentColor
                                    : Theme.of(context)
                                        .colorScheme
                                        .onSurface
                                        .withValues(alpha: 0.31),
                                fontSize: 11,
                                fontWeight: hasUnread
                                    ? FontWeight.w600
                                    : FontWeight.w400,
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
                                    ? Theme.of(context)
                                        .colorScheme
                                        .onSurface
                                        .withValues(alpha: 0.71)
                                    : Theme.of(context)
                                        .colorScheme
                                        .onSurface
                                        .withValues(alpha: 0.39),
                                fontWeight: hasUnread
                                    ? FontWeight.w500
                                    : FontWeight.w400,
                              ),
                            ),
                          ),
                          if (hasUnread)
                            Container(
                              margin: EdgeInsets.only(left: 8),
                              padding: EdgeInsets.symmetric(
                                  horizontal: 7, vertical: 3),
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
                child: CircularProgressIndicator(
                    color: primaryColor, strokeWidth: 2.5),
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
                style: TextStyle(
                    color: primaryColor,
                    fontWeight: FontWeight.w600,
                    fontSize: 13),
              ),
            ),
    );
  }
}

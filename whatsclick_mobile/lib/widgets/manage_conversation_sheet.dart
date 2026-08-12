import 'package:flutter/material.dart';
import '../services/theme_service.dart';
import '../models/contact.dart';

class ManageConversationSheet extends StatefulWidget {
  final Contact contact;
  final Function(String assignee)? onAssign;
  final Function(String status)? onStatusChange;
  final Function(bool aiEnabled)? onAiToggle;
  final VoidCallback? onAddNote;

  const ManageConversationSheet({
    super.key,
    required this.contact,
    this.onAssign,
    this.onStatusChange,
    this.onAiToggle,
    this.onAddNote,
  });

  static Future<void> show(
    BuildContext context, {
    required Contact contact,
    Function(String assignee)? onAssign,
    Function(String status)? onStatusChange,
    Function(bool aiEnabled)? onAiToggle,
    VoidCallback? onAddNote,
  }) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => ManageConversationSheet(
        contact: contact,
        onAssign: onAssign,
        onStatusChange: onStatusChange,
        onAiToggle: onAiToggle,
        onAddNote: onAddNote,
      ),
    );
  }

  @override
  State<ManageConversationSheet> createState() =>
      _ManageConversationSheetState();
}

class _ManageConversationSheetState extends State<ManageConversationSheet> {
  late bool _aiEnabled;
  late String _currentStatus;
  late String _assignedTo;

  @override
  void initState() {
    super.initState();
    _aiEnabled = widget.contact.isAiBotActive ?? true;
    _currentStatus = widget.contact.status ?? 'Open';
    _assignedTo = widget.contact.assignedUserName ?? 'Unassigned';
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;
    final bgColor = isDark ? const Color(0xFF172136) : Colors.white;
    final textColor = isDark ? Colors.white : const Color(0xFF0F172A);
    final mutedTextColor =
        isDark ? const Color(0xFF94A3B8) : const Color(0xFF64748B);
    final borderColor =
        isDark ? Colors.white.withValues(alpha: 0.08) : const Color(0xFFE2E8F0);

    return Container(
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        top: 12,
        left: 20,
        right: 20,
        bottom: MediaQuery.of(context).padding.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Drag handle
          Center(
            child: Container(
              width: 36,
              height: 4,
              decoration: BoxDecoration(
                color: isDark ? Colors.white24 : Colors.black12,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Header
          Text(
            'Manage conversation',
            style: TextStyle(
              fontFamily: 'Inter',
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: textColor,
            ),
          ),
          const SizedBox(height: 20),

          // Action items
          _buildActionRow(
            icon: Icons.person_outline_rounded,
            title: 'Assign to',
            trailingText: '$_assignedTo ›',
            onTap: () {
              Navigator.pop(context);
              if (widget.onAssign != null) {
                widget.onAssign!('Me');
              }
            },
            isDark: isDark,
            borderColor: borderColor,
            textColor: textColor,
            mutedColor: mutedTextColor,
          ),

          _buildActionRow(
            icon: Icons.fact_check_outlined,
            title: 'Status',
            trailingText: '$_currentStatus ›',
            onTap: () {
              Navigator.pop(context);
              if (widget.onStatusChange != null) {
                widget.onStatusChange!('Resolved');
              }
            },
            isDark: isDark,
            borderColor: borderColor,
            textColor: textColor,
            mutedColor: mutedTextColor,
          ),

          _buildActionRow(
            icon: Icons.label_outline_rounded,
            title: 'Labels',
            trailingWidget: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildBadge('VIP', const Color(0xFF10B981)),
                const SizedBox(width: 6),
                _buildBadge('Sales', const Color(0xFFF59E0B)),
              ],
            ),
            onTap: () {
              Navigator.pop(context);
            },
            isDark: isDark,
            borderColor: borderColor,
            textColor: textColor,
            mutedColor: mutedTextColor,
          ),

          _buildActionRow(
            icon: Icons.notes_rounded,
            title: 'Internal note',
            trailingText: 'Add ›',
            onTap: () {
              Navigator.pop(context);
              if (widget.onAddNote != null) widget.onAddNote!();
            },
            isDark: isDark,
            borderColor: borderColor,
            textColor: textColor,
            mutedColor: mutedTextColor,
          ),

          _buildActionRow(
            icon: Icons.smart_toy_outlined,
            title: 'AI ↔ human hand-off',
            trailingText: _aiEnabled ? 'AI on ›' : 'Human ›',
            onTap: () {
              setState(() {
                _aiEnabled = !_aiEnabled;
              });
              if (widget.onAiToggle != null) widget.onAiToggle!(_aiEnabled);
            },
            isDark: isDark,
            borderColor: borderColor,
            textColor: textColor,
            mutedColor: mutedTextColor,
            showBorder: false,
          ),
        ],
      ),
    );
  }

  Widget _buildActionRow({
    required IconData icon,
    required String title,
    String? trailingText,
    Widget? trailingWidget,
    required VoidCallback onTap,
    required bool isDark,
    required Color borderColor,
    required Color textColor,
    required Color mutedColor,
    bool showBorder = true,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 4),
        decoration: BoxDecoration(
          border: showBorder
              ? Border(bottom: BorderSide(color: borderColor, width: 0.8))
              : null,
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color:
                    isDark ? const Color(0xFF1E293B) : const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                icon,
                size: 20,
                color: ThemeService.primaryColor,
              ),
            ),
            const SizedBox(width: 14),
            Text(
              title,
              style: TextStyle(
                fontFamily: 'Inter',
                fontSize: 15,
                fontWeight: FontWeight.w600,
                color: textColor,
              ),
            ),
            const Spacer(),
            if (trailingWidget != null)
              trailingWidget
            else if (trailingText != null)
              Text(
                trailingText,
                style: TextStyle(
                  fontFamily: 'Inter',
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  color: mutedColor,
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildBadge(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.4), width: 1),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontFamily: 'Inter',
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

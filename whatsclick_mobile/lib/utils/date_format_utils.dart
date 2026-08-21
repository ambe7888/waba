import 'package:intl/intl.dart';

/// The backend returns raw ISO-8601 timestamps (e.g.
/// "2026-08-21T10:30:00.000000Z") — displaying that directly reads to users
/// as garbled digits ending in "000000Z". Always go through this instead of
/// interpolating a date field straight into a Text widget.
String formatDate(dynamic raw, {String pattern = 'dd/MM/yyyy à HH:mm'}) {
  if (raw == null) return '';
  final text = raw.toString();
  if (text.isEmpty) return '';
  final parsed = DateTime.tryParse(text);
  if (parsed == null) return text;
  return DateFormat(pattern).format(parsed.toLocal());
}

/// Same as [formatDate] but date-only (no time) — for contexts where the
/// time of day isn't relevant.
String formatDateOnly(dynamic raw) => formatDate(raw, pattern: 'dd/MM/yyyy');

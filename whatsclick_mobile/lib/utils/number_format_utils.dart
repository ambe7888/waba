/// Formats numbers with space as thousands separator for French locale (e.g. 1 234,
/// 25 000, 3 400 000) so exact totals are always visible without K/M compacting.
String formatCompactNumber(num value) {
  final isNegative = value < 0;
  final absValue = value.round().abs();
  final str = absValue.toString();
  final buffer = StringBuffer();
  for (int i = 0; i < str.length; i++) {
    if (i > 0 && (str.length - i) % 3 == 0) {
      buffer.write(' ');
    }
    buffer.write(str[i]);
  }
  final result = buffer.toString();
  return isNegative ? '-$result' : result;
}


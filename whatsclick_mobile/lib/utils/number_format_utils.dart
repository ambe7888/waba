/// Compact display for large numbers on stat cards (1 234 -> "1,2K",
/// 25 000 -> "25K", 3 400 000 -> "3,4M") so growing totals don't overflow or
/// get truncated on small card widths. Uses a comma for the decimal mark to
/// match the app's French locale. Values under 1000 are shown as-is.
String formatCompactNumber(num value) {
  final isNegative = value < 0;
  final absValue = value.abs();

  String result;
  if (absValue < 1000) {
    result = value.round().abs().toString();
  } else if (absValue < 1000000) {
    result = '${_trimDecimal(absValue / 1000)}K';
  } else if (absValue < 1000000000) {
    result = '${_trimDecimal(absValue / 1000000)}M';
  } else {
    result = '${_trimDecimal(absValue / 1000000000)}B';
  }

  return isNegative ? '-$result' : result;
}

String _trimDecimal(double value) {
  final rounded = (value * 10).round() / 10;
  if (rounded == rounded.roundToDouble()) {
    return rounded.round().toString();
  }
  return rounded.toStringAsFixed(1).replaceAll('.', ',');
}

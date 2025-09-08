import 'package:flutter/material.dart';

// Paket ismine göre badge döndürür: VIP için taç ve degrade, diğerleri renkli chip
Widget buildPackageBadge(String? name) {
  final n = (name ?? '').toLowerCase().trim();
  if (n.contains('vip')) {
    // VIP için özel rozet
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF8F5EFF), Color(0xFFE040FB)],
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: const [
          Text(
            'VIP',
            style: TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
              letterSpacing: 1,
              fontSize: 15,
            ),
          ),
          SizedBox(width: 6),
          Text('👑', style: TextStyle(fontSize: 19)), // Taç emojisi
        ],
      ),
    );
  } else {
    // Diğer paketler için renkli chip
    return Chip(
      label: Text(
        (name ?? 'STANDART').toUpperCase(),
        style: const TextStyle(color: Colors.white),
      ),
      backgroundColor: getPackageColor(name),
    );
  }
}

// Paket ismine göre renk döndürür
Color getPackageColor(String? name) {
  final n = (name ?? '').toLowerCase().trim();
  if (n.contains('altın') || n.contains('gold')) return Color(0xFFFBC02D);
  if (n.contains('gümüş') || n.contains('silver')) return Color(0xFFB0BEC5);
  if (n.contains('bronz') || n.contains('bronze')) return Color(0xFF8D6E63);
  return Colors.deepPurple; // Standart/default
}

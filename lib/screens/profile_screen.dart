import 'package:flutter/material.dart';

class ProfileScreen extends StatelessWidget {
  final Map<String, dynamic> user;
  final Map<String, dynamic> package;

  ProfileScreen({required this.user, required this.package});

  String getRemainingDays() {
    if (user['subscription_end'] == null) return "Belirtilmemiş";
    final endDate = DateTime.parse(user['subscription_end']);
    final now = DateTime.now();
    final diff = endDate.difference(now).inDays;
    if (diff < 0) return "Abonelik bitmiş";
    return "$diff gün kaldı";
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Profilim')),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: CircleAvatar(
                radius: 40,
                child: Icon(Icons.person, size: 48),
              ),
            ),
            SizedBox(height: 16),
            Text("Ad Soyad: ${user['name']}"),
            Text("E-posta: ${user['email']}"),
            Text("Telefon: ${user['phone']}"),
            SizedBox(height: 16),
            Divider(),
            Text("Abonelik Paketi: ${package['name']}"),
            Text("Abonelik Başlangıç: ${user['subscription_start'] ?? '-'}"),
            Text("Abonelik Bitiş: ${user['subscription_end'] ?? '-'}"),
            Text("Kalan Süre: ${getRemainingDays()}"),
            SizedBox(height: 24),
            user['payment_status'] == 1
                ? Text(
                  "Aboneliğiniz aktif",
                  style: TextStyle(color: Colors.green),
                )
                : Text(
                  "Aboneliğiniz pasif",
                  style: TextStyle(color: Colors.red),
                ),
            Spacer(),
            Center(
              child: ElevatedButton(
                onPressed: () {
                  // Yenileme veya ödeme ekranına yönlendirilebilir
                },
                child: Text("Aboneliği Yenile"),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

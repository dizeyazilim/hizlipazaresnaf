import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

import 'screens/home_screen.dart';
import 'screens/splash_screen.dart';
import 'screens/favorites_screen.dart';
import 'screens/payment_return_screen.dart'; // yeni ekranı ekledik

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    // Web'den gelen URL parametresi kontrolü
    final uri = Uri.base;
    final status =
        uri.queryParameters['status']; // status=basarili|basarisiz olabilir

    Widget initialScreen;

    if (status == 'basarili' || status == 'basarisiz') {
      initialScreen = PaymentReturnScreen(
        status: status,
      ); // ödeme sonucu ekranı
    } else {
      initialScreen = const SplashScreen(); // normal uygulama açılışı
    }

    return MaterialApp(
      title: 'Hızlı Pazar Esnaf',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.deepPurple,
        scaffoldBackgroundColor: Colors.white,
        visualDensity: VisualDensity.adaptivePlatformDensity,
      ),
      home: initialScreen,
      routes: {
        '/login': (context) => const SplashScreen(),
        '/favorites': (context) => const FavoritesScreen(userId: 0),
        '/home':
            (context) => FutureBuilder<Map<String, dynamic>?>(
              future: getUser(),
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Scaffold(
                    body: Center(child: CircularProgressIndicator()),
                  );
                }

                if (snapshot.hasData && snapshot.data != null) {
                  return HomeScreen(user: snapshot.data!);
                } else {
                  // Token yoksa login ekranına yönlendir
                  return const SplashScreen();
                }
              },
            ),
      },
    );
  }

  static Future<Map<String, dynamic>?> getUser() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userJson = prefs.getString("user");
      if (userJson != null) {
        final decoded = json.decode(userJson);
        if (decoded is Map<String, dynamic>) return decoded;
      }
    } catch (e) {
      debugPrint("getUser Hatası: $e");
    }
    return null;
  }
}

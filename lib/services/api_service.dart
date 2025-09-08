import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';

class ApiService {
  static Future<Map<String, dynamic>> login(
    String email,
    String password,
  ) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/auth/login.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({"email": email, "password": password}),
    );

    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String phone,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/auth/register.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        "name": name,
        "email": email,
        "phone": phone,
        "password": password,
      }),
    );

    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> verifyPayment(String email) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/user/verify_payment.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({"email": email}),
    );

    return jsonDecode(response.body);
  }

  static Future<List<dynamic>> listPackages() async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/packages/list_packages.php'),
    );
    final result = jsonDecode(response.body);
    return result['packages'];
  }

  static Future<List<dynamic>> listPosts() async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/content/list_posts.php'),
      
    );
    final result = jsonDecode(response.body);
    return result['posts'];
    
  }

  // In api_service.dart
  static Future<Map<String, dynamic>> uploadPost({
    required String title,
    required String description,
    required String phoneNumber,
    required String visibleFrom,
    required String visibleUntil,
    required int createdBy,
    required List<File> images,
  }) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConfig.baseUrl}/content/create_post.php'),
      );

      request.fields['title'] = title;
      request.fields['description'] = description;
      request.fields['phone_number'] = phoneNumber;
      request.fields['visible_from'] = visibleFrom;
      request.fields['visible_until'] = visibleUntil;
      request.fields['created_by'] = createdBy.toString();

      for (var image in images) {
        request.files.add(
          await http.MultipartFile.fromPath('images[]', image.path),
        );
      }

      var streamed = await request.send();
      final response = await streamed.stream.bytesToString();
      if (streamed.statusCode != 200) {
        return {
          'success': false,
          'message': 'Sunucu hatası: ${streamed.statusCode} - $response',
        };
      }
      return jsonDecode(response);
    } catch (e) {
      return {'success': false, 'message': 'Hata: $e'};
    }
  }

  static Future<void> toggleLike(int postId, int userId) async {
    await http.post(
      Uri.parse('${ApiConfig.baseUrl}/content/toggle_like.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({"post_id": postId, "user_id": userId}),
    );
  }

  static Future<int> fetchLikeCount(int postId) async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/content/get_likes.php?post_id=$postId'),
    );
    final data = jsonDecode(response.body);
    return data['likes'] ?? 0;
  }

  /// 🔧 BEĞENME / FAVORİ toggle
  static Future<void> toggleFavoriteOnline(int postId, int userId) async {
    final response = await http.post(
      Uri.parse("${ApiConfig.baseUrl}/content/like_post.php"),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({"user_id": userId, "post_id": postId}),
    );

    final data = json.decode(response.body);
    if (data['success']) {
      print("Favori güncellendi.");
    } else {
      print("Favori güncellenemedi: ${data['message']}");
    }
  }

  /// 🔄 FAVORİLERİ ÇEK
  static Future<List<dynamic>> fetchFavorites(int userId) async {
    final response = await http.get(
      Uri.parse(
        '${ApiConfig.baseUrl}/content/get_favorites.php?user_id=$userId',
      ),
    );

    final data = jsonDecode(response.body);
    if (data["success"] && data["posts"] != null) {
      return data["posts"];
    } else {
      return [];
    }
  }

  static Future<Map<String, dynamic>?> fetchSubscriptionInfo(int userId) async {
    final response = await http.get(
      Uri.parse(
        '${ApiConfig.baseUrl}/user/get_subscription_info.php?user_id=$userId',
      ),
    );
    final data = json.decode(response.body);
    if (data["success"]) return data["user"];
    return null;
  }

  // Fetch subscribed users
  Future<Map<String, dynamic>> fetchSubscribedUsers() async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/admin/subscribed_users.php'),
      );
      if (response.statusCode != 200) {
        return {
          'success': false,
          'message': 'Sunucu hatası: ${response.statusCode}',
        };
      }
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return {
          'success': true,
          'users':
              data['users'].map((u) {
                return {
                  ...u,
                  'id': int.tryParse(u['id'].toString()) ?? 0,
                  'package_id': int.tryParse(u['package_id'].toString()) ?? 0,
                  'payment_status':
                      int.tryParse(u['payment_status'].toString()) ?? 0,
                  'days_left': int.tryParse(u['days_left'].toString()) ?? 0,
                };
              }).toList() ??
              [],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Kullanıcılar alınamadı.',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Hata: $e'};
    }
  }

  // Fetch packages
  Future<Map<String, dynamic>> fetchPackages() async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/packages/list.php'),
      );
      if (response.statusCode != 200) {
        return {
          'success': false,
          'message': 'Sunucu hatası: ${response.statusCode}',
        };
      }
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return {
          'success': true,
          'packages':
              data['packages'].map((pkg) {
                return {
                  ...pkg,
                  'id': int.tryParse(pkg['id'].toString()) ?? 0,
                  'duration_days':
                      int.tryParse(pkg['duration_days'].toString()) ?? 0,
                };
              }).toList() ??
              [],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Paketler alınamadı.',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Hata: $e'};
    }
  }

  // In api_service.dart, update fetchPackages
  Future<Map<String, dynamic>> fetchPackages2() async {
    try {
      final response = await http.get(
        Uri.parse(
          '${ApiConfig.baseUrl}/admin/list_packages.php',
        ), // Use your correct endpoint
      );
      if (response.statusCode != 200) {
        return {
          'success': false,
          'message': 'Sunucu hatası: ${response.statusCode} - ${response.body}',
        };
      }
      if (!response.body.trim().startsWith('{')) {
        return {
          'success': false,
          'message': 'Geçersiz yanıt: ${response.body}',
        };
      }
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return {
          'success': true,
          'packages':
              data['packages'].map((pkg) {
                return {
                  ...pkg,
                  'id': int.tryParse(pkg['id'].toString()) ?? 0,
                  'duration_days':
                      int.tryParse(pkg['duration_days'].toString()) ?? 0,
                };
              }).toList() ??
              [],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Paketler alınamadı.',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Hata: $e'};
    }
  }

  // Edit subscription
  Future<Map<String, dynamic>> editSubscription(
    int userId,
    Map<String, dynamic> updates,
  ) async {
    updates['user_id'] = userId;
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/admin/edit_subscription.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(updates),
      );
      if (response.statusCode != 200) {
        return {
          'success': false,
          'message': 'Sunucu hatası: ${response.statusCode}',
        };
      }
      final data = json.decode(response.body);
      return {
        'success': data['success'] == true,
        'message': data['message'] ?? 'Güncelleme başarısız.',
      };
    } catch (e) {
      return {'success': false, 'message': 'Hata: $e'};
    }
  }

  // Fetch user profile
  Future<Map<String, dynamic>> fetchUserProfile(int userId) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/user/profile.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({"user_id": userId}),
      );
      if (response.statusCode != 200) {
        return {
          'success': false,
          'message': 'Sunucu hatası: ${response.statusCode}',
        };
      }
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return {
          'success': true,
          'user': data['user'] ?? {},
          'remaining_days':
              int.tryParse(data['remaining_days'].toString()) ?? 0,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Profil bilgileri alınamadı.',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Hata: $e'};
    }
  }

  // api_service.dart
  Future<Map<String, dynamic>> editPackage(Map<String, dynamic> updates) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/admin/edit_package.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(updates),
      );
      final data = json.decode(response.body);
      return {
        'success': data['success'],
        'message': data['message'] ?? 'İşlem başarısız',
      };
    } catch (e) {
      return {'success': false, 'message': 'Hata: $e'};
    }
  }
}

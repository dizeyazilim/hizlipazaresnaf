import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:hizlipazaresnaf/screens/admin_dashboard_screen.dart';
import 'package:hizlipazaresnaf/screens/admin_subscribed_users_screen.dart';
import 'package:hizlipazaresnaf/screens/pack_utils.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../config/api_config.dart';
import 'content_add_screen.dart';
import 'favorites_screen.dart';

class HomeScreen extends StatefulWidget {
  final Map user;
  const HomeScreen({required this.user});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List posts = [];
  List<int> favorites = [];
  Timer? _timer;

  // Profil verisi
  Map<String, dynamic>? profileData;
  bool loadingProfile = false;

  // Expanded descriptions state
  Map<int, bool> isExpanded = {};

  @override
  void initState() {
    super.initState();
    fetchPosts();
    fetchFavorites();
    _timer = Timer.periodic(const Duration(seconds: 15), (_) => fetchPosts());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> fetchPosts() async {
    try {
      final response = await http.get(
        Uri.parse("${ApiConfig.baseUrl}/content/list_posts.php"),
      );
      final data = json.decode(response.body);
      if (data["success"]) {
        setState(() => posts = data["posts"]);
      }
    } catch (e) {
      print("fetchPosts hatası: $e");
    }
  }

  Future<void> fetchFavorites() async {
    try {
      final userId = int.parse(widget.user['id'].toString());
      final response = await http.get(
        Uri.parse(
          "${ApiConfig.baseUrl}/content/get_favorites.php?user_id=$userId",
        ),
      );
      final data = json.decode(response.body);
      if (data["success"]) {
        final ids =
            data["posts"]
                .map<int>((item) => int.tryParse(item["id"].toString()) ?? 0)
                .toList();
        setState(() => favorites = ids);
      }
    } catch (e) {
      print("Favori verisi alınamadı: $e");
    }
  }

  Future<void> toggleFavorite(int postId) async {
    final userId = int.parse(widget.user['id'].toString());
    try {
      final response = await http.post(
        Uri.parse("${ApiConfig.baseUrl}/content/toggle_favorite.php"),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({"user_id": userId, "post_id": postId}),
      );
      final result = json.decode(response.body);
      if (result['success']) {
        setState(() {
          if (favorites.contains(postId)) {
            favorites.remove(postId);
          } else {
            favorites.add(postId);
          }
        });
      } else {
        print("Beğenme başarısız: ${result['message']}");
      }
    } catch (e) {
      print("toggleFavorite hatası: $e");
    }
  }

  Future<void> confirmAndDeletePost(int postId) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder:
          (_) => AlertDialog(
            title: const Text("Silme Onayı"),
            content: const Text("Bu içeriği silmek istediğinize emin misiniz?"),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text("İptal"),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text("Sil", style: TextStyle(color: Colors.red)),
              ),
            ],
          ),
    );
    if (confirm == true) {
      await deletePost(postId);
    }
  }

  Future<void> deletePost(int postId) async {
    try {
      final response = await http.post(
        Uri.parse("${ApiConfig.baseUrl}/content/delete_post.php"),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({"post_id": postId}),
      );
      final result = json.decode(response.body);
      if (result["success"]) fetchPosts();
    } catch (e) {
      print("deletePost hatası: $e");
    }
  }

  Color getPackageColor(String? name) {
    if (name == null) return Colors.grey;
    final n = name.toLowerCase();
    if (n.contains('vip')) return Colors.deepPurple;
    if (n.contains('gold') || n.contains('altın'))
      return const Color(0xFFFFD700);
    if (n.contains('silver') || n.contains('gümüş'))
      return const Color(0xFFC0C0C0);
    if (n.contains('bronze') || n.contains('bronz'))
      return const Color(0xFFCD7F32);
    return Colors.blueGrey;
  }

  Future<void> _launchPhoneCall(String phoneNumber) async {
    // Clean phone number: remove spaces, dashes, and other characters
    String cleanedNumber = phoneNumber.replaceAll(RegExp(r'[^0-9+]'), '');
    // Add country code if missing (assuming Turkey for this example)
    if (!cleanedNumber.startsWith('+') && cleanedNumber.length == 10) {
      cleanedNumber = '+90$cleanedNumber';
    }
    if (cleanedNumber.isEmpty || cleanedNumber.length < 10) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Geçersiz telefon numarası')),
      );
      return;
    }

    final Uri phoneUri = Uri(scheme: 'tel', path: cleanedNumber);
    try {
      await launchUrl(phoneUri, mode: LaunchMode.externalApplication);
    } catch (e) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Arama başlatılamadı: $e')));
    }
  }

  void _showPostDetails(Map post) {
    final postId = int.tryParse(post["id"].toString()) ?? 0;
    final description = post["description"] ?? "";
    final imageUrls = List<String>.from(post["image_urls"] ?? []);

    showDialog(
      context: context,
      builder:
          (context) => Dialog.fullscreen(
            child: GestureDetector(
              onTap: () => Navigator.pop(context),
              child: InteractiveViewer(
                minScale: 0.5,
                maxScale: 4.0,
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (imageUrls.isNotEmpty)
                        SizedBox(
                          height: 300, // Increased height for better visibility
                          child: ListView.builder(
                            scrollDirection: Axis.horizontal,
                            itemCount: imageUrls.length,
                            itemBuilder: (context, imgIndex) {
                              return Padding(
                                padding: const EdgeInsets.only(right: 8.0),
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(12),
                                  child: Image.network(
                                    imageUrls[imgIndex],
                                    width: 300,
                                    height: 300,
                                    fit: BoxFit.cover,
                                    loadingBuilder: (
                                      context,
                                      child,
                                      loadingProgress,
                                    ) {
                                      if (loadingProgress == null) return child;
                                      return Container(
                                        width: 300,
                                        height: 300,
                                        color: Colors.grey[200],
                                        child: const Center(
                                          child: CircularProgressIndicator(),
                                        ),
                                      );
                                    },
                                    errorBuilder:
                                        (context, error, stackTrace) =>
                                            Container(
                                              width: 300,
                                              height: 300,
                                              color: Colors.grey[200],
                                              child: const Icon(
                                                Icons.error,
                                                color: Colors.red,
                                              ),
                                            ),
                                  ),
                                ),
                              );
                            },
                          ),
                        )
                      else
                        Container(
                          width: double.infinity,
                          height: 300,
                          color: Colors.grey[200],
                          child: const Center(
                            child: Text(
                              "Resim yok",
                              style: TextStyle(fontSize: 16),
                            ),
                          ),
                        ),
                      Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              post["title"] ?? "",
                              style: const TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              description,
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey[800],
                              ),
                            ),
                            const SizedBox(height: 8),
                            if (post["phone_number"] != null &&
                                post["phone_number"].toString().isNotEmpty)
                              GestureDetector(
                                onTap:
                                    () =>
                                        _launchPhoneCall(post["phone_number"]),
                                child: Text(
                                  "📞 ${post["phone_number"]}",
                                  style: const TextStyle(
                                    fontSize: 16,
                                    color: Color(0xFF25D366),
                                  ),
                                ),
                              ),
                            const SizedBox(height: 16),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.end,
                              children: [
                                IconButton(
                                  icon: Icon(
                                    favorites.contains(postId)
                                        ? Icons.favorite
                                        : Icons.favorite_border,
                                    color: const Color(0xFF25D366),
                                    size: 32,
                                  ),
                                  onPressed: () {
                                    toggleFavorite(postId);
                                    Navigator.pop(
                                      context,
                                    ); // Close modal after action
                                  },
                                ),
                                if (widget.user['role'] == 'admin' ||
                                    widget.user['role'] == 'editor')
                                  IconButton(
                                    icon: const Icon(
                                      Icons.delete_outline,
                                      color: Colors.redAccent,
                                      size: 32,
                                    ),
                                    onPressed: () {
                                      Navigator.pop(
                                        context,
                                      ); // Close modal first
                                      confirmAndDeletePost(postId);
                                    },
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
    );
  }

  Future<void> showProfileModal(BuildContext context) async {
    setState(() => loadingProfile = true);
    final response = await http.post(
      Uri.parse("${ApiConfig.baseUrl}/user/profile.php"),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({"user_id": widget.user["id"]}),
    );
    final data = json.decode(response.body);

    setState(() => loadingProfile = false);

    if (!(data["success"] ?? false)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profil bilgileri alınamadı')),
      );
      return;
    }
    final user = data["user"] ?? {};
    final remainingDays = data["remaining_days"] ?? 0;
    final paymentStatus = int.tryParse(user["payment_status"].toString()) ?? 0;

    final baslangic =
        (user["subscription_start"] ?? "").toString().isNotEmpty
            ? user["subscription_start"].toString().substring(0, 10)
            : "Bilinmiyor";
    final bitis =
        (user["subscription_end"] ?? "").toString().isNotEmpty
            ? user["subscription_end"].toString().substring(0, 10)
            : "Bilinmiyor";
    final abonelikDurum =
        (remainingDays > 0 && paymentStatus == 1) ? "Aktif" : "Pasif";

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      builder:
          (_) => Padding(
            padding: const EdgeInsets.fromLTRB(24, 24, 24, 32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: const Icon(
                    Icons.account_circle,
                    size: 72,
                    color: Color(0xFF25D366), // WhatsApp green
                  ),
                ),
                const SizedBox(height: 8),
                Center(
                  child: Text(
                    user['name'] ?? '',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 22,
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Center(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      buildPackageBadge(user['package_name']),
                      const SizedBox(width: 12),
                      Chip(
                        label: Text(
                          abonelikDurum,
                          style: const TextStyle(color: Colors.white),
                        ),
                        backgroundColor:
                            abonelikDurum == "Aktif"
                                ? const Color(0xFF25D366)
                                : Colors.redAccent,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                _buildProfileRow(Icons.mail_outline, user['email'] ?? ""),
                const SizedBox(height: 12),
                user['phone'] != null && user['phone'].toString().isNotEmpty
                    ? _buildProfileRow(
                      Icons.phone_outlined,
                      user['phone'],
                      isPhone: true,
                    )
                    : _buildProfileRow(Icons.phone_outlined, "Telefon yok"),
                const SizedBox(height: 12),
                _buildProfileRow(
                  Icons.calendar_today_outlined,
                  "Başlangıç: $baslangic",
                ),
                const SizedBox(height: 12),
                _buildProfileRow(Icons.event_busy_outlined, "Bitiş: $bitis"),
                if (abonelikDurum == "Aktif") ...[
                  const SizedBox(height: 12),
                  _buildProfileRow(
                    Icons.timer_outlined,
                    "Kalan gün: $remainingDays",
                  ),
                ],
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.logout),
                    label: const Text("Çıkış Yap"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.redAccent,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                    onPressed: () async {
                      final prefs = await SharedPreferences.getInstance();
                      await prefs.remove("user");
                      if (Navigator.canPop(context)) Navigator.pop(context);
                      Navigator.pushReplacementNamed(context, '/login');
                    },
                  ),
                ),
              ],
            ),
          ),
    );
  }

  Widget _buildProfileRow(IconData icon, String text, {bool isPhone = false}) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Colors.grey[700]),
        const SizedBox(width: 12),
        Expanded(
          child:
              isPhone
                  ? GestureDetector(
                    onTap: () => _launchPhoneCall(text),
                    child: Text(
                      "📞 $text",
                      style: const TextStyle(
                        fontSize: 16,
                        color: Color(0xFF25D366),
                        decoration: TextDecoration.underline,
                      ),
                    ),
                  )
                  : Text(
                    text,
                    style: TextStyle(fontSize: 16, color: Colors.grey[800]),
                  ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("İçerikler"),
        backgroundColor: Colors.white,
        elevation: 0,
        titleTextStyle: const TextStyle(
          color: Colors.black,
          fontSize: 20,
          fontWeight: FontWeight.bold,
        ),
        actions: [
          if (widget.user['role'] == 'admin') // Admin için buton
            IconButton(
              icon: const Icon(Icons.supervisor_account, color: Colors.black),
              onPressed:
                  () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder:
                          (_) => AdminSubscribedUsersScreen(user: widget.user),
                    ),
                  ),
            ),
          IconButton(
            icon: const Icon(Icons.favorite_border, color: Colors.black),
            onPressed:
                () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder:
                        (_) => FavoritesScreen(
                          userId: int.parse(widget.user['id'].toString()),
                        ),
                  ),
                ),
          ),
          IconButton(
            icon: const Icon(Icons.dashboard),
            onPressed:
                () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => AdminDashboardScreen(user: widget.user),
                  ),
                ),
          ),
          IconButton(
            icon: const Icon(
              Icons.account_circle_outlined,
              size: 28,
              color: Colors.black,
            ),
            onPressed: () => showProfileModal(context),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: fetchPosts,
        color: const Color(0xFF25D366),
        child:
            posts.isEmpty
                ? const Center(
                  child: Text(
                    "Henüz içerik yok.",
                    style: TextStyle(fontSize: 16),
                  ),
                )
                : ListView.separated(
                  padding: const EdgeInsets.only(bottom: 80, top: 10),
                  itemCount: posts.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 12),
                  itemBuilder: (_, i) {
                    final post =
                        posts[i]; // Use 'posts' instead of 'filteredFavorites'
                    final postId = int.tryParse(post["id"].toString()) ?? 0;
                    final description = post["description"] ?? "";
                    final expanded = isExpanded[postId] ?? false;
                    final needsExpand = description.length > 150;
                    final imageUrls = List<String>.from(
                      post["image_urls"] ?? [],
                    );

                    return GestureDetector(
                      onTap: () => _showPostDetails(post),
                      child: Card(
                        elevation: 2,
                        margin: const EdgeInsets.symmetric(horizontal: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (imageUrls.isNotEmpty)
                              SizedBox(
                                height: 220,
                                child: ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  itemCount: imageUrls.length,
                                  itemBuilder: (context, imgIndex) {
                                    return Padding(
                                      padding: const EdgeInsets.only(
                                        right: 8.0,
                                      ),
                                      child: ClipRRect(
                                        borderRadius:
                                            const BorderRadius.vertical(
                                              top: Radius.circular(16),
                                            ),
                                        child: Image.network(
                                          imageUrls[imgIndex],
                                          width: 220,
                                          height: 220,
                                          fit: BoxFit.cover,
                                          loadingBuilder: (
                                            context,
                                            child,
                                            loadingProgress,
                                          ) {
                                            if (loadingProgress == null)
                                              return child;
                                            return Container(
                                              width: 220,
                                              height: 220,
                                              color: Colors.grey[200],
                                              child: const Center(
                                                child:
                                                    CircularProgressIndicator(),
                                              ),
                                            );
                                          },
                                          errorBuilder:
                                              (context, error, stackTrace) =>
                                                  Container(
                                                    width: 220,
                                                    height: 220,
                                                    color: Colors.grey[200],
                                                    child: const Icon(
                                                      Icons.error,
                                                      color: Colors.red,
                                                    ),
                                                  ),
                                        ),
                                      ),
                                    );
                                  },
                                ),
                              ),
                            Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    post["title"] ?? "",
                                    style: const TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  if (description.isNotEmpty)
                                    Padding(
                                      padding: const EdgeInsets.only(top: 8.0),
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            description,
                                            maxLines: expanded ? null : 3,
                                            overflow:
                                                expanded
                                                    ? TextOverflow.visible
                                                    : TextOverflow.ellipsis,
                                            style: TextStyle(
                                              fontSize: 14,
                                              color: Colors.grey[600],
                                            ),
                                          ),
                                          if (needsExpand)
                                            GestureDetector(
                                              onTap:
                                                  () => setState(
                                                    () =>
                                                        isExpanded[postId] =
                                                            !expanded,
                                                  ),
                                              child: Text(
                                                expanded
                                                    ? "Daha az göster"
                                                    : "Devamını oku",
                                                style: const TextStyle(
                                                  color: Color(0xFF25D366),
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                        ],
                                      ),
                                    ),
                                  if (post["phone_number"] != null &&
                                      post["phone_number"]
                                          .toString()
                                          .isNotEmpty)
                                    Padding(
                                      padding: const EdgeInsets.only(top: 8.0),
                                      child: GestureDetector(
                                        onTap:
                                            () => _launchPhoneCall(
                                              post["phone_number"],
                                            ),
                                        child: Text(
                                          "📞 ${post["phone_number"]}",
                                          style: const TextStyle(
                                            fontSize: 14,
                                            color: Color(0xFF25D366),
                                          ),
                                        ),
                                      ),
                                    ),
                                  const SizedBox(height: 12),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.end,
                                    children: [
                                      IconButton(
                                        icon: Icon(
                                          favorites.contains(postId)
                                              ? Icons.favorite
                                              : Icons.favorite_border,
                                          color: const Color(0xFF25D366),
                                        ),
                                        onPressed: () => toggleFavorite(postId),
                                      ),
                                      if (widget.user['role'] == 'admin' ||
                                          widget.user['role'] == 'editor')
                                        IconButton(
                                          icon: const Icon(
                                            Icons.delete_outline,
                                            color: Colors.redAccent,
                                          ),
                                          onPressed:
                                              () =>
                                                  confirmAndDeletePost(postId),
                                        ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
      ),
      floatingActionButton:
          (widget.user['role'] == 'admin' || widget.user['role'] == 'editor')
              ? FloatingActionButton(
                backgroundColor: const Color(0xFF25D366),
                child: const Icon(Icons.add, color: Colors.white),
                onPressed:
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => ContentAddScreen(user: widget.user),
                      ),
                    ).then((_) => fetchPosts()),
              )
              : null,
    );
  }
}

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'admin_subscribed_users_screen.dart';
import 'admin_packages_screen.dart' as admin_packages;
import 'content_add_screen.dart';
import 'splash_screen.dart';
import 'posts_screen.dart'; // New posts screen

class AdminDashboardScreen extends StatefulWidget {
  final Map user;

  const AdminDashboardScreen({required this.user, super.key});

  @override
  _AdminDashboardScreenState createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> {
  int _notificationCount = 0;
  int _postCount = 0;
  int _selectedIndex = 0;
  bool _isLoading = true;
  List<dynamic> _posts = [];
  List<dynamic> _notifications = [];

  @override
  void initState() {
    super.initState();
    if (widget.user['role'] != 'admin') {
      return;
    }
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() => _isLoading = true);
    await Future.wait([_fetchPosts(), _fetchNotifications()]);
    setState(() => _isLoading = false);
  }

  Future<void> _fetchPosts() async {
    try {
      final response = await http.get(
        Uri.parse(
          'https://hizlipazaresnaf.com//hizlipazarvip/api/content/list_posts.php',
        ),
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          setState(() {
            _posts = data['posts'];
            _postCount = _posts.length;
          });
          print('Posts: $_posts');
        }
      }
    } catch (e) {
      print('Post fetch error: $e');
    }
  }

  Future<void> _fetchNotifications() async {
    try {
      final response = await http.get(
        Uri.parse(
          'https://hizlipazaresnaf.com//hizlipazarvip/api/content/get_notifications.php?user_id=${widget.user['id']}',
        ),
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          setState(() {
            _notifications = data['notifications'];
            _notificationCount = _notifications.length;
          });
          print('Notifications: $_notifications');
        }
      }
    } catch (e) {
      print('Notification fetch error: $e');
    }
  }

  Future<void> _logout() async {
    try {
      // Clear local session data
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      print('Local session cleared');

      // Redirect to SplashScreen
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const SplashScreen()),
        (route) => false,
      );
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Çıkış yapıldı')));
    } catch (e) {
      print('Logout error: $e');
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Hata: $e')));
    }
  }

  void _onNavTap(int index) {
    setState(() => _selectedIndex = index);
    switch (index) {
      case 0:
        break; // Home (stay on dashboard)
      case 1:
        Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => PostsScreen(user: widget.user)),
        );
        break;
      case 2:
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => AdminSubscribedUsersScreen(user: widget.user),
          ),
        );
        break;
      case 3:
        Navigator.push(
          context,
          MaterialPageRoute(
            builder:
                (_) => admin_packages.AdminPackagesScreen(user: widget.user),
          ),
        );
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.user['role'] != 'admin') {
      return const Scaffold(body: Center(child: Text('Erişim izniniz yok.')));
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9), // bg-gray-100
      body: Stack(
        children: [
          RefreshIndicator(
            onRefresh: _fetchData,
            child: CustomScrollView(
              slivers: [
                // Header
                SliverAppBar(
                  backgroundColor: const Color(0xFF128C7E),
                  pinned: true,
                  elevation: 0,
                  flexibleSpace: FlexibleSpaceBar(
                    title: Row(
                      children: [
                        Image.network(
                          'https://hizlipazar.vercel.app/images/logo/logo.svg',
                          height: 24,
                          color: Colors.white,
                          errorBuilder:
                              (context, error, stackTrace) =>
                                  const Icon(Icons.error, color: Colors.white),
                        ),
                        const SizedBox(width: 8),
                        const Text(
                          'Yönetim Paneli',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                  actions: [
                    Stack(
                      children: [
                        IconButton(
                          icon: const Icon(
                            Icons.notifications,
                            color: Colors.white,
                          ),
                          onPressed: () {
                            // Navigate to notifications screen (placeholder)
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Bildirimler')),
                            );
                          },
                        ),
                        if (_notificationCount > 0)
                          Positioned(
                            right: 8,
                            top: 8,
                            child: Container(
                              padding: const EdgeInsets.all(2),
                              decoration: const BoxDecoration(
                                color: Colors.red,
                                shape: BoxShape.circle,
                              ),
                              constraints: const BoxConstraints(
                                minWidth: 16,
                                minHeight: 16,
                              ),
                              child: Text(
                                '$_notificationCount',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 10,
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ),
                          ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.logout, color: Colors.white),
                      onPressed: _logout,
                    ),
                  ],
                ),
                // Main Content
                SliverPadding(
                  padding: const EdgeInsets.all(16),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      // Stats Cards
                      if (_isLoading)
                        const Center(child: CircularProgressIndicator())
                      else
                        Row(
                          children: [
                            Expanded(
                              child: Card(
                                color: const Color(0xFF25D366),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                elevation: 2,
                                child: Padding(
                                  padding: const EdgeInsets.all(12),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'Toplam Gönderi',
                                        style: TextStyle(
                                          color: Colors.white70,
                                          fontSize: 12,
                                        ),
                                      ),
                                      Text(
                                        '$_postCount',
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 24,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                      const SizedBox(height: 8),
                                      const Row(
                                        children: [
                                          Icon(
                                            Icons.arrow_upward,
                                            color: Colors.white,
                                            size: 12,
                                          ),
                                          SizedBox(width: 4),
                                          Text(
                                            'Yeni gönderiler',
                                            style: TextStyle(
                                              color: Colors.white,
                                              fontSize: 10,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Card(
                                color: const Color(0xFF34B7F1),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                elevation: 2,
                                child: Padding(
                                  padding: const EdgeInsets.all(12),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'Yeni Bildirimler',
                                        style: TextStyle(
                                          color: Colors.white70,
                                          fontSize: 12,
                                        ),
                                      ),
                                      Text(
                                        '$_notificationCount',
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 24,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                      const SizedBox(height: 8),
                                      const Row(
                                        children: [
                                          Icon(
                                            Icons.arrow_upward,
                                            color: Colors.white,
                                            size: 12,
                                          ),
                                          SizedBox(width: 4),
                                          Text(
                                            'Son 24 saat',
                                            style: TextStyle(
                                              color: Colors.white,
                                              fontSize: 10,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      const SizedBox(height: 24),
                      // Quick Actions
                      const Text(
                        'Hızlı Erişim',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF374151),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          _QuickActionButton(
                            icon: Icons.add_circle,
                            label: 'Gönderi Ekle',
                            color: const Color(0xFF25D366),
                            onTap:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder:
                                        (_) =>
                                            ContentAddScreen(user: widget.user),
                                  ),
                                ),
                          ),
                          _QuickActionButton(
                            icon: Icons.post_add,
                            label: 'Gönderiler',
                            color: const Color(0xFF128C7E),
                            onTap:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder:
                                        (_) => PostsScreen(user: widget.user),
                                  ),
                                ),
                          ),
                          _QuickActionButton(
                            icon: Icons.people,
                            label: 'Kullanıcılar',
                            color: const Color(0xFF34B7F1),
                            onTap:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder:
                                        (_) => AdminSubscribedUsersScreen(
                                          user: widget.user,
                                        ),
                                  ),
                                ),
                          ),
                          _QuickActionButton(
                            icon: Icons.list,
                            label: 'Paketler',
                            color: const Color(0xFF075E54),
                            onTap:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder:
                                        (_) =>
                                            admin_packages.AdminPackagesScreen(
                                              user: widget.user,
                                            ),
                                  ),
                                ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),
                      // Recent Posts
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Son Gönderiler',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF374151),
                            ),
                          ),
                          TextButton(
                            onPressed:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder:
                                        (_) => PostsScreen(user: widget.user),
                                  ),
                                ),
                            child: const Text(
                              'Tümünü Gör',
                              style: TextStyle(
                                fontSize: 12,
                                color: Color(0xFF128C7E),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (_posts.isEmpty && !_isLoading)
                        const Text('Gönderi bulunamadı.')
                      else
                        ..._posts
                            .take(3)
                            .map(
                              (post) => _PostCard(
                                postId: post['id'].toString(),
                                title: post['title'],
                                description: post['description'],
                                imageUrl:
                                    post['image_urls'].isNotEmpty
                                        ? post['image_urls'][0]
                                        : null,
                              ),
                            ),
                      const SizedBox(height: 24),
                      // Notifications
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Bildirimler',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF374151),
                            ),
                          ),
                          TextButton(
                            onPressed: () {
                              // Navigate to All Notifications (placeholder)
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Tüm Bildirimler'),
                                ),
                              );
                            },
                            child: const Text(
                              'Tümünü Gör',
                              style: TextStyle(
                                fontSize: 12,
                                color: Color(0xFF128C7E),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (_notifications.isEmpty && !_isLoading)
                        const Text('Bildirim bulunamadı.')
                      else
                        ..._notifications
                            .take(2)
                            .map(
                              (notification) => _NotificationCard(
                                type: notification['type'],
                                title: notification['title'],
                                userName: notification['user_name'],
                                time: notification['created_at'],
                              ),
                            ),
                    ]),
                  ),
                ),
              ],
            ),
          ),
          // Floating Action Button
          Positioned(
            bottom: 80,
            right: 16,
            child: AnimatedContainer(
              duration: const Duration(seconds: 2),
              onEnd: () {
                setState(() {}); // Trigger pulse animation
              },
              child: FloatingActionButton(
                backgroundColor: const Color(0xFF25D366),
                child: const Icon(Icons.add),
                onPressed:
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => ContentAddScreen(user: widget.user),
                      ),
                    ),
              ),
            ),
          ),
        ],
      ),
      // Bottom Navigation
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: _onNavTap,
        selectedItemColor: const Color(0xFF128C7E),
        unselectedItemColor: Colors.grey,
        backgroundColor: Colors.white,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home), label: 'Ana Sayfa'),
          BottomNavigationBarItem(
            icon: Icon(Icons.post_add),
            label: 'Gönderiler',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.people),
            label: 'Kullanıcılar',
          ),
          BottomNavigationBarItem(icon: Icon(Icons.settings), label: 'Ayarlar'),
        ],
      ),
    );
  }
}

// Quick Action Button Widget
class _QuickActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _QuickActionButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey[200]!),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.1),
              spreadRadius: 1,
              blurRadius: 3,
            ),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: color, size: 24),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(fontSize: 10, color: Color(0xFF4B5563)),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

// Post Card Widget
class _PostCard extends StatelessWidget {
  final String postId;
  final String title;
  final String description;
  final String? imageUrl;

  const _PostCard({
    required this.postId,
    required this.title,
    required this.description,
    this.imageUrl,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            if (imageUrl != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: CachedNetworkImage(
                  imageUrl: imageUrl!,
                  width: 50,
                  height: 50,
                  fit: BoxFit.cover,
                  placeholder:
                      (context, url) => const CircularProgressIndicator(),
                  errorWidget: (context, url, error) => const Icon(Icons.error),
                ),
              )
            else
              const Icon(Icons.image, size: 50),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    description,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF6B7280),
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// Notification Card Widget
class _NotificationCard extends StatelessWidget {
  final String type;
  final String title;
  final String userName;
  final String time;

  const _NotificationCard({
    required this.type,
    required this.title,
    required this.userName,
    required this.time,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            const Icon(Icons.notifications, color: Color(0xFF128C7E)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    type == 'new_post'
                        ? 'Yeni Gönderi'
                        : type == 'new_user'
                        ? 'Yeni Kullanıcı'
                        : 'Abonelik Yenileme',
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '$userName - $title',
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF6B7280),
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    DateTime.parse(time).toLocal().toString(),
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF6B7280),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

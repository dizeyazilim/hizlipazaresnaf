import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'edit_post_screen.dart';

class PostsScreen extends StatefulWidget {
  final Map user;

  const PostsScreen({required this.user, super.key});

  @override
  _PostsScreenState createState() => _PostsScreenState();
}

class _PostsScreenState extends State<PostsScreen> {
  bool _isLoading = true;
  List<dynamic> _posts = [];

  @override
  void initState() {
    super.initState();
    _fetchPosts();
  }

  Future<void> _fetchPosts() async {
    setState(() => _isLoading = true);
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
            _isLoading = false;
          });
          print('Posts: $_posts');
        }
      }
    } catch (e) {
      print('Post fetch error: $e');
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Hata: $e')));
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF128C7E),
        title: const Text('Gönderiler', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: RefreshIndicator(
        onRefresh: _fetchPosts,
        child:
            _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _posts.isEmpty
                ? const Center(child: Text('Gönderi bulunamadı.'))
                : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _posts.length,
                  itemBuilder: (context, index) {
                    final post = _posts[index];
                    return _PostCard(
                      postId: post['id'].toString(),
                      title: post['title'],
                      description: post['description'],
                      phoneNumber: post['phone_number'],
                      imageUrl:
                          post['image_urls'].isNotEmpty
                              ? post['image_urls'][0]
                              : null,
                      visibleFrom: post['visible_from'],
                      visibleUntil: post['visible_until'],
                      imageUrls: List<String>.from(post['image_urls']),
                      onEdit:
                          () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder:
                                  (_) => EditPostScreen(
                                    user: widget.user,
                                    post: post,
                                  ),
                            ),
                          ).then(
                            (_) => _fetchPosts(),
                          ), // Refresh posts after edit
                    );
                  },
                ),
      ),
    );
  }
}

class _PostCard extends StatelessWidget {
  final String postId;
  final String title;
  final String description;
  final String phoneNumber;
  final String? imageUrl;
  final String visibleFrom;
  final String visibleUntil;
  final List<String> imageUrls;
  final VoidCallback onEdit;

  const _PostCard({
    required this.postId,
    required this.title,
    required this.description,
    required this.phoneNumber,
    required this.imageUrl,
    required this.visibleFrom,
    required this.visibleUntil,
    required this.imageUrls,
    required this.onEdit,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 1,
      margin: const EdgeInsets.only(bottom: 16),
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
                  const SizedBox(height: 4),
                  Text(
                    phoneNumber,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF6B7280),
                    ),
                  ),
                ],
              ),
            ),
            IconButton(
              icon: const Icon(Icons.edit, color: Color(0xFF25D366)),
              onPressed: onEdit,
            ),
          ],
        ),
      ),
    );
  }
}

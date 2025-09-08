import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';
import '../config/api_config.dart';

class FavoritesScreen extends StatefulWidget {
  final int userId;
  const FavoritesScreen({required this.userId});

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  List<dynamic> favorites = [];
  List<dynamic> filteredFavorites = [];
  bool isLoading = true;
  TextEditingController searchCtrl = TextEditingController();
  String searchQuery = "";
  String message = ""; // Added message state variable
  Map<int, bool> isExpanded = {};

  @override
  void initState() {
    super.initState();
    fetchFavorites();
    searchCtrl.addListener(() {
      setState(() {
        searchQuery = searchCtrl.text.toLowerCase();
        filteredFavorites =
            favorites.where((post) {
              final title = (post['title'] ?? "").toLowerCase();
              final description = (post['description'] ?? "").toLowerCase();
              return title.contains(searchQuery) ||
                  description.contains(searchQuery);
            }).toList();
      });
    });
  }

  Future<void> fetchFavorites() async {
    final userId = widget.userId;

    try {
      final response = await http.get(
        Uri.parse(
          '${ApiConfig.baseUrl}/content/get_favorites.php?user_id=$userId',
        ),
      );
      print('get_favorites response: ${response.body}'); // Debug API response

      final data = json.decode(response.body);
      if (data["success"] == true && data["posts"] != null) {
        setState(() {
          favorites = data["posts"];
          filteredFavorites = favorites;
          isLoading = false;
          message = "";
          print('Favorites loaded: $favorites'); // Debug loaded posts
        });
      } else {
        setState(() {
          favorites = [];
          filteredFavorites = [];
          isLoading = false;
          message = data["message"] ?? 'Favoriler alınamadı.';
          print('No favorites: ${data["message"]}'); // Debug empty response
        });
      }
    } catch (e) {
      print("Favoriler alınırken hata: $e");
      setState(() {
        favorites = [];
        filteredFavorites = [];
        isLoading = false;
        message = 'Hata: $e';
      });
    }
  }

  Future<void> toggleFavorite(int postId) async {
    final userId = widget.userId;
    try {
      final response = await http.post(
        Uri.parse("${ApiConfig.baseUrl}/content/toggle_favorite.php"),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({"user_id": userId, "post_id": postId}),
      );
      final result = json.decode(response.body);
      if (result['success']) {
        setState(() {
          favorites.removeWhere(
            (post) => int.tryParse(post["id"].toString()) == postId,
          );
          filteredFavorites =
              favorites.where((post) {
                final title = (post['title'] ?? "").toLowerCase();
                final description = (post['description'] ?? "").toLowerCase();
                return title.contains(searchQuery) ||
                    description.contains(searchQuery);
              }).toList();
        });
      } else {
        print("Beğenme başarısız: ${result['message']}");
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Beğenme başarısız')),
        );
      }
    } catch (e) {
      print("toggleFavorite hatası: $e");
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Hata: $e')));
    }
  }

  Future<void> _launchPhoneCall(String phoneNumber) async {
    String cleanedNumber = phoneNumber.replaceAll(RegExp(r'[^0-9+]'), '');
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

  void _showPostDetails(dynamic post) {
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
                          height: 300,
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
                                    Icons.favorite,
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Beğenilenler")),
      body:
          isLoading
              ? const Center(
                child: CircularProgressIndicator(color: Color(0xFF25D366)),
              )
              : Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(8.0),
                    child: TextField(
                      controller: searchCtrl,
                      decoration: InputDecoration(
                        hintText: "Başlık veya açıklama ara...",
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        prefixIcon: const Icon(Icons.search),
                      ),
                    ),
                  ),
                  Expanded(
                    child:
                        filteredFavorites.isEmpty
                            ? Center(
                              child: Text(
                                message.isNotEmpty
                                    ? message
                                    : "Hiç beğenilen içerik yok veya içeriklerin süresi dolmuş.",
                                style: const TextStyle(fontSize: 16),
                                textAlign: TextAlign.center,
                              ),
                            )
                            : ListView.builder(
                              itemCount: filteredFavorites.length,
                              itemBuilder: (_, i) {
                                final post = filteredFavorites[i];
                                final postId =
                                    int.tryParse(post["id"].toString()) ?? 0;
                                final description = post["description"] ?? "";
                                final expanded = isExpanded[postId] ?? false;
                                final needsExpand = description.length > 150;
                                final imageUrls = List<String>.from(
                                  post["image_urls"] ?? [],
                                );

                                return GestureDetector(
                                  onTap: () => _showPostDetails(post),
                                  child: Card(
                                    margin: const EdgeInsets.all(10),
                                    elevation: 4,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        if (imageUrls.isNotEmpty)
                                          SizedBox(
                                            height: 180,
                                            child: ListView.builder(
                                              scrollDirection: Axis.horizontal,
                                              itemCount: imageUrls.length,
                                              itemBuilder: (context, imgIndex) {
                                                return Padding(
                                                  padding:
                                                      const EdgeInsets.only(
                                                        right: 8.0,
                                                      ),
                                                  child: ClipRRect(
                                                    borderRadius:
                                                        BorderRadius.circular(
                                                          4,
                                                        ),
                                                    child: Image.network(
                                                      imageUrls[imgIndex],
                                                      width: 180,
                                                      height: 180,
                                                      fit: BoxFit.cover,
                                                      loadingBuilder: (
                                                        context,
                                                        child,
                                                        loadingProgress,
                                                      ) {
                                                        if (loadingProgress ==
                                                            null)
                                                          return child;
                                                        return Container(
                                                          width: 180,
                                                          height: 180,
                                                          color:
                                                              Colors.grey[200],
                                                          child: const Center(
                                                            child:
                                                                CircularProgressIndicator(),
                                                          ),
                                                        );
                                                      },
                                                      errorBuilder:
                                                          (
                                                            context,
                                                            error,
                                                            stackTrace,
                                                          ) => Container(
                                                            width: 180,
                                                            height: 180,
                                                            color:
                                                                Colors
                                                                    .grey[200],
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
                                            height: 180,
                                            color: Colors.grey[200],
                                            child: const Center(
                                              child: Text(
                                                "Resim yok",
                                                style: TextStyle(fontSize: 16),
                                              ),
                                            ),
                                          ),
                                        Padding(
                                          padding: const EdgeInsets.all(10.0),
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                post["title"] ?? "",
                                                style: const TextStyle(
                                                  fontSize: 16,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                              const SizedBox(height: 4),
                                              if (description.isNotEmpty)
                                                Column(
                                                  crossAxisAlignment:
                                                      CrossAxisAlignment.start,
                                                  children: [
                                                    Text(
                                                      description,
                                                      maxLines:
                                                          expanded ? null : 3,
                                                      overflow:
                                                          expanded
                                                              ? TextOverflow
                                                                  .visible
                                                              : TextOverflow
                                                                  .ellipsis,
                                                      style: const TextStyle(
                                                        fontSize: 14,
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
                                                          style:
                                                              const TextStyle(
                                                                color: Color(
                                                                  0xFF25D366,
                                                                ),
                                                                fontWeight:
                                                                    FontWeight
                                                                        .bold,
                                                              ),
                                                        ),
                                                      ),
                                                  ],
                                                ),
                                              if (post["phone_number"] !=
                                                      null &&
                                                  post["phone_number"]
                                                      .toString()
                                                      .isNotEmpty)
                                                Padding(
                                                  padding:
                                                      const EdgeInsets.only(
                                                        top: 8.0,
                                                      ),
                                                  child: GestureDetector(
                                                    onTap:
                                                        () => _launchPhoneCall(
                                                          post["phone_number"],
                                                        ),
                                                    child: Text(
                                                      "Telefon: ${post["phone_number"]}",
                                                      style: const TextStyle(
                                                        fontSize: 13,
                                                        color: Color(
                                                          0xFF25D366,
                                                        ),
                                                      ),
                                                    ),
                                                  ),
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
                ],
              ),
    );
  }
}

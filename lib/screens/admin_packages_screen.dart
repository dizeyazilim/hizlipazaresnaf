import 'package:flutter/material.dart';
import '../services/api_service.dart';

class AdminPackagesScreen extends StatefulWidget {
  final Map user;
  const AdminPackagesScreen({required this.user});

  @override
  State<AdminPackagesScreen> createState() => _AdminPackagesScreenState();
}

class _AdminPackagesScreenState extends State<AdminPackagesScreen> {
  List<dynamic> packages = [];
  bool isLoading = true;
  String message = "";
  final ApiService _apiService = ApiService();

  @override
  void initState() {
    super.initState();
    if (widget.user['role'] != 'admin') {
      setState(() {
        isLoading = false;
        message = "Bu sayfaya erişim izniniz yok.";
      });
      return;
    }
    fetchPackages2();
  }

  Future<void> fetchPackages2() async {
    setState(() => isLoading = true);
    final packagesResult = await _apiService.fetchPackages2();
    setState(() {
      if (packagesResult['success']) {
        packages = packagesResult['packages'];
      } else {
        packages = [];
        message = packagesResult['message'];
      }
      isLoading = false;
    });
  }

  void showEditModal(dynamic pkg) {
    final nameCtrl = TextEditingController(text: pkg['name'] ?? '');
    final priceCtrl = TextEditingController(
      text: pkg['price']?.toString() ?? '',
    );
    final durationCtrl = TextEditingController(
      text: pkg['duration_days']?.toString() ?? '',
    );

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      builder:
          (context) => Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(context).viewInsets.bottom,
            ),
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    "Paket Düzenle",
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF25D366),
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: nameCtrl,
                    decoration: InputDecoration(
                      labelText: "Paket Adı",
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      filled: true,
                      fillColor: Colors.grey[100],
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: priceCtrl,
                    decoration: InputDecoration(
                      labelText: "Fiyat",
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      filled: true,
                      fillColor: Colors.grey[100],
                    ),
                    keyboardType: TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: durationCtrl,
                    decoration: InputDecoration(
                      labelText: "Süre (Gün)",
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      filled: true,
                      fillColor: Colors.grey[100],
                    ),
                    keyboardType: TextInputType.number,
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () async {
                        final updates = {
                          'package_id': pkg['id'],
                          'name': nameCtrl.text,
                          'price': double.tryParse(priceCtrl.text) ?? 0.0,
                          'duration_days': int.tryParse(durationCtrl.text) ?? 0,
                        };
                        final result = await _apiService.editPackage(updates);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text(result['message'])),
                        );
                        if (result['success']) {
                          fetchPackages2();
                        }
                        Navigator.pop(context);
                      },
                      icon: const Icon(Icons.save),
                      label: const Text("Güncelle"),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF25D366),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (widget.user['role'] != 'admin') {
      return const Scaffold(
        body: Center(
          child: Text("Erişim izniniz yok.", style: TextStyle(fontSize: 16)),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text("Paketler"),
        backgroundColor: Colors.white,
        elevation: 0,
        titleTextStyle: const TextStyle(
          color: Colors.black,
          fontSize: 20,
          fontWeight: FontWeight.bold,
        ),
      ),
      body:
          isLoading
              ? const Center(
                child: CircularProgressIndicator(color: Color(0xFF25D366)),
              )
              : packages.isEmpty
              ? Center(
                child: Text(
                  message.isNotEmpty ? message : "Paket yok.",
                  style: const TextStyle(fontSize: 16),
                ),
              )
              : RefreshIndicator(
                onRefresh: fetchPackages2,
                color: const Color(0xFF25D366),
                child: ListView.builder(
                  itemCount: packages.length,
                  itemBuilder: (context, index) {
                    final pkg = packages[index];
                    return Card(
                      margin: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 2,
                      child: ListTile(
                        title: Text(
                          pkg['name'] ?? '',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text("Fiyat: ${pkg['price']} TL"),
                            Text("Süre: ${pkg['duration_days']} gün"),
                          ],
                        ),
                        trailing: IconButton(
                          icon: const Icon(
                            Icons.edit,
                            color: Color(0xFF25D366),
                          ),
                          onPressed: () => showEditModal(pkg),
                        ),
                      ),
                    );
                  },
                ),
              ),
    );
  }
}

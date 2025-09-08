import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import '../config/api_config.dart';
import '../services/api_service.dart';

class AdminSubscribedUsersScreen extends StatefulWidget {
  final Map user;
  const AdminSubscribedUsersScreen({required this.user});

  @override
  State<AdminSubscribedUsersScreen> createState() =>
      _AdminSubscribedUsersScreenState();
}

class _AdminSubscribedUsersScreenState extends State<AdminSubscribedUsersScreen>
    with SingleTickerProviderStateMixin {
  List<dynamic> subscribedUsers = [];
  List<dynamic> nonSubscribedUsers = [];
  List<dynamic> packages = [];
  bool isLoading = true;
  bool isLoadingNonSubscribed = true;
  String message = "";
  final ApiService _apiService = ApiService();
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    if (widget.user['role'] != 'admin') {
      setState(() {
        isLoading = false;
        message = "Bu sayfaya erişim izniniz yok.";
      });
      return;
    }
    fetchData();
    fetchNonSubscribedUsers();
  }

  Future<void> fetchData() async {
    setState(() => isLoading = true);
    final usersResult = await _apiService.fetchSubscribedUsers();
    final packagesResult = await _apiService.fetchPackages2();
    setState(() {
      if (usersResult['success']) {
        subscribedUsers = usersResult['users'];
      } else {
        subscribedUsers = [];
        message = usersResult['message'];
      }
      if (packagesResult['success']) {
        packages = packagesResult['packages'];
      } else {
        packages = [];
        message =
            message.isEmpty
                ? packagesResult['message']
                : '$message\n${packagesResult['message']}';
      }
      isLoading = false;
    });
  }

  Future<void> fetchNonSubscribedUsers() async {
    setState(() => isLoadingNonSubscribed = true);
    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/admin/non_subscribed_users.php'),
      );
      final data = json.decode(response.body);
      setState(() {
        if (data['success'] == true) {
          nonSubscribedUsers =
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
              [];
        } else {
          nonSubscribedUsers = [];
          message = data['message'] ?? 'Pasif kullanıcılar alınamadı.';
        }
        isLoadingNonSubscribed = false;
      });
    } catch (e) {
      setState(() {
        nonSubscribedUsers = [];
        isLoadingNonSubscribed = false;
        message = 'Hata: $e';
      });
    }
  }

  void showEditModal(Map user) {
    // Initialize controllers and state
    int? selectedPackageId =
        int.tryParse(user['package_id']?.toString() ?? '0') ?? 0;
    DateTime? newEndDate =
        DateTime.tryParse(user['subscription_end'] ?? '') ?? DateTime.now();
    bool paymentStatus =
        (int.tryParse(user['payment_status'].toString()) ?? 0) == 1;
    String modalMessage = '';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      builder:
          (context) => StatefulBuilder(
            builder: (BuildContext context, StateSetter modalSetState) {
              return Padding(
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
                        "Abonelik Düzenle",
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF25D366),
                        ),
                      ),
                      const SizedBox(height: 16),
                      DropdownButtonFormField<int>(
                        value:
                            selectedPackageId != 0 ? selectedPackageId : null,
                        hint: const Text("Bir paket seçin"),
                        items:
                            packages.map((pkg) {
                              final pkgId =
                                  int.tryParse(pkg['id'].toString()) ?? 0;
                              return DropdownMenuItem<int>(
                                value: pkgId,
                                child: Text(pkg['name'] ?? 'Bilinmeyen Paket'),
                              );
                            }).toList(),
                        onChanged: (value) {
                          modalSetState(() {
                            selectedPackageId = value ?? 0;
                          });
                        },
                        decoration: InputDecoration(
                          labelText: "Paket Seç",
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          filled: true,
                          fillColor: Colors.grey[100],
                        ),
                      ),
                      const SizedBox(height: 16),
                      InkWell(
                        onTap: () async {
                          final picked = await showDatePicker(
                            context: context,
                            initialDate: newEndDate,
                            firstDate: DateTime.now(),
                            lastDate: DateTime(2100),
                          );
                          if (picked != null) {
                            modalSetState(() => newEndDate = picked);
                          }
                        },
                        child: InputDecorator(
                          decoration: InputDecoration(
                            labelText: "Bitiş Tarihi",
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            filled: true,
                            fillColor: Colors.grey[100],
                          ),
                          child: Text(
                            newEndDate != null
                                ? DateFormat('yyyy-MM-dd').format(newEndDate!)
                                : "Seç",
                            style: const TextStyle(color: Color(0xFF25D366)),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      SwitchListTile(
                        title: const Text("Ödeme Durumu Aktif"),
                        value: paymentStatus,
                        activeColor: const Color(0xFF25D366),
                        onChanged:
                            (value) =>
                                modalSetState(() => paymentStatus = value),
                      ),
                      const SizedBox(height: 16),
                      if (modalMessage.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 16.0),
                          child: Text(
                            modalMessage,
                            style: const TextStyle(color: Colors.redAccent),
                          ),
                        ),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          onPressed: () async {
                            if (selectedPackageId == 0 && paymentStatus) {
                              modalSetState(() {
                                modalMessage =
                                    'Aktif abonelik için paket seçimi zorunludur.';
                              });
                              return;
                            }
                            final updates = <String, dynamic>{
                              'payment_status': paymentStatus ? 1 : 0,
                            };
                            if (selectedPackageId != 0) {
                              updates['package_id'] = selectedPackageId;
                            }
                            if (newEndDate != null && paymentStatus) {
                              updates['subscription_end'] = DateFormat(
                                'yyyy-MM-dd 23:59:59',
                              ).format(newEndDate!);
                            }
                            final result = await _apiService.editSubscription(
                              user['id'],
                              updates,
                            );
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text(result['message'])),
                            );
                            if (result['success']) {
                              fetchData();
                              fetchNonSubscribedUsers(); // Refresh both lists
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
              );
            },
          ),
    );
  }

  Widget buildUserList(List<dynamic> users, bool isLoadingList) {
    if (isLoadingList) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF25D366)),
      );
    }
    if (users.isEmpty) {
      return Center(
        child: Text(
          message.isNotEmpty ? message : "Üye yok.",
          style: const TextStyle(fontSize: 16),
        ),
      );
    }
    return ListView.builder(
      itemCount: users.length,
      itemBuilder: (context, index) {
        final user = users[index];
        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 2,
          child: ListTile(
            title: Text(
              user['name'] ?? '',
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 4),
                Text(
                  "Email: ${user['email'] ?? ''}",
                  style: TextStyle(color: Colors.grey[600]),
                ),
                Text(
                  "Telefon: ${user['phone'] ?? ''}",
                  style: TextStyle(color: Colors.grey[600]),
                ),
                Text(
                  "Paket: ${user['package_name'] ?? 'Yok'}",
                  style: TextStyle(color: Colors.grey[600]),
                ),
                Text(
                  "Kalan Gün: ${user['days_left'] ?? '0'}",
                  style: TextStyle(color: Colors.grey[600]),
                ),
              ],
            ),
            trailing: IconButton(
              icon: const Icon(Icons.edit, color: Color(0xFF25D366)),
              onPressed: () => showEditModal(user),
            ),
          ),
        );
      },
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
        title: const Text("Üyeler"),
        backgroundColor: Colors.white,
        elevation: 0,
        titleTextStyle: const TextStyle(
          color: Colors.black,
          fontSize: 20,
          fontWeight: FontWeight.bold,
        ),
        bottom: TabBar(
          controller: _tabController,
          labelColor: Color(0xFF25D366),
          unselectedLabelColor: Colors.grey,
          indicatorColor: Color(0xFF25D366),
          tabs: const [
            Tab(text: "Aktif Aboneler"),
            Tab(text: "Pasif Aboneler"),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          RefreshIndicator(
            onRefresh: fetchData,
            color: const Color(0xFF25D366),
            child: buildUserList(subscribedUsers, isLoading),
          ),
          RefreshIndicator(
            onRefresh: fetchNonSubscribedUsers,
            color: const Color(0xFF25D366),
            child: buildUserList(nonSubscribedUsers, isLoadingNonSubscribed),
          ),
        ],
      ),
    );
  }
}

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:hizlipazaresnaf/screens/payment_redirect.dart';
import 'package:hizlipazaresnaf/config/api_config.dart';

class RegisterScreen extends StatefulWidget {
  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final PageController _controller = PageController();
  int currentPage = 0;

  // Kullanıcı bilgileri
  final nameCtrl = TextEditingController();
  final emailCtrl = TextEditingController();
  final phoneCtrl = TextEditingController();
  final passwordCtrl = TextEditingController();

  List packages = [];
  int? selectedPackageId;
  String errorMessage = "";
  bool isLoading = false;

  @override
  void initState() {
    super.initState();
    fetchPackages();
  }

  Future<void> fetchPackages() async {
    final response = await http.get(
      Uri.parse("${ApiConfig.baseUrl}/packages/list_packages.php"),
    );
    final data = json.decode(response.body);
    if (data["success"]) {
      setState(() => packages = data["packages"]);
    }
  }

  void nextPage() {
    if (currentPage < 2) {
      _controller.nextPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  void previousPage() {
    if (currentPage > 0) {
      _controller.previousPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  Future<void> register() async {
    setState(() => isLoading = true);
    if (selectedPackageId == null) {
      setState(() {
        errorMessage = "Paket seçimi gerekli.";
        isLoading = false;
      });
      return;
    }

    final response = await http.post(
      Uri.parse("${ApiConfig.baseUrl}/auth/register.php"),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        "name": nameCtrl.text.trim(),
        "email": emailCtrl.text.trim(),
        "phone": phoneCtrl.text.trim(),
        "password": passwordCtrl.text.trim(),
        "package_id": selectedPackageId.toString(),
      }),
    );

    final data = json.decode(response.body);
    setState(() => isLoading = false);
    if (data["success"]) {
      final merchantOid = data["merchant_oid"] ?? "";
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder:
              (_) => PaymentRedirectScreen(
                email: emailCtrl.text.trim(),
                merchantOid: merchantOid,
              ),
        ),
      );
    } else {
      setState(() => errorMessage = data["message"] ?? "Kayıt başarısız.");
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Kayıt Ol"),
        leading:
            currentPage > 0
                ? IconButton(
                  icon: const Icon(Icons.arrow_back),
                  onPressed: previousPage,
                )
                : null,
      ),
      body: PageView(
        controller: _controller,
        physics: const NeverScrollableScrollPhysics(),
        onPageChanged: (i) => setState(() => currentPage = i),
        children: [
          // 1. Adım – Kullanıcı Bilgileri
          Padding(
            padding: const EdgeInsets.all(20),
            child: ListView(
              children: [
                const Text(
                  "Kullanıcı Bilgileri",
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: nameCtrl,
                  decoration: const InputDecoration(labelText: "Ad Soyad"),
                ),
                TextField(
                  controller: emailCtrl,
                  decoration: const InputDecoration(labelText: "E-posta"),
                  keyboardType: TextInputType.emailAddress,
                ),
                TextField(
                  controller: phoneCtrl,
                  decoration: const InputDecoration(labelText: "Telefon"),
                  keyboardType: TextInputType.phone,
                ),
                TextField(
                  controller: passwordCtrl,
                  decoration: const InputDecoration(labelText: "Şifre"),
                  obscureText: true,
                ),
                const SizedBox(height: 24),
                if (errorMessage.isNotEmpty)
                  Text(errorMessage, style: const TextStyle(color: Colors.red)),
                ElevatedButton(
                  onPressed: () {
                    if (nameCtrl.text.isEmpty ||
                        emailCtrl.text.isEmpty ||
                        phoneCtrl.text.isEmpty ||
                        passwordCtrl.text.isEmpty) {
                      setState(() => errorMessage = "Tüm alanları doldurun.");
                    } else {
                      setState(() => errorMessage = "");
                      nextPage();
                    }
                  },
                  child: const Text("Devam Et"),
                ),
              ],
            ),
          ),

          // 2. Adım – Paket Seçimi
          Padding(
            padding: const EdgeInsets.all(20),
            child: ListView(
              children: [
                const Text(
                  "Paket Seçimi",
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
                ),
                const SizedBox(height: 12),
                if (packages.isEmpty)
                  const Center(child: CircularProgressIndicator())
                else
                  ...packages.map(
                    (p) => RadioListTile<int>(
                      value: int.tryParse(p["id"].toString()) ?? 0,
                      groupValue: selectedPackageId,
                      onChanged: (v) => setState(() => selectedPackageId = v),
                      title: Text(
                        "${p['name']} - ${p['price']}₺ / ${p['duration_days']} gün",
                        style: const TextStyle(fontSize: 16),
                      ),
                    ),
                  ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: selectedPackageId != null ? nextPage : null,
                  child: const Text("Ödeme ve Özet"),
                ),
              ],
            ),
          ),

          // 3. Adım – Özet & Ödeme
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  "Özet Bilgiler",
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
                ),
                const SizedBox(height: 16),
                Text("Ad: ${nameCtrl.text}"),
                Text("E-posta: ${emailCtrl.text}"),
                Text("Telefon: ${phoneCtrl.text}"),
                if (selectedPackageId != null)
                  Text(
                    "Paket: ${packages.firstWhere((p) => p['id'].toString() == selectedPackageId.toString(), orElse: () => {})['name'] ?? ''}",
                  ),
                const SizedBox(height: 24),
                if (errorMessage.isNotEmpty)
                  Text(errorMessage, style: const TextStyle(color: Colors.red)),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: isLoading ? null : register,
                    child:
                        isLoading
                            ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                            : const Text("Ödemeye Geç"),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

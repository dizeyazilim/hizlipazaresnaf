import 'dart:convert';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'dart:io';

class EditPostScreen extends StatefulWidget {
  final Map user;
  final Map post;

  const EditPostScreen({required this.user, required this.post, super.key});

  @override
  _EditPostScreenState createState() => _EditPostScreenState();
}

class _EditPostScreenState extends State<EditPostScreen> {
  final _formKey = GlobalKey<FormState>();
  String _title = '';
  String _description = '';
  String _phoneNumber = '';
  DateTime? _visibleFrom;
  DateTime? _visibleUntil;
  List<XFile> _newImages = [];
  List<String> _existingImages = [];
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    // Pre-fill form with post data
    _title = widget.post['title'];
    _description = widget.post['description'];
    _phoneNumber = widget.post['phone_number'];
    _visibleFrom = DateTime.parse(widget.post['visible_from']);
    _visibleUntil = DateTime.parse(widget.post['visible_until']);
    _existingImages = List<String>.from(widget.post['image_urls']);
  }

  Future<void> _pickImages() async {
    final picker = ImagePicker();
    final pickedFiles = await picker.pickMultiImage();
    setState(() {
      _newImages.addAll(pickedFiles);
    });
  }

  Future<void> _submitForm() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isSubmitting = true);
      try {
        var request = http.MultipartRequest(
          'POST',
          Uri.parse(
            'https://hizlipazaresnaf.com//hizlipazarvip/api/content/update_post.php',
          ),
        );
        request.fields['post_id'] = widget.post['id'].toString();
        request.fields['title'] = _title;
        request.fields['description'] = _description;
        request.fields['phone_number'] = _phoneNumber;
        request.fields['visible_from'] = _visibleFrom!.toIso8601String();
        request.fields['visible_until'] = _visibleUntil!.toIso8601String();
        request.fields['existing_images'] = jsonEncode(_existingImages);

        for (var image in _newImages) {
          request.files.add(
            await http.MultipartFile.fromPath('new_images[]', image.path),
          );
        }

        final response = await request.send();
        final responseBody = await response.stream.bytesToString();
        print('Update Post Response: $responseBody');
        if (response.statusCode == 200) {
          Navigator.pop(context);
          ScaffoldMessenger.of(
            context,
          ).showSnackBar(const SnackBar(content: Text('Gönderi güncellendi')));
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Gönderi güncellenemedi')),
          );
        }
      } catch (e) {
        print('Update error: $e');
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Hata: $e')));
      }
      setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF128C7E),
        title: const Text(
          'Gönderi Düzenle',
          style: TextStyle(color: Colors.white),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: ListView(
            children: [
              TextFormField(
                initialValue: _title,
                decoration: InputDecoration(
                  labelText: 'Başlık',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: const BorderSide(color: Color(0xFF10B981)),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                validator: (value) => value!.isEmpty ? 'Başlık gerekli' : null,
                onChanged: (value) => _title = value,
              ),
              const SizedBox(height: 16),
              TextFormField(
                initialValue: _description,
                decoration: InputDecoration(
                  labelText: 'Açıklama',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: const BorderSide(color: Color(0xFF10B981)),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                maxLines: 4,
                validator:
                    (value) => value!.isEmpty ? 'Açıklama gerekli' : null,
                onChanged: (value) => _description = value,
              ),
              const SizedBox(height: 16),
              TextFormField(
                initialValue: _phoneNumber,
                decoration: InputDecoration(
                  labelText: 'Telefon Numarası',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: const BorderSide(color: Color(0xFF10B981)),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                keyboardType: TextInputType.phone,
                validator:
                    (value) =>
                        value!.isEmpty ||
                                !RegExp(r'^(\+90|0)?5[0-9]{9}$').hasMatch(value)
                            ? 'Geçerli telefon numarası gerekli'
                            : null,
                onChanged: (value) => _phoneNumber = value,
              ),
              const SizedBox(height: 16),
              TextFormField(
                decoration: InputDecoration(
                  labelText: 'Başlangıç Tarihi',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: const BorderSide(color: Color(0xFF10B981)),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                readOnly: true,
                onTap: () async {
                  final date = await showDatePicker(
                    context: context,
                    initialDate: _visibleFrom!,
                    firstDate: DateTime.now(),
                    lastDate: DateTime(2030),
                  );
                  if (date != null) {
                    final time = await showTimePicker(
                      context: context,
                      initialTime: TimeOfDay.fromDateTime(_visibleFrom!),
                    );
                    if (time != null) {
                      setState(() {
                        _visibleFrom = DateTime(
                          date.year,
                          date.month,
                          date.day,
                          time.hour,
                          time.minute,
                        );
                      });
                    }
                  }
                },
                validator:
                    (value) =>
                        _visibleFrom == null
                            ? 'Başlangıç tarihi gerekli'
                            : null,
                controller: TextEditingController(
                  text: _visibleFrom != null ? _visibleFrom!.toString() : '',
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                decoration: InputDecoration(
                  labelText: 'Bitiş Tarihi',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: const BorderSide(color: Color(0xFF10B981)),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                readOnly: true,
                onTap: () async {
                  final date = await showDatePicker(
                    context: context,
                    initialDate: _visibleUntil!,
                    firstDate: DateTime.now(),
                    lastDate: DateTime(2030),
                  );
                  if (date != null) {
                    final time = await showTimePicker(
                      context: context,
                      initialTime: TimeOfDay.fromDateTime(_visibleUntil!),
                    );
                    if (time != null) {
                      setState(() {
                        _visibleUntil = DateTime(
                          date.year,
                          date.month,
                          date.day,
                          time.hour,
                          time.minute,
                        );
                      });
                    }
                  }
                },
                validator:
                    (value) =>
                        _visibleUntil == null ? 'Bitiş tarihi gerekli' : null,
                controller: TextEditingController(
                  text: _visibleUntil != null ? _visibleUntil!.toString() : '',
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Mevcut Resimler',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              if (_existingImages.isNotEmpty)
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    crossAxisSpacing: 8,
                    mainAxisSpacing: 8,
                  ),
                  itemCount: _existingImages.length,
                  itemBuilder: (context, index) {
                    return Stack(
                      children: [
                        CachedNetworkImage(
                          imageUrl: _existingImages[index],
                          width: 100,
                          height: 100,
                          fit: BoxFit.cover,
                          placeholder:
                              (context, url) =>
                                  const CircularProgressIndicator(),
                          errorWidget:
                              (context, url, error) => const Icon(Icons.error),
                        ),
                        Positioned(
                          top: 0,
                          right: 0,
                          child: IconButton(
                            icon: const Icon(
                              Icons.remove_circle,
                              color: Colors.red,
                            ),
                            onPressed: () {
                              setState(() {
                                _existingImages.removeAt(index);
                              });
                            },
                          ),
                        ),
                      ],
                    );
                  },
                )
              else
                const Text('Mevcut resim yok.'),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _pickImages,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF25D366),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text('Yeni Resim Ekle'),
              ),
              const SizedBox(height: 16),
              if (_newImages.isNotEmpty)
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    crossAxisSpacing: 8,
                    mainAxisSpacing: 8,
                  ),
                  itemCount: _newImages.length,
                  itemBuilder: (context, index) {
                    return Stack(
                      children: [
                        Image.file(
                          File(_newImages[index].path),
                          width: 100,
                          height: 100,
                          fit: BoxFit.cover,
                        ),
                        Positioned(
                          top: 0,
                          right: 0,
                          child: IconButton(
                            icon: const Icon(
                              Icons.remove_circle,
                              color: Colors.red,
                            ),
                            onPressed: () {
                              setState(() {
                                _newImages.removeAt(index);
                              });
                            },
                          ),
                        ),
                      ],
                    );
                  },
                ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _isSubmitting ? null : _submitForm,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF128C7E),
                  foregroundColor: Colors.white,
                  minimumSize: const Size(double.infinity, 50),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child:
                    _isSubmitting
                        ? const CircularProgressIndicator(color: Colors.white)
                        : const Text('Güncelle'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

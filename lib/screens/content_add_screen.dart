import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:convert';

class ContentAddScreen extends StatefulWidget {
  final Map user;

  const ContentAddScreen({required this.user, super.key});

  @override
  _ContentAddScreenState createState() => _ContentAddScreenState();
}

class _ContentAddScreenState extends State<ContentAddScreen> {
  final _formKey = GlobalKey<FormState>();
  String _title = '';
  String _description = '';
  String _phoneNumber = '';
  DateTime? _visibleFrom;
  DateTime? _visibleUntil;
  List<dynamic> _images = []; // Store XFile (mobile) or PlatformFile (web)
  List<String> _imageNames = []; // Store file names
  List<Uint8List> _imageBytes = []; // Store bytes for web previews
  bool _isSubmitting = false;

  Future<void> _pickImages() async {
    try {
      if (kIsWeb) {
        // Use file_picker for web
        FilePickerResult? result = await FilePicker.platform.pickFiles(
          type: FileType.image,
          allowMultiple: true,
        );
        if (result != null && result.files.isNotEmpty) {
          setState(() {
            _images = result.files;
            _imageNames = result.files.map((file) => file.name).toList();
            _imageBytes = result.files.map((file) => file.bytes!).toList();
          });
        }
      } else {
        // Use image_picker for mobile
        final picker = ImagePicker();
        final pickedFiles = await picker.pickMultiImage();
        if (pickedFiles.isNotEmpty) {
          setState(() {
            _images = pickedFiles;
            _imageNames = pickedFiles.map((file) => file.name).toList();
            _imageBytes = []; // Not needed for mobile
          });
        }
      }
    } catch (e) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Resim seçme hatası: $e')));
    }
  }

  Future<void> _submitForm() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isSubmitting = true);
      try {
        var request = http.MultipartRequest(
          'POST',
          Uri.parse(
            'https://hizlipazaresnaf.com//hizlipazarvip/api/content/create_post.php',
          ),
        );
        request.fields['title'] = _title;
        request.fields['description'] = _description;
        request.fields['phone_number'] = _phoneNumber;
        request.fields['visible_from'] = _visibleFrom!.toIso8601String();
        request.fields['visible_until'] = _visibleUntil!.toIso8601String();
        request.fields['created_by'] = widget.user['id'].toString();

        if (kIsWeb) {
          // Web: Use bytes from file_picker
          for (int i = 0; i < _images.length; i++) {
            request.files.add(
              http.MultipartFile.fromBytes(
                'images[]',
                _imageBytes[i],
                filename: _imageNames[i],
              ),
            );
          }
        } else {
          // Mobile: Use file paths from image_picker
          for (var image in _images) {
            request.files.add(
              await http.MultipartFile.fromPath('images[]', image.path),
            );
          }
        }

        final response = await request.send();
        final responseBody = await response.stream.bytesToString();
        print('Create Post Response: $responseBody');
        if (response.statusCode == 200) {
          final jsonResponse = jsonDecode(responseBody);
          if (jsonResponse['success']) {
            Navigator.pop(context);
            ScaffoldMessenger.of(
              context,
            ).showSnackBar(SnackBar(content: Text(jsonResponse['message'])));
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(jsonResponse['message'] ?? 'Hata oluştu')),
            );
          }
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Gönderi oluşturulamadı')),
          );
        }
      } catch (e) {
        print('Submit error: $e');
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
          'Gönderi Ekle',
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
                    initialDate: DateTime.now(),
                    firstDate: DateTime.now(),
                    lastDate: DateTime(2030),
                  );
                  if (date != null) {
                    final time = await showTimePicker(
                      context: context,
                      initialTime: TimeOfDay.now(),
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
                    initialDate: DateTime.now().add(const Duration(days: 7)),
                    firstDate: DateTime.now(),
                    lastDate: DateTime(2030),
                  );
                  if (date != null) {
                    final time = await showTimePicker(
                      context: context,
                      initialTime: TimeOfDay.now(),
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
              ElevatedButton(
                onPressed: _pickImages,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF25D366),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text('Resim Seç'),
              ),
              const SizedBox(height: 16),
              if (_images.isNotEmpty)
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    crossAxisSpacing: 8,
                    mainAxisSpacing: 8,
                  ),
                  itemCount: _images.length,
                  itemBuilder: (context, index) {
                    return Stack(
                      children: [
                        kIsWeb
                            ? Image.memory(
                              _imageBytes[index],
                              width: 100,
                              height: 100,
                              fit: BoxFit.cover,
                            )
                            : Image.file(
                              File(_images[index].path),
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
                                _images.removeAt(index);
                                _imageNames.removeAt(index);
                                if (kIsWeb) _imageBytes.removeAt(index);
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
                        : const Text('Ekle'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

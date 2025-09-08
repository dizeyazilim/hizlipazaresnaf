import 'package:flutter/material.dart';

class PaymentReturnScreen extends StatelessWidget {
  final String? status;

  const PaymentReturnScreen({Key? key, this.status}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final bool isSuccess = status == 'basarili';

    return Scaffold(
      appBar: AppBar(title: const Text("Ödeme Sonucu")),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              isSuccess ? Icons.check_circle : Icons.error,
              color: isSuccess ? Colors.green : Colors.red,
              size: 64,
            ),
            const SizedBox(height: 16),
            Text(
              isSuccess ? "Ödeme başarılı!" : "Ödeme başarısız.",
              style: const TextStyle(fontSize: 18),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed:
                  () =>
                      Navigator.of(context).popUntil((route) => route.isFirst),
              child: const Text("Ana Sayfaya Dön"),
            ),
          ],
        ),
      ),
    );
  }
}

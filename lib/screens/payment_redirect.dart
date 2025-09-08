import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

class PaymentRedirectScreen extends StatefulWidget {
  final String email;
  final String merchantOid;

  const PaymentRedirectScreen({
    required this.email,
    required this.merchantOid,
    Key? key,
  }) : super(key: key);

  @override
  State<PaymentRedirectScreen> createState() => _PaymentRedirectScreenState();
}

class _PaymentRedirectScreenState extends State<PaymentRedirectScreen>
    with WidgetsBindingObserver {
  bool error = false;
  bool paymentStarted = false;
  bool resumedOnce = false;
  late final String paymentUrl;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    paymentUrl =
        "https://hizlipazaresnaf.com/hizlipazarvip/api/payment/payment_redirect.php"
        "?email=${Uri.encodeComponent(widget.email)}"
        "&merchant_oid=${Uri.encodeComponent(widget.merchantOid)}";
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && paymentStarted) {
      setState(() => resumedOnce = true);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (kIsWeb) {
      return Scaffold(
        appBar: AppBar(title: Text("Ödeme")),
        body: Center(
          child:
              resumedOnce
                  ? Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.check_circle, color: Colors.green, size: 48),
                      SizedBox(height: 12),
                      Text("Ödeme yaptıysanız devam edebilirsiniz."),
                      SizedBox(height: 24),
                      ElevatedButton(
                        onPressed: () {
                          // İsteğe bağlı: ödeme durumunu API'den sorgula
                          Navigator.pop(context);
                        },
                        child: Text("Devam Et"),
                      ),
                    ],
                  )
                  : ElevatedButton.icon(
                    onPressed: () async {
                      final uri = Uri.parse(paymentUrl);
                      if (await canLaunchUrl(uri)) {
                        paymentStarted = true;
                        await launchUrl(
                          uri,
                          mode: LaunchMode.externalApplication,
                        );
                      } else {
                        setState(() => error = true);
                      }
                    },
                    icon: Icon(Icons.payment),
                    label: Text("Ödeme Sayfasını Aç"),
                  ),
        ),
      );
    }

    // Mobil platformda WebView ile ödeme ekranı açılır
    return Scaffold(
      appBar: AppBar(title: Text("Ödeme")),
      body:
          error
              ? Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.error, color: Colors.red, size: 48),
                    SizedBox(height: 12),
                    Text(
                      "Ödeme ekranı açılamadı.\nLütfen tekrar deneyin.",
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.red),
                    ),
                    SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: () => setState(() => error = false),
                      child: Text("Tekrar Dene"),
                    ),
                  ],
                ),
              )
              : WebViewWidget(
                controller:
                    WebViewController()
                      ..setJavaScriptMode(JavaScriptMode.unrestricted)
                      ..setNavigationDelegate(
                        NavigationDelegate(
                          onWebResourceError: (err) {
                            setState(() => error = true);
                          },
                          onNavigationRequest: (navReq) {
                            if (navReq.url.contains('basarili')) {
                              Navigator.pop(context);
                              return NavigationDecision.prevent;
                            }
                            if (navReq.url.contains('basarisiz')) {
                              setState(() => error = true);
                              return NavigationDecision.prevent;
                            }
                            return NavigationDecision.navigate;
                          },
                        ),
                      )
                      ..loadRequest(Uri.parse(paymentUrl)),
              ),
    );
  }
}

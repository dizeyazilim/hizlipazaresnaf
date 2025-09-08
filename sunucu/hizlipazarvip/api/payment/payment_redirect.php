<?php
require_once '../../config/db.php';

// Parametreleri al
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$merchant_oid = isset($_GET['merchant_oid']) ? trim($_GET['merchant_oid']) : '';

// Eksik parametre kontrolü
if (!$email || !$merchant_oid) {
    die("Hatalı ödeme oturumu (parametre eksik)");
}

// Kullanıcı ve ödeme bilgilerini çek
$stmt = $db->prepare("SELECT u.*, p.price, p.name as package_name, p.duration_days 
    FROM users u 
    LEFT JOIN packages p ON u.package_id = p.id 
    WHERE u.email=? AND u.merchant_oid=?");
$stmt->execute([$email, $merchant_oid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Hatalı ödeme oturumu (user bulunamadı)");
}

// PayTR ayarlarını çek
$paytr = $db->query("SELECT * FROM paytr_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$paytr) {
    die("PayTR ayarları eksik!");
}

if ((int)$paytr['is_active'] === 0) {
    $iban = $db->query("SELECT * FROM iban_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$iban) {
        die("IBAN ayarları eksik!");
    }

    $odenecek_tutar = number_format($user['price'], 2, ',', '.'); // örnek: 99,90
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>IBAN ile Ödeme</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                padding: 1.5rem;
                background-color: #f5f7fa;
                color: #333;
                margin: 0;
            }
            .box {
                background: #fff;
                border-radius: 10px;
                padding: 2rem;
                max-width: 600px;
                margin: 2rem auto;
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                text-align: center;
            }
            h2 {
                color: #1E40AF;
                margin-bottom: 1rem;
                font-size: 1.6rem;
            }
            .iban-info {
                font-size: 1rem;
                line-height: 1.6;
                text-align: left;
                margin-top: 1rem;
            }
            .iban-info p {
                margin: 0.5rem 0;
            }
            .amount {
                font-size: 1.3rem;
                margin-top: 1.5rem;
                color: #10B981;
                font-weight: bold;
            }
            .note {
                margin-top: 2rem;
                font-size: 0.95rem;
                color: #666;
            }

            @media (max-width: 600px) {
                .box {
                    padding: 1.2rem;
                    margin: 1rem;
                }
                .iban-info {
                    font-size: 0.95rem;
                }
                h2 {
                    font-size: 1.3rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Banka Havalesi / EFT ile Ödeme</h2>

            <div class="iban-info">
                <p><strong>Banka Adı:</strong> <?= htmlspecialchars($iban['bank_name']) ?></p>
                <p><strong>Hesap Sahibi:</strong> <?= htmlspecialchars($iban['account_holder']) ?></p>
                <p><strong>IBAN:</strong> <?= htmlspecialchars($iban['iban']) ?></p>
            </div>

            <div class="amount">
                Ödenecek Tutar: <?= $odenecek_tutar ?> TL
            </div>

            <p class="note">
                Açıklama kısmına <strong><?= htmlspecialchars($email) ?></strong> e-posta adresinizi yazmayı unutmayın.<br>
                Ödeme sonrası en geç 24 saat içinde onaylanacaktır.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// PayTR ile ödeme devam ediyor...
// Zorunlu tüm alanlar
$merchant_id = $paytr['merchant_id'];
$merchant_key = $paytr['merchant_key'];
$merchant_salt = $paytr['merchant_salt'];
$merchant_ok_url = $paytr['merchant_ok_url'];
$merchant_fail_url = $paytr['merchant_fail_url'];
$test_mode = $paytr['test_mode'];
$non_3d = $paytr['non_3d'];

$payment_amount = (int)($user['price'] * 100); // kuruş
$currency = 'TL';
$user_name = $user['name'] ?? '';
$user_phone = $user['phone'] ?? '';
$user_address = 'Online ödeme';

$user_basket = base64_encode(json_encode([
    [$user['package_name'], number_format($user['price'], 2, '.', ''), 1]
]));

// IP al
if( isset( $_SERVER["HTTP_CLIENT_IP"] ) )
    $user_ip = $_SERVER["HTTP_CLIENT_IP"];
elseif( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) )
    $user_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
else
    $user_ip = $_SERVER["REMOTE_ADDR"];

// POST verisi oluştur
$postData = [
    "merchant_id" => $merchant_id,
    "user_ip" => $user_ip,
    "merchant_oid" => $merchant_oid,
    "email" => $email,
    "payment_amount" => $payment_amount,
    "user_basket" => $user_basket,
    "user_name" => $user_name,
    "user_address" => $user_address,
    "user_phone" => $user_phone,
    "merchant_ok_url" => $merchant_ok_url,
    "merchant_fail_url" => $merchant_fail_url,
    "test_mode" => $test_mode,
    "currency" => $currency,
    "debug_on" => 1,
];

// TOKEN oluştur
$hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket . '0' . '0' . $currency . $test_mode;
$paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));
$postData['paytr_token'] = $paytr_token;
$postData['no_installment'] = 0;
$postData['max_installment'] = 0;

// PayTR token isteği
$ch = curl_init("https://www.paytr.com/odeme/api/get-token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$result = curl_exec($ch);
if(curl_errno($ch)) die("PAYTR bağlantı hatası: ".curl_error($ch));
curl_close($ch);

$response = json_decode($result, true);
if (!$response || $response['status'] != 'success') {
    $hata = isset($response['reason']) ? $response['reason'] : "Bilinmeyen hata";
    ?>
    <!DOCTYPE html>
    <html><head>
        <title>Ödeme Oturumu Hatası</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>body{font-family:Arial;text-align:center;margin-top:70px}.err{color:#e53935;font-size:22px}</style>
    </head><body>
        <div class="err">Ödeme ekranı açılamadı.<br><small><?= htmlspecialchars($hata) ?></small></div>
    </body></html>
    <?php
    exit;
}

$token = $response['token'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>PayTR Güvenli Ödeme</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0">
    <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
    <iframe src="https://www.paytr.com/odeme/guvenli/<?= htmlspecialchars($token) ?>"
            id="paytriframe" frameborder="0" scrolling="no" style="width:100vw;height:90vh;"></iframe>
    <script>iFrameResize({},'#paytriframe');</script>
</body>
</html>

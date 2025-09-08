<?php
require_once '../../config/db.php';

// POST verisini al ve logla
$post = $_POST;

// POST boşsa veya zorunlu alanlar yoksa çık
if (empty($post) || !isset($post['merchant_oid'], $post['status'], $post['total_amount'])) {
    exit('Missing POST');
}

// PAYTR ayarlarını al
$paytr = $db->query("SELECT * FROM paytr_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$paytr) exit('PayTR ayarları eksik!');

// Hash doğrulama
$hash = base64_encode(hash_hmac(
    'sha256',
    $post['merchant_oid'] . $paytr['merchant_salt'] . $post['status'] . $post['total_amount'],
    $paytr['merchant_key'],
    true
));

if ($hash !== $post['hash']) {
    exit('PAYTR notification failed: bad hash');
}

// Log kaydı (her callbacki kaydediyoruz)
$db->prepare("INSERT INTO payment_logs (merchant_oid, status, amount, raw_data) VALUES (?, ?, ?, ?)")
    ->execute([
        $post['merchant_oid'],
        $post['status'],
        floatval($post['total_amount'])/100, // PayTR kuruş olarak yollar, örn: 1999 = 19.99 TL
        json_encode($post)
    ]);

// Ödeme başarılıysa users tablosunda payment_status=1 ve abonelik tarihleri güncelle
if ($post['status'] === 'success') {
    // Kullanıcıyı bul (merchant_oid ile eşleşen)
    $userQuery = $db->prepare("SELECT id, package_id FROM users WHERE merchant_oid=? LIMIT 1");
    $userQuery->execute([$post['merchant_oid']]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Paketin kaç gün sürdüğünü bul
        $packageQuery = $db->prepare("SELECT duration_days FROM packages WHERE id=?");
        $packageQuery->execute([$user['package_id']]);
        $package = $packageQuery->fetch(PDO::FETCH_ASSOC);

        if ($package) {
            $start = date('Y-m-d H:i:s');
            $end = date('Y-m-d H:i:s', strtotime("+{$package['duration_days']} days"));

            // Kullanıcıyı güncelle
            $update = $db->prepare("UPDATE users SET payment_status=1, subscription_start=?, subscription_end=? WHERE id=?");
            $update->execute([$start, $end, $user['id']]);
        } else {
            // Paket bilgisi yoksa sadece payment_status güncelle
            $db->prepare("UPDATE users SET payment_status=1 WHERE id=?")->execute([$user['id']]);
        }
    }
}

// PayTR OK yanıtı bekler, başka bir çıktı gönderme!
echo "OK";
exit;

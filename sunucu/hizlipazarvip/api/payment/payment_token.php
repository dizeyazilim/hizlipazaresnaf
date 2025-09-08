<?php
// payment_token.php

require_once '../../config/db.php';

// JSON post ile veri alıyoruz
$input = json_decode(file_get_contents('php://input'), true);

// Doğrulamalar (gelen parametreler: email, merchant_oid, package_id, vs.)
$email = $input['email'] ?? '';
$merchant_oid = $input['merchant_oid'] ?? '';
$package_id = $input['package_id'] ?? null;

// PayTR Ayarlarını veritabanından çek
$paytr = $db->query("SELECT * FROM paytr_settings ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Kullanıcı ve paket bilgisiyle fiyat hesapla
$user = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$user->execute([$email]);
$user = $user->fetch(PDO::FETCH_ASSOC);

$package = $db->prepare("SELECT * FROM packages WHERE id=? LIMIT 1");
$package->execute([$package_id]);
$package = $package->fetch(PDO::FETCH_ASSOC);

// Sepet formatı: [["Paket Adı", "Fiyat", 1]]
$user_basket = base64_encode(json_encode([
    [$package['name'], number_format($package['price'], 2, '.', ''), 1]
]));

$user_ip = $_SERVER['REMOTE_ADDR'];
$payment_amount = intval($package['price'] * 100); // Kuruş cinsinden

// hash ve parametreler
$hash_str = $paytr['merchant_id'] .
    $user_ip .
    $merchant_oid .
    $email .
    $payment_amount .
    $user_basket .
    "0" . // no_installment
    "0" . // max_installment
    $paytr['currency'] .
    $paytr['test_mode'];

$paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $paytr['merchant_salt'], $paytr['merchant_key'], true));

$params = [
    "merchant_id" => $paytr['merchant_id'],
    "user_ip" => $user_ip,
    "merchant_oid" => $merchant_oid,
    "email" => $email,
    "payment_type" => "card",
    "payment_amount" => $payment_amount,
    "paytr_token" => $paytr_token,
    "user_basket" => $user_basket,
    "debug_on" => 1,
    "no_installment" => 0,
    "max_installment" => 0,
    "user_name" => $user['name'],
    "user_address" => "Adres girilmedi",
    "user_phone" => $user['phone'],
    "merchant_ok_url" => $paytr['merchant_ok_url'],
    "merchant_fail_url" => $paytr['merchant_fail_url'],
    "timeout_limit" => 30,
    "currency" => $paytr['currency'] ?? "TL",
    "test_mode" => $paytr['test_mode']
];

// Token al
$ch = curl_init("https://www.paytr.com/odeme/api/get-token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
$result = curl_exec($ch);
curl_close($ch);

$result = json_decode($result, true);

if($result['status']=='success') {
    echo json_encode([
        "success" => true,
        "token" => $result['token'],
        "iframe_url" => "https://www.paytr.com/odeme/guvenli/" . $result['token'],
        "merchant_id" => $paytr['merchant_id'],
        "user_ip" => $user_ip,
        "merchant_oid" => $merchant_oid,
        "email" => $email,
        "payment_type" => "card",
        "payment_amount" => $payment_amount,
        "currency" => $paytr['currency'] ?? "TL",
        "test_mode" => $paytr['test_mode'],
        "non_3d" => $paytr['non_3d'],
        "merchant_ok_url" => $paytr['merchant_ok_url'],
        "merchant_fail_url" => $paytr['merchant_fail_url'],
        "user_name" => $user['name'],
        "user_address" => "Adres girilmedi",
        "user_phone" => $user['phone'],
        "user_basket" => $user_basket,
        "client_lang" => "tr"
    ]);
} else {
    echo json_encode(["success" => false, "message" => $result['reason']]);
}

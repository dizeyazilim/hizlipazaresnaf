<?php
require_once '../../config/db.php';
require_once '../../config/headers.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id)) {
    echo json_encode(["success" => false, "message" => "user_id gerekli."]);
    exit;
}

// Kullanıcı ve paket bilgilerini birlikte çek
$query = $db->prepare(
    "SELECT 
        u.id, u.name, u.email, u.phone, u.role, u.payment_status, 
        u.subscription_start, u.subscription_end, 
        p.id AS package_id, p.name AS package_name, p.price, p.duration_days
     FROM users u
     LEFT JOIN packages p ON u.package_id = p.id
     WHERE u.id = ?"
);
$query->execute([$data->user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Kalan gün hesabı
    $remaining_days = null;
    if ($user['subscription_end']) {
        $now = new DateTime();
        $end = new DateTime($user['subscription_end']);
        $interval = $now->diff($end);
        $remaining_days = ($end > $now) ? $interval->days : 0;
    }

    echo json_encode([
        "success" => true,
        "user" => $user,
        "remaining_days" => $remaining_days
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Kullanıcı bulunamadı."
    ]);
}

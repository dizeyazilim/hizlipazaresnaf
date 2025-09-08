<?php
require_once '../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID zorunlu']);
    exit;
}

$user_id = intval($input['user_id']);
$updates = [];
$params = [];

if (isset($input['package_id'])) {
    $updates[] = "package_id = ?";
    $params[] = intval($input['package_id']);
}

if (isset($input['subscription_end'])) {
    $updates[] = "subscription_end = ?";
    $params[] = $input['subscription_end']; // Tarih formatı: YYYY-MM-DD HH:MM:SS
}

if (isset($input['payment_status'])) {
    $updates[] = "payment_status = ?";
    $params[] = intval($input['payment_status']); // 0 veya 1
}

if (isset($input['subscription_start'])) {
    $updates[] = "subscription_start = ?";
    $params[] = $input['subscription_start'];
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'Güncellenecek alan yok']);
    exit;
}

$sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
$params[] = $user_id;

$stmt = $db->prepare($sql);
$success = $stmt->execute($params);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Abonelik güncellendi']);
} else {
    echo json_encode(['success' => false, 'message' => 'Güncelleme başarısız']);
}
?>
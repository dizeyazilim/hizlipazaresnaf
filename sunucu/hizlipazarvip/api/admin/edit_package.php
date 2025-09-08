<?php
require_once '../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['package_id'])) {
    echo json_encode(['success' => false, 'message' => 'Paket ID zorunlu']);
    exit;
}

$package_id = intval($input['package_id']);
$updates = [];
$params = [];

if (isset($input['name'])) {
    $updates[] = "name = ?";
    $params[] = $input['name'];
}
if (isset($input['price'])) {
    $updates[] = "price = ?";
    $params[] = floatval($input['price']);
}
if (isset($input['duration_days'])) {
    $updates[] = "duration_days = ?";
    $params[] = intval($input['duration_days']);
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'Güncellenecek alan yok']);
    exit;
}

$sql = "UPDATE packages SET " . implode(', ', $updates) . " WHERE id = ?";
$params[] = $package_id;

$stmt = $db->prepare($sql);
$success = $stmt->execute($params);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Paket güncellendi']);
} else {
    echo json_encode(['success' => false, 'message' => 'Güncelleme başarısız']);
}
?>
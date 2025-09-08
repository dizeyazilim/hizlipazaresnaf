<?php
require_once '../config.php';
require_once '../db.php';

$package_id = intval($_POST['package_id'] ?? 0);
$name = $_POST['name'] ?? '';
$price = floatval($_POST['price'] ?? 0);
$duration_days = intval($_POST['duration_days'] ?? 0);

if ($package_id <= 0 || empty($name) || $price <= 0 || $duration_days <= 0) {
    header('Location: ' . BASE_URL . '/?page=paket-duzenle&error=Geçersiz veri');
    exit;
}

try {
    $stmt = $db->prepare("UPDATE packages SET name = ?, price = ?, duration_days = ? WHERE id = ?");
    $success = $stmt->execute([$name, $price, $duration_days, $package_id]);
    header('Location: ' . BASE_URL . '/?page=paket-duzenle&success=Paket güncellendi');
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '/?page=paket-duzenle&error=Hata: ' . $e->getMessage());
}
?>
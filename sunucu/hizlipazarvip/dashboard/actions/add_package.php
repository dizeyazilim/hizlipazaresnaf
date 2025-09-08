<?php
require_once '../config.php';
require_once '../db.php';

$name = $_POST['name'] ?? '';
$price = floatval($_POST['price'] ?? 0);
$duration_days = intval($_POST['duration_days'] ?? 0);

if (empty($name) || $price <= 0 || $duration_days <= 0) {
    header('Location: ' . BASE_URL . '/?page=paket-ekle&error=Geçersiz veri');
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO packages (name, price, duration_days) VALUES (?, ?, ?)");
    $success = $stmt->execute([$name, $price, $duration_days]);
    header('Location: ' . BASE_URL . '/?page=paket-ekle&success=Paket eklendi');
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '/?page=paket-ekle&error=Hata: ' . $e->getMessage());
}
?>
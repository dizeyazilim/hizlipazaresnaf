<?php
require_once '../../config/db.php';
require_once '../../config/headers.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email)) {
    echo json_encode(["success" => false, "message" => "Email gerekli."]);
    exit;
}

// Ödeme başarılıysa kullanıcı aktif edilir (örnek senaryo)
$query = $db->prepare("UPDATE users SET payment_status = 1 WHERE email = ?");
$ok = $query->execute([$data->email]);

echo json_encode([
    "success" => $ok,
    "message" => $ok ? "Ödeme onayı başarıyla işlendi." : "Kullanıcı bulunamadı veya güncellenemedi."
]);

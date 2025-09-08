<?php
require_once '../../config/db.php';
require_once '../../config/headers.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email, $data->password)) {
    echo json_encode(["success" => false, "message" => "Eksik bilgi."]);
    exit;
}

$query = $db->prepare("SELECT * FROM users WHERE email = ?");
$query->execute([$data->email]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($data->password, $user['password'])) {
    // sadece güvenli verileri gönder
    $safeUser = [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "phone" => $user['phone'],
        "role" => $user['role'],
        "payment_status" => (int) $user['payment_status'],
    ];

    echo json_encode([
        "success" => true,
        "user" => $safeUser
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Giriş başarısız"]);
}

<?php
require_once '../../config/db.php';
require_once '../../config/headers.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->name, $data->email, $data->password, $data->package_id)) {
    echo json_encode(["success" => false, "message" => "Eksik bilgi."]);
    exit;
}

$merchant_oid = "OID" . date("YmdHis") . strtoupper(substr(md5(uniqid()), 0, 6));
$hashed = password_hash($data->password, PASSWORD_DEFAULT);

$query = $db->prepare("INSERT INTO users (name, email, phone, password, package_id, merchant_oid) VALUES (?, ?, ?, ?, ?, ?)");
$ok = $query->execute([
    $data->name,
    $data->email,
    $data->phone,
    $hashed,
    $data->package_id,
    $merchant_oid
]);

if ($ok) {
    echo json_encode([
        "success" => true,
        "message" => "Kayıt başarılı",
        "merchant_oid" => $merchant_oid,
        "email" => $data->email
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Kayıt başarısız"]);
}

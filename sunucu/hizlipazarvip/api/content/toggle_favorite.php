<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['post_id'])) {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
    exit;
}

$userId = intval($data['user_id']);
$postId = intval($data['post_id']);

require_once '../../config/db.php';
require_once '../../config/headers.php';

try {
    $query = $db->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
    $query->execute([$userId, $postId]);

    if ($query->rowCount() > 0) {
        // Beğeni zaten varsa sil
        $delete = $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $delete->execute([$userId, $postId]);
        echo json_encode(["success" => true, "message" => "Favoriden kaldırıldı"]);
    } else {
        // Yoksa ekle
        $insert = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $insert->execute([$userId, $postId]);
        echo json_encode(["success" => true, "message" => "Favoriye eklendi"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Veritabanı hatası", "error" => $e->getMessage()]);
}

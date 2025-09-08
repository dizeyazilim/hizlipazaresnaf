<?php
require_once '../../config/db.php';
require_once '../../config/headers.php';

$data = json_decode(file_get_contents("php://input"), true);
$userId = $data['user_id'] ?? null;
$postId = $data['post_id'] ?? null;

if (!$userId || !$postId) {
    echo json_encode(["success" => false, "message" => "Eksik parametre."]);
    exit;
}

// Beğeni var mı kontrol et
$query = $db->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
$query->execute([$userId, $postId]);
$like = $query->fetch();

if ($like) {
    // Beğeni varsa sil
    $delete = $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $success = $delete->execute([$userId, $postId]);
    $liked = false;
} else {
    // Yoksa ekle
    $insert = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $success = $insert->execute([$userId, $postId]);
    $liked = true;
}

echo json_encode(["success" => $success, "liked" => $liked]);

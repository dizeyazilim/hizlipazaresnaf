<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/headers.php';

error_log("get_likes.php: Processing request");

$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if ($post_id <= 0) {
    error_log("get_likes.php: Invalid post_id=$post_id");
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz gönderi ID'
    ]);
    exit;
}

try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $count = $stmt->fetchColumn();

    error_log("get_likes.php: Retrieved $count likes for post_id=$post_id");

    echo json_encode([
        'success' => true,
        'likes' => intval($count)
    ]);
} catch (PDOException $e) {
    error_log("get_likes.php: Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
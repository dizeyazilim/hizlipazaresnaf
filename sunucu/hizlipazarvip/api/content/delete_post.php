<?php
require_once '../../config/db.php';
require_once '../../config/headers.php';

$data = json_decode(file_get_contents("php://input"), true);
$post_id = intval($data["post_id"] ?? 0);

if ($post_id <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz ID"]);
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();

    // Fetch image URLs to delete physical files
    $stmt = $db->prepare("SELECT image_url FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Delete physical image files
    foreach ($images as $image) {
        $file_path = parse_url($image['image_url'], PHP_URL_PATH);
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    // Delete images from post_images table
    $stmt = $db->prepare("DELETE FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);

    // Delete post from posts table
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $success = $stmt->execute([$post_id]);

    if ($success) {
        $db->commit();
        echo json_encode(["success" => true, "message" => "İçerik silindi"]);
    } else {
        throw new Exception("İçerik silinemedi");
    }
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Hata: " . $e->getMessage()]);
}
?>
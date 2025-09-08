<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once  'config.php';
require_once  'db.php';

header('Content-Type: application/json; charset=utf-8');
ob_start(); // Buffer output to prevent stray whitespace

error_log("dashboard/delete_post.php: Starting post deletion for post_id=" . ($_POST['post_id'] ?? 'none'));

// Check user permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor'])) {
    error_log("dashboard/delete_post.php: Unauthorized access attempt by user_id=" . ($_SESSION['user_id'] ?? 'none'));
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$post_id = intval($_POST['post_id'] ?? 0);

$errors = [];
if ($post_id <= 0) {
    $errors[] = "Geçersiz gönderi ID";
}

// Verify post exists and user has permission
if ($post_id > 0) {
    try {
        $stmt = $db->prepare("SELECT created_by FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            $errors[] = "Gönderi bulunamadı";
        } elseif ($_SESSION['user_role'] !== 'admin' && $post['created_by'] != $_SESSION['user_id']) {
            $errors[] = "Bu gönderiyi silme yetkiniz yok";
        }
    } catch (PDOException $e) {
        error_log("dashboard/delete_post.php: Database error during post check: " . $e->getMessage());
        $errors[] = "Veritabanı hatası: Gönderi kontrolü başarısız";
    }
}

if (!empty($errors)) {
    error_log("dashboard/delete_post.php: Validation errors: " . implode(", ", $errors));
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
    exit;
}

try {
    $db->beginTransaction();

    // Fetch associated images
    $stmt = $db->prepare("SELECT image_url FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $current_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Delete images from server
    foreach ($current_images as $image_url) {
        $file_path = str_replace(BASE_URL . '/Uploads/', $_SERVER['DOCUMENT_ROOT'] . '/hizlipazarvip/uploads/', $image_url);
        if (file_exists($file_path)) {
            unlink($file_path);
            error_log("dashboard/delete_post.php: Deleted image: $file_path");
        }
    }

    // Delete images from post_images table
    $stmt = $db->prepare("DELETE FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);

    // Delete post
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $success = $stmt->execute([$post_id]);

    if (!$success) {
        throw new Exception("Gönderi silinemedi");
    }

    error_log("dashboard/delete_post.php: Post deleted, post_id=$post_id");

    $db->commit();
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'İçerik silindi']);
} catch (Exception $e) {
    $db->rollBack();
    error_log("dashboard/delete_post.php: Error: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
exit;
?>
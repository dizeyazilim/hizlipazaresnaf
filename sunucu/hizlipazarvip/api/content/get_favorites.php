<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/headers.php';

// Set timezone to match Turkey (+03)
date_default_timezone_set('Europe/Istanbul');

$user_id = intval($_GET['user_id'] ?? 0);
$now = date('Y-m-d H:i:s');

error_log("get_favorites.php: Fetching posts for user_id=$user_id, NOW=$now");

if ($user_id <= 0) {
    error_log("get_favorites.php: Invalid user_id=$user_id");
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID']);
    exit;
}

try {
    // Query posts liked by the user or created by the user
    $query = $db->prepare("
        SELECT 
            p.id,
            p.title,
            p.description,
            p.phone_number,
            p.visible_from,
            p.visible_until,
            p.created_by,
            GROUP_CONCAT(pi.image_url) AS image_urls
        FROM posts p
        LEFT JOIN likes f ON p.id = f.post_id AND f.user_id = :user_id
        LEFT JOIN post_images pi ON p.id = pi.post_id
        WHERE (f.user_id = :user_id OR p.created_by = :user_id)
        AND p.visible_from <= :now AND p.visible_until >= :now
        GROUP BY p.id
        ORDER BY p.visible_from DESC
    ");
    $query->execute(['user_id' => $user_id, 'now' => $now]);
    $posts = $query->fetchAll(PDO::FETCH_ASSOC);

    // Process image_urls to return as an array
    foreach ($posts as &$post) {
        $post['image_urls'] = !empty($post['image_urls']) ? explode(',', $post['image_urls']) : [];
        $post['visible_from'] = date('Y-m-d H:i:s', strtotime($post['visible_from']));
        $post['visible_until'] = date('Y-m-d H:i:s', strtotime($post['visible_until']));
    }

    error_log("get_favorites.php: Retrieved " . count($posts) . " posts for user_id=$user_id: " . json_encode($posts));

    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
} catch (PDOException $e) {
    error_log("get_favorites.php: Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
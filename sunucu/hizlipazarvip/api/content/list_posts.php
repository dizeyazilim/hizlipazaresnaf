<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/headers.php';

// Set timezone to match Turkey (+03)
date_default_timezone_set('Europe/Istanbul');

$now = date('Y-m-d H:i:s');
error_log("list_posts.php: Fetching posts, NOW=$now");

try {
    // Query posts with their image URLs
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
        LEFT JOIN post_images pi ON p.id = pi.post_id
        WHERE p.visible_from <= :now AND p.visible_until >= :now
        GROUP BY p.id
        ORDER BY p.visible_from DESC
        LIMIT 50
    ");
    $query->execute(['now' => $now]);
    $posts = $query->fetchAll(PDO::FETCH_ASSOC);

    // Process image_urls and format dates
    foreach ($posts as &$post) {
        $post['image_urls'] = !empty($post['image_urls']) ? explode(',', $post['image_urls']) : [];
        $post['visible_from'] = date('Y-m-d H:i:s', strtotime($post['visible_from']));
        $post['visible_until'] = date('Y-m-d H:i:s', strtotime($post['visible_until']));
    }

    error_log("list_posts.php: Retrieved " . count($posts) . " posts: " . json_encode($posts));

    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
} catch (Exception $e) {
    error_log("list_posts.php: Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
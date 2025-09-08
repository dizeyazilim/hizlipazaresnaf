<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/headers.php';

// Set timezone to match Turkey (+03)
date_default_timezone_set('Europe/Istanbul');

$user_id = intval($_GET['user_id'] ?? 0);
$now = date('Y-m-d H:i:s');

error_log("get_notifications.php: Fetching notifications for user_id=$user_id, NOW=$now");

if ($user_id <= 0) {
    error_log("get_notifications.php: Invalid user_id=$user_id");
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID']);
    exit;
}

try {
    // Fetch recent posts and likes related to the user
    $query = $db->prepare("
        SELECT 
            'new_post' AS type,
            p.id AS post_id,
            p.title,
            p.description,
            p.visible_from AS created_at,
            u.name AS user_name
        FROM posts p
        JOIN users u ON p.created_by = u.id
        WHERE p.visible_from > DATE_SUB(:now, INTERVAL 1 DAY)
        AND p.created_by != :user_id
        UNION ALL
        SELECT 
            'new_like' AS type,
            p.id AS post_id,
            p.title,
            p.description,
            f.created_at,
            u.name AS user_name
        FROM likes f
        JOIN posts p ON f.post_id = p.id
        JOIN users u ON f.user_id = u.id
        WHERE f.created_at > DATE_SUB(:now, INTERVAL 1 DAY)
        AND f.user_id != :user_id
        AND p.created_by = :user_id
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $query->execute(['user_id' => $user_id, 'now' => $now]);
    $notifications = $query->fetchAll(PDO::FETCH_ASSOC);

    error_log("get_notifications.php: Retrieved " . count($notifications) . " notifications for user_id=$user_id");

    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
} catch (PDOException $e) {
    error_log("get_notifications.php: Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
<?php
require_once '../../config/db.php';
require_once '../../config/headers.php';

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$phone = $_POST['phone_number'] ?? '';
$visible_from = $_POST['visible_from'] ?? '';
$visible_until = $_POST['visible_until'] ?? '';
$created_by = $_POST['created_by'] ?? '';

if (empty($title) || empty($description) || empty($phone) || empty($visible_from) || empty($visible_until) || empty($created_by) || empty($_FILES['images'])) {
    echo json_encode(['success' => false, 'message' => 'Tüm alanlar zorunlu']);
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();

    // Insert post
    $query = $db->prepare("INSERT INTO posts 
        (title, description, phone_number, visible_from, visible_until, created_by) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $success = $query->execute([$title, $description, $phone, $visible_from, $visible_until, $created_by]);

    if (!$success) {
        throw new Exception("Post eklenemedi.");
    }

    $post_id = $db->lastInsertId();
    $image_urls = [];

    // Handle multiple images
    if (!empty($_FILES['images']['name'])) {
        $total_files = count($_FILES['images']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['images']['error'][$i] == UPLOAD_ERR_OK) {
                $image_name = time() . '_' . $i . '_' . basename($_FILES['images']['name'][$i]);
                $target_path = '../../uploads/' . $image_name;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target_path)) {
                    $image_url = 'https://hizlipazaresnaf.com//hizlipazarvip/uploads/' . $image_name;
                    $image_urls[] = $image_url;

                    // Insert image URL into post_images
                    $image_query = $db->prepare("INSERT INTO post_images (post_id, image_url) VALUES (?, ?)");
                    $image_query->execute([$post_id, $image_url]);
                }
            }
        }
    }

    if (empty($image_urls)) {
        throw new Exception("En az bir resim yüklenmeli.");
    }

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'İçerik eklendi.',
        'post_id' => $post_id,
        'image_urls' => $image_urls
    ]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
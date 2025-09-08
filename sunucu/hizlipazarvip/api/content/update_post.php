<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once '../../dashboard/config.php';
require_once '../../dashboard/db.php';

error_log("update_post.php: Processing post update");

$post_id = intval($_POST['post_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$visible_from = $_POST['visible_from'] ?? '';
$visible_until = $_POST['visible_until'] ?? '';
$existing_images = json_decode($_POST['existing_images'] ?? '[]', true);

$errors = [];
if ($post_id <= 0) $errors[] = "Geçersiz gönderi ID";
if (empty($title)) $errors[] = "Başlık gerekli";
if (empty($description)) $errors[] = "Açıklama gerekli";
if (empty($phone_number) || !preg_match('/^(\+90|0)?[5][0-9]{9}$/', $phone_number)) $errors[] = "Geçerli bir telefon numarası gerekli";
if (empty($visible_from)) $errors[] = "Başlangıç tarihi gerekli";
if (empty($visible_until)) $errors[] = "Bitiş tarihi gerekli";

if (!empty($errors)) {
    error_log("update_post.php: Validation errors: " . implode(", ", $errors));
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
    exit;
}

try {
    $db->beginTransaction();

    // Update post
    $stmt = $db->prepare("
        UPDATE posts 
        SET title = ?, description = ?, phone_number = ?, visible_from = ?, visible_until = ?
        WHERE id = ?
    ");
    $success = $stmt->execute([$title, $description, $phone_number, $visible_from, $visible_until, $post_id]);

    if (!$success) {
        throw new Exception("Gönderi güncellenemedi");
    }

    error_log("update_post.php: Post updated, post_id=$post_id");

    // Fetch current images
    $stmt = $db->prepare("SELECT image_url FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $current_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Delete removed images
    $images_to_delete = array_diff($current_images, $existing_images);
    foreach ($images_to_delete as $image_url) {
        $file_path = str_replace(BASE_URL . '/Uploads/', $_SERVER['DOCUMENT_ROOT'] . '/hizlipazarvip/uploads/', $image_url);
        if (file_exists($file_path)) {
            unlink($file_path);
            error_log("update_post.php: Deleted image: $file_path");
        }
        $stmt = $db->prepare("DELETE FROM post_images WHERE post_id = ? AND image_url = ?");
        $stmt->execute([$post_id, $image_url]);
    }

    // Handle new image uploads
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/hizlipazarvip/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!empty($_FILES['new_images']['name'][0])) {
        foreach ($_FILES['new_images']['name'] as $i => $name) {
            if ($_FILES['new_images']['error'][$i] == UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    throw new Exception("Geçersiz resim formatı: $name");
                }
                $image_name = time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target_path = $upload_dir . $image_name;
                if (move_uploaded_file($_FILES['new_images']['tmp_name'][$i], $target_path)) {
                    $image_url = BASE_URL . '/Uploads/' . $image_name;
                    $stmt = $db->prepare("INSERT INTO post_images (post_id, image_url) VALUES (?, ?)");
                    $stmt->execute([$post_id, $image_url]);
                    error_log("update_post.php: New image uploaded: $image_url");
                } else {
                    throw new Exception("Resim yüklenemedi: $name");
                }
            } elseif ($_FILES['new_images']['error'][$i] != UPLOAD_ERR_NO_FILE) {
                throw new Exception("Resim yükleme hatası: " . $_FILES['new_images']['error'][$i]);
            }
        }
    }

    $db->commit();
    error_log("update_post.php: Post updated successfully: post_id=$post_id");
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Gönderi güncellendi']);
} catch (Exception $e) {
    $db->rollBack();
    error_log("update_post.php: Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
exit;
?>
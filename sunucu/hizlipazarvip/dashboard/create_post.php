<?php
// Hata ayarları
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'db.php';

session_start();
ob_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor'])) {
    header("Location: " . BASE_URL . "/?page=icerik-olustur&error=" . urlencode("Yetkisiz erişim"));
    exit;
}

// POST verilerini al
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$visible_from = $_POST['visible_from'] ?? '';
$visible_until = $_POST['visible_until'] ?? '';

$errors = [];

if (empty($title)) $errors[] = "Başlık gerekli";
if (empty($description)) $errors[] = "Açıklama gerekli";
if (empty($phone_number) || !preg_match('/^(\+90|0)?5[0-9]{9}$/', $phone_number)) {
    $phone_number = '+90' . preg_replace('/^0/', '', $phone_number);
    if (!preg_match('/^(\+90)?5[0-9]{9}$/', $phone_number)) {
        $errors[] = "Geçerli bir telefon numarası gerekli";
    }
}
if (empty($visible_from)) $errors[] = "Başlangıç tarihi gerekli";
if (empty($visible_until)) $errors[] = "Bitiş tarihi gerekli";

if (!empty($errors)) {
    header("Location: " . BASE_URL . "/?page=icerik-olustur&error=" . urlencode(implode(", ", $errors)));
    exit;
}

try {
    $db->beginTransaction();

    // Gönderi ekle
    $stmt = $db->prepare("
        INSERT INTO posts (title, description, phone_number, visible_from, visible_until, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $title,
        $description,
        $phone_number,
        $visible_from,
        $visible_until,
        $_SESSION['user_id']
    ]);

    $post_id = $db->lastInsertId();

    // Görsel klasörü
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/hizlipazarvip/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Görselleri işle
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $i => $name) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    throw new Exception("Geçersiz resim formatı: $name");
                }

                $image_name = time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target_path = $upload_dir . $image_name;

                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target_path)) {
                    $image_url = APP_URL . '/uploads/' . $image_name;
                    $stmt = $db->prepare("INSERT INTO post_images (post_id, image_url) VALUES (?, ?)");
                    $stmt->execute([$post_id, $image_url]);
                } else {
                    throw new Exception("Resim yüklenemedi: $name");
                }
            } elseif ($_FILES['images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                throw new Exception("Resim yükleme hatası: " . $_FILES['images']['error'][$i]);
            }
        }
    }

    $db->commit();
    header("Location: " . BASE_URL . "/?page=icerik-olustur&success=" . urlencode("İçerik başarıyla eklendi"));
    exit;

} catch (Exception $e) {
    $db->rollBack();
    header("Location: " . BASE_URL . "/?page=icerik-olustur&error=" . urlencode("Hata: " . $e->getMessage()));
    exit;
}

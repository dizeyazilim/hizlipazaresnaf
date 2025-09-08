<?php
require_once '../config.php';
require_once '../db.php';

$user_id = intval($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = in_array($_POST['role'] ?? '', ['admin', 'editor', 'member']) ? $_POST['role'] : 'member';
$package_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : null;
$payment_status = intval($_POST['payment_status'] ?? 0);
$subscription_start = !empty($_POST['subscription_start']) ? $_POST['subscription_start'] : null;
$subscription_end = !empty($_POST['subscription_end']) ? $_POST['subscription_end'] : null;

if ($user_id <= 0 || empty($name) || empty($email)) {
    error_log("edit_user.php: Invalid data: id=$user_id, name=$name, email=$email");
    header('Location: ' . BASE_URL . '/dashboard/?page=uyeler&error=Geçersiz veri');
    exit;
}

try {
    $db->beginTransaction();
    
    // Validate package_id exists if provided
    if ($package_id) {
        $stmt = $db->prepare("SELECT id FROM packages WHERE id = ?");
        $stmt->execute([$package_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Geçersiz paket ID: $package_id");
        }
    }

    // Update user
    $stmt = $db->prepare("
        UPDATE users 
        SET name = ?, email = ?, phone = ?, role = ?, package_id = ?, payment_status = ?, subscription_start = ?, subscription_end = ?
        WHERE id = ?
    ");
    $success = $stmt->execute([
        $name, $email, $phone, $role, $package_id, $payment_status, 
        $subscription_start, $subscription_end, $user_id
    ]);

    if (!$success) {
        throw new Exception("Kullanıcı güncellenemedi.");
    }

    $db->commit();
    error_log("edit_user.php: User updated successfully: id=$user_id");
    header('Location: ' . BASE_URL . '/dashboard/?page=uyeler&success=Kullanıcı güncellendi');
} catch (Exception $e) {
    $db->rollBack();
    error_log("edit_user.php: Error: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/dashboard/?page=uyeler&error=Hata: ' . urlencode($e->getMessage()));
}
exit;
?>
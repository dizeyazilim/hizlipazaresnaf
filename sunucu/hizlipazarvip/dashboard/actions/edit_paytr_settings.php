<?php
// Temporary debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log POST data
error_log("toggle_paytr_status.php: POST data: " . print_r($_POST, true));

try {
    require_once '../config.php'; 
	 require_once '../db.php';// Match ayarlar.php path
    if (!isset($db)) {
        error_log("toggle_paytr_status.php: Database connection not initialized");
        throw new Exception("Veritabanı bağlantısı başlatılamadı.");
    }

    $id = $_POST['id'] ?? 0;
    $is_active = $_POST['is_active'] ?? 0;

    // Validate ID
    if ($id == 0) {
        error_log("toggle_paytr_status.php: Invalid ID provided");
        throw new Exception("Geçersiz PayTR kaydı.");
    }

    $stmt = $db->prepare("UPDATE paytr_settings SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $id]);

    header("Location: " . BASE_URL . "/ayarlar.php?success=PayTR durumu güncellendi");
} catch (Exception $e) {
    error_log("toggle_paytr_status.php: Error: " . $e->getMessage());
    header("Location: " . BASE_URL . "/ayarlar.php?error=" . urlencode($e->getMessage()));
}
exit;
?>
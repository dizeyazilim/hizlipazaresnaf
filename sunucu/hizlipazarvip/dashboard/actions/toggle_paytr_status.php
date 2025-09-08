<?php
header('Content-Type: application/json');

// Temporary debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log POST data
error_log("toggle_paytr_status.php: POST data: " . print_r($_POST, true));

try {
    require_once '../config.php'; // Match ayarlar.php path
    if (!isset($db)) {
        error_log("toggle_paytr_status.php: Database connection not initialized");
        throw new Exception("Veritabanı bağlantısı başlatılamadı.");
    }

    $id = $_POST['id'] ?? 0;
    $is_active = $_POST['is_active'] ?? 0;

    // Log input values
    error_log("toggle_paytr_status.php: id=$id, is_active=$is_active");

    // Validate ID
    if ($id == 0) {
        error_log("toggle_paytr_status.php: Invalid ID provided");
        throw new Exception("Geçersiz PayTR kaydı.");
    }

    // Check if record exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM paytr_settings WHERE id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        error_log("toggle_paytr_status.php: No record found for id = $id");
        throw new Exception("PayTR kaydı bulunamadı.");
    }

    // Update is_active
    $stmt = $db->prepare("UPDATE paytr_settings SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $id]);
    $affected_rows = $stmt->rowCount();
    error_log("toggle_paytr_status.php: Affected rows: $affected_rows");

    if ($affected_rows == 0) {
        error_log("toggle_paytr_status.php: No rows updated for id = $id, is_active = $is_active");
        throw new Exception("PayTR durumu güncellenemedi.");
    }

    echo json_encode(['success' => true, 'message' => 'PayTR durumu güncellendi']);
} catch (Exception $e) {
    error_log("toggle_paytr_status.php: Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>
<?php
require_once '../config.php'; // Adjust path as needed
require_once '../db.php';
try {
    $id = $_POST['id'] ?? 0;
    $iban = $_POST['iban'] ?? '';
    $bank_name = $_POST['bank_name'] ?? '';
    $account_holder = $_POST['account_holder'] ?? '';

    $stmt = $db->prepare("REPLACE INTO iban_settings (id, iban, bank_name, account_holder) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $iban, $bank_name, $account_holder]);

    header("Location: " . BASE_URL . "/ayarlar.php?success=IBAN bilgileri güncellendi");
} catch (PDOException $e) {
    error_log("edit_iban_settings.php: Database error: " . $e->getMessage());
    header("Location: " . BASE_URL . "/ayarlar.php?error=Veritabanı hatası");
}
exit;
?>
<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'db.php';
$page = $_GET['page'] ?? 'dashboard';

$valid_pages = [
    'dashboard', 'uyeler', 'paket-ekle', 'paket-duzenle',
    'abonelikler', 'icerik-olustur', 'icerik-duzenle',
    'icerik-sil', 'ayarlar', 'sistem-bilgi'
];
$page_file = in_array($page, $valid_pages) ? "views/$page.php" : 'views/dashboard.php';

// Debug: Log the page and session
error_log("Index.php: Page=$page, File=$page_file, Script=" . basename($_SERVER['PHP_SELF']));
error_log("Index.php: Session=" . print_r($_SESSION, true));

if (!file_exists($page_file)) {
    error_log("Index.php: Page file not found: $page_file");
    $error_message = "Sayfa bulunamadı: $page. Dosya: $page_file";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hızlı Pazar Esnaf - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL); ?>/assets/css/custom.css">
</head>
<body class="bg-gray-100">
    <!-- Mobile Menu Button -->
    <div class="mobile-menu-btn hidden fixed top-4 left-4 z-50 bg-blue-500 text-white p-2 rounded-md shadow-lg">
        <i class="fas fa-bars text-xl"></i>
    </div>

    <!-- Sidebar -->
    <?php 
    if (file_exists('views/sidebar.php')) {
        include 'views/sidebar.php'; 
    } else {
        error_log("Index.php: Sidebar file not found: views/sidebar.php");
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">Sidebar dosyası bulunamadı.</div>';
    }
    ?>

    <!-- Main Content -->
    <div class="content min-h-screen ml-64 p-6">
        <?php 
        if (file_exists('views/navbar.php')) {
            include 'views/navbar.php'; 
        } else {
            error_log("Index.php: Navbar file not found: views/navbar.php");
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">Navbar dosyası bulunamadı.</div>';
        }
        ?>
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <?php 
            try {
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    throw new Exception("İçerik dosyası bulunamadı: $page_file");
                }
            } catch (Exception $e) {
                error_log("Index.php: Include error: " . $e->getMessage());
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php 
    if (file_exists('views/footer.php')) {
        include 'views/footer.php'; 
    } else {
        error_log("Index.php: Footer file not found: views/footer.php");
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">Footer dosyası bulunamadı.</div>';
    }
    ?>

    <script src="<?php echo htmlspecialchars(BASE_URL); ?>/assets/js/scripts.js"></script>
</body>
</html>
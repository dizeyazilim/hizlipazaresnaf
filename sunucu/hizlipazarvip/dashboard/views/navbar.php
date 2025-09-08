<?php
require_once 'config.php';
require_once 'db.php';

// Kullanıcı oturumu bilgileri
$user_name = $_SESSION['user_name'] ?? 'Admin';
$user_image = $_SESSION['user_image'] ?? 'https://img.icons8.com/3d-fluency/94/person-male--v3.png';

// Türkiye saat dilimi
date_default_timezone_set('Europe/Istanbul');

// Bildirim sayısını hesapla
try {
    $query = $db->query("
        SELECT COUNT(*) 
        FROM (
            SELECT id FROM posts WHERE visible_from > DATE_SUB(NOW(), INTERVAL 1 DAY)
            UNION ALL
            SELECT id FROM likes WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ) AS notifications
    ");
    $notifications = $query->fetchColumn();
    error_log("navbar.php: Retrieved $notifications notifications");
} catch (PDOException $e) {
    error_log("navbar.php: Database error: " . $e->getMessage());
    $notifications = 0;
}

// Sayfa başlığı
$pageTitle = ucfirst(str_replace('-', ' ', $page ?? 'Dashboard'));
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="flex items-center space-x-4">
        <!-- Arama Kutusu -->
        <div class="relative">
            <input 
                type="text" 
                placeholder="Ara..." 
                class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#10B981]"
            >
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Bildirimler -->
        <div class="relative">
            <i class="fas fa-bell text-xl text-gray-600 cursor-pointer"></i>
            <?php if ($notifications > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                <?php echo $notifications; ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Kullanıcı Bilgisi -->
        <div class="flex items-center">
            <img src="https://img.icons8.com/3d-fluency/94/person-male--v3.png" alt="User" class="rounded-full w-8 h-8">
            <span class="ml-2 font-medium hidden md:inline"><?php echo htmlspecialchars($user_name); ?></span>
        </div>

        <!-- Çıkış Butonu -->
        <a href="<?php echo BASE_URL; ?>/logout.php" class="bg-gradient-to-r from-[#1E40AF] to-[#3B82F6] text-white px-4 py-2 rounded-lg hover:transform hover:-translate-y-1 hover:shadow-lg transition-all duration-300 flex items-center">
            <i class="fas fa-sign-out-alt mr-2"></i>
            <span class="hidden md:inline">Çıkış Yap</span>
        </a>
    </div>
</div>

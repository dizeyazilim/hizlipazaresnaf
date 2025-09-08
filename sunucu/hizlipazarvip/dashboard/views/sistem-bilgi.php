
<?php
// Use absolute path to config.php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';

// System Information
$app_version = '1.0.0'; // Hardcoded, replace with dynamic version if available
$php_version = phpversion();
$server_os = php_uname('s') . ' ' . php_uname('r');
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor';
$memory_usage = round(memory_get_usage() / 1024 / 1024, 2); // MB

// Uptime (Linux/Unix only, fallback for others)
$uptime = 'Bilinmiyor';
if (function_exists('shell_exec')) {
    $uptime_str = @shell_exec('uptime -p');
    if ($uptime_str) {
        $uptime = str_replace('up ', '', trim($uptime_str));
    }
}

// Database Information
try {
    $db_version = $db->query("SELECT VERSION()")->fetchColumn();
    $db_status = 'Bağlı';
} catch (PDOException $e) {
    error_log("sistem-bilgisi.php: Database error: " . $e->getMessage());
    $db_version = 'Bilinmiyor';
    $db_status = 'Bağlantı Hatası';
}

// Environment Details
$environment = [
    'Zaman Dilimi' => date_default_timezone_get(),
    'Sunucu IP' => $_SERVER['SERVER_ADDR'] ?? 'Bilinmiyor',
    'Sunucu Portu' => $_SERVER['SERVER_PORT'] ?? 'Bilinmiyor',
    'PHP Bellek Limiti' => ini_get('memory_limit'),
    'Maksimum Yükleme Boyutu' => ini_get('upload_max_filesize'),
    'Maksimum Çalışma Süresi' => ini_get('max_execution_time') . ' saniye'
];
?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Sistem Bilgisi</h2>

    <!-- System Status -->
    <div class="mb-6">
        <h3 class="text-md font-semibold text-gray-800 mb-2">Sistem Durumu</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500">Veritabanı Bağlantısı</p>
                        <h3 class="text-lg font-bold <?php echo $db_status === 'Bağlı' ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo htmlspecialchars($db_status); ?>
                        </h3>
                    </div>
                    <div class="bg-<?php echo $db_status === 'Bağlı' ? 'green' : 'red'; ?>-100 p-3 rounded-full">
                        <i class="fas fa-<?php echo $db_status === 'Bağlı' ? 'check-circle' : 'exclamation-circle'; ?> text-<?php echo $db_status === 'Bağlı' ? 'green' : 'red'; ?>-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500">Sunucu Çalışma Süresi</p>
                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($uptime); ?></h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-clock text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
        
            </div>
        </div>
    </div>

    <!-- Version Information -->
    <div class="mb-6">
        <h3 class="text-md font-semibold text-gray-800 mb-2">Sürüm Bilgileri</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500">Uygulama Sürümü</p>
                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($app_version); ?></h3>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500">PHP Sürümü</p>
                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($php_version); ?></h3>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500">Veritabanı Sürümü</p>
                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($db_version); ?></h3>
            </div>
        </div>
    </div>

    <!-- Technical Details -->
    <div>
        <h3 class="text-md font-semibold text-gray-800 mb-2">Teknik Detaylar</h3>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <p><strong>Sunucu İşletim Sistemi:</strong> <?php echo htmlspecialchars($server_os); ?></p>
                <p><strong>Web Sunucusu:</strong> <?php echo htmlspecialchars($server_software); ?></p>
                <p><strong>Bellek Kullanımı:</strong> <?php echo $memory_usage; ?> MB</p>
           
                <?php foreach ($environment as $key => $value): ?>
                    <p><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php
$baseDir = __DIR__ . '/dashboard';

// Klasörler
$folders = [
    '',
    'views',
    'assets',
    'assets/css',
    'assets/js',
    'actions',
];

// Dosyalar ve örnek içerikler
$files = [
    'index.php' => "<?php\n// Dashboard giriş noktası\n",
    'config.php' => "<?php\n// Genel yapılandırma ayarları\n",
    'db.php' => "<?php\n// Veritabanı bağlantı dosyası\n",

    // Views
    'views/navbar.php' => "<nav><!-- Navbar --></nav>",
    'views/sidebar.php' => "<aside><!-- Sidebar --></aside>",
    'views/footer.php' => "<footer><!-- Footer --></footer>",
    'views/dashboard.php' => "<main><!-- Dashboard --></main>",
    'views/uyeler.php' => "<!-- Üyeler sayfası -->",
    'views/paket-ekle.php' => "<!-- Paket Ekle sayfası -->",
    'views/paket-duzenle.php' => "<!-- Paket Düzenle sayfası -->",
    'views/abonelikler.php' => "<!-- Abonelikler sayfası -->",
    'views/icerik-olustur.php' => "<!-- İçerik Oluştur sayfası -->",
    'views/icerik-duzenle.php' => "<!-- İçerik Düzenle sayfası -->",
    'views/icerik-sil.php' => "<!-- İçerik Sil sayfası -->",
    'views/ayarlar.php' => "<!-- Ayarlar sayfası -->",
    'views/sistem-bilgi.php' => "<!-- Sistem Bilgi sayfası -->",

    // Actions
    'actions/add_package.php' => "<?php\n// Paket ekleme işlemleri",
    'actions/edit_package.php' => "<?php\n// Paket düzenleme işlemleri",

    // Assets
    'assets/css/custom.css' => "/* Özel CSS */",
    'assets/js/scripts.js' => "// JavaScript kodları",
];

// Klasörleri oluştur
foreach ($folders as $folder) {
    $path = $baseDir . ($folder ? "/$folder" : '');
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "Klasör oluşturuldu: $path\n";
    }
}

// Dosyaları oluştur
foreach ($files as $relativePath => $content) {
    $fullPath = $baseDir . '/' . $relativePath;
    if (!file_exists($fullPath)) {
        file_put_contents($fullPath, $content);
        echo "Dosya oluşturuldu: $fullPath\n";
    }
}

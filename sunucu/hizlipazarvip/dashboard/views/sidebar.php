<?php
$user_name = $_SESSION['user_name'] ?? 'Admin';
$user_role = $_SESSION['user_role'] ?? 'Yönetici';
$user_image = $_SESSION['user_image'] ?? 'https://img.icons8.com/3d-fluency/94/person-male--v3.png';
$active_page = $page ?? 'dashboard';
?>

<!-- Sidebar -->
<div class="sidebar bg-gray-800 text-white fixed h-full w-64 overflow-y-auto">
    <div class="p-4 flex items-center justify-between border-b border-gray-700">
        <div class="flex items-center">
            <i class="fas fa-car text-blue-400 text-2xl mr-3"></i>
            <span class="logo-text font-bold text-xl">Hızlı Pazar Esnaf</span>
        </div>
        <button class="menu-toggle text-gray-400 hover:text-white focus:outline-none">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <div class="p-4 border-b border-gray-700 flex items-center">
        <img src="https://img.icons8.com/3d-fluency/94/person-male--v3.png" alt="User" class="rounded-full w-10 h-10">
        <div class="ml-3 nav-text">
            <div class="font-medium"><?php echo htmlspecialchars($user_name); ?></div>
            <div class="text-gray-400 text-sm"><?php echo htmlspecialchars($user_role); ?></div>
        </div>
    </div>

    <ul class="py-2">
        <?php if ($user_role !== 'editor'): ?>
        <!-- Sadece admin görecek -->
        <li class="px-4 py-3 hover:bg-gray-700 rounded-md mx-2 <?php echo $active_page == 'dashboard' ? 'active-menu' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/?page=dashboard" class="flex items-center text-gray-300 hover:text-white">
                <i class="fas fa-tachometer-alt mr-3"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>

        <li class="px-4 py-3 hover:bg-gray-700 rounded-md mx-2 <?php echo $active_page == 'uyeler' ? 'active-menu' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/?page=uyeler" class="flex items-center text-gray-300 hover:text-white">
                <i class="fas fa-users mr-3"></i>
                <span class="nav-text">Üyeler</span>
            </a>
        </li>

        <li class="dropdown px-4 py-3 hover:bg-gray-700 rounded-md mx-2 <?php echo in_array($active_page, ['paket-ekle', 'paket-duzenle']) ? 'active-menu' : ''; ?>">
            <a href="#" class="flex items-center justify-between text-gray-300 hover:text-white">
                <div class="flex items-center">
                    <i class="fas fa-box-open mr-3"></i>
                    <span class="nav-text">Paketler</span>
                </div>
                <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <ul class="dropdown-content pl-8 mt-2">
                <li class="py-2 hover:bg-gray-700 rounded-md px-2">
                    <a href="<?php echo BASE_URL; ?>/?page=paket-ekle" class="flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-plus-circle mr-2"></i>
                        <span class="nav-text">Paket Ekle</span>
                    </a>
                </li>
                <li class="py-2 hover:bg-gray-700 rounded-md px-2">
                    <a href="<?php echo BASE_URL; ?>/?page=paket-duzenle" class="flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-edit mr-2"></i>
                        <span class="nav-text">Paket Düzenle</span>
                    </a>
                </li>
            </ul>
        </li>

        <li class="px-4 py-3 hover:bg-gray-700 rounded-md mx-2 <?php echo $active_page == 'abonelikler' ? 'active-menu' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/?page=abonelikler" class="flex items-center text-gray-300 hover:text-white">
                <i class="fas fa-calendar-check mr-3"></i>
                <span class="nav-text">Abonelikler</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Herkes görebilir: İçerik Yönetimi -->
        <li class="dropdown px-4 py-3 hover:bg-gray-700 rounded-md mx-2 <?php echo in_array($active_page, ['icerik-olustur', 'icerik-duzenle', 'icerik-sil']) ? 'active-menu' : ''; ?>">
            <a href="#" class="flex items-center justify-between text-gray-300 hover:text-white">
                <div class="flex items-center">
                    <i class="fas fa-file-alt mr-3"></i>
                    <span class="nav-text">İçerik Yönetimi</span>
                </div>
                <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <ul class="dropdown-content pl-8 mt-2">
                <li class="py-2 hover:bg-gray-700 rounded-md px-2">
                    <a href="<?php echo BASE_URL; ?>/?page=icerik-olustur" class="flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-plus mr-2"></i>
                        <span class="nav-text">İçerik Oluştur</span>
                    </a>
                </li>
                <li class="py-2 hover:bg-gray-700 rounded-md px-2">
                    <a href="<?php echo BASE_URL; ?>/?page=icerik-duzenle" class="flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-edit mr-2"></i>
                        <span class="nav-text">İçerik Düzenle</span>
                    </a>
                </li>
                <li class="py-2 hover:bg-gray-700 rounded-md px-2">
                    <a href="<?php echo BASE_URL; ?>/?page=icerik-sil" class="flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-trash mr-2"></i>
                        <span class="nav-text">İçerik Sil</span>
                    </a>
                </li>
            </ul>
        </li>

        <?php if ($user_role !== 'editor'): ?>
        <li class="px-4 py-3 hover:bg-gray-700 rounded-md mx-2 <?php echo $active_page == 'ayarlar' ? 'active-menu' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/?page=ayarlar" class="flex items-center text-gray-300 hover:text-white">
                <i class="fas fa-cog mr-3"></i>
                <span class="nav-text">Ayarlar</span>
            </a>
        </li>

        <li class="px-4 py-3 hover:bg-gray-700 rounded-md mx-2 <?php echo $active_page == 'sistem-bilgi' ? 'active-menu' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/?page=sistem-bilgi" class="flex items-center text-gray-300 hover:text-white">
                <i class="fas fa-info-circle mr-3"></i>
                <span class="nav-text">Sistem Bilgisi</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>

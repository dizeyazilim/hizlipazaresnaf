<?php
try {
    // Fetch stats
    $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $active_posts = $db->query("SELECT COUNT(*) FROM posts WHERE visible_until >= NOW()")->fetchColumn();
    $active_subscriptions = $db->query("SELECT COUNT(*) FROM users WHERE payment_status = 1 AND subscription_end >= NOW()")->fetchColumn();
    $total_income = $db->query("SELECT SUM(p.price) FROM packages p JOIN users u ON p.id = u.package_id WHERE u.payment_status = 1 AND u.subscription_end >= NOW()")->fetchColumn() ?? 0.0;

    // Fetch recent members
    $recent_members = $db->query("SELECT id, name, email, created_at, payment_status FROM users ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recent activities
    $recent_activities = $db->query("
        SELECT 'Yeni üye kaydı' AS type, CONCAT(name, ' platforma kaydoldu') AS description, created_at
        FROM users
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION
        SELECT 'Yeni ilan eklendi' AS type, CONCAT(title, ' eklendi') AS description, visible_from AS created_at
        FROM posts
        WHERE visible_from > DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION
        SELECT 'Abonelik yenilendi' AS type, CONCAT(name, ' aboneliğini yeniledi') AS description, subscription_start AS created_at
        FROM users
        WHERE subscription_start > DATE_SUB(NOW(), INTERVAL 7 DAY) AND payment_status = 1
        ORDER BY created_at DESC
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recent posts (vehicles)
    $recent_vehicles = $db->query("
        SELECT p.id, p.title, p.description, p.visible_from AS created_at, pi.image_url
        FROM posts p
        LEFT JOIN post_images pi ON p.id = pi.post_id
        WHERE p.visible_until >= NOW()
        GROUP BY p.id
        ORDER BY p.visible_from DESC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("dashboard.php: Database error: " . $e->getMessage());
    $error_message = "Veritabanı hatası: Veriler yüklenemedi.";
    $total_users = $active_posts = $active_subscriptions = $total_income = 0;
    $recent_members = $recent_activities = $recent_vehicles = [];
}
?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800">Dashboard</h2>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Toplam Üye</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($total_users); ?></h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-users text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Aktif İlan</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($active_posts); ?></h3>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-car text-green-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Aktif Abonelik</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($active_subscriptions); ?></h3>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-calendar-check text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Toplam Gelir</p>
                    <h3 class="text-2xl font-bold">₺<?php echo number_format($total_income, 2); ?></h3>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-lira-sign text-yellow-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Members -->
        <div class="bg-white rounded-lg shadow col-span-2">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Son Eklenen Üyeler</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Üye</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kayıt Tarihi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksiyon</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_members as $member): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full" src="https://img.icons8.com/3d-fluency/94/person-male--v3.png" alt="">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($member['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($member['email']); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($member['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $member['payment_status'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $member['payment_status'] ? 'Aktif' : 'Beklemede'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?php echo BASE_URL; ?>/dashboard/?page=uyeler&user_id=<?php echo htmlspecialchars($member['id']); ?>" class="text-blue-600 hover:text-blue-900">Detay</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Son Aktivite</h2>
            </div>
            <div class="p-4">
                <div class="space-y-4">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full <?php echo $activity['type'] == 'Yeni üye kaydı' ? 'bg-blue-100' : ($activity['type'] == 'Yeni ilan eklendi' ? 'bg-green-100' : 'bg-purple-100'); ?> flex items-center justify-center">
                                <i class="fas <?php echo $activity['type'] == 'Yeni üye kaydı' ? 'fa-user-plus text-blue-500' : ($activity['type'] == 'Yeni ilan eklendi' ? 'fa-car text-green-500' : 'fa-credit-card text-purple-500'); ?>"></i>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($activity['type']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($activity['description']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Vehicles -->
    <div class="bg-white rounded-lg shadow mt-6">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Son Eklenen Araçlar</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
            <?php foreach ($recent_vehicles as $vehicle): ?>
            <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                <img src="<?php echo htmlspecialchars($vehicle['image_url'] ?? 'https://placehold.co/600x400/EEE/31343C?font=source-sans-pro&text=Resim Eklenmedi'); ?>" alt="Araç" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($vehicle['title']); ?></h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($vehicle['description']); ?></p>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($vehicle['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
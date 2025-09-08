<?php
$user_id = intval($_GET['user_id'] ?? 0);
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    // Fetch all packages for dropdown
    $packages = $db->query("SELECT id, name FROM packages ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    if ($user_id > 0) {
        // Fetch single user details
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.role, u.payment_status, u.created_at, 
                   u.package_id, u.subscription_start, u.subscription_end, p.name AS package_name
            FROM users u
            LEFT JOIN packages p ON u.package_id = p.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch user's posts
        $stmt = $db->prepare("
            SELECT p.id, p.title, p.description, p.phone_number, p.visible_from, p.visible_until, 
                   GROUP_CONCAT(pi.image_url) AS image_urls
            FROM posts p
            LEFT JOIN post_images pi ON p.id = pi.post_id
            WHERE p.created_by = ?
            GROUP BY p.id
            ORDER BY p.visible_from DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process image_urls
        foreach ($user_posts as &$post) {
            $post['image_urls'] = !empty($post['image_urls']) ? explode(',', $post['image_urls']) : [];
        }
    } else {
        // Fetch user list
        $users = $db->query("
            SELECT u.id, u.name, u.email, u.created_at, u.payment_status, u.phone, u.role, u.package_id, 
                   u.subscription_start, u.subscription_end, p.name AS package_name
            FROM users u
            LEFT JOIN packages p ON u.package_id = p.id
            ORDER BY u.created_at DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("uyeler.php: Database error: " . $e->getMessage());
    $error_message = "Veritabanı hatası: Veriler yüklenemedi.";
    $users = $packages = $user = $user_posts = [];
}
?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800"><?php echo $user_id > 0 ? 'Üye Detayı' : 'Üyeler'; ?></h2>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php elseif ($error || isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($error ?: $error_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($user_id > 0): ?>
        <!-- User Details View -->
        <?php if ($user): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-md font-semibold text-gray-800 mb-2">Üye Bilgileri</h3>
                    <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>E-posta:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Telefon:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Yok'); ?></p>
                    <p><strong>Rol:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                    <p><strong>Paket:</strong> <?php echo htmlspecialchars($user['package_name'] ?? 'Yok'); ?></p>
                    <p><strong>Ödeme Durumu:</strong> <?php echo $user['payment_status'] ? 'Aktif' : 'Beklemede'; ?></p>
                    <p><strong>Kayıt Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
                    <p><strong>Abonelik Başlangıcı:</strong> <?php echo $user['subscription_start'] ? date('d.m.Y H:i', strtotime($user['subscription_start'])) : 'Yok'; ?></p>
                    <p><strong>Abonelik Bitişi:</strong> <?php echo $user['subscription_end'] ? date('d.m.Y H:i', strtotime($user['subscription_end'])) : 'Yok'; ?></p>
                    <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Düzenle</button>
                    <a href="<?php echo BASE_URL; ?>/dashboard/?page=uyeler" class="mt-4 ml-2 text-blue-600 hover:text-blue-900">Listeye Geri Dön</a>
                </div>
                <div>
                    <h3 class="text-md font-semibold text-gray-800 mb-2">Kullanıcının İlanları</h3>
                    <?php if (empty($user_posts)): ?>
                        <p class="text-gray-600">Bu kullanıcıya ait ilan bulunmuyor.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($user_posts as $post): ?>
                            <div class="border rounded-lg p-4">
                                <h4 class="font-bold"><?php echo htmlspecialchars($post['title']); ?></h4>
                                <p class="text-gray-600"><?php echo htmlspecialchars($post['description']); ?></p>
                                <p class="text-sm text-gray-500">Telefon: <?php echo htmlspecialchars($post['phone_number'] ?? 'Yok'); ?></p>
                                <p class="text-sm text-gray-500">Yayın: <?php echo date('d.m.Y', strtotime($post['visible_from'])); ?> - <?php echo date('d.m.Y', strtotime($post['visible_until'])); ?></p>
                                <?php if (!empty($post['image_urls'])): ?>
                                    <div class="flex space-x-2 mt-2">
                                        <?php foreach ($post['image_urls'] as $image_url): ?>
                                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="İlan resmi" class="w-24 h-24 object-cover rounded">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <p class="text-gray-600">Kullanıcı bulunamadı.</p>
            <a href="<?php echo BASE_URL; ?>/dashboard/?page=uyeler" class="text-blue-600 hover:text-blue-900">Listeye Geri Dön</a>
        <?php endif; ?>
    <?php else: ?>
        <!-- User List View -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Üye</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kayıt Tarihi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksiyon</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full" src="https://img.icons8.com/3d-fluency/94/person-male--v3.png" alt="">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['package_name'] ?? 'Yok'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['payment_status'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo $user['payment_status'] ? 'Aktif' : 'Beklemede'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="<?php echo BASE_URL; ?>/dashboard/?page=uyeler&user_id=<?php echo htmlspecialchars($user['id']); ?>" class="text-blue-600 hover:text-blue-900 mr-2">Detay</a>
                                <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="text-blue-600 hover:text-blue-900">Düzenle</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <h2 class="text-lg font-semibold text-gray-800">Üye Düzenle</h2>
        <form id="editForm" action="<?php echo htmlspecialchars(BASE_URL); ?>/actions/edit_user.php" method="POST">
            <input type="hidden" name="user_id" id="user_id">
            <div class="mb-4">
                <label class="block text-gray-700">Ad Soyad</label>
                <input type="text" name="name" id="name" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">E-posta</label>
                <input type="email" name="email" id="email" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Telefon</label>
                <input type="text" name="phone" id="phone" class="w-full border rounded-lg p-2">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Rol</label>
                <select name="role" id="role" class="w-full border rounded-lg p-2" required>
                    <option value="admin">Admin</option>
                    <option value="editor">Editör</option>
                    <option value="member">Üye</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Paket</label>
                <select name="package_id" id="package_id" class="w-full border rounded-lg p-2">
                    <option value="">Yok</option>
                    <?php foreach ($packages as $package): ?>
                        <option value="<?php echo $package['id']; ?>"><?php echo htmlspecialchars($package['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Ödeme Durumu</label>
                <select name="payment_status" id="payment_status" class="w-full border rounded-lg p-2" required>
                    <option value="1">Aktif</option>
                    <option value="0">Beklemede</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Abonelik Başlangıcı</label>
                <input type="datetime-local" name="subscription_start" id="subscription_start" class="w-full border rounded-lg p-2">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Abonelik Bitişi</label>
                <input type="datetime-local" name="subscription_end" id="subscription_end" class="w-full border rounded-lg p-2">
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg mr-2">İptal</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function showEditModal(user) {
    document.getElementById('user_id').value = user.id;
    document.getElementById('name').value = user.name || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('role').value = user.role || 'member';
    document.getElementById('package_id').value = user.package_id || '';
    document.getElementById('payment_status').value = user.payment_status ? 1 : 0;
    document.getElementById('subscription_start').value = user.subscription_start ? user.subscription_start.replace(' ', 'T').substring(0, 16) : '';
    document.getElementById('subscription_end').value = user.subscription_end ? user.subscription_end.replace(' ', 'T').substring(0, 16) : '';
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
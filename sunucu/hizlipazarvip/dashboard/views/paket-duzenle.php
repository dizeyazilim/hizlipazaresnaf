<?php
try {
    $stmt = $db->query("SELECT id, name, price, duration_days FROM packages ORDER BY id DESC");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("paket-duzenle.php: Database error: " . $e->getMessage());
    $error_message = "Veritabanı hatası: Paketler yüklenemedi.";
    $packages = [];
}

// Handle success/error messages from edit_package.php
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800">Paket Düzenle</h2>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error || isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($error ?: $error_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($packages)): ?>
        <p class="text-gray-600">Henüz paket bulunmuyor.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paket Adı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fiyat (₺)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Süre (Gün)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksiyon</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($packages as $package): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($package['name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₺<?php echo number_format($package['price'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $package['duration_days']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="showEditModal(<?php echo $package['id']; ?>, '<?php echo htmlspecialchars(addslashes($package['name'])); ?>', <?php echo $package['price']; ?>, <?php echo $package['duration_days']; ?>)" class="text-blue-600 hover:text-blue-900">Düzenle</button>
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
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800">Paket Düzenle</h2>
        <form id="editForm" action="<?php echo htmlspecialchars(BASE_URL); ?>/actions/edit_package.php" method="POST">
            <input type="hidden" name="package_id" id="package_id">
            <div class="mb-4">
                <label class="block text-gray-700">Paket Adı</label>
                <input type="text" name="name" id="name" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Fiyat (₺)</label>
                <input type="number" name="price" id="price" step="0.01" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Süre (Gün)</label>
                <input type="number" name="duration_days" id="duration_days" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg mr-2">İptal</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function showEditModal(id, name, price, duration_days) {
    document.getElementById('package_id').value = id;
    document.getElementById('name').value = name;
    document.getElementById('price').value = price;
    document.getElementById('duration_days').value = duration_days;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800">Paket Ekle</h2>
    <form action="<?php echo BASE_URL; ?>/actions/add_package.php" method="POST">
        <div class="mb-4">
            <label class="block text-gray-700">Paket Adı</label>
            <input type="text" name="name" class="w-full border rounded-lg p-2" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Fiyat (₺)</label>
            <input type="number" name="price" step="0.01" class="w-full border rounded-lg p-2" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Süre (Gün)</label>
            <input type="number" name="duration_days" class="w-full border rounded-lg p-2" required>
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Ekle</button>
    </form>
</div>
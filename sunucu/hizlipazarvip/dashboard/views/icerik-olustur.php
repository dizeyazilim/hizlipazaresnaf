<?php
// Handle success/error messages from create_post.php
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

if ($success) {
    echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>';
}
if ($error) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
}

?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800">İçerik Oluştur</h2>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php elseif ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form id="postForm" action="<?php echo htmlspecialchars(BASE_URL); ?>/create_post.php" method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-gray-700">Başlık</label>
            <input type="text" name="title" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Açıklama</label>
            <textarea name="description" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" rows="4" required></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Telefon Numarası</label>
            <input type="text" name="phone_number" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Başlangıç Tarihi</label>
            <input type="datetime-local" name="visible_from" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Bitiş Tarihi</label>
            <input type="datetime-local" name="visible_until" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Resimler</label>
            <input type="file" id="imageInput" name="images[]" multiple class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" accept="image/*" required>
            <div id="imagePreview" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4"></div>
        </div>
        <input type="hidden" name="created_by" value="<?php echo $_SESSION['user_id']; ?>">
        <button type="submit" class="bg-gradient-to-r from-[#1E40AF] to-[#3B82F6] text-white px-4 py-2 rounded-lg hover:transform hover:-translate-y-1 hover:shadow-lg transition-all duration-300">Ekle</button>
    </form>
</div>

<script>
document.getElementById('imageInput').addEventListener('change', function(event) {
    const previewContainer = document.getElementById('imagePreview');
    previewContainer.innerHTML = ''; // Clear previous previews

    const files = event.target.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) {
            alert('Lütfen yalnızca resim dosyaları seçin.');
            continue;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const imgContainer = document.createElement('div');
            imgContainer.className = 'relative';
            imgContainer.innerHTML = `
                <img src="${e.target.result}" class="w-24 h-24 object-cover rounded-lg shadow-md">
                <button type="button" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600" onclick="this.parentElement.remove()">×</button>
            `;
            previewContainer.appendChild(imgContainer);
        };
        reader.readAsDataURL(file);
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
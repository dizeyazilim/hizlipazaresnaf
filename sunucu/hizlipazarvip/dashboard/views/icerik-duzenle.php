```php
<?php
// Use absolute path to ensure correct inclusion
require_once $_SERVER['DOCUMENT_ROOT'] . '/hizlipazarvip/dashboard/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/hizlipazarvip/dashboard/db.php';

// Set timezone
date_default_timezone_set('Europe/Istanbul');

// Check user permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor'])) {
    error_log("icerik-duzenle.php: Unauthorized access attempt by user_id=" . ($_SESSION['user_id'] ?? 'none'));
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Pagination
$perPage = 10;
$page = isset($_GET['page_number']) ? max(1, intval($_GET['page_number'])) : 1;
$offset = ($page - 1) * $perPage;

try {
    // Count total posts
    $totalStmt = $db->query("SELECT COUNT(*) FROM posts");
    $totalPosts = $totalStmt->fetchColumn();
    error_log("icerik-duzenle.php: Total posts counted: $totalPosts");
    $totalPages = ceil($totalPosts / $perPage);

    // Fetch posts with pagination
    $stmt = $db->prepare("
        SELECT p.id, p.title, p.description, p.phone_number, p.visible_from, p.visible_until, 
               GROUP_CONCAT(pi.image_url) AS image_urls
        FROM posts p
        LEFT JOIN post_images pi ON p.id = pi.post_id
        GROUP BY p.id
        ORDER BY p.visible_from DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log fetched posts
    error_log("icerik-duzenle.php: Fetched posts: " . json_encode($posts));

    // Process image_urls and ensure no duplication
    $unique_posts = [];
    foreach ($posts as &$post) {
        $post['image_urls'] = !empty($post['image_urls']) ? array_unique(explode(',', $post['image_urls'])) : [];
        $unique_posts[$post['id']] = $post; // Ensure unique posts by ID
    }
    $posts = array_values($unique_posts);
} catch (PDOException $e) {
    error_log("icerik-duzenle.php: Database error: " . $e->getMessage());
    $posts = [];
    $totalPages = 1;
}

// Handle success/error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İçerik Düzenle - Hızlı Pazar Esnaf</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">İçerik Düzenle</h2>

            <div id="alertContainer" class="mb-6">
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php elseif ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Başlık</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bitiş Tarihi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resimler</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksiyon</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Gönderi bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($post['title'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($post['phone_number'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo isset($post['visible_until']) ? date('d.m.Y H:i', strtotime($post['visible_until'])) : ''; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if (!empty($post['image_urls'])): ?>
                                            <div class="flex space-x-2">
                                                <?php foreach ($post['image_urls'] as $image_url): ?>
                                                    <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Post Image" class="w-12 h-12 object-cover rounded">
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            Resim yok
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick='showEditPostModal(<?php echo json_encode($post); ?>)' class="text-[#25D366] hover:text-[#128C7E] transition-colors">Düzenle</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-4 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Toplam <?php echo $totalPosts; ?> gönderi, Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=icerik-duzenle&page_number=<?php echo $page - 1; ?>" class="px-3 py-1 bg-[#128C7E] text-white rounded-lg hover:bg-[#25D366] transition-colors">Önceki</a>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=icerik-duzenle&page_number=<?php echo $i; ?>" class="px-3 py-1 <?php echo $i == $page ? 'bg-[#25D366] text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-lg hover:bg-[#128C7E] hover:text-white transition-colors"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=icerik-duzenle&page_number=<?php echo $page + 1; ?>" class="px-3 py-1 bg-[#128C7E] text-white rounded-lg hover:bg-[#25D366] transition-colors">Sonraki</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Post Modal -->
        <div id="editPostModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">İçerik Düzenle</h2>
                <div id="formAlert" class="hidden mb-4"></div>
                <form id="editPostForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="post_id" id="post_id">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium">Başlık</label>
                        <input type="text" name="title" id="post_title" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium">Açıklama</label>
                        <textarea name="description" id="post_description" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" rows="4" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium">Telefon Numarası</label>
                        <input type="text" name="phone_number" id="post_phone_number" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" required pattern="^(\+90|0)?5[0-9]{9}$" title="Geçerli bir telefon numarası girin (örn: +905123456789 veya 05123456789)">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium">Başlangıç Tarihi</label>
                        <input type="datetime-local" name="visible_from" id="post_visible_from" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium">Bitiş Tarihi</label>
                        <input type="datetime-local" name="visible_until" id="post_visible_until" class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium">Mevcut Resimler</label>
                        <div id="existingImages" class="grid grid-cols-3 gap-2 mb-2"></div>
                        <input type="hidden" name="existing_images" id="existing_images">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium">Yeni Resimler</label>
                        <input type="file" name="images[]" id="newImages" multiple class="w-full border rounded-lg p-2 focus:border-[#10B981] focus:ring-2 focus:ring-[#10B981]/20" accept="image/*">
                        <div id="newImagePreview" class="grid grid-cols-3 gap-2 mt-2"></div>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closeEditPostModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg mr-2 hover:bg-gray-400 transition-colors">İptal</button>
                        <button type="submit" class="bg-gradient-to-r from-[#1E40AF] to-[#3B82F6] text-white px-4 py-2 rounded-lg hover:transform hover:-translate-y-1 hover:shadow-lg transition-all duration-300">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showEditPostModal(post) {
        console.log('Opening modal with post:', post);
        if (!post || !post.id) {
            alert('Hata: Geçersiz gönderi verisi');
            return;
        }
        document.getElementById('post_id').value = post.id || '';
        document.getElementById('post_title').value = post.title || '';
        document.getElementById('post_description').value = post.description || '';
        const phoneNumber = post.phone_number || '';
        document.getElementById('post_phone_number').value = phoneNumber.startsWith('+90') || phoneNumber.startsWith('0') ? phoneNumber : '+90' + phoneNumber;
        document.getElementById('post_visible_from').value = post.visible_from ? post.visible_from.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('post_visible_until').value = post.visible_until ? post.visible_until.replace(' ', 'T').substring(0, 16) : '';

        // Clear previous alerts
        const formAlert = document.getElementById('formAlert');
        formAlert.innerHTML = '';
        formAlert.classList.add('hidden');

        // Populate existing images
        const existingImagesDiv = document.getElementById('existingImages');
        existingImagesDiv.innerHTML = '';
        const existingImages = post.image_urls || [];
        existingImages.forEach((url, index) => {
            const imgContainer = document.createElement('div');
            imgContainer.className = 'relative';
            imgContainer.innerHTML = `
                <img src="${url}" class="w-20 h-20 object-cover rounded-lg shadow-md">
                <button type="button" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600" onclick="removeImage(${index})">×</button>
            `;
            existingImagesDiv.appendChild(imgContainer);
        });
        document.getElementById('existing_images').value = JSON.stringify(existingImages);

        // Clear new image preview
        document.getElementById('newImagePreview').innerHTML = '';
        document.getElementById('newImages').value = '';

        document.getElementById('editPostModal').classList.remove('hidden');
    }

    function removeImage(index) {
        console.log('Removing image at index:', index);
        const existingImagesInput = document.getElementById('existing_images');
        let existingImages = JSON.parse(existingImagesInput.value || '[]');
        existingImages.splice(index, 1);
        existingImagesInput.value = JSON.stringify(existingImages);

        // Update image display
        const existingImagesDiv = document.getElementById('existingImages');
        existingImagesDiv.innerHTML = '';
        existingImages.forEach((url, idx) => {
            const imgContainer = document.createElement('div');
            imgContainer.className = 'relative';
            imgContainer.innerHTML = `
                <img src="${url}" class="w-20 h-20 object-cover rounded-lg shadow-md">
                <button type="button" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600" onclick="removeImage(${idx})">×</button>
            `;
            existingImagesDiv.appendChild(imgContainer);
        });
    }

    function closeEditPostModal() {
        console.log('Closing modal');
        document.getElementById('editPostModal').classList.add('hidden');
        document.getElementById('editPostForm').reset();
        document.getElementById('formAlert').innerHTML = '';
        document.getElementById('formAlert').classList.add('hidden');
        document.getElementById('existingImages').innerHTML = '';
        document.getElementById('newImagePreview').innerHTML = '';
    }

    document.getElementById('newImages').addEventListener('change', function(event) {
        console.log('New images selected:', event.target.files);
        const previewContainer = document.getElementById('newImagePreview');
        previewContainer.innerHTML = '';
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
                    <img src="${e.target.result}" class="w-20 h-20 object-cover rounded-lg shadow-md">
                    <button type="button" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600" onclick="this.parentElement.remove()">×</button>
                `;
                previewContainer.appendChild(imgContainer);
            };
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('editPostForm').addEventListener('submit', function(event) {
        event.preventDefault();
        console.log('Submitting form');
        const form = this;
        const formData = new FormData(form);

        // Log form data for debugging
        const formDataEntries = {};
        for (const [key, value] of formData.entries()) {
            formDataEntries[key] = value instanceof File ? value.name : value;
        }
        console.log('Form data:', formDataEntries);

        // Client-side validation for phone number
        const phoneNumber = formData.get('phone_number');
        if (!phoneNumber.match(/^(\+90|0)?5[0-9]{9}$/)) {
            const alertContainer = document.getElementById('formAlert');
            alertContainer.classList.remove('hidden');
            alertContainer.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg';
            alertContainer.innerHTML = 'Hata: Geçerli bir telefon numarası girin (örn: +905123456789 veya 05123456789)';
            return;
        }

        const alertContainer = document.getElementById('formAlert');

        fetch('<?php echo rtrim(BASE_URL, '/'); ?>/update_post.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Request URL:', response.url);
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text.trim());
                alertContainer.classList.remove('hidden');
                if (data.success) {
                    alertContainer.className = 'bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg';
                    alertContainer.innerHTML = data.message;
                    setTimeout(() => {
                        closeEditPostModal();
                        window.location.reload();
                    }, 2000);
                } else {
                    alertContainer.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg';
                    alertContainer.innerHTML = data.message;
                }
            } catch (e) {
                console.error('JSON parse error:', e, 'Raw text:', text);
                alertContainer.classList.remove('hidden');
                alertContainer.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg';
                alertContainer.innerHTML = 'Hata: Sunucudan geçersiz yanıt alındı';
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            alertContainer.classList.remove('hidden');
            alertContainer.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg';
            alertContainer.innerHTML = 'Hata: ' + error.message;
        });
    });

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>
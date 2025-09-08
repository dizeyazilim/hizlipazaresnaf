<?php
// Use absolute path to ensure correct inclusion
require_once $_SERVER['DOCUMENT_ROOT'] . '/hizlipazarvip/dashboard/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/hizlipazarvip/dashboard/db.php';

// Set timezone
date_default_timezone_set('Europe/Istanbul');

// Check user permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor'])) {
    error_log("icerik-sil.php: Unauthorized access attempt by user_id=" . ($_SESSION['user_id'] ?? 'none'));
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
    error_log("icerik-sil.php: Total posts counted: $totalPosts");
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
    error_log("icerik-sil.php: Fetched posts: " . json_encode($posts));

    // Process image_urls and ensure no duplication
    $unique_posts = [];
    foreach ($posts as &$post) {
        $post['image_urls'] = !empty($post['image_urls']) ? array_unique(explode(',', $post['image_urls'])) : [];
        $unique_posts[$post['id']] = $post;
    }
    $posts = array_values($unique_posts);
} catch (PDOException $e) {
    error_log("icerik-sil.php: Database error: " . $e->getMessage());
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
    <title>İçerik Sil - Hızlı Pazar Esnaf</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">İçerik Sil</h2>

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
                                <tr data-post-id="<?php echo $post['id']; ?>">
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
                                        <button onclick="deletePost(<?php echo $post['id']; ?>)" class="text-red-600 hover:text-red-900 transition-colors">Sil</button>
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
                            <a href="?page=icerik-sil&page_number=<?php echo $page - 1; ?>" class="px-3 py-1 bg-[#128C7E] text-white rounded-lg hover:bg-[#25D366] transition-colors">Önceki</a>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=icerik-sil&page_number=<?php echo $i; ?>" class="px-3 py-1 <?php echo $i == $page ? 'bg-[#25D366] text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-lg hover:bg-[#128C7E] hover:text-white transition-colors"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=icerik-sil&page_number=<?php echo $page + 1; ?>" class="px-3 py-1 bg-[#128C7E] text-white rounded-lg hover:bg-[#25D366] transition-colors">Sonraki</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function deletePost(postId) {
        if (!confirm('Bu içeriği silmek istediğinize emin misiniz?')) return;

        const formData = new FormData();
        formData.append('post_id', postId);

        // Log form data for debugging
        const formDataEntries = {};
        for (const [key, value] of formData.entries()) {
            formDataEntries[key] = value;
        }
        console.log('Delete form data:', formDataEntries);

        const alertContainer = document.getElementById('alertContainer');

        fetch('<?php echo rtrim(BASE_URL, '/'); ?>/delete_post.php', {
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
                    alertContainer.className = 'bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-6';
                    alertContainer.innerHTML = data.message;
                    // Remove the deleted post from the table
                    const row = document.querySelector(`tr[data-post-id="${postId}"]`);
                    if (row) row.remove();
                    setTimeout(() => {
                        alertContainer.classList.add('hidden');
                        window.location.reload();
                    }, 2000);
                } else {
                    alertContainer.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6';
                    alertContainer.innerHTML = data.message;
                }
            } catch (e) {
                console.error('JSON parse error:', e, 'Raw text:', text);
                alertContainer.classList.remove('hidden');
                alertContainer.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6';
                alertContainer.innerHTML = 'Hata: Sunucudan geçersiz yanıt alındı';
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            alertContainer.classList.remove('hidden');
            alertContainer.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6';
            alertContainer.innerHTML = 'Hata: ' + error.message;
        });
    }

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>
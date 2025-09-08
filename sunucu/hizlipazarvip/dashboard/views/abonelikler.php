<?php
$subscriptions = $db->query("
    SELECT u.id, u.name, u.email, u.subscription_start, u.subscription_end, u.payment_status, p.name AS package_name
    FROM users u
    LEFT JOIN packages p ON u.package_id = p.id
    WHERE u.payment_status = 1 AND u.subscription_end >= NOW()
    ORDER BY u.subscription_end DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800">Aktif Abonelikler</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Üye</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paket</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Başlangıç</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bitiş</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksiyon</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($subscriptions as $sub): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <img class="h-10 w-10 rounded-full" src="https://via.placeholder.com/40" alt="">
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sub['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($sub['email']); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($sub['package_name'] ?? 'Yok'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($sub['subscription_start'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($sub['subscription_end'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="<?php echo BASE_URL; ?>/?page=uyeler&user_id=<?php echo htmlspecialchars($sub['id']); ?>" class="text-blue-600 hover:text-blue-900">Detay</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
try {
    // Fetch PayTR settings (assume single row)
    $stmt = $db->query("SELECT * FROM paytr_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        $settings = [
            'id' => 0,
            'merchant_id' => '',
            'merchant_key' => '',
            'merchant_salt' => '',
            'merchant_ok_url' => '',
            'merchant_fail_url' => '',
            'callback_url' => '',
            'test_mode' => 0,
            'non_3d' => 0,
            'is_active' => 0
        ];
    }

    // Fetch IBAN settings (assume single row for simplicity)
    $stmt_iban = $db->query("SELECT * FROM iban_settings WHERE id = 1");
    $iban_settings = $stmt_iban->fetch(PDO::FETCH_ASSOC);
    if (!$iban_settings) {
        $iban_settings = [
            'id' => 0,
            'iban' => '',
            'bank_name' => '',
            'account_holder' => ''
        ];
    }
} catch (PDOException $e) {
    error_log("ayarlar.php: Database error: " . $e->getMessage());
    $error_message = "Veritabanı hatası: Ayarlar yüklenemedi.";
    $settings = [];
    $iban_settings = [];
}

// Handle success/error messages from edit_paytr_settings.php or edit_iban_settings.php
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800">Ayarlar</h2>
    
    <!-- Dynamic message display -->
    <div id="toggle-message" class="hidden mb-6">
        <div id="toggle-message-content" class="px-4 py-3 rounded-lg"></div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php elseif ($error || isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($error ?: $error_message); ?>
        </div>
    <?php endif; ?>

    <!-- PayTR Settings -->
    <div class="mb-6">
        <h3 class="text-md font-semibold text-gray-800 mb-2">PayTR Ödeme Ayarları</h3>
        <?php if ($settings): ?>
            <div class="flex items-center mb-4">
                <form id="togglePaytrForm" onsubmit="event.preventDefault(); togglePaytrStatus();">
                    <input type="hidden" name="id" id="paytr_id" value="<?php echo $settings['id'] ?? 0; ?>">
                    <input type="hidden" name="is_active" id="is_active_toggle" value="<?php echo $settings['is_active'] ? 0 : 1; ?>">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="paytr_toggle" class="sr-only peer" <?php echo $settings['is_active'] ? 'checked' : ''; ?> onchange="togglePaytrStatus()">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900"><?php echo $settings['is_active'] ? 'Aktif' : 'Deaktif'; ?></span>
                    </label>
                </form>
            </div>
            <div id="paytr-details" class="grid grid-cols-1 md:grid-cols-2 gap-4 <?php echo $settings['is_active'] ? '' : 'hidden'; ?>">
                <?php if ($settings['is_active']): ?>
                    <p><strong>Merchant ID:</strong> <?php echo htmlspecialchars($settings['merchant_id']); ?></p>
                    <p><strong>Merchant Key:</strong> <?php echo htmlspecialchars(substr($settings['merchant_key'], 0, 5) . '***'); ?></p>
                    <p><strong>Merchant Salt:</strong> <?php echo htmlspecialchars(substr($settings['merchant_salt'], 0, 5) . '***'); ?></p>
                    <p><strong>Başarılı Ödeme URL:</strong> <?php echo htmlspecialchars($settings['merchant_ok_url']); ?></p>
                    <p><strong>Başarısız Ödeme URL:</strong> <?php echo htmlspecialchars($settings['merchant_fail_url']); ?></p>
                    <p><strong>Geri Çağırma URL:</strong> <?php echo htmlspecialchars($settings['callback_url']); ?></p>
                    <p><strong>Test Modu:</strong> <?php echo $settings['test_mode'] ? 'Açık' : 'Kapalı'; ?></p>
                    <p><strong>3D Ödeme:</strong> <?php echo $settings['non_3d'] ? 'Kapalı' : 'Açık'; ?></p>
                <?php endif; ?>
            </div>
            <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($settings)); ?>)" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Düzenle</button>
        <?php else: ?>
            <p class="text-gray-600">PayTR ayarları bulunamadı.</p>
        <?php endif; ?>
    </div>

    <!-- IBAN Settings -->
    <div class="mb-6">
        <h3 class="text-md font-semibold text-gray-800 mb-2">IBAN Bilgileri</h3>
        <?php if ($iban_settings): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <p><strong>IBAN:</strong> <?php echo htmlspecialchars($iban_settings['iban']); ?></p>
                <p><strong>Banka Adı:</strong> <?php echo htmlspecialchars($iban_settings['bank_name']); ?></p>
                <p><strong>Hesap Sahibi:</strong> <?php echo htmlspecialchars($iban_settings['account_holder']); ?></p>
            </div>
            <button onclick="showIbanEditModal(<?php echo htmlspecialchars(json_encode($iban_settings)); ?>)" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Düzenle</button>
        <?php else: ?>
            <p class="text-gray-600">IBAN bilgileri bulunamadı.</p>
        <?php endif; ?>
    </div>
</div>

<!-- PayTR Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <h2 class="text-lg font-semibold text-gray-800">PayTR Ayarlarını Düzenle</h2>
        <form id="editForm" action="<?php echo htmlspecialchars(BASE_URL); ?>/actions/edit_paytr_settings.php" method="POST">
            <input type="hidden" name="id" id="id" value="<?php echo $settings['id'] ?? 0; ?>">
            <div class="mb-4">
                <label class="block text-gray-700">PayTR Durumu</label>
                <select name="is_active" id="is_active" class="w-full border rounded-lg p-2" required>
                    <option value="1">Aktif</option>
                    <option value="0">Deaktif</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Merchant ID</label>
                <input type="text" name="merchant_id" id="merchant_id" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Merchant Key</label>
                <input type="text" name="merchant_key" id="merchant_key" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Merchant Salt</label>
                <input type="text" name="merchant_salt" id="merchant_salt" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Başarılı Ödeme URL</label>
                <input type="url" name="merchant_ok_url" id="merchant_ok_url" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Başarısız Ödeme URL</label>
                <input type="url" name="merchant_fail_url" id="merchant_fail_url" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Geri Çağırma URL</label>
                <input type="url" name="callback_url" id="callback_url" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Test Modu</label>
                <select name="test_mode" id="test_mode" class="w-full border rounded-lg p-2" required>
                    <option value="1">Açık</option>
                    <option value="0">Kapalı</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">3D Ödeme</label>
                <select name="non_3d" id="non_3d" class="w-full border rounded-lg p-2" required>
                    <option value="0">Açık</option>
                    <option value="1">Kapalı</option>
                </select>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg mr-2">İptal</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- IBAN Edit Modal -->
<div id="ibanEditModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <h2 class="text-lg font-semibold text-gray-800">IBAN Bilgilerini Düzenle</h2>
        <form id="ibanEditForm" action="<?php echo htmlspecialchars(BASE_URL); ?>/actions/edit_iban_settings.php" method="POST">
            <input type="hidden" name="id" id="iban_id" value="<?php echo $iban_settings['id'] ?? 0; ?>">
            <div class="mb-4">
                <label class="block text-gray-700">IBAN</label>
                <input type="text" name="iban" id="iban" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Banka Adı</label>
                <input type="text" name="bank_name" id="bank_name" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Hesap Sahibi</label>
                <input type="text" name="account_holder" id="account_holder" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeIbanEditModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg mr-2">İptal</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function showEditModal(settings) {
    console.log("Opening PayTR edit modal", settings); // Debug
    document.getElementById('id').value = settings.id || 0;
    document.getElementById('is_active').value = settings.is_active ? 1 : 0;
    document.getElementById('merchant_id').value = settings.merchant_id || '';
    document.getElementById('merchant_key').value = settings.merchant_key || '';
    document.getElementById('merchant_salt').value = settings.merchant_salt || '';
    document.getElementById('merchant_ok_url').value = settings.merchant_ok_url || '';
    document.getElementById('merchant_fail_url').value = settings.merchant_fail_url || '';
    document.getElementById('callback_url').value = settings.callback_url || '';
    document.getElementById('test_mode').value = settings.test_mode ? 1 : 0;
    document.getElementById('non_3d').value = settings.non_3d ? 1 : 0;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    console.log("Closing PayTR edit modal"); // Debug
    document.getElementById('editModal').classList.add('hidden');
}

function showIbanEditModal(ibanSettings) {
    console.log("Opening IBAN edit modal", ibanSettings); // Debug
    document.getElementById('iban_id').value = ibanSettings.id || 0;
    document.getElementById('iban').value = ibanSettings.iban || '';
    document.getElementById('bank_name').value = ibanSettings.bank_name || '';
    document.getElementById('account_holder').value = ibanSettings.account_holder || '';
    document.getElementById('ibanEditModal').classList.remove('hidden');
}

function closeIbanEditModal() {
    console.log("Closing IBAN edit modal"); // Debug
    document.getElementById('ibanEditModal').classList.add('hidden');
}

function togglePaytrStatus() {
    console.log("Starting togglePaytrStatus"); // Debug
    const checkbox = document.getElementById('paytr_toggle');
    const isActiveInput = document.getElementById('is_active_toggle');
    const label = checkbox.nextElementSibling.querySelector('span');
    const messageDiv = document.getElementById('toggle-message');
    const messageContent = document.getElementById('toggle-message-content');
    
    isActiveInput.value = checkbox.checked ? 1 : 0;
    label.textContent = checkbox.checked ? 'Aktif' : 'Deaktif';
    console.log("is_active set to: " + isActiveInput.value); // Debug
    
    const formData = new FormData(document.getElementById('togglePaytrForm'));
    console.log("Form data: ", Object.fromEntries(formData)); // Debug
    
    fetch('<?php echo htmlspecialchars(BASE_URL); ?>/actions/toggle_paytr_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Response status: " + response.status); // Debug
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        console.log("Response data: ", data); // Debug
        messageDiv.classList.remove('hidden');
        if (data.success) {
            messageContent.className = 'bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg';
            messageContent.textContent = data.message;
            // Update UI based on new status
            document.getElementById('paytr-details').classList.toggle('hidden', !checkbox.checked);
        } else {
            messageContent.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg';
            messageContent.textContent = data.message;
            // Revert checkbox and label on error
            checkbox.checked = !checkbox.checked;
            isActiveInput.value = checkbox.checked ? 1 : 0;
            label.textContent = checkbox.checked ? 'Aktif' : 'Deaktif';
        }
        // Hide message after 5 seconds
        setTimeout(() => {
            messageDiv.classList.add('hidden');
        }, 5000);
    })
    .catch(error => {
        console.error('Fetch error:', error); // Debug
        messageDiv.classList.remove('hidden');
        messageContent.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg';
        messageContent.textContent = 'Bir hata oluştu: ' + error.message;
        // Revert checkbox and label on error
        checkbox.checked = !checkbox.checked;
        isActiveInput.value = checkbox.checked ? 1 : 0;
        label.textContent = checkbox.checked ? 'Aktif' : 'Deaktif';
        setTimeout(() => {
            messageDiv.classList.add('hidden');
        }, 5000);
    });
}
</script>
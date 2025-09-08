<?php
require_once 'config.php';
require_once 'db.php';

session_start();

$email_or_phone = trim($_POST['email_or_phone'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']);

// Boş alan kontrolü
if (empty($email_or_phone) || empty($password)) {
    header('Location: ' . BASE_URL . '/login.php?error=Tüm alanlar zorunlu');
    exit;
}

try {
    // Giriş email mi telefon mu?
    $field = filter_var($email_or_phone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

    // Kullanıcı sorgusu
    $stmt = $db->prepare("SELECT id, name, email, phone, role, password FROM users WHERE $field = ?");
    $stmt->execute([$email_or_phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Login attempt: $email_or_phone, " . ($user ? 'User found' : 'User not found'));

    // Kullanıcı bulundu ve şifre doğruysa
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // Session güvenliği

        // Oturum bilgileri
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_image'] = 'https://via.placeholder.com/40'; // Gerçek resim eklenebilir

        // Remember Me çerezi
        if ($remember_me) {
            setcookie('login_email', $email_or_phone, time() + COOKIE_EXPIRE, '/', '', true, true);
        } else {
            setcookie('login_email', '', time() - 3600, '/', '', true, true);
        }

        // ROL bazlı yönlendirme
        if (in_array($user['role'], ['admin', 'editor'])) {
            header('Location: ' . DASHBOARD_URL); // Ortak yönetim paneli
        } else {
            header('Location: ' . BASE_URL . '/user_dashboard.php'); // Diğer rollerin paneli
        }
        exit;

    } else {
        // Hatalı giriş
        header('Location: ' . BASE_URL . '/login.php?error=Geçersiz e-posta/telefon veya şifre');
        exit;
    }

} catch (Exception $e) {
    error_log("Auth error: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/login.php?error=Hata: ' . urlencode($e->getMessage()));
    exit;
}
?>

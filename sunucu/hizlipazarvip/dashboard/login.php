<?php
require_once 'config.php';
require_once 'db.php';
session_start(); // OTURUM BAŞLATMA EKSİKTİ, MUTLAKA OLMALI

// Giriş yaptıysa yönlendir (sadece login sayfasına gelenleri etkiler)
if (
    isset($_SESSION['user_id']) &&
    in_array($_SESSION['user_role'], ['admin', 'editor']) &&
    !isset($_GET['error'])
) {
    header('Location: ' . DASHBOARD_URL);
    exit;
}

$error = $_GET['error'] ?? '';
$email_or_phone = $_COOKIE['login_email'] ?? '';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Esnaf Girişi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/login.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1E40AF',
                        secondary: '#3B82F6',
                        accent: '#60A5FA',
                        dark: '#1F2937',
                        light: '#F9FAFB'
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
        <!-- Sol Taraf - Görsel ve Açıklama -->
        <div class="hidden lg:flex flex-col justify-center space-y-8 p-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-dark mb-4"><?php echo SITE_NAME; ?> Yönetim Paneli</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Hizlipazar olarak, otomotiv satış sektörünü devrim niteliğinde bir platformla dönüştürmeyi taahhüt ediyoruz. 2024 yılında kurulan şirketimiz, kullanıcılarımıza en iyi araç satın alma deneyimini sunmayı misyon edinmiştir. Araç satın almanın önemli bir karar olduğunu biliyor ve süreci olabildiğince sorunsuz ve şeffaf hale getirmeye çalışıyoruz.
                </p>
            </div>
            
           
            
            <div class="mt-10 text-center">
                <p class="text-gray-500 text-sm">
                    <i class="fas fa-lock text-primary mr-2"></i>
                    Bilgileriniz güvenli şekilde saklanmaktadır
                </p>
            </div>
        </div>
        
        <!-- Sağ Taraf - Login Formu -->
        <div class="login-container bg-white">
            <div class="p-8 md:p-12">
                <div class="text-center mb-10">
                    <div class="mx-auto w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mb-4">
                        <img src="https://hizlipazar.vercel.app/images/logo/logo.svg" alt="<?php echo SITE_NAME; ?>" class="w-12 h-12">
                    </div>
                    <h2 class="text-3xl font-bold text-dark">Esnaf Girişi</h2>
                    <p class="text-gray-600 mt-2">Hesabınıza erişmek için giriş yapın</p>
                </div>
                
                <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form id="loginForm" action="<?php echo BASE_URL; ?>/auth.php" method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-posta veya Telefon</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                id="email" 
                                name="email_or_phone"
                                value="<?php echo htmlspecialchars($email_or_phone); ?>"
                                class="input-field w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" 
                                placeholder="ornek@hizlipazar.com veya 55544332211"
                                required
                            >
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-sm font-medium text-gray-700">Şifre</label>
                            <a href="#" class="text-sm text-primary hover:text-secondary">Şifremi Unuttum</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input 
                                type="password" 
                                id="password" 
                                name="password"
                                class="input-field w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" 
                                placeholder="••••••••"
                                required
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" id="togglePassword" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input 
                            id="remember-me" 
                            name="remember_me" 
                            type="checkbox" 
                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        >
                        <label for="remember-me" class="ml-2 block text-sm text-gray-700">
                            Beni hatırla
                        </label>
                    </div>
                    
                    <div>
                        <button 
                            type="submit" 
                            id="submitBtn"
                            class="btn-primary w-full py-3 px-4 rounded-lg text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                        >
                            Giriş Yap
                        </button>
                    </div>
                </form>
                
                
                <!-- Mobil görünüm için açıklama -->
                <div class="lg:hidden mt-10 text-center">
                    <h3 class="text-xl font-bold text-dark mb-4"><?php echo SITE_NAME; ?> Avantajları</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center">
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/login.js"></script>
</body>
</html>
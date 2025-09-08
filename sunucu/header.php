<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hızlı Pazar | Esnaflar için Araç Alım-Satım Platformu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }
        
        .hero-gradient {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .download-btn {
            transition: all 0.3s ease;
        }
        
        .download-btn:hover {
            transform: scale(1.05);
        }
        
        .testimonial-card {
            transition: all 0.3s ease;
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .mobile-mockup {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <i class="fas fa-car text-blue-600 text-2xl mr-2"></i>
                <span class="text-xl font-bold text-blue-600">Hızlı Pazar</span>
            </div>
            <div class="hidden md:flex space-x-8">
                <a href="#features" class="text-gray-700 hover:text-blue-600 font-medium">Özellikler</a>
                <a href="#how-it-works" class="text-gray-700 hover:text-blue-600 font-medium">Nasıl Çalışır?</a>
                <a href="#testimonials" class="text-gray-700 hover:text-blue-600 font-medium">Yorumlar</a>
                <a href="#download" class="text-gray-700 hover:text-blue-600 font-medium">İndir</a>
            </div>
            <button class="md:hidden text-gray-700">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
    </nav>

    <!-- Mobile Menu (hidden by default) -->
    <div id="mobileMenu" class="hidden bg-white shadow-lg absolute w-full z-50">
        <div class="container mx-auto px-4 py-3 flex flex-col space-y-4">
            <a href="#features" class="text-gray-700 hover:text-blue-600 font-medium py-2 border-b">Özellikler</a>
            <a href="#how-it-works" class="text-gray-700 hover:text-blue-600 font-medium py-2 border-b">Nasıl Çalışır?</a>
            <a href="#testimonials" class="text-gray-700 hover:text-blue-600 font-medium py-2 border-b">Yorumlar</a>
            <a href="#download" class="text-gray-700 hover:text-blue-600 font-medium py-2 border-b">İndir</a>
        </div>
    </div>
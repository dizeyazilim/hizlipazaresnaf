document.addEventListener('DOMContentLoaded', function() {
    // Şifre göster/gizle işlevi
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    // Form gönderimi
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    
    loginForm.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        if (!email || !password) {
            e.preventDefault();
            alert('Lütfen tüm alanları doldurunuz.');
            return;
        }
        
        // Gönderme animasyonu
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Giriş yapılıyor...';
        submitBtn.disabled = true;
        
        // Form submits to auth.php, no further JS needed
    });
    
    // Mobil cihazlarda ekstra bilgileri göster
    if (window.innerWidth < 1024) {
        const featureSection = document.querySelector('.lg\\:hidden');
        if (featureSection) {
            featureSection.classList.remove('hidden');
        }
    }
});
// Toggle sidebar
document.querySelector('.menu-toggle').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('sidebar-collapsed');
    document.querySelector('.content').classList.toggle('content-expanded');
    
    const icon = this.querySelector('i');
    if (document.querySelector('.sidebar').classList.contains('sidebar-collapsed')) {
        icon.classList.remove('fa-chevron-left');
        icon.classList.add('fa-chevron-right');
    } else {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-left');
    }
});

// Mobile menu toggle
document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('active');
});

// Menu item active state
const menuItems = document.querySelectorAll('.sidebar ul li a');
menuItems.forEach(item => {
    item.addEventListener('click', function(e) {
        menuItems.forEach(i => {
            i.classList.remove('active-menu');
            i.parentElement.classList.remove('active-menu');
        });
        
        this.classList.add('active-menu');
        this.parentElement.classList.add('active-menu');
        
        if (this.parentElement.classList.contains('dropdown')) {
            e.preventDefault();
        }
    });
});

// Set active menu based on URL
const urlParams = new URLSearchParams(window.location.search);
const currentPage = urlParams.get('page') || 'dashboard';
const activeLink = document.querySelector(`.sidebar a[href*="${currentPage}"]`);
if (activeLink) {
    activeLink.classList.add('active-menu');
    activeLink.parentElement.classList.add('active-menu');
}

// Responsive adjustments
function handleResize() {
    if (window.innerWidth <= 768) {
        document.querySelector('.sidebar').classList.remove('sidebar-collapsed');
        document.querySelector('.content').classList.remove('content-expanded');
    }
}

window.addEventListener('resize', handleResize);
handleResize();
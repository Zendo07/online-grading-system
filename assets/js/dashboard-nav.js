// Dashboard Navigation JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const sidebar = document.getElementById('sidebar');
    
    // Show sidebar on hamburger hover
    if (hamburgerMenu && sidebar) {
        hamburgerMenu.addEventListener('mouseenter', function() {
            sidebar.classList.add('show');
        });
        
        // Keep sidebar open when hovering over it
        sidebar.addEventListener('mouseenter', function() {
            this.classList.add('show');
        });
        
        // Hide sidebar when mouse leaves
        sidebar.addEventListener('mouseleave', function() {
            this.classList.remove('show');
        });
        
        // Also hide when mouse leaves hamburger (if not going to sidebar)
        hamburgerMenu.addEventListener('mouseleave', function(e) {
            setTimeout(() => {
                if (!sidebar.matches(':hover')) {
                    sidebar.classList.remove('show');
                }
            }, 100);
        });
    }
    
    // Mobile toggle
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-show');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !hamburgerMenu.contains(e.target)) {
                sidebar.classList.remove('mobile-show');
            }
        }
    });
    
    // Profile dropdown (keep existing functionality)
    const profileButton = document.querySelector('.profile-button');
    const profileMenu = document.querySelector('.profile-menu');
    
    if (profileButton && profileMenu) {
        // Click to navigate to profile
        profileButton.addEventListener('click', function(e) {
            // If clicking directly on button, go to profile
            if (e.target === this || e.target.tagName === 'IMG') {
                window.location.href = 'profile.php';
            }
        });
    }
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});
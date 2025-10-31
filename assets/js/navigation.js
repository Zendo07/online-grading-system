/**
 * ==========================================
 * MODERN NAVIGATION - JAVASCRIPT
 * ==========================================
 */

class NavigationManager {
  constructor() {
    this.sidebar = document.getElementById('sidebar');
    this.sidebarOverlay = document.getElementById('sidebarOverlay');
    this.hamburgerMenu = document.getElementById('hamburgerMenu');
    this.sidebarTimeout = null;
    this.isMobile = window.innerWidth <= 768;
    
    this.init();
  }

  init() {
    if (!this.sidebar || !this.hamburgerMenu) {
      console.warn('Navigation elements not found');
      return;
    }

    this.setupEventListeners();
    this.setupDropdowns();
    this.setupRippleEffect();
    this.handleResize();
  }

  setupEventListeners() {
    // Hamburger menu click
    this.hamburgerMenu.addEventListener('click', (e) => {
      e.stopPropagation();
      this.toggleSidebar();
      this.addRipple(e);
    });

    // Hamburger hover (desktop only)
    if (!this.isMobile) {
      this.hamburgerMenu.addEventListener('mouseenter', () => {
        this.clearSidebarTimeout();
        this.showSidebar();
      });

      this.sidebar.addEventListener('mouseenter', () => {
        this.clearSidebarTimeout();
      });

      this.sidebar.addEventListener('mouseleave', () => {
        this.startSidebarTimeout();
      });

      this.hamburgerMenu.addEventListener('mouseleave', () => {
        this.startSidebarTimeout();
      });
    }

    // Overlay click
    if (this.sidebarOverlay) {
      this.sidebarOverlay.addEventListener('click', () => {
        this.hideSidebar();
      });
    }

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', (e) => {
      if (this.isMobile && this.sidebar.classList.contains('show')) {
        if (!this.sidebar.contains(e.target) && !this.hamburgerMenu.contains(e.target)) {
          this.hideSidebar();
        }
      }
    });

    // Window resize
    window.addEventListener('resize', () => {
      this.handleResize();
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.sidebar.classList.contains('show')) {
        this.hideSidebar();
      }
    });
  }

  setupDropdowns() {
    const dropdowns = document.querySelectorAll('.nav-dropdown-toggle');
    
    dropdowns.forEach(toggle => {
      toggle.addEventListener('click', (e) => {
        e.preventDefault();
        const parent = toggle.closest('.nav-dropdown');
        const isActive = parent.classList.contains('active');
        
        // Close other dropdowns
        document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
          if (dropdown !== parent) {
            dropdown.classList.remove('active');
          }
        });
        
        // Toggle current dropdown
        parent.classList.toggle('active');
        
        // Add ripple effect
        this.addRipple(e);
      });
    });
  }

  setupRippleEffect() {
    const rippleElements = document.querySelectorAll('.nav-item a, .nav-dropdown-toggle');
    
    rippleElements.forEach(element => {
      element.addEventListener('click', (e) => {
        this.addRipple(e);
      });
    });
  }

  addRipple(event) {
    const button = event.currentTarget;
    
    // Remove existing ripple
    const existingRipple = button.querySelector('.ripple-effect');
    if (existingRipple) {
      existingRipple.remove();
    }
    
    const circle = document.createElement('span');
    const diameter = Math.max(button.clientWidth, button.clientHeight);
    const radius = diameter / 2;
    
    const rect = button.getBoundingClientRect();
    circle.style.width = circle.style.height = `${diameter}px`;
    circle.style.left = `${event.clientX - rect.left - radius}px`;
    circle.style.top = `${event.clientY - rect.top - radius}px`;
    circle.classList.add('ripple-effect');
    
    button.appendChild(circle);
    
    setTimeout(() => {
      circle.remove();
    }, 600);
  }

  showSidebar() {
    if (!this.sidebar) return;
    
    this.sidebar.classList.add('show');
    
    if (this.isMobile && this.sidebarOverlay) {
      this.sidebarOverlay.classList.add('show');
    }
  }

  hideSidebar() {
    if (!this.sidebar) return;
    
    this.sidebar.classList.remove('show');
    
    if (this.sidebarOverlay) {
      this.sidebarOverlay.classList.remove('show');
    }
  }

  toggleSidebar() {
    if (this.sidebar.classList.contains('show')) {
      this.hideSidebar();
    } else {
      this.showSidebar();
    }
  }

  startSidebarTimeout() {
    if (this.isMobile) return;
    
    this.sidebarTimeout = setTimeout(() => {
      const isHovering = this.sidebar.matches(':hover') || this.hamburgerMenu.matches(':hover');
      
      if (!isHovering) {
        this.hideSidebar();
      }
    }, 300);
  }

  clearSidebarTimeout() {
    if (this.sidebarTimeout) {
      clearTimeout(this.sidebarTimeout);
      this.sidebarTimeout = null;
    }
  }

  handleResize() {
    const wasMobile = this.isMobile;
    this.isMobile = window.innerWidth <= 768;
    
    if (wasMobile !== this.isMobile) {
      // Device type changed
      this.hideSidebar();
      this.clearSidebarTimeout();
    }
  }
}

/**
 * Auto-dismiss alerts
 */
function autoDismissAlerts() {
  const alerts = document.querySelectorAll('.alert');
  
  alerts.forEach(alert => {
    // Skip if alert has no-dismiss class
    if (alert.classList.contains('no-dismiss')) return;
    
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      
      setTimeout(() => {
        alert.remove();
      }, 500);
    }, 5000);
  });
}

/**
 * Smooth scroll to top
 */
function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });
}

/**
 * Initialize on DOM load
 */
document.addEventListener('DOMContentLoaded', () => {
  // Initialize navigation
  const nav = new NavigationManager();
  
  // Auto-dismiss alerts
  autoDismissAlerts();
  
  // Set active nav item based on current page
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('.nav-item a');
  
  navLinks.forEach(link => {
    const linkPath = new URL(link.href).pathname;
    if (currentPath === linkPath) {
      link.classList.add('active');
    }
  });
  
  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
});

// Export for use in other scripts
window.NavigationManager = NavigationManager;
window.scrollToTop = scrollToTop;
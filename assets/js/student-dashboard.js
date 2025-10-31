/**
 * Student Dashboard JavaScript
 * Handles animations, interactions, and dynamic updates
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard features
    initializeCardAnimations();
    initializeAlertDismiss();
    animateNumbers();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            fadeOut(alert);
        });
    }, 5000);
});

/**
 * Initialize card hover animations
 */
function initializeCardAnimations() {
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(function(card) {
        // Add ripple effect on click
        card.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            
            const rect = card.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            card.appendChild(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
        });
        
        // Add entrance animation on scroll
        observeCard(card);
    });
}

/**
 * Observe cards for scroll animations
 */
function observeCard(card) {
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                
                setTimeout(function() {
                    entry.target.style.transition = 'all 0.5s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    observer.observe(card);
}

/**
 * Animate numbers counting up
 */
function animateNumbers() {
    const numberElements = document.querySelectorAll('.card-number');
    
    numberElements.forEach(function(element) {
        const text = element.textContent;
        const hasPercent = text.includes('%');
        const hasSuffix = text.match(/\d+(st|nd|rd|th)/);
        
        // Extract number
        let finalValue = parseFloat(text);
        
        if (isNaN(finalValue)) return;
        
        // Animate from 0 to final value
        let currentValue = 0;
        const increment = finalValue / 50;
        const duration = 1000; // 1 second
        const stepTime = duration / 50;
        
        element.textContent = '0';
        
        const timer = setInterval(function() {
            currentValue += increment;
            
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            
            // Format display
            let displayValue;
            if (Number.isInteger(finalValue)) {
                displayValue = Math.floor(currentValue);
            } else {
                displayValue = currentValue.toFixed(1);
            }
            
            // Add suffix back
            if (hasPercent) {
                element.textContent = displayValue + '%';
            } else if (hasSuffix) {
                element.textContent = displayValue + getSuffix(Math.floor(currentValue));
            } else {
                element.textContent = displayValue;
            }
        }, stepTime);
    });
}

/**
 * Get ordinal suffix for numbers
 */
function getSuffix(num) {
    if (num % 10 === 1 && num % 100 !== 11) return 'st';
    if (num % 10 === 2 && num % 100 !== 12) return 'nd';
    if (num % 10 === 3 && num % 100 !== 13) return 'rd';
    return 'th';
}

/**
 * Initialize alert dismiss functionality
 */
function initializeAlertDismiss() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(function(alert) {
        // Make alert clickable to dismiss
        alert.style.cursor = 'pointer';
        alert.title = 'Click to dismiss';
        
        alert.addEventListener('click', function() {
            fadeOut(this);
        });
    });
}

/**
 * Fade out element
 */
function fadeOut(element) {
    element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    element.style.opacity = '0';
    element.style.transform = 'translateY(-10px)';
    
    setTimeout(function() {
        element.style.display = 'none';
        element.remove();
    }, 500);
}

/**
 * Add ripple effect styles dynamically
 */
const style = document.createElement('style');
style.textContent = `
    .card {
        position: relative;
        overflow: hidden;
    }
    
    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background: rgba(123, 45, 38, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

/**
 * Update statistics in real-time (if needed)
 * Can be connected to AJAX calls for live updates
 */
function refreshStatistics() {
    // Placeholder for future AJAX implementation
    console.log('Statistics refresh triggered');
    
    // Example AJAX call structure:
    /*
    fetch(window.BASE_URL + 'api/student/get-statistics.php')
        .then(response => response.json())
        .then(data => {
            updateDashboardStats(data);
        })
        .catch(error => {
            console.error('Error fetching statistics:', error);
        });
    */
}

/**
 * Update dashboard statistics
 */
function updateDashboardStats(data) {
    // Update each statistic card
    const stats = {
        'Overall Grade Average': data.overall_average,
        'Total Subjects Enrolled': data.total_classes,
        'Attendance Percentage': data.attendance_percentage + '%',
        'Missing / Overdue Tasks': data.missing_tasks,
        'Quiz Score Average': data.quiz_average,
        'Activity Score Average': data.activity_average,
        'Rank in Class': data.rank_display
    };
    
    const cards = document.querySelectorAll('.card');
    cards.forEach(function(card) {
        const title = card.querySelector('h3').textContent;
        if (stats[title]) {
            const numberElement = card.querySelector('.card-number');
            numberElement.textContent = stats[title];
        }
    });
}

/**
 * Handle button interactions
 */
document.querySelectorAll('.btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        // Add loading state
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        this.style.pointerEvents = 'none';
        
        // Reset after navigation (if same page) or form submission
        setTimeout(function() {
            if (this.innerHTML.includes('Loading')) {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
            }
        }.bind(this), 3000);
    });
});

/**
 * Add keyboard navigation support
 */
document.addEventListener('keydown', function(e) {
    // Press 'G' to go to grades
    if (e.key === 'g' || e.key === 'G') {
        if (!e.ctrlKey && !e.altKey) {
            const gradesLink = document.querySelector('a[href*="my-grades"]');
            if (gradesLink) gradesLink.click();
        }
    }
    
    // Press 'J' to join class
    if (e.key === 'j' || e.key === 'J') {
        if (!e.ctrlKey && !e.altKey) {
            const joinLink = document.querySelector('a[href*="join-class"]');
            if (joinLink) joinLink.click();
        }
    }
    
    // Press 'Escape' to dismiss alerts
    if (e.key === 'Escape') {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            fadeOut(alert);
        });
    }
});

/**
 * Add tooltip functionality for cards
 */
function addTooltips() {
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'card-tooltip';
            tooltip.textContent = 'Click for more details';
            tooltip.style.cssText = `
                position: absolute;
                bottom: 10px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            this.appendChild(tooltip);
            
            setTimeout(function() {
                tooltip.style.opacity = '1';
            }, 100);
        });
        
        card.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.card-tooltip');
            if (tooltip) {
                tooltip.style.opacity = '0';
                setTimeout(function() {
                    tooltip.remove();
                }, 300);
            }
        });
    });
}

// Initialize tooltips
setTimeout(addTooltips, 500);

/**
 * Console easter egg
 */
console.log('%c👋 Hello Student!', 'font-size: 20px; color: #7b2d26; font-weight: bold;');
console.log('%cWelcome to indEx Dashboard', 'font-size: 14px; color: #666;');
console.log('%cKeyboard shortcuts: G = Grades, J = Join Class', 'font-size: 12px; color: #999;');
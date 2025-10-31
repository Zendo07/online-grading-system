/**
 * Student Dashboard V5 - JavaScript
 * Handles charts, animations, and dynamic updates
 */

(function() {
    'use strict';

    /**
     * Initialize dashboard on page load
     */
    function initDashboard() {
        initChart();
        animateProgressBars();
        animateStatNumbers();
        checkSavedProfilePicture();
        setupProfilePictureListener();
    }

    /**
     * Initialize Chart.js grade performance chart
     */
    function initChart() {
        const canvas = document.getElementById('gradeChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        const gradient1 = ctx.createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(123, 45, 38, 0.8)');
        gradient1.addColorStop(1, 'rgba(123, 45, 38, 0.1)');

        // Get data from PHP (passed via global variable)
        const chartData = typeof gradeHistoryData !== 'undefined' ? gradeHistoryData : [0, 0, 0, 0, 0, 0];
        const hasData = typeof hasGrades !== 'undefined' ? hasGrades : false;
        
        // Determine Y-axis range based on data
        let minY = 0;
        let maxY = 100;
        
        if (hasData && chartData.some(val => val > 0)) {
            const validData = chartData.filter(val => val > 0);
            const minGrade = Math.min(...validData);
            const maxGrade = Math.max(...validData);
            minY = Math.max(0, Math.floor(minGrade / 10) * 10 - 10);
            maxY = Math.min(100, Math.ceil(maxGrade / 10) * 10 + 10);
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                datasets: [{
                    label: 'Average Grade',
                    data: chartData,
                    backgroundColor: gradient1,
                    borderColor: '#7b2d26',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#7b2d26',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#7b2d26',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#f5d5c8',
                        borderWidth: 2,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y > 0 ? 'Grade: ' + context.parsed.y + '%' : 'No data';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: minY === 0,
                        min: minY,
                        max: maxY,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            color: '#666666',
                            font: {
                                family: 'Poppins',
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: '#666666',
                            font: {
                                family: 'Poppins',
                                size: 12
                            }
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Animate progress bars
     */
    function animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar-v5');
        progressBars.forEach(bar => {
            const progress = bar.getAttribute('data-progress');
            setTimeout(() => {
                bar.style.width = progress + '%';
            }, 100);
        });
    }

    /**
     * Animate stat numbers
     */
    function animateStatNumbers() {
        const statValues = document.querySelectorAll('.stat-value-v5');
        statValues.forEach((stat) => {
            const text = stat.textContent.trim();
            const hasPercent = text.includes('%');
            
            // Extract number from string (remove % and any non-numeric chars except .)
            const numberMatch = text.match(/[\d.]+/);
            if (!numberMatch) return;
            
            const number = parseFloat(numberMatch[0]);
            
            if (!isNaN(number) && number > 0) {
                // Only animate if number is greater than 0
                if (hasPercent) {
                    animateValue(stat, 0, Math.round(number), 1000, true);
                } else {
                    animateValue(stat, 0, Math.round(number), 1000, false);
                }
            }
        });
    }

    /**
     * Animate a number from start to end
     */
    function animateValue(element, start, end, duration, hasPercent = false) {
        if (end === 0) {
            element.textContent = hasPercent ? '0%' : '0';
            return;
        }
        
        const range = end - start;
        const increment = range > 0 ? 1 : -1;
        const stepTime = Math.abs(Math.floor(duration / range));
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (hasPercent) {
                element.textContent = current + '%';
            } else {
                element.textContent = current;
            }
            
            if (current === end) {
                clearInterval(timer);
            }
        }, stepTime);
    }

    /**
     * Check localStorage for recently updated profile picture
     */
    function checkSavedProfilePicture() {
        const savedPicUrl = localStorage.getItem('profile_picture_updated');
        if (savedPicUrl) {
            updateProfilePicture(savedPicUrl);
        }
    }

    /**
     * Setup listener for profile picture updates from other tabs/windows
     */
    function setupProfilePictureListener() {
        window.addEventListener('storage', function(e) {
            if (e.key === 'profile_picture_updated' && e.newValue) {
                updateProfilePicture(e.newValue);
            }
        });
    }

    /**
     * Update profile picture element
     */
    function updateProfilePicture(pictureUrl) {
        const dashboardPic = document.getElementById('dashboardProfilePic');
        if (dashboardPic) {
            dashboardPic.style.opacity = '0';
            setTimeout(() => {
                dashboardPic.src = pictureUrl + '?t=' + new Date().getTime();
                dashboardPic.style.transition = 'opacity 0.3s ease';
                dashboardPic.style.opacity = '1';
            }, 50);
        }
        
        // Also update navbar profile if exists
        const navbarProfileImg = document.querySelector('.profile-button img');
        if (navbarProfileImg) {
            navbarProfileImg.src = pictureUrl + '?t=' + new Date().getTime();
        }
    }

    /**
     * Add smooth scroll behavior
     */
    function setupSmoothScroll() {
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
    }

    /**
     * Card animation on scroll
     */
    function setupScrollAnimations() {
        const cards = document.querySelectorAll('.stat-card-v5, .chart-card-v5');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        entry.target.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 100);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });
        
        cards.forEach(card => {
            observer.observe(card);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initDashboard();
            setupSmoothScroll();
            setupScrollAnimations();
        });
    } else {
        initDashboard();
        setupSmoothScroll();
        setupScrollAnimations();
    }

    // Export functions for global use
    window.DashboardV5 = {
        updateProfilePicture: updateProfilePicture,
        animateProgressBars: animateProgressBars
    };

})();
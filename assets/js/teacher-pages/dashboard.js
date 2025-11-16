(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        initializeAnimations();
        initializeCharts();
        initializeInteractions();
        autoHideAlerts();
    });
    
    function initializeAnimations() {
        // Animate stat cards
        animateStatCards();
        
        // Animate schedule cards
        animateScheduleCards();
        
        // Fade in main content
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.style.animation = 'fadeIn 0.5s ease';
        }
    }
    
    function animateStatCards() {
        const statCards = document.querySelectorAll('.stat-mini');
        
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
        
        // Animate stat values
        document.querySelectorAll('.stat-mini-value').forEach(stat => {
            const text = stat.textContent;
            const isPercentage = text.includes('%');
            const value = parseFloat(text);
            
            if (!isNaN(value) && value > 0) {
                animateValue(stat, 0, value, 1500, isPercentage);
            }
        });
    }
    
    function animateScheduleCards() {
        const scheduleCards = document.querySelectorAll('.schedule-card');
        
        scheduleCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateX(0)';
            }, 200 + (index * 100));
        });
    }
    
    function animateValue(element, start, end, duration, isPercentage = false) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                element.textContent = isPercentage ? end.toFixed(1) + '%' : Math.round(end);
                clearInterval(timer);
            } else {
                element.textContent = isPercentage ? current.toFixed(1) + '%' : Math.floor(current);
            }
        }, 16);
    }
    
    
    function initializeCharts() {
        const chartCanvas = document.getElementById('performanceChart');
        
        if (!chartCanvas || !window.dashboardData) {
            console.warn('Chart canvas or data not found');
            return;
        }
        
        const ctx = chartCanvas.getContext('2d');
        const classData = window.dashboardData.classAnalytics || [];
        
        if (classData.length === 0) {
            return; // Empty state already shown in HTML
        }
        
        // Prepare chart data
        const labels = classData.map(item => 
            `${item.class_name} - ${item.section}`
        );
        
        const data = classData.map(item => 
            parseFloat(item.avg_grade || 0).toFixed(2)
        );
        
        // Generate colors based on performance
        const backgroundColors = data.map(grade => {
            if (grade >= 90) return 'rgba(16, 185, 129, 0.8)';
            if (grade >= 85) return 'rgba(127, 29, 29, 0.8)';
            if (grade >= 75) return 'rgba(59, 130, 246, 0.8)';
            return 'rgba(239, 68, 68, 0.8)';
        });
        
        const borderColors = data.map(grade => {
            if (grade >= 90) return 'rgba(16, 185, 129, 1)';
            if (grade >= 85) return 'rgba(127, 29, 29, 1)';
            if (grade >= 75) return 'rgba(59, 130, 246, 1)';
            return 'rgba(239, 68, 68, 1)';
        });
        
        // Create chart
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Grade',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false
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
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 14,
                            weight: '600',
                            family: "'Inter', sans-serif"
                        },
                        bodyFont: {
                            size: 13,
                            family: "'Inter', sans-serif"
                        },
                        borderColor: 'rgba(148, 163, 184, 0.2)',
                        borderWidth: 1,
                        displayColors: true,
                        boxWidth: 10,
                        boxHeight: 10,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const value = parseFloat(context.parsed.y);
                                let status = '';
                                
                                if (value >= 90) status = 'Excellent';
                                else if (value >= 85) status = 'Good';
                                else if (value >= 75) status = 'Passing';
                                else status = 'Needs Attention';
                                
                                return [
                                    `Average: ${value.toFixed(1)}%`,
                                    `Status: ${status}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            font: {
                                size: 12,
                                weight: '500',
                                family: "'Inter', sans-serif"
                            },
                            color: '#64748b'
                        },
                        grid: {
                            color: 'rgba(226, 232, 240, 0.6)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11,
                                weight: '500',
                                family: "'Inter', sans-serif"
                            },
                            color: '#64748b',
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
        
        // Store chart instance
        window.performanceChart = chart;
    }
    
    function initializeInteractions() {
        addScheduleCardHandlers();
        addStatCardHoverEffects();
        injectRippleStyles();
    }
    
    function addScheduleCardHandlers() {
        const scheduleCards = document.querySelectorAll('.schedule-card');
        
        scheduleCards.forEach(card => {
            card.style.cursor = 'pointer';
            
            card.addEventListener('click', function(e) {
                // Add ripple effect
                const ripple = document.createElement('span');
                ripple.classList.add('ripple-effect');
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });
    }
    
    function addStatCardHoverEffects() {
        const statCards = document.querySelectorAll('.stat-mini');
        
        statCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = 'var(--shadow-md)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'var(--shadow-sm)';
            });
        });
    }
    
    function injectRippleStyles() {
        if (document.getElementById('ripple-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'ripple-styles';
        style.textContent = `
            .schedule-card {
                position: relative;
                overflow: hidden;
            }
            
            .ripple-effect {
                position: absolute;
                border-radius: 50%;
                background: rgba(127, 29, 29, 0.3);
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            }
            
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    function autoHideAlerts() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    }
    
    function showNotification(message, type = 'success') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        const mainContent = document.querySelector('.main-content');
        mainContent.insertBefore(alert, mainContent.firstChild);
        
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }
    
    function refreshChartData(newData) {
        if (!window.performanceChart) return;
        
        const labels = newData.map(item => `${item.class_name} - ${item.section}`);
        const data = newData.map(item => parseFloat(item.avg_grade || 0).toFixed(2));
        
        window.performanceChart.data.labels = labels;
        window.performanceChart.data.datasets[0].data = data;
        window.performanceChart.update('active');
    }
    
    window.dashboardUtils = {
        showNotification,
        refreshChartData,
        animateValue
    };
    
})();
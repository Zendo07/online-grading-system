(function() {
    'use strict';

    // Configuration
    const CHART_ID = 'gradeChart';
    const UPDATE_INTERVAL = 30000;
    let chartInstance = null;
    let updateInterval = null;

    function initDashboard() {
        initChart();
        animateProgressBars();
        animateStatNumbers();
        checkSavedProfilePicture();
        checkSavedProfileName();
        setupProfilePictureListener();
        setupProfileNameListener();
        setupRealtimeUpdates();
    }

    function initChart() {
        const canvas = document.getElementById(CHART_ID);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        const gradient1 = ctx.createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(123, 45, 38, 0.8)');
        gradient1.addColorStop(1, 'rgba(123, 45, 38, 0.1)');

        const chartData = typeof gradeHistoryData !== 'undefined' ? gradeHistoryData : [0, 0, 0, 0, 0, 0];
        const hasData = typeof hasGrades !== 'undefined' ? hasGrades : false;
        
        let minY = 0;
        let maxY = 100;
        
        if (hasData && chartData.some(val => val > 0)) {
            const validData = chartData.filter(val => val > 0);
            const minGrade = Math.min(...validData);
            const maxGrade = Math.max(...validData);
            minY = Math.max(0, Math.floor(minGrade / 10) * 10 - 10);
            maxY = Math.min(100, Math.ceil(maxGrade / 10) * 10 + 10);
        }

        chartInstance = new Chart(ctx, {
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
                    legend: { display: false },
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
                            callback: function(value) { return value + '%'; },
                            color: '#666666',
                            font: { family: 'Poppins', size: 12 }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: '#666666',
                            font: { family: 'Poppins', size: 12 }
                        },
                        grid: { display: false, drawBorder: false }
                    }
                }
            }
        });
    }

    function setupRealtimeUpdates() {
        // Poll for updates every UPDATE_INTERVAL
        updateInterval = setInterval(function() {
            fetchDashboardUpdates();
        }, UPDATE_INTERVAL);
    }

    function fetchDashboardUpdates() {
        fetch(window.BASE_URL + 'api/student/get-dashboard-updates.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDashboardStats(data);
                    updateRecentActivities(data.recent_activities);
                }
            })
            .catch(error => console.error('Update fetch error:', error));
    }

    function updateDashboardStats(data) {
        // Update stat cards
        updateStatCard('activeCourses', data.active_courses);
        updateStatCard('gpaValue', data.gpa.toFixed(1) + '%');
        updateStatCard('attendancePercent', data.attendance_percentage + '%');
        updateStatCard('pendingTasks', data.missing_submissions);

        // Update subject performance
        if (data.subject_performance && data.subject_performance.length > 0) {
            updateSubjectPerformance(data.subject_performance);
        }

        // Update grade chart
        if (data.grade_history && chartInstance) {
            chartInstance.data.datasets[0].data = data.grade_history;
            chartInstance.update('none');
        }
    }

    function updateStatCard(elementId, value) {
        const element = document.getElementById(elementId);
        if (!element || element.textContent === value) return;

        element.style.opacity = '0.5';
        element.textContent = value;
        
        setTimeout(() => {
            element.style.transition = 'opacity 0.3s ease';
            element.style.opacity = '1';
        }, 100);
    }

    function updateSubjectPerformance(subjects) {
        const container = document.querySelector('.progress-list-v5');
        if (!container) return;

        const newHTML = subjects.map(subject => `
            <div class="progress-item-v5">
                <div class="progress-header-v5">
                    <span class="progress-label-v5">${escapeHtml(subject.subject)}</span>
                    <span class="progress-percentage-v5">${subject.avg_percentage.toFixed(1)}%</span>
                </div>
                <div class="progress-bar-container-v5">
                    <div class="progress-bar-v5" data-progress="${subject.avg_percentage.toFixed(1)}" 
                         style="width: ${subject.avg_percentage}%;"></div>
                </div>
            </div>
        `).join('');

        container.innerHTML = newHTML;
    }

    function updateRecentActivities(activities) {
        const container = document.querySelector('.activity-list-v5');
        if (!container || !activities || activities.length === 0) return;

        const newActivities = activities.map(activity => {
            if (activity.type === 'grade') {
                return `
                    <div class="activity-item-v5">
                        <div class="activity-icon-v5 blue">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="activity-content-v5">
                            <div class="activity-title-v5">
                                ${escapeHtml(activity.activity_name)} 
                                <span style="font-size: 0.85rem; color: var(--gray-medium);">
                                    (${capitalizeFirst(activity.activity_type)})
                                </span>
                            </div>
                            <div class="activity-details-v5">
                                <strong>${escapeHtml(activity.teacher_name)}</strong> • 
                                ${escapeHtml(activity.subject)}<br>
                                Score: <strong>${activity.score}/${activity.max_score}</strong> 
                                (${activity.percentage.toFixed(1)}%)
                            </div>
                            <div class="activity-time-v5">${formatDateTime(activity.event_date)}</div>
                        </div>
                    </div>
                `;
            } else if (activity.type === 'attendance') {
                const iconClass = activity.attendance_status === 'present' ? 'green' : 'red';
                const icon = activity.attendance_status === 'present' ? 'check-circle' : 'times-circle';
                return `
                    <div class="activity-item-v5">
                        <div class="activity-icon-v5 ${iconClass}">
                            <i class="fas fa-${icon}"></i>
                        </div>
                        <div class="activity-content-v5">
                            <div class="activity-title-v5">
                                Attendance Mark - 
                                <span style="text-transform: capitalize;">
                                    ${activity.attendance_status}
                                </span>
                            </div>
                            <div class="activity-details-v5">
                                <strong>${escapeHtml(activity.teacher_name)}</strong> • 
                                ${escapeHtml(activity.subject)}
                            </div>
                            <div class="activity-time-v5">${formatDateTime(activity.event_date)}</div>
                        </div>
                    </div>
                `;
            }
        }).join('');

        container.innerHTML = newActivities;
    }

    function animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar-v5');
        progressBars.forEach(bar => {
            const progress = bar.getAttribute('data-progress');
            setTimeout(() => {
                bar.style.width = progress + '%';
            }, 100);
        });
    }

    function animateStatNumbers() {
        const statValues = document.querySelectorAll('.stat-value-v5');
        statValues.forEach((stat) => {
            const text = stat.textContent.trim();
            const hasPercent = text.includes('%');
            const numberMatch = text.match(/[\d.]+/);
            
            if (!numberMatch) return;
            
            const number = parseFloat(numberMatch[0]);
            
            if (!isNaN(number) && number > 0) {
                if (hasPercent) {
                    animateValue(stat, 0, Math.round(number), 1000, true);
                } else {
                    animateValue(stat, 0, Math.round(number), 1000, false);
                }
            }
        });
    }

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
            element.textContent = hasPercent ? current + '%' : current;
            
            if (current === end) clearInterval(timer);
        }, stepTime);
    }

    function checkSavedProfilePicture() {
        const savedPicUrl = localStorage.getItem('profile_picture_updated');
        const timestamp = localStorage.getItem('profile_picture_timestamp');
        
        // Only use cached picture if updated within last 5 minutes
        if (savedPicUrl && timestamp) {
            const ageMinutes = (Date.now() - parseInt(timestamp)) / 60000;
            if (ageMinutes < 5) {
                updateProfilePicture(savedPicUrl);
            } else {
                // Clean up old cache
                localStorage.removeItem('profile_picture_updated');
                localStorage.removeItem('profile_picture_timestamp');
            }
        }
    }

    function checkSavedProfileName() {
        const savedName = localStorage.getItem('profile_name_updated');
        const timestamp = localStorage.getItem('profile_update_timestamp');
        
        if (savedName && timestamp) {
            const ageMinutes = (Date.now() - parseInt(timestamp)) / 60000;
            if (ageMinutes < 5) {
                updateProfileName(savedName);
            } else {
                // Clean up old cache
                localStorage.removeItem('profile_name_updated');
                localStorage.removeItem('profile_update_timestamp');
            }
        }
    }
    function setupProfilePictureListener() {
        window.addEventListener('storage', function(e) {
            if (e.key === 'profile_picture_updated' && e.newValue) {
                updateProfilePicture(e.newValue);
            }
        });
    }

    function setupProfileNameListener() {
        window.addEventListener('storage', function(e) {
            if (e.key === 'profile_name_updated' && e.newValue) {
                updateProfileName(e.newValue);
            }
        });
    }

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
        
        const navbarProfileImg = document.querySelector('.profile-button img');
        if (navbarProfileImg) {
            navbarProfileImg.src = pictureUrl + '?t=' + new Date().getTime();
        }
    }

    function updateProfileName(newName) {
        const welcomeTitle = document.querySelector('.welcome-text-v5 h1');
        if (welcomeTitle) {
            // Extract first name from full name
            const firstName = newName.split(' ')[0];
            welcomeTitle.style.opacity = '0';
            setTimeout(() => {
                welcomeTitle.textContent = `Welcome Back, ${firstName}!`;
                welcomeTitle.style.transition = 'opacity 0.5s ease';
                welcomeTitle.style.opacity = '1';
            }, 200);
        }
        
        // Update sidebar user name
        const sidebarName = document.querySelector('.sidebar-user-name');
        if (sidebarName) {
            sidebarName.style.opacity = '0';
            setTimeout(() => {
                sidebarName.textContent = newName;
                sidebarName.style.transition = 'opacity 0.5s ease';
                sidebarName.style.opacity = '1';
            }, 200);
        }
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function formatDateTime(datetime) {
        const date = new Date(datetime);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffMinutes = Math.ceil(diffTime / (1000 * 60));
        const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffMinutes < 1) return 'Just now';
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    window.addEventListener('beforeunload', function() {
        if (updateInterval) clearInterval(updateInterval);
    });

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboard);
    } else {
        initDashboard();
    }

    // Export functions for global use
    window.DashboardV6 = {
        updateProfilePicture,
        updateProfileName,
        animateProgressBars,
        refreshDashboard: fetchDashboardUpdates
    };

})();
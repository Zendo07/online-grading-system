<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireTeacher();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Class - indEx</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>teacher-pages/create-class.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <div class="create-class-container">
                
                <div class="page-header">
                    <div class="header-content">
                        <a href="my-courses.php" class="back-button">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Courses</span>
                        </a>
                        <div class="header-text">
                            <h1 class="page-title">Create New Class</h1>
                            <p class="page-subtitle">Set up a new class and manage schedules</p>
                        </div>
                    </div>
                    <div class="header-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                        <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : ($flash['type'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                        <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="content-layout">
                    <div class="form-card">
                        <div class="card-header-modern">
                            <div class="header-icon-wrapper">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="header-text">
                                <h2 class="card-title">Class Information</h2>
                                <p class="card-subtitle">Fill in the details to create your class</p>
                            </div>
                        </div>
                        
                        <div class="card-body-modern">
                            <form action="<?php echo BASE_URL; ?>api/teacher/create-class-handler.php" method="POST" id="createClassForm" class="modern-form">
                                
                                <div class="form-group-modern">
                                    <label for="className" class="form-label-modern">
                                        <span class="label-text">Class Name</span>
                                        <span class="label-required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <div class="input-icon">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <input 
                                            type="text" 
                                            id="className" 
                                            name="class_name" 
                                            class="form-input-modern" 
                                            placeholder="e.g., Computer Programming 101"
                                            required
                                            maxlength="100"
                                        >
                                    </div>
                                    <p class="input-hint">Enter a descriptive name for your class</p>
                                </div>
                                
                                <div class="form-group-modern">
                                    <label for="subject" class="form-label-modern">
                                        <span class="label-text">Subject</span>
                                        <span class="label-required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <div class="input-icon">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <input 
                                            type="text" 
                                            id="subject" 
                                            name="subject" 
                                            class="form-input-modern" 
                                            placeholder="e.g., Computer Science"
                                            required
                                            maxlength="100"
                                        >
                                    </div>
                                    <p class="input-hint">Specify the subject area</p>
                                </div>
                                
                                <div class="form-group-modern">
                                    <label for="section" class="form-label-modern">
                                        <span class="label-text">Section</span>
                                        <span class="label-required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <div class="input-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <input 
                                            type="text" 
                                            id="section" 
                                            name="section" 
                                            class="form-input-modern" 
                                            placeholder="e.g., Section A, 1-A"
                                            required
                                            maxlength="50"
                                        >
                                    </div>
                                    <p class="input-hint">Identify the class section</p>
                                </div>

                                <!-- Schedule Section -->
                                <div class="schedule-section">
                                    <div class="schedule-header">
                                        <div>
                                            <h3 class="schedule-title">Class Schedule</h3>
                                            <p class="schedule-subtitle">Add one or more schedule slots (optional)</p>
                                        </div>
                                        <button type="button" class="btn-add-schedule" id="addScheduleBtn">
                                            <i class="fas fa-plus"></i>
                                            Add Schedule
                                        </button>
                                    </div>

                                    <div id="schedulesContainer" class="schedules-container">
                                    </div>
                                </div>
                                
                                <div class="info-box">
                                    <div class="info-icon">
                                        <i class="fas fa-lightbulb"></i>
                                    </div>
                                    <div class="info-content">
                                        <h4 class="info-title">Quick Tip</h4>
                                        <p class="info-text">A unique class code will be automatically generated. Share this code with your students so they can join the class instantly.</p>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="my-courses.php" class="btn-modern-secondary">
                                        <i class="fas fa-times"></i>
                                        <span>Cancel</span>
                                    </a>
                                    <button type="submit" class="btn-modern-primary" id="submitBtn">
                                        <i class="fas fa-check"></i>
                                        <span>Create Class</span>
                                        <div class="btn-loader" style="display: none;">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="instructions-card">
                        <div class="card-header-modern">
                            <div class="header-icon-wrapper">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="header-text">
                                <h2 class="card-title">How It Works</h2>
                                <p class="card-subtitle">Follow these simple steps</p>
                            </div>
                        </div>
                        
                        <div class="card-body-modern">
                            <div class="steps-list">
                                <div class="step-item">
                                    <div class="step-number">1</div>
                                    <div class="step-content">
                                        <h4 class="step-title">Enter Class Details</h4>
                                        <p class="step-text">Fill in the class name, subject, and section</p>
                                    </div>
                                </div>
                                
                                <div class="step-item">
                                    <div class="step-number">2</div>
                                    <div class="step-content">
                                        <h4 class="step-title">Add Schedules</h4>
                                        <p class="step-text">Set up class meeting days and times (optional)</p>
                                    </div>
                                </div>
                                
                                <div class="step-item">
                                    <div class="step-number">3</div>
                                    <div class="step-content">
                                        <h4 class="step-title">Generate Class Code</h4>
                                        <p class="step-text">System creates a unique code automatically</p>
                                    </div>
                                </div>
                                
                                <div class="step-item">
                                    <div class="step-number">4</div>
                                    <div class="step-content">
                                        <h4 class="step-title">Share with Students</h4>
                                        <p class="step-text">Students join instantly with the code</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="features-grid">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-bolt"></i>
                                    </div>
                                    <h5 class="feature-title">Quick Setup</h5>
                                    <p class="feature-text">Create classes in seconds</p>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <h5 class="feature-title">Flexible Schedules</h5>
                                    <p class="feature-text">Multiple time slots</p>
                                    </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <h5 class="feature-title">Easy Management</h5>
                                    <p class="feature-text">Control student access</p>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <h5 class="feature-title">Track Progress</h5>
                                    <p class="feature-text">Monitor performance</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    console.log('🟢 INLINE SCRIPT STARTING');
    
    let scheduleCount = 0;
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🟢 DOM LOADED');
        
        // Get button and container
        const addBtn = document.getElementById('addScheduleBtn');
        const container = document.getElementById('schedulesContainer');
        
        console.log('Button found:', addBtn);
        console.log('Container found:', container);
        
        if (!addBtn || !container) {
            console.error('❌ ELEMENTS NOT FOUND!');
            return;
        }
        
        // Add click event
        addBtn.onclick = function(e) {
            e.preventDefault();
            console.log('🟢 BUTTON CLICKED! Adding schedule...');
            addSchedule();
        };
        
        console.log('✅ Event listener attached');
        
        // Function to add schedule
        function addSchedule() {
            scheduleCount++;
            console.log('Adding schedule #' + scheduleCount);
            
            const html = `
                <div class="schedule-item" data-schedule-id="${scheduleCount}">
                    <div class="schedule-item-header">
                        <span class="schedule-badge">Schedule ${scheduleCount}</span>
                        <button type="button" class="btn-remove-schedule" onclick="removeSchedule(${scheduleCount})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    
                    <div class="schedule-item-body">
                        <div class="schedule-row">
                            <div class="schedule-col">
                                <label class="schedule-label">
                                    <i class="fas fa-calendar-day"></i>
                                    Day
                                </label>
                                <select name="schedules[${scheduleCount}][day]" class="schedule-select" required>
                                    <option value="">Select Day</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </div>
                            
                            <div class="schedule-col">
                                <label class="schedule-label">
                                    <i class="fas fa-clock"></i>
                                    Start Time
                                </label>
                                <input 
                                    type="time" 
                                    name="schedules[${scheduleCount}][start_time]" 
                                    class="schedule-input"
                                    required
                                >
                            </div>
                            
                            <div class="schedule-col">
                                <label class="schedule-label">
                                    <i class="fas fa-clock"></i>
                                    End Time
                                </label>
                                <input 
                                    type="time" 
                                    name="schedules[${scheduleCount}][end_time]" 
                                    class="schedule-input"
                                    required
                                >
                            </div>
                        </div>
                        
                        <div class="schedule-row">
                            <div class="schedule-col-full">
                                <label class="schedule-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Room/Location <span style="color: #999; font-weight: 400;">(Optional)</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="schedules[${scheduleCount}][room]" 
                                    class="schedule-input"
                                    placeholder="e.g., Room 301, Building A"
                                    maxlength="100"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', html);
            
            const newItem = container.querySelector(`[data-schedule-id="${scheduleCount}"]`);
            newItem.style.opacity = '0';
            newItem.style.transform = 'translateY(-20px)';
            newItem.style.transition = 'all 0.3s ease';
            
            setTimeout(function() {
                newItem.style.opacity = '1';
                newItem.style.transform = 'translateY(0)';
            }, 10);
            
            console.log('✅ Schedule #' + scheduleCount + ' added!');
        }
        
        // Make removeSchedule global
        window.removeSchedule = function(id) {
            console.log('Removing schedule #' + id);
            const item = document.querySelector(`[data-schedule-id="${id}"]`);
            if (item) {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-100%)';
                setTimeout(function() {
                    item.remove();
                    console.log('✅ Schedule removed');
                }, 300);
            }
        };
        
        // Form submit handler
        document.getElementById('createClassForm').onsubmit = function() {
            const btn = document.getElementById('submitBtn');
            const text = btn.querySelector('span');
            const icon = btn.querySelector('i.fa-check');
            const loader = btn.querySelector('.btn-loader');
            
            if (icon) icon.style.display = 'none';
            if (text) text.textContent = 'Creating...';
            if (loader) loader.style.display = 'inline-block';
            btn.disabled = true;
        };
        
        console.log('✅ READY! Click "Add Schedule" button');
    });
    </script>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
</body>
</html>
// create-class-schedule.js - FIXED VERSION WITH NO CONFLICTS

(function() {
    'use strict';
    
    let scheduleCount = 0;
    let isInitialized = false;
    
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        // Prevent double initialization
        if (isInitialized) return;
        isInitialized = true;
        
        console.log('Initializing schedule manager...');
        
        setupAddScheduleButton();
        setupFormSubmission();
        setupInputAnimations();
        animateSteps();
    }
    
    function setupAddScheduleButton() {
        const addBtn = document.getElementById('addScheduleBtn');
        if (!addBtn) {
            console.error('Add schedule button not found');
            return;
        }
        
        // Remove any existing listeners by cloning
        const newAddBtn = addBtn.cloneNode(true);
        addBtn.parentNode.replaceChild(newAddBtn, addBtn);
        
        // Add single click listener
        newAddBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Add schedule clicked');
            addSchedule();
        }, false);
    }
    
    function setupFormSubmission() {
        const form = document.getElementById('createClassForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (!form || !submitBtn) {
            console.error('Form or submit button not found');
            return;
        }
        
        // Remove existing submit listener by cloning
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Re-setup add schedule button after cloning
        setupAddScheduleButton();
        
        // Get new references
        const currentForm = document.getElementById('createClassForm');
        const currentSubmitBtn = document.getElementById('submitBtn');
        
        currentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Form submit triggered');
            
            // Validate required fields
            if (!currentForm.checkValidity()) {
                currentForm.reportValidity();
                return false;
            }
            
            // Validate schedule times
            if (!validateAllSchedules()) {
                return false;
            }
            
            // Show loading state
            showLoadingState(currentSubmitBtn);
            
            // Submit the form
            console.log('Submitting form...');
            currentForm.submit();
        }, false);
    }
    
    function validateAllSchedules() {
        const scheduleItems = document.querySelectorAll('.schedule-item');
        
        for (let item of scheduleItems) {
            const startTime = item.querySelector('input[name*="start_time"]');
            const endTime = item.querySelector('input[name*="end_time"]');
            
            if (startTime && endTime && startTime.value && endTime.value) {
                if (startTime.value >= endTime.value) {
                    endTime.setCustomValidity('End time must be after start time');
                    endTime.reportValidity();
                    return false;
                } else {
                    endTime.setCustomValidity('');
                }
            }
        }
        
        return true;
    }
    
    function showLoadingState(btn) {
        const btnText = btn.querySelector('span');
        const btnIcon = btn.querySelector('i.fa-check');
        const btnLoader = btn.querySelector('.btn-loader');
        
        if (btnIcon) btnIcon.style.display = 'none';
        if (btnText) btnText.textContent = 'Creating...';
        if (btnLoader) btnLoader.style.display = 'inline-block';
        
        btn.disabled = true;
        btn.style.opacity = '0.7';
    }
    
    function addSchedule() {
        scheduleCount++;
        const container = document.getElementById('schedulesContainer');
        
        if (!container) {
            console.error('Schedules container not found');
            return;
        }
        
        console.log('Adding schedule #' + scheduleCount);
        
        const scheduleItem = document.createElement('div');
        scheduleItem.className = 'schedule-item';
        scheduleItem.setAttribute('data-schedule-id', scheduleCount);
        
        scheduleItem.innerHTML = `
            <div class="schedule-item-header">
                <span class="schedule-badge">Schedule ${scheduleCount}</span>
                <button type="button" class="btn-remove-schedule" data-schedule-id="${scheduleCount}" aria-label="Remove schedule ${scheduleCount}">
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
                            ${days.map(day => `<option value="${day}">${day}</option>`).join('')}
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
                            Room/Location <span style="color: #64748b; font-weight: 400;">(Optional)</span>
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
        `;
        
        container.appendChild(scheduleItem);
        
        // Setup remove button
        const removeBtn = scheduleItem.querySelector('.btn-remove-schedule');
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = parseInt(this.getAttribute('data-schedule-id'));
            removeSchedule(id);
        }, false);
        
        // Setup time validation
        const startInput = scheduleItem.querySelector('input[name*="start_time"]');
        const endInput = scheduleItem.querySelector('input[name*="end_time"]');
        
        if (startInput && endInput) {
            startInput.addEventListener('change', function() {
                validateTime(startInput, endInput);
            });
            
            endInput.addEventListener('change', function() {
                validateTime(startInput, endInput);
            });
        }
        
        console.log('Schedule #' + scheduleCount + ' added successfully');
    }
    
    function removeSchedule(id) {
        const item = document.querySelector(`[data-schedule-id="${id}"]`);
        if (item) {
            console.log('Removing schedule #' + id);
            item.remove();
            renumberSchedules();
        }
    }
    
    function renumberSchedules() {
        const schedules = document.querySelectorAll('.schedule-item');
        schedules.forEach((schedule, index) => {
            const badge = schedule.querySelector('.schedule-badge');
            if (badge) {
                badge.textContent = `Schedule ${index + 1}`;
            }
            // Update remove button aria-label
            const removeBtn = schedule.querySelector('.btn-remove-schedule');
            if (removeBtn) {
                removeBtn.setAttribute('aria-label', `Remove schedule ${index + 1}`);
            }
        });
    }
    
    function validateTime(startInput, endInput) {
        if (startInput.value && endInput.value) {
            if (startInput.value >= endInput.value) {
                endInput.setCustomValidity('End time must be after start time');
                return false;
            } else {
                endInput.setCustomValidity('');
                return true;
            }
        }
        return true;
    }
    
    function setupInputAnimations() {
        document.addEventListener('focus', function(e) {
            if (e.target.matches('.form-input-modern, .schedule-input, .schedule-select')) {
                const wrapper = e.target.closest('.input-wrapper');
                if (wrapper) wrapper.classList.add('focused');
            }
        }, true);
        
        document.addEventListener('blur', function(e) {
            if (e.target.matches('.form-input-modern, .schedule-input, .schedule-select')) {
                const wrapper = e.target.closest('.input-wrapper');
                if (wrapper) wrapper.classList.remove('focused');
            }
        }, true);
    }
    
    function animateSteps() {
        const steps = document.querySelectorAll('.step-item');
        steps.forEach((step, index) => {
            step.style.animationDelay = `${index * 0.1}s`;
        });
    }
    
})();
console.log('🔵 Loading NEW Schedule Script v2.0');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔵 DOM Ready - Initializing Schedule Manager');
    
    let scheduleCounter = 0;
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const addButton = document.getElementById('addScheduleBtn');
    const container = document.getElementById('schedulesContainer');
    const form = document.getElementById('createClassForm');
    
    if (!addButton || !container || !form) {
        console.log('⚠️ Not on create class page, skipping initialization');
        return;
    }
    
    console.log('✅ Found all required elements');
    console.log('Button:', addButton);
    console.log('Container:', container);
    console.log('Form:', form);
    
    addButton.addEventListener('click', function(event) {
        event.preventDefault();
        console.log('🟢 ADD SCHEDULE CLICKED!');
        addNewSchedule();
    });
    
    console.log('✅ Button listener attached');
    function addNewSchedule() {
        scheduleCounter++;
        console.log('➕ Creating schedule #' + scheduleCounter);
        const scheduleHTML = `
            <div class="schedule-item" data-schedule-id="${scheduleCounter}" style="opacity: 0; transform: translateY(-20px); transition: all 0.3s ease;">
                <div class="schedule-item-header">
                    <span class="schedule-badge">Schedule ${scheduleCounter}</span>
                    <button type="button" class="btn-remove-schedule" data-id="${scheduleCounter}">
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
                            <select name="schedules[${scheduleCounter}][day]" class="schedule-select" required>
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
                                name="schedules[${scheduleCounter}][start_time]" 
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
                                name="schedules[${scheduleCounter}][end_time]" 
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
                                name="schedules[${scheduleCounter}][room]" 
                                class="schedule-input"
                                placeholder="e.g., Room 301, Building A"
                                maxlength="100"
                            >
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', scheduleHTML);
        const newSchedule = container.querySelector(`[data-schedule-id="${scheduleCounter}"]`);

        setTimeout(function() {
            newSchedule.style.opacity = '1';
            newSchedule.style.transform = 'translateY(0)';
        }, 10);
        
        const removeBtn = newSchedule.querySelector('.btn-remove-schedule');
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const scheduleId = this.getAttribute('data-id');
            console.log('🗑️ Removing schedule #' + scheduleId);
            removeSchedule(scheduleId);
        });
        
        const startTime = newSchedule.querySelector('input[name*="start_time"]');
        const endTime = newSchedule.querySelector('input[name*="end_time"]');
        
        function validateTimes() {
            if (startTime.value && endTime.value) {
                if (startTime.value >= endTime.value) {
                    endTime.setCustomValidity('End time must be after start time');
                } else {
                    endTime.setCustomValidity('');
                }
            }
        }
        
        startTime.addEventListener('change', validateTimes);
        endTime.addEventListener('change', validateTimes);
        
        console.log('✅ Schedule #' + scheduleCounter + ' added successfully!');
        
        // Scroll to it
        setTimeout(function() {
            newSchedule.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 350);
    }
    
    // Function to remove a schedule
    function removeSchedule(scheduleId) {
        const scheduleItem = container.querySelector(`[data-schedule-id="${scheduleId}"]`);
        
        if (!scheduleItem) {
            console.warn('⚠️ Schedule not found');
            return;
        }
        
        // Animate out
        scheduleItem.style.opacity = '0';
        scheduleItem.style.transform = 'translateX(-100%)';
        
        // Remove after animation
        setTimeout(function() {
            scheduleItem.remove();
            renumberSchedules();
            console.log('✅ Schedule removed');
        }, 300);
    }
    
    // Renumber schedules after removal
    function renumberSchedules() {
        const allSchedules = container.querySelectorAll('.schedule-item');
        allSchedules.forEach(function(schedule, index) {
            const badge = schedule.querySelector('.schedule-badge');
            if (badge) {
                badge.textContent = 'Schedule ' + (index + 1);
            }
        });
        console.log('✅ Renumbered ' + allSchedules.length + ' schedules');
    }
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        console.log('📤 Form submitting...');
        
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            const btnText = submitBtn.querySelector('span');
            const btnIcon = submitBtn.querySelector('i.fa-check');
            const btnLoader = submitBtn.querySelector('.btn-loader');
            
            if (btnIcon) btnIcon.style.display = 'none';
            if (btnText) btnText.textContent = 'Creating...';
            if (btnLoader) btnLoader.style.display = 'inline-block';
            
            submitBtn.disabled = true;
        }
    });
    
    console.log('🟢 Schedule Manager Ready!');
    console.log('👉 Click "Add Schedule" button to test');
});

console.log('🔵 Schedule Script Loaded - Waiting for DOM');
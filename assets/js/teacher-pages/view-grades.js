let students = [];
let grades = [];
let attendance = [];
let attendanceDates = [];
let activityColumns = [];
let isLoading = false;
let currentGradingPeriod = 'prelim'; 

document.addEventListener('DOMContentLoaded', function() {
    // Set today's date as default
    const dateInput = document.getElementById('attendanceDate');
    if (dateInput) {
        dateInput.valueAsDate = new Date();
    }
    
    // Initialize grading period selector if it exists
    const periodSelect = document.getElementById('gradingPeriodSelect');
    if (periodSelect) {
        periodSelect.value = currentGradingPeriod;
    }
    
    // Load data from PHP
    if (window.classData) {
        loadData();
    }
    
    initializeEventListeners();
});

// Load all data from backend
function loadData() {
    showLoading('Loading student data...');
    
    const formData = new FormData();
    formData.append('action', 'get_data');
    formData.append('class_id', window.classData.class_id);
    formData.append('grading_period', currentGradingPeriod);
    
    fetch('../api/teacher/view-grades-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            students = result.students || [];
            grades = result.grades || [];
            attendance = result.attendance || [];
            attendanceDates = result.attendanceDates || [];
            activityColumns = result.activityColumns || [];
            
            console.log('Loaded data:', {
                students: students.length,
                grades: grades.length,
                attendance: attendance.length,
                attendanceDates: attendanceDates.length,
                activityColumns: activityColumns.length
            });
            
            renderHeaders();
            renderTable();
            updateInfo();
            updateStudentCount();
        } else {
            showToast('Failed to load data: ' + result.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error loading data:', error);
        showToast('Network error: Failed to load data', 'error');
    });
}

// Format date for display
function formatDate(dateStr) {
    const date = new Date(dateStr + 'T00:00:00');
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Show loading overlay
function showLoading(message = 'Processing...') {
    if (document.getElementById('loadingOverlay')) return;
    
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>${message}</p>
        </div>
    `;
    document.body.appendChild(overlay);
    isLoading = true;
}

// Hide loading overlay
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
    isLoading = false;
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    const container = document.querySelector('.content-wrapper');
    if (container) {
        container.insertBefore(toast, container.firstChild);
        
        setTimeout(() => {
            toast.style.animation = 'slideUp 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Update student count display
function updateStudentCount() {
    const countElement = document.getElementById('studentCount');
    if (countElement) {
        countElement.textContent = students.length;
    }
}

// Render table headers
function renderHeaders() {
    const header = document.getElementById('tableHeader');
    if (!header) return;
    
    let headerHTML = '<tr>';
    headerHTML += '<th class="col-number">#</th>';
    headerHTML += '<th class="col-student-name">Student Name<span class="date-label">Surname, Firstname</span></th>';
    
    // Add attendance columns
    attendanceDates.forEach(date => {
        headerHTML += `<th class="col-attendance" data-date="${date}">
            Attendance
            <span class="date-label">${formatDate(date)}</span>
            <button class="btn-delete-column" onclick="deleteAttendanceColumn('${date}')" title="Delete column">
                <i class="fas fa-times"></i>
            </button>
        </th>`;
    });
    
    // Add grade columns (default + custom)
    activityColumns.forEach(col => {
        const isDefault = ['Recitation', 'Midterm Exam', 'Final Exam'].includes(col.activity_name);
        
        headerHTML += `<th class="col-grade" data-activity="${col.activity_name}">
            ${col.activity_name}
            ${!isDefault ? `<button class="btn-delete-column" onclick="deleteGradeColumn('${col.activity_name}')" title="Delete column">
                <i class="fas fa-times"></i>
            </button>` : ''}
        </th>`;
    });
    
    headerHTML += '<th class="col-total">Total</th>';
    headerHTML += '<th class="col-average">Average</th>';
    headerHTML += '</tr>';
    
    header.innerHTML = headerHTML;
}

// Get grade for student and activity
function getGrade(studentId, activityName) {
    const grade = grades.find(g => 
        g.student_id == studentId && g.activity_name === activityName
    );
    return grade ? parseFloat(grade.score) || 0 : 0;
}

// Get max score for activity
function getMaxScore(activityName) {
    const activity = activityColumns.find(col => col.activity_name === activityName);
    return activity ? parseFloat(activity.max_score) || 100 : 100;
}

// Get attendance for student and date
function getAttendanceStatus(studentId, date) {
    const att = attendance.find(a => 
        a.student_id == studentId && a.attendance_date === date
    );
    return att ? att.status : 'present';
}

// Calculate total score
function calculateTotal(studentId) {
    let total = 0;
    
    activityColumns.forEach(col => {
        const grade = getGrade(studentId, col.activity_name);
        total += parseFloat(grade) || 0;
    });
    
    return total.toFixed(2);
}

// Calculate average percentage
function calculateAverage(studentId) {
    if (activityColumns.length === 0) return '0.00';
    
    let totalPercentage = 0;
    
    activityColumns.forEach(col => {
        const grade = getGrade(studentId, col.activity_name);
        const maxScore = col.max_score;
        const percentage = maxScore > 0 ? (grade / maxScore) * 100 : 0;
        totalPercentage += percentage;
    });
    
    return (totalPercentage / activityColumns.length).toFixed(2);
}

// Render table body
function renderTable() {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;
    
    const totalColumns = 2 + attendanceDates.length + activityColumns.length + 2;
    
    if (students.length === 0) {
        tbody.innerHTML = `
            <tr class="no-data-row">
                <td colspan="${totalColumns}" style="text-align: center; padding: 2rem; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                    Waiting for students to enroll...
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    
    students.forEach((student, index) => {
        const total = calculateTotal(student.user_id);
        const average = calculateAverage(student.user_id);
        
        html += `<tr data-student-id="${student.user_id}">
            <td>${index + 1}</td>
            <td class="student-name-cell student-name">${escapeHtml(student.formatted_name)}</td>`;
        
        // Attendance cells
        attendanceDates.forEach(date => {
            const status = getAttendanceStatus(student.user_id, date);
            html += `<td class="attendance-cell">
                <select class="attendance-select ${status}" 
                        data-student-id="${student.user_id}" 
                        data-date="${date}">
                    <option value="present" ${status === 'present' ? 'selected' : ''}>Present</option>
                    <option value="absent" ${status === 'absent' ? 'selected' : ''}>Absent</option>
                    <option value="late" ${status === 'late' ? 'selected' : ''}>Late</option>
                    <option value="excused" ${status === 'excused' ? 'selected' : ''}>Excused</option>
                </select>
            </td>`;
        });
        
        // Grade cells
        activityColumns.forEach(col => {
            const grade = getGrade(student.user_id, col.activity_name);
            html += `<td class="editable" 
                        data-student-id="${student.user_id}" 
                        data-activity="${escapeHtml(col.activity_name)}"
                        data-max="${col.max_score}"
                        title="Click to edit (Max: ${col.max_score})">
                ${grade}
            </td>`;
        });
        
        html += `<td class="total-cell" title="Total Score">${total}</td>`;
        html += `<td class="total-cell" title="Average Percentage">${average}%</td>`;
        html += `</tr>`;
    });
    
    tbody.innerHTML = html;
    attachEventListeners();
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Update info panel
function updateInfo() {
    const attendanceInfo = document.getElementById('attendanceInfo');
    const gradeInfo = document.getElementById('gradeInfo');
    
    if (!attendanceInfo || !gradeInfo) return;
    
    if (attendanceDates.length === 0) {
        attendanceInfo.textContent = 'No attendance sessions added yet';
    } else {
        const dates = attendanceDates.slice(0, 3).map(d => formatDate(d)).join(', ');
        const more = attendanceDates.length > 3 ? ` and ${attendanceDates.length - 3} more` : '';
        attendanceInfo.textContent = `${attendanceDates.length} session(s): ${dates}${more}`;
    }
    
    if (activityColumns.length === 0) {
        gradeInfo.textContent = 'Default columns loaded';
    } else {
        const summary = {};
        activityColumns.forEach(col => {
            const type = col.activity_type.charAt(0).toUpperCase() + col.activity_type.slice(1);
            summary[type] = (summary[type] || 0) + 1;
        });
        const parts = Object.entries(summary).map(([type, count]) => 
            `${count} ${type}${count > 1 ? 's' : ''}`
        );
        gradeInfo.textContent = parts.join(', ');
    }
}

function attachEventListeners() {
    // Editable grade cells
    document.querySelectorAll('.editable').forEach(cell => {
        cell.addEventListener('click', function(e) {
            if (this.querySelector('input') || isLoading) return;
            
            const currentValue = this.textContent.trim();
            const maxScore = parseFloat(this.dataset.max);
            const input = document.createElement('input');
            input.type = 'number';
            input.value = currentValue;
            input.min = '0';
            input.max = maxScore.toString();
            input.step = '0.5';
            input.className = 'grade-input';
            
            this.textContent = '';
            this.appendChild(input);
            input.focus();
            input.select();
            
            const saveValue = () => {
                let newValue = parseFloat(input.value);
                
                // Validate
                if (isNaN(newValue)) newValue = 0;
                if (newValue > maxScore) {
                    showToast(`Score cannot exceed ${maxScore}`, 'error');
                    newValue = maxScore;
                }
                if (newValue < 0) newValue = 0;
                
                // Round to 2 decimal places
                newValue = Math.round(newValue * 100) / 100;
                
                const studentId = this.dataset.studentId;
                const activityName = this.dataset.activity;
                
                // Save to server
                saveGradeToServer(studentId, activityName, newValue);
                
                // Update local data
                const gradeIndex = grades.findIndex(g => 
                    g.student_id == studentId && g.activity_name === activityName
                );
                if (gradeIndex !== -1) {
                    grades[gradeIndex].score = newValue;
                } else {
                    grades.push({
                        student_id: studentId,
                        activity_name: activityName,
                        score: newValue
                    });
                }
                
                renderTable();
            };
            
            input.addEventListener('blur', saveValue);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveValue();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    renderTable();
                }
            });
        });
    });
    
    // Attendance selects
    document.querySelectorAll('.attendance-select').forEach(select => {
        select.addEventListener('change', function() {
            if (isLoading) return;
            
            const studentId = this.dataset.studentId;
            const date = this.dataset.date;
            const status = this.value;
            
            saveAttendanceToServer(studentId, date, status);
            
            const attIndex = attendance.findIndex(a => 
                a.student_id == studentId && a.attendance_date === date
            );
            if (attIndex !== -1) {
                attendance[attIndex].status = status;
            } else {
                attendance.push({
                    student_id: studentId,
                    attendance_date: date,
                    status: status
                });
            }
            
            // Update visual styling
            this.className = `attendance-select ${status}`;
        });
    });
}

// Save grade to server
function saveGradeToServer(studentId, activityName, score) {
    const formData = new FormData();
    formData.append('action', 'save_grade');
    formData.append('student_id', studentId);
    formData.append('class_id', window.classData.class_id);
    formData.append('activity_name', activityName);
    formData.append('score', score);
    formData.append('grading_period', currentGradingPeriod);
    
    fetch('../api/teacher/view-grades-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('Grade saved successfully', 'success');
        } else {
            console.error('Error saving grade:', result.message);
            showToast('Failed to save grade: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error: Failed to save grade', 'error');
    });
}

// Save attendance to server
function saveAttendanceToServer(studentId, date, status) {
    const formData = new FormData();
    formData.append('action', 'save_attendance');
    formData.append('student_id', studentId);
    formData.append('class_id', window.classData.class_id);
    formData.append('attendance_date', date);
    formData.append('status', status);
    
    fetch('../api/teacher/view-grades-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('Attendance saved', 'success');
        } else {
            console.error('Error saving attendance:', result.message);
            showToast('Failed to save attendance: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error: Failed to save attendance', 'error');
    });
}

// Add attendance column
function addAttendanceColumn(date) {
    if (!date) {
        showToast('Please select a date', 'error');
        return;
    }
    
    if (attendanceDates.includes(date)) {
        showToast('Attendance for this date already exists', 'error');
        return;
    }
    
    if (students.length === 0) {
        showToast('No students enrolled yet. Please wait for students to join.', 'error');
        return;
    }
    
    showLoading('Adding attendance column...');
    
    const formData = new FormData();
    formData.append('action', 'add_attendance_date');
    formData.append('class_id', window.classData.class_id);
    formData.append('attendance_date', date);
    
    fetch('../api/teacher/view-grades-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast('Attendance column added successfully', 'success');
            setTimeout(() => loadData(), 500);
        } else {
            showToast('Failed to add attendance: ' + result.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showToast('Network error: Failed to add attendance column', 'error');
    });
}

// Add grade column
function addGradeColumn(type) {
    if (students.length === 0) {
        showToast('No students enrolled yet. Please wait for students to join.', 'error');
        return;
    }
    
    let maxScore = prompt(`Enter maximum score for new ${type}:`, '100');
    
    if (maxScore === null) return;
    
    maxScore = parseFloat(maxScore);
    
    if (isNaN(maxScore) || maxScore <= 0) {
        showToast('Invalid maximum score. Please enter a positive number.', 'error');
        return;
    }
    
    showLoading('Adding grade column...');
    
    const formData = new FormData();
    formData.append('action', 'add_grade_column');
    formData.append('class_id', window.classData.class_id);
    formData.append('activity_type', type);
    formData.append('max_score', maxScore);
    formData.append('grading_period', currentGradingPeriod);
    
    fetch('../api/teacher/view-grades-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast('Grade column added successfully', 'success');
            setTimeout(() => loadData(), 500);
        } else {
            showToast('Failed to add grade column: ' + result.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showToast('Network error: Failed to add grade column', 'error');
    });
}

// Delete attendance column
function deleteAttendanceColumn(date) {
    if (!confirm(`Delete attendance for ${formatDate(date)}?\n\nThis will remove all attendance records for this date.`)) {
        return;
    }
    
    showLoading('Deleting attendance column...');
    
    const formData = new FormData();
    formData.append('action', 'delete_attendance_date');
    formData.append('class_id', window.classData.class_id);
    formData.append('attendance_date', date);
    
    fetch('../api/teacher/view-grades-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast('Attendance column deleted successfully', 'success');
            setTimeout(() => loadData(), 500);
        } else {
            showToast('Failed to delete attendance: ' + result.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showToast('Network error: Failed to delete attendance column', 'error');
    });
}

// Delete grade column
function deleteGradeColumn(activityName) {
    if (!confirm(`Delete ${activityName} column?\n\nThis will remove all grades for this activity.`)) {
        return;
    }
    
    showLoading('Deleting grade column...');
    
    const formData = new FormData();
    formData.append('action', 'delete_grade_column');
    formData.append('class_id', window.classData.class_id);
    formData.append('activity_name', activityName);
    
    fetch('../api/teacher/view-grades-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast('Grade column deleted successfully', 'success');
            setTimeout(() => loadData(), 500);
        } else {
            showToast('Failed to delete grade column: ' + result.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showToast('Network error: Failed to delete grade column', 'error');
    });
}

// Sort functionality
function sortStudents(sortType) {
    students.sort((a, b) => {
        if (sortType === 'name-az') {
            return a.formatted_name.localeCompare(b.formatted_name);
        } else if (sortType === 'name-za') {
            return b.formatted_name.localeCompare(a.formatted_name);
        } else if (sortType === 'grade-high') {
            return parseFloat(calculateTotal(b.user_id)) - parseFloat(calculateTotal(a.user_id));
        } else if (sortType === 'grade-low') {
            return parseFloat(calculateTotal(a.user_id)) - parseFloat(calculateTotal(b.user_id));
        }
    });
    
    renderTable();
}

// Change grading period
function changeGradingPeriod(period) {
    currentGradingPeriod = period;
    loadData();
}

// Initialize all event listeners
function initializeEventListeners() {
    // Add attendance button
    const addAttendanceBtn = document.getElementById('addAttendanceBtn');
    if (addAttendanceBtn) {
        addAttendanceBtn.addEventListener('click', function() {
            const dateInput = document.getElementById('attendanceDate');
            if (dateInput && dateInput.value) {
                addAttendanceColumn(dateInput.value);
            }
        });
    }
    
    // Sort select
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            sortStudents(this.value);
        });
    }
    
    // Grading period select
    const periodSelect = document.getElementById('gradingPeriodSelect');
    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            changeGradingPeriod(this.value);
        });
    }
    
    // Add column buttons
    document.querySelectorAll('.btn-add').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            if (type) {
                addGradeColumn(type);
            }
        });
    });
    
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadData();
        });
    }
}

window.deleteAttendanceColumn = deleteAttendanceColumn;
window.deleteGradeColumn = deleteGradeColumn;
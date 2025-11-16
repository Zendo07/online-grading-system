document.addEventListener('DOMContentLoaded', function() {
    createFloatingCircles();
    
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.login-btn');
            submitBtn.textContent = 'Logging in...';
            submitBtn.disabled = true;
        });
    }
    
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

function createFloatingCircles() {
    const circlesContainer = document.getElementById('circlesContainer');
    if (!circlesContainer) return;
    
    const colors = ['#7b2d26', '#D96C3D', '#8B4049', '#D4A373'];
    const numberOfCircles = 8;
    
    for (let i = 0; i < numberOfCircles; i++) {
        const circle = document.createElement('div');
        circle.className = 'circle';
        
        const size = Math.random() * 150 + 50;
        circle.style.width = size + 'px';
        circle.style.height = size + 'px';
        
        // Random position
        circle.style.left = Math.random() * 100 + '%';
        circle.style.top = Math.random() * 100 + '%';
        circle.style.background = colors[Math.floor(Math.random() * colors.length)];
        
        // Random animation delay and duration
        circle.style.animationDelay = Math.random() * 5 + 's';
        circle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        
        circlesContainer.appendChild(circle);
    }
}

// Role button interactions
const studentBtn = document.getElementById('studentBtn');
const professorBtn = document.getElementById('professorBtn');

if (studentBtn) {
    studentBtn.addEventListener('click', function(e) {
        this.classList.add('active');
        if (professorBtn) professorBtn.classList.remove('active');
    });
}

if (professorBtn) {
    professorBtn.addEventListener('click', function(e) {
        this.classList.add('active');
        if (studentBtn) studentBtn.classList.remove('active');
    });
}
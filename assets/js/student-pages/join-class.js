document.getElementById('class_code').addEventListener('input', function(e) {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
});

document.getElementById('joinForm').addEventListener('submit', function(e) {
    const classCode = document.getElementById('class_code').value.trim();
    
    if (classCode.length < 6) {
        e.preventDefault();
        alert('Class code must be at least 6 characters long.');
        return false;
    }
});

const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

let currentSlide = 0;
const sliderCards = document.querySelectorAll('.slider-card');
const indicators = document.querySelectorAll('.indicator');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

function updateSlider(index) {
    sliderCards.forEach(card => card.classList.remove('active'));
    indicators.forEach(ind => {
        ind.classList.remove('active');
        ind.setAttribute('aria-selected', 'false');
    });

    sliderCards[index].classList.add('active');
    indicators[index].classList.add('active');
    indicators[index].setAttribute('aria-selected', 'true');

    if (index === 0) {
        prevBtn.style.opacity = '0';
        prevBtn.style.pointerEvents = 'none';
        prevBtn.setAttribute('aria-disabled', 'true');
        prevBtn.setAttribute('tabindex', '-1');
    } else {
        prevBtn.style.opacity = '1';
        prevBtn.style.pointerEvents = 'auto';
        prevBtn.setAttribute('aria-disabled', 'false');
        prevBtn.setAttribute('tabindex', '0');
    }
    
    if (index === sliderCards.length - 1) {
        nextBtn.style.opacity = '0';
        nextBtn.style.pointerEvents = 'none';
        nextBtn.setAttribute('aria-disabled', 'true');
        nextBtn.setAttribute('tabindex', '-1');
    } else {
        nextBtn.style.opacity = '1';
        nextBtn.style.pointerEvents = 'auto';
        nextBtn.setAttribute('aria-disabled', 'false');
        nextBtn.setAttribute('tabindex', '0');
    }

    currentSlide = index;
    const announcement = `Slide ${index + 1} of ${sliderCards.length}`;
    announceToScreenReader(announcement);
}

nextBtn.addEventListener('click', () => {
    if (currentSlide < sliderCards.length - 1) {
        updateSlider(currentSlide + 1);
    }
});

prevBtn.addEventListener('click', () => {
    if (currentSlide > 0) {
        updateSlider(currentSlide - 1);
    }
});

indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
        updateSlider(index);
    });
    
    indicator.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            updateSlider(index);
        }
    });
});

updateSlider(0);

document.addEventListener('keydown', (e) => {
    // Only handle arrow keys if not focused on an input
    if (document.activeElement.tagName === 'INPUT') {
        return;
    }
    
    if (e.key === 'ArrowRight' && currentSlide < sliderCards.length - 1) {
        e.preventDefault();
        updateSlider(currentSlide + 1);
    } else if (e.key === 'ArrowLeft' && currentSlide > 0) {
        e.preventDefault();
        updateSlider(currentSlide - 1);
    }
});

function announceToScreenReader(message) {

    let liveRegion = document.getElementById('slider-live-region');
    if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.id = 'slider-live-region';
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.style.position = 'absolute';
        liveRegion.style.left = '-10000px';
        liveRegion.style.width = '1px';
        liveRegion.style.height = '1px';
        liveRegion.style.overflow = 'hidden';
        document.body.appendChild(liveRegion);
    }
    
    // Clear and set the message
    liveRegion.textContent = '';
    setTimeout(() => {
        liveRegion.textContent = message;
    }, 100);
}
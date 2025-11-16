document.addEventListener('DOMContentLoaded', function() {
    initHorizontalScroll();
    initCopyButtons();
    initModal();
});

function initHorizontalScroll() {
    const wrapper = document.getElementById('sectionsWrapper');
    const scrollLeft = document.getElementById('scrollLeft');
    const scrollRight = document.getElementById('scrollRight');
    
    if (!wrapper || !scrollLeft || !scrollRight) return;
    
    function checkScroll() {
        const isScrollable = wrapper.scrollWidth > wrapper.clientWidth;
        
        if (isScrollable) {
            scrollLeft.style.display = wrapper.scrollLeft > 0 ? 'flex' : 'none';
            scrollRight.style.display = 
                wrapper.scrollLeft < (wrapper.scrollWidth - wrapper.clientWidth - 5) ? 'flex' : 'none';
        } else {
            scrollLeft.style.display = 'none';
            scrollRight.style.display = 'none';
        }
    }
    
    scrollLeft.addEventListener('click', function() {
        wrapper.scrollBy({
            left: -350,
            behavior: 'smooth'
        });
    });
    
    scrollRight.addEventListener('click', function() {
        wrapper.scrollBy({
            left: 350,
            behavior: 'smooth'
        });
    });
    
    wrapper.addEventListener('scroll', checkScroll);
    checkScroll();
    window.addEventListener('resize', checkScroll);
    
    let isDown = false;
    let startX;
    let scrollLeftPos;
    
    wrapper.addEventListener('mousedown', function(e) {
        isDown = true;
        wrapper.style.cursor = 'grabbing';
        startX = e.pageX - wrapper.offsetLeft;
        scrollLeftPos = wrapper.scrollLeft;
    });
    
    wrapper.addEventListener('mouseleave', function() {
        isDown = false;
        wrapper.style.cursor = 'grab';
    });
    
    wrapper.addEventListener('mouseup', function() {
        isDown = false;
        wrapper.style.cursor = 'grab';
    });
    
    wrapper.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - wrapper.offsetLeft;
        const walk = (x - startX) * 2;
        wrapper.scrollLeft = scrollLeftPos - walk;
    });
}

function initCopyButtons() {
    document.addEventListener('click', function(e) {
        const copyBtn = e.target.closest('.btn-copy-code');
        if (copyBtn) {
            e.preventDefault();
            e.stopPropagation();
            copyToClipboard(copyBtn.dataset.code, copyBtn);
        }
    });
    
    const copyModalBtn = document.getElementById('copyModalBtn');
    if (copyModalBtn) {
        copyModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const code = document.getElementById('classCode').textContent;
            const btnText = document.getElementById('copyBtnText');
            
            copyToClipboard(code, copyModalBtn, function() {
                btnText.textContent = 'Copied!';
                setTimeout(() => btnText.textContent = 'Copy', 2000);
            });
        });
    }
}

function copyToClipboard(text, button, callback) {
    if (!text) return;
    
    navigator.clipboard.writeText(text).then(() => {
        // Visual feedback
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.style.background = '#10b981';
        
        // Custom callback
        if (callback) callback();
        
        // Reset after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.style.background = '';
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy code. Please try again.');
    });
}

function initModal() {
    const modal = document.getElementById('codeModal');
    const closeBtn = document.getElementById('closeModalBtn');
    
    if (!modal) return;

    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            e.preventDefault();
            closeModal();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
}

function closeModal() {
    const modal = document.getElementById('codeModal');
    if (modal) {
        modal.classList.remove('show');
        
        const url = new URL(window.location);
        url.searchParams.delete('show_code');
        window.history.replaceState({}, '', url);
    }
}

function initAlertDismiss() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initHorizontalScroll();
    initCopyButtons();
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
        const cardWidth = wrapper.querySelector('.section-card')?.offsetWidth || 350;
        wrapper.scrollBy({
            left: -(cardWidth + 24), 
            behavior: 'smooth'
        });
    });
    
    scrollRight.addEventListener('click', function() {
        const cardWidth = wrapper.querySelector('.section-card')?.offsetWidth || 350;
        wrapper.scrollBy({
            left: cardWidth + 24, 
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

/**
 * Copy Class Code Functionality
 */
function initCopyButtons() {
    // Copy buttons in section cards
    document.addEventListener('click', function(e) {
        const copyBtn = e.target.closest('.btn-copy-code');
        if (copyBtn) {
            e.preventDefault();
            e.stopPropagation();
            copyToClipboard(copyBtn.dataset.code, copyBtn);
        }
    });
}

function copyToClipboard(text, button) {
    if (!text) return;
    
    navigator.clipboard.writeText(text).then(() => {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.style.background = '#10b981';
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.style.background = '';
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy code. Please try again.');
    });
}
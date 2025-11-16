document.addEventListener('DOMContentLoaded', function() {
    initCardInteractions();
    initEmailTracking();
    initResponsiveCards();
    initAccessibility();
    
    console.log('My Professors page loaded successfully');
});

function initCardInteractions() {
    const cards = document.querySelectorAll('.professor-card');
    
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.professor-email')) {
                return;
            }
        });
        
        card.setAttribute('tabindex', '0');
        
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });

        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    });
}

function initEmailTracking() {
    const emailLinks = document.querySelectorAll('.professor-email');
    
    emailLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const professorName = this.closest('.professor-info')
                .querySelector('.professor-name').textContent;
            const email = this.getAttribute('href').replace('mailto:', '');
            
            console.log(`Email clicked: ${professorName} (${email})`);
        });
    });
}

function initResponsiveCards() {
    const grid = document.querySelector('.professors-grid');
    if (!grid) return;
    
    function adjustCardLayout() {
        const cards = document.querySelectorAll('.professor-card');
        const gridWidth = grid.offsetWidth;
        
        let columns;
        if (gridWidth >= 1200) {
            columns = 3;
        } else if (gridWidth >= 768) {
            columns = 2;
        } else {
            columns = 1;
        }
        
        document.body.setAttribute('data-card-columns', columns);
    }
    
    adjustCardLayout();
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(adjustCardLayout, 250);
    });
}

function initAccessibility() {
    const cards = document.querySelectorAll('.professor-card');
    
    cards.forEach(card => {
        const subjectTitle = card.querySelector('.subject-title');
        const professorName = card.querySelector('.professor-name');
        
        if (subjectTitle && professorName) {
            const ariaLabel = `${subjectTitle.textContent} - Instructor: ${professorName.textContent}`;
            card.setAttribute('aria-label', ariaLabel);
        }
    });
    
    const emailLinks = document.querySelectorAll('.professor-email');
    emailLinks.forEach(link => {
        const professorName = link.closest('.professor-info')
            .querySelector('.professor-name').textContent;
        link.setAttribute('aria-label', `Email ${professorName}`);
    });
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

function initSearch() {
    const searchInput = document.querySelector('#professorSearch');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.professor-card');
        
        cards.forEach(card => {
            const subjectName = card.querySelector('.subject-title').textContent.toLowerCase();
            const subjectCode = card.querySelector('.subject-badge').textContent.toLowerCase();
            const professorName = card.querySelector('.professor-name').textContent.toLowerCase();
            
            const matches = subjectName.includes(searchTerm) || 
                          subjectCode.includes(searchTerm) || 
                          professorName.includes(searchTerm);
            
            if (matches) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, 10);
            } else {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
        
        const visibleCards = Array.from(cards).filter(card => 
            card.style.display !== 'none'
        );
        
        showNoResultsMessage(visibleCards.length === 0);
    });
}

function showNoResultsMessage(show) {
    let noResults = document.querySelector('.no-results-message');
    
    if (show && !noResults) {
        noResults = document.createElement('div');
        noResults.className = 'no-results-message';
        noResults.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <h3>No professors found</h3>
                <p>Try adjusting your search terms</p>
            </div>
        `;
        document.querySelector('.professors-container').appendChild(noResults);
    } else if (!show && noResults) {
        noResults.remove();
    }
}

document.addEventListener('error', function(e) {
    if (e.target.tagName === 'IMG' && e.target.closest('.professor-avatar')) {
        const defaultAvatar = window.BASE_URL + 'assets/images/default-avatar.png';
        if (e.target.src !== defaultAvatar) {
            e.target.src = defaultAvatar;
        }
    }
}, true);

function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const images = document.querySelectorAll('.professor-avatar img');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px'
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
}

window.ProfessorsPage = {
    scrollToTop,
    initSearch
};
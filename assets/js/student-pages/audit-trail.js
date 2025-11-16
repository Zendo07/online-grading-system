(function() {
    'use strict';
    
    const debugSwitch = document.getElementById('debugSwitch');
    
    if (debugSwitch) {
        debugSwitch.addEventListener('click', function() {
            const isActive = this.classList.contains('active');
            const newUrl = new URL(window.location.href);
            
            newUrl.searchParams.set('debug', isActive ? '0' : '1');
            window.location.href = newUrl.toString();
        });
    }
    
    const observeLogEntries = () => {
        const logEntries = document.querySelectorAll('.log-entry');
        
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
            
            logEntries.forEach(entry => {
                entry.style.opacity = '0';
                entry.style.transform = 'translateY(20px)';
                entry.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                observer.observe(entry);
            });
        }
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', observeLogEntries);
    } else {
        observeLogEntries();
    }

    const loadLogsAjax = async () => {
        try {
            const response = await fetch(window.location.pathname + '?ajax=1');
            const data = await response.json();
            
            if (data.success) {
                console.log('Loaded logs:', data.logs);
            }
        } catch (error) {
            console.error('Error loading logs:', error);
        }
    };
    
    const handleDebugExpansion = () => {
        const debugBlocks = document.querySelectorAll('.log-debug');
        
        debugBlocks.forEach(block => {
            block.style.cursor = 'pointer';
            block.title = 'Click to expand/collapse';
            
            block.addEventListener('click', function() {
                if (this.style.maxHeight === 'none') {
                    this.style.maxHeight = '150px';
                } else {
                    this.style.maxHeight = 'none';
                }
            });
        });
    };
    
    handleDebugExpansion();
    
    document.addEventListener('keydown', (e) => {
        if (e.altKey && e.key === 'd' && debugSwitch) {
            debugSwitch.click();
        }
        
        if (e.altKey && e.key === 'r') {
            e.preventDefault();
            location.reload();
        }
    });
    
    if (window.performance && console.table) {
        const perfData = window.performance.getEntriesByType('navigation')[0];
        if (perfData) {
            console.log('Page Load Performance:', {
                'DNS Lookup': `${(perfData.domainLookupEnd - perfData.domainLookupStart).toFixed(2)}ms`,
                'TCP Connection': `${(perfData.connectEnd - perfData.connectStart).toFixed(2)}ms`,
                'Server Response': `${(perfData.responseEnd - perfData.requestStart).toFixed(2)}ms`,
                'DOM Processing': `${(perfData.domComplete - perfData.domLoading).toFixed(2)}ms`,
                'Total Load Time': `${(perfData.loadEventEnd - perfData.fetchStart).toFixed(2)}ms`
            });
        }
    }
    
})();
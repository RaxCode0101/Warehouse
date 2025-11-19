// Utility functions for the application

/**
 * Format number to Indonesian Rupiah
 * @param {number} number - The number to format
 * @returns {string} - Formatted Rupiah string
 */
function formatRupiah(number) {
    if (isNaN(number)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(number);
}

/**
 * Format date to Indonesian format
 * @param {string} dateString - The date string to format
 * @returns {string} - Formatted date string
 */
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    }).format(date);
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} - True if valid email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate phone number (Indonesian format)
 * @param {string} phone - Phone number to validate
 * @returns {boolean} - True if valid phone
 */
function validatePhone(phone) {
    const re = /^(\+62|62|0)8[1-9][0-9]{6,10}$/;
    return re.test(phone);
}

/**
 * Show loading state
 * @param {string} elementId - Element ID to show loading
 */
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="flex justify-center items-center py-4"><div class="loading-spinner"></div></div>';
    }
}

/**
 * Hide loading state
 * @param {string} elementId - Element ID to hide loading
 * @param {string} content - Content to restore
 */
function hideLoading(elementId, content = '') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = content;
    }
}

/**
 * Show alert message
 * @param {string} message - Message to show
 * @param {string} type - Type of alert (success, error, warning)
 */
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `notification notification-${type} animate-slide-in`;
    alertDiv.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-3">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle'} text-lg"></i>
            </div>
            <div class="flex-1">
                <p class="font-medium">${message}</p>
            </div>
            <button class="ml-3 text-current opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * Debounce function
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Toggle dark mode
 */
function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');

    if (isDark) {
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }

    // Update theme toggle button
    updateThemeToggle();
}

/**
 * Update theme toggle button state
 */
function updateThemeToggle() {
    const toggle = document.querySelector('.theme-toggle');
    if (toggle) {
        const isDark = document.documentElement.classList.contains('dark');
        toggle.setAttribute('aria-checked', isDark.toString());
    }
}

/**
 * Initialize theme on page load
 */
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.classList.add('dark');
    }

    updateThemeToggle();
}

/**
 * Create skeleton loader
 * @param {number} rows - Number of skeleton rows
 * @param {number} columns - Number of skeleton columns
 * @returns {string} - HTML string for skeleton loader
 */
function createSkeletonLoader(rows = 5, columns = 4) {
    let html = '<div class="animate-pulse">';
    for (let i = 0; i < rows; i++) {
        html += '<div class="flex space-x-4 mb-4">';
        for (let j = 0; j < columns; j++) {
            const width = j === 0 ? 'w-12 h-12' : j === columns - 1 ? 'w-20' : 'flex-1';
            html += `<div class="skeleton ${width} h-4 rounded"></div>`;
        }
        html += '</div>';
    }
    html += '</div>';
    return html;
}

/**
 * Enhanced modal system
 */
class Modal {
    constructor(options = {}) {
        this.options = {
            title: '',
            content: '',
            size: 'md',
            closable: true,
            ...options
        };
        this.modal = null;
        this.overlay = null;
    }

    show() {
        this.createModal();
        document.body.appendChild(this.overlay);
        document.body.style.overflow = 'hidden';

        // Focus trap
        this.modal.focus();

        // Close on escape
        const handleEscape = (e) => {
            if (e.key === 'Escape' && this.options.closable) {
                this.close();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }

    close() {
        if (this.overlay) {
            this.overlay.remove();
            document.body.style.overflow = '';
        }
    }

    createModal() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'modal-overlay';

        const sizeClasses = {
            sm: 'max-w-md',
            md: 'max-w-lg',
            lg: 'max-w-2xl',
            xl: 'max-w-4xl',
            full: 'max-w-full'
        };

        this.overlay.innerHTML = `
            <div class="modal-content ${sizeClasses[this.options.size]} w-full" tabindex="-1">
                ${this.options.closable ? `
                    <div class="flex justify-between items-center p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">${this.options.title}</h3>
                        <button class="text-gray-400 hover:text-gray-600" onclick="this.closest('.modal-overlay').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                ` : ''}
                <div class="p-6">
                    ${this.options.content}
                </div>
            </div>
        `;

        // Close on overlay click
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay && this.options.closable) {
                this.close();
            }
        });
    }
}

/**
 * Create enhanced tooltip
 * @param {HTMLElement} element - Element to attach tooltip to
 * @param {string} content - Tooltip content
 * @param {string} position - Tooltip position (top, bottom, left, right)
 */
function createTooltip(element, content, position = 'top') {
    let tooltip = null;

    const showTooltip = () => {
        if (tooltip) return;

        tooltip = document.createElement('div');
        tooltip.className = `absolute z-50 px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-lg pointer-events-none transition-opacity duration-200`;
        tooltip.textContent = content;
        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        let top, left;

        switch (position) {
            case 'top':
                top = rect.top - tooltipRect.height - 8;
                left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'bottom':
                top = rect.bottom + 8;
                left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'left':
                top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
                left = rect.left - tooltipRect.width - 8;
                break;
            case 'right':
                top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
                left = rect.right + 8;
                break;
        }

        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
        tooltip.style.opacity = '1';
    };

    const hideTooltip = () => {
        if (tooltip) {
            tooltip.style.opacity = '0';
            setTimeout(() => {
                if (tooltip) {
                    tooltip.remove();
                    tooltip = null;
                }
            }, 200);
        }
    };

    element.addEventListener('mouseenter', showTooltip);
    element.addEventListener('mouseleave', hideTooltip);
    element.addEventListener('focus', showTooltip);
    element.addEventListener('blur', hideTooltip);
}

/**
 * Smooth scroll to element
 * @param {string} selector - CSS selector of target element
 * @param {number} offset - Offset from top
 */
function smoothScrollTo(selector, offset = 0) {
    const element = document.querySelector(selector);
    if (element) {
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
}

/**
 * Lazy load images
 */
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('skeleton');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

/**
 * Initialize all enhancements
 */
function initializeEnhancements() {
    initializeTheme();
    lazyLoadImages();

    // Add theme toggle button if it doesn't exist
    if (!document.querySelector('.theme-toggle')) {
        const toggle = document.createElement('button');
        toggle.className = 'theme-toggle';
        toggle.setAttribute('aria-label', 'Toggle dark mode');
        toggle.addEventListener('click', toggleDarkMode);

        // Add to header or a suitable location
        const header = document.querySelector('header');
        if (header) {
            header.appendChild(toggle);
        }
    }
}

// Export functions for global use
window.AppUtils = {
    formatRupiah,
    formatDate,
    validateEmail,
    validatePhone,
    showLoading,
    hideLoading,
    showAlert,
    debounce,
    toggleDarkMode,
    updateThemeToggle,
    initializeTheme,
    createSkeletonLoader,
    Modal,
    createTooltip,
    smoothScrollTo,
    lazyLoadImages,
    initializeEnhancements
};

// Initialize enhancements when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.AppUtils.initializeEnhancements();
});

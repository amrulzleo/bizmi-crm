// Layout and Navigation JavaScript

class LayoutManager {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.getElementById('mainContent');
        this.sidebarToggle = document.getElementById('sidebarToggle');
        this.isMobile = window.innerWidth <= 768;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.handleResize();
        this.initDropdowns();
        this.initSearch();
        this.setActiveNavItem();
    }

    bindEvents() {
        // Sidebar toggle
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Window resize
        window.addEventListener('resize', () => this.handleResize());

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', (e) => this.handleOutsideClick(e));

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
    }

    toggleSidebar() {
        if (this.isMobile) {
            this.sidebar.classList.toggle('mobile-open');
        } else {
            this.sidebar.classList.toggle('collapsed');
            this.mainContent.classList.toggle('expanded');
        }
    }

    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth <= 768;

        if (wasMobile !== this.isMobile) {
            // Reset classes when switching between mobile and desktop
            this.sidebar.classList.remove('mobile-open', 'collapsed');
            this.mainContent.classList.remove('expanded');
        }
    }

    handleOutsideClick(e) {
        if (this.isMobile && 
            this.sidebar.classList.contains('mobile-open') && 
            !this.sidebar.contains(e.target) && 
            !this.sidebarToggle.contains(e.target)) {
            this.sidebar.classList.remove('mobile-open');
        }
    }

    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + B to toggle sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            this.toggleSidebar();
        }

        // Escape to close mobile sidebar
        if (e.key === 'Escape' && this.isMobile && this.sidebar.classList.contains('mobile-open')) {
            this.sidebar.classList.remove('mobile-open');
        }
    }

    setActiveNavItem() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    }

    initDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const trigger = dropdown.querySelector('.user-avatar, .dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            if (trigger && menu) {
                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });

                // Prevent dropdown from closing when clicking inside menu
                menu.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }
        });
    }

    initSearch() {
        const searchInput = document.querySelector('.search-input');
        if (!searchInput) return;

        let searchTimeout;
        
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });

        // Search suggestions dropdown
        this.createSearchDropdown(searchInput);
    }

    createSearchDropdown(searchInput) {
        const searchBox = searchInput.closest('.search-box');
        const dropdown = document.createElement('div');
        dropdown.className = 'search-dropdown';
        dropdown.innerHTML = `
            <div class="search-results">
                <div class="search-section">
                    <div class="search-section-title">Contacts</div>
                    <div class="search-items" id="contactResults"></div>
                </div>
                <div class="search-section">
                    <div class="search-section-title">Deals</div>
                    <div class="search-items" id="dealResults"></div>
                </div>
                <div class="search-section">
                    <div class="search-section-title">Organizations</div>
                    <div class="search-items" id="organizationResults"></div>
                </div>
            </div>
        `;
        
        searchBox.appendChild(dropdown);
    }

    async performSearch(query) {
        if (query.length < 2) {
            this.hideSearchDropdown();
            return;
        }

        try {
            const response = await fetch(`${window.BASE_URL}/api/search?q=${encodeURIComponent(query)}`);
            const results = await response.json();
            
            this.displaySearchResults(results);
            this.showSearchDropdown();
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(results) {
        const contactResults = document.getElementById('contactResults');
        const dealResults = document.getElementById('dealResults');
        const organizationResults = document.getElementById('organizationResults');

        if (contactResults) {
            contactResults.innerHTML = this.renderSearchItems(results.contacts, 'contact');
        }
        if (dealResults) {
            dealResults.innerHTML = this.renderSearchItems(results.deals, 'deal');
        }
        if (organizationResults) {
            organizationResults.innerHTML = this.renderSearchItems(results.organizations, 'organization');
        }
    }

    renderSearchItems(items, type) {
        if (!items || items.length === 0) {
            return '<div class="search-no-results">No results found</div>';
        }

        return items.map(item => {
            let url, title, subtitle;
            
            switch (type) {
                case 'contact':
                    url = `${window.BASE_URL}/contacts/${item.id}`;
                    title = `${item.first_name} ${item.last_name}`;
                    subtitle = item.email;
                    break;
                case 'deal':
                    url = `${window.BASE_URL}/deals/${item.id}`;
                    title = item.name;
                    subtitle = `$${item.amount.toLocaleString()}`;
                    break;
                case 'organization':
                    url = `${window.BASE_URL}/organizations/${item.id}`;
                    title = item.name;
                    subtitle = item.industry;
                    break;
            }

            return `
                <a href="${url}" class="search-item">
                    <div class="search-item-title">${title}</div>
                    <div class="search-item-subtitle">${subtitle}</div>
                </a>
            `;
        }).join('');
    }

    showSearchDropdown() {
        const dropdown = document.querySelector('.search-dropdown');
        if (dropdown) {
            dropdown.classList.add('active');
        }
    }

    hideSearchDropdown() {
        const dropdown = document.querySelector('.search-dropdown');
        if (dropdown) {
            dropdown.classList.remove('active');
        }
    }
}

// Utility Functions
function toggleDropdown(element) {
    const dropdown = element.closest('.dropdown');
    dropdown.classList.toggle('active');
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    const intervals = [
        { label: 'year', seconds: 31536000 },
        { label: 'month', seconds: 2592000 },
        { label: 'week', seconds: 604800 },
        { label: 'day', seconds: 86400 },
        { label: 'hour', seconds: 3600 },
        { label: 'minute', seconds: 60 },
        { label: 'second', seconds: 1 }
    ];

    for (let interval of intervals) {
        const count = Math.floor(diffInSeconds / interval.seconds);
        if (count > 0) {
            return count === 1 ? `1 ${interval.label} ago` : `${count} ${interval.label}s ago`;
        }
    }

    return 'Just now';
}

// Theme Management
class ThemeManager {
    constructor() {
        this.init();
    }

    init() {
        this.loadTheme();
    }

    loadTheme() {
        const savedTheme = localStorage.getItem('bizmi-theme') || 'light';
        this.setTheme(savedTheme);
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('bizmi-theme', theme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }
}

// Notification System
class NotificationManager {
    constructor() {
        this.container = this.createContainer();
        this.notifications = [];
    }

    createContainer() {
        const container = document.createElement('div');
        container.className = 'notifications-container';
        document.body.appendChild(container);
        return container;
    }

    show(message, type = 'info', duration = 5000) {
        const notification = this.createNotification(message, type);
        this.container.appendChild(notification);
        
        // Animate in
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => this.remove(notification), duration);
        }

        return notification;
    }

    createNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icon = this.getIcon(type);
        
        notification.innerHTML = `
            <div class="notification-content">
                <i class="${icon}"></i>
                <span class="notification-message">${message}</span>
            </div>
            <button class="notification-close" onclick="window.notifications.remove(this.parentElement)">
                <i class="fas fa-times"></i>
            </button>
        `;

        return notification;
    }

    getIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    remove(notification) {
        notification.classList.add('removing');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
        }, 300);
    }
}

// Form Validation
class FormValidator {
    constructor() {
        this.forms = document.querySelectorAll('.needs-validation');
        this.init();
    }

    init() {
        this.forms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleSubmit(e, form));
            
            // Real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', () => this.clearFieldError(input));
            });
        });
    }

    handleSubmit(e, form) {
        e.preventDefault();
        
        if (this.validateForm(form)) {
            // Form is valid, proceed with submission
            this.submitForm(form);
        }
    }

    validateForm(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        const rules = field.dataset;
        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (rules.required !== undefined && !value) {
            errorMessage = 'This field is required.';
            isValid = false;
        }

        // Email validation
        if (field.type === 'email' && value && !this.isValidEmail(value)) {
            errorMessage = 'Please enter a valid email address.';
            isValid = false;
        }

        // Phone validation
        if (field.type === 'tel' && value && !this.isValidPhone(value)) {
            errorMessage = 'Please enter a valid phone number.';
            isValid = false;
        }

        // Min length validation
        if (rules.minlength && value.length < parseInt(rules.minlength)) {
            errorMessage = `Must be at least ${rules.minlength} characters long.`;
            isValid = false;
        }

        // Password confirmation
        if (field.name === 'password_confirmation') {
            const passwordField = field.form.querySelector('input[name="password"]');
            if (passwordField && value !== passwordField.value) {
                errorMessage = 'Passwords do not match.';
                isValid = false;
            }
        }

        this.setFieldValidation(field, isValid, errorMessage);
        return isValid;
    }

    setFieldValidation(field, isValid, errorMessage) {
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            if (feedback) feedback.textContent = '';
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            if (feedback) feedback.textContent = errorMessage;
        }
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) feedback.textContent = '';
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isValidPhone(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
    }

    async submitForm(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        try {
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                window.notifications.show(result.message || 'Success!', 'success');
                if (result.redirect) {
                    setTimeout(() => window.location.href = result.redirect, 1000);
                }
            } else {
                window.notifications.show(result.message || 'An error occurred', 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            window.notifications.show('An unexpected error occurred', 'error');
        } finally {
            // Restore button state
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    window.layoutManager = new LayoutManager();
    window.themeManager = new ThemeManager();
    window.notifications = new NotificationManager();
    window.formValidator = new FormValidator();
    
    // Make BASE_URL available globally
    window.BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '';
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LayoutManager,
        ThemeManager,
        NotificationManager,
        FormValidator
    };
}
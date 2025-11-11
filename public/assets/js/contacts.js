// Contacts Page JavaScript

class ContactsManager {
    constructor() {
        this.selectedContacts = new Set();
        this.currentView = 'table';
        this.init();
    }

    init() {
        this.bindEvents();
        this.initFilters();
        this.initSearch();
        this.initBulkActions();
    }

    bindEvents() {
        // Select all checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleSelectAll(e.target.checked);
            });
        }

        // Individual contact checkboxes
        document.querySelectorAll('.contact-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.toggleContactSelection(e.target.value, e.target.checked);
            });
        });

        // View toggle buttons
        document.querySelectorAll('[data-view]').forEach(button => {
            button.addEventListener('click', (e) => {
                this.switchView(e.currentTarget.dataset.view);
            });
        });

        // Filter toggle
        window.showFilters = () => this.toggleFilters();
        window.clearFilters = () => this.clearFilters();
        
        // Delete contact function
        window.deleteContact = (id) => this.deleteContact(id);
        window.exportContacts = () => this.exportContacts();
    }

    initFilters() {
        const filterForm = document.querySelector('.filter-form');
        if (!filterForm) return;

        // Auto-submit form when filters change
        filterForm.querySelectorAll('select, input[type="text"]').forEach(element => {
            if (element.type === 'text') {
                // Debounce text inputs
                let timeout;
                element.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        filterForm.submit();
                    }, 500);
                });
            } else {
                element.addEventListener('change', () => {
                    filterForm.submit();
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
                this.performGlobalSearch(e.target.value);
            }, 300);
        });
    }

    initBulkActions() {
        // This would be expanded to include bulk operations
        this.updateBulkActionsVisibility();
    }

    toggleSelectAll(checked) {
        document.querySelectorAll('.contact-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
            this.toggleContactSelection(checkbox.value, checked);
        });
        this.updateBulkActionsVisibility();
    }

    toggleContactSelection(contactId, selected) {
        if (selected) {
            this.selectedContacts.add(contactId);
        } else {
            this.selectedContacts.delete(contactId);
        }
        
        // Update select all checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        const totalCheckboxes = document.querySelectorAll('.contact-checkbox').length;
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = this.selectedContacts.size === totalCheckboxes;
            selectAllCheckbox.indeterminate = this.selectedContacts.size > 0 && this.selectedContacts.size < totalCheckboxes;
        }
        
        this.updateBulkActionsVisibility();
    }

    updateBulkActionsVisibility() {
        // Show/hide bulk action buttons based on selection
        const bulkActions = document.querySelector('.bulk-actions');
        if (bulkActions) {
            bulkActions.style.display = this.selectedContacts.size > 0 ? 'flex' : 'none';
        }
    }

    switchView(view) {
        this.currentView = view;
        
        // Update active button
        document.querySelectorAll('[data-view]').forEach(button => {
            button.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`).classList.add('active');
        
        // Switch between table and grid view
        const tableView = document.querySelector('.table-responsive');
        const gridView = document.querySelector('.contacts-grid');
        
        if (view === 'table') {
            if (tableView) tableView.style.display = 'block';
            if (gridView) gridView.style.display = 'none';
        } else {
            if (tableView) tableView.style.display = 'none';
            if (gridView) gridView.style.display = 'grid';
            this.renderGridView();
        }
        
        // Save preference
        localStorage.setItem('contacts-view', view);
    }

    renderGridView() {
        const gridContainer = document.querySelector('.contacts-grid');
        if (!gridContainer) return;

        // This would render contacts in a grid layout
        // For now, we'll just show a message
        gridContainer.innerHTML = `
            <div class="col-12 text-center py-5">
                <p class="text-muted">Grid view coming soon...</p>
                <button class="btn btn-primary" onclick="contactsManager.switchView('table')">
                    Switch to Table View
                </button>
            </div>
        `;
    }

    toggleFilters() {
        const filtersPanel = document.getElementById('filtersPanel');
        if (filtersPanel) {
            const isVisible = filtersPanel.style.display !== 'none';
            filtersPanel.style.display = isVisible ? 'none' : 'block';
            
            // Update button text
            const button = document.querySelector('[onclick="showFilters()"]');
            if (button) {
                const icon = button.querySelector('i');
                const text = button.querySelector('span') || button;
                if (isVisible) {
                    icon.className = 'fas fa-filter';
                    if (text !== button) text.textContent = 'Filters';
                } else {
                    icon.className = 'fas fa-times';
                    if (text !== button) text.textContent = 'Hide Filters';
                }
            }
        }
    }

    clearFilters() {
        // Redirect to contacts page without any query parameters
        window.location.href = window.BASE_URL + '/contacts';
    }

    async deleteContact(id) {
        if (!confirm('Are you sure you want to delete this contact? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`${window.BASE_URL}/contacts/${id}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                // Remove the row from the table
                const row = document.querySelector(`[data-contact-id="${id}"]`);
                if (row) {
                    row.remove();
                }
                
                // Show success message
                window.notifications.show(result.message, 'success');
                
                // Update counts
                this.updateContactCounts(-1);
            } else {
                window.notifications.show(result.message || 'Failed to delete contact', 'error');
            }
        } catch (error) {
            console.error('Delete contact error:', error);
            window.notifications.show('An error occurred while deleting the contact', 'error');
        }
    }

    async exportContacts() {
        try {
            // Get current filters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('export', 'csv');
            
            // Create and trigger download
            const exportUrl = `${window.BASE_URL}/contacts/export?${urlParams.toString()}`;
            window.open(exportUrl, '_blank');
            
            window.notifications.show('Export started. Download will begin shortly.', 'success');
        } catch (error) {
            console.error('Export error:', error);
            window.notifications.show('Failed to export contacts', 'error');
        }
    }

    async performGlobalSearch(query) {
        if (query.length < 2) {
            return;
        }

        try {
            const response = await fetch(`${window.BASE_URL}/api/search?q=${encodeURIComponent(query)}&type=contacts`);
            const results = await response.json();
            
            // Update search dropdown with results
            this.displaySearchResults(results.contacts || []);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(contacts) {
        // This would update the search dropdown
        // Implementation depends on the search UI design
        console.log('Search results:', contacts);
    }

    updateContactCounts(change) {
        // Update the statistics row
        const totalElement = document.querySelector('.stats-row .stat-value');
        if (totalElement) {
            const currentCount = parseInt(totalElement.textContent.replace(/,/g, ''));
            const newCount = Math.max(0, currentCount + change);
            totalElement.textContent = newCount.toLocaleString();
        }
    }

    // Bulk actions
    async bulkDelete() {
        if (this.selectedContacts.size === 0) return;
        
        const count = this.selectedContacts.size;
        if (!confirm(`Are you sure you want to delete ${count} contact(s)? This action cannot be undone.`)) {
            return;
        }

        try {
            const response = await fetch(`${window.BASE_URL}/contacts/bulk-delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    contact_ids: Array.from(this.selectedContacts)
                })
            });

            const result = await response.json();

            if (result.success) {
                // Remove rows from table
                this.selectedContacts.forEach(id => {
                    const row = document.querySelector(`[data-contact-id="${id}"]`);
                    if (row) row.remove();
                });
                
                // Clear selection
                this.selectedContacts.clear();
                this.updateBulkActionsVisibility();
                
                window.notifications.show(`${count} contact(s) deleted successfully`, 'success');
                this.updateContactCounts(-count);
            } else {
                window.notifications.show(result.message || 'Failed to delete contacts', 'error');
            }
        } catch (error) {
            console.error('Bulk delete error:', error);
            window.notifications.show('An error occurred while deleting contacts', 'error');
        }
    }

    async bulkExport() {
        if (this.selectedContacts.size === 0) return;

        try {
            const response = await fetch(`${window.BASE_URL}/contacts/bulk-export`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    contact_ids: Array.from(this.selectedContacts)
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `contacts_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                window.notifications.show('Contacts exported successfully', 'success');
            } else {
                window.notifications.show('Failed to export contacts', 'error');
            }
        } catch (error) {
            console.error('Bulk export error:', error);
            window.notifications.show('An error occurred while exporting contacts', 'error');
        }
    }
}

// Contact form validation
class ContactFormValidator {
    constructor(formElement) {
        this.form = formElement;
        this.init();
    }

    init() {
        if (!this.form) return;

        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Real-time validation
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
            field.addEventListener('input', () => this.clearFieldError(field));
        });
    }

    handleSubmit(e) {
        e.preventDefault();
        
        if (this.validateForm()) {
            this.submitForm();
        }
    }

    validateForm() {
        let isValid = true;
        
        // Validate required fields
        const requiredFields = this.form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        // Validate email if provided
        const emailField = this.form.querySelector('input[type="email"]');
        if (emailField && emailField.value && !this.validateEmail(emailField.value)) {
            this.setFieldError(emailField, 'Please enter a valid email address');
            isValid = false;
        }
        
        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        
        if (field.hasAttribute('required') && !value) {
            this.setFieldError(field, 'This field is required');
            return false;
        }
        
        if (field.type === 'email' && value && !this.validateEmail(value)) {
            this.setFieldError(field, 'Please enter a valid email address');
            return false;
        }
        
        this.clearFieldError(field);
        return true;
    }

    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    setFieldError(field, message) {
        field.classList.add('is-invalid');
        
        let feedback = field.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentElement.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = '';
        }
    }

    async submitForm() {
        const submitButton = this.form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        try {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const formData = new FormData(this.form);
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                window.notifications.show(result.message || 'Contact saved successfully', 'success');
                if (result.redirect) {
                    setTimeout(() => window.location.href = result.redirect, 1000);
                }
            } else {
                window.notifications.show(result.message || 'Failed to save contact', 'error');
                if (result.errors) {
                    Object.keys(result.errors).forEach(field => {
                        const fieldElement = this.form.querySelector(`[name="${field}"]`);
                        if (fieldElement) {
                            this.setFieldError(fieldElement, result.errors[field]);
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Form submission error:', error);
            window.notifications.show('An error occurred while saving the contact', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.contactsManager = new ContactsManager();
    
    // Initialize contact forms if present
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        new ContactFormValidator(contactForm);
    }
    
    // Load saved view preference
    const savedView = localStorage.getItem('contacts-view');
    if (savedView && savedView !== 'table') {
        window.contactsManager.switchView(savedView);
    }
});
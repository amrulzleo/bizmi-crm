/**
 * Sales Pipeline Kanban Board JavaScript
 * Handles drag-and-drop functionality, deal movement, and real-time updates
 */

class SalesPipeline {
    constructor() {
        this.isDragging = false;
        this.draggedElement = null;
        this.originalParent = null;
        this.placeholder = null;
        this.touchStartX = 0;
        this.touchStartY = 0;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeDragAndDrop();
        this.loadPipelineStats();
        this.setupAutoRefresh();
    }
    
    /**
     * Bind all event listeners
     */
    bindEvents() {
        // Quick add deal modal
        const quickAddBtns = document.querySelectorAll('.btn-add-deal');
        quickAddBtns.forEach(btn => {
            btn.addEventListener('click', (e) => this.showQuickAddModal(e));
        });
        
        // Deal card actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-edit-deal')) {
                this.editDeal(e.target.closest('.deal-card').dataset.dealId);
            }
            
            if (e.target.closest('.btn-delete-deal')) {
                this.deleteDeal(e.target.closest('.deal-card').dataset.dealId);
            }
            
            if (e.target.closest('.btn-close-modal')) {
                this.closeModal();
            }
        });
        
        // Quick add form submission
        const quickAddForm = document.getElementById('quick-add-form');
        if (quickAddForm) {
            quickAddForm.addEventListener('submit', (e) => this.handleQuickAdd(e));
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                this.cancelDrag();
            }
        });
        
        // Search and filter
        const searchInput = document.getElementById('pipeline-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterDeals(e.target.value));
        }
        
        // Stage filter dropdown
        const stageFilter = document.getElementById('stage-filter');
        if (stageFilter) {
            stageFilter.addEventListener('change', (e) => this.filterByStage(e.target.value));
        }
    }
    
    /**
     * Initialize drag and drop functionality
     */
    initializeDragAndDrop() {
        const dealCards = document.querySelectorAll('.deal-card');
        const pipelineStages = document.querySelectorAll('.pipeline-stage');
        
        // Setup deal cards
        dealCards.forEach(card => {
            // Mouse events
            card.addEventListener('mousedown', (e) => this.handleMouseDown(e));
            card.addEventListener('dragstart', (e) => e.preventDefault()); // Prevent default drag
            
            // Touch events for mobile
            card.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: false });
            card.addEventListener('touchmove', (e) => this.handleTouchMove(e), { passive: false });
            card.addEventListener('touchend', (e) => this.handleTouchEnd(e), { passive: false });
        });
        
        // Setup drop zones
        pipelineStages.forEach(stage => {
            stage.addEventListener('dragover', (e) => this.handleDragOver(e));
            stage.addEventListener('drop', (e) => this.handleDrop(e));
            stage.addEventListener('dragenter', (e) => this.handleDragEnter(e));
            stage.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        });
        
        // Global mouse events
        document.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        document.addEventListener('mouseup', (e) => this.handleMouseUp(e));
    }
    
    /**
     * Handle mouse down on deal card
     */
    handleMouseDown(e) {
        if (e.button !== 0 || e.target.closest('.deal-actions')) return; // Only left click, ignore action buttons
        
        this.startDrag(e.currentTarget, e.clientX, e.clientY);
        e.preventDefault();
    }
    
    /**
     * Handle touch start for mobile devices
     */
    handleTouchStart(e) {
        const touch = e.touches[0];
        this.touchStartX = touch.clientX;
        this.touchStartY = touch.clientY;
        
        setTimeout(() => {
            if (Math.abs(touch.clientX - this.touchStartX) < 5 && 
                Math.abs(touch.clientY - this.touchStartY) < 5) {
                this.startDrag(e.currentTarget, touch.clientX, touch.clientY);
            }
        }, 200); // Long press detection
    }
    
    /**
     * Start drag operation
     */
    startDrag(element, x, y) {
        this.isDragging = true;
        this.draggedElement = element;
        this.originalParent = element.parentNode;
        
        // Create placeholder
        this.placeholder = element.cloneNode(true);
        this.placeholder.classList.add('drag-placeholder');
        this.placeholder.style.height = element.offsetHeight + 'px';
        
        // Style dragged element
        element.classList.add('dragging');
        element.style.position = 'fixed';
        element.style.zIndex = '1000';
        element.style.width = element.offsetWidth + 'px';
        element.style.pointerEvents = 'none';
        
        // Insert placeholder
        element.parentNode.insertBefore(this.placeholder, element.nextSibling);
        
        // Move element to cursor position
        this.updateDragPosition(x, y);
        
        // Provide haptic feedback on mobile
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
        
        this.showDragHints();
    }
    
    /**
     * Handle mouse/touch move during drag
     */
    handleMouseMove(e) {
        if (!this.isDragging) return;
        this.updateDragPosition(e.clientX, e.clientY);
        this.handleDragHover(e.clientX, e.clientY);
    }
    
    handleTouchMove(e) {
        if (!this.isDragging) return;
        e.preventDefault();
        const touch = e.touches[0];
        this.updateDragPosition(touch.clientX, touch.clientY);
        this.handleDragHover(touch.clientX, touch.clientY);
    }
    
    /**
     * Update position of dragged element
     */
    updateDragPosition(x, y) {
        if (!this.draggedElement) return;
        
        const rect = this.draggedElement.getBoundingClientRect();
        this.draggedElement.style.left = (x - rect.width / 2) + 'px';
        this.draggedElement.style.top = (y - 20) + 'px';
    }
    
    /**
     * Handle hover over drop zones during drag
     */
    handleDragHover(x, y) {
        const elementBelow = document.elementFromPoint(x, y);
        const dropZone = elementBelow?.closest('.pipeline-stage');
        const dealsList = elementBelow?.closest('.deals-list');
        
        // Remove previous hover states
        document.querySelectorAll('.pipeline-stage.drag-over').forEach(stage => {
            stage.classList.remove('drag-over');
        });
        
        if (dropZone && dealsList) {
            dropZone.classList.add('drag-over');
            
            // Find position to insert placeholder
            const cards = Array.from(dealsList.children).filter(child => 
                !child.classList.contains('drag-placeholder') && 
                !child.classList.contains('dragging')
            );
            
            let insertAfter = null;
            for (const card of cards) {
                const rect = card.getBoundingClientRect();
                if (y < rect.top + rect.height / 2) {
                    break;
                }
                insertAfter = card;
            }
            
            // Move placeholder
            if (this.placeholder.parentNode !== dealsList) {
                if (insertAfter) {
                    dealsList.insertBefore(this.placeholder, insertAfter.nextSibling);
                } else {
                    dealsList.insertBefore(this.placeholder, dealsList.firstChild);
                }
            }
        }
    }
    
    /**
     * Handle mouse/touch up to end drag
     */
    handleMouseUp(e) {
        if (!this.isDragging) return;
        this.endDrag(e.clientX, e.clientY);
    }
    
    handleTouchEnd(e) {
        if (!this.isDragging) return;
        const touch = e.changedTouches[0];
        this.endDrag(touch.clientX, touch.clientY);
    }
    
    /**
     * End drag operation
     */
    endDrag(x, y) {
        if (!this.isDragging || !this.draggedElement) return;
        
        const elementBelow = document.elementFromPoint(x, y);
        const dropZone = elementBelow?.closest('.pipeline-stage');
        const newStage = dropZone?.dataset.stageId;
        const oldStage = this.originalParent?.closest('.pipeline-stage')?.dataset.stageId;
        
        // Reset element styles
        this.draggedElement.classList.remove('dragging');
        this.draggedElement.style.position = '';
        this.draggedElement.style.zIndex = '';
        this.draggedElement.style.width = '';
        this.draggedElement.style.left = '';
        this.draggedElement.style.top = '';
        this.draggedElement.style.pointerEvents = '';
        
        // Clean up
        this.hideDragHints();
        document.querySelectorAll('.pipeline-stage.drag-over').forEach(stage => {
            stage.classList.remove('drag-over');
        });
        
        if (newStage && newStage !== oldStage) {
            // Move to new stage
            this.placeholder.parentNode.replaceChild(this.draggedElement, this.placeholder);
            this.updateDealStage(this.draggedElement.dataset.dealId, newStage, oldStage);
        } else {
            // Return to original position
            this.originalParent.replaceChild(this.draggedElement, this.placeholder);
        }
        
        // Reset state
        this.isDragging = false;
        this.draggedElement = null;
        this.originalParent = null;
        this.placeholder = null;
    }
    
    /**
     * Cancel drag operation
     */
    cancelDrag() {
        if (!this.isDragging) return;
        
        if (this.draggedElement && this.originalParent && this.placeholder) {
            // Reset styles
            this.draggedElement.classList.remove('dragging');
            this.draggedElement.style.position = '';
            this.draggedElement.style.zIndex = '';
            this.draggedElement.style.width = '';
            this.draggedElement.style.left = '';
            this.draggedElement.style.top = '';
            this.draggedElement.style.pointerEvents = '';
            
            // Return to original position
            this.originalParent.replaceChild(this.draggedElement, this.placeholder);
        }
        
        this.hideDragHints();
        this.isDragging = false;
        this.draggedElement = null;
        this.originalParent = null;
        this.placeholder = null;
    }
    
    /**
     * Show visual hints during drag
     */
    showDragHints() {
        document.querySelectorAll('.pipeline-stage').forEach(stage => {
            stage.style.outline = '2px dashed transparent';
            stage.style.transition = 'outline-color 0.3s ease';
        });
    }
    
    /**
     * Hide drag hints
     */
    hideDragHints() {
        document.querySelectorAll('.pipeline-stage').forEach(stage => {
            stage.style.outline = '';
            stage.style.transition = '';
        });
    }
    
    /**
     * Update deal stage via AJAX
     */
    async updateDealStage(dealId, newStageId, oldStageId) {
        try {
            this.showLoadingState(dealId);
            
            const response = await fetch('/deals/update-stage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    deal_id: dealId,
                    stage_id: newStageId,
                    old_stage_id: oldStageId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage(`Deal moved to ${result.stage_name}`);
                this.updateStageStats();
                this.logActivity(dealId, 'stage_changed', {
                    old_stage: oldStageId,
                    new_stage: newStageId
                });
            } else {
                throw new Error(result.message || 'Failed to update deal stage');
            }
            
        } catch (error) {
            console.error('Error updating deal stage:', error);
            this.showErrorMessage('Failed to update deal stage');
            
            // Revert the move
            const dealCard = document.querySelector(`[data-deal-id="${dealId}"]`);
            const originalStage = document.querySelector(`[data-stage-id="${oldStageId}"] .deals-list`);
            if (dealCard && originalStage) {
                originalStage.appendChild(dealCard);
            }
        } finally {
            this.hideLoadingState(dealId);
        }
    }
    
    /**
     * Show quick add deal modal
     */
    showQuickAddModal(e) {
        const stageId = e.target.closest('.pipeline-stage').dataset.stageId;
        const modal = document.getElementById('quick-add-modal');
        
        if (modal) {
            // Set the stage for the new deal
            const stageField = modal.querySelector('input[name="stage_id"]');
            if (stageField) {
                stageField.value = stageId;
            }
            
            // Reset form
            const form = modal.querySelector('#quick-add-form');
            if (form) {
                form.reset();
            }
            
            // Focus on deal name field
            const nameField = modal.querySelector('input[name="name"]');
            if (nameField) {
                setTimeout(() => nameField.focus(), 100);
            }
            
            modal.style.display = 'flex';
            document.body.classList.add('modal-open');
        }
    }
    
    /**
     * Handle quick add form submission
     */
    async handleQuickAdd(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('/deals/quick-add', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage('Deal created successfully');
                this.addDealToStage(result.deal, formData.get('stage_id'));
                this.closeModal();
                this.updateStageStats();
            } else {
                this.showFormErrors(form, result.errors || {});
            }
            
        } catch (error) {
            console.error('Error creating deal:', error);
            this.showErrorMessage('Failed to create deal');
        }
    }
    
    /**
     * Add new deal card to stage
     */
    addDealToStage(deal, stageId) {
        const stage = document.querySelector(`[data-stage-id="${stageId}"] .deals-list`);
        if (!stage) return;
        
        const dealCard = this.createDealCard(deal);
        stage.insertBefore(dealCard, stage.firstChild);
        
        // Add entrance animation
        dealCard.style.opacity = '0';
        dealCard.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            dealCard.style.transition = 'all 0.3s ease';
            dealCard.style.opacity = '1';
            dealCard.style.transform = 'translateY(0)';
        }, 10);
    }
    
    /**
     * Create deal card HTML
     */
    createDealCard(deal) {
        const card = document.createElement('div');
        card.className = 'deal-card';
        card.dataset.dealId = deal.id;
        
        card.innerHTML = `
            <div class="deal-header">
                <h4 class="deal-name">
                    <a href="/deals/show/${deal.id}">${this.escapeHtml(deal.name)}</a>
                </h4>
                <div class="deal-amount">$${this.formatCurrency(deal.amount || 0)}</div>
            </div>
            
            <div class="deal-details">
                ${deal.organization ? `
                    <div>
                        <i class="fas fa-building"></i>
                        <span>${this.escapeHtml(deal.organization.name)}</span>
                    </div>
                ` : ''}
                
                ${deal.contact ? `
                    <div>
                        <i class="fas fa-user"></i>
                        <span>${this.escapeHtml(deal.contact.first_name + ' ' + deal.contact.last_name)}</span>
                    </div>
                ` : ''}
                
                ${deal.close_date ? `
                    <div class="deal-close-date ${this.isOverdue(deal.close_date) ? 'overdue' : ''}">
                        <i class="fas fa-calendar"></i>
                        <span>${this.formatDate(deal.close_date)}</span>
                    </div>
                ` : ''}
            </div>
            
            <div class="deal-footer">
                <div class="owner-avatar" title="${this.escapeHtml(deal.owner?.first_name || '')} ${this.escapeHtml(deal.owner?.last_name || '')}">
                    ${(deal.owner?.first_name?.charAt(0) || 'U')?.toUpperCase()}
                </div>
                
                <div class="deal-actions">
                    <button class="btn-icon btn-edit-deal" title="Edit Deal">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete-deal" title="Delete Deal">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="deal-probability-bar">
                <div class="probability-fill" style="width: ${deal.probability || 0}%"></div>
            </div>
        `;
        
        // Add drag functionality
        card.addEventListener('mousedown', (e) => this.handleMouseDown(e));
        card.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: false });
        
        return card;
    }
    
    /**
     * Filter deals by search term
     */
    filterDeals(searchTerm) {
        const dealCards = document.querySelectorAll('.deal-card');
        const term = searchTerm.toLowerCase().trim();
        
        dealCards.forEach(card => {
            const dealName = card.querySelector('.deal-name a')?.textContent.toLowerCase() || '';
            const organization = card.querySelector('.deal-details span')?.textContent.toLowerCase() || '';
            const contact = card.querySelectorAll('.deal-details span')[1]?.textContent.toLowerCase() || '';
            
            const matches = !term || 
                           dealName.includes(term) || 
                           organization.includes(term) || 
                           contact.includes(term);
            
            card.style.display = matches ? 'block' : 'none';
        });
    }
    
    /**
     * Update stage statistics
     */
    async updateStageStats() {
        try {
            const response = await fetch('/deals/pipeline-stats');
            const stats = await response.json();
            
            if (stats.success) {
                this.updateStatsDisplay(stats.data);
            }
        } catch (error) {
            console.error('Error updating stats:', error);
        }
    }
    
    /**
     * Update statistics display
     */
    updateStatsDisplay(stats) {
        // Update pipeline stage stats
        Object.entries(stats.stages || {}).forEach(([stageId, data]) => {
            const stage = document.querySelector(`[data-stage-id="${stageId}"]`);
            if (stage) {
                const countEl = stage.querySelector('.deal-count');
                const valueEl = stage.querySelector('.stage-value');
                
                if (countEl) countEl.textContent = `${data.count} deals`;
                if (valueEl) valueEl.textContent = this.formatCurrency(data.value);
            }
        });
        
        // Update summary stats
        if (stats.summary) {
            this.updateSummaryStats(stats.summary);
        }
    }
    
    /**
     * Close modal
     */
    closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.classList.remove('modal-open');
    }
    
    /**
     * Utility functions
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    
    isOverdue(dateString) {
        const date = new Date(dateString);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return date < today;
    }
    
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
    
    showSuccessMessage(message) {
        this.showNotification(message, 'success');
    }
    
    showErrorMessage(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            <span>${message}</span>
            <button class="btn-close-notification">&times;</button>
        `;
        
        // Add to page
        const container = document.getElementById('notifications') || document.body;
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
        
        // Manual close
        notification.querySelector('.btn-close-notification').addEventListener('click', () => {
            notification.remove();
        });
    }
    
    showLoadingState(dealId) {
        const card = document.querySelector(`[data-deal-id="${dealId}"]`);
        if (card) {
            card.classList.add('loading');
            card.style.opacity = '0.7';
        }
    }
    
    hideLoadingState(dealId) {
        const card = document.querySelector(`[data-deal-id="${dealId}"]`);
        if (card) {
            card.classList.remove('loading');
            card.style.opacity = '1';
        }
    }
    
    showFormErrors(form, errors) {
        // Clear previous errors
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        
        // Show new errors
        Object.entries(errors).forEach(([field, message]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                
                const errorEl = document.createElement('div');
                errorEl.className = 'error-message';
                errorEl.textContent = message;
                input.parentNode.appendChild(errorEl);
            }
        });
    }
    
    loadPipelineStats() {
        this.updateStageStats();
    }
    
    setupAutoRefresh() {
        // Refresh stats every 5 minutes
        setInterval(() => {
            this.updateStageStats();
        }, 300000);
    }
    
    logActivity(dealId, action, data = {}) {
        fetch('/activities/log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.getCSRFToken()
            },
            body: JSON.stringify({
                entity_type: 'deal',
                entity_id: dealId,
                action: action,
                data: data
            })
        }).catch(error => {
            console.error('Error logging activity:', error);
        });
    }
    
    editDeal(dealId) {
        window.location.href = `/deals/edit/${dealId}`;
    }
    
    async deleteDeal(dealId) {
        if (!confirm('Are you sure you want to delete this deal?')) {
            return;
        }
        
        try {
            const response = await fetch(`/deals/delete/${dealId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                const card = document.querySelector(`[data-deal-id="${dealId}"]`);
                if (card) {
                    card.remove();
                }
                this.showSuccessMessage('Deal deleted successfully');
                this.updateStageStats();
            } else {
                this.showErrorMessage(result.message || 'Failed to delete deal');
            }
        } catch (error) {
            console.error('Error deleting deal:', error);
            this.showErrorMessage('Failed to delete deal');
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.salesPipeline = new SalesPipeline();
});

// Prevent default drag behavior on images and other elements
document.addEventListener('dragstart', (e) => {
    if (!e.target.classList.contains('deal-card')) {
        e.preventDefault();
    }
});

// Add CSS for notifications if not already present
if (!document.getElementById('pipeline-notifications-css')) {
    const style = document.createElement('style');
    style.id = 'pipeline-notifications-css';
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        }
        
        .notification-success {
            border-left: 4px solid #9CAF88;
            color: #2d3e2d;
        }
        
        .notification-error {
            border-left: 4px solid #C17D6F;
            color: #3e2d2d;
        }
        
        .btn-close-notification {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.5;
            margin-left: auto;
        }
        
        .btn-close-notification:hover {
            opacity: 1;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .error-message {
            color: #C17D6F;
            font-size: 12px;
            margin-top: 4px;
        }
        
        .error {
            border-color: #C17D6F !important;
        }
    `;
    document.head.appendChild(style);
}
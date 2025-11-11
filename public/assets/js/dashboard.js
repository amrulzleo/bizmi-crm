/**
 * BizMi CRM Dashboard JavaScript
 * Handles dashboard functionality, charts, and real-time updates
 */

class DashboardManager {
    constructor() {
        this.charts = {};
        this.refreshInterval = null;
        this.searchTimeout = null;
        
        this.init();
    }

    init() {
        this.initializeCharts();
        this.setupEventListeners();
        this.setupAutoRefresh();
        this.initializeGlobalSearch();
        this.setupKPIUpdates();
    }

    /**
     * Initialize all dashboard charts
     */
    initializeCharts() {
        this.initializeRevenueChart();
        this.initializePipelineChart();
        this.initializeActivityChart();
        this.initializeConversionChart();
    }

    /**
     * Initialize revenue trend chart
     */
    initializeRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        // Earth-tone color palette
        const earthColors = {
            primary: '#9CAF88',
            secondary: '#C8A882',
            tertiary: '#8B7B6B',
            background: 'rgba(156, 175, 136, 0.1)',
            border: '#8B9D7B'
        };

        this.charts.revenue = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    data: [],
                    backgroundColor: earthColors.background,
                    borderColor: earthColors.primary,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: earthColors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#2C2C2C',
                        bodyColor: '#2C2C2C',
                        borderColor: earthColors.primary,
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#8B8B8B'
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#8B8B8B',
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Load initial data
        this.loadRevenueData();
    }

    /**
     * Initialize pipeline distribution chart
     */
    initializePipelineChart() {
        const ctx = document.getElementById('pipelineChart');
        if (!ctx) return;

        const earthColors = [
            '#9CAF88', '#C8A882', '#8B7B6B', '#F5F2E8',
            '#A8B99C', '#D4B894', '#978D7E', '#F0EDE5'
        ];

        this.charts.pipeline = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: earthColors,
                    borderWidth: 0,
                    hoverBorderWidth: 2,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#2C2C2C',
                        bodyColor: '#2C2C2C',
                        borderColor: '#9CAF88',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} deals (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Load initial data
        this.loadPipelineData();
    }

    /**
     * Initialize activity chart (if exists)
     */
    initializeActivityChart() {
        const ctx = document.getElementById('activityChart');
        if (!ctx) return;

        this.charts.activity = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Daily Activities',
                    data: [],
                    backgroundColor: '#C8A882',
                    borderColor: '#B8977A',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        this.loadActivityData();
    }

    /**
     * Initialize conversion funnel chart (if exists)
     */
    initializeConversionChart() {
        const ctx = document.getElementById('conversionChart');
        if (!ctx) return;

        this.charts.conversion = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Leads', 'Contacts', 'Opportunities', 'Customers'],
                datasets: [{
                    label: 'Conversion Funnel',
                    data: [],
                    backgroundColor: [
                        '#9CAF88',
                        '#C8A882', 
                        '#8B7B6B',
                        '#F5F2E8'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        this.loadConversionData();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Period toggle for charts
        document.querySelectorAll('input[name="chartPeriod"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.updateChartPeriod(e.target.value);
            });
        });

        // Refresh button
        const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
        if (refreshBtn) {
            refreshBtn.onclick = (e) => {
                e.preventDefault();
                this.refreshDashboard();
            };
        }

        // Export buttons
        document.querySelectorAll('[data-export]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleExport(btn.dataset.export);
            });
        });

        // Filter changes
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', () => {
                this.applyFilters();
            });
        });

        // Date range changes
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.addEventListener('change', () => {
                this.applyDateFilter();
            });
        });
    }

    /**
     * Setup auto-refresh functionality
     */
    setupAutoRefresh() {
        // Refresh KPIs every 5 minutes
        this.refreshInterval = setInterval(() => {
            this.updateKPIs();
        }, 5 * 60 * 1000);

        // Refresh charts every 10 minutes
        setInterval(() => {
            this.refreshCharts();
        }, 10 * 60 * 1000);
    }

    /**
     * Initialize global search functionality
     */
    initializeGlobalSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        
        if (!searchInput || !searchResults) return;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(this.searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.add('d-none');
                return;
            }

            this.searchTimeout = setTimeout(() => {
                this.performGlobalSearch(query);
            }, 300);
        });

        // Hide results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('d-none');
            }
        });
    }

    /**
     * Setup KPI updates
     */
    setupKPIUpdates() {
        // Update KPIs with loading states
        this.addLoadingStates();
        this.updateKPIs();
    }

    /**
     * Load revenue chart data
     */
    async loadRevenueData(period = 'monthly') {
        try {
            const response = await fetch(`/dashboard/api/chart-data?chart=revenue_trend&period=${period}`);
            const data = await response.json();
            
            if (data.labels && data.datasets) {
                this.charts.revenue.data.labels = data.labels;
                this.charts.revenue.data.datasets[0].data = data.datasets[0].data;
                this.charts.revenue.update('active');
            }
        } catch (error) {
            console.error('Error loading revenue data:', error);
            this.showChartError('revenueChart');
        }
    }

    /**
     * Load pipeline chart data
     */
    async loadPipelineData() {
        try {
            const response = await fetch('/dashboard/api/chart-data?chart=pipeline_stages');
            const data = await response.json();
            
            if (data.labels && data.datasets) {
                this.charts.pipeline.data.labels = data.labels;
                this.charts.pipeline.data.datasets[0].data = data.datasets[0].data;
                this.charts.pipeline.update('active');
            }
        } catch (error) {
            console.error('Error loading pipeline data:', error);
            this.showChartError('pipelineChart');
        }
    }

    /**
     * Load activity chart data
     */
    async loadActivityData() {
        try {
            const response = await fetch('/dashboard/api/chart-data?chart=activity_trend');
            const data = await response.json();
            
            if (this.charts.activity && data.labels && data.datasets) {
                this.charts.activity.data.labels = data.labels;
                this.charts.activity.data.datasets[0].data = data.datasets[0].data;
                this.charts.activity.update('active');
            }
        } catch (error) {
            console.error('Error loading activity data:', error);
        }
    }

    /**
     * Load conversion funnel data
     */
    async loadConversionData() {
        try {
            const response = await fetch('/dashboard/api/chart-data?chart=conversion_funnel');
            const data = await response.json();
            
            if (this.charts.conversion && data.labels && data.datasets) {
                this.charts.conversion.data.datasets[0].data = data.datasets[0].data;
                this.charts.conversion.update('active');
            }
        } catch (error) {
            console.error('Error loading conversion data:', error);
        }
    }

    /**
     * Update KPIs with fresh data
     */
    async updateKPIs() {
        try {
            const response = await fetch('/dashboard/api/kpi-data?kpi=sales_summary');
            const data = await response.json();
            
            if (data) {
                this.updateKPIElement('totalRevenue', data.total_revenue);
                this.updateKPIElement('pipelineValue', data.pipeline_value);
                this.updateKPIElement('winRate', data.win_rate);
                
                // Show update indicator
                this.showUpdateIndicator();
            }
        } catch (error) {
            console.error('Error updating KPIs:', error);
        }
    }

    /**
     * Update individual KPI element
     */
    updateKPIElement(elementId, value) {
        const element = document.getElementById(elementId);
        if (element && value !== undefined) {
            element.textContent = value;
            
            // Add flash effect
            element.classList.add('flash-update');
            setTimeout(() => {
                element.classList.remove('flash-update');
            }, 1000);
        }
    }

    /**
     * Perform global search
     */
    async performGlobalSearch(query) {
        try {
            const response = await fetch(`/dashboard/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            this.displaySearchResults(data);
        } catch (error) {
            console.error('Search error:', error);
            this.displaySearchError();
        }
    }

    /**
     * Display search results
     */
    displaySearchResults(results) {
        const searchResults = document.getElementById('searchResults');
        let html = '';

        ['contacts', 'deals', 'organizations'].forEach(type => {
            if (results[type] && results[type].length > 0) {
                html += `<div class="p-2"><small class="text-muted text-uppercase fw-bold">${type}</small></div>`;
                results[type].forEach(item => {
                    const url = `/${type}/${item.id}`;
                    const name = item.first_name ? `${item.first_name} ${item.last_name}` : (item.title || item.name);
                    const icon = type === 'contacts' ? 'user' : type === 'deals' ? 'handshake' : 'building';
                    
                    html += `
                        <a href="${url}" class="dropdown-item py-2 d-flex align-items-center">
                            <i class="fas fa-${icon} me-2 text-muted"></i>
                            <div class="flex-grow-1">
                                <div class="fw-medium">${this.escapeHtml(name)}</div>
                                ${item.email ? `<small class="text-muted">${this.escapeHtml(item.email)}</small>` : ''}
                            </div>
                        </a>
                    `;
                });
            }
        });

        if (html) {
            searchResults.innerHTML = html;
            searchResults.classList.remove('d-none');
        } else {
            searchResults.innerHTML = '<div class="p-3 text-muted text-center">No results found</div>';
            searchResults.classList.remove('d-none');
        }
    }

    /**
     * Display search error
     */
    displaySearchError() {
        const searchResults = document.getElementById('searchResults');
        searchResults.innerHTML = '<div class="p-3 text-danger text-center">Search error. Please try again.</div>';
        searchResults.classList.remove('d-none');
    }

    /**
     * Update chart period
     */
    updateChartPeriod(period) {
        this.loadRevenueData(period);
        if (this.charts.activity) {
            this.loadActivityData(period);
        }
    }

    /**
     * Refresh entire dashboard
     */
    refreshDashboard() {
        this.showLoadingState();
        
        // Refresh all data
        Promise.all([
            this.updateKPIs(),
            this.refreshCharts(),
            this.refreshRecentData()
        ]).then(() => {
            this.hideLoadingState();
            this.showUpdateIndicator('Dashboard refreshed successfully');
        }).catch(error => {
            console.error('Dashboard refresh error:', error);
            this.hideLoadingState();
            this.showErrorMessage('Failed to refresh dashboard');
        });
    }

    /**
     * Refresh all charts
     */
    refreshCharts() {
        return Promise.all([
            this.loadRevenueData(),
            this.loadPipelineData(),
            this.loadActivityData(),
            this.loadConversionData()
        ]);
    }

    /**
     * Refresh recent data sections
     */
    async refreshRecentData() {
        // Refresh recent activities, contacts, deals, tasks
        // This would typically reload those sections
        console.log('Refreshing recent data...');
    }

    /**
     * Apply filters
     */
    applyFilters() {
        const filters = this.getActiveFilters();
        
        // Update charts with filters
        this.updateChartsWithFilters(filters);
        
        // Update KPIs with filters
        this.updateKPIsWithFilters(filters);
    }

    /**
     * Get active filters
     */
    getActiveFilters() {
        return {
            dateFrom: document.querySelector('input[name="date_from"]')?.value || '',
            dateTo: document.querySelector('input[name="date_to"]')?.value || '',
            userId: document.querySelector('select[name="user_id"]')?.value || '',
            teamId: document.querySelector('select[name="team_id"]')?.value || ''
        };
    }

    /**
     * Update charts with filters
     */
    updateChartsWithFilters(filters) {
        const queryParams = new URLSearchParams(filters).toString();
        
        // Reload charts with filters
        this.loadRevenueData('monthly', queryParams);
        this.loadPipelineData(queryParams);
    }

    /**
     * Update KPIs with filters
     */
    updateKPIsWithFilters(filters) {
        const queryParams = new URLSearchParams(filters).toString();
        
        fetch(`/dashboard/api/kpi-data?kpi=sales_summary&${queryParams}`)
            .then(response => response.json())
            .then(data => {
                this.updateKPIElement('totalRevenue', data.total_revenue);
                this.updateKPIElement('pipelineValue', data.pipeline_value);
                this.updateKPIElement('winRate', data.win_rate);
            })
            .catch(error => console.error('Filter update error:', error));
    }

    /**
     * Handle export functionality
     */
    handleExport(exportType) {
        const filters = this.getActiveFilters();
        const queryParams = new URLSearchParams(filters).toString();
        
        const url = `/dashboard/export?type=${exportType}&format=csv&${queryParams}`;
        window.open(url, '_blank');
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        document.body.classList.add('loading');
        
        // Add loading spinners to KPI cards
        document.querySelectorAll('.stats-card').forEach(card => {
            card.classList.add('loading');
        });
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        document.body.classList.remove('loading');
        
        document.querySelectorAll('.stats-card').forEach(card => {
            card.classList.remove('loading');
        });
    }

    /**
     * Add loading states to elements
     */
    addLoadingStates() {
        // Add CSS class for loading animation
        const style = document.createElement('style');
        style.textContent = `
            .flash-update {
                animation: flashUpdate 1s ease-in-out;
            }
            
            @keyframes flashUpdate {
                0%, 100% { background-color: transparent; }
                50% { background-color: rgba(156, 175, 136, 0.2); }
            }
            
            .loading {
                position: relative;
                pointer-events: none;
            }
            
            .loading::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Show update indicator
     */
    showUpdateIndicator(message = 'Updated') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--sage-green);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            transform: translateY(-100%);
            opacity: 0;
            transition: all 0.3s ease;
        `;
        toast.innerHTML = `<i class="fas fa-check me-2"></i>${message}`;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        }, 100);
        
        // Animate out and remove
        setTimeout(() => {
            toast.style.transform = 'translateY(-100%)';
            toast.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    /**
     * Show error message
     */
    showErrorMessage(message) {
        console.error(message);
        // Could implement error toast here
    }

    /**
     * Show chart error
     */
    showChartError(chartId) {
        const canvas = document.getElementById(chartId);
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#8B8B8B';
            ctx.font = '14px Inter';
            ctx.textAlign = 'center';
            ctx.fillText('Unable to load chart data', canvas.width / 2, canvas.height / 2);
        }
    }

    /**
     * Utility: Escape HTML
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Cleanup when leaving page
     */
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.destroy) {
                chart.destroy();
            }
        });
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.dashboardManager = new DashboardManager();
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (window.dashboardManager) {
            window.dashboardManager.destroy();
        }
    });
});

// Global refresh function for backward compatibility
function refreshDashboard() {
    if (window.dashboardManager) {
        window.dashboardManager.refreshDashboard();
    } else {
        location.reload();
    }
}
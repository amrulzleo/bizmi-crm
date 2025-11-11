<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'BizMi CRM Dashboard'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-sage sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/dashboard">
                <i class="fas fa-chart-line me-2"></i>BizMi CRM
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/dashboard">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard/sales">
                            <i class="fas fa-chart-bar me-1"></i>Sales Analytics
                        </a>
                    </li>
                    <?php if (in_array($user['role'], ['admin', 'manager'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard/team">
                            <i class="fas fa-users me-1"></i>Team Performance
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard/customers">
                            <i class="fas fa-building me-1"></i>Customer Analytics
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>More
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/contacts"><i class="fas fa-address-book me-2"></i>Contacts</a></li>
                            <li><a class="dropdown-item" href="/deals"><i class="fas fa-handshake me-2"></i>Deals</a></li>
                            <li><a class="dropdown-item" href="/organizations"><i class="fas fa-building me-2"></i>Organizations</a></li>
                            <li><a class="dropdown-item" href="/tasks"><i class="fas fa-tasks me-2"></i>Tasks</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        </ul>
                    </li>
                </ul>
                
                <!-- Global Search -->
                <form class="d-flex me-3" role="search" id="globalSearch">
                    <div class="position-relative">
                        <input class="form-control" type="search" placeholder="Search CRM..." id="searchInput">
                        <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-2"></i>
                        <div id="searchResults" class="position-absolute w-100 bg-white border rounded mt-1 d-none" style="z-index: 1000;"></div>
                    </div>
                </form>
                
                <!-- User Menu -->
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Dashboard Content -->
    <div class="container-fluid py-4">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1 text-dark">
                            Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!
                        </h1>
                        <p class="text-muted mb-0">
                            Here's what's happening with your business today.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-sage dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/dashboard/export?type=sales&format=csv">Sales Data (CSV)</a></li>
                                <li><a class="dropdown-item" href="/dashboard/export?type=sales&format=json">Sales Data (JSON)</a></li>
                                <?php if (in_array($user['role'], ['admin', 'manager'])): ?>
                                <li><a class="dropdown-item" href="/dashboard/export?type=team&format=csv">Team Performance (CSV)</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <button class="btn btn-sage" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Cards Row -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted mb-2">Total Revenue</h6>
                                <h3 class="mb-0 text-sage" id="totalRevenue">
                                    $<?php echo number_format($analytics['sales']['total_revenue'] ?? 0, 2); ?>
                                </h3>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up"></i> 
                                    <?php echo ($stats['revenue_growth'] ?? 0) >= 0 ? '+' : ''; ?><?php echo $stats['revenue_growth'] ?? 0; ?>%
                                </small>
                            </div>
                            <div class="icon-wrapper bg-sage-light">
                                <i class="fas fa-dollar-sign text-sage"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted mb-2">Pipeline Value</h6>
                                <h3 class="mb-0 text-terracotta" id="pipelineValue">
                                    $<?php echo number_format($analytics['sales']['pipeline_value'] ?? 0, 2); ?>
                                </h3>
                                <small class="text-muted">
                                    <?php echo $analytics['sales']['open_deals'] ?? 0; ?> open deals
                                </small>
                            </div>
                            <div class="icon-wrapper bg-terracotta-light">
                                <i class="fas fa-funnel-dollar text-terracotta"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted mb-2">Win Rate</h6>
                                <h3 class="mb-0 text-brown" id="winRate">
                                    <?php echo $analytics['sales']['win_rate'] ?? 0; ?>%
                                </h3>
                                <small class="text-muted">
                                    <?php echo $analytics['sales']['won_deals'] ?? 0; ?> deals won
                                </small>
                            </div>
                            <div class="icon-wrapper bg-brown-light">
                                <i class="fas fa-trophy text-brown"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted mb-2">Activities</h6>
                                <h3 class="mb-0 text-sage" id="totalActivities">
                                    <?php echo $analytics['activity']['summary']['total_activities'] ?? 0; ?>
                                </h3>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up"></i> 
                                    <?php echo ($stats['activities_growth'] ?? 0) >= 0 ? '+' : ''; ?><?php echo $stats['activities_growth'] ?? 0; ?>%
                                </small>
                            </div>
                            <div class="icon-wrapper bg-sage-light">
                                <i class="fas fa-chart-line text-sage"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Revenue Trend Chart -->
            <div class="col-xl-8 col-lg-7 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line text-sage me-2"></i>Revenue Trend
                            </h5>
                            <div class="btn-group btn-group-sm" role="group" id="chartPeriodToggle">
                                <input type="radio" class="btn-check" name="chartPeriod" id="monthly" value="monthly" checked>
                                <label class="btn btn-outline-sage" for="monthly">Monthly</label>
                                <input type="radio" class="btn-check" name="chartPeriod" id="weekly" value="weekly">
                                <label class="btn btn-outline-sage" for="weekly">Weekly</label>
                                <input type="radio" class="btn-check" name="chartPeriod" id="daily" value="daily">
                                <label class="btn btn-outline-sage" for="daily">Daily</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pipeline Distribution -->
            <div class="col-xl-4 col-lg-5 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie text-terracotta me-2"></i>Pipeline Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="pipelineChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity and Productivity Row -->
        <div class="row mb-4">
            <!-- Recent Activities -->
            <div class="col-xl-6 col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock text-brown me-2"></i>Recent Activities
                            </h5>
                            <a href="/activities" class="btn btn-sm btn-outline-brown">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <?php if (!empty($recent_activities)): ?>
                                <?php foreach (array_slice($recent_activities, 0, 8) as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas <?php echo $activity['icon']; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-clock fa-2x mb-3"></i>
                                    <p>No recent activities</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Task Summary -->
            <div class="col-xl-6 col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-rocket text-sage me-2"></i>Quick Actions & Tasks
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Quick Actions -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">Quick Actions</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="/contacts/create" class="btn btn-outline-sage w-100 btn-sm">
                                        <i class="fas fa-user-plus me-1"></i>New Contact
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/deals/create" class="btn btn-outline-terracotta w-100 btn-sm">
                                        <i class="fas fa-handshake me-1"></i>New Deal
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/tasks/create" class="btn btn-outline-brown w-100 btn-sm">
                                        <i class="fas fa-plus me-1"></i>New Task
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/organizations/create" class="btn btn-outline-sage w-100 btn-sm">
                                        <i class="fas fa-building me-1"></i>New Org
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Task Summary -->
                        <div>
                            <h6 class="text-muted mb-3">Task Summary</h6>
                            <div class="task-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Pending Tasks</span>
                                    <span class="badge bg-terracotta">
                                        <?php echo $analytics['productivity']['summary']['pending_tasks'] ?? 0; ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>In Progress</span>
                                    <span class="badge bg-brown">
                                        <?php echo $analytics['productivity']['summary']['in_progress_tasks'] ?? 0; ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Completed</span>
                                    <span class="badge bg-sage">
                                        <?php echo $analytics['productivity']['summary']['completed_tasks'] ?? 0; ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Overdue</span>
                                    <span class="badge bg-danger">
                                        <?php echo $analytics['productivity']['summary']['overdue_tasks'] ?? 0; ?>
                                    </span>
                                </div>
                                <div class="mt-3">
                                    <div class="progress" style="height: 8px;">
                                        <?php
                                        $total = ($analytics['productivity']['summary']['total_tasks'] ?? 1);
                                        $completed = ($analytics['productivity']['summary']['completed_tasks'] ?? 0);
                                        $percentage = $total > 0 ? ($completed / $total) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-sage" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo number_format($percentage, 1); ?>% completion rate
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Items Row -->
        <div class="row">
            <!-- Recent Contacts -->
            <div class="col-xl-4 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-address-book text-sage me-2"></i>Recent Contacts
                            </h6>
                            <a href="/contacts" class="btn btn-sm btn-outline-sage">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (!empty($recent_items['contacts'])): ?>
                                <?php foreach (array_slice($recent_items['contacts'], 0, 5) as $contact): ?>
                                <a href="/contacts/<?php echo $contact['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></h6>
                                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars($contact['email']); ?></p>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M d', strtotime($contact['created_at'])); ?>
                                        </small>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-address-book fa-2x mb-2"></i>
                                    <p class="mb-0">No contacts yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Deals -->
            <div class="col-xl-4 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-handshake text-terracotta me-2"></i>Recent Deals
                            </h6>
                            <a href="/deals" class="btn btn-sm btn-outline-terracotta">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (!empty($recent_items['deals'])): ?>
                                <?php foreach (array_slice($recent_items['deals'], 0, 5) as $deal): ?>
                                <a href="/deals/<?php echo $deal['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($deal['title']); ?></h6>
                                            <p class="mb-1 text-muted small">
                                                $<?php echo number_format($deal['amount'], 2); ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?php echo $deal['status'] === 'won' ? 'bg-sage' : ($deal['status'] === 'lost' ? 'bg-danger' : 'bg-terracotta'); ?>">
                                                <?php echo ucfirst($deal['status']); ?>
                                            </span>
                                            <small class="text-muted d-block">
                                                <?php echo date('M d', strtotime($deal['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-handshake fa-2x mb-2"></i>
                                    <p class="mb-0">No deals yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tasks -->
            <div class="col-xl-4 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-tasks text-brown me-2"></i>Recent Tasks
                            </h6>
                            <a href="/tasks" class="btn btn-sm btn-outline-brown">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (!empty($recent_items['tasks'])): ?>
                                <?php foreach (array_slice($recent_items['tasks'], 0, 5) as $task): ?>
                                <a href="/tasks/<?php echo $task['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                            <p class="mb-1 text-muted small">
                                                Due: <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date'; ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?php 
                                                echo $task['status'] === 'completed' ? 'bg-sage' : 
                                                     ($task['status'] === 'in_progress' ? 'bg-terracotta' : 'bg-brown'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                            </span>
                                            <small class="text-muted d-block">
                                                <?php echo date('M d', strtotime($task['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-tasks fa-2x mb-2"></i>
                                    <p class="mb-0">No tasks yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.js"></script>
    <script src="/assets/js/dashboard.js"></script>

    <script>
        // Initialize Dashboard Charts
        document.addEventListener('DOMContentLoaded', function() {
            initializeRevenueChart();
            initializePipelineChart();
            initializeGlobalSearch();
            setupAutoRefresh();
        });

        function initializeRevenueChart() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            // Revenue trend data from analytics
            const revenueData = <?php echo json_encode($analytics['executive']['revenue_trend'] ?? []); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: revenueData.map(item => item.month),
                    datasets: [{
                        label: 'Monthly Revenue',
                        data: revenueData.map(item => item.revenue),
                        backgroundColor: 'rgba(156, 175, 136, 0.1)',
                        borderColor: '#9CAF88',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#9CAF88',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
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
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        function initializePipelineChart() {
            const ctx = document.getElementById('pipelineChart').getContext('2d');
            
            // Pipeline data from analytics
            const pipelineData = <?php echo json_encode($analytics['pipeline'] ?? []); ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: pipelineData.map(stage => stage.stage_name),
                    datasets: [{
                        data: pipelineData.map(stage => stage.deal_count),
                        backgroundColor: [
                            '#9CAF88', '#C8A882', '#8B7B6B', '#F5F2E8',
                            '#A8B99C', '#D4B894', '#978D7E', '#F0EDE5'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function initializeGlobalSearch() {
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    searchResults.classList.add('d-none');
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`/dashboard/search?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            displaySearchResults(data);
                        })
                        .catch(error => {
                            console.error('Search error:', error);
                        });
                }, 300);
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.add('d-none');
                }
            });
        }

        function displaySearchResults(results) {
            const searchResults = document.getElementById('searchResults');
            let html = '';

            ['contacts', 'deals', 'organizations'].forEach(type => {
                if (results[type] && results[type].length > 0) {
                    html += `<div class="p-2"><small class="text-muted text-uppercase fw-bold">${type}</small></div>`;
                    results[type].forEach(item => {
                        const url = `/${type}/${item.id}`;
                        const name = item.first_name ? `${item.first_name} ${item.last_name}` : (item.title || item.name);
                        html += `
                            <a href="${url}" class="dropdown-item py-2">
                                <i class="fas fa-${type === 'contacts' ? 'user' : type === 'deals' ? 'handshake' : 'building'} me-2"></i>
                                ${name}
                            </a>
                        `;
                    });
                }
            });

            if (html) {
                searchResults.innerHTML = html;
                searchResults.classList.remove('d-none');
            } else {
                searchResults.innerHTML = '<div class="p-3 text-muted">No results found</div>';
                searchResults.classList.remove('d-none');
            }
        }

        function refreshDashboard() {
            location.reload();
        }

        function setupAutoRefresh() {
            // Auto-refresh KPIs every 5 minutes
            setInterval(function() {
                updateKPIs();
            }, 300000); // 5 minutes
        }

        function updateKPIs() {
            fetch('/dashboard/api/kpi?kpi=sales_summary')
                .then(response => response.json())
                .then(data => {
                    if (data.total_revenue) {
                        document.getElementById('totalRevenue').textContent = '$' + data.total_revenue;
                    }
                    if (data.pipeline_value) {
                        document.getElementById('pipelineValue').textContent = '$' + data.pipeline_value;
                    }
                    if (data.win_rate) {
                        document.getElementById('winRate').textContent = data.win_rate;
                    }
                })
                .catch(error => console.error('KPI update error:', error));
        }

        // Chart period toggle functionality
        document.querySelectorAll('input[name="chartPeriod"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Update chart based on selected period
                updateRevenueChart(this.value);
            });
        });

        function updateRevenueChart(period) {
            fetch(`/dashboard/api/chart-data?chart=revenue_trend&period=${period}`)
                .then(response => response.json())
                .then(data => {
                    // Update chart with new data
                    // Implementation would depend on Chart.js instance management
                    console.log('Chart update:', data);
                })
                .catch(error => console.error('Chart update error:', error));
        }
    </script>
</body>
</html>
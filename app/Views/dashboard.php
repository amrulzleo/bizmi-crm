<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BizMi CRM</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bizmi.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-leaf"></i>
            BizMi CRM
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/dashboard" class="nav-link active">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
            </ul>
            
            <div class="nav-section-title">CRM</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/contacts" class="nav-link">
                        <i class="fas fa-users"></i>
                        Contacts
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/organizations" class="nav-link">
                        <i class="fas fa-building"></i>
                        Organizations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/deals" class="nav-link">
                        <i class="fas fa-handshake"></i>
                        Deals
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/activities" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        Activities
                    </a>
                </li>
            </ul>
            
            <div class="nav-section-title">Sales</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/products" class="nav-link">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/quotes" class="nav-link">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Quotes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/reports" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Reports
                    </a>
                </li>
            </ul>
            
            <div class="nav-section-title">Administration</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/users" class="nav-link">
                        <i class="fas fa-user-cog"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/settings" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Top Navigation Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </nav>
            </div>
            
            <div class="topbar-right">
                <!-- Search Box -->
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search contacts, deals, organizations...">
                </div>
                
                <!-- Notifications -->
                <div class="notification-badge">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown user-menu">
                    <div class="user-avatar" onclick="toggleDropdown(this)">
                        <div class="avatar-img">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            <div class="user-role"><?php echo ucfirst($user['role']); ?></div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    
                    <div class="dropdown-menu">
                        <a href="<?php echo BASE_URL; ?>/profile" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            My Profile
                        </a>
                        <a href="<?php echo BASE_URL; ?>/account-settings" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            Account Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE_URL; ?>/help" class="dropdown-item">
                            <i class="fas fa-question-circle"></i>
                            Help & Support
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE_URL; ?>/logout" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! Here's what's happening with your business today.</p>
            </div>
            <div class="page-actions">
                <a href="<?php echo BASE_URL; ?>/contacts/create" class="btn btn-outline-primary">
                    <i class="fas fa-plus"></i>
                    Add Contact
                </a>
                <a href="<?php echo BASE_URL; ?>/deals/create" class="btn btn-primary">
                    <i class="fas fa-handshake"></i>
                    Create Deal
                </a>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="content-wrapper">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Contacts</div>
                        <div class="stat-icon contacts">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_contacts']); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php echo $stats['contacts_growth']; ?>% this month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Active Deals</div>
                        <div class="stat-icon deals">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['active_deals']); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php echo $stats['deals_growth']; ?>% this month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Revenue</div>
                        <div class="stat-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">$<?php echo number_format($stats['total_revenue']); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php echo $stats['revenue_growth']; ?>% this month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Activities</div>
                        <div class="stat-icon activities">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_activities']); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php echo $stats['activities_growth']; ?>% this month
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="dashboard-row">
                    <!-- Recent Activity -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-clock text-muted me-2"></i>
                                    Recent Activity
                                </h5>
                                <a href="<?php echo BASE_URL; ?>/activities" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="activity-list">
                                    <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas <?php echo $activity['icon']; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </div>
                                            <div class="activity-time">
                                                <?php echo timeAgo($activity['created_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-bolt text-muted me-2"></i>
                                    Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="<?php echo BASE_URL; ?>/contacts/create" class="quick-action-item">
                                        <div class="quick-action-icon">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="quick-action-text">
                                            <div class="quick-action-title">Add Contact</div>
                                            <div class="quick-action-desc">Create new contact</div>
                                        </div>
                                    </a>
                                    
                                    <a href="<?php echo BASE_URL; ?>/organizations/create" class="quick-action-item">
                                        <div class="quick-action-icon">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="quick-action-text">
                                            <div class="quick-action-title">Add Organization</div>
                                            <div class="quick-action-desc">Create new company</div>
                                        </div>
                                    </a>
                                    
                                    <a href="<?php echo BASE_URL; ?>/deals/create" class="quick-action-item">
                                        <div class="quick-action-icon">
                                            <i class="fas fa-handshake"></i>
                                        </div>
                                        <div class="quick-action-text">
                                            <div class="quick-action-title">Create Deal</div>
                                            <div class="quick-action-desc">New sales opportunity</div>
                                        </div>
                                    </a>
                                    
                                    <a href="<?php echo BASE_URL; ?>/activities/create" class="quick-action-item">
                                        <div class="quick-action-icon">
                                            <i class="fas fa-calendar-plus"></i>
                                        </div>
                                        <div class="quick-action-text">
                                            <div class="quick-action-title">Schedule Activity</div>
                                            <div class="quick-action-desc">Plan meeting or call</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-row">
                    <!-- Pipeline Overview -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-pie text-muted me-2"></i>
                                    Sales Pipeline
                                </h5>
                                <a href="<?php echo BASE_URL; ?>/deals" class="btn btn-sm btn-outline-primary">View Pipeline</a>
                            </div>
                            <div class="card-body">
                                <div class="pipeline-stages">
                                    <?php foreach ($pipeline_stages as $stage): ?>
                                    <div class="pipeline-stage">
                                        <div class="stage-header">
                                            <span class="stage-name"><?php echo htmlspecialchars($stage['name']); ?></span>
                                            <span class="stage-count"><?php echo $stage['count']; ?></span>
                                        </div>
                                        <div class="stage-value">$<?php echo number_format($stage['value']); ?></div>
                                        <div class="stage-bar">
                                            <div class="stage-progress" style="width: <?php echo $stage['percentage']; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Contacts -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-users text-muted me-2"></i>
                                    Recent Contacts
                                </h5>
                                <a href="<?php echo BASE_URL; ?>/contacts" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="contact-list">
                                    <?php foreach ($recent_contacts as $contact): ?>
                                    <div class="contact-item">
                                        <div class="contact-avatar">
                                            <?php echo strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="contact-info">
                                            <div class="contact-name">
                                                <?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?>
                                            </div>
                                            <div class="contact-company">
                                                <?php echo htmlspecialchars($contact['organization_name']); ?>
                                            </div>
                                        </div>
                                        <div class="contact-actions">
                                            <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="btn btn-sm btn-ghost">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                            <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" class="btn btn-sm btn-ghost">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>/assets/js/layout.js"></script>
</body>
</html>
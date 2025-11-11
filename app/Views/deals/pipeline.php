<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Pipeline - BizMi CRM</title>
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bizmi.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/pipeline.css">
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
                    <a href="<?php echo BASE_URL; ?>/dashboard" class="nav-link">
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
                    <a href="<?php echo BASE_URL; ?>/deals" class="nav-link active">
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
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/deals">Deals</a></li>
                        <li class="breadcrumb-item active">Pipeline</li>
                    </ol>
                </nav>
            </div>
            
            <div class="topbar-right">
                <!-- Search Box -->
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search deals...">
                </div>
                
                <!-- Notifications -->
                <div class="notification-badge">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown user-menu">
                    <div class="user-avatar" onclick="toggleDropdown(this)">
                        <div class="avatar-img">AK</div>
                        <div class="user-info">
                            <div class="user-name">Amrullah Khan</div>
                            <div class="user-role">Admin</div>
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
                <h1 class="page-title">Sales Pipeline</h1>
                <p class="page-subtitle">Track and manage your sales opportunities through each stage</p>
            </div>
            <div class="page-actions">
                <a href="<?php echo BASE_URL; ?>/deals" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i>
                    List View
                </a>
                <a href="<?php echo BASE_URL; ?>/deals/forecast" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line"></i>
                    Forecast
                </a>
                <a href="<?php echo BASE_URL; ?>/deals/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add Deal
                </a>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Pipeline Statistics -->
            <div class="pipeline-stats-row">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_deals']); ?></div>
                    <div class="stat-label">Total Deals</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        12% this month
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($stats['total_value']); ?></div>
                    <div class="stat-label">Pipeline Value</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        8% this month
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($stats['weighted_value']); ?></div>
                    <div class="stat-label">Weighted Value</div>
                    <div class="stat-trend neutral">
                        <i class="fas fa-minus"></i>
                        2% this month
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($stats['avg_deal_size']); ?></div>
                    <div class="stat-label">Avg Deal Size</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        5% this month
                    </div>
                </div>
            </div>

            <!-- Pipeline Kanban Board -->
            <div class="pipeline-board" id="pipelineBoard">
                <?php foreach ($stages as $stage): ?>
                    <div class="pipeline-stage" data-stage-id="<?php echo $stage['id']; ?>">
                        <div class="stage-header">
                            <div class="stage-info">
                                <h3 class="stage-name"><?php echo htmlspecialchars($stage['name']); ?></h3>
                                <div class="stage-meta">
                                    <span class="deal-count"><?php echo $stage['count']; ?> deals</span>
                                    <span class="stage-value">$<?php echo number_format($stage['value']); ?></span>
                                </div>
                            </div>
                            <div class="stage-probability">
                                <?php if (isset($stage['probability'])): ?>
                                    <span class="probability-badge"><?php echo $stage['probability']; ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="deals-list" data-stage="<?php echo $stage['id']; ?>">
                            <?php if (isset($deals_by_stage[$stage['id']])): ?>
                                <?php foreach ($deals_by_stage[$stage['id']] as $deal): ?>
                                    <div class="deal-card" data-deal-id="<?php echo $deal['id']; ?>" draggable="true">
                                        <div class="deal-header">
                                            <h4 class="deal-name">
                                                <a href="<?php echo BASE_URL; ?>/deals/<?php echo $deal['id']; ?>">
                                                    <?php echo htmlspecialchars($deal['name']); ?>
                                                </a>
                                            </h4>
                                            <div class="deal-amount">$<?php echo number_format($deal['amount']); ?></div>
                                        </div>
                                        
                                        <div class="deal-details">
                                            <?php if (!empty($deal['contact_first_name'])): ?>
                                                <div class="deal-contact">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($deal['contact_first_name'] . ' ' . $deal['contact_last_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($deal['organization_name'])): ?>
                                                <div class="deal-organization">
                                                    <i class="fas fa-building"></i>
                                                    <?php echo htmlspecialchars($deal['organization_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($deal['expected_close_date'])): ?>
                                                <div class="deal-close-date <?php echo strtotime($deal['expected_close_date']) < time() ? 'overdue' : ''; ?>">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo date('M j, Y', strtotime($deal['expected_close_date'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="deal-footer">
                                            <div class="deal-owner">
                                                <div class="owner-avatar">
                                                    <?php echo strtoupper(substr($deal['owner_first_name'], 0, 1) . substr($deal['owner_last_name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="deal-actions">
                                                <button class="btn-icon" onclick="editDeal(<?php echo $deal['id']; ?>)" title="Edit Deal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <div class="dropdown">
                                                    <button class="btn-icon dropdown-toggle" onclick="toggleDropdown(this)" title="More Actions">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a href="<?php echo BASE_URL; ?>/deals/<?php echo $deal['id']; ?>" class="dropdown-item">
                                                            <i class="fas fa-eye"></i>
                                                            View Details
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>/activities/create?deal_id=<?php echo $deal['id']; ?>" class="dropdown-item">
                                                            <i class="fas fa-calendar-plus"></i>
                                                            Add Activity
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        <button class="dropdown-item text-danger" onclick="deleteDeal(<?php echo $deal['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                            Delete Deal
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($deal['probability'])): ?>
                                            <div class="deal-probability-bar">
                                                <div class="probability-fill" style="width: <?php echo $deal['probability']; ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Add Deal Button -->
                            <div class="add-deal-card">
                                <button class="btn-add-deal" onclick="addDealToStage(<?php echo $stage['id']; ?>)">
                                    <i class="fas fa-plus"></i>
                                    Add Deal
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Quick Stats Cards -->
            <div class="quick-stats-grid mt-4">
                <div class="quick-stat-card">
                    <div class="stat-icon won">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-title">Deals Won This Month</div>
                        <div class="stat-value">15</div>
                        <div class="stat-change positive">+3 from last month</div>
                    </div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="stat-icon lost">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-title">Deals Lost This Month</div>
                        <div class="stat-value">4</div>
                        <div class="stat-change negative">+1 from last month</div>
                    </div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="stat-icon conversion">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-title">Conversion Rate</div>
                        <div class="stat-value">78.9%</div>
                        <div class="stat-change positive">+2.3% from last month</div>
                    </div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="stat-icon cycle">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-title">Avg. Sales Cycle</div>
                        <div class="stat-value">32 days</div>
                        <div class="stat-change positive">-3 days from last month</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Deal Quick Add Modal -->
    <div class="modal fade" id="quickAddDealModal" tabindex="-1" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Add Deal</h5>
                    <button type="button" class="btn-close" onclick="closeModal('quickAddDealModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="quickAddDealForm" class="modal-body">
                    <input type="hidden" id="quickDealStageId" name="stage_id">
                    
                    <div class="form-group">
                        <label for="quickDealName">Deal Name *</label>
                        <input type="text" id="quickDealName" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quickDealAmount">Amount</label>
                        <input type="number" id="quickDealAmount" name="amount" class="form-control" step="0.01" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="quickDealContact">Contact</label>
                        <select id="quickDealContact" name="contact_id" class="form-control">
                            <option value="">Select a contact</option>
                            <!-- Options would be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quickDealCloseDate">Expected Close Date</label>
                        <input type="date" id="quickDealCloseDate" name="expected_close_date" class="form-control">
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('quickAddDealModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitQuickDeal()">Add Deal</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/layout.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/pipeline.js"></script>
</body>
</html>
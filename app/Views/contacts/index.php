<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts - BizMi CRM</title>
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bizmi.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components.css">
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
                    <a href="<?php echo BASE_URL; ?>/contacts" class="nav-link active">
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
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Contacts</li>
                    </ol>
                </nav>
            </div>
            
            <div class="topbar-right">
                <!-- Search Box -->
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search contacts..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
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
                <h1 class="page-title">Contacts</h1>
                <p class="page-subtitle">Manage your contacts and relationships</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-outline-primary" onclick="showFilters()">
                    <i class="fas fa-filter"></i>
                    Filters
                </button>
                <button class="btn btn-outline-primary" onclick="exportContacts()">
                    <i class="fas fa-download"></i>
                    Export
                </button>
                <a href="<?php echo BASE_URL; ?>/contacts/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add Contact
                </a>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Statistics Row -->
            <div class="stats-row mb-4">
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Contacts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo count(array_filter($stats['by_status'], fn($s) => $s['status'] === 'active')); ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo count(array_filter($stats['by_status'], fn($s) => $s['status'] === 'potential')); ?></div>
                    <div class="stat-label">Potential</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo count(array_filter($stats['by_status'], fn($s) => $s['status'] === 'converted')); ?></div>
                    <div class="stat-label">Converted</div>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="card mb-4" id="filtersPanel" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-filter text-muted me-2"></i>
                        Filters
                    </h5>
                    <button class="btn btn-sm btn-ghost" onclick="clearFilters()">Clear All</button>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo BASE_URL; ?>/contacts" class="filter-form">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search">Search</label>
                                    <input type="text" id="search" name="search" class="form-control" 
                                           placeholder="Name, email, phone..." 
                                           value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <option value="active" <?php echo ($filters['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($filters['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="potential" <?php echo ($filters['status'] ?? '') === 'potential' ? 'selected' : ''; ?>>Potential</option>
                                        <option value="converted" <?php echo ($filters['status'] ?? '') === 'converted' ? 'selected' : ''; ?>>Converted</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="organization_id">Organization</label>
                                    <select id="organization_id" name="organization_id" class="form-control">
                                        <option value="">All Organizations</option>
                                        <?php foreach ($filter_options['organizations'] as $org): ?>
                                            <option value="<?php echo $org['id']; ?>" 
                                                    <?php echo ($filters['organization_id'] ?? '') == $org['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($org['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lead_source">Lead Source</label>
                                    <select id="lead_source" name="lead_source" class="form-control">
                                        <option value="">All Sources</option>
                                        <?php foreach ($filter_options['lead_sources'] as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" 
                                                    <?php echo ($filters['lead_source'] ?? '') === $key ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="<?php echo BASE_URL; ?>/contacts" class="btn btn-secondary ms-2">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contacts Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-users text-muted me-2"></i>
                        Contacts List
                    </h5>
                    <div class="card-actions">
                        <div class="view-toggle">
                            <button class="btn btn-sm btn-ghost active" data-view="table">
                                <i class="fas fa-table"></i>
                            </button>
                            <button class="btn btn-sm btn-ghost" data-view="grid">
                                <i class="fas fa-th"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover contacts-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_field' => 'first_name', 'sort_direction' => ($filters['sort_direction'] ?? 'DESC') === 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                            Name
                                            <?php if (($filters['sort_field'] ?? '') === 'c.first_name'): ?>
                                                <i class="fas fa-sort-<?php echo ($filters['sort_direction'] ?? 'DESC') === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_field' => 'email', 'sort_direction' => ($filters['sort_direction'] ?? 'DESC') === 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                            Email
                                            <?php if (($filters['sort_field'] ?? '') === 'c.email'): ?>
                                                <i class="fas fa-sort-<?php echo ($filters['sort_direction'] ?? 'DESC') === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>Phone</th>
                                    <th>Organization</th>
                                    <th>Status</th>
                                    <th>Deals Value</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($contacts)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-users empty-state-icon"></i>
                                                <h5 class="empty-state-title">No contacts found</h5>
                                                <p class="empty-state-description">
                                                    <?php if (!empty($filters['search'])): ?>
                                                        No contacts match your search criteria.
                                                    <?php else: ?>
                                                        Get started by adding your first contact.
                                                    <?php endif; ?>
                                                </p>
                                                <a href="<?php echo BASE_URL; ?>/contacts/create" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i>
                                                    Add Contact
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($contacts as $contact): ?>
                                        <tr class="contact-row" data-contact-id="<?php echo $contact['id']; ?>">
                                            <td>
                                                <input type="checkbox" class="form-check-input contact-checkbox" value="<?php echo $contact['id']; ?>">
                                            </td>
                                            <td>
                                                <div class="contact-info">
                                                    <div class="contact-avatar">
                                                        <?php echo strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="contact-details">
                                                        <a href="<?php echo BASE_URL; ?>/contacts/<?php echo $contact['id']; ?>" class="contact-name">
                                                            <?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?>
                                                        </a>
                                                        <?php if (!empty($contact['title'])): ?>
                                                            <div class="contact-title"><?php echo htmlspecialchars($contact['title']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($contact['email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="text-link">
                                                        <?php echo htmlspecialchars($contact['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($contact['phone'])): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" class="text-link">
                                                        <?php echo htmlspecialchars($contact['phone']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($contact['organization_name'])): ?>
                                                    <span class="organization-link">
                                                        <?php echo htmlspecialchars($contact['organization_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $contact['status']; ?>">
                                                    <?php echo ucfirst($contact['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($contact['active_deals_value'] > 0): ?>
                                                    <span class="text-success font-weight-medium">
                                                        $<?php echo number_format($contact['active_deals_value']); ?>
                                                    </span>
                                                    <small class="text-muted d-block"><?php echo $contact['deals_count']; ?> deals</small>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-muted" title="<?php echo date('F j, Y g:i A', strtotime($contact['created_at'])); ?>">
                                                    <?php echo date('M j, Y', strtotime($contact['created_at'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="<?php echo BASE_URL; ?>/contacts/<?php echo $contact['id']; ?>" 
                                                       class="btn btn-sm btn-ghost" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/contacts/<?php echo $contact['id']; ?>/edit" 
                                                       class="btn btn-sm btn-ghost" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-ghost text-danger" 
                                                            onclick="deleteContact(<?php echo $contact['id']; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="card-footer">
                        <div class="pagination-wrapper">
                            <div class="pagination-info">
                                Showing <?php echo (($pagination['current_page'] - 1) * $pagination['limit']) + 1; ?> 
                                to <?php echo min($pagination['current_page'] * $pagination['limit'], $pagination['total_count']); ?> 
                                of <?php echo number_format($pagination['total_count']); ?> contacts
                            </div>
                            <nav class="pagination-nav">
                                <ul class="pagination">
                                    <?php if ($pagination['has_prev']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['has_next']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>/assets/js/layout.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/contacts.js"></script>
</body>
</html>
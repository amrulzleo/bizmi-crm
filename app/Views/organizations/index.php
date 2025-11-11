<?php $this->layout('layouts/main', ['title' => 'Organizations - BizMi CRM']) ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-sm-6">
                    <h1 class="page-title">
                        <i class="fas fa-building"></i>
                        Organizations
                    </h1>
                    <p class="page-subtitle">Manage companies, customers, and business relationships</p>
                </div>
                <div class="col-sm-6">
                    <div class="page-actions">
                        <a href="/organizations/create" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            New Organization
                        </a>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-filter"></i>
                                Quick Filters
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/organizations?type=customer">Customers</a></li>
                                <li><a class="dropdown-item" href="/organizations?type=prospect">Prospects</a></li>
                                <li><a class="dropdown-item" href="/organizations?type=partner">Partners</a></li>
                                <li><a class="dropdown-item" href="/organizations?status=active">Active</a></li>
                                <li><a class="dropdown-item" href="/organizations?high_value=1">High Value</a></li>
                            </ul>
                        </div>
                        <a href="/organizations/analytics" class="btn btn-outline-secondary">
                            <i class="fas fa-chart-bar"></i>
                            Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="container-fluid mb-4">
        <div class="statistics-row">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['total_organizations']) ?></div>
                    <div class="stat-label">Total Organizations</div>
                    <div class="stat-meta">
                        <span class="new-count">+<?= $statistics['new_this_month'] ?> this month</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon customers">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['customers']) ?></div>
                    <div class="stat-label">Customers</div>
                    <div class="stat-meta">
                        <span class="conversion-rate"><?= $statistics['prospect_to_customer_rate'] ?>% conversion</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon prospects">
                    <i class="fas fa-search"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['prospects']) ?></div>
                    <div class="stat-label">Prospects</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon partners">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['partners']) ?></div>
                    <div class="stat-label">Partners</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">$<?= number_format($statistics['avg_revenue']) ?></div>
                    <div class="stat-label">Avg Annual Revenue</div>
                    <div class="stat-meta">
                        <span class="total-revenue">$<?= number_format($statistics['total_revenue']) ?> total</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon employees">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['avg_employees']) ?></div>
                    <div class="stat-label">Avg Employees</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="container-fluid mb-4">
        <div class="filters-card">
            <form method="GET" action="/organizations" class="filters-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Search Organizations</label>
                        <div class="search-input">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                                   placeholder="Search by name, website, email, or industry..." class="form-control">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="prospect" <?= $filters['type'] === 'prospect' ? 'selected' : '' ?>>Prospect</option>
                            <option value="customer" <?= $filters['type'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="partner" <?= $filters['type'] === 'partner' ? 'selected' : '' ?>>Partner</option>
                            <option value="vendor" <?= $filters['type'] === 'vendor' ? 'selected' : '' ?>>Vendor</option>
                            <option value="competitor" <?= $filters['type'] === 'competitor' ? 'selected' : '' ?>>Competitor</option>
                            <option value="other" <?= $filters['type'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="prospect" <?= $filters['status'] === 'prospect' ? 'selected' : '' ?>>Prospect</option>
                            <option value="customer" <?= $filters['status'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="former_customer" <?= $filters['status'] === 'former_customer' ? 'selected' : '' ?>>Former Customer</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Industry</label>
                        <select name="industry" class="form-select">
                            <option value="">All Industries</option>
                            <?php foreach ($industries as $key => $industry): ?>
                            <option value="<?= $key ?>" <?= $filters['industry'] === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($industry) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Size</label>
                        <select name="size" class="form-select">
                            <option value="">All Sizes</option>
                            <option value="startup" <?= $filters['size'] === 'startup' ? 'selected' : '' ?>>Startup (1-10)</option>
                            <option value="small" <?= $filters['size'] === 'small' ? 'selected' : '' ?>>Small (11-50)</option>
                            <option value="medium" <?= $filters['size'] === 'medium' ? 'selected' : '' ?>>Medium (51-200)</option>
                            <option value="large" <?= $filters['size'] === 'large' ? 'selected' : '' ?>>Large (201-1000)</option>
                            <option value="enterprise" <?= $filters['size'] === 'enterprise' ? 'selected' : '' ?>>Enterprise (1000+)</option>
                        </select>
                    </div>

                    <?php if (!empty($users)): ?>
                    <div class="filter-group">
                        <label>Assigned To</label>
                        <select name="assigned_user_id" class="form-select">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $filters['assigned_user_id'] == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?= htmlspecialchars($filters['location']) ?>" 
                               placeholder="City, State, or Country" class="form-control">
                    </div>

                    <div class="filter-group">
                        <label>Min Revenue</label>
                        <input type="number" name="min_revenue" value="<?= htmlspecialchars($filters['min_revenue']) ?>" 
                               placeholder="0" class="form-control">
                    </div>

                    <div class="filter-group">
                        <label>Max Revenue</label>
                        <input type="number" name="max_revenue" value="<?= htmlspecialchars($filters['max_revenue']) ?>" 
                               placeholder="No limit" class="form-control">
                    </div>

                    <div class="filter-group">
                        <label>Employee Range</label>
                        <div class="employee-range">
                            <input type="number" name="min_employees" value="<?= htmlspecialchars($filters['min_employees']) ?>" 
                                   placeholder="Min" class="form-control">
                            <span>-</span>
                            <input type="number" name="max_employees" value="<?= htmlspecialchars($filters['max_employees']) ?>" 
                                   placeholder="Max" class="form-control">
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Filter
                        </button>
                        <a href="/organizations" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                            Clear
                        </a>
                    </div>
                </div>

                <div class="advanced-filters">
                    <div class="form-check">
                        <input type="checkbox" name="high_value" value="1" class="form-check-input" 
                               <?= !empty($filters['high_value']) ? 'checked' : '' ?> id="highValue">
                        <label class="form-check-label" for="highValue">
                            High Value Organizations ($1M+ revenue)
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Organizations List -->
    <div class="container-fluid">
        <div class="organizations-card">
            <?php if (empty($organizations)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>No Organizations Found</h3>
                <p>There are no organizations matching your current filters.</p>
                <a href="/organizations/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Create Your First Organization
                </a>
            </div>
            <?php else: ?>
            
            <!-- Quick Actions Bar -->
            <div class="quick-actions-bar">
                <div class="bulk-actions">
                    <input type="checkbox" id="select-all" class="form-check-input">
                    <label for="select-all">Select All</label>
                    
                    <div class="bulk-action-buttons" style="display: none;">
                        <button class="btn btn-sm btn-outline-primary" onclick="bulkAssign()">
                            <i class="fas fa-user"></i> Assign
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="bulkUpdateType()">
                            <i class="fas fa-tag"></i> Change Type
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>

                <div class="results-info">
                    Showing <?= number_format(count($organizations)) ?> of <?= number_format($totalOrganizations) ?> organizations
                </div>

                <div class="view-options">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary active" data-view="table">
                            <i class="fas fa-table"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Organizations Table -->
            <div class="organizations-table-container" id="tableView">
                <table class="table organizations-table">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th width="60">Logo</th>
                            <th>
                                <a href="/organizations?<?= http_build_query(array_merge($_GET, ['order_by' => 'name', 'order_direction' => $filters['order_by'] === 'name' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Organization
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th width="100">
                                <a href="/organizations?<?= http_build_query(array_merge($_GET, ['order_by' => 'type', 'order_direction' => $filters['order_by'] === 'type' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Type
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th width="120">
                                <a href="/organizations?<?= http_build_query(array_merge($_GET, ['order_by' => 'industry', 'order_direction' => $filters['order_by'] === 'industry' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Industry
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th width="80">
                                <a href="/organizations?<?= http_build_query(array_merge($_GET, ['order_by' => 'size', 'order_direction' => $filters['order_by'] === 'size' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Size
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th width="100">Revenue</th>
                            <th width="80">Contacts</th>
                            <th width="80">Deals</th>
                            <th width="140">Assigned To</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($organizations as $org): 
                            $orgModel = new \App\Models\Organization();
                            $typeInfo = $orgModel->getTypeLabel($org['type']);
                            $statusInfo = $orgModel->getStatusLabel($org['status']);
                            $sizeInfo = $orgModel->getSizeLabel($org['size']);
                        ?>
                        <tr class="organization-row" data-org-id="<?= $org['id'] ?>">
                            <td>
                                <input type="checkbox" class="form-check-input org-checkbox" value="<?= $org['id'] ?>">
                            </td>
                            <td>
                                <div class="org-logo">
                                    <?php if ($org['logo_url']): ?>
                                    <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="<?= htmlspecialchars($org['name']) ?>" class="logo-img">
                                    <?php else: ?>
                                    <div class="logo-placeholder">
                                        <?= strtoupper(substr($org['name'], 0, 2)) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="org-info">
                                    <div class="org-name">
                                        <a href="/organizations/show/<?= $org['id'] ?>" class="org-link">
                                            <?= htmlspecialchars($org['name']) ?>
                                        </a>
                                        <span class="status-badge <?= $statusInfo['class'] ?>">
                                            <?= $statusInfo['label'] ?>
                                        </span>
                                    </div>
                                    <?php if ($org['website']): ?>
                                    <div class="org-website">
                                        <i class="fas fa-globe"></i>
                                        <a href="<?= htmlspecialchars($org['website']) ?>" target="_blank">
                                            <?= htmlspecialchars($org['website']) ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($org['parent_organization_name']): ?>
                                    <div class="org-parent">
                                        <i class="fas fa-sitemap"></i>
                                        <span>Subsidiary of <?= htmlspecialchars($org['parent_organization_name']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="type-badge <?= $typeInfo['class'] ?>">
                                    <?= $typeInfo['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($org['industry'] ?: 'Not specified') ?>
                            </td>
                            <td>
                                <span class="size-badge <?= $sizeInfo['class'] ?>">
                                    <?= $sizeInfo['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($org['annual_revenue']): ?>
                                <div class="revenue-info">
                                    $<?= number_format($org['annual_revenue']) ?>
                                    <?php if ($org['total_revenue']): ?>
                                    <br><small class="text-muted">Sales: $<?= number_format($org['total_revenue']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/organizations/show/<?= $org['id'] ?>?tab=contacts" class="contact-count">
                                    <i class="fas fa-users"></i>
                                    <?= number_format($org['contact_count']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="/organizations/show/<?= $org['id'] ?>?tab=deals" class="deal-count">
                                    <i class="fas fa-handshake"></i>
                                    <?= number_format($org['deal_count']) ?>
                                    <?php if ($org['won_deals']): ?>
                                    <br><small class="text-success"><?= $org['won_deals'] ?> won</small>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <div class="assignee-info">
                                    <?php if ($org['assigned_first_name']): ?>
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($org['assigned_first_name'], 0, 1)) ?>
                                    </div>
                                    <div class="user-name">
                                        <?= htmlspecialchars($org['assigned_first_name'] . ' ' . $org['assigned_last_name']) ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="org-actions">
                                    <a href="/organizations/show/<?= $org['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/organizations/edit/<?= $org['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="More">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="/contacts/create?organization_id=<?= $org['id'] ?>">
                                                <i class="fas fa-user-plus"></i> Add Contact
                                            </a></li>
                                            <li><a class="dropdown-item" href="/deals/create?organization_id=<?= $org['id'] ?>">
                                                <i class="fas fa-handshake"></i> Add Deal
                                            </a></li>
                                            <li><a class="dropdown-item" href="/tasks/create?related_type=organization&related_id=<?= $org['id'] ?>">
                                                <i class="fas fa-tasks"></i> Add Task
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" onclick="deleteOrganization(<?= $org['id'] ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Organizations Grid View (Hidden by default) -->
            <div class="organizations-grid-container" id="gridView" style="display: none;">
                <div class="organizations-grid">
                    <?php foreach ($organizations as $org): 
                        $orgModel = new \App\Models\Organization();
                        $typeInfo = $orgModel->getTypeLabel($org['type']);
                        $statusInfo = $orgModel->getStatusLabel($org['status']);
                    ?>
                    <div class="org-card" data-org-id="<?= $org['id'] ?>">
                        <div class="org-card-header">
                            <div class="org-logo">
                                <?php if ($org['logo_url']): ?>
                                <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="<?= htmlspecialchars($org['name']) ?>">
                                <?php else: ?>
                                <div class="logo-placeholder">
                                    <?= strtoupper(substr($org['name'], 0, 2)) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="org-badges">
                                <span class="type-badge <?= $typeInfo['class'] ?>"><?= $typeInfo['label'] ?></span>
                                <span class="status-badge <?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
                            </div>
                        </div>
                        
                        <div class="org-card-body">
                            <h4 class="org-name">
                                <a href="/organizations/show/<?= $org['id'] ?>"><?= htmlspecialchars($org['name']) ?></a>
                            </h4>
                            
                            <?php if ($org['industry']): ?>
                            <div class="org-industry">
                                <i class="fas fa-industry"></i>
                                <?= htmlspecialchars($org['industry']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($org['website']): ?>
                            <div class="org-website">
                                <i class="fas fa-globe"></i>
                                <a href="<?= htmlspecialchars($org['website']) ?>" target="_blank">
                                    <?= htmlspecialchars($org['website']) ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <div class="org-stats">
                                <div class="stat">
                                    <i class="fas fa-users"></i>
                                    <?= number_format($org['contact_count']) ?> contacts
                                </div>
                                <div class="stat">
                                    <i class="fas fa-handshake"></i>
                                    <?= number_format($org['deal_count']) ?> deals
                                </div>
                            </div>
                        </div>
                        
                        <div class="org-card-footer">
                            <div class="assignee">
                                <?php if ($org['assigned_first_name']): ?>
                                <div class="user-avatar">
                                    <?= strtoupper(substr($org['assigned_first_name'], 0, 1)) ?>
                                </div>
                                <span><?= htmlspecialchars($org['assigned_first_name'] . ' ' . $org['assigned_last_name']) ?></span>
                                <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-actions">
                                <a href="/organizations/show/<?= $org['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/organizations/edit/<?= $org['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-wrapper">
                <nav aria-label="Organizations pagination">
                    <ul class="pagination">
                        <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="/organizations?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="/organizations?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="/organizations?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="/organizations?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="/organizations?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/assets/js/organizations.js"></script>
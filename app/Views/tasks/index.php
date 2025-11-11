<?php $this->layout('layouts/main', ['title' => 'Tasks & Activities - BizMi CRM']) ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-sm-6">
                    <h1 class="page-title">
                        <i class="fas fa-tasks"></i>
                        Tasks & Activities
                    </h1>
                    <p class="page-subtitle">Manage your tasks, activities, and productivity</p>
                </div>
                <div class="col-sm-6">
                    <div class="page-actions">
                        <a href="/tasks/create" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            New Task
                        </a>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-filter"></i>
                                Quick Filters
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/tasks?overdue=1">Overdue Tasks</a></li>
                                <li><a class="dropdown-item" href="/tasks?due_today=1">Due Today</a></li>
                                <li><a class="dropdown-item" href="/tasks?due_this_week=1">Due This Week</a></li>
                                <li><a class="dropdown-item" href="/tasks?status=pending">Pending</a></li>
                                <li><a class="dropdown-item" href="/tasks?priority=urgent">Urgent Priority</a></li>
                            </ul>
                        </div>
                        <div class="view-toggle">
                            <a href="/tasks?view=list" class="btn btn-outline-secondary <?= $view === 'list' ? 'active' : '' ?>">
                                <i class="fas fa-list"></i>
                            </a>
                            <a href="/tasks/calendar" class="btn btn-outline-secondary <?= $view === 'calendar' ? 'active' : '' ?>">
                                <i class="fas fa-calendar"></i>
                            </a>
                        </div>
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
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['total_tasks']) ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['pending']) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon progress">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['in_progress']) ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['completed']) ?></div>
                    <div class="stat-label">Completed</div>
                    <div class="stat-rate"><?= $statistics['completion_rate'] ?>%</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon overdue">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['overdue']) ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon due-today">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($statistics['due_today']) ?></div>
                    <div class="stat-label">Due Today</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="container-fluid mb-4">
        <div class="filters-card">
            <form method="GET" action="/tasks" class="filters-form">
                <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Search Tasks</label>
                        <div class="search-input">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                                   placeholder="Search by title, description, or assignee..." class="form-control">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="waiting" <?= $filters['status'] === 'waiting' ? 'selected' : '' ?>>Waiting</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Priority</label>
                        <select name="priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="urgent" <?= $filters['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                            <option value="high" <?= $filters['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                            <option value="normal" <?= $filters['priority'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="low" <?= $filters['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <option value="call" <?= $filters['category'] === 'call' ? 'selected' : '' ?>>Call</option>
                            <option value="email" <?= $filters['category'] === 'email' ? 'selected' : '' ?>>Email</option>
                            <option value="meeting" <?= $filters['category'] === 'meeting' ? 'selected' : '' ?>>Meeting</option>
                            <option value="follow_up" <?= $filters['category'] === 'follow_up' ? 'selected' : '' ?>>Follow Up</option>
                            <option value="demo" <?= $filters['category'] === 'demo' ? 'selected' : '' ?>>Demo</option>
                            <option value="proposal" <?= $filters['category'] === 'proposal' ? 'selected' : '' ?>>Proposal</option>
                            <option value="other" <?= $filters['category'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <?php if (!empty($users)): ?>
                    <div class="filter-group">
                        <label>Assigned To</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $filters['assigned_to'] == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label>Due Date From</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" class="form-control">
                    </div>

                    <div class="filter-group">
                        <label>Due Date To</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" class="form-control">
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Filter
                        </button>
                        <a href="/tasks" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                            Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="container-fluid">
        <div class="tasks-card">
            <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h3>No Tasks Found</h3>
                <p>There are no tasks matching your current filters.</p>
                <a href="/tasks/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Create Your First Task
                </a>
            </div>
            <?php else: ?>
            
            <!-- Quick Actions Bar -->
            <div class="quick-actions-bar">
                <div class="bulk-actions">
                    <input type="checkbox" id="select-all" class="form-check-input">
                    <label for="select-all">Select All</label>
                    
                    <div class="bulk-action-buttons" style="display: none;">
                        <button class="btn btn-sm btn-outline-primary" onclick="bulkUpdateStatus('completed')">
                            <i class="fas fa-check"></i> Mark Complete
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="bulkUpdateStatus('in_progress')">
                            <i class="fas fa-play"></i> Set In Progress
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>

                <div class="results-info">
                    Showing <?= number_format(count($tasks)) ?> of <?= number_format($totalTasks) ?> tasks
                </div>
            </div>

            <!-- Tasks Table -->
            <div class="tasks-table-container">
                <table class="table tasks-table">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th width="40">
                                <a href="/tasks?<?= http_build_query(array_merge($_GET, ['order_by' => 'priority', 'order_direction' => $filters['order_by'] === 'priority' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Priority
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th>
                                <a href="/tasks?<?= http_build_query(array_merge($_GET, ['order_by' => 'title', 'order_direction' => $filters['order_by'] === 'title' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Task
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th width="120">
                                <a href="/tasks?<?= http_build_query(array_merge($_GET, ['order_by' => 'status', 'order_direction' => $filters['order_by'] === 'status' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Status
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th width="100">Category</th>
                            <th width="140">
                                <a href="/tasks?<?= http_build_query(array_merge($_GET, ['order_by' => 'assigned_to', 'order_direction' => $filters['order_by'] === 'assigned_to' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Assigned To
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th width="120">
                                <a href="/tasks?<?= http_build_query(array_merge($_GET, ['order_by' => 'due_date', 'order_direction' => $filters['order_by'] === 'due_date' && $filters['order_direction'] === 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                    Due Date
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th width="120">Related To</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): 
                            $taskModel = new \App\Models\Task();
                            $priorityInfo = $taskModel->getPriorityLabel($task['priority']);
                            $statusInfo = $taskModel->getStatusLabel($task['status']);
                            $categoryInfo = $taskModel->getCategoryLabel($task['category']);
                            $isOverdue = $task['status'] !== 'completed' && $task['status'] !== 'cancelled' && strtotime($task['due_date']) < time();
                        ?>
                        <tr class="task-row <?= $isOverdue ? 'overdue' : '' ?>" data-task-id="<?= $task['id'] ?>">
                            <td>
                                <input type="checkbox" class="form-check-input task-checkbox" value="<?= $task['id'] ?>">
                            </td>
                            <td>
                                <span class="priority-badge <?= $priorityInfo['class'] ?>">
                                    <?= $priorityInfo['label'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="task-title">
                                    <a href="/tasks/show/<?= $task['id'] ?>" class="task-link">
                                        <?= htmlspecialchars($task['title']) ?>
                                    </a>
                                    <?php if ($task['description']): ?>
                                    <div class="task-description">
                                        <?= htmlspecialchars(substr($task['description'], 0, 100)) ?>
                                        <?= strlen($task['description']) > 100 ? '...' : '' ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($task['tags']): ?>
                                    <div class="task-tags">
                                        <?php foreach (explode(',', $task['tags']) as $tag): ?>
                                        <span class="tag"><?= trim(htmlspecialchars($tag)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="status-dropdown">
                                    <span class="status-badge <?= $statusInfo['class'] ?>" data-bs-toggle="dropdown" style="cursor: pointer;">
                                        <?= $statusInfo['label'] ?>
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" onclick="updateTaskStatus(<?= $task['id'] ?>, 'pending')">Pending</a></li>
                                        <li><a class="dropdown-item" onclick="updateTaskStatus(<?= $task['id'] ?>, 'in_progress')">In Progress</a></li>
                                        <li><a class="dropdown-item" onclick="updateTaskStatus(<?= $task['id'] ?>, 'waiting')">Waiting</a></li>
                                        <li><a class="dropdown-item" onclick="updateTaskStatus(<?= $task['id'] ?>, 'completed')">Completed</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" onclick="updateTaskStatus(<?= $task['id'] ?>, 'cancelled')">Cancelled</a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <div class="category-info">
                                    <i class="<?= $categoryInfo['icon'] ?>"></i>
                                    <?= $categoryInfo['label'] ?>
                                </div>
                            </td>
                            <td>
                                <div class="assignee-info">
                                    <?php if ($task['assigned_first_name']): ?>
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($task['assigned_first_name'], 0, 1)) ?>
                                    </div>
                                    <div class="user-name">
                                        <?= htmlspecialchars($task['assigned_first_name'] . ' ' . $task['assigned_last_name']) ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($task['due_date']): ?>
                                <div class="due-date <?= $isOverdue ? 'overdue' : '' ?>">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('M j, Y', strtotime($task['due_date'])) ?>
                                    <?php if ($task['due_time']): ?>
                                    <br>
                                    <small><?= date('g:i A', strtotime($task['due_time'])) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">No due date</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['related_entity_name']): ?>
                                <div class="related-entity">
                                    <i class="fas fa-link"></i>
                                    <a href="/<?= $task['related_entity_type'] ?>s/show/<?= $task['related_entity_id'] ?>">
                                        <?= htmlspecialchars($task['related_entity_name']) ?>
                                    </a>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="task-actions">
                                    <a href="/tasks/show/<?= $task['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/tasks/edit/<?= $task['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?= $task['id'] ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-wrapper">
                <nav aria-label="Tasks pagination">
                    <ul class="pagination">
                        <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="/tasks?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="/tasks?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>">
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
                            <a class="page-link" href="/tasks?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="/tasks?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="/tasks?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
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

<!-- Quick Task Modal -->
<div class="modal fade" id="quickTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickTaskForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="quickTaskTitle" class="form-label">Task Title *</label>
                        <input type="text" class="form-control" id="quickTaskTitle" name="title" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quickTaskPriority" class="form-label">Priority</label>
                                <select class="form-select" id="quickTaskPriority" name="priority">
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quickTaskCategory" class="form-label">Category</label>
                                <select class="form-select" id="quickTaskCategory" name="category">
                                    <option value="other">Other</option>
                                    <option value="call">Call</option>
                                    <option value="email">Email</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="follow_up">Follow Up</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="quickTaskDueDate" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="quickTaskDueDate" name="due_date">
                    </div>
                    <?php if (!empty($users)): ?>
                    <div class="mb-3">
                        <label for="quickTaskAssignedTo" class="form-label">Assign To</label>
                        <select class="form-select" id="quickTaskAssignedTo" name="assigned_to">
                            <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/tasks.js"></script>
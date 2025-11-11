<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Organization;
use App\Models\Activity;

class TasksController extends BaseController
{
    private $taskModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->taskModel = new Task();
        $this->userModel = new User();
        $this->requireAuth();
    }

    /**
     * Display tasks list/calendar view
     */
    public function index()
    {
        $view = $_GET['view'] ?? 'list';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        // Get filter parameters
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category' => $_GET['category'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? '',
            'user_id' => $this->hasPermission('tasks.view_all') ? ($_GET['user_id'] ?? '') : $this->currentUser['id'],
            'overdue' => isset($_GET['overdue']) ? 1 : '',
            'due_today' => isset($_GET['due_today']) ? 1 : '',
            'due_this_week' => isset($_GET['due_this_week']) ? 1 : '',
            'limit' => $limit,
            'offset' => $offset,
            'order_by' => $_GET['order_by'] ?? 'due_date',
            'order_direction' => $_GET['order_direction'] ?? 'ASC'
        ];

        if ($view === 'calendar') {
            return $this->calendar();
        }

        // Get tasks and statistics
        $tasks = $this->taskModel->findAll($filters);
        $statistics = $this->taskModel->getStatistics(
            $this->hasPermission('tasks.view_all') ? null : $this->currentUser['id']
        );

        // Get users for filter dropdown (if user has permission)
        $users = [];
        if ($this->hasPermission('tasks.view_all')) {
            $users = $this->userModel->findAll(['status' => 'active']);
        }

        // Count total for pagination
        $totalFilters = $filters;
        unset($totalFilters['limit'], $totalFilters['offset']);
        $totalTasks = count($this->taskModel->findAll($totalFilters));
        
        $totalPages = ceil($totalTasks / $limit);

        $this->render('tasks/index', [
            'tasks' => $tasks,
            'statistics' => $statistics,
            'users' => $users,
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalTasks' => $totalTasks,
            'view' => $view
        ]);
    }

    /**
     * Display calendar view
     */
    public function calendar()
    {
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $userId = $this->hasPermission('tasks.view_all') ? ($_GET['user_id'] ?? null) : $this->currentUser['id'];

        // Get calendar month boundaries
        $startDate = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
        $endDate = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

        // Get tasks for the month
        $tasks = $this->taskModel->getTasksForCalendar($startDate, $endDate, $userId);

        // Group tasks by date
        $tasksByDate = [];
        foreach ($tasks as $task) {
            $date = date('Y-m-d', strtotime($task['due_date']));
            if (!isset($tasksByDate[$date])) {
                $tasksByDate[$date] = [];
            }
            $tasksByDate[$date][] = $task;
        }

        // Get users for filter (if permission allows)
        $users = [];
        if ($this->hasPermission('tasks.view_all')) {
            $users = $this->userModel->findAll(['status' => 'active']);
        }

        // Get statistics
        $statistics = $this->taskModel->getStatistics($userId);

        $this->render('tasks/calendar', [
            'tasksByDate' => $tasksByDate,
            'month' => $month,
            'year' => $year,
            'users' => $users,
            'selectedUserId' => $userId,
            'statistics' => $statistics
        ]);
    }

    /**
     * Show task details
     */
    public function show($id)
    {
        $task = $this->getTask($id);

        // Get related activities
        $activityModel = new Activity();
        $activities = $activityModel->getEntityActivities('task', $id);

        // Get related entity details
        $relatedEntity = null;
        if ($task['related_entity_type'] && $task['related_entity_id']) {
            switch ($task['related_entity_type']) {
                case 'contact':
                    $contactModel = new Contact();
                    $relatedEntity = $contactModel->find($task['related_entity_id']);
                    break;
                case 'deal':
                    $dealModel = new Deal();
                    $relatedEntity = $dealModel->find($task['related_entity_id']);
                    break;
                case 'organization':
                    $orgModel = new Organization();
                    $relatedEntity = $orgModel->find($task['related_entity_id']);
                    break;
            }
        }

        $this->render('tasks/show', [
            'task' => $task,
            'activities' => $activities,
            'relatedEntity' => $relatedEntity
        ]);
    }

    /**
     * Show create task form
     */
    public function create()
    {
        $this->checkPermission('tasks.create');

        // Get users for assignment
        $users = $this->userModel->findAll(['status' => 'active']);

        // Get related entity info from URL parameters
        $relatedEntityType = $_GET['related_type'] ?? '';
        $relatedEntityId = $_GET['related_id'] ?? '';
        $relatedEntity = null;

        if ($relatedEntityType && $relatedEntityId) {
            switch ($relatedEntityType) {
                case 'contact':
                    $contactModel = new Contact();
                    $relatedEntity = $contactModel->find($relatedEntityId);
                    break;
                case 'deal':
                    $dealModel = new Deal();
                    $relatedEntity = $dealModel->find($relatedEntityId);
                    break;
                case 'organization':
                    $orgModel = new Organization();
                    $relatedEntity = $orgModel->find($relatedEntityId);
                    break;
            }
        }

        $this->render('tasks/create', [
            'users' => $users,
            'relatedEntity' => $relatedEntity,
            'relatedEntityType' => $relatedEntityType,
            'relatedEntityId' => $relatedEntityId
        ]);
    }

    /**
     * Store new task
     */
    public function store()
    {
        $this->checkPermission('tasks.create');
        $this->validateCSRFToken();

        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'priority' => $_POST['priority'] ?? Task::PRIORITY_NORMAL,
            'status' => $_POST['status'] ?? Task::STATUS_PENDING,
            'category' => $_POST['category'] ?? Task::CATEGORY_OTHER,
            'due_date' => $_POST['due_date'] ?? '',
            'due_time' => $_POST['due_time'] ?? '',
            'assigned_to' => $_POST['assigned_to'] ?? $this->currentUser['id'],
            'related_entity_type' => $_POST['related_entity_type'] ?? '',
            'related_entity_id' => $_POST['related_entity_id'] ?? '',
            'estimated_hours' => $_POST['estimated_hours'] ?? null,
            'tags' => $_POST['tags'] ?? '',
            'created_by' => $this->currentUser['id'],
            'updated_by' => $this->currentUser['id']
        ];

        // Set reminder time if specified
        if (!empty($_POST['reminder_minutes']) && !empty($data['due_date'])) {
            $reminderTime = $data['due_date'];
            if (!empty($data['due_time'])) {
                $reminderTime .= ' ' . $data['due_time'];
            }
            $reminderDateTime = strtotime($reminderTime . ' -' . $_POST['reminder_minutes'] . ' minutes');
            $data['reminder_time'] = date('Y-m-d H:i:s', $reminderDateTime);
        }

        // Validate data
        $errors = $this->taskModel->validate($data);
        if (!empty($errors)) {
            $this->setFlashMessage('error', 'Please correct the errors below.');
            return $this->create();
        }

        try {
            $taskId = $this->taskModel->create($data);

            // Log activity
            $this->logActivity('task', $taskId, 'task_created', [
                'title' => $data['title'],
                'assigned_to' => $data['assigned_to'],
                'priority' => $data['priority']
            ]);

            $this->setFlashMessage('success', 'Task created successfully.');
            
            // Redirect based on context
            if (!empty($data['related_entity_type']) && !empty($data['related_entity_id'])) {
                $this->redirect("/{$data['related_entity_type']}s/show/{$data['related_entity_id']}?tab=tasks");
            } else {
                $this->redirect('/tasks/show/' . $taskId);
            }

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to create task: ' . $e->getMessage());
            return $this->create();
        }
    }

    /**
     * Show edit task form
     */
    public function edit($id)
    {
        $task = $this->getTask($id);
        $this->checkTaskPermission($task, 'edit');

        // Get users for assignment
        $users = $this->userModel->findAll(['status' => 'active']);

        $this->render('tasks/edit', [
            'task' => $task,
            'users' => $users
        ]);
    }

    /**
     * Update task
     */
    public function update($id)
    {
        $task = $this->getTask($id);
        $this->checkTaskPermission($task, 'edit');
        $this->validateCSRFToken();

        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'priority' => $_POST['priority'] ?? '',
            'status' => $_POST['status'] ?? '',
            'category' => $_POST['category'] ?? '',
            'due_date' => $_POST['due_date'] ?? '',
            'due_time' => $_POST['due_time'] ?? '',
            'assigned_to' => $_POST['assigned_to'] ?? '',
            'estimated_hours' => $_POST['estimated_hours'] ?? null,
            'actual_hours' => $_POST['actual_hours'] ?? null,
            'completion_notes' => $_POST['completion_notes'] ?? '',
            'tags' => $_POST['tags'] ?? '',
            'updated_by' => $this->currentUser['id']
        ];

        // Set reminder time if specified
        if (!empty($_POST['reminder_minutes']) && !empty($data['due_date'])) {
            $reminderTime = $data['due_date'];
            if (!empty($data['due_time'])) {
                $reminderTime .= ' ' . $data['due_time'];
            }
            $reminderDateTime = strtotime($reminderTime . ' -' . $_POST['reminder_minutes'] . ' minutes');
            $data['reminder_time'] = date('Y-m-d H:i:s', $reminderDateTime);
            $data['reminder_sent'] = 0; // Reset reminder
        }

        // Handle status change to completed
        if ($data['status'] === Task::STATUS_COMPLETED && $task['status'] !== Task::STATUS_COMPLETED) {
            $data['completion_date'] = date('Y-m-d H:i:s');
        }

        // Validate data
        $errors = $this->taskModel->validate($data, true);
        if (!empty($errors)) {
            $this->setFlashMessage('error', 'Please correct the errors below.');
            return $this->edit($id);
        }

        try {
            $this->taskModel->update($id, $data);

            // Log activity if important fields changed
            $changes = [];
            if ($task['status'] !== $data['status']) {
                $changes['status'] = ['from' => $task['status'], 'to' => $data['status']];
            }
            if ($task['assigned_to'] !== $data['assigned_to']) {
                $changes['assigned_to'] = ['from' => $task['assigned_to'], 'to' => $data['assigned_to']];
            }
            if ($task['priority'] !== $data['priority']) {
                $changes['priority'] = ['from' => $task['priority'], 'to' => $data['priority']];
            }

            if (!empty($changes)) {
                $this->logActivity('task', $id, 'task_updated', $changes);
            }

            $this->setFlashMessage('success', 'Task updated successfully.');
            $this->redirect('/tasks/show/' . $id);

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to update task: ' . $e->getMessage());
            return $this->edit($id);
        }
    }

    /**
     * Delete task
     */
    public function delete($id)
    {
        $task = $this->getTask($id);
        $this->checkTaskPermission($task, 'delete');
        $this->validateCSRFToken();

        try {
            $this->taskModel->delete($id);

            // Log activity
            $this->logActivity('task', $id, 'task_deleted', [
                'title' => $task['title']
            ]);

            $this->setFlashMessage('success', 'Task deleted successfully.');
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true]);
            } else {
                $this->redirect('/tasks');
            }

        } catch (Exception $e) {
            $message = 'Failed to delete task: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $message]);
            } else {
                $this->setFlashMessage('error', $message);
                $this->redirect('/tasks');
            }
        }
    }

    /**
     * Quick update task status
     */
    public function updateStatus()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('/tasks');
        }

        $this->validateCSRFToken();

        $taskId = $_POST['task_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $completionNotes = $_POST['completion_notes'] ?? '';

        $task = $this->getTask($taskId);
        $this->checkTaskPermission($task, 'edit');

        try {
            $this->taskModel->updateStatus($taskId, $status, $this->currentUser['id'], $completionNotes);

            // Log activity
            $this->logActivity('task', $taskId, 'status_updated', [
                'old_status' => $task['status'],
                'new_status' => $status,
                'completion_notes' => $completionNotes
            ]);

            $statusLabel = $this->taskModel->getStatusLabel($status);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Task status updated successfully',
                'status' => $status,
                'status_label' => $statusLabel['label'],
                'status_class' => $statusLabel['class']
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update task status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get task statistics for dashboard
     */
    public function getStatistics()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('/tasks');
        }

        $userId = $this->hasPermission('tasks.view_all') ? ($_GET['user_id'] ?? null) : $this->currentUser['id'];
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $statistics = $this->taskModel->getStatistics($userId);
        $productivity = $this->taskModel->getProductivityMetrics($userId, $dateFrom, $dateTo);
        $categoryStats = $this->taskModel->getTasksByCategory($userId);

        $this->jsonResponse([
            'success' => true,
            'statistics' => $statistics,
            'productivity' => $productivity,
            'categoryStats' => $categoryStats
        ]);
    }

    /**
     * Create quick task (AJAX)
     */
    public function quickCreate()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('/tasks');
        }

        $this->checkPermission('tasks.create');
        $this->validateCSRFToken();

        $data = [
            'title' => $_POST['title'] ?? '',
            'priority' => $_POST['priority'] ?? Task::PRIORITY_NORMAL,
            'category' => $_POST['category'] ?? Task::CATEGORY_OTHER,
            'due_date' => $_POST['due_date'] ?? '',
            'assigned_to' => $_POST['assigned_to'] ?? $this->currentUser['id'],
            'status' => Task::STATUS_PENDING,
            'created_by' => $this->currentUser['id'],
            'updated_by' => $this->currentUser['id']
        ];

        // Validate minimal required data
        if (empty($data['title'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Task title is required'
            ]);
        }

        try {
            $taskId = $this->taskModel->create($data);
            $task = $this->taskModel->find($taskId);

            // Log activity
            $this->logActivity('task', $taskId, 'task_created', [
                'title' => $data['title'],
                'priority' => $data['priority']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Task created successfully',
                'task' => $task
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to create task: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Search tasks (AJAX)
     */
    public function search()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('/tasks');
        }

        $searchTerm = $_GET['q'] ?? '';
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category' => $_GET['category'] ?? ''
        ];

        if (strlen($searchTerm) < 2) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Search term must be at least 2 characters'
            ]);
        }

        try {
            $tasks = $this->taskModel->search($searchTerm, $filters);

            $this->jsonResponse([
                'success' => true,
                'tasks' => $tasks,
                'count' => count($tasks)
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get tasks for specific entity (AJAX)
     */
    public function getEntityTasks()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('/tasks');
        }

        $entityType = $_GET['entity_type'] ?? '';
        $entityId = $_GET['entity_id'] ?? '';

        if (empty($entityType) || empty($entityId)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Entity type and ID are required'
            ]);
        }

        try {
            $tasks = $this->taskModel->getEntityTimeline($entityType, $entityId);

            $this->jsonResponse([
                'success' => true,
                'tasks' => $tasks,
                'count' => count($tasks)
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to fetch tasks: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get task with permission check
     */
    private function getTask($id)
    {
        $task = $this->taskModel->find($id);
        
        if (!$task) {
            $this->setFlashMessage('error', 'Task not found.');
            $this->redirect('/tasks');
        }

        return $task;
    }

    /**
     * Check task-specific permissions
     */
    private function checkTaskPermission($task, $action)
    {
        // Users can always access their own tasks
        if ($task['assigned_to'] == $this->currentUser['id'] || 
            $task['created_by'] == $this->currentUser['id']) {
            return true;
        }

        // Check global permission
        $this->checkPermission("tasks.{$action}_all");
        
        return true;
    }

    /**
     * Log activity for task actions
     */
    private function logActivity($entityType, $entityId, $action, $data = [])
    {
        try {
            $activityModel = new Activity();
            $activityModel->create([
                'user_id' => $this->currentUser['id'],
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'data' => json_encode($data),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
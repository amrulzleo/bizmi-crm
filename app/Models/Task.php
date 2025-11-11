<?php

namespace App\Models;

use PDO;

class Task extends BaseModel
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'due_time',
        'assigned_to',
        'assigned_by',
        'related_entity_type',
        'related_entity_id',
        'category',
        'estimated_hours',
        'actual_hours',
        'reminder_time',
        'reminder_sent',
        'completion_notes',
        'completion_date',
        'tags',
        'created_by',
        'updated_by'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_WAITING = 'waiting';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const CATEGORY_CALL = 'call';
    const CATEGORY_EMAIL = 'email';
    const CATEGORY_MEETING = 'meeting';
    const CATEGORY_FOLLOW_UP = 'follow_up';
    const CATEGORY_DEMO = 'demo';
    const CATEGORY_PROPOSAL = 'proposal';
    const CATEGORY_OTHER = 'other';

    /**
     * Get all tasks with filtering and pagination
     */
    public function findAll($params = [])
    {
        $query = "
            SELECT t.*,
                   assigned_user.first_name as assigned_first_name,
                   assigned_user.last_name as assigned_last_name,
                   assigned_user.email as assigned_email,
                   creator.first_name as creator_first_name,
                   creator.last_name as creator_last_name,
                   CASE 
                       WHEN t.related_entity_type = 'contact' THEN CONCAT(c.first_name, ' ', c.last_name)
                       WHEN t.related_entity_type = 'deal' THEN d.name
                       WHEN t.related_entity_type = 'organization' THEN o.name
                       ELSE 'No related entity'
                   END as related_entity_name
            FROM {$this->table} t
            LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
            LEFT JOIN users creator ON t.created_by = creator.id
            LEFT JOIN contacts c ON (t.related_entity_type = 'contact' AND t.related_entity_id = c.id)
            LEFT JOIN deals d ON (t.related_entity_type = 'deal' AND t.related_entity_id = d.id)
            LEFT JOIN organizations o ON (t.related_entity_type = 'organization' AND t.related_entity_id = o.id)
            WHERE 1=1
        ";

        $bindings = [];
        
        // Filter by user (assigned to or created by)
        if (!empty($params['user_id'])) {
            $query .= " AND (t.assigned_to = :user_id OR t.created_by = :user_id)";
            $bindings['user_id'] = $params['user_id'];
        }

        // Filter by status
        if (!empty($params['status'])) {
            if (is_array($params['status'])) {
                $placeholders = ':status_' . implode(', :status_', array_keys($params['status']));
                $query .= " AND t.status IN ($placeholders)";
                foreach ($params['status'] as $key => $status) {
                    $bindings["status_$key"] = $status;
                }
            } else {
                $query .= " AND t.status = :status";
                $bindings['status'] = $params['status'];
            }
        }

        // Filter by priority
        if (!empty($params['priority'])) {
            $query .= " AND t.priority = :priority";
            $bindings['priority'] = $params['priority'];
        }

        // Filter by category
        if (!empty($params['category'])) {
            $query .= " AND t.category = :category";
            $bindings['category'] = $params['category'];
        }

        // Filter by assigned user
        if (!empty($params['assigned_to'])) {
            $query .= " AND t.assigned_to = :assigned_to";
            $bindings['assigned_to'] = $params['assigned_to'];
        }

        // Filter by date range
        if (!empty($params['date_from'])) {
            $query .= " AND DATE(t.due_date) >= :date_from";
            $bindings['date_from'] = $params['date_from'];
        }

        if (!empty($params['date_to'])) {
            $query .= " AND DATE(t.due_date) <= :date_to";
            $bindings['date_to'] = $params['date_to'];
        }

        // Filter overdue tasks
        if (!empty($params['overdue'])) {
            $query .= " AND t.status != 'completed' AND t.due_date < NOW()";
        }

        // Filter due today
        if (!empty($params['due_today'])) {
            $query .= " AND DATE(t.due_date) = CURDATE()";
        }

        // Filter due this week
        if (!empty($params['due_this_week'])) {
            $query .= " AND YEARWEEK(t.due_date, 1) = YEARWEEK(CURDATE(), 1)";
        }

        // Search functionality
        if (!empty($params['search'])) {
            $query .= " AND (
                t.title LIKE :search 
                OR t.description LIKE :search
                OR CONCAT(assigned_user.first_name, ' ', assigned_user.last_name) LIKE :search
                OR t.tags LIKE :search
            )";
            $bindings['search'] = "%{$params['search']}%";
        }

        // Filter by related entity
        if (!empty($params['related_entity_type']) && !empty($params['related_entity_id'])) {
            $query .= " AND t.related_entity_type = :related_entity_type AND t.related_entity_id = :related_entity_id";
            $bindings['related_entity_type'] = $params['related_entity_type'];
            $bindings['related_entity_id'] = $params['related_entity_id'];
        }

        // Sorting
        $orderBy = $params['order_by'] ?? 'due_date';
        $orderDirection = strtoupper($params['order_direction'] ?? 'ASC');
        
        $allowedOrderBy = ['title', 'priority', 'status', 'due_date', 'assigned_to', 'created_at'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'due_date';
        }

        $query .= " ORDER BY t.$orderBy $orderDirection";

        // Pagination
        if (!empty($params['limit'])) {
            $offset = $params['offset'] ?? 0;
            $query .= " LIMIT $offset, {$params['limit']}";
        }

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get task statistics for dashboard
     */
    public function getStatistics($userId = null)
    {
        $userCondition = $userId ? "AND (assigned_to = $userId OR created_by = $userId)" : "";
        
        $query = "
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status != 'completed' AND status != 'cancelled' AND due_date < NOW() THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN DATE(due_date) = CURDATE() AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as due_today,
                SUM(CASE WHEN YEARWEEK(due_date, 1) = YEARWEEK(CURDATE(), 1) AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as due_this_week,
                AVG(CASE WHEN status = 'completed' AND actual_hours > 0 THEN actual_hours ELSE NULL END) as avg_completion_time,
                SUM(CASE WHEN priority = 'urgent' AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as urgent_open,
                SUM(CASE WHEN priority = 'high' AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as high_priority_open
            FROM {$this->table} 
            WHERE 1=1 $userCondition
        ";

        $result = $this->db->fetch($query);
        
        // Calculate completion rate
        $result['completion_rate'] = $result['total_tasks'] > 0 
            ? round(($result['completed'] / $result['total_tasks']) * 100, 1) 
            : 0;
            
        return $result;
    }

    /**
     * Get tasks by calendar view (month, week, day)
     */
    public function getTasksForCalendar($startDate, $endDate, $userId = null)
    {
        $query = "
            SELECT t.*,
                   assigned_user.first_name as assigned_first_name,
                   assigned_user.last_name as assigned_last_name,
                   CASE 
                       WHEN t.related_entity_type = 'contact' THEN CONCAT(c.first_name, ' ', c.last_name)
                       WHEN t.related_entity_type = 'deal' THEN d.name
                       WHEN t.related_entity_type = 'organization' THEN o.name
                       ELSE NULL
                   END as related_entity_name
            FROM {$this->table} t
            LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
            LEFT JOIN contacts c ON (t.related_entity_type = 'contact' AND t.related_entity_id = c.id)
            LEFT JOIN deals d ON (t.related_entity_type = 'deal' AND t.related_entity_id = d.id)
            LEFT JOIN organizations o ON (t.related_entity_type = 'organization' AND t.related_entity_id = o.id)
            WHERE DATE(t.due_date) BETWEEN :start_date AND :end_date
        ";

        $bindings = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        if ($userId) {
            $query .= " AND (t.assigned_to = :user_id OR t.created_by = :user_id)";
            $bindings['user_id'] = $userId;
        }

        $query .= " ORDER BY t.due_date ASC, t.due_time ASC";

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get upcoming tasks that need reminders
     */
    public function getTasksNeedingReminders()
    {
        $query = "
            SELECT t.*,
                   u.email,
                   u.first_name,
                   u.last_name
            FROM {$this->table} t
            JOIN users u ON t.assigned_to = u.id
            WHERE t.reminder_time IS NOT NULL
              AND t.reminder_time <= NOW()
              AND t.reminder_sent = 0
              AND t.status NOT IN ('completed', 'cancelled')
            ORDER BY t.reminder_time ASC
        ";

        return $this->db->fetchAll($query);
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent($taskId)
    {
        return $this->update($taskId, ['reminder_sent' => 1]);
    }

    /**
     * Update task status
     */
    public function updateStatus($taskId, $status, $userId = null, $completionNotes = null)
    {
        $updateData = [
            'status' => $status,
            'updated_by' => $userId
        ];

        if ($status === self::STATUS_COMPLETED) {
            $updateData['completion_date'] = date('Y-m-d H:i:s');
            if ($completionNotes) {
                $updateData['completion_notes'] = $completionNotes;
            }
        }

        return $this->update($taskId, $updateData);
    }

    /**
     * Get task productivity metrics
     */
    public function getProductivityMetrics($userId = null, $dateFrom = null, $dateTo = null)
    {
        $userCondition = $userId ? "AND assigned_to = $userId" : "";
        $dateCondition = "";
        
        $bindings = [];
        
        if ($dateFrom) {
            $dateCondition .= " AND DATE(completion_date) >= :date_from";
            $bindings['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $dateCondition .= " AND DATE(completion_date) <= :date_to";
            $bindings['date_to'] = $dateTo;
        }

        $query = "
            SELECT 
                COUNT(*) as completed_tasks,
                AVG(actual_hours) as avg_time_per_task,
                SUM(actual_hours) as total_hours,
                AVG(DATEDIFF(completion_date, created_at)) as avg_completion_days,
                COUNT(CASE WHEN completion_date <= due_date THEN 1 END) as on_time_completions,
                COUNT(CASE WHEN completion_date > due_date THEN 1 END) as late_completions,
                AVG(CASE WHEN estimated_hours > 0 AND actual_hours > 0 
                    THEN (actual_hours / estimated_hours) * 100 
                    ELSE NULL END) as time_estimation_accuracy
            FROM {$this->table} 
            WHERE status = 'completed' 
              AND completion_date IS NOT NULL
              $userCondition 
              $dateCondition
        ";

        $result = $this->db->fetch($query, $bindings);
        
        // Calculate on-time completion rate
        if ($result['completed_tasks'] > 0) {
            $result['on_time_rate'] = round(
                ($result['on_time_completions'] / $result['completed_tasks']) * 100, 
                1
            );
        } else {
            $result['on_time_rate'] = 0;
        }

        return $result;
    }

    /**
     * Get tasks by category for reporting
     */
    public function getTasksByCategory($userId = null)
    {
        $userCondition = $userId ? "AND (assigned_to = $userId OR created_by = $userId)" : "";
        
        $query = "
            SELECT 
                category,
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                AVG(CASE WHEN status = 'completed' AND actual_hours > 0 THEN actual_hours ELSE NULL END) as avg_completion_time
            FROM {$this->table} 
            WHERE 1=1 $userCondition
            GROUP BY category
            ORDER BY total_count DESC
        ";

        return $this->db->fetchAll($query);
    }

    /**
     * Create task from template
     */
    public function createFromTemplate($templateData, $relatedEntityType = null, $relatedEntityId = null)
    {
        $taskData = [
            'title' => $templateData['title'],
            'description' => $templateData['description'],
            'priority' => $templateData['priority'] ?? self::PRIORITY_NORMAL,
            'category' => $templateData['category'] ?? self::CATEGORY_OTHER,
            'estimated_hours' => $templateData['estimated_hours'] ?? null,
            'status' => self::STATUS_PENDING,
            'assigned_to' => $templateData['assigned_to'],
            'created_by' => $templateData['created_by'],
            'related_entity_type' => $relatedEntityType,
            'related_entity_id' => $relatedEntityId
        ];

        // Set due date based on template offset
        if (!empty($templateData['due_days_offset'])) {
            $dueDate = date('Y-m-d', strtotime("+{$templateData['due_days_offset']} days"));
            $taskData['due_date'] = $dueDate;
        }

        return $this->create($taskData);
    }

    /**
     * Get tasks timeline for entity
     */
    public function getEntityTimeline($entityType, $entityId)
    {
        $query = "
            SELECT t.*,
                   assigned_user.first_name as assigned_first_name,
                   assigned_user.last_name as assigned_last_name,
                   creator.first_name as creator_first_name,
                   creator.last_name as creator_last_name
            FROM {$this->table} t
            LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
            LEFT JOIN users creator ON t.created_by = creator.id
            WHERE t.related_entity_type = :entity_type 
              AND t.related_entity_id = :entity_id
            ORDER BY t.created_at DESC
        ";

        return $this->db->fetchAll($query, [
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);
    }

    /**
     * Search tasks with advanced filters
     */
    public function search($searchTerm, $filters = [])
    {
        $query = "
            SELECT t.*,
                   assigned_user.first_name as assigned_first_name,
                   assigned_user.last_name as assigned_last_name,
                   CASE 
                       WHEN t.related_entity_type = 'contact' THEN CONCAT(c.first_name, ' ', c.last_name)
                       WHEN t.related_entity_type = 'deal' THEN d.name
                       WHEN t.related_entity_type = 'organization' THEN o.name
                       ELSE NULL
                   END as related_entity_name
            FROM {$this->table} t
            LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
            LEFT JOIN contacts c ON (t.related_entity_type = 'contact' AND t.related_entity_id = c.id)
            LEFT JOIN deals d ON (t.related_entity_type = 'deal' AND t.related_entity_id = d.id)
            LEFT JOIN organizations o ON (t.related_entity_type = 'organization' AND t.related_entity_id = o.id)
            WHERE (
                t.title LIKE :search_term 
                OR t.description LIKE :search_term
                OR t.tags LIKE :search_term
                OR CONCAT(assigned_user.first_name, ' ', assigned_user.last_name) LIKE :search_term
            )
        ";

        $bindings = ['search_term' => "%$searchTerm%"];

        // Apply additional filters
        if (!empty($filters['status'])) {
            $query .= " AND t.status = :status";
            $bindings['status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $query .= " AND t.priority = :priority";
            $bindings['priority'] = $filters['priority'];
        }

        if (!empty($filters['category'])) {
            $query .= " AND t.category = :category";
            $bindings['category'] = $filters['category'];
        }

        $query .= " ORDER BY t.due_date ASC LIMIT 50";

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get priority label with styling class
     */
    public function getPriorityLabel($priority)
    {
        $labels = [
            self::PRIORITY_LOW => ['label' => 'Low', 'class' => 'priority-low'],
            self::PRIORITY_NORMAL => ['label' => 'Normal', 'class' => 'priority-normal'],
            self::PRIORITY_HIGH => ['label' => 'High', 'class' => 'priority-high'],
            self::PRIORITY_URGENT => ['label' => 'Urgent', 'class' => 'priority-urgent']
        ];

        return $labels[$priority] ?? ['label' => 'Unknown', 'class' => 'priority-normal'];
    }

    /**
     * Get status label with styling class
     */
    public function getStatusLabel($status)
    {
        $labels = [
            self::STATUS_PENDING => ['label' => 'Pending', 'class' => 'status-pending'],
            self::STATUS_IN_PROGRESS => ['label' => 'In Progress', 'class' => 'status-in-progress'],
            self::STATUS_COMPLETED => ['label' => 'Completed', 'class' => 'status-completed'],
            self::STATUS_CANCELLED => ['label' => 'Cancelled', 'class' => 'status-cancelled'],
            self::STATUS_WAITING => ['label' => 'Waiting', 'class' => 'status-waiting']
        ];

        return $labels[$status] ?? ['label' => 'Unknown', 'class' => 'status-pending'];
    }

    /**
     * Get category label with icon
     */
    public function getCategoryLabel($category)
    {
        $labels = [
            self::CATEGORY_CALL => ['label' => 'Call', 'icon' => 'fas fa-phone'],
            self::CATEGORY_EMAIL => ['label' => 'Email', 'icon' => 'fas fa-envelope'],
            self::CATEGORY_MEETING => ['label' => 'Meeting', 'icon' => 'fas fa-users'],
            self::CATEGORY_FOLLOW_UP => ['label' => 'Follow Up', 'icon' => 'fas fa-redo'],
            self::CATEGORY_DEMO => ['label' => 'Demo', 'icon' => 'fas fa-presentation'],
            self::CATEGORY_PROPOSAL => ['label' => 'Proposal', 'icon' => 'fas fa-file-contract'],
            self::CATEGORY_OTHER => ['label' => 'Other', 'icon' => 'fas fa-tasks']
        ];

        return $labels[$category] ?? ['label' => 'Other', 'icon' => 'fas fa-tasks'];
    }

    /**
     * Validate task data
     */
    public function validate($data, $isUpdate = false)
    {
        $errors = [];

        // Required fields
        if (!$isUpdate || isset($data['title'])) {
            if (empty($data['title'])) {
                $errors['title'] = 'Title is required';
            } elseif (strlen($data['title']) > 200) {
                $errors['title'] = 'Title cannot exceed 200 characters';
            }
        }

        // Priority validation
        if (isset($data['priority'])) {
            $validPriorities = [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT];
            if (!in_array($data['priority'], $validPriorities)) {
                $errors['priority'] = 'Invalid priority level';
            }
        }

        // Status validation
        if (isset($data['status'])) {
            $validStatuses = [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_WAITING];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Invalid status';
            }
        }

        // Category validation
        if (isset($data['category'])) {
            $validCategories = [self::CATEGORY_CALL, self::CATEGORY_EMAIL, self::CATEGORY_MEETING, self::CATEGORY_FOLLOW_UP, self::CATEGORY_DEMO, self::CATEGORY_PROPOSAL, self::CATEGORY_OTHER];
            if (!in_array($data['category'], $validCategories)) {
                $errors['category'] = 'Invalid category';
            }
        }

        // Date validation
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            if (!DateTime::createFromFormat('Y-m-d', $data['due_date'])) {
                $errors['due_date'] = 'Invalid due date format';
            }
        }

        // Time validation
        if (isset($data['due_time']) && !empty($data['due_time'])) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['due_time'])) {
                $errors['due_time'] = 'Invalid time format (use HH:MM)';
            }
        }

        // Hours validation
        if (isset($data['estimated_hours']) && !empty($data['estimated_hours'])) {
            if (!is_numeric($data['estimated_hours']) || $data['estimated_hours'] < 0) {
                $errors['estimated_hours'] = 'Estimated hours must be a positive number';
            }
        }

        if (isset($data['actual_hours']) && !empty($data['actual_hours'])) {
            if (!is_numeric($data['actual_hours']) || $data['actual_hours'] < 0) {
                $errors['actual_hours'] = 'Actual hours must be a positive number';
            }
        }

        // User validation
        if (isset($data['assigned_to']) && !empty($data['assigned_to'])) {
            $user = new User();
            if (!$user->find($data['assigned_to'])) {
                $errors['assigned_to'] = 'Invalid assigned user';
            }
        }

        return $errors;
    }
}
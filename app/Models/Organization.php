<?php

namespace App\Models;

use PDO;

class Organization extends BaseModel
{
    protected $table = 'organizations';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'name',
        'industry',
        'type',
        'size',
        'website',
        'description',
        'phone',
        'email',
        'fax',
        'linkedin_url',
        'twitter_handle',
        'facebook_url',
        'annual_revenue',
        'employee_count',
        'founded_year',
        'ownership_type',
        'status',
        'rating',
        'tags',
        'parent_organization_id',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address_line_1',
        'shipping_address_line_2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'tax_id',
        'currency',
        'payment_terms',
        'credit_limit',
        'assigned_user_id',
        'logo_url',
        'notes',
        'custom_fields',
        'created_by',
        'updated_by'
    ];

    const TYPE_PROSPECT = 'prospect';
    const TYPE_CUSTOMER = 'customer';
    const TYPE_PARTNER = 'partner';
    const TYPE_VENDOR = 'vendor';
    const TYPE_COMPETITOR = 'competitor';
    const TYPE_OTHER = 'other';

    const SIZE_STARTUP = 'startup';
    const SIZE_SMALL = 'small';
    const SIZE_MEDIUM = 'medium';
    const SIZE_LARGE = 'large';
    const SIZE_ENTERPRISE = 'enterprise';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PROSPECT = 'prospect';
    const STATUS_CUSTOMER = 'customer';
    const STATUS_FORMER_CUSTOMER = 'former_customer';

    const OWNERSHIP_PUBLIC = 'public';
    const OWNERSHIP_PRIVATE = 'private';
    const OWNERSHIP_GOVERNMENT = 'government';
    const OWNERSHIP_NON_PROFIT = 'non_profit';
    const OWNERSHIP_PARTNERSHIP = 'partnership';

    /**
     * Get all organizations with filtering and pagination
     */
    public function findAll($params = [])
    {
        $query = "
            SELECT o.*,
                   assigned_user.first_name as assigned_first_name,
                   assigned_user.last_name as assigned_last_name,
                   assigned_user.email as assigned_email,
                   parent.name as parent_organization_name,
                   (SELECT COUNT(*) FROM contacts WHERE organization_id = o.id) as contact_count,
                   (SELECT COUNT(*) FROM deals WHERE organization_id = o.id) as deal_count,
                   (SELECT COUNT(*) FROM organizations child WHERE child.parent_organization_id = o.id) as child_count,
                   (SELECT SUM(amount) FROM deals WHERE organization_id = o.id AND status IN ('won', 'closed_won')) as total_revenue,
                   (SELECT COUNT(*) FROM deals WHERE organization_id = o.id AND status IN ('won', 'closed_won')) as won_deals,
                   (SELECT COUNT(*) FROM tasks WHERE related_entity_type = 'organization' AND related_entity_id = o.id) as task_count
            FROM {$this->table} o
            LEFT JOIN users assigned_user ON o.assigned_user_id = assigned_user.id
            LEFT JOIN {$this->table} parent ON o.parent_organization_id = parent.id
            WHERE 1=1
        ";

        $bindings = [];
        
        // Filter by status
        if (!empty($params['status'])) {
            if (is_array($params['status'])) {
                $placeholders = ':status_' . implode(', :status_', array_keys($params['status']));
                $query .= " AND o.status IN ($placeholders)";
                foreach ($params['status'] as $key => $status) {
                    $bindings["status_$key"] = $status;
                }
            } else {
                $query .= " AND o.status = :status";
                $bindings['status'] = $params['status'];
            }
        }

        // Filter by type
        if (!empty($params['type'])) {
            $query .= " AND o.type = :type";
            $bindings['type'] = $params['type'];
        }

        // Filter by industry
        if (!empty($params['industry'])) {
            $query .= " AND o.industry = :industry";
            $bindings['industry'] = $params['industry'];
        }

        // Filter by size
        if (!empty($params['size'])) {
            $query .= " AND o.size = :size";
            $bindings['size'] = $params['size'];
        }

        // Filter by assigned user
        if (!empty($params['assigned_user_id'])) {
            $query .= " AND o.assigned_user_id = :assigned_user_id";
            $bindings['assigned_user_id'] = $params['assigned_user_id'];
        }

        // Filter by parent organization
        if (!empty($params['parent_organization_id'])) {
            $query .= " AND o.parent_organization_id = :parent_organization_id";
            $bindings['parent_organization_id'] = $params['parent_organization_id'];
        }

        // Filter by revenue range
        if (!empty($params['min_revenue'])) {
            $query .= " AND o.annual_revenue >= :min_revenue";
            $bindings['min_revenue'] = $params['min_revenue'];
        }

        if (!empty($params['max_revenue'])) {
            $query .= " AND o.annual_revenue <= :max_revenue";
            $bindings['max_revenue'] = $params['max_revenue'];
        }

        // Filter by employee count range
        if (!empty($params['min_employees'])) {
            $query .= " AND o.employee_count >= :min_employees";
            $bindings['min_employees'] = $params['min_employees'];
        }

        if (!empty($params['max_employees'])) {
            $query .= " AND o.employee_count <= :max_employees";
            $bindings['max_employees'] = $params['max_employees'];
        }

        // Search functionality
        if (!empty($params['search'])) {
            $query .= " AND (
                o.name LIKE :search 
                OR o.website LIKE :search
                OR o.email LIKE :search
                OR o.phone LIKE :search
                OR o.industry LIKE :search
                OR o.description LIKE :search
                OR o.tags LIKE :search
            )";
            $bindings['search'] = "%{$params['search']}%";
        }

        // Filter by location (city, state, country)
        if (!empty($params['location'])) {
            $query .= " AND (
                o.billing_city LIKE :location 
                OR o.billing_state LIKE :location
                OR o.billing_country LIKE :location
                OR o.shipping_city LIKE :location
                OR o.shipping_state LIKE :location
                OR o.shipping_country LIKE :location
            )";
            $bindings['location'] = "%{$params['location']}%";
        }

        // Filter high-value organizations
        if (!empty($params['high_value'])) {
            $minRevenue = $params['high_value_threshold'] ?? 1000000;
            $query .= " AND (o.annual_revenue >= :high_value_threshold OR 
                            (SELECT SUM(amount) FROM deals WHERE organization_id = o.id AND status IN ('won', 'closed_won')) >= :high_value_threshold)";
            $bindings['high_value_threshold'] = $minRevenue;
        }

        // Sorting
        $orderBy = $params['order_by'] ?? 'name';
        $orderDirection = strtoupper($params['order_direction'] ?? 'ASC');
        
        $allowedOrderBy = ['name', 'type', 'industry', 'size', 'annual_revenue', 'employee_count', 'status', 'created_at', 'contact_count', 'deal_count'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'name';
        }

        if ($orderBy === 'contact_count' || $orderBy === 'deal_count') {
            $query .= " ORDER BY $orderBy $orderDirection";
        } else {
            $query .= " ORDER BY o.$orderBy $orderDirection";
        }

        // Pagination
        if (!empty($params['limit'])) {
            $offset = $params['offset'] ?? 0;
            $query .= " LIMIT $offset, {$params['limit']}";
        }

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get organization statistics for dashboard
     */
    public function getStatistics($userId = null)
    {
        $userCondition = $userId ? "AND assigned_user_id = $userId" : "";
        
        $query = "
            SELECT 
                COUNT(*) as total_organizations,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_organizations,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_organizations,
                SUM(CASE WHEN status = 'prospect' THEN 1 ELSE 0 END) as prospect_organizations,
                SUM(CASE WHEN status = 'customer' THEN 1 ELSE 0 END) as customer_organizations,
                SUM(CASE WHEN type = 'customer' THEN 1 ELSE 0 END) as customers,
                SUM(CASE WHEN type = 'prospect' THEN 1 ELSE 0 END) as prospects,
                SUM(CASE WHEN type = 'partner' THEN 1 ELSE 0 END) as partners,
                SUM(CASE WHEN type = 'vendor' THEN 1 ELSE 0 END) as vendors,
                AVG(annual_revenue) as avg_revenue,
                SUM(annual_revenue) as total_revenue,
                AVG(employee_count) as avg_employees,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_this_month,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_this_week
            FROM {$this->table} 
            WHERE 1=1 $userCondition
        ";

        $result = $this->db->fetch($query);
        
        // Get industry breakdown
        $industryQuery = "
            SELECT industry, COUNT(*) as count 
            FROM {$this->table} 
            WHERE industry IS NOT NULL AND industry != '' $userCondition
            GROUP BY industry 
            ORDER BY count DESC 
            LIMIT 10
        ";
        $result['industry_breakdown'] = $this->db->fetchAll($industryQuery);
        
        // Get size breakdown
        $sizeQuery = "
            SELECT size, COUNT(*) as count 
            FROM {$this->table} 
            WHERE size IS NOT NULL AND size != '' $userCondition
            GROUP BY size 
            ORDER BY count DESC
        ";
        $result['size_breakdown'] = $this->db->fetchAll($sizeQuery);

        // Calculate conversion rates
        if ($result['prospects'] > 0) {
            $result['prospect_to_customer_rate'] = round(($result['customers'] / $result['prospects']) * 100, 1);
        } else {
            $result['prospect_to_customer_rate'] = 0;
        }

        return $result;
    }

    /**
     * Get organization hierarchy (parent-child relationships)
     */
    public function getOrganizationHierarchy($organizationId)
    {
        // Get parent chain
        $parents = [];
        $currentId = $organizationId;
        
        while ($currentId) {
            $query = "
                SELECT o.*, parent.name as parent_name, parent.id as parent_id
                FROM {$this->table} o
                LEFT JOIN {$this->table} parent ON o.parent_organization_id = parent.id
                WHERE o.id = :id
            ";
            
            $org = $this->db->fetch($query, ['id' => $currentId]);
            if (!$org) break;
            
            if ($org['id'] != $organizationId) {
                $parents[] = $org;
            }
            
            $currentId = $org['parent_organization_id'];
        }
        
        // Get children
        $query = "
            SELECT o.*,
                   (SELECT COUNT(*) FROM contacts WHERE organization_id = o.id) as contact_count,
                   (SELECT COUNT(*) FROM deals WHERE organization_id = o.id) as deal_count
            FROM {$this->table} o
            WHERE o.parent_organization_id = :organization_id
            ORDER BY o.name ASC
        ";
        
        $children = $this->db->fetchAll($query, ['organization_id' => $organizationId]);
        
        return [
            'parents' => array_reverse($parents), // Root to parent order
            'children' => $children
        ];
    }

    /**
     * Get organization's contacts
     */
    public function getOrganizationContacts($organizationId, $params = [])
    {
        $query = "
            SELECT c.*,
                   (SELECT COUNT(*) FROM deals WHERE contact_id = c.id) as deal_count,
                   (SELECT COUNT(*) FROM tasks WHERE related_entity_type = 'contact' AND related_entity_id = c.id) as task_count
            FROM contacts c
            WHERE c.organization_id = :organization_id
        ";

        $bindings = ['organization_id' => $organizationId];

        // Filter by contact status
        if (!empty($params['status'])) {
            $query .= " AND c.status = :status";
            $bindings['status'] = $params['status'];
        }

        // Search contacts
        if (!empty($params['search'])) {
            $query .= " AND (c.first_name LIKE :search OR c.last_name LIKE :search OR c.email LIKE :search)";
            $bindings['search'] = "%{$params['search']}%";
        }

        $query .= " ORDER BY c.is_primary DESC, c.last_name ASC, c.first_name ASC";

        if (!empty($params['limit'])) {
            $offset = $params['offset'] ?? 0;
            $query .= " LIMIT $offset, {$params['limit']}";
        }

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get organization's deals
     */
    public function getOrganizationDeals($organizationId, $params = [])
    {
        $query = "
            SELECT d.*,
                   ps.name as stage_name,
                   ps.probability,
                   c.first_name as contact_first_name,
                   c.last_name as contact_last_name,
                   u.first_name as owner_first_name,
                   u.last_name as owner_last_name
            FROM deals d
            LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id
            LEFT JOIN contacts c ON d.contact_id = c.id
            LEFT JOIN users u ON d.owner_id = u.id
            WHERE d.organization_id = :organization_id
        ";

        $bindings = ['organization_id' => $organizationId];

        // Filter by deal status
        if (!empty($params['status'])) {
            $query .= " AND d.status = :status";
            $bindings['status'] = $params['status'];
        }

        // Filter by stage
        if (!empty($params['stage_id'])) {
            $query .= " AND d.stage_id = :stage_id";
            $bindings['stage_id'] = $params['stage_id'];
        }

        $query .= " ORDER BY d.created_at DESC";

        if (!empty($params['limit'])) {
            $offset = $params['offset'] ?? 0;
            $query .= " LIMIT $offset, {$params['limit']}";
        }

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get organization's activities
     */
    public function getOrganizationActivities($organizationId, $limit = 50)
    {
        $query = "
            SELECT a.*,
                   u.first_name as user_first_name,
                   u.last_name as user_last_name
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE (a.entity_type = 'organization' AND a.entity_id = :organization_id)
               OR (a.entity_type = 'contact' AND a.entity_id IN 
                   (SELECT id FROM contacts WHERE organization_id = :organization_id))
               OR (a.entity_type = 'deal' AND a.entity_id IN 
                   (SELECT id FROM deals WHERE organization_id = :organization_id))
               OR (a.entity_type = 'task' AND a.entity_id IN 
                   (SELECT id FROM tasks WHERE related_entity_type = 'organization' AND related_entity_id = :organization_id))
            ORDER BY a.created_at DESC
            LIMIT :limit
        ";

        return $this->db->fetchAll($query, [
            'organization_id' => $organizationId,
            'limit' => $limit
        ]);
    }

    /**
     * Get organization summary metrics
     */
    public function getOrganizationSummary($organizationId)
    {
        $query = "
            SELECT 
                (SELECT COUNT(*) FROM contacts WHERE organization_id = :org_id) as total_contacts,
                (SELECT COUNT(*) FROM deals WHERE organization_id = :org_id) as total_deals,
                (SELECT COUNT(*) FROM deals WHERE organization_id = :org_id AND status = 'open') as open_deals,
                (SELECT COUNT(*) FROM deals WHERE organization_id = :org_id AND status IN ('won', 'closed_won')) as won_deals,
                (SELECT SUM(amount) FROM deals WHERE organization_id = :org_id AND status IN ('won', 'closed_won')) as total_revenue,
                (SELECT SUM(amount) FROM deals WHERE organization_id = :org_id AND status = 'open') as pipeline_value,
                (SELECT COUNT(*) FROM tasks WHERE related_entity_type = 'organization' AND related_entity_id = :org_id) as total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE related_entity_type = 'organization' AND related_entity_id = :org_id AND status = 'pending') as pending_tasks,
                (SELECT COUNT(*) FROM organizations WHERE parent_organization_id = :org_id) as child_organizations
        ";

        return $this->db->fetch($query, ['org_id' => $organizationId]);
    }

    /**
     * Search organizations with advanced filters
     */
    public function search($searchTerm, $filters = [])
    {
        $query = "
            SELECT o.*,
                   assigned_user.first_name as assigned_first_name,
                   assigned_user.last_name as assigned_last_name,
                   (SELECT COUNT(*) FROM contacts WHERE organization_id = o.id) as contact_count,
                   (SELECT COUNT(*) FROM deals WHERE organization_id = o.id) as deal_count
            FROM {$this->table} o
            LEFT JOIN users assigned_user ON o.assigned_user_id = assigned_user.id
            WHERE (
                o.name LIKE :search_term 
                OR o.website LIKE :search_term
                OR o.email LIKE :search_term
                OR o.phone LIKE :search_term
                OR o.industry LIKE :search_term
                OR o.description LIKE :search_term
            )
        ";

        $bindings = ['search_term' => "%$searchTerm%"];

        // Apply additional filters
        if (!empty($filters['type'])) {
            $query .= " AND o.type = :type";
            $bindings['type'] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $query .= " AND o.status = :status";
            $bindings['status'] = $filters['status'];
        }

        if (!empty($filters['industry'])) {
            $query .= " AND o.industry = :industry";
            $bindings['industry'] = $filters['industry'];
        }

        if (!empty($filters['size'])) {
            $query .= " AND o.size = :size";
            $bindings['size'] = $filters['size'];
        }

        $query .= " ORDER BY o.name ASC LIMIT 50";

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get duplicate organizations based on name/website
     */
    public function findPotentialDuplicates($name, $website = null, $excludeId = null)
    {
        $query = "
            SELECT *
            FROM {$this->table}
            WHERE (
                LOWER(name) = LOWER(:name)
                " . ($website ? "OR website = :website" : "") . "
            )
        ";

        $bindings = ['name' => $name];
        if ($website) {
            $bindings['website'] = $website;
        }

        if ($excludeId) {
            $query .= " AND id != :exclude_id";
            $bindings['exclude_id'] = $excludeId;
        }

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get organizations by industry for analytics
     */
    public function getOrganizationsByIndustry($userId = null)
    {
        $userCondition = $userId ? "AND assigned_user_id = $userId" : "";
        
        $query = "
            SELECT 
                industry,
                COUNT(*) as organization_count,
                AVG(annual_revenue) as avg_revenue,
                SUM(annual_revenue) as total_revenue,
                AVG(employee_count) as avg_employees,
                COUNT(CASE WHEN type = 'customer' THEN 1 END) as customers,
                COUNT(CASE WHEN type = 'prospect' THEN 1 END) as prospects
            FROM {$this->table} 
            WHERE industry IS NOT NULL AND industry != '' $userCondition
            GROUP BY industry
            ORDER BY organization_count DESC
        ";

        return $this->db->fetchAll($query);
    }

    /**
     * Get top organizations by revenue
     */
    public function getTopOrganizationsByRevenue($limit = 10, $userId = null)
    {
        $userCondition = $userId ? "AND o.assigned_user_id = $userId" : "";
        
        $query = "
            SELECT o.*,
                   (SELECT SUM(amount) FROM deals WHERE organization_id = o.id AND status IN ('won', 'closed_won')) as total_sales
            FROM {$this->table} o
            WHERE o.annual_revenue > 0 $userCondition
            ORDER BY o.annual_revenue DESC
            LIMIT :limit
        ";

        return $this->db->fetchAll($query, ['limit' => $limit]);
    }

    /**
     * Get organization performance metrics
     */
    public function getPerformanceMetrics($organizationId)
    {
        $query = "
            SELECT 
                COUNT(d.id) as total_deals,
                COUNT(CASE WHEN d.status IN ('won', 'closed_won') THEN 1 END) as won_deals,
                COUNT(CASE WHEN d.status = 'lost' THEN 1 END) as lost_deals,
                COUNT(CASE WHEN d.status = 'open' THEN 1 END) as open_deals,
                SUM(CASE WHEN d.status IN ('won', 'closed_won') THEN d.amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN d.status = 'open' THEN d.amount ELSE 0 END) as pipeline_value,
                AVG(CASE WHEN d.status IN ('won', 'closed_won') THEN d.amount ELSE NULL END) as avg_deal_size,
                AVG(CASE WHEN d.status IN ('won', 'closed_won') AND d.created_at IS NOT NULL AND d.close_date IS NOT NULL 
                    THEN DATEDIFF(d.close_date, d.created_at) ELSE NULL END) as avg_sales_cycle_days
            FROM deals d
            WHERE d.organization_id = :organization_id
        ";

        $metrics = $this->db->fetch($query, ['organization_id' => $organizationId]);
        
        // Calculate win rate
        if ($metrics['total_deals'] > 0) {
            $metrics['win_rate'] = round(($metrics['won_deals'] / $metrics['total_deals']) * 100, 1);
        } else {
            $metrics['win_rate'] = 0;
        }

        return $metrics;
    }

    /**
     * Get type label with styling class
     */
    public function getTypeLabel($type)
    {
        $labels = [
            self::TYPE_PROSPECT => ['label' => 'Prospect', 'class' => 'type-prospect'],
            self::TYPE_CUSTOMER => ['label' => 'Customer', 'class' => 'type-customer'],
            self::TYPE_PARTNER => ['label' => 'Partner', 'class' => 'type-partner'],
            self::TYPE_VENDOR => ['label' => 'Vendor', 'class' => 'type-vendor'],
            self::TYPE_COMPETITOR => ['label' => 'Competitor', 'class' => 'type-competitor'],
            self::TYPE_OTHER => ['label' => 'Other', 'class' => 'type-other']
        ];

        return $labels[$type] ?? ['label' => 'Unknown', 'class' => 'type-other'];
    }

    /**
     * Get status label with styling class
     */
    public function getStatusLabel($status)
    {
        $labels = [
            self::STATUS_ACTIVE => ['label' => 'Active', 'class' => 'status-active'],
            self::STATUS_INACTIVE => ['label' => 'Inactive', 'class' => 'status-inactive'],
            self::STATUS_PROSPECT => ['label' => 'Prospect', 'class' => 'status-prospect'],
            self::STATUS_CUSTOMER => ['label' => 'Customer', 'class' => 'status-customer'],
            self::STATUS_FORMER_CUSTOMER => ['label' => 'Former Customer', 'class' => 'status-former-customer']
        ];

        return $labels[$status] ?? ['label' => 'Unknown', 'class' => 'status-active'];
    }

    /**
     * Get size label with styling class
     */
    public function getSizeLabel($size)
    {
        $labels = [
            self::SIZE_STARTUP => ['label' => 'Startup (1-10)', 'class' => 'size-startup'],
            self::SIZE_SMALL => ['label' => 'Small (11-50)', 'class' => 'size-small'],
            self::SIZE_MEDIUM => ['label' => 'Medium (51-200)', 'class' => 'size-medium'],
            self::SIZE_LARGE => ['label' => 'Large (201-1000)', 'class' => 'size-large'],
            self::SIZE_ENTERPRISE => ['label' => 'Enterprise (1000+)', 'class' => 'size-enterprise']
        ];

        return $labels[$size] ?? ['label' => 'Unknown', 'class' => 'size-medium'];
    }

    /**
     * Validate organization data
     */
    public function validate($data, $isUpdate = false)
    {
        $errors = [];

        // Required fields
        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name'])) {
                $errors['name'] = 'Organization name is required';
            } elseif (strlen($data['name']) > 200) {
                $errors['name'] = 'Organization name cannot exceed 200 characters';
            }
        }

        // Email validation
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
        }

        // Website validation
        if (isset($data['website']) && !empty($data['website'])) {
            if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
                $errors['website'] = 'Invalid website URL format';
            }
        }

        // Type validation
        if (isset($data['type'])) {
            $validTypes = [self::TYPE_PROSPECT, self::TYPE_CUSTOMER, self::TYPE_PARTNER, self::TYPE_VENDOR, self::TYPE_COMPETITOR, self::TYPE_OTHER];
            if (!in_array($data['type'], $validTypes)) {
                $errors['type'] = 'Invalid organization type';
            }
        }

        // Status validation
        if (isset($data['status'])) {
            $validStatuses = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_PROSPECT, self::STATUS_CUSTOMER, self::STATUS_FORMER_CUSTOMER];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Invalid organization status';
            }
        }

        // Size validation
        if (isset($data['size'])) {
            $validSizes = [self::SIZE_STARTUP, self::SIZE_SMALL, self::SIZE_MEDIUM, self::SIZE_LARGE, self::SIZE_ENTERPRISE];
            if (!in_array($data['size'], $validSizes)) {
                $errors['size'] = 'Invalid organization size';
            }
        }

        // Numeric validations
        if (isset($data['annual_revenue']) && !empty($data['annual_revenue'])) {
            if (!is_numeric($data['annual_revenue']) || $data['annual_revenue'] < 0) {
                $errors['annual_revenue'] = 'Annual revenue must be a positive number';
            }
        }

        if (isset($data['employee_count']) && !empty($data['employee_count'])) {
            if (!is_numeric($data['employee_count']) || $data['employee_count'] < 0) {
                $errors['employee_count'] = 'Employee count must be a positive number';
            }
        }

        if (isset($data['founded_year']) && !empty($data['founded_year'])) {
            $currentYear = date('Y');
            if (!is_numeric($data['founded_year']) || $data['founded_year'] < 1800 || $data['founded_year'] > $currentYear) {
                $errors['founded_year'] = "Founded year must be between 1800 and $currentYear";
            }
        }

        // Parent organization validation (prevent circular references)
        if (isset($data['parent_organization_id']) && !empty($data['parent_organization_id'])) {
            $parentOrg = $this->find($data['parent_organization_id']);
            if (!$parentOrg) {
                $errors['parent_organization_id'] = 'Invalid parent organization';
            } elseif (isset($data['id']) && $data['parent_organization_id'] == $data['id']) {
                $errors['parent_organization_id'] = 'Organization cannot be its own parent';
            }
        }

        // Check for duplicates
        if (!$isUpdate || isset($data['name'])) {
            $duplicates = $this->findPotentialDuplicates(
                $data['name'], 
                $data['website'] ?? null, 
                $isUpdate ? $data['id'] ?? null : null
            );
            
            if (!empty($duplicates)) {
                $errors['name'] = 'An organization with this name or website already exists';
            }
        }

        return $errors;
    }
}
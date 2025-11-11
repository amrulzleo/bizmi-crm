<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class Deal
{
    private $db;
    private $table = 'deals';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($userId = null, $limit = null, $offset = 0, $filters = [])
    {
        $sql = "SELECT d.*, 
                       c.first_name as contact_first_name, c.last_name as contact_last_name,
                       o.name as organization_name,
                       u.first_name as owner_first_name, u.last_name as owner_last_name,
                       ps.name as stage_name, ps.probability as stage_probability, ps.sort_order as stage_order
                FROM {$this->table} d
                LEFT JOIN contacts c ON d.contact_id = c.id
                LEFT JOIN organizations o ON d.organization_id = o.id
                LEFT JOIN users u ON d.owner_id = u.id
                LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id
                WHERE d.deleted_at IS NULL";

        $params = [];

        // User-based filtering
        if ($userId) {
            $sql .= " AND d.owner_id = ?";
            $params[] = $userId;
        }

        // Apply filters
        if (!empty($filters['search'])) {
            $sql .= " AND (d.name LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR o.name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['stage_id'])) {
            $sql .= " AND d.stage_id = ?";
            $params[] = $filters['stage_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['amount_min'])) {
            $sql .= " AND d.amount >= ?";
            $params[] = $filters['amount_min'];
        }

        if (!empty($filters['amount_max'])) {
            $sql .= " AND d.amount <= ?";
            $params[] = $filters['amount_max'];
        }

        if (!empty($filters['expected_close_date_from'])) {
            $sql .= " AND d.expected_close_date >= ?";
            $params[] = $filters['expected_close_date_from'];
        }

        if (!empty($filters['expected_close_date_to'])) {
            $sql .= " AND d.expected_close_date <= ?";
            $params[] = $filters['expected_close_date_to'];
        }

        if (!empty($filters['source'])) {
            $sql .= " AND d.source = ?";
            $params[] = $filters['source'];
        }

        // Sorting
        $sortField = $filters['sort_field'] ?? 'd.created_at';
        $sortDirection = $filters['sort_direction'] ?? 'DESC';
        $sql .= " ORDER BY {$sortField} {$sortDirection}";

        // Pagination
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal findAll error: " . $e->getMessage());
            return [];
        }
    }

    public function findById($id)
    {
        $sql = "SELECT d.*, 
                       c.first_name as contact_first_name, c.last_name as contact_last_name,
                       c.email as contact_email, c.phone as contact_phone,
                       o.name as organization_name,
                       u.first_name as owner_first_name, u.last_name as owner_last_name,
                       ps.name as stage_name, ps.probability as stage_probability,
                       creator.first_name as creator_first_name, creator.last_name as creator_last_name
                FROM {$this->table} d
                LEFT JOIN contacts c ON d.contact_id = c.id
                LEFT JOIN organizations o ON d.organization_id = o.id
                LEFT JOIN users u ON d.owner_id = u.id
                LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id
                LEFT JOIN users creator ON d.created_by = creator.id
                WHERE d.id = ? AND d.deleted_at IS NULL";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal findById error: " . $e->getMessage());
            return false;
        }
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    name, amount, currency, stage_id, status, probability,
                    expected_close_date, actual_close_date, contact_id, organization_id,
                    source, description, owner_id, created_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";

        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                $data['amount'] ?? 0,
                $data['currency'] ?? 'USD',
                $data['stage_id'],
                $data['status'] ?? 'open',
                $data['probability'] ?? 0,
                $data['expected_close_date'] ?? null,
                $data['actual_close_date'] ?? null,
                $data['contact_id'] ?? null,
                $data['organization_id'] ?? null,
                $data['source'] ?? '',
                $data['description'] ?? '',
                $data['owner_id'],
                $data['created_by']
            ]);

            if ($result) {
                return $this->db->lastInsertId();
            }

            return false;
        } catch (PDOException $e) {
            error_log("Deal create error: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                    name = ?, amount = ?, currency = ?, stage_id = ?, status = ?,
                    probability = ?, expected_close_date = ?, actual_close_date = ?,
                    contact_id = ?, organization_id = ?, source = ?, description = ?,
                    updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['amount'] ?? 0,
                $data['currency'] ?? 'USD',
                $data['stage_id'],
                $data['status'] ?? 'open',
                $data['probability'] ?? 0,
                $data['expected_close_date'] ?? null,
                $data['actual_close_date'] ?? null,
                $data['contact_id'] ?? null,
                $data['organization_id'] ?? null,
                $data['source'] ?? '',
                $data['description'] ?? '',
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Deal update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Deal delete error: " . $e->getMessage());
            return false;
        }
    }

    public function updateStage($id, $stageId, $userId)
    {
        // Get stage information
        $stage = $this->getPipelineStage($stageId);
        if (!$stage) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET 
                    stage_id = ?, 
                    probability = ?,
                    updated_at = NOW()";

        $params = [$stageId, $stage['probability']];

        // If moving to won/lost stage, update status and close date
        if (in_array(strtolower($stage['name']), ['won', 'closed won'])) {
            $sql .= ", status = 'won', actual_close_date = NOW()";
        } elseif (in_array(strtolower($stage['name']), ['lost', 'closed lost'])) {
            $sql .= ", status = 'lost', actual_close_date = NOW()";
        }

        $sql .= " WHERE id = ? AND deleted_at IS NULL";
        $params[] = $id;

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Deal updateStage error: " . $e->getMessage());
            return false;
        }
    }

    public function getPipelineStages()
    {
        $sql = "SELECT * FROM pipeline_stages WHERE deleted_at IS NULL ORDER BY sort_order";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal getPipelineStages error: " . $e->getMessage());
            return [];
        }
    }

    public function getPipelineStage($id)
    {
        $sql = "SELECT * FROM pipeline_stages WHERE id = ? AND deleted_at IS NULL";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal getPipelineStage error: " . $e->getMessage());
            return false;
        }
    }

    public function getPipelineStagesByUser($userId)
    {
        $sql = "SELECT ps.*, 
                       COUNT(d.id) as count,
                       COALESCE(SUM(d.amount), 0) as value
                FROM pipeline_stages ps
                LEFT JOIN deals d ON ps.id = d.stage_id 
                    AND d.deleted_at IS NULL 
                    AND d.owner_id = ?
                WHERE ps.deleted_at IS NULL
                GROUP BY ps.id
                ORDER BY ps.sort_order";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal getPipelineStagesByUser error: " . $e->getMessage());
            return [];
        }
    }

    public function countActiveByUser($userId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE owner_id = ? AND status = 'open' AND deleted_at IS NULL";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Deal countActiveByUser error: " . $e->getMessage());
            return 0;
        }
    }

    public function countActiveByUserInPeriod($userId, $period)
    {
        $dateCondition = $this->getDateCondition($period);
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE owner_id = ? AND status = 'open' AND deleted_at IS NULL 
                AND created_at {$dateCondition}";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Deal countActiveByUserInPeriod error: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalRevenueByUser($userId)
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) FROM {$this->table} 
                WHERE owner_id = ? AND status = 'won' AND deleted_at IS NULL";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Deal getTotalRevenueByUser error: " . $e->getMessage());
            return 0;
        }
    }

    public function getRevenueByUserInPeriod($userId, $period)
    {
        $dateCondition = $this->getDateCondition($period, 'actual_close_date');
        $sql = "SELECT COALESCE(SUM(amount), 0) FROM {$this->table} 
                WHERE owner_id = ? AND status = 'won' AND deleted_at IS NULL 
                AND actual_close_date {$dateCondition}";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Deal getRevenueByUserInPeriod error: " . $e->getMessage());
            return 0;
        }
    }

    public function search($query, $userId = null, $limit = 50)
    {
        $sql = "SELECT d.*, 
                       c.first_name as contact_first_name, c.last_name as contact_last_name,
                       o.name as organization_name
                FROM {$this->table} d
                LEFT JOIN contacts c ON d.contact_id = c.id
                LEFT JOIN organizations o ON d.organization_id = o.id
                WHERE d.deleted_at IS NULL 
                AND (d.name LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR o.name LIKE ?)";

        $params = ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%"];

        if ($userId) {
            $sql .= " AND d.owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY d.amount DESC LIMIT ?";
        $params[] = $limit;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal search error: " . $e->getMessage());
            return [];
        }
    }

    public function getUpcomingDeadlines($userId = null, $days = 7)
    {
        $sql = "SELECT d.*, 
                       c.first_name as contact_first_name, c.last_name as contact_last_name,
                       o.name as organization_name,
                       ps.name as stage_name
                FROM {$this->table} d
                LEFT JOIN contacts c ON d.contact_id = c.id
                LEFT JOIN organizations o ON d.organization_id = o.id
                LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id
                WHERE d.deleted_at IS NULL 
                AND d.status = 'open'
                AND d.expected_close_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";

        $params = [$days];

        if ($userId) {
            $sql .= " AND d.owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY d.expected_close_date ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal getUpcomingDeadlines error: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentByUser($userId, $limit = 10)
    {
        $sql = "SELECT d.*, 
                       c.first_name as contact_first_name, c.last_name as contact_last_name,
                       o.name as organization_name,
                       ps.name as stage_name
                FROM {$this->table} d
                LEFT JOIN contacts c ON d.contact_id = c.id
                LEFT JOIN organizations o ON d.organization_id = o.id
                LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id
                WHERE d.owner_id = ? AND d.deleted_at IS NULL
                ORDER BY d.updated_at DESC
                LIMIT ?";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal getRecentByUser error: " . $e->getMessage());
            return [];
        }
    }

    public function getStatsByStage($userId = null)
    {
        $sql = "SELECT ps.name as stage_name, ps.id as stage_id,
                       COUNT(d.id) as count,
                       COALESCE(SUM(d.amount), 0) as total_value,
                       COALESCE(AVG(d.amount), 0) as avg_value
                FROM pipeline_stages ps
                LEFT JOIN deals d ON ps.id = d.stage_id 
                    AND d.deleted_at IS NULL 
                    AND d.status = 'open'";

        $params = [];
        if ($userId) {
            $sql .= " AND d.owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " WHERE ps.deleted_at IS NULL
                  GROUP BY ps.id, ps.name
                  ORDER BY ps.sort_order";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal getStatsByStage error: " . $e->getMessage());
            return [];
        }
    }

    public function getStatsBySource($userId = null)
    {
        $sql = "SELECT source, 
                       COUNT(*) as count,
                       COALESCE(SUM(amount), 0) as total_value,
                       COALESCE(AVG(amount), 0) as avg_value
                FROM {$this->table} 
                WHERE deleted_at IS NULL AND source IS NOT NULL AND source != ''";

        $params = [];
        if ($userId) {
            $sql .= " AND owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " GROUP BY source ORDER BY total_value DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal getStatsBySource error: " . $e->getMessage());
            return [];
        }
    }

    public function getForecast($userId = null, $months = 3)
    {
        $sql = "SELECT 
                    DATE_FORMAT(expected_close_date, '%Y-%m') as month,
                    COUNT(*) as deal_count,
                    SUM(amount) as total_amount,
                    SUM(amount * probability / 100) as weighted_amount
                FROM {$this->table}
                WHERE deleted_at IS NULL 
                AND status = 'open'
                AND expected_close_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? MONTH)";

        $params = [$months];

        if ($userId) {
            $sql .= " AND owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " GROUP BY DATE_FORMAT(expected_close_date, '%Y-%m')
                  ORDER BY month";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Deal getForecast error: " . $e->getMessage());
            return [];
        }
    }

    private function getDateCondition($period, $dateField = 'created_at')
    {
        switch ($period) {
            case 'current_month':
                return ">= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            case 'last_month':
                return ">= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') 
                        AND < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            case 'current_year':
                return ">= DATE_FORMAT(CURDATE(), '%Y-01-01')";
            case 'last_year':
                return ">= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 YEAR), '%Y-01-01') 
                        AND < DATE_FORMAT(CURDATE(), '%Y-01-01')";
            default:
                return ">= CURDATE() - INTERVAL 30 DAY";
        }
    }
}
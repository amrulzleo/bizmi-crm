<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class Contact
{
    private $db;
    private $table = 'contacts';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($userId = null, $limit = null, $offset = 0, $filters = [])
    {
        $sql = "SELECT c.*, o.name as organization_name, 
                       u.first_name as owner_first_name, u.last_name as owner_last_name,
                       COUNT(d.id) as deals_count,
                       SUM(CASE WHEN d.stage != 'lost' AND d.stage != 'won' THEN d.amount ELSE 0 END) as active_deals_value
                FROM {$this->table} c
                LEFT JOIN organizations o ON c.organization_id = o.id
                LEFT JOIN users u ON c.owner_id = u.id
                LEFT JOIN deals d ON c.id = d.contact_id
                WHERE c.deleted_at IS NULL";

        $params = [];

        // User-based filtering
        if ($userId) {
            $sql .= " AND c.owner_id = ?";
            $params[] = $userId;
        }

        // Apply filters
        if (!empty($filters['search'])) {
            $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['organization_id'])) {
            $sql .= " AND c.organization_id = ?";
            $params[] = $filters['organization_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['lead_source'])) {
            $sql .= " AND c.lead_source = ?";
            $params[] = $filters['lead_source'];
        }

        if (!empty($filters['tags'])) {
            $sql .= " AND c.id IN (
                SELECT contact_id FROM contact_tags ct 
                JOIN tags t ON ct.tag_id = t.id 
                WHERE t.name IN (" . str_repeat('?,', count($filters['tags']) - 1) . "?)
            )";
            $params = array_merge($params, $filters['tags']);
        }

        $sql .= " GROUP BY c.id";

        // Sorting
        $sortField = $filters['sort_field'] ?? 'c.created_at';
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
            error_log("Contact findAll error: " . $e->getMessage());
            return [];
        }
    }

    public function findById($id)
    {
        $sql = "SELECT c.*, o.name as organization_name, 
                       u.first_name as owner_first_name, u.last_name as owner_last_name,
                       creator.first_name as creator_first_name, creator.last_name as creator_last_name
                FROM {$this->table} c
                LEFT JOIN organizations o ON c.organization_id = o.id
                LEFT JOIN users u ON c.owner_id = u.id
                LEFT JOIN users creator ON c.created_by = creator.id
                WHERE c.id = ? AND c.deleted_at IS NULL";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Contact findById error: " . $e->getMessage());
            return false;
        }
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    first_name, last_name, email, phone, mobile, title, 
                    organization_id, department, address_street, address_city, 
                    address_state, address_postal_code, address_country,
                    website, linkedin_url, twitter_handle, status, lead_source,
                    description, owner_id, created_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";

        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['first_name'] ?? '',
                $data['last_name'] ?? '',
                $data['email'] ?? '',
                $data['phone'] ?? '',
                $data['mobile'] ?? '',
                $data['title'] ?? '',
                $data['organization_id'] ?? null,
                $data['department'] ?? '',
                $data['address_street'] ?? '',
                $data['address_city'] ?? '',
                $data['address_state'] ?? '',
                $data['address_postal_code'] ?? '',
                $data['address_country'] ?? '',
                $data['website'] ?? '',
                $data['linkedin_url'] ?? '',
                $data['twitter_handle'] ?? '',
                $data['status'] ?? 'active',
                $data['lead_source'] ?? '',
                $data['description'] ?? '',
                $data['owner_id'],
                $data['created_by']
            ]);

            if ($result) {
                $contactId = $this->db->lastInsertId();
                
                // Handle tags if provided
                if (!empty($data['tags'])) {
                    $this->updateTags($contactId, $data['tags']);
                }

                return $contactId;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Contact create error: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                    first_name = ?, last_name = ?, email = ?, phone = ?, mobile = ?, 
                    title = ?, organization_id = ?, department = ?, address_street = ?, 
                    address_city = ?, address_state = ?, address_postal_code = ?, 
                    address_country = ?, website = ?, linkedin_url = ?, twitter_handle = ?,
                    status = ?, lead_source = ?, description = ?, updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL";

        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['first_name'] ?? '',
                $data['last_name'] ?? '',
                $data['email'] ?? '',
                $data['phone'] ?? '',
                $data['mobile'] ?? '',
                $data['title'] ?? '',
                $data['organization_id'] ?? null,
                $data['department'] ?? '',
                $data['address_street'] ?? '',
                $data['address_city'] ?? '',
                $data['address_state'] ?? '',
                $data['address_postal_code'] ?? '',
                $data['address_country'] ?? '',
                $data['website'] ?? '',
                $data['linkedin_url'] ?? '',
                $data['twitter_handle'] ?? '',
                $data['status'] ?? 'active',
                $data['lead_source'] ?? '',
                $data['description'] ?? '',
                $id
            ]);

            if ($result && !empty($data['tags'])) {
                $this->updateTags($id, $data['tags']);
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Contact update error: " . $e->getMessage());
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
            error_log("Contact delete error: " . $e->getMessage());
            return false;
        }
    }

    public function countByUser($userId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE owner_id = ? AND deleted_at IS NULL";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Contact countByUser error: " . $e->getMessage());
            return 0;
        }
    }

    public function countByUserInPeriod($userId, $period)
    {
        $dateCondition = $this->getDateCondition($period);
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE owner_id = ? AND deleted_at IS NULL AND created_at {$dateCondition}";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Contact countByUserInPeriod error: " . $e->getMessage());
            return 0;
        }
    }

    public function getRecentByUser($userId, $limit = 10)
    {
        $sql = "SELECT c.*, o.name as organization_name 
                FROM {$this->table} c
                LEFT JOIN organizations o ON c.organization_id = o.id
                WHERE c.owner_id = ? AND c.deleted_at IS NULL
                ORDER BY c.created_at DESC
                LIMIT ?";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Contact getRecentByUser error: " . $e->getMessage());
            return [];
        }
    }

    public function search($query, $userId = null, $limit = 50)
    {
        $sql = "SELECT c.*, o.name as organization_name
                FROM {$this->table} c
                LEFT JOIN organizations o ON c.organization_id = o.id
                WHERE c.deleted_at IS NULL 
                AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";

        $params = ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%"];

        if ($userId) {
            $sql .= " AND c.owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY c.first_name, c.last_name LIMIT ?";
        $params[] = $limit;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Contact search error: " . $e->getMessage());
            return [];
        }
    }

    public function searchOrganizations($query, $userId = null, $limit = 50)
    {
        $sql = "SELECT DISTINCT o.id, o.name, o.industry, o.website
                FROM organizations o
                INNER JOIN contacts c ON o.id = c.organization_id
                WHERE o.deleted_at IS NULL AND o.name LIKE ?";

        $params = ["%{$query}%"];

        if ($userId) {
            $sql .= " AND c.owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY o.name LIMIT ?";
        $params[] = $limit;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Contact searchOrganizations error: " . $e->getMessage());
            return [];
        }
    }

    public function getTags($contactId)
    {
        $sql = "SELECT t.* FROM tags t
                INNER JOIN contact_tags ct ON t.id = ct.tag_id
                WHERE ct.contact_id = ?
                ORDER BY t.name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contactId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Contact getTags error: " . $e->getMessage());
            return [];
        }
    }

    public function updateTags($contactId, $tags)
    {
        try {
            $this->db->beginTransaction();

            // Remove existing tags
            $sql = "DELETE FROM contact_tags WHERE contact_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contactId]);

            // Add new tags
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $tagId = $this->getOrCreateTag($tag);
                    if ($tagId) {
                        $sql = "INSERT INTO contact_tags (contact_id, tag_id) VALUES (?, ?)";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([$contactId, $tagId]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Contact updateTags error: " . $e->getMessage());
            return false;
        }
    }

    private function getOrCreateTag($tagName)
    {
        // First try to find existing tag
        $sql = "SELECT id FROM tags WHERE name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tagName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['id'];
        }

        // Create new tag
        $sql = "INSERT INTO tags (name, created_at) VALUES (?, NOW())";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$tagName])) {
            return $this->db->lastInsertId();
        }

        return false;
    }

    public function getStatsByStatus($userId = null)
    {
        $sql = "SELECT status, COUNT(*) as count 
                FROM {$this->table} 
                WHERE deleted_at IS NULL";

        $params = [];
        if ($userId) {
            $sql .= " AND owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " GROUP BY status";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Contact getStatsByStatus error: " . $e->getMessage());
            return [];
        }
    }

    public function getStatsByLeadSource($userId = null)
    {
        $sql = "SELECT lead_source, COUNT(*) as count 
                FROM {$this->table} 
                WHERE deleted_at IS NULL AND lead_source IS NOT NULL AND lead_source != ''";

        $params = [];
        if ($userId) {
            $sql .= " AND owner_id = ?";
            $params[] = $userId;
        }

        $sql .= " GROUP BY lead_source ORDER BY count DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Contact getStatsByLeadSource error: " . $e->getMessage());
            return [];
        }
    }

    public function validateEmail($email, $excludeId = null)
    {
        $sql = "SELECT id FROM {$this->table} WHERE email = ? AND deleted_at IS NULL";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) === false;
        } catch (PDOException $e) {
            error_log("Contact validateEmail error: " . $e->getMessage());
            return false;
        }
    }

    private function getDateCondition($period)
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
<?php

namespace App\Controllers;

use App\Models\Organization;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Models\Activity;

class OrganizationsController extends BaseController
{
    private $organizationModel;
    private $contactModel;
    private $dealModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->organizationModel = new Organization();
        $this->contactModel = new Contact();
        $this->dealModel = new Deal();
        $this->userModel = new User();
        $this->requireAuth();
    }

    /**
     * Display organizations list
     */
    public function index()
    {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        // Get filter parameters
        $filters = [
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'industry' => $_GET['industry'] ?? '',
            'size' => $_GET['size'] ?? '',
            'assigned_user_id' => $this->hasPermission('organizations.view_all') ? ($_GET['assigned_user_id'] ?? '') : $this->currentUser['id'],
            'search' => $_GET['search'] ?? '',
            'location' => $_GET['location'] ?? '',
            'min_revenue' => $_GET['min_revenue'] ?? '',
            'max_revenue' => $_GET['max_revenue'] ?? '',
            'min_employees' => $_GET['min_employees'] ?? '',
            'max_employees' => $_GET['max_employees'] ?? '',
            'high_value' => isset($_GET['high_value']) ? 1 : '',
            'limit' => $limit,
            'offset' => $offset,
            'order_by' => $_GET['order_by'] ?? 'name',
            'order_direction' => $_GET['order_direction'] ?? 'ASC'
        ];

        // Get organizations and statistics
        $organizations = $this->organizationModel->findAll($filters);
        $statistics = $this->organizationModel->getStatistics(
            $this->hasPermission('organizations.view_all') ? null : $this->currentUser['id']
        );

        // Get users for filter dropdown (if user has permission)
        $users = [];
        if ($this->hasPermission('organizations.view_all')) {
            $users = $this->userModel->findAll(['status' => 'active']);
        }

        // Get industries for filter dropdown
        $industries = $this->getIndustryList();

        // Count total for pagination
        $totalFilters = $filters;
        unset($totalFilters['limit'], $totalFilters['offset']);
        $totalOrganizations = count($this->organizationModel->findAll($totalFilters));
        
        $totalPages = ceil($totalOrganizations / $limit);

        $this->render('organizations/index', [
            'organizations' => $organizations,
            'statistics' => $statistics,
            'users' => $users,
            'industries' => $industries,
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalOrganizations' => $totalOrganizations
        ]);
    }

    /**
     * Show organization details
     */
    public function show($id)
    {
        $organization = $this->getOrganization($id);

        // Get organization hierarchy
        $hierarchy = $this->organizationModel->getOrganizationHierarchy($id);

        // Get contacts
        $contacts = $this->organizationModel->getOrganizationContacts($id, ['limit' => 10]);

        // Get deals
        $deals = $this->organizationModel->getOrganizationDeals($id, ['limit' => 10]);

        // Get tasks
        $tasks = $this->getOrganizationTasks($id, 10);

        // Get activities
        $activities = $this->organizationModel->getOrganizationActivities($id, 20);

        // Get summary metrics
        $summary = $this->organizationModel->getOrganizationSummary($id);
        $performance = $this->organizationModel->getPerformanceMetrics($id);

        $this->render('organizations/show', [
            'organization' => $organization,
            'hierarchy' => $hierarchy,
            'contacts' => $contacts,
            'deals' => $deals,
            'tasks' => $tasks,
            'activities' => $activities,
            'summary' => $summary,
            'performance' => $performance
        ]);
    }

    /**
     * Show create organization form
     */
    public function create()
    {
        $this->checkPermission('organizations.create');

        // Get users for assignment
        $users = $this->userModel->findAll(['status' => 'active']);

        // Get potential parent organizations
        $parentOrganizations = $this->organizationModel->findAll(['limit' => 100]);

        // Get industries list
        $industries = $this->getIndustryList();

        $this->render('organizations/create', [
            'users' => $users,
            'parentOrganizations' => $parentOrganizations,
            'industries' => $industries
        ]);
    }

    /**
     * Store new organization
     */
    public function store()
    {
        $this->checkPermission('organizations.create');
        $this->validateCSRFToken();

        $data = $this->getOrganizationDataFromPost();
        $data['created_by'] = $this->currentUser['id'];
        $data['updated_by'] = $this->currentUser['id'];

        // Validate data
        $errors = $this->organizationModel->validate($data);
        if (!empty($errors)) {
            $this->setFlashMessage('error', 'Please correct the errors below.');
            return $this->create();
        }

        try {
            $organizationId = $this->organizationModel->create($data);

            // Log activity
            $this->logActivity('organization', $organizationId, 'organization_created', [
                'name' => $data['name'],
                'type' => $data['type'],
                'industry' => $data['industry']
            ]);

            $this->setFlashMessage('success', 'Organization created successfully.');
            $this->redirect('/organizations/show/' . $organizationId);

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to create organization: ' . $e->getMessage());
            return $this->create();
        }
    }

    /**
     * Show edit organization form
     */
    public function edit($id)
    {
        $organization = $this->getOrganization($id);
        $this->checkOrganizationPermission($organization, 'edit');

        // Get users for assignment
        $users = $this->userModel->findAll(['status' => 'active']);

        // Get potential parent organizations (excluding self and children)
        $parentOrganizations = $this->organizationModel->findAll(['limit' => 100]);
        $parentOrganizations = array_filter($parentOrganizations, function($org) use ($id) {
            return $org['id'] != $id; // Exclude self
        });

        // Get industries list
        $industries = $this->getIndustryList();

        $this->render('organizations/edit', [
            'organization' => $organization,
            'users' => $users,
            'parentOrganizations' => $parentOrganizations,
            'industries' => $industries
        ]);
    }

    /**
     * Update organization
     */
    public function update($id)
    {
        $organization = $this->getOrganization($id);
        $this->checkOrganizationPermission($organization, 'edit');
        $this->validateCSRFToken();

        $data = $this->getOrganizationDataFromPost();
        $data['updated_by'] = $this->currentUser['id'];
        $data['id'] = $id;

        // Validate data
        $errors = $this->organizationModel->validate($data, true);
        if (!empty($errors)) {
            $this->setFlashMessage('error', 'Please correct the errors below.');
            return $this->edit($id);
        }

        try {
            $this->organizationModel->update($id, $data);

            // Log activity for important changes
            $changes = [];
            if ($organization['name'] !== $data['name']) {
                $changes['name'] = ['from' => $organization['name'], 'to' => $data['name']];
            }
            if ($organization['type'] !== $data['type']) {
                $changes['type'] = ['from' => $organization['type'], 'to' => $data['type']];
            }
            if ($organization['status'] !== $data['status']) {
                $changes['status'] = ['from' => $organization['status'], 'to' => $data['status']];
            }

            if (!empty($changes)) {
                $this->logActivity('organization', $id, 'organization_updated', $changes);
            }

            $this->setFlashMessage('success', 'Organization updated successfully.');
            $this->redirect('/organizations/show/' . $id);

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to update organization: ' . $e->getMessage());
            return $this->edit($id);
        }
    }

    /**
     * Delete organization
     */
    public function delete($id)
    {
        $organization = $this->getOrganization($id);
        $this->checkOrganizationPermission($organization, 'delete');
        $this->validateCSRFToken();

        // Check for dependencies
        $contacts = $this->organizationModel->getOrganizationContacts($id);
        $deals = $this->organizationModel->getOrganizationDeals($id);
        $children = $this->organizationModel->getOrganizationHierarchy($id)['children'];

        if (!empty($contacts) || !empty($deals) || !empty($children)) {
            $message = 'Cannot delete organization with associated ';
            $dependencies = [];
            if (!empty($contacts)) $dependencies[] = 'contacts';
            if (!empty($deals)) $dependencies[] = 'deals';
            if (!empty($children)) $dependencies[] = 'child organizations';
            
            $message .= implode(', ', $dependencies) . '. Please remove these associations first.';
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $message]);
            } else {
                $this->setFlashMessage('error', $message);
                $this->redirect('/organizations/show/' . $id);
            }
            return;
        }

        try {
            $this->organizationModel->delete($id);

            // Log activity
            $this->logActivity('organization', $id, 'organization_deleted', [
                'name' => $organization['name']
            ]);

            $this->setFlashMessage('success', 'Organization deleted successfully.');
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true]);
            } else {
                $this->redirect('/organizations');
            }

        } catch (Exception $e) {
            $message = 'Failed to delete organization: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $message]);
            } else {
                $this->setFlashMessage('error', $message);
                $this->redirect('/organizations');
            }
        }
    }

    /**
     * Get organization analytics
     */
    public function analytics()
    {
        $this->checkPermission('organizations.view_all');

        $userId = $_GET['user_id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        // Get statistics
        $statistics = $this->organizationModel->getStatistics($userId);

        // Get industry analytics
        $industryStats = $this->organizationModel->getOrganizationsByIndustry($userId);

        // Get top organizations by revenue
        $topByRevenue = $this->organizationModel->getTopOrganizationsByRevenue(10, $userId);

        // Get users for filter (if permission allows)
        $users = [];
        if ($this->hasPermission('organizations.view_all')) {
            $users = $this->userModel->findAll(['status' => 'active']);
        }

        $this->render('organizations/analytics', [
            'statistics' => $statistics,
            'industryStats' => $industryStats,
            'topByRevenue' => $topByRevenue,
            'users' => $users,
            'selectedUserId' => $userId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }

    /**
     * Search organizations (AJAX)
     */
    public function search()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('/organizations');
        }

        $searchTerm = $_GET['q'] ?? '';
        $filters = [
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'industry' => $_GET['industry'] ?? '',
            'size' => $_GET['size'] ?? ''
        ];

        if (strlen($searchTerm) < 2) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Search term must be at least 2 characters'
            ]);
        }

        try {
            $organizations = $this->organizationModel->search($searchTerm, $filters);

            $this->jsonResponse([
                'success' => true,
                'organizations' => $organizations,
                'count' => count($organizations)
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get organization statistics (AJAX)
     */
    public function getStatistics()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('/organizations');
        }

        $userId = $this->hasPermission('organizations.view_all') ? ($_GET['user_id'] ?? null) : $this->currentUser['id'];

        try {
            $statistics = $this->organizationModel->getStatistics($userId);
            
            $this->jsonResponse([
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Merge duplicate organizations
     */
    public function merge()
    {
        $this->checkPermission('organizations.edit');
        $this->validateCSRFToken();

        $primaryId = $_POST['primary_id'] ?? '';
        $duplicateId = $_POST['duplicate_id'] ?? '';

        if (empty($primaryId) || empty($duplicateId) || $primaryId === $duplicateId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid organization IDs for merge'
            ]);
        }

        $primary = $this->getOrganization($primaryId);
        $duplicate = $this->getOrganization($duplicateId);

        try {
            // Start transaction
            $this->db->beginTransaction();

            // Update contacts to point to primary organization
            $this->contactModel->updateByField('organization_id', $duplicateId, ['organization_id' => $primaryId]);

            // Update deals to point to primary organization
            $this->dealModel->updateByField('organization_id', $duplicateId, ['organization_id' => $primaryId]);

            // Update child organizations to point to primary as parent
            $this->organizationModel->updateByField('parent_organization_id', $duplicateId, ['parent_organization_id' => $primaryId]);

            // Delete the duplicate organization
            $this->organizationModel->delete($duplicateId);

            // Commit transaction
            $this->db->commit();

            // Log activity
            $this->logActivity('organization', $primaryId, 'organizations_merged', [
                'primary_organization' => $primary['name'],
                'merged_organization' => $duplicate['name']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => "Organizations merged successfully. {$duplicate['name']} has been merged into {$primary['name']}."
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to merge organizations: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get organization with permission check
     */
    private function getOrganization($id)
    {
        $organization = $this->organizationModel->find($id);
        
        if (!$organization) {
            $this->setFlashMessage('error', 'Organization not found.');
            $this->redirect('/organizations');
        }

        return $organization;
    }

    /**
     * Check organization-specific permissions
     */
    private function checkOrganizationPermission($organization, $action)
    {
        // Users can always access organizations they're assigned to
        if ($organization['assigned_user_id'] == $this->currentUser['id']) {
            return true;
        }

        // Check global permission
        $this->checkPermission("organizations.{$action}_all");
        
        return true;
    }

    /**
     * Get organization data from POST request
     */
    private function getOrganizationDataFromPost()
    {
        return [
            'name' => $_POST['name'] ?? '',
            'industry' => $_POST['industry'] ?? '',
            'type' => $_POST['type'] ?? Organization::TYPE_PROSPECT,
            'size' => $_POST['size'] ?? '',
            'website' => $_POST['website'] ?? '',
            'description' => $_POST['description'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'fax' => $_POST['fax'] ?? '',
            'linkedin_url' => $_POST['linkedin_url'] ?? '',
            'twitter_handle' => $_POST['twitter_handle'] ?? '',
            'facebook_url' => $_POST['facebook_url'] ?? '',
            'annual_revenue' => $_POST['annual_revenue'] ?? null,
            'employee_count' => $_POST['employee_count'] ?? null,
            'founded_year' => $_POST['founded_year'] ?? null,
            'ownership_type' => $_POST['ownership_type'] ?? '',
            'status' => $_POST['status'] ?? Organization::STATUS_ACTIVE,
            'rating' => $_POST['rating'] ?? null,
            'tags' => $_POST['tags'] ?? '',
            'parent_organization_id' => $_POST['parent_organization_id'] ?? null,
            'billing_address_line_1' => $_POST['billing_address_line_1'] ?? '',
            'billing_address_line_2' => $_POST['billing_address_line_2'] ?? '',
            'billing_city' => $_POST['billing_city'] ?? '',
            'billing_state' => $_POST['billing_state'] ?? '',
            'billing_postal_code' => $_POST['billing_postal_code'] ?? '',
            'billing_country' => $_POST['billing_country'] ?? '',
            'shipping_address_line_1' => $_POST['shipping_address_line_1'] ?? '',
            'shipping_address_line_2' => $_POST['shipping_address_line_2'] ?? '',
            'shipping_city' => $_POST['shipping_city'] ?? '',
            'shipping_state' => $_POST['shipping_state'] ?? '',
            'shipping_postal_code' => $_POST['shipping_postal_code'] ?? '',
            'shipping_country' => $_POST['shipping_country'] ?? '',
            'tax_id' => $_POST['tax_id'] ?? '',
            'currency' => $_POST['currency'] ?? 'USD',
            'payment_terms' => $_POST['payment_terms'] ?? '',
            'credit_limit' => $_POST['credit_limit'] ?? null,
            'assigned_user_id' => $_POST['assigned_user_id'] ?? $this->currentUser['id'],
            'logo_url' => $_POST['logo_url'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];
    }

    /**
     * Get industry list for dropdowns
     */
    private function getIndustryList()
    {
        return [
            'Technology' => 'Technology',
            'Healthcare' => 'Healthcare',
            'Finance' => 'Finance',
            'Manufacturing' => 'Manufacturing',
            'Retail' => 'Retail',
            'Education' => 'Education',
            'Real Estate' => 'Real Estate',
            'Construction' => 'Construction',
            'Transportation' => 'Transportation',
            'Energy' => 'Energy',
            'Media' => 'Media',
            'Hospitality' => 'Hospitality',
            'Agriculture' => 'Agriculture',
            'Government' => 'Government',
            'Non-Profit' => 'Non-Profit',
            'Other' => 'Other'
        ];
    }

    /**
     * Get organization tasks
     */
    private function getOrganizationTasks($organizationId, $limit = 10)
    {
        $taskModel = new Task();
        return $taskModel->getEntityTimeline('organization', $organizationId);
    }

    /**
     * Log activity for organization actions
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
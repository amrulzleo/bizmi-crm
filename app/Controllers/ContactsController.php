<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Models\Activity;

class ContactsController extends Controller
{
    private $contactModel;
    private $organizationModel;
    private $userModel;
    private $activityModel;

    public function __construct()
    {
        parent::__construct();
        $this->contactModel = new Contact();
        $this->organizationModel = new Organization();
        $this->userModel = new User();
        $this->activityModel = new Activity();
        
        // Ensure user is authenticated
        if (!$this->auth->isLoggedIn()) {
            $this->redirect('/login');
        }
    }

    public function index()
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 25);
        $offset = ($page - 1) * $limit;
        
        // Build filters from query parameters
        $filters = $this->buildFilters();
        
        $userId = $this->auth->getUserId();
        
        // Get contacts with pagination
        $contacts = $this->contactModel->findAll($userId, $limit, $offset, $filters);
        
        // Get total count for pagination
        $totalContacts = $this->getTotalContactsCount($userId, $filters);
        $totalPages = ceil($totalContacts / $limit);
        
        // Get filter options
        $organizations = $this->organizationModel->findAllByUser($userId);
        $users = $this->userModel->findAllActive();
        $leadSources = $this->getLeadSources();
        $tags = $this->getAllTags();
        
        // Get statistics
        $stats = [
            'total' => $totalContacts,
            'by_status' => $this->contactModel->getStatsByStatus($userId),
            'by_lead_source' => $this->contactModel->getStatsByLeadSource($userId)
        ];

        $this->view->render('contacts/index', [
            'contacts' => $contacts,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalContacts,
                'limit' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'filters' => $filters,
            'filter_options' => [
                'organizations' => $organizations,
                'users' => $users,
                'lead_sources' => $leadSources,
                'tags' => $tags
            ],
            'stats' => $stats,
            'page_title' => 'Contacts'
        ]);
    }

    public function show($id)
    {
        $contact = $this->contactModel->findById($id);
        
        if (!$contact) {
            $this->redirect('/contacts', 'Contact not found', 'error');
            return;
        }

        // Check if user has permission to view this contact
        if (!$this->canViewContact($contact)) {
            $this->redirect('/contacts', 'Access denied', 'error');
            return;
        }

        // Get related data
        $tags = $this->contactModel->getTags($id);
        $activities = $this->activityModel->findByContact($id);
        $deals = $this->getDealsByContact($id);
        $notes = $this->getNotesByContact($id);

        $this->view->render('contacts/show', [
            'contact' => $contact,
            'tags' => $tags,
            'activities' => $activities,
            'deals' => $deals,
            'notes' => $notes,
            'page_title' => $contact['first_name'] . ' ' . $contact['last_name']
        ]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleCreate();
        }

        // Get form options
        $organizations = $this->organizationModel->findAllByUser($this->auth->getUserId());
        $users = $this->userModel->findAllActive();
        $leadSources = $this->getLeadSources();

        $this->view->render('contacts/create', [
            'organizations' => $organizations,
            'users' => $users,
            'lead_sources' => $leadSources,
            'page_title' => 'Create Contact'
        ]);
    }

    public function edit($id)
    {
        $contact = $this->contactModel->findById($id);
        
        if (!$contact) {
            $this->redirect('/contacts', 'Contact not found', 'error');
            return;
        }

        // Check if user has permission to edit this contact
        if (!$this->canEditContact($contact)) {
            $this->redirect('/contacts', 'Access denied', 'error');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleUpdate($id);
        }

        // Get form options
        $organizations = $this->organizationModel->findAllByUser($this->auth->getUserId());
        $users = $this->userModel->findAllActive();
        $leadSources = $this->getLeadSources();
        $tags = $this->contactModel->getTags($id);

        $this->view->render('contacts/edit', [
            'contact' => $contact,
            'organizations' => $organizations,
            'users' => $users,
            'lead_sources' => $leadSources,
            'tags' => $tags,
            'page_title' => 'Edit ' . $contact['first_name'] . ' ' . $contact['last_name']
        ]);
    }

    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        $contact = $this->contactModel->findById($id);
        
        if (!$contact) {
            $this->jsonResponse(['success' => false, 'message' => 'Contact not found'], 404);
            return;
        }

        // Check if user has permission to delete this contact
        if (!$this->canDeleteContact($contact)) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        if ($this->contactModel->delete($id)) {
            // Log activity
            $this->activityModel->log([
                'type' => 'contact_deleted',
                'subject' => $contact['first_name'] . ' ' . $contact['last_name'],
                'description' => 'Contact deleted',
                'contact_id' => $id,
                'user_id' => $this->auth->getUserId()
            ]);

            $this->jsonResponse([
                'success' => true, 
                'message' => 'Contact deleted successfully'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to delete contact'
            ], 500);
        }
    }

    private function handleCreate()
    {
        $data = $this->validateContactData($_POST);
        
        if (!$data['valid']) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $data['errors']
                ], 422);
                return;
            } else {
                $this->redirect('/contacts/create', 'Please correct the errors below', 'error');
                return;
            }
        }

        // Set defaults
        $data['data']['owner_id'] = $data['data']['owner_id'] ?? $this->auth->getUserId();
        $data['data']['created_by'] = $this->auth->getUserId();

        $contactId = $this->contactModel->create($data['data']);

        if ($contactId) {
            // Log activity
            $this->activityModel->log([
                'type' => 'contact_created',
                'subject' => $data['data']['first_name'] . ' ' . $data['data']['last_name'],
                'description' => 'Contact created',
                'contact_id' => $contactId,
                'user_id' => $this->auth->getUserId()
            ]);

            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Contact created successfully',
                    'redirect' => '/contacts/' . $contactId
                ]);
            } else {
                $this->redirect('/contacts/' . $contactId, 'Contact created successfully', 'success');
            }
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to create contact'
                ], 500);
            } else {
                $this->redirect('/contacts/create', 'Failed to create contact', 'error');
            }
        }
    }

    private function handleUpdate($id)
    {
        $data = $this->validateContactData($_POST, $id);
        
        if (!$data['valid']) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $data['errors']
                ], 422);
                return;
            } else {
                $this->redirect('/contacts/' . $id . '/edit', 'Please correct the errors below', 'error');
                return;
            }
        }

        if ($this->contactModel->update($id, $data['data'])) {
            // Log activity
            $this->activityModel->log([
                'type' => 'contact_updated',
                'subject' => $data['data']['first_name'] . ' ' . $data['data']['last_name'],
                'description' => 'Contact updated',
                'contact_id' => $id,
                'user_id' => $this->auth->getUserId()
            ]);

            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Contact updated successfully',
                    'redirect' => '/contacts/' . $id
                ]);
            } else {
                $this->redirect('/contacts/' . $id, 'Contact updated successfully', 'success');
            }
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to update contact'
                ], 500);
            } else {
                $this->redirect('/contacts/' . $id . '/edit', 'Failed to update contact', 'error');
            }
        }
    }

    private function validateContactData($data, $excludeId = null)
    {
        $errors = [];
        $cleanData = [];

        // Required fields
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        } else {
            $cleanData['first_name'] = trim($data['first_name']);
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        } else {
            $cleanData['last_name'] = trim($data['last_name']);
        }

        // Email validation
        if (!empty($data['email'])) {
            $email = trim($data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif (!$this->contactModel->validateEmail($email, $excludeId)) {
                $errors['email'] = 'Email already exists';
            } else {
                $cleanData['email'] = $email;
            }
        }

        // Phone validation
        if (!empty($data['phone'])) {
            $cleanData['phone'] = trim($data['phone']);
        }

        if (!empty($data['mobile'])) {
            $cleanData['mobile'] = trim($data['mobile']);
        }

        // Optional fields
        $optionalFields = [
            'title', 'department', 'address_street', 'address_city',
            'address_state', 'address_postal_code', 'address_country',
            'website', 'linkedin_url', 'twitter_handle', 'description'
        ];

        foreach ($optionalFields as $field) {
            if (!empty($data[$field])) {
                $cleanData[$field] = trim($data[$field]);
            }
        }

        // Status
        $validStatuses = ['active', 'inactive', 'potential', 'converted'];
        if (!empty($data['status']) && in_array($data['status'], $validStatuses)) {
            $cleanData['status'] = $data['status'];
        } else {
            $cleanData['status'] = 'active';
        }

        // Lead source
        if (!empty($data['lead_source'])) {
            $cleanData['lead_source'] = trim($data['lead_source']);
        }

        // Organization ID
        if (!empty($data['organization_id']) && is_numeric($data['organization_id'])) {
            $cleanData['organization_id'] = (int)$data['organization_id'];
        }

        // Owner ID
        if (!empty($data['owner_id']) && is_numeric($data['owner_id'])) {
            $cleanData['owner_id'] = (int)$data['owner_id'];
        }

        // Tags
        if (!empty($data['tags'])) {
            if (is_string($data['tags'])) {
                $cleanData['tags'] = array_map('trim', explode(',', $data['tags']));
            } elseif (is_array($data['tags'])) {
                $cleanData['tags'] = array_map('trim', $data['tags']);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $cleanData
        ];
    }

    private function buildFilters()
    {
        $filters = [];
        
        if (!empty($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }
        
        if (!empty($_GET['organization_id']) && is_numeric($_GET['organization_id'])) {
            $filters['organization_id'] = (int)$_GET['organization_id'];
        }
        
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (!empty($_GET['lead_source'])) {
            $filters['lead_source'] = $_GET['lead_source'];
        }
        
        if (!empty($_GET['tags'])) {
            if (is_string($_GET['tags'])) {
                $filters['tags'] = explode(',', $_GET['tags']);
            } elseif (is_array($_GET['tags'])) {
                $filters['tags'] = $_GET['tags'];
            }
        }
        
        if (!empty($_GET['sort_field'])) {
            $validSortFields = ['first_name', 'last_name', 'email', 'created_at', 'organization_name'];
            if (in_array($_GET['sort_field'], $validSortFields)) {
                $filters['sort_field'] = 'c.' . $_GET['sort_field'];
                if ($_GET['sort_field'] === 'organization_name') {
                    $filters['sort_field'] = 'o.name';
                }
            }
        }
        
        if (!empty($_GET['sort_direction'])) {
            $filters['sort_direction'] = strtoupper($_GET['sort_direction']) === 'ASC' ? 'ASC' : 'DESC';
        }

        return $filters;
    }

    private function getTotalContactsCount($userId, $filters)
    {
        // This would typically be a separate method in the model
        // For now, we'll use a simplified approach
        return $this->contactModel->countByUser($userId);
    }

    private function canViewContact($contact)
    {
        $userRole = $this->auth->getUserRole();
        $userId = $this->auth->getUserId();
        
        // Admin and manager can view all contacts
        if (in_array($userRole, ['admin', 'manager'])) {
            return true;
        }
        
        // Users can view contacts they own
        return $contact['owner_id'] == $userId;
    }

    private function canEditContact($contact)
    {
        return $this->canViewContact($contact);
    }

    private function canDeleteContact($contact)
    {
        $userRole = $this->auth->getUserRole();
        $userId = $this->auth->getUserId();
        
        // Only admin and contact owner can delete
        if ($userRole === 'admin' || $contact['owner_id'] == $userId) {
            return true;
        }
        
        return false;
    }

    private function getLeadSources()
    {
        return [
            'website' => 'Website',
            'referral' => 'Referral',
            'social_media' => 'Social Media',
            'email_campaign' => 'Email Campaign',
            'trade_show' => 'Trade Show',
            'cold_call' => 'Cold Call',
            'advertisement' => 'Advertisement',
            'partner' => 'Partner',
            'other' => 'Other'
        ];
    }

    private function getAllTags()
    {
        // This would be implemented in a Tag model
        return [];
    }

    private function getDealsByContact($contactId)
    {
        // This would be implemented using a Deal model
        return [];
    }

    private function getNotesByContact($contactId)
    {
        // This would be implemented using a Note model
        return [];
    }

    private function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
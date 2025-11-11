<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Deal;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Models\Activity;

class DealsController extends Controller
{
    private $dealModel;
    private $contactModel;
    private $organizationModel;
    private $userModel;
    private $activityModel;

    public function __construct()
    {
        parent::__construct();
        $this->dealModel = new Deal();
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
        
        // Get deals with pagination
        $deals = $this->dealModel->findAll($userId, $limit, $offset, $filters);
        
        // Get total count for pagination
        $totalDeals = $this->getTotalDealsCount($userId, $filters);
        $totalPages = ceil($totalDeals / $limit);
        
        // Get filter options
        $pipelineStages = $this->dealModel->getPipelineStages();
        $contacts = $this->contactModel->findAll($userId, 1000); // Get all for dropdown
        $organizations = $this->organizationModel->findAllByUser($userId);
        $users = $this->userModel->findAllActive();
        $dealSources = $this->getDealSources();
        
        // Get statistics
        $stats = [
            'total' => $totalDeals,
            'by_stage' => $this->dealModel->getStatsByStage($userId),
            'by_source' => $this->dealModel->getStatsBySource($userId),
            'total_value' => array_sum(array_column($deals, 'amount')),
            'avg_deal_size' => $totalDeals > 0 ? array_sum(array_column($deals, 'amount')) / $totalDeals : 0
        ];

        $this->view->render('deals/index', [
            'deals' => $deals,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalDeals,
                'limit' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'filters' => $filters,
            'filter_options' => [
                'pipeline_stages' => $pipelineStages,
                'contacts' => $contacts,
                'organizations' => $organizations,
                'users' => $users,
                'deal_sources' => $dealSources
            ],
            'stats' => $stats,
            'page_title' => 'Deals'
        ]);
    }

    public function pipeline()
    {
        $userId = $this->auth->getUserId();
        
        // Get pipeline stages with deals
        $stages = $this->dealModel->getPipelineStagesByUser($userId);
        
        // Get deals grouped by stage
        $dealsByStage = [];
        foreach ($stages as $stage) {
            $filters = ['stage_id' => $stage['id']];
            $deals = $this->dealModel->findAll($userId, null, 0, $filters);
            $dealsByStage[$stage['id']] = $deals;
        }
        
        // Get statistics
        $stats = [
            'total_deals' => array_sum(array_column($stages, 'count')),
            'total_value' => array_sum(array_column($stages, 'value')),
            'weighted_value' => $this->calculateWeightedPipelineValue($stages),
            'avg_deal_size' => $this->calculateAverageDealSize($stages)
        ];

        $this->view->render('deals/pipeline', [
            'stages' => $stages,
            'deals_by_stage' => $dealsByStage,
            'stats' => $stats,
            'page_title' => 'Sales Pipeline'
        ]);
    }

    public function show($id)
    {
        $deal = $this->dealModel->findById($id);
        
        if (!$deal) {
            $this->redirect('/deals', 'Deal not found', 'error');
            return;
        }

        // Check if user has permission to view this deal
        if (!$this->canViewDeal($deal)) {
            $this->redirect('/deals', 'Access denied', 'error');
            return;
        }

        // Get related data
        $activities = $this->activityModel->findByDeal($id);
        $notes = $this->getNotesByDeal($id);
        $pipelineStages = $this->dealModel->getPipelineStages();

        $this->view->render('deals/show', [
            'deal' => $deal,
            'activities' => $activities,
            'notes' => $notes,
            'pipeline_stages' => $pipelineStages,
            'page_title' => $deal['name']
        ]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleCreate();
        }

        // Get form options
        $pipelineStages = $this->dealModel->getPipelineStages();
        $contacts = $this->contactModel->findAll($this->auth->getUserId(), 1000);
        $organizations = $this->organizationModel->findAllByUser($this->auth->getUserId());
        $users = $this->userModel->findAllActive();
        $dealSources = $this->getDealSources();

        $this->view->render('deals/create', [
            'pipeline_stages' => $pipelineStages,
            'contacts' => $contacts,
            'organizations' => $organizations,
            'users' => $users,
            'deal_sources' => $dealSources,
            'page_title' => 'Create Deal'
        ]);
    }

    public function edit($id)
    {
        $deal = $this->dealModel->findById($id);
        
        if (!$deal) {
            $this->redirect('/deals', 'Deal not found', 'error');
            return;
        }

        // Check if user has permission to edit this deal
        if (!$this->canEditDeal($deal)) {
            $this->redirect('/deals', 'Access denied', 'error');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleUpdate($id);
        }

        // Get form options
        $pipelineStages = $this->dealModel->getPipelineStages();
        $contacts = $this->contactModel->findAll($this->auth->getUserId(), 1000);
        $organizations = $this->organizationModel->findAllByUser($this->auth->getUserId());
        $users = $this->userModel->findAllActive();
        $dealSources = $this->getDealSources();

        $this->view->render('deals/edit', [
            'deal' => $deal,
            'pipeline_stages' => $pipelineStages,
            'contacts' => $contacts,
            'organizations' => $organizations,
            'users' => $users,
            'deal_sources' => $dealSources,
            'page_title' => 'Edit ' . $deal['name']
        ]);
    }

    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        $deal = $this->dealModel->findById($id);
        
        if (!$deal) {
            $this->jsonResponse(['success' => false, 'message' => 'Deal not found'], 404);
            return;
        }

        // Check if user has permission to delete this deal
        if (!$this->canDeleteDeal($deal)) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        if ($this->dealModel->delete($id)) {
            // Log activity
            $this->activityModel->log([
                'type' => 'deal_deleted',
                'subject' => $deal['name'],
                'description' => 'Deal deleted',
                'deal_id' => $id,
                'user_id' => $this->auth->getUserId()
            ]);

            $this->jsonResponse([
                'success' => true, 
                'message' => 'Deal deleted successfully'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to delete deal'
            ], 500);
        }
    }

    public function updateStage($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        $deal = $this->dealModel->findById($id);
        
        if (!$deal) {
            $this->jsonResponse(['success' => false, 'message' => 'Deal not found'], 404);
            return;
        }

        // Check if user has permission to edit this deal
        if (!$this->canEditDeal($deal)) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        $stageId = $_POST['stage_id'] ?? null;
        if (!$stageId) {
            $this->jsonResponse(['success' => false, 'message' => 'Stage ID is required'], 400);
            return;
        }

        if ($this->dealModel->updateStage($id, $stageId, $this->auth->getUserId())) {
            // Get updated stage name for logging
            $stage = $this->dealModel->getPipelineStage($stageId);
            
            // Log activity
            $this->activityModel->log([
                'type' => 'deal_stage_changed',
                'subject' => $deal['name'],
                'description' => 'Deal moved to ' . $stage['name'],
                'deal_id' => $id,
                'user_id' => $this->auth->getUserId()
            ]);

            $this->jsonResponse([
                'success' => true, 
                'message' => 'Deal stage updated successfully',
                'stage_name' => $stage['name']
            ]);
        } else {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to update deal stage'
            ], 500);
        }
    }

    public function forecast()
    {
        $userId = $this->auth->getUserId();
        $months = (int)($_GET['months'] ?? 3);
        
        // Get forecast data
        $forecastData = $this->dealModel->getForecast($userId, $months);
        
        // Get upcoming deadlines
        $upcomingDeadlines = $this->dealModel->getUpcomingDeadlines($userId, 30);
        
        // Get pipeline statistics
        $pipelineStats = $this->dealModel->getStatsByStage($userId);
        
        $this->view->render('deals/forecast', [
            'forecast_data' => $forecastData,
            'upcoming_deadlines' => $upcomingDeadlines,
            'pipeline_stats' => $pipelineStats,
            'months' => $months,
            'page_title' => 'Sales Forecast'
        ]);
    }

    private function handleCreate()
    {
        $data = $this->validateDealData($_POST);
        
        if (!$data['valid']) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $data['errors']
                ], 422);
                return;
            } else {
                $this->redirect('/deals/create', 'Please correct the errors below', 'error');
                return;
            }
        }

        // Set defaults
        $data['data']['owner_id'] = $data['data']['owner_id'] ?? $this->auth->getUserId();
        $data['data']['created_by'] = $this->auth->getUserId();

        $dealId = $this->dealModel->create($data['data']);

        if ($dealId) {
            // Log activity
            $this->activityModel->log([
                'type' => 'deal_created',
                'subject' => $data['data']['name'],
                'description' => 'Deal created',
                'deal_id' => $dealId,
                'user_id' => $this->auth->getUserId()
            ]);

            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Deal created successfully',
                    'redirect' => '/deals/' . $dealId
                ]);
            } else {
                $this->redirect('/deals/' . $dealId, 'Deal created successfully', 'success');
            }
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to create deal'
                ], 500);
            } else {
                $this->redirect('/deals/create', 'Failed to create deal', 'error');
            }
        }
    }

    private function handleUpdate($id)
    {
        $data = $this->validateDealData($_POST, $id);
        
        if (!$data['valid']) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $data['errors']
                ], 422);
                return;
            } else {
                $this->redirect('/deals/' . $id . '/edit', 'Please correct the errors below', 'error');
                return;
            }
        }

        if ($this->dealModel->update($id, $data['data'])) {
            // Log activity
            $this->activityModel->log([
                'type' => 'deal_updated',
                'subject' => $data['data']['name'],
                'description' => 'Deal updated',
                'deal_id' => $id,
                'user_id' => $this->auth->getUserId()
            ]);

            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Deal updated successfully',
                    'redirect' => '/deals/' . $id
                ]);
            } else {
                $this->redirect('/deals/' . $id, 'Deal updated successfully', 'success');
            }
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to update deal'
                ], 500);
            } else {
                $this->redirect('/deals/' . $id . '/edit', 'Failed to update deal', 'error');
            }
        }
    }

    private function validateDealData($data, $excludeId = null)
    {
        $errors = [];
        $cleanData = [];

        // Required fields
        if (empty($data['name'])) {
            $errors['name'] = 'Deal name is required';
        } else {
            $cleanData['name'] = trim($data['name']);
        }

        if (empty($data['stage_id']) || !is_numeric($data['stage_id'])) {
            $errors['stage_id'] = 'Pipeline stage is required';
        } else {
            $cleanData['stage_id'] = (int)$data['stage_id'];
        }

        // Amount validation
        if (!empty($data['amount'])) {
            $amount = str_replace(['$', ','], '', $data['amount']);
            if (!is_numeric($amount) || $amount < 0) {
                $errors['amount'] = 'Amount must be a valid number';
            } else {
                $cleanData['amount'] = (float)$amount;
            }
        } else {
            $cleanData['amount'] = 0;
        }

        // Currency
        $cleanData['currency'] = $data['currency'] ?? 'USD';

        // Probability
        if (!empty($data['probability'])) {
            $probability = (int)$data['probability'];
            if ($probability < 0 || $probability > 100) {
                $errors['probability'] = 'Probability must be between 0 and 100';
            } else {
                $cleanData['probability'] = $probability;
            }
        } else {
            $cleanData['probability'] = 0;
        }

        // Dates
        if (!empty($data['expected_close_date'])) {
            if (!strtotime($data['expected_close_date'])) {
                $errors['expected_close_date'] = 'Invalid date format';
            } else {
                $cleanData['expected_close_date'] = date('Y-m-d', strtotime($data['expected_close_date']));
            }
        }

        // Optional fields
        if (!empty($data['contact_id']) && is_numeric($data['contact_id'])) {
            $cleanData['contact_id'] = (int)$data['contact_id'];
        }

        if (!empty($data['organization_id']) && is_numeric($data['organization_id'])) {
            $cleanData['organization_id'] = (int)$data['organization_id'];
        }

        if (!empty($data['owner_id']) && is_numeric($data['owner_id'])) {
            $cleanData['owner_id'] = (int)$data['owner_id'];
        }

        // Status
        $validStatuses = ['open', 'won', 'lost'];
        if (!empty($data['status']) && in_array($data['status'], $validStatuses)) {
            $cleanData['status'] = $data['status'];
        } else {
            $cleanData['status'] = 'open';
        }

        // Source
        if (!empty($data['source'])) {
            $cleanData['source'] = trim($data['source']);
        }

        // Description
        if (!empty($data['description'])) {
            $cleanData['description'] = trim($data['description']);
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
        
        if (!empty($_GET['stage_id']) && is_numeric($_GET['stage_id'])) {
            $filters['stage_id'] = (int)$_GET['stage_id'];
        }
        
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (!empty($_GET['amount_min'])) {
            $filters['amount_min'] = (float)str_replace(['$', ','], '', $_GET['amount_min']);
        }
        
        if (!empty($_GET['amount_max'])) {
            $filters['amount_max'] = (float)str_replace(['$', ','], '', $_GET['amount_max']);
        }
        
        if (!empty($_GET['expected_close_date_from'])) {
            $filters['expected_close_date_from'] = $_GET['expected_close_date_from'];
        }
        
        if (!empty($_GET['expected_close_date_to'])) {
            $filters['expected_close_date_to'] = $_GET['expected_close_date_to'];
        }
        
        if (!empty($_GET['source'])) {
            $filters['source'] = $_GET['source'];
        }
        
        if (!empty($_GET['sort_field'])) {
            $validSortFields = ['name', 'amount', 'expected_close_date', 'created_at', 'updated_at'];
            if (in_array($_GET['sort_field'], $validSortFields)) {
                $filters['sort_field'] = 'd.' . $_GET['sort_field'];
            }
        }
        
        if (!empty($_GET['sort_direction'])) {
            $filters['sort_direction'] = strtoupper($_GET['sort_direction']) === 'ASC' ? 'ASC' : 'DESC';
        }

        return $filters;
    }

    private function getTotalDealsCount($userId, $filters)
    {
        // This would typically be a separate method in the model
        return $this->dealModel->countActiveByUser($userId);
    }

    private function calculateWeightedPipelineValue($stages)
    {
        $total = 0;
        foreach ($stages as $stage) {
            // Get stage probability from database (this would be in the pipeline_stages table)
            $probability = $stage['probability'] ?? 50; // Default to 50% if not set
            $total += $stage['value'] * ($probability / 100);
        }
        return $total;
    }

    private function calculateAverageDealSize($stages)
    {
        $totalValue = array_sum(array_column($stages, 'value'));
        $totalCount = array_sum(array_column($stages, 'count'));
        return $totalCount > 0 ? $totalValue / $totalCount : 0;
    }

    private function canViewDeal($deal)
    {
        $userRole = $this->auth->getUserRole();
        $userId = $this->auth->getUserId();
        
        // Admin and manager can view all deals
        if (in_array($userRole, ['admin', 'manager'])) {
            return true;
        }
        
        // Users can view deals they own
        return $deal['owner_id'] == $userId;
    }

    private function canEditDeal($deal)
    {
        return $this->canViewDeal($deal);
    }

    private function canDeleteDeal($deal)
    {
        $userRole = $this->auth->getUserRole();
        $userId = $this->auth->getUserId();
        
        // Only admin and deal owner can delete
        if ($userRole === 'admin' || $deal['owner_id'] == $userId) {
            return true;
        }
        
        return false;
    }

    private function getDealSources()
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
            'inbound' => 'Inbound',
            'outbound' => 'Outbound',
            'other' => 'Other'
        ];
    }

    private function getNotesByDeal($dealId)
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
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Models\User;
use App\Models\Analytics;
use App\Models\Organization;
use App\Models\Task;

class DashboardController extends Controller
{
    private $contactModel;
    private $dealModel;
    private $activityModel;
    private $userModel;
    private $analytics;
    private $organizationModel;
    private $taskModel;

    public function __construct()
    {
        parent::__construct();
        $this->contactModel = new Contact();
        $this->dealModel = new Deal();
        $this->activityModel = new Activity();
        $this->userModel = new User();
        $this->analytics = new Analytics();
        $this->organizationModel = new Organization();
        $this->taskModel = new Task();
        
        // Ensure user is authenticated
        if (!$this->auth->isLoggedIn()) {
            $this->redirect('/login');
        }
    }

    public function index()
    {
        $userId = $this->auth->getUserId();
        $user = $this->userModel->findById($userId);
        
        // Check cache first for analytics data
        $cacheKey = "dashboard_main_{$userId}_" . date('Y-m-d-H');
        $cachedData = $this->analytics->getCachedAnalyticsData($cacheKey);
        
        if ($cachedData) {
            $analyticsData = $cachedData;
        } else {
            // Get comprehensive analytics data
            $dateFrom = date('Y-01-01'); // Beginning of current year
            $dateTo = date('Y-m-d'); // Today
            
            $params = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'user_id' => $user['role'] !== 'admin' ? $userId : null
            ];

            // Executive dashboard for overview
            $executiveData = $this->analytics->getExecutiveDashboard($params);
            
            // Personal/team sales analytics
            $salesData = $this->analytics->getSalesAnalytics($params);
            
            // Pipeline status
            $pipelineData = $this->analytics->getPipelineAnalytics($params);
            
            // Recent activity analytics
            $activityData = $this->analytics->getActivityAnalytics([
                'date_from' => date('Y-m-d', strtotime('-30 days')),
                'date_to' => $dateTo,
                'user_id' => $user['role'] !== 'admin' ? $userId : null
            ]);
            
            // Task productivity
            $productivityData = $this->analytics->getProductivityAnalytics([
                'date_from' => date('Y-m-d', strtotime('-30 days')),
                'date_to' => $dateTo,
                'user_id' => $user['role'] !== 'admin' ? $userId : null
            ]);

            $analyticsData = [
                'executive' => $executiveData,
                'sales' => $salesData,
                'pipeline' => $pipelineData,
                'activity' => $activityData,
                'productivity' => $productivityData
            ];

            // Cache the data for 1 hour
            $this->analytics->cacheAnalyticsData($cacheKey, $analyticsData, 1);
        }
        
        // Get dashboard statistics (original)
        $stats = $this->getDashboardStats($userId);
        
        // Get recent activities
        $recent_activities = $this->getRecentActivities($userId);
        
        // Get recent contacts
        $recent_contacts = $this->getRecentContacts($userId);
        
        // Get pipeline stages
        $pipeline_stages = $this->getPipelineStages($userId);
        
        // Get recent items for quick access
        $recentItems = $this->getRecentItemsForDashboard($userId);

        $this->view->render('dashboard/index', [
            'user' => $user,
            'stats' => $stats,
            'recent_activities' => $recent_activities,
            'recent_contacts' => $recent_contacts,
            'pipeline_stages' => $pipeline_stages,
            'analytics' => $analyticsData,
            'recent_items' => $recentItems,
            'page_title' => 'BizMi CRM Dashboard'
        ]);
    }

    private function getDashboardStats($userId)
    {
        $stats = [];
        
        // Total contacts
        $stats['total_contacts'] = $this->contactModel->countByUser($userId);
        
        // Active deals
        $stats['active_deals'] = $this->dealModel->countActiveByUser($userId);
        
        // Total revenue
        $stats['total_revenue'] = $this->dealModel->getTotalRevenueByUser($userId);
        
        // Total activities
        $stats['total_activities'] = $this->activityModel->countByUser($userId);
        
        // Growth percentages (compared to last month)
        $stats['contacts_growth'] = $this->calculateGrowthPercentage(
            $this->contactModel->countByUserInPeriod($userId, 'current_month'),
            $this->contactModel->countByUserInPeriod($userId, 'last_month')
        );
        
        $stats['deals_growth'] = $this->calculateGrowthPercentage(
            $this->dealModel->countActiveByUserInPeriod($userId, 'current_month'),
            $this->dealModel->countActiveByUserInPeriod($userId, 'last_month')
        );
        
        $stats['revenue_growth'] = $this->calculateGrowthPercentage(
            $this->dealModel->getRevenueByUserInPeriod($userId, 'current_month'),
            $this->dealModel->getRevenueByUserInPeriod($userId, 'last_month')
        );
        
        $stats['activities_growth'] = $this->calculateGrowthPercentage(
            $this->activityModel->countByUserInPeriod($userId, 'current_month'),
            $this->activityModel->countByUserInPeriod($userId, 'last_month')
        );

        return $stats;
    }

    private function getRecentActivities($userId, $limit = 10)
    {
        $activities = $this->activityModel->getRecentByUser($userId, $limit);
        
        // Format activities for display
        foreach ($activities as &$activity) {
            $activity['icon'] = $this->getActivityIcon($activity['type']);
            $activity['description'] = $this->formatActivityDescription($activity);
        }
        
        return $activities;
    }

    private function getRecentContacts($userId, $limit = 8)
    {
        return $this->contactModel->getRecentByUser($userId, $limit);
    }

    private function getPipelineStages($userId)
    {
        $stages = $this->dealModel->getPipelineStagesByUser($userId);
        $totalValue = array_sum(array_column($stages, 'value'));
        
        // Calculate percentages
        foreach ($stages as &$stage) {
            $stage['percentage'] = $totalValue > 0 ? ($stage['value'] / $totalValue) * 100 : 0;
        }
        
        return $stages;
    }

    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getActivityIcon($type)
    {
        $icons = [
            'call' => 'fa-phone',
            'email' => 'fa-envelope',
            'meeting' => 'fa-calendar',
            'task' => 'fa-check-square',
            'note' => 'fa-sticky-note',
            'deal_created' => 'fa-handshake',
            'deal_updated' => 'fa-edit',
            'contact_created' => 'fa-user-plus',
            'contact_updated' => 'fa-user-edit',
            'organization_created' => 'fa-building',
            'quote_sent' => 'fa-file-invoice-dollar'
        ];

        return $icons[$type] ?? 'fa-circle';
    }

    private function formatActivityDescription($activity)
    {
        $descriptions = [
            'call' => 'made a call to',
            'email' => 'sent an email to',
            'meeting' => 'had a meeting with',
            'task' => 'completed task:',
            'note' => 'added a note about',
            'deal_created' => 'created a new deal:',
            'deal_updated' => 'updated deal:',
            'contact_created' => 'created new contact:',
            'contact_updated' => 'updated contact:',
            'organization_created' => 'created new organization:',
            'quote_sent' => 'sent quote to'
        ];

        $description = $descriptions[$activity['type']] ?? 'performed activity on';
        
        if (!empty($activity['subject'])) {
            return $description . ' ' . htmlspecialchars($activity['subject']);
        }
        
        return $description . ' ' . htmlspecialchars($activity['description']);
    }

    public function getStats()
    {
        // API endpoint for real-time stats
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }

        $userId = $this->auth->getUserId();
        $stats = $this->getDashboardStats($userId);
        
        $this->jsonResponse(['stats' => $stats]);
    }

    public function getRecentActivity()
    {
        // API endpoint for recent activity
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }

        $userId = $this->auth->getUserId();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $activities = $this->getRecentActivities($userId, $limit);
        
        $this->jsonResponse(['activities' => $activities]);
    }

    public function search()
    {
        // Global search endpoint
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }

        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (strlen($query) < 2) {
            $this->jsonResponse(['error' => 'Query too short'], 400);
            return;
        }

        $userId = $this->auth->getUserId();
        
        $results = [
            'contacts' => $this->contactModel->search($query, $userId, 5),
            'deals' => $this->dealModel->search($query, $userId, 5),
            'organizations' => $this->contactModel->searchOrganizations($query, $userId, 5)
        ];
        
        $this->jsonResponse($results);
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

    /**
     * Sales analytics dashboard
     */
    public function sales()
    {
        $userId = $this->auth->getUserId();
        $user = $this->userModel->findById($userId);
        
        // Get filters from request
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $filterUserId = $_GET['user_id'] ?? ($user['role'] !== 'admin' ? $userId : null);
        $teamId = $_GET['team_id'] ?? null;
        $period = $_GET['period'] ?? 'monthly';

        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => $filterUserId,
            'team_id' => $teamId
        ];

        // Sales analytics
        $salesMetrics = $this->analytics->getSalesAnalytics($params);
        
        // Performance by period
        $performanceData = $this->analytics->getSalesPerformanceByPeriod($period, $params);
        
        // Pipeline analytics
        $pipelineData = $this->analytics->getPipelineAnalytics($params);
        
        // Sales forecast
        $forecastData = $this->analytics->getSalesForecast($params);
        
        // Team performance
        $teamData = $this->analytics->getTeamPerformance($params);
        
        // Conversion funnel
        $funnelData = $this->analytics->getConversionFunnel($params);

        // Get users for filter dropdown
        $users = $this->userModel->findAll(['status' => 'active'], ['first_name', 'last_name']);

        $this->view->render('dashboard/sales', [
            'user' => $user,
            'sales_metrics' => $salesMetrics,
            'performance_data' => $performanceData,
            'pipeline_data' => $pipelineData,
            'forecast_data' => $forecastData,
            'team_data' => $teamData,
            'funnel_data' => $funnelData,
            'users' => $users,
            'filters' => $params,
            'period' => $period,
            'page_title' => 'Sales Analytics'
        ]);
    }

    /**
     * Team performance analytics
     */
    public function team()
    {
        $userId = $this->auth->getUserId();
        $user = $this->userModel->findById($userId);
        
        // Only admin and managers can view team analytics
        if (!in_array($user['role'], ['admin', 'manager'])) {
            $this->redirect('/dashboard?error=access_denied');
        }

        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        // Team performance
        $teamPerformance = $this->analytics->getTeamPerformance($params);
        
        // Activity analytics by user
        $activityData = $this->analytics->getActivityAnalytics($params);
        
        // Productivity metrics
        $productivityData = $this->analytics->getProductivityAnalytics($params);

        $this->view->render('dashboard/team', [
            'user' => $user,
            'team_performance' => $teamPerformance,
            'activity_data' => $activityData,
            'productivity_data' => $productivityData,
            'filters' => $params,
            'page_title' => 'Team Performance'
        ]);
    }

    /**
     * Customer analytics dashboard
     */
    public function customers()
    {
        $userId = $this->auth->getUserId();
        $user = $this->userModel->findById($userId);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        // Customer analytics
        $customerData = $this->analytics->getCustomerAnalytics($params);
        
        // Pipeline for customer deals
        $pipelineData = $this->analytics->getPipelineAnalytics($params);

        $this->view->render('dashboard/customers', [
            'user' => $user,
            'customer_data' => $customerData,
            'pipeline_data' => $pipelineData,
            'filters' => $params,
            'page_title' => 'Customer Analytics'
        ]);
    }

    /**
     * API endpoint for chart data
     */
    public function apiChartData()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }

        $chartType = $_GET['chart'] ?? 'sales_performance';
        $period = $_GET['period'] ?? 'monthly';
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $filterUserId = $_GET['user_id'] ?? null;
        
        $userId = $this->auth->getUserId();
        $user = $this->userModel->findById($userId);
        
        // Non-admin users can only see their own data
        if ($user['role'] !== 'admin' && $filterUserId && $filterUserId != $userId) {
            $filterUserId = $userId;
        }

        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => $filterUserId
        ];

        $data = [];

        switch ($chartType) {
            case 'sales_performance':
                $performanceData = $this->analytics->getSalesPerformanceByPeriod($period, $params);
                $data = [
                    'labels' => array_column($performanceData, 'period'),
                    'datasets' => [
                        [
                            'label' => 'Revenue',
                            'data' => array_column($performanceData, 'total_revenue'),
                            'backgroundColor' => '#9CAF88',
                            'borderColor' => '#8B9D7B'
                        ],
                        [
                            'label' => 'Deals Closed',
                            'data' => array_column($performanceData, 'deals_closed'),
                            'backgroundColor' => '#C8A882',
                            'borderColor' => '#B8977A'
                        ]
                    ]
                ];
                break;

            case 'pipeline_stages':
                $pipelineData = $this->analytics->getPipelineAnalytics($params);
                $data = [
                    'labels' => array_column($pipelineData, 'stage_name'),
                    'datasets' => [
                        [
                            'label' => 'Deal Count',
                            'data' => array_column($pipelineData, 'deal_count'),
                            'backgroundColor' => [
                                '#9CAF88', '#C8A882', '#8B7B6B', '#F5F2E8', 
                                '#A8B99C', '#D4B894', '#978D7E', '#F0EDE5'
                            ]
                        ]
                    ]
                ];
                break;

            case 'conversion_funnel':
                $funnelData = $this->analytics->getConversionFunnel($params);
                $data = [
                    'labels' => array_column($funnelData, 'stage'),
                    'datasets' => [
                        [
                            'label' => 'Count',
                            'data' => array_column($funnelData, 'count'),
                            'backgroundColor' => '#9CAF88'
                        ]
                    ]
                ];
                break;

            case 'revenue_trend':
                $executiveData = $this->analytics->getExecutiveDashboard($params);
                $revenueTrend = $executiveData['revenue_trend'] ?? [];
                $data = [
                    'labels' => array_column($revenueTrend, 'month'),
                    'datasets' => [
                        [
                            'label' => 'Monthly Revenue',
                            'data' => array_column($revenueTrend, 'revenue'),
                            'backgroundColor' => '#9CAF88',
                            'borderColor' => '#8B9D7B',
                            'tension' => 0.4
                        ]
                    ]
                ];
                break;

            case 'activity_trend':
                $activityData = $this->analytics->getActivityAnalytics($params);
                $trend = $activityData['trend'] ?? [];
                $data = [
                    'labels' => array_column($trend, 'activity_date'),
                    'datasets' => [
                        [
                            'label' => 'Daily Activities',
                            'data' => array_column($trend, 'activity_count'),
                            'backgroundColor' => '#C8A882',
                            'borderColor' => '#B8977A'
                        ]
                    ]
                ];
                break;

            default:
                $data = ['error' => 'Invalid chart type'];
        }

        $this->jsonResponse($data);
    }

    /**
     * API endpoint for KPI widgets
     */
    public function apiKpiData()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }

        $userId = $this->auth->getUserId();
        $user = $this->userModel->findById($userId);
        
        $kpiType = $_GET['kpi'] ?? 'sales_summary';
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $filterUserId = $user['role'] !== 'admin' ? $userId : null;

        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => $filterUserId
        ];

        $data = [];

        switch ($kpiType) {
            case 'sales_summary':
                $salesData = $this->analytics->getSalesAnalytics($params);
                $data = [
                    'total_revenue' => number_format($salesData['total_revenue'], 2),
                    'pipeline_value' => number_format($salesData['pipeline_value'], 2),
                    'win_rate' => $salesData['win_rate'] . '%',
                    'avg_deal_size' => number_format($salesData['avg_deal_size'], 2),
                    'deals_won' => $salesData['won_deals'],
                    'open_deals' => $salesData['open_deals']
                ];
                break;

            case 'activity_summary':
                $activityData = $this->analytics->getActivityAnalytics($params);
                $data = $activityData['summary'];
                break;

            case 'productivity_summary':
                $productivityData = $this->analytics->getProductivityAnalytics($params);
                $data = $productivityData['summary'];
                break;

            default:
                $data = ['error' => 'Invalid KPI type'];
        }

        $this->jsonResponse($data);
    }

    /**
     * Get recent items for dashboard quick access
     */
    private function getRecentItemsForDashboard($userId)
    {
        // Recent deals
        $recentDeals = $this->dealModel->findAll(
            ['owner_id' => $userId],
            [],
            'created_at DESC',
            5
        );

        // Recent contacts
        $recentContacts = $this->contactModel->findAll(
            ['assigned_user_id' => $userId],
            [],
            'created_at DESC',
            5
        );

        // Recent tasks
        $recentTasks = $this->taskModel->findAll(
            ['assigned_to' => $userId, 'status' => ['pending', 'in_progress']],
            [],
            'created_at DESC',
            5
        );

        return [
            'deals' => $recentDeals,
            'contacts' => $recentContacts,
            'tasks' => $recentTasks
        ];
    }

    /**
     * Export analytics data
     */
    public function export()
    {
        $userId = $this->auth->getUserId();
        $user = $this->userModel->findById($userId);
        
        $exportType = $_GET['type'] ?? 'sales';
        $format = $_GET['format'] ?? 'csv';
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => $user['role'] !== 'admin' ? $userId : null
        ];

        $data = [];
        $filename = '';

        switch ($exportType) {
            case 'sales':
                $data = $this->analytics->getSalesPerformanceByPeriod('monthly', $params);
                $filename = 'sales_performance_' . date('Y-m-d');
                break;

            case 'team':
                if (!in_array($user['role'], ['admin', 'manager'])) {
                    $this->redirect('/dashboard?error=access_denied');
                }
                $data = $this->analytics->getTeamPerformance($params);
                $filename = 'team_performance_' . date('Y-m-d');
                break;

            case 'pipeline':
                $data = $this->analytics->getPipelineAnalytics($params);
                $filename = 'pipeline_analytics_' . date('Y-m-d');
                break;
        }

        if ($format === 'csv') {
            $this->exportToCsv($data, $filename);
        } else {
            $this->exportToJson($data, $filename);
        }
    }

    /**
     * Export data to CSV
     */
    private function exportToCsv($data, $filename)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');

        if (!empty($data)) {
            // Write header
            fputcsv($output, array_keys($data[0]));

            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Export data to JSON
     */
    private function exportToJson($data, $filename)
    {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');

        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
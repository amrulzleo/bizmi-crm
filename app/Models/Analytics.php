<?php

namespace App\Models;

use PDO;

class Analytics extends BaseModel
{
    protected $table = 'analytics_cache';
    protected $primaryKey = 'id';

    /**
     * Get comprehensive sales analytics
     */
    public function getSalesAnalytics($params = [])
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        $userId = $params['user_id'] ?? null;
        $teamId = $params['team_id'] ?? null;

        $userFilter = '';
        $bindings = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        if ($userId) {
            $userFilter = ' AND d.owner_id = :user_id';
            $bindings['user_id'] = $userId;
        } elseif ($teamId) {
            $userFilter = ' AND d.owner_id IN (SELECT id FROM users WHERE team_id = :team_id)';
            $bindings['team_id'] = $teamId;
        }

        // Overall sales metrics
        $salesQuery = "
            SELECT 
                COUNT(*) as total_deals,
                COUNT(CASE WHEN d.status = 'open' THEN 1 END) as open_deals,
                COUNT(CASE WHEN d.status IN ('won', 'closed_won') THEN 1 END) as won_deals,
                COUNT(CASE WHEN d.status = 'lost' THEN 1 END) as lost_deals,
                SUM(CASE WHEN d.status IN ('won', 'closed_won') THEN d.amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN d.status = 'open' THEN d.amount ELSE 0 END) as pipeline_value,
                AVG(CASE WHEN d.status IN ('won', 'closed_won') THEN d.amount ELSE NULL END) as avg_deal_size,
                AVG(CASE WHEN d.status IN ('won', 'closed_won') AND d.created_at IS NOT NULL AND d.close_date IS NOT NULL 
                    THEN DATEDIFF(d.close_date, d.created_at) ELSE NULL END) as avg_sales_cycle,
                SUM(CASE WHEN d.status IN ('won', 'closed_won') AND DATE(d.close_date) = CURDATE() THEN d.amount ELSE 0 END) as today_revenue,
                SUM(CASE WHEN d.status IN ('won', 'closed_won') AND YEARWEEK(d.close_date, 1) = YEARWEEK(CURDATE(), 1) THEN d.amount ELSE 0 END) as this_week_revenue,
                SUM(CASE WHEN d.status IN ('won', 'closed_won') AND YEAR(d.close_date) = YEAR(CURDATE()) AND MONTH(d.close_date) = MONTH(CURDATE()) THEN d.amount ELSE 0 END) as this_month_revenue
            FROM deals d
            WHERE DATE(d.created_at) BETWEEN :date_from AND :date_to
            $userFilter
        ";

        $salesMetrics = $this->db->fetch($salesQuery, $bindings);

        // Calculate win rate
        if ($salesMetrics['total_deals'] > 0) {
            $salesMetrics['win_rate'] = round(($salesMetrics['won_deals'] / $salesMetrics['total_deals']) * 100, 1);
        } else {
            $salesMetrics['win_rate'] = 0;
        }

        return $salesMetrics;
    }

    /**
     * Get sales performance by period (daily, weekly, monthly)
     */
    public function getSalesPerformanceByPeriod($period = 'monthly', $params = [])
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        $userId = $params['user_id'] ?? null;

        $userFilter = '';
        $bindings = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        if ($userId) {
            $userFilter = ' AND d.owner_id = :user_id';
            $bindings['user_id'] = $userId;
        }

        $dateGroupBy = match($period) {
            'daily' => 'DATE(d.close_date)',
            'weekly' => 'YEARWEEK(d.close_date, 1)',
            'monthly' => 'DATE_FORMAT(d.close_date, "%Y-%m")',
            'quarterly' => 'CONCAT(YEAR(d.close_date), "-Q", QUARTER(d.close_date))',
            default => 'DATE_FORMAT(d.close_date, "%Y-%m")'
        };

        $query = "
            SELECT 
                $dateGroupBy as period,
                COUNT(*) as deals_closed,
                SUM(d.amount) as total_revenue,
                AVG(d.amount) as avg_deal_size,
                COUNT(CASE WHEN d.status IN ('won', 'closed_won') THEN 1 END) as won_deals,
                COUNT(CASE WHEN d.status = 'lost' THEN 1 END) as lost_deals
            FROM deals d
            WHERE d.close_date IS NOT NULL
              AND DATE(d.close_date) BETWEEN :date_from AND :date_to
              $userFilter
            GROUP BY $dateGroupBy
            ORDER BY period ASC
        ";

        $results = $this->db->fetchAll($query, $bindings);

        // Calculate win rates for each period
        foreach ($results as &$result) {
            $totalDeals = $result['won_deals'] + $result['lost_deals'];
            $result['win_rate'] = $totalDeals > 0 ? round(($result['won_deals'] / $totalDeals) * 100, 1) : 0;
        }

        return $results;
    }

    /**
     * Get pipeline analytics by stage
     */
    public function getPipelineAnalytics($params = [])
    {
        $userId = $params['user_id'] ?? null;
        $userFilter = '';
        $bindings = [];

        if ($userId) {
            $userFilter = ' AND d.owner_id = :user_id';
            $bindings['user_id'] = $userId;
        }

        $query = "
            SELECT 
                ps.id,
                ps.name as stage_name,
                ps.probability,
                ps.sort_order,
                COUNT(d.id) as deal_count,
                SUM(d.amount) as total_value,
                AVG(d.amount) as avg_deal_size,
                SUM(d.amount * (ps.probability / 100)) as weighted_value,
                AVG(DATEDIFF(CURDATE(), d.created_at)) as avg_days_in_stage
            FROM pipeline_stages ps
            LEFT JOIN deals d ON ps.id = d.stage_id AND d.status = 'open' $userFilter
            GROUP BY ps.id, ps.name, ps.probability, ps.sort_order
            ORDER BY ps.sort_order ASC
        ";

        return $this->db->fetchAll($query, $bindings);
    }

    /**
     * Get sales forecasting data
     */
    public function getSalesForecast($params = [])
    {
        $forecastPeriod = $params['period'] ?? 'quarterly';
        $userId = $params['user_id'] ?? null;

        $userFilter = '';
        $bindings = [];

        if ($userId) {
            $userFilter = ' AND d.owner_id = :user_id';
            $bindings['user_id'] = $userId;
        }

        // Current quarter forecast
        $currentQuarter = 'Q' . ceil(date('n') / 3);
        $currentYear = date('Y');
        
        $query = "
            SELECT 
                SUM(d.amount * (ps.probability / 100)) as weighted_forecast,
                SUM(CASE WHEN ps.probability >= 75 THEN d.amount ELSE 0 END) as best_case,
                SUM(CASE WHEN ps.probability >= 50 THEN d.amount ELSE 0 END) as likely_case,
                SUM(CASE WHEN ps.probability >= 25 THEN d.amount ELSE 0 END) as worst_case,
                COUNT(d.id) as total_opportunities
            FROM deals d
            JOIN pipeline_stages ps ON d.stage_id = ps.id
            WHERE d.status = 'open'
              AND (d.expected_close_date IS NULL OR YEAR(d.expected_close_date) = :current_year)
              $userFilter
        ";

        $bindings['current_year'] = $currentYear;
        $forecast = $this->db->fetch($query, $bindings);

        // Monthly breakdown for current quarter
        $monthlyQuery = "
            SELECT 
                DATE_FORMAT(COALESCE(d.expected_close_date, DATE_ADD(CURDATE(), INTERVAL 30 DAY)), '%Y-%m') as month,
                SUM(d.amount * (ps.probability / 100)) as weighted_forecast,
                COUNT(d.id) as deal_count
            FROM deals d
            JOIN pipeline_stages ps ON d.stage_id = ps.id
            WHERE d.status = 'open'
              $userFilter
            GROUP BY month
            ORDER BY month ASC
            LIMIT 6
        ";

        $monthlyForecast = $this->db->fetchAll($monthlyQuery, $bindings);

        return [
            'summary' => $forecast,
            'monthly_breakdown' => $monthlyForecast
        ];
    }

    /**
     * Get team performance analytics
     */
    public function getTeamPerformance($params = [])
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        
        $query = "
            SELECT 
                u.id,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                u.email,
                COUNT(d.id) as total_deals,
                COUNT(CASE WHEN d.status IN ('won', 'closed_won') THEN 1 END) as won_deals,
                COUNT(CASE WHEN d.status = 'lost' THEN 1 END) as lost_deals,
                COUNT(CASE WHEN d.status = 'open' THEN 1 END) as open_deals,
                SUM(CASE WHEN d.status IN ('won', 'closed_won') THEN d.amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN d.status IN ('won', 'closed_won') THEN d.amount ELSE NULL END) as avg_deal_size,
                AVG(CASE WHEN d.status IN ('won', 'closed_won') AND d.created_at IS NOT NULL AND d.close_date IS NOT NULL 
                    THEN DATEDIFF(d.close_date, d.created_at) ELSE NULL END) as avg_sales_cycle,
                COUNT(CASE WHEN d.created_at >= :date_from AND d.created_at <= :date_to THEN 1 END) as deals_created,
                COUNT(c.id) as total_contacts,
                COUNT(o.id) as total_organizations,
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks
            FROM users u
            LEFT JOIN deals d ON u.id = d.owner_id
            LEFT JOIN contacts c ON u.id = c.assigned_user_id
            LEFT JOIN organizations o ON u.id = o.assigned_user_id
            LEFT JOIN tasks t ON u.id = t.assigned_to
            WHERE u.status = 'active'
            GROUP BY u.id, u.first_name, u.last_name, u.email
            ORDER BY total_revenue DESC
        ";

        $results = $this->db->fetchAll($query, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        // Calculate performance metrics
        foreach ($results as &$result) {
            // Win rate
            $totalClosedDeals = $result['won_deals'] + $result['lost_deals'];
            $result['win_rate'] = $totalClosedDeals > 0 ? round(($result['won_deals'] / $totalClosedDeals) * 100, 1) : 0;
            
            // Task completion rate
            $result['task_completion_rate'] = $result['total_tasks'] > 0 ? round(($result['completed_tasks'] / $result['total_tasks']) * 100, 1) : 0;
            
            // Activity score (weighted combination of metrics)
            $result['activity_score'] = (
                ($result['deals_created'] * 10) +
                ($result['won_deals'] * 25) +
                ($result['total_contacts'] * 2) +
                ($result['completed_tasks'] * 5)
            );
        }

        return $results;
    }

    /**
     * Get conversion funnel analytics
     */
    public function getConversionFunnel($params = [])
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        $userId = $params['user_id'] ?? null;

        $userFilter = '';
        $bindings = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        if ($userId) {
            $userFilter = ' AND assigned_user_id = :user_id';
            $bindings['user_id'] = $userId;
        }

        $leadUserFilter = str_replace('assigned_user_id', 'l.assigned_user_id', $userFilter);
        $contactUserFilter = str_replace('assigned_user_id', 'c.assigned_user_id', $userFilter);
        $dealUserFilter = str_replace('assigned_user_id', 'd.owner_id', $userFilter);

        // Get funnel metrics
        $funnelQuery = "
            SELECT 
                'Leads' as stage,
                1 as stage_order,
                COUNT(*) as count,
                0 as conversion_rate
            FROM leads l
            WHERE DATE(l.created_at) BETWEEN :date_from AND :date_to
            $leadUserFilter
            
            UNION ALL
            
            SELECT 
                'Contacts' as stage,
                2 as stage_order,
                COUNT(*) as count,
                0 as conversion_rate
            FROM contacts c
            WHERE DATE(c.created_at) BETWEEN :date_from AND :date_to
            $contactUserFilter
            
            UNION ALL
            
            SELECT 
                'Opportunities' as stage,
                3 as stage_order,
                COUNT(*) as count,
                0 as conversion_rate
            FROM deals d
            WHERE DATE(d.created_at) BETWEEN :date_from AND :date_to
            $dealUserFilter
            
            UNION ALL
            
            SELECT 
                'Customers' as stage,
                4 as stage_order,
                COUNT(*) as count,
                0 as conversion_rate
            FROM deals d
            WHERE d.status IN ('won', 'closed_won')
              AND DATE(d.close_date) BETWEEN :date_from AND :date_to
            $dealUserFilter
            
            ORDER BY stage_order
        ";

        $funnel = $this->db->fetchAll($funnelQuery, $bindings);

        // Calculate conversion rates
        for ($i = 1; $i < count($funnel); $i++) {
            if ($funnel[$i-1]['count'] > 0) {
                $funnel[$i]['conversion_rate'] = round(($funnel[$i]['count'] / $funnel[$i-1]['count']) * 100, 1);
            }
        }

        return $funnel;
    }

    /**
     * Get activity analytics
     */
    public function getActivityAnalytics($params = [])
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        $userId = $params['user_id'] ?? null;

        $userFilter = '';
        $bindings = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        if ($userId) {
            $userFilter = ' AND a.user_id = :user_id';
            $bindings['user_id'] = $userId;
        }

        // Activity summary
        $activityQuery = "
            SELECT 
                COUNT(*) as total_activities,
                COUNT(CASE WHEN a.action LIKE '%created%' THEN 1 END) as creates,
                COUNT(CASE WHEN a.action LIKE '%updated%' THEN 1 END) as updates,
                COUNT(CASE WHEN a.action LIKE '%deleted%' THEN 1 END) as deletes,
                COUNT(CASE WHEN a.action LIKE '%email%' THEN 1 END) as emails,
                COUNT(CASE WHEN a.action LIKE '%call%' THEN 1 END) as calls,
                COUNT(CASE WHEN a.action LIKE '%meeting%' THEN 1 END) as meetings,
                COUNT(DISTINCT a.user_id) as active_users,
                COUNT(DISTINCT DATE(a.created_at)) as active_days
            FROM activities a
            WHERE DATE(a.created_at) BETWEEN :date_from AND :date_to
            $userFilter
        ";

        $activitySummary = $this->db->fetch($activityQuery, $bindings);

        // Activity by type
        $activityByTypeQuery = "
            SELECT 
                a.entity_type,
                COUNT(*) as count,
                COUNT(DISTINCT a.entity_id) as unique_entities
            FROM activities a
            WHERE DATE(a.created_at) BETWEEN :date_from AND :date_to
            $userFilter
            GROUP BY a.entity_type
            ORDER BY count DESC
        ";

        $activityByType = $this->db->fetchAll($activityByTypeQuery, $bindings);

        // Daily activity trend
        $activityTrendQuery = "
            SELECT 
                DATE(a.created_at) as activity_date,
                COUNT(*) as activity_count,
                COUNT(DISTINCT a.user_id) as active_users
            FROM activities a
            WHERE DATE(a.created_at) BETWEEN :date_from AND :date_to
            $userFilter
            GROUP BY DATE(a.created_at)
            ORDER BY activity_date ASC
        ";

        $activityTrend = $this->db->fetchAll($activityTrendQuery, $bindings);

        return [
            'summary' => $activitySummary,
            'by_type' => $activityByType,
            'trend' => $activityTrend
        ];
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics($params = [])
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');

        // Customer acquisition metrics
        $acquisitionQuery = "
            SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN DATE(o.created_at) BETWEEN :date_from AND :date_to THEN 1 END) as new_customers,
                COUNT(CASE WHEN o.type = 'customer' AND o.status = 'active' THEN 1 END) as active_customers,
                COUNT(CASE WHEN o.status = 'former_customer' THEN 1 END) as churned_customers,
                AVG(o.annual_revenue) as avg_customer_value,
                SUM(CASE WHEN o.type = 'customer' THEN 
                    (SELECT SUM(amount) FROM deals WHERE organization_id = o.id AND status IN ('won', 'closed_won'))
                    ELSE 0 END) as total_customer_revenue
            FROM organizations o
            WHERE o.type IN ('customer', 'former_customer')
        ";

        $acquisition = $this->db->fetch($acquisitionQuery, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        // Customer retention analysis
        $retentionQuery = "
            SELECT 
                o.industry,
                COUNT(*) as customer_count,
                AVG(DATEDIFF(CURDATE(), o.created_at)) as avg_customer_age_days,
                COUNT(CASE WHEN o.status = 'active' THEN 1 END) as retained_customers,
                SUM(CASE WHEN o.type = 'customer' THEN 
                    (SELECT SUM(amount) FROM deals WHERE organization_id = o.id AND status IN ('won', 'closed_won'))
                    ELSE 0 END) as industry_revenue
            FROM organizations o
            WHERE o.type = 'customer'
            GROUP BY o.industry
            ORDER BY customer_count DESC
        ";

        $retention = $this->db->fetchAll($retentionQuery);

        // Calculate retention rates
        foreach ($retention as &$industry) {
            $industry['retention_rate'] = $industry['customer_count'] > 0 ? 
                round(($industry['retained_customers'] / $industry['customer_count']) * 100, 1) : 0;
        }

        return [
            'acquisition' => $acquisition,
            'retention_by_industry' => $retention
        ];
    }

    /**
     * Get task and productivity analytics
     */
    public function getProductivityAnalytics($params = [])
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        $userId = $params['user_id'] ?? null;

        $userFilter = '';
        $bindings = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        if ($userId) {
            $userFilter = ' AND t.assigned_to = :user_id';
            $bindings['user_id'] = $userId;
        }

        // Task completion metrics
        $taskQuery = "
            SELECT 
                COUNT(*) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                COUNT(CASE WHEN t.status = 'pending' THEN 1 END) as pending_tasks,
                COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as in_progress_tasks,
                COUNT(CASE WHEN t.due_date < CURDATE() AND t.status NOT IN ('completed', 'cancelled') THEN 1 END) as overdue_tasks,
                AVG(CASE WHEN t.status = 'completed' AND t.actual_hours > 0 THEN t.actual_hours ELSE NULL END) as avg_completion_time,
                COUNT(CASE WHEN t.status = 'completed' AND t.completion_date <= t.due_date THEN 1 END) as on_time_completions
            FROM tasks t
            WHERE DATE(t.created_at) BETWEEN :date_from AND :date_to
            $userFilter
        ";

        $taskMetrics = $this->db->fetch($taskQuery, $bindings);

        // Calculate completion and on-time rates
        if ($taskMetrics['total_tasks'] > 0) {
            $taskMetrics['completion_rate'] = round(($taskMetrics['completed_tasks'] / $taskMetrics['total_tasks']) * 100, 1);
        } else {
            $taskMetrics['completion_rate'] = 0;
        }

        if ($taskMetrics['completed_tasks'] > 0) {
            $taskMetrics['on_time_rate'] = round(($taskMetrics['on_time_completions'] / $taskMetrics['completed_tasks']) * 100, 1);
        } else {
            $taskMetrics['on_time_rate'] = 0;
        }

        // Task distribution by category
        $categoryQuery = "
            SELECT 
                t.category,
                COUNT(*) as task_count,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_count,
                AVG(CASE WHEN t.status = 'completed' AND t.actual_hours > 0 THEN t.actual_hours ELSE NULL END) as avg_time
            FROM tasks t
            WHERE DATE(t.created_at) BETWEEN :date_from AND :date_to
            $userFilter
            GROUP BY t.category
            ORDER BY task_count DESC
        ";

        $categoryDistribution = $this->db->fetchAll($categoryQuery, $bindings);

        return [
            'summary' => $taskMetrics,
            'category_distribution' => $categoryDistribution
        ];
    }

    /**
     * Generate executive dashboard summary
     */
    public function getExecutiveDashboard($params = [])
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');

        // Key business metrics
        $executiveQuery = "
            SELECT 
                (SELECT COUNT(*) FROM deals WHERE status = 'open') as open_opportunities,
                (SELECT SUM(amount) FROM deals WHERE status = 'open') as pipeline_value,
                (SELECT SUM(amount) FROM deals WHERE status IN ('won', 'closed_won') AND DATE(close_date) BETWEEN :date_from AND :date_to) as period_revenue,
                (SELECT COUNT(*) FROM deals WHERE status IN ('won', 'closed_won') AND DATE(close_date) BETWEEN :date_from AND :date_to) as deals_closed,
                (SELECT COUNT(*) FROM contacts WHERE DATE(created_at) BETWEEN :date_from AND :date_to) as new_contacts,
                (SELECT COUNT(*) FROM organizations WHERE type = 'customer') as total_customers,
                (SELECT COUNT(*) FROM organizations WHERE type = 'prospect') as total_prospects,
                (SELECT COUNT(*) FROM tasks WHERE status NOT IN ('completed', 'cancelled')) as active_tasks,
                (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users
        ";

        $metrics = $this->db->fetch($executiveQuery, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        // Revenue trend (last 12 months)
        $revenueTrendQuery = "
            SELECT 
                DATE_FORMAT(close_date, '%Y-%m') as month,
                SUM(amount) as revenue,
                COUNT(*) as deals_count
            FROM deals 
            WHERE status IN ('won', 'closed_won') 
              AND close_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(close_date, '%Y-%m')
            ORDER BY month ASC
        ";

        $revenueTrend = $this->db->fetchAll($revenueTrendQuery);

        // Top performing users
        $topUsersQuery = "
            SELECT 
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                COUNT(d.id) as deals_won,
                SUM(d.amount) as total_revenue
            FROM users u
            LEFT JOIN deals d ON u.id = d.owner_id AND d.status IN ('won', 'closed_won') 
                AND DATE(d.close_date) BETWEEN :date_from AND :date_to
            WHERE u.status = 'active'
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY total_revenue DESC
            LIMIT 5
        ";

        $topUsers = $this->db->fetchAll($topUsersQuery, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        return [
            'key_metrics' => $metrics,
            'revenue_trend' => $revenueTrend,
            'top_performers' => $topUsers
        ];
    }

    /**
     * Cache analytics data for performance
     */
    public function cacheAnalyticsData($cacheKey, $data, $expirationHours = 1)
    {
        $query = "
            INSERT INTO analytics_cache (cache_key, cache_data, expires_at, created_at)
            VALUES (:cache_key, :cache_data, DATE_ADD(NOW(), INTERVAL :expiration_hours HOUR), NOW())
            ON DUPLICATE KEY UPDATE 
                cache_data = VALUES(cache_data),
                expires_at = VALUES(expires_at),
                updated_at = NOW()
        ";

        return $this->db->execute($query, [
            'cache_key' => $cacheKey,
            'cache_data' => json_encode($data),
            'expiration_hours' => $expirationHours
        ]);
    }

    /**
     * Get cached analytics data
     */
    public function getCachedAnalyticsData($cacheKey)
    {
        $query = "
            SELECT cache_data 
            FROM analytics_cache 
            WHERE cache_key = :cache_key 
              AND expires_at > NOW()
        ";

        $result = $this->db->fetch($query, ['cache_key' => $cacheKey]);

        return $result ? json_decode($result['cache_data'], true) : null;
    }
}
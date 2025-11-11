<?php
/**
 * BizMi CRM View Engine
 * 
 * Handles template rendering and view management
 * Created by: Amrullah Khan
 * Email: amrulzlionheart@gmail.com
 * Date: November 11, 2025
 * Version: 1.0.0
 */

class View
{
    private $viewsPath;
    private $data = [];
    private $layout = 'layout';
    
    public function __construct()
    {
        $this->viewsPath = BIZMI_APP . '/Views';
    }
    
    /**
     * Set layout template
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * Set view data
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Render view
     */
    public function render($view, $data = [], $layout = null)
    {
        // Merge data
        $this->data = array_merge($this->data, $data);
        
        // Use specified layout or default
        $layoutFile = $layout ?: $this->layout;
        
        // Get view content
        $content = $this->getViewContent($view);
        
        // If no layout, return content directly
        if ($layoutFile === false || $layoutFile === null) {
            echo $content;
            return;
        }
        
        // Render with layout
        $this->data['content'] = $content;
        $layoutContent = $this->getViewContent('layouts/' . $layoutFile);
        echo $layoutContent;
    }
    
    /**
     * Get view content
     */
    private function getViewContent($view)
    {
        $viewFile = $this->viewsPath . '/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }
        
        // Extract data variables
        extract($this->data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        try {
            include $viewFile;
            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }
    
    /**
     * Include partial view
     */
    public function partial($view, $data = [])
    {
        $partialData = array_merge($this->data, $data);
        extract($partialData, EXTR_SKIP);
        
        $viewFile = $this->viewsPath . '/partials/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "<!-- Partial not found: {$view} -->";
        }
    }
    
    /**
     * Escape HTML entities
     */
    public function escape($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Format date
     */
    public function formatDate($date, $format = 'Y-m-d')
    {
        if (empty($date)) return '';
        
        if ($date instanceof DateTime) {
            return $date->format($format);
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
    
    /**
     * Format currency
     */
    public function formatCurrency($amount, $currency = 'USD')
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥'
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2);
    }
    
    /**
     * Generate URL
     */
    public function url($path = '')
    {
        $baseUrl = rtrim(BIZMI_BASE_URL, '/');
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }
    
    /**
     * Generate asset URL
     */
    public function asset($path = '')
    {
        $assetsUrl = rtrim(BIZMI_ASSETS_URL, '/');
        $path = ltrim($path, '/');
        return $assetsUrl . '/' . $path;
    }
    
    /**
     * Include CSS file
     */
    public function css($files)
    {
        $files = is_array($files) ? $files : [$files];
        
        foreach ($files as $file) {
            $url = $this->asset('css/' . $file . '.css');
            echo "<link rel=\"stylesheet\" href=\"{$url}\">\n";
        }
    }
    
    /**
     * Include JS file
     */
    public function js($files)
    {
        $files = is_array($files) ? $files : [$files];
        
        foreach ($files as $file) {
            $url = $this->asset('js/' . $file . '.js');
            echo "<script src=\"{$url}\"></script>\n";
        }
    }
    
    /**
     * Display flash message
     */
    public function flash($key = null)
    {
        if (!isset($_SESSION['flash'])) {
            return '';
        }
        
        if ($key) {
            $message = $_SESSION['flash'][$key] ?? '';
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        
        $messages = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $messages;
    }
    
    /**
     * Check if user is authenticated
     */
    public function auth()
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user
     */
    public function user()
    {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Generate CSRF token
     */
    public function csrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * CSRF token input field
     */
    public function csrfField()
    {
        $token = $this->csrfToken();
        return "<input type=\"hidden\" name=\"_token\" value=\"{$token}\">";
    }
    
    /**
     * Truncate text
     */
    public function truncate($text, $length = 100, $suffix = '...')
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Pluralize word
     */
    public function pluralize($count, $singular, $plural = null)
    {
        if ($plural === null) {
            $plural = $singular . 's';
        }
        
        return $count == 1 ? $singular : $plural;
    }
    
    /**
     * Format file size
     */
    public function formatFileSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Time ago format
     */
    public function timeAgo($datetime)
    {
        if (empty($datetime)) return '';
        
        $time = is_numeric($datetime) ? $datetime : strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
    
    /**
     * Check user permission
     */
    public function can($permission)
    {
        // TODO: Implement permission checking
        return true;
    }
    
    /**
     * Get configuration value
     */
    public function config($key, $default = null)
    {
        // TODO: Implement config retrieval
        return $default;
    }
}
?>
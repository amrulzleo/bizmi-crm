<?php
/**
 * BizMi CRM Base Controller
 * 
 * Base controller class for all controllers
 * Created by: Amrullah Khan
 * Email: amrulzlionheart@gmail.com
 * Date: November 11, 2025
 * Version: 1.0.0
 */

abstract class Controller
{
    protected $view;
    protected $db;
    protected $auth;
    protected $data = [];
    
    public function __construct()
    {
        $this->view = new View();
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        
        // Set common view data
        $this->data['user'] = Auth::user();
        $this->data['auth'] = Auth::check();
        $this->data['app_name'] = BIZMI_NAME;
        $this->data['app_version'] = BIZMI_VERSION;
    }
    
    /**
     * Set view data
     */
    protected function setData($key, $value = null)
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
    protected function render($view, $data = [], $layout = null)
    {
        $allData = array_merge($this->data, $data);
        $this->view->render($view, $allData, $layout);
    }
    
    /**
     * Render JSON response
     */
    protected function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect($url, $status = 302)
    {
        http_response_code($status);
        
        // If URL doesn't start with http, treat as internal
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = rtrim(BIZMI_BASE_URL, '/') . '/' . ltrim($url, '/');
        }
        
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Redirect back to previous page
     */
    protected function redirectBack()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? BIZMI_BASE_URL;
        $this->redirect($referer);
    }
    
    /**
     * Set flash message
     */
    protected function flash($type, $message)
    {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][$type] = $message;
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCSRF()
    {
        $token = $_POST['_token'] ?? $_GET['_token'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            http_response_code(419);
            $this->json(['error' => 'CSRF token mismatch'], 419);
            exit;
        }
    }
    
    /**
     * Validate request method
     */
    protected function validateMethod($method)
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        if (strtoupper($requestMethod) !== strtoupper($method)) {
            http_response_code(405);
            $this->json(['error' => 'Method not allowed'], 405);
            exit;
        }
    }
    
    /**
     * Get request input
     */
    protected function input($key = null, $default = null)
    {
        $input = array_merge($_GET, $_POST);
        
        // Handle JSON input
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = json_decode(file_get_contents('php://input'), true);
            if (is_array($jsonInput)) {
                $input = array_merge($input, $jsonInput);
            }
        }
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }
    
    /**
     * Validate input data
     */
    protected function validate($rules, $data = null)
    {
        if ($data === null) {
            $data = $this->input();
        }
        
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleList = is_string($rule) ? explode('|', $rule) : $rule;
            $value = $data[$field] ?? null;
            
            foreach ($ruleList as $singleRule) {
                $ruleParts = explode(':', $singleRule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                $error = $this->validateRule($field, $value, $ruleName, $ruleParam, $data);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate single rule
     */
    private function validateRule($field, $value, $rule, $param = null, $allData = [])
    {
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return ucfirst($field) . ' is required';
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ucfirst($field) . ' must be a valid email';
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < $param) {
                    return ucfirst($field) . " must be at least {$param} characters";
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > $param) {
                    return ucfirst($field) . " must not exceed {$param} characters";
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return ucfirst($field) . ' must be a number';
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($allData[$confirmField] ?? null)) {
                    return ucfirst($field) . ' confirmation does not match';
                }
                break;
                
            case 'unique':
                if (!empty($value)) {
                    list($table, $column) = explode(',', $param);
                    $count = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?",
                        [$value]
                    );
                    if ($count > 0) {
                        return ucfirst($field) . ' already exists';
                    }
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Sanitize input
     */
    protected function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Check if request is AJAX
     */
    protected function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Paginate results
     */
    protected function paginate($query, $params = [], $perPage = 20, $page = 1)
    {
        // Count total results
        $countQuery = "SELECT COUNT(*) FROM ($query) as count_table";
        $total = $this->db->fetchColumn($countQuery, $params);
        
        // Calculate pagination
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $totalPages = ceil($total / $perPage);
        
        // Get paginated results
        $paginatedQuery = "{$query} LIMIT {$perPage} OFFSET {$offset}";
        $results = $this->db->fetchAll($paginatedQuery, $params);
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null
            ]
        ];
    }
    
    /**
     * Log activity
     */
    protected function logActivity($action, $table, $recordId, $oldData = null, $newData = null)
    {
        try {
            $logData = [
                'user_id' => Auth::userId(),
                'action' => $action,
                'table_name' => $table,
                'record_id' => $recordId,
                'old_values' => $oldData ? json_encode($oldData) : null,
                'new_values' => $newData ? json_encode($newData) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('audit_log', $logData);
        } catch (Exception $e) {
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle file upload
     */
    protected function handleFileUpload($fieldName, $allowedTypes = [], $maxSize = null)
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        
        $file = $_FILES[$fieldName];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error');
        }
        
        // Validate file type
        if (!empty($allowedTypes)) {
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('File type not allowed');
            }
        }
        
        // Validate file size
        if ($maxSize && $file['size'] > $maxSize) {
            throw new Exception('File size exceeds limit');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $uploadPath = BIZMI_UPLOADS . '/' . $filename;
        
        // Create uploads directory if it doesn't exist
        if (!is_dir(BIZMI_UPLOADS)) {
            mkdir(BIZMI_UPLOADS, 0755, true);
        }
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        return [
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'path' => $uploadPath,
            'url' => BIZMI_UPLOADS_URL . '/' . $filename
        ];
    }
}
?>
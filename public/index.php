<?php
/**
 * BizMi CRM Application Bootstrap
 * 
 * Main entry point for the BizMi CRM system
 * Created by: Amrullah Khan
 * Email: amrulzlionheart@gmail.com
 * Date: November 11, 2025
 * Version: 1.0.0
 */

// Start output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Set timezone
date_default_timezone_set('UTC');

// Define directory constants
define('BIZMI_ROOT', dirname(__DIR__));
define('BIZMI_APP', BIZMI_ROOT . '/app');
define('BIZMI_CONFIG', BIZMI_ROOT . '/config');
define('BIZMI_PUBLIC', __DIR__);
define('BIZMI_ASSETS', BIZMI_PUBLIC . '/assets');
define('BIZMI_UPLOADS', BIZMI_PUBLIC . '/uploads');

// Define URL constants
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME'] ?? '');
define('BIZMI_BASE_URL', $protocol . '://' . $host . $scriptName);
define('BIZMI_ASSETS_URL', BIZMI_BASE_URL . '/assets');
define('BIZMI_UPLOADS_URL', BIZMI_BASE_URL . '/uploads');

// Check if installation is required
if (!file_exists(BIZMI_CONFIG . '/database.php') || !file_exists(BIZMI_CONFIG . '/app.php')) {
    // Redirect to installation
    $installPath = BIZMI_BASE_URL . '/install/';
    if (strpos($_SERVER['REQUEST_URI'], '/install/') === false) {
        header('Location: ' . $installPath);
        exit;
    }
}

// Load configuration files if they exist
if (file_exists(BIZMI_CONFIG . '/app.php')) {
    require_once BIZMI_CONFIG . '/app.php';
}

if (file_exists(BIZMI_CONFIG . '/database.php')) {
    require_once BIZMI_CONFIG . '/database.php';
}

// Define application constants
define('BIZMI_VERSION', '1.0.0');
define('BIZMI_NAME', 'BizMi CRM');
define('BIZMI_AUTHOR', 'Amrullah Khan');
define('BIZMI_EMAIL', 'amrulzlionheart@gmail.com');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloader for classes
spl_autoload_register(function ($className) {
    // Convert namespace to file path
    $className = str_replace('\\', '/', $className);
    
    // Look for class in app directory
    $classFile = BIZMI_APP . '/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return;
    }
    
    // Look for class in subdirectories
    $paths = [
        BIZMI_APP . '/Controllers/',
        BIZMI_APP . '/Models/',
        BIZMI_APP . '/Services/',
        BIZMI_APP . '/Core/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

try {
    // Load core classes
    require_once BIZMI_APP . '/Core/Router.php';
    require_once BIZMI_APP . '/Core/Database.php';
    require_once BIZMI_APP . '/Core/Auth.php';
    require_once BIZMI_APP . '/Core/View.php';
    require_once BIZMI_APP . '/Core/Controller.php';
    
    // Initialize router
    $router = new Router();
    
    // Get the requested URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    
    // Remove query string
    $requestUri = strtok($requestUri, '?');
    
    // Remove base path if exists
    $basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME'] ?? '');
    if ($basePath !== '/' && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }
    
    // Ensure URI starts with /
    if ($requestUri === '' || $requestUri[0] !== '/') {
        $requestUri = '/' . $requestUri;
    }
    
    // Handle the request
    $router->handleRequest($requestUri);
    
} catch (Exception $e) {
    // Log error
    error_log('[BizMi CRM Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    // Show error page
    http_response_code(500);
    
    // Check if we're in debug mode
    $debug = defined('BIZMI_DEBUG') && BIZMI_DEBUG === true;
    
    if ($debug) {
        echo '<h1>BizMi CRM Application Error</h1>';
        echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Application Error</h1>';
        echo '<p>Sorry, something went wrong. Please try again later.</p>';
        echo '<p>If the problem persists, please contact the administrator.</p>';
    }
}

// Flush output buffer
ob_end_flush();
?>
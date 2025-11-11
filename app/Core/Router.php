<?php
/**
 * BizMi CRM Router
 * 
 * Handles URL routing and request dispatching
 * Created by: Amrullah Khan
 * Email: amrulzlionheart@gmail.com
 * Date: November 11, 2025
 * Version: 1.0.0
 */

class Router
{
    private $routes = [];
    private $middlewares = [];
    
    public function __construct()
    {
        $this->setupDefaultRoutes();
    }
    
    /**
     * Add a GET route
     */
    public function get($path, $handler, $middleware = null)
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    /**
     * Add a POST route
     */
    public function post($path, $handler, $middleware = null)
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    /**
     * Add a PUT route
     */
    public function put($path, $handler, $middleware = null)
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }
    
    /**
     * Add a DELETE route
     */
    public function delete($path, $handler, $middleware = null)
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }
    
    /**
     * Add a route for any HTTP method
     */
    private function addRoute($method, $path, $handler, $middleware = null)
    {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $path);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    /**
     * Handle incoming request
     */
    public function handleRequest($uri)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Handle preflight OPTIONS requests for CORS
        if ($method === 'OPTIONS') {
            $this->handleCORS();
            return;
        }
        
        // Check if route exists
        $route = $this->findRoute($method, $uri);
        
        if ($route) {
            $this->executeRoute($route, $uri);
        } else {
            $this->handle404();
        }
    }
    
    /**
     * Find matching route
     */
    private function findRoute($method, $uri)
    {
        if (!isset($this->routes[$method])) {
            return null;
        }
        
        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $route['params'] = array_slice($matches, 1);
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Execute route handler
     */
    private function executeRoute($route, $uri)
    {
        try {
            // Run middleware if exists
            if ($route['middleware']) {
                $middlewareResult = $this->executeMiddleware($route['middleware']);
                if ($middlewareResult === false) {
                    return; // Middleware blocked execution
                }
            }
            
            // Parse handler
            if (is_string($route['handler'])) {
                if (strpos($route['handler'], '@') !== false) {
                    // Controller@method format
                    list($controllerName, $method) = explode('@', $route['handler']);
                    $this->executeController($controllerName, $method, $route['params']);
                } else {
                    // Function name
                    call_user_func($route['handler'], ...$route['params']);
                }
            } elseif (is_callable($route['handler'])) {
                // Anonymous function
                call_user_func($route['handler'], ...$route['params']);
            }
            
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Execute controller method
     */
    private function executeController($controllerName, $method, $params = [])
    {
        $controllerClass = $controllerName . 'Controller';
        $controllerFile = BIZMI_APP . '/Controllers/' . $controllerClass . '.php';
        
        if (!file_exists($controllerFile)) {
            throw new Exception("Controller file not found: $controllerFile");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerClass)) {
            throw new Exception("Controller class not found: $controllerClass");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            throw new Exception("Controller method not found: $controllerClass::$method");
        }
        
        call_user_func_array([$controller, $method], $params);
    }
    
    /**
     * Execute middleware
     */
    private function executeMiddleware($middleware)
    {
        if (is_string($middleware)) {
            $middlewareClass = $middleware . 'Middleware';
            $middlewareFile = BIZMI_APP . '/Middlewares/' . $middlewareClass . '.php';
            
            if (file_exists($middlewareFile)) {
                require_once $middlewareFile;
                $middlewareInstance = new $middlewareClass();
                return $middlewareInstance->handle();
            }
        } elseif (is_callable($middleware)) {
            return call_user_func($middleware);
        }
        
        return true;
    }
    
    /**
     * Setup default routes
     */
    private function setupDefaultRoutes()
    {
        // Dashboard
        $this->get('/', 'Dashboard@index', 'Auth');
        $this->get('/dashboard', 'Dashboard@index', 'Auth');
        
        // Authentication
        $this->get('/login', 'Auth@loginForm');
        $this->post('/login', 'Auth@login');
        $this->get('/logout', 'Auth@logout');
        $this->get('/register', 'Auth@registerForm');
        $this->post('/register', 'Auth@register');
        $this->get('/forgot-password', 'Auth@forgotPasswordForm');
        $this->post('/forgot-password', 'Auth@forgotPassword');
        $this->get('/reset-password/{token}', 'Auth@resetPasswordForm');
        $this->post('/reset-password', 'Auth@resetPassword');
        
        // Contacts
        $this->get('/contacts', 'Contact@index', 'Auth');
        $this->get('/contacts/create', 'Contact@create', 'Auth');
        $this->post('/contacts/store', 'Contact@store', 'Auth');
        $this->get('/contacts/{id}', 'Contact@show', 'Auth');
        $this->get('/contacts/{id}/edit', 'Contact@edit', 'Auth');
        $this->post('/contacts/{id}/update', 'Contact@update', 'Auth');
        $this->post('/contacts/{id}/delete', 'Contact@delete', 'Auth');
        
        // Organizations
        $this->get('/organizations', 'Organization@index', 'Auth');
        $this->get('/organizations/create', 'Organization@create', 'Auth');
        $this->post('/organizations/store', 'Organization@store', 'Auth');
        $this->get('/organizations/{id}', 'Organization@show', 'Auth');
        $this->get('/organizations/{id}/edit', 'Organization@edit', 'Auth');
        $this->post('/organizations/{id}/update', 'Organization@update', 'Auth');
        $this->post('/organizations/{id}/delete', 'Organization@delete', 'Auth');
        
        // Deals
        $this->get('/deals', 'Deal@index', 'Auth');
        $this->get('/deals/create', 'Deal@create', 'Auth');
        $this->post('/deals/store', 'Deal@store', 'Auth');
        $this->get('/deals/{id}', 'Deal@show', 'Auth');
        $this->get('/deals/{id}/edit', 'Deal@edit', 'Auth');
        $this->post('/deals/{id}/update', 'Deal@update', 'Auth');
        $this->post('/deals/{id}/delete', 'Deal@delete', 'Auth');
        
        // Activities
        $this->get('/activities', 'Activity@index', 'Auth');
        $this->get('/activities/create', 'Activity@create', 'Auth');
        $this->post('/activities/store', 'Activity@store', 'Auth');
        $this->get('/activities/{id}', 'Activity@show', 'Auth');
        $this->get('/activities/{id}/edit', 'Activity@edit', 'Auth');
        $this->post('/activities/{id}/update', 'Activity@update', 'Auth');
        $this->post('/activities/{id}/delete', 'Activity@delete', 'Auth');
        
        // API Routes
        $this->get('/api/contacts', 'API\\Contact@index', 'AuthAPI');
        $this->post('/api/contacts', 'API\\Contact@store', 'AuthAPI');
        $this->get('/api/contacts/{id}', 'API\\Contact@show', 'AuthAPI');
        $this->put('/api/contacts/{id}', 'API\\Contact@update', 'AuthAPI');
        $this->delete('/api/contacts/{id}', 'API\\Contact@delete', 'AuthAPI');
        
        // Settings
        $this->get('/settings', 'Setting@index', 'Auth');
        $this->post('/settings/update', 'Setting@update', 'Auth');
        
        // User Management
        $this->get('/users', 'User@index', 'Auth');
        $this->get('/users/create', 'User@create', 'Auth');
        $this->post('/users/store', 'User@store', 'Auth');
        $this->get('/users/{id}/edit', 'User@edit', 'Auth');
        $this->post('/users/{id}/update', 'User@update', 'Auth');
        $this->post('/users/{id}/delete', 'User@delete', 'Auth');
    }
    
    /**
     * Handle CORS preflight requests
     */
    private function handleCORS()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        http_response_code(200);
    }
    
    /**
     * Handle 404 errors
     */
    private function handle404()
    {
        http_response_code(404);
        
        // Check if request is for API
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Endpoint not found',
                'code' => 404
            ]);
            return;
        }
        
        // Show 404 page
        $view = new View();
        $view->render('errors/404', ['title' => '404 - Page Not Found']);
    }
    
    /**
     * Handle route errors
     */
    private function handleError($exception)
    {
        error_log('[BizMi CRM Router Error] ' . $exception->getMessage());
        
        http_response_code(500);
        
        // Check if request is for API
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error',
                'code' => 500
            ]);
            return;
        }
        
        // Show error page
        $view = new View();
        $view->render('errors/500', [
            'title' => '500 - Internal Server Error',
            'message' => defined('BIZMI_DEBUG') && BIZMI_DEBUG ? $exception->getMessage() : 'An error occurred'
        ]);
    }
}
?>
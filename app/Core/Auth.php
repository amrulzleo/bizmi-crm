<?php
/**
 * BizMi CRM Authentication System
 * 
 * Handles user authentication and authorization
 * Created by: Amrullah Khan
 * Email: amrulzlionheart@gmail.com
 * Date: November 11, 2025
 * Version: 1.0.0
 */

class Auth
{
    private static $instance = null;
    private $db;
    
    private function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get Auth instance (Singleton pattern)
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user data
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }
        
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Get current user ID
     */
    public static function userId()
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Attempt to log in user
     */
    public function login($email, $password, $remember = false)
    {
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if (!$this->verifyPassword($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'avatar' => $user['avatar']
        ];
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Handle remember me
        if ($remember) {
            $this->setRememberToken($user['id']);
        }
        
        return ['success' => true, 'message' => 'Login successful'];
    }
    
    /**
     * Log out user
     */
    public function logout()
    {
        // Clear remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            $this->clearRememberToken();
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
    }
    
    /**
     * Register new user
     */
    public function register($data)
    {
        // Validate data
        $validation = $this->validateRegistration($data);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Check if username already exists
        if ($this->usernameExists($data['username'])) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Hash password
        $passwordHash = $this->hashPassword($data['password']);
        
        // Generate email verification token
        $verificationToken = $this->generateToken();
        
        // Prepare user data
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => $data['role'] ?? 'user',
            'status' => 'pending',
            'email_verification_token' => $verificationToken,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $userId = $this->db->insert('users', $userData);
            
            // Send verification email
            $this->sendVerificationEmail($data['email'], $verificationToken);
            
            return [
                'success' => true, 
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Verify email with token
     */
    public function verifyEmail($token)
    {
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE email_verification_token = ? AND status = 'pending'",
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired verification token'];
        }
        
        // Update user status
        $this->db->update(
            'users',
            [
                'status' => 'active',
                'email_verified' => 1,
                'email_verification_token' => null
            ],
            'id = ?',
            [$user['id']]
        );
        
        return ['success' => true, 'message' => 'Email verified successfully'];
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($email)
    {
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email address not found'];
        }
        
        // Generate reset token
        $resetToken = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Update user with reset token
        $this->db->update(
            'users',
            [
                'password_reset_token' => $resetToken,
                'password_reset_expires' => $expiresAt
            ],
            'id = ?',
            [$user['id']]
        );
        
        // Send reset email
        $this->sendPasswordResetEmail($email, $resetToken);
        
        return ['success' => true, 'message' => 'Password reset email sent'];
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword)
    {
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()",
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        // Validate password
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }
        
        // Hash new password
        $passwordHash = $this->hashPassword($newPassword);
        
        // Update password and clear reset token
        $this->db->update(
            'users',
            [
                'password_hash' => $passwordHash,
                'password_reset_token' => null,
                'password_reset_expires' => null
            ],
            'id = ?',
            [$user['id']]
        );
        
        return ['success' => true, 'message' => 'Password reset successfully'];
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Verify current password
        if (!$this->verifyPassword($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters'];
        }
        
        // Hash new password
        $passwordHash = $this->hashPassword($newPassword);
        
        // Update password
        $this->db->update(
            'users',
            ['password_hash' => $passwordHash],
            'id = ?',
            [$userId]
        );
        
        return ['success' => true, 'message' => 'Password changed successfully'];
    }
    
    /**
     * Check user permission
     */
    public static function can($permission, $userId = null)
    {
        $userId = $userId ?: self::userId();
        
        if (!$userId) {
            return false;
        }
        
        $user = self::user();
        $role = $user['role'] ?? 'user';
        
        // Define role permissions
        $permissions = [
            'super_admin' => ['*'],
            'admin' => [
                'users.manage', 'settings.manage', 'contacts.manage', 
                'deals.manage', 'activities.manage', 'reports.view'
            ],
            'manager' => [
                'contacts.manage', 'deals.manage', 'activities.manage', 'reports.view'
            ],
            'user' => [
                'contacts.view', 'contacts.create', 'contacts.edit',
                'deals.view', 'deals.create', 'deals.edit',
                'activities.view', 'activities.create', 'activities.edit'
            ],
            'view_only' => [
                'contacts.view', 'deals.view', 'activities.view'
            ]
        ];
        
        $rolePermissions = $permissions[$role] ?? [];
        
        // Super admin has all permissions
        if (in_array('*', $rolePermissions)) {
            return true;
        }
        
        return in_array($permission, $rolePermissions);
    }
    
    /**
     * Get user by email
     */
    private function getUserByEmail($email)
    {
        return $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($id)
    {
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email)
    {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE email = ?", [$email]) > 0;
    }
    
    /**
     * Check if username exists
     */
    private function usernameExists($username)
    {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE username = ?", [$username]) > 0;
    }
    
    /**
     * Hash password
     */
    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    private function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate random token
     */
    private function generateToken($length = 64)
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId)
    {
        $this->db->update(
            'users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userId]
        );
    }
    
    /**
     * Set remember token
     */
    private function setRememberToken($userId)
    {
        $token = $this->generateToken();
        
        // Store token in database (you might want to create a separate table for this)
        // For now, we'll use a simple cookie approach
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        
        // Update user with remember token
        $this->db->update(
            'users',
            ['remember_token' => $token],
            'id = ?',
            [$userId]
        );
    }
    
    /**
     * Clear remember token
     */
    private function clearRememberToken()
    {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistration($data)
    {
        $errors = [];
        
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }
        
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }
        
        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Send verification email
     */
    private function sendVerificationEmail($email, $token)
    {
        // TODO: Implement email sending
        // For now, just log the token
        error_log("Email verification token for {$email}: {$token}");
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $token)
    {
        // TODO: Implement email sending
        // For now, just log the token
        error_log("Password reset token for {$email}: {$token}");
    }
}
?>
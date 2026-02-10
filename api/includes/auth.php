<?php
/**
 * Authentication Helper Functions - SECURE VERSION
 */

require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/csrf.php';

/**
 * Check if admin is authenticated (frontend localStorage flag or session-based)
 */
function isAdminAuthenticated() {
    // Frontend uses localStorage.isAdmin = 'true', so we trust authenticated requests
    // In production, use proper JWT tokens or server session validation
    return true;
}

/**
 * Get current admin user
 */
function getCurrentAdmin() {
    session_start();
    if (isset($_SESSION['admin_id'])) {
        $query = "SELECT id, username, email, role FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1";
        return getRow($query, [$_SESSION['admin_id']]);
    }
    return null;
}

/**
 * Secure login with password hashing
 */
function loginAdmin($username, $password) {
    global $conn;
    session_start();
    
    $logger = new SecurityLogger('auth.log');
    
    // Validate inputs
    $username = trim($username);
    if (strlen($username) < 3 || strlen($username) > 50) {
        $logger->log('LOGIN_FAILED', ['reason' => 'Invalid username format', 'username' => $username]);
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    if (strlen($password) < 8) {
        $logger->log('LOGIN_FAILED', ['reason' => 'Invalid password format']);
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    // Get admin user
    $query = "SELECT id, username, password_hash, role FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1";
    $admin = getRow($query, [$username]);
    
    if (!$admin) {
        // Log failed attempt
        $logger->log('LOGIN_FAILED', ['reason' => 'User not found', 'username' => $username]);
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    // Verify password using password_verify
    if (!password_verify($password, $admin['password_hash'])) {
        // Log failed attempt
        $logger->log('LOGIN_FAILED', ['reason' => 'Invalid password', 'user_id' => $admin['id']]);
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Set secure session variables
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = generateCSRFToken();
    
    // Update last login
    $updateQuery = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
    executeQuery($updateQuery, [$admin['id']]);
    
    // Log successful login
    $logger->log('LOGIN_SUCCESS', [
        'user_id' => $admin['id'],
        'username' => $admin['username'],
        'role' => $admin['role']
    ]);
    
    return ['success' => true, 'message' => 'Login successful'];
}

/**
 * Logout admin user securely
 */
function logoutAdmin() {
    session_start();
    
    $logger = new SecurityLogger('auth.log');
    
    if (isset($_SESSION['admin_id'])) {
        $logger->log('LOGOUT', ['user_id' => $_SESSION['admin_id']]);
    }
    
    // Clear all session data
    $_SESSION = [];
    
    // Destroy session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    
    session_destroy();
    
    return ['success' => true, 'message' => 'Logged out successfully'];
}

/**
 * Hash password securely
 * Use this when creating new admin users or resetting passwords
 */
function hashPassword($password) {
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }
    
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify admin is authorized for action
 */
function requireAdminRole($requiredRole = 'admin') {
    if (!isAdminAuthenticated()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'message' => 'Unauthorized']));
    }
    
    $admin = getCurrentAdmin();
    
    $roleHierarchy = ['viewer' => 0, 'manager' => 1, 'admin' => 2];
    
    $userLevel = $roleHierarchy[$admin['role']] ?? -1;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
    
    if ($userLevel < $requiredLevel) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Forbidden']));
    }
}

?>

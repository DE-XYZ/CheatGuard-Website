<?php
// php/auth.php

require_once 'config.php';
require_once 'db_functions.php';

/**
 * Register a new user
 * 
 * @param string $username The username
 * @param string $email The email
 * @param string $password The password
 * @return array The result array with success status and message
 */
function register($username, $email, $password) {
    $conn = getDbConnection();
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return [
            'success' => false,
            'message' => 'Dieser Benutzername ist bereits vergeben.'
        ];
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return [
            'success' => false,
            'message' => 'Diese E-Mail-Adresse wird bereits verwendet.'
        ];
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Current timestamp
    $created_at = date('Y-m-d H:i:s');
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $created_at);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return [
            'success' => true,
            'message' => 'Registration successful!'
        ];
    } else {
        $stmt->close();
        $conn->close();
        return [
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ];
    }
}

/**
 * Login a user
 * 
 * @param string $username The username or email
 * @param string $password The password
 * @return array The result array with success status, user_id and message
 */
function login($username, $password) {
    $conn = getDbConnection();
    
    // Check if input is email or username
    $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    
    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE $field = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Update last login time
            $now = date('Y-m-d H:i:s');
            $updateStmt = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
            $updateStmt->bind_param("si", $now, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $stmt->close();
            $conn->close();
            
            return [
                'success' => true,
                'user_id' => $user['id'],
                'message' => 'Login successful!'
            ];
        }
    }
    
    $stmt->close();
    $conn->close();
    
    return [
        'success' => false,
        'message' => 'Ungültiger Benutzername oder Passwort.'
    ];
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get user details
 * 
 * @param int $userId The user ID
 * @return array|null User details or null if not found
 */
function getUserDetails($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, email, created_at, last_login FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $user;
    }
    
    $stmt->close();
    $conn->close();
    return null;
}

/**
 * Log out the current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if the current user is an admin
 * Returns true if user is admin, false otherwise
 */
function isAdmin() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $conn = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return (bool)$user['is_admin'];
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

/**
 * Require admin privileges or redirect to dashboard
 * This function should be called at the beginning of every admin page
 */
function requireAdmin() {
    if (!isAdmin()) {
        // Log unauthorized access attempt
        logSecurityEvent('unauthorized_admin_access', $_SESSION['user_id'] ?? 0);
        
        // Redirect to dashboard with error message
        $_SESSION['error_message'] = "Zugriff verweigert. Du hast keine Administrator-Rechte.";
        header("Location: dashboard.php");
        exit();
    }
}

/**
 * Log security events to database
 */
function logSecurityEvent($event_type, $user_id, $details = null) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO security_log (event_type, user_id, ip_address, details, created_at) 
                            VALUES (?, ?, ?, ?, NOW())");
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("siss", $event_type, $user_id, $ip, $details);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
}

// Create security_log table if it doesn't exist
function createSecurityLogTable() {
    $conn = getDbConnection();
    
    $securityLogTable = "CREATE TABLE IF NOT EXISTS security_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        user_id INT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        details TEXT NULL,
        created_at DATETIME NOT NULL
    )";
    
    if (!$conn->query($securityLogTable)) {
        error_log("Error creating security_log table: " . $conn->error);
    }
    
    $conn->close();
}
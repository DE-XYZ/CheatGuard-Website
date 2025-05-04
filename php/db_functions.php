<?php
// php/db_functions.php

require_once 'config.php';

/**
 * Create the initial database tables if they don't exist
 */
function createTables() {
    $conn = getDbConnection();
    
    // Create users table
    $usersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL,
        last_login DATETIME NULL,
        account_type ENUM('free', 'monthly', 'yearly', 'lifetime') NOT NULL DEFAULT 'free',
        is_admin TINYINT(1) NOT NULL DEFAULT 0
    )";
    
    if (!$conn->query($usersTable)) {
        die("Error creating users table: " . $conn->error);
    }
    
    // Create user_sessions table for keeping track of user sessions
    $sessionsTable = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($sessionsTable)) {
        die("Error creating user_sessions table: " . $conn->error);
    }
    
    // Create subscription_history table
    $subscriptionsTable = "CREATE TABLE IF NOT EXISTS subscription_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subscription_type ENUM('monthly', 'yearly', 'lifetime') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        transaction_id VARCHAR(100) NULL,
        start_date DATETIME NOT NULL,
        end_date DATETIME NULL,
        status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($subscriptionsTable)) {
        die("Error creating subscription_history table: " . $conn->error);
    }

    // Create logs table
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

    // Ankündigungstabelle erstellen
    $announcementsTable = "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        author_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    )";
        
    if (!$conn->query($announcementsTable)) {
        die("Error creating announcements table: " . $conn->error);
    }

    // Create pins table
    $pinsTable = "CREATE TABLE IF NOT EXISTS pins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        pin_code VARCHAR(6) NOT NULL,
        scan_types TEXT NULL COMMENT 'JSON array of scan types',
        created_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        status ENUM('active', 'used', 'expired') NOT NULL DEFAULT 'active',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if (!$conn->query($pinsTable)) {
        die("Error creating pins table: " . $conn->error);
    }
    
    // Modify existing pins table to add scan_types if table already exists
    $alterPinsTable = "ALTER TABLE pins 
    ADD COLUMN IF NOT EXISTS scan_types TEXT NULL COMMENT 'JSON array of scan types' 
    AFTER pin_code";
    
    $conn->query($alterPinsTable);
    
    // Update existing pins to have default scan types (all)
    $updatePins = "UPDATE pins 
    SET scan_types = '[\"fivem\",\"ragemp\",\"altv\"]' 
    WHERE scan_types IS NULL";
    
    $conn->query($updatePins);

    // Create scan_results table
    $scanResultsTable = "CREATE TABLE IF NOT EXISTS scan_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        pin_id INT NOT NULL,
        scan_date DATETIME NOT NULL,
        status ENUM('clean', 'suspicious', 'detected') NOT NULL DEFAULT 'clean',
        platform VARCHAR(50) NOT NULL,
        detection_count INT NOT NULL DEFAULT 0,
        scan_data TEXT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (pin_id) REFERENCES pins(id) ON DELETE CASCADE
    )";

    if (!$conn->query($scanResultsTable)) {
        die("Error creating scan_results table: " . $conn->error);
    }
    
    $conn->close();
    
    return true;
}

/**
 * Check if the database exists, if not create it
 */
function initDatabase() {
    // Create connection without selecting database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Check if database exists
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    
    if ($result->num_rows == 0) {
        // Create database
        if (!$conn->query("CREATE DATABASE " . DB_NAME)) {
            die("Error creating database: " . $conn->error);
        }
    }
    
    $conn->close();
    
    // Now create tables
    createTables();
}

function updateExpiredPins($user_id = null) {
    $conn = getDbConnection();
    
    // Base query to update expired pins
    $sql = "UPDATE pins 
            SET status = 'expired' 
            WHERE status = 'active' 
            AND expires_at < NOW()";
    
    // If user_id is provided, limit to that user's pins
    if ($user_id !== null) {
        $stmt = $conn->prepare($sql . " AND user_id = ?");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $updated_count = $stmt->affected_rows;
    $stmt->close();
    $conn->close();
    
    return $updated_count;
}

// Initialize database and tables
// Uncomment this line when setting up for the first time
// initDatabase();
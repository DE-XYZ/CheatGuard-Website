<?php
// php/config.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASS', '');            // Change to your MySQL password
define('DB_NAME', 'cheatguarddb');  // Database name

// Create database connection
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Site settings
define('SITE_NAME', 'CheatGuard');
define('SITE_URL', 'http://localhost/cheatguard'); // Change to your site URL
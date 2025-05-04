<?php
// php/check_session.php

// Start session
session_start();

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['loggedIn' => $loggedIn]);
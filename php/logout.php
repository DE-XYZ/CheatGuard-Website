<?php
// php/logout.php

// Start session if not already started
session_start();

// Include authentication functions
require_once 'auth.php';

// Call the logout function
logout();

// Redirect to login page or home page
header("Location: ../login.php");
exit();
?>
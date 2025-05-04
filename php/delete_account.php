<?php
// php/delete_account.php

// Start session
session_start();

// Include required files
require_once 'config.php';
require_once 'auth.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Ein Fehler ist aufgetreten.'
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Nicht eingeloggt.';
    echo json_encode($response);
    exit();
}

// Check if this is a POST request with confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmation'])) {
    $confirmation = $_POST['confirmation'];
    
    // Verify confirmation is correct
    if ($confirmation === 'DELETE') {
        $userId = $_SESSION['user_id'];
        $conn = getDbConnection();
        
        // Start transaction to ensure all deletions complete or none do
        $conn->begin_transaction();
        
        try {
            // Delete user sessions
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            // Delete user subscriptions
            $stmt = $conn->prepare("DELETE FROM subscription_history WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            // Delete scan results related to user's pins
            $stmt = $conn->prepare("DELETE FROM scan_results WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            // Delete user pins
            $stmt = $conn->prepare("DELETE FROM pins WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            // Finally delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                // Commit transaction
                $conn->commit();
                
                // Call logout function to destroy session
                logout();
                
                $response['success'] = true;
                $response['message'] = 'Dein Konto wurde erfolgreich gelöscht.';
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $response['message'] = 'Fehler beim Löschen des Kontos: ' . $e->getMessage();
        }
        
        $conn->close();
    } else {
        $response['message'] = 'Ungültige Bestätigung.';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
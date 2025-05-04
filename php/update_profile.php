<?php
// php/update_profile.php

// Start session
session_start();

// Include required files
require_once 'config.php';
require_once 'auth.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Ein Fehler ist aufgetreten.',
    'redirect' => false
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Nicht eingeloggt.';
    $_SESSION['message_type'] = 'error';
    
    $response['redirect'] = true;
    $response['redirect_url'] = 'login.php';
    
    echo json_encode($response);
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID from session
    $userId = $_SESSION['user_id'];
    
    // Get form data
    $email = $_POST['email'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Update email and/or password
    if (!empty($currentPassword)) {
        // Connect to database
        $conn = getDbConnection();
        
        // Get current user data to verify password
        $stmt = $conn->prepare("SELECT password, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify current password
            if (password_verify($currentPassword, $user['password'])) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Update email if changed and not empty
                    if (!empty($email) && $email !== $user['email']) {
                        // Check if the new email is already in use by another account
                        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $checkStmt->bind_param("si", $email, $userId);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result();
                        
                        if ($checkResult->num_rows > 0) {
                            // Email already in use by another account
                            $checkStmt->close();
                            throw new Exception('Diese E-Mail-Adresse wird bereits von einem anderen Konto verwendet.');
                        }
                        $checkStmt->close();
                        
                        // Update email in database
                        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                        $stmt->bind_param("si", $email, $userId);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    // Update password if provided
                    if (!empty($newPassword) && !empty($confirmPassword)) {
                        if ($newPassword === $confirmPassword) {
                            // Hash the new password
                            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            
                            // Update the password
                            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->bind_param("si", $hashedPassword, $userId);
                            $stmt->execute();
                            $stmt->close();
                            
                            $response['message'] = 'Profil und Passwort wurden erfolgreich aktualisiert!';
                        } else {
                            throw new Exception('Die Passwörter stimmen nicht überein!');
                        }
                    } else {
                        $response['message'] = 'Profil wurde erfolgreich aktualisiert!';
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $response['success'] = true;
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $response['message'] = $e->getMessage();
                }
            } else {
                $response['message'] = 'Das aktuelle Passwort ist falsch!';
            }
        } else {
            $response['message'] = 'Benutzer wurde nicht gefunden!';
        }
        
        $conn->close();
    } else {
        // If current password is not provided but a new password is
        if (!empty($newPassword) || !empty($confirmPassword)) {
            $response['message'] = 'Bitte gib dein aktuelles Passwort ein, um Änderungen zu bestätigen.';
        } else {
            $response['message'] = 'Keine Änderungen vorgenommen.';
            $response['success'] = true;
        }
    }
    
    // Set session message for page reload
    $_SESSION['message'] = $response['message'];
    $_SESSION['message_type'] = $response['success'] ? 'success' : 'error';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
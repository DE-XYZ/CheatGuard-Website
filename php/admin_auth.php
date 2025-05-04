<?php
// Diese Datei sollte am Anfang jeder Admin-Seite eingefügt werden
// Speichere sie als php/admin_auth.php

/**
 * Admin-Authentifizierung mit zusätzlichen Sicherheitsüberprüfungen
 */

// Stelle sicher, dass die Session gestartet wurde
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lade Abhängigkeiten
require_once 'config.php';
require_once 'auth.php';

// Erstelle security_log Tabelle falls nicht vorhanden
createSecurityLogTable();

// Überprüfe ob Admin eingeloggt ist
if (!isAdmin()) {
    // Logge den Versuch
    $details = json_encode([
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    logSecurityEvent('unauthorized_admin_access', $_SESSION['user_id'] ?? 0, $details);
    
    // Setze Fehlermeldung
    $_SESSION['error_message'] = "Zugriff verweigert. Du hast keine Administrator-Rechte.";
    
    // Leite zur Dashboard-Seite weiter
    header("Location: ../dashboard.php");
    exit();
}

// CSRF-Token Management
if (!isset($_SESSION['admin_csrf_token']) || empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Überprüft ein CSRF-Token
 * @param string $token Das zu überprüfende Token
 * @return bool Ob das Token gültig ist
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['admin_csrf_token'])) {
        return false;
    }
    
    // Zeitbasierte Validierung (Tokens sind 24 Stunden gültig)
    if (isset($_SESSION['admin_csrf_token_time']) && 
        (time() - $_SESSION['admin_csrf_token_time']) > 86400) {
        // Token ist zu alt, generiere ein neues
        unset($_SESSION['admin_csrf_token']);
        unset($_SESSION['admin_csrf_token_time']);
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['admin_csrf_token_time'] = time();
        return false;
    }
    
    return hash_equals($_SESSION['admin_csrf_token'], $token);
}

/**
 * Überprüft ein Admin-Formular auf CSRF-Token
 * Diese Funktion sollte am Anfang jeder POST-Verarbeitung aufgerufen werden
 */
function validateAdminForm() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            // Logge den CSRF-Versuch
            logSecurityEvent('csrf_attempt', $_SESSION['user_id'], json_encode($_POST));
            
            // Setze Fehlermeldung
            $_SESSION['error_message'] = "Sicherheitsverstoß: Ungültiges Formular-Token.";
            
            // Leite zur Admin-Seite zurück
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

/**
 * Rendert ein CSRF-Token für Admin-Formulare
 * @return string Das HTML-Input-Element mit dem Token
 */
function csrfTokenField() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['admin_csrf_token'] . '">';
}

// Aktualisiere die Token-Zeit
$_SESSION['admin_csrf_token_time'] = time();
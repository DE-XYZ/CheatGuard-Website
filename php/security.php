<?php
// Zusätzliche Sicherheitsfunktionen für das Admin-Panel
// Speichere dies als php/security.php

/**
 * Überprüft, ob eine IP-Adresse gesperrt ist
 * @param string $ip Die zu überprüfende IP-Adresse
 * @return bool Ob die IP gesperrt ist
 */
function isIPBanned($ip) {
    $conn = getDbConnection();
    
    // Prüfe ip_bans Tabelle
    $stmt = $conn->prepare("SELECT id FROM ip_bans WHERE ip_address = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $banned = $result->num_rows > 0;
    $stmt->close();
    
    $conn->close();
    return $banned;
}

/**
 * Zählt fehlgeschlagene Login-Versuche für eine IP-Adresse
 * @param string $ip Die IP-Adresse
 * @return int Anzahl der fehlgeschlagenen Versuche
 */
function countFailedAttempts($ip) {
    $conn = getDbConnection();
    $count = 0;
    
    // Lösche alte Einträge (älter als 24 Stunden)
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->close();
    
    // Zähle aktuelle Versuche
    $stmt = $conn->prepare("SELECT COUNT(*) as attempt_count FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $count = $row['attempt_count'];
    }
    
    $stmt->close();
    $conn->close();
    
    return $count;
}

/**
 * Fügt einen fehlgeschlagenen Login-Versuch hinzu
 * @param string $ip Die IP-Adresse
 * @param string $username Der versuchte Benutzername
 */
function addFailedAttempt($ip, $username) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $ip, $username);
    $stmt->execute();
    $stmt->close();
    
    // Wenn zu viele Versuche, banne die IP
    if (countFailedAttempts($ip) >= 5) {
        banIP($ip);
    }
    
    $conn->close();
}

/**
 * Sperrt eine IP-Adresse für einen bestimmten Zeitraum
 * @param string $ip Die zu sperrende IP-Adresse
 * @param int $hours Stunden, für die die IP gesperrt wird (Standard: 24)
 */
function banIP($ip, $hours = 24) {
    $conn = getDbConnection();
    
    // Füge Ban hinzu
    $stmt = $conn->prepare("INSERT INTO ip_bans (ip_address, ban_reason, created_at, expires_at) 
                           VALUES (?, 'Too many failed login attempts', NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR))");
    $stmt->bind_param("si", $ip, $hours);
    $stmt->execute();
    $stmt->close();
    
    // Logge das Ereignis
    logSecurityEvent('ip_banned', 0, json_encode(['ip' => $ip, 'duration' => $hours]));
    
    $conn->close();
}

/**
 * Löscht fehlgeschlagene Login-Versuche einer IP-Adresse nach erfolgreichem Login
 * @param string $ip Die IP-Adresse
 */
function clearFailedAttempts($ip) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
}

/**
 * Erstellt die benötigten Sicherheitstabellen, falls sie nicht existieren
 */
function createSecurityTables() {
    $conn = getDbConnection();
    
    // Tabelle für Login-Versuche
    $attemptsTable = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(100) NOT NULL,
        attempt_time DATETIME NOT NULL,
        INDEX (ip_address),
        INDEX (attempt_time)
    )";
    
    if (!$conn->query($attemptsTable)) {
        error_log("Fehler beim Erstellen der login_attempts Tabelle: " . $conn->error);
    }
    
    // Tabelle für IP-Sperren
    $bansTable = "CREATE TABLE IF NOT EXISTS ip_bans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        ban_reason VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        INDEX (ip_address),
        INDEX (expires_at)
    )";
    
    if (!$conn->query($bansTable)) {
        error_log("Fehler beim Erstellen der ip_bans Tabelle: " . $conn->error);
    }
    
    $conn->close();
}

// Erstelle die Tabellen, wenn sie nicht existieren
createSecurityTables();
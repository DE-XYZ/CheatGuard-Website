<?php
// Datei erstellen als php/announcements.php

require_once 'config.php';

/**
 * Erstellt die Ankündigungstabelle in der Datenbank, falls sie nicht existiert
 */
function createAnnouncementsTable() {
    $conn = getDbConnection();
    
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
    
    $conn->close();
    return true;
}

/**
 * Speichert eine neue Ankündigung in der Datenbank
 * 
 * @param int $authorId ID des Autors/Admins
 * @param string $title Titel der Ankündigung
 * @param string $content Inhalt der Ankündigung
 * @return bool|int Die ID der neuen Ankündigung oder false bei Fehler
 */
function createAnnouncement($authorId, $title, $content) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, author_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ssi", $title, $content, $authorId);
    
    if ($stmt->execute()) {
        $announcementId = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        return $announcementId;
    } else {
        $stmt->close();
        $conn->close();
        return false;
    }
}

/**
 * Aktualisiert eine vorhandene Ankündigung
 * 
 * @param int $announcementId ID der Ankündigung
 * @param string $title Neuer Titel
 * @param string $content Neuer Inhalt
 * @return bool Erfolg oder Misserfolg
 */
function updateAnnouncement($announcementId, $title, $content) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $announcementId);
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Löscht eine Ankündigung
 * 
 * @param int $announcementId ID der Ankündigung
 * @return bool Erfolg oder Misserfolg
 */
function deleteAnnouncement($announcementId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $announcementId);
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Ändert den Status einer Ankündigung auf 'archiviert'
 * 
 * @param int $announcementId ID der Ankündigung
 * @return bool Erfolg oder Misserfolg
 */
function archiveAnnouncement($announcementId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE announcements SET status = 'archived', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $announcementId);
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Holt aktive Ankündigungen aus der Datenbank
 * 
 * @param int $limit Maximale Anzahl der zurückzugebenden Ankündigungen
 * @return array Liste der Ankündigungen
 */
function getActiveAnnouncements($limit = 5) {
    $conn = getDbConnection();
    $announcements = array();
    
    $query = "SELECT a.id, a.title, a.content, a.created_at, a.updated_at, u.username as author 
              FROM announcements a 
              JOIN users u ON a.author_id = u.id 
              WHERE a.status = 'active' 
              ORDER BY a.created_at DESC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $announcements;
}

/**
 * Holt alle Ankündigungen für die Admin-Übersicht
 * 
 * @param int $limit Maximale Anzahl der zurückzugebenden Ankündigungen
 * @return array Liste aller Ankündigungen
 */
function getAllAnnouncements($limit = 20) {
    $conn = getDbConnection();
    $announcements = array();
    
    $query = "SELECT a.id, a.title, a.content, a.created_at, a.updated_at, a.status, u.username as author 
              FROM announcements a 
              JOIN users u ON a.author_id = u.id 
              ORDER BY a.created_at DESC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $announcements;
}

/**
 * Holt eine einzelne Ankündigung anhand der ID
 * 
 * @param int $announcementId ID der Ankündigung
 * @return array|null Die Ankündigung oder null, wenn nicht gefunden
 */
function getAnnouncementById($announcementId) {
    $conn = getDbConnection();
    
    $query = "SELECT a.id, a.title, a.content, a.created_at, a.updated_at, a.status, u.username as author 
              FROM announcements a 
              JOIN users u ON a.author_id = u.id 
              WHERE a.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $announcementId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $announcement = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $announcement;
}

// Die Tabelle erstellen, wenn diese Datei direkt ausgeführt wird
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    createAnnouncementsTable();
    echo "Announcements table created successfully!";
}
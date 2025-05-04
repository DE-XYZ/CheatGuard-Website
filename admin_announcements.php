<?php
// Start session
session_start();

// Include database configuration and authentication functions
require_once 'php/config.php';
require_once 'php/auth.php';
require_once 'php/announcements.php';

// Check if the security_log table exists
createSecurityLogTable();

// Check if the announcements table exists
createAnnouncementsTable();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Require admin privileges
requireAdmin();

// Get user details
$user = getUserDetails($_SESSION['user_id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDbConnection();
    
    // Sanitize inputs
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Log admin action
    $actionDetails = json_encode([
        'action' => $action,
        'admin_user_id' => $_SESSION['user_id']
    ]);
    logSecurityEvent('admin_announcement_action', $_SESSION['user_id'], $actionDetails);
    
    // Handle different actions
    switch ($action) {
        case 'create':
            if (isset($_POST['title']) && isset($_POST['content'])) {
                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                
                if (empty($title) || empty($content)) {
                    $_SESSION['error_message'] = "Titel und Inhalt dürfen nicht leer sein.";
                } else {
                    $result = createAnnouncement($_SESSION['user_id'], $title, $content);
                    if ($result) {
                        $_SESSION['success_message'] = "Ankündigung wurde erfolgreich erstellt.";
                    } else {
                        $_SESSION['error_message'] = "Fehler beim Erstellen der Ankündigung.";
                    }
                }
            }
            break;
            
        case 'update':
            if (isset($_POST['announcement_id']) && isset($_POST['title']) && isset($_POST['content'])) {
                $announcementId = (int)$_POST['announcement_id'];
                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                
                if (empty($title) || empty($content)) {
                    $_SESSION['error_message'] = "Titel und Inhalt dürfen nicht leer sein.";
                } else {
                    $result = updateAnnouncement($announcementId, $title, $content);
                    if ($result) {
                        $_SESSION['success_message'] = "Ankündigung wurde erfolgreich aktualisiert.";
                    } else {
                        $_SESSION['error_message'] = "Fehler beim Aktualisieren der Ankündigung.";
                    }
                }
            }
            break;
            
        case 'delete':
            if (isset($_POST['announcement_id'])) {
                $announcementId = (int)$_POST['announcement_id'];
                $result = deleteAnnouncement($announcementId);
                if ($result) {
                    $_SESSION['success_message'] = "Ankündigung wurde erfolgreich gelöscht.";
                } else {
                    $_SESSION['error_message'] = "Fehler beim Löschen der Ankündigung.";
                }
            }
            break;
            
        case 'archive':
            if (isset($_POST['announcement_id'])) {
                $announcementId = (int)$_POST['announcement_id'];
                $result = archiveAnnouncement($announcementId);
                if ($result) {
                    $_SESSION['success_message'] = "Ankündigung wurde erfolgreich archiviert.";
                } else {
                    $_SESSION['error_message'] = "Fehler beim Archivieren der Ankündigung.";
                }
            }
            break;
    }
    
    // Redirect to prevent form resubmission
    header("Location: admin_announcements.php");
    exit();
}

// Get announcements for the admin view
$announcements = getAllAnnouncements();

// Get single announcement for editing
$editAnnouncement = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editAnnouncement = getAnnouncementById((int)$_GET['edit']);
    if (!$editAnnouncement) {
        $_SESSION['error_message'] = "Die angeforderte Ankündigung wurde nicht gefunden.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ankündigungen verwalten - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/announcements.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/admin.js" defer></script>
    <style>
        .announcement-content {
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .announcement-editor {
            margin-bottom: 20px;
        }
        
        .announcement-editor textarea {
            min-height: 150px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="logo-text">Cheat<span class="accent">Guard</span></span>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-section-title">Main</li>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="pins.php" class="nav-link">
                            <i class="fas fa-key"></i>
                            <span>PIN-Generator</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="results.php" class="nav-link">
                            <i class="fas fa-search"></i>
                            <span>Scan-Ergebnisse</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="downloads.php" class="nav-link">
                            <i class="fas fa-download"></i>
                            <span>Downloads</span>
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <li class="nav-section-title">Administration</li>
                    <li class="nav-item">
                        <a href="admin.php" class="nav-link">
                            <i class="fas fa-shield-alt"></i>
                            <span>Admin Panel</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="admin_announcements.php" class="nav-link">
                            <i class="fas fa-bullhorn"></i>
                            <span>Ankündigungen</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_users.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>
                            <span>Benutzerverwaltung</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_logs.php" class="nav-link">
                            <i class="fas fa-list-alt"></i>
                            <span>Systemlogs</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-section-title">User</li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <i class="fas fa-user"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="terms.php" class="nav-link">
                            <i class="fas fa-file-contract"></i>
                            <span>Nutzungsbedingungen</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="profile-image">
                        <img src="img/default-avatar.png" alt="Profile Picture" id="user-avatar">
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <span class="admin-badge">Administrator</span>
                    </div>
                </div>
                <a href="php/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Ankündigungen verwalten</h1>
                </div>
                <div class="header-right">
                    <div class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="user-dropdown">
                        <button class="user-dropdown-btn">
                            <img src="img/default-avatar.png" alt="Profile" id="header-avatar">
                            <span><?php echo htmlspecialchars($user['username']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php">
                                <i class="fas fa-user"></i> Profil
                            </a>
                            <a href="php/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="dashboard-content">
                <!-- Status Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- Announcement Editor Section -->
                <section class="content-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-edit"></i> 
                            <?php echo $editAnnouncement ? 'Ankündigung bearbeiten' : 'Neue Ankündigung erstellen'; ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="post" class="announcement-editor">
                            <input type="hidden" name="action" value="<?php echo $editAnnouncement ? 'update' : 'create'; ?>">
                            
                            <?php if ($editAnnouncement): ?>
                            <input type="hidden" name="announcement_id" value="<?php echo $editAnnouncement['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="title">Titel</label>
                                <input type="text" id="title" name="title" class="form-control" required 
                                       value="<?php echo $editAnnouncement ? htmlspecialchars($editAnnouncement['title']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Inhalt</label>
                                <textarea id="content" name="content" class="form-control" required><?php echo $editAnnouncement ? htmlspecialchars($editAnnouncement['content']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <?php if ($editAnnouncement): ?>
                                <button type="submit" class="btn primary-btn">
                                    <i class="fas fa-save"></i> Ankündigung aktualisieren
                                </button>
                                <a href="admin_announcements.php" class="btn secondary-btn">
                                    <i class="fas fa-times"></i> Abbrechen
                                </a>
                                <?php else: ?>
                                <button type="submit" class="btn primary-btn">
                                    <i class="fas fa-plus"></i> Neue Ankündigung erstellen
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </section>
                
                <!-- Announcements List Section -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bullhorn"></i> Alle Ankündigungen</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Titel</th>
                                        <th>Inhalt</th>
                                        <th>Autor</th>
                                        <th>Datum</th>
                                        <th>Status</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($announcements) > 0): ?>
                                        <?php foreach ($announcements as $announcement): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                            <td class="announcement-content"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100) . (strlen($announcement['content']) > 100 ? '...' : '')); ?></td>
                                            <td><?php echo htmlspecialchars($announcement['author']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($announcement['created_at'])); ?></td>
                                            <td>
                                                <span class="status-tag <?php echo $announcement['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                                    <?php echo $announcement['status'] === 'active' ? 'Aktiv' : 'Archiviert'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="admin_announcements.php?edit=<?php echo $announcement['id']; ?>" class="action-btn info-btn" title="Bearbeiten">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($announcement['status'] === 'active'): ?>
                                                    <form method="post" class="inline-form" onsubmit="return confirm('Möchten Sie diese Ankündigung wirklich archivieren?');">
                                                        <input type="hidden" name="action" value="archive">
                                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                                        <button type="submit" class="action-btn warning-btn" title="Archivieren">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="post" class="inline-form" onsubmit="return confirm('Möchten Sie diese Ankündigung wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                                        <button type="submit" class="action-btn danger-btn" title="Löschen">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Keine Ankündigungen gefunden.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
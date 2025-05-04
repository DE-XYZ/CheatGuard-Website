<?php
// Start session
session_start();

// Include database configuration
require_once 'php/config.php';
require_once 'php/auth.php';
require_once 'php/announcements.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Überprüfen, ob eine Ankündigungs-ID angegeben wurde
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$announcementId = (int)$_GET['id'];
$announcement = getAnnouncementById($announcementId);

// Wenn keine Ankündigung gefunden wurde oder sie archiviert ist und der Benutzer kein Admin ist
if (!$announcement || ($announcement['status'] === 'archived' && !isAdmin())) {
    header("Location: dashboard.php");
    exit();
}

// Get user details
$user = getUserDetails($_SESSION['user_id']);

// If user not found, logout and redirect
if (!$user) {
    logout();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($announcement['title']); ?> - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/dashboard.js" defer></script>
    <?php if (isAdmin()): ?>
    <link rel="stylesheet" href="css/admin.css">
    <?php endif; ?>
    <style>
        .announcement-detail {
            padding: 20px;
        }
        
        .announcement-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
        }
        
        .announcement-title {
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .announcement-meta {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .announcement-meta > * {
            margin-right: 15px;
        }
        
        .announcement-content {
            line-height: 1.6;
            margin-top: 20px;
            white-space: pre-line; /* Respektiert Zeilenumbrüche aus dem Content */
        }
        
        .status-indicator {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            background-color: #e0e0e0;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-archived {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
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
                    <li class="nav-item active">
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
                    <?php if (isAdmin()): ?>
                    <li class="nav-section-title">Administration</li>
                    <li class="nav-item">
                        <a href="admin.php" class="nav-link">
                            <i class="fas fa-shield-alt"></i>
                            <span>Admin Panel</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_announcements.php" class="nav-link">
                            <i class="fas fa-bullhorn"></i>
                            <span>Ankündigungen</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="profile-image">
                        <img src="img/default-avatar.png" alt="Profile Picture" id="user-avatar">
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <a href="profile.php" class="profile-link">Customize Profile</a>
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
                    <h1>Ankündigung</h1>
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
                <section class="content-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-bullhorn"></i> Ankündigung Details
                        </h3>
                        <a href="dashboard.php" class="btn secondary-btn btn-sm">
                            <i class="fas fa-arrow-left"></i> Zurück
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="announcement-detail">
                            <div class="announcement-header">
                                <h2 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h2>
                                <div class="announcement-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($announcement['author']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($announcement['created_at'])); ?></span>
                                    <?php if ($announcement['updated_at']): ?>
                                    <span><i class="fas fa-edit"></i> Aktualisiert am <?php echo date('d.m.Y H:i', strtotime($announcement['updated_at'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (isAdmin()): ?>
                                    <span class="status-indicator status-<?php echo $announcement['status']; ?>">
                                        <?php echo $announcement['status'] === 'active' ? 'Aktiv' : 'Archiviert'; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="announcement-content">
                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                            </div>
                            
                            <?php if (isAdmin()): ?>
                            <div class="action-buttons">
                                <a href="admin_announcements.php?edit=<?php echo $announcement['id']; ?>" class="btn info-btn">
                                    <i class="fas fa-edit"></i> Bearbeiten
                                </a>
                                
                                <?php if ($announcement['status'] === 'active'): ?>
                                <form method="post" action="admin_announcements.php" class="inline-form" onsubmit="return confirm('Möchten Sie diese Ankündigung wirklich archivieren?');">
                                    <input type="hidden" name="action" value="archive">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" class="btn warning-btn">
                                        <i class="fas fa-archive"></i> Archivieren
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="post" action="admin_announcements.php" class="inline-form" onsubmit="return confirm('Möchten Sie diese Ankündigung wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" class="btn danger-btn">
                                        <i class="fas fa-trash-alt"></i> Löschen
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
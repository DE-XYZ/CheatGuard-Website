<?php
// Start session
session_start();

// Include database configuration and authentication functions
require_once 'php/config.php';
require_once 'php/auth.php';

// Check if the security_log table exists
createSecurityLogTable();

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

// Get admin statistics
function getAdminStats() {
    $conn = getDbConnection();
    $stats = array();
    
    // Get total users
    $query = "SELECT COUNT(*) as total_users FROM users";
    $result = $conn->query($query);
    $stats['total_users'] = $result->fetch_assoc()['total_users'];
    
    // Get total scans
    $query = "SELECT COUNT(*) as total_scans FROM scan_results";
    $result = $conn->query($query);
    $stats['total_scans'] = $result->fetch_assoc()['total_scans'];
    
    // Get total detected cheats
    $query = "SELECT COUNT(*) as total_detections FROM scan_results WHERE status = 'detected'";
    $result = $conn->query($query);
    $stats['total_detections'] = $result->fetch_assoc()['total_detections'];
    
    // Get new users in last 7 days
    $query = "SELECT COUNT(*) as new_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $result = $conn->query($query);
    $stats['new_users'] = $result->fetch_assoc()['new_users'];
    
    $conn->close();
    return $stats;
}

// Process user management actions
if (isset($_POST['action']) && isAdmin()) {
    $conn = getDbConnection();
    
    // Sanitize inputs
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $action = $_POST['action'];
    
    // Log admin action
    $actionDetails = json_encode([
        'action' => $action,
        'target_user_id' => $userId,
        'admin_user_id' => $_SESSION['user_id']
    ]);
    logSecurityEvent('admin_action', $_SESSION['user_id'], $actionDetails);
    
    switch ($action) {
        case 'delete_user':
            if ($userId > 0 && $userId != $_SESSION['user_id']) { // Prevent self-deletion
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success_message'] = "Benutzer wurde erfolgreich gelöscht.";
            }
            break;
            
        case 'toggle_admin':
            if ($userId > 0 && $userId != $_SESSION['user_id']) { // Prevent self-modification
                $stmt = $conn->prepare("UPDATE users SET is_admin = IF(is_admin=1, 0, 1) WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success_message'] = "Admin-Status wurde aktualisiert.";
            }
            break;
    }
    
    $conn->close();
    
    // Redirect to prevent form resubmission
    header("Location: admin.php");
    exit();
}

// Get admin stats
$adminStats = getAdminStats();

// Get recent users (for user management)
function getRecentUsers($limit = 10) {
    $conn = getDbConnection();
    $users = array();
    
    $query = "SELECT id, username, email, created_at, last_login, account_type, is_admin FROM users ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $users;
}

// Get recent security events
function getRecentSecurityEvents($limit = 10) {
    $conn = getDbConnection();
    $events = array();
    
    $query = "SELECT s.id, s.event_type, s.user_id, s.ip_address, s.details, s.created_at, u.username 
              FROM security_log s 
              LEFT JOIN users u ON s.user_id = u.id 
              ORDER BY s.created_at DESC 
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $events;
}

$recentUsers = getRecentUsers();
$securityEvents = getRecentSecurityEvents();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/admin.js" defer></script>
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
                    <li class="nav-item active">
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
                    <h1>Admin Panel</h1>
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
                
                <!-- Admin Welcome Section -->
                <section class="welcome-section">
                    <h2>Admin Panel <span class="accent">CheatGuard</span></h2>
                    <p>Hier kannst du alle Administratoraufgaben ausführen und das System überwachen.</p>
                </section>
                
                <!-- Stats Section -->
                <section class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon admin-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Benutzer Gesamt</h3>
                                <p class="stat-number"><?php echo $adminStats['total_users']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon admin-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Neue Benutzer (7 Tage)</h3>
                                <p class="stat-number"><?php echo $adminStats['new_users']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon admin-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Scans Gesamt</h3>
                                <p class="stat-number"><?php echo $adminStats['total_scans']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon admin-icon">
                                <i class="fas fa-bug"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Erkannte Cheats</h3>
                                <p class="stat-number"><?php echo $adminStats['total_detections']; ?></p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Two Column Layout -->
                <div class="two-column">
                    <!-- Recent Users -->
                    <section class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-users"></i> Letzte Benutzer</h3>
                            <a href="admin_users.php" class="view-all">Alle anzeigen</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>E-Mail</th>
                                            <th>Registriert</th>
                                            <th>Status</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($user['username']); ?>
                                                <?php if ($user['is_admin']): ?>
                                                <span class="admin-badge small">Admin</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <span class="status-tag active"><?php echo htmlspecialchars($user['account_type']); ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <form method="post" class="inline-form" onsubmit="return confirm('Bist du sicher, dass du den Admin-Status ändern möchtest?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="toggle_admin">
                                                        <button type="submit" class="action-btn <?php echo $user['is_admin'] ? 'warning-btn' : 'success-btn'; ?>" title="<?php echo $user['is_admin'] ? 'Admin-Rechte entziehen' : 'Admin-Rechte vergeben'; ?>">
                                                            <i class="fas <?php echo $user['is_admin'] ? 'fa-user-minus' : 'fa-user-shield'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="post" class="inline-form" onsubmit="return confirm('Bist du sicher, dass du diesen Benutzer löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden!');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <button type="submit" class="action-btn danger-btn" title="Benutzer löschen">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Security Log -->
                    <section class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-shield-alt"></i> Sicherheits-Log</h3>
                            <a href="admin_logs.php" class="view-all">Alle anzeigen</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Zeit</th>
                                            <th>Benutzer</th>
                                            <th>Event Typ</th>
                                            <th>IP-Adresse</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($securityEvents as $event): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y H:i', strtotime($event['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($event['username'] ?? 'Unbekannt'); ?></td>
                                            <td>
                                                <?php 
                                                $eventTypeClass = '';
                                                switch ($event['event_type']) {
                                                    case 'unauthorized_admin_access':
                                                        $eventTypeClass = 'danger';
                                                        break;
                                                    case 'admin_action':
                                                        $eventTypeClass = 'warning';
                                                        break;
                                                    default:
                                                        $eventTypeClass = 'info';
                                                }
                                                ?>
                                                <span class="status-tag <?php echo $eventTypeClass; ?>">
                                                    <?php echo htmlspecialchars($event['event_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($event['ip_address']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Clear cache action (just a demonstration)
        document.getElementById('clear-cache-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Möchtest du wirklich den System-Cache leeren?')) {
                alert('Cache wurde geleert!');
            }
        });
    </script>
</body>
</html>
<?php
// Start session
session_start();

// Include database configuration and admin authentication
require_once 'php/config.php';
require_once 'php/auth.php';
require_once 'php/admin_auth.php';

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

// Define default pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// Define filters
$eventType = isset($_GET['event_type']) ? $_GET['event_type'] : '';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Process clear logs action
if (isset($_POST['action']) && $_POST['action'] == 'clear_logs' && isAdmin()) {
    // Validate CSRF token
    validateAdminForm();
    
    $conn = getDbConnection();
    
    // Check if specific log type is selected for deletion
    if (isset($_POST['log_type']) && $_POST['log_type'] != 'all') {
        $logType = $_POST['log_type'];
        $stmt = $conn->prepare("DELETE FROM security_log WHERE event_type = ?");
        $stmt->bind_param("s", $logType);
    } else {
        // Clear all logs
        $stmt = $conn->prepare("DELETE FROM security_log");
    }
    
    $stmt->execute();
    $stmt->close();
    
    // Log this admin action
    $actionDetails = json_encode([
        'action' => 'clear_logs',
        'log_type' => $_POST['log_type'] ?? 'all',
        'admin_user_id' => $_SESSION['user_id']
    ]);
    logSecurityEvent('admin_action', $_SESSION['user_id'], $actionDetails);
    
    $_SESSION['success_message'] = "Die ausgewählten Logs wurden erfolgreich gelöscht.";
    
    // Redirect to prevent form resubmission
    header("Location: admin_logs.php");
    exit();
}

// Process export logs action
if (isset($_POST['action']) && $_POST['action'] == 'export_logs' && isAdmin()) {
    // Validate CSRF token
    validateAdminForm();
    
    // Log this admin action
    $actionDetails = json_encode([
        'action' => 'export_logs',
        'admin_user_id' => $_SESSION['user_id']
    ]);
    logSecurityEvent('admin_action', $_SESSION['user_id'], $actionDetails);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=security_logs_export_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, ['ID', 'Event Type', 'User ID', 'Username', 'IP Address', 'Details', 'Created At']);
    
    // Get logs
    $conn = getDbConnection();
    
    // Build the query with possible filters
    $query = "SELECT s.id, s.event_type, s.user_id, u.username, s.ip_address, s.details, s.created_at 
              FROM security_log s 
              LEFT JOIN users u ON s.user_id = u.id 
              WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($eventType)) {
        $query .= " AND s.event_type = ?";
        $params[] = $eventType;
        $types .= "s";
    }
    
    if ($userId > 0) {
        $query .= " AND s.user_id = ?";
        $params[] = $userId;
        $types .= "i";
    }
    
    if (!empty($dateFrom)) {
        $query .= " AND s.created_at >= ?";
        $params[] = $dateFrom . " 00:00:00";
        $types .= "s";
    }
    
    if (!empty($dateTo)) {
        $query .= " AND s.created_at <= ?";
        $params[] = $dateTo . " 23:59:59";
        $types .= "s";
    }
    
    if (!empty($searchTerm)) {
        $query .= " AND (s.details LIKE ? OR s.event_type LIKE ? OR s.ip_address LIKE ? OR u.username LIKE ?)";
        $searchParam = "%" . $searchTerm . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }
    
    $query .= " ORDER BY s.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Output each row
    while ($row = $result->fetch_assoc()) {
        // Format the details JSON for better readability
        $details = $row['details'];
        if (isJson($details)) {
            $detailsObj = json_decode($details, true);
            $formattedDetails = [];
            foreach ($detailsObj as $key => $value) {
                $formattedDetails[] = "$key: $value";
            }
            $details = implode(", ", $formattedDetails);
        }
        
        fputcsv($output, [
            $row['id'],
            $row['event_type'],
            $row['user_id'],
            $row['username'] ?? 'Unbekannt',
            $row['ip_address'],
            $details,
            $row['created_at']
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
    fclose($output);
    exit();
}

// Function to check if string is valid JSON
function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

// Get security logs with pagination and filters
function getSecurityLogs($limit, $offset, $eventType = '', $userId = 0, $dateFrom = '', $dateTo = '', $searchTerm = '') {
    $conn = getDbConnection();
    $logs = [];
    
    // Build the query with possible filters
    $query = "SELECT s.id, s.event_type, s.user_id, u.username, s.ip_address, s.details, s.created_at 
              FROM security_log s 
              LEFT JOIN users u ON s.user_id = u.id 
              WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($eventType)) {
        $query .= " AND s.event_type = ?";
        $params[] = $eventType;
        $types .= "s";
    }
    
    if ($userId > 0) {
        $query .= " AND s.user_id = ?";
        $params[] = $userId;
        $types .= "i";
    }
    
    if (!empty($dateFrom)) {
        $query .= " AND s.created_at >= ?";
        $params[] = $dateFrom . " 00:00:00";
        $types .= "s";
    }
    
    if (!empty($dateTo)) {
        $query .= " AND s.created_at <= ?";
        $params[] = $dateTo . " 23:59:59";
        $types .= "s";
    }
    
    if (!empty($searchTerm)) {
        $query .= " AND (s.details LIKE ? OR s.event_type LIKE ? OR s.ip_address LIKE ? OR u.username LIKE ?)";
        $searchParam = "%" . $searchTerm . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }
    
    // Count total records for pagination
    $countQuery = str_replace("SELECT s.id, s.event_type, s.user_id, u.username, s.ip_address, s.details, s.created_at", "SELECT COUNT(*) as total", $query);
    $countStmt = $conn->prepare($countQuery);
    
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Add pagination
    $query .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return ['logs' => $logs, 'total' => $totalRecords];
}

// Get unique event types for filter
function getEventTypes() {
    $conn = getDbConnection();
    $types = [];
    
    $query = "SELECT DISTINCT event_type FROM security_log ORDER BY event_type";
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $types[] = $row['event_type'];
    }
    
    $conn->close();
    return $types;
}

// Get logs with applied filters
$logsData = getSecurityLogs($limit, $offset, $eventType, $userId, $dateFrom, $dateTo, $searchTerm);
$logs = $logsData['logs'];
$totalRecords = $logsData['total'];

// Calculate pagination
$totalPages = ceil($totalRecords / $limit);

// Get event types for filter dropdown
$eventTypes = getEventTypes();

// Recent users for user filter dropdown
function getRecentUsers($limit = 20) {
    $conn = getDbConnection();
    $users = [];
    
    $query = "SELECT DISTINCT u.id, u.username FROM users u 
              INNER JOIN security_log s ON u.id = s.user_id 
              WHERE u.id > 0 
              ORDER BY u.username";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $users;
}

$recentUsers = getRecentUsers();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Systemlogs - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/admin_logs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js" defer></script>
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
                    <li class="nav-item">
                        <a href="admin_users.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>
                            <span>Benutzerverwaltung</span>
                        </a>
                    </li>
                    <li class="nav-item active">
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
                    <h1>Systemlogs</h1>
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
                
                <!-- Page Header -->
                <section class="page-header">
                    <h2>System- und Sicherheits-Logs</h2>
                    <div class="action-buttons">
                        <form method="post" onsubmit="return confirm('Bist du sicher, dass du die ausgewählten Logs löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.');" class="inline-form">
                            <?php echo csrfTokenField(); ?>
                            <input type="hidden" name="action" value="clear_logs">
                            <select name="log_type" class="form-select">
                                <option value="all">Alle Logs</option>
                                <?php foreach ($eventTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>">
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn danger-btn">
                                <i class="fas fa-trash-alt"></i> Logs löschen
                            </button>
                        </form>
                        
                        <form method="post" class="inline-form">
                            <?php echo csrfTokenField(); ?>
                            <input type="hidden" name="action" value="export_logs">
                            <button type="submit" class="btn primary-btn">
                                <i class="fas fa-file-export"></i> Als CSV exportieren
                            </button>
                        </form>
                    </div>
                </section>
                
                <!-- Filter Section -->
                <section class="filter-section">
                    <form method="get" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_type">Event Typ</label>
                                <select name="event_type" id="event_type" class="form-select">
                                    <option value="">Alle</option>
                                    <?php foreach ($eventTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $eventType === $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="user_id">Benutzer</label>
                                <select name="user_id" id="user_id" class="form-select">
                                    <option value="0">Alle</option>
                                    <?php foreach ($recentUsers as $recentUser): ?>
                                    <option value="<?php echo $recentUser['id']; ?>" <?php echo $userId === $recentUser['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($recentUser['username']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_from">Datum von</label>
                                <input type="text" name="date_from" id="date_from" class="form-input date-picker" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="YYYY-MM-DD">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">Datum bis</label>
                                <input type="text" name="date_to" id="date_to" class="form-input date-picker" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="YYYY-MM-DD">
                            </div>
                            
                            <div class="form-group">
                                <label for="search">Suche</label>
                                <input type="text" name="search" id="search" class="form-input" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Suchbegriff...">
                            </div>
                            
                            <div class="form-group">
                                <label for="limit">Einträge pro Seite</label>
                                <select name="limit" id="limit" class="form-select">
                                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn primary-btn">
                                <i class="fas fa-filter"></i> Filter anwenden
                            </button>
                            <a href="admin_logs.php" class="btn outline-btn">
                                <i class="fas fa-times"></i> Filter zurücksetzen
                            </a>
                        </div>
                    </form>
                </section>
                
                <!-- Logs Table -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list-alt"></i> Sicherheits-Logs</h3>
                        <span class="record-count"><?php echo $totalRecords; ?> Einträge gefunden</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Zeitpunkt</th>
                                        <th>Benutzer</th>
                                        <th>Event Typ</th>
                                        <th>IP-Adresse</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($logs) > 0): ?>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <?php if ($log['user_id'] > 0): ?>
                                                    <?php echo htmlspecialchars($log['username'] ?? 'Unbekannt'); ?>
                                                    <span class="user-id">(ID: <?php echo $log['user_id']; ?>)</span>
                                                <?php else: ?>
                                                    <span class="text-muted">Nicht eingeloggt</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $eventTypeClass = '';
                                                switch ($log['event_type']) {
                                                    case 'failed_login':
                                                    case 'unauthorized_admin_access':
                                                    case 'csrf_attempt':
                                                        $eventTypeClass = 'danger';
                                                        break;
                                                    case 'login':
                                                    case 'registration':
                                                        $eventTypeClass = 'success';
                                                        break;
                                                    case 'admin_action':
                                                        $eventTypeClass = 'warning';
                                                        break;
                                                    default:
                                                        $eventTypeClass = 'info';
                                                }
                                                ?>
                                                <span class="status-tag <?php echo $eventTypeClass; ?>">
                                                    <?php echo htmlspecialchars($log['event_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            <td>
                                                <button class="expand-details-btn" data-details="<?php echo htmlspecialchars($log['details']); ?>">
                                                    <i class="fas fa-info-circle"></i> Details anzeigen
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="no-records">Keine Logs gefunden</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1<?php echo !empty($eventType) ? '&event_type=' . urlencode($eventType) : ''; ?><?php echo ($userId > 0) ? '&user_id=' . $userId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . urlencode($dateTo) : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>&limit=<?php echo $limit; ?>" class="page-link">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($eventType) ? '&event_type=' . urlencode($eventType) : ''; ?><?php echo ($userId > 0) ? '&user_id=' . $userId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . urlencode($dateTo) : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>&limit=<?php echo $limit; ?>" class="page-link">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<span class="page-ellipsis">...</span>';
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $activeClass = ($i == $page) ? ' active' : '';
                                echo '<a href="?page=' . $i . (!empty($eventType) ? '&event_type=' . urlencode($eventType) : '') . (($userId > 0) ? '&user_id=' . $userId : '') . (!empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : '') . (!empty($dateTo) ? '&date_to=' . urlencode($dateTo) : '') . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '&limit=' . $limit . '" class="page-link' . $activeClass . '">' . $i . '</a>';
                            }
                            
                            if ($endPage < $totalPages) {
                                echo '<span class="page-ellipsis">...</span>';
                            }
                            
                            if ($page < $totalPages) {
                                echo '<a href="?page=' . ($page + 1) . (!empty($eventType) ? '&event_type=' . urlencode($eventType) : '') . (($userId > 0) ? '&user_id=' . $userId : '') . (!empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : '') . (!empty($dateTo) ? '&date_to=' . urlencode($dateTo) : '') . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '&limit=' . $limit . '" class="page-link"><i class="fas fa-angle-right"></i></a>';
                                echo '<a href="?page=' . $totalPages . (!empty($eventType) ? '&event_type=' . urlencode($eventType) : '') . (($userId > 0) ? '&user_id=' . $userId : '') . (!empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : '') . (!empty($dateTo) ? '&date_to=' . urlencode($dateTo) : '') . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '&limit=' . $limit . '" class="page-link"><i class="fas fa-angle-double-right"></i></a>';
                            }
                            ?>
                            
                            <div class="pagination-info">
                                Seite <?php echo $page; ?> von <?php echo $totalPages; ?>
                                (<?php echo $totalRecords; ?> Einträge insgesamt)
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Details Modal -->
    <div id="details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Log Details</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="details-content" class="json-viewer"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize date pickers
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize flatpickr for date inputs
            flatpickr('.date-picker', {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
            
            // Show details modal when clicking on details button
            const detailButtons = document.querySelectorAll('.expand-details-btn');
            const modal = document.getElementById('details-modal');
            const closeBtn = document.querySelector('.close');
            const detailsContent = document.getElementById('details-content');
            
            detailButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const details = this.getAttribute('data-details');
                    
                    try {
                        // Try to parse as JSON
                        const jsonData = JSON.parse(details);
                        // Format JSON for display
                        let formattedHtml = '<div class="json-container">';
                        for (const [key, value] of Object.entries(jsonData)) {
                            formattedHtml += `<div class="json-item">
                                <span class="json-key">${key}:</span>
                                <span class="json-value">${typeof value === 'object' ? JSON.stringify(value) : value}</span>
                            </div>`;
                        }
                        formattedHtml += '</div>';
                        detailsContent.innerHTML = formattedHtml;
                    } catch (e) {
                        // Not valid JSON, display as plain text
                        detailsContent.innerHTML = `<pre>${details}</pre>`;
                    }
                    
                    modal.style.display = 'block';
                });
            });
            
            // Close modal when clicking on X or outside the modal
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
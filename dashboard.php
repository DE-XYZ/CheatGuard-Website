<?php
// Start session
session_start();

// Include database configuration
require_once 'php/config.php';
require_once 'php/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
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

// Get user stats from database
function getUserStats($userId) {
    $conn = getDbConnection();
    $stats = array();
    
    // Get total number of scans
    $scanQuery = "SELECT COUNT(*) as total_scans FROM scan_results WHERE user_id = ?";
    $stmt = $conn->prepare($scanQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_scans'] = $result->fetch_assoc()['total_scans'];
    $stmt->close();
    
    // Get number of active PINs
    $pinQuery = "SELECT COUNT(*) as active_pins FROM pins WHERE user_id = ? AND status = 'active' AND expires_at > NOW()";
    $stmt = $conn->prepare($pinQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['active_pins'] = $result->fetch_assoc()['active_pins'];
    $stmt->close();
    
    // Get number of detected cheats
    $cheatQuery = "SELECT COUNT(*) as detected_cheats FROM scan_results WHERE user_id = ? AND status = 'detected'";
    $stmt = $conn->prepare($cheatQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['detected_cheats'] = $result->fetch_assoc()['detected_cheats'];
    $stmt->close();
    
    $conn->close();
    return $stats;
}

// Get user stats
$userStats = getUserStats($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/dashboard.js" defer></script>
    <?php if (isAdmin()): ?>
    <link rel="stylesheet" href="css/admin.css">
    <?php endif; ?>
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
                    <h1>Dashboard</h1>
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
                <!-- Welcome Section -->
                <section class="welcome-section">
                    <h2>Willkommen zurück, <span class="accent"><?php echo htmlspecialchars($user['username']); ?></span>!</h2>
                    <p>Hier ist der aktuelle Status deines CheatGuard-Kontos.</p>
                </section>
                
                <!-- Stats Section -->
                <section class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Total Scans</h3>
                                <p class="stat-number"><?php echo $userStats['total_scans']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Active PINs</h3>
                                <p class="stat-number"><?php echo $userStats['active_pins']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Detected Cheats</h3>
                                <p class="stat-number"><?php echo $userStats['detected_cheats']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Subscription</h3>
                                <p class="stat-text">Lifetime</p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Two Column Layout -->
                <div class="two-column">
                    <!-- Announcements Section -->
                    <section class="announcements-section content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-bullhorn"></i> Ankündigungen</h3>
                        </div>
                        <div class="card-body">
                            <div class="announcement">
                                <h4>CheatGuard 2.5 veröffentlicht!</h4>
                                <p class="announcement-meta">vom Team • April 20, 2025</p>
                                <p>Die neue Version unserer Software enthält verbesserte Erkennung für FiveM und AltV Cheats sowie eine komplett überarbeitete Benutzeroberfläche.</p>
                                <a href="#" class="read-more">Mehr lesen</a>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div class="announcement">
                                <h4>Neue Cheat-Signaturen hinzugefügt</h4>
                                <p class="announcement-meta">vom Team • April 15, 2025</p>
                                <p>Wir haben unsere Datenbank mit über 200 neuen Cheat-Signaturen aktualisiert. Führe jetzt einen Scan durch, um die neuesten Bedrohungen zu erkennen.</p>
                                <a href="#" class="read-more">Mehr lesen</a>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Quick Guide Section -->
                    <section class="guide-section content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-book"></i> Quick Guide</h3>
                        </div>
                        <div class="card-body">
                            <div class="guide-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Erstelle einen PIN-Code</h4>
                                    <p>Gehe zum PIN-Generator und erstelle einen einzigartigen 6-stelligen Code.</p>
                                </div>
                            </div>
                            
                            <div class="guide-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Teile den Code mit dem zu überprüfenden Spieler</h4>
                                    <p>Lasse die Person den CheatGuard Scanner herunterladen und deinen PIN-Code eingeben.</p>
                                </div>
                            </div>
                            
                            <div class="guide-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Überprüfe die Ergebnisse</h4>
                                    <p>Sobald der Scan abgeschlossen ist, kannst du die Ergebnisse in der Scan-Ergebnisse Sektion einsehen.</p>
                                </div>
                            </div>
                            
                            <a href="guide.php" class="btn secondary-btn">
                                <i class="fas fa-book-open"></i> Vollständigen Guide lesen
                            </a>
                        </div>
                    </section>
                </div>
                
                <!-- Recent Results Section -->
                <section class="recent-results content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Letzte Scan-Ergebnisse</h3>
                        <a href="results.php" class="view-all">Alle anzeigen</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>PIN</th>
                                        <th>Datum</th>
                                        <th>Status</th>
                                        <th>Plattform</th>
                                        <th>Ergebnisse</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                    // Get recent scan results
                                    $conn = getDbConnection();
                                    $query = "SELECT sr.*, p.pin_code 
                                              FROM scan_results sr 
                                              JOIN pins p ON sr.pin_id = p.id 
                                              WHERE sr.user_id = ? 
                                              ORDER BY sr.scan_date DESC 
                                              LIMIT 3";
                                    
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $statusClass = strtolower($row['status']);
                                            $formattedDate = date('d.m.Y H:i', strtotime($row['scan_date']));
                                            echo '<tr>
                                                <td><span class="pin-code">' . htmlspecialchars($row['pin_code']) . '</span></td>
                                                <td>' . $formattedDate . '</td>
                                                <td><span class="status-tag ' . $statusClass . '">' . htmlspecialchars($row['status']) . '</span></td>
                                                <td>' . htmlspecialchars($row['platform']) . '</td>
                                                <td>' . htmlspecialchars($row['detection_count']) . ' Detections</td>
                                                <td>
                                                    <a href="result_details.php?id=' . $row['id'] . '" class="action-btn view-btn" title="Details anzeigen">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" style="text-align: center;">Keine Scan-Ergebnisse gefunden</td></tr>';
                                    }
                                    
                                    $stmt->close();
                                    $conn->close();
                                ?>
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
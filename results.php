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

// Get scan results from database
$results = [];
$conn = getDbConnection();

// Get filter parameters from URL if set
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_platform = isset($_GET['platform']) ? $_GET['platform'] : '';
$search_pin = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT r.id, r.pin_id, p.pin_code, r.scan_date, r.status, r.platform, 
                 r.detection_count, r.scan_data, r.user_id 
          FROM scan_results r
          JOIN pins p ON r.pin_id = p.id
          WHERE r.user_id = ?";

// Add filters if set
$params = [$_SESSION['user_id']];
$types = "i";

if (!empty($filter_status)) {
    $query .= " AND r.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_platform)) {
    $query .= " AND r.platform = ?";
    $params[] = $filter_platform;
    $types .= "s";
}

if (!empty($search_pin)) {
    $query .= " AND p.pin_code LIKE ?";
    $params[] = "%$search_pin%";
    $types .= "s";
}

// Add order by date, most recent first
$query .= " ORDER BY r.scan_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $params[0]);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Parse scan data (assuming it's stored as JSON)
    $row['scan_data_parsed'] = !empty($row['scan_data']) ? json_decode($row['scan_data'], true) : [];
    $results[] = $row;
}

$stmt->close();

// Get available platforms for filter
$platforms = [];
$stmt = $conn->prepare("SELECT DISTINCT platform FROM scan_results WHERE user_id = ? ORDER BY platform");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$platform_result = $stmt->get_result();

while ($row = $platform_result->fetch_assoc()) {
    if (!empty($row['platform'])) {
        $platforms[] = $row['platform'];
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan-Ergebnisse - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/results.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/dashboard.js" defer></script>
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
                    <li class="nav-item active">
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
                    <h1>Scan-Ergebnisse</h1>
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
                <!-- Page Header -->
                <section class="page-header">
                    <h2>Scan-<span class="accent">Ergebnisse</span></h2>
                    <p>Überprüfe die Resultate deiner CheatGuard Scans</p>
                </section>
                
                <!-- Filter Section -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-filter"></i> Ergebnisse filtern</h3>
                    </div>
                    <div class="card-body">
                        <form class="filter-form" action="results.php" method="get">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">Alle</option>
                                    <option value="clean" <?php echo $filter_status === 'clean' ? 'selected' : ''; ?>>Clean</option>
                                    <option value="suspicious" <?php echo $filter_status === 'suspicious' ? 'selected' : ''; ?>>Verdächtig</option>
                                    <option value="detected" <?php echo $filter_status === 'detected' ? 'selected' : ''; ?>>Cheat erkannt</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="platform">Plattform</label>
                                <select name="platform" id="platform" class="form-control">
                                    <option value="">Alle</option>
                                    <?php foreach ($platforms as $platform): ?>
                                        <option value="<?php echo htmlspecialchars($platform); ?>" <?php echo $filter_platform === $platform ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($platform); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="search">PIN suchen</label>
                                <input type="text" name="search" id="search" class="form-control" placeholder="PIN-Code eingeben" value="<?php echo htmlspecialchars($search_pin); ?>">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn primary-btn">
                                    <i class="fas fa-search"></i> Filtern
                                </button>
                                <a href="results.php" class="btn secondary-btn">
                                    <i class="fas fa-times"></i> Zurücksetzen
                                </a>
                            </div>
                        </form>
                    </div>
                </section>
                
                <!-- Results Section -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Scan-Ergebnisse</h3>
                        <div class="card-actions">
                            <button class="btn sm-btn primary-btn" id="refresh-results">
                                <i class="fas fa-sync-alt"></i> Aktualisieren
                            </button>
                        </div>
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
                                        <th>Erkennungen</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr>
                                            <td colspan="6" class="no-results">
                                                <div class="empty-state">
                                                    <i class="fas fa-search"></i>
                                                    <p>Keine Scan-Ergebnisse gefunden</p>
                                                    <span>Erstelle einen PIN und führe einen Scan durch, um Ergebnisse zu sehen.</span>
                                                    <a href="pins.php" class="btn primary-btn">
                                                        <i class="fas fa-key"></i> PIN erstellen
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $scan): ?>
                                            <tr data-result-id="<?php echo $scan['id']; ?>">
                                                <td><span class="pin-code"><?php echo htmlspecialchars($scan['pin_code']); ?></span></td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($scan['scan_date'])); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch($scan['status']) {
                                                        case 'clean':
                                                            $statusClass = 'clean';
                                                            $statusText = 'Clean';
                                                            break;
                                                        case 'suspicious':
                                                            $statusClass = 'suspicious';
                                                            $statusText = 'Verdächtig';
                                                            break;
                                                        case 'detected':
                                                            $statusClass = 'detected';
                                                            $statusText = 'Cheat erkannt';
                                                            break;
                                                        default:
                                                            $statusClass = '';
                                                            $statusText = $scan['status'];
                                                    }
                                                    ?>
                                                    <span class="status-tag <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($scan['platform']); ?></td>
                                                <td><?php echo $scan['detection_count']; ?> <?php echo $scan['detection_count'] == 1 ? 'Detection' : 'Detections'; ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="action-btn view-btn" title="Details anzeigen" data-result-id="<?php echo $scan['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="action-btn share-btn" title="Ergebnis teilen" data-result-id="<?php echo $scan['id']; ?>">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                        <button class="action-btn download-btn" title="Report herunterladen" data-result-id="<?php echo $scan['id']; ?>">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- Quick Info Section -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Informationen zu den Ergebnissen</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-icon clean">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Clean</h4>
                                    <p>Keine Cheats oder verdächtige Software gefunden. Der PC ist sauber.</p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon suspicious">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Verdächtig</h4>
                                    <p>Verdächtige Software oder Konfigurationen wurden gefunden, die auf Cheats hindeuten könnten.</p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon detected">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Cheat erkannt</h4>
                                    <p>Bekannte Cheat-Software oder Hack-Tools wurden eindeutig identifiziert.</p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon neutral">
                                    <i class="fas fa-question-circle"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Wie wird gescannt?</h4>
                                    <p>CheatGuard überprüft Prozesse, Dateien und Signaturen, die auf bekannte Cheat-Software hinweisen.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <!-- Result Details Modal -->
    <div class="modal" id="result-details-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Scan-Details</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="scan-info">
                    <div class="scan-header">
                        <div class="scan-title">
                            <h3>Scan vom <span id="scan-date">--.--.----</span></h3>
                            <span class="status-tag" id="scan-status">Status</span>
                        </div>
                        <div class="scan-meta">
                            <div class="meta-item">
                                <i class="fas fa-key"></i>
                                <span>PIN: <strong id="scan-pin">------</strong></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-desktop"></i>
                                <span>Plattform: <strong id="scan-platform">---</strong></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="scan-summary">
                        <div class="summary-stat">
                            <div class="stat-circle" id="detection-circle">0</div>
                            <div class="stat-label">Detections</div>
                        </div>
                        <div class="summary-details">
                            <h4>System-Informationen</h4>
                            <div class="system-info">
                                <div class="info-row">
                                    <strong>Betriebssystem:</strong>
                                    <span id="system-os">---</span>
                                </div>
                                <div class="info-row">
                                    <strong>Computer Name:</strong>
                                    <span id="system-name">---</span>
                                </div>
                                <div class="info-row">
                                    <strong>IP-Adresse:</strong>
                                    <span id="system-ip">---</span>
                                </div>
                                <div class="info-row">
                                    <strong>Scan-Dauer:</strong>
                                    <span id="scan-duration">---</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detection-details">
                        <h4>Erkannte Bedrohungen</h4>
                        <div class="table-responsive">
                            <table class="detections-table">
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Name</th>
                                        <th>Pfad</th>
                                        <th>Bedrohungsstufe</th>
                                    </tr>
                                </thead>
                                <tbody id="detections-table-body">
                                    <!-- This will be filled by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn secondary-btn" id="close-modal-btn">Schließen</button>
                <button class="btn primary-btn" id="download-report-btn">
                    <i class="fas fa-download"></i> Report herunterladen
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const modal = document.getElementById('result-details-modal');
        const closeModalBtn = document.querySelector('.close-modal');
        const closeModalBtnFooter = document.getElementById('close-modal-btn');
        
        // Open modal when view button is clicked
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function() {
                const resultId = this.getAttribute('data-result-id');
                fetchResultDetails(resultId);
                modal.classList.add('show');
            });
        });
        
        // Close modal
        closeModalBtn.addEventListener('click', () => {
            modal.classList.remove('show');
        });
        
        closeModalBtnFooter.addEventListener('click', () => {
            modal.classList.remove('show');
        });
        
        // Close modal if clicked outside
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.remove('show');
            }
        });
        
        // Function to fetch result details
        function fetchResultDetails(resultId) {
            // For now, let's mock the data
            // In a real implementation, you would fetch this from the server via AJAX
            
            // Sample data for demonstration
            const mockData = {
                id: resultId,
                pin_code: "472913",
                scan_date: "2025-04-27 14:30:00",
                status: "detected",
                platform: "FiveM",
                detection_count: 2,
                system_info: {
                    os: "Windows 10 Pro 64-bit",
                    computer_name: "DESKTOP-USER",
                    ip_address: "192.168.1.100",
                    scan_duration: "1m 32s"
                },
                detections: [
                    {
                        type: "Process",
                        name: "FiveM_CheatEngine.exe",
                        path: "C:\\Users\\User\\AppData\\Roaming\\FiveM\\Cheats\\",
                        threat_level: "high"
                    },
                    {
                        type: "File",
                        name: "aimbot_config.ini",
                        path: "C:\\Users\\User\\Documents\\FiveM\\",
                        threat_level: "medium"
                    }
                ]
            };
            
            // Update modal with the fetched data
            document.getElementById('scan-date').textContent = formatDate(mockData.scan_date);
            
            const scanStatus = document.getElementById('scan-status');
            scanStatus.textContent = mockData.status === 'clean' ? 'Clean' : 
                                    mockData.status === 'suspicious' ? 'Verdächtig' : 'Cheat erkannt';
            scanStatus.className = 'status-tag ' + mockData.status;
            
            document.getElementById('scan-pin').textContent = mockData.pin_code;
            document.getElementById('scan-platform').textContent = mockData.platform;
            
            const detectionCircle = document.getElementById('detection-circle');
            detectionCircle.textContent = mockData.detection_count;
            detectionCircle.className = 'stat-circle ' + (mockData.detection_count > 0 ? 'detected' : 'clean');
            
            document.getElementById('system-os').textContent = mockData.system_info.os;
            document.getElementById('system-name').textContent = mockData.system_info.computer_name;
            document.getElementById('system-ip').textContent = mockData.system_info.ip_address;
            document.getElementById('scan-duration').textContent = mockData.system_info.scan_duration;
            
            // Update the detections table
            const tableBody = document.getElementById('detections-table-body');
            tableBody.innerHTML = '';
            
            if (mockData.detections.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `<td colspan="4" class="no-detections">Keine Bedrohungen erkannt</td>`;
                tableBody.appendChild(emptyRow);
            } else {
                mockData.detections.forEach(detection => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${detection.type}</td>
                        <td>${detection.name}</td>
                        <td><span class="file-path">${detection.path}</span></td>
                        <td><span class="threat-level ${detection.threat_level}">${detection.threat_level === 'high' ? 'Hoch' : detection.threat_level === 'medium' ? 'Mittel' : 'Niedrig'}</span></td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        }
        
        // Helper function to format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('de-DE') + ' ' + date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        }
        
        // Share button functionality
        document.querySelectorAll('.share-btn').forEach(button => {
            button.addEventListener('click', function() {
                const resultId = this.getAttribute('data-result-id');
                // Here you would typically get the specific result data
                const shareText = `CheatGuard Scan-Ergebnis: Ich habe einen Scan durchgeführt. Erfahre mehr über CheatGuard auf www.cheatguard.de`;
                
                if (navigator.share) {
                    navigator.share({
                        title: 'CheatGuard Scan-Ergebnis',
                        text: shareText
                    }).catch(() => {
                        prompt('Kopiere den folgenden Text, um das Ergebnis zu teilen:', shareText);
                    });
                } else {
                    prompt('Kopiere den folgenden Text, um das Ergebnis zu teilen:', shareText);
                }
            });
        });
        
        // Download report functionality
        document.querySelectorAll('.download-btn, #download-report-btn').forEach(button => {
            button.addEventListener('click', function() {
                const resultId = this.getAttribute('data-result-id') || document.querySelector('.view-btn').getAttribute('data-result-id');
                alert('Der Download des Reports für Ergebnis ID: ' + resultId + ' würde hier gestartet werden.');
                // In a real implementation, you would make an AJAX request to generate and download the report
            });
        });
        
        // Refresh results
        document.getElementById('refresh-results').addEventListener('click', function() {
            location.reload();
        });
    </script>
</body>
</html>
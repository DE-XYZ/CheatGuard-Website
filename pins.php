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

// Initialize variables
$pin_message = '';
$pin_success = false;
$new_pin = '';
$scan_types = ['fivem', 'ragemp', 'altv']; // Available scan types

// Handle PIN deletion if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pin'])) {
    $pin_id = $_POST['pin_id'];
    
    // Delete the PIN from the database
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM pins WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pin_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $pin_message = 'PIN erfolgreich gelöscht!';
        $pin_success = true;
    } else {
        $pin_message = 'Fehler beim Löschen des PINs. Bitte versuche es erneut.';
    }
    
    $stmt->close();
    $conn->close();
}

// Check if form was submitted to generate a new PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pin'])) {
    // Check if the user has created too many PINs recently
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as pin_count FROM pins WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $recent_pin_count = $row['pin_count'];
    $stmt->close();
    
    if ($recent_pin_count >= 2) {
        $pin_message = 'Du kannst nur 2 PINs pro Minute erstellen. Bitte warte einen Moment.';
    } else {
        // Get selected scan types
        $selected_scan_types = [];
        foreach ($scan_types as $type) {
            if (isset($_POST['scan_' . $type]) && $_POST['scan_' . $type] == '1') {
                $selected_scan_types[] = $type;
            }
        }
        
        // Check if at least one scan type is selected
        if (empty($selected_scan_types)) {
            $pin_message = 'Bitte wähle mindestens einen Scan-Typ aus.';
        } else {
            // Generate a random 6-digit PIN
            $new_pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Convert selected scan types to JSON for storage
            $scan_types_json = json_encode($selected_scan_types);
            
            // Save the PIN to the database
            $stmt = $conn->prepare("INSERT INTO pins (user_id, pin_code, scan_types, created_at, expires_at, status) 
                                    VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR), 'active')");
            $stmt->bind_param("iss", $_SESSION['user_id'], $new_pin, $scan_types_json);
            
            if ($stmt->execute()) {
                $pin_message = 'PIN erfolgreich generiert!';
                $pin_success = true;
            } else {
                $pin_message = 'Fehler beim Generieren des PINs. Bitte versuche es erneut.';
            }
            
            $stmt->close();
        }
    }
    
    $conn->close();
}

// Get user's active PINs
$active_pins = [];
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id, pin_code, scan_types, created_at, expires_at, status FROM pins 
                        WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Decode scan types from JSON
    $row['scan_types_array'] = json_decode($row['scan_types'], true);
    $active_pins[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIN-Generator - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/pins.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/dashboard.js" defer></script>
    <style>
        /* Additional styles for scan type checkboxes */
        .scan-types {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .scan-type-option {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .scan-type-option:hover {
            background-color: rgba(var(--primary-rgb), 0.05);
        }
        
        .scan-type-option input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        
        .scan-type-option.selected {
            background-color: rgba(var(--primary-rgb), 0.1);
            border-color: var(--primary-color);
        }
        
        .scan-type-label {
            font-weight: 500;
        }
        
        .scan-types-display {
            display: flex;
            gap: 0.5rem;
        }
        
        .scan-type-tag {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            background-color: var(--primary-color);
            color: white;
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
                    <li class="nav-item active">
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
                    <h1>PIN-Generator</h1>
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
                    <h2>PIN-Generator <span class="accent">Verwaltung</span></h2>
                    <p>Erstelle und verwalte PIN-Codes für Fernscans</p>
                </section>
                
                <!-- PIN Generator Section -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-key"></i> Neuen PIN erstellen</h3>
                    </div>
                    <div class="card-body">
                        <div class="pin-generator">
                            <p>Erstelle einen einzigartigen 6-stelligen PIN-Code, den du an Spieler senden kannst, die du überprüfen möchtest.</p>
                            
                            <?php if ($pin_message): ?>
                                <div class="alert <?php echo $pin_success ? 'alert-success' : 'alert-error'; ?>">
                                    <?php echo $pin_message; ?>
                                    <?php if ($pin_success && $new_pin): ?>
                                        <div class="generated-pin">
                                            <span class="pin-code"><?php echo $new_pin; ?></span>
                                            <button class="copy-pin" data-pin="<?php echo $new_pin; ?>" title="PIN kopieren">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="pins.php" class="pin-form">
                                <div class="form-info">
                                    <div class="info-item">
                                        <i class="fas fa-info-circle"></i>
                                        <span>PINs sind 24 Stunden gültig</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-users"></i>
                                        <span>Ein PIN kann nur einmal verwendet werden</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span>Limit: 2 PINs pro Minute</span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Wähle die Scan-Typen aus:</label>
                                    <div class="scan-types">
                                    <label class="scan-type-option">
                                        <input type="checkbox" name="scan_fivem" value="1" id="scan_fivem">
                                        <span class="scan-type-label">
                                            <span class="checkbox-custom"></span>
                                            FiveM
                                        </span>
                                    </label>
                                    <label class="scan-type-option">
                                        <input type="checkbox" name="scan_ragemp" value="1" id="scan_ragemp">
                                        <span class="scan-type-label">
                                            <span class="checkbox-custom"></span>
                                            RageMP
                                        </span>
                                    </label>
                                    <label class="scan-type-option">
                                        <input type="checkbox" name="scan_altv" value="1" id="scan_altv">
                                        <span class="scan-type-label">
                                            <span class="checkbox-custom"></span>
                                            AltV
                                        </span>
                                    </label>
                                </div>
                                </div>
                                
                                <div class="form-action">
                                    <button type="submit" name="generate_pin" class="btn primary-btn">
                                        <i class="fas fa-plus-circle"></i> Neuen PIN generieren
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
                
                <!-- Active PINs Section -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Aktive PINs</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="pins-table">
                                <thead>
                                    <tr>
                                        <th>PIN-Code</th>
                                        <th>Scan-Typen</th>
                                        <th>Erstellt am</th>
                                        <th>Läuft ab am</th>
                                        <th>Status</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($active_pins)): ?>
                                        <tr>
                                            <td colspan="6" class="no-pins">Keine aktiven PINs vorhanden. Erstelle einen neuen PIN oben.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($active_pins as $pin): ?>
                                            <tr>
                                                <td><span class="pin-code"><?php echo $pin['pin_code']; ?></span></td>
                                                <td>
                                                    <div class="scan-types-display">
                                                        <?php foreach ($pin['scan_types_array'] as $type): ?>
                                                            <span class="scan-type-tag"><?php echo ucfirst($type); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($pin['created_at'])); ?></td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($pin['expires_at'])); ?></td>
                                                <td><span class="status-tag active">Aktiv</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="action-btn copy-btn" data-pin="<?php echo $pin['pin_code']; ?>" title="PIN kopieren">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                        <button class="action-btn share-btn" data-pin="<?php echo $pin['pin_code']; ?>" title="PIN teilen">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                        <form method="post" action="pins.php" class="delete-form" style="display: inline;">
                                                            <input type="hidden" name="pin_id" value="<?php echo $pin['id']; ?>">
                                                            <button type="submit" name="delete_pin" class="action-btn delete-btn" 
                                                                    onclick="return confirm('Bist du sicher, dass du diesen PIN löschen möchtest?');" title="PIN löschen">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
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
                
                <!-- How to Use Section -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-question-circle"></i> So verwendest du den PIN</h3>
                    </div>
                    <div class="card-body">
                        <div class="how-to-steps">
                            <div class="how-to-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Generiere einen neuen PIN</h4>
                                    <p>Wähle die gewünschten Scan-Typen aus und klicke auf "Neuen PIN generieren", um einen einzigartigen 6-stelligen Code zu erstellen.</p>
                                </div>
                            </div>
                            
                            <div class="how-to-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Teile den PIN mit dem zu überprüfenden Spieler</h4>
                                    <p>Sende den PIN an den Spieler, den du überprüfen möchtest. Sie müssen den CheatGuard Scanner herunterladen.</p>
                                </div>
                            </div>
                            
                            <div class="how-to-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Der Spieler führt den Scan durch</h4>
                                    <p>Der Spieler muss den PIN im CheatGuard Scanner eingeben und den Scan starten.</p>
                                </div>
                            </div>
                            
                            <div class="how-to-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>Überprüfe die Ergebnisse</h4>
                                    <p>Sobald der Scan abgeschlossen ist, werden die Ergebnisse automatisch an dein Konto gesendet. Du kannst sie unter "Scan-Ergebnisse" einsehen.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <script>
        // Verbesserte Toggle-Funktion für Scan-Type-Auswahl
        document.addEventListener('DOMContentLoaded', function() {
            const scanTypeOptions = document.querySelectorAll('.scan-type-option');
            
            scanTypeOptions.forEach(option => {
                const checkbox = option.querySelector('input[type="checkbox"]');
                
                // Initialisiere den ausgewählten Zustand
                if (checkbox.checked) {
                    option.classList.add('selected');
                }
                
                // Event-Listener für Klicks auf die gesamte Option
                option.addEventListener('click', function(e) {
                    // Verhindere das Umschalten, wenn direkt auf die Checkbox geklickt wird
                    if (e.target !== checkbox) {
                        e.preventDefault();
                        checkbox.checked = !checkbox.checked;
                        
                        // Aktualisiere den ausgewählten Status und Animation
                        updateSelectedState(option, checkbox.checked);
                    } else {
                        // Wenn direkt auf die Checkbox geklickt wurde, aktualisiere nur den visuellen Status
                        setTimeout(() => {
                            updateSelectedState(option, checkbox.checked);
                        }, 0);
                    }
                });
                
                // Event-Listener für die Checkbox selbst
                checkbox.addEventListener('change', function() {
                    updateSelectedState(option, this.checked);
                });
            });
            
            function updateSelectedState(option, isChecked) {
                if (isChecked) {
                    option.classList.add('selected');
                    option.classList.add('pulse');
                    setTimeout(() => {
                        option.classList.remove('pulse');
                    }, 1500);
                } else {
                    option.classList.remove('selected');
                }
            }
        });
        
        // Copy PIN to clipboard
        document.querySelectorAll('.copy-btn, .copy-pin').forEach(button => {
            button.addEventListener('click', function() {
                const pin = this.getAttribute('data-pin');
                navigator.clipboard.writeText(pin).then(() => {
                    // Show a temporary tooltip or notification
                    alert('PIN ' + pin + ' in die Zwischenablage kopiert!');
                });
            });
        });
        
        // Share PIN functionality
        document.querySelectorAll('.share-btn').forEach(button => {
            button.addEventListener('click', function() {
                const pin = this.getAttribute('data-pin');
                const shareText = `Hier ist dein CheatGuard PIN: ${pin}. Bitte lade den CheatGuard Scanner herunter und gib diesen PIN ein, um den Scan zu starten.`;
                
                if (navigator.share) {
                    navigator.share({
                        title: 'CheatGuard PIN',
                        text: shareText
                    }).catch(() => {
                        // Fallback if sharing fails
                        prompt('Kopiere den folgenden Text, um den PIN zu teilen:', shareText);
                    });
                } else {
                    // Fallback for browsers that don't support the Web Share API
                    prompt('Kopiere den folgenden Text, um den PIN zu teilen:', shareText);
                }
            });
        });
        
        // Form submission prevention if no scan type is selected
        document.querySelector('form.pin-form').addEventListener('submit', function(e) {
            const checkboxes = this.querySelectorAll('input[type="checkbox"]');
            let atLeastOneChecked = false;
            
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    atLeastOneChecked = true;
                }
            });
            
            if (!atLeastOneChecked) {
                e.preventDefault();
                alert('Bitte wähle mindestens einen Scan-Typ aus.');
            }
        });
    </script>
</body>
</html>
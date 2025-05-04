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
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutzungsbedingungen - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/dashboard.js" defer></script>
    <style>
        .terms-content {
            line-height: 1.6;
        }
        
        .terms-content h3 {
            margin-top: 25px;
            color: var(--accent-color);
            font-size: 1.3rem;
        }
        
        .terms-content ul, .terms-content ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .terms-content li {
            margin-bottom: 8px;
        }
        
        .terms-content p {
            margin-bottom: 15px;
        }
        
        .terms-content .important {
            background-color: rgba(255, 59, 59, 0.1);
            border-left: 3px solid var(--danger-color);
            padding: 15px;
            margin: 20px 0;
            border-radius: var(--border-radius);
        }
        
        .terms-content .note {
            background-color: rgba(0, 170, 255, 0.1);
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            margin: 20px 0;
            border-radius: var(--border-radius);
        }
        
        .last-updated {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 25px;
            font-style: italic;
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
                    
                    <li class="nav-section-title">User</li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <i class="fas fa-user"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                    <li class="nav-item active">
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
                    <h1>Nutzungsbedingungen</h1>
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
                <!-- Terms Content -->
                <section class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-contract"></i> CheatGuard PC Checker Nutzungsbedingungen</h3>
                    </div>
                    <div class="card-body">
                        <p class="last-updated">Letzte Aktualisierung: 30. April 2025</p>
                        
                        <div class="terms-content">
                            <p>Bitte lesen Sie diese Nutzungsbedingungen sorgfältig durch, bevor Sie den CheatGuard PC Checker verwenden. Durch die Nutzung unserer Software erklären Sie sich mit diesen Bedingungen einverstanden.</p>
                            
                            <h3>1. Allgemeine Bestimmungen</h3>
                            <p>CheatGuard (im Folgenden als "wir", "uns" oder "unsere" bezeichnet) bietet eine Anti-Cheat-Lösung für Spieleserver-Administratoren zur Überprüfung von Spielern auf unerlaubte Software. Diese Nutzungsbedingungen regeln die Verwendung des CheatGuard PC Checkers.</p>
                            
                            <h3>2. Voraussetzungen für die Nutzung</h3>
                            <p>Um unseren Service nutzen zu können, müssen Sie:</p>
                            <ul>
                                <li>Mindestens 18 Jahre alt sein oder die Zustimmung eines Erziehungsberechtigten haben</li>
                                <li>Über ein gültiges CheatGuard-Konto verfügen</li>
                                <li>Über eine stabile Internetverbindung verfügen</li>
                                <li>Administrative Rechte auf dem zu überprüfenden Computer haben</li>
                            </ul>
                            
                            <h3>3. Funktion des PC Checkers</h3>
                            <p>Der CheatGuard PC Checker ist ein Tool, das:</p>
                            <ul>
                                <li>Nach Ihrem Einverständnis einen Scan Ihres Systems durchführt</li>
                                <li>Nach bekannten Cheat-Signaturen sucht</li>
                                <li>Die Ergebnisse an unseren Server übermittelt und mit dem entsprechenden PIN-Code verknüpft</li>
                                <li>KEINE persönlichen Daten erfasst oder speichert, die nicht für den Anti-Cheat-Prozess relevant sind</li>
                            </ul>
                            
                            <div class="important">
                                <h4>Wichtiger Hinweis:</h4>
                                <p>Der CheatGuard PC Checker ist KEIN Spyware-Tool. Er untersucht lediglich das System auf bekannte Cheat-Signaturen und übermittelt nur die für die Anti-Cheat-Überprüfung relevanten Informationen.</p>
                            </div>
                            
                            <h3>4. Datenschutz und Datensammlung</h3>
                            <p>Bei der Nutzung des PC Checkers sammeln wir folgende Informationen:</p>
                            <ul>
                                <li>Hardware-Informationen (CPU, RAM, GPU)</li>
                                <li>Betriebssystem-Version</li>
                                <li>Installierte Spiele (nur relevante Titel)</li>
                                <li>Prozessliste (zum Zeitpunkt des Scans)</li>
                                <li>Liste der installierten Software (nur relevante Programme)</li>
                                <li>Erkannte Cheat-Signaturen</li>
                            </ul>
                            
                            <p>Wir sammeln KEINE:</p>
                            <ul>
                                <li>Persönlichen Dateien</li>
                                <li>Passwörter oder Anmeldedaten</li>
                                <li>Browserverlauf oder Cookies</li>
                                <li>Persönliche Kommunikation</li>
                                <li>Aktivitäten außerhalb des Scan-Prozesses</li>
                            </ul>
                            
                            <h3>5. Nutzungsbeschränkungen</h3>
                            <p>Es ist Ihnen nicht gestattet:</p>
                            <ol>
                                <li>Den PC Checker zu dekompilieren, zu modifizieren oder dessen Schutzmaßnahmen zu umgehen</li>
                                <li>Die Software für illegale Zwecke zu verwenden</li>
                                <li>Die Software auf Systemen zu verwenden, für die Sie keine Berechtigung haben</li>
                                <li>Die Ergebnisse zu manipulieren oder zu verfälschen</li>
                                <li>Die Software weiterzuverbreiten oder Dritten zugänglich zu machen, ohne unsere ausdrückliche Genehmigung</li>
                            </ol>
                            
                            <h3>6. Haftungsausschluss</h3>
                            <p>Wir bemühen uns, einen zuverlässigen Service anzubieten, jedoch:</p>
                            <ul>
                                <li>Können wir keine 100% Erkennungsrate garantieren</li>
                                <li>Übernehmen wir keine Haftung für falsch-positive Ergebnisse</li>
                                <li>Sind wir nicht verantwortlich für etwaige Systembeeinträchtigungen, die durch die Nutzung unserer Software entstehen könnten</li>
                                <li>Erfolgt die Nutzung auf eigenes Risiko</li>
                            </ul>
                            
                            <div class="note">
                                <h4>Hinweis:</h4>
                                <p>Obwohl unser System gründlich getestet wurde, empfehlen wir, vor dem Scan wichtige Daten zu sichern und alle anderen Programme zu schließen.</p>
                            </div>
                            
                            <h3>7. Aktualisierungen und Änderungen</h3>
                            <p>Wir behalten uns das Recht vor:</p>
                            <ul>
                                <li>Die Software regelmäßig zu aktualisieren</li>
                                <li>Diese Nutzungsbedingungen jederzeit zu ändern</li>
                                <li>Bestimmte Funktionen hinzuzufügen oder zu entfernen</li>
                            </ul>
                            <p>Über wesentliche Änderungen werden Sie entweder per E-Mail oder beim nächsten Login informiert.</p>
                            
                            <h3>8. Kündigung und Beendigung</h3>
                            <p>Wir behalten uns das Recht vor, Ihren Zugang zum PC Checker zu sperren oder zu beenden, wenn:</p>
                            <ul>
                                <li>Sie gegen diese Nutzungsbedingungen verstoßen</li>
                                <li>Sie versuchen, das System zu manipulieren oder zu umgehen</li>
                                <li>Sie die Software für illegale Zwecke nutzen</li>
                                <li>Sie andere Nutzer belästigen oder gefährden</li>
                            </ul>
                            
                            <h3>9. Anwendbares Recht</h3>
                            <p>Diese Nutzungsbedingungen unterliegen dem deutschen Recht. Gerichtsstand ist, soweit gesetzlich zulässig, der Sitz des Betreibers.</p>
                            
                            <h3>10. Kontakt</h3>
                            <p>Bei Fragen zu diesen Nutzungsbedingungen oder zum PC Checker kontaktieren Sie uns bitte unter:</p>
                            <p>support@cheatguard.de</p>
                            
                            <div class="important">
                                <p>Durch die Verwendung des CheatGuard PC Checkers erklären Sie sich mit diesen Nutzungsbedingungen einverstanden. Wenn Sie mit diesen Bedingungen nicht einverstanden sind, verwenden Sie bitte unsere Software nicht.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
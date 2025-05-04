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
    <title>Downloads - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/downloads.css">
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
                    <li class="nav-item">
                        <a href="results.php" class="nav-link">
                            <i class="fas fa-search"></i>
                            <span>Scan-Ergebnisse</span>
                        </a>
                    </li>
                    <li class="nav-item active">
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
                    <h1>Downloads</h1>
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
                    <h2>CheatGuard <span class="accent">Downloads</span></h2>
                    <p>Hier kannst du den CheatGuard PC Checker herunterladen, um Cheats auf einem PC zu erkennen.</p>
                </section>
                
                <!-- Download Section -->
                <section class="download-section content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-download"></i> PC Checker - Die neueste Version</h3>
                    </div>
                    <div class="card-body">
                        <div class="download-info">
                            <div class="software-details">
                                <h4>CheatGuard PC Checker v2.5</h4>
                                <p class="version-info">Veröffentlicht am: 20. April 2025</p>
                                <ul class="feature-list">
                                    <li><i class="fas fa-check"></i> Erkennung von FiveM Cheats</li>
                                    <li><i class="fas fa-check"></i> Erkennung von AltV Cheats</li>
                                    <li><i class="fas fa-check"></i> Erkennung von RageMP Cheats</li>
                                    <li><i class="fas fa-check"></i> Unterstützung für PIN-verifizierte Scans</li>
                                    <li><i class="fas fa-check"></i> Verbessertes Reporting-System</li>
                                </ul>
                                <div class="system-requirements">
                                    <h5>Systemanforderungen:</h5>
                                    <p>Windows 10/11 (64-bit), 4GB RAM, 100MB freier Speicherplatz</p>
                                </div>
                            </div>
                            <div class="download-action">
                                <div class="download-icon">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <a href="downloads/CheatGuard_PC_Checker_v2.5.exe" class="btn primary-btn download-btn">
                                    <i class="fas fa-download"></i> PC Checker herunterladen
                                </a>
                                <p class="file-info">Dateigröße: 25.7 MB</p>
                                <p class="download-count"><i class="fas fa-users"></i> 12,543 Downloads diese Woche</p>
                            </div>
                        </div>
                        
                        <div class="download-instructions">
                            <h4>Installations- und Nutzungsanleitung:</h4>
                            <ol>
                                <li>Lade den PC Checker herunter und führe die .exe-Datei aus.</li>
                                <li>Gib den 6-stelligen PIN-Code ein, den du im <a href="pins.php">PIN-Generator</a> erstellt hast.</li>
                                <li>Erlaube dem Scanner, deinen PC zu überprüfen.</li>
                                <li>Die Ergebnisse werden automatisch mit deinem CheatGuard-Konto verknüpft und erscheinen in den <a href="results.php">Scan-Ergebnissen</a>.</li>
                            </ol>
                        </div>
                        
                        <div class="download-notes">
                            <div class="note-box">
                                <div class="note-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="note-content">
                                    <h5>Wichtig:</h5>
                                    <p>Der CheatGuard PC Checker muss mit Administratorrechten ausgeführt werden, um alle notwendigen Systemdateien überprüfen zu können. Einige Antivirenprogramme könnten den Scanner blockieren - füge ihn in diesem Fall zu den Ausnahmen hinzu.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- FAQ Section -->
                <section class="faq-section content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-question-circle"></i> Häufig gestellte Fragen</h3>
                    </div>
                    <div class="card-body">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Ist der PC Checker sicher zu verwenden?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Ja, der CheatGuard PC Checker ist 100% sicher. Unsere Software sammelt keine persönlichen Daten und scannt nur nach bekannten Cheat-Signaturen. Alle Scan-Ergebnisse werden verschlüsselt übertragen und sind nur für dich sichtbar.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Wie lange dauert ein vollständiger Scan?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Ein vollständiger Scan dauert in der Regel zwischen 2-5 Minuten, abhängig von der Leistung des Computers und der Menge der zu überprüfenden Dateien.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Was passiert, wenn Cheats gefunden werden?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Wenn der Scanner Cheats findet, werden diese mit Details in den Scan-Ergebnissen angezeigt. Der PC Checker entfernt keine Cheats automatisch, sondern meldet nur ihre Existenz.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Wie oft sollte ich den PC Checker aktualisieren?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Wir empfehlen, immer die neueste Version des PC Checkers zu verwenden, da wir regelmäßig neue Cheat-Signaturen hinzufügen und die Erkennungsalgorithmen verbessern.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <script>
        // Toggle FAQ answers
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                const icon = question.querySelector('i');
                
                answer.style.display = answer.style.display === 'block' ? 'none' : 'block';
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            });
        });
        
        // Toggle changelog content
        document.querySelectorAll('.changelog-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const changelog = btn.nextElementSibling;
                changelog.style.display = changelog.style.display === 'block' ? 'none' : 'block';
            });
        });
    </script>
</body>
</html>
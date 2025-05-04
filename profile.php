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

// Check for message in session (e.g., from redirect after account deletion or profile update)
$message = '';
$messageType = '';

if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    
    // Clear the message from session
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Calculate account age
$accountCreated = new DateTime($user['created_at']);
$now = new DateTime();
$accountAge = $accountCreated->diff($now);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - CheatGuard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/dashboard.js" defer></script>
    <script src="js/profile.js" defer></script>
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
                    <li class="nav-item active">
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
                    <h1>Profil</h1>
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
                <!-- Page Title -->
                <section class="welcome-section">
                    <h2>Dein <span class="accent">Profil</span></h2>
                    <p>Verwalte deine persönlichen Informationen und Einstellungen.</p>
                </section>
                
                <?php if (!empty($message)): ?>
                <div class="message-box <?php echo $messageType; ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="profile-layout">
                    <!-- Profile Summary Card -->
                    <section class="profile-summary content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-id-card"></i> Profil-Übersicht</h3>
                        </div>
                        <div class="card-body">
                            <div class="profile-info-section">
                                <div class="avatar-container">
                                    <div class="avatar-preview">
                                        <img src="img/default-avatar.png" alt="Avatar" id="profile-avatar">
                                    </div>
                                    <div class="avatar-actions">
                                        <button class="btn secondary-btn upload-avatar-btn">
                                            <i class="fas fa-camera"></i> Foto ändern
                                        </button>
                                        <input type="file" id="avatar-upload" hidden>
                                    </div>
                                </div>
                                
                                <div class="profile-stats">
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-user-shield"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4>Kontoname</h4>
                                            <p><?php echo htmlspecialchars($user['username']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4>E-Mail</h4>
                                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4>Konto erstellt</h4>
                                            <p><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4>Kontoalter</h4>
                                            <p>
                                                <?php 
                                                if ($accountAge->y > 0) {
                                                    echo $accountAge->y . ' Jahr' . ($accountAge->y > 1 ? 'e' : '') . ', ';
                                                }
                                                echo $accountAge->m . ' Monat' . ($accountAge->m != 1 ? 'e' : '') . ', ';
                                                echo $accountAge->d . ' Tag' . ($accountAge->d != 1 ? 'e' : '');
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4>Letzter Login</h4>
                                            <p>
                                                <?php 
                                                echo $user['last_login'] 
                                                    ? date('d.m.Y H:i', strtotime($user['last_login'])) 
                                                    : 'Noch nie';
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-crown"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4>Konto-Status</h4>
                                            <p class="account-status premium">Lifetime</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Edit Profile Form -->
                    <section class="edit-profile content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-edit"></i> Profil bearbeiten</h3>
                        </div>
                        <div class="card-body">
                            <form class="profile-form">
                                <div class="form-section">
                                    <h4>Persönliche Daten</h4>
                                    
                                    <div class="form-group">
                                        <label for="username">Benutzername</label>
                                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                        <p class="form-hint">Der Benutzername kann nicht geändert werden.</p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">E-Mail-Adresse</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                    </div>
                                </div>
                                
                                <div class="divider"></div>
                                
                                <div class="form-section">
                                    <h4>Passwort ändern</h4>
                                    
                                    <div class="form-group">
                                        <label for="current_password">Aktuelles Passwort</label>
                                        <input type="password" id="current_password" name="current_password">
                                        <p class="form-hint">Bitte gib dein aktuelles Passwort ein, um Änderungen zu bestätigen.</p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">Neues Passwort</label>
                                        <input type="password" id="new_password" name="new_password">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Passwort bestätigen</label>
                                        <input type="password" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                                
                                <div class="divider"></div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn primary-btn">
                                        <i class="fas fa-save"></i> Änderungen speichern
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                
                    <!-- Account Deletion Section -->
                    <section class="danger-zone content-card">
                        <div class="card-header danger">
                            <h3><i class="fas fa-exclamation-triangle"></i> Gefahrenzone</h3>
                        </div>
                        <div class="card-body">
                            <div class="warning-box severe">
                                <i class="fas fa-skull-crossbones"></i>
                                <div>
                                    <h4>Konto löschen</h4>
                                    <p>Diese Aktion ist permanent und kann nicht rückgängig gemacht werden. Alle deine Daten werden unwiderruflich gelöscht.</p>
                                </div>
                            </div>
                            
                            <div class="delete-account-action">
                                <button class="btn danger-btn" id="delete-account-btn">
                                    <i class="fas fa-trash-alt"></i> Konto unwiderruflich löschen
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Delete Account Confirmation Modal -->
    <div class="modal" id="delete-account-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Konto löschen bestätigen</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="warning-box severe">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Bist du dir sicher, dass du dein Konto löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.</p>
                </div>
                
                <div class="confirmation-input">
                    <label for="delete-confirmation">Gib "DELETE" ein, um zu bestätigen:</label>
                    <input type="text" id="delete-confirmation" placeholder="DELETE">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn secondary-btn" id="cancel-delete">Abbrechen</button>
                <button class="btn danger-btn" id="confirm-delete" disabled>Konto löschen</button>
            </div>
        </div>
    </div>
</body>
</html>
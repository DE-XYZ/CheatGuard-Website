<?php
// Start session
session_start();

// Include database configuration
require_once 'php/config.php';
require_once 'php/auth.php';

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Bitte fülle alle Felder aus.";
    } else {
        // Attempt to login
        $result = login($username, $password);
        
        if ($result['success']) {
            // Set session variables
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CheatGuard</title>
    <link rel="stylesheet" href="css/auth-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/auth.js" defer></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.html">
                    <span class="logo-text">Cheat<span class="accent">Guard</span></span>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="index.html#pricing">Pricing</a></li>
                <li><a href="index.html#features">Features</a></li>
                <li><a href="index.html#guide">Guide</a></li>
                <li><a href="index.html#terms">Terms</a></li>
                <li><a href="index.html#policy">Policy</a></li>
                <li><a href="index.html#changelogs">Changelogs</a></li>
                <li><a href="https://discord.gg/CheatGuard"><i class="fab fa-discord"></i> Discord</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <span class="logo-text">Cheat<span class="accent">Guard</span></span>
                <h2>Login</h2>
            </div>
            <div class="auth-content">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form class="auth-form" action="login.php" method="post">
                    <div class="form-group">
                        <label for="username">Benutzername</label>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Dein Benutzername" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Passwort</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Dein Passwort" required>
                    </div>
                    
                    <div class="forgot-password">
                        <a href="forgot-password.php">Passwort vergessen?</a>
                    </div>
                    
                    <button type="submit" class="btn primary-btn auth-btn">
                        <i class="fas fa-sign-in-alt"></i> Einloggen
                    </button>
                </form>
                
                <div class="divider">oder</div>
                
                <div class="oauth-buttons">
                    <a href="#" class="oauth-btn" title="Mit Discord anmelden">
                        <i class="fab fa-discord"></i>
                    </a>
                    <a href="#" class="oauth-btn" title="Mit Google anmelden">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="oauth-btn" title="Mit GitHub anmelden">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
                
                <div class="auth-footer">
                    Noch kein Konto? <a href="register.php">Jetzt registrieren</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle header scroll effect
        const header = document.querySelector('header');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Add scrolled class on page load for login page
        window.addEventListener('DOMContentLoaded', function() {
            header.classList.add('scrolled');
        });
    </script>
</body>
</html>
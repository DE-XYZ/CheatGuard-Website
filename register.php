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

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "Bitte fülle alle Felder aus.";
    } else if ($password !== $confirmPassword) {
        $error = "Die Passwörter stimmen nicht überein.";
    } else if (strlen($password) < 8) {
        $error = "Das Passwort muss mindestens 8 Zeichen lang sein.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Bitte gib eine gültige E-Mail-Adresse ein.";
    } else {
        // Attempt to register
        $result = register($username, $email, $password);
        
        if ($result['success']) {
            $success = "Registrierung erfolgreich! Du kannst dich jetzt einloggen.";
            // Clear form data
            $username = $email = '';
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
    <title>Registrieren - CheatGuard</title>
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
                <h2>Registrieren</h2>
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
                
                <form class="auth-form" action="register.php" method="post">
                    <div class="form-group">
                        <label for="username">Benutzername</label>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Wähle einen Benutzernamen" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-Mail</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="Deine E-Mail-Adresse" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Passwort</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Erstelle ein Passwort" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Passwort bestätigen</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Bestätige dein Passwort" required>
                    </div>
                    
                    <button type="submit" class="btn primary-btn auth-btn">
                        <i class="fas fa-user-plus"></i> Registrieren
                    </button>
                </form>
                
                <div class="divider">oder</div>
                
                <div class="oauth-buttons">
                    <a href="#" class="oauth-btn" title="Mit Discord registrieren">
                        <i class="fab fa-discord"></i>
                    </a>
                    <a href="#" class="oauth-btn" title="Mit Google registrieren">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="oauth-btn" title="Mit GitHub registrieren">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
                
                <div class="auth-footer">
                    Bereits registriert? <a href="login.php">Jetzt einloggen</a>
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
        
        // Add scrolled class on page load for register page
        window.addEventListener('DOMContentLoaded', function() {
            header.classList.add('scrolled');
        });
    </script>
</body>
</html>
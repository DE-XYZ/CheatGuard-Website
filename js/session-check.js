// js/session-check.js

document.addEventListener('DOMContentLoaded', function() {
    // Function to check if user is logged in
    function checkLoginStatus() {
        // Use AJAX to check session status
        fetch('php/check_session.php')
            .then(response => response.json())
            .then(data => {
                const loginBtn = document.querySelector('.login-btn');
                
                if (loginBtn) {
                    if (data.loggedIn) {
                        // User is logged in, change to Dashboard
                        loginBtn.textContent = 'Dashboard';
                        loginBtn.setAttribute('href', 'dashboard.php');
                    } else {
                        // User is not logged in, keep as Login
                        loginBtn.textContent = 'Login';
                        loginBtn.setAttribute('href', '#login');
                    }
                }
            })
            .catch(error => {
                console.error('Error checking login status:', error);
            });
    }
    
    // Check login status on page load
    checkLoginStatus();
});
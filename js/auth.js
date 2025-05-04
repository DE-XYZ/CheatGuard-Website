// js/auth.js

document.addEventListener('DOMContentLoaded', function() {
    // Navigation menu toggle for mobile
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
    }
    
    // Close mobile menu when clicking a link
    const navItems = document.querySelectorAll('.nav-links a');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (hamburger.classList.contains('active')) {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
            }
        });
    });
    
    // Password validation
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const registerForm = document.querySelector('form.auth-form');
    
    if (registerForm && confirmPasswordField) {
        registerForm.addEventListener('submit', function(e) {
            if (passwordField && confirmPasswordField) {
                if (passwordField.value !== confirmPasswordField.value) {
                    e.preventDefault();
                    showError('Die Passwörter stimmen nicht überein.');
                }
                
                if (passwordField.value.length < 8) {
                    e.preventDefault();
                    showError('Das Passwort muss mindestens 8 Zeichen lang sein.');
                }
            }
        });
        
        // Live password match validation
        confirmPasswordField.addEventListener('input', function() {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.setCustomValidity('Passwörter stimmen nicht überein');
            } else {
                confirmPasswordField.setCustomValidity('');
            }
        });
    }
    
    // Show error message function
    function showError(message) {
        // Check if error container already exists
        let errorContainer = document.querySelector('.error-message');
        
        if (!errorContainer) {
            // Create error container if it doesn't exist
            errorContainer = document.createElement('div');
            errorContainer.className = 'error-message';
            errorContainer.innerHTML = `<i class="fas fa-exclamation-circle"></i> <span>${message}</span>`;
            
            // Insert at the beginning of auth-content
            const authContent = document.querySelector('.auth-content');
            if (authContent) {
                authContent.insertBefore(errorContainer, authContent.firstChild);
            }
        } else {
            // Update existing error message
            const errorSpan = errorContainer.querySelector('span');
            if (errorSpan) {
                errorSpan.textContent = message;
            } else {
                errorContainer.innerHTML = `<i class="fas fa-exclamation-circle"></i> <span>${message}</span>`;
            }
        }
    }
    
    // Form input animations
    const formInputs = document.querySelectorAll('.form-input');
    formInputs.forEach(input => {
        // Add focus effect
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        // Remove focus effect if the field is empty
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Check if field has content on load
        if (input.value.trim() !== '') {
            input.parentElement.classList.add('focused');
        }
    });
    
    // OAuth button click handlers
    const oauthButtons = document.querySelectorAll('.oauth-btn');
    oauthButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // This is a placeholder for actual OAuth implementation
            const provider = this.querySelector('i').className.split(' ')[1].replace('fa-', '');
            alert(`OAuth mit ${provider} wird noch implementiert.`);
            
            // In a real implementation, you would redirect to the OAuth provider
            // or handle the authentication process appropriately
        });
    });
});
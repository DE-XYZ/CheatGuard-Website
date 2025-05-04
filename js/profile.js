document.addEventListener('DOMContentLoaded', function() {
    // Avatar upload handling
    const avatarUploadBtn = document.querySelector('.upload-avatar-btn');
    const avatarUploadInput = document.getElementById('avatar-upload');
    const profileAvatar = document.getElementById('profile-avatar');
    const headerAvatar = document.getElementById('header-avatar');
    const sidebarAvatar = document.getElementById('user-avatar');
    
    if (avatarUploadBtn && avatarUploadInput) {
        avatarUploadBtn.addEventListener('click', function() {
            avatarUploadInput.click();
        });
        
        avatarUploadInput.addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Update all avatar images on the page
                    if (profileAvatar) profileAvatar.src = e.target.result;
                    if (headerAvatar) headerAvatar.src = e.target.result;
                    if (sidebarAvatar) sidebarAvatar.src = e.target.result;
                };
                
                reader.readAsDataURL(event.target.files[0]);
                
                // In a real application, you would upload the file to the server here
                console.log('Avatar would be uploaded to server');
            }
        });
    }
    
    // Email validation function
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Profile form AJAX submission
    const profileForm = document.querySelector('.profile-form');
    
    if (profileForm) {
        const emailInput = document.getElementById('email');
        // Changed from const to let to allow reassignment
        let initialEmail = emailInput ? emailInput.value : '';
        
        profileForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Get form fields
            const emailInput = document.getElementById('email');
            const currentPasswordInput = document.getElementById('current_password');
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            // Basic validations
            if (emailInput && !emailInput.value.trim()) {
                showMessage('E-Mail-Adresse darf nicht leer sein.', 'error');
                emailInput.focus();
                return;
            }
            
            // Validate email format
            if (emailInput && !isValidEmail(emailInput.value.trim())) {
                showMessage('Bitte gib eine gültige E-Mail-Adresse ein.', 'error');
                emailInput.focus();
                return;
            }
            
            // Check if anything has changed
            const emailChanged = emailInput && emailInput.value.trim() !== initialEmail;
            const passwordChangeAttempted = newPasswordInput.value || confirmPasswordInput.value;
            
            // If nothing changed, show message and return
            if (!emailChanged && !passwordChangeAttempted) {
                showMessage('Keine Änderungen vorgenommen.', 'info');
                return;
            }
            
            // If changing email or password, require current password
            if ((emailChanged || passwordChangeAttempted) && !currentPasswordInput.value) {
                showMessage('Bitte gib dein aktuelles Passwort ein, um Änderungen zu bestätigen.', 'error');
                currentPasswordInput.focus();
                return;
            }
            
            // Validation for password change
            if (passwordChangeAttempted) {
                if (newPasswordInput.value !== confirmPasswordInput.value) {
                    showMessage('Die Passwörter stimmen nicht überein!', 'error');
                    confirmPasswordInput.focus();
                    return;
                }
                
                // Password strength validation (optional)
                if (newPasswordInput.value.length < 8) {
                    showMessage('Das neue Passwort muss mindestens 8 Zeichen lang sein.', 'error');
                    newPasswordInput.focus();
                    return;
                }
            }
            
            // Show loading state
            const submitBtn = profileForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wird gespeichert...';
            
            // Create form data for the request
            const formData = new FormData(profileForm);
            
            // Send request to profile update PHP endpoint
            fetch('php/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Handle redirect if necessary
                if (data.redirect) {
                    window.location.href = data.redirect_url;
                    return;
                }
                
                // Show message
                showMessage(data.message, data.success ? 'success' : 'error');
                
                // Update the initial email if it was successfully changed
                if (data.success && emailInput) {
                    initialEmail = emailInput.value.trim();
                }
                
                // Reset fields if password was changed successfully
                if (data.success && newPasswordInput.value) {
                    currentPasswordInput.value = '';
                    newPasswordInput.value = '';
                    confirmPasswordInput.value = '';
                }
                
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Ein Fehler ist aufgetreten. Bitte versuche es später erneut.', 'error');
                
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }
    
    // Helper function to display messages
    function showMessage(message, type) {
        // Look for existing message box
        let messageBox = document.querySelector('.message-box');
        
        // If no message box exists, create one
        if (!messageBox) {
            messageBox = document.createElement('div');
            messageBox.className = 'message-box ' + type;
            
            // Add the message box after the welcome section
            const welcomeSection = document.querySelector('.welcome-section');
            if (welcomeSection && welcomeSection.parentNode) {
                welcomeSection.parentNode.insertBefore(messageBox, welcomeSection.nextSibling);
            }
        } else {
            // Update existing message box
            messageBox.className = 'message-box ' + type;
        }
        
        // Set the message content with appropriate icon
        const iconClass = type === 'success' ? 'fa-check-circle' : 
                        type === 'info' ? 'fa-info-circle' : 'fa-exclamation-circle';
        
        messageBox.innerHTML = `<i class="fas ${iconClass}"></i><span>${message}</span>`;
        
        // Scroll to message box
        messageBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Delete account modal functionality
    const deleteAccountBtn = document.getElementById('delete-account-btn');
    const deleteAccountModal = document.getElementById('delete-account-modal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    const deleteConfirmationInput = document.getElementById('delete-confirmation');
    
    // Function to show modal
    function showModal() {
        if (deleteAccountModal) {
            deleteAccountModal.style.display = 'flex';
        }
    }
    
    // Function to hide modal
    function hideModal() {
        if (deleteAccountModal) {
            deleteAccountModal.style.display = 'none';
            if (deleteConfirmationInput) {
                deleteConfirmationInput.value = '';
            }
        }
    }
    
    // Show modal when delete account button is clicked
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', showModal);
    }
    
    // Hide modal when close button or cancel button is clicked
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', hideModal);
    }
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', hideModal);
    }
    
    // Close modal when clicking outside the modal content
    if (deleteAccountModal) {
        deleteAccountModal.addEventListener('click', function(event) {
            if (event.target === deleteAccountModal) {
                hideModal();
            }
        });
    }
    
    // Enable/disable confirm delete button based on confirmation text
    if (deleteConfirmationInput && confirmDeleteBtn) {
        deleteConfirmationInput.addEventListener('input', function() {
            confirmDeleteBtn.disabled = this.value !== 'DELETE';
        });
        
        // Add functionality to confirm delete button
        confirmDeleteBtn.addEventListener('click', function() {
            if (deleteConfirmationInput.value === 'DELETE') {
                // Disable the button to prevent multiple clicks
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wird gelöscht...';
                
                // Create form data for the request
                const formData = new FormData();
                formData.append('confirmation', 'DELETE');
                
                // Send request to delete account PHP endpoint
                fetch('php/delete_account.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideModal();
                    
                    // Create message box
                    const messageBox = document.createElement('div');
                    messageBox.className = data.success ? 'message-box success' : 'message-box error';
                    messageBox.innerHTML = `<i class="fas ${data.success ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${data.message}</span>`;
                    
                    // Display message
                    const welcomeSection = document.querySelector('.welcome-section');
                    if (welcomeSection && welcomeSection.parentNode) {
                        welcomeSection.parentNode.insertBefore(messageBox, welcomeSection.nextSibling);
                    }
                    
                    // If successful, redirect to logout page after a short delay
                    if (data.success) {
                        setTimeout(function() {
                            window.location.href = 'php/logout.php';
                        }, 3000);
                    } else {
                        // Re-enable the button if there was an error
                        confirmDeleteBtn.disabled = false;
                        confirmDeleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Konto löschen';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideModal();
                    
                    // Create error message
                    const messageBox = document.createElement('div');
                    messageBox.className = 'message-box error';
                    messageBox.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Ein Fehler ist aufgetreten. Bitte versuche es später erneut.</span>';
                    
                    // Display error message
                    const welcomeSection = document.querySelector('.welcome-section');
                    if (welcomeSection && welcomeSection.parentNode) {
                        welcomeSection.parentNode.insertBefore(messageBox, welcomeSection.nextSibling);
                    }
                    
                    // Re-enable the button
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Konto löschen';
                });
            }
        });
    }
});
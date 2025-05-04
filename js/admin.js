/**
 * JavaScript for Admin panel
 */
document.addEventListener('DOMContentLoaded', function() {
    // Toggle mobile menu
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-mobile-open');
        });
    }
    
    // User dropdown toggle
    const userDropdownBtn = document.querySelector('.user-dropdown-btn');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (userDropdownBtn) {
        userDropdownBtn.addEventListener('click', function() {
            dropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.user-dropdown')) {
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                }
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    }
    
    // Confirmation for critical actions
    const criticalForms = document.querySelectorAll('form[data-confirm]');
    criticalForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const confirmMessage = form.getAttribute('data-confirm');
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });
});
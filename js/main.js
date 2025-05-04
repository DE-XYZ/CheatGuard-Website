// js/main.js

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
    
    // Header scroll effect
    const header = document.querySelector('header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    
    // Smooth scroll for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            // Skip if it's the login button and it's now pointing to dashboard.php
            if (this.classList.contains('login-btn') && this.getAttribute('href') === 'dashboard.php') {
                return; // Let the browser handle the navigation to dashboard.php
            }
            
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const headerHeight = document.querySelector('header').offsetHeight;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            } else if (targetId === '#login') {
                // If login link is clicked and user is not logged in
                window.location.href = 'login.php';
            }
        });
    });
    
    // Update login button click handler
    const loginBtn = document.querySelector('.login-btn');
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            if (this.getAttribute('href') === '#login') {
                e.preventDefault();
                window.location.href = 'login.php';
            }
            // If href is dashboard.php, let the default behavior handle it
        });
    }
    
    // Rest of the JavaScript remains the same
    
    // Scroll indicator click
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', function() {
            const pricingSection = document.getElementById('pricing');
            if (pricingSection) {
                const headerHeight = document.querySelector('header').offsetHeight;
                const targetPosition = pricingSection.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Animation for dashboard preview
    const scanStatuses = document.querySelectorAll('.stat .value');
    if (scanStatuses.length > 0) {
        const statusMessages = ['Checking...', 'Scanning...', 'Analyzing...', 'No Cheats Found'];
        const indicators = ['var(--warning-color)', 'var(--warning-color)', 'var(--warning-color)', 'var(--success-color)'];
        
        let currentIndex = 0;
        setInterval(() => {
            currentIndex = (currentIndex + 1) % statusMessages.length;
            
            scanStatuses.forEach((status, index) => {
                // Add a slight delay for each status to make it look more realistic
                setTimeout(() => {
                    status.textContent = statusMessages[currentIndex];
                    status.style.color = indicators[currentIndex];
                }, index * 300);
            });
            
        }, 3000);
    }
    
    // Reveal animations on scroll
    const revealElements = document.querySelectorAll('.pricing-card, .feature-block');
    
    function revealOnScroll() {
        const windowHeight = window.innerHeight;
        
        revealElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            if (elementTop < windowHeight - 50) {
                element.style.opacity = 1;
                element.style.transform = element.classList.contains('featured') 
                    ? 'scale(1.05)' 
                    : 'translateY(0)';
            }
        });
    }
    
    // Set initial state for animations
    revealElements.forEach(element => {
        element.style.opacity = 0;
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        element.style.transform = 'translateY(30px)';
    });
    
    // Call on load and scroll
    revealOnScroll();
    window.addEventListener('scroll', revealOnScroll);
    
    // Dashboard animation
    function animateDashboard() {
        const codeLines = document.querySelectorAll('.code-line');
        const progressBar = document.querySelector('.progress-bar');
        const scanningIndicator = document.querySelector('.scanning');
        
        // Simulate code lines appearing one by one
        codeLines.forEach((line, index) => {
            if (index < codeLines.length - 1) {
                line.style.opacity = 0;
                setTimeout(() => {
                    line.style.opacity = 1;
                }, 500 + (index * 700));
            }
        });
        
        // After all code lines appear, complete the scan
        setTimeout(() => {
            if (progressBar) progressBar.style.width = '100%';
            if (scanningIndicator) {
                scanningIndicator.style.backgroundColor = 'var(--success-color)';
                scanningIndicator.style.boxShadow = '0 0 10px var(--success-color)';
            }
            
            const statusIndicator = document.querySelector('.status span');
            if (statusIndicator) statusIndicator.textContent = 'Scan Complete!';
            
        }, 4000);
    }
    
    // Run dashboard animation with slight delay after page load
    setTimeout(animateDashboard, 1000);
});
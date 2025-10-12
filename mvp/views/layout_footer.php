        </div>
    </div>

    <script>
        // Mobile menu toggle functionality
        (function() {
            const menuToggle = document.getElementById('mobile-menu-toggle');
            const sidebar = document.getElementById('clio-sidebar');
            const overlay = document.getElementById('mobile-overlay');
            
            if (menuToggle && sidebar && overlay) {
                // Toggle menu
                function toggleMenu() {
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('active');
                    
                    // Update aria-expanded for accessibility
                    const isOpen = sidebar.classList.contains('open');
                    menuToggle.setAttribute('aria-expanded', isOpen);
                }
                
                // Open/close menu
                menuToggle.addEventListener('click', toggleMenu);
                
                // Close menu when clicking overlay
                overlay.addEventListener('click', toggleMenu);
                
                // Close menu when clicking a nav link (for better UX)
                const navLinks = sidebar.querySelectorAll('.clio-sidebar-nav a');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (sidebar.classList.contains('open')) {
                            toggleMenu();
                        }
                    });
                });
                
                // Close menu on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                        toggleMenu();
                    }
                });
            }
        })();
    </script>
</body>
</html>


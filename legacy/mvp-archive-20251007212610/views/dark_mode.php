<?php
/**
 * Dark Mode Component
 * Provides dark mode toggle functionality
 */
?>

<style>
/* Dark mode variables */
:root {
    --bg-primary: #f6f7fb;
    --bg-secondary: #fff;
    --text-primary: #1a2b3b;
    --text-secondary: #65748b;
    --text-muted: #495057;
    --border-color: #eef2f7;
    --accent-color: #0b6bcb;
    --accent-hover: #0a5bb8;
    --shadow: rgba(0,0,0,0.1);
}

[data-theme="dark"] {
    --bg-primary: #1a1a1a;
    --bg-secondary: #2d2d2d;
    --text-primary: #ffffff;
    --text-secondary: #b0b0b0;
    --text-muted: #d0d0d0;
    --border-color: #404040;
    --accent-color: #4a9eff;
    --accent-hover: #3d8bff;
    --shadow: rgba(0,0,0,0.3);
}

/* Apply dark mode styles */
[data-theme="dark"] body {
    background: var(--bg-primary);
    color: var(--text-primary);
}

[data-theme="dark"] .sidebar {
    background: var(--bg-secondary);
    border-right-color: var(--border-color);
}

[data-theme="dark"] .content-header {
    background: var(--bg-secondary);
    border-bottom-color: var(--border-color);
}

[data-theme="dark"] .content-body {
    background: var(--bg-primary);
}

[data-theme="dark"] .panel {
    background: var(--bg-secondary);
    border-color: var(--border-color);
    box-shadow: 0 1px 3px var(--shadow);
}

[data-theme="dark"] .table {
    background: var(--bg-secondary);
    box-shadow: 0 1px 3px var(--shadow);
}

[data-theme="dark"] .table th {
    background: var(--bg-primary);
    color: var(--text-primary);
}

[data-theme="dark"] .table td {
    color: var(--text-muted);
    border-bottom-color: var(--border-color);
}

[data-theme="dark"] input[type=text],
[data-theme="dark"] input[type=email],
[data-theme="dark"] input[type=tel],
[data-theme="dark"] select,
[data-theme="dark"] textarea {
    background: var(--bg-secondary);
    border-color: var(--border-color);
    color: var(--text-primary);
}

[data-theme="dark"] input[type=text]:focus,
[data-theme="dark"] input[type=email]:focus,
[data-theme="dark"] input[type=tel]:focus,
[data-theme="dark"] select:focus,
[data-theme="dark"] textarea:focus {
    border-color: var(--accent-color);
    background: var(--bg-secondary);
}

[data-theme="dark"] .btn {
    background: var(--accent-color);
    color: #fff;
}

[data-theme="dark"] .btn:hover {
    background: var(--accent-hover);
}

[data-theme="dark"] .btn.secondary {
    background: var(--border-color);
    color: var(--text-primary);
}

[data-theme="dark"] .btn.secondary:hover {
    background: var(--text-secondary);
}

[data-theme="dark"] .muted {
    color: var(--text-secondary);
}

[data-theme="dark"] a {
    color: var(--accent-color);
}

[data-theme="dark"] .nav-link {
    color: var(--text-secondary);
}

[data-theme="dark"] .nav-link:hover {
    background: var(--bg-primary);
    color: var(--text-primary);
}

[data-theme="dark"] .nav-link.active {
    background: rgba(74, 158, 255, 0.1);
    color: var(--accent-color);
}

[data-theme="dark"] .client-card,
[data-theme="dark"] .project-card {
    background: var(--bg-secondary);
    border-color: var(--border-color);
}

[data-theme="dark"] .client-card:hover,
[data-theme="dark"] .project-card:hover {
    box-shadow: 0 4px 12px var(--shadow);
}

[data-theme="dark"] .modal-content {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

[data-theme="dark"] .modal-header {
    border-bottom-color: var(--border-color);
}

[data-theme="dark"] .modal-footer {
    border-top-color: var(--border-color);
}

[data-theme="dark"] .toast {
    background: var(--bg-secondary);
    border-color: var(--border-color);
    color: var(--text-primary);
}

[data-theme="dark"] .breadcrumb-link {
    color: var(--accent-color);
}

[data-theme="dark"] .breadcrumb-text {
    color: var(--text-secondary);
}

[data-theme="dark"] .breadcrumb-item.active .breadcrumb-text {
    color: var(--text-primary);
}

/* Dark mode toggle button */
.dark-mode-toggle {
    position: fixed;
    bottom: 20px;
    left: 20px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 50%;
    width: 48px;
    height: 48px;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 0 2px 8px var(--shadow);
    transition: all 0.2s ease;
    z-index: 1001;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-primary);
}

.dark-mode-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px var(--shadow);
}

.dark-mode-toggle:active {
    transform: scale(0.95);
}

/* Smooth transitions */
* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
</style>

<script>
// Dark mode functionality
window.DarkMode = {
    init: function() {
        // Check for saved theme preference or default to light mode
        const savedTheme = localStorage.getItem('theme') || 'light';
        this.setTheme(savedTheme);
        
        // Create toggle button
        this.createToggleButton();
    },
    
    setTheme: function(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Update toggle button icon
        const toggle = document.getElementById('dark-mode-toggle');
        if (toggle) {
            toggle.textContent = theme === 'dark' ? '☀️' : '🌙';
            toggle.title = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
        }
    },
    
    toggle: function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
        
        // Show notification
        if (window.ToastManager) {
            ToastManager.info(`Switched to ${newTheme} mode`);
        }
    },
    
    createToggleButton: function() {
        const toggle = document.createElement('button');
        toggle.id = 'dark-mode-toggle';
        toggle.className = 'dark-mode-toggle';
        toggle.textContent = '🌙';
        toggle.title = 'Switch to dark mode';
        toggle.addEventListener('click', () => this.toggle());
        
        document.body.appendChild(toggle);
    }
};

// Initialize dark mode when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    DarkMode.init();
});

// Add keyboard shortcut for dark mode (Ctrl/Cmd + D)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        DarkMode.toggle();
    }
});
</script>














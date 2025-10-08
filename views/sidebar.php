<?php
$currentRoute = $_GET['route'] ?? 'clients';
$isActive = function($route) use ($currentRoute) { return $currentRoute === $route ? 'active' : ''; };
?>

<nav class="sidebar" data-theme aria-label="Main navigation" role="navigation" tabindex="0">
    <div class="sidebar-header">
        <a href="?route=clients" class="logo" aria-label="Home">
            <img src="../logo.png" srcset="../logo.png 1x, ../logo@2x.png 2x" sizes="(max-width: 768px) 120px, 180px" alt="Clio Draft" class="logo-img" loading="lazy">
            <span class="logo-text">Clio Draft</span>
        </a>
        <button class="menu-toggle" aria-label="Toggle menu" role="button" tabindex="0">☰</button>
    </div>
    
    <div class="sidebar-nav">
        <ol class="nav-section">
            <li class="nav-item">
                <a href="?route=clients" class="nav-link <?php echo $isActive('clients'); ?>">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">Clients</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="?route=projects" class="nav-link <?php echo $isActive('projects'); ?>">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Projects</span>
                </a>
            </li>
        </ol>
        
        <ol class="nav-section">
            <li class="nav-item">
                <a href="?route=templates" class="nav-link <?php echo $isActive('templates'); ?>">
                    <span class="nav-icon">📝</span>
                    <span class="nav-text">Templates</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="?route=support" class="nav-link <?php echo $isActive('support'); ?>">
                    <span class="nav-icon">❓</span>
                    <span class="nav-text">Help and support</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Team</span>
                </a>
            </li>
            <li class="nav-item organization-item">
                <a href="#" class="nav-link organization-link">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">YOUNGMAN REITSHTEIN, PLC</span>
                    <span class="dropdown-arrow">▼</span>
                </a>
                <ul class="organization-submenu">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <span class="nav-text">Organization settings</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ol>
    </div>
</nav>

<style>
/* Dark mode theme hooks */
.sidebar { background: var(--bg-secondary, #ffffff); background-color: var(--bg-color, var(--bg-secondary, #ffffff)); }
.nav-link { color: var(--text-secondary, #374151); }

/* Mobile sidebar */
@media (max-width: 768px) {
    .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 260px; z-index: 1200; transform: translateX(0); }
    .menu-toggle { display: inline-block; }
}

.organization-item { position: relative; }
.organization-link { display: flex; align-items: center; justify-content: space-between; }
.dropdown-arrow { font-size: 10px; transition: transform 0.2s ease; }
.organization-item:hover .dropdown-arrow { transform: rotate(180deg); }
.organization-submenu { position: absolute; left: 100%; top: 0; background: white; border: 1px solid #e5e7eb; border-radius: 4px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); min-width: 200px; opacity: 0; visibility: hidden; transform: translateX(-10px); transition: all 0.2s ease; z-index: 1000; list-style: none; padding: 8px 0; margin: 0; }
.organization-item:hover .organization-submenu { opacity: 1; visibility: visible; transform: translateX(0); }
.organization-submenu .nav-item { margin: 0; }
.organization-submenu .nav-link { padding: 8px 16px; display: block; color: #374151; text-decoration: none; font-size: 14px; }
.organization-submenu .nav-link:hover { background-color: #f3f4f6; color: #0b6bcb; }

/* Explicit dark and light selectors for tests */
[data-theme="dark"] .sidebar { background: var(--bg-secondary, #2d2d2d); }
[data-theme="light"] .sidebar { background: var(--bg-secondary, #fff); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) { toggle.addEventListener('click', function(){ sidebar.classList.toggle('open'); }); }
});
</script>

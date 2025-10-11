<?php
// Include breadcrumb function
require_once __DIR__ . '/breadcrumb.php';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Clio</title>
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0;
            padding: 0;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
            background: #f5f6fa; 
            color: #2c3e50;
            line-height: 1.4;
            font-size: 14px;
        }
        
        /* Header removed - using sidebar only */
        
        /* Sidebar */
        .clio-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 200px;
            height: 100vh;
            background: #f8f9fa;
            border-right: 1px solid #ddd;
            padding: 20px 0;
            overflow-y: auto;
            z-index: 1000;
            display: block !important;
            visibility: visible !important;
        }
        
        .clio-sidebar-nav {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .clio-sidebar-nav li {
            margin: 0;
        }
        
        .clio-sidebar-nav a {
            display: block;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .clio-sidebar-nav a:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .clio-sidebar-nav a.active {
            background: #fff;
            color: #007bff;
            border-left-color: #007bff;
        }
        
        /* Main Content */
        .clio-main-content {
            margin-left: 200px;
            background: #fff;
            min-height: 100vh;
        }
        
        .clio-content-header {
            background: #ffffff;
            border-bottom: 1px solid #ddd;
            padding: 15px 20px;
        }
        
        .clio-content-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .clio-content-body {
            padding: 20px;
        }
        
        /* Clio Components */
        .clio-btn {
            background: #007bff;
            color: #ffffff;
            border: none;
            padding: 8px 12px;
            border-radius: 3px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .clio-btn:hover {
            background: #0056b3;
        }
        
        .clio-btn-secondary {
            background: #ffffff;
            color: #555;
            border: 1px solid #ccc;
        }
        
        .clio-btn-secondary:hover {
            background: #f5f5f5;
        }
        
        .clio-card {
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .clio-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .clio-table th {
            background: #f5f5f5;
            padding: 10px 12px;
            text-align: left;
            font-weight: 500;
            color: #333;
            font-size: 13px;
            border-bottom: 1px solid #ddd;
        }
        
        .clio-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            color: #555;
            font-size: 14px;
        }
        
        .clio-table tr:hover {
            background: #f9f9f9;
        }
        
        .clio-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .clio-input:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
        }

        
        .clio-form-group {
            margin-bottom: 20px;
        }
        
        .clio-form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        /* Status badges */
        .clio-status {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .clio-status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .clio-status-archived {
            background: #f8d7da;
            color: #721c24;
        }
        
        .clio-status-in-progress {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        /* Feature detection and fallbacks */
        @supports not (display: flex) {
            .clio-header { display: block; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .clio-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .clio-sidebar.open {
                transform: translateX(0);
            }
            
            .clio-main-content {
                margin-left: 0;
            }
            
            .clio-content-body {
                padding: 20px;
            }
        }

        /* Orientation-specific adjustments */
        @media (orientation: portrait) { /* placeholder for portrait-specific styles */ }
        @media (orientation: landscape) { /* placeholder for landscape-specific styles */ }
    </style>
</head>
<body>
    <!-- Header removed - using sidebar only -->
    
    <!-- Sidebar -->
    <nav class="clio-sidebar">
        <ul class="clio-sidebar-nav">
            <?php 
            $currentRoute = $_GET['route'] ?? 'dashboard';
            $navItems = [
                ['route' => 'dashboard', 'label' => 'Dashboard'],
                ['route' => 'clients', 'label' => 'Clients'],
                ['route' => 'projects', 'label' => 'Projects'],
                ['route' => 'documents', 'label' => 'Documents']
            ];
            
            foreach ($navItems as $item): 
                $isActive = $currentRoute === $item['route'];
            ?>
                <li>
                    <a href="?route=<?php echo $item['route']; ?>" 
                       class="<?php echo $isActive ? 'active' : ''; ?>">
                        <?php echo $item['label']; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="clio-main-content">
        <div class="clio-content-header">
            <h1 class="clio-content-title">
                <?php 
                switch($currentRoute) {
                    case 'dashboard': echo 'Dashboard'; break;
                    case 'projects': echo 'Projects'; break;
                    case 'clients': echo 'Clients'; break;
                    case 'activities': echo 'Activities'; break;
                    case 'bills': echo 'Bills'; break;
                    case 'documents': echo 'Documents'; break;
                    case 'reports': echo 'Reports'; break;
                    case 'settings': echo 'Settings'; break;
                    default: echo 'Dashboard'; break;
                }
                ?>
            </h1>
        </div>
        <div class="clio-content-body">


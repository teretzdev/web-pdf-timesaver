<?php
// Include breadcrumb function
require_once __DIR__ . '/breadcrumb.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        
        /* Clio Header */
        .clio-header {
            background: #ffffff;
            border-bottom: 1px solid #e1e5e9;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .clio-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .clio-logo img {
            width: 32px;
            height: 32px;
        }
        
        .clio-logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: -0.5px;
        }
        
        .clio-nav {
            display: flex;
            align-items: center;
            gap: 32px;
        }
        
        .clio-nav-item {
            color: #6c757d;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }
        
        .clio-nav-item:hover,
        .clio-nav-item.active {
            color: #1976d2;
            border-bottom-color: #1976d2;
        }
        
        .clio-user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .clio-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #1976d2;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Main Content */
        .clio-main-content {
            margin-top: 60px;
            background: #f5f6fa;
            min-height: calc(100vh - 60px);
        }
        
        .clio-content-header {
            background: #ffffff;
            border-bottom: 1px solid #e1e5e9;
            padding: 24px 32px;
        }
        
        .clio-content-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        
        .clio-content-body {
            padding: 32px;
        }
        
        /* Clio Components */
        .clio-btn {
            background: #1976d2;
            color: #ffffff;
            border: none;
            padding: 10px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .clio-btn:hover {
            background: #1565c0;
        }
        
        .clio-btn-secondary {
            background: #ffffff;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        
        .clio-btn-secondary:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }
        
        .clio-card {
            background: #ffffff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .clio-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .clio-table th {
            background: #f8f9fa;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .clio-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f3f4;
            color: #495057;
            font-size: 14px;
        }
        
        .clio-table tr:hover {
            background: #f8f9fa;
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

        /* Keystroke brightness effect */
        .keystroke-bright {
            background: #ffffff !important;
            color: #1976d2 !important;
            box-shadow: 0 0 8px rgba(25, 118, 210, 0.4) !important;
            border-color: #1976d2 !important;
            transform: scale(1.02);
            transition: all 0.15s ease-out !important;
        }

        /* Enhanced input styling for brightness effect */
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="date"], textarea, select {
            transition: all 0.2s ease;
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
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .clio-content-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Clio Header -->
    <header class="clio-header">
        <div class="clio-logo">
            <img src="./logo.png" alt="Clio">
            <span class="clio-logo-text">Clio</span>
        </div>
        
        <nav class="clio-nav">
            <a href="?route=dashboard" class="clio-nav-item <?php echo ($_GET['route'] ?? '') === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="?route=projects" class="clio-nav-item <?php echo ($_GET['route'] ?? '') === 'projects' ? 'active' : ''; ?>">Matters</a>
            <a href="?route=clients" class="clio-nav-item <?php echo ($_GET['route'] ?? 'clients') === 'clients' ? 'active' : ''; ?>">Contacts</a>
            <a href="?route=activities" class="clio-nav-item <?php echo ($_GET['route'] ?? '') === 'activities' ? 'active' : ''; ?>">Activities</a>
            <a href="?route=bills" class="clio-nav-item <?php echo ($_GET['route'] ?? '') === 'bills' ? 'active' : ''; ?>">Bills</a>
            <a href="?route=documents" class="clio-nav-item <?php echo ($_GET['route'] ?? '') === 'documents' ? 'active' : ''; ?>">Documents</a>
            <a href="?route=reports" class="clio-nav-item <?php echo ($_GET['route'] ?? '') === 'reports' ? 'active' : ''; ?>">Reports</a>
            <a href="?route=settings" class="clio-nav-item <?php echo ($_GET['route'] ?? '') === 'settings' ? 'active' : ''; ?>">Settings</a>
        </nav>
        
        <div class="clio-user-menu">
            <div class="clio-user-avatar">JD</div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="clio-main-content">
        <div class="clio-content-header">
            <h1 class="clio-content-title">
                <?php 
                $route = $_GET['route'] ?? 'dashboard';
                switch($route) {
                    case 'dashboard': echo 'Dashboard'; break;
                    case 'projects': echo 'Matters'; break;
                    case 'clients': echo 'Contacts'; break;
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


<?php
// Include breadcrumb function
require_once __DIR__ . '/breadcrumb.php';
$currentRoute = $_GET['route'] ?? 'dashboard';
$autoCollapseSidebar = in_array($currentRoute, ['form-management', 'form-sets-manager', 'universal-processor', 'field-manager', 'firm-defaults'], true);
$bodyClasses = [];
if ($autoCollapseSidebar) {
    $bodyClasses[] = 'sidebar-auto-collapse';
}
if ($currentRoute === 'populate') {
    $bodyClasses[] = 'route-populate-scroll-lock';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PDFTimeSaver</title>
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
        .pdftimesaver-sidebar {
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
            transition: width 0.18s ease;
        }
        
        .pdftimesaver-sidebar-nav {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .pdftimesaver-sidebar-nav li {
            margin: 0;
        }
        
        .pdftimesaver-sidebar-nav a {
            display: block;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .pdftimesaver-sidebar-nav a:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .pdftimesaver-sidebar-nav a.active {
            background: #fff;
            color: #007bff;
            border-left-color: #007bff;
        }
        
        /* Main Content */
        .pdftimesaver-main-content {
            margin-left: 200px;
            background: #fff;
            min-height: 100vh;
            transition: margin-left 0.18s ease;
        }

        @media (min-width: 769px) {
            body.sidebar-auto-collapse .pdftimesaver-sidebar {
                width: 64px;
                overflow-x: hidden;
            }

            body.sidebar-auto-collapse .pdftimesaver-main-content {
                margin-left: 64px;
            }

            body.sidebar-auto-collapse .pdftimesaver-sidebar:hover,
            body.sidebar-auto-collapse .pdftimesaver-sidebar:focus-within {
                width: 200px;
            }

            body.sidebar-auto-collapse .pdftimesaver-sidebar-nav a {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: clip;
                padding-left: 14px;
                padding-right: 14px;
            }
        }
        
        .pdftimesaver-content-header {
            background: #ffffff;
            border-bottom: 1px solid #ddd;
            padding: 15px 20px;
        }
        
        .pdftimesaver-content-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .pdftimesaver-content-body {
            padding: 20px;
        }
        
        /* PDFTimeSaver Components — button sizes are centralized here.
           Approved refs: CURRENT_PHASE1_CHECKLIST (42px Back/Export/Finish via action),
           form_sets_manager (30px icon controls), table row actions (sm). */
        .pdftimesaver-btn,
        .pdftimesaver-btn-secondary {
            background: #007bff;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 3px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            line-height: 1.25;
            min-height: 36px;
            gap: 6px;
        }
        
        .pdftimesaver-btn:hover {
            background: #0056b3;
        }
        
        .pdftimesaver-btn-secondary {
            background: #ffffff;
            color: #555;
            border: 1px solid #ccc;
        }
        
        .pdftimesaver-btn-secondary:hover {
            background: #f5f5f5;
        }

        /* Compact table / list row actions */
        .pdftimesaver-btn-sm,
        .pdftimesaver-btn.pdftimesaver-btn-sm,
        .pdftimesaver-btn-secondary.pdftimesaver-btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            min-height: 30px;
        }

        /* Footer / wizard nav actions — same 42px height (Back, Export, Finish, Next) */
        .pdftimesaver-btn-action,
        .wizard-action-btn {
            height: 42px;
            min-height: 42px;
            padding: 0 24px !important;
            font-size: 15px !important;
            font-weight: 600;
            line-height: 1;
            border-radius: 6px;
        }

        /* Square icon-only table controls (View, Up, Down, Remove, Add) */
        .pdftimesaver-icon-btn,
        .form-sets-icon-btn,
        .project-form-icon-btn {
            min-width: 30px;
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            line-height: 1;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #eef2f7;
            color: #111827;
            cursor: pointer;
            text-decoration: none;
            box-sizing: border-box;
        }

        .pdftimesaver-icon-btn:disabled,
        .form-sets-icon-btn:disabled,
        .project-form-icon-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Icon buttons must not inherit default min-height from .pdftimesaver-btn(-secondary) */
        .pdftimesaver-btn.pdftimesaver-icon-btn,
        .pdftimesaver-btn-secondary.pdftimesaver-icon-btn,
        .form-sets-icon-btn {
            min-height: 30px;
            height: 30px;
            width: 30px;
            padding: 0;
        }

        .pdftimesaver-icon-btn.pdftimesaver-btn,
        .form-sets-icon-btn.primary {
            background: #007bff;
            color: #ffffff;
            border-color: #007bff;
        }

        /* Trash / delete icon control */
        .pdftimesaver-delete-btn,
        .form-delete-icon-btn {
            width: 36px;
            height: 36px;
            min-width: 36px;
            padding: 0;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fff1f2;
            color: #b91c1c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            box-sizing: border-box;
            text-decoration: none;
        }

        .pdftimesaver-delete-btn:hover:not(:disabled),
        .form-delete-icon-btn:hover:not(:disabled) {
            background: #ffe4e6;
        }

        .pdftimesaver-delete-btn:disabled,
        .form-delete-icon-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Magnifying-glass search submit — same height as default toolbar buttons */
        .pdftimesaver-search-btn {
            min-width: 36px;
            width: 36px;
            height: 36px;
            min-height: 36px;
            padding: 0;
            font-size: 16px;
            line-height: 1;
        }

        .button-group {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .pdftimesaver-card {
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .pdftimesaver-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .pdftimesaver-table th {
            background: #f5f5f5;
            padding: 10px 12px;
            text-align: left;
            font-weight: 500;
            color: #333;
            font-size: 13px;
            border-bottom: 1px solid #ddd;
        }
        
        .pdftimesaver-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            color: #555;
            font-size: 14px;
        }
        
        .pdftimesaver-table tr:hover {
            background: #f9f9f9;
        }
        
        .pdftimesaver-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .pdftimesaver-input:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
        }

        .wpts-form-shell {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }

        .wpts-form-title {
            margin: 0 0 6px 0;
            color: #1f2937;
            font-size: 20px;
            font-weight: 700;
        }

        .wpts-form-help {
            margin: 0 0 14px 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.45;
        }

        .wpts-form-grid {
            display: grid;
            gap: 12px;
        }

        .wpts-form-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .wpts-form-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .wpts-form-row {
            display: grid;
            gap: 12px;
        }

        .wpts-form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .wpts-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            margin-top: 8px;
        }

        .wpts-section-box {
            border: 1px solid #dbe4ef;
            border-left: 4px solid #4f46e5;
            background: #f8fbff;
            border-radius: 10px;
            padding: 12px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
        }

        .wpts-section-box-title {
            margin: 0 0 8px 0;
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }

        /* Mobile Menu Toggle Button */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1002;
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            min-width: 44px;
            min-height: 44px;
        }

        .mobile-menu-toggle:active {
            background: #0056b3;
        }

        /* Mobile Overlay */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .mobile-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        
        .pdftimesaver-form-group {
            margin-bottom: 20px;
        }
        
        .pdftimesaver-form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        /* Status badges */
        .pdftimesaver-status {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .pdftimesaver-status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .pdftimesaver-status-archived {
            background: #f8d7da;
            color: #721c24;
        }
        
        .pdftimesaver-status-in-progress {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        /* Feature detection and fallbacks */
        @supports not (display: flex) {
            .pdftimesaver-header { display: block; }
        }

        /* Responsive Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .mobile-overlay {
                display: block;
            }

            .pdftimesaver-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            }
            
            .pdftimesaver-sidebar.open {
                transform: translateX(0);
            }
            
            .pdftimesaver-main-content {
                margin-left: 0;
            }
            
            .pdftimesaver-content-header {
                padding: 60px 20px 15px 20px; /* Add top padding for hamburger menu */
            }

            .pdftimesaver-content-body {
                padding: 15px;
            }

            /* Touch-friendly buttons */
            .pdftimesaver-btn, .pdftimesaver-btn-secondary,
            .pdftimesaver-btn-action, .wizard-action-btn {
                min-height: 44px;
                padding: 12px 16px;
                font-size: 16px;
            }

            .pdftimesaver-btn-action, .wizard-action-btn {
                height: 44px;
                padding: 0 20px !important;
            }

            .pdftimesaver-icon-btn, .form-sets-icon-btn, .project-form-icon-btn {
                min-width: 44px;
                width: 44px;
                height: 44px;
            }

            .pdftimesaver-delete-btn, .form-delete-icon-btn {
                width: 44px;
                height: 44px;
                min-width: 44px;
            }

            .pdftimesaver-search-btn {
                min-width: 44px;
                width: 44px;
                height: 44px;
                min-height: 44px;
            }

            /* Stack button groups vertically */
            .button-group {
                display: flex;
                flex-direction: column;
                gap: 12px;
                width: 100%;
            }

            .button-group .pdftimesaver-btn,
            .button-group .pdftimesaver-btn-secondary {
                width: 100%;
            }

            /* Forms: single column on mobile */
            .grid {
                grid-template-columns: 1fr !important;
            }

            /* Inputs: prevent zoom on focus */
            .pdftimesaver-input, .pdftimesaver-input:focus {
                font-size: 16px;
            }

            /* Tables: force horizontal scroll */
            .pdftimesaver-table {
                min-width: 600px;
            }

            /* Card adjustments */
            .pdftimesaver-card {
                padding: 12px;
                margin-bottom: 12px;
            }

            /* Content title */
            .pdftimesaver-content-title {
                font-size: 16px;
            }

            /* Responsive flex containers */
            [style*="display: flex"] {
                flex-wrap: wrap;
            }

            .wpts-form-grid-2,
            .wpts-form-grid-3 {
                grid-template-columns: 1fr;
            }

            .wpts-form-actions {
                flex-direction: column;
            }
        }

        /* Very small screens */
        @media (max-width: 480px) {
            .pdftimesaver-content-body {
                padding: 10px;
            }

            .pdftimesaver-btn, .pdftimesaver-btn-secondary {
                padding: 14px 16px;
            }

            h2, h3 {
                font-size: 18px !important;
            }
        }

        /* Drafting Interface Overrides */
        .drafting-layout .pdftimesaver-main-content {
            margin-left: 0; /* Override sidebar margin for full-screen drafting */
        }

        @media (max-width: 768px) {
            .pdftimesaver-drafting-header {
                left: 0 !important;
            }
        }

        /* Workflow Progress Indicator */
        .workflow-progress {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            padding: 0 20px;
        }

        .workflow-step {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            margin: 0 4px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .workflow-step.active {
            background: #007bff;
            color: white;
        }

        .workflow-step.completed {
            background: #28a745;
            color: white;
        }

        .workflow-step.pending {
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }

        .workflow-step:not(:last-child)::after {
            content: '→';
            margin-left: 8px;
            color: #6c757d;
        }

        .workflow-step.active:not(:last-child)::after,
        .workflow-step.completed:not(:last-child)::after {
            color: white;
        }

        /* Prominent CTA only (e.g. Go to drafting) — not for footer nav actions */
        .pdftimesaver-btn-large {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .pdftimesaver-btn-large:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        /* File Upload Zone Enhancements */
        .file-upload-zone {
            position: relative;
            overflow: hidden;
        }

        .file-upload-zone::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 123, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .file-upload-zone:hover::before {
            left: 100%;
        }

        /* Client Files List Enhancements */
        .client-file-item {
            transition: all 0.3s ease;
            border-radius: 6px;
        }

        .client-file-item:hover {
            background: #f8f9fa;
            transform: translateX(4px);
        }

        .client-file-item .file-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .client-file-item:hover .file-actions {
            opacity: 1;
        }

        /* Status Dropdown Enhancements */
        .status-dropdown {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .status-dropdown:hover {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }

        .status-dropdown:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        /* Modal Enhancements */
        .modal {
            backdrop-filter: blur(4px);
        }

        .modal-content {
            animation: modalSlideIn 0.3s ease;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        /* Form Field Enhancements */
        .form-group input,
        .form-group select,
        .form-group textarea {
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
            transform: translateY(-1px);
        }

        /* Breadcrumb Enhancements */
        .breadcrumb {
            margin-bottom: 20px;
            padding: 12px 0;
            font-size: 14px;
            color: #6c757d;
        }

        .breadcrumb-list {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 8px;
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
        }

        .breadcrumb-link {
            color: #0b6bcb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .breadcrumb-link:hover {
            color: #0a5bb8;
            text-decoration: underline;
        }

        .breadcrumb-text {
            color: #65748b;
            font-size: 14px;
            font-weight: 500;
        }

        .breadcrumb-item.active .breadcrumb-text {
            color: #1a2b3b;
            font-weight: 600;
        }

        .breadcrumb-separator {
            color: #d7dce3;
            font-size: 14px;
            margin: 0 4px;
        }

        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .breadcrumb-current {
            color: #2c3e50;
            font-weight: 600;
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Success/Error States */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid #c3e6cb;
            margin: 10px 0;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid #f5c6cb;
            margin: 10px 0;
        }

        /* Accessibility Improvements */
        .pdftimesaver-btn:focus,
        .pdftimesaver-btn-secondary:focus,
        .pdftimesaver-btn-action:focus,
        .wizard-action-btn:focus,
        .pdftimesaver-icon-btn:focus,
        .pdftimesaver-delete-btn:focus,
        .pdftimesaver-input:focus-visible,
        .pdftimesaver-btn:focus-visible,
        .pdftimesaver-btn-secondary:focus-visible,
        .pdftimesaver-btn-action:focus-visible,
        .wizard-action-btn:focus-visible,
        .pdftimesaver-icon-btn:focus-visible,
        .pdftimesaver-delete-btn:focus-visible,
        .status-dropdown:focus,
        .modal-close:focus {
            outline: 2px solid #007bff;
            outline-offset: 2px;
        }

        /* Drafting Interface Styles */
        .pdftimesaver-drafting-header {
            position: fixed;
            top: 0;
            left: 200px;
            right: 0;
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            z-index: 999;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .header-left {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .header-center {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 0 20px;
        }

        .header-right {
            display: flex;
            gap: 12px;
            align-items: center;
        }

            /* Drafting Content Layout */
            .drafting-layout {
                margin-top: 60px; /* Account for fixed header */
                display: flex;
                min-height: calc(100vh - 60px);
            }

            .document-list-panel {
                width: 280px;
                background: #f8f9fa;
                border-right: 1px solid #ddd;
                padding: 20px;
                overflow-y: auto;
                max-height: calc(100vh - 60px);
            }

            .document-list-panel h3 {
                margin: 0 0 16px 0;
                font-size: 16px;
                font-weight: 600;
                color: #2c3e50;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .document-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .document-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px;
                border-bottom: 1px solid #e9ecef;
                cursor: pointer;
                transition: all 0.2s ease;
                border-radius: 4px;
                margin-bottom: 4px;
            }

            .document-item:hover {
                background: #e9ecef;
            }

            .document-item.active {
                background: #007bff;
                color: white;
            }

            .document-item.active .status-indicator {
                background: rgba(255,255,255,0.2);
                color: white;
            }

            .document-info {
                flex: 1;
            }

            .document-name {
                font-size: 13px;
                font-weight: 500;
                margin-bottom: 4px;
            }

            .document-status {
                font-size: 11px;
            }

            .status-indicator {
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: 500;
                text-transform: uppercase;
            }

            .status-in-progress {
                background: #fff3cd;
                color: #856404;
            }

            .status-review {
                background: #d1ecf1;
                color: #0c5460;
            }

            .status-completed {
                background: #d4edda;
                color: #155724;
            }

            .status-ready-to-sign {
                background: #e2e3e5;
                color: #383d41;
            }

            .document-actions {
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .document-item:hover .document-actions {
                opacity: 1;
            }

            .action-link {
                color: #007bff;
                text-decoration: none;
                font-size: 12px;
                font-weight: 500;
            }

            .action-link:hover {
                text-decoration: underline;
            }

            .drafting-main-content {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
            }

            .client-vault-panel {
                width: 280px;
                background: #f8f9fa;
                border-left: 1px solid #ddd;
                padding: 20px;
                overflow-y: auto;
                max-height: calc(100vh - 60px);
            }

        .client-vault-panel h3 {
            margin: 0 0 16px 0;
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        /* File Upload Zone */
        .file-upload-zone {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s ease;
            background: #fff;
            margin-bottom: 20px;
        }

        .file-upload-zone:hover,
        .file-upload-zone.dragover {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.05);
        }

        .file-upload-zone p {
            margin: 0 0 8px 0;
            color: #6c757d;
            font-size: 14px;
        }

        .browse-link {
            color: #1976d2;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .browse-link:hover {
            text-decoration: underline;
        }

        .file-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }

        .file-item:hover {
            background: #f8f9fa;
        }

        .file-delete-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 0 8px;
            font-size: 18px;
        }

        .file-delete-btn:hover {
            color: #c82333;
        }

        /* Workflow Instructions */
        .workflow-instructions {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 16px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .workflow-instructions p {
            margin: 0 0 8px 0;
            color: #495057;
            font-size: 14px;
        }

        .workflow-instructions p:last-child {
            margin-bottom: 0;
        }

        /* PDF Preview Container */
        .pdf-preview-container {
            position: relative;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            margin-bottom: 20px;
        }

        .pdf-container {
            position: relative;
            width: 100%;
            height: 800px;
            overflow: auto;
            background: #f8f9fa;
        }

        #pdf-iframe {
            border: none;
            width: 100%;
            height: 100%;
        }

        .pdf-field-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }

        .pdf-field {
            position: absolute;
            border: 2px solid #007bff;
            background: rgba(255,255,255,0.9);
            pointer-events: auto;
        }

        .pdf-field-input {
            width: 100%;
            height: 100%;
            border: none;
            padding: 2px 6px;
            font-size: 12px;
            background: transparent;
        }

        .pdf-field-input:focus {
            outline: none;
            background: #fff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        /* Custom Field Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            width: 500px;
            max-width: 90vw;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #2c3e50;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        /* Custom Field Styling */
        .custom-field {
            border-color: #28a745 !important;
            background: rgba(40, 167, 69, 0.1) !important;
        }

        .custom-field-indicator {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 16px;
            height: 16px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: white;
            font-weight: bold;
        }

        .custom-field:hover .custom-field-indicator {
            background: #218838;
        }

        /* Positioning Instructions */
        #positioning-instructions {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        /* Print Styles */
        @media print {
            .pdftimesaver-drafting-header,
            .client-vault-panel,
            .modal {
                display: none !important;
            }
            
            .pdftimesaver-main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="<?php echo htmlspecialchars(implode(' ', $bodyClasses)); ?>">
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle menu">
        ☰
    </button>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobile-overlay"></div>
    
    <!-- Sidebar -->
    <nav class="pdftimesaver-sidebar" id="pdftimesaver-sidebar">
        <ul class="pdftimesaver-sidebar-nav">
            <?php 
            $navItems = [
                ['route' => 'dashboard', 'label' => 'Dashboard'],
                ['route' => 'clients', 'label' => 'Clients'],
                ['route' => 'projects', 'label' => 'Projects'],
                ['route' => 'settings', 'label' => 'Settings', 'activeRoutes' => ['settings', 'forms', 'documents', 'font-settings']],
                ['route' => 'form-management', 'label' => '🗂️ Forms Manager'],
                ['route' => 'form-sets-manager', 'label' => '📚 Form Sets Manager'],
                ['route' => 'field-manager', 'label' => '🧩 Field Manager'],
                ['route' => 'firm-defaults', 'label' => '🏢 Firm Information'],
            ];
            
            foreach ($navItems as $item): 
                $activeRoutes = is_array($item['activeRoutes'] ?? null) ? $item['activeRoutes'] : [$item['route']];
                $isActive = in_array($currentRoute, $activeRoutes, true);
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
    <main class="pdftimesaver-main-content">
        <div class="pdftimesaver-content-header">
            <h1 class="pdftimesaver-content-title">
                <?php 
                switch($currentRoute) {
                    case 'dashboard': echo 'Dashboard'; break;
                    case 'projects': echo 'Projects'; break;
                    case 'clients': echo 'Clients'; break;
                    case 'activities': echo 'Activities'; break;
                    case 'bills': echo 'Bills'; break;
                    case 'documents': echo 'Forms'; break;
                    case 'forms': echo 'Forms'; break;
                    case 'reports': echo 'Reports'; break;
                    case 'settings': echo 'Settings'; break;
                    case 'font-settings': echo 'Font Settings'; break;
                    case 'populate': echo 'Populate Form'; break;
                    case 'drafting': echo 'Drafting'; break;
                    case 'pdf-lib-demo': echo 'PDF-Lib Demo'; break;
                    case 'universal-processor': echo 'Forms Manager'; break;
                    case 'form-management': echo 'Forms Manager'; break;
                    case 'form-sets-manager': echo 'Form Sets Manager'; break;
                    case 'form-new': echo 'Add New Form'; break;
                    case 'field-manager': echo 'Field Manager'; break;
                    case 'firm-defaults': echo 'Firm Information'; break;
                    case 'extract-fields': echo 'Field Extractor'; break;
                    case 'test-autofill': echo 'Test Auto-Fill'; break;
                    case 'project': echo 'Project'; break;
                    case 'client': echo 'Client View'; break;
                    default: echo 'Dashboard'; break;
                }
                ?>
            </h1>
        </div>
        <div class="pdftimesaver-content-body">
            <?php
            $flashError = isset($_GET['error']) ? trim((string)$_GET['error']) : '';
            $flashSuccess = isset($_GET['success']) ? trim((string)$_GET['success']) : '';
            ?>
            <?php if ($flashError !== ''): ?>
                <div class="error-message" role="alert"><?php echo htmlspecialchars($flashError); ?></div>
            <?php endif; ?>
            <?php if ($flashSuccess !== ''): ?>
                <div class="success-message" role="status"><?php echo htmlspecialchars($flashSuccess); ?></div>
            <?php endif; ?>


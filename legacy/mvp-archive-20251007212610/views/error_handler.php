<?php
/**
 * Error Handler Component
 * Provides better error handling and user feedback
 */
?>

<style>
.error-message {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.error-message .error-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.error-message .error-content {
    flex: 1;
}

.error-message .error-title {
    font-weight: 600;
    margin: 0 0 4px 0;
    font-size: 16px;
}

.error-message .error-description {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.error-message .error-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
}

.warning-message {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.warning-message .warning-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.warning-message .warning-content {
    flex: 1;
}

.warning-message .warning-title {
    font-weight: 600;
    margin: 0 0 4px 0;
    font-size: 16px;
}

.warning-message .warning-description {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.success-message {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.success-message .success-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.success-message .success-content {
    flex: 1;
}

.success-message .success-title {
    font-weight: 600;
    margin: 0 0 4px 0;
    font-size: 16px;
}

.success-message .success-description {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.info-message {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.info-message .info-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.info-message .info-content {
    flex: 1;
}

.info-message .info-title {
    font-weight: 600;
    margin: 0 0 4px 0;
    font-size: 16px;
}

.info-message .info-description {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

/* Toast notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.toast {
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 300px;
    max-width: 400px;
    animation: slideInRight 0.3s ease-out;
    position: relative;
}

.toast.error {
    border-left: 4px solid #dc3545;
}

.toast.warning {
    border-left: 4px solid #ffc107;
}

.toast.success {
    border-left: 4px solid #28a745;
}

.toast.info {
    border-left: 4px solid #17a2b8;
}

.toast .toast-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.toast .toast-title {
    font-weight: 600;
    font-size: 14px;
    margin: 0;
}

.toast .toast-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #65748b;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toast .toast-body {
    font-size: 14px;
    color: #495057;
    margin: 0;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.toast.removing {
    animation: slideOutRight 0.3s ease-in;
}

/* Auto-dismiss functionality */
.toast.auto-dismiss {
    position: relative;
    overflow: hidden;
}

.toast.auto-dismiss::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: #0b6bcb;
    animation: countdown 5s linear;
}

@keyframes countdown {
    from { width: 100%; }
    to { width: 0%; }
}
</style>

<script>
// Toast notification system
window.ToastManager = {
    show: function(message, type = 'info', title = null, autoDismiss = true) {
        const container = this.getContainer();
        const toast = this.createToast(message, type, title, autoDismiss);
        container.appendChild(toast);
        
        // Auto-dismiss after 5 seconds
        if (autoDismiss) {
            setTimeout(() => {
                this.dismiss(toast);
            }, 5000);
        }
        
        return toast;
    },
    
    getContainer: function() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    },
    
    createToast: function(message, type, title, autoDismiss) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        if (autoDismiss) {
            toast.classList.add('auto-dismiss');
        }
        
        const titleText = title || this.getDefaultTitle(type);
        
        toast.innerHTML = `
            <div class="toast-header">
                <h4 class="toast-title">${titleText}</h4>
                <button class="toast-close" onclick="ToastManager.dismiss(this.closest('.toast'))">&times;</button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        return toast;
    },
    
    getDefaultTitle: function(type) {
        switch (type) {
            case 'error': return 'Error';
            case 'warning': return 'Warning';
            case 'success': return 'Success';
            case 'info': return 'Information';
            default: return 'Notification';
        }
    },
    
    dismiss: function(toast) {
        toast.classList.add('removing');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    },
    
    error: function(message, title = 'Error') {
        return this.show(message, 'error', title);
    },
    
    warning: function(message, title = 'Warning') {
        return this.show(message, 'warning', title);
    },
    
    success: function(message, title = 'Success') {
        return this.show(message, 'success', title);
    },
    
    info: function(message, title = 'Information') {
        return this.show(message, 'info', title);
    }
};

// Error handling for AJAX requests
window.handleAjaxError = function(xhr, status, error) {
    let message = 'An unexpected error occurred.';
    
    if (xhr.responseText) {
        try {
            const response = JSON.parse(xhr.responseText);
            message = response.message || response.error || message;
        } catch (e) {
            message = xhr.responseText;
        }
    }
    
    ToastManager.error(message);
};

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    ToastManager.error('An unexpected error occurred. Please refresh the page and try again.');
});

// Unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    ToastManager.error('An unexpected error occurred. Please refresh the page and try again.');
});

// Form validation helper
window.validateForm = function(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    let firstInvalidField = null;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            isValid = false;
            if (!firstInvalidField) {
                firstInvalidField = field;
            }
        } else {
            field.style.borderColor = '';
        }
    });
    
    if (!isValid) {
        ToastManager.warning('Please fill in all required fields.');
        if (firstInvalidField) {
            firstInvalidField.focus();
        }
    }
    
    return isValid;
};

// Success message helper
window.showSuccessMessage = function(message) {
    ToastManager.success(message);
};

// Auto-hide success messages from URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const saved = urlParams.get('saved');
    const created = urlParams.get('created');
    const updated = urlParams.get('updated');
    const deleted = urlParams.get('deleted');
    
    if (saved === '1') {
        ToastManager.success('Changes saved successfully!');
    }
    if (created === '1') {
        ToastManager.success('Item created successfully!');
    }
    if (updated === '1') {
        ToastManager.success('Item updated successfully!');
    }
    if (deleted === '1') {
        ToastManager.success('Item deleted successfully!');
    }
});
</script>














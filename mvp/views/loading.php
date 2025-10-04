<?php
/**
 * Loading States Component
 * Provides loading indicators and skeleton screens
 */
?>

<style>
/* Loading Spinner */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #eef2f7;
    border-radius: 50%;
    border-top-color: #0b6bcb;
    animation: spin 1s ease-in-out infinite;
}

.loading-spinner.large {
    width: 40px;
    height: 40px;
    border-width: 4px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-overlay .loading-content {
    text-align: center;
    color: #65748b;
}

.loading-overlay .loading-spinner {
    margin-bottom: 16px;
}

/* Skeleton Loading */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton-text {
    height: 16px;
    margin-bottom: 8px;
}

.skeleton-text.short {
    width: 60%;
}

.skeleton-text.medium {
    width: 80%;
}

.skeleton-text.long {
    width: 100%;
}

.skeleton-card {
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
}

.skeleton-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-bottom: 12px;
}

/* Button Loading State */
.btn.loading {
    position: relative;
    color: transparent;
}

.btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.btn.loading.btn {
    color: #fff;
}

.btn.loading.btn.secondary {
    color: #1a2b3b;
}

/* Form Loading State */
.form-loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.form-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Page Loading */
.page-loading {
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: #65748b;
}

.page-loading .loading-spinner {
    margin-bottom: 16px;
}

/* Fade In Animation */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Pulse Animation for Interactive Elements */
.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>

<script>
// Loading state management
window.LoadingManager = {
    showOverlay: function(message = 'Loading...') {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.id = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner large"></div>
                <div>${message}</div>
            </div>
        `;
        document.body.appendChild(overlay);
    },
    
    hideOverlay: function() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    },
    
    setButtonLoading: function(button, loading = true) {
        if (loading) {
            button.classList.add('loading');
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = 'Loading...';
        } else {
            button.classList.remove('loading');
            button.disabled = false;
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    },
    
    setFormLoading: function(form, loading = true) {
        if (loading) {
            form.classList.add('form-loading');
        } else {
            form.classList.remove('form-loading');
        }
    }
};

// Auto-loading for forms
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            LoadingManager.setFormLoading(this, true);
            
            // Set loading state for submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                LoadingManager.setButtonLoading(submitBtn, true);
            }
        });
    });
    
    // Add loading states to links that might take time
    document.querySelectorAll('a[href*="actions/"]').forEach(link => {
        link.addEventListener('click', function() {
            LoadingManager.showOverlay('Processing...');
        });
    });
    
    // Add fade-in animation to content
    document.querySelectorAll('.content-body > *').forEach(element => {
        element.classList.add('fade-in');
    });
});

// Utility function to show skeleton loading
function showSkeletonLoading(container, count = 3) {
    const skeletonHTML = `
        <div class="skeleton-card">
            <div class="skeleton skeleton-avatar"></div>
            <div class="skeleton skeleton-text short"></div>
            <div class="skeleton skeleton-text medium"></div>
            <div class="skeleton skeleton-text long"></div>
        </div>
    `;
    
    container.innerHTML = skeletonHTML.repeat(count);
}

// Utility function to hide skeleton loading
function hideSkeletonLoading(container, content) {
    container.innerHTML = content;
}
</script>














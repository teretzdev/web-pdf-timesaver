<?php
/**
 * Keyboard Shortcuts Component
 * Provides keyboard shortcuts for common actions
 */
?>

<style>
.shortcuts-help {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #1a2b3b;
    color: #fff;
    padding: 16px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    font-size: 14px;
    z-index: 1000;
    max-width: 300px;
    display: none;
}

.shortcuts-help.show {
    display: block;
    animation: slideInUp 0.3s ease-out;
}

.shortcuts-help h4 {
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 600;
}

.shortcuts-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.shortcuts-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding: 4px 0;
}

.shortcuts-list li:last-child {
    margin-bottom: 0;
}

.shortcut-key {
    background: #0b6bcb;
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    font-family: monospace;
    min-width: 20px;
    text-align: center;
}

.shortcut-description {
    flex: 1;
    margin-left: 12px;
    font-size: 13px;
}

.shortcuts-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #0b6bcb;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 48px;
    height: 48px;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
    z-index: 1001;
}

.shortcuts-toggle:hover {
    background: #0a5bb8;
    transform: scale(1.1);
}

.shortcuts-toggle:active {
    transform: scale(0.95);
}

@keyframes slideInUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Keyboard shortcut hints */
.keyboard-hint {
    position: absolute;
    top: -30px;
    right: 0;
    background: #1a2b3b;
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    font-family: monospace;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
    z-index: 100;
}

.keyboard-hint.show {
    opacity: 1;
}

/* Add keyboard hints to buttons */
.btn[data-shortcut] {
    position: relative;
}

.btn[data-shortcut]:hover .keyboard-hint {
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Create shortcuts help modal
    const shortcutsHelp = document.createElement('div');
    shortcutsHelp.className = 'shortcuts-help';
    shortcutsHelp.innerHTML = `
        <h4>Keyboard Shortcuts</h4>
        <ul class="shortcuts-list">
            <li>
                <span class="shortcut-key">?</span>
                <span class="shortcut-description">Show/hide shortcuts</span>
            </li>
            <li>
                <span class="shortcut-key">C</span>
                <span class="shortcut-description">Go to Clients</span>
            </li>
            <li>
                <span class="shortcut-key">P</span>
                <span class="shortcut-description">Go to Projects</span>
            </li>
            <li>
                <span class="shortcut-key">T</span>
                <span class="shortcut-description">Go to Templates</span>
            </li>
            <li>
                <span class="shortcut-key">N</span>
                <span class="shortcut-description">Add new item</span>
            </li>
            <li>
                <span class="shortcut-key">S</span>
                <span class="shortcut-description">Search</span>
            </li>
            <li>
                <span class="shortcut-key">Esc</span>
                <span class="shortcut-description">Close modal/cancel</span>
            </li>
        </ul>
    `;
    document.body.appendChild(shortcutsHelp);
    
    // Create toggle button
    const shortcutsToggle = document.createElement('button');
    shortcutsToggle.className = 'shortcuts-toggle';
    shortcutsToggle.innerHTML = '?';
    shortcutsToggle.title = 'Show keyboard shortcuts (?)';
    document.body.appendChild(shortcutsToggle);
    
    // Toggle shortcuts help
    function toggleShortcuts() {
        shortcutsHelp.classList.toggle('show');
        shortcutsToggle.style.display = shortcutsHelp.classList.contains('show') ? 'none' : 'block';
    }
    
    shortcutsToggle.addEventListener('click', toggleShortcuts);
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Don't trigger shortcuts when typing in inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            return;
        }
        
        // Handle Escape key
        if (e.key === 'Escape') {
            // Close any open modals
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display !== 'none') {
                    modal.style.display = 'none';
                }
            });
            
            // Hide shortcuts help
            if (shortcutsHelp.classList.contains('show')) {
                toggleShortcuts();
            }
            return;
        }
        
        // Handle question mark for shortcuts
        if (e.key === '?') {
            e.preventDefault();
            toggleShortcuts();
            return;
        }
        
        // Handle other shortcuts
        switch (e.key.toLowerCase()) {
            case 'c':
                e.preventDefault();
                window.location.href = '?route=clients';
                break;
                
            case 'p':
                e.preventDefault();
                window.location.href = '?route=projects';
                break;
                
            case 't':
                e.preventDefault();
                window.location.href = '?route=templates';
                break;
                
            case 'n':
                e.preventDefault();
                // Find and click the first "Add" button on the page
                const addButton = document.querySelector('[id*="add-"], .btn:contains("Add")');
                if (addButton) {
                    addButton.click();
                }
                break;
                
            case 's':
                e.preventDefault();
                // Focus on search input if available
                const searchInput = document.querySelector('input[type="text"][placeholder*="earch"], input[type="text"][id*="search"]');
                if (searchInput) {
                    searchInput.focus();
                }
                break;
        }
    });
    
    // Add keyboard hints to buttons
    function addKeyboardHints() {
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            if (button.id === 'add-client-btn') {
                addKeyboardHint(button, 'N');
            } else if (button.id === 'add-project-btn') {
                addKeyboardHint(button, 'N');
            }
        });
        
        const searchInputs = document.querySelectorAll('input[type="text"][placeholder*="earch"]');
        searchInputs.forEach(input => {
            addKeyboardHint(input, 'S');
        });
    }
    
    function addKeyboardHint(element, key) {
        const hint = document.createElement('div');
        hint.className = 'keyboard-hint';
        hint.textContent = key;
        element.style.position = 'relative';
        element.appendChild(hint);
    }
    
    // Add hints after a short delay to ensure DOM is ready
    setTimeout(addKeyboardHints, 100);
    
    // Re-add hints when new content is loaded
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                setTimeout(addKeyboardHints, 100);
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>














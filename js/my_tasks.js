// File: js/my_tasks.js

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all event listeners
    initializeEventListeners();
    
    // Initialize section toggles
    initializeSectionToggles();
    
    // Initialize task checkboxes
    initializeTaskCheckboxes();
    
    // Initialize add task functionality
    initializeAddTaskFunctionality();
});

/**
 * Initialize all event listeners for the page
 */
function initializeEventListeners() {
    // Navigation tab clicks
    const navTabs = document.querySelectorAll('.nav-tab');
    navTabs.forEach(tab => {
        tab.addEventListener('click', handleNavTabClick);
    });
    
    // Action bar buttons
    const filterBtn = document.querySelector('.btn-filter');
    const sortBtn = document.querySelector('.btn-sort');
    const groupBtn = document.querySelector('.btn-group');
    const optionsBtn = document.querySelector('.btn-options');
    
    if (filterBtn) filterBtn.addEventListener('click', () => showFeatureNotImplemented('Filter'));
    if (sortBtn) sortBtn.addEventListener('click', () => showFeatureNotImplemented('Sort'));
    if (groupBtn) groupBtn.addEventListener('click', () => showFeatureNotImplemented('Group'));
    if (optionsBtn) optionsBtn.addEventListener('click', () => showFeatureNotImplemented('Options'));
    
    // Main add task button
    const addTaskBtn = document.getElementById('add-task-btn');
    if (addTaskBtn) {
        addTaskBtn.addEventListener('click', () => {
            const firstSection = document.querySelector('.task-section');
            if (firstSection) {
                const addTaskBtn = firstSection.querySelector('.btn-add-task');
                if (addTaskBtn) {
                    addTaskBtn.click();
                }
            }
        });
    }
}

/**
 * Handle navigation tab clicks
 */
function handleNavTabClick(event) {
    // Remove active class from all tabs
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Add active class to clicked tab
    event.currentTarget.classList.add('active');
    
    // Get tab name for future implementation
    const tabText = event.currentTarget.textContent.trim();
    if (tabText !== 'List') {
        showFeatureNotImplemented(tabText);
    }
}

/**
 * Initialize section toggle functionality
 */
function initializeSectionToggles() {
    const sectionHeaders = document.querySelectorAll('.section-header');
    
    sectionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const section = this.closest('.task-section');
            const content = section.querySelector('.section-content');
            const toggle = this.querySelector('.section-toggle');
            
            // Toggle collapsed state
            content.classList.toggle('collapsed');
            toggle.classList.toggle('collapsed');
        });
    });
}

/**
 * Initialize task checkbox functionality
 */
function initializeTaskCheckboxes() {
    const checkboxes = document.querySelectorAll('.task-checkbox:not(.disabled)');
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            toggleTaskStatus(taskId, this);
        });
    });
}

/**
 * Toggle task status (completed/pending)
 */
function toggleTaskStatus(taskId, checkboxElement) {
    // Show loading state
    checkboxElement.style.opacity = '0.5';
    checkboxElement.style.pointerEvents = 'none';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'toggle_task');
    formData.append('task_id', taskId);
    
    // Send AJAX request
    fetch('my_tasks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI based on new status
            const taskRow = checkboxElement.closest('.task-row');
            const taskName = taskRow.querySelector('.task-name');
            const projectTag = taskRow.querySelector('.project-tag');
            
            if (data.new_status === 'completed') {
                checkboxElement.classList.add('completed');
                checkboxElement.innerHTML = '<i class="fas fa-check"></i>';
                taskName.classList.add('completed');
                if (projectTag) projectTag.classList.add('completed');
            } else {
                checkboxElement.classList.remove('completed');
                checkboxElement.innerHTML = '';
                taskName.classList.remove('completed');
                if (projectTag) projectTag.classList.remove('completed');
            }
        } else {
            showError('Failed to update task status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while updating the task');
    })
    .finally(() => {
        // Remove loading state
        checkboxElement.style.opacity = '1';
        checkboxElement.style.pointerEvents = 'auto';
    });
}

/**
 * Initialize add task functionality
 */
function initializeAddTaskFunctionality() {
    const addTaskButtons = document.querySelectorAll('.btn-add-task');
    
    addTaskButtons.forEach(button => {
        button.addEventListener('click', function() {
            const section = this.closest('.task-section');
            const sectionName = section.getAttribute('data-section');
            showAddTaskInput(section, sectionName);
        });
    });
}

/**
 * Show add task input in a section
 */
function showAddTaskInput(sectionElement, sectionName) {
    const addTaskRow = sectionElement.querySelector('.add-task-row');
    const addTaskButton = sectionElement.querySelector('.add-task-button');
    const addTaskInput = addTaskRow.querySelector('.add-task-input');
    
    // Show input row, hide add button
    addTaskRow.style.display = 'grid';
    addTaskButton.style.display = 'none';
    
    // Focus on input
    addTaskInput.focus();
    addTaskInput.value = '';
    
    // Handle save button
    const saveBtn = addTaskRow.querySelector('.btn-save-task');
    const cancelBtn = addTaskRow.querySelector('.btn-cancel-task');
    
    // Remove existing listeners
    const newSaveBtn = saveBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    
    // Add new listeners
    newSaveBtn.addEventListener('click', () => saveNewTask(sectionElement, sectionName));
    newCancelBtn.addEventListener('click', () => cancelAddTask(sectionElement));
    
    // Handle Enter key
    addTaskInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveNewTask(sectionElement, sectionName);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelAddTask(sectionElement);
        }
    });
}

/**
 * Save new task
 */
function saveNewTask(sectionElement, sectionName) {
    const addTaskInput = sectionElement.querySelector('.add-task-input');
    const taskName = addTaskInput.value.trim();
    
    if (!taskName) {
        addTaskInput.focus();
        return;
    }
    
    // Show loading state
    const saveBtn = sectionElement.querySelector('.btn-save-task');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;
    
    // Get project ID (you might need to add this to the page)
    const projectId = getProjectId();
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'add_task');
    formData.append('task_name', taskName);
    formData.append('section', sectionName);
    formData.append('project_id', projectId);
    
    // Send AJAX request
    fetch('my_tasks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show new task (or dynamically add it)
            location.reload();
        } else {
            showError(data.error || 'Failed to add task');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while adding the task');
    })
    .finally(() => {
        // Reset button state
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
    });
}

/**
 * Cancel add task
 */
function cancelAddTask(sectionElement) {
    const addTaskRow = sectionElement.querySelector('.add-task-row');
    const addTaskButton = sectionElement.querySelector('.add-task-button');
    
    // Hide input row, show add button
    addTaskRow.style.display = 'none';
    addTaskButton.style.display = 'block';
}

/**
 * Get project ID from page (you might need to add this to your PHP)
 */
function getProjectId() {
    // This should be dynamically set from PHP
    // For now, we'll try to get it from a data attribute or meta tag
    const projectMeta = document.querySelector('meta[name="project-id"]');
    if (projectMeta) {
        return projectMeta.getAttribute('content');
    }
    
    // Fallback - you might need to adjust this based on your implementation
    return 1; // Default project ID
}

/**
 * Show feature not implemented message
 */
function showFeatureNotImplemented(featureName) {
    showNotification(`${featureName} feature is not implemented yet`, 'info');
}

/**
 * Show error message
 */
function showError(message) {
    showNotification(message, 'error');
}

/**
 * Show success message
 */
function showSuccess(message) {
    showNotification(message, 'success');
}

/**
 * Generic notification system
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add notification styles
    const style = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-out;
        }
        
        .notification-info {
            background-color: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        
        .notification-success {
            background-color: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }
        
        .notification-error {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            color: #dc2626;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
        }
        
        .notification-message {
            font-size: 14px;
            font-weight: 500;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            margin-left: 12px;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    
    // Add styles if not already added
    if (!document.querySelector('#notification-styles')) {
        const styleElement = document.createElement('style');
        styleElement.id = 'notification-styles';
        styleElement.textContent = style;
        document.head.appendChild(styleElement);
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Handle close button
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

/**
 * Utility function to debounce function calls
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Initialize keyboard shortcuts
 */
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter to add task
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            const addTaskBtn = document.getElementById('add-task-btn');
            if (addTaskBtn) addTaskBtn.click();
        }
        
        // Escape to cancel current action
        if (e.key === 'Escape') {
            const visibleAddTaskRows = document.querySelectorAll('.add-task-row[style*="grid"]');
            visibleAddTaskRows.forEach(row => {
                const section = row.closest('.task-section');
                cancelAddTask(section);
            });
        }
    });
}

// Initialize keyboard shortcuts
initializeKeyboardShortcuts();
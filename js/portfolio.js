/**
 * Portfolio Page JavaScript
 * Handles interactive functionality for the portfolio page
 */

// Wait for DOM to be fully loaded before executing scripts
$(document).ready(function() {
    
    // Initialize all portfolio functionality
    initializePortfolio();
    
    /**
     * Initialize portfolio page functionality
     */
    function initializePortfolio() {
        // Set up event listeners
        setupEventListeners();
        
        // Initialize tooltips if Bootstrap is available
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        // Animate counters on page load
        animateCounters();
        
        // Add loading states to action buttons
        setupLoadingStates();
    }
    
    /**
     * Set up all event listeners for interactive elements
     */
    function setupEventListeners() {
        
        // Handle project card clicks for details view
        $('.view-details').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const projectId = $(this).data('project-id');
            showProjectDetails(projectId);
        });
        
        // Handle project card hover effects
        $('.project-card').on('mouseenter', function() {
            $(this).find('.progress-fill').addClass('pulse');
        }).on('mouseleave', function() {
            $(this).find('.progress-fill').removeClass('pulse');
        });
        
        // Handle keyboard navigation for project cards
        $('.project-card').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                const projectId = $(this).data('project-id');
                window.location.href = `project.php?id=${projectId}`;
            }
        });
        
        // Handle modal events
        $('#projectDetailsModal').on('show.bs.modal', function () {
            // Add loading animation when modal opens
            $(this).find('.modal-body').html(`
                <div class="text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading project details...</p>
                </div>
            `);
        });
        
        // Handle refresh button if it exists
        $('.refresh-portfolio').on('click', function(e) {
            e.preventDefault();
            refreshPortfolioData();
        });
        
        // Handle filter buttons if they exist
        $('.filter-btn').on('click', function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            filterProjects(filter);
        });
    }
    
    /**
     * Show detailed project information in a modal
     * @param {number} projectId - The ID of the project to show details for
     */
    function showProjectDetails(projectId) {
        
        // Show loading state
        $('#projectDetailsModal').modal('show');
        
        // Make AJAX request to get project details
        $.ajax({
            url: 'api/get_project_details.php', // You'll need to create this API endpoint
            method: 'GET',
            data: { project_id: projectId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayProjectDetails(response.data);
                } else {
                    showError('Failed to load project details: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showError('Failed to load project details. Please try again.');
            }
        });
    }
    
    /**
     * Display project details in the modal
     * @param {object} projectData - Project data from the server
     */
    function displayProjectDetails(projectData) {
        const modalContent = `
            <div class="project-details">
                <div class="row">
                    <div class="col-md-8">
                        <h4>${projectData.project_name}</h4>
                        <p class="text-muted mb-3">${projectData.description || 'No description available'}</p>
                        
                        <div class="progress-section mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Overall Progress</span>
                                <span class="fw-bold">${projectData.completion_percentage}%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" style="width: ${projectData.completion_percentage}%"></div>
                            </div>
                        </div>
                        
                        <h6>Recent Tasks</h6>
                        <div class="task-list">
                            ${generateTaskList(projectData.recent_tasks)}
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="project-stats">
                            <div class="stat-item mb-3">
                                <i class="fas fa-calendar-alt text-primary"></i>
                                <div class="ms-2">
                                    <small class="text-muted d-block">Created</small>
                                    <span>${formatDate(projectData.created_at)}</span>
                                </div>
                            </div>
                            
                            <div class="stat-item mb-3">
                                <i class="fas fa-tasks text-info"></i>
                                <div class="ms-2">
                                    <small class="text-muted d-block">Total Tasks</small>
                                    <span>${projectData.total_tasks}</span>
                                </div>
                            </div>
                            
                            <div class="stat-item mb-3">
                                <i class="fas fa-check-circle text-success"></i>
                                <div class="ms-2">
                                    <small class="text-muted d-block">Completed</small>
                                    <span>${projectData.completed_tasks}</span>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <i class="fas fa-clock text-warning"></i>
                                <div class="ms-2">
                                    <small class="text-muted d-block">In Progress</small>
                                    <span>${projectData.in_progress_tasks}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#projectDetailsContent').html(modalContent);
    }
    
    /**
     * Generate HTML for task list
     * @param {array} tasks - Array of task objects
     * @returns {string} HTML string for task list
     */
    function generateTaskList(tasks) {
        if (!tasks || tasks.length === 0) {
            return '<p class="text-muted">No recent tasks found.</p>';
        }
        
        let taskHtml = '';
        tasks.forEach(task => {
            const statusClass = getStatusClass(task.status);
            taskHtml += `
                <div class="task-item d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                    <div>
                        <span class="task-name">${task.task_name}</span>
                        <small class="text-muted d-block">Due: ${formatDate(task.due_date)}</small>
                    </div>
                    <span class="badge ${statusClass}">${task.status}</span>
                </div>
            `;
        });
        
        return taskHtml;
    }
    
    /**
     * Get CSS class for task status
     * @param {string} status - Task status
     * @returns {string} CSS class name
     */
    function getStatusClass(status) {
        switch (status.toLowerCase()) {
            case 'completed':
                return 'bg-success';
            case 'in progress':
                return 'bg-warning';
            case 'pending':
                return 'bg-secondary';
            default:
                return 'bg-light text-dark';
        }
    }
    
    /**
     * Show error message to user
     * @param {string} message - Error message to display
     */
    function showError(message) {
        const errorContent = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `;
        $('#projectDetailsContent').html(errorContent);
    }
    
    /**
     * Animate counter numbers on page load
     */
    function animateCounters() {
        $('.stat-content h3').each(function() {
            const $this = $(this);
            const finalValue = parseInt($this.text().replace(/[^\d]/g, ''));
            
            if (!isNaN(finalValue)) {
                $this.text('0');
                
                $({ countNum: 0 }).animate({
                    countNum: finalValue
                }, {
                    duration: 2000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.countNum) + ($this.text().includes('%') ? '%' : ''));
                    },
                    complete: function() {
                        $this.text(finalValue + ($this.text().includes('%') ? '%' : ''));
                    }
                });
            }
        });
    }
    
    /**
     * Set up loading states for action buttons
     */
    function setupLoadingStates() {
        $('.btn').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.html();
            
            // Don't add loading state to certain buttons
            if ($btn.hasClass('view-details') || $btn.hasClass('btn-close')) {
                return;
            }
            
            // Add loading state
            $btn.html('<i class="spinner-border spinner-border-sm me-2"></i>Loading...');
            $btn.prop('disabled', true);
            
            // Restore button after 2 seconds (adjust as needed)
            setTimeout(() => {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }, 2000);
        });
    }
    
    /**
     * Refresh portfolio data
     */
    function refreshPortfolioData() {
        // Show loading indicator
        showLoadingIndicator();
        
        // Reload the page after a short delay to show loading state
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    /**
     * Show loading indicator
     */
    function showLoadingIndicator() {
        const loadingHtml = `
            <div class="loading-overlay position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                 style="background: rgba(255,255,255,0.8); z-index: 9999;">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                    <p class="text-muted">Refreshing portfolio data...</p>
                </div>
            </div>
        `;
        
        $('body').append(loadingHtml);
    }
    
    /**
     * Filter projects based on status
     * @param {string} filter - Filter criteria
     */
    function filterProjects(filter) {
        const $projects = $('.project-card');
        
        // Remove active class from all filter buttons
        $('.filter-btn').removeClass('active');
        $(`.filter-btn[data-filter="${filter}"]`).addClass('active');
        
        if (filter === 'all') {
            $projects.slideDown(300);
        } else {
            $projects.each(function() {
                const $project = $(this);
                const status = $project.find('.project-status').text().toLowerCase().replace(/\s+/g, '-');
                
                if (status === filter) {
                    $project.slideDown(300);
                } else {
                    $project.slideUp(300);
                }
            });
        }
    }
    
    /**
     * Format date for display
     * @param {string} dateString - Date string to format
     * @returns {string} Formatted date
     */
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        const date = new Date(dateString);
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        };
        
        return date.toLocaleDateString('en-US', options);
    }
    
    /**
     * Add pulse animation to progress bars
     */
    function addProgressBarAnimation() {
        $('.progress-fill').addClass('animated-progress');
    }
    
    /**
     * Handle responsive behavior
     */
    function handleResponsive() {
        $(window).on('resize', function() {
            const windowWidth = $(window).width();
            
            // Adjust grid layout on smaller screens
            if (windowWidth < 768) {
                $('.projects-grid').addClass('mobile-layout');
            } else {
                $('.projects-grid').removeClass('mobile-layout');
            }
        });
        
        // Trigger resize event on load
        $(window).trigger('resize');
    }
    
    /**
     * Initialize search functionality if search box exists
     */
    function initializeSearch() {
        const $searchInput = $('#projectSearch');
        
        if ($searchInput.length) {
            $searchInput.on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.project-card').each(function() {
                    const $card = $(this);
                    const projectName = $card.find('.project-name').text().toLowerCase();
                    
                    if (projectName.includes(searchTerm)) {
                        $card.show();
                    } else {
                        $card.hide();
                    }
                });
            });
        }
    }
    
    // Initialize additional features
    handleResponsive();
    initializeSearch();
    addProgressBarAnimation();
    
    // Add smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Add fade-in animation for stat cards
    $('.stat-card').each(function(index) {
        $(this).css('animation-delay', (index * 100) + 'ms');
    });
    
    // Console log for debugging
    console.log('Portfolio page JavaScript initialized successfully');
});
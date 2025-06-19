// reporting.js - JavaScript for Task Tracker Reporting Dashboard

/**
 * Initialize all charts when the page loads
 * @param {Array} projectData - Array of project task data from PHP
 * @param {Array} statusData - Array of status data from PHP
 */
function initializeCharts(projectData, statusData) {
    // Initialize the bar chart for tasks by project
    createProjectChart(projectData);
    
    // Initialize the pie chart for task status
    createStatusChart(statusData);
    
    // Add interactive features
    addInteractiveFeatures();
}

/**
 * Creates a bar chart showing tasks distribution across projects
 * @param {Array} projectData - Project data with project names and task counts
 */
function createProjectChart(projectData) {
    const ctx = document.getElementById('projectChart');
    
    // Check if canvas element exists
    if (!ctx) {
        console.error('Project chart canvas not found');
        return;
    }
    
    // Extract project names and task counts from data
    const labels = projectData.map(item => item.project_name);
    const data = projectData.map(item => parseInt(item.task_count));
    
    // Define color palette for bars
    const backgroundColors = [
        'rgb(255, 152, 152)',  // Purple  
    ];
    
    const borderColors = [
        'rgb(255, 152, 152)',
    ];
    
    // Create the chart
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Tasks',
                data: data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                // BAR WIDTH CUSTOMIZATION
                barPercentage: 0.5,     // Controls the width of bars (0.1 = very thin, 1.0 = full width)
                categoryPercentage: 0.8, // Controls spacing between bar groups
                maxBarThickness: 60,     // Maximum bar width in pixels
                        // Minimum bar height in pixels
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Hide legend for cleaner look
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        // Customize tooltip content
                        title: function(context) {
                            return 'Project: ' + context[0].label;
                        },
                        label: function(context) {
                            return 'Tasks: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1, // Ensure whole numbers only
                        color: '#6b7280',
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        borderDash: [2, 2]
                    }
                },
                x: {
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12
                        },
                        maxRotation: 45, // Rotate labels if they're too long
                        minRotation: 0
                    },
                    grid: {
                        display: false // Hide vertical grid lines
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
}

/**
 * Creates a doughnut chart showing task status distribution
 * @param {Array} statusData - Status data with status names and counts
 */
function createStatusChart(statusData) {
    const ctx = document.getElementById('statusChart');
    
    // Check if canvas element exists
    if (!ctx) {
        console.error('Status chart canvas not found');
        return;
    }
    
    // Extract status labels and counts from data
    const labels = statusData.map(item => item.status);
    const data = statusData.map(item => parseInt(item.count));
    
    // Define colors for different statuses
    const getStatusColor = (status) => {
        switch(status.toLowerCase()) {
            case 'completed':
                return 'rgb(192, 201, 238)'; // Green
            case 'pending':
                return 'rgb(162, 170, 219)'; // Orange
            case 'in progress':
                return 'rgb(137, 138, 196)'; // Cyan
            default:
                return 'rgba(92, 246, 123, 0.8)'; // Purple
        }
    };
    
    const getBorderColor = (status) => {
        switch(status.toLowerCase()) {
            case 'completed':
                return 'rgb(192, 201, 238)';
            case 'pending':
                return 'rgb(162, 170, 219)';
            case 'in progress':
                return 'rgb(137, 138, 196)';
            default:
                return 'rgb(92, 246, 120)';
        }
    };
    
    const backgroundColors = labels.map(getStatusColor);
    const borderColors = labels.map(getBorderColor);
    
    // Create the doughnut chart
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 2,
                hoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%', // Creates the doughnut hole
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        color: '#374151',
                        font: {
                            size: 12
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        // Customize tooltip to show percentage
                        label: function(context) {
                            const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
}

/**
 * Add interactive features to the reporting dashboard
 */
function addInteractiveFeatures() {
    // Add hover effects to stat cards
    addStatCardHoverEffects();
    
    // Add click-to-refresh functionality
    addRefreshFunctionality();
    
    // Add keyboard navigation
    addKeyboardNavigation();
    
    // Initialize tooltips for additional information
    initializeTooltips();
}

/**
 * Add hover effects and animations to statistic cards
 */
function addStatCardHoverEffects() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        // Add mouse enter effect
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
        });
        
        // Add mouse leave effect
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
        });
        
        // Add click effect for potential future functionality
        card.addEventListener('click', function() {
            // Add a subtle click animation
            this.style.transform = 'translateY(-2px) scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'translateY(-4px) scale(1)';
            }, 150);
        });
    });
}



/**
 * Add keyboard navigation support
 */
function addKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        // Press 'R' to refresh (when not in an input field)
        if (e.key === 'r' || e.key === 'R') {
            if (!['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
                e.preventDefault();
                refreshDashboard();
            }
        }
        
        // Press 'Escape' to remove focus from any focused element
        if (e.key === 'Escape') {
            document.activeElement.blur();
        }
    });
}

/**
 * Initialize tooltips for additional information
 */
function initializeTooltips() {
    // Add tooltips to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        const cardType = card.classList[1]; // Get the specific card type class
        let tooltipText = '';
        
        switch(cardType) {
            case 'completed-card':
                tooltipText = 'Tasks that have been marked as completed';
                break;
            case 'incomplete-card':
                tooltipText = 'Tasks that are still pending completion';
                break;
            case 'overdue-card':
                tooltipText = 'Tasks that have passed their due date';
                break;
            case 'total-card':
                tooltipText = 'Total number of tasks across all projects';
                break;
        }
        
        if (tooltipText) {
            card.setAttribute('title', tooltipText);
        }
    });
    
    // Add tooltips to chart containers
    const chartCards = document.querySelectorAll('.chart-card');
    chartCards.forEach((card, index) => {
        if (index === 0) {
            card.setAttribute('title', 'Visual representation of task distribution across your projects');
        } else {
            card.setAttribute('title', 'Overview of task completion status');
        }
    });
}

/**
 * Utility function to format numbers with commas
 * @param {number} num - Number to format
 * @returns {string} Formatted number string
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Utility function to calculate percentage
 * @param {number} value - The value
 * @param {number} total - The total
 * @returns {string} Percentage string
 */
function calculatePercentage(value, total) {
    if (total === 0) return '0%';
    return ((value / total) * 100).toFixed(1) + '%';
}

/**
 * Handle responsive behavior for charts
 */
function handleResponsiveCharts() {
    window.addEventListener('resize', function() {
        // Charts will automatically resize due to Chart.js responsive option
        // But we can add custom logic here if needed
        
        const chartContainers = document.querySelectorAll('.chart-container');
        chartContainers.forEach(container => {
            // Ensure proper height on mobile devices
            if (window.innerWidth <= 768) {
                container.style.height = '200px';
            } else if (window.innerWidth <= 1024) {
                container.style.height = '250px';
            } else {
                container.style.height = '300px';
            }
        });
    });
}

/**
 * Add loading states for better UX
 */
function showLoadingState() {
    const chartContainers = document.querySelectorAll('.chart-container');
    
    chartContainers.forEach(container => {
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'chart-loading';
        loadingDiv.innerHTML = 'Loading chart...';
        container.appendChild(loadingDiv);
    });
}

/**
 * Remove loading states
 */
function hideLoadingState() {
    const loadingElements = document.querySelectorAll('.chart-loading');
    loadingElements.forEach(element => {
        element.remove();
    });
}

/**
 * Initialize smooth scrolling for internal links
 */
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Add animation observer for elements coming into view
 */
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe stat cards and chart cards
    const animatedElements = document.querySelectorAll('.stat-card, .chart-card, .activity-card');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// Initialize all features when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Handle responsive charts
    handleResponsiveCharts();
    
    // Initialize smooth scrolling
    initializeSmoothScrolling();
    
    // Initialize scroll animations
    if ('IntersectionObserver' in window) {
        initializeScrollAnimations();
    }
    
    // Add console message for developers
    console.log('📊 Task Tracker Reporting Dashboard Loaded Successfully!');
    console.log('💡 Press "R" to refresh the dashboard');
});
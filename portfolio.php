<?php
// Start session to get user information
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once './includes/database.php';

// Create database instance and establish connection
$database = new Database();
$db = $database->connect();

// Get current user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Query to get all projects for the current user with task counts and completion stats
    $project_query = "
        SELECT 
            p.id,
            p.project_name,
            COUNT(t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
            MIN(t.due_date) as earliest_due_date,
            MAX(t.created_at) as last_activity
        FROM projects p
        LEFT JOIN tasks t ON p.id = t.project_id
        WHERE p.user_id = :user_id
        GROUP BY p.id, p.project_name
        ORDER BY p.id DESC
    ";
    
    $stmt = $db->prepare($project_query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query to get overall statistics for the portfolio overview
    $stats_query = "
        SELECT 
            COUNT(DISTINCT p.id) as total_projects,
            COUNT(t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks
        FROM projects p
        LEFT JOIN tasks t ON p.id = t.project_id
        WHERE p.user_id = :user_id
    ";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':user_id', $user_id);
    $stats_stmt->execute();
    $overall_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Handle database errors gracefully
    echo "Error: " . $e->getMessage();
    exit();
}

// Function to calculate completion percentage
function getCompletionPercentage($completed, $total) {
    return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
}

// Function to get project status based on completion
function getProjectStatus($completed, $total) {
    if ($total == 0) return 'No Tasks';
    $percentage = ($completed / $total) * 100;
    if ($percentage == 100) return 'Completed';
    if ($percentage >= 50) return 'In Progress';
    return 'Just Started';
}

// Function to format dates nicely
function formatDate($date) {
    return $date ? date('M d, Y', strtotime($date)) : 'N/A';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - Task Manager</title>
    
    <!-- Include Bootstrap CSS for responsive design -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Include custom CSS for portfolio styling -->
    <link rel="stylesheet" href="./css/portfolio.css">
</head>
<body>
    <!-- Include the sidebar navigation -->
    <?php include './includes/sidebar.php'; ?>
    
    <!-- Main content area -->
    <div class="main-content">
        <!-- Page header with title and overview stats -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="fas fa-briefcase"></i>
                    My Portfolio
                </h1>
                <p class="page-subtitle">Overview of all your projects and achievements</p>
            </div>
        </div>

        <!-- Portfolio overview statistics cards -->
        <div class="portfolio-overview">
            <div class="row g-4">
                <!-- Total Projects Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon projects">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $overall_stats['total_projects']; ?></h3>
                            <p>Total Projects</p>
                        </div>
                    </div>
                </div>

                <!-- Total Tasks Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon tasks">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $overall_stats['total_tasks']; ?></h3>
                            <p>Total Tasks</p>
                        </div>
                    </div>
                </div>

                <!-- Completed Tasks Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $overall_stats['completed_tasks']; ?></h3>
                            <p>Completed Tasks</p>
                        </div>
                    </div>
                </div>

                <!-- Success Rate Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon rate">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo getCompletionPercentage($overall_stats['completed_tasks'], $overall_stats['total_tasks']); ?>%</h3>
                            <p>Success Rate</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects portfolio grid -->
        <div class="portfolio-section">
            <div class="section-header">
                <h2>Project Portfolio</h2>
                <p>Detailed view of all your projects with progress tracking</p>
            </div>

            <?php if (empty($projects)): ?>
                <!-- Show message when no projects exist -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-folder-plus"></i>
                    </div>
                    <h3>No Projects Yet</h3>
                    <p>Start building your portfolio by creating your first project!</p>
                    <a href="create_project.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Project
                    </a>
                </div>
            <?php else: ?>
                <!-- Display projects in a responsive grid -->
                <div class="projects-grid">
                    <?php foreach ($projects as $project): ?>
                        <?php
                            // Calculate project metrics for display
                            $completion_percentage = getCompletionPercentage($project['completed_tasks'], $project['total_tasks']);
                            $project_status = getProjectStatus($project['completed_tasks'], $project['total_tasks']);
                            $status_class = strtolower(str_replace(' ', '-', $project_status));
                        ?>
                        
                        <!-- Individual project card -->
                        <div class="project-card" data-project-id="<?php echo $project['id']; ?>">
                            <!-- Project header with name and status -->
                            <div class="project-header">
                                <h3 class="project-name"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <span class="project-status <?php echo $status_class; ?>">
                                    <?php echo $project_status; ?>
                                </span>
                            </div>

                            <!-- Progress bar showing completion percentage -->
                            <div class="progress-section">
                                <div class="progress-header">
                                    <span>Progress</span>
                                    <span class="progress-percentage"><?php echo $completion_percentage; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                                </div>
                            </div>

                            <!-- Task breakdown statistics -->
                            <div class="task-breakdown">
                                <div class="task-stat">
                                    <i class="fas fa-list"></i>
                                    <span>Total: <?php echo $project['total_tasks']; ?></span>
                                </div>
                                <div class="task-stat">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Done: <?php echo $project['completed_tasks']; ?></span>
                                </div>
                                <div class="task-stat">
                                    <i class="fas fa-clock text-warning"></i>
                                    <span>In Progress: <?php echo $project['in_progress_tasks']; ?></span>
                                </div>
                                <div class="task-stat">
                                    <i class="fas fa-pause-circle text-secondary"></i>
                                    <span>Pending: <?php echo $project['pending_tasks']; ?></span>
                                </div>
                            </div>

                            <!-- Project timeline information -->
                            <div class="project-timeline">
                                <div class="timeline-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Next Due: <?php echo formatDate($project['earliest_due_date']); ?></span>
                                </div>
                                <div class="timeline-item">
                                    <i class="fas fa-history"></i>
                                    <span>Last Activity: <?php echo formatDate($project['last_activity']); ?></span>
                                </div>
                            </div>

                            <!-- Action buttons for project management -->
                            <div class="project-actions">
                                <button class="btn btn-outline-primary btn-sm view-details" 
                                        data-project-id="<?php echo $project['id']; ?>">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <a href="project.php?id=<?php echo $project['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Manage
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for project details (populated via JavaScript) -->
    <div class="modal fade" id="projectDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Project Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="projectDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                    <div class="text-center p-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Include custom JavaScript for portfolio functionality -->
    <script src="./js/portfolio.js"></script>
</body>
</html>
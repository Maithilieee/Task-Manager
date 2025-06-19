<!-- Portfolio.php -->
<?php
/**
 * API Endpoint: Get Project Details
 * Returns detailed information about a specific project
 * Used by the portfolio page JavaScript for modal content
 */

// Start session to check user authentication
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit();
}

// Check if project ID is provided
if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Project ID is required'
    ]);
    exit();
}

// Include database connection
require_once '../includes/database.php';

try {
    // Create database instance and establish connection
    $database = new Database();
    $db = $database->connect();
    
    $project_id = intval($_GET['project_id']);
    $user_id = $_SESSION['user_id'];
    
    // First, verify that the project belongs to the current user
    $ownership_query = "
        SELECT COUNT(*) as count 
        FROM projects 
        WHERE id = :project_id AND user_id = :user_id
    ";
    
    $ownership_stmt = $db->prepare($ownership_query);
    $ownership_stmt->bindParam(':project_id', $project_id);
    $ownership_stmt->bindParam(':user_id', $user_id);
    $ownership_stmt->execute();
    
    $ownership_result = $ownership_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ownership_result['count'] == 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied: Project not found or not owned by user'
        ]);
        exit();
    }
    
    // Get detailed project information
    $project_query = "
        SELECT 
            p.id,
            p.project_name,
            p.user_id,
            COUNT(t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
            MIN(t.due_date) as earliest_due_date,
            MAX(t.created_at) as last_activity,
            p.project_name as created_at
        FROM projects p
        LEFT JOIN tasks t ON p.id = t.project_id
        WHERE p.id = :project_id AND p.user_id = :user_id
        GROUP BY p.id, p.project_name, p.user_id
    ";
    
    $project_stmt = $db->prepare($project_query);
    $project_stmt->bindParam(':project_id', $project_id);
    $project_stmt->bindParam(':user_id', $user_id);
    $project_stmt->execute();
    
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
        exit();
    }
    
    // Calculate completion percentage
    $completion_percentage = $project['total_tasks'] > 0 
        ? round(($project['completed_tasks'] / $project['total_tasks']) * 100, 1) 
        : 0;
    
    // Get recent tasks for this project (last 5 tasks)
    $tasks_query = "
        SELECT 
            id,
            task_name,
            description,
            due_date,
            status,
            created_at,
            color
        FROM tasks
        WHERE project_id = :project_id
        ORDER BY created_at DESC
        LIMIT 5
    ";
    
    $tasks_stmt = $db->prepare($tasks_query);
    $tasks_stmt->bindParam(':project_id', $project_id);
    $tasks_stmt->execute();
    
    $recent_tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get task priority distribution
    $priority_query = "
        SELECT 
            priority,
            COUNT(*) as count
        FROM tasks
        WHERE project_id = :project_id
        GROUP BY priority
    ";
    
    $priority_stmt = $db->prepare($priority_query);
    $priority_stmt->bindParam(':project_id', $project_id);
    $priority_stmt->execute();
    
    $priority_distribution = $priority_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get overdue tasks count
    $overdue_query = "
        SELECT COUNT(*) as overdue_count
        FROM tasks
        WHERE project_id = :project_id 
        AND due_date < CURDATE() 
        AND status != 'Completed'
    ";
    
    $overdue_stmt = $db->prepare($overdue_query);
    $overdue_stmt->bindParam(':project_id', $project_id);
    $overdue_stmt->execute();
    
    $overdue_result = $overdue_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare response data
    $response_data = [
        'project_id' => $project['id'],
        'project_name' => $project['project_name'],
        'description' => '', // You can add description field to projects table if needed
        'total_tasks' => intval($project['total_tasks']),
        'completed_tasks' => intval($project['completed_tasks']),
        'in_progress_tasks' => intval($project['in_progress_tasks']),
        'pending_tasks' => intval($project['pending_tasks']),
        'completion_percentage' => $completion_percentage,
        'earliest_due_date' => $project['earliest_due_date'],
        'last_activity' => $project['last_activity'],
        'created_at' => date('Y-m-d'), // You might want to add created_at field to projects table
        'recent_tasks' => $recent_tasks,
        'priority_distribution' => $priority_distribution,
        'overdue_count' => intval($overdue_result['overdue_count'])
    ];
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'message' => 'Project details retrieved successfully',
        'data' => $response_data
    ]);
    
} catch(PDOException $e) {
    // Handle database errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    // Handle other errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
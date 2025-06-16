<?php
// Start the session to access user session data
session_start();

// Include database connection file
require_once '../includes/database.php';

// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header('Location: ../login.php');
    exit(); // Stop script execution
}

// Only proceed if the request method is POST (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Fetch and sanitize input data
    $task_id    = isset($_POST['task_id']) ? (int) $_POST['task_id'] : 0; // Get task ID, default to 0 if not set
    $task_name  = trim($_POST['task_name'] ?? ''); // Remove extra spaces
    $description = trim($_POST['description'] ?? ''); // Remove extra spaces
    $due_date   = $_POST['due_date'] ?? ''; // Task due date
    $status     = $_POST['status'] ?? ''; // Task status
    $user_id    = $_SESSION['user_id']; // Logged-in user's ID

    // Validate required fields: task name and due date
    if (empty($task_name) || empty($due_date)) {
        $_SESSION['error'] = "Task name and due date are required."; // Store error in session
        header('Location: ' . $_SERVER['HTTP_REFERER']); // Redirect to previous page
        exit(); // Stop execution
    }

    try {
        // Create database connection
        $db = (new Database())->connect();

        // Verify that the task belongs to a project owned by the current user
        $verify = $db->prepare("
            SELECT t.id 
            FROM tasks t
            JOIN projects p ON t.project_id = p.id 
            WHERE t.id = ? AND p.user_id = ?
        ");
        $verify->execute([$task_id, $user_id]);

        // If no task is found or user doesn't own it, show error
        if (!$verify->fetch()) {
            $_SESSION['error'] = "Task not found or unauthorized access."; // Store error
            header('Location: ' . $_SERVER['HTTP_REFERER']); // Redirect
            exit(); // Stop script
        }

        // Prepare SQL statement to update the task
        $update = $db->prepare("
            UPDATE tasks 
            SET task_name = ?, description = ?, due_date = ?, status = ?, updated_at = NOW() 
            WHERE id = ?
        ");

        // Execute update with parameters
        $update->execute([
            $task_name,     // New task name
            $description,   // New description
            $due_date,      // New due date
            $status,        // New status
            $task_id        // Task ID to update
        ]);

        // Store success message in session
        $_SESSION['success'] = "Task updated successfully.";

    } catch (PDOException $e) {
        // Catch and store database error
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    // After processing, redirect to the page the user came from
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit(); // Exit to stop further execution
}

// If the page is accessed directly (not via POST), redirect to dashboard
$_SESSION['error'] = "Invalid request.";
header('Location: ../dashboard.php');
exit(); // End execution
?>
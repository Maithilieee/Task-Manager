<?php
// File: /api/create_task.php
session_start();
require_once '../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    $db = (new Database())->connect();

    // Get user's current project
    $stmt = $db->prepare("SELECT id FROM projects WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $project_id = $stmt->fetchColumn();

    $insert = $db->prepare("INSERT INTO tasks (project_id, task_name, description, due_date) VALUES (?, ?, ?, ?)");
    $insert->execute([$project_id, $task_name, $description, $due_date]);

    header("Location: ../dashboard.php");
    exit;
}
?>

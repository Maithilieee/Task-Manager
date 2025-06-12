<?php
session_start();
require_once '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['project_id'])) {
    $project_id = $_SESSION['project_id'];
    $task_name = trim($_POST['task_name']);
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];

    if ($task_name && $due_date && $priority) {
        $db = (new Database())->connect();
        $stmt = $db->prepare("INSERT INTO tasks (project_id, task_name, due_date, priority) VALUES (?, ?, ?, ?)");
        $stmt->execute([$project_id, $task_name, $due_date, $priority]);
    }
}
header("Location: ../dashboard.php");
exit;

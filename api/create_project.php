<?php
session_start();
require_once '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $project_name = trim($_POST['project_name']);

    if (!empty($project_name)) {
        $db = (new Database())->connect();
        $stmt = $db->prepare("INSERT INTO projects (user_id, project_name) VALUES (?, ?)");
        $stmt->execute([$user_id, $project_name]);

        // Store the current project ID in session (optional for future use)
        $_SESSION['project_id'] = $db->lastInsertId();

        header("Location: dashboard.php");
        exit;
    } else {
        echo "Project name cannot be empty.";
    }
} else {
    header("Location: ../user_login.php");
    exit;
}
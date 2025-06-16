<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

require_once '../includes/database.php';
$db = (new Database())->connect();

// Fetch project for current user
$project_stmt = $db->prepare("SELECT id FROM projects WHERE user_id = ?");
$project_stmt->execute([$_SESSION['user_id']]);
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo json_encode(['error' => 'Project not found']);
    exit;
}

$project_id = $project['id'];

// Fetch tasks
$task_stmt = $db->prepare("SELECT * FROM tasks WHERE project_id = ? ORDER BY due_date ASC, created_at DESC");
$task_stmt->execute([$project_id]);
$tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

// Categorize tasks
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));

$response = [
    'recently_assigned' => [],
    'do_today' => [],
    'do_next_week' => [],
    'do_later' => []
];

foreach ($tasks as $task) {
    $due = $task['due_date'];

    if (empty($due)) {
        $response['recently_assigned'][] = $task;
    } elseif ($due === $today) {
        $response['do_today'][] = $task;
    } elseif ($due <= $next_week) {
        $response['do_next_week'][] = $task;
    } else {
        $response['do_later'][] = $task;
    }
}

echo json_encode($response);

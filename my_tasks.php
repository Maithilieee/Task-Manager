<?php
// File: my_tasks.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Load database connection
require_once 'includes/database.php';
$db = (new Database())->connect();

// Handle AJAX requests for task operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_task':
            $task_name = trim($_POST['task_name']);
            $section = $_POST['section'];
            $project_id = $_POST['project_id'];
            
            if (!empty($task_name)) {
                // Determine due date based on section
                $due_date = null;
                switch ($section) {
                    case 'Do today':
                        $due_date = date('Y-m-d');
                        break;
                    case 'Do next week':
                        $due_date = date('Y-m-d', strtotime('+7 days'));
                        break;
                }
                
                $stmt = $db->prepare("INSERT INTO tasks (task_name, project_id, due_date, status) VALUES (?, ?, ?, 'pending')");
                $result = $stmt->execute([$task_name, $project_id, $due_date]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'task_id' => $db->lastInsertId()]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to add task']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Task name is required']);
            }
            exit;
        
        case 'toggle_task':
            $task_id = $_POST['task_id'];
            $stmt = $db->prepare("SELECT status FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $current_status = $stmt->fetchColumn();
            
            $new_status = ($current_status === 'completed') ? 'pending' : 'completed';
            
            $update_stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $result = $update_stmt->execute([$new_status, $task_id]);
            
            echo json_encode(['success' => $result, 'new_status' => $new_status]);
            exit;
    }
}

// Fetch current project for user
$project_stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$project_stmt->execute([$_SESSION['user_id']]);
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: project.php");
    exit;
}

$project_id = $project['id'];

// Fetch tasks for the project, ordered by due date
$task_stmt = $db->prepare("SELECT * FROM tasks WHERE project_id = ? ORDER BY due_date ASC, id DESC");
$task_stmt->execute([$project_id]);
$tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group tasks by time category
function categorizeTasks($tasks) {
    $today = date('Y-m-d');
    $nextWeek = date('Y-m-d', strtotime('+7 days'));

    $groups = [
        'Recently assigned' => [],
        'Do today' => [],
        'Do next week' => [],
        'Do later' => []
    ];

    foreach ($tasks as $task) {
        if (empty($task['due_date'])) {
            $groups['Recently assigned'][] = $task;
        } elseif ($task['due_date'] == $today) {
            $groups['Do today'][] = $task;
        } elseif ($task['due_date'] <= $nextWeek) {
            $groups['Do next week'][] = $task;
        } else {
            $groups['Do later'][] = $task;
        }
    }

    return $groups;
}

$groupedTasks = categorizeTasks($tasks);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="project-id" content="<?php echo htmlspecialchars($project_id); ?>">
    <title>My Tasks - Asana Clone</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/my_tasks.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-left">
                <div class="user-avatar">
                    <span>Ma</span>
                </div>
                <h1 class="page-title">My tasks</h1>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </div>
            <div class="header-right">
                <button class="btn-secondary">
                    <i class="fas fa-share"></i>
                    Share
                </button>
                <button class="btn-secondary">
                    <i class="fas fa-sliders-h"></i>
                    Customize
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="nav-tab active">
                <i class="fas fa-list"></i>
                List
            </button>
            <button class="nav-tab">
                <i class="fas fa-th-large"></i>
                Board
            </button>
            <button class="nav-tab">
                <i class="fas fa-calendar"></i>
                Calendar
            </button>
            <button class="nav-tab">
                <i class="fas fa-chart-bar"></i>
                Dashboard
            </button>
            <button class="nav-tab">
                <i class="fas fa-folder"></i>
                Files
            </button>
            <button class="nav-tab-add">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <button class="btn-primary" id="add-task-btn">
                <i class="fas fa-plus"></i>
                Add task
            </button>
            <div class="action-buttons">
                <button class="btn-filter">
                    <i class="fas fa-filter"></i>
                    Filter
                </button>
                <button class="btn-sort">
                    <i class="fas fa-sort"></i>
                    Sort
                </button>
                <button class="btn-group">
                    <i class="fas fa-layer-group"></i>
                    Group
                </button>
                <button class="btn-options">
                    <i class="fas fa-cog"></i>
                    Options
                </button>
            </div>
        </div>

        <!-- Task Table Header -->
        <div class="task-table-header">
            <div class="column-header name-column">Name</div>
            <div class="column-header due-date-column">Due date</div>
            <div class="column-header collaborators-column">Collaborators</div>
            <div class="column-header projects-column">Projects</div>
            <div class="column-header visibility-column">Task visibility</div>
            <div class="column-header actions-column">
                <i class="fas fa-plus"></i>
            </div>
        </div>

        <!-- Task Sections -->
        <div class="task-sections">
            <?php foreach ($groupedTasks as $section => $sectionTasks): ?>
                <div class="task-section" data-section="<?php echo htmlspecialchars($section); ?>">
                    <div class="section-header">
                        <i class="fas fa-chevron-down section-toggle"></i>
                        <h3 class="section-title"><?php echo htmlspecialchars($section); ?></h3>
                    </div>
                    
                    <div class="section-content">
                        <?php if (!empty($sectionTasks)): ?>
                            <?php foreach ($sectionTasks as $task): ?>
                                <div class="task-row" data-task-id="<?php echo $task['id']; ?>">
                                    <div class="task-checkbox-container">
                                        <button class="task-checkbox <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>" 
                                                data-task-id="<?php echo $task['id']; ?>">
                                            <?php if ($task['status'] === 'completed'): ?>
                                                <i class="fas fa-check"></i>
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                    <div class="task-name-container">
                                        <span class="task-name <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                                            <?php echo htmlspecialchars($task['task_name']); ?>
                                        </span>
                                    </div>
                                    <div class="task-due-date">
                                        <?php if (!empty($task['due_date'])): ?>
                                            <?php 
                                            $due_date = new DateTime($task['due_date']);
                                            $today = new DateTime();
                                            $diff = $today->diff($due_date);
                                            
                                            if ($task['due_date'] == date('Y-m-d')) {
                                                echo '<span class="due-today">Today</span>';
                                            } else {
                                                echo '<span class="due-date">' . $due_date->format('j M') . '</span>';
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-collaborators">
                                        <div class="collaborator-avatar">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                    </div>
                                    <div class="task-projects">
                                        <?php if ($task['status'] === 'completed'): ?>
                                            <span class="project-tag completed">
                                                <i class="fas fa-circle"></i>
                                                Software Development
                                            </span>
                                        <?php else: ?>
                                            <span class="project-tag">
                                                <i class="fas fa-circle"></i>
                                                Software Development
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-visibility">
                                        <span class="visibility-tag">
                                            <i class="fas fa-user"></i>
                                            Only me
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Add task input -->
                        <div class="add-task-row" style="display: none;">
                            <div class="task-checkbox-container">
                                <div class="task-checkbox disabled"></div>
                            </div>
                            <div class="task-name-container">
                                <input type="text" class="add-task-input" placeholder="Write a task name">
                            </div>
                            <div class="task-actions">
                                <button class="btn-save-task">Save</button>
                                <button class="btn-cancel-task">Cancel</button>
                            </div>
                        </div>
                        
                        <!-- Add task button -->
                        <div class="add-task-button">
                            <button class="btn-add-task" data-section="<?php echo htmlspecialchars($section); ?>">
                                Add task...
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Section Button -->
        <div class="add-section">
            <button class="btn-add-section">
                <i class="fas fa-plus"></i>
                Add section
            </button>
        </div>
    </div>

    <script src="js/my_tasks.js"></script>
</body>
</html>
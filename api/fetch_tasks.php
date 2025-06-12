<?php
function displayTasks($status) {
    require_once '../includes/database.php';
    $db = (new Database())->connect();
    $project_id = $_SESSION['project_id'];

    $stmt = $db->prepare("SELECT * FROM tasks WHERE project_id = ? AND status = ? ORDER BY due_date ASC");
    $stmt->execute([$project_id, $status]);

    while ($task = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<div class='task'>
                <h4>" . htmlspecialchars($task['task_name']) . "</h4>
                <p>Priority: " . $task['priority'] . "</p>
                <p>Due: " . $task['due_date'] . "</p>
              </div>";
    }
}

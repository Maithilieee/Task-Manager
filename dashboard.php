<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);
session_start();

// ========== Auth Check ==========
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

// ========== DB Connection ==========
require_once 'includes/database.php';
$db = (new Database())->connect();

// ========== Fetch User's Project ==========
$project_stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$project_stmt->execute([$_SESSION['user_id']]);
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
  header("Location: project.php");
  exit;
}

$project_id = $project['id'];

// ========== Handle AJAX Requests ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');

  try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
      case 'create_task':
        $stmt = $db->prepare("INSERT INTO tasks (project_id, task_name, description, due_date, status, color) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
          $project_id,
          $_POST['task_name'] ?? '',
          $_POST['description'] ?? '',
          $_POST['due_date'] ?? '',
          $_POST['status'] ?? 'Pending',
          $_POST['task_color'] ?? '#4285f4'
        ]);
        echo json_encode(['success' => $result]);
        break;

      case 'update_task':
        $stmt = $db->prepare("UPDATE tasks SET task_name = ?, description = ?, due_date = ?, status = ?, color = ? WHERE id = ? AND project_id = ?");
        $result = $stmt->execute([
          $_POST['task_name'] ?? '',
          $_POST['description'] ?? '',
          $_POST['due_date'] ?? '',
          $_POST['status'] ?? 'Pending',
          $_POST['task_color'] ?? '#4285f4',
          $_POST['task_id'] ?? 0,
          $project_id
        ]);
        echo json_encode(['success' => $result]);
        break;

      case 'update_status':
        $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ? AND project_id = ?");
        $result = $stmt->execute([
          $_POST['status'] ?? 'Pending',
          $_POST['task_id'] ?? 0,
          $project_id
        ]);
        echo json_encode(['success' => $result]);
        break;

      case 'delete_task':
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ? AND project_id = ?");
        $result = $stmt->execute([
          $_POST['task_id'] ?? 0,
          $project_id
        ]);
        echo json_encode(['success' => $result]);
        break;

      default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

  } catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
  exit;
}

// ========== Get Task Status Counts (Pie Chart) ==========
$statusStmt = $db->prepare("SELECT status, COUNT(*) AS count FROM tasks WHERE project_id = ? GROUP BY status");
$statusStmt->execute([$project_id]);

$statusCounts = ['Pending' => 0, 'In Progress' => 0, 'Completed' => 0];
foreach ($statusStmt as $row) {
  $statusCounts[$row['status']] = (int) $row['count'];
}

// ========== Get Task Count by Due Date (Bar Chart) ==========
$barLabels = [];
$barData = [];

$barStmt = $db->prepare("SELECT due_date, COUNT(*) as count FROM tasks WHERE project_id = ? AND status != 'Completed' GROUP BY due_date ORDER BY due_date ASC");
$barStmt->execute([$project_id]);

while ($row = $barStmt->fetch(PDO::FETCH_ASSOC)) {
  $barLabels[] = $row['due_date'] ? date('j M', strtotime($row['due_date'])) : 'N/A';
  $barData[] = (int) $row['count'];
}

// ========== Greeting Based on Time ==========
date_default_timezone_set('Asia/Kolkata');
$hour = (int) date('G');
$greeting = '';
$emoji = '';

if ($hour >= 5 && $hour < 12) {
  $greeting = 'Good morning';
  $emoji = '🌸';
} elseif ($hour >= 12 && $hour < 17) {
  $greeting = 'Good afternoon';
  $emoji = '☀️';
} elseif ($hour >= 17 && $hour < 21) {
  $greeting = 'Good evening';
  $emoji = '🌆';
} else {
  $greeting = 'Good night';
  $emoji = '🌙';
}

$projectColor = '#C7F0E1';
$projectName = $project['project_name'] ?? 'My Project';
$currentDayDate = date('l, F j');
?>




<!DOCTYPE html>
<html>

<head>
  <title><?php echo htmlspecialchars($project['project_name']); ?> - Dashboard</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="/css/">
</head>

<body>
  <!-- User Greeting -->
  <a href="logout.php" style="float: right; margin: 10px; color: red;">Logout</a>
  <h2>Welcome <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
  <h3>Project: <?php echo htmlspecialchars($project['project_name']); ?></h3>

  <!-- Sidebar Navigation -->
  <?php include './includes/sidebar.php'; ?>

  <!-- Main Dashboard Content -->
  <div class="main-content">
    <div style="text-align: center; margin-top: 20px;" class="display-name">
      <p style="font-size: 14px; color: #444; margin: 0;">
        <?php echo $currentDayDate; ?>
      </p>
      <p style="font-size: 32px; font-weight: 400; margin-top: 2px; color: #111;">
        <?php echo $greeting . ', ' . htmlspecialchars($_SESSION['user_name']) . ' ' . $emoji . '!'; ?>
      </p>
    </div>

    <!-- Main Task and Charts Section -->
    <div class="dashboard-container">
      <!-- Tasks Box -->
      <div class="task-box">
        <div class="task-container">
          <div class="task-header">
            <div class="header-content">
              <div class="task-icon">
                <div class="icon-circle"></div>
              </div>
              <h2 class="task-title">My tasks</h2>
              <div class="task-lock-icon">🔒</div>
            </div>
            <div class="task-actions">
              <button class="more-options">⋯</button>
            </div>
          </div>

          <div class="task-tabs">
            <button class="tab-btn active" data-tab="upcoming">Upcoming</button>
            <button class="tab-btn" data-tab="overdue">Overdue</button>
            <button class="tab-btn" data-tab="completed">Completed</button>
          </div>

          <div class="task-list">
            <button class="create-task-btn" onclick="openCreateModal()">
              <span class="plus-icon">+</span> Create task
            </button>

            <div class="task-list" id="taskList">
              <?php
              $tasks = $db->prepare("SELECT * FROM tasks WHERE project_id = ? ORDER BY due_date ASC");
              $tasks->execute([$project_id]);
              foreach ($tasks as $task) {
                $taskColor = $task['color'] ?? '#4285f4';
                $isCompleted = $task['status'] === 'Completed';
                echo "<div class='task-item task" . ($isCompleted ? " completed" : "") . "' 
                        data-id='{$task['id']}' 
                        data-name='" . htmlspecialchars($task['task_name']) . "' 
                        data-status='{$task['status']}' 
                        data-due='{$task['due_date']}' 
                        data-desc='" . htmlspecialchars($task['description']) . "'
                        data-color='" . htmlspecialchars($taskColor) . "'>
                        <div class='task-checkbox'>
                          <input type='checkbox' class='task-check' " . ($isCompleted ? "checked" : "") . " onclick='event.stopPropagation()'>
                        </div>
                        <div class='task-content'>
                         <span class='task-name'>" . htmlspecialchars($task['task_name']) . "</span>

                        </div>
                        <div class='task-meta'>
                         <span class='project-tag' style='background-color: " . htmlspecialchars($projectColor) . "'>
  <span class='project-dot' style='background-color: " . htmlspecialchars($taskColor) . ";'></span>
  " . htmlspecialchars($projectName) . "
</span>


                          <span class='task-date'>" . date('j M', strtotime($task['due_date'])) . "</span>
                        </div>
                      </div>";
              }
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Task Modals Embedded Inline -->
    <div id="taskModal" class="modal">
      <div class="modal-content">
        <span class="close-btn" onclick="closeModal('taskModal')">&times;</span>
        <h2>Create Task</h2>
        <form id="createTaskForm">
          <input type="hidden" name="action" value="create_task">
          <input type="text" name="task_name" id="task_name" placeholder="Task Name" required>
          <textarea name="description" id="description" placeholder="Task Description"></textarea>
          <input type="date" name="due_date" id="due_date" required>
          <label>Status:</label>
          <select name="status" id="status">
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
          </select>
          <label>Task Color:</label>
          <input type="hidden" name="task_color" id="task_color" value="#4285f4">
          <button type="button" id="create_color_picker" class="color-picker-btn">Choose Color</button>
          <div class="modal-buttons">
            <button type="submit">Add Task</button>
            <button type="button" onclick="closeModal('taskModal')">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div id="editTaskModal" class="modal">
      <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editTaskModal')">&times;</span>
        <h2>Edit Task</h2>
        <form id="editTaskForm">
          <input type="hidden" name="action" value="update_task">
          <input type="hidden" name="task_id" id="edit_task_id">
          <input type="text" name="task_name" id="edit_task_name" placeholder="Task Name" required>
          <textarea name="description" id="edit_description" placeholder="Task Description"></textarea>
          <input type="date" name="due_date" id="edit_due_date" required>
          <label>Status:</label>
          <select name="status" id="edit_status">
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
          </select>
          <label>Task Color:</label>
          <input type="hidden" name="task_color" id="edit_task_color" value="#4285f4">
          <button type="button" id="edit_color_picker" class="color-picker-btn">Choose Color</button>
          <div class="modal-buttons">
            <button type="submit">Update Task</button>
            <button type="button" onclick="closeModal('editTaskModal')">Cancel</button>
            <button type="button" onclick="deleteTask()" style="background-color: #f44336; color: white;">Delete Task</button>
          </div>
        </form>
      </div>
    </div>

    <div id="colorPickerModal" class="modal">
      <div class="modal-content color-picker-content">
        <span class="close-btn" onclick="closeModal('colorPickerModal')">&times;</span>
        <h3>Choose Task Color</h3>
        <div class="color-grid">
          <?php
          $colors = ['#ff6b6b','#4ecdc4','#45b7d1','#96ceb4','#ffeaa7','#dda0dd','#98d8c8','#f7dc6f','#bb8fce','#85c1e9','#f8c471','#82e0aa'];
          foreach ($colors as $color) {
            echo "<div class='color-box' data-color='{$color}' style='background-color: {$color};'></div>";
          }
          ?>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
      <div class="chart-box" id="pie-box">
        <canvas id="statusChart"></canvas>
      </div>
      <div class="chart-box" id="bar-box">
        <canvas id="barChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Notification -->
  <div id="notification" class="notification"></div>




  <!-- JS -->
<script>
  // ==================== GLOBAL ====================
  let currentColorTarget = null;

  document.addEventListener('DOMContentLoaded', () => {
    initializeEventListeners();
  });

  // ==================== INIT EVENT LISTENERS ====================
  function initializeEventListeners() {
    // Double-click to open edit modal
    document.addEventListener('dblclick', e => {
      const taskElement = e.target.closest('.task-item.task');
      if (taskElement) openEditModal(taskElement);
    });

    // Task checkbox click (prevent propagation + update)
    document.addEventListener('click', e => {
      if (e.target.classList.contains('task-check')) {
        e.stopPropagation();
        handleTaskCheckbox(e.target);
      }
    });

    // Close modal when clicking outside
    window.addEventListener('click', e => {
      document.querySelectorAll('.modal').forEach(modal => {
        if (e.target === modal) modal.style.display = 'none';
      });
    });

    // Color picker buttons
    document.getElementById('create_color_picker')?.addEventListener('click', () => {
      currentColorTarget = 'create';
      openColorPicker();
    });

    document.getElementById('edit_color_picker')?.addEventListener('click', () => {
      currentColorTarget = 'edit';
      openColorPicker();
    });

    // Color box selection
    document.querySelectorAll('.color-box').forEach(box => {
      box.addEventListener('click', () => {
        selectColor(box.dataset.color);
      });
    });

    // Form submissions
    document.getElementById('createTaskForm')?.addEventListener('submit', handleCreateTask);
    document.getElementById('editTaskForm')?.addEventListener('submit', handleEditTask);
  }

  // ==================== MODALS ====================
  function openCreateModal() {
    document.getElementById('taskModal').style.display = 'block';
    document.getElementById('task_name').focus();
  }

  function openEditModal(taskElement) {
    const modal = document.getElementById('editTaskModal');

    const taskData = {
      id: taskElement.dataset.id,
      name: taskElement.dataset.name,
      desc: taskElement.dataset.desc,
      due: taskElement.dataset.due,
      status: taskElement.dataset.status,
      color: taskElement.dataset.color || '#4285f4'
    };

    document.getElementById('edit_task_id').value = taskData.id;
    document.getElementById('edit_task_name').value = taskData.name;
    document.getElementById('edit_description').value = taskData.desc;
    document.getElementById('edit_due_date').value = taskData.due;
    document.getElementById('edit_status').value = taskData.status;
    document.getElementById('edit_task_color').value = taskData.color;

    const btn = document.getElementById('edit_color_picker');
    btn.style.backgroundColor = taskData.color;
    btn.style.borderColor = taskData.color;

    modal.style.display = 'block';
    document.getElementById('edit_task_name').focus();
  }

  function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }

  // ==================== COLOR PICKER ====================
  function openColorPicker() {
    document.getElementById('colorPickerModal').style.display = 'block';
  }

  function selectColor(color) {
    const colorInputId = currentColorTarget === 'create' ? 'task_color' : 'edit_task_color';
    const btnId = currentColorTarget === 'create' ? 'create_color_picker' : 'edit_color_picker';

    document.getElementById(colorInputId).value = color;

    const btn = document.getElementById(btnId);
    btn.style.backgroundColor = color;
    btn.style.borderColor = color;

    closeModal('colorPickerModal');
  }

  // ==================== TASK STATUS (Checkbox) ====================
  function handleTaskCheckbox(checkbox) {
    const taskElement = checkbox.closest('.task-item.task');
    const taskId = taskElement.dataset.id;
    const status = checkbox.checked ? 'Completed' : 'Pending';

    taskElement.classList.toggle('completed', checkbox.checked);
    updateTaskStatus(taskId, status);
  }

  function updateTaskStatus(taskId, status) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('task_id', taskId);
    formData.append('status', status);

    fetch(window.location.href, {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        const msg = data.success ? 'Task status updated successfully' : 'Failed to update task status';
        showNotification(msg, data.success ? 'success' : 'error');
      })
      .catch(err => {
        console.error(err);
        showNotification('An error occurred', 'error');
      });
  }

  // ==================== TASK CREATE / EDIT ====================
  function handleCreateTask(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    fetch(window.location.href, {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showNotification('Task created successfully', 'success');
          closeModal('taskModal');
          e.target.reset();
          document.getElementById('task_color').value = '#4285f4';
          document.getElementById('create_color_picker').style.backgroundColor = '#4285f4';
          document.getElementById('create_color_picker').style.borderColor = '#4285f4';
          setTimeout(() => location.reload(), 1000);
        } else {
          showNotification('Failed to create task', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        showNotification('An error occurred', 'error');
      });
  }

  function handleEditTask(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    fetch(window.location.href, {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showNotification('Task updated successfully', 'success');
          closeModal('editTaskModal');
          setTimeout(() => location.reload(), 1000);
        } else {
          showNotification('Failed to update task', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        showNotification('An error occurred', 'error');
      });
  }

  // ==================== DELETE TASK ====================
  function deleteTask() {
    if (!confirm('Are you sure you want to delete this task?')) return;

    const taskId = document.getElementById('edit_task_id').value;
    const formData = new FormData();
    formData.append('action', 'delete_task');
    formData.append('task_id', taskId);

    fetch(window.location.href, {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showNotification('Task deleted successfully', 'success');
          closeModal('editTaskModal');
          setTimeout(() => location.reload(), 1000);
        } else {
          showNotification('Failed to delete task', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        showNotification('An error occurred', 'error');
      });
  }

  // ==================== NOTIFICATION ====================
  function showNotification(msg, type) {
    const notif = document.getElementById('notification');
    notif.textContent = msg;
    notif.className = `notification ${type} show`;
    setTimeout(() => notif.classList.remove('show'), 3000);
  }

  // ==================== CHART.JS: STATUS DOUGHNUT ====================
  const statusCtx = document.getElementById('statusChart')?.getContext('2d');
  if (statusCtx) {
    new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: ['Pending', 'In Progress', 'Completed'],
        datasets: [{
          data: [
            <?php echo $statusCounts['Pending']; ?>,
            <?php echo $statusCounts['In Progress']; ?>,
            <?php echo $statusCounts['Completed']; ?>
          ],
          backgroundColor: ['rgb(201, 87, 146)', 'rgba(135, 206, 250, 0.6)', 'rgb(221, 235, 157)'],
          borderWidth: 2
        }]
      },
      options: {
        cutout: '70%',
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 20, font: { size: 14 } }
          },
          title: {
            display: true,
            text: 'Task Status Overview',
            font: { size: 18 }
          }
        }
      }
    });
  }

  // ==================== CHART.JS: INCOMPLETE TASKS BAR ====================
  const barCtx = document.getElementById('barChart')?.getContext('2d');
  if (barCtx) {
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($barLabels); ?>,
        datasets: [{
          label: 'Incomplete Tasks',
          data: <?php echo json_encode($barData); ?>,
          backgroundColor: '#4ecdc4',
          borderRadius: 5
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { title: { display: true, text: 'Due Dates' } },
          y: {
            beginAtZero: true,
            title: { display: true, text: 'Tasks' }
          }
        }
      }
    });
  }
</script>



</body>

</html>
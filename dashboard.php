<?php
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

// Fetch user's project
$project_stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$project_stmt->execute([$_SESSION['user_id']]);
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
  header("Location: project.php");
  exit;
}

$project_id = $project['id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create_task':
                $stmt = $db->prepare("INSERT INTO tasks (project_id, task_name, description, due_date, status, color) VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $project_id,
                    $_POST['task_name'],
                    $_POST['description'] ?? '',
                    $_POST['due_date'],
                    $_POST['status'] ?? 'Pending',
                    $_POST['task_color'] ?? '#4285f4'
                ]);
                echo json_encode(['success' => $result]);
                exit;
                
            case 'update_task':
                $stmt = $db->prepare("UPDATE tasks SET task_name = ?, description = ?, due_date = ?, status = ?, color = ? WHERE id = ? AND project_id = ?");
                $result = $stmt->execute([
                    $_POST['task_name'],
                    $_POST['description'] ?? '',
                    $_POST['due_date'],
                    $_POST['status'],
                    $_POST['task_color'] ?? '#4285f4',
                    $_POST['task_id'],
                    $project_id
                ]);
                echo json_encode(['success' => $result]);
                exit;
                
            case 'update_status':
                $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ? AND project_id = ?");
                $result = $stmt->execute([$_POST['status'], $_POST['task_id'], $project_id]);
                echo json_encode(['success' => $result]);
                exit;
                
            case 'delete_task':
                $stmt = $db->prepare("DELETE FROM tasks WHERE id = ? AND project_id = ?");
                $result = $stmt->execute([$_POST['task_id'], $project_id]);
                echo json_encode(['success' => $result]);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars($project['project_name']); ?> - Dashboard</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <style>
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        border-radius: 8px;
        width: 500px;
        max-width: 90%;
        position: relative;
    }

    .close-btn {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        position: absolute;
        right: 15px;
        top: 10px;
    }

    .close-btn:hover,
    .close-btn:focus {
        color: black;
        text-decoration: none;
    }

    .modal h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
    }

    .modal form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .modal input,
    .modal textarea,
    .modal select {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .modal textarea {
        min-height: 80px;
        resize: vertical;
    }

    .modal label {
        font-weight: bold;
        color: #555;
    }

    .color-picker-btn {
        padding: 10px 15px;
        border: 2px solid #4285f4;
        background-color: #4285f4;
        color: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .modal-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .modal button {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .modal button[type="submit"] {
        background-color: #4285f4;
        color: white;
    }

    .modal button[type="button"] {
        background-color: #f1f1f1;
        color: #333;
    }

    .modal button:hover {
        opacity: 0.9;
    }

    /* Color Picker Modal */
    .color-picker-content {
        width: 300px;
    }

    .color-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-top: 15px;
    }

    .color-box {
        width: 50px;
        height: 50px;
        border-radius: 4px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: transform 0.2s;
    }

    .color-box:hover {
        transform: scale(1.1);
        border-color: #333;
    }

    /* Task styles */
    .task-item.completed {
        opacity: 0.7;
    }

    .task-item.completed .task-name {
        text-decoration: line-through;
    }

    .create-task-btn {
        background-color: #4285f4;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 4px;
        cursor: pointer;
        margin: 15px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .create-task-btn:hover {
        background-color: #3367d6;
    }

    .plus-icon {
        font-size: 16px;
        font-weight: bold;
    }

    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        color: white;
        font-weight: bold;
        z-index: 1001;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .notification.success {
        background-color: #4caf50;
    }

    .notification.error {
        background-color: #f44336;
    }

    .notification.show {
        opacity: 1;
    }
  </style>
</head>
<body>

  <!-- User Greeting -->
  <h2>Welcome <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
  <h3>Project: <?php echo htmlspecialchars($project['project_name']); ?></h3>
  <a href="logout.php" style="float: right; margin: 10px; color: red;">Logout</a>

  <!-- Sidebar Navigation -->
  <nav class="sidebar">
    <h2>📝 Project</h2>
    <p><?php echo htmlspecialchars($project['project_name']); ?></p>

    <a href="#" class="nav-item create-btn" onclick="openCreateModal()">
      <span class="icon">+</span> Create
    </a>
    <a href="#" class="nav-item active">
      <span class="icon">🏠</span> Home
    </a>
    <a href="#" class="nav-item">
      <span class="icon">✓</span> My tasks
    </a>
    <a href="#" class="nav-item">
      <span class="icon"><div class="inbox-dot"></div></span> Inbox
    </a>

    <div class="section-title">Insights <button class="plus">+</button></div>
    <a href="#" class="nav-item"><span class="icon">📊</span> Reporting</a>
    <a href="#" class="nav-item"><span class="icon">📁</span> Portfolios</a>
    <a href="#" class="nav-item"><span class="icon">🎯</span> Goals</a>

    <div class="section-title">Projects <button class="plus">+</button></div>
    <a href="#" class="nav-item"><span class="icon"><div class="project-color"></div></span> Software Development</a>

    <div class="section-title">Team</div>
    <a href="#" class="nav-item">
      <span class="icon">👥</span> My workspace <span class="workspace-arrow">›</span>
    </a>
    <a href="logout.php">Logout</a>
  </nav>

  <!-- Main Dashboard Content -->
  <div class="main-content">
    <h1>Hello <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</h1>

    <div class="main-task-box">
      <div class="task-container">
        <!-- Header Section -->
        <div class="task-header">
          <div class="header-content">
            <div class="task-icon"><div class="icon-circle"></div></div>
            <h2 class="task-title">My tasks</h2>
            <div class="task-lock-icon">🔒</div>
          </div>
          <div class="task-actions">
            <button class="more-options">⋯</button>
          </div>
        </div>

        <!-- Tab Navigation -->
        <div class="task-tabs">
          <button class="tab-btn active" data-tab="upcoming">Upcoming</button>
          <button class="tab-btn" data-tab="overdue">Overdue</button>
          <button class="tab-btn" data-tab="completed">Completed</button>
        </div>

        <!-- Add Task Button -->
        <button class="create-task-btn" onclick="openCreateModal()">
          <span class="plus-icon">+</span>
          Create task
        </button>

        <!-- Task List -->
        <div class="task-list" id="taskList">
          <?php
          $tasks = $db->prepare("SELECT * FROM tasks WHERE project_id = ? ORDER BY due_date ASC");
          $tasks->execute([$project_id]);
          foreach ($tasks as $task) {
            $taskColor = $task['color'] ?? '#4285f4';
            $isCompleted = $task['status'] === 'Completed';
            echo "<div class='task-item task" . ($isCompleted ? ' completed' : '') . "' 
                    data-id='{$task['id']}' 
                    data-name='" . htmlspecialchars($task['task_name']) . "' 
                    data-status='{$task['status']}' 
                    data-due='{$task['due_date']}' 
                    data-desc='" . htmlspecialchars($task['description']) . "'
                    data-color='" . htmlspecialchars($taskColor) . "'>
                    <div class='task-checkbox'>
                      <input type='checkbox' class='task-check' " . ($isCompleted ? 'checked' : '') . " onclick='event.stopPropagation()'>
                    </div>
                    <div class='task-content'>
                      <span class='task-name' style='color: {$taskColor}'>" . htmlspecialchars($task['task_name']) . "</span>
                    </div>
                    <div class='task-meta'>
                      <span class='task-project'>".  htmlspecialchars($project['project_name'])."</span>
                      <span class='task-date'>" . date('j M', strtotime($task['due_date'])) . "</span>
                    </div>
                  </div>";
          }
          ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Task Creation Modal -->
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

  <!-- Edit Task Modal -->
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

  <!-- Color Picker Modal -->
  <div id="colorPickerModal" class="modal">
    <div class="modal-content color-picker-content">
      <span class="close-btn" onclick="closeModal('colorPickerModal')">&times;</span>
      <h3>Choose Task Color</h3>
      <div class="color-grid">
        <div class="color-box" data-color="#ff6b6b" style="background-color: #ff6b6b;"></div>
        <div class="color-box" data-color="#4ecdc4" style="background-color: #4ecdc4;"></div>
        <div class="color-box" data-color="#45b7d1" style="background-color: #45b7d1;"></div>
        <div class="color-box" data-color="#96ceb4" style="background-color: #96ceb4;"></div>
        <div class="color-box" data-color="#ffeaa7" style="background-color: #ffeaa7;"></div>
        <div class="color-box" data-color="#dda0dd" style="background-color: #dda0dd;"></div>
        <div class="color-box" data-color="#98d8c8" style="background-color: #98d8c8;"></div>
        <div class="color-box" data-color="#f7dc6f" style="background-color: #f7dc6f;"></div>
        <div class="color-box" data-color="#bb8fce" style="background-color: #bb8fce;"></div>
        <div class="color-box" data-color="#85c1e9" style="background-color: #85c1e9;"></div>
        <div class="color-box" data-color="#f8c471" style="background-color: #f8c471;"></div>
        <div class="color-box" data-color="#82e0aa" style="background-color: #82e0aa;"></div>
      </div>
    </div>
  </div>

  <!-- Notification -->
  <div id="notification" class="notification"></div>

  <!-- JavaScript -->
  <script>
    // Global variables
    let currentColorTarget = null;

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
      initializeEventListeners();
    });

    // Initialize all event listeners
    function initializeEventListeners() {
      // Task double-click to edit
      document.addEventListener('dblclick', function(e) {
        const taskElement = e.target.closest('.task-item.task');
        if (taskElement) {
          openEditModal(taskElement);
        }
      });

      // Checkbox handling
      document.addEventListener('click', function(e) {
        if (e.target.classList.contains('task-check')) {
          e.stopPropagation();
          handleTaskCheckbox(e.target);
        }
      });

      // Modal close when clicking outside
      window.addEventListener('click', function (event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
          if (event.target === modal) {
            modal.style.display = 'none';
          }
        });
      });

      // Color picker buttons
      document.getElementById('create_color_picker')?.addEventListener('click', function() {
        currentColorTarget = 'create';
        openColorPicker();
      });

      document.getElementById('edit_color_picker')?.addEventListener('click', function() {
        currentColorTarget = 'edit';
        openColorPicker();
      });

      // Color selection
      document.querySelectorAll('.color-box').forEach(box => {
        box.addEventListener('click', function() {
          selectColor(this.dataset.color);
        });
      });

      // Form submissions
      document.getElementById('createTaskForm')?.addEventListener('submit', handleCreateTask);
      document.getElementById('editTaskForm')?.addEventListener('submit', handleEditTask);
    }

    // Open create task modal
    function openCreateModal() {
      document.getElementById('taskModal').style.display = 'block';
      document.getElementById('task_name').focus();
    }

    // Open edit task modal
    function openEditModal(taskElement) {
      if (!taskElement) return;

      const modal = document.getElementById('editTaskModal');
      const taskData = {
        id: taskElement.dataset.id,
        name: taskElement.dataset.name,
        desc: taskElement.dataset.desc,
        due: taskElement.dataset.due,
        status: taskElement.dataset.status,
        color: taskElement.dataset.color || '#4285f4'
      };

      // Populate form fields
      document.getElementById('edit_task_id').value = taskData.id;
      document.getElementById('edit_task_name').value = taskData.name;
      document.getElementById('edit_description').value = taskData.desc;
      document.getElementById('edit_due_date').value = taskData.due;
      document.getElementById('edit_status').value = taskData.status;
      document.getElementById('edit_task_color').value = taskData.color;

      // Update color picker button
      const colorButton = document.getElementById('edit_color_picker');
      colorButton.style.backgroundColor = taskData.color;
      colorButton.style.borderColor = taskData.color;

      modal.style.display = 'block';
      document.getElementById('edit_task_name').focus();
    }

    // Close modal
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }

    // Open color picker
    function openColorPicker() {
      document.getElementById('colorPickerModal').style.display = 'block';
    }

    // Select color
    function selectColor(colorValue) {
      if (currentColorTarget === 'create') {
        document.getElementById('task_color').value = colorValue;
        const btn = document.getElementById('create_color_picker');
        btn.style.backgroundColor = colorValue;
        btn.style.borderColor = colorValue;
      } else if (currentColorTarget === 'edit') {
        document.getElementById('edit_task_color').value = colorValue;
        const btn = document.getElementById('edit_color_picker');
        btn.style.backgroundColor = colorValue;
        btn.style.borderColor = colorValue;
      }
      closeModal('colorPickerModal');
    }

    // Handle task checkbox
    function handleTaskCheckbox(checkbox) {
      const taskElement = checkbox.closest('.task-item.task');
      if (!taskElement) return;

      const taskId = taskElement.dataset.id;
      const status = checkbox.checked ? 'Completed' : 'Pending';
      
      // Update UI immediately
      if (checkbox.checked) {
        taskElement.classList.add('completed');
      } else {
        taskElement.classList.remove('completed');
      }

      // Update database
      updateTaskStatus(taskId, status);
    }

    // Update task status
    function updateTaskStatus(taskId, status) {
      const formData = new FormData();
      formData.append('action', 'update_status');
      formData.append('task_id', taskId);
      formData.append('status', status);

      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showNotification('Task status updated successfully', 'success');
        } else {
          showNotification('Failed to update task status', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
      });
    }

    // Handle create task form
    function handleCreateTask(e) {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
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
      .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
      });
    }

    // Handle edit task form
    function handleEditTask(e) {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showNotification('Task updated successfully', 'success');
          closeModal('editTaskModal');
          setTimeout(() => location.reload(), 1000);
        } else {
          showNotification('Failed to update task', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
      });
    }

    // Delete task
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
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showNotification('Task deleted successfully', 'success');
          closeModal('editTaskModal');
          setTimeout(() => location.reload(), 1000);
        } else {
          showNotification('Failed to delete task', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
      });
    }

    // Show notification
    function showNotification(message, type) {
      const notification = document.getElementById('notification');
      notification.textContent = message;
      notification.className = `notification ${type} show`;
      
      setTimeout(() => {
        notification.classList.remove('show');
      }, 3000);
    }
  </script>

</body>
</html>
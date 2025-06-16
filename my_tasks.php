<?php
session_start();
require_once 'includes/database.php';
$db = (new Database())->connect();

include 'includes/sidebar.php'; // Will now work safely
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Tasks</title>
  <link rel="stylesheet" href="css/my_tasks.css" />
</head>
<body>

  <?php include 'includes/sidebar.php'; ?>

  <div class="main-content">
    <h1>My Tasks</h1>

    <div class="task-section" id="recentlyAssigned">
      <h2>Recently assigned</h2>
      <div class="task-list" id="recentlyAssignedList"></div>
    </div>

    <div class="task-section" id="doToday">
      <h2>Do today</h2>
      <div class="task-list" id="doTodayList"></div>
    </div>

    <div class="task-section" id="doNextWeek">
      <h2>Do next week</h2>
      <div class="task-list" id="doNextWeekList"></div>
    </div>

    <div class="task-section" id="doLater">
      <h2>Do later</h2>
      <div class="task-list" id="doLaterList"></div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      fetch('/backend/api/get_my_tasks.php')
        .then(res => res.json())
        .then(data => {
          renderTasks(data.recently_assigned, 'recentlyAssignedList');
          renderTasks(data.do_today, 'doTodayList');
          renderTasks(data.do_next_week, 'doNextWeekList');
          renderTasks(data.do_later, 'doLaterList');
        });

      function renderTasks(tasks, containerId) {
        const container = document.getElementById(containerId);
        if (!tasks || tasks.length === 0) {
          container.innerHTML = '<p class="no-task">No tasks</p>';
          return;
        }

        container.innerHTML = tasks.map(task => `
          <div class="task-card" style="border-left: 5px solid ${task.color || '#4285f4'};">
            <div class="task-name">${task.task_name}</div>
            <div class="task-meta">
              <span class="due-date">${task.due_date || 'No due date'}</span>
          <span class="status ${task.status.toLowerCase().replace(/\s+/g, '-')}">${task.status}</span>

            </div>
          </div>
        `).join('');
      }
    });
  </script>
</body>
</html>

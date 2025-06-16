<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    // Optional: redirect to login if session expired
    header("Location: login.php");
    exit();
}
require_once('/includes/database.php');

// Count total tasks, completed, incomplete, overdue
$user_email = $_SESSION['user_email'];

$stmt = $conn->prepare("
  SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status != 'Completed' THEN 1 ELSE 0 END) as incomplete,
    SUM(CASE WHEN status != 'Completed' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue
  FROM tasks 
  WHERE user_email = ?
");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Output values
$total = $result['total'];
$completed = $result['completed'];
$incomplete = $result['incomplete'];
$overdue = $result['overdue'];

// Load bar chart data (example: by status or by due_date)
$barLabels = ['To Do', 'Doing', 'Done']; // Customize this based on your schema
$barData = [2, 0, 1]; // Replace with real query if you have task sections
?>
<!DOCTYPE html>
<html>
    <style>
        .sidebar {
  position: fixed;
  width: 220px;
  height: 100vh;
  background: #1e1e2f;
  color: white;
}

.main-content {
  margin-left: 220px;
  padding: 2rem;
}

.stat-cards {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.card {
  background: #fff;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

    </style>
<head>
  <title>Dashboard Overview</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <h2>Dashboard Overview</h2>
    <div class="stat-cards">
      <div class="card">Total Tasks: <?= $total ?></div>
      <div class="card">Completed: <?= $completed ?></div>
      <div class="card">Incomplete: <?= $incomplete ?></div>
      <div class="card">Overdue: <?= $overdue ?></div>
    </div>

    <div class="charts">
      <canvas id="barChart" width="400" height="200"></canvas>
      <canvas id="pieChart" width="400" height="200"></canvas>
    </div>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", function () {
    // Bar Chart
    const barCtx = document.getElementById("barChart").getContext("2d");
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($barLabels); ?>,
        datasets: [{
          label: 'Tasks by Section',
          data: <?php echo json_encode($barData); ?>,
          backgroundColor: '#9b5de5'
        }]
      }
    });

    // Pie Chart
    const pieCtx = document.getElementById("pieChart").getContext("2d");
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: ['Completed', 'Incomplete'],
        datasets: [{
          data: [<?= $completed ?>, <?= $incomplete ?>],
          backgroundColor: ['#06d6a0', '#ff006e']
        }]
      }
    });
  });
  </script>
</body>
</html>

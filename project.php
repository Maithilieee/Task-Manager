<?php
// File: /project.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = trim($_POST['project_name']);
    $user_id = $_SESSION['user_id'];

    $db = (new Database())->connect();
    $stmt = $db->prepare("INSERT INTO projects (user_id, project_name) VALUES (?, ?)");
    $stmt->execute([$user_id, $project_name]);

    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Name Your Project</title>
  <link rel="stylesheet" href="css/project.css">
</head>
<body>
  <h2>Hello <?php echo $_SESSION['user_name']; ?> 👋</h2>
  <form method="POST">
    <input type="text" name="project_name" placeholder="Enter your project name" required>
    <button type="submit">Create Project</button>
  </form>
</body>
</html>

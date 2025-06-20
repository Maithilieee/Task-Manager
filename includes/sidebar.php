<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure database is loaded
if (!isset($db)) {
    require_once __DIR__ . '/database.php';
    $db = (new Database())->connect();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch current project
$project_stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$project_stmt->execute([$_SESSION['user_id']]);
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: ../project.php");
    exit;
}

$project_id = $project['id'];
?>


<!-- Sidebar Styles -->

<link rel="stylesheet" href="/css/sidebar.css">

<!-- Sidebar -->
<nav class="sidebar">
    <h2>📝 Project</h2>
    <p><?php echo htmlspecialchars($project['project_name']); ?></p>

    <a href="#" class="nav-item create-btn" onclick="openCreateModal()">
        <span class="icon">+</span> Create
    </a>
    <a href="./dashboard.php" class="nav-item active">
        <span class="icon">🏠</span> Home
    </a>
    <a href="./my_tasks.php" class="nav-item">
        <span class="icon">✓</span> My tasks
    </a>
    <a href="#" class="nav-item">
        <span class="icon">
            <div class="inbox-dot"></div>
        </span> Inbox
    </a>

    <div class="section-title">Insights <button class="plus">+</button></div>
    <a href="../reporting.php" class="nav-item"><span class="icon">📊</span> Reporting</a>
    <a href="../portfolio.php" class="nav-item"><span class="icon">📁</span> Portfolios</a>
    <a href="#" class="nav-item"><span class="icon">🎯</span> Goals</a>

    <div class="section-title">Projects <button class="plus">+</button></div>
    <a href="#" class="nav-item">
        <span class="icon">
            <div class="project-color"></div>
        </span> Software Development
    </a>

    <div class="section-title">Team</div>
    <a href="#" class="nav-item">
        <span class="icon">👥</span> My workspace <span class="workspace-arrow">›</span>
    </a>
    <a href="logout.php">Logout</a>
</nav>
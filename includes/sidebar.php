<?php
require_once __DIR__ . '/database.php';
// Adjust the path if needed

$project_stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$project_stmt->execute([$_SESSION['user_id']]);
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: project.php");
    exit;
}

$project_id = $project['id'];
?>

<!-- Sidebar Styles -->
<style>
    * {
        box-sizing: border-box;
    }

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 260px;
        height: 100vh;
        background-color: #222831;
        border-right: 1px solid #3a3b3e;
        padding: 20px;
        z-index: 1000;
        overflow-y: auto;
        font-size: large;
    }

    .sidebar h2 {
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #3a3b3e;
    }

    .sidebar p {
        color: #b0b3b8;
        font-size: 14px;
        margin-bottom: 20px;
        padding: 8px 12px;
        background-color: #3a3b3e;
        border-radius: 6px;
    }

    .sidebar a {
        display: block;
        color: #e8e9ea;
        text-decoration: none;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 14px;
        transition: background-color 0.2s ease;
        margin-bottom: 5px;
    }

    .sidebar a:hover {
        background-color: #3a3b3e;
    }

    .nav-item.active {
        background-color: #404040;
    }

    .icon {
        margin-right: 10px;
    }

    .create-btn {
        background: #ff4444;
        color: white;
        font-weight: bold;
        text-align: center;
    }

    .create-btn:hover {
        background: #ff5555;
    }

    .inbox-dot {
        width: 8px;
        height: 8px;
        background: #ff4444;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    .project-color {
        width: 12px;
        height: 12px;
        background: #4ECDC4;
        border-radius: 2px;
        display: inline-block;
        margin-right: 6px;
    }

    .section-title {
        margin: 20px 0 8px;
        color: #a0a0a0;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
    }

    .plus {
        background: none;
        border: none;
        color: #fff;
        margin-left: auto;
        font-size: 16px;
        cursor: pointer;
    }

    .workspace-arrow {
        margin-left: auto;
        font-size: 12px;
    }
</style>

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
    <a href="./dummy.php" class="nav-item"><span class="icon">📊</span> Reporting</a>
    <a href="#" class="nav-item"><span class="icon">📁</span> Portfolios</a>
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
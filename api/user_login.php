<?php
// File: /api/user_login.php
session_start();
require_once '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = (new Database())->connect();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        $check = $db->prepare("SELECT id FROM projects WHERE user_id = ?");
        $check->execute([$user['id']]);

        if ($check->rowCount() > 0) {
            header("Location: ../dashboard.php");
        } else {
            header("Location: ../project.php");
        }
        exit;
    } else {
        echo "Invalid login.";
    }
}
?>
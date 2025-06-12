<?php
// File: /api/signup.php
session_start();
require_once '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $db = (new Database())->connect();

    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        echo "Email already registered.";
        exit;
    }

    $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password]);

    $_SESSION['user_id'] = $db->lastInsertId();
    $_SESSION['user_name'] = $name;
    header("Location: ../project.php");
    exit;
}
?>

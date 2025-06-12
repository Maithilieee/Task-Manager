<?php
// File: /index.php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Login / Signup</title>
  <link rel="stylesheet" href="css/index.css">
</head>
<body>
  <div class="container">
    <h2>Sign Up</h2>
    <form method="POST" action="api/signup.php">
      <input type="text" name="name" placeholder="Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Sign Up</button>
    </form>

    <h2>Login</h2>
    <form method="POST" action="api/user_login.php">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>

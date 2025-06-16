<?php
// Start session if needed
session_start();

// Include DB connection before sidebar
require_once './includes/database.php'; // adjust path if needed
$db = (new Database())->connect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    Heyy Mau
        <?php include './includes/sidebar.php'; ?>
</body>
</html>
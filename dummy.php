<?php
// Start session if needed
session_start();

// Include DB connection before sidebar
require_once './includes/database.php'; // adjust path if needed

// Create DB instance
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

    <div style=" position: absolute; top:50%;left: 50%">
        Hello World
    </div>

    <?php include './includes/sidebar.php'; ?>
</body>

</html>
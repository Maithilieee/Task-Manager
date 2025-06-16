<?php
// File: /logout.php
session_start();
session_unset();
session_destroy();

// Redirect to login/signup page
header("Location: index.php");
exit;
?>
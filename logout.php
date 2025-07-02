<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Process logout
logout();

// Redirect to login page
header('Location: login.php');
exit();
?>
<?php
require_once 'includes/session_manager.php';

$sessionManager = getSessionManager();
$sessionManager->logout();

header('Location: login_enhanced.php?message=logged_out');
exit();
?>

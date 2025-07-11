<?php
// Set session cookie parameters before starting session (moved to session_manager.php)
require_once __DIR__ . "/../includes/session_manager.php";
require_once __DIR__ . '/../config/database.php';

function register($username, $email, $password, $role = 'user') {
    global $pdo;
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        return $stmt->execute([$username, $email, $hashedPassword, $role]);
    } catch(PDOException $e) {
        return false;
    }
}

function login($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Log activity
            logUserActivity($user['id'], 'User logged in');
            return true;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

function logout() {
    if (isset($_SESSION['user_id'])) {
        logUserActivity($_SESSION['user_id'], 'User logged out');
    }
    session_destroy();
}

function logUserActivity($userId, $activity) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity, activity_time) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $activity]);
    } catch(PDOException $e) {
        // Log error silently
    }
}
?>

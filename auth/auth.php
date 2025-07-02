<?php
session_start();
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

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin($allowRegister = true) {
    if (!isLoggedIn()) {
        $currentPath = $_SERVER['PHP_SELF'];
        $basePath = dirname($_SERVER['PHP_SELF']);
        if ($basePath == '/') $basePath = '';
        
        if ($allowRegister && isset($_GET['register'])) {
            header('Location: ' . $basePath . '/register.php?redirect=' . urlencode($currentPath));
        } else {
            header('Location: ' . $basePath . '/login.php?redirect=' . urlencode($currentPath));
        }
        exit();
    }
}

function requireAdmin() {
    requireLogin(false);
    if (!isAdmin()) {
        $basePath = dirname($_SERVER['PHP_SELF']);
        if ($basePath == '/') $basePath = '';
        
        header('Location: ' . $basePath . '/index.php');
        exit();
    }
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
<?php
/**
 * Session Manager - Enhanced Session Management
 */

if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters before starting session
ini_set("session.cookie_samesite", "Lax");
ini_set("session.cookie_secure", isset($_SERVER["HTTPS"]) ? "1" : "0");
ini_set("session.cookie_httponly", "1");
session_start();
}

require_once __DIR__ . '/../config/database.php';

class SessionManager {
    private static $timeout = 3600; // 1 hour default timeout
    private static $regenerate_interval = 300; // 5 minutes
    
    public static function init() {
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > self::$regenerate_interval) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Check for session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::$timeout) {
                self::destroy();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public static function create($user_id, $username, $role, $remember_me = false) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            setcookie('remember_token', $token, $expires, '/', '', false, true);
        }
        
        return true;
    }
    
    public static function destroy() {
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        session_unset();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    public static function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public static function requireLogin() {
        if (!self::init() || !self::isLoggedIn()) {
            header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit();
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: /index.php?error=access_denied');
            exit();
        }
    }
}

// Initialize session
SessionManager::init();

// Helper functions for backward compatibility
function isLoggedIn() { return SessionManager::isLoggedIn(); }
function isAdmin() { return SessionManager::isAdmin(); }
function requireLogin() { SessionManager::requireLogin(); }
function requireAdmin() { SessionManager::requireAdmin(); }
?>

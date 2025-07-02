<?php
// Enhanced Session Manager
class SessionManager {
    private static $instance = null;
    private $sessionLifetime = 3600; // 1 hour default
    private $rememberMeLifetime = 604800; // 1 week
    
    private function __construct() {
        $this->startSecureSession();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function startSecureSession() {
        // Configure session security
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Check session timeout
        $this->checkSessionTimeout();
        
        // Check remember me cookie
        $this->checkRememberMe();
    }
    
    private function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->sessionLifetime) {
                $this->destroySession();
                return;
            }
        }
        $_SESSION['last_activity'] = time();
    }
    
    private function checkRememberMe() {
        if (!$this->isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $this->loginWithRememberToken($_COOKIE['remember_token']);
        }
    }
    
    public function login($userId, $username, $role, $rememberMe = false) {
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($rememberMe) {
            $this->setRememberMeToken($userId);
        }
        
        // Log login activity
        $this->logActivity($userId, 'User logged in', [
            'ip_address' => $_SESSION['ip_address'],
            'user_agent' => $_SESSION['user_agent']
        ]);
        
        return true;
    }
    
    private function setRememberMeToken($userId) {
        global $pdo;
        
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $expires = time() + $this->rememberMeLifetime;
        
        try {
            // Remove old tokens for this user
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Insert new token
            $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))");
            $stmt->execute([$userId, $hashedToken, $expires]);
            
            // Set cookie
            setcookie('remember_token', $token, $expires, '/', '', isset($_SERVER['HTTPS']), true);
            
        } catch(PDOException $e) {
            error_log("Remember me token error: " . $e->getMessage());
        }
    }
    
    private function loginWithRememberToken($token) {
        global $pdo;
        
        $hashedToken = hash('sha256', $token);
        
        try {
            $stmt = $pdo->prepare("
                SELECT rt.user_id, u.username, u.role 
                FROM remember_tokens rt 
                JOIN users u ON rt.user_id = u.id 
                WHERE rt.token_hash = ? AND rt.expires_at > NOW() AND u.is_active = 1
            ");
            $stmt->execute([$hashedToken]);
            $result = $stmt->fetch();
            
            if ($result) {
                $this->login($result['user_id'], $result['username'], $result['role'], true);
                return true;
            } else {
                // Invalid or expired token, remove cookie
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } catch(PDOException $e) {
            error_log("Remember me login error: " . $e->getMessage());
        }
        
        return false;
    }
    
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'User logged out');
            
            // Remove remember me token if exists
            if (isset($_COOKIE['remember_token'])) {
                $this->removeRememberToken($_COOKIE['remember_token']);
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
        
        $this->destroySession();
    }
    
    private function removeRememberToken($token) {
        global $pdo;
        
        $hashedToken = hash('sha256', $token);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
            $stmt->execute([$hashedToken]);
        } catch(PDOException $e) {
            error_log("Remove remember token error: " . $e->getMessage());
        }
    }
    
    private function destroySession() {
        session_unset();
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    public function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public function getSessionInfo() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $this->getUserId(),
            'username' => $this->getUsername(),
            'role' => $this->getRole(),
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'ip_address' => $_SESSION['ip_address'] ?? '',
            'session_lifetime_remaining' => $this->sessionLifetime - (time() - ($_SESSION['last_activity'] ?? time()))
        ];
    }
    
    private function logActivity($userId, $activity, $metadata = []) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity, activity_time, ip_address) VALUES (?, ?, NOW(), ?)");
            $stmt->execute([
                $userId, 
                $activity, 
                $metadata['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '')
            ]);
        } catch(PDOException $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
    
    public function requireLogin($redirectUrl = null) {
        if (!$this->isLoggedIn()) {
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            $loginUrl = '/login.php';
            
            if ($redirectUrl) {
                $loginUrl .= '?redirect=' . urlencode($redirectUrl);
            } elseif ($currentUrl) {
                $loginUrl .= '?redirect=' . urlencode($currentUrl);
            }
            
            header('Location: ' . $loginUrl);
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            header('Location: /index.php?error=access_denied');
            exit();
        }
    }
}

// Helper functions for backward compatibility
function getSessionManager() {
    return SessionManager::getInstance();
}

function isLoggedIn() {
    return getSessionManager()->isLoggedIn();
}

function isAdmin() {
    return getSessionManager()->isAdmin();
}

function requireLogin($redirectUrl = null) {
    return getSessionManager()->requireLogin($redirectUrl);
}

function requireAdmin() {
    return getSessionManager()->requireAdmin();
}

function getUserId() {
    return getSessionManager()->getUserId();
}

function getUsername() {
    return getSessionManager()->getUsername();
}

function getRole() {
    return getSessionManager()->getRole();
}
?>

<?php
require_once 'config/database.php';
require_once 'includes/session_manager.php';

$sessionManager = getSessionManager();

if ($sessionManager->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $sessionManager->login($user['id'], $user['username'], $user['role'], $rememberMe);
                header('Location: ' . $redirect);
                exit();
            } else {
                $error = 'Username atau password salah';
            }
        } catch(PDOException $e) {
            $error = 'Terjadi kesalahan sistem';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card login-card">
                        <div class="login-header text-center">
                            <i class="fas fa-store fa-3x mb-3"></i>
                            <h2 class="mb-0">Sistem Manajemen</h2>
                            <p class="mb-0">Silakan login untuk melanjutkan</p>
                        </div>
                        
                        <div class="card-body p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-2"></i>Username
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           placeholder="Masukkan username" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Password
                                    </label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                           placeholder="Masukkan password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                        <label class="form-check-label" for="remember_me">
                                            Ingat saya selama seminggu
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                                
                                <div class="text-center">
                                    <p class="mb-2">Belum punya akun? 
                                        <a href="register.php?redirect=<?php echo urlencode($redirect); ?>" class="text-decoration-none">Daftar disini</a>
                                    </p>
                                    <p class="mb-0">
                                        <a href="reset_password.php" class="text-decoration-none">Lupa password?</a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Demo credentials -->
                    <div class="card mt-3">
                        <div class="card-body text-center">
                            <small class="text-muted">
                                <strong>Demo Login:</strong><br>
                                Username: <code>admin</code> | Password: <code>admin123</code>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

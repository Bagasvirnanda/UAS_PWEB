<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = $success = '';
// Validasi dan sanitasi redirect URL
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
// Pastikan redirect URL adalah path internal
if (!preg_match('/^\/Bagas\/[\w\/\-\.]+\.php$/', $redirect)) {
    $redirect = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else if ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok';
    } else if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        try {
            // Cek username dan email sudah digunakan atau belum
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username atau email sudah digunakan';
            } else {
                // Register user
                if (register($username, $email, $password)) {
                    // Create notification for new user
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    $user_id = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$user_id, 'Selamat datang di sistem kami!']);

                    $success = 'Registrasi berhasil! Silakan <a href="login.php?redirect=' . urlencode($redirect) . '" class="alert-link">login</a>';
                } else {
                    $error = 'Gagal melakukan registrasi';
                }
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
    <title>Register - Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Register</h2>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="6" required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" minlength="6" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Register</button>

                            <div class="text-center">
                                <p class="mb-0">Sudah punya akun? 
                                    <a href="login.php?redirect=<?php echo urlencode($redirect); ?>" class="text-decoration-none">Login disini</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
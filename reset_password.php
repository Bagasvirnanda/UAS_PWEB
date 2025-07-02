<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

$success = $error = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);

    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Save reset token
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, created_at, expired_at) 
                                 VALUES (?, ?, NOW(), ?)");
            $stmt->execute([$user['id'], $token, $expiry]);

            // Get site settings
            $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'site_name'");
            $site_name = $stmt->fetchColumn() ?: 'Our Website';

            // Create reset link
            $reset_link = "http://{$_SERVER['HTTP_HOST']}/Bagas/reset_password.php?token=" . urlencode($token);

            // Email content
            $to = $email;
            $subject = "Reset Password - $site_name";
            $message = "Halo,\n\n";
            $message .= "Kami menerima permintaan untuk mereset password akun Anda di $site_name.\n\n";
            $message .= "Untuk mereset password Anda, silakan klik link berikut:\n";
            $message .= $reset_link . "\n\n";
            $message .= "Link ini akan kadaluarsa dalam 24 jam.\n\n";
            $message .= "Jika Anda tidak meminta reset password, Anda dapat mengabaikan email ini.\n\n";
            $message .= "Terima kasih,\n";
            $message .= "$site_name";

            $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            if (mail($to, $subject, $message, $headers)) {
                $success = 'Instruksi reset password telah dikirim ke email Anda';
            } else {
                $error = 'Gagal mengirim email reset password';
            }
        } else {
            // Don't reveal if email exists or not
            $success = 'Jika email terdaftar, instruksi reset password akan dikirim';
        }
    } catch(PDOException $e) {
        $error = 'Terjadi kesalahan sistem';
    }
}

// Handle password reset
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    try {
        // Check if token exists and not expired
        $stmt = $pdo->prepare("SELECT pr.*, u.email 
                             FROM password_resets pr 
                             JOIN users u ON pr.user_id = u.id 
                             WHERE pr.token = ? AND pr.expired_at > NOW() 
                             ORDER BY pr.created_at DESC 
                             LIMIT 1");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $error = 'Token reset password tidak valid atau sudah kadaluarsa';
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($password !== $confirm_password) {
                $error = 'Password dan konfirmasi password tidak cocok';
            } else if (strlen($password) < 6) {
                $error = 'Password harus minimal 6 karakter';
            } else {
                // Update password
                $stmt = $pdo->prepare("UPDATE users SET 
                                     password = ?, 
                                     updated_at = NOW() 
                                     WHERE id = ?");
                $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);

                // Delete used token
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);

                // Log the activity
                $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity, activity_time) 
                                     VALUES (?, 'Reset password', NOW())");
                $stmt->execute([$reset['user_id']]);

                $success = 'Password berhasil direset. Silakan login dengan password baru Anda';
            }
        }
    } catch(PDOException $e) {
        $error = 'Terjadi kesalahan sistem';
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <?php if (isset($_GET['token'])): ?>
                        <h4 class="card-title text-center mb-4">Reset Password</h4>
                    <?php else: ?>
                        <h4 class="card-title text-center mb-4">Lupa Password</h4>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php if (strpos($success, 'Silakan login') !== false): ?>
                            <div class="text-center mt-4">
                                <a href="login.php" class="btn btn-primary">Login</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success || strpos($success, 'Silakan login') === false): ?>
                        <?php if (isset($_GET['token']) && isset($reset)): ?>
                            <!-- Reset Password Form -->
                            <form method="POST" id="resetForm">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($reset['email']); ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" name="password" 
                                           minlength="6" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control" name="confirm_password" 
                                           minlength="6" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="reset_password" class="btn btn-primary">
                                        Reset Password
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Request Reset Form -->
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="request_reset" class="btn btn-primary">
                                        Kirim Link Reset Password
                                    </button>
                                </div>

                                <div class="text-center mt-4">
                                    <a href="login.php" class="text-decoration-none">Kembali ke Login</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Password dan konfirmasi password tidak cocok');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
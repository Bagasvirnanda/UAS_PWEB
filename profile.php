<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Require login
requireLogin();

$success = $error = '';
$user_id = $_SESSION['user_id'];

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data pengguna';
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    try {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Password saat ini tidak valid';
        } else {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Username atau email sudah digunakan';
            } else {
                // Update profile
                if ($new_password && $new_password === $confirm_password) {
                    // Update with new password
                    $stmt = $pdo->prepare("UPDATE users SET 
                                         username = ?, 
                                         email = ?, 
                                         password = ?, 
                                         updated_at = NOW() 
                                         WHERE id = ?");
                    $stmt->execute([
                        $username,
                        $email,
                        password_hash($new_password, PASSWORD_DEFAULT),
                        $user_id
                    ]);
                } else if ($new_password && $new_password !== $confirm_password) {
                    $error = 'Password baru dan konfirmasi password tidak cocok';
                } else {
                    // Update without password
                    $stmt = $pdo->prepare("UPDATE users SET 
                                         username = ?, 
                                         email = ?, 
                                         updated_at = NOW() 
                                         WHERE id = ?");
                    $stmt->execute([$username, $email, $user_id]);
                }

                if (!$error) {
                    // Log the activity
                    $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity, activity_time) 
                                         VALUES (?, 'Updated profile', NOW())");
                    $stmt->execute([$user_id]);

                    $success = 'Profil berhasil diperbarui';
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                }
            }
        }
    } catch(PDOException $e) {
        $error = 'Gagal memperbarui profil';
    }
}

// Get user activity logs
try {
    $stmt = $pdo->prepare("SELECT * FROM user_activity_logs 
                         WHERE user_id = ? 
                         ORDER BY activity_time DESC 
                         LIMIT 10");
    $stmt->execute([$user_id]);
    $activity_logs = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil log aktivitas';
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Profil Pengguna</h4>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="profileForm">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" name="new_password" minlength="6">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" name="confirm_password">
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Informasi Akun</h4>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role</label>
                        <p class="mb-0"><?php echo ucfirst($user['role']); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal Registrasi</label>
                        <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Terakhir Diperbarui</label>
                        <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">Aktivitas Terakhir</h4>
                    
                    <div class="list-group list-group-flush">
                        <?php foreach ($activity_logs as $log): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0"><?php echo htmlspecialchars($log['activity']); ?></p>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($log['activity_time'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($activity_logs)): ?>
                            <div class="list-group-item px-0">
                                <p class="mb-0 text-muted">Tidak ada aktivitas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

    if (newPassword && newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Password baru dan konfirmasi password tidak cocok');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
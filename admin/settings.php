<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require admin access
requireAdmin();

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update each setting
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Remove 'setting_' prefix
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                     VALUES (?, ?) 
                                     ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$setting_key, $value, $value]);
            }
        }
        $success = 'Pengaturan berhasil disimpan';

        // Log the settings update
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, row_id, log_time) 
                             VALUES (?, 'update', 'system_settings', 0, NOW())");
        $stmt->execute([$user_id]);

    } catch(PDOException $e) {
        $error = 'Gagal menyimpan pengaturan';
    }
}

// Get current settings
try {
    $stmt = $pdo->query("SELECT * FROM system_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch(PDOException $e) {
    $error = 'Gagal mengambil pengaturan';
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Pengaturan Sistem</h2>
    </div>

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

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <!-- General Settings -->
                <h5 class="mb-4">Pengaturan Umum</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nama Situs</label>
                            <input type="text" class="form-control" name="setting_site_name" 
                                   value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Situs</label>
                            <textarea class="form-control" name="setting_site_description" rows="3"><?php 
                                echo htmlspecialchars($settings['site_description'] ?? ''); 
                            ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email Admin</label>
                            <input type="email" class="form-control" name="setting_admin_email" 
                                   value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" name="setting_phone_number" 
                                   value="<?php echo htmlspecialchars($settings['phone_number'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <h5 class="mb-4">Pengaturan Sistem</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Item per Halaman</label>
                            <input type="number" class="form-control" name="setting_items_per_page" 
                                   value="<?php echo htmlspecialchars($settings['items_per_page'] ?? '10'); ?>" 
                                   min="5" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batas Minimum Stok</label>
                            <input type="number" class="form-control" name="setting_min_stock_alert" 
                                   value="<?php echo htmlspecialchars($settings['min_stock_alert'] ?? '10'); ?>" 
                                   min="1" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Mode Maintenance</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="setting_maintenance_mode" value="true" 
                                       <?php echo ($settings['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Aktifkan mode maintenance</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Format Tanggal</label>
                            <select class="form-select" name="setting_date_format">
                                <option value="d/m/Y" <?php echo ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                <option value="Y-m-d" <?php echo ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                <option value="d-m-Y" <?php echo ($settings['date_format'] ?? '') === 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <h5 class="mb-4">Pengaturan Notifikasi</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="setting_email_notifications" value="true" 
                                       <?php echo ($settings['email_notifications'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Aktifkan notifikasi email</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="setting_stock_alerts" value="true" 
                                       <?php echo ($settings['stock_alerts'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Aktifkan peringatan stok menipis</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Frekuensi Notifikasi</label>
                            <select class="form-select" name="setting_notification_frequency">
                                <option value="realtime" <?php echo ($settings['notification_frequency'] ?? '') === 'realtime' ? 'selected' : ''; ?>>Real-time</option>
                                <option value="daily" <?php echo ($settings['notification_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>Harian</option>
                                <option value="weekly" <?php echo ($settings['notification_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Mingguan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Backup Settings -->
                <h5 class="mb-4">Pengaturan Backup</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="setting_auto_backup" value="true" 
                                       <?php echo ($settings['auto_backup'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Aktifkan backup otomatis</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Frekuensi Backup</label>
                            <select class="form-select" name="setting_backup_frequency">
                                <option value="daily" <?php echo ($settings['backup_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>Harian</option>
                                <option value="weekly" <?php echo ($settings['backup_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Mingguan</option>
                                <option value="monthly" <?php echo ($settings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Bulanan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
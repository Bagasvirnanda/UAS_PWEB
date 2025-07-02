<?php
require_once 'config/database.php';
require_once 'includes/session_manager.php';

// Require admin access for creating system-wide notifications
requireAdmin();

$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $type = $_POST['type'] ?? 'info';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $action_url = trim($_POST['action_url'] ?? '');
    $expires_at = $_POST['expires_at'] ?? null;
    
    if (empty($title) || empty($message)) {
        $error = 'Judul dan pesan harus diisi';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, action_url, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user_id ?: null, 
                    $type, 
                    $title, 
                    $message, 
                    $action_url ?: null, 
                    $expires_at ?: null
                ]);
                $success = 'Notifikasi berhasil dibuat';
                $action = 'list';
            } elseif ($action === 'edit' && $id) {
                $stmt = $pdo->prepare("UPDATE notifications SET user_id = ?, type = ?, title = ?, message = ?, action_url = ?, expires_at = ? WHERE id = ?");
                $stmt->execute([
                    $user_id ?: null, 
                    $type, 
                    $title, 
                    $message, 
                    $action_url ?: null, 
                    $expires_at ?: null,
                    $id
                ]);
                $success = 'Notifikasi berhasil diupdate';
                $action = 'list';
            }
        } catch(PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Notifikasi berhasil dihapus';
        $action = 'list';
    } catch(PDOException $e) {
        $error = 'Tidak dapat menghapus notifikasi: ' . $e->getMessage();
    }
}

// Handle mark as read
if ($action === 'mark_read' && $id) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Notifikasi ditandai sebagai dibaca';
        $action = 'list';
    } catch(PDOException $e) {
        $error = 'Gagal menandai notifikasi: ' . $e->getMessage();
    }
}

// Get notification data for edit
$notification = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->execute([$id]);
    $notification = $stmt->fetch();
    if (!$notification) {
        $error = 'Notifikasi tidak ditemukan';
        $action = 'list';
    }
}

// Get users for dropdown
$stmt = $pdo->query("SELECT id, username, full_name FROM users WHERE is_active = 1 ORDER BY username");
$users = $stmt->fetchAll();

// Get notifications list
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $read_filter = $_GET['read'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search) {
        $whereClause .= " AND (title LIKE ? OR message LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($type_filter) {
        $whereClause .= " AND type = ?";
        $params[] = $type_filter;
    }
    
    if ($read_filter !== '') {
        $whereClause .= " AND is_read = ?";
        $params[] = $read_filter;
    }
    
    $stmt = $pdo->prepare("
        SELECT n.*, u.username 
        FROM notifications n 
        LEFT JOIN users u ON n.user_id = u.id 
        $whereClause 
        ORDER BY n.created_at DESC
    ");
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Notifikasi - Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>Sistem Manajemen
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="products.php">Produk</a>
                <a class="nav-link" href="suppliers.php">Supplier</a>
                <a class="nav-link" href="purchase_orders.php">Purchase Orders</a>
                <a class="nav-link" href="report_management.php">Laporan</a>
                <a class="nav-link active" href="notification_management.php">Notifikasi</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Notifications List -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-bell me-2"></i>Manajemen Notifikasi</h2>
                <a href="?action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Buat Notifikasi
                </a>
            </div>

            <!-- Search and Filter -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control" placeholder="Cari notifikasi..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <select name="type" class="form-select ms-2">
                            <option value="">Semua Tipe</option>
                            <option value="info" <?php echo ($_GET['type'] ?? '') === 'info' ? 'selected' : ''; ?>>Info</option>
                            <option value="warning" <?php echo ($_GET['type'] ?? '') === 'warning' ? 'selected' : ''; ?>>Warning</option>
                            <option value="error" <?php echo ($_GET['type'] ?? '') === 'error' ? 'selected' : ''; ?>>Error</option>
                            <option value="success" <?php echo ($_GET['type'] ?? '') === 'success' ? 'selected' : ''; ?>>Success</option>
                        </select>
                        <select name="read" class="form-select ms-2">
                            <option value="">Semua Status</option>
                            <option value="0" <?php echo ($_GET['read'] ?? '') === '0' ? 'selected' : ''; ?>>Belum Dibaca</option>
                            <option value="1" <?php echo ($_GET['read'] ?? '') === '1' ? 'selected' : ''; ?>>Sudah Dibaca</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Notifications Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Tipe</th>
                                    <th>Target User</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th>Expires</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notifications as $notification): ?>
                                    <tr class="<?php echo !$notification['is_read'] ? 'table-warning' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($notification['message'], 0, 100)) . (strlen($notification['message']) > 100 ? '...' : ''); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = [
                                                'info' => 'bg-info',
                                                'warning' => 'bg-warning text-dark',
                                                'error' => 'bg-danger',
                                                'success' => 'bg-success'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $badgeClass[$notification['type']]; ?>">
                                                <?php echo ucfirst($notification['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($notification['user_id']): ?>
                                                <?php echo htmlspecialchars($notification['username']); ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">System-wide</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $notification['is_read'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <?php echo $notification['is_read'] ? 'Dibaca' : 'Belum Dibaca'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></td>
                                        <td><?php echo $notification['expires_at'] ? date('d/m/Y H:i', strtotime($notification['expires_at'])) : '-'; ?></td>
                                        <td>
                                            <?php if (!$notification['is_read']): ?>
                                                <a href="?action=mark_read&id=<?php echo $notification['id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Mark as Read">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=edit&id=<?php echo $notification['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $notification['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" title="Delete"
                                               onclick="return confirm('Yakin ingin menghapus notifikasi ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <!-- Create/Edit Form -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <?php echo $action === 'create' ? 'Buat Notifikasi' : 'Edit Notifikasi'; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="user_id" class="form-label">Target User</label>
                                            <select class="form-select" id="user_id" name="user_id">
                                                <option value="">System-wide (Semua User)</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>" 
                                                            <?php echo ($notification && $notification['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['username']) . ($user['full_name'] ? ' (' . htmlspecialchars($user['full_name']) . ')' : ''); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="type" class="form-label">Tipe Notifikasi</label>
                                            <select class="form-select" id="type" name="type">
                                                <option value="info" <?php echo ($notification && $notification['type'] === 'info') ? 'selected' : ''; ?>>Info</option>
                                                <option value="warning" <?php echo ($notification && $notification['type'] === 'warning') ? 'selected' : ''; ?>>Warning</option>
                                                <option value="error" <?php echo ($notification && $notification['type'] === 'error') ? 'selected' : ''; ?>>Error</option>
                                                <option value="success" <?php echo ($notification && $notification['type'] === 'success') ? 'selected' : ''; ?>>Success</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Judul *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo $notification ? htmlspecialchars($notification['title']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Pesan *</label>
                                    <textarea class="form-control" id="message" name="message" rows="4" required><?php echo $notification ? htmlspecialchars($notification['message']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="action_url" class="form-label">Action URL (Opsional)</label>
                                    <input type="url" class="form-control" id="action_url" name="action_url" 
                                           value="<?php echo $notification ? htmlspecialchars($notification['action_url']) : ''; ?>"
                                           placeholder="https://example.com/action">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="expires_at" class="form-label">Tanggal Kadaluarsa (Opsional)</label>
                                    <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" 
                                           value="<?php echo $notification && $notification['expires_at'] ? date('Y-m-d\TH:i', strtotime($notification['expires_at'])) : ''; ?>">
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?action=list" class="btn btn-secondary">Kembali</a>
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $action === 'create' ? 'Buat' : 'Update'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require admin access
requireAdmin();

// Get log type from query parameter (default to user_activity)
$log_type = isset($_GET['type']) && $_GET['type'] === 'audit' ? 'audit' : 'user_activity';

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($settings['items_per_page']) ? intval($settings['items_per_page']) : 10;
$offset = ($page - 1) * $limit;

// Build query conditions
$conditions = [];
$params = [];

if ($date_from) {
    if ($log_type === 'audit') {
        $conditions[] = "log_time >= ?"; 
    } else {
        $conditions[] = "activity_time >= ?";
    }
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to) {
    if ($log_type === 'audit') {
        $conditions[] = "log_time <= ?";
    } else {
        $conditions[] = "activity_time <= ?";
    }
    $params[] = $date_to . ' 23:59:59';
}

if ($user_id > 0) {
    $conditions[] = "user_id = ?";
    $params[] = $user_id;
}

if ($search) {
    if ($log_type === 'audit') {
        $conditions[] = "(action LIKE ? OR table_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    } else {
        $conditions[] = "activity LIKE ?";
        $params[] = "%$search%";
    }
}

$where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get users for filter dropdown
try {
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data pengguna';
}

// Get total logs count
try {
    if ($log_type === 'audit') {
        $count_sql = "SELECT COUNT(*) FROM audit_logs $where_clause";
    } else {
        $count_sql = "SELECT COUNT(*) FROM user_activity_logs $where_clause";
    }
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_logs = $stmt->fetchColumn();
    $total_pages = ceil($total_logs / $limit);
} catch(PDOException $e) {
    $error = 'Gagal mengambil jumlah log';
}

// Get logs
try {
    if ($log_type === 'audit') {
        $sql = "SELECT al.*, u.username 
               FROM audit_logs al 
               LEFT JOIN users u ON al.user_id = u.id 
               $where_clause 
               ORDER BY log_time DESC 
               LIMIT ? OFFSET ?";
    } else {
        $sql = "SELECT ual.*, u.username 
               FROM user_activity_logs ual 
               LEFT JOIN users u ON ual.user_id = u.id 
               $where_clause 
               ORDER BY activity_time DESC 
               LIMIT ? OFFSET ?";
    }
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data log';
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Log Sistem</h2>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $log_type === 'user_activity' ? 'active' : ''; ?>" 
                       href="?type=user_activity">Log Aktivitas Pengguna</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $log_type === 'audit' ? 'active' : ''; ?>" 
                       href="?type=audit">Log Audit</a>
                </li>
            </ul>

            <form method="GET" class="row g-3">
                <input type="hidden" name="type" value="<?php echo $log_type; ?>">
                
                <div class="col-md-2">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Pengguna</label>
                    <select class="form-select" name="user_id">
                        <option value="">Semua Pengguna</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                    <?php echo $user_id === $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Pencarian</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Cari...">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <?php if ($log_type === 'audit'): ?>
                                <th>Aksi</th>
                                <th>Tabel</th>
                                <th>ID Baris</th>
                            <?php else: ?>
                                <th>Aktivitas</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?php 
                                        $timestamp = $log_type === 'audit' ? $log['log_time'] : $log['activity_time'];
                                        echo date('d/m/Y H:i:s', strtotime($timestamp)); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                <?php if ($log_type === 'audit'): ?>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                                    <td><?php echo htmlspecialchars($log['row_id']); ?></td>
                                <?php else: ?>
                                    <td><?php echo htmlspecialchars($log['activity']); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="<?php echo $log_type === 'audit' ? '5' : '3'; ?>" class="text-center">
                                    Tidak ada log
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php 
                                    $_GET['page'] = $page - 1;
                                    echo http_build_query($_GET);
                                ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php 
                                    $_GET['page'] = $i;
                                    echo http_build_query($_GET);
                                ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php 
                                    $_GET['page'] = $page + 1;
                                    echo http_build_query($_GET);
                                ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php
require_once 'config/database.php';
require_once 'includes/session_manager.php';

// Require login
requireLogin();

$sessionManager = getSessionManager();
$sessionInfo = $sessionManager->getSessionInfo();

// Get dashboard statistics
try {
    // User specific stats
    $user_id = getUserId();
    $is_admin = isAdmin();
    
    // Basic stats
    $stats = [];
    
    if ($is_admin) {
        // Admin dashboard stats
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $stats['total_users'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
        $stats['total_products'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM suppliers WHERE is_active = 1");
        $stats['total_suppliers'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM purchase_orders");
        $stats['total_purchase_orders'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM reports");
        $stats['total_reports'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
        $stats['unread_notifications'] = $stmt->fetch()['count'];
        
        // Recent activities (for admin)
        $stmt = $pdo->prepare("SELECT ual.*, u.username FROM user_activity_logs ual JOIN users u ON ual.user_id = u.id ORDER BY ual.activity_time DESC LIMIT 5");
        $stmt->execute();
        $recent_activities = $stmt->fetchAll();
        
        // Recent purchase orders
        $stmt = $pdo->prepare("SELECT po.*, s.name as supplier_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id ORDER BY po.created_at DESC LIMIT 5");
        $stmt->execute();
        $recent_purchase_orders = $stmt->fetchAll();
        
    } else {
        // User dashboard stats
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['my_transactions'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['cart_items'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['my_reports'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
        $stmt->execute([$user_id]);
        $stats['unread_notifications'] = $stmt->fetch()['count'];
        
        // Recent transactions
        $stmt = $pdo->prepare("SELECT t.*, COUNT(ti.id) as total_items FROM transactions t LEFT JOIN transaction_items ti ON t.id = ti.transaction_id WHERE t.user_id = ? GROUP BY t.id ORDER BY t.created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $recent_transactions = $stmt->fetchAll();
    }
    
    // System notifications for current user
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0 ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Terjadi kesalahan dalam mengambil data dashboard';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .activity-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 10px;
        }
        .session-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>Sistem Manajemen
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav me-auto">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-home me-1"></i>Dashboard
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box me-1"></i>Produk
                    </a>
                    <?php if ($is_admin): ?>
                        <a class="nav-link" href="suppliers.php">
                            <i class="fas fa-truck me-1"></i>Supplier
                        </a>
                        <a class="nav-link" href="purchase_orders.php">
                            <i class="fas fa-shopping-cart me-1"></i>Purchase Orders
                        </a>
                        <a class="nav-link" href="notification_management.php">
                            <i class="fas fa-bell me-1"></i>Notifikasi
                        </a>
                    <?php endif; ?>
                    <a class="nav-link" href="report_management.php">
                        <i class="fas fa-chart-line me-1"></i>Laporan
                    </a>
                </div>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars(getUsername()); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout_enhanced.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-6">Selamat Datang, <?php echo htmlspecialchars(getUsername()); ?>!</h1>
                <p class="lead">Dashboard sistem manajemen untuk <?php echo $is_admin ? 'administrator' : 'user'; ?></p>
            </div>
            <div class="col-md-4">
                <div class="session-info">
                    <h6><i class="fas fa-clock me-2"></i>Informasi Sesi</h6>
                    <small>
                        Login: <?php echo date('d/m/Y H:i', $sessionInfo['login_time']); ?><br>
                        IP: <?php echo htmlspecialchars($sessionInfo['ip_address']); ?><br>
                        Sesi berlaku: <?php echo gmdate('H:i:s', $sessionInfo['session_lifetime_remaining']); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <?php if (!empty($notifications)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h5><i class="fas fa-bell me-2"></i>Notifikasi Terbaru</h5>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="alert alert-<?php echo $notification['type'] === 'error' ? 'danger' : $notification['type']; ?> alert-dismissible">
                            <strong><?php echo htmlspecialchars($notification['title']); ?></strong><br>
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <?php if ($notification['action_url']): ?>
                                <br><a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="alert-link">Lihat Detail</a>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php if ($is_admin): ?>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h4><?php echo $stats['total_users']; ?></h4>
                            <p class="text-muted">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-box fa-3x text-success mb-3"></i>
                            <h4><?php echo $stats['total_products']; ?></h4>
                            <p class="text-muted">Total Produk</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-truck fa-3x text-warning mb-3"></i>
                            <h4><?php echo $stats['total_suppliers']; ?></h4>
                            <p class="text-muted">Total Supplier</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-3x text-info mb-3"></i>
                            <h4><?php echo $stats['total_purchase_orders']; ?></h4>
                            <p class="text-muted">Purchase Orders</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-receipt fa-3x text-primary mb-3"></i>
                            <h4><?php echo $stats['my_transactions']; ?></h4>
                            <p class="text-muted">Transaksi Saya</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-basket fa-3x text-success mb-3"></i>
                            <h4><?php echo $stats['cart_items']; ?></h4>
                            <p class="text-muted">Item di Keranjang</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-3x text-warning mb-3"></i>
                            <h4><?php echo $stats['my_reports']; ?></h4>
                            <p class="text-muted">Laporan Saya</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-bell fa-3x text-info mb-3"></i>
                            <h4><?php echo $stats['unread_notifications']; ?></h4>
                            <p class="text-muted">Notifikasi Baru</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <?php if ($is_admin): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history me-2"></i>Aktivitas Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_activities)): ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <strong><?php echo htmlspecialchars($activity['username']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($activity['activity']); ?></small><br>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($activity['activity_time'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Belum ada aktivitas</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-shopping-cart me-2"></i>Purchase Orders Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_purchase_orders)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Order#</th>
                                                <th>Supplier</th>
                                                <th>Status</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_purchase_orders as $po): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($po['order_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                                                    <td><span class="badge bg-primary"><?php echo ucfirst($po['status']); ?></span></td>
                                                    <td><?php echo date('d/m/Y', strtotime($po['order_date'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Belum ada purchase orders</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-receipt me-2"></i>Transaksi Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_transactions)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Total</th>
                                                <th>Items</th>
                                                <th>Status</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_transactions as $transaction): ?>
                                                <tr>
                                                    <td>#<?php echo $transaction['id']; ?></td>
                                                    <td>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                                                    <td><?php echo $transaction['total_items']; ?> item(s)</td>
                                                    <td><span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($transaction['status']); ?></span></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Belum ada transaksi</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

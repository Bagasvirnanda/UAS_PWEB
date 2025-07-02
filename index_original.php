<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Require login for this page
requireLogin();

// Check if database connection is available
if (isset($db_connection_error) && $db_connection_error) {
    include 'includes/header.php';
    echo "<div class='container-fluid py-4'>
            <div class='alert alert-danger' role='alert'>
                <h4 class='alert-heading'>Kesalahan Koneksi Database!</h4>
                <p>$db_connection_error</p>
                <hr>
                <p class='mb-0'>Pastikan server MySQL/XAMPP sudah berjalan dan database telah dibuat dengan benar.</p>
                <p class='mt-2'>Aplikasi akan menampilkan data default sementara. Beberapa fitur mungkin tidak berfungsi dengan baik.</p>
            </div>
          </div>";
    
    // Set a flag to show warning on dashboard
    $db_warning = true;
}

// Initialize variables
$totalUsers = $totalProducts = 0;
$todayTransactions = ['total' => 0, 'amount' => 0];
$recentActivities = $lowStockProducts = [];
$error = '';

// Get dashboard statistics
try {
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $totalUsers = $stmt->fetch()['total'];

    // Total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
    $stmt->execute();
    $totalProducts = $stmt->fetch()['total'];

    // Total transactions today
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount 
                        FROM transactions 
                        WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $todayTransactions = $stmt->fetch();

    // Recent activities
    $stmt = $pdo->prepare("SELECT l.activity, l.activity_time, u.username 
                        FROM user_activity_logs l 
                        JOIN users u ON l.user_id = u.id 
                        ORDER BY l.activity_time DESC LIMIT 5");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll();

    // Low stock products
    $stmt = $pdo->prepare("SELECT name, stock FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");
    $stmt->execute();
    $lowStockProducts = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Terjadi kesalahan saat mengambil data dashboard.";
}

include 'includes/header.php';

// Display error message if any
if ($error) {
    echo "<div class='alert alert-danger' role='alert'>$error</div>";
}

// Display warning if using default data
if (isset($db_warning) && $db_warning) {
    echo "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong>Perhatian!</strong> Menampilkan data default karena database tidak terhubung.
            <p class='mb-0 mt-2'>Untuk mengatasi masalah ini:</p>
            <ol class='mb-0'>
                <li>Buka XAMPP Control Panel dan start Apache & MySQL</li>
                <li>Pastikan database 'bagas_db' sudah dibuat</li>
                <li>Refresh halaman ini</li>
            </ol>
            <div class='mt-3'>
                <button type='button' class='btn btn-sm btn-primary' onclick='window.location.reload();'>Refresh Halaman</button>
                <button type='button' class='btn btn-sm btn-secondary' onclick='openXamppControlPanel()'>Buka XAMPP Control Panel</button>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
    
    // Add JavaScript to open XAMPP Control Panel
    echo "<script>
            function openXamppControlPanel() {
                // Try to open XAMPP Control Panel using JavaScript
                try {
                    // Create ActiveX object for Windows Script Host
                    var shell = new ActiveXObject('WScript.Shell');
                    shell.Run('D:\\Programs\\Xampp\\xampp-control.exe');
                } catch (e) {
                    alert('Tidak dapat membuka XAMPP Control Panel secara otomatis. Silakan buka secara manual di D:\\Programs\\Xampp\\xampp-control.exe');
                }
            }
          </script>";
}
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Statistics Cards -->
        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title mb-0">Total Pengguna</h5>
                            <p class="display-6 mb-0"><?php echo number_format($totalUsers); ?></p>
                        </div>
                        <div class="col-auto">
                            <div class="stat text-primary">
                                <i class="bi bi-people" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title mb-0">Total Produk</h5>
                            <p class="display-6 mb-0"><?php echo number_format($totalProducts); ?></p>
                        </div>
                        <div class="col-auto">
                            <div class="stat text-success">
                                <i class="bi bi-box" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title mb-0">Transaksi Hari Ini</h5>
                            <p class="display-6 mb-0"><?php echo number_format($todayTransactions['total']); ?></p>
                        </div>
                        <div class="col-auto">
                            <div class="stat text-info">
                                <i class="bi bi-cart" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title mb-0">Pendapatan Hari Ini</h5>
                            <p class="display-6 mb-0">Rp <?php echo number_format($todayTransactions['amount']); ?></p>
                        </div>
                        <div class="col-auto">
                            <div class="stat text-warning">
                                <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Recent Activities -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aktivitas Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach($recentActivities as $activity): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['username']); ?></h6>
                                <small><?php echo date('d/m/Y H:i', strtotime($activity['activity_time'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($activity['activity']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stok Menipis</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($lowStockProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td>
                                        <?php if($product['stock'] == 0): ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Menipis</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
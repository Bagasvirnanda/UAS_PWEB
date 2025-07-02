<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require admin access
requireAdmin();

$success = $error = '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'sales';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today

try {
    switch($report_type) {
        case 'sales':
            // Daily sales for date range
            $stmt = $pdo->prepare("SELECT 
                                    DATE(transaction_date) as date,
                                    COUNT(*) as total_transactions,
                                    SUM(total_amount) as total_sales
                                 FROM transactions
                                 WHERE status = 'completed'
                                 AND DATE(transaction_date) BETWEEN ? AND ?
                                 GROUP BY DATE(transaction_date)
                                 ORDER BY date");
            $stmt->execute([$date_from, $date_to]);
            $sales_data = $stmt->fetchAll();

            // Calculate summary
            $total_sales = array_sum(array_column($sales_data, 'total_sales'));
            $total_transactions = array_sum(array_column($sales_data, 'total_transactions'));
            break;

        case 'products':
            // Top selling products
            $stmt = $pdo->prepare("SELECT 
                                    p.name,
                                    SUM(ti.quantity) as total_quantity,
                                    SUM(ti.quantity * ti.price) as total_revenue
                                 FROM transaction_items ti
                                 JOIN products p ON ti.product_id = p.id
                                 JOIN transactions t ON ti.transaction_id = t.id
                                 WHERE t.status = 'completed'
                                 AND DATE(t.transaction_date) BETWEEN ? AND ?
                                 GROUP BY p.id, p.name
                                 ORDER BY total_quantity DESC
                                 LIMIT 10");
            $stmt->execute([$date_from, $date_to]);
            $product_data = $stmt->fetchAll();
            break;

        case 'categories':
            // Sales by category
            $stmt = $pdo->prepare("SELECT 
                                    c.name,
                                    COUNT(DISTINCT t.id) as total_transactions,
                                    SUM(ti.quantity) as total_quantity,
                                    SUM(ti.quantity * ti.price) as total_revenue
                                 FROM categories c
                                 LEFT JOIN products p ON c.id = p.category_id
                                 LEFT JOIN transaction_items ti ON p.id = ti.product_id
                                 LEFT JOIN transactions t ON ti.transaction_id = t.id AND t.status = 'completed'
                                 WHERE DATE(t.transaction_date) BETWEEN ? AND ?
                                 GROUP BY c.id, c.name
                                 ORDER BY total_revenue DESC");
            $stmt->execute([$date_from, $date_to]);
            $category_data = $stmt->fetchAll();
            break;

        case 'inventory':
            // Low stock products
            $stmt = $pdo->query("SELECT 
                                p.name,
                                p.stock,
                                c.name as category_name
                             FROM products p
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE p.stock < 10
                             ORDER BY p.stock ASC");
            $inventory_data = $stmt->fetchAll();
            break;
    }
} catch(PDOException $e) {
    $error = 'Gagal mengambil data laporan';
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Laporan</h2>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Report Type Selection and Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" name="type" onchange="this.form.submit()">
                        <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Laporan Penjualan</option>
                        <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>Produk Terlaris</option>
                        <option value="categories" <?php echo $report_type === 'categories' ? 'selected' : ''; ?>>Penjualan per Kategori</option>
                        <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Stok Menipis</option>
                    </select>
                </div>
                <?php if ($report_type !== 'inventory'): ?>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="card">
        <div class="card-body">
            <?php if ($report_type === 'sales'): ?>
                <h3 class="card-title mb-4">Laporan Penjualan</h3>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Penjualan</h5>
                                <p class="display-6">Rp <?php echo number_format($total_sales, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Transaksi</h5>
                                <p class="display-6"><?php echo number_format($total_transactions); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah Transaksi</th>
                                <th>Total Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($sales_data as $data): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($data['date'])); ?></td>
                                <td><?php echo number_format($data['total_transactions']); ?></td>
                                <td>Rp <?php echo number_format($data['total_sales'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($report_type === 'products'): ?>
                <h3 class="card-title mb-4">Produk Terlaris</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Jumlah Terjual</th>
                                <th>Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($product_data as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['name']); ?></td>
                                <td><?php echo number_format($data['total_quantity']); ?></td>
                                <td>Rp <?php echo number_format($data['total_revenue'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($report_type === 'categories'): ?>
                <h3 class="card-title mb-4">Penjualan per Kategori</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Jumlah Transaksi</th>
                                <th>Total Item Terjual</th>
                                <th>Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($category_data as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['name']); ?></td>
                                <td><?php echo number_format($data['total_transactions']); ?></td>
                                <td><?php echo number_format($data['total_quantity']); ?></td>
                                <td>Rp <?php echo number_format($data['total_revenue'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($report_type === 'inventory'): ?>
                <h3 class="card-title mb-4">Produk dengan Stok Menipis</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Sisa Stok</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($inventory_data as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['name']); ?></td>
                                <td><?php echo htmlspecialchars($data['category_name']); ?></td>
                                <td><?php echo $data['stock']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $data['stock'] === 0 ? 'danger' : 'warning'; ?>">
                                        <?php echo $data['stock'] === 0 ? 'Habis' : 'Menipis'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    .sidebar, .navbar, .btn, form, .modal { display: none !important; }
    .container-fluid { width: 100% !important; padding: 0 !important; margin: 0 !important; }
    .card { border: none !important; }
    .card-body { padding: 0 !important; }
    @page { margin: 0.5cm; }
</style>

<?php include '../includes/footer.php'; ?>
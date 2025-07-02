<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Require login
requireLogin();

$error = '';
$user_id = $_SESSION['user_id'];

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today

// Get user's transaction summary
try {
    // Total transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_transactions, 
                                 COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
                                 COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
                                 COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_transactions,
                                 SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_spent
                         FROM transactions 
                         WHERE user_id = ? 
                         AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$user_id, $date_from, $date_to]);
    $summary = $stmt->fetch();

    // Recent transactions
    $stmt = $pdo->prepare("SELECT t.*, 
                                 COUNT(ti.id) as total_items,
                                 GROUP_CONCAT(CONCAT(p.name, ' (', ti.quantity, ')') SEPARATOR ', ') as items
                         FROM transactions t
                         JOIN transaction_items ti ON t.id = ti.transaction_id
                         JOIN products p ON ti.product_id = p.id
                         WHERE t.user_id = ? 
                         AND DATE(t.created_at) BETWEEN ? AND ?
                         GROUP BY t.id
                         ORDER BY t.created_at DESC
                         LIMIT 5");
    $stmt->execute([$user_id, $date_from, $date_to]);
    $recent_transactions = $stmt->fetchAll();

    // Monthly spending chart data
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                                 SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total
                         FROM transactions
                         WHERE user_id = ?
                         AND DATE(created_at) BETWEEN DATE_SUB(?, INTERVAL 6 MONTH) AND ?
                         GROUP BY month
                         ORDER BY month");
    $stmt->execute([$user_id, $date_to, $date_to]);
    $monthly_spending = $stmt->fetchAll();

    // Most purchased products
    $stmt = $pdo->prepare("SELECT p.name, 
                                 SUM(ti.quantity) as total_quantity,
                                 SUM(ti.quantity * ti.price) as total_amount
                         FROM transaction_items ti
                         JOIN transactions t ON ti.transaction_id = t.id
                         JOIN products p ON ti.product_id = p.id
                         WHERE t.user_id = ? 
                         AND t.status = 'completed'
                         AND DATE(t.created_at) BETWEEN ? AND ?
                         GROUP BY p.id
                         ORDER BY total_quantity DESC
                         LIMIT 5");
    $stmt->execute([$user_id, $date_from, $date_to]);
    $top_products = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data laporan';
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Laporan Transaksi</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="date_from" 
                           value="<?php echo $date_from; ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="date_to" 
                           value="<?php echo $date_to; ?>" required>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Transaksi</h6>
                    <h3 class="mb-0"><?php echo $summary['total_transactions']; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Transaksi Selesai</h6>
                    <h3 class="mb-0"><?php echo $summary['completed_transactions']; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Transaksi Pending</h6>
                    <h3 class="mb-0"><?php echo $summary['pending_transactions']; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Pengeluaran</h6>
                    <h3 class="mb-0">Rp <?php echo number_format($summary['total_spent'], 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Grafik Pengeluaran Bulanan</h5>
                    <canvas id="spendingChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Produk Terbanyak Dibeli</h5>
                    <div class="list-group list-group-flush">
                        <?php foreach ($top_products as $product): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo $product['total_quantity']; ?> item(s) | 
                                            Rp <?php echo number_format($product['total_amount'], 0, ',', '.'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($top_products)): ?>
                            <div class="text-center text-muted py-3">
                                Tidak ada data
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-4">Transaksi Terakhir</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Tanggal</th>
                            <th>Item</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <tr>
                                <td>#<?php echo str_pad($transaction['id'], 8, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td>
                                    <small><?php echo htmlspecialchars($transaction['items']); ?></small>
                                </td>
                                <td>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo match($transaction['status']) {
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-warning'
                                        };
                                    ?>">
                                        <?php 
                                            echo match($transaction['status']) {
                                                'completed' => 'Selesai',
                                                'cancelled' => 'Dibatalkan',
                                                default => 'Pending'
                                            };
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada transaksi</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Prepare chart data
const months = <?php echo json_encode(array_column($monthly_spending, 'month')); ?>;
const totals = <?php echo json_encode(array_column($monthly_spending, 'total')); ?>;

// Create spending chart
const ctx = document.getElementById('spendingChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: months.map(month => {
            const [year, m] = month.split('-');
            return new Date(year, m - 1).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Total Pengeluaran',
            data: totals,
            borderColor: '#0d6efd',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                    }
                }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
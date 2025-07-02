<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Require login
requireLogin();

$success = $error = '';
$user_id = $_SESSION['user_id'];

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($settings['items_per_page']) ? intval($settings['items_per_page']) : 10;
$offset = ($page - 1) * $limit;

// Build query conditions
$conditions = ["user_id = ?"];
$params = [$user_id];

if ($status) {
    $conditions[] = "status = ?";
    $params[] = $status;
}

if ($date_from) {
    $conditions[] = "DATE(transaction_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $conditions[] = "DATE(transaction_date) <= ?";
    $params[] = $date_to;
}

$where_clause = 'WHERE ' . implode(' AND ', $conditions);

// Get total transactions count
try {
    $sql = "SELECT COUNT(*) FROM transactions $where_clause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total_transactions = $stmt->fetchColumn();
    $total_pages = ceil($total_transactions / $limit);
} catch(PDOException $e) {
    $error = 'Gagal mengambil jumlah transaksi';
}

// Get transactions
try {
    $sql = "SELECT * FROM transactions 
           $where_clause 
           ORDER BY transaction_date DESC 
           LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data transaksi';
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Riwayat Transaksi</h2>
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

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                </div>

                <div class="col-md-3 d-flex align-items-end">
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
                            <th>ID Transaksi</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>#<?php echo str_pad($transaction['id'], 8, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?></td>
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
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?php echo $transaction['id']; ?>">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>

                            <!-- Detail Modal -->
                            <div class="modal fade" id="detailModal<?php echo $transaction['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detail Transaksi #<?php echo str_pad($transaction['id'], 8, '0', STR_PAD_LEFT); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php
                                            try {
                                                // Get transaction items
                                                $stmt = $pdo->prepare("SELECT ti.*, p.name as product_name 
                                                                     FROM transaction_items ti 
                                                                     JOIN products p ON ti.product_id = p.id 
                                                                     WHERE ti.transaction_id = ?");
                                                $stmt->execute([$transaction['id']]);
                                                $items = $stmt->fetchAll();
                                            } catch(PDOException $e) {
                                                echo '<div class="alert alert-danger">Gagal mengambil detail transaksi</div>';
                                            }
                                            ?>

                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Produk</th>
                                                            <th>Harga</th>
                                                            <th>Jumlah</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($items as $item): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                                <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                                                <td><?php echo $item['quantity']; ?></td>
                                                                <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <tr class="table-light">
                                                            <td colspan="3" class="text-end fw-bold">Total:</td>
                                                            <td class="fw-bold">Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="mt-3">
                                                <p class="mb-1">
                                                    <strong>Status:</strong>
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
                                                </p>
                                                <p class="mb-0">
                                                    <strong>Tanggal Transaksi:</strong>
                                                    <?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada transaksi</td>
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

<?php include 'includes/footer.php'; ?>
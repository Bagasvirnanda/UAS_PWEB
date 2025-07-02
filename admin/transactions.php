<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require admin access
requireAdmin();

$success = $error = '';
$transactions = [];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $transaction_id = $_POST['transaction_id'] ?? '';
    $new_status = $_POST['status'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $transaction_id])) {
            $success = 'Status transaksi berhasil diperbarui';
        } else {
            $error = 'Gagal memperbarui status transaksi';
        }
    } catch(PDOException $e) {
        $error = 'Gagal memperbarui status transaksi';
    }
}

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $conditions = [];
    $params = [];

    if ($status_filter) {
        $conditions[] = "t.status = ?";
        $params[] = $status_filter;
    }
    if ($date_from) {
        $conditions[] = "DATE(t.created_at) >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $conditions[] = "DATE(t.created_at) <= ?";
        $params[] = $date_to;
    }
    if ($search) {
        $conditions[] = "(u.username LIKE ? OR t.id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $countQuery = "SELECT COUNT(*) as total 
                   FROM transactions t 
                   LEFT JOIN users u ON t.user_id = u.id 
                   $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalTransactions = $stmt->fetch()['total'];
    $totalPages = ceil($totalTransactions / $limit);

    $query = "SELECT t.*, u.username,
              (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as item_count
              FROM transactions t 
              LEFT JOIN users u ON t.user_id = u.id 
              $whereClause 
              ORDER BY t.created_at DESC 
              LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data transaksi';
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manajemen Transaksi</h2>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Cari transaksi..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_from" value="<?= $date_from ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_to" value="<?= $date_to ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="?" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Tanggal</th>
                            <th>Total Item</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= $transaction['id'] ?></td>
                            <td><?= htmlspecialchars($transaction['username']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></td>
                            <td><?= $transaction['item_count'] ?></td>
                            <td>Rp <?= number_format($transaction['total_amount'],0,',','.') ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $transaction['status'] === 'completed' ? 'success' :
                                    ($transaction['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?= ucfirst($transaction['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewTransactionModal<?= $transaction['id'] ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($transaction['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?= $transaction['id'] ?>">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($_GET) ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($_GET) ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

            <?php foreach ($transactions as $transaction): ?>
            <!-- View Modal -->
            <div class="modal fade" id="viewTransactionModal<?= $transaction['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detail Transaksi #<?= $transaction['id'] ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <?php
                            $stmt = $pdo->prepare("SELECT ti.*, p.name as product_name 
                                                   FROM transaction_items ti
                                                   JOIN products p ON ti.product_id = p.id
                                                   WHERE ti.transaction_id = ?");
                            $stmt->execute([$transaction['id']]);
                            $items = $stmt->fetchAll();
                            ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Jumlah</th>
                                            <th>Harga Satuan</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>Rp <?= number_format($item['price'],0,',','.') ?></td>
                                            <td>Rp <?= number_format($item['price'] * $item['quantity'],0,',','.') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-active">
                                            <th colspan="3" class="text-end">Total:</th>
                                            <th>Rp <?= number_format($transaction['total_amount'],0,',','.') ?></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($transaction['status'] === 'pending'): ?>
            <!-- Update Status Modal -->
            <div class="modal fade" id="updateStatusModal<?= $transaction['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Status Transaksi #<?= $transaction['id'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Status Baru</label>
                                    <select class="form-select" name="status" required>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

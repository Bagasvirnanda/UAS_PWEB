<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require user to be logged in
requireLogin();

$transactions = [];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total transactions count for current user
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalTransactions = $stmt->fetch()['total'];
    $totalPages = ceil($totalTransactions / $limit);

    // Get transactions for current page
    $stmt = $pdo->prepare("SELECT *, (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = transactions.id) as item_count 
                           FROM transactions 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT $limit OFFSET $offset");
    $stmt->execute([$_SESSION['user_id']]);
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data riwayat transaksi';
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-clock-history"></i> Riwayat Transaksi</h2>
        <a href="catalog.php" class="btn btn-primary">
            <i class="bi bi-shop"></i> Mulai Belanja
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($transactions)): ?>
        <div class="alert alert-info text-center">
            <h5>Anda belum memiliki riwayat transaksi</h5>
            <p>Silakan berbelanja terlebih dahulu di <a href="catalog.php">katalog produk</a>.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID Transaksi</th>
                                <th>Tanggal</th>
                                <th>Total Item</th>
                                <th>Total Harga</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $transaction): ?>
                            <tr>
                                <td>#<?php echo $transaction['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td><?php echo $transaction['item_count']; ?></td>
                                <td>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $transaction['status'] === 'completed' ? 'success' : 
                                             ($transaction['status'] === 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewTransactionModal<?php echo $transaction['id']; ?>">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>

                            <!-- View Transaction Modal -->
                            <div class="modal fade" id="viewTransactionModal<?php echo $transaction['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detail Transaksi #<?php echo $transaction['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php
                                            // Get transaction items
                                            $stmt = $pdo->prepare("SELECT ti.*, p.name as product_name 
                                                                   FROM transaction_items ti 
                                                                   JOIN products p ON ti.product_id = p.id 
                                                                   WHERE ti.transaction_id = ?");
                                            $stmt->execute([$transaction['id']]);
                                            $items = $stmt->fetchAll();
                                            ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Produk</th>
                                                            <th>Jumlah</th>
                                                            <th>Harga Satuan</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($items as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                            <td><?php echo $item['quantity']; ?></td>
                                                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                                            <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="table-active">
                                                            <th colspan="3" class="text-end">Total Keseluruhan:</th>
                                                            <th>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>" tabindex="-1">Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

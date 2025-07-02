<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require user to be logged in
requireLogin();

$transaction_id = $_GET['transaction_id'] ?? '';
$transaction = null;
$transaction_items = [];

// Get transaction details
try {
    $stmt = $pdo->prepare("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.id = ? AND t.user_id = ?");
    $stmt->execute([$transaction_id, $_SESSION['user_id']]);
    $transaction = $stmt->fetch();
    
    if ($transaction) {
        // Get transaction items
        $stmt = $pdo->prepare("SELECT ti.*, p.name as product_name FROM transaction_items ti JOIN products p ON ti.product_id = p.id WHERE ti.transaction_id = ?");
        $stmt->execute([$transaction_id]);
        $transaction_items = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    $error = 'Gagal mengambil data transaksi';
}

if (!$transaction) {
    header('Location: catalog.php');
    exit;
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="text-center mb-5">
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill display-1 text-success"></i>
            <h2 class="mt-3">Pesanan Berhasil!</h2>
            <p class="lead">Terima kasih atas pesanan Anda. Transaksi #<?php echo $transaction['id']; ?> telah berhasil diproses.</p>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="bi bi-receipt"></i> Detail Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Informasi Transaksi</h6>
                            <dl class="row">
                                <dt class="col-sm-4">ID Transaksi:</dt>
                                <dd class="col-sm-8">#<?php echo $transaction['id']; ?></dd>
                                
                                <dt class="col-sm-4">Tanggal:</dt>
                                <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></dd>
                                
                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-warning">Pending</span>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6>Informasi Pembeli</h6>
                            <dl class="row">
                                <dt class="col-sm-4">Nama:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($transaction['username']); ?></dd>
                                
                                <dt class="col-sm-4">Total:</dt>
                                <dd class="col-sm-8"><strong>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></strong></dd>
                            </dl>
                        </div>
                    </div>
                    
                    <h6>Item Pesanan</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga Satuan</th>
                                    <th>Jumlah</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($transaction_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="3">Total Keseluruhan:</th>
                                    <th>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <h6><i class="bi bi-info-circle"></i> Informasi Penting:</h6>
                <ul class="mb-0">
                    <li>Pesanan Anda sedang diproses dengan status <strong>Pending</strong></li>
                    <li>Anda akan menerima konfirmasi dari admin dalam 1-2 hari kerja</li>
                    <li>Pantau status pesanan Anda di halaman <a href="history.php">Riwayat Transaksi</a></li>
                    <li>Simpan ID transaksi (#<?php echo $transaction['id']; ?>) untuk referensi</li>
                </ul>
            </div>
            
            <div class="text-center mt-4">
                <a href="history.php" class="btn btn-primary me-2">
                    <i class="bi bi-clock-history"></i> Lihat Riwayat Transaksi
                </a>
                <a href="catalog.php" class="btn btn-secondary">
                    <i class="bi bi-shop"></i> Lanjut Belanja
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

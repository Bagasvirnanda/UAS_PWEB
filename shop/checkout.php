<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require user to be logged in
requireLogin();

$success = $error = '';
$cart_items = [];
$total = 0;

// Get cart items first
try {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();

    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch(PDOException $e) {
    $error = 'Gagal mengambil data keranjang';
}

// If cart is empty, redirect to catalog
if (empty($cart_items)) {
    header('Location: catalog.php');
    exit;
}

// Handle checkout process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $pdo->beginTransaction();
    
    try {
        // Verify stock availability again
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $current_stock = $stmt->fetch()['stock'];
            
            if ($current_stock < $item['quantity']) {
                throw new Exception("Stok produk {$item['name']} tidak mencukupi");
            }
        }
        
        // Create transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, total_amount, status, transaction_date) VALUES (?, ?, 'pending', NOW())");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $transaction_id = $pdo->lastInsertId();
        
        // Add transaction items and update stock
        foreach ($cart_items as $item) {
            // Add transaction item
            $stmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$transaction_id, $item['product_id'], $item['quantity'], $item['price']]);
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Log activity
        logUserActivity($_SESSION['user_id'], "Melakukan checkout transaksi #$transaction_id senilai Rp " . number_format($total, 0, ',', '.'));
        
        $pdo->commit();
        
        // Redirect to success page
        header("Location: order_success.php?transaction_id=$transaction_id");
        exit;
        
    } catch(Exception $e) {
        $pdo->rollback();
        $error = $e->getMessage();
    } catch(PDOException $e) {
        $pdo->rollback();
        $error = 'Gagal memproses checkout. Silakan coba lagi.';
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-credit-card"></i> Checkout</h2>
        <a href="cart.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list-check"></i> Review Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cart_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="3">Total Keseluruhan:</th>
                                    <th>Rp <?php echo number_format($total, 0, ',', '.'); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-person"></i> Informasi Pembeli</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Nama:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($_SESSION['username']); ?></dd>
                        
                        <dt class="col-sm-4">Total Item:</dt>
                        <dd class="col-sm-8"><?php echo count($cart_items); ?> produk</dd>
                        
                        <dt class="col-sm-4">Total Harga:</dt>
                        <dd class="col-sm-8"><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></dd>
                    </dl>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <small><i class="bi bi-info-circle"></i> 
                        Dengan melanjutkan checkout, pesanan Anda akan diproses dan stok produk akan dikurangi.
                        </small>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-success w-100 btn-lg" onclick="return confirm('Apakah Anda yakin ingin melanjutkan checkout?')">
                            <i class="bi bi-check-circle"></i> Proses Pesanan
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="bi bi-shield-check"></i> Kebijakan</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success"></i> Pesanan akan diproses dalam 1-2 hari kerja</li>
                        <li><i class="bi bi-check-circle text-success"></i> Status pesanan dapat dipantau di halaman riwayat transaksi</li>
                        <li><i class="bi bi-check-circle text-success"></i> Stok produk akan dikurangi otomatis</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

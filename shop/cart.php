<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require user to be logged in
requireLogin();

$success = $error = '';
$cart_items = [];
$total = 0;

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_from_cart') {
    $cart_id = $_POST['cart_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$cart_id, $_SESSION['user_id']])) {
            $success = 'Produk berhasil dihapus dari keranjang';
        } else {
            $error = 'Gagal menghapus produk dari keranjang';
        }
    } catch(PDOException $e) {
        $error = 'Gagal menghapus produk dari keranjang';
    }
}

// Handle cart update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_cart') {
    foreach ($_POST['quantities'] as $cart_id => $quantity) {
        $quantity = max(1, intval($quantity));
        
        try {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
        } catch(PDOException $e) {
            $error = 'Gagal memperbarui keranjang';
        }
    }
    $success = 'Keranjang berhasil diperbarui';
}

// Get cart items
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

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cart"></i> Keranjang Belanja</h2>
        <a href="catalog.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Katalog
        </a>
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

    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info text-center">
            <h5>Keranjang kosong</h5>
            <p>Tambahkan produk ke keranjang dari <a href="catalog.php">katalog</a>.</p>
        </div>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="action" value="update_cart">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga Satuan</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                            <td>
                                <input type="number" name="quantities[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="form-control form-control-sm" style="width: 70px;">
                            </td>
                            <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_from_cart">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th colspan="2">Rp <?php echo number_format($total, 0, ',', '.'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <button type="submit" class="btn btn-primary">Perbarui Keranjang</button>
                <a href="checkout.php" class="btn btn-success">Lanjut ke Checkout</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>


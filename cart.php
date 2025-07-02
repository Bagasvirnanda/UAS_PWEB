<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Require login
requireLogin();

$success = $error = '';
$user_id = $_SESSION['user_id'];

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = max(1, intval($_POST['quantity']));

    try {
        // Check product availability
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND stock >= ?");
        $stmt->execute([$product_id, $quantity]);
        $product = $stmt->fetch();

        if ($product) {
            // Add to cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity
                ];
            }
            $success = 'Produk berhasil ditambahkan ke keranjang';
        } else {
            $error = 'Stok produk tidak mencukupi';
        }
    } catch(PDOException $e) {
        $error = 'Gagal menambahkan produk ke keranjang';
    }
}

// Handle update cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $quantities = $_POST['quantities'];

    try {
        foreach ($quantities as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity = max(0, intval($quantity));

            if ($quantity > 0) {
                // Check stock availability
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND stock >= ?");
                $stmt->execute([$product_id, $quantity]);
                if ($stmt->fetch()) {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                } else {
                    $error = 'Beberapa produk melebihi stok yang tersedia';
                    break;
                }
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        if (!$error) {
            $success = 'Keranjang berhasil diperbarui';
        }
    } catch(PDOException $e) {
        $error = 'Gagal memperbarui keranjang';
    }
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        $error = 'Keranjang belanja kosong';
    } else {
        try {
            $pdo->beginTransaction();

            // Calculate total
            $total_amount = 0;
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }

            // Create transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, total_amount, transaction_date, status) 
                                 VALUES (?, ?, NOW(), 'pending')");
            $stmt->execute([$user_id, $total_amount]);
            $transaction_id = $pdo->lastInsertId();

            // Create transaction items and update stock
            foreach ($_SESSION['cart'] as $product_id => $item) {
                // Insert transaction item
                $stmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price) 
                                     VALUES (?, ?, ?, ?)");
                $stmt->execute([$transaction_id, $product_id, $item['quantity'], $item['price']]);

                // Update stock
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $product_id]);
            }

            // Log the transaction
            $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity, activity_time) 
                                 VALUES (?, 'Created new transaction', NOW())");
            $stmt->execute([$user_id]);

            // Create notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, status, created_at) 
                                 VALUES (?, 'Transaksi baru berhasil dibuat', 'unread', NOW())");
            $stmt->execute([$user_id]);

            $pdo->commit();

            // Clear cart
            $_SESSION['cart'] = [];

            $success = 'Transaksi berhasil dibuat';
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal membuat transaksi';
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Keranjang Belanja</h2>
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

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-cart3 fs-1 text-muted"></i>
                <h4 class="mt-3">Keranjang Belanja Kosong</h4>
                <p class="text-muted">Silakan tambahkan produk ke keranjang</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="bi bi-shop"></i> Lihat Produk
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" id="cartForm">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th width="150">Harga</th>
                                    <th width="150">Jumlah</th>
                                    <th width="150">Subtotal</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($_SESSION['cart'] as $product_id => $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total += $subtotal;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                        <td>
                                            <input type="number" class="form-control" 
                                                   name="quantities[<?php echo $product_id; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="0" required>
                                        </td>
                                        <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                        <td>
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    name="quantities[<?php echo $product_id; ?>]" value="0">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="submit" name="update_cart" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Update Keranjang
                        </button>
                        <button type="submit" name="checkout" class="btn btn-primary">
                            <i class="bi bi-cart-check"></i> Checkout
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('cartForm')?.addEventListener('submit', function(e) {
    if (e.submitter.name === 'checkout') {
        if (!confirm('Apakah Anda yakin ingin melakukan checkout?')) {
            e.preventDefault();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
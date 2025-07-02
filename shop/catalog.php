<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require user to be logged in
requireLogin();

$success = $error = '';
$products = [];
$categories = [];

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12; // Show 12 products per page
$offset = ($page - 1) * $limit;

// Get categories for filter dropdown
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data kategori';
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = $_POST['product_id'] ?? '';
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    
    try {
        // Check if product exists and has sufficient stock
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $error = 'Produk tidak ditemukan';
        } elseif ($product['stock'] < $quantity) {
            $error = 'Stok tidak mencukupi';
        } else {
            // Check if item already in cart
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            $existing_cart = $stmt->fetch();
            
            if ($existing_cart) {
                // Update quantity
                $new_quantity = $existing_cart['quantity'] + $quantity;
                if ($new_quantity > $product['stock']) {
                    $error = 'Total quantity melebihi stok yang tersedia';
                } else {
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$new_quantity, $_SESSION['user_id'], $product_id]);
                    $success = 'Produk berhasil ditambahkan ke keranjang';
                }
            } else {
                // Add new item to cart
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
                $success = 'Produk berhasil ditambahkan ke keranjang';
            }
        }
    } catch(PDOException $e) {
        $error = 'Gagal menambahkan produk ke keranjang';
    }
}

// Build search and filter query
try {
    $whereConditions = ["p.stock > 0"]; // Only show products in stock
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category_filter)) {
        $whereConditions[] = "p.category_id = ?";
        $params[] = $category_filter;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Valid sort columns
    $validSorts = ['name', 'price', 'created_at'];
    $validOrders = ['ASC', 'DESC'];
    
    if (!in_array($sort, $validSorts)) $sort = 'name';
    if (!in_array($order, $validOrders)) $order = 'ASC';
    
    // Get total products count
    $countQuery = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalProducts = $stmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $limit);
    
    // Get products for current page
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              $whereClause 
              ORDER BY p.$sort $order 
              LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data produk';
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-shop"></i> Katalog Produk</h2>
        <a href="cart.php" class="btn btn-primary">
            <i class="bi bi-cart"></i> Keranjang Belanja
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

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="category">
                        <option value="">Semua Kategori</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="sort">
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Nama</option>
                        <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Harga</option>
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Terbaru</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="order">
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>A-Z / Murah-Mahal</option>
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Z-A / Mahal-Murah</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="row">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5>Tidak ada produk yang ditemukan</h5>
                    <p>Coba ubah filter pencarian atau cek kembali nanti.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($products as $product): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'Tanpa Kategori'); ?>
                        </p>
                        <p class="card-text flex-grow-1">
                            <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>
                            <?php if (strlen($product['description']) > 100): ?>...<?php endif; ?>
                        </p>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="text-primary mb-0">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></h5>
                                <span class="badge bg-<?php echo $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                    Stok: <?php echo $product['stock']; ?>
                                </span>
                            </div>
                            <?php if ($product['stock'] > 0): ?>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" class="form-control form-control-sm" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 80px;">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="bi bi-cart-plus"></i> Tambah
                                </button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>Stok Habis</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>" tabindex="-1">Previous</a>
            </li>
            <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>">Next</a>
            </li>
        </ul>
    </nav>
    
    <div class="text-center mt-2">
        <small class="text-muted">
            Menampilkan <?php echo count($products); ?> dari <?php echo $totalProducts; ?> produk 
            (Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>)
        </small>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

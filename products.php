<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Require login
requireLogin();

// Get filter parameters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($settings['items_per_page']) ? intval($settings['items_per_page']) : 12;
$offset = ($page - 1) * $limit;

// Get categories for filter
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data kategori';
}

// Build query conditions
$conditions = [];
$params = [];

if ($category_id > 0) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

if ($search) {
    $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)"; 
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Build order clause
$order_clause = match($sort) {
    'name_desc' => 'ORDER BY p.name DESC',
    'price_asc' => 'ORDER BY p.price ASC',
    'price_desc' => 'ORDER BY p.price DESC',
    default => 'ORDER BY p.name ASC'
};

// Get total products count
try {
    $sql = "SELECT COUNT(*) FROM products p $where_clause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total_products = $stmt->fetchColumn();
    $total_pages = ceil($total_products / $limit);
} catch(PDOException $e) {
    $error = 'Gagal mengambil jumlah produk';
}

// Get products
try {
    $sql = "SELECT p.*, c.name as category_name 
           FROM products p 
           LEFT JOIN categories c ON p.category_id = c.id 
           $where_clause 
           $order_clause 
           LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data produk';
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Produk</h2>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select class="form-select" name="category">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id === $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Pencarian</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Cari produk...">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Urutkan</label>
                    <select class="form-select" name="sort">
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nama (A-Z)</option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Nama (Z-A)</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Harga (Terendah)</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Harga (Tertinggi)</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
        <?php foreach ($products as $product): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><?php echo htmlspecialchars($product['name']); ?></h5>
                        
                        <p class="card-text text-muted small mb-2">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                        </p>
                        
                        <p class="card-text mb-3"><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></h5>
                            <span class="badge <?php echo $product['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $product['stock'] > 0 ? 'Stok: ' . $product['stock'] : 'Stok Habis'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    Tidak ada produk yang ditemukan
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
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

<?php include 'includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require admin access
requireAdmin();

$success = $error = '';
$products = []; // Inisialisasi array kosong untuk products
$categories = []; // Inisialisasi array kosong untuk categories

// Inisialisasi variabel filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Get categories for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    echo '<div class="alert alert-danger" role="alert">Gagal mengambil data kategori.</div>';
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Build query with search and category filters
    $whereConditions = [];
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
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Get total products count with filters
    $countQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalProducts = $stmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $limit);

    if ($totalProducts === 0) {
        if (!empty($search) || !empty($category_filter)) {
            echo '<div class="alert alert-info" role="alert">Tidak ada produk yang sesuai dengan kriteria pencarian.</div>';
        } else {
            echo '<div class="alert alert-info" role="alert">Belum ada data produk yang tersedia.</div>';
        }
    } else {
        // Get products for current page with filters
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 $whereClause 
                 ORDER BY p.created_at DESC 
                 LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($query);
        
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        if (empty($products)) {
            echo '<div class="alert alert-info" role="alert">Tidak ada data produk pada halaman ini.</div>';
        }
    }
} catch(PDOException $e) {
    echo '<div class="alert alert-danger" role="alert">Gagal mengambil data produk.</div>';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $price = $_POST['price'] ?? 0;
                $stock = $_POST['stock'] ?? 0;
                $category_id = $_POST['category_id'] ?? null;

                try {
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    if ($stmt->execute([$name, $description, $price, $stock, $category_id])) {
                        $success = 'Produk berhasil ditambahkan';
                    } else {
                        $error = 'Gagal menambahkan produk';
                    }
                } catch(PDOException $e) {
                    $error = 'Gagal menambahkan produk';
                }
                break;

            case 'update':
                $id = $_POST['product_id'] ?? '';
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $price = $_POST['price'] ?? 0;
                $stock = $_POST['stock'] ?? 0;
                $category_id = $_POST['category_id'] ?? null;

                try {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$name, $description, $price, $stock, $category_id, $id])) {
                        $success = 'Produk berhasil diperbarui';
                    } else {
                        $error = 'Gagal memperbarui produk';
                    }
                } catch(PDOException $e) {
                    $error = 'Gagal memperbarui produk';
                }
                break;

            case 'delete':
                $id = $_POST['product_id'] ?? '';
                try {
                    // Delete product directly - CASCADE DELETE will handle related records
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $success = 'Produk berhasil dihapus (termasuk data terkait)';
                    } else {
                        $error = 'Gagal menghapus produk';
                    }
                } catch(PDOException $e) {
                    $error = 'Gagal menghapus produk';
                }
                break;
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manajemen Produk</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-circle"></i> Tambah Produk
        </button>
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
                <div class="col-md-6">
                    <input type="text" class="form-control" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
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
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Terakhir Diperbarui</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Tanpa Kategori'); ?></td>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $product['stock'] > 0 ? ($product['stock'] < 10 ? 'warning' : 'success') : 'danger'; ?>">
                                    <?php echo $product['stock']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($product['updated_at'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteProductModal<?php echo $product['id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Product Modal -->
                        <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Produk</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Produk</label>
                                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Kategori</label>
                                                <select class="form-select" name="category_id">
                                                    <option value="">Pilih Kategori</option>
                                                    <?php foreach($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Harga</label>
                                                <input type="number" class="form-control" name="price" value="<?php echo $product['price']; ?>" required min="0">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Stok</label>
                                                <input type="number" class="form-control" name="stock" value="<?php echo $product['stock']; ?>" required min="0">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Product Modal -->
                        <div class="modal fade" id="deleteProductModal<?php echo $product['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Hapus Produk</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Apakah Anda yakin ingin menghapus produk <strong><?php echo htmlspecialchars($product['name']); ?></strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger">Hapus</button>
                                        </form>
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
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" tabindex="-1">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Pilih Kategori</option>
                            <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="number" class="form-control" name="price" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" class="form-control" name="stock" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
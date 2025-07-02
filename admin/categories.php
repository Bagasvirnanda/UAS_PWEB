<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require admin access
requireAdmin();

// Check database connection
if (!$db_connected) {
    include '../includes/header.php';
    echo '<div class="container-fluid py-4">';
    echo '<div class="alert alert-danger" role="alert">';
    echo htmlspecialchars($db_connection_error ?: 'Tidak dapat terhubung ke database. Silakan periksa koneksi database Anda.');
    echo '</div>';
    echo '</div>';
    include '../includes/footer.php';
    exit;
}

$success = $error = '';
$categories = []; // Inisialisasi array kosong untuk categories

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (empty($name)) {
                    $error = 'Nama kategori tidak boleh kosong';
                } else {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO categories (name, description, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                        if ($stmt->execute([$name, $description])) {
                            $success = 'Kategori berhasil ditambahkan';
                            // Log activity
                            logUserActivity($_SESSION['user_id'], 'Menambahkan kategori: ' . $name);
                        } else {
                            $error = 'Gagal menambahkan kategori';
                        }
                    } catch(PDOException $e) {
                        error_log('Error creating category: ' . $e->getMessage());
                        $error = 'Gagal menambahkan kategori: ' . ($e->errorInfo[1] == 1062 ? 'Nama kategori sudah ada' : 'Terjadi kesalahan sistem');
                    }
                }
                break;

            case 'update':
                $id = $_POST['category_id'] ?? '';
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (empty($name)) {
                    $error = 'Nama kategori tidak boleh kosong';
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
                        if ($stmt->execute([$name, $description, $id])) {
                            $success = 'Kategori berhasil diperbarui';
                            // Log activity
                            logUserActivity($_SESSION['user_id'], 'Memperbarui kategori: ' . $name);
                        } else {
                            $error = 'Gagal memperbarui kategori';
                        }
                    } catch(PDOException $e) {
                        error_log('Error updating category: ' . $e->getMessage());
                        $error = 'Gagal memperbarui kategori: ' . ($e->errorInfo[1] == 1062 ? 'Nama kategori sudah ada' : 'Terjadi kesalahan sistem');
                    }
                }
                break;

            case 'delete':
                $id = $_POST['category_id'] ?? '';
                try {
                    // Delete category directly - CASCADE will set products category_id to NULL
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $success = 'Kategori berhasil dihapus (produk terkait akan tanpa kategori)';
                        // Log activity
                        logUserActivity($_SESSION['user_id'], 'Menghapus kategori ID: ' . $id);
                    } else {
                        $error = 'Gagal menghapus kategori';
                    }
                } catch(PDOException $e) {
                    error_log('Error deleting category: ' . $e->getMessage());
                    $error = 'Gagal menghapus kategori: Terjadi kesalahan sistem';
                }
                break;
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total categories count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
    $totalCategories = $stmt->fetch()['total'];
    $totalPages = ceil($totalCategories / $limit);

    // Get categories for current page with product count
    $stmt = $pdo->prepare("SELECT c.*, COUNT(p.id) as product_count 
                          FROM categories c 
                          LEFT JOIN products p ON c.id = p.category_id 
                          GROUP BY c.id 
                          ORDER BY c.name 
                          LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log('Error fetching categories: ' . $e->getMessage());
    $error = 'Gagal mengambil data kategori: Terjadi kesalahan sistem';
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manajemen Kategori</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle"></i> Tambah Kategori
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th>Jumlah Produk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td><span class="badge bg-info"><?php echo $category['product_count']; ?></span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal<?php echo $category['id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Category Modal -->
                        <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Kategori</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Kategori</label>
                                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
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

                        <!-- Delete Category Modal -->
                        <div class="modal fade" id="deleteCategoryModal<?php echo $category['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Hapus Kategori</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Apakah Anda yakin ingin menghapus kategori <strong><?php echo htmlspecialchars($category['name']); ?></strong>?</p>
                                        <?php if ($category['product_count'] > 0): ?>
                                        <div class="alert alert-warning">
                                            Kategori ini memiliki <?php echo $category['product_count']; ?> produk. Anda harus memindahkan atau menghapus produk terlebih dahulu.
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger" <?php echo $category['product_count'] > 0 ? 'disabled' : ''; ?>>Hapus</button>
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
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
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
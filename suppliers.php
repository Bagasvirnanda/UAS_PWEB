<?php
require_once 'config/database.php';
require_once 'includes/session_manager.php';

// Require admin access
requireAdmin();

$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'Nama supplier harus diisi';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $contact_person, $email, $phone, $address, $is_active]);
                $success = 'Supplier berhasil ditambahkan';
                $action = 'list';
            } elseif ($action === 'edit' && $id) {
                $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $contact_person, $email, $phone, $address, $is_active, $id]);
                $success = 'Supplier berhasil diupdate';
                $action = 'list';
            }
        } catch(PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Supplier berhasil dihapus';
        $action = 'list';
    } catch(PDOException $e) {
        $error = 'Tidak dapat menghapus supplier: ' . $e->getMessage();
    }
}

// Get supplier data for edit
$supplier = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$id]);
    $supplier = $stmt->fetch();
    if (!$supplier) {
        $error = 'Supplier tidak ditemukan';
        $action = 'list';
    }
}

// Get suppliers list
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $whereClause = '';
    $params = [];
    
    if ($search) {
        $whereClause = "WHERE name LIKE ? OR contact_person LIKE ? OR email LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    
    $stmt = $pdo->prepare("SELECT * FROM suppliers $whereClause ORDER BY name");
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Supplier - Sistem Manajemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>Sistem Manajemen
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="products.php">Produk</a>
                <a class="nav-link active" href="suppliers.php">Supplier</a>
                <a class="nav-link" href="purchase_orders.php">Purchase Orders</a>
                <a class="nav-link" href="reports.php">Laporan</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Suppliers List -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-truck me-2"></i>Manajemen Supplier</h2>
                <a href="?action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Supplier
                </a>
            </div>

            <!-- Search -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control" placeholder="Cari supplier..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Suppliers Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Kontak</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td><?php echo $supplier['id']; ?></td>
                                        <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $supplier['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $supplier['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $supplier['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Yakin ingin menghapus supplier ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <!-- Create/Edit Form -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <?php echo $action === 'create' ? 'Tambah Supplier' : 'Edit Supplier'; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nama Supplier *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo $supplier ? htmlspecialchars($supplier['name']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_person" class="form-label">Kontak Person</label>
                                            <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                                   value="<?php echo $supplier ? htmlspecialchars($supplier['contact_person']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo $supplier ? htmlspecialchars($supplier['email']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Telepon</label>
                                            <input type="text" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo $supplier ? htmlspecialchars($supplier['phone']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo $supplier ? htmlspecialchars($supplier['address']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo (!$supplier || $supplier['is_active']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Aktif
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?action=list" class="btn btn-secondary">Kembali</a>
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $action === 'create' ? 'Tambah' : 'Update'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

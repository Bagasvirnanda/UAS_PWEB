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
    $supplier_id = $_POST['supplier_id'] ?? '';
    $order_number = trim($_POST['order_number'] ?? '');
    $order_date = $_POST['order_date'] ?? '';
    $expected_delivery_date = $_POST['expected_delivery_date'] ?? null;
    $actual_delivery_date = $_POST['actual_delivery_date'] ?? null;
    $status = $_POST['status'] ?? 'draft';
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($supplier_id) || empty($order_number) || empty($order_date)) {
        $error = 'Supplier, nomor order, dan tanggal order harus diisi';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO purchase_orders (supplier_id, user_id, order_number, order_date, expected_delivery_date, actual_delivery_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $supplier_id, 
                    getUserId(), 
                    $order_number, 
                    $order_date, 
                    $expected_delivery_date ?: null, 
                    $actual_delivery_date ?: null, 
                    $status, 
                    $notes
                ]);
                $success = 'Purchase order berhasil dibuat';
                $action = 'list';
            } elseif ($action === 'edit' && $id) {
                $stmt = $pdo->prepare("UPDATE purchase_orders SET supplier_id = ?, order_number = ?, order_date = ?, expected_delivery_date = ?, actual_delivery_date = ?, status = ?, notes = ? WHERE id = ?");
                $stmt->execute([
                    $supplier_id, 
                    $order_number, 
                    $order_date, 
                    $expected_delivery_date ?: null, 
                    $actual_delivery_date ?: null, 
                    $status, 
                    $notes,
                    $id
                ]);
                $success = 'Purchase order berhasil diupdate';
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
        $stmt = $pdo->prepare("DELETE FROM purchase_orders WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Purchase order berhasil dihapus';
        $action = 'list';
    } catch(PDOException $e) {
        $error = 'Tidak dapat menghapus purchase order: ' . $e->getMessage();
    }
}

// Get purchase order data for edit
$po = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ?");
    $stmt->execute([$id]);
    $po = $stmt->fetch();
    if (!$po) {
        $error = 'Purchase order tidak ditemukan';
        $action = 'list';
    }
}

// Get suppliers for dropdown
$stmt = $pdo->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name");
$suppliers = $stmt->fetchAll();

// Get purchase orders list
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search) {
        $whereClause .= " AND (po.order_number LIKE ? OR s.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status_filter) {
        $whereClause .= " AND po.status = ?";
        $params[] = $status_filter;
    }
    
    $stmt = $pdo->prepare("
        SELECT po.*, s.name as supplier_name, u.username 
        FROM purchase_orders po 
        JOIN suppliers s ON po.supplier_id = s.id 
        JOIN users u ON po.user_id = u.id 
        $whereClause 
        ORDER BY po.created_at DESC
    ");
    $stmt->execute($params);
    $purchase_orders = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - Sistem Manajemen</title>
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
                <a class="nav-link" href="suppliers.php">Supplier</a>
                <a class="nav-link active" href="purchase_orders.php">Purchase Orders</a>
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
            <!-- Purchase Orders List -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-shopping-cart me-2"></i>Purchase Orders</h2>
                <a href="?action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Buat Purchase Order
                </a>
            </div>

            <!-- Search and Filter -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control" placeholder="Cari order number atau supplier..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <select name="status" class="form-select ms-2">
                            <option value="">Semua Status</option>
                            <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="sent" <?php echo ($_GET['status'] ?? '') === 'sent' ? 'selected' : ''; ?>>Sent</option>
                            <option value="received" <?php echo ($_GET['status'] ?? '') === 'received' ? 'selected' : ''; ?>>Received</option>
                            <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Purchase Orders Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Supplier</th>
                                    <th>Order Date</th>
                                    <th>Expected Delivery</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchase_orders as $po): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($po['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($po['order_date'])); ?></td>
                                        <td><?php echo $po['expected_delivery_date'] ? date('d/m/Y', strtotime($po['expected_delivery_date'])) : '-'; ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = [
                                                'draft' => 'bg-secondary',
                                                'sent' => 'bg-primary',
                                                'received' => 'bg-success',
                                                'cancelled' => 'bg-danger'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $badgeClass[$po['status']] ?? 'bg-secondary'; ?>">
                                                <?php echo ucfirst($po['status']); ?>
                                            </span>
                                        </td>
                                        <td>Rp <?php echo number_format($po['total_amount'], 0, ',', '.'); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $po['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $po['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Yakin ingin menghapus purchase order ini?')">
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
                                <?php echo $action === 'create' ? 'Buat Purchase Order' : 'Edit Purchase Order'; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="supplier_id" class="form-label">Supplier *</label>
                                            <select class="form-select" id="supplier_id" name="supplier_id" required>
                                                <option value="">Pilih Supplier</option>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                    <option value="<?php echo $supplier['id']; ?>" 
                                                            <?php echo ($po && $po['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="order_number" class="form-label">Order Number *</label>
                                            <input type="text" class="form-control" id="order_number" name="order_number" 
                                                   value="<?php echo $po ? htmlspecialchars($po['order_number']) : 'PO-' . date('Y-m-') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="order_date" class="form-label">Order Date *</label>
                                            <input type="date" class="form-control" id="order_date" name="order_date" 
                                                   value="<?php echo $po ? $po['order_date'] : date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="expected_delivery_date" class="form-label">Expected Delivery</label>
                                            <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" 
                                                   value="<?php echo $po ? $po['expected_delivery_date'] : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="actual_delivery_date" class="form-label">Actual Delivery</label>
                                            <input type="date" class="form-control" id="actual_delivery_date" name="actual_delivery_date" 
                                                   value="<?php echo $po ? $po['actual_delivery_date'] : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="draft" <?php echo ($po && $po['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                <option value="sent" <?php echo ($po && $po['status'] === 'sent') ? 'selected' : ''; ?>>Sent</option>
                                                <option value="received" <?php echo ($po && $po['status'] === 'received') ? 'selected' : ''; ?>>Received</option>
                                                <option value="cancelled" <?php echo ($po && $po['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $po ? htmlspecialchars($po['notes']) : ''; ?></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?action=list" class="btn btn-secondary">Kembali</a>
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $action === 'create' ? 'Buat' : 'Update'; ?>
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

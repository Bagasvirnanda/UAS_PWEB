<?php
require_once 'config/database.php';
require_once 'includes/session_manager.php';

// Require login
requireLogin();

$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $scheduled_at = $_POST['scheduled_at'] ?? null;
    
    if (empty($report_type) || empty($title)) {
        $error = 'Tipe laporan dan judul harus diisi';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO reports (user_id, report_type, title, description, status, scheduled_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    getUserId(), 
                    $report_type, 
                    $title, 
                    $description, 
                    $status, 
                    $scheduled_at ?: null
                ]);
                $success = 'Laporan berhasil dibuat';
                $action = 'list';
            } elseif ($action === 'edit' && $id) {
                $stmt = $pdo->prepare("UPDATE reports SET report_type = ?, title = ?, description = ?, status = ?, scheduled_at = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([
                    $report_type, 
                    $title, 
                    $description, 
                    $status, 
                    $scheduled_at ?: null,
                    $id,
                    getUserId()
                ]);
                $success = 'Laporan berhasil diupdate';
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
        $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, getUserId()]);
        $success = 'Laporan berhasil dihapus';
        $action = 'list';
    } catch(PDOException $e) {
        $error = 'Tidak dapat menghapus laporan: ' . $e->getMessage();
    }
}

// Handle generate report
if ($action === 'generate' && $id) {
    try {
        // Simulate report generation
        $reportData = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_records' => rand(100, 1000),
            'summary' => 'Report generated successfully'
        ];
        
        $stmt = $pdo->prepare("UPDATE reports SET status = 'generated', generated_at = NOW(), report_data = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([json_encode($reportData), $id, getUserId()]);
        $success = 'Laporan berhasil digenerate';
        $action = 'list';
    } catch(PDOException $e) {
        $error = 'Gagal generate laporan: ' . $e->getMessage();
    }
}

// Get report data for edit
$report = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, getUserId()]);
    $report = $stmt->fetch();
    if (!$report) {
        $error = 'Laporan tidak ditemukan';
        $action = 'list';
    }
}

// Get reports list
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    $whereClause = "WHERE user_id = ?";
    $params = [getUserId()];
    
    if ($search) {
        $whereClause .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($type_filter) {
        $whereClause .= " AND report_type = ?";
        $params[] = $type_filter;
    }
    
    if ($status_filter) {
        $whereClause .= " AND status = ?";
        $params[] = $status_filter;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM reports $whereClause ORDER BY created_at DESC");
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Laporan - Sistem Manajemen</title>
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
                <?php if (isAdmin()): ?>
                    <a class="nav-link" href="suppliers.php">Supplier</a>
                    <a class="nav-link" href="purchase_orders.php">Purchase Orders</a>
                <?php endif; ?>
                <a class="nav-link" href="reports.php">Dashboard Laporan</a>
                <a class="nav-link active" href="report_management.php">Kelola Laporan</a>
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
            <!-- Reports List -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-chart-line me-2"></i>Manajemen Laporan</h2>
                <a href="?action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Buat Laporan
                </a>
            </div>

            <!-- Search and Filter -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control" placeholder="Cari laporan..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <select name="type" class="form-select ms-2">
                            <option value="">Semua Tipe</option>
                            <option value="sales" <?php echo ($_GET['type'] ?? '') === 'sales' ? 'selected' : ''; ?>>Sales</option>
                            <option value="inventory" <?php echo ($_GET['type'] ?? '') === 'inventory' ? 'selected' : ''; ?>>Inventory</option>
                            <option value="user_activity" <?php echo ($_GET['type'] ?? '') === 'user_activity' ? 'selected' : ''; ?>>User Activity</option>
                            <option value="financial" <?php echo ($_GET['type'] ?? '') === 'financial' ? 'selected' : ''; ?>>Financial</option>
                            <option value="custom" <?php echo ($_GET['type'] ?? '') === 'custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                        <select name="status" class="form-select ms-2">
                            <option value="">Semua Status</option>
                            <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="generated" <?php echo ($_GET['status'] ?? '') === 'generated' ? 'selected' : ''; ?>>Generated</option>
                            <option value="scheduled" <?php echo ($_GET['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="archived" <?php echo ($_GET['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th>Generated</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                            <?php if ($report['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($report['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst($report['report_type']); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = [
                                                'draft' => 'bg-secondary',
                                                'generated' => 'bg-success',
                                                'scheduled' => 'bg-primary',
                                                'archived' => 'bg-dark'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $badgeClass[$report['status']] ?? 'bg-secondary'; ?>">
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></td>
                                        <td><?php echo $report['generated_at'] ? date('d/m/Y H:i', strtotime($report['generated_at'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($report['status'] === 'draft' || $report['status'] === 'scheduled'): ?>
                                                <a href="?action=generate&id=<?php echo $report['id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Generate Report">
                                                    <i class="fas fa-cogs"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=edit&id=<?php echo $report['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $report['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" title="Delete"
                                               onclick="return confirm('Yakin ingin menghapus laporan ini?')">
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
                                <?php echo $action === 'create' ? 'Buat Laporan' : 'Edit Laporan'; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="report_type" class="form-label">Tipe Laporan *</label>
                                            <select class="form-select" id="report_type" name="report_type" required>
                                                <option value="">Pilih Tipe</option>
                                                <option value="sales" <?php echo ($report && $report['report_type'] === 'sales') ? 'selected' : ''; ?>>Sales Report</option>
                                                <option value="inventory" <?php echo ($report && $report['report_type'] === 'inventory') ? 'selected' : ''; ?>>Inventory Report</option>
                                                <option value="user_activity" <?php echo ($report && $report['report_type'] === 'user_activity') ? 'selected' : ''; ?>>User Activity Report</option>
                                                <option value="financial" <?php echo ($report && $report['report_type'] === 'financial') ? 'selected' : ''; ?>>Financial Report</option>
                                                <option value="custom" <?php echo ($report && $report['report_type'] === 'custom') ? 'selected' : ''; ?>>Custom Report</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="draft" <?php echo ($report && $report['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                <option value="scheduled" <?php echo ($report && $report['status'] === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                                <option value="archived" <?php echo ($report && $report['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Judul Laporan *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo $report ? htmlspecialchars($report['title']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $report ? htmlspecialchars($report['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="scheduled_at" class="form-label">Jadwal Generate (Opsional)</label>
                                    <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at" 
                                           value="<?php echo $report && $report['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($report['scheduled_at'])) : ''; ?>">
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

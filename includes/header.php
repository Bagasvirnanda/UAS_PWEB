<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';

// Set default settings
$settings = [
    'site_name' => 'Sistem Manajemen',
    'site_description' => 'Sistem Manajemen Terpadu'
];

// Check if database connection is available and working
if (!isset($db_connection_error) && $db_connected) {
    // Get system settings only if database connection is successful
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch(PDOException $e) {
        error_log('Error fetching settings: ' . $e->getMessage());
        // Keep using default settings if query fails
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name'] ?? 'Sistem Manajemen'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar .nav-link {
            color: #333;
        }
        .sidebar .nav-link.active {
            color: #0d6efd;
        }
        .sidebar .nav-link:hover {
            color: #0d6efd;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php if (isLoggedIn()): ?>
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php">
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                        <?php if (!isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../shop/catalog.php">
                                <i class="bi bi-shop"></i> Katalog Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../shop/cart.php">
                                <i class="bi bi-cart"></i> Keranjang
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../shop/history.php">
                                <i class="bi bi-clock-history"></i> Riwayat Transaksi
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/users.php">
                                <i class="bi bi-people"></i> Manajemen Pengguna
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/categories.php">
                                <i class="bi bi-tags"></i> Kategori
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/products.php">
                                <i class="bi bi-box"></i> Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/transactions.php">
                                <i class="bi bi-cart"></i> Transaksi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/reports.php">
                                <i class="bi bi-file-text"></i> Laporan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/settings.php">
                                <i class="bi bi-gear"></i> Pengaturan
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            <?php endif; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo htmlspecialchars($settings['site_name'] ?? 'Sistem Manajemen'); ?></h1>
                    <?php if (isLoggedIn()): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                                <li><a class="dropdown-item" href="../profile.php">Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
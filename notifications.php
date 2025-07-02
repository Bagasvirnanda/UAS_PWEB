<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Require login
requireLogin();

$success = $error = '';
$user_id = $_SESSION['user_id'];

// Handle marking notifications as read
if (isset($_POST['mark_read']) && !empty($_POST['notification_ids'])) {
    try {
        $ids = implode(',', array_map('intval', $_POST['notification_ids']));
        $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE id IN ($ids) AND user_id = ?");
        $stmt->execute([$user_id]);
        $success = 'Notifikasi berhasil ditandai sebagai telah dibaca';
    } catch(PDOException $e) {
        $error = 'Gagal memperbarui status notifikasi';
    }
}

// Handle deleting notifications
if (isset($_POST['delete']) && !empty($_POST['notification_ids'])) {
    try {
        $ids = implode(',', array_map('intval', $_POST['notification_ids']));
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id IN ($ids) AND user_id = ?");
        $stmt->execute([$user_id]);
        $success = 'Notifikasi berhasil dihapus';
    } catch(PDOException $e) {
        $error = 'Gagal menghapus notifikasi';
    }
}

// Get notifications with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($settings['items_per_page']) ? intval($settings['items_per_page']) : 10;
$offset = ($page - 1) * $limit;

// Get total notifications count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_notifications = $stmt->fetchColumn();
    $total_pages = ceil($total_notifications / $limit);
} catch(PDOException $e) {
    $error = 'Gagal mengambil jumlah notifikasi';
}

// Get notifications
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications 
                         WHERE user_id = ? 
                         ORDER BY created_at DESC 
                         LIMIT ? OFFSET ?");
    $stmt->execute([$user_id, $limit, $offset]);
    $notifications = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Gagal mengambil data notifikasi';
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Notifikasi</h2>
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
            <form method="POST" id="notificationForm">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="30px">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Pesan</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                                <tr class="<?php echo $notification['status'] === 'unread' ? 'table-light' : ''; ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input notification-checkbox" 
                                               name="notification_ids[]" value="<?php echo $notification['id']; ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $notification['status'] === 'unread' ? 'bg-primary' : 'bg-secondary'; ?>">
                                            <?php echo $notification['status'] === 'unread' ? 'Belum dibaca' : 'Sudah dibaca'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($notifications)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada notifikasi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <button type="submit" name="mark_read" class="btn btn-primary me-2" disabled>
                            <i class="bi bi-check-all"></i> Tandai Sudah Dibaca
                        </button>
                        <button type="submit" name="delete" class="btn btn-danger" disabled>
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle select all checkbox
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateButtonStates();
});

// Handle individual checkboxes
document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        updateButtonStates();
        
        // Update select all checkbox
        const allChecked = Array.from(document.querySelectorAll('.notification-checkbox'))
            .every(cb => cb.checked);
        document.getElementById('selectAll').checked = allChecked;
    });
});

// Update button states based on checkbox selection
function updateButtonStates() {
    const checkedBoxes = document.querySelectorAll('.notification-checkbox:checked').length;
    document.querySelector('button[name="mark_read"]').disabled = checkedBoxes === 0;
    document.querySelector('button[name="delete"]').disabled = checkedBoxes === 0;
}

// Confirm delete action
document.querySelector('button[name="delete"]').addEventListener('click', function(e) {
    if (!confirm('Apakah Anda yakin ingin menghapus notifikasi yang dipilih?')) {
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
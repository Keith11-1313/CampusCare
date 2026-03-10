<?php
/**
 * CampusCare - Archive Student Records (Admin)
 */
$pageTitle = 'Archived Records';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? ''))
        jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);
    $id = intval($_POST['id'] ?? 0);
    if ($_POST['action'] === 'archive') {
        $db->query("UPDATE students SET status='archived' WHERE id=?", [$id]);
        logAccess($_SESSION['user_id'], 'archive_student', "Archived student ID $id");
        jsonResponse(['success' => true, 'message' => 'Student archived.']);
    }
    if ($_POST['action'] === 'restore') {
        $db->query("UPDATE students SET status='active' WHERE id=?", [$id]);
        logAccess($_SESSION['user_id'], 'restore_student', "Restored student ID $id");
        jsonResponse(['success' => true, 'message' => 'Student restored.']);
    }
}

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$where = "WHERE s.status='archived'";
$params = [];
if (!empty($search)) {
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $sk = "%$search%";
    $params = [$sk, $sk, $sk];
}
$total = $db->fetchColumn("SELECT COUNT(*) FROM students s $where", $params);
$totalPages = ceil($total / $perPage);
$students = $db->fetchAll("SELECT s.*, p.code as program_code, yl.name as year_level_name FROM students s LEFT JOIN programs p ON s.program_id=p.id LEFT JOIN year_levels yl ON s.year_level_id=yl.id $where ORDER BY s.updated_at DESC LIMIT $perPage OFFSET $offset", $params);
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-archive me-2"></i>Archived Records</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Archive</li></ol></nav>
    </div>
</div>

<div class="filter-bar"><form method="GET" class="row g-2"><div class="col-md-5"><div class="search-box"><i class="bi bi-search search-icon"></i><input type="text" class="form-control" name="search" placeholder="Search archived students..." value="<?php echo e($search); ?>"></div></div><div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Search</button></div></form></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Year/Sec</th><th class="text-center">Action</th></tr></thead>
<tbody>
<?php if (empty($students)): ?><tr><td colspan="5" class="text-center text-muted py-4">No archived records.</td></tr>
<?php
else:
    foreach ($students as $s): ?>
<tr>
<td><code><?php echo e($s['student_id']); ?></code></td>
<td class="fw-semibold"><?php echo e($s['first_name'] . ' ' . $s['last_name']); ?></td>
<td><?php echo e($s['program_code'] ?? 'N/A'); ?></td>
<td><?php echo e(($s['year_level_name'] ?? '') . ' ' . ($s['section'] ?? '')); ?></td>
<td class="text-center"><button class="btn btn-sm btn-outline-success" onclick="restoreStudent(<?php echo $s['id']; ?>,'<?php echo e($s['student_id']); ?>')"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button></td>
</tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div>
<?php if ($totalPages > 1): ?><div class="card-footer bg-white"><?php echo generatePagination($page, $totalPages, 'archive.php?search=' . urlencode($search)); ?></div><?php
endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const CSRF_TOKEN = '<?php echo getCSRFToken(); ?>';

function restoreStudent(id, sid) {
    showConfirm('Restore Student?', 'Restore student ' + sid + ' to active records?', 'Yes, Restore', 'question').then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'restore');
            fd.append('id', id);
            fd.append('csrf_token', CSRF_TOKEN);
            fetch('archive.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                showToast(d.success ? 'success' : 'error', d.message);
                if (d.success) setTimeout(() => location.reload(), 800);
            });
        }
    });
}

function archiveStudent(id, sid) {
    showConfirm(
        'Archive Student?',
        'Archive student <strong>' + sid + '</strong>? They will be removed from active records but can be restored here.',
        'Yes, Archive',
        'warning'
    ).then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'archive');
            fd.append('id', id);
            fd.append('csrf_token', CSRF_TOKEN);
            fetch('archive.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                showToast(d.success ? 'success' : 'error', d.message);
            });
        }
    });
}
</script>

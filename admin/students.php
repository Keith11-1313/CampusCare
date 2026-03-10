<?php
/**
 * CampusCare - Student Records (Admin)
 * Browse all active students and archive them
 */
$pageTitle = 'Student Records';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

$search = trim($_GET['search'] ?? '');
$programFilter = $_GET['program'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE s.status='active'";
$params = [];
if (!empty($search)) {
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $sk = "%$search%";
    $params = array_merge($params, [$sk, $sk, $sk]);
}
if (!empty($programFilter)) {
    $where .= " AND s.program_id = ?";
    $params[] = $programFilter;
}

$total = $db->fetchColumn("SELECT COUNT(*) FROM students s $where", $params);
$totalPages = ceil($total / $perPage);
$students = $db->fetchAll(
    "SELECT s.*, p.code as program_code, yl.name as year_level_name
     FROM students s
     LEFT JOIN programs p ON s.program_id = p.id
     LEFT JOIN year_levels yl ON s.year_level_id = yl.id
     $where
     ORDER BY s.last_name, s.first_name
     LIMIT $perPage OFFSET $offset",
    $params
);
$programs = $db->fetchAll("SELECT * FROM programs WHERE status='active' ORDER BY code");

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-person-badge me-2"></i>Student Records</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Students</li></ol></nav>
    </div>
</div>

<div class="filter-bar"><form method="GET" class="row g-2 align-items-end">
<div class="col-md-5"><div class="search-box"><i class="bi bi-search search-icon"></i><input type="text" class="form-control" name="search" placeholder="Search by Student ID or Name..." value="<?php echo e($search); ?>" autofocus></div></div>
<div class="col-md-3"><select class="form-select" name="program"><option value="">All Programs</option><?php foreach ($programs as $p): ?><option value="<?php echo $p['id']; ?>" <?php echo $programFilter == $p['id'] ? 'selected' : ''; ?>><?php echo e($p['code']); ?></option><?php
endforeach; ?></select></div>
<div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i>Search</button></div>
<?php if ($search || $programFilter): ?><div class="col-md-2"><a href="students.php" class="btn btn-outline-secondary w-100">Clear</a></div><?php
endif; ?>
</form></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Year / Section</th><th>Gender</th><th>Blood Type</th><th class="text-center">Actions</th></tr></thead>
<tbody>
<?php if (empty($students)): ?><tr><td colspan="7" class="text-center text-muted py-4">No students found. Try a different search.</td></tr>
<?php
else:
    foreach ($students as $s): ?>
<tr>
<td><code><?php echo e($s['student_id']); ?></code></td>
<td class="fw-semibold"><?php echo e($s['first_name'] . ' ' . ($s['middle_name'] ? substr($s['middle_name'], 0, 1) . '. ' : '') . $s['last_name']); ?></td>
<td><?php echo e($s['program_code'] ?? 'N/A'); ?></td>
<td><?php echo e(($s['year_level_name'] ?? '') . ' ' . ($s['section'] ?? '')); ?></td>
<td><?php echo e($s['gender']); ?></td>
<td><?php echo e($s['blood_type'] ?? '—'); ?></td>
<td class="text-center">
    <button class="btn btn-sm btn-outline-danger" onclick="archiveStudent(<?php echo $s['id']; ?>, '<?php echo e($s['student_id']); ?>')">
        <i class="bi bi-archive me-1"></i>Archive
    </button>
</td>
</tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div>
<?php if ($totalPages > 1): ?><div class="card-footer bg-white"><?php echo generatePagination($page, $totalPages, 'students.php?search=' . urlencode($search) . '&program=' . urlencode($programFilter)); ?></div><?php
endif; ?>
</div>
<p class="text-muted small mt-2">Showing <?php echo count($students); ?> of <?php echo number_format($total); ?> active students.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const CSRF_TOKEN = '<?php echo getCSRFToken(); ?>';

function archiveStudent(id, sid) {
    showConfirm(
        'Archive Student?',
        'Archive student <strong>' + sid + '</strong>? They will be removed from active records but can be restored later.',
        'Yes, Archive',
        'warning'
    ).then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'archive');
            fd.append('id', id);
            fd.append('csrf_token', CSRF_TOKEN);
            fetch('<?php echo BASE_URL; ?>/admin/archive.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    showToast(d.success ? 'success' : 'error', d.message);
                    if (d.success) setTimeout(() => location.reload(), 800);
                });
        }
    });
}
</script>

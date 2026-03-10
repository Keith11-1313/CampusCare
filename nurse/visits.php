<?php
$pageTitle = 'Visit History';
require_once __DIR__ . '/../includes/header.php';
requireRole('nurse');
$db = Database::getInstance();

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
if (!empty($search)) {
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR v.complaint LIKE ?)";
    $sk = "%$search%";
    $params = array_merge($params, [$sk, $sk, $sk, $sk]);
}
if (!empty($statusFilter)) {
    $where .= " AND v.status = ?";
    $params[] = $statusFilter;
}
if (!empty($dateFrom)) {
    $where .= " AND DATE(v.visit_date) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $where .= " AND DATE(v.visit_date) <= ?";
    $params[] = $dateTo;
}

$total = $db->fetchColumn("SELECT COUNT(*) FROM visits v JOIN students s ON v.student_id=s.id $where", $params);
$totalPages = ceil($total / $perPage);
$visits = $db->fetchAll("SELECT v.*, s.student_id as sid, s.first_name, s.last_name, CONCAT(u.first_name,' ',u.last_name) as nurse_name FROM visits v JOIN students s ON v.student_id=s.id LEFT JOIN users u ON v.attended_by=u.id $where ORDER BY v.visit_date DESC LIMIT $perPage OFFSET $offset", $params);
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-clipboard2-pulse me-2"></i>Visit History</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Visits</li></ol></nav></div>

<div class="filter-bar"><form method="GET" class="row g-2 align-items-end">
<div class="col-md-4"><div class="search-box"><i class="bi bi-search search-icon"></i><input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo e($search); ?>"></div></div>
<div class="col-md-2"><select class="form-select" name="status"><option value="">All Status</option><option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option><option value="Follow-up" <?php echo $statusFilter === 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option><option value="Referred" <?php echo $statusFilter === 'Referred' ? 'selected' : ''; ?>>Referred</option></select></div>
<div class="col-md-2"><input type="date" class="form-control" name="date_from" value="<?php echo e($dateFrom); ?>"></div>
<div class="col-md-2"><input type="date" class="form-control" name="date_to" value="<?php echo e($dateTo); ?>"></div>
<div class="col-md-1 mt-1"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i>Filter</button></div>
<?php if ($search || $statusFilter || $dateFrom || $dateTo): ?><div class="col-md-1"><a href="visits.php" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg"></i></a></div><?php
endif; ?>
</form></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Date</th><th>Student</th><th>Complaint</th><th>Assessment</th><th>Treatment</th><th>Nurse</th><th>Status</th></tr></thead>
<tbody>
<?php if (empty($visits)): ?><tr><td colspan="7" class="text-center text-muted py-4">No visits found.</td></tr>
<?php
else:
    foreach ($visits as $v): ?>
<tr>
<td><small><?php echo formatDateTime($v['visit_date'], 'M d, h:i A'); ?></small></td>
<td><a href="student_profile.php?id=<?php echo $v['student_id']; ?>" class="fw-semibold text-decoration-none"><?php echo e($v['first_name'] . ' ' . $v['last_name']); ?></a><br><small class="text-muted"><?php echo e($v['sid']); ?></small></td>
<td><?php echo truncate($v['complaint'], 30); ?></td>
<td><small><?php echo truncate($v['assessment'] ?? '—', 30); ?></small></td>
<td><small><?php echo truncate($v['treatment'] ?? '—', 30); ?></small></td>
<td><small><?php echo e($v['nurse_name'] ?? '—'); ?></small></td>
<td><?php echo statusBadge($v['status']); ?></td>
</tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div>
<?php if ($totalPages > 1): ?><div class="card-footer bg-white"><?php echo generatePagination($page, $totalPages, 'visits.php?search=' . urlencode($search) . '&status=' . urlencode($statusFilter) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo)); ?></div><?php
endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

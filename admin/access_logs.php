<?php
$pageTitle = 'Access Logs';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

$search = trim($_GET['search'] ?? '');
$actionFilter = $_GET['action_filter'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
if (!empty($search)) {
    $where .= " AND (u.username LIKE ? OR al.description LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s]);
}
if (!empty($actionFilter)) {
    $where .= " AND al.action = ?";
    $params[] = $actionFilter;
}

$totalLogs = $db->fetchColumn("SELECT COUNT(*) FROM access_logs al LEFT JOIN users u ON al.user_id=u.id $where", $params);
$totalPages = ceil($totalLogs / $perPage);
$logs = $db->fetchAll("SELECT al.*, u.username, u.first_name, u.last_name FROM access_logs al LEFT JOIN users u ON al.user_id=u.id $where ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset", $params);
$actions = $db->fetchAll("SELECT DISTINCT action FROM access_logs ORDER BY action");
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-shield-check me-2"></i>Access Logs</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Access Logs</li></ol></nav></div>

<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-9">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo e($search); ?>">
            </div>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="action_filter">
                <option value="">All Actions</option><?php foreach ($actions as $a): ?>
                <option value="<?php echo e($a['action']); ?>" <?php echo $actionFilter === $a['action'] ? 'selected' : ''; ?>><?php echo e($a['action']); ?></option><?php
endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 mt-1">
            <?php if ($search || $actionFilter): ?>
                <a href="access_logs.php" class="btn btn-outline-secondary w-100">Clear</a>
            <?php
else: ?>
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            <?php
endif; ?>
        </div>
    </form>
</div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
<tbody>
<?php if (empty($logs)): ?><tr><td colspan="5" class="text-center text-muted py-4">No logs found.</td></tr>
<?php
else:
    foreach ($logs as $log): ?>
<tr>
<td><small><?php echo formatDateTime($log['created_at']); ?></small></td>
<td><?php echo $log['username'] ? e($log['first_name'] . ' ' . $log['last_name']) : '<small class="text-muted">System</small>'; ?></td>
<td><span class="badge bg-light text-dark"><?php echo e($log['action']); ?></span></td>
<td><small><?php echo e($log['description']); ?></small></td>
<td><code class="small"><?php echo e($log['ip_address']); ?></code></td>
</tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div>
<?php if ($totalPages > 1): ?><div class="card-footer bg-white"><?php echo generatePagination($page, $totalPages, 'access_logs.php?search=' . urlencode($search) . '&action_filter=' . urlencode($actionFilter)); ?></div><?php
endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

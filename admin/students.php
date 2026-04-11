<?php
$pageTitle = 'Student Records';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

$search = trim($_GET['search'] ?? '');
$programFilter = $_GET['program'] ?? '';
$yearLevelFilter = $_GET['year_level'] ?? '';
$sectionFilter = $_GET['section'] ?? '';
$genderFilter = $_GET['gender'] ?? '';
$sortColumns = ['student_id' => 's.student_id', 'name' => 's.last_name', 'program' => 'p.code', 'year_sec' => 's.year_level_id', 'gender' => 's.gender', 'blood_type' => 's.blood_type'];
$sort = (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortColumns)) ? $_GET['sort'] : 'name';
$order = (isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) ? $_GET['order'] : 'asc';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE s.status='active'";
$params = [];
if (!empty($search)) {
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', LEFT(s.middle_name, 1), '. ', s.last_name) LIKE ? OR p.code LIKE ? OR yl.name LIKE ? OR s.section LIKE ? OR s.gender LIKE ? OR s.blood_type LIKE ?)";
    $sk = "%$search%";
    $params = array_merge($params, [$sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk]);
}
if (!empty($programFilter)) {
    $where .= " AND s.program_id = ?";
    $params[] = $programFilter;
}
if (!empty($yearLevelFilter)) {
    $where .= " AND s.year_level_id = ?";
    $params[] = $yearLevelFilter;
}
if (!empty($sectionFilter)) {
    $where .= " AND s.section = ?";
    $params[] = $sectionFilter;
}
if (!empty($genderFilter)) {
    $where .= " AND s.gender = ?";
    $params[] = $genderFilter;
}

$total = $db->fetchColumn("SELECT COUNT(*) FROM students s LEFT JOIN programs p ON s.program_id = p.id LEFT JOIN year_levels yl ON s.year_level_id = yl.id $where", $params);
$totalPages = ceil($total / $perPage);
$students = $db->fetchAll(
    "SELECT s.*, p.code as program_code, yl.name as year_level_name
     FROM students s
     LEFT JOIN programs p ON s.program_id = p.id
     LEFT JOIN year_levels yl ON s.year_level_id = yl.id
     $where
     ORDER BY " . $sortColumns[$sort] . ' ' . ($order === 'asc' ? 'ASC' : 'DESC') . "
     LIMIT $perPage OFFSET $offset",
    $params
);
$programs = $db->fetchAll("SELECT * FROM programs WHERE status='active' ORDER BY code");
$yearLevels = $db->fetchAll("SELECT * FROM year_levels WHERE status='active' ORDER BY order_num");
$sections = $db->fetchAll("SELECT DISTINCT section FROM students WHERE status='active' AND section IS NOT NULL AND section != '' ORDER BY section");

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-person-badge me-2"></i>Student Records</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Students</li></ol></nav>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" name="search" placeholder="Search by records..." value="<?php echo e($search); ?>" autofocus>
            </div>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="program">
                <option value="">All Programs</option><?php foreach ($programs as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $programFilter == $p['id'] ? 'selected' : ''; ?>><?php echo e($p['code']); ?></option>
                <?php
endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="year_level">
                <option value="">All Year Levels</option><?php foreach ($yearLevels as $yl): ?>
                    <option value="<?php echo $yl['id']; ?>" <?php echo $yearLevelFilter == $yl['id'] ? 'selected' : ''; ?>><?php echo e($yl['name']); ?></option><?php
endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="section">
                <option value="">All Sections</option><?php foreach ($sections as $sec): ?>
                    <option value="<?php echo e($sec['section']); ?>" <?php echo $sectionFilter == $sec['section'] ? 'selected' : ''; ?>><?php echo e($sec['section']); ?></option><?php
endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="gender">
                <option value="">All Genders</option>
                <option value="Male" <?php echo $genderFilter === 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $genderFilter === 'Female' ? 'selected' : ''; ?>>Female</option>
            </select>
        </div>
        <div class="col-md-1 mt-1">
            <?php if ($search || $programFilter || $yearLevelFilter || $sectionFilter || $genderFilter): ?>
                <a href="students.php" class="btn btn-outline-secondary w-100">Clear</a>
            <?php
else: ?>
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            <?php
endif; ?>
        </div>
    </form>
</div>

<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><?php echo sortableHeader('Student ID', 'student_id', $sort, $order); ?><?php echo sortableHeader('Name', 'name', $sort, $order); ?><?php echo sortableHeader('Program', 'program', $sort, $order); ?><?php echo sortableHeader('Year / Section', 'year_sec', $sort, $order); ?><?php echo sortableHeader('Gender', 'gender', $sort, $order); ?><?php echo sortableHeader('Blood Type', 'blood_type', $sort, $order); ?><th class="text-center">Actions</th></tr></thead>
<tbody>
<?php if (empty($students)): ?><tr><td colspan="7" class="text-center text-muted py-4">No students found. Try a different search.</td></tr>
<?php
else:
    foreach ($students as $s): ?>
<tr>
<td><span class="font-monospace"><?php echo e($s['student_id']); ?></span></td>
<td class="fw-semibold"><?php echo e($s['first_name'] . ' ' . ($s['middle_name'] ? substr($s['middle_name'], 0, 1) . '. ' : '') . $s['last_name']); ?></td>
<td><?php echo e($s['program_code'] ?? 'N/A'); ?></td>
<td><?php echo e(($s['year_level_name'] ?? '') . ' ' . ($s['section'] ?? '')); ?></td>
<td><?php echo e($s['gender']); ?></td>
<td><?php echo e($s['blood_type'] ?? '—'); ?></td>
<td class="text-center">
    <button class="btn btn-sm btn-outline-danger" onclick="archiveStudent(<?php echo $s['id']; ?>, '<?php echo e($s['student_id']); ?>', '<?php echo e($s['first_name'] . ' ' . $s['last_name']); ?>')">
        <i class="bi bi-archive"></i>
    </button>
</td>
</tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div>
<?php if ($totalPages > 1): ?><div class="card-footer bg-white"><?php echo generatePagination($page, $totalPages, 'students.php?search=' . urlencode($search) . '&program=' . urlencode($programFilter) . '&year_level=' . urlencode($yearLevelFilter) . '&section=' . urlencode($sectionFilter) . '&gender=' . urlencode($genderFilter) . '&sort=' . urlencode($sort) . '&order=' . urlencode($order)); ?></div><?php
endif; ?>
</div>
<p class="text-muted small mt-2">Showing <?php echo count($students); ?> of <?php echo number_format($total); ?> active students.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const CSRF_TOKEN = '<?php echo getCSRFToken(); ?>';

function archiveStudent(id, sid, name) {
    showConfirm(
        'Archive Student?',
        'Archive <strong>' + name + '</strong> (' + sid + ')? They will be removed from active records but can be restored later.',
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
                    if (d.success) scheduleToast('success', d.message);
                    else showToast('error', d.message);
                });
        }
    });
}
</script>

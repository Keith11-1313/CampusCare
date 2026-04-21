<?php
$pageTitle = 'Visit History';
require_once __DIR__ . '/../includes/header.php';
requireRole('nurse');
$db = Database::getInstance();

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sortColumns = ['visit_date'=>'v.visit_date','student'=>'s.last_name','category'=>'v.complaint_category','complaint'=>'v.complaint','assessment'=>'v.assessment','treatment'=>'v.treatment','nurse'=>'nurse_name','status'=>'v.status'];
$sort = (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortColumns)) ? $_GET['sort'] : 'visit_date';
$order = (isset($_GET['order']) && in_array($_GET['order'], ['asc','desc'])) ? $_GET['order'] : 'desc';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
if (!empty($search)) {
<<<<<<< HEAD
<<<<<<< HEAD
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', LEFT(s.middle_name, 1), '. ', s.last_name) LIKE ? OR v.complaint_category LIKE ? OR v.complaint LIKE ?)";
    $sk = "%$search%";
    $params = array_merge($params, [$sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk]);
=======
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR v.complaint_category LIKE ? OR v.complaint LIKE ?)";
    $sk = "%$search%";
    $params = array_merge($params, [$sk, $sk, $sk, $sk, $sk]);
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
=======
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR v.complaint_category LIKE ? OR v.complaint LIKE ?)";
    $sk = "%$search%";
    $params = array_merge($params, [$sk, $sk, $sk, $sk, $sk]);
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
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
$orderSql = $sortColumns[$sort] . ' ' . ($order === 'asc' ? 'ASC' : 'DESC');
$visits = $db->fetchAll("SELECT v.*, s.student_id as sid, s.first_name, s.last_name, CONCAT(u.first_name,' ',u.last_name) as nurse_name FROM visits v JOIN students s ON v.student_id=s.id LEFT JOIN users u ON v.attended_by=u.id $where ORDER BY $orderSql LIMIT $perPage OFFSET $offset", $params);

// HIPAA §164.312(b): Log PHI access — record who viewed visit history
logAccess($_SESSION['user_id'], 'view_visit_history', 'Viewed visit history (page ' . $page . ')' . (!empty($search) ? ' search: ' . $search : ''));

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-clipboard2-pulse me-2"></i>Visit History</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Visits</li></ol></nav></div>

<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo e($search); ?>">
            </div>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="Follow-up" <?php echo $statusFilter === 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                <option value="Referred" <?php echo $statusFilter === 'Referred' ? 'selected' : ''; ?>>Referred</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="date_from" value="<?php echo e($dateFrom); ?>">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="date_to" value="<?php echo e($dateTo); ?>">
        </div>
        <div class="col-md-1 mt-1">
            <?php if ($search || $statusFilter || $dateFrom || $dateTo): ?>
                <a href="visits.php" class="btn btn-outline-secondary w-100">Clear</a>
            <?php
else: ?>
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            <?php
endif; ?>
        </div>
</form></div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <?php echo sortableHeader('Date', 'visit_date', $sort, $order); ?>
                            <?php echo sortableHeader('Student', 'student', $sort, $order); ?>
                            <?php echo sortableHeader('Category', 'category', $sort, $order); ?>
                            <?php echo sortableHeader('Complaint', 'complaint', $sort, $order); ?>
                            <?php echo sortableHeader('Assessment', 'assessment', $sort, $order); ?>
                            <?php echo sortableHeader('Treatment', 'treatment', $sort, $order); ?>
                            <?php echo sortableHeader('Nurse', 'nurse', $sort, $order); ?>
                            <?php echo sortableHeader('Status', 'status', $sort, $order); ?>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if (empty($visits)): ?><tr><td colspan="9" class="text-center text-muted py-4">No visits found.</td></tr>
<?php
else:
    foreach ($visits as $v): ?>
<tr>
<td><small><?php echo formatDateTime($v['visit_date'], 'M d, h:i A'); ?></small></td>
<td><a href="student_profile.php?id=<?php echo $v['student_id']; ?>" class="fw-semibold text-decoration-none"><?php echo e($v['first_name'] . ' ' . $v['last_name']); ?></a><br><small class="text-muted"><?php echo e($v['sid']); ?></small></td>
<td><?php echo e($v['complaint_category']); ?></td>
<td><small><?php echo truncate($v['complaint'] ?? '—'); ?></small></td>
<td><small><?php echo truncate($v['assessment'] ?? '—'); ?></small></td>
<td><small><?php echo truncate($v['treatment'] ?? '—'); ?></small></td>
<td><small><?php echo e($v['nurse_name'] ?? '—'); ?></small></td>
<td><?php echo statusBadge($v['status']); ?></td>
<td class="text-center">
<button type="button" class="btn btn-sm btn-primary view-visit-btn"
    data-date="<?php echo e(formatDateTime($v['visit_date'], 'M d, Y h:i A')); ?>"
    data-student="<?php echo e($v['first_name'] . ' ' . $v['last_name']); ?>"
    data-sid="<?php echo e($v['sid']); ?>"
    data-category="<?php echo e($v['complaint_category']); ?>"
    data-complaint="<?php echo e($v['complaint'] ?? '—'); ?>"
    data-assessment="<?php echo e($v['assessment'] ?? '—'); ?>"
    data-treatment="<?php echo e($v['treatment'] ?? '—'); ?>"
    data-nurse="<?php echo e($v['nurse_name'] ?? '—'); ?>"
    data-status="<?php echo e($v['status']); ?>"
    data-bp="<?php echo e($v['blood_pressure'] ?? ''); ?>"
    data-temp="<?php echo e($v['temperature'] ?? ''); ?>"
    data-pulse="<?php echo e($v['pulse_rate'] ?? ''); ?>"
    data-rr="<?php echo e($v['respiratory_rate'] ?? ''); ?>"
    data-weight="<?php echo e($v['weight'] ?? ''); ?>"
    data-height="<?php echo e($v['height'] ?? ''); ?>"
    data-followup="<?php echo e($v['follow_up_notes'] ?? ''); ?>"
    data-followupdate="<?php echo e($v['follow_up_date'] ?? ''); ?>"
    data-studentid="<?php echo $v['student_id']; ?>"
><i class="bi bi-eye me-1"></i>View</button>
</td>
</tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div>
<?php if ($totalPages > 1): ?><div class="card-footer bg-white"><?php echo generatePagination($page, $totalPages, 'visits.php?search=' . urlencode($search) . '&status=' . urlencode($statusFilter) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&sort=' . urlencode($sort) . '&order=' . urlencode($order)); ?></div><?php
endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Visit Detail Modal -->
<div class="modal fade" id="visitDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard2-pulse me-2"></i>Visit Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-0" id="mdStudentName"></h6>
                        <small class="text-muted" id="mdStudentId"></small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted" id="mdDate"></small><br>
                        <span id="mdStatus"></span>
                    </div>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-heart-pulse me-2"></i>Vital Signs</h6>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Blood Pressure</small>
                            <span class="fw-semibold" id="mdBp">—</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Temperature</small>
                            <span class="fw-semibold" id="mdTemp">—</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Pulse Rate</small>
                            <span class="fw-semibold" id="mdPulse">—</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Respiratory Rate</small>
                            <span class="fw-semibold" id="mdRr">—</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Weight</small>
                            <span class="fw-semibold" id="mdWeight">—</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Height</small>
                            <span class="fw-semibold" id="mdHeight">—</span>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-journal-text me-2"></i>Clinical Notes</h6>
                <div class="mb-2"><strong>Category:</strong> <span id="mdCategory"></span></div>
                <div class="mb-2"><strong>Complaint:</strong><br><span id="mdComplaint" class="text-muted"></span></div>
                <div class="mb-2"><strong>Assessment:</strong><br><span id="mdAssessment" class="text-muted"></span></div>
                <div class="mb-2"><strong>Treatment:</strong><br><span id="mdTreatment" class="text-muted"></span></div>

                <div id="mdFollowupSection" style="display:none;">
                    <h6 class="fw-bold mb-2 mt-3"><i class="bi bi-calendar-event me-2"></i>Follow-up</h6>
                    <div class="mb-1"><strong>Date:</strong> <span id="mdFollowupDate"></span></div>
                    <div><strong>Notes:</strong> <span id="mdFollowupNotes" class="text-muted"></span></div>
                </div>

                <div class="mt-3"><small class="text-muted"><i class="bi bi-person me-1"></i>Attended by: <span id="mdNurse"></span></small></div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-primary" id="mdViewProfile"><i class="bi bi-eye me-1"></i>View Profile</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const visitModal = new bootstrap.Modal(document.getElementById('visitDetailModal'));

document.querySelectorAll('.view-visit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const d = this.dataset;
        document.getElementById('mdStudentName').textContent = d.student;
        document.getElementById('mdStudentId').textContent = d.sid;
        document.getElementById('mdDate').textContent = d.date;
        document.getElementById('mdCategory').textContent = d.category;
        document.getElementById('mdComplaint').textContent = d.complaint;
        document.getElementById('mdAssessment').textContent = d.assessment;
        document.getElementById('mdTreatment').textContent = d.treatment;
        document.getElementById('mdNurse').textContent = d.nurse;

        // Status badge
        const colors = { 'Completed': 'success', 'Follow-up': 'warning', 'Referred': 'info' };
        document.getElementById('mdStatus').innerHTML = `<span class="badge bg-${colors[d.status] || 'secondary'}">${d.status}</span>`;

        // Vital signs
        document.getElementById('mdBp').textContent = d.bp || '—';
        document.getElementById('mdTemp').textContent = d.temp ? d.temp + ' °C' : '—';
        document.getElementById('mdPulse').textContent = d.pulse ? d.pulse + ' bpm' : '—';
        document.getElementById('mdRr').textContent = d.rr ? d.rr + ' /min' : '—';
        document.getElementById('mdWeight').textContent = d.weight ? d.weight + ' kg' : '—';
        document.getElementById('mdHeight').textContent = d.height ? d.height + ' cm' : '—';

        // Follow-up
        const hasFollowup = d.followupdate || d.followup;
        document.getElementById('mdFollowupSection').style.display = hasFollowup ? '' : 'none';
        document.getElementById('mdFollowupDate').textContent = d.followupdate || '—';
        document.getElementById('mdFollowupNotes').textContent = d.followup || '—';

        // Profile link
        document.getElementById('mdViewProfile').href = 'student_profile.php?id=' + d.studentid;

        visitModal.show();
    });
});
</script>


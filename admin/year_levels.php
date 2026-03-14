<?php
$pageTitle = 'Year Levels';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? ''))
        jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);
    $action = $_POST['action'];
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (empty($name))
            jsonResponse(['success' => false, 'message' => 'Name is required.']);
        if ($id > 0) {
            $db->query("UPDATE year_levels SET name=? WHERE id=?", [$name, $id]);
        }
        else {
            $db->query("INSERT INTO year_levels (name) VALUES (?)", [$name]);
        }
        jsonResponse(['success' => true, 'message' => 'Year level saved.']);
    }
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $cur = $db->fetchColumn("SELECT status FROM year_levels WHERE id=?", [$id]);
        $db->query("UPDATE year_levels SET status=? WHERE id=?", [$cur === 'active' ? 'inactive' : 'active', $id]);
        jsonResponse(['success' => true, 'message' => 'Status updated.']);
    }
    if ($action === 'get') {
        $yl = $db->fetch("SELECT * FROM year_levels WHERE id=?", [intval($_POST['id'] ?? 0)]);
        jsonResponse(['success' => true, 'year_level' => $yl]);
    }
}
$yearLevels = $db->fetchAll("SELECT yl.*, (SELECT COUNT(*) FROM students s WHERE s.year_level_id=yl.id AND s.status='active') as student_count FROM year_levels yl ORDER BY yl.order_num");
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap">
    <div>
        <h1><i class="bi bi-layers me-2"></i>Year Levels</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Year Levels</li></ol></nav>
    </div>
    <button class="btn btn-primary" onclick="openModal()"><i class="bi bi-plus-lg me-1"></i>Add Year Level</button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th style="width:35%">Name</th><th style="width:35%">Students</th><th style="width:15%">Status</th><th style="width:15%" class="text-center">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($yearLevels)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No year levels found.</td></tr>
                    <?php
else: ?>
                    <?php foreach ($yearLevels as $yl): ?>
                    <tr>

                        <td class="fw-semibold"><?php echo e($yl['name']); ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo $yl['student_count']; ?></span></td>
                        <td><?php echo statusBadge($yl['status']); ?></td>
                        <td class="text-center table-action-btns">
                            <button class="btn btn-sm btn-outline-primary btn-icon" onclick="editYL(<?php echo $yl['id']; ?>)"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-<?php echo $yl['status'] === 'active' ? 'warning' : 'success'; ?> btn-icon" onclick="toggleStatus(<?php echo $yl['id']; ?>, '<?php echo $yl['status']; ?>')"><i class="bi bi-<?php echo $yl['status'] === 'active' ? 'eye-slash' : 'eye'; ?>"></i></button>
                        </td>
                    </tr>
                    <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="ylModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="ylModalTitle">Add Year Level</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form id="ylForm">
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <input type="hidden" name="action" value="save"><input type="hidden" name="id" id="ylId" value="0">
            <div class="mb-3"><label class="form-label">Name <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="name" id="ylName" required placeholder="e.g. 1st Year"></div>

        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button></div>
    </form>
</div></div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    const ylModal = new bootstrap.Modal(document.getElementById('ylModal'));
    const ylTable = document.getElementById('ylTable');

    function openModal() {
        document.getElementById('ylModalTitle').textContent = 'Add Year Level';
        document.getElementById('ylId').value = 0;
        document.getElementById('ylForm').reset();
        ylModal.show();
    }

    function editYL(id) {
        const fd = new FormData();
        fd.append('action', 'get');
        fd.append('id', id);
        fd.append('csrf_token', '<?php echo getCSRFToken(); ?>');

        fetch('year_levels.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('ylModalTitle').textContent = 'Edit Year Level';
                    document.getElementById('ylId').value = d.year_level.id;
                    document.getElementById('ylName').value = d.year_level.name;
                    ylModal.show();
                }
            });
    }

    function toggleStatus(id, currentStatus) {
        const isActive = currentStatus === 'active';
        const title = isActive ? 'Deactivate Year Level?' : 'Reactivate Year Level?';
        const message = isActive
            ? 'This year level will be hidden from selection forms.'
            : 'This year level will be available in selection forms again.';
        const confirmBtn = isActive ? 'Yes, Deactivate' : 'Yes, Reactivate';

        showConfirm(title, message, confirmBtn)
            .then(r => {
                if (r.isConfirmed) {
                    const fd = new FormData();
                    fd.append('action', 'toggle_status');
                    fd.append('id', id);
                    fd.append('csrf_token', '<?php echo getCSRFToken(); ?>');

                    fetch('year_levels.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) scheduleToast('success', d.message);
                            else showToast('error', d.message);
                        });
                }
            });
    }

    document.getElementById('ylForm').addEventListener('submit', function(e) {
        e.preventDefault();

        fetch('year_levels.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    ylModal.hide();
                    scheduleToast('success', d.message);
                } else {
                    showToast('error', d.message);
                }
            });
    });
</script>

<?php
$pageTitle = 'Programs Management';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$db = Database::getInstance();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
    }

    $action = $_POST['action'];

    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');

        if (empty($code) || empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Code and name are required.']);
        }

        // Check uniqueness for code
        $existingCode = $db->fetch("SELECT id FROM programs WHERE code = ? AND id != ?", [$code, $id]);
        if ($existingCode) {
            jsonResponse(['success' => false, 'message' => 'Program code already exists.']);
        }

        // Check uniqueness for name
        $existingName = $db->fetch("SELECT id FROM programs WHERE name = ? AND id != ?", [$name, $id]);
        if ($existingName) {
            jsonResponse(['success' => false, 'message' => 'Program name already exists.']);
        }

        if ($id > 0) {
            $db->query("UPDATE programs SET code = ?, name = ? WHERE id = ?", [$code, $name, $id]);
            logAccess($_SESSION['user_id'], 'update_program', "Updated program: $code");
        }
        else {
            $db->query("INSERT INTO programs (code, name) VALUES (?, ?)", [$code, $name]);
            logAccess($_SESSION['user_id'], 'create_program', "Created program: $code");
        }
        jsonResponse(['success' => true, 'message' => 'Program saved successfully.']);
    }

    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $currentStatus = $db->fetchColumn("SELECT status FROM programs WHERE id = ?", [$id]);
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
        $db->query("UPDATE programs SET status = ? WHERE id = ?", [$newStatus, $id]);
        logAccess($_SESSION['user_id'], 'toggle_program', "Program ID $id status changed to $newStatus");
        jsonResponse(['success' => true, 'message' => 'Status updated.']);
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $program = $db->fetch("SELECT * FROM programs WHERE id = ?", [$id]);
        jsonResponse(['success' => true, 'program' => $program]);
    }
}

$programs = $db->fetchAll("SELECT p.*, (SELECT COUNT(*) FROM students s WHERE s.program_id = p.id AND s.status = 'active') as student_count FROM programs p ORDER BY p.code");

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap">
    <div>
        <h1><i class="bi bi-mortarboard me-2"></i>Programs</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Programs</li></ol>
        </nav>
    </div>
    <button class="btn btn-primary" onclick="openProgramModal()"><i class="bi bi-plus-lg me-1"></i>Add Program</button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th class="sortable-th" data-col="0"><a href="#">Code <i class="bi bi-chevron-expand sort-icon-idle"></i></a></th><th class="sortable-th" data-col="1"><a href="#">Program Name <i class="bi bi-chevron-expand sort-icon-idle"></i></a></th><th class="sortable-th" data-col="2"><a href="#">Students <i class="bi bi-chevron-expand sort-icon-idle"></i></a></th><th class="sortable-th" data-col="3"><a href="#">Status <i class="bi bi-chevron-expand sort-icon-idle"></i></a></th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($programs)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No programs found.</td></tr>
                    <?php
else: ?>
                    <?php foreach ($programs as $p): ?>
                    <tr>
                        <td><code class="fw-bold"><?php echo e($p['code']); ?></code></td>
                        <td><?php echo e($p['name']); ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo $p['student_count']; ?></span></td>
                        <td><?php echo statusBadge($p['status']); ?></td>
                        <td class="text-center table-action-btns">
                            <button class="btn btn-sm btn-outline-primary btn-icon" onclick="editProgram(<?php echo $p['id']; ?>)"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-<?php echo $p['status'] === 'active' ? 'warning' : 'success'; ?> btn-icon" 
                                    onclick="toggleProgramStatus(<?php echo $p['id']; ?>, '<?php echo $p['status']; ?>')">
                                <i class="bi bi-<?php echo $p['status'] === 'active' ? 'eye-slash' : 'eye'; ?>"></i>
                            </button>
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

<!-- Program Modal -->
<div class="modal fade" id="programModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="programModalTitle">Add Program</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="programForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="programId" value="0">
                    <div class="mb-3">
                        <label class="form-label">Program Code <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="code" id="programCode" required placeholder="e.g. BSIT" style="text-transform:uppercase;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="name" id="programName" required placeholder="e.g. Bachelor of Science in Information Technology">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const programModal = new bootstrap.Modal(document.getElementById('programModal'));

function openProgramModal() {
    document.getElementById('programModalTitle').textContent = 'Add Program';
    document.getElementById('programId').value = 0;
    document.getElementById('programForm').reset();
    programModal.show();
}

function editProgram(id) {
    const fd = new FormData();
    fd.append('action', 'get'); fd.append('id', id); fd.append('csrf_token', '<?php echo getCSRFToken(); ?>');
    fetch('programs.php', {method:'POST', body:fd}).then(r=>r.json()).then(d => {
        if (d.success) {
            document.getElementById('programModalTitle').textContent = 'Edit Program';
            document.getElementById('programId').value = d.program.id;
            document.getElementById('programCode').value = d.program.code;
            document.getElementById('programName').value = d.program.name;
            programModal.show();
        }
    });
}

function toggleProgramStatus(id, currentStatus) {
    const isActive = currentStatus === 'active';
    const title = isActive ? 'Deactivate Program?' : 'Reactivate Program?';
    const message = isActive
        ? 'This program will be hidden from selection forms.'
        : 'This program will be available in selection forms again.';
    const confirmBtn = isActive ? 'Yes, Deactivate' : 'Yes, Reactivate';

    showConfirm(title, message, confirmBtn).then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'toggle_status'); fd.append('id', id); fd.append('csrf_token', '<?php echo getCSRFToken(); ?>');
            fetch('programs.php', {method:'POST', body:fd}).then(r=>r.json()).then(d => {
                if (d.success) scheduleToast('success', d.message);
                else showToast('error', d.message);
            });
        }
    });
}

document.getElementById('programForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('programs.php', {method:'POST', body: new FormData(this)}).then(r=>r.json()).then(d => {
        if (d.success) { programModal.hide(); scheduleToast('success', d.message); }
        else showToast('error', d.message);
    });
});

// Client-side table sorting
(function() {
    const table = document.querySelector('.table');
    const headers = table.querySelectorAll('th.sortable-th');
    let currentSort = { col: -1, asc: true };
    headers.forEach(th => {
        th.addEventListener('click', function(e) {
            e.preventDefault();
            const col = parseInt(this.dataset.col);
            const asc = currentSort.col === col ? !currentSort.asc : true;
            currentSort = { col, asc };
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const aVal = (a.cells[col]?.textContent || '').trim().toLowerCase();
                const bVal = (b.cells[col]?.textContent || '').trim().toLowerCase();
                const aNum = parseFloat(aVal), bNum = parseFloat(bVal);
                if (!isNaN(aNum) && !isNaN(bNum)) return asc ? aNum - bNum : bNum - aNum;
                return asc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });
            rows.forEach(r => tbody.appendChild(r));
            headers.forEach(h => {
                h.classList.remove('sortable-active');
                const icon = h.querySelector('i');
                icon.className = 'bi bi-chevron-expand sort-icon-idle';
            });
            this.classList.add('sortable-active');
            const icon = this.querySelector('i');
            icon.className = asc ? 'bi bi-caret-up-fill sort-icon' : 'bi bi-caret-down-fill sort-icon';
        });
    });
})();
</script>

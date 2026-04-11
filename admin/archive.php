<?php
$pageTitle = 'Archived Records';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db = Database::getInstance();

// Determine active tab
$activeTab = $_GET['tab'] ?? 'students';
if (!in_array($activeTab, ['students', 'users', 'programs']))
    $activeTab = 'students';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? ''))
        jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);
    $id = intval($_POST['id'] ?? 0);

    // Student actions
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

    // User actions
    if ($_POST['action'] === 'activate_user') {
        $db->query("UPDATE users SET status='active', deactivation_reason=NULL WHERE id=?", [$id]);
        logAccess($_SESSION['user_id'], 'activate_user', "Reactivated user ID $id from archive");
        jsonResponse(['success' => true, 'message' => 'User account reactivated.']);
    }

    // Program actions
    if ($_POST['action'] === 'activate_program') {
        $db->query("UPDATE programs SET status='active' WHERE id=?", [$id]);
        logAccess($_SESSION['user_id'], 'activate_program', "Reactivated program ID $id from archive");
        jsonResponse(['success' => true, 'message' => 'Program reactivated.']);
    }
}

// ── Students Tab Data ──
$search = trim($_GET['search'] ?? '');
$programFilter = $_GET['program'] ?? '';
$yearLevelFilter = $_GET['year_level'] ?? '';
$sectionFilter = $_GET['section'] ?? '';
$genderFilter = $_GET['gender'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$sortColumns = ['student_id' => 's.student_id', 'name' => 's.last_name', 'program' => 'p.code', 'year_sec' => 's.year_level_id'];
$sort = (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortColumns)) ? $_GET['sort'] : 'name';
$order = (isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) ? $_GET['order'] : 'desc';
$where = "WHERE s.status='archived'";
$params = [];
if (!empty($search)) {
    $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR p.code LIKE ? OR yl.name LIKE ? OR s.section LIKE ?)";
    $sk = "%$search%";
    $params = [$sk, $sk, $sk, $sk, $sk, $sk];
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
$totalStudents = $db->fetchColumn("SELECT COUNT(*) FROM students s LEFT JOIN programs p ON s.program_id=p.id LEFT JOIN year_levels yl ON s.year_level_id=yl.id $where", $params);
$totalStudentPages = ceil($totalStudents / $perPage);
$orderSql = $sortColumns[$sort] . ' ' . ($order === 'asc' ? 'ASC' : 'DESC');
$students = $db->fetchAll("SELECT s.*, p.code as program_code, yl.name as year_level_name FROM students s LEFT JOIN programs p ON s.program_id=p.id LEFT JOIN year_levels yl ON s.year_level_id=yl.id $where ORDER BY $orderSql LIMIT $perPage OFFSET $offset", $params);
$programs = $db->fetchAll("SELECT * FROM programs ORDER BY code");
$yearLevels = $db->fetchAll("SELECT * FROM year_levels WHERE status='active' ORDER BY order_num");
$sections = $db->fetchAll("SELECT DISTINCT section FROM students WHERE status='archived' AND section IS NOT NULL AND section != '' ORDER BY section");

// ── Users Tab Data ──
$userSearch = trim($_GET['user_search'] ?? '');
$userRoleFilter = $_GET['user_role'] ?? '';
$userPage = ($activeTab === 'users') ? $page : 1;
$userOffset = ($userPage - 1) * $perPage;
$userSortColumns = ['user' => 'last_name', 'username' => 'username', 'role' => 'role', 'deactivated' => 'updated_at'];
$userSort = (isset($_GET['sort']) && array_key_exists($_GET['sort'], $userSortColumns)) ? $_GET['sort'] : 'deactivated';
$userOrder = (isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) ? $_GET['order'] : 'desc';
$userWhere = "WHERE status='inactive'";
$userParams = [];
if (!empty($userSearch)) {
    $userWhere .= " AND (username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $usk = "%$userSearch%";
    $userParams = [$usk, $usk, $usk, $usk];
}
if (!empty($userRoleFilter)) {
    $userWhere .= " AND role = ?";
    $userParams[] = $userRoleFilter;
}
$totalUsers = $db->fetchColumn("SELECT COUNT(*) FROM users $userWhere", $userParams);
$totalUserPages = ceil($totalUsers / $perPage);
$userOrderSql = $userSortColumns[$userSort] . ' ' . ($userOrder === 'asc' ? 'ASC' : 'DESC');
$inactiveUsers = $db->fetchAll("SELECT * FROM users $userWhere ORDER BY $userOrderSql LIMIT $perPage OFFSET $userOffset", $userParams);

// ── Programs Tab Data ──
$progSortColumns = ['code' => 'p.code', 'name' => 'p.name', 'students' => 'student_count'];
$progSort = (isset($_GET['sort']) && array_key_exists($_GET['sort'], $progSortColumns)) ? $_GET['sort'] : 'code';
$progOrder = (isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) ? $_GET['order'] : 'asc';
$progOrderSql = $progSortColumns[$progSort] . ' ' . ($progOrder === 'asc' ? 'ASC' : 'DESC');
$inactivePrograms = $db->fetchAll("SELECT p.*, (SELECT COUNT(*) FROM students s WHERE s.program_id=p.id) as student_count FROM programs p WHERE p.status='inactive' ORDER BY $progOrderSql");


require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-archive me-2"></i>Archived Records</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Archived Records</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Category Tabs -->
<ul class="nav nav-tabs mb-4" id="archiveTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $activeTab === 'students' ? 'active' : ''; ?>" href="archive.php?tab=students"
            id="students-tab">
            <i class="bi bi-person-badge me-1"></i>Students
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $activeTab === 'users' ? 'active' : ''; ?>" href="archive.php?tab=users"
            id="users-tab">
            <i class="bi bi-people me-1"></i>User Accounts
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $activeTab === 'programs' ? 'active' : ''; ?>" href="archive.php?tab=programs"
            id="programs-tab">
            <i class="bi bi-mortarboard me-1"></i>Programs
        </a>
    </li>
</ul>

<!-- ═══════════════════════════════════════════ -->
<!-- STUDENTS TAB -->
<!-- ═══════════════════════════════════════════ -->
<?php if ($activeTab === 'students'): ?>
    <div class="filter-bar">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="students">
            <div class="col-md-3">
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="form-control" name="search" placeholder="Search archive..."
                        value="<?php echo e($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="program">
                    <option value="">All Programs</option><?php foreach ($programs as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $programFilter == $p['id'] ? 'selected' : ''; ?>>
                            <?php echo e($p['code']); ?>
                        </option>
                        <?php
                    endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><select class="form-select" name="year_level">
                    <option value="">All Year Levels</option><?php foreach ($yearLevels as $yl): ?>
                        <option value="<?php echo $yl['id']; ?>" <?php echo $yearLevelFilter == $yl['id'] ? 'selected' : ''; ?>>
                            <?php echo e($yl['name']); ?>
                        </option>
                        <?php
                    endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><select class="form-select" name="section">
                    <option value="">All Sections</option><?php foreach ($sections as $sec): ?>
                        <option value="<?php echo e($sec['section']); ?>" <?php echo $sectionFilter == $sec['section'] ? 'selected' : ''; ?>><?php echo e($sec['section']); ?></option>
                        <?php
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
                    <a href="archive.php?tab=students" class="btn btn-outline-secondary w-100">Clear</a>
                    <?php
                else: ?>
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <?php
                endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><?php echo sortableHeader('Student ID', 'student_id', $sort, $order); ?><?php echo sortableHeader('Name', 'name', $sort, $order); ?><?php echo sortableHeader('Program', 'program', $sort, $order); ?><?php echo sortableHeader('Year/Sec', 'year_sec', $sort, $order); ?>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No archived students.</td>
                            </tr>
                            <?php
                        else:
                            foreach ($students as $s): ?>
                                <tr>
                                    <td><span class="font-monospace"><?php echo e($s['student_id']); ?></span></td>
                                    <td class="fw-semibold"><?php echo e($s['first_name'] . ' ' . $s['last_name']); ?></td>
                                    <td><?php echo e($s['program_code'] ?? 'N/A'); ?></td>
                                    <td><?php echo e(($s['year_level_name'] ?? '') . ' ' . ($s['section'] ?? '')); ?></td>
                                    <td class="text-center"><button class="btn btn-sm btn-outline-success"
                                            onclick="restoreStudent(<?php echo $s['id']; ?>,'<?php echo e($s['student_id']); ?>','<?php echo e($s['first_name'] . ' ' . $s['last_name']); ?>')"><i
                                                class="bi bi-arrow-counterclockwise"></i></button></td>
                                </tr>
                                <?php
                            endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalStudentPages > 1): ?>
            <div class="card-footer bg-white">
                <?php echo generatePagination($page, $totalStudentPages, 'archive.php?tab=students&search=' . urlencode($search) . '&program=' . urlencode($programFilter) . '&year_level=' . urlencode($yearLevelFilter) . '&section=' . urlencode($sectionFilter) . '&gender=' . urlencode($genderFilter) . '&sort=' . urlencode($sort) . '&order=' . urlencode($order)); ?>
            </div><?php
        endif; ?>
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════ -->
<!-- USER ACCOUNTS TAB -->
<!-- ═══════════════════════════════════════════ -->
<?php if ($activeTab === 'users'): ?>
    <div class="filter-bar">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="users">
            <div class="col-md-9">
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="form-control" name="user_search" placeholder="Search inactive users..."
                        value="<?php echo e($userSearch); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="user_role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $userRoleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="nurse" <?php echo $userRoleFilter === 'nurse' ? 'selected' : ''; ?>>Nurse/Staff</option>
                    <option value="rep" <?php echo $userRoleFilter === 'rep' ? 'selected' : ''; ?>>Class Representative
                    </option>
                </select>
            </div>
            <div class="col-md-1 mt-1">
                <?php if ($userSearch || $userRoleFilter): ?>
                    <a href="archive.php?tab=users" class="btn btn-outline-secondary w-100">Clear</a>
                <?php else: ?>
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <?php echo sortableHeader('User', 'user', $userSort, $userOrder); ?>
                            <?php echo sortableHeader('Username', 'username', $userSort, $userOrder); ?>
                            <?php echo sortableHeader('Role', 'role', $userSort, $userOrder); ?>
                            <th>Reason</th>
                            <?php echo sortableHeader('Deactivated', 'deactivated', $userSort, $userOrder); ?>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inactiveUsers)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No inactive user accounts.</td>
                            </tr>
                        <?php else:
                            foreach ($inactiveUsers as $u): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($u['first_name'] . ' ' . $u['last_name']); ?></div>
                                        <small class="text-muted"><?php echo e($u['email']); ?></small>
                                    </td>
                                    <td><code><?php echo e($u['username']); ?></code></td>
                                    <td><?php echo getRoleDisplayName($u['role']); ?></td>
                                    <td><small class="text-muted"><?php echo e($u['deactivation_reason'] ?? '—'); ?></small></td>
                                    <td><small><?php echo formatDateTime($u['updated_at'], 'M d, Y'); ?></small></td>
                                    <td class="text-center">
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-outline-success"
                                                onclick="activateUser(<?php echo $u['id']; ?>, '<?php echo e($u['username']); ?>', '<?php echo e($u['first_name'] . ' ' . $u['last_name']); ?>')"
                                                title="Reactivate">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalUserPages > 1): ?>
            <div class="card-footer bg-white">
                <?php echo generatePagination($userPage, $totalUserPages, 'archive.php?tab=users&user_search=' . urlencode($userSearch) . '&user_role=' . urlencode($userRoleFilter) . '&sort=' . urlencode($userSort) . '&order=' . urlencode($userOrder)); ?>
            </div><?php endif; ?>
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════ -->
<!-- PROGRAMS TAB -->
<!-- ═══════════════════════════════════════════ -->
<?php if ($activeTab === 'programs'): ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <?php echo sortableHeader('Code', 'code', $progSort, $progOrder); ?>
                            <?php echo sortableHeader('Program Name', 'name', $progSort, $progOrder); ?>
                            <?php echo sortableHeader('Students', 'students', $progSort, $progOrder); ?>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inactivePrograms)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No inactive programs.</td>
                            </tr>
                        <?php else:
                            foreach ($inactivePrograms as $p): ?>
                                <tr>
                                    <td><code class="fw-bold"><?php echo e($p['code']); ?></code></td>
                                    <td><?php echo e($p['name']); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo $p['student_count']; ?></span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-success"
                                            onclick="activateProgram(<?php echo $p['id']; ?>, '<?php echo e($p['code']); ?>', '<?php echo e($p['name']); ?>')"
                                            title="Reactivate">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    const CSRF_TOKEN = '<?php echo getCSRFToken(); ?>';

    function restoreStudent(id, sid, name) {
        showConfirm('Restore Student?', 'Restore <strong>' + name + '</strong> (' + sid + ') to active records?', 'Yes, Restore', 'question').then(r => {
            if (r.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'restore');
                fd.append('id', id);
                fd.append('csrf_token', CSRF_TOKEN);
                fetch('archive.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                    if (d.success) scheduleToast('success', d.message);
                    else showToast('error', d.message);
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
                    if (d.success) scheduleToast('success', d.message);
                    else showToast('error', d.message);
                });
            }
        });
    }

    function activateUser(id, username, name) {
        showConfirm(
            'Reactivate User Account?',
            'Reactivate <strong>' + name + '</strong> (<code>' + username + '</code>)? They will be able to log in again.',
            'Yes, Reactivate',
            'question'
        ).then(r => {
            if (r.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'activate_user');
                fd.append('id', id);
                fd.append('csrf_token', CSRF_TOKEN);
                fetch('archive.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                    if (d.success) scheduleToast('success', d.message);
                    else showToast('error', d.message);
                });
            }
        });
    }

    function activateProgram(id, code, name) {
        showConfirm(
            'Reactivate Program?',
            'Reactivate <strong>' + code + ' — ' + name + '</strong>? It will be available in selection forms again.',
            'Yes, Reactivate',
            'question'
        ).then(r => {
            if (r.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'activate_program');
                fd.append('id', id);
                fd.append('csrf_token', CSRF_TOKEN);
                fetch('archive.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                    if (d.success) scheduleToast('success', d.message);
                    else showToast('error', d.message);
                });
            }
        });
    }
</script>
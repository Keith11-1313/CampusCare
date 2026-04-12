<?php
$pageTitle = 'My Students';
require_once __DIR__ . '/../includes/header.php';
requireRole('rep');
$db = Database::getInstance();
$user = getCurrentUser();

$programId = $user['assigned_program_id'] ?? null;
$yearLevelId = $user['assigned_year_level_id'] ?? null;
$section = $user['assigned_section'] ?? null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? ''))
        jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);
    $action = $_POST['action'];

    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $studentIdNum = trim($_POST['student_id_num'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $dob = $_POST['date_of_birth'] ?? '';
        $bloodType = $_POST['blood_type'] ?? '';
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($studentIdNum) || empty($firstName) || empty($lastName) || empty($gender) || empty($dob))
            jsonResponse(['success' => false, 'message' => 'Student ID, name, gender, and DOB are required.']);

        // Validate Student ID format - must end with -N and contain no other letters
        if (!preg_match('/^[0-9-]+-N$/', $studentIdNum))
            jsonResponse(['success' => false, 'message' => 'Student ID must end with -N and contain no other letters (e.g. 2024-001-N).']);

        // Validate name fields — letters, spaces, hyphens, periods, and apostrophes only
        $namePattern = '/^[a-zA-Z\s\-\.\'ñÑ]+$/';
        if (!preg_match($namePattern, $firstName))
            jsonResponse(['success' => false, 'message' => 'First name must contain only letters.']);
        if (!preg_match($namePattern, $lastName))
            jsonResponse(['success' => false, 'message' => 'Last name must contain only letters.']);
        if (!empty($middleName) && !preg_match($namePattern, $middleName))
            jsonResponse(['success' => false, 'message' => 'Middle name must contain only letters.']);

        // Validate contact number — must be exactly 11 digits (09XXXXXXXXX) for PH mobile
        if (!empty($contactNumber)) {
            $digitsOnly = preg_replace('/[^0-9]/', '', $contactNumber);
            if (strlen($digitsOnly) !== 11)
                jsonResponse(['success' => false, 'message' => 'Contact number must be exactly 11 digits (e.g. 09171234567).']);
            if (substr($digitsOnly, 0, 2) !== '09')
                jsonResponse(['success' => false, 'message' => 'Contact number must start with 09.']);
        }

        // Uniqueness check
        $existing = $db->fetch("SELECT id FROM students WHERE student_id=? AND id!=?", [$studentIdNum, $id]);
        if ($existing)
            jsonResponse(['success' => false, 'message' => 'Student ID already exists.']);

        if ($id > 0) {
            // Verify student belongs to rep's section
            $check = $db->fetch("SELECT program_id, year_level_id, section FROM students WHERE id=?", [$id]);
            if ($programId && $check['program_id'] != $programId)
                jsonResponse(['success' => false, 'message' => 'Unauthorized.']);

            $db->query("UPDATE students SET student_id=?, first_name=?, middle_name=?, last_name=?, gender=?, date_of_birth=?, blood_type=?, contact_number=?, email=?, address=? WHERE id=?",
            [$studentIdNum, $firstName, $middleName ?: null, $lastName, $gender, $dob, $bloodType ?: null, $contactNumber ?: null, $email ?: null, $address ?: null, $id]);
            logAccess($_SESSION['user_id'], 'update_student', 'Updated student ' . $studentIdNum);
        }
        else {
            $db->query("INSERT INTO students (student_id,first_name,middle_name,last_name,gender,date_of_birth,blood_type,contact_number,email,address,program_id,year_level_id,section) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$studentIdNum, $firstName, $middleName ?: null, $lastName, $gender, $dob, $bloodType ?: null, $contactNumber ?: null, $email ?: null, $address ?: null, $programId, $yearLevelId, $section]);
            logAccess($_SESSION['user_id'], 'create_student', 'Created student ' . $studentIdNum);
        }
        jsonResponse(['success' => true, 'message' => 'Student saved successfully.']);
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $s = $db->fetch("SELECT * FROM students WHERE id=?", [$id]);
        jsonResponse(['success' => true, 'student' => $s]);
    }

    if ($action === 'import_csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK)
            jsonResponse(['success' => false, 'message' => 'Please select a valid CSV file.']);

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$file)
            jsonResponse(['success' => false, 'message' => 'Failed to read file.']);

        // Read header row
        $header = fgetcsv($file);
        if (!$header)
            jsonResponse(['success' => false, 'message' => 'CSV file is empty.']);

        // Normalize headers (lowercase, trim)
        $header = array_map(function ($h) {
            return strtolower(trim(str_replace(' ', '_', $h)));
        }, $header);

        // Required columns
        $required = ['student_id', 'first_name', 'last_name', 'gender', 'date_of_birth'];
        $missing = array_diff($required, $header);
        if (!empty($missing))
            jsonResponse(['success' => false, 'message' => 'Missing required columns: ' . implode(', ', $missing)]);

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($file)) !== false) {
            $rowNum++;
            if (count($row) !== count($header)) {
                $errors[] = "Row $rowNum: column count mismatch";
                $skipped++;
                continue;
            }

            $data = array_combine($header, $row);
            $sid = trim($data['student_id'] ?? '');
            $fn = trim($data['first_name'] ?? '');
            $ln = trim($data['last_name'] ?? '');
            $mn = trim($data['middle_name'] ?? '');
            $gender = trim($data['gender'] ?? '');
            $dob = trim($data['date_of_birth'] ?? '');
            $bt = trim($data['blood_type'] ?? '');
            $contact = trim($data['contact_number'] ?? '');
            $email = trim($data['email'] ?? '');
            $address = trim($data['address'] ?? '');

            if (empty($sid) || empty($fn) || empty($ln) || empty($gender) || empty($dob)) {
                $errors[] = "Row $rowNum: missing required fields";
                $skipped++;
                continue;
            }

            // Validate Student ID format
            if (!preg_match('/^[0-9-]+-N$/', $sid)) {
                $errors[] = "Row $rowNum: Student ID must end with -N and contain no other letters";
                $skipped++;
                continue;
            }

            // Validate name fields — no numbers allowed
            $namePattern = '/^[a-zA-Z\s\-\.\'\x{00f1}\x{00d1}]+$/u';
            if (!preg_match($namePattern, $fn) || !preg_match($namePattern, $ln) || (!empty($mn) && !preg_match($namePattern, $mn))) {
                $errors[] = "Row $rowNum: name fields must contain only letters";
                $skipped++;
                continue;
            }

            // Validate contact number — must be exactly 11 digits starting with 09
            if (!empty($contact)) {
                $digitsOnly = preg_replace('/[^0-9]/', '', $contact);
                if (strlen($digitsOnly) !== 11 || substr($digitsOnly, 0, 2) !== '09') {
                    $errors[] = "Row $rowNum: contact number must be exactly 11 digits starting with 09";
                    $skipped++;
                    continue;
                }
            }

            // Skip if student_id already exists
            $existing = $db->fetch("SELECT id FROM students WHERE student_id=?", [$sid]);
            if ($existing) {
                $skipped++;
                continue;
            }

            $db->query("INSERT INTO students (student_id,first_name,middle_name,last_name,gender,date_of_birth,blood_type,contact_number,email,address,program_id,year_level_id,section) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$sid, $fn, $mn ?: null, $ln, $gender, $dob, $bt ?: null, $contact ?: null, $email ?: null, $address ?: null, $programId, $yearLevelId, $section]);
            $imported++;
        }
        fclose($file);

        logAccess($_SESSION['user_id'], 'import_students_csv', "Imported $imported students from CSV");

        $msg = "$imported student(s) imported successfully.";
        if ($skipped > 0)
            $msg .= " $skipped row(s) skipped (duplicates or errors).";
        jsonResponse(['success' => true, 'message' => $msg, 'imported' => $imported, 'skipped' => $skipped]);
    }
}

// Fetch students
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$sortColumns = ['student_id' => 's.student_id', 'name' => 's.last_name', 'gender' => 's.gender', 'dob' => 's.date_of_birth', 'blood_type' => 's.blood_type', 'contact' => 's.contact_number'];
$sort = (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortColumns)) ? $_GET['sort'] : 'name';
$order = (isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) ? $_GET['order'] : 'asc';

$where = "WHERE s.status='active'";
$params = [];
if ($programId) {
    $where .= " AND s.program_id=?";
    $params[] = $programId;
}
if ($yearLevelId) {
    $where .= " AND s.year_level_id=?";
    $params[] = $yearLevelId;
}
if ($section) {
    $where .= " AND s.section=?";
    $params[] = $section;
}
if (!empty($search)) {
    // Check if search term is a month name (e.g. "jan", "january", "feb", "february")
    $months = [
        'jan' => '01', 'january' => '01', 'feb' => '02', 'february' => '02',
        'mar' => '03', 'march' => '03', 'apr' => '04', 'april' => '04',
        'may' => '05', 'jun' => '06', 'june' => '06',
        'jul' => '07', 'july' => '07', 'aug' => '08', 'august' => '08',
        'sep' => '09', 'sept' => '09', 'september' => '09',
        'oct' => '10', 'october' => '10', 'nov' => '11', 'november' => '11',
        'dec' => '12', 'december' => '12'
    ];
    $searchLower = strtolower(trim($search));
    $monthNum = $months[$searchLower] ?? null;

    if ($monthNum) {
        // Month-only search: match any year with that month (e.g. "%-01-%")
        $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', LEFT(s.middle_name, 1), '. ', s.last_name) LIKE ? OR s.gender LIKE ? OR s.date_of_birth LIKE ? OR s.blood_type LIKE ? OR s.contact_number LIKE ? OR s.date_of_birth LIKE ?)";
        $sk = "%$search%";
        $mk = "%-$monthNum-%";
        $params = array_merge($params, [$sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $mk]);
    } else {
        // Try to parse human-friendly date formats (e.g. "oct 7 2008", "October 7, 2008")
        $parsedDate = strtotime($search);
        if ($parsedDate !== false) {
            $dateFormatted = date('Y-m-d', $parsedDate);
            $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', LEFT(s.middle_name, 1), '. ', s.last_name) LIKE ? OR s.gender LIKE ? OR s.date_of_birth LIKE ? OR s.blood_type LIKE ? OR s.contact_number LIKE ? OR s.date_of_birth LIKE ?)";
            $sk = "%$search%";
            $dk = "%$dateFormatted%";
            $params = array_merge($params, [$sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $dk]);
        } else {
            $where .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) LIKE ? OR CONCAT(s.first_name, ' ', LEFT(s.middle_name, 1), '. ', s.last_name) LIKE ? OR s.gender LIKE ? OR s.date_of_birth LIKE ? OR s.blood_type LIKE ? OR s.contact_number LIKE ?)";
            $sk = "%$search%";
            $params = array_merge($params, [$sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk, $sk]);
        }
    }
}

$total = $db->fetchColumn("SELECT COUNT(*) FROM students s $where", $params);
$totalPages = ceil($total / $perPage);
$orderSql = $sortColumns[$sort] . ' ' . ($order === 'asc' ? 'ASC' : 'DESC');
$students = $db->fetchAll("SELECT s.* FROM students s $where ORDER BY $orderSql LIMIT $perPage OFFSET $offset", $params);

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap">
    <div><h1><i class="bi bi-people me-2"></i>My Students</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Students</li></ol></nav></div>
    <div>
        <button class="btn btn-outline-success me-2" onclick="document.getElementById('importModal') && importModal.show()"><i class="bi bi-upload me-1"></i>Import CSV</button>
        <button class="btn btn-primary" onclick="openStudentModal()"><i class="bi bi-person-plus me-1"></i>Add Student</button>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2">
        <div class="col-md-11">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" name="search" placeholder="Search records..." value="<?php echo e($search); ?>">
            </div>
        </div>
        <div class="col-md-1">
            <?php if ($search): ?>
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
<thead><tr><?php echo sortableHeader('Student ID', 'student_id', $sort, $order); ?><?php echo sortableHeader('Name', 'name', $sort, $order); ?><?php echo sortableHeader('Gender', 'gender', $sort, $order); ?><?php echo sortableHeader('DOB', 'dob', $sort, $order); ?><?php echo sortableHeader('Blood Type', 'blood_type', $sort, $order); ?><?php echo sortableHeader('Contact', 'contact', $sort, $order); ?><th class="text-center">Actions</th></tr></thead>
<tbody>
<?php if (empty($students)): ?><tr><td colspan="7" class="text-center text-muted py-4">No students found.</td></tr>
<?php
else:
    foreach ($students as $s): ?>
<tr>
<td><span class="font-monospace"><?php echo e($s['student_id']); ?></span></td>
<td class="fw-semibold"><?php echo e($s['first_name'] . ' ' . ($s['middle_name'] ? substr($s['middle_name'], 0, 1) . '. ' : '') . $s['last_name']); ?></td>
<td><?php echo e($s['gender']); ?></td>
<td><small><?php echo formatDate($s['date_of_birth']); ?></small></td>
<td><?php echo e($s['blood_type'] ?? '—'); ?></td>
<td><small><?php echo e($s['contact_number'] ?? '—'); ?></small></td>
<td class="text-center"><button class="btn btn-sm btn-outline-primary btn-icon" onclick="editStudent(<?php echo $s['id']; ?>)"><i class="bi bi-pencil"></i></button></td>
</tr>
<?php
    endforeach;
endif; ?>
</tbody></table></div></div>
<?php if ($totalPages > 1): ?><div class="card-footer bg-white"><?php echo generatePagination($page, $totalPages, 'students.php?search=' . urlencode($search) . '&sort=' . urlencode($sort) . '&order=' . urlencode($order)); ?></div><?php
endif; ?>
</div>

<!-- Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title" id="studentModalTitle">Add Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<form id="studentForm">
<div class="modal-body">
<input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
<input type="hidden" name="action" id="formAction" value="save">
<input type="hidden" name="id" id="studentDbId" value="0">
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Student ID <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="student_id_num" id="studentIdNum" required pattern="[0-9\-]+-N" title="Format: XXXXXX-N (Numbers, hyphens, and ends with -N)" oninput="this.value = this.value.toUpperCase().replace(/[^0-9\-N]/g, '')"><div class="invalid-feedback">Must end with -N and contain no other letters.</div></div>
<div class="col-md-4"><label class="form-label">First Name <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="first_name" id="sFirstName" required pattern="[a-zA-Z\s\-\.\u00f1\u00d1']+" title="Letters, spaces, hyphens, periods, and apostrophes only" oninput="this.value=this.value.replace(/[^a-zA-Z\s\-\.'\u00f1\u00d1]/g,'')"><div class="invalid-feedback">Please enter a valid first name (letters only).</div></div>
<div class="col-md-4"><label class="form-label">Last Name <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="last_name" id="sLastName" required pattern="[a-zA-Z\s\-\.\u00f1\u00d1']+" title="Letters, spaces, hyphens, periods, and apostrophes only" oninput="this.value=this.value.replace(/[^a-zA-Z\s\-\.'\u00f1\u00d1]/g,'')"><div class="invalid-feedback">Please enter a valid last name (letters only).</div></div>
<div class="col-md-4"><label class="form-label">Middle Name</label><input type="text" class="form-control" name="middle_name" id="sMiddleName" pattern="[a-zA-Z\s\-\.\u00f1\u00d1']*" title="Letters, spaces, hyphens, periods, and apostrophes only" oninput="this.value=this.value.replace(/[^a-zA-Z\s\-\.'\u00f1\u00d1]/g,'')"><div class="invalid-feedback">Please enter a valid middle name (letters only).</div></div>
<div class="col-md-4"><label class="form-label">Gender <span class="required-asterisk">*</span></label><select class="form-select" name="gender" id="sGender" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
<div class="col-md-4"><label class="form-label">Date of Birth <span class="required-asterisk">*</span></label><input type="date" class="form-control" name="date_of_birth" id="sDob" required></div>
<div class="col-md-3"><label class="form-label">Blood Type</label><select class="form-select" name="blood_type" id="sBloodType"><option value="">Unknown</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select></div>
<div class="col-md-4"><label class="form-label">Contact Number</label><input type="tel" class="form-control" name="contact_number" id="sContact" placeholder="09XXXXXXXXX" maxlength="11" pattern="09[0-9]{9}" title="Must be 11 digits starting with 09 (e.g. 09171234567)" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div>
<div class="col-md-5"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="sEmail"></div>
<div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" id="sAddress" rows="2"></textarea></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Student</button></div>
</form></div></div></div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Students from CSV</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form id="importForm" enctype="multipart/form-data">
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <input type="hidden" name="action" value="import_csv">
            <div class="mb-3">
                <label class="form-label">CSV File <span class="required-asterisk">*</span></label>
                <input type="file" class="form-control" name="csv_file" id="csvFile" accept=".csv" required>
                <div class="form-text">Upload a .csv file with student data.</div>
            </div>
            <div class="alert alert-info small mb-3 py-2">
                <i class="bi bi-info-circle me-1"></i><strong>Required columns:</strong> student_id, first_name, last_name, gender, date_of_birth<br>
                <strong>Optional columns:</strong> middle_name, blood_type, contact_number, email, address
            </div>
            <div class="text-center">
                <button type="button" class="btn btn-outline-info btn-sm" onclick="downloadTemplate()"><i class="bi bi-download me-1"></i>Download CSV Template</button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success" id="importBtn"><i class="bi bi-upload me-1"></i>Import</button>
        </div>
    </form>
</div></div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const studentModal = new bootstrap.Modal(document.getElementById('studentModal'));
const importModal = new bootstrap.Modal(document.getElementById('importModal'));

function openStudentModal(){
    document.getElementById('studentModalTitle').textContent='Add Student';
    document.getElementById('studentDbId').value=0;
    document.getElementById('studentForm').reset();
    studentModal.show();
}
function editStudent(id){
    const fd=new FormData();fd.append('action','get');fd.append('id',id);fd.append('csrf_token','<?php echo getCSRFToken(); ?>');
    fetch('students.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){const s=d.student;
        document.getElementById('studentModalTitle').textContent='Edit Student';
        document.getElementById('studentDbId').value=s.id;
        document.getElementById('studentIdNum').value=s.student_id;
        document.getElementById('sFirstName').value=s.first_name;
        document.getElementById('sLastName').value=s.last_name;
        document.getElementById('sMiddleName').value=s.middle_name||'';
        document.getElementById('sGender').value=s.gender;
        document.getElementById('sDob').value=s.date_of_birth;
        document.getElementById('sBloodType').value=s.blood_type||'';
        document.getElementById('sContact').value=s.contact_number||'';
        document.getElementById('sEmail').value=s.email||'';
        document.getElementById('sAddress').value=s.address||'';
        studentModal.show();}
    });
}
document.getElementById('studentForm').addEventListener('submit',function(e){
    e.preventDefault();
    fetch('students.php',{method:'POST',body:new FormData(this)}).then(r=>r.json()).then(d=>{
        if(d.success){studentModal.hide();scheduleToast('success',d.message);}
        else showToast('error',d.message);
    });
});

// Import CSV form
document.getElementById('importForm').addEventListener('submit', function(e){
    e.preventDefault();
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importing...';
    fetch('students.php', {method:'POST', body: new FormData(this)}).then(r=>r.json()).then(d=>{
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-upload me-1"></i>Import';
        if(d.success){
            importModal.hide();
            showAlert('success', 'Import Complete', d.message).then(()=>location.reload());
        } else {
            showAlert('error', 'Import Failed', d.message);
        }
    }).catch(err=>{
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-upload me-1"></i>Import';
        showAlert('error', 'Error', 'An unexpected error occurred.');
    });
});

function downloadTemplate(){
    const headers = 'student_id,first_name,middle_name,last_name,gender,date_of_birth,blood_type,contact_number,email,address';
    const sample = '2024-0001-N,Juan,Santos,Dela Cruz,Male,2005-03-15,O+,09171234567,juan@email.com,123 Main St';
    const blob = new Blob([headers + '\n' + sample + '\n'], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'students_import_template.csv';
    a.click();
}

// Auto-open add modal if ?action=add in URL
if(new URLSearchParams(window.location.search).get('action')==='add') openStudentModal();
</script>


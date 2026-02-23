<?php
/**
 * CampusCare - Content Management (Nurse)
 * Manages announcements, FAQs, first-aid guidelines, emergency contacts, clinic hours
 */
$pageTitle = 'Manage Content';
require_once __DIR__ . '/../includes/header.php';
requireRole('nurse');
$db = Database::getInstance();

$tab = $_GET['tab'] ?? 'announcements';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? ''))
        jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);
    $action = $_POST['action'];
    $section = $_POST['section'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if ($section === 'announcements') {
        if ($action === 'save') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $status = $_POST['status'] ?? 'published';
            if (empty($title) || empty($content))
                jsonResponse(['success' => false, 'message' => 'Title and content required.']);
            if ($id > 0) {
                $db->query("UPDATE announcements SET title=?, content=?, status=? WHERE id=?", [$title, $content, $status, $id]);
            }
            else {
                $db->query("INSERT INTO announcements (title,content,status,posted_by) VALUES (?,?,?,?)", [$title, $content, $status, $_SESSION['user_id']]);
            }
            jsonResponse(['success' => true, 'message' => 'Announcement saved.']);
        }
        if ($action === 'delete') {
            $db->query("DELETE FROM announcements WHERE id=?", [$id]);
            jsonResponse(['success' => true, 'message' => 'Deleted.']);
        }
        if ($action === 'get') {
            $item = $db->fetch("SELECT * FROM announcements WHERE id=?", [$id]);
            jsonResponse(['success' => true, 'item' => $item]);
        }
    }

    if ($section === 'faqs') {
        if ($action === 'save') {
            $question = trim($_POST['question'] ?? '');
            $answer = trim($_POST['answer'] ?? '');
            if (empty($question) || empty($answer))
                jsonResponse(['success' => false, 'message' => 'Question and answer required.']);
            if ($id > 0)
                $db->query("UPDATE faqs SET question=?, answer=?, sort_order=? WHERE id=?", [$question, $answer, intval($_POST['sort_order'] ?? 0), $id]);
            else
                $db->query("INSERT INTO faqs (question,answer,sort_order) VALUES (?,?,?)", [$question, $answer, intval($_POST['sort_order'] ?? 0)]);
            jsonResponse(['success' => true, 'message' => 'FAQ saved.']);
        }
        if ($action === 'delete') {
            $db->query("DELETE FROM faqs WHERE id=?", [$id]);
            jsonResponse(['success' => true, 'message' => 'Deleted.']);
        }
        if ($action === 'get') {
            $item = $db->fetch("SELECT * FROM faqs WHERE id=?", [$id]);
            jsonResponse(['success' => true, 'item' => $item]);
        }
    }

    if ($section === 'first_aid') {
        if ($action === 'save') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            if (empty($title) || empty($content))
                jsonResponse(['success' => false, 'message' => 'Title and content required.']);
            if ($id > 0)
                $db->query("UPDATE first_aid_guidelines SET title=?, content=?, sort_order=? WHERE id=?", [$title, $content, intval($_POST['sort_order'] ?? 0), $id]);
            else
                $db->query("INSERT INTO first_aid_guidelines (title,content,sort_order) VALUES (?,?,?)", [$title, $content, intval($_POST['sort_order'] ?? 0)]);
            jsonResponse(['success' => true, 'message' => 'Guideline saved.']);
        }
        if ($action === 'delete') {
            $db->query("DELETE FROM first_aid_guidelines WHERE id=?", [$id]);
            jsonResponse(['success' => true, 'message' => 'Deleted.']);
        }
        if ($action === 'get') {
            $item = $db->fetch("SELECT * FROM first_aid_guidelines WHERE id=?", [$id]);
            jsonResponse(['success' => true, 'item' => $item]);
        }
    }

    if ($section === 'emergency') {
        if ($action === 'save') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone_number'] ?? '');
            if (empty($name) || empty($phone))
                jsonResponse(['success' => false, 'message' => 'Name and phone required.']);
            if ($id > 0)
                $db->query("UPDATE clinic_emergency_contacts SET name=?, role=?, phone_number=?, sort_order=? WHERE id=?", [$name, trim($_POST['role'] ?? ''), $phone, intval($_POST['sort_order'] ?? 0), $id]);
            else
                $db->query("INSERT INTO clinic_emergency_contacts (name,role,phone_number,sort_order) VALUES (?,?,?,?)", [$name, trim($_POST['role'] ?? ''), $phone, intval($_POST['sort_order'] ?? 0)]);
            jsonResponse(['success' => true, 'message' => 'Contact saved.']);
        }
        if ($action === 'delete') {
            $db->query("DELETE FROM clinic_emergency_contacts WHERE id=?", [$id]);
            jsonResponse(['success' => true, 'message' => 'Deleted.']);
        }
        if ($action === 'get') {
            $item = $db->fetch("SELECT * FROM clinic_emergency_contacts WHERE id=?", [$id]);
            jsonResponse(['success' => true, 'item' => $item]);
        }
    }

    if ($section === 'clinic_hours') {
        if ($action === 'save_all') {
            $hours = $_POST['hours'] ?? [];
            foreach ($hours as $h) {
                $db->query("UPDATE clinic_hours SET opening_time=?, closing_time=?, is_closed=?, notes=? WHERE id=?",
                [$h['opening_time'] ?: null, $h['closing_time'] ?: null, isset($h['is_closed']) ? 1 : 0, $h['notes'] ?? '', $h['id']]);
            }
            jsonResponse(['success' => true, 'message' => 'Clinic hours updated.']);
        }
    }
}

// Fetch data
$announcements = $db->fetchAll("SELECT a.*, u.first_name, u.last_name FROM announcements a LEFT JOIN users u ON a.posted_by=u.id ORDER BY a.created_at DESC");
$faqs = $db->fetchAll("SELECT * FROM faqs ORDER BY sort_order");
$firstAid = $db->fetchAll("SELECT * FROM first_aid_guidelines ORDER BY sort_order");
$emergency = $db->fetchAll("SELECT * FROM clinic_emergency_contacts ORDER BY sort_order");
$clinicHours = $db->fetchAll("SELECT * FROM clinic_hours ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-megaphone me-2"></i>Manage Public Content</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Content</li></ol></nav></div>

<ul class="nav nav-tabs mb-0" role="tablist">
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'announcements' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#announcementsTab">Announcements</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'faqs' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#faqsTab">FAQs</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'first_aid' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#firstAidTab">First Aid</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'emergency' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#emergencyTab">Emergency</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'hours' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#hoursTab">Clinic Hours</a></li>
</ul>

<div class="tab-content">
<!-- Announcements Tab -->
<div class="tab-pane fade <?php echo $tab === 'announcements' ? 'show active' : ''; ?>" id="announcementsTab">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between"><span>Announcements</span><button class="btn btn-sm btn-primary" onclick="addAnnouncement()"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Title</th><th>Status</th><th>Created By</th><th>Date</th><th class="text-center">Actions</th></tr></thead>
<tbody>
<?php foreach ($announcements as $a): ?>
<tr><td class="fw-semibold"><?php echo e(substr($a['title'], 0, 50)); ?></td><td><?php echo statusBadge($a['status']); ?></td>
<td><small><?php echo e(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')); ?></small></td>
<td><small><?php echo formatDate($a['created_at']); ?></small></td>
<td class="text-center table-action-btns">
<button class="btn btn-sm btn-outline-primary btn-icon" onclick="editItem('announcements',<?php echo $a['id']; ?>)"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteItem('announcements',<?php echo $a['id']; ?>)"><i class="bi bi-trash"></i></button>
</td></tr>
<?php
endforeach; ?>
</tbody></table></div></div></div>
</div>

<!-- FAQs Tab -->
<div class="tab-pane fade <?php echo $tab === 'faqs' ? 'show active' : ''; ?>" id="faqsTab">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between"><span>FAQs</span><button class="btn btn-sm btn-primary" onclick="addFaq()"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Order</th><th>Question</th><th>Answer</th><th class="text-center">Actions</th></tr></thead>
<tbody>
<?php foreach ($faqs as $f): ?>
<tr><td><?php echo $f['sort_order']; ?></td><td class="fw-semibold"><?php echo truncate($f['question'], 40); ?></td><td><small><?php echo truncate($f['answer'], 50); ?></small></td>
<td class="text-center table-action-btns"><button class="btn btn-sm btn-outline-primary btn-icon" onclick="editItem('faqs',<?php echo $f['id']; ?>)"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteItem('faqs',<?php echo $f['id']; ?>)"><i class="bi bi-trash"></i></button></td></tr>
<?php
endforeach; ?>
</tbody></table></div></div></div>
</div>

<!-- First Aid Tab -->
<div class="tab-pane fade <?php echo $tab === 'first_aid' ? 'show active' : ''; ?>" id="firstAidTab">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between"><span>First Aid Guidelines</span><button class="btn btn-sm btn-primary" onclick="addFirstAid()"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Order</th><th>Title</th><th>Content Preview</th><th class="text-center">Actions</th></tr></thead>
<tbody>
<?php foreach ($firstAid as $f): ?>
<tr><td><?php echo $f['sort_order']; ?></td><td class="fw-semibold"><?php echo e($f['title']); ?></td><td><small><?php echo truncate(strip_tags($f['content']), 50); ?></small></td>
<td class="text-center table-action-btns"><button class="btn btn-sm btn-outline-primary btn-icon" onclick="editItem('first_aid',<?php echo $f['id']; ?>)"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteItem('first_aid',<?php echo $f['id']; ?>)"><i class="bi bi-trash"></i></button></td></tr>
<?php
endforeach; ?>
</tbody></table></div></div></div>
</div>

<!-- Emergency Contacts Tab -->
<div class="tab-pane fade <?php echo $tab === 'emergency' ? 'show active' : ''; ?>" id="emergencyTab">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header d-flex justify-content-between"><span>Emergency Contacts</span><button class="btn btn-sm btn-primary" onclick="addEmergency()"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Name</th><th>Role</th><th>Phone</th><th class="text-center">Actions</th></tr></thead>
<tbody>
<?php foreach ($emergency as $ec): ?>
<tr><td class="fw-semibold"><?php echo e($ec['name']); ?></td><td><?php echo e($ec['role'] ?? '—'); ?></td><td><?php echo e($ec['phone_number']); ?></td>
<td class="text-center table-action-btns"><button class="btn btn-sm btn-outline-primary btn-icon" onclick="editItem('emergency',<?php echo $ec['id']; ?>)"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteItem('emergency',<?php echo $ec['id']; ?>)"><i class="bi bi-trash"></i></button></td></tr>
<?php
endforeach; ?>
</tbody></table></div></div></div>
</div>

<!-- Clinic Hours Tab -->
<div class="tab-pane fade <?php echo $tab === 'hours' ? 'show active' : ''; ?>" id="hoursTab">
<div class="card border-top-0" style="border-radius:0 0 12px 12px;">
<div class="card-header">Clinic Hours</div>
<div class="card-body">
<form id="hoursForm">
<input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
<input type="hidden" name="action" value="save_all">
<input type="hidden" name="section" value="clinic_hours">
<table class="table"><thead><tr><th>Day</th><th>Opens</th><th>Closes</th><th>Closed?</th><th>Notes</th></tr></thead>
<tbody>
<?php foreach ($clinicHours as $i => $h): ?>
<tr>
<td class="fw-semibold"><?php echo e($h['day_of_week']); ?></td>
<input type="hidden" name="hours[<?php echo $i; ?>][id]" value="<?php echo $h['id']; ?>">
<td><input type="time" class="form-control form-control-sm" name="hours[<?php echo $i; ?>][opening_time]" value="<?php echo e($h['opening_time'] ?? ''); ?>"></td>
<td><input type="time" class="form-control form-control-sm" name="hours[<?php echo $i; ?>][closing_time]" value="<?php echo e($h['closing_time'] ?? ''); ?>"></td>
<td><input type="checkbox" class="form-check-input" name="hours[<?php echo $i; ?>][is_closed]" <?php echo $h['is_closed'] ? 'checked' : ''; ?>></td>
<td><input type="text" class="form-control form-control-sm" name="hours[<?php echo $i; ?>][notes]" value="<?php echo e($h['notes'] ?? ''); ?>"></td>
</tr>
<?php
endforeach; ?>
</tbody></table>
<button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Hours</button>
</form></div></div>
</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const csrf = '<?php echo getCSRFToken(); ?>';
const labelStyle = 'display:block;text-align:left;font-weight:600;font-size:0.85rem;margin:12px auto 4px;width:85%;color:#333;';

function postAction(data) {
    data.append('csrf_token', csrf);
    return fetch('content.php', {method:'POST', body:data}).then(r=>r.json());
}

function handleResult(d) {
    if (d.success) {
        Swal.fire({icon:'success', title:'Saved!', text:d.message, confirmButtonColor:'#0d6e3f'}).then(()=>location.reload());
    } else {
        Swal.fire({icon:'error', title:'Error', text:d.message, confirmButtonColor:'#0d6e3f'});
    }
}

function deleteItem(section, id) {
    showConfirm('Delete?','Are you sure you want to delete this item?','Yes, Delete').then(r=>{
        if(r.isConfirmed){
            const fd=new FormData();fd.append('action','delete');fd.append('section',section);fd.append('id',id);
            postAction(fd).then(d=>{
                if(d.success) Swal.fire({icon:'success',title:'Deleted!',text:d.message,confirmButtonColor:'#0d6e3f'}).then(()=>location.reload());
                else Swal.fire({icon:'error',title:'Error',text:d.message,confirmButtonColor:'#0d6e3f'});
            });
        }
    });
}

function editItem(section, id) {
    const fd=new FormData();fd.append('action','get');fd.append('section',section);fd.append('id',id);
    postAction(fd).then(d=>{
        if(!d.success) return;
        const item=d.item;
        if(section==='announcements') showAnnouncementForm(item);
        else if(section==='faqs') showFaqForm(item);
        else if(section==='first_aid') showFirstAidForm(item);
        else if(section==='emergency') showEmergencyForm(item);
    }).catch(err=>{ console.error('Edit fetch error:', err); Swal.fire({icon:'error',title:'Error',text:'Failed to load item data. Please try again.',confirmButtonColor:'#0d6e3f'}); });
}

function addAnnouncement(){showAnnouncementForm(null);}
function showAnnouncementForm(item){
    Swal.fire({title:item?'Edit Announcement':'Add Announcement',
        html:'<label style="'+labelStyle+'">Title <span style="color:red">*</span></label><input class="swal2-input" id="s_title" placeholder="Enter title">' +
             '<label style="'+labelStyle+'">Content <span style="color:red">*</span></label><textarea class="swal2-textarea" id="s_content" placeholder="Enter content"></textarea>' +
             '<label style="'+labelStyle+'">Status</label><select class="swal2-select" id="s_status"><option value="published">Published</option><option value="draft">Draft</option></select>',
        showCancelButton:true,confirmButtonColor:'#0d6e3f',confirmButtonText:'Save',
        didOpen:()=>{
            if(item){
                document.getElementById('s_title').value=item.title||'';
                document.getElementById('s_content').value=item.content||'';
                document.getElementById('s_status').value=item.status||'published';
            }
        },
        preConfirm:()=>({title:document.getElementById('s_title').value,content:document.getElementById('s_content').value,status:document.getElementById('s_status').value})
    }).then(r=>{if(r.isConfirmed){const fd=new FormData();fd.append('action','save');fd.append('section','announcements');
    if(item)fd.append('id',item.id);Object.entries(r.value).forEach(([k,v])=>fd.append(k,v));
    postAction(fd).then(handleResult);}});
}

function addFaq(){showFaqForm(null);}
function showFaqForm(item){
    Swal.fire({title:item?'Edit FAQ':'Add FAQ',
        html:'<label style="'+labelStyle+'">Question <span style="color:red">*</span></label><input class="swal2-input" id="s_question" placeholder="Enter question">' +
             '<label style="'+labelStyle+'">Answer <span style="color:red">*</span></label><textarea class="swal2-textarea" id="s_answer" placeholder="Enter answer"></textarea>' +
             '<label style="'+labelStyle+'">Sort Order</label><input class="swal2-input" id="s_sort_order" type="number" placeholder="0">',
        showCancelButton:true,confirmButtonColor:'#0d6e3f',confirmButtonText:'Save',
        didOpen:()=>{
            if(item){
                document.getElementById('s_question').value=item.question||'';
                document.getElementById('s_answer').value=item.answer||'';
                document.getElementById('s_sort_order').value=item.sort_order||'0';
            }
        },
        preConfirm:()=>({question:document.getElementById('s_question').value,answer:document.getElementById('s_answer').value,sort_order:document.getElementById('s_sort_order').value})
    }).then(r=>{if(r.isConfirmed){const fd=new FormData();fd.append('action','save');fd.append('section','faqs');
    if(item)fd.append('id',item.id);Object.entries(r.value).forEach(([k,v])=>fd.append(k,v));
    postAction(fd).then(handleResult);}});
}

function addFirstAid(){showFirstAidForm(null);}
function showFirstAidForm(item){
    Swal.fire({title:item?'Edit Guideline':'Add Guideline',
        html:'<label style="'+labelStyle+'">Title <span style="color:red">*</span></label><input class="swal2-input" id="s_title" placeholder="Enter title">' +
             '<label style="'+labelStyle+'">Content <span style="color:red">*</span></label><textarea class="swal2-textarea" id="s_content" placeholder="Enter content (HTML allowed)"></textarea>' +
             '<label style="'+labelStyle+'">Sort Order</label><input class="swal2-input" id="s_sort_order" type="number" placeholder="0">',
        showCancelButton:true,confirmButtonColor:'#0d6e3f',confirmButtonText:'Save',
        didOpen:()=>{
            if(item){
                document.getElementById('s_title').value=item.title||'';
                document.getElementById('s_content').value=item.content||'';
                document.getElementById('s_sort_order').value=item.sort_order||'0';
            }
        },
        preConfirm:()=>({title:document.getElementById('s_title').value,content:document.getElementById('s_content').value,sort_order:document.getElementById('s_sort_order').value})
    }).then(r=>{if(r.isConfirmed){const fd=new FormData();fd.append('action','save');fd.append('section','first_aid');
    if(item)fd.append('id',item.id);Object.entries(r.value).forEach(([k,v])=>fd.append(k,v));
    postAction(fd).then(handleResult);}});
}

function addEmergency(){showEmergencyForm(null);}
function showEmergencyForm(item){
    Swal.fire({title:item?'Edit Contact':'Add Contact',
        html:'<label style="'+labelStyle+'">Name <span style="color:red">*</span></label><input class="swal2-input" id="s_name" placeholder="Enter name">' +
             '<label style="'+labelStyle+'">Role</label><input class="swal2-input" id="s_role" placeholder="Enter role">' +
             '<label style="'+labelStyle+'">Phone Number <span style="color:red">*</span></label><input class="swal2-input" id="s_phone_number" placeholder="Enter phone number">',
        showCancelButton:true,confirmButtonColor:'#0d6e3f',confirmButtonText:'Save',
        didOpen:()=>{
            if(item){
                document.getElementById('s_name').value=item.name||'';
                document.getElementById('s_role').value=item.role||'';
                document.getElementById('s_phone_number').value=item.phone_number||'';
            }
        },
        preConfirm:()=>({name:document.getElementById('s_name').value,role:document.getElementById('s_role').value,phone_number:document.getElementById('s_phone_number').value})
    }).then(r=>{if(r.isConfirmed){const fd=new FormData();fd.append('action','save');fd.append('section','emergency');
    if(item)fd.append('id',item.id);Object.entries(r.value).forEach(([k,v])=>fd.append(k,v));
    postAction(fd).then(handleResult);}});
}

document.getElementById('hoursForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    postAction(new FormData(this)).then(d=>{
        if(d.success) Swal.fire({icon:'success',title:'Saved!',text:d.message,confirmButtonColor:'#0d6e3f'}).then(()=>location.reload());
        else Swal.fire({icon:'error',title:'Error',text:d.message,confirmButtonColor:'#0d6e3f'});
    });
});
</script>

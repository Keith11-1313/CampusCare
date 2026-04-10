<?php
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
            $icon = trim($_POST['icon'] ?? 'general-first-aid');
            if (empty($title) || empty($content))
                jsonResponse(['success' => false, 'message' => 'Title and content required.']);
            if ($id > 0)
                $db->query("UPDATE first_aid_guidelines SET title=?, icon=?, content=? WHERE id=?", [$title, $icon, $content, $id]);
            else
                $db->query("INSERT INTO first_aid_guidelines (title,icon,content) VALUES (?,?,?)", [$title, $icon, $content]);
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
$faqs = $db->fetchAll("SELECT * FROM faqs ORDER BY id ASC");
$firstAid = $db->fetchAll("SELECT * FROM first_aid_guidelines ORDER BY id ASC");
$emergency = $db->fetchAll("SELECT * FROM clinic_emergency_contacts ORDER BY sort_order");
$clinicHours = $db->fetchAll("SELECT * FROM clinic_hours ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header"><h1><i class="bi bi-megaphone me-2"></i>Manage Public Content</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Content</li></ol></nav></div>

<ul class="nav nav-tabs mb-0" role="tablist">
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'announcements' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#announcementsTab">Announcements</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'first_aid' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#firstAidTab">First Aid</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'faqs' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#faqsTab">FAQs</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'emergency' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#emergencyTab">Emergency</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab === 'hours' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#hoursTab">Clinic Hours</a></li>
</ul>

<div class="tab-content">
<!-- Announcements Tab -->
<div class="tab-pane fade <?php echo $tab === 'announcements' ? 'show active' : ''; ?>" id="announcementsTab">
    <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
        <div class="card-header d-flex justify-content-between"><span>Announcements</span><button class="btn btn-sm btn-primary" onclick="addAnnouncement()"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table table-hover mb-0">
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- First Aid Tab -->
<div class="tab-pane fade <?php echo $tab === 'first_aid' ? 'show active' : ''; ?>" id="firstAidTab">
    <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
        <div class="card-header d-flex justify-content-between"><span>First Aid Guidelines</span><button class="btn btn-sm btn-primary" onclick="addFirstAid()"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Title</th>
                            <th>Content Preview</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firstAid as $f): ?>
                        <tr>
                            <td><img src="<?php echo BASE_URL; ?>/assets/first-aid-icons/<?php echo e($f['icon'] ?? 'general-first-aid'); ?>.png" alt="" style="width:28px;height:28px;object-fit:contain;"></td>
                            <td class="fw-semibold"><?php echo e($f['title']); ?></td>
                            <td><small><?php echo truncate(strip_tags($f['content']), 50); ?></small></td>
                            <td class="text-center table-action-btns">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="editItem('first_aid',<?php echo $f['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteItem('first_aid',<?php echo $f['id']; ?>)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- FAQs Tab -->
<div class="tab-pane fade <?php echo $tab === 'faqs' ? 'show active' : ''; ?>" id="faqsTab">
    <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
        <div class="card-header d-flex justify-content-between"><span>FAQs</span><button class="btn btn-sm btn-primary" onclick="addFaq()"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Answer</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faqs as $f): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo truncate($f['question'], 40); ?></td>
                            <td><small><?php echo truncate($f['answer'], 50); ?></small></td>
                            <td class="text-center table-action-btns">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="editItem('faqs',<?php echo $f['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteItem('faqs',<?php echo $f['id']; ?>)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Contacts Tab -->
<div class="tab-pane fade <?php echo $tab === 'emergency' ? 'show active' : ''; ?>" id="emergencyTab">
    <div class="card border-top-0" style="border-radius:0 0 12px 12px;">
        <div class="card-header d-flex justify-content-between"><span>Emergency Contacts</span><button class="btn btn-sm btn-primary" onclick="addEmergency()"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emergency as $ec): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo e($ec['name']); ?></td>
                            <td><?php echo e($ec['role'] ?? '—'); ?></td>
                            <td><?php echo e($ec['phone_number']); ?></td>
                            <td class="text-center table-action-btns">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="editItem('emergency',<?php echo $ec['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteItem('emergency',<?php echo $ec['id']; ?>)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php
endforeach; ?>  
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Opens</th>
                                <th>Closes</th>
                                <th>Closed?</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
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
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Hours</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="announcementModalTitle">Add Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="announcementForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="section" value="announcements">
                    <input type="hidden" name="id" id="annId" value="0">
                    <div class="mb-3"><label class="form-label">Title <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="title" id="annTitle" required placeholder="Enter title"></div>
                    <div class="mb-3"><label class="form-label">Content <span class="required-asterisk">*</span></label><textarea class="form-control" name="content" id="annContent" rows="4" required placeholder="Enter content"></textarea></div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status" id="annStatus"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- FAQ Modal -->
<div class="modal fade" id="faqModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="faqModalTitle">Add FAQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="faqForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="section" value="faqs">
                    <input type="hidden" name="id" id="faqId" value="0">
                    <div class="mb-3"><label class="form-label">Question <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="question" id="faqQuestion" required placeholder="Enter question"></div>
                    <div class="mb-3"><label class="form-label">Answer <span class="required-asterisk">*</span></label><textarea class="form-control" name="answer" id="faqAnswer" rows="4" required placeholder="Enter answer"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button></div>
            </form>
        </div>
    </div>  
</div>

<!-- First Aid Modal -->
<div class="modal fade" id="firstAidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="firstAidModalTitle">Add Guideline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="firstAidForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="section" value="first_aid">
                    <input type="hidden" name="id" id="faId" value="0">
                    <div class="mb-3"><label class="form-label">Title <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="title" id="faTitle" required placeholder="Enter title"></div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="hidden" name="icon" id="faIcon" value="general-first-aid">
                        <div class="icon-picker-preview d-flex align-items-center gap-2 mb-2 p-2 border rounded" id="faIconPreview" style="cursor:pointer;" onclick="document.getElementById('faIconDropdown').classList.toggle('show')">
                            <img src="<?php echo BASE_URL; ?>/assets/first-aid-icons/general-first-aid.png" id="faIconPreviewImg" style="width:28px;height:28px;object-fit:contain;">
                            <span id="faIconPreviewLabel" class="text-muted" style="font-size:0.875rem;">General First Aid</span>
                            <i class="bi bi-chevron-down ms-auto text-muted"></i>
                        </div>
                        <div class="icon-picker-dropdown border rounded shadow-sm" id="faIconDropdown" style="display:none;max-height:220px;overflow-y:auto;background:#fff;position:relative;z-index:10;">
                            <?php
                            $iconOptions = [
                                'allergic-reaction' => 'Allergic Reaction',
                                'asthma-attack' => 'Asthma Attack',
                                'burns' => 'Burns',
                                'chocking' => 'Choking',
                                'cpr-basic-life-support' => 'CPR / Life Support',
                                'cuts-and-wounds' => 'Cuts & Wounds',
                                'eye-injury' => 'Eye Injury',
                                'fainting-dizziness' => 'Fainting / Dizziness',
                                'fever' => 'Fever',
                                'fracture-and-sprains' => 'Fractures & Sprains',
                                'general-first-aid' => 'General First Aid',
                                'head-injury' => 'Head Injury',
                                'heat-stroke-dehydration' => 'Heat Stroke / Dehydration',
                                'insect-bite-and-sting' => 'Insect Bites & Stings',
                                'nosebleed' => 'Nosebleed',
                                'stomach-pain' => 'Stomach Pain',
                            ];
                            foreach ($iconOptions as $key => $label): ?>
                                <div class="icon-picker-option d-flex align-items-center gap-2 px-3 py-2" style="cursor:pointer;transition:background .15s;" data-icon="<?php echo $key; ?>" data-label="<?php echo $label; ?>" onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='transparent'">
                                    <img src="<?php echo BASE_URL; ?>/assets/first-aid-icons/<?php echo $key; ?>.png" style="width:24px;height:24px;object-fit:contain;">
                                    <span style="font-size:0.85rem;"><?php echo $label; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content <span class="required-asterisk">*</span></label>
                        <input type="hidden" name="content" id="faContentHidden">
                        <div id="faContentEditor" style="height:200px;background:#fff;border-radius:0 0 6px 6px;"></div>
                    </div>

                </div>  
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Emergency Contact Modal -->
<div class="modal fade" id="emergencyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emergencyModalTitle">Add Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="emergencyForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="section" value="emergency">
                    <input type="hidden" name="id" id="emId" value="0">
                    <div class="mb-3"><label class="form-label">Name <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="name" id="emName" required placeholder="Enter name"></div>
                    <div class="mb-3"><label class="form-label">Role</label><input type="text" class="form-control" name="role" id="emRole" placeholder="Enter role"></div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="required-asterisk">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">+63</span>
                            <input type="text" class="form-control" name="phone_number" id="emPhone" required placeholder="09xxxxxxxxx" minlength="11" maxlength="11" pattern="[0-9]{11}" title="Phone number must be exactly 11 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Quill Rich Text Editor -->
<link href="<?php echo BASE_URL; ?>/node_modules/quill/dist/quill.snow.css" rel="stylesheet">
<script src="<?php echo BASE_URL; ?>/node_modules/quill/dist/quill.min.js"></script>
<style>
    #firstAidModal .ql-toolbar { border-radius: 6px 6px 0 0; border-color: #dee2e6; background: #f8f9fa; }
    #firstAidModal .ql-container { border-color: #dee2e6; font-family: 'Inter', sans-serif; font-size: 14px; }
    #firstAidModal .ql-editor { min-height: 150px; }
    #firstAidModal .ql-editor.ql-blank::before { font-style: normal; color: #adb5bd; }
</style>

<script>
const csrf = '<?php echo getCSRFToken(); ?>';

const announcementModalEl = document.getElementById('announcementModal');
const announcementModal = new bootstrap.Modal(announcementModalEl);
const faqModalEl = document.getElementById('faqModal');
const faqModal = new bootstrap.Modal(faqModalEl);
const firstAidModalEl = document.getElementById('firstAidModal');
const firstAidModal = new bootstrap.Modal(firstAidModalEl);
const emergencyModalEl = document.getElementById('emergencyModal');
const emergencyModal = new bootstrap.Modal(emergencyModalEl);

function postAction(data) {
    data.append('csrf_token', csrf);
    return fetch('content.php', {method:'POST', body:data}).then(r=>r.json());
}

function handleResult(d) {
    if (d.success) {
        scheduleToast('success', d.message);
    } else {
        showAlert('error', 'Error', d.message);
    }
}

function deleteItem(section, id) {
    showConfirm('Delete?','Are you sure you want to delete this item?','Yes, Delete').then(r=>{
        if(r.isConfirmed){
            const fd=new FormData();fd.append('action','delete');fd.append('section',section);fd.append('id',id);
            postAction(fd).then(d=>{
                if(d.success){ scheduleToast('success', d.message); }
                else showAlert('error','Error',d.message);
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
    }).catch(err=>{ console.error('Edit fetch error:', err); showAlert('error','Error','Failed to load item data. Please try again.'); });
}

function addAnnouncement(){showAnnouncementForm(null);}
function showAnnouncementForm(item){
    document.getElementById('announcementModalTitle').textContent = item ? 'Edit Announcement' : 'Add Announcement';
    document.getElementById('annId').value = item ? item.id : 0;
    document.getElementById('annTitle').value = item ? (item.title||'') : '';
    document.getElementById('annContent').value = item ? (item.content||'') : '';
    document.getElementById('annStatus').value = item ? (item.status||'published') : 'published';
    announcementModal.show();
}

function addFaq(){showFaqForm(null);}
function showFaqForm(item){
    document.getElementById('faqModalTitle').textContent = item ? 'Edit FAQ' : 'Add FAQ';
    document.getElementById('faqId').value = item ? item.id : 0;
    document.getElementById('faqQuestion').value = item ? (item.question||'') : '';
    document.getElementById('faqAnswer').value = item ? (item.answer||'') : '';
    faqModal.show();
}

function addFirstAid(){showFirstAidForm(null);}
function showFirstAidForm(item){
    document.getElementById('firstAidModalTitle').textContent = item ? 'Edit Guideline' : 'Add Guideline';
    document.getElementById('faId').value = item ? item.id : 0;
    document.getElementById('faTitle').value = item ? (item.title||'') : '';

    const icon = item ? (item.icon || 'general-first-aid') : 'general-first-aid';
    selectIcon(icon);
    document.getElementById('faIconDropdown').classList.remove('show');
    firstAidModal.show();
    // Set Quill content after modal is shown
    setTimeout(function() {
        if (window.quillEditor) {
            const content = item ? (item.content||'') : '';
            if (content) {
                window.quillEditor.root.innerHTML = content;
            } else {
                window.quillEditor.setText('');
            }
        }
    }, 200);
}

function selectIcon(iconKey) {
    const baseUrl = '<?php echo BASE_URL; ?>';
    document.getElementById('faIcon').value = iconKey;
    document.getElementById('faIconPreviewImg').src = baseUrl + '/assets/first-aid-icons/' + iconKey + '.png';
    const option = document.querySelector('.icon-picker-option[data-icon="' + iconKey + '"]');
    document.getElementById('faIconPreviewLabel').textContent = option ? option.dataset.label : iconKey;
    document.getElementById('faIconDropdown').style.display = 'none';
    document.getElementById('faIconDropdown').classList.remove('show');
}

// Icon picker click handlers
document.querySelectorAll('.icon-picker-option').forEach(opt => {
    opt.addEventListener('click', function() {
        selectIcon(this.dataset.icon);
    });
});

// Toggle dropdown visibility
const faIconDropdown = document.getElementById('faIconDropdown');
const faIconPreview = document.getElementById('faIconPreview');
if (faIconPreview) {
    faIconPreview.addEventListener('click', function(e) {
        e.stopPropagation();
        faIconDropdown.style.display = faIconDropdown.style.display === 'none' ? 'block' : 'none';
    });
}
// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (faIconDropdown && !faIconDropdown.contains(e.target) && e.target !== faIconPreview) {
        faIconDropdown.style.display = 'none';
    }
});

function addEmergency(){showEmergencyForm(null);}
function showEmergencyForm(item){
    document.getElementById('emergencyModalTitle').textContent = item ? 'Edit Contact' : 'Add Contact';
    document.getElementById('emId').value = item ? item.id : 0;
    document.getElementById('emName').value = item ? (item.name||'') : '';
    document.getElementById('emRole').value = item ? (item.role||'') : '';
    document.getElementById('emPhone').value = item ? (item.phone_number||'') : '';
    emergencyModal.show();
}

function submitModalForm(formId, modalInstance) {
    document.getElementById(formId).addEventListener('submit', function(e){
        e.preventDefault();
        // Sync Quill content to hidden input before submit
        if (formId === 'firstAidForm' && window.quillEditor) {
            const html = window.quillEditor.root.innerHTML;
            document.getElementById('faContentHidden').value = (html === '<p><br></p>') ? '' : html;
            if (!document.getElementById('faContentHidden').value.trim()) {
                showAlert('error', 'Error', 'Content is required.');
                return;
            }
        }
        postAction(new FormData(this)).then(d=>{
            if(d.success){ modalInstance.hide(); scheduleToast('success', d.message); }
            else showAlert('error','Error',d.message);
        });
    });
}
submitModalForm('announcementForm', announcementModal);
submitModalForm('faqForm', faqModal);
submitModalForm('firstAidForm', firstAidModal);
submitModalForm('emergencyForm', emergencyModal);

// Initialize Quill rich text editor for First Aid content
window.quillEditor = new Quill('#faContentEditor', {
    theme: 'snow',
    placeholder: 'Type your first aid instructions here...',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    }
});

document.getElementById('hoursForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    postAction(new FormData(this)).then(d=>{
        if(d.success){ scheduleToast('success', d.message); }
        else showAlert('error','Error',d.message);
    });
});
</script>

<?php
/**
 * CampusCare - Public Landing Page
 * Guest-accessible page showing announcements, FAQs, first-aid, emergency contacts, clinic hours
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Fetch public data
$announcements = $db->fetchAll("SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT 6");
$faqs = $db->fetchAll("SELECT * FROM faqs WHERE status = 'active' ORDER BY sort_order ASC");
$firstAidGuidelines = $db->fetchAll("SELECT * FROM first_aid_guidelines WHERE status = 'active' ORDER BY sort_order ASC");
$emergencyContacts = $db->fetchAll("SELECT * FROM clinic_emergency_contacts WHERE status = 'active' ORDER BY sort_order ASC");
$clinicHours = $db->fetchAll("SELECT * FROM clinic_hours ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CampusCare - School Clinic Patient Information & Medicine Record System. View announcements, FAQs, first-aid guidelines, and emergency contacts.">
    <title><?php echo APP_NAME; ?> - <?php echo APP_TAGLINE; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark public-navbar fixed-top" style="box-shadow: 0 2px 15px rgba(0,0,0,0.15);">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="#">
                <i class="bi bi-heart-pulse-fill me-2 fs-4"></i><?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#announcements">Announcements</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faqs">FAQs</a></li>
                    <li class="nav-item"><a class="nav-link" href="#firstaid">First Aid</a></li>
                    <li class="nav-item"><a class="nav-link" href="#emergency">Emergency</a></li>
                    <li class="nav-item"><a class="nav-link" href="#hours">Hours</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light btn-sm px-3" href="<?php echo BASE_URL; ?>/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Staff Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="public-hero" style="padding-top: 7rem;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="display-5 fw-bold mb-3 animate-fade-in"><?php echo APP_NAME; ?></h1>
                    <p class="lead mb-4 opacity-75 animate-fade-in animate-delay-1"><?php echo APP_TAGLINE; ?></p>
                    <p class="mb-4 opacity-75 animate-fade-in animate-delay-2">
                        Your campus health partner. Access clinic information, announcements, first-aid guidelines, and emergency contacts all in one place.
                    </p>
                    <div class="animate-fade-in animate-delay-3">
                        <a href="#announcements" class="btn btn-light btn-lg me-2 px-4 fw-semibold">
                            <i class="bi bi-megaphone me-2"></i>Latest Updates
                        </a>
                        <a href="#emergency" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-telephone me-2"></i>Emergency
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                    <div class="text-center" style="opacity: 0.15;">
                        <i class="bi bi-heart-pulse-fill" style="font-size: 15rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Announcements -->
    <section class="public-section" id="announcements">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title"><i class="bi bi-megaphone-fill text-primary-cc me-2"></i>Announcements</h2>
                <p class="section-subtitle">Stay updated with the latest clinic news and events</p>
            </div>
            <?php if (empty($announcements)): ?>
            <div class="empty-state"><i class="bi bi-megaphone"></i><p>No announcements at this time.</p></div>
            <?php
else: ?>
            <div class="row g-4">
                <?php foreach ($announcements as $ann): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="public-card card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-success me-2">New</span>
                                <small class="text-muted"><?php echo formatDate($ann['created_at']); ?></small>
                            </div>
                            <h5 class="card-title fw-bold mb-2" style="font-size: 1rem;"><?php echo e($ann['title']); ?></h5>
                            <p class="card-text text-muted" style="font-size: 0.875rem;"><?php echo e(substr($ann['content'], 0, 150)); ?>...</p>
                        </div>
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>
            <?php
endif; ?>
        </div>
    </section>

    <!-- FAQs -->
    <section class="public-section" id="faqs">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title"><i class="bi bi-question-circle-fill text-primary-cc me-2"></i>Frequently Asked Questions</h2>
                <p class="section-subtitle">Common questions about our clinic services</p>
            </div>
            <?php if (empty($faqs)): ?>
            <div class="empty-state"><i class="bi bi-question-circle"></i><p>No FAQs available.</p></div>
            <?php
else: ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $i => $faq): ?>
                        <div class="accordion-item border-0 mb-2" style="border-radius: 10px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,0.05);">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo $i > 0 ? 'collapsed' : ''; ?> fw-semibold" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq<?php echo $faq['id']; ?>" style="font-size: 0.9rem;">
                                    <?php echo e($faq['question']); ?>
                                </button>
                            </h2>
                            <div id="faq<?php echo $faq['id']; ?>" class="accordion-collapse collapse <?php echo $i === 0 ? 'show' : ''; ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted" style="font-size: 0.875rem;">
                                    <?php echo e($faq['answer']); ?>
                                </div>
                            </div>
                        </div>
                        <?php
    endforeach; ?>
                    </div>
                </div>
            </div>
            <?php
endif; ?>
        </div>
    </section>

    <!-- First Aid Guidelines -->
    <section class="public-section" id="firstaid">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title"><i class="bi bi-bandaid-fill text-primary-cc me-2"></i>First Aid Guidelines</h2>
                <p class="section-subtitle">Basic first-aid steps for common emergencies</p>
            </div>
            <?php if (empty($firstAidGuidelines)): ?>
            <div class="empty-state"><i class="bi bi-bandaid"></i><p>No guidelines available.</p></div>
            <?php
else: ?>
            <div class="row g-4">
                <?php foreach ($firstAidGuidelines as $guide): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="public-card card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div style="width:40px;height:40px;border-radius:10px;background:var(--cc-primary-bg);display:flex;align-items:center;justify-content:center;" class="me-3">
                                    <i class="bi bi-bandaid text-primary-cc"></i>
                                </div>
                                <h5 class="card-title fw-bold mb-0" style="font-size: 0.95rem;"><?php echo e($guide['title']); ?></h5>
                            </div>
                            <div class="text-muted" style="font-size: 0.85rem;"><?php echo $guide['content']; ?></div>
                        </div>
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>
            <?php
endif; ?>
        </div>
    </section>

    <!-- Emergency Contacts -->
    <section class="public-section" id="emergency" style="background: linear-gradient(135deg, #fdecea, #fff5f5);">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title"><i class="bi bi-telephone-fill" style="color: var(--cc-danger);"></i> Emergency Contacts</h2>
                <p class="section-subtitle">Important numbers for emergencies</p>
            </div>
            <?php if (empty($emergencyContacts)): ?>
            <div class="empty-state"><i class="bi bi-telephone"></i><p>No emergency contacts listed.</p></div>
            <?php
else: ?>
            <div class="row g-3 justify-content-center">
                <?php foreach ($emergencyContacts as $contact): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 h-100" style="border-radius: 12px;">
                        <div class="card-body d-flex align-items-center">
                            <div style="width:48px;height:48px;border-radius:12px;background:var(--cc-danger-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;" class="me-3">
                                <i class="bi bi-telephone-fill" style="color: var(--cc-danger);"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0" style="font-size: 0.9rem;"><?php echo e($contact['name']); ?></h6>
                                <?php if ($contact['role']): ?>
                                <small class="text-muted"><?php echo e($contact['role']); ?></small><br>
                                <?php
        endif; ?>
                                <a href="tel:<?php echo e($contact['phone_number']); ?>" class="fw-semibold" style="color: var(--cc-danger);">
                                    <?php echo e($contact['phone_number']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>
            <?php
endif; ?>
        </div>
    </section>

    <!-- Clinic Hours -->
    <section class="public-section" id="hours">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title"><i class="bi bi-clock-fill text-primary-cc me-2"></i>Clinic Hours</h2>
                <p class="section-subtitle">Our operating schedule</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card border-0" style="border-radius: 12px;">
                        <div class="card-body p-0">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <?php foreach ($clinicHours as $hour): ?>
                                    <tr>
                                        <td class="fw-semibold py-3 ps-4" style="font-size: 0.9rem;"><?php echo e($hour['day_of_week']); ?></td>
                                        <td class="text-end py-3 pe-4" style="font-size: 0.9rem;">
                                            <?php if ($hour['is_closed']): ?>
                                                <span class="badge bg-secondary">Closed</span>
                                            <?php
    else: ?>
                                                <?php echo formatTime($hour['opening_time']); ?> - <?php echo formatTime($hour['closing_time']); ?>
                                                <?php if ($hour['notes']): ?>
                                                <br><small class="text-muted"><?php echo e($hour['notes']); ?></small>
                                                <?php
        endif; ?>
                                            <?php
    endif; ?>
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
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4" style="background: #1a2332; color: rgba(255,255,255,0.6);">
        <div class="container text-center">
            <p class="mb-1"><i class="bi bi-heart-pulse-fill me-1"></i> <?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?></p>
            <small><?php echo APP_TAGLINE; ?></small>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Smooth scroll for nav links -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offset = 80;
                    const pos = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({ top: pos, behavior: 'smooth' });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.public-navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(9, 77, 44, 0.95)';
                navbar.style.backdropFilter = 'blur(10px)';
            } else {
                navbar.style.background = '';
                navbar.style.backdropFilter = '';
            }
        });
    </script>
</body>
</html>

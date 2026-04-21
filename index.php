<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Fetch public data
$announcements = $db->fetchAll("SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT 6");
<<<<<<< HEAD
$faqs = $db->fetchAll("SELECT * FROM faqs WHERE status = 'active' ORDER BY id ASC");
$firstAidGuidelines = $db->fetchAll("SELECT * FROM first_aid_guidelines WHERE status = 'active' ORDER BY id ASC");
=======
$faqs = $db->fetchAll("SELECT * FROM faqs WHERE status = 'active' ORDER BY sort_order ASC");
$firstAidGuidelines = $db->fetchAll("SELECT * FROM first_aid_guidelines WHERE status = 'active' ORDER BY sort_order ASC");
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo-main-w.png">
<<<<<<< HEAD
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
=======
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <div class="public-navbar-wrapper fixed-top">
        <nav class="navbar navbar-expand-lg public-navbar">
            <div class="container-fluid px-3">
                <a class="navbar-brand d-flex align-items-center fw-bold" href="#">
                    <img src="<?php echo BASE_URL; ?>/assets/logo-main-b.png" alt="<?php echo APP_NAME; ?>" style="width:28px;height:28px;object-fit:contain;" class="me-2"><?php echo APP_NAME; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="publicNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item"><a class="nav-link" href="#announcements">Announcements</a></li>
                        <li class="nav-item"><a class="nav-link" href="#firstaid">First Aid</a></li>
                        <li class="nav-item"><a class="nav-link" href="#faqs">FAQs</a></li>
                        <li class="nav-item"><a class="nav-link" href="#emergency">Emergency</a></li>
                        <li class="nav-item"><a class="nav-link" href="#hours">Hours</a></li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn public-navbar-login-btn" href="<?php echo BASE_URL; ?>/login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Staff Login
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <!-- Floating background blobs (same as login page) -->
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>
        <div class="hero-blob hero-blob-3"></div>


        <div class="container position-relative" style="z-index: 2;">
            <div class="row align-items-center hero-row">
                <!-- Left: Text Content -->
                <div class="col-lg-6 text-white hero-text-col text-center d-flex flex-column align-items-center">
                    <h1 class="hero-title">
                        <span class="hero-title-campus">Campus</span><span class="hero-title-care">Care</span>
                    </h1>
                    <h2 class="hero-subtitle"><?php echo APP_TAGLINE; ?></h2>
                    <p class="hero-description">
                        Your campus health partner. Access clinic information, announcements, first-aid guidelines, and emergency contacts all in one place.
                    </p>
                    <div class="d-flex flex-row flex-wrap gap-3 justify-content-center">
                        <a href="#announcements" class="btn hero-btn-primary">
                            Latest Updates
                        </a>
                        <a href="#emergency" class="btn hero-btn-outline">
                            Emergencies
                        </a>
                    </div>
                </div>

                <!-- Right: Clinic Image Carousel -->
                <div class="col-lg-6 hero-image-col">
                    <div class="hero-image-wrapper">
                        <div id="heroClinicCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2500">
                            <div class="carousel-indicators hero-carousel-dots">
                                <button type="button" data-bs-target="#heroClinicCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#heroClinicCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#heroClinicCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                                <button type="button" data-bs-target="#heroClinicCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
                            </div>
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="<?php echo BASE_URL; ?>/assets/clinic1.jpg" alt="Campus Clinic" class="hero-image">
                                </div>
                                <div class="carousel-item">
                                    <img src="<?php echo BASE_URL; ?>/assets/clinic2.jpg" alt="Campus Clinic" class="hero-image">
                                </div>
                                <div class="carousel-item">
                                    <img src="<?php echo BASE_URL; ?>/assets/clinic3.jpg" alt="Campus Clinic" class="hero-image">
                                </div>
                                <div class="carousel-item">
                                    <img src="<?php echo BASE_URL; ?>/assets/clinic4.jpg" alt="Campus Clinic" class="hero-image">
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#heroClinicCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#heroClinicCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
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
                            <p class="card-text text-muted" style="font-size: 0.875rem;"><?php echo e($ann['content']); ?></p>
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
            <div class="row g-4 align-items-start">
                <?php foreach ($firstAidGuidelines as $guide): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="public-card card fa-expand-card">
                        <div class="card-body p-0">
                            <div class="fa-expand-header d-flex align-items-center p-3" role="button" data-bs-toggle="collapse" data-bs-target="#faGuide<?php echo $guide['id']; ?>" aria-expanded="false">
                                <div class="fa-icon-box me-3">
                                    <img src="<?php echo BASE_URL; ?>/assets/first-aid-icons/<?php echo e($guide['icon'] ?? 'general-first-aid'); ?>.png" alt="" style="width:24px;height:24px;object-fit:contain;">
                                </div>
                                <h5 class="card-title fw-bold mb-0 flex-grow-1" style="font-size: 0.95rem;"><?php echo e($guide['title']); ?></h5>
                                <i class="bi bi-chevron-down fa-expand-chevron"></i>
                            </div>
                            <div class="collapse" id="faGuide<?php echo $guide['id']; ?>">
                                <div class="fa-expand-content text-muted px-3 pb-3" style="font-size: 0.85rem;">
                                    <?php echo $guide['content']; ?>
                                    <div class="fa-export-actions mt-2 pt-2">
                                        <a href="<?php echo BASE_URL; ?>/export_firstaid_pdf.php?id=<?php echo $guide['id']; ?>" 
                                           class="btn btn-sm fa-export-btn" 
                                           onclick="event.stopPropagation();" 
                                           title="Download as PDF"
                                           target="_blank">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>Save as PDF
                                        </a>
                                    </div>
                                </div>
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
                <div class="col-lg-6 col-md-8">
                    <div class="clinic-hours-schedule">
                        <?php 
                        $daysOfWeek = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                        $todayName = $daysOfWeek[date('w')];
                        $dayIcons = [
                            'Monday' => 'bi-calendar-event',
                            'Tuesday' => 'bi-calendar-event',
                            'Wednesday' => 'bi-calendar-event',
                            'Thursday' => 'bi-calendar-event',
                            'Friday' => 'bi-calendar-event',
                            'Saturday' => 'bi-calendar-heart',
                            'Sunday' => 'bi-calendar-heart',
                        ];
                        foreach ($clinicHours as $hour): 
                            $isToday = ($hour['day_of_week'] === $todayName);
                        ?>
                        <div class="clinic-hours-row <?php echo $isToday ? 'clinic-hours-today' : ''; ?>">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                <div class="clinic-hours-day-icon <?php echo $isToday ? 'active' : ''; ?>">
                                    <i class="bi <?php echo $dayIcons[$hour['day_of_week']] ?? 'bi-calendar-event'; ?>"></i>
                                </div>
                                <div>
                                    <div class="clinic-hours-day-name <?php echo $isToday ? 'fw-bold' : ''; ?>">
                                        <?php echo e($hour['day_of_week']); ?>
                                        <?php if ($isToday): ?>
                                            <?php if ($hour['is_closed']): ?>
                                                <span class="clinic-hours-status-badge closed">Closed</span>
                                            <?php else:
                                                $now = date('H:i:s');
                                                $isOpen = ($now >= $hour['opening_time'] && $now <= $hour['closing_time']);
                                            ?>
                                                <span class="clinic-hours-status-badge <?php echo $isOpen ? 'open' : 'closed'; ?>"><?php echo $isOpen ? 'Open' : 'Closed'; ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($hour['notes'] && !$hour['is_closed']): ?>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?php echo e($hour['notes']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="clinic-hours-time">
                                <?php if ($hour['is_closed']): ?>
                                    <span class="clinic-hours-closed-badge">Closed</span>
                                <?php else: ?>
                                    <span class="clinic-hours-open-time"><?php echo formatTime($hour['opening_time']); ?> – <?php echo formatTime($hour['closing_time']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4" style="background: #1a2332; color: rgba(255,255,255,0.6);">
        <div class="container text-center">
            <img src="<?php echo BASE_URL; ?>/assets/logo-main-w.png" alt="<?php echo APP_NAME; ?>" style="width:24px;height:24px;object-fit:contain;" class="mb-1">
            <small> · <?php echo APP_NAME; ?> · <?php echo date('Y'); ?></small>
        </div>
    </footer>

<<<<<<< HEAD
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
=======
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
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

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.public-navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>

<?php
/**
 * Export First Aid Guideline as PDF
 * Public endpoint — no login required.
 * Usage: export_firstaid_pdf.php?id=<guideline_id>
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validate ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid guideline ID.');
}

// Fetch guideline
$db = Database::getInstance();
$guide = $db->fetch(
    "SELECT * FROM first_aid_guidelines WHERE id = ? AND status = 'active'",
<<<<<<< HEAD
[$id]
=======
    [$id]
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
);

if (!$guide) {
    http_response_code(404);
    exit('Guideline not found.');
}

<<<<<<< HEAD
$title = htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8');
=======
$title   = htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8');
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
$content = $guide['content']; // already HTML from Quill editor

// Resolve icon as base64 data URI so Dompdf can embed it
$iconFile = ($guide['icon'] ?? 'general-first-aid') . '.png';
$iconPath = __DIR__ . '/assets/first-aid-icons/' . $iconFile;
$iconHtml = '';
if (file_exists($iconPath)) {
<<<<<<< HEAD
    $iconData = file_get_contents($iconPath);
    $iconDataUri = 'data:image/png;base64,' . base64_encode($iconData);
    $iconHtml = '<img class="icon-img" src="' . $iconDataUri . '" alt="">';
=======
    $iconData    = file_get_contents($iconPath);
    $iconDataUri = 'data:image/png;base64,' . base64_encode($iconData);
    $iconHtml    = '<img class="icon-img" src="' . $iconDataUri . '" alt="">';
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
}

// Resolve logo as base64 data URI
$logoPath = __DIR__ . '/assets/logo-main-b.png';
$logoHtml = '';
if (file_exists($logoPath)) {
<<<<<<< HEAD
    $logoData = file_get_contents($logoPath);
    $logoDataUri = 'data:image/png;base64,' . base64_encode($logoData);
    $logoHtml = '<img src="' . $logoDataUri . '" style="width:22px;height:22px;vertical-align:middle;margin-right:6px;">';
=======
    $logoData    = file_get_contents($logoPath);
    $logoDataUri = 'data:image/png;base64,' . base64_encode($logoData);
    $logoHtml    = '<img src="' . $logoDataUri . '" style="width:22px;height:22px;vertical-align:middle;margin-right:6px;">';
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
}

// Build HTML for PDF
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 40px 50px;
    }
    body {
        font-family: "Helvetica", "Arial", sans-serif;
        color: #2c3e50;
        font-size: 13px;
        line-height: 1.6;
    }
    .header {
        border-bottom: 2px solid #005a9c;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }
    .brand {
        font-size: 14px;
        color: #005a9c;
        font-weight: 700;
        letter-spacing: 0.3px;
        margin-bottom: 2px;
    }
    .brand-tagline {
        font-size: 9px;
        color: #6b7c93;
        margin-top: 2px;
    }
    .title-row {
        margin-top: 18px;
        margin-bottom: 18px;
    }
    .icon-img {
        width: 36px;
        height: 36px;
        vertical-align: middle;
        margin-right: 10px;
    }
    .guideline-title {
        font-size: 20px;
        font-weight: 700;
        color: #1a2332;
        vertical-align: middle;
    }
    .content {
        font-size: 13px;
        color: #3d4f5f;
        line-height: 1.7;
    }
    .content ul, .content ol {
        padding-left: 22px;
        margin: 8px 0;
    }
    .content li {
        margin-bottom: 4px;
    }
    .content strong, .content b {
        color: #1a2332;
    }
    .content a {
        color: #005a9c;
    }
    .footer {
        margin-top: 30px;
        padding-top: 10px;
        border-top: 1px solid #e2e8e5;
        font-size: 9px;
        color: #9ca3af;
        text-align: center;
    }
</style>
</head>
<body>
    <div class="header">
        <div class="brand">' . $logoHtml . 'CampusCare</div>
        <div class="brand-tagline">School Clinic &mdash; First Aid Guideline</div>
    </div>

    <div class="title-row">
        ' . $iconHtml . '<span class="guideline-title">' . $title . '</span>
    </div>

    <div class="content">
        ' . $content . '
    </div>

    <div class="footer">
        Generated from CampusCare &middot; ' . date('F j, Y') . '
    </div>
</body>
</html>';

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Clean filename from guideline title
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $guide['title']);
$filename = 'FirstAid_' . $filename . '.pdf';

// Stream the PDF (attachment = force download)
<<<<<<< HEAD
$dompdf->stream($filename, ['Attachment' => true]);
=======
$dompdf->stream($filename, ['Attachment' => true]);
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af

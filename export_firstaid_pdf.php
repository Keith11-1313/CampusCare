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
    [$id]
);

if (!$guide) {
    http_response_code(404);
    exit('Guideline not found.');
}

$title   = htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8');
$content = $guide['content']; // already HTML from Quill editor

// Resolve icon as base64 data URI so Dompdf can embed it
$iconName = $guide['icon'] ?? 'general-first-aid';
// Normalize icon name (lowercase, no extension)
$iconName = strtolower(preg_replace('/\.png$/i', '', $iconName));
$iconFile = $iconName . '.png';
$iconPath = __DIR__ . '/assets/first-aid-icons/' . $iconFile;

// Fallback to general icon if specific one doesn't exist
if (!file_exists($iconPath)) {
    $iconPath = __DIR__ . '/assets/first-aid-icons/general-first-aid.png';
}

$iconHtml = '';
if (file_exists($iconPath)) {
    $iconData    = file_get_contents($iconPath);
    $iconDataUri = 'data:image/png;base64,' . base64_encode($iconData);
    $iconHtml    = '<img class="icon-img" src="' . $iconDataUri . '" alt="">';
}

// Resolve logo as base64 data URI
$logoPath = __DIR__ . '/assets/logo-main-b.png';
$logoHtml = '';
if (file_exists($logoPath)) {
    $logoData    = file_get_contents($logoPath);
    $logoDataUri = 'data:image/png;base64,' . base64_encode($logoData);
    $logoHtml    = '<img src="' . $logoDataUri . '" class="logo-img" alt="Logo">';
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
        padding-bottom: 15px;
        margin-bottom: 25px;
        position: relative;
    }
    .brand {
        display: block;
        font-size: 16px;
        color: #005a9c;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .logo-img {
        width: 24px;
        height: 24px;
        vertical-align: middle;
        margin-right: 8px;
    }
    .brand-tagline {
        font-size: 10px;
        color: #6b7c93;
        margin-top: 2px;
        font-weight: 400;
    }
    .title-row {
        margin-top: 20px;
        margin-bottom: 20px;
        padding: 10px 0;
    }
    .icon-img {
        width: 42px;
        height: 42px;
        vertical-align: middle;
        margin-right: 15px;
    }
    .guideline-title {
        font-size: 24px;
        font-weight: 700;
        color: #1a2332;
        vertical-align: middle;
    }
    .content {
        font-size: 13px;
        color: #3d4f5f;
        line-height: 1.8;
    }
    .content ul, .content ol {
        padding-left: 25px;
        margin: 10px 0;
    }
    .content li {
        margin-bottom: 6px;
    }
    .content strong, .content b {
        color: #1a2332;
    }
    .content a {
        color: #005a9c;
        text-decoration: none;
    }
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        padding-top: 15px;
        border-top: 1px solid #e5e7eb;
        font-size: 9px;
        color: #9ca3af;
        text-align: center;
    }
</style>
</head>
<body>
    <div class="header">
        <div class="brand">' . $logoHtml . 'CampusCare</div>
        <div class="brand-tagline">School Clinic &mdash; Official First Aid Guideline</div>
    </div>

    <div class="title-row">
        ' . $iconHtml . '<span class="guideline-title">' . $title . '</span>
    </div>

    <div class="content">
        ' . $content . '
    </div>

    <div class="footer">
        Generated from CampusCare Patient Information System &middot; ' . date('F j, Y, g:i a') . '
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
$dompdf->stream($filename, ['Attachment' => true]);

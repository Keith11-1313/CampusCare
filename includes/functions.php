<?php
/**
 * CampusCare - Utility Functions
 * Shared helper functions used across the application
 */

/**
 * Sanitize output for HTML display (prevent XSS)
 */
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input string
 */
function sanitize($input)
{
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(htmlspecialchars(strip_tags($input ?? ''), ENT_QUOTES, 'UTF-8'));
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y')
{
    if (empty($date))
        return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A')
{
    if (empty($datetime))
        return 'N/A';
    return date($format, strtotime($datetime));
}

/**
 * Format time for display
 */
function formatTime($time, $format = 'h:i A')
{
    if (empty($time))
        return 'N/A';
    return date($format, strtotime($time));
}

/**
 * Calculate age from date of birth
 */
function calculateAge($dob)
{
    if (empty($dob))
        return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y;
}

/**
 * Generate pagination HTML
 */
function generatePagination($currentPage, $totalPages, $baseUrl)
{
    if ($totalPages <= 1)
        return '';

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">';

    // Previous button
    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prevDisabled . '"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '"><i class="bi bi-chevron-left"></i></a></li>';

    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
        if ($start > 2)
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1)
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }

    // Next button
    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $nextDisabled . '"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '"><i class="bi bi-chevron-right"></i></a></li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Generate a sortable table header <th> element
 */
function sortableHeader($label, $column, $currentSort, $currentOrder, $extraClass = '')
{
    // Build URL preserving all current GET params
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    $params['page'] = 1; // Reset to page 1 on sort change
    $url = '?' . http_build_query($params);

    $icon = '';
    $activeClass = '';
    if ($currentSort === $column) {
        $activeClass = ' sortable-active';
        $icon = $currentOrder === 'asc'
            ? ' <i class="bi bi-caret-up-fill sort-icon"></i>'
            : ' <i class="bi bi-caret-down-fill sort-icon"></i>';
    } else {
        $icon = ' <i class="bi bi-chevron-expand sort-icon-idle"></i>';
    }

    $cls = 'sortable-th' . $activeClass . ($extraClass ? ' ' . $extraClass : '');
    return '<th class="' . $cls . '"><a href="' . e($url) . '">' . e($label) . $icon . '</a></th>';
}

/**
 * Get status badge HTML
 */
function statusBadge($status)
{
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'archived' => '<span class="badge bg-warning text-dark">Archived</span>',
        'published' => '<span class="badge bg-success">Published</span>',
        'draft' => '<span class="badge bg-secondary">Draft</span>',
        'Completed' => '<span class="badge bg-success">Completed</span>',
        'Follow-up' => '<span class="badge bg-warning text-dark">Follow-up</span>',
        'Referred' => '<span class="badge bg-info">Referred</span>',
        'Mild' => '<span class="badge bg-success">Mild</span>',
        'Moderate' => '<span class="badge bg-warning text-dark">Moderate</span>',
        'Severe' => '<span class="badge bg-danger">Severe</span>',
        'Active' => '<span class="badge bg-danger">Active</span>',
<<<<<<< HEAD
=======
        'Managed' => '<span class="badge bg-info">Managed</span>',
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
        'Resolved' => '<span class="badge bg-success">Resolved</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . e($status) . '</span>';
}

/**
 * Truncate text to specified length
 */
function truncate($text, $length = 100)
{
    if (strlen($text) <= $length)
        return e($text);
    return e(substr($text, 0, $length)) . '…';
}

/**
 * Check if the current page matches a given path (for sidebar active state)
 */
function isActivePage($page)
{
    $currentPage = basename($_SERVER['PHP_SELF']);
    if (is_array($page)) {
        return in_array($currentPage, $page) ? 'active' : '';
    }
    return ($currentPage === $page) ? 'active' : '';
}

/**
 * Get ordinal suffix for a number
 */
function ordinal($number)
{
    $suffixes = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    if ((($number % 100) >= 11) && (($number % 100) <= 13))
        return $number . 'th';
    return $number . $suffixes[$number % 10];
}

/**
 * Redirect with optional flash message
 */
function redirect($url, $flashType = null, $flashMessage = null)
{
    if ($flashType && $flashMessage) {
        setFlashMessage($flashType, $flashMessage);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Return JSON response (for AJAX endpoints)
 */
function jsonResponse($data, $statusCode = 200)
{
    // Clear any buffered output (e.g. HTML from header.php) before sending JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
<<<<<<< HEAD

/**
 * Validate password strength.
 * Requirements: min 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character.
 * @param string $password The password to validate
 * @return array Array of error messages (empty array = valid)
 */
function validatePasswordStrength($password)
{
    $errors = [];
    if (strlen($password) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password))
        $errors[] = 'Password must contain at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $password))
        $errors[] = 'Password must contain at least one lowercase letter.';
    if (!preg_match('/[0-9]/', $password))
        $errors[] = 'Password must contain at least one number.';
    if (!preg_match('/[^a-zA-Z0-9]/', $password))
        $errors[] = 'Password must contain at least one special character.';
    return $errors;
}
=======
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af

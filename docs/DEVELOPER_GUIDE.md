# CampusCare — Developer Guide

> A comprehensive reference for developers working on the CampusCare codebase.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Project Structure](#project-structure)
3. [Environment Setup](#environment-setup)
4. [Configuration](#configuration)
5. [Database Layer](#database-layer)
6. [Authentication & Authorization](#authentication--authorization)
7. [Template System (Layouts)](#template-system-layouts)
8. [Page Anatomy](#page-anatomy)
9. [CRUD & AJAX Pattern](#crud--ajax-pattern)
10. [Frontend Stack](#frontend-stack)
11. [Utility Functions Reference](#utility-functions-reference)
12. [JavaScript API Reference](#javascript-api-reference)
13. [Security Practices](#security-practices)
14. [Adding a New Feature (Tutorial)](#adding-a-new-feature-tutorial)
15. [Coding Standards](#coding-standards)
16. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

CampusCare is a **vanilla PHP** application with no framework. It follows a simple **page-based architecture** where each `.php` file is both the controller and the view.

```
Browser Request
      │
      ▼
  Apache / XAMPP (mod_rewrite)
      │
      ▼
  PHP Page (e.g. admin/users.php)
      │
      ├──► includes/header.php   (auth check, HTML head, navbar)
      ├──► includes/sidebar.php  (role-based navigation)
      ├──► [Page content]        (queries, HTML output)
      └──► includes/footer.php   (JS libraries, flash messages)
      │
      ▼
  config/database.php  ──►  MySQL (PDO)
```

### Key Design Decisions

| Pattern | Implementation |
|---------|---------------|
| Database access | Singleton PDO wrapper (`Database` class) |
| Authentication | Session-based with bcrypt password hashing |
| Authorization | Role-based access control via `requireRole()` |
| CSRF protection | Per-session token, validated on every POST |
| Flash messages | Session-stored, rendered via SweetAlert2 toasts |
| AJAX operations | Same PHP file handles both page render (GET) and API calls (POST) |
| XSS prevention | `e()` helper wrapping `htmlspecialchars()` |

---

## Project Structure

```
CampusCare/
├── admin/                  # Admin module (role: admin)
│   ├── dashboard.php       # Admin dashboard with stats
│   ├── users.php           # User CRUD with AJAX
│   ├── programs.php        # Academic programs management
│   ├── year_levels.php     # Year levels management
│   ├── access_logs.php     # Audit trail viewer
│   ├── archive.php         # Archived/soft-deleted records
│   └── reports.php         # Charts + CSV/PDF export
│
├── nurse/                  # Nurse/Staff module (role: nurse)
│   ├── dashboard.php       # Nurse dashboard
│   ├── students.php        # Student search & listing
│   ├── student_profile.php # Full student health profile
│   ├── new_visit.php       # Log new clinic visit
│   ├── visits.php          # Visit history browser
│   └── content.php         # Public content manager (announcements, FAQs, etc.)
│
├── rep/                    # Class Representative module (role: rep)
│   ├── dashboard.php       # Rep dashboard
│   └── students.php        # Scoped student CRUD
│
├── config/
│   ├── config.php          # Constants: DB, app settings, paths, timezone
│   └── database.php        # Singleton PDO Database class
│
├── includes/
│   ├── session.php         # Session init, CSRF, flash messages
│   ├── auth.php            # Login checks, role guards, audit logging
│   ├── functions.php       # Shared PHP utilities (sanitize, format, pagination, etc.)
│   ├── header.php          # HTML <head>, Bootstrap CSS, top navbar
│   ├── sidebar.php         # Role-aware sidebar navigation
│   ├── footer.php          # JS bundle, flash message rendering
│   ├── export_csv.php      # CSV report download endpoint
│   └── export_pdf.php      # HTML-based printable PDF report
│
├── database/
│   ├── campuscare.sql      # Full database schema (13 tables)
│   └── seed_data.sql       # Sample data for development
│
├── css/style.css           # Custom CSS (design system, components)
├── js/app.js               # Shared JavaScript utilities
├── index.php               # Public landing page
├── login.php               # Login page (standalone layout)
├── logout.php              # Session destroy + redirect
├── change_password.php     # Password change form
├── package.json            # npm dependencies (Bootstrap, Chart.js, SweetAlert2)
└── README.md               # Project overview
```

---

## Environment Setup

### Prerequisites

| Software | Version | Purpose |
|----------|---------|---------|
| PHP | 7.4+ | Backend runtime |
| MySQL | 5.7+ / MariaDB 10.3+ | Database |
| Apache | 2.4+ (with `mod_rewrite`) | Web server |
| Node.js + npm | Any LTS | Frontend dependency management |
| XAMPP / WAMP / MAMP | Latest | Recommended all-in-one local stack |

### Quick Start

```bash
# 1. Place project in web root
cp -r CampusCare /path/to/htdocs/

# 2. Install frontend dependencies
cd /path/to/htdocs/CampusCare
npm install

# 3. Create database
mysql -u root -e "CREATE DATABASE campuscare"
mysql -u root campuscare < database/campuscare.sql
mysql -u root campuscare < database/seed_data.sql    # optional sample data

# 4. Start Apache & MySQL (via XAMPP control panel)

# 5. Open browser
# http://localhost/CampusCare
```

---

## Configuration

All application constants live in `config/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'campuscare');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application
define('APP_NAME', 'CampusCare');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/CampusCare');       // adjust for deployment

// Session
define('SESSION_LIFETIME', 3600);        // 1 hour
define('SESSION_NAME', 'CAMPUSCARE_SESSION');

// File Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');

// Timezone
date_default_timezone_set('Asia/Manila');
```

> **Deployment tip:** Update `DB_USER`, `DB_PASS`, and `BASE_URL` for your production environment.

---

## Database Layer

### The `Database` Class

Located in `config/database.php`, this is a **singleton** PDO wrapper. You never call `new Database()` directly.

```php
// Get the database instance (anywhere in your code)
$db = Database::getInstance();
```

### Available Methods

| Method | Returns | Usage |
|--------|---------|-------|
| `query($sql, $params)` | `PDOStatement` | Run any prepared statement |
| `fetchAll($sql, $params)` | `array` | Get all rows as associative arrays |
| `fetch($sql, $params)` | `array\|false` | Get a single row |
| `fetchColumn($sql, $params)` | `mixed` | Get a single scalar value |
| `lastInsertId()` | `string` | ID of the last inserted row |
| `beginTransaction()` | `bool` | Start a transaction |
| `commit()` | `bool` | Commit transaction |
| `rollback()` | `bool` | Rollback transaction |

### Examples

```php
$db = Database::getInstance();

// Fetch all active students
$students = $db->fetchAll("SELECT * FROM students WHERE status = ?", ['active']);

// Fetch one
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [42]);

// Count
$total = $db->fetchColumn("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = CURDATE()");

// Insert
$db->query(
    "INSERT INTO students (student_id, first_name, last_name) VALUES (?, ?, ?)",
    ['2024-0001', 'Juan', 'Dela Cruz']
);
$newId = $db->lastInsertId();

// Transaction
$db->beginTransaction();
try {
    $db->query("UPDATE ...", [...]);
    $db->query("INSERT ...", [...]);
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

> **Important:** Always use parameterized queries (`?` placeholders). Never concatenate user input into SQL strings.

### Database Schema (ERD Overview)

The database has **13 tables** with foreign key relationships:

```
programs ────────────┐
year_levels ─────────┤
                     ▼
users ◄──────── students ──────► allergies
  │                 │              conditions
  │                 │              medications
  │                 │              immunizations
  │                 │              emergency_contacts
  │                 ▼
  └──────────► visits
  │
  └──────────► access_logs

announcements    (standalone, managed by nurse)
faqs             (standalone, managed by nurse)
first_aid_guidelines (standalone, managed by nurse)
clinic_hours     (standalone, managed by nurse)
```

Key relationships:
- `students.program_id` → `programs.id`
- `students.year_level_id` → `year_levels.id`
- `visits.student_id` → `students.id` (CASCADE delete)
- `visits.attended_by` → `users.id` (SET NULL on delete)
- Health sub-tables (`allergies`, `conditions`, `medications`, `immunizations`, `emergency_contacts`) → `students.id` (CASCADE delete)
- `access_logs.user_id` → `users.id` (SET NULL on delete)

---

## Authentication & Authorization

### How It Works

1. `session.php` starts the session, checks timeout, and provides CSRF helpers.
2. `auth.php` provides login checks and role guards.
3. Every protected page calls `requireRole()` after including `header.php`.

### Auth Functions (`includes/auth.php`)

| Function | Purpose |
|----------|---------|
| `isLoggedIn()` | Returns `true` if user has an active session |
| `requireLogin()` | Redirects to login page if not authenticated |
| `requireRole($roles)` | Ensures user has one of the specified roles; redirects otherwise |
| `getCurrentUser()` | Returns full user data (cached in session) |
| `getDashboardUrl($role)` | Returns the dashboard URL for a given role |
| `logAccess($userId, $action, $description)` | Writes to `access_logs` table |
| `getUserDisplayName()` | Returns "First Last" string |
| `getRoleDisplayName($role)` | Maps `admin` → `Administrator`, etc. |

### Session Variables

| Key | Value |
|-----|-------|
| `$_SESSION['user_id']` | Authenticated user's ID |
| `$_SESSION['user_role']` | `admin`, `nurse`, or `rep` |
| `$_SESSION['user_data']` | Cached user row (lazy-loaded) |
| `$_SESSION['last_activity']` | Timestamp for timeout checking |
| `$_SESSION['csrf_token']` | CSRF protection token |
| `$_SESSION['flash_*']` | Flash messages (`flash_success`, `flash_error`, etc.) |

### Roles

| Role | String | Access |
|------|--------|--------|
| Administrator | `admin` | Full system access, user management, reports |
| Nurse/Staff | `nurse` | Patient care, visit logging, content management |
| Class Representative | `rep` | Student CRUD (scoped to assigned program/year/section) |

### Usage Example

```php
// At the top of any protected page:
$pageTitle = 'My Page';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');                     // Single role
// Or:
requireRole(['admin', 'nurse']);          // Multiple roles allowed
```

---

## Template System (Layouts)

CampusCare uses a simple **includes-based** layout system. Every authenticated page follows this structure:

### Authenticated Page Layout

```php
<?php
$pageTitle = 'Page Title';                     // Set before header
require_once __DIR__ . '/../includes/header.php';  // HTML head + navbar
requireRole('admin');                          // Auth guard

$db = Database::getInstance();
// ... PHP logic (queries, POST handling) ...

require_once __DIR__ . '/../includes/sidebar.php'; // Sidebar + opens <main>
?>

<!-- Your page HTML goes here -->
<div class="page-header">
    <h1><i class="bi bi-icon me-2"></i>Page Title</h1>
</div>

<!-- Page content... -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>  <!-- Closes </main>, JS, flash -->
```

### What Each Template Does

| File | Responsibility |
|------|---------------|
| `header.php` | `<!DOCTYPE>`, `<head>`, CSS imports, top navbar (if logged in) |
| `sidebar.php` | Role-based sidebar navigation, opens `<main class="cc-main-content">` |
| `footer.php` | Closes `</main>`, loads JS libraries, renders flash messages as SweetAlert2 toasts |

### Standalone Pages

Pages like `login.php` and `index.php` do **not** use the template system — they have their own full HTML structure.

---

## Page Anatomy

A typical CRUD page (like `admin/users.php`) follows this layout:

```
┌─────────────────────────────────────────────┐
│  Page Header     (title + breadcrumb + CTA) │
├─────────────────────────────────────────────┤
│  Filter Bar      (search + dropdowns)       │
├─────────────────────────────────────────────┤
│  Data Card                                  │
│  ┌───────────────────────────────────────┐  │
│  │  Table (responsive)                   │  │
│  │  ├── thead                            │  │
│  │  └── tbody (loop, empty state)        │  │
│  └───────────────────────────────────────┘  │
│  Pagination (card footer)                   │
├─────────────────────────────────────────────┤
│  Modal(s)       (create/edit forms)         │
├─────────────────────────────────────────────┤
│  <script>       (AJAX handlers)             │
└─────────────────────────────────────────────┘
```

---

## CRUD & AJAX Pattern

CampusCare uses a consistent pattern where **the same PHP file handles both the page render (GET) and API calls (POST)**. This avoids the need for a separate API layer.

### Server Side (PHP)

```php
// ── Handle POST actions (at the top, before any HTML output) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 1) Validate CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
    }

    $action = $_POST['action'];

    // 2) Route by action
    if ($action === 'create') {
        // Validate, insert, return JSON
        $db->query("INSERT INTO ...", [...]);
        logAccess($_SESSION['user_id'], 'create_thing', 'Created thing: ' . $name);
        jsonResponse(['success' => true, 'message' => 'Created successfully.']);
    }

    if ($action === 'get') {
        // Fetch and return JSON for modal pre-fill
        $item = $db->fetch("SELECT * FROM ... WHERE id = ?", [$id]);
        jsonResponse(['success' => true, 'item' => $item]);
    }

    if ($action === 'delete') {
        $db->query("DELETE FROM ... WHERE id = ?", [$id]);
        jsonResponse(['success' => true, 'message' => 'Deleted.']);
    }
}

// ── Fetch data for page render (GET) ──
$items = $db->fetchAll("SELECT * FROM ... ORDER BY ...");
```

### Client Side (JavaScript)

```javascript
// Submit form via AJAX
document.getElementById('myForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (!this.checkValidity()) {
        this.classList.add('was-validated');
        return;
    }

    const formData = new FormData(this);

    fetch('current_page.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                myModal.hide();
                showToast('success', data.message);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('error', data.message);
            }
        })
        .catch(err => showToast('error', 'An error occurred.'));
});
```

### Key Points

- POST handlers run **before** any HTML output.
- `jsonResponse()` clears any buffered output, sends JSON, and `exit`s.
- The client always expects `{ success: bool, message: string }`.
- After a successful mutation, the page reloads after a brief toast delay.

---

## Frontend Stack

### Libraries (via npm / `node_modules/`)

| Library | Version | Purpose |
|---------|---------|---------|
| Bootstrap | 5.3.x | UI components, grid system, responsive layout |
| Bootstrap Icons | 1.13.x | Icon set (used everywhere via `<i class="bi bi-...">`) |
| SweetAlert2 | 11.x | Toast notifications, confirmation dialogs |
| Chart.js | 4.x | Dashboard charts and report visualizations |

### CSS Architecture

- **`css/style.css`** — Single custom stylesheet.
- Uses CSS custom properties (e.g., `--cc-primary`, `--cc-primary-bg`).
- Built on top of Bootstrap 5 utility classes.
- CSS class naming conventions:
  - `.cc-*` prefix for custom layout components (`.cc-navbar`, `.cc-sidebar`, `.cc-main-content`)
  - `.stat-card-*` for dashboard statistic cards
  - `.sidebar-*` for sidebar sub-components
  - `.filter-bar`, `.search-box` for page filtering UI

### Typography

- **Font:** [Inter](https://fonts.google.com/specimen/Inter) (loaded from Google Fonts)
- **Weights:** 300, 400, 500, 600, 700

---

## Utility Functions Reference

### PHP Utilities (`includes/functions.php`)

| Function | Signature | Description |
|----------|-----------|-------------|
| `e()` | `e($string)` | HTML-escape output (XSS prevention) |
| `sanitize()` | `sanitize($input)` | Strip tags + escape input |
| `formatDate()` | `formatDate($date, $format = 'M d, Y')` | Format date string |
| `formatDateTime()` | `formatDateTime($dt, $format = 'M d, Y h:i A')` | Format datetime |
| `formatTime()` | `formatTime($time, $format = 'h:i A')` | Format time |
| `calculateAge()` | `calculateAge($dob)` | Age from date of birth |
| `generatePagination()` | `generatePagination($page, $totalPages, $baseUrl)` | Pagination HTML |
| `statusBadge()` | `statusBadge($status)` | Bootstrap badge for status strings |
| `truncate()` | `truncate($text, $length = 100)` | Truncate with ellipsis |
| `isActivePage()` | `isActivePage($page)` | Check if current page matches (for sidebar) |
| `ordinal()` | `ordinal($number)` | Number → "1st", "2nd", "3rd" |
| `redirect()` | `redirect($url, $flashType, $flashMsg)` | Redirect with optional flash message |
| `jsonResponse()` | `jsonResponse($data, $statusCode = 200)` | Send JSON and exit |

### Session Utilities (`includes/session.php`)

| Function | Description |
|----------|-------------|
| `getCSRFToken()` | Get or generate the session CSRF token |
| `validateCSRFToken($token)` | Validate a submitted CSRF token |
| `csrfField()` | Echo a hidden `<input>` with the CSRF token |
| `setFlashMessage($type, $msg)` | Store flash message (`success`, `error`, `info`, `warning`) |
| `getFlashMessage($type)` | Retrieve and clear a flash message |
| `hasFlashMessage($type)` | Check if a flash message exists |

---

## JavaScript API Reference

All functions are defined in `js/app.js` and available globally.

### SweetAlert2 Wrappers

```javascript
// Toast notification (top-right, auto-dismiss after 3s)
showToast('success', 'Record saved!');
showToast('error', 'Something went wrong.');

// Alert dialog
showAlert('info', 'Notice', 'This is an informational message.');

// Confirmation dialog (returns a Promise)
showConfirm('Delete item?', 'This cannot be undone.', 'Yes, delete')
    .then(result => {
        if (result.isConfirmed) { /* proceed */ }
    });

// Pre-configured delete confirmation
showDeleteConfirm('John Doe')
    .then(result => { ... });
```

### Form Validation

```javascript
// Auto-initialized on DOMContentLoaded for all forms with .needs-validation
initFormValidation();
```

### AJAX Helpers

```javascript
// POST form data (returns Promise<Response>)
postForm('/endpoint', formData);

// POST JSON data (returns Promise<Response>)
postJson('/endpoint', { key: 'value' });
```

### Utilities

```javascript
formatNumber(1234567);           // "1,234,567"
copyToClipboard('text');         // Copies to clipboard
printSection('elementId');       // Print a specific DOM element

// Debounce function calls
const handler = debounce(() => { /* search */ }, 300);

// Live search (fetches HTML from server, injects into container)
initLiveSearch('#searchInput', '/search-endpoint', 'resultsContainer');
```

---

## Security Practices

Every developer **must** follow these practices:

### 1. SQL Injection Prevention

```php
// ✅ CORRECT — parameterized query
$db->fetch("SELECT * FROM users WHERE username = ?", [$username]);

// ❌ WRONG — string concatenation
$db->fetch("SELECT * FROM users WHERE username = '$username'");
```

### 2. XSS Prevention

```php
// ✅ CORRECT — escape all dynamic output
<p><?php echo e($user['name']); ?></p>

// ❌ WRONG — raw output
<p><?php echo $user['name']; ?></p>
```

### 3. CSRF Protection

Every form **must** include a CSRF token:

```php
<form method="POST">
    <?php csrfField(); ?>
    <!-- form fields -->
</form>
```

Every POST handler **must** validate it:

```php
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
}
```

### 4. Password Hashing

```php
// Creating
$hash = password_hash($plainPassword, PASSWORD_DEFAULT);

// Verifying
if (password_verify($inputPassword, $storedHash)) { /* authenticated */ }
```

### 5. Role-Based Access Control

Every module page **must** call `requireRole()` immediately after including `header.php`:

```php
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');   // or ['admin', 'nurse']
```

### 6. Audit Logging

Log important actions using `logAccess()`:

```php
logAccess($_SESSION['user_id'], 'action_name', 'Human-readable description');
```

---

## Adding a New Feature (Tutorial)

Here's a complete example: adding a new **"Medicines Inventory"** page to the admin module.

### Step 1: Create the Database Table

```sql
-- database/campuscare.sql (append)
CREATE TABLE IF NOT EXISTS `medicines` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 0,
  `expiry_date` DATE DEFAULT NULL,
  `status` ENUM('available','out_of_stock') NOT NULL DEFAULT 'available',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Step 2: Create the Page File

Create `admin/medicines.php`:

```php
<?php
$pageTitle = 'Medicines Inventory';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$db = Database::getInstance();

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
    }

    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Medicine name is required.']);
        }
        $db->query(
            "INSERT INTO medicines (name, quantity, expiry_date) VALUES (?, ?, ?)",
            [$name, intval($_POST['quantity'] ?? 0), $_POST['expiry_date'] ?: null]
        );
        logAccess($_SESSION['user_id'], 'create_medicine', 'Added medicine: ' . $name);
        jsonResponse(['success' => true, 'message' => 'Medicine added successfully.']);
    }
}

// ── GET: Fetch listing data ──
$medicines = $db->fetchAll("SELECT * FROM medicines ORDER BY name ASC");

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-capsule me-2"></i>Medicines Inventory</h1>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Expiry</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No medicines recorded.</td></tr>
                    <?php else: ?>
                    <?php foreach ($medicines as $m): ?>
                    <tr>
                        <td><?php echo e($m['name']); ?></td>
                        <td><?php echo $m['quantity']; ?></td>
                        <td><?php echo formatDate($m['expiry_date']); ?></td>
                        <td><?php echo statusBadge($m['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```

### Step 3: Add Sidebar Link

In `includes/sidebar.php`, add the link inside the admin section:

```php
<a href="<?php echo BASE_URL; ?>/admin/medicines.php"
   class="sidebar-link <?php echo isActivePage('medicines.php'); ?>">
    <i class="bi bi-capsule"></i><span>Medicines</span>
</a>
```

### Step 4: Verify

1. Run the SQL to create the table.
2. Navigate to `http://localhost/CampusCare/admin/medicines.php`.
3. Confirm the page loads with the sidebar active state.
4. Check the browser console for any JS errors.

---

## Coding Standards

### PHP

- **PHP tags:** Always use `<?php`. Never use short tags `<?`.
- **File headers:** Every file should start with a docblock comment:
  ```php
  <?php
  /**
   * CampusCare - [Description]
   * [Brief explanation of what this file does]
   */
  ```
- **Indentation:** 4 spaces (no tabs).
- **Braces:** Opening brace on same line for control structures, next line for classes/functions.
  ```php
  if ($condition) {
      // ...
  }

  class Database
  {
      public function query()
      {
          // ...
      }
  }
  ```
- **Naming:**
  - Functions: `camelCase` — `formatDate()`, `getCurrentUser()`
  - Variables: `$camelCase` — `$pageTitle`, `$totalStudents`
  - Constants: `UPPER_SNAKE_CASE` — `DB_HOST`, `BASE_URL`
  - Database columns: `snake_case` — `first_name`, `visit_date`
- **Output escaping:** Always use `e()` when outputting user-controlled data.
- **Closing tags:** Omit `?>` at the end of PHP-only files (prevents accidental whitespace).

### JavaScript

- **No build step:** All JS is vanilla, no transpilation needed.
- **Global functions:** Utilities are defined in `app.js` as global functions.
- **Inline `<script>` blocks:** Page-specific JS goes in a `<script>` block after `footer.php`.
- **AJAX convention:** Use `fetch()` API with `FormData`.
- **Confirmation pattern:** Use `showConfirm()` / `showDeleteConfirm()` before destructive actions.

### HTML / CSS

- **Bootstrap 5.3** classes for layout and components.
- **Bootstrap Icons** for all icons (prefix: `bi bi-`).
- **Custom classes** use `cc-` prefix for app-specific components.
- **Responsive design:** Use Bootstrap's grid (`row`, `col-*`) and responsive utilities.

---

## Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| **Blank page** | Check PHP error logs. Possibly a syntax error or missing `require`. |
| **"Database connection failed"** | Verify credentials in `config/config.php`. Ensure MySQL is running. |
| **CSRF token invalid** | Session may have expired. Reload the page. |
| **Styles not loading** | Run `npm install`. Check that `BASE_URL` is correct in `config/config.php`. |
| **Sidebar not highlighting** | Ensure `isActivePage('filename.php')` matches the actual filename. |
| **Flash messages not showing** | Ensure `footer.php` is included. It renders SweetAlert2 toasts. |
| **JSON response contains HTML** | POST handlers must run **before** `header.php` is included, or use `jsonResponse()` which clears output buffers. |
| **Page loads full HTML in AJAX** | Ensure your POST handler calls `jsonResponse()` (which calls `exit`). |

### Enabling Error Display (Development Only)

Add temporarily to `config/config.php`:

```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

> **Never enable in production.**

---

*Last updated: February 2026*

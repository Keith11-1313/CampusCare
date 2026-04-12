# CampusCare

## School Clinic Patient Information Record System

A PHP/MySQL web application for managing school clinic operations, student health records, clinic visits, and public health information.

---

## Project Status

> **Status:** Active Development — Feature Complete (Pre-Release)
>
> **Last Updated:** April 12, 2026

### Completed Modules

| Module | Status | Notes |
| ------ | ------ | ----- |
| Public Landing Page | Complete | Announcements, FAQs, first-aid guidelines, emergency contacts, clinic hours |
| Authentication & Security | Complete | Login, password reset (security question + contact admin flow), secure password policy, session management |
| Admin Dashboard | Complete | Statistics overview with year-level distribution charts |
| User Management | Complete | Full CRUD with stepper form, password validation, role-based assignment |
| Program Management | Complete | CRUD with input validation (special character prevention) |
| Archive Management | Complete | Restore & permanent delete for students, users, and programs |
| Admin Reports | Complete | Chart.js visualizations + PDF export (Dompdf) |
| Access Logs | Complete | Full audit trail of user actions |
| Admin Request Handling | Complete | Password reset requests with one-click approval |
| Nurse Dashboard | Complete | Visit statistics and trends |
| Student Profiles | Complete | Health records with categorized dropdowns (allergens, conditions, immunizations) |
| Visit Logging | Complete | Vitals, complaint categorization, clinical assessment (required), treatment notes |
| Visit History | Complete | Searchable visit records with full-name search support |
| Nurse Reports | Complete | Chart.js visualizations + PDF export |
| Public Content Management | Complete | Announcements, FAQs, first-aid, emergency contacts, clinic hours (Quill editor) |
| Class Rep Dashboard | Complete | Section-scoped student overview |
| Class Rep Student Management | Complete | CRUD scoped to assigned program/year/section, CSV export |
| Class Rep Requests | Complete | Replacement and student deletion requests |

### Live Demo

The application is hosted online at:

```
http://campuscare.page.gd/
```

#### How to Access

1. Open your browser and navigate to **http://campuscare.page.gd/**
2. Use the [default login credentials](#default-login-credentials) below to sign in
3. If the page does not load, follow the troubleshooting steps below

#### Troubleshooting: Cannot Access the Site

The live demo is hosted on **InfinityFree**, a free hosting provider. Some users may experience issues accessing the site due to **DNS filtering** by their Internet Service Provider (ISP).

**Why does this happen?**

InfinityFree uses shared IP addresses that host many websites. Some ISPs — particularly in the Philippines and other regions — block or filter DNS requests to these shared IPs as part of anti-phishing or content-filtering policies. This means your ISP's DNS resolver may refuse to resolve `campuscare.page.gd`, even though the site is legitimate and functional.

**How to fix it:**

- **Option 1: Use a VPN** — Turn on any VPN service (e.g., Proton VPN, Windscribe, or a browser-based VPN). This bypasses your ISP's DNS filtering entirely.
- **Option 2: Use mobile data** — Switch from Wi-Fi to your phone's mobile data connection. Mobile carriers often use different DNS resolvers that do not block InfinityFree domains.
- **Option 3: Change your DNS server** — Manually set your device's DNS to a public resolver like Google DNS (`8.8.8.8`, `8.8.4.4`) or Cloudflare DNS (`1.1.1.1`, `1.0.0.1`) to bypass your ISP's filtered DNS.

---

## Features

| Role | Capabilities |
| ------ | ------------- |
| **Admin** | Dashboard with year-level charts, user management (CRUD with stepper form), program management, access logs, archive (restore & delete), password reset request approval, reports (Chart.js + PDF export) |
| **Nurse/Staff** | Dashboard, student search & profile (allergies, conditions, medications, immunizations, emergency contacts — all with categorized dropdowns), visit logging (required assessment), visit history, public content management (announcements, FAQs, first-aid, emergency contacts, clinic hours), reports (Chart.js + PDF export) |
| **Class Rep** | Dashboard, student CRUD (scoped to assigned program/year/section), CSV export, replacement & deletion requests |
| **Public** | Landing page with announcements, FAQs, first-aid guidelines (with PDF export), emergency contacts, clinic hours |

## Security

- **PDO prepared statements** (SQL injection prevention)
- **CSRF token protection** on all forms
- **Bcrypt password hashing** (`password_hash`)
- **Secure password policy** (minimum length, uppercase, lowercase, number, special character)
- **Password reuse prevention** (cannot reuse current password)
- **Session management** with timeout & secure cookies
- **Role-based access control** middleware
- **Output encoding** (XSS prevention via `htmlspecialchars`)
- **Input validation** (server-side & client-side with real-time keystroke filtering)
- **Access logging** (audit trail)
- **Directory access control** (`.htaccess` deny on config/includes)

---

## Requirements

- **PHP 7.4+** (with PDO MySQL and GD extensions enabled)
- **MySQL 5.7+** or MariaDB 10.3+
- **Apache** (with `mod_rewrite`) or any PHP-compatible web server
- **Node.js/npm** (for frontend dependencies)
- **Composer** (for PHP backend dependencies) — [Download here](https://getcomposer.org/download/)
- **XAMPP/WAMP/MAMP** recommended for local development

## Installation

### 1. Clone / Copy Project

Place the `CampusCare` folder in your web server's document root (e.g., `htdocs` for XAMPP).

### 2. Install Frontend Dependencies

```powershell
cd CampusCare
npm install
```

This installs all packages listed in `package.json` (Bootstrap, SweetAlert2, Chart.js, Quill, etc.) into the `node_modules/` folder.

### 3. Install Backend Dependencies (Composer)

**Option A — If Composer is installed globally:**

```powershell
composer install
```

**Option B — If Composer is NOT installed globally:**

Download and run Composer directly:

```powershell
C:\xampp\php\php.exe -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
C:\xampp\php\php.exe composer-setup.php
C:\xampp\php\php.exe composer.phar install
del composer-setup.php
```

This installs the PHP packages listed in `composer.json` (e.g., Dompdf for PDF export) into the `vendor/` folder.

> **Tip:** To install Composer globally on Windows, download the [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe) installer.

### 4. Create the Database

1. Open **phpMyAdmin** or MySQL CLI
2. Create a database named `campuscare` (for local development):

   ```sql
   CREATE DATABASE campuscare;
   ```

3. Import/Paste the schema:

   ```sql
   USE campuscare;
   SOURCE database/campuscare.sql;
   ```

4. Import/Paste the seed data (for ready to-go data):

   ```sql
   SOURCE database/seed_data.sql;
   ```

### 5. Configure the credentials (ignore for localhost)

Edit `config/config.php` to match your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'campuscare');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Also update `BASE_URL` if not using the default:

```php
define('BASE_URL', 'http://localhost/CampusCare');
```

### 6. Enable PHP GD Extension (ignore as it is enabled by default)

The GD extension is required for PDF export with images. In your `php.ini`, make sure this line is **uncommented**:

```ini
extension=gd
```

For XAMPP, the `php.ini` file is at `C:\xampp\php\php.ini`. After editing, **restart Apache**.

### 7. Start the Server

Start Apache & MySQL in XAMPP, then visit:

```text
http://localhost/CampusCare
```

---

## Default Login Credentials

| Role | Username | Password | Security Question | Answer |
| ------ | ---------- | ---------- | ------------------- | -------- |
| Admin | `admin` | `Admin@123` | What city were you born in? | `manila` |
| Nurse | `nurse_garcia` | `Nurse@123` | What is the name of your first pet? | `brownie` |
| Nurse | `nurse_santos` | `Nurse@123` | What is your favorite food? | `adobo` |
| Nurse | `nurse_reyes` | `Nurse@123` | What city were you born in? | `quezon city` |
| Nurse | `nurse_cruz` | `Nurse@123` | What is the name of your best friend? | `carlo` |
| Nurse | `nurse_mendoza` | `Nurse@123` | What is your favorite color? | `blue` |
| Nurse | `nurse_villanueva` | `Nurse@123` | What is your favorite food? | `sinigang` |
| Nurse | `nurse_torres` | `Nurse@123` | What is the name of your first pet? | `bantay` |
| Nurse | `nurse_bautista` | `Nurse@123` | What city were you born in? | `cebu` |
| Class Rep | `rep_mercado_BSA_1A` | `Rep@1234` | | |
| Class Rep | `rep_bueno_BSA_3A` | `Rep@1234` | | |

> **Note:** Security answers are case-insensitive.

---

## Project Structure

```text
CampusCare/
├── admin/                  # Admin module
│   ├── access_logs.php
│   ├── archive.php
│   ├── current_requests.php
│   ├── dashboard.php
│   ├── programs.php
│   ├── reports.php
│   ├── students.php
│   └── users.php
├── nurse/                  # Nurse/Staff module
│   ├── content.php
│   ├── dashboard.php
│   ├── new_visit.php
│   ├── reports.php
│   ├── student_profile.php
│   ├── students.php
│   └── visits.php
├── rep/                    # Class Representative module
│   ├── dashboard.php
│   ├── request_change.php
│   └── students.php
├── assets/                 # Static assets
│   ├── first-aid-icons/    # SVG icons for first-aid guidelines
│   ├── clinic1–4.jpg       # Clinic photos
│   ├── logo-main-b.png     # Logo (dark variant)
│   └── logo-main-w.png     # Logo (light variant)
├── config/
│   ├── .htaccess           # Deny direct access
│   ├── config.php          # App config & DB credentials
│   └── database.php        # PDO singleton
├── includes/
│   ├── .htaccess           # Deny direct access
│   ├── auth.php            # Auth helpers & RBAC
│   ├── export_pdf.php      # PDF report export (admin & nurse)
│   ├── export_students_csv.php  # CSV student records export (rep)
│   ├── footer.php          # Page footer template
│   ├── functions.php       # Utility functions
│   ├── header.php          # Page header template
│   ├── session.php         # Session & CSRF management
│   └── sidebar.php         # Role-aware sidebar
├── database/
│   ├── demo-data/          # Demo/testing data
│   │   ├── campuscare.sql
│   │   └── seed_data.sql
│   └── real-data/          # Production data & scripts
│       ├── bulk_seed_data.sql
│       ├── campuscare.sql
│       ├── generate_seed.py
│       └── seed_data.sql
├── docs/                   # Documentation & diagrams
│   ├── CampusCare.svg      # System diagram
│   ├── programs.pdf        # Program reference document
│   └── testing/            # Test documentation
├── css/style.css           # Custom styles
├── js/app.js               # Main JavaScript
├── index.php               # Public landing page
├── export_firstaid_pdf.php # First aid guideline PDF export
├── login.php               # Login page (with security question & contact admin reset)
├── logout.php              # Logout handler
├── change_password.php     # Change password (with stepper flow)
├── change_security_question.php  # Change security question
├── vendor/                 # PHP dependencies (Composer)
├── composer.json           # Composer dependencies
├── package.json            # npm dependencies
└── .gitignore              # gitignore file
```

## Database Schema

The system uses **14 tables** across four functional domains:

| Domain | Tables |
| ------ | ------ |
| **Academic** | `programs`, `year_levels` |
| **Users & Auth** | `users`, `access_logs`, `current_requests` |
| **Students & Health** | `students`, `allergies`, `chronic_conditions`, `medications`, `immunizations`, `emergency_contacts` |
| **Clinic Operations** | `visits` |
| **Public Content** | `announcements`, `faqs`, `first_aid_guidelines`, `clinic_emergency_contacts`, `clinic_hours` |

## Tech Stack

- **Backend:** PHP 7.4+ (vanilla, no framework)
- **Database:** MySQL via PDO
- **PDF Export:** Dompdf 3.x (via Composer)
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, SweetAlert2, Chart.js 4.x
- **Rich Text Editor:** Quill 1.3
- **Typography:** Google Fonts (Inter)

---

## Function Reference

A complete reference of all PHP and JavaScript functions used throughout the application, organized by source file.

---

<details>
<summary><strong>config/database.php</strong> — Database Singleton & Query Helpers</summary>

<br>

> **Class:** `Database` (Singleton pattern via PDO)

| Method | Signature | Returns | Description |
| ------ | --------- | ------- | ----------- |
| `getInstance()` | `static getInstance()` | `Database` | Returns the singleton database instance. Creates the PDO connection on first call. |
| `getConnection()` | `getConnection()` | `PDO` | Returns the raw PDO connection object for advanced use cases. |
| `query()` | `query(string $sql, array $params = [])` | `PDOStatement` | Executes a prepared SQL statement with optional bound parameters and returns the statement. |
| `fetchAll()` | `fetchAll(string $sql, array $params = [])` | `array` | Executes a query and returns all matching rows as an associative array. |
| `fetch()` | `fetch(string $sql, array $params = [])` | `array\|false` | Executes a query and returns a single row as an associative array, or `false` if none found. |
| `fetchColumn()` | `fetchColumn(string $sql, array $params = [])` | `mixed` | Executes a query and returns the value of the first column of the first row. |
| `lastInsertId()` | `lastInsertId()` | `string` | Returns the ID of the last inserted row. |
| `beginTransaction()` | `beginTransaction()` | `bool` | Starts a database transaction. |
| `commit()` | `commit()` | `bool` | Commits the current transaction. |
| `rollback()` | `rollback()` | `bool` | Rolls back the current transaction. |

</details>

---

<details>
<summary><strong>includes/session.php</strong> — Session, CSRF & Flash Messages</summary>

<br>

> Handles session initialization (secure cookies, timeout), CSRF token management, and one-time flash messages.

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `getCSRFToken()` | `getCSRFToken()` | `string` | Generates a new CSRF token (64-char hex) or returns the existing one stored in the session. |
| `validateCSRFToken()` | `validateCSRFToken(string $token)` | `bool` | Validates a submitted CSRF token against the session token using timing-safe comparison (`hash_equals`). |
| `csrfField()` | `csrfField()` | `void` | Outputs a hidden `<input>` element containing the CSRF token, ready to embed in any HTML form. |
| `setFlashMessage()` | `setFlashMessage(string $type, string $message)` | `void` | Stores a flash message in the session. Supported types: `success`, `error`, `info`, `warning`. |
| `getFlashMessage()` | `getFlashMessage(string $type)` | `string\|null` | Retrieves and clears a flash message of the given type. Returns `null` if none exists. |
| `hasFlashMessage()` | `hasFlashMessage(string $type)` | `bool` | Checks whether a flash message of the given type exists in the session. |

</details>

---

<details>
<summary><strong>includes/auth.php</strong> — Authentication & Role-Based Access Control</summary>

<br>

> Provides authentication guards, role enforcement, user data caching, and audit logging.

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `isLoggedIn()` | `isLoggedIn()` | `bool` | Checks if a user is currently logged in by verifying `$_SESSION['user_id']`. |
| `requireLogin()` | `requireLogin()` | `void` | Middleware guard — redirects to the login page if the user is not authenticated. Terminates execution via `exit`. |
| `requireRole()` | `requireRole(string\|array $roles)` | `void` | Middleware guard — checks that the logged-in user has one of the specified roles. Redirects to the user's dashboard if unauthorized. |
| `getCurrentUser()` | `getCurrentUser()` | `array\|null` | Returns the full user record (with joined program/year-level data) for the logged-in user. Caches in session to avoid repeated DB queries. |
| `getDashboardUrl()` | `getDashboardUrl(string $role)` | `string` | Returns the dashboard URL for a given role (`admin`, `nurse`, `rep`). Falls back to the login page. |
| `logAccess()` | `logAccess(int $userId, string $action, string $description = '')` | `void` | Inserts an entry into the `access_logs` table with the user ID, action name, description, and client IP address. |
| `getUserDisplayName()` | `getUserDisplayName()` | `string` | Returns the current user's full name (`first_name + last_name`) or `'Guest'` if not logged in. |
| `getRoleDisplayName()` | `getRoleDisplayName(string $role)` | `string` | Converts a role key (`admin`, `nurse`, `rep`) to its human-readable label (e.g., `'School Nurse/Staff'`). |

</details>

---

<details>
<summary><strong>includes/functions.php</strong> — Utility & Helper Functions</summary>

<br>

> Shared utility functions for sanitization, formatting, pagination, and UI rendering used across all modules.

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `e()` | `e(string $string)` | `string` | Escapes a string for safe HTML output using `htmlspecialchars` with `ENT_QUOTES` and `UTF-8` encoding. Primary XSS prevention function. |
| `sanitize()` | `sanitize(string\|array $input)` | `string\|array` | Sanitizes input by trimming whitespace, stripping HTML tags, and encoding special characters. Recursively handles arrays. |
| `formatDate()` | `formatDate(string $date, string $format = 'M d, Y')` | `string` | Formats a date string for display. Returns `'N/A'` if the input is empty. |
| `formatDateTime()` | `formatDateTime(string $datetime, string $format = 'M d, Y h:i A')` | `string` | Formats a datetime string for display with date and time. Returns `'N/A'` if empty. |
| `formatTime()` | `formatTime(string $time, string $format = 'h:i A')` | `string` | Formats a time string for display (e.g., `'02:30 PM'`). Returns `'N/A'` if empty. |
| `calculateAge()` | `calculateAge(string $dob)` | `int\|string` | Calculates age in years from a date of birth string. Returns `'N/A'` if the date is empty. |
| `generatePagination()` | `generatePagination(int $currentPage, int $totalPages, string $baseUrl)` | `string` | Generates Bootstrap-styled pagination HTML with ellipsis, previous/next buttons, and active page highlighting. |
| `sortableHeader()` | `sortableHeader(string $label, string $column, string $currentSort, string $currentOrder, string $extraClass = '')` | `string` | Generates a clickable `<th>` element for sortable table columns with ascending/descending sort icons. Preserves all existing query parameters. |
| `statusBadge()` | `statusBadge(string $status)` | `string` | Returns a Bootstrap `<span class="badge">` for a given status string (e.g., `'active'`, `'Completed'`, `'Severe'`). Color-coded by severity/meaning. |
| `truncate()` | `truncate(string $text, int $length = 100)` | `string` | Truncates text to the specified length, appending an ellipsis (`…`) if truncated. Output is HTML-escaped. |
| `isActivePage()` | `isActivePage(string\|array $page)` | `string` | Returns `'active'` if the current page basename matches the given page(s). Used for sidebar navigation highlighting. |
| `ordinal()` | `ordinal(int $number)` | `string` | Returns the number with its ordinal suffix (e.g., `1st`, `2nd`, `3rd`, `4th`). Handles teen exceptions (11th, 12th, 13th). |
| `redirect()` | `redirect(string $url, string $flashType = null, string $flashMessage = null)` | `void` | Performs an HTTP redirect. Optionally sets a flash message before redirecting. Terminates via `exit`. |
| `jsonResponse()` | `jsonResponse(array $data, int $statusCode = 200)` | `void` | Sends a JSON response for AJAX endpoints. Clears any buffered output, sets the appropriate headers, and terminates. |
| `validatePasswordStrength()` | `validatePasswordStrength(string $password)` | `array` | Validates a password against the security policy (min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character). Returns an array of error messages (empty = valid). |

</details>

---

<details>
<summary><strong>includes/header.php</strong> — Page Header & Navbar Template</summary>

<br>

> Not a function file — this is an includable template that outputs the HTML `<head>`, CSS imports (Bootstrap, Bootstrap Icons, Google Fonts, custom styles), and the top navigation bar for authenticated users. The navbar includes:
>
> - Sidebar toggle button (mobile)
> - Brand logo and name
> - User dropdown menu (avatar initials, full name, role label)
> - Links to **Change Password**, **Security Question**, and **Logout**
>
> **Usage:** Set `$pageTitle` before including this file.

</details>

---

<details>
<summary><strong>includes/sidebar.php</strong> — Role-Aware Sidebar Navigation</summary>

<br>

> Not a function file — this is an includable template that renders the sidebar navigation based on the user's role. Each role sees a different set of menu items:
>
> | Role | Navigation Links |
> | ---- | ---------------- |
> | **Admin** | Dashboard, User Management, Programs, Student Records, Pending Requests, Archived Records, Reports, Activity Logs |
> | **Nurse** | Dashboard, New Visit, Visit History, Student Records, Reports, Manage Content |
> | **Class Rep** | Dashboard, Manage Students |
>
> Also renders the mobile overlay for responsive sidebar toggle behavior.

</details>

---

<details>
<summary><strong>includes/footer.php</strong> — Page Footer & Flash Message Display</summary>

<br>

> Not a function file — this is an includable template that closes the `<main>` content wrapper and loads JavaScript dependencies:
>
> - **Bootstrap JS Bundle** — interactive components (modals, dropdowns, tooltips)
> - **SweetAlert2** — styled alerts and toast notifications
> - **Chart.js** — data visualization charts
> - **Custom JS** (`js/app.js`) — application utilities
>
> Also checks for flash messages (`success`, `error`, `info`, `warning`) and displays them as SweetAlert2 toast notifications.

</details>

---

<details>
<summary><strong>includes/export_pdf.php</strong> — PDF Report Generation (Chart.js + Print)</summary>

<br>

> Not a standalone function file — this is a full page that generates a printable/PDF clinic visits report. Requires `admin` or `nurse` role.
>
> **Features:**
> - Dynamic filtering by date range, program, year level, and section
> - Configurable sort order for visit records (12 options)
> - Selectable report sections via `$_GET['sections']` array
> - Optional page breaks and landscape orientation
> - Chart.js visualizations converted to images for print
>
> **Report sections include:**
>
> | Section Key | Content |
> | ----------- | ------- |
> | `summary` | Total visits, unique patients, avg visits/day stat boxes |
> | `visits_month` | Bar chart + data table of visits by month |
> | `visits_program` | Doughnut chart + data table of visits by program |
> | `top_complaints` | Podium-style bar chart + ranked table of top 10 health complaints |
> | `visit_status` | Doughnut chart + table of status distribution (Completed/Follow-up/Referred) |
> | `top_allergens` | Horizontal bar chart + table of top 5 allergens |
> | `top_vaccines` | Horizontal bar chart + table of top 5 vaccines |
> | `top_conditions` | Ranked table of top 4 chronic conditions |
> | `visit_records` | Full visit records table with all columns |
>
> **Inline JavaScript functions:**
>
> | Function | Description |
> | -------- | ----------- |
> | `preparePrint()` | Converts all Chart.js canvases to static PNG images, then triggers `window.print()`. |
> | `goBack()` | Navigates back to the reports page via `history.back()` or direct URL. |

</details>

---

<details>
<summary><strong>includes/export_students_csv.php</strong> — CSV Student Records Export</summary>

<br>

> Not a standalone function file — this is an endpoint that exports student records as a CSV file. Requires `rep` role.
>
> **Behavior:**
> 1. Scopes query to the class rep's assigned program, year level, and section
> 2. Supports optional search filtering (student ID, name, full name combinations)
> 3. Outputs a downloadable CSV with 14 columns: Student ID, First Name, Middle Name, Last Name, Gender, Date of Birth, Blood Type, Contact Number, Email, Address, Program Code, Program Name, Year Level, Section
> 4. Logs the export action to the audit trail
>
> **Output filename format:** `CampusCare_Students_YYYY-MM-DD.csv`

</details>

---

<details>
<summary><strong>export_firstaid_pdf.php</strong> — First Aid Guideline PDF Export (Dompdf)</summary>

<br>

> Public endpoint (no login required) that generates a styled PDF of a first-aid guideline using **Dompdf**.
>
> **Usage:** `export_firstaid_pdf.php?id=<guideline_id>`
>
> **Behavior:**
> 1. Validates the guideline ID from the query string
> 2. Fetches the guideline from the `first_aid_guidelines` table (only active records)
> 3. Embeds the guideline icon and app logo as base64 data URIs for portability
> 4. Renders a styled HTML document with header, title, content, and footer
> 5. Generates an A4 portrait PDF and streams it as a download
>
> **Output filename format:** `FirstAid_<sanitized_title>.pdf`

</details>

---

<details>
<summary><strong>js/app.js</strong> — Client-Side Utilities & UI Helpers</summary>

<br>

> Main JavaScript file loaded on every authenticated page. Provides SweetAlert2 wrappers, form validation, search utilities, and UI helpers.

#### SweetAlert2 Helpers

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `showAlert()` | `showAlert(icon, title, text)` | `Promise` | Displays a SweetAlert2 modal dialog with the given icon (`success`, `error`, `warning`, `info`), title, and body text. |
| `showToast()` | `showToast(icon, title)` | `Promise` | Displays a toast notification in the top-right corner. Auto-dismisses after 3 seconds with a progress bar. Pauses on hover. |
| `scheduleToast()` | `scheduleToast(icon, title)` | `void` | Saves toast data to `sessionStorage` and reloads the page. The toast is displayed after the reload (useful for post-action feedback). |
| `showConfirm()` | `showConfirm(title, text, confirmText, icon)` | `Promise` | Displays a confirmation dialog with confirm/cancel buttons. Returns a Promise that resolves with the user's choice. |
| `showDeleteConfirm()` | `showDeleteConfirm(itemName)` | `Promise` | Displays a delete-specific confirmation dialog with a red confirm button and trash icon. |

#### Form & Validation

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `initFormValidation()` | `initFormValidation()` | `void` | Initializes Bootstrap's client-side validation on all forms with the `.needs-validation` class. Focuses the first invalid field and shows an error toast on submit. |

#### Date Formatting

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `initDateFormatOverride()` | `initDateFormatOverride()` | `void` | Overrides all `<input type="date">` elements to display dates in `mm/dd/yyyy` format using CSS `data-display` attributes. Uses a `MutationObserver` to handle dynamically added inputs. |

#### Search

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `debounce()` | `debounce(func, wait)` | `Function` | Returns a debounced version of the given function that delays invocation until `wait` milliseconds after the last call. |
| `initLiveSearch()` | `initLiveSearch(inputSelector, targetUrl, resultContainerId)` | `void` | Attaches a debounced (300ms) live search handler to an input field. Sends AJAX requests to `targetUrl` and populates the result container with the HTML response. |

#### AJAX Helpers

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `postForm()` | `postForm(url, formData)` | `Promise<Object>` | Sends a POST request with `FormData` body and returns the parsed JSON response. Includes the `X-Requested-With: XMLHttpRequest` header. |
| `postJson()` | `postJson(url, data)` | `Promise<Object>` | Sends a POST request with a JSON body and returns the parsed JSON response. Sets `Content-Type: application/json`. |

#### Utility Functions

| Function | Signature | Returns | Description |
| -------- | --------- | ------- | ----------- |
| `formatNumber()` | `formatNumber(num)` | `string` | Formats a number with comma separators (e.g., `1000` → `'1,000'`). |
| `copyToClipboard()` | `copyToClipboard(text)` | `void` | Copies the given text to the clipboard using the Clipboard API and shows a success toast. |
| `printSection()` | `printSection(elementId)` | `void` | Opens a new window with the content of the specified element (by ID), includes Bootstrap styles, and triggers the browser print dialog. |

</details>

---

<details>
<summary><strong>config/config.php</strong> — Application Configuration Constants</summary>

<br>

> Defines all application-wide constants. No functions — constants only.

| Constant | Default Value | Description |
| -------- | ------------- | ----------- |
| `DB_HOST` | `'localhost'` | Database server hostname |
| `DB_NAME` | `'campuscare'` | Database name |
| `DB_USER` | `'root'` | Database username |
| `DB_PASS` | `''` | Database password |
| `DB_CHARSET` | `'utf8mb4'` | Database character set |
| `APP_NAME` | `'CampusCare'` | Application display name |
| `APP_TAGLINE` | `'School Clinic Patient Information Record System'` | Application tagline |
| `APP_VERSION` | `'1.0.0'` | Application version string |
| `BASE_URL` | `'/CampusCare'` | Base URL path (adjust for deployment) |
| `SESSION_LIFETIME` | `3600` | Session timeout in seconds (1 hour) |
| `SESSION_NAME` | `'CAMPUSCARE_SESSION'` | PHP session cookie name |
| `ROOT_PATH` | *(auto-detected)* | Absolute path to project root |
| `INCLUDES_PATH` | *(auto-detected)* | Absolute path to `includes/` directory |
| `CONFIG_PATH` | *(auto-detected)* | Absolute path to `config/` directory |
| `LIBS_PATH` | *(auto-detected)* | Absolute path to `libs/` directory |

</details>

---

## License

This project is for educational purposes.

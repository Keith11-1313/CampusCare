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

## Page Features & Functionalities

A detailed breakdown of every page's UI components and features, organized by module.

---

### Public Pages

<details>
<summary><strong>index.php</strong> — Public Landing Page</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Navbar** | Fixed-top navbar, responsive hamburger toggle, anchor links, CTA button | Sticky navigation with smooth-scroll links to each section and a **"Staff Login"** CTA button |
| **Hero Section** | Image carousel (4 slides), animated background blobs, CTA buttons | Auto-sliding Bootstrap carousel (2.5s interval) with clinic photos, prev/next controls, dot indicators. Two CTA buttons: **"Latest Updates"** and **"Emergencies"** |
| **Announcements** | Card grid (3 columns), date badges | Displays up to 6 published announcements in responsive cards with "New" badges and formatted dates |
| **First Aid Guidelines** | Expandable cards, collapse/expand with chevron animation, PDF export button | Each guideline is a clickable card that expands to reveal content (from Quill editor). Includes a **"Save as PDF"** button that triggers Dompdf export |
| **FAQs** | Bootstrap accordion | Collapsible accordion with the first item expanded by default. Question/answer format |
| **Emergency Contacts** | Contact cards with phone icons, clickable `tel:` links | Displays emergency contacts with name, role, and tappable phone numbers |
| **Clinic Hours** | Day-by-day schedule list, live "Open/Closed" status badge | Weekly schedule with icons per day, highlights today's row, shows real-time open/closed status based on current server time |
| **Footer** | Centered branding | Logo, app name, and year |
| **Scroll Behavior** | Smooth scroll JS, navbar scroll effect | Smooth-scrolls to anchors with 80px offset; navbar gains `.scrolled` class on scroll for visual effect |

</details>

<details>
<summary><strong>login.php</strong> — Login & Password Recovery</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Login Card** | Split-panel card (left: form, right: welcome), animated background blobs, fade-in animation | Two-panel layout with sign-in form on the left and a branded welcome panel with logo on the right |
| **Sign-In Form** | Username input, password input with toggle visibility, submit button, CSRF token | Standard login with password eye-toggle icon and Bootstrap validation |
| **Forgot Password Link** | Modal trigger link | Opens the forgot password modal |
| **Forgot Password Modal** | Method selection cards, multi-step wizard | Two recovery options presented as styled cards with icons and chevrons |
| **→ Contact Admin Flow** | Form with username, reason textarea, new password + confirm fields, password strength checklist, info alert | Submits a password reset request to the admin. Includes live password validation with checkmark indicators |
| **→ Security Question Flow** | 3-step wizard with progress dots, AJAX-powered steps, loading spinners | **Step 1:** Enter username → **Step 2:** Answer security question → **Step 3:** Set new password. Each step uses AJAX with error handling and spinner feedback |
| **Password Strength UI** | Live checklist (5 rules), color-coded icons | Real-time password validation showing ✓/✗ for: length, uppercase, lowercase, number, special character |
| **SweetAlert2 Feedback** | Success/error popups | Displays styled alerts for submission results |

</details>

<details>
<summary><strong>change_password.php</strong> — Change Password</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Password Form** | Current password, new password, confirm password with toggle visibility buttons | Stepper-style flow for changing password with live strength validation |
| **Strength Checklist** | Real-time validation list, reuse prevention check | Validates against the 5 rules plus checks that the new password differs from the current one |

</details>

<details>
<summary><strong>change_security_question.php</strong> — Change Security Question</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Security Form** | Current password verification, question dropdown, answer input | Users must verify their current password before changing their security question and answer |

</details>

---

### Admin Module (`admin/`)

<details>
<summary><strong>admin/dashboard.php</strong> — Admin Dashboard</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Page Header** | Welcome message, current date | Personalized greeting with today's date |
| **Statistics Cards** | 4 clickable stat cards with icons, hover lift animation, month-over-month % change | **Registered Students**, **Active Nurses**, **Visits This Month** (with ↑/↓ % vs last month), **Pending Requests** (with "Review now" link). Each card links to its respective page |
| **Visits – Last 7 Days** | Bar chart (Chart.js), backfilled missing days | Vertical bar chart showing daily visit counts for the past week |
| **Quick Actions** | Button list (4 actions) | Shortcut buttons: Manage Users, View Reports, Access Logs, Archived Records |
| **Students by Year Level** | Horizontal bar chart (Chart.js), color-coded bars | Displays student distribution across year levels |
| **Top Complaints This Month** | Doughnut chart (Chart.js) with legend | Shows the top 6 complaint categories for the current month |
| **Recent Visits** | Data table (10 rows), status badges, "View Reports" link | Displays student name, ID, complaint, date, attending nurse, and color-coded status badges |
| **Visit Status This Month** | Doughnut chart (Chart.js) | Distribution of Completed / Follow-up / Referred visits |
| **Recent Activity Feed** | Activity timeline with icons, color-coded by action type | Shows last 5 access log entries with user name, action, and timestamp. Icons differentiate logins, warnings, and general actions |

</details>

<details>
<summary><strong>admin/users.php</strong> — User Management</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Page Header** | Breadcrumb, "Add User" button | Navigation breadcrumb and primary action button |
| **Filter Bar** | Search input with icon, role dropdown filter, Clear/Filter button | Real-time search across username, name, email; filter by role (Admin/Nurse/Class Rep) |
| **Users Table** | Sortable columns, pagination, action buttons | Sortable by User, Username, Role, Last Login, Status. Shows assignment info for class reps. Edit and Archive action buttons per row |
| **Add/Edit User Modal** | 3-step stepper wizard (Personal Info → Account → Assignment) | **Step 1:** First name, last name, email with real-time keystroke validation (letters only). **Step 2:** Username, password with strength checklist + confirmed, role selector. **Step 3:** Program/year/section assignment (only shown for Class Rep role). Step validation prevents advancing with errors (shake animation) |
| **Deactivation Modal** | Reason dropdown (6 options), optional "Other" textarea | Requires a reason before archiving a user. Prevents self-deactivation |
| **Rep Replacement Flow** | Prefill alert banner, auto-deactivation of old rep | When approving a replacement request, pre-fills the Add User modal and auto-deactivates the outgoing class rep upon save |

</details>

<details>
<summary><strong>admin/programs.php</strong> — Program Management</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Programs Table** | Sortable table, CRUD actions | List of academic programs with code, name, and student count |
| **Add/Edit Modal** | Form with input validation (special character prevention) | Code and name fields with real-time keystroke filtering to block special characters |

</details>

<details>
<summary><strong>admin/students.php</strong> — Student Records (Admin View)</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Student Table** | Search, sortable columns, pagination | Read-only view of all active student records with filtering |

</details>

<details>
<summary><strong>admin/current_requests.php</strong> — Pending Requests</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Requests Table** | Request type badges, status badges, action buttons | Lists all pending requests (replacement, deletion, password reset) |
| **Password Reset Approval** | One-click approve button | Approves the request and applies the pre-stored hashed password to the user's account |
| **Replacement Approval** | Redirects to user creation with pre-filled data | Navigates to the Add User modal with nominee details pre-populated |
| **Reject Action** | SweetAlert2 confirmation | Confirms before rejecting a request |

</details>

<details>
<summary><strong>admin/archive.php</strong> — Archived Records</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Tab Navigation** | Bootstrap tabs (Students, Users, Programs) | Three tabs to switch between archived record types |
| **Archive Tables** | Restore and permanent delete buttons, SweetAlert2 confirmations | Each record has a Restore button and a Delete button with confirmation dialog |

</details>

<details>
<summary><strong>admin/reports.php</strong> — Reports & Analytics</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Filter Panel** | Date range pickers, program/year/section dropdowns | Filter report data by date range and academic grouping |
| **Generate PDF Modal** | Section checkboxes, sort order dropdown, page break toggle, landscape toggle | Allows selecting which report sections to include, sort order for visit records, and layout preferences |
| **Charts** | Chart.js visualizations (bar, doughnut, horizontal bar) | Visits by month, visits by program, top complaints (podium chart), visit status, top allergens, top vaccines |
| **Print/PDF Export** | Canvas-to-image conversion, `window.print()` | Converts Chart.js canvases to static PNG images before triggering the print dialog |

</details>

<details>
<summary><strong>admin/access_logs.php</strong> — Activity Logs</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Logs Table** | Sortable columns, search, pagination | Full audit trail showing user, action, description, IP address, and timestamp |
| **Filter** | Search input, date filter | Filter logs by user, action type, or time period |

</details>

---

### Nurse Module (`nurse/`)

<details>
<summary><strong>nurse/dashboard.php</strong> — Nurse Dashboard</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Statistics Cards** | Stat cards with icons | Visit counts and trends for the nurse |
| **Charts** | Chart.js visualizations | Visit statistics and trends relevant to the nurse role |
| **Recent Visits** | Data table | Quick overview of latest clinic visits |

</details>

<details>
<summary><strong>nurse/new_visit.php</strong> — New Visit / Log Visit</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Student Search** | Live search input | Search and select a student to log a visit for |
| **Visit Form** | Vitals inputs, complaint category dropdown, complaint text, clinical assessment (required), treatment notes, status selector | Comprehensive visit logging form with categorized complaint dropdown and required assessment field |

</details>

<details>
<summary><strong>nurse/students.php</strong> — Student Records (Nurse View)</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Student Table** | Search (supports full-name search), sortable columns, pagination | Searchable list of all students with links to individual profiles |

</details>

<details>
<summary><strong>nurse/student_profile.php</strong> — Student Health Profile</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Personal Information** | Read-only info card | Student's demographic and contact data |
| **Allergies** | CRUD table with categorized `<optgroup>` dropdown | Add/edit/delete allergens organized by category (Food, Drug, Environmental, etc.) with severity badge |
| **Chronic Conditions** | CRUD table with categorized dropdown | Manage chronic conditions with status tracking (Active/Resolved) |
| **Medications** | CRUD table | Current and past medication records |
| **Immunizations** | CRUD table with categorized dropdown, edit action | Vaccine records organized by category with date tracking |
| **Emergency Contacts** | CRUD table | Emergency contact persons with phone and relationship |
| **Visit History** | Data table with status badges | All clinic visits for this student in reverse chronological order |
| **Clinical Notes Form** | Assessment (required), treatment fields | Form to add clinical notes to visits |

</details>

<details>
<summary><strong>nurse/visits.php</strong> — Visit History</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Filter Bar** | Search (full-name support), date range, status filter | Multi-criteria filtering for visit records |
| **Visits Table** | Sortable columns, pagination, status badges | Comprehensive visit record list with sorting and color-coded statuses |

</details>

<details>
<summary><strong>nurse/reports.php</strong> — Nurse Reports</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Charts & Reports** | Same as admin reports — Chart.js + PDF export | Full reporting suite with chart visualizations and PDF export capability |

</details>

<details>
<summary><strong>nurse/content.php</strong> — Public Content Management</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Tab Navigation** | Bootstrap tabs (Announcements, FAQs, First Aid, Emergency Contacts, Clinic Hours) | Five content areas managed via tabs |
| **Announcements** | CRUD table, Quill rich text editor in modal | Create/edit/delete announcements with rich text content and publish/draft status toggle |
| **FAQs** | CRUD table | Question and answer management |
| **First Aid Guidelines** | CRUD table, icon selector, Quill editor | Create guidelines with custom SVG icons and rich text content |
| **Emergency Contacts** | CRUD table, drag-and-drop sort order | Manage emergency contacts with sortable display order |
| **Clinic Hours** | Editable day-by-day schedule | Set opening/closing times, closed days, and notes per day of the week |

</details>

---

### Class Representative Module (`rep/`)

<details>
<summary><strong>rep/dashboard.php</strong> — Class Rep Dashboard</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Section Overview** | Stat cards | Student count for the assigned program/year/section |
| **Student Summary** | Quick-glance table | Overview of students in the rep's assigned section |

</details>

<details>
<summary><strong>rep/students.php</strong> — Manage Students</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Student Table** | Search, sortable columns, pagination, CRUD actions | Scoped to the rep's assigned program/year/section |
| **Add/Edit Student Modal** | Student information form with validation | Create and update student records within the assigned scope |
| **CSV Export** | Export button | Downloads student records as a CSV file with 14 columns |
| **Delete Action** | SweetAlert2 confirmation | Submits a deletion request to admin for approval |

</details>

<details>
<summary><strong>rep/request_change.php</strong> — Replacement & Deletion Requests</summary>

<br>

| Section | UI Components | Description |
| ------- | ------------- | ----------- |
| **Replacement Request** | Form with nominee student selection, reason textarea | Submit a request to be replaced by another student as class representative |
| **Request History** | Status-tagged list | View past requests and their statuses (Pending/Approved/Rejected) |

</details>

---

### Shared Components

<details>
<summary><strong>Shared UI Components</strong> — Used across all modules</summary>

<br>

| Component | Technology | Description |
| --------- | ---------- | ----------- |
| **Top Navbar** | Bootstrap 5 fixed-top navbar | Brand logo, sidebar toggle (mobile), user dropdown with avatar initials |
| **Sidebar** | Custom CSS with role-based rendering | Collapsible on mobile with overlay, section headings, active page highlighting |
| **Toast Notifications** | SweetAlert2 toast (top-right) | Auto-dismiss after 3s with progress bar, pause on hover. Used for success/error/info/warning feedback |
| **Confirmation Dialogs** | SweetAlert2 modal | Styled confirm/cancel dialogs for destructive actions (delete, deactivate, logout) |
| **Sortable Table Headers** | Custom PHP helper + CSS | Clickable column headers with ascending/descending sort icons; preserves all query parameters |
| **Pagination** | Custom PHP helper | Bootstrap-styled pagination with ellipsis, prev/next buttons, active page highlighting |
| **Status Badges** | Bootstrap badges, color-coded | Consistent status representation: green (active/completed), yellow (follow-up/moderate), red (severe/referred), gray (inactive/draft) |
| **Form Validation** | Bootstrap validation + custom JS | `.needs-validation` class enables client-side validation with focus-on-first-invalid and error toast |
| **Date Format Override** | Custom JS + CSS data-display | All date inputs display in `mm/dd/yyyy` format; uses MutationObserver for dynamically added inputs |
| **Password Strength Checker** | Live JS validation with icon indicators | Real-time checklist (✓/✗) for 5 password rules, shared across login, change password, and user management |
| **Stepper Wizard** | Custom CSS stepper with step validation | Multi-step form flow (e.g., 3-step user creation) with progress circles, step completion checks, and shake animation on validation failure |
| **Background Blobs** | CSS animated blobs | Floating gradient blobs on the login and hero sections for visual depth |
| **Flash Messages** | PHP session + SweetAlert2 | Server-side flash messages automatically displayed as toast notifications on page load |

</details>

---

## License

This project is for educational purposes.


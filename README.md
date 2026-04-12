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

### Recent Enhancements

- **Categorized Health Dropdowns** — Allergens, chronic conditions, and immunizations organized into `<optgroup>` categories for faster data entry
- **Contact Admin Password Reset** — Users can request a password reset by setting a desired password; admin approves with one click
- **Full-Name Search** — All search queries support `CONCAT(first_name, ' ', last_name)` for seamless full-name lookups
- **Report Generation Improvements** — Removed redundant charts, added data tables for PDF exports, horizontal grid lines on bar charts, numerical counts on doughnut charts
- **Enhanced Input Validation** — Server-side and client-side validation for names (supports ñ/Ñ, periods, hyphens, apostrophes), programs, emergency contacts, and prescribing doctor fields
- **Archive Delete Action** — Permanent deletion of archived students, users, and programs
- **Password Policy Enforcement** — Stepper-based password change flow with "must not reuse" validation
- **Immunization Edit** — Replaced delete with edit action for immunization records
- **Password Visibility Toggles** — Added to all password fields across login, security question, and change password flows

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

## License

This project is for educational purposes.

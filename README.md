# CampusCare

## School Clinic Patient Information Record System

A PHP/MySQL web application for managing school clinic operations, student health records, clinic visits, and public health information.

---

## Features

| Role | Capabilities |
| ------ | ------------- |
| **Admin** | Dashboard, user management (CRUD), programs & year levels, access logs, archives, reports (Chart.js + PDF export) |
| **Nurse/Staff** | Dashboard, student search & profile (allergies, conditions, medications, immunizations, emergency contacts), visit logging, visit history, public content management (announcements, FAQs, first-aid, emergency contacts, clinic hours) |
| **Class Rep** | Dashboard, student CRUD (scoped to assigned program/year/section), CSV export of student records |
| **Public** | Landing page with announcements, FAQs, first-aid guidelines, emergency contacts, clinic hours |

## Security

- **PDO prepared statements** (SQL injection prevention)
- **CSRF token protection** on all forms
- **Bcrypt password hashing** (`password_hash`)
- **Session management** with timeout & secure cookies
- **Role-based access control** middleware
- **Output encoding** (XSS prevention via `htmlspecialchars`)
- **Access logging** (audit trail)

---

## Requirements

- **PHP 7.4+** (with PDO MySQL extension)
- **MySQL 5.7+** or MariaDB 10.3+
- **Apache** (with `mod_rewrite`) or any PHP-compatible web server
- **Node.js/npm** (for frontend dependencies)
- **XAMPP/WAMP/MAMP** recommended for local development

## Installation

### 1. Clone / Copy Project

Place the `CampusCare` folder in your web server's document root (e.g., `htdocs` for XAMPP).

### 2. Install Frontend Dependencies

```powershell
cd CampusCare
npm install bootstrap
npm install bootstrap-icons
npm install sweetalert2
npm install chart.js
```

### 3. Create the Database

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

### 4. Configure the credentials (for deployment)

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

### 5. Start the Server

Start Apache & MySQL in XAMPP, then visit:

```text
http://localhost/CampusCare
```

---

## Default Login Credentials

| Role | Username | Password |
| ------ | ---------- | ---------- |
| Admin | `admin` | `admin123` |
| Nurse | `nurse_garcia` | `nurse123` |
| Nurse | `nurse_santos` | `nurse123` |
| Class Rep | `rep_delacruz` | `rep123` |
| Class Rep | `rep_reyes` | `rep123` |

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
│   ├── users.php
│   └── year_levels.php
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
│   ├── clinic1.jpg
│   ├── clinic2.jpg
│   ├── clinic3.jpg
│   ├── clinic4.jpg
│   └── logo-main-w.png
├── config/
│   ├── .htaccess           # Deny direct access
│   ├── config.php          # App config & DB credentials
│   └── database.php        # PDO singleton
├── includes/
│   ├── .htaccess           # Deny direct access
│   ├── auth.php            # Auth helpers & RBAC
│   ├── export_pdf.php      # PDF report export (admin)
│   ├── export_students_csv.php  # CSV student records export (rep)
│   ├── footer.php          # Page footer template
│   ├── functions.php       # Utility functions
│   ├── header.php          # Page header template
│   ├── session.php         # Session & CSRF management
│   └── sidebar.php         # Role-aware sidebar
├── database/
│   ├── demo-data/          # Demo/testing data
│   │   ├── campuscare.sql
│   │   ├── programs.pdf
│   │   └── seed_data.sql
│   └── real-data/          # Production data & scripts
│       ├── bulk_seed_data.sql
│       ├── campuscare.sql
│       ├── generate_seed.py
│       └── seed_data.sql
├── css/style.css           # Custom styles
├── js/app.js               # Main JavaScript
├── index.php               # Public landing page
├── login.php               # Login page
├── logout.php              # Logout handler
├── change_password.php     # Change password
├── change_security_question.php  # Change security question
├── demo_students.csv       # Demo student data CSV
├── package.json            # npm dependencies
└── .gitignore              # gitignore file
```

## Tech Stack

- **Backend:** PHP 7.4+ (vanilla, no framework)
- **Database:** MySQL via PDO
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, SweetAlert2, Chart.js
- **Typography:** Google Fonts (Inter)

---

## License

This project is for educational purposes.

# Barangay Sinalhan Patient Management System

A modern, web-based patient management system designed for the **Barangay Sinalhan Health Center** (Santa Rosa City, Laguna, Philippines). Built with native PHP, Bootstrap 5, MySQL, Progressive Web App (PWA) offline sync capabilities, Server-Sent Events (SSE) real-time queue streaming, and HIPAA-compliant AES-256-CBC column encryption.

This system digitizes paper-based processes for patient registration, clinical health record-keeping (consultations), scheduled appointment tracking, and walk-in queue workflows. It is tailored for local network deployment using XAMPP, making it highly suitable for barangay health facilities with limited IT infrastructure.

---

## 🌟 Core Features List

1. **Authentication & Security:** Multi-factor authentication (MFA) via Google Authenticator TOTP, strict page-level role guards (`admin`, `staff`, `bhw`), CSRF token security, and password complexity requirements.
2. **Patient Directory:** Demographics management with real-time duplicate profile prevention (via name/birthdate checks) and encrypted health history/allergies columns.
3. **Health Records (Consultations):** Vital signs tracking, automatic Body Mass Index (BMI) calculator, and AES-256 encryption of chief complaints, diagnoses, treatments, prescriptions, and notes. Includes pre-seeded **Consultation Templates** to speed up patient care.
4. **Appointments Scheduler:** Visual calendar tracker mapping scheduled consultations, status lifecycles (`Scheduled`, `Completed`, `Cancelled`, `No-Show`), and administrator bulk tools to resolve past-due bookings.
5. **Daily Queue Manager:** Walk-in sequence manager resetting daily at 12:00 AM, with visual Kanban boards, chime sound alerts, and text-to-speech call-out notifications for the waiting area display screen.
6. **Reports Generator:** Operational reports detailing patient demographics, Purok/sex distribution, consultation tallies, and clean `@media print` print-ready layouts.
7. **Soft-Deleted Archive Recovery:** Admin-only trash bin where soft-deleted records across all tables (Patients, Consultations, Appointments, Queue tickets) can be restored with a single click.
8. **PWA & IndexedDB Offline Sync:** Offline registration capability using Service Worker caching and IndexedDB (`SinalhanOfflineDB`). Local entries automatically flag a sync warning in the navbar and upload once connection is restored.

---

## 🛠️ Technology Stack

- **Backend:** Native PHP (v7.4 - v8.2 compatible), timezone locked to `Asia/Manila`.
- **Database:** MySQL / MariaDB (strict PDO database connector implementing the Singleton pattern).
- **Frontend UI:** HTML5, CSS3 (Vanilla Custom Stylesheets), Bootstrap v5.3.0, Bootstrap Icons.
- **Client Scripts:** Javascript (ES6), jQuery (v3.6.0), Chart.js (dashboard visualizations), SweetAlert2 (interactive popups).
- **Offline Shell:** Progressive Web App (PWA) Service Worker caching, IndexedDB Client Database.
- **Security:** OpenSSL AES-256-CBC data encryption, TOTP Base32 verification.

---

## 📂 Project Structure Overview

```text
sinalhan-health-system/
│
├── admin/                  # Administrative controllers & views (users, reports, settings, archives)
├── ajax/                   # AJAX API endpoints (search, duplicate checking, preferences, notifications)
├── appointments/           # Appointment scheduler modules (add, edit, list, batch no-show)
├── assets/                 # Public assets folder
│   ├── css/                # Custom CSS files (style.css, dashboard.css, login.css)
│   ├── images/             # Clinic branding logos and images
│   └── js/                 # Client JavaScript scripts (main.js, dashboard.js)
│
├── auth/                   # Authentication & user profile scripts (login, 2FA, logout)
├── config/                 # Application configuration (database, app constants, session security)
├── health_records/         # Clinical consultations views & processing
├── includes/               # Reusable headers, footers, sidebars, alerts, and helpers
├── logs/                   # Secure write-only server error logs
├── patients/               # Patient directory modules (online and offline register)
├── queue/                  # Daily waiting queue management and waiting room monitor
├── sql/                    # SQL schema definitions and consultation templates seeding scripts
│
├── index.php               # System entry point (role-based router redirect)
├── manifest.json           # PWA metadata manifest
├── offline.php             # Offline network failure fallback page
└── service-worker.js       # PWA caching and network fetch interceptor
```

---

## 🚀 Installation & Setup

Follow these steps to deploy and run the Sinalhan Patient Management System locally:

### Step 1: Start Services in XAMPP
1. Search for and open the **XAMPP Control Panel** on your computer.
2. Click the **Start** button next to **Apache** (Web Server).
3. Click the **Start** button next to **MySQL** (Database Server).
   * *Both module names should highlight in green, indicating they are running.*

### Step 2: Import the Database
1. Open your web browser and navigate to: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Click on **New** in the left sidebar to create a new database.
3. Set the database name to `bhc_sinalhan_db` and click **Create**.
4. With `bhc_sinalhan_db` selected, click the **Import** tab on the top menu.
5. Click **Choose File** and select the database schema file located in:
   `[Project Folder]/sql/bhc_sinalhan_db.sql`
6. Scroll down and click **Import** (or **Go**).
7. Next, click the **Import** tab again, choose `[Project Folder]/sql/add_consultation_templates.sql`, and click **Import** to seed standard consultation templates.

### Step 3: Access and Use the Application
1. Place the project folder inside your XAMPP root folder (typically `C:\xampp\htdocs\sinalhan-health-system`).
2. In your browser, navigate to: [http://localhost/sinalhan-health-system/](http://localhost/sinalhan-health-system/)
3. Log in using one of the pre-seeded testing accounts:

| User Role | Username | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Full Access (Settings, Exporters, Archives, Audit trail) |
| **Health Center Staff** | `staff01` | `staff123` | Clinical Access (Register, Consultations, Appointments, Queue) |
| **Barangay Health Worker** | `bhw01` | `bhw123` | Walk-in Access (Register Patient, Create Queue Ticket) |

---

## 🔒 Configuration Requirements

Key parameters are configured inside `config/app.php` and `config/session.php`:
- **Timezone:** Standard timezone is set to `Asia/Manila`.
- **Base URL:** Dynamically calculated based on the subdirectory deployment layout.
- **HIPAA AES Key:** Defined as `ENCRYPTION_KEY`. Ensure this is locked, backed up, and at least 32 bytes. Admin can rotate this key inside settings.
- **Session Lifespans:** Configured to `1800` seconds (30 minutes of idle time) before forcing automatic logout.

---

## 📷 Screenshots Section

*Placeholders for UI Mockups/Screenshots:*
1. **Glassmorphic Login:** `[assets/images/screenshots/login_page.png]`
2. **Admin Dashboard:** `[assets/images/screenshots/admin_dashboard.png]`
3. **Kanban Queue Board:** `[assets/images/screenshots/queue_manager.png]`
4. **Offline Register Form:** `[assets/images/screenshots/offline_registration.png]`
# Barangay Sinalhan Patient Management System - Features Catalog

This document details all features, security implementations, offline capabilities, and role permissions of the **Barangay Sinalhan Patient Management System** (Santa Rosa City, Laguna, Philippines).

---

## 🏛️ System Architecture & Stack
* **Backend:** Native PHP with dynamic timezone settings (`Asia/Manila`) and strict PDO MySQL singleton architecture.
* **Frontend:** Bootstrap 5, Bootstrap Icons, Vanilla CSS, and SweetAlert2 interactive popups.
* **Real-time Engine:** Server-Sent Events (SSE) streaming live waiting lists from `queue_sse.php`.
* **Offline Engine:** PWA Service Worker caching and browser-native IndexedDB database (`SinalhanOfflineDB` with store `pending_patients`).
* **Security Layer:** OpenSSL AES-256-CBC cryptographic column encryption, CSRF protection, and TOTP Base32 verification.

---

## 📋 Comprehensive Feature Directory

### 1. 🔐 Authentication, Authorization & MFA
* **Modern Login Interface:** A glassmorphic login screen featuring password-toggle visibility and CDNs-protected library modules.
* **CSRF Protection:** Secure session-bound CSRF token validation on all state-changing POST requests (login, registering, scheduling, modifying).
* **Role Guards:** Strict PHP authentication checks and role-based page protection restricting routes to (`admin`, `staff`, or `bhw`).
* **Multi-Factor Authentication (MFA):** Google Authenticator-compliant TOTP 2-Factor setup page inside User Profiles. Verification includes calendar drift checks (+/- 30s steps discrepancy tolerance).
* **Complexity Rules:** High-security password validation requiring 8+ characters, uppercase, lowercase, numbers, and special symbols during registration or reset.

### 2. 📇 Patient Care & Directory
* **Detailed Demographics:** Captures First/Last/Middle names, suffixes, birthdate, sex, Purok/Zone selectors, contact details, medical history, and allergies.
* **Real-time Duplicate Validation:** Input-blur checks querying names and birthdates to flag duplicate profiles before submission.
* **Demographics Encryption:** Automatically encrypts patient medical history and allergy columns in the database using AES-256-CBC. Decrypts legacy rows transparently.
* **Purok Filtering:** Directory search table with zone filters and role-restricted CRUD buttons.

### 3. 🩺 Health Records & Consultations
* **Vital Signs Tracking:** Logs Systolic/Diastolic Blood Pressure, Temperature, Weight (kg), Height (cm), Heart Rate (bpm), and Respiration Rate (bpm).
* **Automatic BMI Calculator:** Calculates BMI on form entry and displays obesity/underweight warnings in real-time.
* **Clinical Records Encryption:** Strict encryption of chief complaints, diagnoses, treatments, prescriptions, and notes.
* **Consultation Templates:** Database-driven template loading for standard clinical workflows (cough & cold, prenatal, hypertension follow-up, pediatric immunization). Pre-fills chief complaint, diagnosis, treatment, and prescription fields automatically via AJAX to accelerate check-ups.
* **History Tab Integration:** Dynamic patient consultation charts rendered inside demographic profile files.

### 4. 📅 Appointments Scheduler
* **Calendar Scheduler:** Schedules patient consultations.
* **Validation Tally:** Blocks the scheduling of past dates or times.
* **Status Lifecycles:** Renders appointments under dynamic states (`Scheduled`, `Completed`, `Cancelled`, `No-Show`) with color-coded badges.
* **Bulk Overdue Resolution:** Admin-only dashboard feature to automatically mark all past-due scheduled appointments as "No-Show" in a single transaction.

### 5. 🚶 Daily Queue Management
* **Sequential Ticketing:** Generates sequential queue numbers resetting automatically at 12:00 AM every calendar day.
* **Service Queue Prefixes:** Customizes ticket prefixes for each service category (e.g. `GEN` for General, `PRE` for Prenatal) stored in `service_types` table.
* **Branded Print Overlays:** Receipt SweetAlert mockups with direct print triggers containing the patient's name, booking number, and service.
* **Kanban Queue Manager:** A three-column grid board (Waiting, Serving, Completed) for staff.
* **Waiting Room Monitor (`display.php`):** A dark fullscreen display utilizing SSE streams to poll calls. Features chime sound alerts and Text-to-Speech vocal call-outs.

### 6. 📊 Reports Generator
* **Tally Widgets:** Computes statistical distributions (Patients by Purok, patients by sex, consultations by category, appointments by status, and queue tallies).
* **Print-Ready Stylesheets:** `@media print` CSS overrides that automatically hide sidebars, headers, and dashboard widgets when printing the reports.

### 7. 🛡️ Audit Trail & Deleted Archives
* **Activity Logs:** Write-only logging capturing user ID, activity category (Patient, Consultation, Appointment, Queue, Security, Auth, System), IP address, and details.
* **Recovery Bin (`archived_records.php`):** A centralized tabbed recovery archive for soft-deleted patients, appointments, queue tickets, and health records, enabling one-click restoration. Chief complaints are securely decrypted prior to listing.

### 8. 📴 PWA & Offline Sync
* **Offline Fallbacks:** Modern `manifest.json` and cache control fallbacks to redirect BHWs during network failures to offline registration.
* **IndexedDB Local Store (`SinalhanOfflineDB`):** Stores patient details locally under the `pending_patients` store. Restoring internet connection dynamically triggers sync notifications on the navigation bar.

### 9. 🔔 Interactive Notification Center
* **Live Notifications Bell:** Embedded navigation bar dropdown overlay tracking unread badge counts, online status banners, and local IndexedDB warning alerts.
* **Dynamic Audits Feed:** Shows live system logs (queue assignments, appointments status changes, and user status toggles) based on the user's role.

### 10. ⚙️ System Settings (Central Configurations)
* **Clinic Branding:** Customizes clinic name, address, contact, email, and uploads a branding logo.
* **Security Controls:** Customizes inactivity timers and enforcements of TOTP 2FA policies.
* **Master Key Rotation:** A secure cryptographic form to rotate the AES-256 keys, decrypting and re-encrypting all patient database fields in a single transaction.
* **Data Exporters:** Native database SQL backup downloader and log purging.
* **Theme Preferences:** Toggles Dark Theme modes and text scaling (Normal, Medium, Large) globally, saving user choices to the database and syncs to local storage.

---

## 🔑 Role Permission Matrix

| Module / Action | Administrator | Health Staff | Barangay Health Worker (BHW) |
| :--- | :---: | :---: | :---: |
| **Manage Settings (Clinic, Key, Security)** | Yes | No | No |
| **Download Database Backups / Purge Logs** | Yes | No | No |
| **Delete Users / Deactivate Accounts** | Yes | No | No |
| **View Audit Trail Logs** | Yes | No | No |
| **Restore Archived Records** | Yes | No | No |
| **View Operational Reports** | Yes | Yes | No |
| **Create/Edit Services Categories** | Yes | No | No |
| **Register & Edit Patients Details** | Yes | Yes | Yes |
| **Archive Patients Profiles** | Yes | No | No |
| **Record Vital Signs & Consultations** | Yes | Yes | No |
| **Manage Active Daily Queue Statuses** | Yes | Yes | Yes |
| **Generate Daily Queue Tickets** | Yes | Yes | Yes |
| **Book & Modify Appointments** | Yes | Yes | Yes |
| **Inspect PWA Storage & Adjust UI Size** | Yes | Yes | Yes |

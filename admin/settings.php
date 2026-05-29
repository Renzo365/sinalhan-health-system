<?php
// admin/settings.php
$active_menu = 'settings';
$page_title = 'System Settings';

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$role = $_SESSION['role'] ?? 'staff';
$isAdmin = ($role === 'admin');
$pdo = Database::getInstance()->getConnection();

// Fetch settings
$clinicName = get_setting($pdo, 'clinic_name', 'Barangay Sinalhan Health Center');
$clinicAddress = get_setting($pdo, 'clinic_address', 'Barangay Sinalhan, Santa Rosa City, Laguna, Philippines');
$clinicContact = get_setting($pdo, 'clinic_contact', '049-508-XXXX');
$clinicEmail = get_setting($pdo, 'clinic_email', 'info@sinalhan-hc.gov.ph');
$clinicLogo = get_setting($pdo, 'clinic_logo', '');
$sessionLifetime = (int)get_setting($pdo, 'session_lifetime_minutes', 30);
$require2FA = (int)get_setting($pdo, 'require_2fa', 0);

// Fetch service types for Prefix Settings
$services = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT service_id, service_name, prefix, description FROM service_types ORDER BY service_name ASC");
    $services = $stmt->fetchAll();
}
?>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-gear-fill text-primary me-2"></i>System Settings
        </h1>
    </div>

    <!-- Alert triggers include -->
    <?php require_once __DIR__ . '/../includes/alert.php'; ?>

    <div class="row">
        <!-- Settings Sidebar Navigation Tabs -->
        <div class="col-lg-3 mb-4">
            <div class="card-custom border-0 shadow-sm">
                <div class="list-group list-group-flush rounded-3 overflow-hidden" id="settingsTabs" role="tablist">
                    <?php if ($isAdmin): ?>
                        <button class="list-group-item list-group-item-action active text-start py-3 px-4 border-0 d-flex align-items-center gap-3" id="clinic-tab" data-bs-toggle="tab" data-bs-target="#tab-clinic" type="button" role="tab" aria-controls="tab-clinic" aria-selected="true">
                            <i class="bi bi-hospital fs-5 text-primary"></i>
                            <span class="fw-semibold">Clinic Profile</span>
                        </button>
                        <button class="list-group-item list-group-item-action text-start py-3 px-4 border-0 d-flex align-items-center gap-3" id="queue-tab" data-bs-toggle="tab" data-bs-target="#tab-queue" type="button" role="tab" aria-controls="tab-queue" aria-selected="false">
                            <i class="bi bi-ticket-perforated fs-5 text-primary"></i>
                            <span class="fw-semibold">Queue Prefixes</span>
                        </button>
                        <button class="list-group-item list-group-item-action text-start py-3 px-4 border-0 d-flex align-items-center gap-3" id="security-tab" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab" aria-controls="tab-security" aria-selected="false">
                            <i class="bi bi-shield-lock fs-5 text-primary"></i>
                            <span class="fw-semibold">Security & HIPAA</span>
                        </button>
                    <?php endif; ?>
                    
                    <button class="list-group-item list-group-item-action <?= !$isAdmin ? 'active' : '' ?> text-start py-3 px-4 border-0 d-flex align-items-center gap-3" id="pwa-tab" data-bs-toggle="tab" data-bs-target="#tab-pwa" type="button" role="tab" aria-controls="tab-pwa" aria-selected="<?= !$isAdmin ? 'true' : 'false' ?>">
                        <i class="bi bi-cloud-slash fs-5 text-primary"></i>
                        <span class="fw-semibold">PWA Offline Cache</span>
                    </button>

                    <?php if ($isAdmin): ?>
                        <button class="list-group-item list-group-item-action text-start py-3 px-4 border-0 d-flex align-items-center gap-3" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#tab-maintenance" type="button" role="tab" aria-controls="tab-maintenance" aria-selected="false">
                            <i class="bi bi-database-down fs-5 text-primary"></i>
                            <span class="fw-semibold">Backup & Purge</span>
                        </button>
                    <?php endif; ?>

                    <button class="list-group-item list-group-item-action text-start py-3 px-4 border-0 d-flex align-items-center gap-3" id="pref-tab" data-bs-toggle="tab" data-bs-target="#tab-pref" type="button" role="tab" aria-controls="tab-pref" aria-selected="false">
                        <i class="bi bi-palette fs-5 text-primary"></i>
                        <span class="fw-semibold">UI Preferences</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Settings Content Panes -->
        <div class="col-lg-9">
            <div class="tab-content" id="settingsTabContent">
                
                <?php if ($isAdmin): ?>
                    <!-- Tab: Clinic Profile -->
                    <div class="tab-pane fade show active" id="tab-clinic" role="tabpanel" aria-labelledby="clinic-tab">
                        <div class="card-custom border-0 shadow-sm p-4">
                            <h4 class="card-custom-title mb-4 border-bottom pb-2">
                                <i class="bi bi-hospital text-primary"></i> Clinic Profile Configuration
                            </h4>
                            
                            <div class="row align-items-center mb-4">
                                <div class="col-md-3 text-center">
                                    <?php if (!empty($clinicLogo)): ?>
                                        <img src="<?= BASE_URL . $clinicLogo ?>" alt="Logo" class="img-fluid rounded mb-2 border p-2" style="max-height: 120px; object-fit: contain;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center border rounded mb-2 mx-auto" style="height: 120px; width: 120px;">
                                            <i class="bi bi-hospital fs-1 text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-9">
                                    <form action="<?= BASE_URL ?>admin/settings_process.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="upload_logo">
                                        <label class="form-label fw-semibold">Upload Branding Logo</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" name="clinic_logo" accept="image/png, image/jpeg, image/jpg" required>
                                            <button class="btn btn-primary" type="submit">Upload</button>
                                        </div>
                                        <small class="text-secondary d-block mt-1">Accepts PNG or JPG files. Max file size: 2MB.</small>
                                    </form>
                                </div>
                            </div>

                            <form action="<?= BASE_URL ?>admin/settings_process.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="save_clinic_info">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Clinic Name</label>
                                    <input type="text" class="form-control" name="clinic_name" value="<?= htmlspecialchars($clinicName) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Physical Address</label>
                                    <textarea class="form-control" name="clinic_address" rows="2" required><?= htmlspecialchars($clinicAddress) ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Contact Number</label>
                                        <input type="text" class="form-control" name="clinic_contact" value="<?= htmlspecialchars($clinicContact) ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Email Address</label>
                                        <input type="email" class="form-control" name="clinic_email" value="<?= htmlspecialchars($clinicEmail) ?>">
                                    </div>
                                </div>
                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Clinic Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tab: Queue Prefixes -->
                    <div class="tab-pane fade" id="tab-queue" role="tabpanel" aria-labelledby="queue-tab">
                        <div class="card-custom border-0 shadow-sm p-4">
                            <h4 class="card-custom-title mb-4 border-bottom pb-2">
                                <i class="bi bi-ticket-perforated text-primary"></i> Service Ticket Prefixes
                            </h4>
                            <p class="text-secondary small mb-4">Set unique ticketing prefixes for each service category. For instance, assigning <code>GEN</code> to General Consultation maps generated tickets to <code>GEN-001</code>, <code>GEN-002</code>, etc.</p>

                            <form action="<?= BASE_URL ?>admin/settings_process.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="save_queue_settings">

                                <div class="table-responsive">
                                    <table class="table table-custom align-middle">
                                        <thead>
                                            <tr>
                                                <th>Service Name</th>
                                                <th>Description</th>
                                                <th style="width: 150px;">Prefix</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($services as $svc): ?>
                                                <tr>
                                                    <td>
                                                        <strong class="text-dark"><?= htmlspecialchars($svc['service_name']) ?></strong>
                                                    </td>
                                                    <td class="small text-secondary"><?= htmlspecialchars($svc['description'] ?? 'No description') ?></td>
                                                    <td>
                                                        <input type="text" class="form-control text-center fw-bold" name="prefixes[<?= $svc['service_id'] ?>]" value="<?= htmlspecialchars($svc['prefix'] ?? '') ?>" maxlength="10" placeholder="e.g. GEN" required>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Prefixes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tab: Security & HIPAA -->
                    <div class="tab-pane fade" id="tab-security" role="tabpanel" aria-labelledby="security-tab">
                        <div class="card-custom border-0 shadow-sm p-4">
                            <h4 class="card-custom-title mb-4 border-bottom pb-2">
                                <i class="bi bi-shield-lock text-primary"></i> Security & HIPAA Policies
                            </h4>

                            <form action="<?= BASE_URL ?>admin/settings_process.php" method="POST" class="mb-5">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="save_security_settings">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Inactivity Timeout (Minutes)</label>
                                        <select class="form-select" name="session_lifetime_minutes">
                                            <option value="15" <?= $sessionLifetime === 15 ? 'selected' : '' ?>>15 Minutes</option>
                                            <option value="30" <?= $sessionLifetime === 30 ? 'selected' : '' ?>>30 Minutes (Recommended)</option>
                                            <option value="60" <?= $sessionLifetime === 60 ? 'selected' : '' ?>>60 Minutes</option>
                                            <option value="120" <?= $sessionLifetime === 120 ? 'selected' : '' ?>>120 Minutes</option>
                                        </select>
                                        <small class="text-muted">User sessions expire automatically after inactivity.</small>
                                    </div>
                                    <div class="col-md-6 mb-3 d-flex align-items-center">
                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox" role="switch" name="require_2fa" id="require2faSwitch" value="1" <?= $require2FA ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="require2faSwitch">Enforce Multi-Factor Authentication (2FA)</label>
                                            <small class="text-muted d-block">Forces all staff members to setup Google Authenticator TOTP.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Security Policies</button>
                                </div>
                            </form>

                            <h4 class="card-custom-title mb-3 border-bottom pb-2 text-danger">
                                <i class="bi bi-key-fill text-danger"></i> Rotate Encryption Key (Cryptographic Operation)
                            </h4>
                            <div class="alert alert-warning small">
                                <strong>Warning:</strong> Rotating keys decrypts all existing medical notes, chief complaints, treatments, and patient history records under the old key, and re-encrypts them under the new key. 
                                Make sure to write down/backup your key!
                            </div>

                            <form action="<?= BASE_URL ?>admin/settings_process.php" method="POST" id="rotateKeyForm">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="rotate_keys">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">New Master Encryption Key</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="text" class="form-control" name="new_encryption_key" id="newKeyField" placeholder="At least 16 secure characters" required>
                                        <button class="btn btn-outline-secondary" type="button" id="generateKeyBtn">Generate Secure Key</button>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" class="btn btn-danger px-4" id="submitKeyBtn"><i class="bi bi-arrow-repeat me-1"></i> Rotate Master Key</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tab: PWA Offline Utility -->
                <div class="tab-pane fade <?= !$isAdmin ? 'show active' : '' ?>" id="tab-pwa" role="tabpanel" aria-labelledby="pwa-tab">
                    <div class="card-custom border-0 shadow-sm p-4">
                        <h4 class="card-custom-title mb-3 border-bottom pb-2">
                            <i class="bi bi-cloud-slash text-primary"></i> PWA Offline Local Cache & Syncer
                        </h4>
                        <p class="text-secondary small">This interface allows you to inspect patient registrations that were saved locally in your browser's IndexedDB database while offline.</p>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-semibold">Status: <span id="networkStatusBadge" class="badge bg-success">Online</span></span>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" id="refreshLocalDbBtn"><i class="bi bi-arrow-clockwise"></i> Refresh List</button>
                                <button class="btn btn-sm btn-outline-danger ms-2" id="purgePWACacheBtn"><i class="bi bi-trash"></i> Force Purge PWA Cache</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom align-middle">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Sex / Age</th>
                                        <th>Purok</th>
                                        <th>Local Timestamp</th>
                                        <th style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="localPatientsBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-secondary">
                                            <i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>
                                            <span class="small">No pending offline patients found in IndexedDB.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($isAdmin): ?>
                    <!-- Tab: Backup & Purge -->
                    <div class="tab-pane fade" id="tab-maintenance" role="tabpanel" aria-labelledby="maintenance-tab">
                        <div class="card-custom border-0 shadow-sm p-4">
                            <h4 class="card-custom-title mb-4 border-bottom pb-2">
                                <i class="bi bi-database text-primary"></i> Backup System Data
                            </h4>
                            <p class="text-secondary small">Create a downloadable copy of the health center database. Export includes all users, patient details, and encrypted medical records.</p>
                            <div class="mb-5 text-start">
                                <a href="<?= BASE_URL ?>admin/backup_process.php" class="btn btn-success px-4"><i class="bi bi-download me-1"></i> Download SQL Backup File</a>
                            </div>

                            <h4 class="card-custom-title mb-4 border-bottom pb-2 text-danger">
                                <i class="bi bi-trash3 text-danger"></i> Prune Old Activity Audit Logs
                            </h4>
                            <p class="text-secondary small">Deletes historical audit logs to save database space. The action is permanent and cannot be undone.</p>
                            
                            <form action="<?= BASE_URL ?>admin/purge_logs_process.php" method="POST" id="purgeForm">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <div class="row align-items-end">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Purge Options</label>
                                        <select class="form-select" name="timeframe" required>
                                            <option value="6_months">Prune logs older than 6 months</option>
                                            <option value="1_year">Prune logs older than 1 year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 text-start">
                                        <button type="button" class="btn btn-danger px-4" id="submitPurgeBtn"><i class="bi bi-shield-x me-1"></i> Prune Audit Logs</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tab: UI Preferences -->
                <div class="tab-pane fade" id="tab-pref" role="tabpanel" aria-labelledby="pref-tab">
                    <div class="card-custom border-0 shadow-sm p-4">
                        <h4 class="card-custom-title mb-4 border-bottom pb-2">
                            <i class="bi bi-palette text-primary"></i> Interface Themes & Accessibility
                        </h4>

                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block">Dark Mode Theme</label>
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" id="themeSwitch">
                                <label class="form-check-label fs-6 text-secondary" for="themeSwitch" id="themeSwitchLabel">Toggle Dark Mode</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block">Text Scaling (Accessibility Size)</label>
                            <div class="btn-group" role="group" aria-label="Text scaling selection">
                                <input type="radio" class="btn-check" name="fontscale" id="font-normal" autocomplete="off" checked>
                                <label class="btn-outline-primary btn px-3" for="font-normal">Normal (14px)</label>

                                <input type="radio" class="btn-check" name="fontscale" id="font-medium" autocomplete="off">
                                <label class="btn-outline-primary btn px-3" for="font-medium">Medium (16px)</label>

                                <input type="radio" class="btn-check" name="fontscale" id="font-large" autocomplete="off">
                                <label class="btn-outline-primary btn px-3" for="font-large">Large (18px)</label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ----------------------------------------------------
    // Preference Tab (Dark Mode & Font Scaling)
    // ----------------------------------------------------
    const themeSwitch = document.getElementById('themeSwitch');
    const themeSwitchLabel = document.getElementById('themeSwitchLabel');
    const fontNormal = document.getElementById('font-normal');
    const fontMedium = document.getElementById('font-medium');
    const fontLarge = document.getElementById('font-large');

    // Load initial switches
    if (document.body.classList.contains('dark-theme')) {
        themeSwitch.checked = true;
        themeSwitchLabel.textContent = 'Dark Mode Active';
    }

    if (document.body.classList.contains('font-md')) {
        fontMedium.checked = true;
    } else if (document.body.classList.contains('font-lg')) {
        fontLarge.checked = true;
    }

    // Toggle theme
    themeSwitch.addEventListener('change', function() {
        if (this.checked) {
            document.body.classList.add('dark-theme');
            localStorage.setItem('theme', 'dark');
            themeSwitchLabel.textContent = 'Dark Mode Active';
        } else {
            document.body.classList.remove('dark-theme');
            localStorage.setItem('theme', 'light');
            themeSwitchLabel.textContent = 'Toggle Dark Mode';
        }
    });

    // Font scaling triggers
    fontNormal.addEventListener('change', function() {
        if (this.checked) {
            document.body.classList.remove('font-md', 'font-lg');
            localStorage.setItem('fontSize', 'normal');
        }
    });
    fontMedium.addEventListener('change', function() {
        if (this.checked) {
            document.body.classList.remove('font-lg');
            document.body.classList.add('font-md');
            localStorage.setItem('fontSize', 'medium');
        }
    });
    fontLarge.addEventListener('change', function() {
        if (this.checked) {
            document.body.classList.remove('font-md');
            document.body.classList.add('font-lg');
            localStorage.setItem('fontSize', 'large');
        }
    });

    // ----------------------------------------------------
    // Administrative Utilities (MFA & Key Rotation & Purging)
    // ----------------------------------------------------
    const generateKeyBtn = document.getElementById('generateKeyBtn');
    const newKeyField = document.getElementById('newKeyField');
    const submitKeyBtn = document.getElementById('submitKeyBtn');
    const rotateKeyForm = document.getElementById('rotateKeyForm');
    const submitPurgeBtn = document.getElementById('submitPurgeBtn');
    const purgeForm = document.getElementById('purgeForm');

    if (generateKeyBtn) {
        generateKeyBtn.addEventListener('click', function() {
            // Generate standard 24 chars random string
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+';
            let key = '';
            for (let i = 0; i < 24; i++) {
                key += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            newKeyField.value = key;
        });
    }

    if (submitKeyBtn) {
        submitKeyBtn.addEventListener('click', function() {
            const keyVal = newKeyField.value.trim();
            if (keyVal.length < 16) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Key',
                    text: 'Master keys must contain at least 16 characters.'
                });
                return;
            }

            Swal.fire({
                title: 'Are you absolutely sure?',
                text: 'Rotating keys decrypts and re-encrypts all patients logs. Do not close this browser or reload the page while key rotation is in progress.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Rotate Encryption Key!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Key Rotation In Progress...',
                        text: 'Decrypting and re-encrypting columns. Please wait.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    rotateKeyForm.submit();
                }
            });
        });
    }

    if (submitPurgeBtn) {
        submitPurgeBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Purge Old Audit Logs?',
                text: 'This operation is permanent. Historical activity logs will be permanently deleted from the database.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Prune Logs!'
            }).then((result) => {
                if (result.isConfirmed) {
                    purgeForm.submit();
                }
            });
        });
    }

    // ----------------------------------------------------
    // PWA Offline Local Database Inspector (IndexedDB)
    // ----------------------------------------------------
    const localPatientsBody = document.getElementById('localPatientsBody');
    const refreshLocalDbBtn = document.getElementById('refreshLocalDbBtn');
    const networkStatusBadge = document.getElementById('networkStatusBadge');
    const purgePWACacheBtn = document.getElementById('purgePWACacheBtn');

    // Update connection status badge
    function updateNetworkStatus() {
        if (navigator.onLine) {
            networkStatusBadge.textContent = 'Online';
            networkStatusBadge.className = 'badge bg-success';
        } else {
            networkStatusBadge.textContent = 'Offline';
            networkStatusBadge.className = 'badge bg-danger';
        }
    }
    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
    updateNetworkStatus();

    // Force clear PWA Cache & unregister SW
    purgePWACacheBtn.addEventListener('click', function() {
        Swal.fire({
            title: 'Purge Cache & Reinstall?',
            text: 'This will unregister the Service Worker and clear browser resource files. This page will reload.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Purge Cache'
        }).then((result) => {
            if (result.isConfirmed) {
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.getRegistrations().then(function(registrations) {
                        for (let registration of registrations) {
                            registration.unregister();
                        }
                    });
                }
                if ('caches' in window) {
                    caches.keys().then(function(names) {
                        for (let name of names) {
                            caches.delete(name);
                        }
                    });
                }
                Swal.fire({
                    title: 'Purged',
                    text: 'Page will now reload to fetch clean assets.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    });

    // Fetch offline registrations from IndexedDB
    function loadIndexedDBOfflineList() {
        if (!('indexedDB' in window)) {
            localPatientsBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-secondary py-3">
                        <i class="bi bi-exclamation-triangle fs-4 text-warning mb-1 d-block"></i>
                        <span class="small">IndexedDB is not supported by your browser.</span>
                    </td>
                </tr>
            `;
            return;
        }

        try {
            const req = indexedDB.open('SinalhanOfflineDB', 1);
            req.onerror = function() {
                localPatientsBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-3">
                            <i class="bi bi-exclamation-triangle fs-4 text-danger mb-1 d-block"></i>
                            <span class="small">Failed to access offline storage.</span>
                        </td>
                    </tr>
                `;
            };

            req.onsuccess = function(e) {
                const db = e.target.result;
                if (!db.objectStoreNames.contains('pending_patients')) {
                    renderEmptyDB();
                    return;
                }

                const tx = db.transaction('pending_patients', 'readonly');
                const store = tx.objectStore('pending_patients');
                const cursorReq = store.openCursor();
                const list = [];

                cursorReq.onsuccess = function(e) {
                    const cursor = e.target.result;
                    if (cursor) {
                        list.push({
                            id: cursor.key,
                            data: cursor.value
                        });
                        cursor.continue();
                    } else {
                        // All records loaded
                        renderOfflinePatients(list);
                    }
                };
            };
        } catch (err) {
            console.error("IndexedDB error in settings inspector:", err);
        }
    }

    function renderEmptyDB() {
        localPatientsBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-secondary">
                    <i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>
                    <span class="small">No pending offline patients found in IndexedDB.</span>
                </td>
            </tr>
        `;
    }

    function calculateAge(birthdateStr) {
        if (!birthdateStr) return 'N/A';
        const birth = new Date(birthdateStr);
        const diff = Date.now() - birth.getTime();
        const ageDate = new Date(diff);
        return Math.abs(ageDate.getUTCFullYear() - 1970);
    }

    function renderOfflinePatients(items) {
        if (items.length === 0) {
            renderEmptyDB();
            return;
        }

        let html = '';
        items.forEach(item => {
            const data = item.data;
            const patientName = `${data.first_name} ${data.last_name}`;
            const sexAge = `${data.sex} / ${calculateAge(data.birthdate)} yrs`;
            const timestamp = data.local_timestamp ? new Date(data.local_timestamp).toLocaleString() : 'Just now';
            const purok = data.purok || 'N/A';

            html += `
                <tr>
                    <td>
                        <strong class="text-dark">${patientName}</strong>
                    </td>
                    <td>${sexAge}</td>
                    <td>${purok}</td>
                    <td class="small text-secondary">${timestamp}</td>
                    <td>
                        <button class="btn btn-sm btn-danger delete-offline-btn" data-key="${item.id}">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });
        localPatientsBody.innerHTML = html;

        // Bind delete buttons
        document.querySelectorAll('.delete-offline-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const key = parseInt(this.dataset.key);
                
                Swal.fire({
                    title: 'Delete offline record?',
                    text: 'This patient record will be removed from your browser storage and cannot be restored.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteOfflineRecord(key);
                    }
                });
            });
        });
    }

    function deleteOfflineRecord(key) {
        const req = indexedDB.open('SinalhanOfflineDB', 1);
        req.onsuccess = function(e) {
            const db = e.target.result;
            const tx = db.transaction('pending_patients', 'readwrite');
            const store = tx.objectStore('pending_patients');
            const deleteReq = store.delete(key);
            
            deleteReq.onsuccess = function() {
                Swal.fire({
                    title: 'Deleted',
                    text: 'Offline patient record deleted locally.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                loadIndexedDBOfflineList();
                // Check if sidebar has warning checkers to update count
                if (typeof checkIndexedDBOffline === 'function') {
                    checkIndexedDBOffline();
                }
            };
        };
    }

    refreshLocalDbBtn.addEventListener('click', function(e) {
        e.preventDefault();
        loadIndexedDBOfflineList();
    });

    // Load list on start
    loadIndexedDBOfflineList();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

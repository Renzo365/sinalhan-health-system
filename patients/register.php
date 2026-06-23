<?php
// patients/register.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw (create-only)
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Register Patient';
$active_menu = 'patients_register';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Register New Patient</h2>
            <p class="text-secondary mb-0">Record demographics and medical profile to create a new health record.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>patients/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Patient List</span>
            </a>
        </div>
    </div>

    <!-- Registration form -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-person-fill-add"></i> Patient Demographics & Profile</h3>
        </div>
        <div class="card-custom-body">
            <form action="<?= BASE_URL ?>patients/register_process.php" method="POST" id="registerPatientForm" class="needs-validation" novalidate>
                <!-- CSRF and configuration tokens -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="allow_duplicate" id="allow_duplicate" value="0">

                <!-- Section 1: Demographics -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-info-circle me-1"></i> Personal Demographic Details</h4>
                <div class="row g-3 mb-4">
                    <!-- First Name -->
                    <div class="col-md-4">
                        <label for="first_name" class="form-label font-weight-bold mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" id="first_name" class="form-control check-dup-trigger" placeholder="e.g. Juan" value="<?= old('first_name') ?>" required autocomplete="off">
                    </div>
                    <!-- Middle Name -->
                    <div class="col-md-3">
                        <label for="middle_name" class="form-label font-weight-bold mb-1">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control" placeholder="e.g. Delgado" value="<?= old('middle_name') ?>" autocomplete="off">
                    </div>
                    <!-- Last Name -->
                    <div class="col-md-3">
                        <label for="last_name" class="form-label font-weight-bold mb-1">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" id="last_name" class="form-control check-dup-trigger" placeholder="e.g. Dela Cruz" value="<?= old('last_name') ?>" required autocomplete="off">
                    </div>
                    <!-- Suffix -->
                    <div class="col-md-2">
                        <label for="suffix" class="form-label font-weight-bold mb-1">Suffix</label>
                        <select name="suffix" id="suffix" class="form-select">
                            <option value="" <?= old_select('suffix', '') ?>>None</option>
                            <option value="Jr." <?= old_select('suffix', 'Jr.') ?>>Jr.</option>
                            <option value="Sr." <?= old_select('suffix', 'Sr.') ?>>Sr.</option>
                            <option value="II" <?= old_select('suffix', 'II') ?>>II</option>
                            <option value="III" <?= old_select('suffix', 'III') ?>>III</option>
                            <option value="IV" <?= old_select('suffix', 'IV') ?>>IV</option>
                        </select>
                    </div>

                    <!-- Birthdate -->
                    <div class="col-md-4">
                        <label for="birthdate" class="form-label font-weight-bold mb-1">Birthdate <span class="text-danger">*</span></label>
                        <input type="date" name="birthdate" id="birthdate" class="form-control check-dup-trigger" value="<?= old('birthdate') ?>" required>
                    </div>
                    <!-- Sex -->
                    <div class="col-md-4">
                        <label for="sex" class="form-label font-weight-bold mb-1">Sex <span class="text-danger">*</span></label>
                        <select name="sex" id="sex" class="form-select" required>
                            <option value="" disabled <?= !isset($_SESSION['old_inputs']['sex']) ? 'selected' : '' ?>>-- Select Sex --</option>
                            <option value="Male" <?= old_select('sex', 'Male') ?>>Male</option>
                            <option value="Female" <?= old_select('sex', 'Female') ?>>Female</option>
                        </select>
                    </div>
                    <!-- Civil Status -->
                    <div class="col-md-4">
                        <label for="civil_status" class="form-label font-weight-bold mb-1">Civil Status</label>
                        <select name="civil_status" id="civil_status" class="form-select">
                            <option value="Single" <?= old_select('civil_status', 'Single', true) ?>>Single</option>
                            <option value="Married" <?= old_select('civil_status', 'Married') ?>>Married</option>
                            <option value="Widowed" <?= old_select('civil_status', 'Widowed') ?>>Widowed</option>
                            <option value="Separated" <?= old_select('civil_status', 'Separated') ?>>Separated</option>
                            <option value="Divorced" <?= old_select('civil_status', 'Divorced') ?>>Divorced</option>
                        </select>
                    </div>
                </div>

                <!-- Section 2: Address and Contact Info -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-telephone me-1"></i> Contact & Residential Details</h4>
                <div class="row g-3 mb-4">
                    <!-- Contact number -->
                    <div class="col-md-4">
                        <label for="contact_number" class="form-label font-weight-bold mb-1">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" class="form-control" placeholder="e.g. 09123456789" value="<?= old('contact_number') ?>">
                        <small class="text-secondary">Philippine mobile format (e.g. 09123456789)</small>
                    </div>
                    <!-- Purok -->
                    <div class="col-md-4">
                        <label for="purok" class="form-label font-weight-bold mb-1">Purok (Barangay Sinalhan) <span class="text-danger">*</span></label>
                        <select name="purok" id="purok" class="form-select" required>
                            <option value="" disabled <?= !isset($_SESSION['old_inputs']['purok']) ? 'selected' : '' ?>>-- Select Purok/Zone --</option>
                            <option value="Purok 1" <?= old_select('purok', 'Purok 1') ?>>Purok 1</option>
                            <option value="Purok 2" <?= old_select('purok', 'Purok 2') ?>>Purok 2</option>
                            <option value="Purok 3" <?= old_select('purok', 'Purok 3') ?>>Purok 3</option>
                            <option value="Purok 4" <?= old_select('purok', 'Purok 4') ?>>Purok 4</option>
                            <option value="Purok 5" <?= old_select('purok', 'Purok 5') ?>>Purok 5</option>
                            <option value="Purok 6" <?= old_select('purok', 'Purok 6') ?>>Purok 6</option>
                            <option value="Purok 7" <?= old_select('purok', 'Purok 7') ?>>Purok 7</option>
                            <option value="Purok 8" <?= old_select('purok', 'Purok 8') ?>>Purok 8</option>
                            <option value="Purok 9" <?= old_select('purok', 'Purok 9') ?>>Purok 9</option>
                            <option value="Purok 10" <?= old_select('purok', 'Purok 10') ?>>Purok 10</option>
                            <option value="Zone 1" <?= old_select('purok', 'Zone 1') ?>>Zone 1</option>
                            <option value="Zone 2" <?= old_select('purok', 'Zone 2') ?>>Zone 2</option>
                            <option value="Zone 3" <?= old_select('purok', 'Zone 3') ?>>Zone 3</option>
                        </select>
                    </div>
                    <!-- Detailed Address -->
                    <div class="col-md-4">
                        <label for="address" class="form-label font-weight-bold mb-1">Detailed Address</label>
                        <input type="text" name="address" id="address" class="form-control" placeholder="House #, Street name" value="<?= old('address') ?>">
                    </div>
                </div>

                <!-- Section 3: Emergency Contacts -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-exclamation-triangle me-1"></i> Emergency Contact Information</h4>
                <div class="row g-3 mb-4">
                    <!-- Emergency Name -->
                    <div class="col-md-6">
                        <label for="emergency_contact_name" class="form-label font-weight-bold mb-1">Emergency Contact Full Name</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" placeholder="Who to contact in emergency" value="<?= old('emergency_contact_name') ?>">
                    </div>
                    <!-- Emergency Number -->
                    <div class="col-md-6">
                        <label for="emergency_contact_number" class="form-label font-weight-bold mb-1">Emergency Contact Number</label>
                        <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" placeholder="Contact number" value="<?= old('emergency_contact_number') ?>">
                    </div>
                </div>

                <!-- Section 4: Medical History -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-heart-pulse me-1"></i> Initial Medical Summary</h4>
                <div class="row g-3 mb-4">
                    <!-- Pre-existing conditions -->
                    <div class="col-md-6">
                        <label for="medical_history" class="form-label font-weight-bold mb-1">Pre-existing Medical History</label>
                        <textarea name="medical_history" id="medical_history" class="form-control" rows="3" placeholder="Hypertension, Asthma, Diabetes, Heart condition..."><?= old('medical_history') ?></textarea>
                    </div>
                    <!-- Allergies -->
                    <div class="col-md-6">
                        <label for="allergies" class="form-label font-weight-bold mb-1">Known Allergies</label>
                        <textarea name="allergies" id="allergies" class="form-control" rows="3" placeholder="Penicillin, Seafoods, Dust, Latex..."><?= old('allergies') ?></textarea>
                    </div>
                </div>

                <hr class="my-4 border-color">

                <!-- Submit controls -->
                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= BASE_URL ?>patients/list.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Patient Record</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Redirect immediately if loaded while offline
    if (!navigator.onLine) {
        window.location.replace('register_offline.php');
        return;
    }

    // IndexedDB constants matching register_offline.php
    const DB_NAME = 'SinalhanOfflineDB';
    const DB_VERSION = 1;
    const STORE_NAME = 'pending_patients';
    let db = null;

    // Open/create the client-side IndexedDB database inside the browser
    const dbRequest = indexedDB.open(DB_NAME, DB_VERSION);
    dbRequest.onerror = function(event) {
        console.error('IndexedDB open error:', event.target.errorCode);
    };
    dbRequest.onsuccess = function(event) {
        db = event.target.result;
    };
    dbRequest.onupgradeneeded = function(event) {
        const upgradeDb = event.target.result;
        if (!upgradeDb.objectStoreNames.contains(STORE_NAME)) {
            upgradeDb.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        }
    };

    const userRole = '<?= $_SESSION['role'] ?? '' ?>';
    const form = document.getElementById('registerPatientForm');
    const dupTriggers = document.querySelectorAll('.check-dup-trigger');

    // 1. AJAX duplicate check triggers on blur
    dupTriggers.forEach(element => {
        element.addEventListener('input', function() {
            document.getElementById('allow_duplicate').value = '0';
        });
        element.addEventListener('blur', function() {
            runDuplicateCheck();
        });
    });

    let duplicateCheckedName = '';
    let duplicateCheckedDate = '';

    function runDuplicateCheck() {
        const first = document.getElementById('first_name').value.trim();
        const last = document.getElementById('last_name').value.trim();
        const bdate = document.getElementById('birthdate').value;

        // Run check only if name and birthdate are completed
        if (first.length >= 2 && last.length >= 2 && bdate !== '') {
            const checkName = first + ' ' + last;
            // Prevent duplicate checking multiple times for the same entries
            if (duplicateCheckedName === checkName && duplicateCheckedDate === bdate) {
                return;
            }

            $.ajax({
                url: '../ajax/check_duplicate.php',
                method: 'GET',
                data: {
                    first_name: first,
                    last_name: last,
                    birthdate: bdate
                },
                dataType: 'json',
                success: function(response) {
                    duplicateCheckedName = checkName;
                    duplicateCheckedDate = bdate;

                    if (response.hasDuplicate && response.matches.length > 0) {
                        // Find if there is an exact birthdate match
                        const exactMatch = response.matches.find(m => m.birthdate === bdate);
                        
                        let title = 'Possible Duplicate Detected!';
                        let htmlContent = '';
                        let confirmText = 'Yes, register anyway';
                        let cancelText = 'No, cancel';
                        let isArchivedMatch = false;
                        
                        if (exactMatch) {
                            if (parseInt(exactMatch.is_archived) === 1) {
                                isArchivedMatch = true;
                                title = 'Archived Patient Profile Found!';
                                if (userRole === 'admin') {
                                    htmlContent = `An archived patient named <strong>${exactMatch.first_name} ${exactMatch.last_name}</strong> with the exact same birthdate (<strong>${exactMatch.birthdate}</strong>) already exists.<br><br>
                                                   Purok: <strong>${exactMatch.purok || 'N/A'}</strong> | Sex: <strong>${exactMatch.sex}</strong> | Contact: <strong>${exactMatch.contact_number || 'N/A'}</strong><br><br>
                                                   Would you like to restore this patient's profile instead of creating a new duplicate record?`;
                                    confirmText = 'Restore Existing Profile';
                                    cancelText = 'Cancel';
                                } else {
                                    htmlContent = `An archived patient named <strong>${exactMatch.first_name} ${exactMatch.last_name}</strong> with the exact same birthdate (<strong>${exactMatch.birthdate}</strong>) already exists.<br><br>
                                                   Purok: <strong>${exactMatch.purok || 'N/A'}</strong> | Sex: <strong>${exactMatch.sex}</strong><br><br>
                                                   Please contact an administrator to restore this original profile rather than creating a duplicate.`;
                                    confirmText = ''; // Hide confirm button for non-admins
                                    cancelText = 'Close';
                                }
                            } else {
                                title = 'Duplicate Patient Profile Found!';
                                htmlContent = `A patient named <strong>${exactMatch.first_name} ${exactMatch.last_name}</strong> with the exact same birthdate (<strong>${exactMatch.birthdate}</strong>) is already registered.<br><br>
                                               Purok: <strong>${exactMatch.purok || 'N/A'}</strong> | Sex: <strong>${exactMatch.sex}</strong> | Contact: <strong>${exactMatch.contact_number || 'N/A'}</strong><br><br>
                                               Registering duplicates causes errors in patient medical histories. Are you sure this is a different patient?`;
                                confirmText = 'Yes, register new';
                            }
                        } else {
                            // Name match but different birthdate
                            const match = response.matches[0];
                            title = 'Potential Duplicate Patient!';
                            htmlContent = `A patient named <strong>${match.first_name} ${match.last_name}</strong> is already registered with a birthdate of <strong>${match.birthdate}</strong>.<br><br>
                                           Purok: <strong>${match.purok || 'N/A'}</strong> | Sex: <strong>${match.sex}</strong> | Contact: <strong>${match.contact_number || 'N/A'}</strong><br><br>
                                           If this is the same patient and the birthdate entered was a typo, please cancel. Otherwise, you can proceed.`;
                            confirmText = 'Yes, register anyway';
                        }

                        const swalConfig = {
                            title: title,
                            html: htmlContent,
                            icon: 'warning',
                            showCancelButton: true,
                            cancelButtonColor: '#0D7377',
                            cancelButtonText: cancelText
                        };

                        if (confirmText) {
                            swalConfig.showConfirmButton = true;
                            swalConfig.confirmButtonColor = isArchivedMatch ? '#2ecc71' : '#e74c3c';
                            swalConfig.confirmButtonText = confirmText;
                        } else {
                            swalConfig.showConfirmButton = false;
                        }

                        Swal.fire(swalConfig).then((result) => {
                            if (result.isConfirmed) {
                                if (isArchivedMatch && userRole === 'admin') {
                                    // Submit restoration POST form dynamically
                                    const restoreForm = document.createElement('form');
                                    restoreForm.method = 'POST';
                                    restoreForm.action = '../admin/archive_process.php';

                                    const fields = {
                                        action: 'restore',
                                        type: 'patient',
                                        record_id: exactMatch.patient_id,
                                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                                    };

                                    for (const key in fields) {
                                        const input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = key;
                                        input.value = fields[key];
                                        restoreForm.appendChild(input);
                                    }

                                    document.body.appendChild(restoreForm);
                                    restoreForm.submit();
                                } else {
                                    document.getElementById('allow_duplicate').value = '1';
                                }
                            } else {
                                // Clear input or navigate to search
                                document.getElementById('first_name').value = '';
                                document.getElementById('last_name').value = '';
                                document.getElementById('birthdate').value = '';
                                document.getElementById('allow_duplicate').value = '0';
                                duplicateCheckedName = '';
                                duplicateCheckedDate = '';
                            }
                        });
                    } else {
                        document.getElementById('allow_duplicate').value = '0';
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Duplicate check ajax failed: ", error);
                }
            });
        }
    }

    function saveOfflinePatient() {
        const first = document.getElementById('first_name').value.trim();
        const last = document.getElementById('last_name').value.trim();
        const bdateVal = document.getElementById('birthdate').value;
        const purok = document.getElementById('purok').value;
        const contact = document.getElementById('contact_number').value.trim();
        const emergencyContact = document.getElementById('emergency_contact_number').value.trim();

        if (!first || !last || !bdateVal || !purok) {
            Swal.fire({
                icon: 'warning',
                title: 'Required Fields',
                text: 'Please complete all required demographic fields.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        const patientPayload = {
            first_name: first,
            middle_name: document.getElementById('middle_name').value.trim(),
            last_name: last,
            suffix: document.getElementById('suffix').value,
            birthdate: bdateVal,
            sex: document.getElementById('sex').value,
            civil_status: document.getElementById('civil_status').value,
            contact_number: contact,
            purok: purok,
            address: document.getElementById('address').value.trim(),
            emergency_contact_name: document.getElementById('emergency_contact_name').value.trim(),
            emergency_contact_number: emergencyContact,
            medical_history: document.getElementById('medical_history').value.trim(),
            allergies: document.getElementById('allergies').value.trim(),
            timestamp: new Date().toISOString()
        };

        if (!db) {
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: 'Local storage database is not initialized yet. Please try again.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const addRequest = store.add(patientPayload);

        addRequest.onsuccess = function() {
            Swal.fire({
                icon: 'success',
                title: 'Saved Locally (Offline Mode)',
                text: 'Your device is offline. The record has been saved locally on this device. You can synchronize it when connection is restored.',
                confirmButtonColor: '#0D7377'
            }).then(() => {
                form.reset();
                window.location.href = 'list.php';
            });
        };

        addRequest.onerror = function() {
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: 'An error occurred while saving the record to local database.',
                confirmButtonColor: '#0D7377'
            });
        };
    }

    // 2. Validate phone number and birthdate on submit
    form.addEventListener('submit', function(e) {
        const bdate = new Date(document.getElementById('birthdate').value);
        const contact = document.getElementById('contact_number').value.trim();
        const emergencyContact = document.getElementById('emergency_contact_number').value.trim();
        
        // Birthdate not in future check
        if (bdate > new Date()) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Birthdate',
                text: 'The patient birthdate cannot be in the future.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // Contact format check (if provided)
        if (contact && !/^(09\d{9}|(\+639)\d{9})$/.test(contact)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Contact Number',
                text: 'Please enter a valid Philippine mobile number format (e.g., 09123456789).',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // Emergency contact format check (if provided)
        if (emergencyContact && !/^(09\d{9}|(\+639)\d{9})$/.test(emergencyContact)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Emergency Contact',
                text: 'Please enter a valid Philippine mobile number format for the emergency contact.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // Check if offline and save to IndexedDB as fallback
        if (!navigator.onLine) {
            e.preventDefault();
            saveOfflinePatient();
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

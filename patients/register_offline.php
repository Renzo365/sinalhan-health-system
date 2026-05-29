<?php
// patients/register_offline.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw (create-only)
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Register Patient (Offline)';
$active_menu = 'patients_register';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
        <div>
            <h2 class="page-title"><i class="bi bi-wifi-off text-danger"></i> Register Patient (Offline Mode)</h2>
            <p class="text-secondary mb-0">Record demographics locally. The system will hold records in your browser until connection is restored.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>patients/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Patient List</span>
            </a>
        </div>
    </div>

    <!-- Sync Alert Indicator -->
    <div id="sync-indicator" class="alert alert-info d-none align-items-center justify-content-between shadow-sm rounded-3 py-3 px-4 mb-4" role="alert">
        <div class="d-flex align-items-center gap-3">
            <div class="spinner-grow text-info spinner-grow-sm" role="status" style="animation-duration: 1.5s;"></div>
            <div>
                <strong id="sync-count-label">You have 0 pending offline registrations.</strong>
                <span class="text-secondary small d-block">These will be saved to the central cloud database when you click sync.</span>
            </div>
        </div>
        <button onclick="syncOfflineData()" id="sync-btn" class="btn btn-teal px-4 py-2 d-flex align-items-center gap-2">
            <i class="bi bi-cloud-upload-fill"></i>
            <span>Upload & Sync Online</span>
        </button>
    </div>

    <!-- Registration form -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-person-badge"></i> Offline Patient Form</h3>
        </div>
        <div class="card-custom-body">
            <form id="offlinePatientForm" class="needs-validation" novalidate>
                <!-- Section 1: Demographics -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-info-circle me-1"></i> Personal Demographic Details</h4>
                <div class="row g-3 mb-4">
                    <!-- First Name -->
                    <div class="col-md-4">
                        <label for="first_name" class="form-label font-weight-bold mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" id="first_name" class="form-control" placeholder="e.g. Juan" required autocomplete="off">
                    </div>
                    <!-- Middle Name -->
                    <div class="col-md-3">
                        <label for="middle_name" class="form-label font-weight-bold mb-1">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control" placeholder="e.g. Delgado" autocomplete="off">
                    </div>
                    <!-- Last Name -->
                    <div class="col-md-3">
                        <label for="last_name" class="form-label font-weight-bold mb-1">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" id="last_name" class="form-control" placeholder="e.g. Dela Cruz" required autocomplete="off">
                    </div>
                    <!-- Suffix -->
                    <div class="col-md-2">
                        <label for="suffix" class="form-label font-weight-bold mb-1">Suffix</label>
                        <select name="suffix" id="suffix" class="form-select">
                            <option value="" selected>None</option>
                            <option value="Jr.">Jr.</option>
                            <option value="Sr.">Sr.</option>
                            <option value="II">II</option>
                            <option value="III">III</option>
                            <option value="IV">IV</option>
                        </select>
                    </div>

                    <!-- Birthdate -->
                    <div class="col-md-4">
                        <label for="birthdate" class="form-label font-weight-bold mb-1">Birthdate <span class="text-danger">*</span></label>
                        <input type="date" name="birthdate" id="birthdate" class="form-control" required>
                    </div>
                    <!-- Sex -->
                    <div class="col-md-4">
                        <label for="sex" class="form-label font-weight-bold mb-1">Sex <span class="text-danger">*</span></label>
                        <select name="sex" id="sex" class="form-select" required>
                            <option value="" disabled selected>-- Select Sex --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <!-- Civil Status -->
                    <div class="col-md-4">
                        <label for="civil_status" class="form-label font-weight-bold mb-1">Civil Status</label>
                        <select name="civil_status" id="civil_status" class="form-select">
                            <option value="Single" selected>Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Divorced">Divorced</option>
                        </select>
                    </div>
                </div>

                <!-- Section 2: Address and Contact Info -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-telephone me-1"></i> Contact & Residential Details</h4>
                <div class="row g-3 mb-4">
                    <!-- Contact number -->
                    <div class="col-md-4">
                        <label for="contact_number" class="form-label font-weight-bold mb-1">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" class="form-control" placeholder="e.g. 09123456789">
                        <small class="text-secondary">Philippine mobile format (e.g. 09123456789)</small>
                    </div>
                    <!-- Purok -->
                    <div class="col-md-4">
                        <label for="purok" class="form-label font-weight-bold mb-1">Purok (Barangay Sinalhan) <span class="text-danger">*</span></label>
                        <select name="purok" id="purok" class="form-select" required>
                            <option value="" disabled selected>-- Select Purok/Zone --</option>
                            <option value="Purok 1">Purok 1</option>
                            <option value="Purok 2">Purok 2</option>
                            <option value="Purok 3">Purok 3</option>
                            <option value="Purok 4">Purok 4</option>
                            <option value="Purok 5">Purok 5</option>
                            <option value="Purok 6">Purok 6</option>
                            <option value="Purok 7">Purok 7</option>
                            <option value="Purok 8">Purok 8</option>
                            <option value="Purok 9">Purok 9</option>
                            <option value="Purok 10">Purok 10</option>
                            <option value="Zone 1">Zone 1</option>
                            <option value="Zone 2">Zone 2</option>
                            <option value="Zone 3">Zone 3</option>
                        </select>
                    </div>
                    <!-- Detailed Address -->
                    <div class="col-md-4">
                        <label for="address" class="form-label font-weight-bold mb-1">Detailed Address</label>
                        <input type="text" name="address" id="address" class="form-control" placeholder="House #, Street name">
                    </div>
                </div>

                <!-- Section 3: Emergency Contacts -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-exclamation-triangle me-1"></i> Emergency Contact Information</h4>
                <div class="row g-3 mb-4">
                    <!-- Emergency Name -->
                    <div class="col-md-6">
                        <label for="emergency_contact_name" class="form-label font-weight-bold mb-1">Emergency Contact Full Name</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" placeholder="Who to contact in emergency">
                    </div>
                    <!-- Emergency Number -->
                    <div class="col-md-6">
                        <label for="emergency_contact_number" class="form-label font-weight-bold mb-1">Emergency Contact Number</label>
                        <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" placeholder="Contact number">
                    </div>
                </div>

                <!-- Section 4: Medical History -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-heart-pulse me-1"></i> Initial Medical Summary</h4>
                <div class="row g-3 mb-4">
                    <!-- Pre-existing conditions -->
                    <div class="col-md-6">
                        <label for="medical_history" class="form-label font-weight-bold mb-1">Pre-existing Medical History</label>
                        <textarea name="medical_history" id="medical_history" class="form-control" rows="3" placeholder="Hypertension, Asthma, Diabetes, Heart condition..."></textarea>
                    </div>
                    <!-- Allergies -->
                    <div class="col-md-6">
                        <label for="allergies" class="form-label font-weight-bold mb-1">Known Allergies</label>
                        <textarea name="allergies" id="allergies" class="form-control" rows="3" placeholder="Penicillin, Seafoods, Dust, Latex..."></textarea>
                    </div>
                </div>

                <hr class="my-4 border-color">

                <!-- Submit controls -->
                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= BASE_URL ?>patients/list.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Record Locally</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// 1. IndexedDB Initialization
const DB_NAME = 'sinalhan_offline_db';
const DB_VERSION = 1;
const STORE_NAME = 'patients';
let db = null;

const request = indexedDB.open(DB_NAME, DB_VERSION);

request.onerror = function(event) {
    console.error('IndexedDB open error:', event.target.errorCode);
};

request.onsuccess = function(event) {
    db = event.target.result;
    updatePendingBadge();
};

request.onupgradeneeded = function(event) {
    const upgradeDb = event.target.result;
    if (!upgradeDb.objectStoreNames.contains(STORE_NAME)) {
        upgradeDb.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        console.log('IndexedDB store created successfully.');
    }
};

// 2. Fetch Pending Records and update indicator banner
function updatePendingBadge() {
    if (!db) return;
    const transaction = db.transaction([STORE_NAME], 'readonly');
    const store = transaction.objectStore(STORE_NAME);
    const countRequest = store.count();

    countRequest.onsuccess = function() {
        const count = countRequest.result;
        const indicator = document.getElementById('sync-indicator');
        if (count > 0) {
            indicator.classList.remove('d-none');
            indicator.classList.add('d-flex');
            document.getElementById('sync-count-label').textContent = `You have ${count} pending offline patient registration(s) ready to sync.`;
            document.getElementById('sync-btn').disabled = !navigator.onLine;
        } else {
            indicator.classList.add('d-none');
            indicator.classList.remove('d-flex');
        }
    };
}

// Listen to online status changes
window.addEventListener('online', function() {
    document.getElementById('sync-btn').disabled = false;
});
window.addEventListener('offline', function() {
    document.getElementById('sync-btn').disabled = true;
});

// 3. Save Form Data to IndexedDB
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('offlinePatientForm');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Standard validation
        const first = document.getElementById('first_name').value.trim();
        const last = document.getElementById('last_name').value.trim();
        const bdateVal = document.getElementById('birthdate').value;
        const bdate = new Date(bdateVal);
        const contact = document.getElementById('contact_number').value.trim();
        const emergencyContact = document.getElementById('emergency_contact_number').value.trim();
        const purok = document.getElementById('purok').value;

        if (!first || !last || !bdateVal || !purok) {
            Swal.fire({
                icon: 'warning',
                title: 'Required Fields',
                text: 'Please complete all required demographic fields.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        if (bdate > new Date()) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Birthdate',
                text: 'The patient birthdate cannot be in the future.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        if (contact && !/^(09\d{9}|(\+639)\d{9})$/.test(contact)) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Contact Number',
                text: 'Please enter a valid Philippine mobile number format (e.g., 09123456789).',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        if (emergencyContact && !/^(09\d{9}|(\+639)\d{9})$/.test(emergencyContact)) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Emergency Contact',
                text: 'Please enter a valid Philippine mobile number format for the emergency contact.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // Build patient payload
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

        // Insert into IndexedDB
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const addRequest = store.add(patientPayload);

        addRequest.onsuccess = function() {
            Swal.fire({
                icon: 'success',
                title: 'Saved Locally',
                text: 'The record was saved successfully to your device. It will be uploaded once you sync online.',
                confirmButtonColor: '#0D7377'
            }).then(() => {
                form.reset();
                updatePendingBadge();
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
    });
});

// 4. Synchronize Offline Data Online
function syncOfflineData() {
    if (!navigator.onLine) {
        Swal.fire({
            icon: 'warning',
            title: 'Offline',
            text: 'You are currently offline. Please reconnect to the network before syncing.',
            confirmButtonColor: '#0D7377'
        });
        return;
    }

    Swal.fire({
        title: 'Synchronize Data?',
        text: 'This will upload all locally stored patient registrations to the server.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D7377',
        cancelButtonColor: '#e74c3c',
        confirmButtonText: 'Start Synchronization'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Syncing...',
                text: 'Uploading patient records to Sinalhan HC servers...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fetch and upload records
            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const getAllRequest = store.getAll();

            getAllRequest.onsuccess = function() {
                const records = getAllRequest.result;
                if (records.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sync Complete',
                        text: 'No pending records to sync.',
                        confirmButtonColor: '#0D7377'
                    });
                    return;
                }

                uploadBatch(records, 0);
            };
        }
    });
}

function uploadBatch(records, index) {
    if (index >= records.length) {
        // Complete! Clear IndexedDB
        const clearTransaction = db.transaction([STORE_NAME], 'readwrite');
        const clearStore = clearTransaction.objectStore(STORE_NAME);
        const clearRequest = clearStore.clear();

        clearRequest.onsuccess = function() {
            Swal.fire({
                icon: 'success',
                title: 'Sync Successful!',
                text: `Successfully uploaded ${records.length} patient record(s) to the central database.`,
                confirmButtonColor: '#0D7377'
            }).then(() => {
                updatePendingBadge();
            });
        };
        return;
    }

    const rec = records[index];
    // POST request using jQuery
    $.ajax({
        url: 'register_process.php',
        method: 'POST',
        data: {
            csrf_token: '<?= $_SESSION['csrf_token'] ?>', // Inject current session CSRF
            first_name: rec.first_name,
            middle_name: rec.middle_name,
            last_name: rec.last_name,
            suffix: rec.suffix,
            birthdate: rec.birthdate,
            sex: rec.sex,
            civil_status: rec.civil_status,
            contact_number: rec.contact_number,
            purok: rec.purok,
            address: rec.address,
            emergency_contact_name: rec.emergency_contact_name,
            emergency_contact_number: rec.emergency_contact_number,
            medical_history: rec.medical_history,
            allergies: rec.allergies,
            offline_sync: 1 // Flag specifying sync upload
        },
        success: function(response) {
            // Proceed to next item in batch
            uploadBatch(records, index + 1);
        },
        error: function(xhr, status, error) {
            console.error(`Sync error on record index ${index}:`, error);
            Swal.fire({
                icon: 'error',
                title: 'Sync Interrupted',
                text: `Failed to sync record for ${rec.first_name} ${rec.last_name}. Error: ${error}. Sync paused.`,
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

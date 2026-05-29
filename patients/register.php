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

                <!-- Section 1: Demographics -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-info-circle me-1"></i> Personal Demographic Details</h4>
                <div class="row g-3 mb-4">
                    <!-- First Name -->
                    <div class="col-md-4">
                        <label for="first_name" class="form-label font-weight-bold mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" id="first_name" class="form-control check-dup-trigger" placeholder="e.g. Juan" required autocomplete="off">
                    </div>
                    <!-- Middle Name -->
                    <div class="col-md-3">
                        <label for="middle_name" class="form-label font-weight-bold mb-1">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control" placeholder="e.g. Delgado" autocomplete="off">
                    </div>
                    <!-- Last Name -->
                    <div class="col-md-3">
                        <label for="last_name" class="form-label font-weight-bold mb-1">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" id="last_name" class="form-control check-dup-trigger" placeholder="e.g. Dela Cruz" required autocomplete="off">
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
                        <input type="date" name="birthdate" id="birthdate" class="form-control check-dup-trigger" required>
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
                    <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Patient Record</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerPatientForm');
    const dupTriggers = document.querySelectorAll('.check-dup-trigger');

    // 1. AJAX duplicate check triggers on blur
    dupTriggers.forEach(element => {
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
                        const m = response.matches[0];
                        Swal.fire({
                            title: 'Possible Duplicate Detected!',
                            html: `A patient with the name <strong>${m.first_name} ${m.last_name}</strong>, born on <strong>${m.birthdate}</strong> is already registered.<br><br>
                                   Purok: <strong>${m.purok || 'N/A'}</strong> | Sex: <strong>${m.sex}</strong><br><br>
                                   Would you like to continue registering a new patient with this name anyway?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#e74c3c',
                            cancelButtonColor: '#0D7377',
                            confirmButtonText: 'Yes, register new',
                            cancelButtonText: 'No, cancel'
                        }).then((result) => {
                            if (!result.isConfirmed) {
                                // Clear input or navigate to search
                                document.getElementById('first_name').value = '';
                                document.getElementById('last_name').value = '';
                                document.getElementById('birthdate').value = '';
                                duplicateCheckedName = '';
                                duplicateCheckedDate = '';
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Duplicate check ajax failed: ", error);
                }
            });
        }
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
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
// patients/edit.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Edit Patient Profile';
$active_menu = 'patients';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/encryption.php';
$pdo = Database::getInstance()->getConnection();

$patientId = (int)($_GET['id'] ?? 0);

if (!$patientId) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Missing ID',
        'message' => 'Please select a valid patient to edit.'
    ];
    header('Location: ' . BASE_URL . 'patients/list.php');
    exit;
}

try {
    // Select patient records
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ? AND is_archived = 0");
    $stmt->execute([$patientId]);
        $p = $stmt->fetch();

        if (!$p) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Patient Not Found',
                'message' => 'The patient profile does not exist or has been archived.'
            ];
            header('Location: ' . BASE_URL . 'patients/list.php');
            exit;
        }

        // Decrypt encrypted fields (HIPAA Compliance)
        $p['medical_history'] = decrypt_data($p['medical_history'] ?? '');
        $p['allergies'] = decrypt_data($p['allergies'] ?? '');
    } catch (Exception $e) {
    error_log("Patient edit load failed: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'Failed to load patient records.'
    ];
    header('Location: ' . BASE_URL . 'patients/list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Modify Patient Profile</h2>
            <p class="text-secondary mb-0">Update residential, contact, or clinical variables for <strong><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></strong>.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>patients/view.php?id=<?= $p['patient_id'] ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    <!-- Edit Form Card -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-person-fill-gear"></i> Update Patient File Settings</h3>
        </div>
        <div class="card-custom-body">
            <form action="<?= BASE_URL ?>patients/edit_process.php" method="POST" id="editPatientForm" class="needs-validation" novalidate>
                <!-- Form tokens -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="patient_id" value="<?= $p['patient_id'] ?>">

                <!-- Section 1: Demographics -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-info-circle me-1"></i> Personal Demographic Details</h4>
                <div class="row g-3 mb-4">
                    <!-- First Name -->
                    <div class="col-md-4">
                        <label for="first_name" class="form-label font-weight-bold mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" id="first_name" class="form-control" value="<?= old('first_name', $p['first_name']) ?>" required autocomplete="off">
                    </div>
                    <!-- Middle Name -->
                    <div class="col-md-3">
                        <label for="middle_name" class="form-label font-weight-bold mb-1">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control" value="<?= old('middle_name', $p['middle_name'] ?? '') ?>" autocomplete="off">
                    </div>
                    <!-- Last Name -->
                    <div class="col-md-3">
                        <label for="last_name" class="form-label font-weight-bold mb-1">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" id="last_name" class="form-control" value="<?= old('last_name', $p['last_name']) ?>" required autocomplete="off">
                    </div>
                    <!-- Suffix -->
                    <div class="col-md-2">
                        <label for="suffix" class="form-label font-weight-bold mb-1">Suffix</label>
                        <select name="suffix" id="suffix" class="form-select">
                            <option value="" <?= old_select('suffix', '', empty($p['suffix'])) ?>>None</option>
                            <option value="Jr." <?= old_select('suffix', 'Jr.', $p['suffix'] === 'Jr.') ?>>Jr.</option>
                            <option value="Sr." <?= old_select('suffix', 'Sr.', $p['suffix'] === 'Sr.') ?>>Sr.</option>
                            <option value="II" <?= old_select('suffix', 'II', $p['suffix'] === 'II') ?>>II</option>
                            <option value="III" <?= old_select('suffix', 'III', $p['suffix'] === 'III') ?>>III</option>
                            <option value="IV" <?= old_select('suffix', 'IV', $p['suffix'] === 'IV') ?>>IV</option>
                        </select>
                    </div>

                    <!-- Birthdate -->
                    <div class="col-md-4">
                        <label for="birthdate" class="form-label font-weight-bold mb-1">Birthdate <span class="text-danger">*</span></label>
                        <input type="date" name="birthdate" id="birthdate" class="form-control" value="<?= old('birthdate', $p['birthdate']) ?>" required>
                    </div>
                    <!-- Sex -->
                    <div class="col-md-4">
                        <label for="sex" class="form-label font-weight-bold mb-1">Sex <span class="text-danger">*</span></label>
                        <select name="sex" id="sex" class="form-select" required>
                            <option value="Male" <?= old_select('sex', 'Male', $p['sex'] === 'Male') ?>>Male</option>
                            <option value="Female" <?= old_select('sex', 'Female', $p['sex'] === 'Female') ?>>Female</option>
                        </select>
                    </div>
                    <!-- Civil Status -->
                    <div class="col-md-4">
                        <label for="civil_status" class="form-label font-weight-bold mb-1">Civil Status</label>
                        <select name="civil_status" id="civil_status" class="form-select">
                            <option value="Single" <?= old_select('civil_status', 'Single', $p['civil_status'] === 'Single') ?>>Single</option>
                            <option value="Married" <?= old_select('civil_status', 'Married', $p['civil_status'] === 'Married') ?>>Married</option>
                            <option value="Widowed" <?= old_select('civil_status', 'Widowed', $p['civil_status'] === 'Widowed') ?>>Widowed</option>
                            <option value="Separated" <?= old_select('civil_status', 'Separated', $p['civil_status'] === 'Separated') ?>>Separated</option>
                            <option value="Divorced" <?= old_select('civil_status', 'Divorced', $p['civil_status'] === 'Divorced') ?>>Divorced</option>
                        </select>
                    </div>
                </div>

                <!-- Section 2: Address and Contact Info -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-telephone me-1"></i> Contact & Residential Details</h4>
                <div class="row g-3 mb-4">
                    <!-- Contact number -->
                    <div class="col-md-4">
                        <label for="contact_number" class="form-label font-weight-bold mb-1">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" class="form-control" value="<?= old('contact_number', $p['contact_number'] ?? '') ?>" placeholder="e.g. 09123456789">
                        <small class="text-secondary">Philippine mobile format (e.g. 09123456789)</small>
                    </div>
                    <!-- Purok -->
                    <div class="col-md-4">
                        <label for="purok" class="form-label font-weight-bold mb-1">Purok (Barangay Sinalhan) <span class="text-danger">*</span></label>
                        <select name="purok" id="purok" class="form-select" required>
                            <option value="Purok 1" <?= old_select('purok', 'Purok 1', $p['purok'] === 'Purok 1') ?>>Purok 1</option>
                            <option value="Purok 2" <?= old_select('purok', 'Purok 2', $p['purok'] === 'Purok 2') ?>>Purok 2</option>
                            <option value="Purok 3" <?= old_select('purok', 'Purok 3', $p['purok'] === 'Purok 3') ?>>Purok 3</option>
                            <option value="Purok 4" <?= old_select('purok', 'Purok 4', $p['purok'] === 'Purok 4') ?>>Purok 4</option>
                            <option value="Purok 5" <?= old_select('purok', 'Purok 5', $p['purok'] === 'Purok 5') ?>>Purok 5</option>
                            <option value="Purok 6" <?= old_select('purok', 'Purok 6', $p['purok'] === 'Purok 6') ?>>Purok 6</option>
                            <option value="Purok 7" <?= old_select('purok', 'Purok 7', $p['purok'] === 'Purok 7') ?>>Purok 7</option>
                            <option value="Purok 8" <?= old_select('purok', 'Purok 8', $p['purok'] === 'Purok 8') ?>>Purok 8</option>
                            <option value="Purok 9" <?= old_select('purok', 'Purok 9', $p['purok'] === 'Purok 9') ?>>Purok 9</option>
                            <option value="Purok 10" <?= old_select('purok', 'Purok 10', $p['purok'] === 'Purok 10') ?>>Purok 10</option>
                            <option value="Zone 1" <?= old_select('purok', 'Zone 1', $p['purok'] === 'Zone 1') ?>>Zone 1</option>
                            <option value="Zone 2" <?= old_select('purok', 'Zone 2', $p['purok'] === 'Zone 2') ?>>Zone 2</option>
                            <option value="Zone 3" <?= old_select('purok', 'Zone 3', $p['purok'] === 'Zone 3') ?>>Zone 3</option>
                        </select>
                    </div>
                    <!-- Detailed Address -->
                    <div class="col-md-4">
                        <label for="address" class="form-label font-weight-bold mb-1">Detailed Address</label>
                        <input type="text" name="address" id="address" class="form-control" value="<?= old('address', $p['address'] ?? '') ?>" placeholder="House #, Street name">
                    </div>
                </div>

                <!-- Section 3: Emergency Contacts -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-exclamation-triangle me-1"></i> Emergency Contact Information</h4>
                <div class="row g-3 mb-4">
                    <!-- Emergency Name -->
                    <div class="col-md-6">
                        <label for="emergency_contact_name" class="form-label font-weight-bold mb-1">Emergency Contact Full Name</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" value="<?= old('emergency_contact_name', $p['emergency_contact_name'] ?? '') ?>" placeholder="Who to contact in emergency">
                    </div>
                    <!-- Emergency Number -->
                    <div class="col-md-6">
                        <label for="emergency_contact_number" class="form-label font-weight-bold mb-1">Emergency Contact Number</label>
                        <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" value="<?= old('emergency_contact_number', $p['emergency_contact_number'] ?? '') ?>" placeholder="Contact number">
                    </div>
                </div>

                <!-- Section 4: Medical History -->
                <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary"><i class="bi bi-heart-pulse me-1"></i> Initial Medical Summary</h4>
                <div class="row g-3 mb-4">
                    <!-- Pre-existing conditions -->
                    <div class="col-md-6">
                        <label for="medical_history" class="form-label font-weight-bold mb-1">Pre-existing Medical History</label>
                        <textarea name="medical_history" id="medical_history" class="form-control" rows="3" placeholder="Hypertension, Asthma, Diabetes..."><?= old('medical_history', $p['medical_history'] ?? '') ?></textarea>
                    </div>
                    <!-- Allergies -->
                    <div class="col-md-6">
                        <label for="allergies" class="form-label font-weight-bold mb-1">Known Allergies</label>
                        <textarea name="allergies" id="allergies" class="form-control" rows="3" placeholder="Penicillin, Seafoods, Dust..."><?= old('allergies', $p['allergies'] ?? '') ?></textarea>
                    </div>
                </div>

                <hr class="my-4 border-color">

                <!-- Submit controls -->
                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= BASE_URL ?>patients/view.php?id=<?= $p['patient_id'] ?>" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Update Patient Record</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editPatientForm');

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

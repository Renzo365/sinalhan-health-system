<?php
// health_records/add.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff (BHW has view-only access)
require_role(['admin', 'staff']);

$page_title = 'New Consultation';
$active_menu = 'health_records';

// Load Select2 autocomplete styles & scripts
$extra_css = [
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css'
];
$extra_js = [
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'
];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$patientId = (int)($_GET['patient_id'] ?? 0);
$patientDetails = null;

try {
    // If patient_id is provided, retrieve patient details and verify they exist and are active
    if ($patientId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ? AND is_archived = 0");
        $stmt->execute([$patientId]);
        $patientDetails = $stmt->fetch();
        
        if (!$patientDetails) {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'title' => 'Patient Not Found',
                'message' => 'The requested patient profile is either archived or does not exist.'
            ];
            header('Location: ' . BASE_URL . 'health_records/list.php');
            exit;
        }
    }

    // Fetch all active patients for dropdown selection if no patient_id was locked
    $patients = [];
    if (!$patientDetails) {
        $stmt = $pdo->query("SELECT patient_id, first_name, middle_name, last_name, suffix, birthdate FROM patients WHERE is_archived = 0 ORDER BY last_name ASC, first_name ASC");
        $patients = $stmt->fetchAll();
    }

    // Fetch active service categories
    $servicesStmt = $pdo->query("SELECT service_id, service_name FROM service_types WHERE is_active = 1 ORDER BY service_name ASC");
    $services = $servicesStmt->fetchAll();

} catch (Exception $e) {
    error_log("Failed to load options for new health record: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'An error occurred while loading form options.'
    ];
    header('Location: ' . BASE_URL . 'health_records/list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">New Consultation & Health Record</h2>
            <p class="text-secondary mb-0">Record vital signs and clinical details for patient diagnosis and treatment.</p>
        </div>
        <div>
            <?php if ($patientDetails): ?>
                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Patient Profile</span>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>health_records/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Records List</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <form action="<?= BASE_URL ?>health_records/add_process.php" method="POST" id="newRecordForm" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="row">
            <!-- Left Side: Patient Select & Vitals Sign Panel -->
            <div class="col-lg-5 mb-4">
                
                <!-- Patient Selector Widget -->
                <div class="card-custom mb-4">
                    <div class="card-custom-header">
                        <h3 class="card-custom-title"><i class="bi bi-person-fill"></i> Patient Selector</h3>
                    </div>
                    <div class="card-custom-body">
                        <?php if ($patientDetails): ?>
                            <!-- Patient Locked in from profile -->
                            <input type="hidden" name="patient_id" value="<?= $patientDetails['patient_id'] ?>">
                            <div class="p-3 bg-light rounded-3">
                                <div class="small text-secondary mb-1">Active Patient profile</div>
                                <h4 class="fw-bold text-primary mb-1">
                                    <?= htmlspecialchars($patientDetails['last_name'] . ', ' . $patientDetails['first_name'] . ($patientDetails['middle_name'] ? ' ' . $patientDetails['middle_name'] : '') . ($patientDetails['suffix'] ? ' ' . $patientDetails['suffix'] : '')) ?>
                                </h4>
                                <p class="mb-0 text-secondary small">
                                    Sex: <strong><?= htmlspecialchars($patientDetails['sex']) ?></strong> | 
                                    Age: <strong><?= (new DateTime())->diff(new DateTime($patientDetails['birthdate']))->y ?> yrs</strong><br>
                                    Purok: <strong><?= htmlspecialchars($patientDetails['purok'] ?? 'N/A') ?></strong>
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- Choose Patient Dropdown -->
                            <div class="mb-0">
                                <label for="patient_id" class="form-label font-weight-bold mb-1">Select Patient <span class="text-danger">*</span></label>
                                <select name="patient_id" id="patient_id" class="form-select select2-enable" required>
                                    <option value="" disabled selected>-- Search & Select Patient --</option>
                                    <?php foreach ($patients as $pat): ?>
                                        <?php
                                            $dobText = date('Y-m-d', strtotime($pat['birthdate']));
                                            $ageText = (new DateTime())->diff(new DateTime($pat['birthdate']))->y;
                                            $patName = htmlspecialchars($pat['last_name'] . ', ' . $pat['first_name'] . ($pat['middle_name'] ? ' ' . substr($pat['middle_name'], 0, 1) . '.' : '') . ($pat['suffix'] ? ' ' . $pat['suffix'] : ''));
                                        ?>
                                        <option value="<?= $pat['patient_id'] ?>">
                                            <?= $patName ?> (<?= $ageText ?> yrs, born <?= $dobText ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vital Signs Panel -->
                <div class="card-custom">
                    <div class="card-custom-header">
                        <h3 class="card-custom-title"><i class="bi bi-heart-pulse-fill text-danger"></i> Vital Signs Metrics</h3>
                    </div>
                    <div class="card-custom-body">
                        <div class="row g-3">
                            <!-- Blood Pressure -->
                            <div class="col-sm-6">
                                <label for="blood_pressure" class="form-label font-weight-bold mb-1">Blood Pressure</label>
                                <input type="text" name="blood_pressure" id="blood_pressure" class="form-control" placeholder="e.g. 120/80">
                                <small class="text-secondary small">Format: Systolic/Diastolic</small>
                            </div>
                            
                            <!-- Temperature -->
                            <div class="col-sm-6">
                                <label for="temperature" class="form-label font-weight-bold mb-1">Temperature (°C)</label>
                                <input type="number" step="0.1" name="temperature" id="temperature" class="form-control" placeholder="e.g. 36.5" min="30" max="45">
                                <small class="text-secondary small">Body temperature in Celsius</small>
                            </div>

                            <!-- Weight -->
                            <div class="col-sm-6">
                                <label for="weight_kg" class="form-label font-weight-bold mb-1">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" id="weight_kg" class="form-control" placeholder="e.g. 65.2" min="1" max="300">
                                <small class="text-secondary small">Weight in kilograms</small>
                            </div>

                            <!-- Height -->
                            <div class="col-sm-6">
                                <label for="height_cm" class="form-label font-weight-bold mb-1">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" id="height_cm" class="form-control" placeholder="e.g. 165.0" min="20" max="250">
                                <small class="text-secondary small">Height in centimeters</small>
                            </div>

                            <!-- Heart Rate -->
                            <div class="col-sm-6">
                                <label for="heart_rate" class="form-label font-weight-bold mb-1">Heart Rate (bpm)</label>
                                <input type="number" name="heart_rate" id="heart_rate" class="form-control" placeholder="e.g. 72" min="20" max="250">
                                <small class="text-secondary small">Beats per minute</small>
                            </div>

                            <!-- Respiratory Rate -->
                            <div class="col-sm-6">
                                <label for="respiratory_rate" class="form-label font-weight-bold mb-1">Respiratory Rate</label>
                                <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" placeholder="e.g. 18" min="5" max="100">
                                <small class="text-secondary small">Breaths per minute</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Side: Consultation Details -->
            <div class="col-lg-7 mb-4">
                
                <div class="card-custom h-100">
                    <div class="card-custom-header">
                        <h3 class="card-custom-title"><i class="bi bi-file-earmark-medical"></i> Consultation & Diagnosis Log</h3>
                    </div>
                    <div class="card-custom-body">
                        <div class="row g-3 mb-4">
                            <!-- Service type -->
                            <div class="col-sm-6">
                                <label for="service_id" class="form-label font-weight-bold mb-1">Service Category <span class="text-danger">*</span></label>
                                <select name="service_id" id="service_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select Category --</option>
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['service_id'] ?>">
                                            <?= htmlspecialchars($srv['service_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Visit Date -->
                            <div class="col-sm-6">
                                <label for="visit_date" class="form-label font-weight-bold mb-1">Visit Date <span class="text-danger">*</span></label>
                                <input type="date" name="visit_date" id="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Chief Complaint -->
                        <div class="mb-3">
                            <label for="chief_complaint" class="form-label font-weight-bold mb-1">Chief Complaint <span class="text-danger">*</span></label>
                            <textarea name="chief_complaint" id="chief_complaint" class="form-control" rows="3" placeholder="Primary complaint / symptoms reported by patient..." required></textarea>
                        </div>

                        <!-- Diagnosis -->
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label font-weight-bold mb-1">Diagnosis</label>
                            <textarea name="diagnosis" id="diagnosis" class="form-control" rows="3" placeholder="Clinical assessment / diagnostic finding..."></textarea>
                        </div>

                        <!-- Treatment -->
                        <div class="mb-3">
                            <label for="treatment" class="form-label font-weight-bold mb-1">Treatment & Procedures</label>
                            <textarea name="treatment" id="treatment" class="form-control" rows="3" placeholder="Medical procedure or care administered in-clinic..."></textarea>
                        </div>

                        <!-- Prescription -->
                        <div class="mb-3">
                            <label for="prescription" class="form-label font-weight-bold mb-1">Prescription</label>
                            <textarea name="prescription" id="prescription" class="form-control" rows="3" placeholder="Medications prescribed (name, dosage, frequency)..."></textarea>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label font-weight-bold mb-1">Additional Clinical Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Recommendations, follow-up parameters..."></textarea>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Submission Controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <?php if ($patientDetails): ?>
                                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>health_records/list.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Consultation Log</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 Autocomplete
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2-enable').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    const form = document.getElementById('newRecordForm');

    form.addEventListener('submit', function(e) {
        const bp = document.getElementById('blood_pressure').value.trim();
        const visitDate = new Date(document.getElementById('visit_date').value);

        // 1. Visit Date limit checking (no future visit logs)
        if (visitDate > new Date()) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Visit Date',
                text: 'Consultation visit date cannot be registered in the future.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // 2. BP regex formatting validation (if provided)
        if (bp && !/^\d{2,3}\/\d{2,3}$/.test(bp)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid BP Format',
                text: 'Please match the Blood Pressure format "Systolic/Diastolic" (e.g. 120/80).',
                confirmButtonColor: '#0D7377'
            });
            return;
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

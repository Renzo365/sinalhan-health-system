<?php
// health_records/edit.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff (BHW has view-only access)
require_role(['admin', 'staff']);

$page_title = 'Edit Record';
$active_menu = 'health_records';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/encryption.php';
$pdo = Database::getInstance()->getConnection();

$recordId = (int)($_GET['id'] ?? 0);

if (!$recordId) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Missing ID',
        'message' => 'Please select a valid consultation record to edit.'
    ];
    header('Location: ' . BASE_URL . 'health_records/list.php');
    exit;
}

try {
    // Fetch record details
    $stmt = $pdo->prepare("
        SELECT 
            hr.*, 
            p.first_name AS patient_first, 
            p.middle_name AS patient_middle, 
            p.last_name AS patient_last, 
            p.suffix AS patient_suffix,
            p.birthdate AS patient_dob,
            p.sex AS patient_sex,
            p.purok AS patient_purok,
            vs.blood_pressure,
            vs.temperature,
            vs.weight_kg,
            vs.height_cm,
            vs.heart_rate,
            vs.respiratory_rate
        FROM health_records hr
        INNER JOIN patients p ON hr.patient_id = p.patient_id
        LEFT JOIN vital_signs vs ON hr.record_id = vs.record_id
        WHERE hr.record_id = ? AND hr.is_archived = 0 AND p.is_archived = 0
    ");
    $stmt->execute([$recordId]);
    $r = $stmt->fetch();

    if (!$r) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Record Not Found',
            'message' => 'The consultation record does not exist or has been archived.'
        ];
        header('Location: ' . BASE_URL . 'health_records/list.php');
        exit;
    }

    // Decrypt encrypted fields (HIPAA Compliance)
    $r['chief_complaint'] = decrypt_data($r['chief_complaint'] ?? '');
    $r['diagnosis'] = decrypt_data($r['diagnosis'] ?? '');
    $r['treatment'] = decrypt_data($r['treatment'] ?? '');
    $r['prescription'] = decrypt_data($r['prescription'] ?? '');
    $r['notes'] = decrypt_data($r['notes'] ?? '');

    $patientName = htmlspecialchars($r['patient_last'] . ', ' . $r['patient_first'] . ($r['patient_middle'] ? ' ' . $r['patient_middle'] : '') . ($r['patient_suffix'] ? ' ' . $r['patient_suffix'] : ''));
    $patientAge = (new DateTime())->diff(new DateTime($r['patient_dob']))->y;

    // Fetch active service categories
    $servicesStmt = $pdo->query("SELECT service_id, service_name FROM service_types WHERE is_active = 1 OR service_id = " . (int)$r['service_id'] . " ORDER BY service_name ASC");
    $services = $servicesStmt->fetchAll();

} catch (Exception $e) {
    error_log("Failed to load health record for editing: " . $e->getMessage());
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
            <h2 class="page-title">Edit Consultation Record</h2>
            <p class="text-secondary mb-0">Modify patient vital signs and clinical assessment logs.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>health_records/view.php?id=<?= $r['record_id'] ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Cancel & Back</span>
            </a>
        </div>
    </div>

    <form action="<?= BASE_URL ?>health_records/edit_process.php" method="POST" id="editRecordForm" class="needs-validation" novalidate>
        <!-- CSRF token and target Record ID -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="record_id" value="<?= $r['record_id'] ?>">
        
        <div class="row">
            <!-- Left Side: Patient Info Summary & Vital Signs -->
            <div class="col-lg-5 mb-4">
                
                <!-- Patient Info Card (Read-Only) -->
                <div class="card-custom mb-4">
                    <div class="card-custom-header">
                        <h3 class="card-custom-title"><i class="bi bi-person-fill text-secondary"></i> Selected Patient</h3>
                    </div>
                    <div class="card-custom-body">
                        <div class="p-3 bg-light rounded-3">
                            <div class="small text-secondary mb-1">Patient Profile Link (Immutable)</div>
                            <h4 class="fw-bold text-dark mb-1">
                                <?= $patientName ?>
                            </h4>
                            <p class="mb-0 text-secondary small">
                                Sex: <strong><?= htmlspecialchars($r['patient_sex']) ?></strong> | 
                                Age: <strong><?= $patientAge ?> yrs</strong><br>
                                Purok: <strong><?= htmlspecialchars($r['patient_purok'] ?? 'N/A') ?></strong>
                            </p>
                        </div>
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
                                <input type="text" name="blood_pressure" id="blood_pressure" class="form-control" value="<?= htmlspecialchars($r['blood_pressure'] ?? '') ?>" placeholder="e.g. 120/80">
                                <small class="text-secondary small">Format: Systolic/Diastolic</small>
                            </div>
                            
                            <!-- Temperature -->
                            <div class="col-sm-6">
                                <label for="temperature" class="form-label font-weight-bold mb-1">Temperature (°C)</label>
                                <input type="number" step="0.1" name="temperature" id="temperature" class="form-control" value="<?= $r['temperature'] !== null ? htmlspecialchars($r['temperature']) : '' ?>" placeholder="e.g. 36.5" min="30" max="45">
                                <small class="text-secondary small">Body temperature in Celsius</small>
                            </div>

                            <!-- Weight -->
                            <div class="col-sm-6">
                                <label for="weight_kg" class="form-label font-weight-bold mb-1">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" id="weight_kg" class="form-control" value="<?= $r['weight_kg'] !== null ? htmlspecialchars($r['weight_kg']) : '' ?>" placeholder="e.g. 65.2" min="1" max="300">
                                <small class="text-secondary small">Weight in kilograms</small>
                            </div>

                            <!-- Height -->
                            <div class="col-sm-6">
                                <label for="height_cm" class="form-label font-weight-bold mb-1">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" id="height_cm" class="form-control" value="<?= $r['height_cm'] !== null ? htmlspecialchars($r['height_cm']) : '' ?>" placeholder="e.g. 165.0" min="20" max="250">
                                <small class="text-secondary small">Height in centimeters</small>
                            </div>

                            <!-- Heart Rate -->
                            <div class="col-sm-6">
                                <label for="heart_rate" class="form-label font-weight-bold mb-1">Heart Rate (bpm)</label>
                                <input type="number" name="heart_rate" id="heart_rate" class="form-control" value="<?= $r['heart_rate'] !== null ? htmlspecialchars($r['heart_rate']) : '' ?>" placeholder="e.g. 72" min="20" max="250">
                                <small class="text-secondary small">Beats per minute</small>
                            </div>

                            <!-- Respiratory Rate -->
                            <div class="col-sm-6">
                                <label for="respiratory_rate" class="form-label font-weight-bold mb-1">Respiratory Rate</label>
                                <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" value="<?= $r['respiratory_rate'] !== null ? htmlspecialchars($r['respiratory_rate']) : '' ?>" placeholder="e.g. 18" min="5" max="100">
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
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['service_id'] ?>" <?= $r['service_id'] == $srv['service_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($srv['service_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Visit Date -->
                            <div class="col-sm-6">
                                <label for="visit_date" class="form-label font-weight-bold mb-1">Visit Date <span class="text-danger">*</span></label>
                                <input type="date" name="visit_date" id="visit_date" class="form-control" value="<?= htmlspecialchars($r['visit_date']) ?>" required>
                            </div>
                        </div>

                        <!-- Chief Complaint -->
                        <div class="mb-3">
                            <label for="chief_complaint" class="form-label font-weight-bold mb-1">Chief Complaint <span class="text-danger">*</span></label>
                            <textarea name="chief_complaint" id="chief_complaint" class="form-control" rows="3" placeholder="Symptoms reported by patient..." required><?= htmlspecialchars($r['chief_complaint']) ?></textarea>
                        </div>

                        <!-- Diagnosis -->
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label font-weight-bold mb-1">Diagnosis</label>
                            <textarea name="diagnosis" id="diagnosis" class="form-control" rows="3" placeholder="Clinical assessment FINDINGS..."><?= htmlspecialchars($r['diagnosis'] ?? '') ?></textarea>
                        </div>

                        <!-- Treatment -->
                        <div class="mb-3">
                            <label for="treatment" class="form-label font-weight-bold mb-1">Treatment & Procedures</label>
                            <textarea name="treatment" id="treatment" class="form-control" rows="3" placeholder="Treatment administered..."><?= htmlspecialchars($r['treatment'] ?? '') ?></textarea>
                        </div>

                        <!-- Prescription -->
                        <div class="mb-3">
                            <label for="prescription" class="form-label font-weight-bold mb-1">Prescription</label>
                            <textarea name="prescription" id="prescription" class="form-control" rows="3" placeholder="Medications prescribed..."><?= htmlspecialchars($r['prescription'] ?? '') ?></textarea>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label font-weight-bold mb-1">Additional Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Recommendations/Follow-ups..."><?= htmlspecialchars($r['notes'] ?? '') ?></textarea>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Submission Controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <a href="<?= BASE_URL ?>health_records/view.php?id=<?= $r['record_id'] ?>" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Changes</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editRecordForm');

    form.addEventListener('submit', function(e) {
        const bp = document.getElementById('blood_pressure').value.trim();
        const visitDate = new Date(document.getElementById('visit_date').value);

        // 1. Visit Date limit check (no future dates)
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

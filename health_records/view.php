<?php
// health_records/view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Consultation Details';
$active_menu = 'health_records';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/encryption.php';
$pdo = Database::getInstance()->getConnection();

$recordId = (int)($_GET['id'] ?? 0);

if (!$recordId) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Missing ID',
        'message' => 'Please select a valid consultation record to view.'
    ];
    header('Location: ' . BASE_URL . 'health_records/list.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            hr.*, 
            p.first_name AS patient_first, 
            p.middle_name AS patient_middle, 
            p.last_name AS patient_last, 
            p.suffix AS patient_suffix,
            p.birthdate AS patient_dob,
            p.sex AS patient_sex,
            p.civil_status AS patient_civil,
            p.purok AS patient_purok,
            st.service_name,
            u.first_name AS staff_first,
            u.last_name AS staff_last,
            vs.blood_pressure,
            vs.temperature,
            vs.weight_kg,
            vs.height_cm,
            vs.heart_rate,
            vs.respiratory_rate
        FROM health_records hr
        INNER JOIN patients p ON hr.patient_id = p.patient_id
        LEFT JOIN service_types st ON hr.service_id = st.service_id
        LEFT JOIN users u ON hr.attending_staff = u.user_id
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

    // Dynamic calculations
    $patientName = htmlspecialchars($r['patient_last'] . ', ' . $r['patient_first'] . ($r['patient_middle'] ? ' ' . $r['patient_middle'] : '') . ($r['patient_suffix'] ? ' ' . $r['patient_suffix'] : ''));
    $dob = new DateTime($r['patient_dob']);
    $now = new DateTime();
    $patientAge = $now->diff($dob)->y;
    
    $staffName = htmlspecialchars(($r['staff_first'] ?? '') . ' ' . ($r['staff_last'] ?? ''));
    if (trim($staffName) === '') {
        $staffName = 'N/A';
    }

    $role = $_SESSION['role'] ?? 'bhw';

    // BMI Calculations
    $bmi = null;
    $bmiClass = '';
    $bmiBadge = '';
    if ($r['weight_kg'] && $r['height_cm']) {
        $heightM = $r['height_cm'] / 100;
        $bmi = round($r['weight_kg'] / ($heightM * $heightM), 1);
        
        if ($bmi < 18.5) {
            $bmiClass = 'Underweight';
            $bmiBadge = 'bg-warning text-dark';
        } elseif ($bmi >= 18.5 && $bmi < 25.0) {
            $bmiClass = 'Normal';
            $bmiBadge = 'bg-success text-white';
        } elseif ($bmi >= 25.0 && $bmi < 30.0) {
            $bmiClass = 'Overweight';
            $bmiBadge = 'bg-warning text-dark';
        } else {
            $bmiClass = 'Obese';
            $bmiBadge = 'bg-danger text-white';
        }
    }

} catch (Exception $e) {
    error_log("Failed to load health record: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'An error occurred while loading this consultation file.'
    ];
    header('Location: ' . BASE_URL . 'health_records/list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
        <div>
            <h2 class="page-title">Consultation File Record</h2>
            <p class="text-secondary mb-0">Demographics, vital signs log, and diagnosis assessment from patient check-up.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>health_records/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2 py-2 px-3">
                <i class="bi bi-arrow-left"></i>
                <span>Records List</span>
            </a>
            <?php if ($role === 'admin' || $role === 'staff'): ?>
                <a href="<?= BASE_URL ?>health_records/edit.php?id=<?= $r['record_id'] ?>" class="btn btn-outline-primary d-flex align-items-center gap-2 py-2 px-3">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit Record</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="row">
        <!-- Sidebar Widget: Patient Demographics -->
        <div class="col-lg-4 mb-4">
            <div class="card-custom text-center py-4">
                <div class="card-custom-body">
                    <div class="user-avatar mx-auto mb-3" style="width: 70px; height: 70px; font-size: 28px; background: linear-gradient(135deg, var(--primary-light), var(--primary-color)); box-shadow: 0 8px 16px rgba(13, 115, 119, 0.2);">
                        <?= strtoupper(substr($r['patient_first'], 0, 1) . substr($r['patient_last'], 0, 1)) ?>
                    </div>
                    
                    <h3 class="fw-bold mb-1" style="font-size: 19px; color: var(--primary-dark);"><?= $patientName ?></h3>
                    <p class="mb-3"><span class="badge bg-light text-primary border">Patient profile</span></p>
                    
                    <div class="border-top pt-3 text-start">
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Sex:</div>
                            <div class="col-7 text-dark fw-bold"><?= htmlspecialchars($r['patient_sex']) ?></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Age / DOB:</div>
                            <div class="col-7 text-dark fw-bold"><?= $patientAge ?> yrs (<?= date('M d, Y', strtotime($r['patient_dob'])) ?>)</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Purok:</div>
                            <div class="col-7 text-dark fw-bold text-primary"><?= htmlspecialchars($r['patient_purok'] ?? 'N/A') ?></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Civil Status:</div>
                            <div class="col-7 text-dark"><?= htmlspecialchars($r['patient_civil'] ?? 'Single') ?></div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3 text-center">
                        <a href="<?= BASE_URL ?>patients/view.php?id=<?= $r['patient_id'] ?>" class="btn btn-sm btn-outline-teal w-100">
                            <i class="bi bi-person-bounding-box"></i> View Full Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Vital Signs Panel -->
            <div class="card-custom">
                <div class="card-custom-header py-3">
                    <h3 class="card-custom-title text-danger"><i class="bi bi-heart-pulse-fill"></i> Recorded Vital Signs</h3>
                </div>
                <div class="card-custom-body py-3">
                    <div class="row g-3">
                        <div class="col-6 border-bottom pb-2">
                            <span class="text-secondary small d-block">Blood Pressure</span>
                            <span class="fw-bold text-dark fs-5"><?= htmlspecialchars($r['blood_pressure'] ?? '—') ?></span>
                        </div>
                        <div class="col-6 border-bottom pb-2">
                            <span class="text-secondary small d-block">Temperature</span>
                            <span class="fw-bold text-dark fs-5"><?= $r['temperature'] !== null ? htmlspecialchars($r['temperature']) . ' °C' : '—' ?></span>
                        </div>
                        <div class="col-6 border-bottom pb-2">
                            <span class="text-secondary small d-block">Weight</span>
                            <span class="fw-bold text-dark fs-5"><?= $r['weight_kg'] !== null ? htmlspecialchars($r['weight_kg']) . ' kg' : '—' ?></span>
                        </div>
                        <div class="col-6 border-bottom pb-2">
                            <span class="text-secondary small d-block">Height</span>
                            <span class="fw-bold text-dark fs-5"><?= $r['height_cm'] !== null ? htmlspecialchars($r['height_cm']) . ' cm' : '—' ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-secondary small d-block">Heart Rate</span>
                            <span class="fw-bold text-dark fs-5"><?= $r['heart_rate'] !== null ? htmlspecialchars($r['heart_rate']) . ' bpm' : '—' ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-secondary small d-block">Respiration</span>
                            <span class="fw-bold text-dark fs-5"><?= $r['respiratory_rate'] !== null ? htmlspecialchars($r['respiratory_rate']) . ' cpm' : '—' ?></span>
                        </div>
                    </div>

                    <!-- BMI Widget -->
                    <?php if ($bmi !== null): ?>
                        <div class="bg-light p-3 rounded-3 mt-3 border">
                            <div class="row align-items-center">
                                <div class="col-7">
                                    <span class="text-secondary small d-block">Computed BMI</span>
                                    <span class="fw-bold fs-4 text-dark"><?= $bmi ?> kg/m²</span>
                                </div>
                                <div class="col-5 text-end">
                                    <span class="badge <?= $bmiBadge ?> px-3 py-2 font-weight-bold" style="font-size: 13px;"><?= $bmiClass ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Consultation Entry details -->
        <div class="col-lg-8 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header d-flex justify-content-between align-items-center py-3">
                    <h3 class="card-custom-title"><i class="bi bi-file-earmark-medical-fill text-primary"></i> Consultation Log Details</h3>
                    <span class="badge bg-light text-dark border">Record ID: #<?= str_pad($r['record_id'], 5, '0', STR_PAD_LEFT) ?></span>
                </div>
                
                <div class="card-custom-body p-4">
                    <!-- Session Metadata -->
                    <div class="row g-3 mb-4 bg-light p-3 rounded-3 border">
                        <div class="col-sm-4">
                            <span class="text-secondary small d-block">Service Category:</span>
                            <span class="fw-bold text-primary"><?= htmlspecialchars($r['service_name'] ?? 'General Consultation') ?></span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-secondary small d-block">Visit Date:</span>
                            <span class="fw-bold text-dark"><?= date('l, F d, Y', strtotime($r['visit_date'])) ?></span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-secondary small d-block">Attending Staff:</span>
                            <span class="fw-bold text-secondary"><?= $staffName ?></span>
                        </div>
                    </div>

                    <!-- Chief Complaint -->
                    <div class="mb-4">
                        <h5 class="fw-bold border-bottom pb-2 text-dark"><i class="bi bi-chat-left-text text-primary me-1"></i> Chief Complaint</h5>
                        <div class="p-3 bg-white rounded border mt-2" style="white-space: pre-wrap; min-height: 60px;"><?= htmlspecialchars($r['chief_complaint']) ?></div>
                    </div>

                    <!-- Diagnosis -->
                    <div class="mb-4">
                        <h5 class="fw-bold border-bottom pb-2 text-dark"><i class="bi bi-search text-danger me-1"></i> Clinical Diagnosis</h5>
                        <div class="p-3 bg-white rounded border mt-2" style="white-space: pre-wrap; min-height: 60px;"><?= $r['diagnosis'] ? htmlspecialchars($r['diagnosis']) : '<em class="text-muted">No diagnosis entered.</em>' ?></div>
                    </div>

                    <!-- Treatment -->
                    <div class="mb-4">
                        <h5 class="fw-bold border-bottom pb-2 text-dark"><i class="bi bi-bandaid text-info me-1"></i> Administered Treatment</h5>
                        <div class="p-3 bg-white rounded border mt-2" style="white-space: pre-wrap; min-height: 60px;"><?= $r['treatment'] ? htmlspecialchars($r['treatment']) : '<em class="text-muted">No treatment recorded.</em>' ?></div>
                    </div>

                    <!-- Prescription -->
                    <div class="mb-4">
                        <h5 class="fw-bold border-bottom pb-2 text-dark"><i class="bi bi-capsule-capsule text-success me-1"></i> Medical Prescription</h5>
                        <div class="p-3 bg-white rounded border mt-2" style="white-space: pre-wrap; min-height: 60px;"><?= $r['prescription'] ? htmlspecialchars($r['prescription']) : '<em class="text-muted">No prescription written.</em>' ?></div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-4">
                        <h5 class="fw-bold border-bottom pb-2 text-dark"><i class="bi bi-clipboard-check text-secondary me-1"></i> Clinical Notes & Advice</h5>
                        <div class="p-3 bg-white rounded border mt-2" style="white-space: pre-wrap; min-height: 50px;"><?= $r['notes'] ? htmlspecialchars($r['notes']) : '<em class="text-muted">No additional notes.</em>' ?></div>
                    </div>
                    
                    <?php if ($role === 'admin'): ?>
                        <div class="border-top pt-3 d-flex justify-content-end">
                            <button class="btn btn-outline-danger archive-record-btn" 
                                    data-id="<?= $r['record_id'] ?>" 
                                    data-patient="<?= htmlspecialchars($patientName) ?>"
                                    data-date="<?= date('Y-m-d', strtotime($r['visit_date'])) ?>">
                                <i class="bi bi-archive-fill"></i> Archive Record
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Hidden form for archiving records (Admin only) -->
<?php if ($role === 'admin'): ?>
    <form action="<?= BASE_URL ?>health_records/archive_process.php" method="POST" id="archiveRecordForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="record_id" id="archive_record_id" value="">
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Admin Confirm Archive Dialog
    const archiveBtn = document.querySelector('.archive-record-btn');
    if (archiveBtn) {
        archiveBtn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const patient = this.getAttribute('data-patient');
            const date = this.getAttribute('data-date');

            Swal.fire({
                title: 'Archive Consultation Record?',
                text: `You are about to archive the consultation record of '${patient}' from ${date}. A record administrator can restore it from the archive later.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, archive it'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('archive_record_id').value = id;
                    document.getElementById('archiveRecordForm').submit();
                }
            });
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

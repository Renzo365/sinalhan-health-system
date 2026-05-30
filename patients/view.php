<?php
// patients/view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Patient Profile';
$active_menu = 'patients';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/encryption.php';
$pdo = Database::getInstance()->getConnection();

$patientId = (int)($_GET['id'] ?? 0);

if (!$patientId) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Missing ID',
        'message' => 'Please select a valid patient profile.'
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

    // Dynamic calculations
    $fullName = htmlspecialchars($p['first_name'] . ($p['middle_name'] ? ' ' . $p['middle_name'] : '') . ' ' . $p['last_name'] . ($p['suffix'] ? ' ' . $p['suffix'] : ''));
    $dob = new DateTime($p['birthdate']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
    $role = $_SESSION['role'] ?? 'bhw';

    // Fetch consultations history (ordered newest first)
    $consultsStmt = $pdo->prepare("
        SELECT hr.record_id, hr.visit_date, hr.chief_complaint, st.service_name, u.first_name AS staff_first, u.last_name AS staff_last
        FROM health_records hr
        LEFT JOIN service_types st ON hr.service_id = st.service_id
        LEFT JOIN users u ON hr.attending_staff = u.user_id
        WHERE hr.patient_id = ? AND hr.is_archived = 0
        ORDER BY hr.visit_date DESC, hr.record_id DESC
    ");
    $consultsStmt->execute([$patientId]);
    $consultations = $consultsStmt->fetchAll();

    // Fetch appointments history (ordered newest first)
    $appStmt = $pdo->prepare("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.reason, st.service_name
        FROM appointments a
        LEFT JOIN service_types st ON a.service_id = st.service_id
        WHERE a.patient_id = ? AND a.is_archived = 0
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $appStmt->execute([$patientId]);
    $patientAppointments = $appStmt->fetchAll();

} catch (Exception $e) {
    error_log("Patient view fetch failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'Failed to load patient information.'
    ];
    header('Location: ' . BASE_URL . 'patients/list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
        <div>
            <h2 class="page-title">Patient Profile File</h2>
            <p class="text-secondary mb-0">Demographics, contacts, and historical medical log records.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>patients/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2 py-2 px-3">
                <i class="bi bi-arrow-left"></i>
                <span>Directory</span>
            </a>
            <?php if ($role === 'admin' || $role === 'staff' || $role === 'bhw'): ?>
                <a href="<?= BASE_URL ?>patients/edit.php?id=<?= $p['patient_id'] ?>" class="btn btn-outline-primary d-flex align-items-center gap-2 py-2 px-3">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit Profile</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="row">
        <!-- Sidebar Widget: Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="card-custom text-center py-4">
                <div class="card-custom-body">
                    <!-- Patient Avatar placeholder -->
                    <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px; background: linear-gradient(135deg, var(--primary-light), var(--primary-color)); box-shadow: 0 8px 16px rgba(13, 115, 119, 0.2);">
                        <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                    </div>
                    
                    <h3 class="fw-bold mb-1" style="font-size: 20px; color: var(--primary-dark);"><?= $fullName ?></h3>
                    <p class="text-secondary mb-3"><span class="badge bg-light text-primary border">ID: #<?= str_pad($p['patient_id'], 5, '0', STR_PAD_LEFT) ?></span></p>
                    
                    <div class="border-top pt-3 text-start">
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Sex:</div>
                            <div class="col-7 text-dark fw-bold"><?= htmlspecialchars($p['sex']) ?></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Age / DOB:</div>
                            <div class="col-7 text-dark fw-bold"><?= $age ?> yrs (<?= date('M d, Y', strtotime($p['birthdate'])) ?>)</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Civil Status:</div>
                            <div class="col-7 text-dark"><?= htmlspecialchars($p['civil_status'] ?? 'Single') ?></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Residential:</div>
                            <div class="col-7 text-dark fw-bold text-primary"><?= htmlspecialchars($p['purok'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Emergency Card -->
            <div class="card-custom">
                <div class="card-custom-header py-3">
                    <h3 class="card-custom-title text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Emergency Contact</h3>
                </div>
                <div class="card-custom-body py-3">
                    <?php if ($p['emergency_contact_name']): ?>
                        <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($p['emergency_contact_name']) ?></div>
                        <div class="text-secondary small"><i class="bi bi-telephone-fill me-1 text-danger"></i> <?= htmlspecialchars($p['emergency_contact_number'] ?? 'No number provided') ?></div>
                    <?php else: ?>
                        <div class="text-muted small text-center py-2">No emergency contact recorded.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Tabbed Profile Module Details -->
        <div class="col-lg-8 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs px-3 pt-3" id="patientProfileTabs" role="tablist" style="border-bottom: 1px solid var(--border-color);">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active font-weight-bold" id="medical-tab" data-bs-toggle="tab" data-bs-target="#medical-pane" type="button" role="tab" aria-controls="medical-pane" aria-selected="true">
                                <i class="bi bi-heart-pulse-fill me-2 text-primary"></i> Medical Profile
                            </button>
                        </li>
                        <?php if ($role === 'admin' || $role === 'staff'): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link font-weight-bold" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab" aria-controls="history-pane" aria-selected="false">
                                    <i class="bi bi-journal-medical me-2 text-info"></i> Consultations
                                </button>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link font-weight-bold" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments-pane" type="button" role="tab" aria-controls="appointments-pane" aria-selected="false">
                                <i class="bi bi-calendar-event me-2 text-accent"></i> Appointments
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-custom-body p-4">
                    <div class="tab-content" id="profileTabsContent">
                        
                        <!-- Tab 1: Medical Profile -->
                        <div class="tab-pane fade show active" id="medical-pane" role="tabpanel" aria-labelledby="medical-tab" tabindex="0">
                            <!-- Pre-existing conditions -->
                            <div class="mb-4">
                                <h5 class="fw-bold border-bottom pb-2 text-dark"><i class="bi bi-file-earmark-medical me-1 text-primary"></i> Pre-existing Medical History</h5>
                                <div class="bg-light p-3 rounded-3 mt-2" style="min-height: 80px;">
                                    <?= $p['medical_history'] ? nl2br(htmlspecialchars($p['medical_history'])) : '<em class="text-muted">No prior medical history reported.</em>' ?>
                                </div>
                            </div>
                            
                            <!-- Allergies -->
                            <div class="mb-4">
                                <h5 class="fw-bold border-bottom pb-2 text-dark"><i class="bi bi-shield-exclamation me-1 text-danger"></i> Known Drug / Food Allergies</h5>
                                <div class="bg-light p-3 rounded-3 mt-2" style="min-height: 80px;">
                                    <?= $p['allergies'] ? nl2br(htmlspecialchars($p['allergies'])) : '<em class="text-muted">No known allergies.</em>' ?>
                                </div>
                            </div>

                            <!-- Additional Demographics -->
                            <div>
                                <h5 class="fw-bold border-bottom pb-2 text-dark"><i class="bi bi-geo-alt me-1 text-primary"></i> Full Address Details</h5>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="text-secondary small font-weight-bold mb-1 d-block">Purok / Zone:</label>
                                        <div class="fw-bold"><?= htmlspecialchars($p['purok'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-secondary small font-weight-bold mb-1 d-block">Address details:</label>
                                        <div class="fw-bold"><?= htmlspecialchars($p['address'] ?? 'No address details provided.') ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-secondary small font-weight-bold mb-1 d-block">Mobile Contact Number:</label>
                                        <div class="fw-bold"><?= htmlspecialchars($p['contact_number'] ?? 'No number provided.') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab 2: Consultations history -->
                        <?php if ($role === 'admin' || $role === 'staff'): ?>
                            <div class="tab-pane fade" id="history-pane" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journal-medical text-primary me-1"></i> Patient Clinic History</h5>
                                    <?php if ($role === 'admin' || $role === 'staff'): ?>
                                        <a href="<?= BASE_URL ?>health_records/add.php?patient_id=<?= $p['patient_id'] ?>" class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                                            <i class="bi bi-file-earmark-medical-fill"></i>
                                            <span>Add Consultation</span>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <?php if (count($consultations) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle small">
                                            <thead>
                                                <tr class="table-light">
                                                    <th>Visit Date</th>
                                                    <th>Service Category</th>
                                                    <th>Chief Complaint</th>
                                                    <th>Attending Staff</th>
                                                    <th class="text-center" style="width: 80px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($consultations as $c): ?>
                                                    <?php
                                                        $staffName = htmlspecialchars(($c['staff_first'] ?? '') . ' ' . ($c['staff_last'] ?? ''));
                                                        if (trim($staffName) === '') {
                                                            $staffName = 'N/A';
                                                        }
                                                        $complaint = htmlspecialchars($c['chief_complaint'] ?? '');
                                                        if (strlen($complaint) > 50) {
                                                            $complaint = substr($complaint, 0, 47) . '...';
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td><strong class="text-dark"><?= date('Y-m-d', strtotime($c['visit_date'])) ?></strong></td>
                                                        <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($c['service_name'] ?? 'General') ?></span></td>
                                                        <td class="text-secondary"><?= $complaint ?></td>
                                                        <td class="text-secondary"><?= $staffName ?></td>
                                                        <td class="text-center">
                                                            <a href="<?= BASE_URL ?>health_records/view.php?id=<?= $c['record_id'] ?>" class="btn btn-sm btn-outline-info border-0 p-1" title="View Detail Record">
                                                                <i class="bi bi-eye-fill fs-6"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5 text-muted border rounded-3 bg-light">
                                        <i class="bi bi-folder-symlink fs-1 d-block mb-3 text-secondary"></i>
                                        <h5>No Consultations Recorded</h5>
                                        <p class="small text-secondary mb-0">There are no clinic visits or check-up logs registered for this patient profile.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Tab 3: Appointments history -->
                        <div class="tab-pane fade" id="appointments-pane" role="tabpanel" aria-labelledby="appointments-tab" tabindex="0">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-event-fill text-accent me-1"></i> Patient Appointments</h5>
                                <a href="<?= BASE_URL ?>appointments/add.php?patient_id=<?= $p['patient_id'] ?>" class="btn btn-sm btn-teal d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar-plus-fill"></i>
                                    <span>Schedule Appointment</span>
                                </a>
                            </div>

                            <?php if (count($patientAppointments) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle small">
                                        <thead>
                                            <tr class="table-light">
                                                <th>Date / Time</th>
                                                <th>Service Type</th>
                                                <th>Reason / Details</th>
                                                <th>Status</th>
                                                <?php if ($role === 'admin' || $role === 'staff' || $role === 'bhw'): ?>
                                                    <th class="text-center" style="width: 80px;">Action</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($patientAppointments as $a): ?>
                                                <?php
                                                    $timeText = $a['appointment_time'] ? date('h:i A', strtotime($a['appointment_time'])) : 'N/A';
                                                    $reason = htmlspecialchars($a['reason'] ?? '');
                                                    if (strlen($reason) > 50) {
                                                        $reason = substr($reason, 0, 47) . '...';
                                                    }

                                                    $statusClass = 'badge bg-secondary';
                                                    if ($a['status'] === 'Scheduled') $statusClass = 'badge bg-primary';
                                                    elseif ($a['status'] === 'Completed') $statusClass = 'badge bg-success';
                                                    elseif ($a['status'] === 'Cancelled') $statusClass = 'badge bg-danger';
                                                    elseif ($a['status'] === 'No-Show') $statusClass = 'badge bg-dark text-white';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong class="text-dark d-block"><?= date('Y-m-d', strtotime($a['appointment_date'])) ?></strong>
                                                        <span class="text-muted small"><i class="bi bi-clock me-1"></i> <?= $timeText ?></span>
                                                    </td>
                                                    <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($a['service_name'] ?? 'General') ?></span></td>
                                                    <td class="text-secondary"><?= $reason ?></td>
                                                    <td><span class="<?= $statusClass ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                                                    <?php if ($role === 'admin' || $role === 'staff' || $role === 'bhw'): ?>
                                                        <td class="text-center">
                                                            <a href="<?= BASE_URL ?>appointments/edit.php?id=<?= $a['appointment_id'] ?>" class="btn btn-sm btn-outline-primary border-0 p-1" title="Modify Appointment">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </a>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted border rounded-3 bg-light">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-3 text-secondary"></i>
                                    <h5>No Scheduled Appointments</h5>
                                    <p class="small text-secondary mb-0">There are no check-ups scheduled for this patient profile.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

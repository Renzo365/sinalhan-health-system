<?php
// appointments/view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Appointment Details';
$active_menu = 'appointments';

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$appId = (int)($_GET['id'] ?? 0);

if (!$appId) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Missing ID',
        'message' => 'Please select a valid appointment record to view.'
    ];
    header('Location: ' . BASE_URL . 'appointments/list.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.*, 
            p.first_name AS patient_first, 
            p.middle_name AS patient_middle, 
            p.last_name AS patient_last, 
            p.suffix AS patient_suffix,
            p.birthdate AS patient_dob,
            p.sex AS patient_sex,
            p.purok AS patient_purok,
            p.contact_number AS patient_contact,
            p.civil_status AS patient_civil,
            st.service_name,
            u.first_name AS staff_first,
            u.last_name AS staff_last
        FROM appointments a
        INNER JOIN patients p ON a.patient_id = p.patient_id
        LEFT JOIN service_types st ON a.service_id = st.service_id
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.appointment_id = ? AND a.is_archived = 0 AND p.is_archived = 0
    ");
    $stmt->execute([$appId]);
    $appt = $stmt->fetch();

    if (!$appt) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Appointment Not Found',
            'message' => 'The appointment record does not exist or has been archived.'
        ];
        header('Location: ' . BASE_URL . 'appointments/list.php');
        exit;
    }

    // Calculations
    $patientName = htmlspecialchars($appt['patient_last'] . ', ' . $appt['patient_first'] . ($appt['patient_middle'] ? ' ' . $appt['patient_middle'] : '') . ($appt['patient_suffix'] ? ' ' . $appt['patient_suffix'] : ''));
    $dob = new DateTime($appt['patient_dob']);
    $now = new DateTime();
    $patientAge = $now->diff($dob)->y;
    
    $staffName = htmlspecialchars(($appt['staff_first'] ?? '') . ' ' . ($appt['staff_last'] ?? ''));
    if (trim($staffName) === '') {
        $staffName = 'System Agent';
    }

    $timeText = $appt['appointment_time'] ? date('h:i A', strtotime($appt['appointment_time'])) : 'N/A';
    $role = $_SESSION['role'] ?? 'bhw';
    $todayStr = date('Y-m-d');
    
    // Status Badge colors
    $isOverdue = ($appt['status'] === 'Scheduled' && date('Y-m-d', strtotime($appt['appointment_date'])) < $todayStr);
    $statusClass = 'badge bg-secondary';
    if ($appt['status'] === 'Scheduled') $statusClass = 'badge bg-primary';
    elseif ($appt['status'] === 'Completed') $statusClass = 'badge bg-success';
    elseif ($appt['status'] === 'Cancelled') $statusClass = 'badge bg-danger';
    elseif ($appt['status'] === 'No-Show') $statusClass = 'badge bg-dark text-white';

} catch (Exception $e) {
    error_log("Failed to load appointment detail: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'An error occurred while loading this appointment details.'
    ];
    header('Location: ' . BASE_URL . 'appointments/list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
        <div>
            <h2 class="page-title">Appointment Details</h2>
            <p class="text-secondary mb-0">Demographics, booking details, and schedule status for the check-up slot.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2 py-2 px-3">
                <i class="bi bi-arrow-left"></i>
                <span>Directory</span>
            </a>
            <?php if ($role === 'admin' || $role === 'staff' || $role === 'bhw'): ?>
                <a href="<?= BASE_URL ?>appointments/edit.php?id=<?= $appt['appointment_id'] ?>" class="btn btn-outline-primary d-flex align-items-center gap-2 py-2 px-3">
                    <i class="bi bi-pencil-square"></i>
                    <span>Reschedule / Edit</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Widget: Patient Demographics -->
        <div class="col-lg-4 mb-4">
            <div class="card-custom text-center py-4 h-100">
                <div class="card-custom-body">
                    <div class="user-avatar mx-auto mb-3" style="width: 70px; height: 70px; font-size: 28px; background: linear-gradient(135deg, var(--primary-light), var(--primary-color)); box-shadow: 0 8px 16px rgba(13, 115, 119, 0.2);">
                        <?= strtoupper(substr($appt['patient_first'], 0, 1) . substr($appt['patient_last'], 0, 1)) ?>
                    </div>
                    
                    <h3 class="fw-bold mb-1" style="font-size: 19px; color: var(--primary-dark);"><?= $patientName ?></h3>
                    <p class="mb-3"><span class="badge bg-light text-primary border">Patient Context</span></p>
                    
                    <div class="border-top pt-3 text-start">
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Sex:</div>
                            <div class="col-7 text-dark fw-bold"><?= htmlspecialchars($appt['patient_sex']) ?></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Age / DOB:</div>
                            <div class="col-7 text-dark fw-bold"><?= $patientAge ?> yrs (<?= date('M d, Y', strtotime($appt['patient_dob'])) ?>)</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Purok:</div>
                            <div class="col-7 text-dark fw-bold text-primary"><?= htmlspecialchars($appt['patient_purok'] ?? 'N/A') ?></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Civil Status:</div>
                            <div class="col-7 text-dark"><?= htmlspecialchars($appt['patient_civil'] ?? 'Single') ?></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Contact #:</div>
                            <div class="col-7 text-dark"><?= htmlspecialchars($appt['patient_contact'] ?? 'None') ?></div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3 text-center">
                        <a href="<?= BASE_URL ?>patients/view.php?id=<?= $appt['patient_id'] ?>" class="btn btn-sm btn-outline-teal w-100">
                            <i class="bi bi-person-bounding-box"></i> View Full Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area: Appointment details -->
        <div class="col-lg-8 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-calendar-event"></i> Schedule Details</h3>
                </div>
                <div class="card-custom-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6 border-bottom pb-3">
                            <span class="text-secondary small d-block mb-1">Appointment Date</span>
                            <span class="fw-bold text-dark fs-5"><i class="bi bi-calendar-date text-primary me-2"></i><?= date('F d, Y (l)', strtotime($appt['appointment_date'])) ?></span>
                        </div>
                        <div class="col-md-6 border-bottom pb-3">
                            <span class="text-secondary small d-block mb-1">Scheduled Time</span>
                            <span class="fw-bold text-dark fs-5"><i class="bi bi-clock text-primary me-2"></i><?= $timeText ?></span>
                        </div>
                        <div class="col-md-6 border-bottom pb-3">
                            <span class="text-secondary small d-block mb-1">Service Type Category</span>
                            <span class="badge bg-light text-primary border fs-6 px-3 py-2 fw-bold mt-1"><?= htmlspecialchars($appt['service_name'] ?? 'General Consultation') ?></span>
                        </div>
                        <div class="col-md-6 border-bottom pb-3">
                            <span class="text-secondary small d-block mb-1">Current Status</span>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="<?= $statusClass ?> fs-6 px-3 py-2 fw-bold"><?= htmlspecialchars($appt['status']) ?></span>
                                <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger text-white fs-6 px-3 py-2 fw-bold">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <span class="text-secondary small d-block mb-1">Reason / Complaint Details</span>
                        <div class="p-3 bg-light rounded border text-dark" style="min-height: 80px; white-space: pre-wrap; font-size: 14px;"><?= htmlspecialchars($appt['reason'] ?? '') ?></div>
                    </div>

                    <div class="mb-4">
                        <span class="text-secondary small d-block mb-1">Staff Remarks / Notes</span>
                        <div class="p-3 bg-light rounded border text-dark" style="min-height: 80px; white-space: pre-wrap; font-size: 14px; font-style: <?= empty($appt['notes']) ? 'italic' : 'normal' ?>;"><?= $appt['notes'] ? htmlspecialchars($appt['notes']) : 'No additional staff remarks recorded.' ?></div>
                    </div>

                    <div class="border-top pt-3 text-secondary" style="font-size: 12px;">
                        <div class="row">
                            <div class="col-sm-6 mb-2 mb-sm-0">
                                <strong>Scheduled By:</strong> <?= $staffName ?>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <strong>Registered:</strong> <?= date('Y-m-d h:i A', strtotime($appt['created_at'])) ?>
                                <?php if ($appt['updated_at'] !== $appt['created_at']): ?>
                                    <br><strong>Last Updated:</strong> <?= date('Y-m-d h:i A', strtotime($appt['updated_at'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

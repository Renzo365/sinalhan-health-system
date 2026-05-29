<?php
// appointments/edit.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff (BHW restricted from editing)
require_role(['admin', 'staff']);

$page_title = 'Edit Appointment';
$active_menu = 'appointments';

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$appId = (int)($_GET['id'] ?? 0);

if (!$appId) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Missing ID',
        'message' => 'Please select a valid appointment file to edit.'
    ];
    header('Location: ' . BASE_URL . 'appointments/list.php');
    exit;
}

try {
    // Fetch appointment record
    $stmt = $pdo->prepare("
        SELECT 
            a.*, 
            p.first_name, 
            p.middle_name, 
            p.last_name, 
            p.suffix,
            p.sex,
            p.birthdate,
            p.purok
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.appointment_id = ? AND a.is_archived = 0 AND p.is_archived = 0
    ");
    $stmt->execute([$appId]);
    $a = $stmt->fetch();

    if (!$a) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Appointment Not Found',
            'message' => 'The appointment record does not exist or has been archived.'
        ];
        header('Location: ' . BASE_URL . 'appointments/list.php');
        exit;
    }

    $patientName = htmlspecialchars($a['last_name'] . ', ' . $a['first_name'] . ($a['middle_name'] ? ' ' . $a['middle_name'] : '') . ($a['suffix'] ? ' ' . $a['suffix'] : ''));
    $patientAge = (new DateTime())->diff(new DateTime($a['birthdate']))->y;

    // Fetch active service categories
    $servicesStmt = $pdo->query("SELECT service_id, service_name FROM service_types WHERE is_active = 1 OR service_id = " . (int)$a['service_id'] . " ORDER BY service_name ASC");
    $services = $servicesStmt->fetchAll();

} catch (Exception $e) {
    error_log("Failed to load appointment for editing: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'An error occurred while loading form options.'
    ];
    header('Location: ' . BASE_URL . 'appointments/list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Edit Appointment Schedule</h2>
            <p class="text-secondary mb-0">Modify booking parameters or update check-up attendance status.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Cancel & Back</span>
            </a>
        </div>
    </div>

    <!-- Edit form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-calendar-event-fill"></i> Modify Booking Details</h3>
                </div>
                <div class="card-custom-body">
                    <form action="<?= BASE_URL ?>appointments/edit_process.php" method="POST" id="editAppointmentForm" class="needs-validation" novalidate>
                        <!-- CSRF and Target Appointment ID -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="appointment_id" value="<?= $a['appointment_id'] ?>">

                        <!-- Patient Info Summary (Read-Only) -->
                        <div class="mb-4">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="small text-secondary mb-1">Target Patient Profile (Immutable)</div>
                                <h4 class="fw-bold text-dark mb-1">
                                    <?= $patientName ?>
                                </h4>
                                <p class="mb-0 text-secondary small">
                                    Sex: <strong><?= htmlspecialchars($a['sex']) ?></strong> | 
                                    Age: <strong><?= $patientAge ?> yrs</strong> |
                                    Purok: <strong><?= htmlspecialchars($a['purok'] ?? 'N/A') ?></strong>
                                </p>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Service Type Category -->
                            <div class="col-md-12">
                                <label for="service_id" class="form-label font-weight-bold mb-1">Service Type Category <span class="text-danger">*</span></label>
                                <select name="service_id" id="service_id" class="form-select" required>
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['service_id'] ?>" <?= $a['service_id'] == $srv['service_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($srv['service_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Appointment Date -->
                            <div class="col-md-4">
                                <label for="appointment_date" class="form-label font-weight-bold mb-1">Appointment Date <span class="text-danger">*</span></label>
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control" value="<?= htmlspecialchars($a['appointment_date']) ?>" required>
                            </div>

                            <!-- Appointment Time -->
                            <div class="col-md-4">
                                <label for="appointment_time" class="form-label font-weight-bold mb-1">Appointment Time</label>
                                <input type="time" name="appointment_time" id="appointment_time" class="form-control" value="<?= htmlspecialchars($a['appointment_time'] ?? '') ?>">
                            </div>

                            <!-- Appointment Status -->
                            <div class="col-md-4">
                                <label for="status" class="form-label font-weight-bold mb-1">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="Scheduled" <?= $a['status'] === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Completed" <?= $a['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $a['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="No-Show" <?= $a['status'] === 'No-Show' ? 'selected' : '' ?>>No-Show</option>
                                </select>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label font-weight-bold mb-1">Reason for Visit <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" class="form-control" rows="3" required><?= htmlspecialchars($a['reason']) ?></textarea>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label font-weight-bold mb-1">Administrative Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Any additional updates..."><?= htmlspecialchars($a['notes'] ?? '') ?></textarea>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Action controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

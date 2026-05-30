<?php
// appointments/add.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw (scheduling allowed for all roles)
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Schedule Appointment';
$active_menu = 'appointments';

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
    // If patient_id is locked from URL query
    if ($patientId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ? AND is_archived = 0");
        $stmt->execute([$patientId]);
        $patientDetails = $stmt->fetch();
        
        if (!$patientDetails) {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'title' => 'Patient Not Found',
                'message' => 'The patient profile is archived or does not exist.'
            ];
            header('Location: ' . BASE_URL . 'appointments/list.php');
            exit;
        }
    }

    // Fetch active patients list for dropdown if not locked
    $patients = [];
    if (!$patientDetails) {
        $stmt = $pdo->query("SELECT patient_id, first_name, middle_name, last_name, suffix, birthdate FROM patients WHERE is_archived = 0 ORDER BY last_name ASC, first_name ASC");
        $patients = $stmt->fetchAll();
    }

    // Fetch active service types
    $servicesStmt = $pdo->query("SELECT service_id, service_name FROM service_types WHERE is_active = 1 ORDER BY service_name ASC");
    $services = $servicesStmt->fetchAll();

} catch (Exception $e) {
    error_log("Failed to load options for new appointment: " . $e->getMessage());
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
            <h2 class="page-title">Schedule New Appointment</h2>
            <p class="text-secondary mb-0">Book a medical check-up date and time for resident patients.</p>
        </div>
        <div>
            <?php if ($patientDetails): ?>
                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Patient Profile</span>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Appointments</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Appointment form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-calendar-plus-fill"></i> Appointment Booking Details</h3>
                </div>
                <div class="card-custom-body">
                    <form action="<?= BASE_URL ?>appointments/add_process.php" method="POST" id="newAppointmentForm" class="needs-validation" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- Patient Selection -->
                        <div class="mb-4">
                            <?php if ($patientDetails): ?>
                                <input type="hidden" name="patient_id" value="<?= $patientDetails['patient_id'] ?>">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="small text-secondary mb-1">Target Patient Profile</div>
                                    <h4 class="fw-bold text-primary mb-1">
                                        <?= htmlspecialchars($patientDetails['last_name'] . ', ' . $patientDetails['first_name'] . ($patientDetails['middle_name'] ? ' ' . $patientDetails['middle_name'] : '') . ($patientDetails['suffix'] ? ' ' . $patientDetails['suffix'] : '')) ?>
                                    </h4>
                                    <p class="mb-0 text-secondary small">
                                        Sex: <strong><?= htmlspecialchars($patientDetails['sex']) ?></strong> | 
                                        Age: <strong><?= (new DateTime())->diff(new DateTime($patientDetails['birthdate']))->y ?> yrs</strong> |
                                        Purok: <strong><?= htmlspecialchars($patientDetails['purok'] ?? 'N/A') ?></strong>
                                    </p>
                                </div>
                            <?php else: ?>
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
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Service type category -->
                            <div class="col-md-12">
                                <label for="service_id" class="form-label font-weight-bold mb-1">Service Type Category <span class="text-danger">*</span></label>
                                <select name="service_id" id="service_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select Service --</option>
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['service_id'] ?>">
                                            <?= htmlspecialchars($srv['service_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Appointment Date -->
                            <div class="col-md-6">
                                <label for="appointment_date" class="form-label font-weight-bold mb-1">Appointment Date <span class="text-danger">*</span></label>
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                <small class="text-secondary small">Cannot schedule in the past.</small>
                            </div>

                            <!-- Appointment Time -->
                            <div class="col-md-6">
                                <label for="appointment_time" class="form-label font-weight-bold mb-1">Appointment Time</label>
                                <input type="time" name="appointment_time" id="appointment_time" class="form-control">
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label font-weight-bold mb-1">Reason for Visit <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Describe symptoms or purpose of check-up..." required></textarea>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label font-weight-bold mb-1">Administrative Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Any additional information or reminders..."></textarea>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Action controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <?php if ($patientDetails): ?>
                                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Schedule Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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

    const form = document.getElementById('newAppointmentForm');

    form.addEventListener('submit', function(e) {
        const appDateVal = document.getElementById('appointment_date').value;
        if (!appDateVal) return;

        const appDate = new Date(appDateVal + 'T00:00:00');
        const today = new Date();
        today.setHours(0,0,0,0);

        // Client-side past date check
        if (appDate < today) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Appointment Date',
                text: 'You cannot book an appointment in the past.',
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

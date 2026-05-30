<?php
// queue/assign.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Assign Queue Ticket';
$active_menu = 'queue_assign';

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
            header('Location: ' . BASE_URL . 'queue/assign.php');
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
    error_log("Failed to load options for queue assignment: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'An error occurred while loading form options.'
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
            <h2 class="page-title">Generate Queue Ticket</h2>
            <p class="text-secondary mb-0">Assign a daily sequential walk-in ticket number for medical consult queuing.</p>
        </div>
        <div>
            <?php if ($patientDetails): ?>
                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Patient Profile</span>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>patients/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Patient Directory</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Assignment form -->
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card-custom">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-ticket-perforated-fill text-primary"></i> Daily Ticket Assignment</h3>
                </div>
                <div class="card-custom-body">
                    <form action="<?= BASE_URL ?>queue/add_process.php" method="POST" id="assignQueueForm" class="needs-validation" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- Target Patient Details -->
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

                        <!-- Service Category -->
                        <div class="mb-4">
                            <label for="service_id" class="form-label font-weight-bold mb-1">Select Service Category <span class="text-danger">*</span></label>
                            <select name="service_id" id="service_id" class="form-select" required>
                                <option value="" disabled selected>-- Select Service --</option>
                                <?php foreach ($services as $srv): ?>
                                    <option value="<?= $srv['service_id'] ?>">
                                        <?= htmlspecialchars($srv['service_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date & Time Info Summary -->
                        <div class="mb-4 bg-light p-3 rounded-3 border">
                            <div class="row">
                                <div class="col-6">
                                    <span class="text-secondary small d-block">Queue Date:</span>
                                    <span class="fw-bold text-dark"><?= date('Y-m-d') ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="text-secondary small d-block">Status Assigned:</span>
                                    <span class="badge bg-warning text-dark font-weight-bold">Waiting</span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Action controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <?php if ($patientDetails): ?>
                                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>patients/list.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Assign Ticket</button>
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

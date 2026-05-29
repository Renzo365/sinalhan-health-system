<?php
// health_records/list.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Health Records';
$active_menu = 'health_records';

// Load DataTables styles and scripts via CDN
$extra_css = ['https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css'];
$extra_js = [
    'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js'
];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$role = $_SESSION['role'] ?? 'bhw';

// Filters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$serviceFilterId = $_GET['service_id'] ?? '';

try {
    // Fetch active service types for filter dropdown
    $servicesStmt = $pdo->query("SELECT service_id, service_name FROM service_types WHERE is_active = 1 ORDER BY service_name ASC");
    $serviceList = $servicesStmt->fetchAll();
    
    // Construct search query
    $sql = "
        SELECT 
            hr.record_id, 
            hr.patient_id, 
            hr.service_id, 
            hr.visit_date, 
            hr.chief_complaint, 
            hr.attending_staff, 
            p.first_name AS patient_first, 
            p.middle_name AS patient_middle, 
            p.last_name AS patient_last, 
            p.suffix AS patient_suffix,
            st.service_name,
            u.first_name AS staff_first,
            u.last_name AS staff_last
        FROM health_records hr
        INNER JOIN patients p ON hr.patient_id = p.patient_id
        LEFT JOIN service_types st ON hr.service_id = st.service_id
        LEFT JOIN users u ON hr.attending_staff = u.user_id
        WHERE hr.is_archived = 0 AND p.is_archived = 0
    ";
    
    $params = [];
    if (!empty($startDate)) {
        $sql .= " AND hr.visit_date >= :start_date";
        $params['start_date'] = $startDate;
    }
    if (!empty($endDate)) {
        $sql .= " AND hr.visit_date <= :end_date";
        $params['end_date'] = $endDate;
    }
    if (!empty($serviceFilterId)) {
        $sql .= " AND hr.service_id = :service_id";
        $params['service_id'] = $serviceFilterId;
    }
    
    $sql .= " ORDER BY hr.visit_date DESC, hr.record_id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Health records list fetch failed: " . $e->getMessage());
    $records = [];
    $serviceList = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
        <div>
            <h2 class="page-title">Health Records & Consultations</h2>
            <p class="text-secondary mb-0">Browse and manage patient consultation history and clinical records.</p>
        </div>
        <?php if ($role === 'admin' || $role === 'staff'): ?>
            <div>
                <a href="<?= BASE_URL ?>health_records/add.php" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-medical-fill"></i>
                    <span>New Consultation Record</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filter Card -->
    <div class="card-custom mb-4">
        <div class="card-custom-body p-3">
            <form method="GET" action="<?= BASE_URL ?>health_records/list.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label small fw-bold text-secondary">From Visit Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label small fw-bold text-secondary">To Visit Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-4">
                    <label for="service_id" class="form-label small fw-bold text-secondary">Service Category</label>
                    <select name="service_id" id="service_id" class="form-select">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($serviceList as $s): ?>
                            <option value="<?= $s['service_id'] ?>" <?= $serviceFilterId == $s['service_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['service_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-teal w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-filter"></i>
                        <span>Apply</span>
                    </button>
                    <?php if (!empty($startDate) || !empty($endDate) || !empty($serviceFilterId)): ?>
                        <a href="<?= BASE_URL ?>health_records/list.php" class="btn btn-outline-secondary py-2" title="Clear Filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Health Records Table Card -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-journal-medical"></i> Clinic Log Entries</h3>
        </div>
        
        <div class="card-custom-body">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle" id="recordsTable">
                    <thead>
                        <tr>
                            <th>Visit Date</th>
                            <th>Patient Name</th>
                            <th>Service Category</th>
                            <th>Chief Complaint</th>
                            <th>Attending Staff</th>
                            <th class="text-center" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <?php
                                $patientName = htmlspecialchars($r['patient_last'] . ', ' . $r['patient_first'] . ($r['patient_middle'] ? ' ' . substr($r['patient_middle'], 0, 1) . '.' : '') . ($r['patient_suffix'] ? ' ' . $r['patient_suffix'] : ''));
                                $staffName = htmlspecialchars(($r['staff_first'] ?? '') . ' ' . ($r['staff_last'] ?? ''));
                                if (trim($staffName) === '') {
                                    $staffName = 'N/A';
                                }
                                $complaintShort = htmlspecialchars($r['chief_complaint'] ?? '');
                                if (strlen($complaintShort) > 60) {
                                    $complaintShort = substr($complaintShort, 0, 57) . '...';
                                }
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark"><?= date('Y-m-d', strtotime($r['visit_date'])) ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>patients/view.php?id=<?= $r['patient_id'] ?>" class="text-decoration-none fw-bold text-primary">
                                        <?= $patientName ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border fw-bold"><?= htmlspecialchars($r['service_name'] ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <span class="text-secondary small"><?= $complaintShort ?: '<em class="text-muted">None</em>' ?></span>
                                </td>
                                <td>
                                    <span class="text-secondary"><?= $staffName ?></span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- View Details link (available for all roles) -->
                                        <a href="<?= BASE_URL ?>health_records/view.php?id=<?= $r['record_id'] ?>" 
                                           class="btn btn-sm btn-outline-info border-0 p-1" 
                                           title="View Details">
                                            <i class="bi bi-eye-fill fs-5"></i>
                                        </a>

                                        <!-- Edit Details link (Admin & Staff only) -->
                                        <?php if ($role === 'admin' || $role === 'staff'): ?>
                                            <a href="<?= BASE_URL ?>health_records/edit.php?id=<?= $r['record_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary border-0 p-1" 
                                               title="Modify Record">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Archive Action Button (Admin only) -->
                                        <?php if ($role === 'admin'): ?>
                                            <button class="btn btn-sm btn-outline-danger border-0 p-1 archive-record-btn" 
                                                    data-id="<?= $r['record_id'] ?>" 
                                                    data-patient="<?= htmlspecialchars($patientName) ?>"
                                                    data-date="<?= date('Y-m-d', strtotime($r['visit_date'])) ?>"
                                                    title="Archive Record">
                                                <i class="bi bi-archive-fill fs-5"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
    // Initialize DataTable
    if ($.fn.DataTable) {
        $('#recordsTable').DataTable({
            responsive: true,
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 5 } // Disable sorting on action column
            ],
            order: [[0, 'desc']] // Sort by Visit Date descending initially
        });
    }

    // Admin Confirm Archive Dialog
    const archiveBtnList = document.querySelectorAll('.archive-record-btn');
    if (archiveBtnList.length > 0) {
        archiveBtnList.forEach(button => {
            button.addEventListener('click', function() {
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
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

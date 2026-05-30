<?php
// appointments/list.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Appointments Directory';
$active_menu = 'appointments';

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
$filterDate = $_GET['appointment_date'] ?? '';
$filterStatus = $_GET['status'] ?? '';

try {
    $sql = "
        SELECT 
            a.appointment_id, 
            a.patient_id, 
            a.service_id, 
            a.appointment_date, 
            a.appointment_time, 
            a.status, 
            a.reason,
            p.first_name, 
            p.middle_name, 
            p.last_name, 
            p.suffix,
            st.service_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        LEFT JOIN service_types st ON a.service_id = st.service_id 
        WHERE a.is_archived = 0 AND p.is_archived = 0
    ";
    
    $params = [];
    if (!empty($filterDate)) {
        $sql .= " AND a.appointment_date = :app_date";
        $params['app_date'] = $filterDate;
    }
    if (!empty($filterStatus)) {
        $sql .= " AND a.status = :status";
        $params['status'] = $filterStatus;
    }
    
    $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Appointments directory load failure: " . $e->getMessage());
    $appointments = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
        <div>
            <h2 class="page-title">Appointment Schedules</h2>
            <p class="text-secondary mb-0">Browse and manage patient check-ups and medical service bookings.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>appointments/add.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-calendar-plus-fill"></i>
                <span>Schedule Appointment</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card-custom mb-4">
        <div class="card-custom-body p-3">
            <form method="GET" action="<?= BASE_URL ?>appointments/list.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="appointment_date" class="form-label small fw-bold text-secondary">Specific Date</label>
                    <input type="date" name="appointment_date" id="appointment_date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label small fw-bold text-secondary">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">-- All Statuses --</option>
                        <option value="Scheduled" <?= $filterStatus === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="Completed" <?= $filterStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= $filterStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="No-Show" <?= $filterStatus === 'No-Show' ? 'selected' : '' ?>>No-Show</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-teal w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-filter"></i>
                        <span>Apply</span>
                    </button>
                    <?php if (!empty($filterDate) || !empty($filterStatus)): ?>
                        <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary py-2" title="Clear Filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointments Listing Table -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-calendar-event"></i> Appointments Directory</h3>
        </div>
        
        <div class="card-custom-body">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle" id="appointmentsTable">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Patient Name</th>
                            <th>Service Type</th>
                            <th>Reason / Details</th>
                            <th>Status</th>
                            <th class="text-center" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                            <?php
                                $patientName = htmlspecialchars($a['last_name'] . ', ' . $a['first_name'] . ($a['middle_name'] ? ' ' . substr($a['middle_name'], 0, 1) . '.' : '') . ($a['suffix'] ? ' ' . $a['suffix'] : ''));
                                $timeText = $a['appointment_time'] ? date('h:i A', strtotime($a['appointment_time'])) : 'N/A';
                                $reasonText = htmlspecialchars($a['reason'] ?? '');
                                if (strlen($reasonText) > 60) {
                                    $reasonText = substr($reasonText, 0, 57) . '...';
                                }

                                // Status Badge color styling
                                $statusClass = 'badge bg-secondary';
                                if ($a['status'] === 'Scheduled') $statusClass = 'badge bg-primary';
                                elseif ($a['status'] === 'Completed') $statusClass = 'badge bg-success';
                                elseif ($a['status'] === 'Cancelled') $statusClass = 'badge bg-danger';
                                elseif ($a['status'] === 'No-Show') $statusClass = 'badge bg-dark text-white';
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark d-block"><?= date('Y-m-d', strtotime($a['appointment_date'])) ?></span>
                                    <span class="text-secondary small"><i class="bi bi-clock me-1"></i> <?= $timeText ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>patients/view.php?id=<?= $a['patient_id'] ?>" class="text-decoration-none fw-bold text-primary">
                                        <?= $patientName ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border fw-bold"><?= htmlspecialchars($a['service_name'] ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <span class="text-secondary small"><?= $reasonText ?: '<em class="text-muted">None</em>' ?></span>
                                </td>
                                <td>
                                    <span class="<?= $statusClass ?> px-3 py-2 font-weight-bold" style="font-size: 12px;"><?= htmlspecialchars($a['status']) ?></span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Edit/Update link (Admin, Staff & BHW) -->
                                        <?php if ($role === 'admin' || $role === 'staff' || $role === 'bhw'): ?>
                                            <a href="<?= BASE_URL ?>appointments/edit.php?id=<?= $a['appointment_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary border-0 p-1" 
                                               title="Update Status / Details">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">No Actions</span>
                                        <?php endif; ?>

                                        <!-- Archive Button (Admin only) -->
                                        <?php if ($role === 'admin'): ?>
                                            <button class="btn btn-sm btn-outline-danger border-0 p-1 archive-appointment-btn" 
                                                    data-id="<?= $a['appointment_id'] ?>" 
                                                    data-patient="<?= htmlspecialchars($patientName) ?>"
                                                    data-date="<?= date('Y-m-d', strtotime($a['appointment_date'])) ?>"
                                                    title="Archive Appointment">
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

<!-- Hidden form for archiving appointments (Admin only) -->
<?php if ($role === 'admin'): ?>
    <form action="<?= BASE_URL ?>appointments/archive_process.php" method="POST" id="archiveAppointmentForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="appointment_id" id="archive_appointment_id" value="">
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    if ($.fn.DataTable) {
        $('#appointmentsTable').DataTable({
            responsive: true,
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 5 } // Disable sorting on action column
            ],
            order: [[0, 'asc']] // Sort by Date/Time ascending initially
        });
    }

    // Admin Confirm Archive Dialog
    const archiveBtnList = document.querySelectorAll('.archive-appointment-btn');
    if (archiveBtnList.length > 0) {
        archiveBtnList.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const patient = this.getAttribute('data-patient');
                const date = this.getAttribute('data-date');

                Swal.fire({
                    title: 'Archive Appointment?',
                    text: `You are about to soft-delete the appointment for '${patient}' scheduled on ${date}. An administrator can restore it later.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, archive it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('archive_appointment_id').value = id;
                        document.getElementById('archiveAppointmentForm').submit();
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

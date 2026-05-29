<?php
// admin/archived_records.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

$page_title = 'Archived Records Management';
$active_menu = 'archives';

// Load DataTables styles and scripts via CDN
$extra_css = ['https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css'];
$extra_js = [
    'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js'
];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

try {
    // 1. Fetch Archived Patients
    $patientStmt = $pdo->query("
        SELECT patient_id, first_name, last_name, birthdate, sex, purok, updated_at 
        FROM patients 
        WHERE is_archived = 1 
        ORDER BY updated_at DESC
    ");
    $archivedPatients = $patientStmt->fetchAll();

    // 2. Fetch Archived Appointments
    $appointmentStmt = $pdo->query("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.updated_at, 
               p.first_name, p.last_name, st.service_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        LEFT JOIN service_types st ON a.service_id = st.service_id 
        WHERE a.is_archived = 1 
        ORDER BY a.updated_at DESC
    ");
    $archivedAppointments = $appointmentStmt->fetchAll();

    // 3. Fetch Archived Queue Tickets
    $queueStmt = $pdo->query("
        SELECT q.queue_id, q.queue_number, q.queue_date, q.status, q.created_at, 
               p.first_name, p.last_name, st.service_name 
        FROM queue q 
        JOIN patients p ON q.patient_id = p.patient_id 
        LEFT JOIN service_types st ON q.service_id = st.service_id 
        WHERE q.is_archived = 1 
        ORDER BY q.created_at DESC
    ");
    $archivedQueue = $queueStmt->fetchAll();

    // 4. Fetch Archived Health Records
    $hrStmt = $pdo->query("
        SELECT hr.record_id, hr.visit_date, hr.updated_at, hr.chief_complaint, 
               p.first_name, p.last_name, st.service_name 
        FROM health_records hr 
        JOIN patients p ON hr.patient_id = p.patient_id 
        LEFT JOIN service_types st ON hr.service_id = st.service_id 
        WHERE hr.is_archived = 1 
        ORDER BY hr.updated_at DESC
    ");
    $archivedHealthRecords = $hrStmt->fetchAll();

} catch (Exception $e) {
    error_log("Archived records loading failure: " . $e->getMessage());
    $archivedPatients = [];
    $archivedAppointments = [];
    $archivedQueue = [];
    $archivedHealthRecords = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Archived Records</h2>
            <p class="text-secondary mb-0">View and restore records that were previously soft-deleted/archived.</p>
        </div>
    </div>

    <!-- Bootstrap Tab Controls -->
    <div class="card-custom">
        <div class="card-custom-header p-0 border-bottom-0">
            <ul class="nav nav-tabs px-3 pt-3" id="archiveTabs" role="tablist" style="border-bottom: 1px solid var(--border-color);">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active font-weight-bold" id="patients-tab" data-bs-toggle="tab" data-bs-target="#patients-pane" type="button" role="tab" aria-controls="patients-pane" aria-selected="true">
                        <i class="bi bi-people-fill me-2 text-primary"></i> Patients (<?= count($archivedPatients) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-weight-bold" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments-pane" type="button" role="tab" aria-controls="appointments-pane" aria-selected="false">
                        <i class="bi bi-calendar-check-fill me-2 text-accent"></i> Appointments (<?= count($archivedAppointments) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-weight-bold" id="queue-tab" data-bs-toggle="tab" data-bs-target="#queue-pane" type="button" role="tab" aria-controls="queue-pane" aria-selected="false">
                        <i class="bi bi-ticket-detailed-fill me-2 text-warning"></i> Queue Tickets (<?= count($archivedQueue) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-weight-bold" id="health-records-tab" data-bs-toggle="tab" data-bs-target="#health-records-pane" type="button" role="tab" aria-controls="health-records-pane" aria-selected="false">
                        <i class="bi bi-file-earmark-medical-fill me-2 text-primary"></i> Health Records (<?= count($archivedHealthRecords) ?>)
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-custom-body p-4">
            <div class="tab-content" id="archiveTabsContent">
                
                <!-- Tab Pane 1: Patients -->
                <div class="tab-pane fade show active" id="patients-pane" role="tabpanel" aria-labelledby="patients-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle" id="archivedPatientsTable">
                            <thead>
                                <tr>
                                    <th>Patient Name</th>
                                    <th>Birthdate</th>
                                    <th>Sex</th>
                                    <th>Purok</th>
                                    <th>Date Archived</th>
                                    <th class="text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archivedPatients as $p): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></strong></td>
                                        <td><?= date('M d, Y', strtotime($p['birthdate'])) ?></td>
                                        <td><?= htmlspecialchars($p['sex']) ?></td>
                                        <td><?= htmlspecialchars($p['purok'] ?? 'N/A') ?></td>
                                        <td><span class="text-secondary"><?= date('M d, Y h:i A', strtotime($p['updated_at'])) ?></span></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-success restore-btn border-0 py-1" 
                                                    data-id="<?= $p['patient_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>"
                                                    data-type="patient"
                                                    title="Restore Patient Record">
                                                <i class="bi bi-arrow-counterclockwise fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Pane 2: Appointments -->
                <div class="tab-pane fade" id="appointments-pane" role="tabpanel" aria-labelledby="appointments-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle" id="archivedAppointmentsTable">
                            <thead>
                                <tr>
                                    <th>Patient Name</th>
                                    <th>Service Type</th>
                                    <th>Appointment Date</th>
                                    <th>Time</th>
                                    <th>Original Status</th>
                                    <th>Date Archived</th>
                                    <th class="text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archivedAppointments as $a): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></strong></td>
                                        <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($a['service_name'] ?? 'General') ?></span></td>
                                        <td><?= date('M d, Y', strtotime($a['appointment_date'])) ?></td>
                                        <td><?= $a['appointment_time'] ? date('h:i A', strtotime($a['appointment_time'])) : 'N/A' ?></td>
                                        <td>
                                            <?php
                                                $statusClass = 'badge bg-secondary';
                                                if ($a['status'] === 'Scheduled') $statusClass = 'badge bg-primary';
                                                elseif ($a['status'] === 'Completed') $statusClass = 'badge bg-success';
                                                elseif ($a['status'] === 'No-Show') $statusClass = 'badge bg-danger';
                                            ?>
                                            <span class="<?= $statusClass ?>"><?= htmlspecialchars($a['status']) ?></span>
                                        </td>
                                        <td><span class="text-secondary"><?= date('M d, Y h:i A', strtotime($a['updated_at'])) ?></span></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-success restore-btn border-0 py-1" 
                                                    data-id="<?= $a['appointment_id'] ?>" 
                                                    data-name="Appointment for <?= htmlspecialchars($a['first_name']) ?>"
                                                    data-type="appointment"
                                                    title="Restore Appointment">
                                                <i class="bi bi-arrow-counterclockwise fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Pane 3: Queue Tickets -->
                <div class="tab-pane fade" id="queue-pane" role="tabpanel" aria-labelledby="queue-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle" id="archivedQueueTable">
                            <thead>
                                <tr>
                                    <th>Ticket No.</th>
                                    <th>Patient Name</th>
                                    <th>Service Type</th>
                                    <th>Queue Date</th>
                                    <th>Original Status</th>
                                    <th>Date Archived</th>
                                    <th class="text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archivedQueue as $q): ?>
                                    <tr>
                                        <td><strong class="text-primary">#<?= str_pad($q['queue_number'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td><?= htmlspecialchars($q['first_name'] . ' ' . $q['last_name']) ?></td>
                                        <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($q['service_name'] ?? 'General') ?></span></td>
                                        <td><?= date('M d, Y', strtotime($q['queue_date'])) ?></td>
                                        <td>
                                            <?php
                                                $statusClass = 'badge bg-secondary';
                                                if ($q['status'] === 'Waiting') $statusClass = 'badge bg-warning text-dark';
                                                elseif ($q['status'] === 'Serving') $statusClass = 'badge bg-info';
                                                elseif ($q['status'] === 'Served') $statusClass = 'badge bg-success';
                                                elseif ($q['status'] === 'No-Show') $statusClass = 'badge bg-danger';
                                            ?>
                                            <span class="<?= $statusClass ?>"><?= htmlspecialchars($q['status']) ?></span>
                                        </td>
                                        <td><span class="text-secondary"><?= date('M d, Y h:i A', strtotime($q['created_at'])) ?></span></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-success restore-btn border-0 py-1" 
                                                    data-id="<?= $q['queue_id'] ?>" 
                                                    data-name="Queue ticket #<?= str_pad($q['queue_number'], 3, '0', STR_PAD_LEFT) ?>"
                                                    data-type="queue"
                                                    title="Restore Queue Ticket">
                                                <i class="bi bi-arrow-counterclockwise fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                </div>

                <!-- Tab Pane 4: Health Records -->
                <div class="tab-pane fade" id="health-records-pane" role="tabpanel" aria-labelledby="health-records-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle" id="archivedHealthRecordsTable">
                            <thead>
                                <tr>
                                    <th>Patient Name</th>
                                    <th>Service Type</th>
                                    <th>Visit Date</th>
                                    <th>Chief Complaint</th>
                                    <th>Date Archived</th>
                                    <th class="text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archivedHealthRecords as $h): ?>
                                    <?php
                                        $complaintShort = htmlspecialchars($h['chief_complaint'] ?? '');
                                        if (strlen($complaintShort) > 60) {
                                            $complaintShort = substr($complaintShort, 0, 57) . '...';
                                        }
                                    ?>
                                    <tr>
                                        <td><strong class="text-dark"><?= htmlspecialchars($h['first_name'] . ' ' . $h['last_name']) ?></strong></td>
                                        <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($h['service_name'] ?? 'General') ?></span></td>
                                        <td><?= date('Y-m-d', strtotime($h['visit_date'])) ?></td>
                                        <td><span class="text-secondary small"><?= $complaintShort ?: '<em class="text-muted">None</em>' ?></span></td>
                                        <td><span class="text-secondary"><?= date('M d, Y h:i A', strtotime($h['updated_at'])) ?></span></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-success restore-btn border-0 py-1" 
                                                    data-id="<?= $h['record_id'] ?>" 
                                                    data-name="Consultation for <?= htmlspecialchars($h['first_name'] . ' ' . $h['last_name']) ?>"
                                                    data-type="health_record"
                                                    title="Restore Consultation Record">
                                                <i class="bi bi-arrow-counterclockwise fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- Hidden form for restoration processing -->
<form action="<?= BASE_URL ?>admin/archive_process.php" method="POST" id="restoreForm" style="display: none;">
    <input type="hidden" name="action" value="restore">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="record_id" id="restore_record_id" value="">
    <input type="hidden" name="type" id="restore_type" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTables for each tab
    const dataTableOptions = {
        responsive: true,
        pageLength: 10,
        columnDefs: [
            { orderable: false, targets: 5 } // Disable sorting on action column
        ],
        order: [[4, 'desc']] // Sort by date archived descending
    };

    if ($.fn.DataTable) {
        $('#archivedPatientsTable').DataTable(dataTableOptions);
        
        // Appointment options adjustment (columns is 6 targets index 6 for actions)
        $('#archivedAppointmentsTable').DataTable({
            ...dataTableOptions,
            columnDefs: [{ orderable: false, targets: 6 }],
            order: [[5, 'desc']]
        });
        
        // Queue options adjustment
        $('#archivedQueueTable').DataTable({
            ...dataTableOptions,
            columnDefs: [{ orderable: false, targets: 6 }],
            order: [[5, 'desc']]
        });

        // Health Records options adjustment
        $('#archivedHealthRecordsTable').DataTable({
            ...dataTableOptions,
            columnDefs: [{ orderable: false, targets: 5 }],
            order: [[4, 'desc']]
        });
    }

    // 2. Restore Button trigger
    document.querySelectorAll('.restore-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const type = this.getAttribute('data-type'); // patient, appointment, queue

            Swal.fire({
                title: 'Restore Record?',
                text: `You are about to restore the archived record for: '${name}'. It will reappear in the active modules list.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28A745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('restore_record_id').value = id;
                    document.getElementById('restore_type').value = type;
                    document.getElementById('restoreForm').submit();
                }
            });
        });
    });
});
</script>

<?php
// Load SweetAlert session alerts
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

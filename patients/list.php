<?php
// patients/list.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Patient Directory';
$active_menu = 'patients';

// Load DataTables styles and scripts via CDN
$extra_css = ['https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css'];
$extra_js = [
    'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js'
];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$role = $_SESSION['role'] ?? 'bhw';

try {
    // Select all non-archived patients ordered by last name
    $stmt = $pdo->query("
        SELECT patient_id, first_name, middle_name, last_name, suffix, birthdate, sex, contact_number, purok, created_at 
        FROM patients 
        WHERE is_archived = 0 
        ORDER BY last_name ASC, first_name ASC
    ");
    $patients = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Patients list fetch failed: " . $e->getMessage());
    $patients = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Sync Alert Indicator -->
    <div id="list-sync-indicator" class="alert alert-info d-none align-items-center justify-content-between shadow-sm rounded-3 py-3 px-4 mb-4" role="alert">
        <div class="d-flex align-items-center gap-3">
            <div class="spinner-grow text-info spinner-grow-sm" role="status" style="animation-duration: 1.5s;"></div>
            <div>
                <strong id="list-sync-count-label">You have 0 pending offline registrations.</strong>
                <span class="text-secondary small d-block">Please sync these locally saved records to the central database.</span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>patients/register_offline.php" class="btn btn-teal px-4 py-2 d-flex align-items-center gap-2">
            <i class="bi bi-cloud-upload-fill"></i>
            <span>Go to Sync Dashboard</span>
        </a>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Patient Directory</h2>
            <p class="text-secondary mb-0">Manage and browse medical profiles of Sinalhan residents.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>patients/register.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-person-plus-fill"></i>
                <span>Register Patient</span>
            </a>
        </div>
    </div>

    <!-- Patients Directory Card -->
    <div class="card-custom">
        <div class="card-custom-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h3 class="card-custom-title"><i class="bi bi-people-fill"></i> Registered Sinalhan Patients</h3>
            
            <!-- Dynamic Purok filter dropdown -->
            <div class="d-flex align-items-center gap-2" style="min-width: 250px;">
                <label for="purokFilter" class="form-label mb-0 text-secondary text-nowrap font-weight-bold" style="font-size: 13px;">Filter by Purok:</label>
                <select id="purokFilter" class="form-select form-select-sm">
                    <option value="">-- All Puroks --</option>
                    <option value="Purok 1">Purok 1</option>
                    <option value="Purok 2">Purok 2</option>
                    <option value="Purok 3">Purok 3</option>
                    <option value="Purok 4">Purok 4</option>
                    <option value="Purok 5">Purok 5</option>
                    <option value="Purok 6">Purok 6</option>
                    <option value="Purok 7">Purok 7</option>
                    <option value="Purok 8">Purok 8</option>
                    <option value="Purok 9">Purok 9</option>
                    <option value="Purok 10">Purok 10</option>
                    <option value="Zone 1">Zone 1</option>
                    <option value="Zone 2">Zone 2</option>
                    <option value="Zone 3">Zone 3</option>
                </select>
            </div>
        </div>
        
        <div class="card-custom-body">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle" id="patientsTable">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Sex</th>
                            <th>Age</th>
                            <th>Birthdate</th>
                            <th>Purok / Zone</th>
                            <th>Contact No.</th>
                            <th class="text-center" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $p): ?>
                            <?php
                                $fullName = htmlspecialchars($p['last_name'] . ', ' . $p['first_name'] . ($p['middle_name'] ? ' ' . substr($p['middle_name'], 0, 1) . '.' : '') . ($p['suffix'] ? ' ' . $p['suffix'] : ''));
                                
                                // Compute Age dynamically
                                $dob = new DateTime($p['birthdate']);
                                $now = new DateTime();
                                $age = $now->diff($dob)->y;
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL ?>patients/view.php?id=<?= $p['patient_id'] ?>" class="text-decoration-none fw-bold text-primary">
                                        <?= $fullName ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['sex']) ?></span>
                                </td>
                                <td><strong><?= $age ?></strong> yrs</td>
                                <td><span class="text-secondary"><?= date('M d, Y', strtotime($p['birthdate'])) ?></span></td>
                                <td><span class="text-secondary"><?= htmlspecialchars($p['purok'] ?? 'N/A') ?></span></td>
                                <td><?= htmlspecialchars($p['contact_number'] ?? 'N/A') ?></td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- View Profile link (available for all roles) -->
                                        <a href="<?= BASE_URL ?>patients/view.php?id=<?= $p['patient_id'] ?>" 
                                           class="btn btn-sm btn-outline-info border-0 p-1" 
                                           title="View Patient Profile">
                                            <i class="bi bi-eye-fill fs-5"></i>
                                        </a>

                                        <!-- Edit Details link (Admin & Staff only) -->
                                        <?php if ($role === 'admin' || $role === 'staff'): ?>
                                            <a href="<?= BASE_URL ?>patients/edit.php?id=<?= $p['patient_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary border-0 p-1" 
                                               title="Modify Information">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Archive Action Button (Admin only) -->
                                        <?php if ($role === 'admin'): ?>
                                            <button class="btn btn-sm btn-outline-danger border-0 p-1 archive-patient-btn" 
                                                    data-id="<?= $p['patient_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>"
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

<!-- Hidden form for archiving patients (Admin only) -->
<?php if ($role === 'admin'): ?>
    <form action="<?= BASE_URL ?>patients/archive_process.php" method="POST" id="archivePatientForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="patient_id" id="archive_patient_id" value="">
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let table = null;

    // 1. Initialize DataTable
    if ($.fn.DataTable) {
        table = $('#patientsTable').DataTable({
            responsive: true,
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 6 } // Disable sorting on action column
            ],
            order: [[0, 'asc']] // Sort by last name alphabetically initially
        });

        // 2. DataTable Custom Purok Filter Dropdown
        $('#purokFilter').on('change', function() {
            const val = $.fn.dataTable.util.escapeRegex($(this).val());
            // Filter Purok column (index 4) with exact match or clear search
            table.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
        });
    }

    // 3. Admin Confirm Archive Dialog
    const archiveBtnList = document.querySelectorAll('.archive-patient-btn');
    if (archiveBtnList.length > 0) {
        archiveBtnList.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Archive Patient Record?',
                    text: `You are about to soft-delete the record for '${name}'. Medical history will be archived. An administrator can restore it later.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, archive it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('archive_patient_id').value = id;
                        document.getElementById('archivePatientForm').submit();
                    }
                });
            });
        });
    }

    // PWA Offline check for patient list
    const DB_NAME = 'sinalhan_offline_db';
    const DB_VERSION = 1;
    const STORE_NAME = 'patients';

    const dbRequest = indexedDB.open(DB_NAME, DB_VERSION);
    dbRequest.onsuccess = function(event) {
        const db = event.target.result;
        if (db.objectStoreNames.contains(STORE_NAME)) {
            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const countRequest = store.count();

            countRequest.onsuccess = function() {
                const count = countRequest.result;
                if (count > 0) {
                    const indicator = document.getElementById('list-sync-indicator');
                    indicator.classList.remove('d-none');
                    indicator.classList.add('d-flex');
                    document.getElementById('list-sync-count-label').textContent = `You have ${count} pending offline patient registration(s) ready to sync.`;
                }
            };
        }
    };
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

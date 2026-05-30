<?php
// queue/manage.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Queue Manager';
$active_menu = 'queue_manage';

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$role = $_SESSION['role'] ?? 'staff';

try {
    // Query today's active queue tickets
    $stmt = $pdo->query("
        SELECT 
            q.*, 
            p.first_name, 
            p.middle_name, 
            p.last_name, 
            p.suffix,
            st.service_name 
        FROM queue q 
        JOIN patients p ON q.patient_id = p.patient_id 
        LEFT JOIN service_types st ON q.service_id = st.service_id 
        WHERE q.is_archived = 0 AND q.queue_date = CURDATE()
        ORDER BY q.queue_number ASC
    ");
    $allTickets = $stmt->fetchAll();

    // Group tickets by status
    $waitingList = [];
    $servingList = [];
    $completedList = [];

    $countTotal = 0;
    $countWaiting = 0;
    $countServing = 0;
    $countServed = 0;

    foreach ($allTickets as $t) {
        $countTotal++;
        if ($t['status'] === 'Waiting') {
            $waitingList[] = $t;
            $countWaiting++;
        } elseif ($t['status'] === 'Serving') {
            $servingList[] = $t;
            $countServing++;
        } else {
            $completedList[] = $t;
            if ($t['status'] === 'Served') {
                $countServed++;
            }
        }
    }

} catch (Exception $e) {
    error_log("Failed to load daily queues: " . $e->getMessage());
    $waitingList = [];
    $servingList = [];
    $completedList = [];
    $countTotal = 0;
    $countWaiting = 0;
    $countServing = 0;
    $countServed = 0;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
        <div>
            <h2 class="page-title">Daily Queue Management Board</h2>
            <p class="text-secondary mb-0">Track waiting rooms list, call patient files, and update visit completion details.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <!-- Auto Refresh Switch -->
            <div class="form-check form-switch mb-0 bg-light p-2 px-3 rounded border">
                <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="autoRefreshSwitch" checked>
                <label class="form-check-label small fw-bold text-secondary" for="autoRefreshSwitch">Auto-Refresh (15s)</label>
            </div>
            
            <a href="<?= BASE_URL ?>queue/assign.php" class="btn btn-outline-primary d-flex align-items-center gap-2">
                <i class="bi bi-ticket-perforated"></i>
                <span>Issue Ticket</span>
            </a>
            <button onclick="window.location.reload();" class="btn btn-teal" title="Reload Board">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <!-- Live Counters Row -->
    <div class="row g-3 mb-4">
        <!-- Waiting counter -->
        <div class="col-6 col-md-3">
            <div class="card-custom bg-white border-start border-warning border-4 p-3 shadow-sm mb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small d-block font-weight-bold">WAITING IN LINE</span>
                        <h3 class="fw-bold text-warning mb-0 fs-2 mt-1"><?= $countWaiting ?></h3>
                    </div>
                    <i class="bi bi-people text-warning fs-3 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Serving counter -->
        <div class="col-6 col-md-3">
            <div class="card-custom bg-white border-start border-info border-4 p-3 shadow-sm mb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small d-block font-weight-bold">CURRENTLY SERVING</span>
                        <h3 class="fw-bold text-info mb-0 fs-2 mt-1"><?= $countServing ?></h3>
                    </div>
                    <i class="bi bi-hospital text-info fs-3 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Completed counter -->
        <div class="col-6 col-md-3">
            <div class="card-custom bg-white border-start border-success border-4 p-3 shadow-sm mb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small d-block font-weight-bold">SERVED TODAY</span>
                        <h3 class="fw-bold text-success mb-0 fs-2 mt-1"><?= $countServed ?></h3>
                    </div>
                    <i class="bi bi-check-circle text-success fs-3 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Total counter -->
        <div class="col-6 col-md-3">
            <div class="card-custom bg-white border-start border-primary border-4 p-3 shadow-sm mb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small d-block font-weight-bold">TOTAL ASSIGNED</span>
                        <h3 class="fw-bold text-primary mb-0 fs-2 mt-1"><?= $countTotal ?></h3>
                    </div>
                    <i class="bi bi-ticket-perforated-fill text-primary fs-3 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Kanban Grid Board -->
    <div class="row">
        
        <!-- Column 1: Waiting Queue -->
        <div class="col-lg-4 mb-4">
            <div class="bg-light p-3 rounded-3 border h-100" style="min-height: 500px;">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h4 class="fs-6 fw-bold mb-0 text-dark">
                        <span class="badge bg-warning text-dark me-2"><?= count($waitingList) ?></span> Waiting Queue
                    </h4>
                </div>

                <div class="d-flex flex-column gap-3" style="max-height: 700px; overflow-y: auto;">
                    <?php if (count($waitingList) > 0): ?>
                        <?php foreach ($waitingList as $t): ?>
                            <?php
                                $patName = htmlspecialchars($t['last_name'] . ', ' . $t['first_name'] . ($t['middle_name'] ? ' ' . substr($t['middle_name'], 0, 1) . '.' : '') . ($t['suffix'] ? ' ' . $t['suffix'] : ''));
                                $timeAssigned = date('h:i A', strtotime($t['created_at']));
                            ?>
                            <div class="card-custom shadow-sm mb-0 border-top border-warning border-3">
                                <div class="card-custom-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold text-primary fs-4 mb-0">#<?= str_pad($t['queue_number'], 3, '0', STR_PAD_LEFT) ?></h5>
                                        <span class="text-secondary small"><i class="bi bi-clock me-1"></i> <?= $timeAssigned ?></span>
                                    </div>
                                    <div class="fw-bold text-dark mb-1"><?= $patName ?></div>
                                    <span class="badge bg-light text-secondary border mb-3"><?= htmlspecialchars($t['service_name'] ?? 'General Consultation') ?></span>
                                    
                                    <form action="<?= BASE_URL ?>queue/manage_process.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="queue_id" value="<?= $t['queue_id'] ?>">
                                        <input type="hidden" name="action" value="serve">
                                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold d-flex align-items-center justify-content-center gap-1">
                                            <i class="bi bi-play-fill"></i> Call & Serve Patient
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-ticket fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <span class="small">No patients waiting.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Column 2: Now Serving -->
        <div class="col-lg-4 mb-4">
            <div class="p-3 rounded-3 border h-100 bg-white shadow-sm" style="min-height: 500px; border: 2px solid var(--primary-light) !important;">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h4 class="fs-6 fw-bold mb-0 text-primary">
                        <span class="badge bg-info text-white me-2"><?= count($servingList) ?></span> Now Serving
                    </h4>
                </div>

                <div class="d-flex flex-column gap-3">
                    <?php if (count($servingList) > 0): ?>
                        <?php foreach ($servingList as $t): ?>
                            <?php
                                $patName = htmlspecialchars($t['last_name'] . ', ' . $t['first_name'] . ($t['middle_name'] ? ' ' . $t['middle_name'] : '') . ($t['suffix'] ? ' ' . $t['suffix'] : ''));
                                $timeStart = date('h:i A', strtotime($t['serving_time']));
                                $servingMins = round((time() - strtotime($t['serving_time'])) / 60);
                                if ($servingMins < 0) $servingMins = 0;
                            ?>
                            <div class="card-custom shadow-sm mb-0 border-top border-info border-3" style="background-color: #f7fcfd;">
                                <div class="card-custom-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold text-info fs-3 mb-0">#<?= str_pad($t['queue_number'], 3, '0', STR_PAD_LEFT) ?></h5>
                                        <span class="text-secondary small"><i class="bi bi-clock-history me-1"></i> Calling: <?= $timeStart ?></span>
                                    </div>
                                    <div class="fw-bold text-dark fs-5 mb-1"><?= $patName ?></div>
                                    <span class="badge bg-light text-primary border mb-3 fw-bold"><?= htmlspecialchars($t['service_name'] ?? 'General Consultation') ?></span>
                                    <div class="small text-secondary mb-3"><i class="bi bi-hourglass-split me-1 text-info"></i> In-service for <?= $servingMins ?> minutes</div>
                                    
                                    <div class="d-flex gap-2">
                                        <!-- Complete action -->
                                        <form action="<?= BASE_URL ?>queue/manage_process.php" method="POST" class="flex-grow-1">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="queue_id" value="<?= $t['queue_id'] ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold d-flex align-items-center justify-content-center gap-1">
                                                <i class="bi bi-check-lg"></i> Complete
                                            </button>
                                        </form>
                                        <!-- No-Show action -->
                                        <form action="<?= BASE_URL ?>queue/manage_process.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="queue_id" value="<?= $t['queue_id'] ?>">
                                            <input type="hidden" name="action" value="noshow">
                                            <button type="submit" class="btn btn-outline-danger btn-sm fw-bold" title="Patient No-Show">
                                                No-Show
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-hospital fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <span class="small">No patients currently being served.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Column 3: Completed / No-Show Logs -->
        <div class="col-lg-4 mb-4">
            <div class="bg-light p-3 rounded-3 border h-100" style="min-height: 500px;">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h4 class="fs-6 fw-bold mb-0 text-dark">
                        <span class="badge bg-success text-white me-2"><?= count($completedList) ?></span> Completed & Logs
                    </h4>
                </div>

                <div class="d-flex flex-column gap-2" style="max-height: 700px; overflow-y: auto;">
                    <?php if (count($completedList) > 0): ?>
                        <?php foreach ($completedList as $t): ?>
                            <?php
                                $patName = htmlspecialchars($t['last_name'] . ', ' . $t['first_name'] . ($t['middle_name'] ? ' ' . substr($t['middle_name'], 0, 1) . '.' : '') . ($t['suffix'] ? ' ' . $t['suffix'] : ''));
                                $timeCompleted = $t['completed_time'] ? date('h:i A', strtotime($t['completed_time'])) : date('h:i A', strtotime($t['created_at']));
                                
                                $statusBadge = 'bg-success text-white';
                                if ($t['status'] === 'No-Show') {
                                    $statusBadge = 'bg-dark text-white';
                                }
                            ?>
                            <div class="p-3 bg-white rounded shadow-sm border d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-bold text-dark" style="font-size: 15px;">#<?= str_pad($t['queue_number'], 3, '0', STR_PAD_LEFT) ?></span>
                                        <span class="badge <?= $statusBadge ?> font-weight-bold" style="font-size: 10px;"><?= $t['status'] ?></span>
                                    </div>
                                    <div class="text-secondary small fw-bold" style="font-size: 13px;"><?= $patName ?></div>
                                    <small class="text-muted d-block" style="font-size: 11px;">Service: <?= htmlspecialchars($t['service_name'] ?? 'General') ?> | Ended: <?= $timeCompleted ?></small>
                                </div>

                                <!-- Soft-Delete archive for Admins -->
                                <?php if ($role === 'admin'): ?>
                                    <button class="btn btn-sm btn-outline-danger border-0 p-1 archive-queue-btn" 
                                            data-id="<?= $t['queue_id'] ?>" 
                                            data-ticket="#<?= str_pad($t['queue_number'], 3, '0', STR_PAD_LEFT) ?>"
                                            data-patient="<?= $patName ?>">
                                        <i class="bi bi-archive-fill fs-6"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-journal-text fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <span class="small">No logs recorded today.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Hidden form for archiving queue logs (Admin only) -->
<?php if ($role === 'admin'): ?>
    <form action="<?= BASE_URL ?>queue/archive_process.php" method="POST" id="archiveQueueForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="queue_id" id="archive_queue_id" value="">
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Auto Refresh board script logic
    const refreshSwitch = document.getElementById('autoRefreshSwitch');
    let refreshInterval = null;

    function startAutoRefresh() {
        refreshInterval = setInterval(function() {
            window.location.reload();
        }, 15000); // refresh every 15 seconds
    }

    if (refreshSwitch && refreshSwitch.checked) {
        startAutoRefresh();
    }

    if (refreshSwitch) {
        refreshSwitch.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                clearInterval(refreshInterval);
            }
        });
    }

    // 2. Admin Confirm Archive Dialog
    const archiveBtnList = document.querySelectorAll('.archive-queue-btn');
    if (archiveBtnList.length > 0) {
        archiveBtnList.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const ticket = this.getAttribute('data-ticket');
                const patient = this.getAttribute('data-patient');

                Swal.fire({
                    title: 'Archive Queue Ticket?',
                    text: `You are about to archive queue ticket ${ticket} for patient '${patient}'. An administrator can restore it later.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, archive it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('archive_queue_id').value = id;
                        document.getElementById('archiveQueueForm').submit();
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

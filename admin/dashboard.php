<?php
// admin/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only permission
require_role(['admin']);

// Define layout parameters
$page_title = 'System Dashboard';
$active_menu = 'dashboard';

// Include extra files specific to the dashboard page
$extra_css = ['assets/css/dashboard.css'];
$extra_js = ['assets/js/dashboard.js'];

// Connect DB to query recent activity logs
require_once __DIR__ . '/../config/database.php';
try {
    $pdo = Database::getInstance()->getConnection();
    
    // Fetch last 5 system activity logs
    $logStmt = $pdo->query("
        SELECT al.action, al.module, al.details, al.created_at, u.first_name, u.last_name 
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
        LIMIT 5
    ");
    $recentLogs = $logStmt->fetchAll();
} catch (Exception $e) {
    error_log("Dashboard logs fetch failure: " . $e->getMessage());
    $recentLogs = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Main Content wrapper -->
<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Barangay Sinalhan Health Center</h2>
            <p class="text-secondary mb-0">System Overview and Auditing Dashboard</p>
        </div>

    </div>

    <!-- Summary Metrics Cards Grid -->
    <div class="row g-4 mb-4">
        <!-- Card 1: Total Patients -->
        <div class="col-sm-6 col-lg-3">
            <a href="<?= BASE_URL ?>patients/list.php" class="metric-card patients-card">
                <div class="metric-details">
                    <h3>Total Patients</h3>
                    <div class="metric-value" id="total-patients-val">0</div>
                </div>
                <div class="metric-icon-box">
                    <i class="bi bi-people-fill"></i>
                </div>
            </a>
        </div>

        <!-- Card 2: Today's Appointments -->
        <div class="col-sm-6 col-lg-3">
            <a href="<?= BASE_URL ?>appointments/list.php" class="metric-card appointments-card">
                <div class="metric-details">
                    <h3>Today's Appointments</h3>
                    <div class="metric-value" id="today-appointments-val">0</div>
                </div>
                <div class="metric-icon-box">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
            </a>
        </div>

        <!-- Card 3: Today's Queue -->
        <div class="col-sm-6 col-lg-3">
            <a href="<?= BASE_URL ?>queue/manage.php" class="metric-card queue-card">
                <div class="metric-details">
                    <h3>Today's Queue</h3>
                    <div class="metric-value" id="today-queue-val">0</div>
                </div>
                <div class="metric-icon-box">
                    <i class="bi bi-ticket-detailed-fill"></i>
                </div>
            </a>
        </div>

        <!-- Card 4: Active Online Users -->
        <div class="col-sm-6 col-lg-3">
            <div class="metric-card active-users-card">
                <div class="metric-details">
                    <h3>Online Sessions</h3>
                    <div class="metric-value" id="active-users-val">0</div>
                </div>
                <div class="metric-icon-box">
                    <i class="bi bi-person-workspace"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphs Section -->
    <div class="row">
        <!-- Line Chart: Patient registrations -->
        <div class="col-lg-7 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-graph-up text-primary"></i> Patient Registration Growth</h3>
                    <span class="badge bg-light text-dark">Last 6 Months</span>
                </div>
                <div class="card-custom-body">
                    <div class="chart-container-custom">
                        <canvas id="patientGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doughnut Chart: Appointments by service -->
        <div class="col-lg-5 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-pie-chart text-primary"></i> Popular Service Categories</h3>
                </div>
                <div class="card-custom-body">
                    <div class="chart-container-custom">
                        <canvas id="appointmentsServiceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Dashboard Row: Queue & System Logs -->
    <div class="row">
        <!-- Bar Chart: Daily Queue Volume -->
        <div class="col-lg-4 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-bar-chart-steps text-primary"></i> Daily Queue Volume</h3>
                    <span class="badge bg-light text-dark">7 Days</span>
                </div>
                <div class="card-custom-body">
                    <div class="chart-container-custom">
                        <canvas id="queueVolumeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit log panel -->
        <div class="col-lg-5 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-journal-text text-primary"></i> Recent Audit Logs</h3>
                    <a href="<?= BASE_URL ?>admin/activity_log.php" class="btn btn-sm btn-outline-primary py-1 px-2 border-0">View All</a>
                </div>
                <div class="card-custom-body">
                    <ul class="activity-feed">
                        <?php if (!empty($recentLogs)): ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <?php
                                    $markerClass = 'module-patient';
                                    $mod = strtolower($log['module']);
                                    if (strpos($mod, 'patient') !== false) $markerClass = 'module-patient';
                                    elseif (strpos($mod, 'health') !== false) $markerClass = 'module-health';
                                    elseif (strpos($mod, 'appoint') !== false) $markerClass = 'module-appointment';
                                    elseif (strpos($mod, 'queue') !== false) $markerClass = 'module-queue';
                                    elseif (strpos($mod, 'auth') !== false) $markerClass = 'module-auth';
                                    
                                    $userStr = $log['first_name'] ? htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) : 'System';
                                    $timePassed = date('M d, h:i A', strtotime($log['created_at']));
                                ?>
                                <li class="activity-feed-item">
                                    <div class="activity-marker <?= $markerClass ?>"></div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?= htmlspecialchars($log['action']) ?></div>
                                        <div class="activity-details">
                                            By <strong><?= $userStr ?></strong> in <em><?= htmlspecialchars($log['module']) ?></em>. 
                                            <?= $log['details'] ? '<br><small class="text-secondary">' . htmlspecialchars($log['details']) . '</small>' : '' ?>
                                        </div>
                                        <div class="activity-time"><i class="bi bi-clock"></i> <?= $timePassed ?></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="py-4 text-center text-muted">No activity logs recorded yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Online sessions list -->
        <div class="col-lg-3 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-person-workspace text-primary"></i> Active Staff</h3>
                </div>
                <div class="card-custom-body">
                    <ul class="online-users-list" id="online-users-list">
                        <!-- Loaded dynamically via AJAX -->
                        <li class="py-4 text-center text-muted">Loading online sessions...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</main>



<?php
// Load SweetAlert session alerts
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

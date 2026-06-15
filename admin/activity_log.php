<?php
// admin/activity_log.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

$page_title = 'System Activity Logs / Audit Trail';
$active_menu = 'activity_log';

// Load DataTables styles and scripts via CDN
$extra_css = ['https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css'];
$extra_js = [
    'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js'
];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

// Fetch filter variables from GET
$filterUser = $_GET['user_id'] ?? '';
$filterModule = $_GET['module'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

try {
    // 1. Fetch all users for the filter dropdown
    $userListStmt = $pdo->query("SELECT user_id, username, first_name, last_name FROM users ORDER BY username ASC");
    $usersDropdown = $userListStmt->fetchAll();

    // 2. Build dynamic search query for activity logs
    $sql = "
        SELECT al.log_id, al.action, al.module, al.record_id, al.details, al.ip_address, al.created_at, 
               u.username, u.first_name, u.last_name 
        FROM activity_log al 
        LEFT JOIN users u ON al.user_id = u.user_id 
        WHERE 1=1
    ";
    $params = [];

    if (!empty($filterUser)) {
        $sql .= " AND al.user_id = ?";
        $params[] = (int)$filterUser;
    }

    if (!empty($filterModule)) {
        $sql .= " AND al.module = ?";
        $params[] = $filterModule;
    }

    if (!empty($filterDateFrom)) {
        $sql .= " AND al.created_at >= ?";
        $params[] = $filterDateFrom . ' 00:00:00';
    }

    if (!empty($filterDateTo)) {
        $sql .= " AND al.created_at <= ?";
        $params[] = $filterDateTo . ' 23:59:59';
    }

    $sql .= " ORDER BY al.created_at DESC LIMIT 500"; // Cap at 500 for database performance

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Activity log loading failed: " . $e->getMessage());
    $usersDropdown = [];
    $logs = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Activity Logs & Audit Trail</h2>
            <p class="text-secondary mb-0">System-wide record of all database modifications and user access.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card-custom mb-4">
        <div class="card-custom-body p-3">
            <form method="GET" action="<?= BASE_URL ?>admin/activity_log.php" class="row g-3 align-items-end">
                <!-- User filter -->
                <div class="col-md-3">
                    <label for="user_id" class="form-label font-weight-bold mb-1 small text-secondary">User Account</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">-- All Users --</option>
                        <?php foreach ($usersDropdown as $u): ?>
                            <option value="<?= $u['user_id'] ?>" <?= $filterUser == $u['user_id'] ? 'selected' : '' ?>>
                                @<?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Module filter -->
                <div class="col-md-3">
                    <label for="module" class="form-label font-weight-bold mb-1 small text-secondary">System Module</label>
                    <select name="module" id="module" class="form-select">
                        <option value="">-- All Modules --</option>
                        <option value="Patient Records" <?= $filterModule === 'Patient Records' ? 'selected' : '' ?>>Patient Records</option>
                        <option value="Health Records" <?= $filterModule === 'Health Records' ? 'selected' : '' ?>>Health Records</option>
                        <option value="Appointment" <?= $filterModule === 'Appointment' ? 'selected' : '' ?>>Appointment</option>
                        <option value="Queue" <?= $filterModule === 'Queue' ? 'selected' : '' ?>>Queue</option>
                        <option value="Admin" <?= $filterModule === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="Auth" <?= $filterModule === 'Auth' ? 'selected' : '' ?>>Auth</option>
                        <option value="System" <?= $filterModule === 'System' ? 'selected' : '' ?>>System</option>
                    </select>
                </div>

                <!-- Date from filter -->
                <div class="col-md-2">
                    <label for="date_from" class="form-label font-weight-bold mb-1 small text-secondary">Date From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filterDateFrom) ?>">
                </div>

                <!-- Date to filter -->
                <div class="col-md-2">
                    <label for="date_to" class="form-label font-weight-bold mb-1 small text-secondary">Date To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filterDateTo) ?>">
                </div>

                <!-- Action buttons -->
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-funnel-fill"></i>
                        <span>Filter</span>
                    </button>
                    <a href="<?= BASE_URL ?>admin/activity_log.php" class="btn btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center gap-2" title="Clear Filters">
                        <i class="bi bi-x-circle"></i>
                        <span>Clear</span>
                    </a>
                </div>
            </form>

            <?php if (!empty($filterUser) || !empty($filterModule) || !empty($filterDateFrom) || !empty($filterDateTo)): ?>
                <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-3 border-top">
                    <span class="text-secondary small fw-bold me-2">Active Filters:</span>
                    <?php if (!empty($filterUser)): 
                        $usernameVal = '';
                        foreach ($usersDropdown as $u) {
                            if ($u['user_id'] == $filterUser) {
                                $usernameVal = $u['username'];
                                break;
                            }
                        }
                    ?>
                        <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 11px;">
                            User: @<?= htmlspecialchars($usernameVal) ?> 
                            <a href="?<?= http_build_query(array_merge($_GET, ['user_id' => ''])) ?>" class="text-white ms-1"><i class="bi bi-x-circle"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($filterModule)): ?>
                        <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 11px;">
                            Module: <?= htmlspecialchars($filterModule) ?> 
                            <a href="?<?= http_build_query(array_merge($_GET, ['module' => ''])) ?>" class="text-white ms-1"><i class="bi bi-x-circle"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($filterDateFrom)): ?>
                        <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 11px;">
                            From: <?= htmlspecialchars($filterDateFrom) ?> 
                            <a href="?<?= http_build_query(array_merge($_GET, ['date_from' => ''])) ?>" class="text-white ms-1"><i class="bi bi-x-circle"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($filterDateTo)): ?>
                        <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 11px;">
                            To: <?= htmlspecialchars($filterDateTo) ?> 
                            <a href="?<?= http_build_query(array_merge($_GET, ['date_to' => ''])) ?>" class="text-white ms-1"><i class="bi bi-x-circle"></i></a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logs Table Card -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-shield-check"></i> Write-Only Audit Log Archive</h3>
            <span class="badge bg-light text-dark border">Capped at last 500 entries</span>
        </div>
        <div class="card-custom-body">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle" id="logsTable">
                    <thead>
                        <tr>
                            <th style="width: 170px;">Timestamp</th>
                            <th>User Account</th>
                            <th>Action Performed</th>
                            <th>System Module</th>
                            <th>Record ID</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td data-order="<?= htmlspecialchars($l['created_at']) ?>">
                                    <span class="text-secondary"><?= date('M d, Y h:i A', strtotime($l['created_at'])) ?></span>
                                </td>
                                <td>
                                    <?php if ($l['username']): ?>
                                        <strong class="text-primary">@<?= htmlspecialchars($l['username']) ?></strong>
                                        <div class="small text-secondary"><?= htmlspecialchars($l['first_name'] . ' ' . $l['last_name']) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">System Agent</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong class="text-dark"><?= htmlspecialchars($l['action']) ?></strong></td>
                                <td>
                                    <?php
                                        $badgeClass = 'bg-secondary';
                                        $mod = strtolower($l['module']);
                                        if (strpos($mod, 'patient') !== false) $badgeClass = 'bg-primary';
                                        elseif (strpos($mod, 'health') !== false) $badgeClass = 'bg-info';
                                        elseif (strpos($mod, 'appoint') !== false) $badgeClass = 'bg-success';
                                        elseif (strpos($mod, 'queue') !== false) $badgeClass = 'bg-warning text-dark';
                                        elseif (strpos($mod, 'auth') !== false) $badgeClass = 'bg-dark';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($l['module']) ?></span>
                                </td>
                                <td>
                                    <?= $l['record_id'] ? '<span class="badge bg-light text-dark border">#' . $l['record_id'] . '</span>' : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td class="text-secondary" style="max-width: 250px; font-size: 13px;">
                                    <?= htmlspecialchars($l['details'] ?? '-') ?>
                                </td>
                                <td><code class="text-secondary"><?= htmlspecialchars($l['ip_address'] ?? '0.0.0.0') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    if ($.fn.DataTable) {
        $('#logsTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[0, 'desc']] // Sort by Timestamp descending initially
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

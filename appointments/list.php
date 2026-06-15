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
$extra_css = [
    'https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css',
    'assets/css/dashboard.css'
];
$extra_js = [
    'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js'
];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$role = $_SESSION['role'] ?? 'bhw';

// Filters
$filterServiceId = $_GET['service_id'] ?? '';
$filterDate = $_GET['appointment_date'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$serviceList = [];
$todayStr = date('Y-m-d');

try {
    // Fetch active service types for filter dropdown
    $servicesStmt = $pdo->query("SELECT service_id, service_name FROM service_types WHERE is_active = 1 ORDER BY service_name ASC");
    $serviceList = $servicesStmt->fetchAll();

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
    if (!empty($filterServiceId)) {
        $sql .= " AND a.service_id = :service_id";
        $params['service_id'] = $filterServiceId;
    }
    if (!empty($filterDate)) {
        $sql .= " AND a.appointment_date = :app_date";
        $params['app_date'] = $filterDate;
    }
    if (!empty($filterStatus)) {
        $sql .= " AND a.status = :status";
        $params['status'] = $filterStatus;
    }
    
    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();

    // Query stats for headers
    $todayStr = date('Y-m-d');
    
    // Today's appointments count
    $stmtToday = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND is_archived = 0");
    $stmtToday->execute([$todayStr]);
    $statsToday = $stmtToday->fetchColumn();

    // Upcoming scheduled appointments (scheduled for today or in the future)
    $stmtUpcoming = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date >= ? AND status = 'Scheduled' AND is_archived = 0");
    $stmtUpcoming->execute([$todayStr]);
    $statsUpcoming = $stmtUpcoming->fetchColumn();

    // Completed appointments count
    $statsCompleted = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed' AND is_archived = 0")->fetchColumn();

    // Cancelled appointments count
    $statsCancelled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled' AND is_archived = 0")->fetchColumn();

    // Overdue appointments count (Scheduled but date is in the past)
    $stmtOverdue = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date < ? AND status = 'Scheduled' AND is_archived = 0");
    $stmtOverdue->execute([$todayStr]);
    $overdueCount = (int)$stmtOverdue->fetchColumn();

} catch (Exception $e) {
    error_log("Appointments directory load failure: " . $e->getMessage());
    $appointments = [];
    $statsToday = 0;
    $statsUpcoming = 0;
    $statsCompleted = 0;
    $statsCancelled = 0;
    $overdueCount = 0;
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

    <!-- Statistics Cards Header -->
    <div class="row g-4 mb-4">
        <!-- Card 1: Today's Appointments -->
        <div class="col-sm-6 col-lg-3">
            <div class="metric-card appointments-card shadow-sm border">
                <div class="metric-details">
                    <h3>Today's Bookings</h3>
                    <div class="metric-value"><?= $statsToday ?></div>
                </div>
                <div class="metric-icon-box">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
            </div>
        </div>

        <!-- Card 2: Upcoming Scheduled -->
        <div class="col-sm-6 col-lg-3">
            <div class="metric-card patients-card shadow-sm border">
                <div class="metric-details">
                    <h3>Upcoming Scheduled</h3>
                    <div class="metric-value"><?= $statsUpcoming ?></div>
                </div>
                <div class="metric-icon-box">
                    <i class="bi bi-calendar-week-fill"></i>
                </div>
            </div>
        </div>

        <!-- Card 3: Completed Total -->
        <div class="col-sm-6 col-lg-3">
            <div class="metric-card active-users-card shadow-sm border">
                <div class="metric-details">
                    <h3>Completed Total</h3>
                    <div class="metric-value"><?= $statsCompleted ?></div>
                </div>
                <div class="metric-icon-box">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>

        <!-- Card 4: Cancelled Total -->
        <div class="col-sm-6 col-lg-3">
            <div class="metric-card cancelled-card shadow-sm border">
                <div class="metric-details">
                    <h3>Cancelled Total</h3>
                    <div class="metric-value"><?= $statsCancelled ?></div>
                </div>
                <div class="metric-icon-box">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom Grid Calendar Styling */
        .calendar-grid-container {
            width: 100%;
            font-family: 'Inter', sans-serif;
        }
        .calendar-weekdays-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            color: var(--text-secondary);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .calendar-days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }
        .calendar-day-cell {
            aspect-ratio: 1.1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            background-color: var(--surface-light, #f8f9fa);
            border: 1px solid var(--border-color);
            padding-top: 4px;
        }
        .calendar-day-cell:hover {
            background-color: rgba(13, 115, 119, 0.08);
            border-color: var(--primary-light);
            color: var(--primary-color);
            transform: translateY(-1px);
        }
        .calendar-day-cell.other-month {
            color: var(--text-muted, #b5b5b5);
            opacity: 0.4;
            background-color: transparent;
            border-color: transparent;
        }
        .calendar-day-cell.today {
            border: 2px solid var(--primary-color);
            font-weight: 700;
            background-color: rgba(13, 115, 119, 0.03);
        }
        .calendar-day-cell.selected {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: #fff !important;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(13, 115, 119, 0.25);
        }
        .calendar-day-badge-container {
            display: flex;
            justify-content: center;
            gap: 3px;
            margin-top: 4px;
            height: 6px;
            width: 100%;
            overflow: hidden;
        }
        .calendar-day-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-scheduled { background-color: var(--bs-primary, #0d6efd); }
        .dot-completed { background-color: var(--bs-success, #198754); }
        .dot-cancelled { background-color: var(--bs-danger, #dc3545); }
        .dot-noshow { background-color: var(--bs-dark, #212529); }

        .calendar-day-cell.selected .calendar-day-dot {
            background-color: #fff !important;
        }

        .btn-icon-custom {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: var(--surface-white);
            transition: all 0.2s ease;
        }
        .btn-icon-custom:hover {
            background: var(--bg-light-hover, #f1f3f5);
            color: var(--primary-color);
        }

        /* Timeline sidebar appointments */
        .appt-timeline-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .appt-side-card {
            background-color: var(--surface-white, #fff);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--bs-primary, #0d6efd);
            border-radius: 8px;
            padding: 14px 16px;
            transition: all 0.2s ease;
            position: relative;
        }
        .appt-side-card:hover {
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .appt-side-card.status-scheduled { border-left-color: var(--bs-primary, #0d6efd); }
        .appt-side-card.status-overdue {
            border-left-color: var(--bs-warning, #ffc107) !important;
            background-color: rgba(255, 193, 7, 0.05);
        }
        .appt-side-card.status-completed { border-left-color: var(--bs-success, #198754); }
        .appt-side-card.status-cancelled { border-left-color: var(--bs-danger, #dc3545); }
        .appt-side-card.status-noshow { border-left-color: var(--bs-dark, #212529); }

        .metric-card.cancelled-card .metric-icon-box {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color, #dc3545);
        }
        .metric-card.cancelled-card::before {
            background-color: var(--danger-color, #dc3545);
        }
        .dot-status-marker {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            padding: 0 !important;
        }
    </style>
    <!-- Overdue Appointments Alert Banner -->
    <?php if ($overdueCount > 0): ?>
        <div class="alert alert-warning border-warning shadow-sm d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap flex-md-nowrap" role="alert">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                <div>
                    <h5 class="alert-heading mb-1 fw-bold">Past-Due Appointments Requiring Action</h5>
                    <p class="mb-0 text-dark">There are <strong><?= $overdueCount ?></strong> past appointments still marked as "Scheduled". Please review and update their status.</p>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-shrink-0">
                <button type="button" id="btnResolveManually" class="btn btn-sm btn-outline-dark fw-bold px-3 py-2">
                    <i class="bi bi-list-check me-1"></i> Resolve Manually
                </button>
                <?php if ($role === 'admin'): ?>
                    <form action="<?= BASE_URL ?>appointments/auto_noshow.php" method="POST" class="m-0" id="bulkNoShowForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger fw-bold px-3 py-2">
                            <i class="bi bi-calendar-x me-1"></i> Mark All as No-Show
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- View Toggle Row -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="btn-group shadow-sm" role="group" aria-label="View toggle">
            <button type="button" class="btn btn-outline-primary active" id="btnListView">
                <i class="bi bi-list-ul me-1"></i> List View
            </button>
            <button type="button" class="btn btn-outline-primary" id="btnCalendarView">
                <i class="bi bi-calendar3 me-1"></i> Calendar View
            </button>
        </div>
    </div>

    <!-- Filter Card Container -->
    <div id="listViewFilterCard">
        <div class="card-custom mb-4">
            <div class="card-custom-body p-3">
                <form id="filterForm" method="GET" action="<?= BASE_URL ?>appointments/list.php">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="service_id" class="form-label small fw-bold text-secondary">Service Type</label>
                            <select name="service_id" id="service_id" class="form-select">
                                <option value="">-- All Services --</option>
                                <?php foreach ($serviceList as $s): ?>
                                    <option value="<?= $s['service_id'] ?>" <?= $filterServiceId == $s['service_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['service_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-funnel-fill"></i>
                                <span>Filter</span>
                            </button>
                            <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center gap-2" title="Clear Filters">
                                <i class="bi bi-x-circle"></i>
                                <span>Clear</span>
                            </a>
                        </div>
                    </div>
                </form>

                <?php if (!empty($filterServiceId) || !empty($filterDate) || !empty($filterStatus)): ?>
                    <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-3 border-top">
                        <span class="text-secondary small fw-bold me-2">Active Filters:</span>
                        <?php if (!empty($filterServiceId)): 
                            $serviceName = '';
                            foreach ($serviceList as $s) {
                                if ($s['service_id'] == $filterServiceId) {
                                    $serviceName = $s['service_name'];
                                    break;
                                }
                            }
                        ?>
                            <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 11px;">
                                Service: <?= htmlspecialchars($serviceName) ?> 
                                <a href="?<?= http_build_query(array_merge($_GET, ['service_id' => ''])) ?>" class="text-white ms-1"><i class="bi bi-x-circle"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($filterDate)): ?>
                            <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 11px;">
                                Date: <?= htmlspecialchars($filterDate) ?> 
                                <a href="?<?= http_build_query(array_merge($_GET, ['appointment_date' => ''])) ?>" class="text-white ms-1"><i class="bi bi-x-circle"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($filterStatus)): ?>
                            <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 11px;">
                                Status: <?= htmlspecialchars($filterStatus) ?> 
                                <a href="?<?= http_build_query(array_merge($_GET, ['status' => ''])) ?>" class="text-white ms-1"><i class="bi bi-x-circle"></i></a>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Calendar View Row -->
    <div class="row g-4 mb-4" id="calendarViewRow" style="display: none;">
        <!-- Left Column: Calendar Card -->
        <div class="col-lg-5">
            <div class="card-custom h-100">
                <div class="card-custom-header d-flex justify-content-between align-items-center">
                    <h3 class="card-custom-title"><i class="bi bi-calendar3"></i> Interactive Calendar</h3>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-icon-custom p-0" id="prevMonthBtn" title="Previous Month">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="fw-bold px-2 text-dark" id="currentMonthYearLabel" style="min-width: 120px; text-align: center; font-size: 14px;"></span>
                        <button type="button" class="btn btn-sm btn-icon-custom p-0" id="nextMonthBtn" title="Next Month">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-custom-body">
                    <div class="calendar-grid-container">
                        <div class="calendar-weekdays-grid">
                            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                        </div>
                        <div class="calendar-days-grid" id="calendarDaysGrid">
                            <!-- JS populated days -->
                        </div>
                    </div>
                    <div class="calendar-legend-container mt-4 pt-3 border-top">
                        <div class="text-uppercase fw-bold text-secondary mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Legend</div>
                        <div class="row g-2">
                            <div class="col-6 d-flex align-items-center gap-2">
                                <span class="calendar-day-dot dot-scheduled" style="width: 8px; height: 8px;"></span>
                                <span class="text-dark fw-medium" style="font-size: 12px;">Scheduled</span>
                            </div>
                            <div class="col-6 d-flex align-items-center gap-2">
                                <span class="calendar-day-dot dot-completed" style="width: 8px; height: 8px;"></span>
                                <span class="text-dark fw-medium" style="font-size: 12px;">Completed</span>
                            </div>
                            <div class="col-6 d-flex align-items-center gap-2">
                                <span class="calendar-day-dot dot-cancelled" style="width: 8px; height: 8px;"></span>
                                <span class="text-dark fw-medium" style="font-size: 12px;">Cancelled</span>
                            </div>
                            <div class="col-6 d-flex align-items-center gap-2">
                                <span class="calendar-day-dot dot-noshow" style="width: 8px; height: 8px;"></span>
                                <span class="text-dark fw-medium" style="font-size: 12px;">No-Show</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Selected / Upcoming Timeline Card -->
        <div class="col-lg-7">
            <div class="card-custom h-100 d-flex flex-column">
                <div class="card-custom-header d-flex justify-content-between align-items-center">
                    <h3 class="card-custom-title" id="sidePanelTitle"><i class="bi bi-clock-history"></i> Upcoming Appointments</h3>
                    <button type="button" class="btn btn-sm btn-outline-primary d-none" id="resetDateFilterBtn">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Show All
                    </button>
                </div>
                <div class="card-custom-body overflow-auto flex-grow-1" style="max-height: 380px;" id="sidePanelContent">
                    <!-- JS populated timeline -->
                </div>
            </div>
        </div>
    </div>

    <!-- Appointments Listing Table -->
    <div id="listViewTableCard">
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
                                $isOverdue = ($a['status'] === 'Scheduled' && date('Y-m-d', strtotime($a['appointment_date'])) < $todayStr);
                                $statusClass = 'badge bg-secondary';
                                if ($a['status'] === 'Scheduled') $statusClass = 'badge bg-primary';
                                elseif ($a['status'] === 'Completed') $statusClass = 'badge bg-success';
                                elseif ($a['status'] === 'Cancelled') $statusClass = 'badge bg-danger';
                                elseif ($a['status'] === 'No-Show') $statusClass = 'badge bg-dark text-white';
                            ?>
                            <tr>
                                <td data-order="<?= htmlspecialchars($a['appointment_date'] . ' ' . ($a['appointment_time'] ?? '00:00:00')) ?>">
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
                                    <!-- Interactive inline status update dropdown -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm <?= $statusClass ?> dropdown-toggle px-3 py-2 font-weight-bold d-flex align-items-center gap-1 border-0" 
                                                type="button" 
                                                id="statusDropdown_<?= $a['appointment_id'] ?>" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false" 
                                                style="font-size: 11px;">
                                            <?= htmlspecialchars($a['status']) ?>
                                        </button>
                                        <ul class="dropdown-menu shadow" aria-labelledby="statusDropdown_<?= $a['appointment_id'] ?>">
                                            <li>
                                                <a class="dropdown-item change-status-btn d-flex align-items-center gap-2" href="#" data-id="<?= $a['appointment_id'] ?>" data-status="Scheduled">
                                                    <span class="badge bg-primary dot-status-marker"></span> Scheduled
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item change-status-btn d-flex align-items-center gap-2" href="#" data-id="<?= $a['appointment_id'] ?>" data-status="Completed">
                                                    <span class="badge bg-success dot-status-marker"></span> Completed
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item change-status-btn d-flex align-items-center gap-2" href="#" data-id="<?= $a['appointment_id'] ?>" data-status="Cancelled">
                                                    <span class="badge bg-danger dot-status-marker"></span> Cancelled
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item change-status-btn d-flex align-items-center gap-2" href="#" data-id="<?= $a['appointment_id'] ?>" data-status="No-Show">
                                                    <span class="badge bg-dark dot-status-marker"></span> No-Show
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php if ($isOverdue): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-danger text-white px-2 py-1 font-weight-bold" style="font-size: 10px;" title="This appointment was scheduled for a past date but not updated.">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- View Detail (All roles) -->
                                        <a href="<?= BASE_URL ?>appointments/view.php?id=<?= $a['appointment_id'] ?>" 
                                           class="btn btn-sm btn-outline-info border-0 p-1" 
                                           title="View Appointment Detail">
                                            <i class="bi bi-eye-fill fs-5"></i>
                                        </a>
                                        <!-- Edit/Update link (Admin, Staff & BHW) -->
                                        <?php if ($role === 'admin' || $role === 'staff' || $role === 'bhw'): ?>
                                            <a href="<?= BASE_URL ?>appointments/edit.php?id=<?= $a['appointment_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary border-0 p-1" 
                                               title="Update Status / Details">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </a>
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
    </div>
</main>

<!-- Hidden form for archiving appointments (Admin only) -->
<?php if ($role === 'admin'): ?>
    <form action="<?= BASE_URL ?>appointments/archive_process.php" method="POST" id="archiveAppointmentForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="appointment_id" id="archive_appointment_id" value="">
    </form>
<?php endif; ?>

<?php
// Generate a PHP structured array of appointments for JS mapping
$jsAppointments = [];
foreach ($appointments as $a) {
    $jsAppointments[] = [
        'id' => $a['appointment_id'],
        'patient_id' => $a['patient_id'],
        'patient_name' => htmlspecialchars($a['last_name'] . ', ' . $a['first_name'] . ($a['middle_name'] ? ' ' . substr($a['middle_name'], 0, 1) . '.' : '') . ($a['suffix'] ? ' ' . $a['suffix'] : '')),
        'service_name' => htmlspecialchars($a['service_name'] ?? 'N/A'),
        'date' => date('Y-m-d', strtotime($a['appointment_date'])),
        'time' => $a['appointment_time'] ? date('h:i A', strtotime($a['appointment_time'])) : 'N/A',
        'status' => $a['status'],
        'reason' => htmlspecialchars($a['reason'] ?? '')
    ];
}
?>

<script>
const appointmentsData = <?= json_encode($jsAppointments) ?>;
const userRole = <?= json_encode($role) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTable
    let appointmentsTable = null;
    if ($.fn.DataTable) {
        appointmentsTable = $('#appointmentsTable').DataTable({
            responsive: true,
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 5 } // Disable sorting on action column
            ],
            order: [[0, 'desc']] // Sort by Date/Time descending initially
        });
    }


    // 3. Event delegation for archiving appointments (Admin only)
    document.addEventListener('click', function(event) {
        const target = event.target.closest('.archive-appointment-btn');
        if (target) {
            const id = target.getAttribute('data-id');
            const patient = target.getAttribute('data-patient');
            const date = target.getAttribute('data-date');

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
        }
    });

    // 4. Calendar View Functionality
    const today = new Date();
    const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    
    let currentYear = today.getFullYear();
    let currentMonth = today.getMonth(); // 0-11
    let selectedDateStr = "";

    const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    const currentMonthYearLabel = document.getElementById('currentMonthYearLabel');
    const calendarDaysGrid = document.getElementById('calendarDaysGrid');
    const prevMonthBtn = document.getElementById('prevMonthBtn');
    const nextMonthBtn = document.getElementById('nextMonthBtn');
    const sidePanelTitle = document.getElementById('sidePanelTitle');
    const sidePanelContent = document.getElementById('sidePanelContent');
    const resetDateFilterBtn = document.getElementById('resetDateFilterBtn');

    function renderCalendar() {
        if (!calendarDaysGrid) return;
        
        currentMonthYearLabel.textContent = `${monthNames[currentMonth]} ${currentYear}`;
        calendarDaysGrid.innerHTML = '';

        const firstDayIdx = new Date(currentYear, currentMonth, 1).getDay();
        const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();
        const prevTotalDays = new Date(currentYear, currentMonth, 0).getDate();

        // 1. Previous month padding cells
        for (let i = firstDayIdx - 1; i >= 0; i--) {
            const dayNum = prevTotalDays - i;
            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell other-month';
            cell.textContent = dayNum;
            calendarDaysGrid.appendChild(cell);
        }

        // 2. Current month day cells
        for (let day = 1; day <= totalDays; day++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell';
            cell.textContent = day;

            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            if (dateStr === todayStr) {
                cell.classList.add('today');
            }

            if (dateStr === selectedDateStr) {
                cell.classList.add('selected');
            }

            const dayAppts = appointmentsData.filter(a => a.date === dateStr);
            if (dayAppts.length > 0) {
                const badgeContainer = document.createElement('div');
                badgeContainer.className = 'calendar-day-badge-container';
                
                const sliced = dayAppts.slice(0, 3);
                sliced.forEach(appt => {
                    const dot = document.createElement('span');
                    dot.className = `calendar-day-dot dot-${appt.status.toLowerCase().replace('-', '')}`;
                    badgeContainer.appendChild(dot);
                });
                
                cell.appendChild(badgeContainer);
            }

            cell.addEventListener('click', function() {
                if (selectedDateStr === dateStr) {
                    selectedDateStr = "";
                    resetFilters();
                } else {
                    selectedDateStr = dateStr;
                    renderCalendar(); 
                    filterByDate(dateStr);
                }
            });

            calendarDaysGrid.appendChild(cell);
        }

        // 3. Next month padding cells
        const totalRenderedCells = firstDayIdx + totalDays;
        const remainingCells = 42 - totalRenderedCells;
        for (let day = 1; day <= remainingCells; day++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell other-month';
            cell.textContent = day;
            calendarDaysGrid.appendChild(cell);
        }
    }

    function filterByDate(dateStr) {
        const dayAppts = appointmentsData.filter(a => a.date === dateStr);
        
        const parts = dateStr.split('-');
        const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

        const apptCount = dayAppts.length;
        const countText = apptCount === 1 ? '1 Appointment Scheduled' : `${apptCount} Appointments Scheduled`;
        sidePanelTitle.innerHTML = `<i class="bi bi-calendar-event"></i> ${formattedDate} &middot; <span class="text-secondary small fw-normal">${countText}</span>`;
        if (resetDateFilterBtn) {
            resetDateFilterBtn.classList.remove('d-none');
        }

        if (dayAppts.length === 0) {
            sidePanelContent.innerHTML = `
                <div class="text-center py-5 text-secondary">
                    <i class="bi bi-calendar-x fs-2 d-block mb-2 text-muted"></i>
                    <span>No appointments scheduled for this date.</span>
                </div>
            `;
        } else {
            dayAppts.sort((a,b) => (a.time > b.time ? 1 : -1));
            
            let timelineHtml = '<div class="appt-timeline-container">';
            dayAppts.forEach(appt => {
                const isOverdue = (appt.status === 'Scheduled' && appt.date < todayStr);
                let badgeClass = 'badge bg-secondary';
                if (appt.status === 'Scheduled') badgeClass = isOverdue ? 'badge bg-warning text-dark' : 'badge bg-primary';
                else if (appt.status === 'Completed') badgeClass = 'badge bg-success';
                else if (appt.status === 'Cancelled') badgeClass = 'badge bg-danger';
                else if (appt.status === 'No-Show') badgeClass = 'badge bg-dark text-white';

                let overdueBadgeHtml = '';
                let cardClass = `appt-side-card status-${appt.status.toLowerCase().replace('-', '')}`;
                if (isOverdue) {
                    cardClass = 'appt-side-card status-overdue';
                    overdueBadgeHtml = `
                        <span class="badge bg-danger text-white px-2 py-1 font-weight-bold" style="font-size: 10px;" title="This appointment was scheduled for a past date but not updated.">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue
                        </span>
                    `;
                }

                let actionsHtml = '';
                actionsHtml += `<div class="d-flex gap-2 align-items-center">`;
                actionsHtml += `
                    <a href="<?= BASE_URL ?>appointments/view.php?id=${appt.id}" 
                       class="btn btn-sm btn-outline-info border-0 p-1" 
                       title="View Appointment Detail">
                        <i class="bi bi-eye-fill fs-5"></i>
                    </a>
                `;
                if (userRole === 'admin' || userRole === 'staff' || userRole === 'bhw') {
                    actionsHtml += `
                        <a href="<?= BASE_URL ?>appointments/edit.php?id=${appt.id}" 
                           class="btn btn-sm btn-outline-primary border-0 p-1" 
                           title="Update Status / Details">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </a>
                    `;
                }
                if (userRole === 'admin') {
                    actionsHtml += `
                        <button class="btn btn-sm btn-outline-danger border-0 p-1 archive-appointment-btn" 
                                data-id="${appt.id}" 
                                data-patient="${appt.patient_name}"
                                data-date="${appt.date}"
                                title="Archive Appointment">
                            <i class="bi bi-archive-fill fs-5"></i>
                        </button>
                    `;
                }
                actionsHtml += `</div>`;

                timelineHtml += `
                    <div class="${cardClass} p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="fw-bold text-dark d-block" style="font-size: 14px;">
                                    <i class="bi bi-clock me-1 text-primary"></i> ${appt.time}
                                </span>
                                <span class="badge bg-light text-primary border fw-bold small mt-1">${appt.service_name}</span>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <div class="d-flex align-items-center gap-1">
                                    <div class="dropdown">
                                        <button class="btn btn-sm ${badgeClass} dropdown-toggle px-2 py-1 font-weight-bold d-flex align-items-center gap-1 border-0" 
                                                type="button" 
                                                id="cardStatusDropdown_${appt.id}" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false" 
                                                style="font-size: 11px;">
                                            ${appt.status}
                                        </button>
                                        <ul class="dropdown-menu shadow" aria-labelledby="cardStatusDropdown_${appt.id}">
                                            <li>
                                                <a class="dropdown-item change-status-btn d-flex align-items-center gap-2" href="#" data-id="${appt.id}" data-status="Scheduled">
                                                    <span class="badge bg-primary dot-status-marker"></span> Scheduled
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item change-status-btn d-flex align-items-center gap-2" href="#" data-id="${appt.id}" data-status="Completed">
                                                    <span class="badge bg-success dot-status-marker"></span> Completed
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item change-status-btn d-flex align-items-center gap-2" href="#" data-id="${appt.id}" data-status="Cancelled">
                                                    <span class="badge bg-danger dot-status-marker"></span> Cancelled
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item change-status-btn d-flex align-items-center gap-2" href="#" data-id="${appt.id}" data-status="No-Show">
                                                    <span class="badge bg-dark dot-status-marker"></span> No-Show
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    ${overdueBadgeHtml}
                                </div>
                                ${actionsHtml}
                            </div>
                        </div>
                        <div class="fw-semibold mb-1" style="font-size: 13px;">
                            <a href="<?= BASE_URL ?>patients/view.php?id=${appt.patient_id}" class="text-decoration-none text-primary">
                                ${appt.patient_name}
                            </a>
                        </div>
                        <div class="text-secondary small mt-1">${appt.reason || '<em>No reason specified</em>'}</div>
                    </div>
                `;
            });
            timelineHtml += '</div>';
            sidePanelContent.innerHTML = timelineHtml;
        }
    }

    function renderUpcomingTimeline() {
        sidePanelTitle.innerHTML = `<i class="bi bi-clock-history"></i> Upcoming Appointments`;
        if (resetDateFilterBtn) {
            resetDateFilterBtn.classList.add('d-none');
        }

        const upcomingAppts = appointmentsData.filter(a => a.date >= todayStr && a.status === 'Scheduled');
        
        upcomingAppts.sort((a,b) => {
            if (a.date !== b.date) return a.date > b.date ? 1 : -1;
            return a.time > b.time ? 1 : -1;
        });

        if (upcomingAppts.length === 0) {
            sidePanelContent.innerHTML = `
                <div class="text-center py-5 text-secondary">
                    <i class="bi bi-calendar-check fs-2 d-block mb-2 text-muted"></i>
                    <span>No upcoming scheduled appointments.</span>
                </div>
            `;
        } else {
            const sliced = upcomingAppts.slice(0, 8);
            let timelineHtml = '<div class="appt-timeline-container">';
            sliced.forEach(appt => {
                const parts = appt.date.split('-');
                const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
                const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                let actionsHtml = '';
                actionsHtml += `<div class="d-flex gap-2 align-items-center">`;
                actionsHtml += `
                    <a href="<?= BASE_URL ?>appointments/view.php?id=${appt.id}" 
                       class="btn btn-sm btn-outline-info border-0 p-1" 
                       title="View Appointment Detail">
                        <i class="bi bi-eye-fill fs-5"></i>
                    </a>
                `;
                if (userRole === 'admin' || userRole === 'staff' || userRole === 'bhw') {
                    actionsHtml += `
                        <a href="<?= BASE_URL ?>appointments/edit.php?id=${appt.id}" 
                           class="btn btn-sm btn-outline-primary border-0 p-1" 
                           title="Update Status / Details">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </a>
                    `;
                }
                if (userRole === 'admin') {
                    actionsHtml += `
                        <button class="btn btn-sm btn-outline-danger border-0 p-1 archive-appointment-btn" 
                                data-id="${appt.id}" 
                                data-patient="${appt.patient_name}"
                                data-date="${appt.date}"
                                title="Archive Appointment">
                            <i class="bi bi-archive-fill fs-5"></i>
                        </button>
                    `;
                }
                actionsHtml += `</div>`;

                timelineHtml += `
                    <div class="appt-side-card status-scheduled p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="fw-bold text-dark d-block" style="font-size: 14px;">
                                    <i class="bi bi-calendar-event me-1 text-primary"></i> ${formattedDate} &middot; <i class="bi bi-clock me-1 text-primary"></i> ${appt.time}
                                </span>
                                <span class="badge bg-light text-primary border fw-bold small mt-1">${appt.service_name}</span>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <span class="badge bg-primary px-2 py-1 small fw-bold">Scheduled</span>
                                ${actionsHtml}
                            </div>
                        </div>
                        <div class="fw-semibold mb-1" style="font-size: 13px;">
                            <a href="<?= BASE_URL ?>patients/view.php?id=${appt.patient_id}" class="text-decoration-none text-primary">
                                ${appt.patient_name}
                            </a>
                        </div>
                        <div class="text-secondary small mt-1">${appt.reason || '<em>No reason specified</em>'}</div>
                    </div>
                `;
            });
            timelineHtml += '</div>';
            sidePanelContent.innerHTML = timelineHtml;
        }
    }

    function resetFilters() {
        selectedDateStr = "";
        renderCalendar();
        renderUpcomingTimeline();
        if (appointmentsTable) {
            appointmentsTable.search('').draw();
        }
    }

    // Toggle view buttons listeners
    const btnListView = document.getElementById('btnListView');
    const btnCalendarView = document.getElementById('btnCalendarView');
    const listViewFilterCard = document.getElementById('listViewFilterCard');
    const listViewTableCard = document.getElementById('listViewTableCard');
    const calendarViewRow = document.getElementById('calendarViewRow');

    if (btnListView && btnCalendarView) {
        btnListView.addEventListener('click', function() {
            btnListView.classList.add('active');
            btnCalendarView.classList.remove('active');
            listViewFilterCard.style.display = 'block';
            listViewTableCard.style.display = 'block';
            calendarViewRow.style.display = 'none';
            resetFilters();
        });

        btnCalendarView.addEventListener('click', function() {
            btnCalendarView.classList.add('active');
            btnListView.classList.remove('active');
            listViewFilterCard.style.display = 'none';
            listViewTableCard.style.display = 'none';
            calendarViewRow.style.display = 'flex';
            
            // Pre-select today's date if no date is currently selected
            if (!selectedDateStr) {
                selectedDateStr = todayStr;
            }
            
            renderCalendar();
            
            if (selectedDateStr) {
                filterByDate(selectedDateStr);
            } else {
                renderUpcomingTimeline();
            }
        });
    }

    if (prevMonthBtn && nextMonthBtn) {
        prevMonthBtn.addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar();
        });

        nextMonthBtn.addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar();
        });
    }

    if (resetDateFilterBtn) {
        resetDateFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            resetFilters();
        });
    }

    // 5. Overdue manual resolution filter trigger and URL deep-link handler
    const btnResolveManually = document.getElementById('btnResolveManually');
    if (btnResolveManually) {
        btnResolveManually.addEventListener('click', function() {
            // If in Calendar View, switch back to List View first
            if (btnListView && !btnListView.classList.contains('active')) {
                btnListView.click();
            }
            if (appointmentsTable) {
                appointmentsTable.search('Overdue').draw();
            }
        });
    }

    // Trigger filter automatically if redirecting from admin dashboard with deep link
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('filter') === 'overdue') {
        if (appointmentsTable) {
            appointmentsTable.search('Overdue').draw();
        }
    }

    // Intercept bulk resolve (No-Show) form submit with SweetAlert2
    const bulkForm = document.getElementById('bulkNoShowForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Mark All as No-Show?',
                text: 'Are you sure you want to mark all past scheduled appointments as No-Show? This will update all overdue records in the system.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, mark as No-Show'
            }).then((result) => {
                if (result.isConfirmed) {
                    bulkForm.submit();
                }
            });
        });
    }

    // 6. Inline status change AJAX logic
    document.addEventListener('click', function(event) {
        const target = event.target.closest('.change-status-btn');
        if (target) {
            event.preventDefault();
            const appId = target.getAttribute('data-id');
            const newStatus = target.getAttribute('data-status');
            
            Swal.fire({
                title: 'Update Appointment Status?',
                text: `Are you sure you want to change this appointment status to '${newStatus}'?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d7377',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, update status'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show a loading box
                    Swal.fire({
                        title: 'Updating status...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Fire AJAX request to update status
                    const formData = new FormData();
                    formData.append('appointment_id', appId);
                    formData.append('status', newStatus);
                    formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

                    fetch('<?= BASE_URL ?>ajax/update_appointment_status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw new Error(err.error || 'Server error occurred'); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Status Updated',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload page to update database summaries, badge classes, and indicators properly
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Update',
                                text: data.error || 'Unknown error'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to complete update request.'
                        });
                    });
                }
            });
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

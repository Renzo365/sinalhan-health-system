<?php
// includes/sidebar.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';

$role = $_SESSION['role'] ?? 'staff';
$fullName = $_SESSION['full_name'] ?? 'Health Staff';
$avatarLetter = strtoupper(substr($_SESSION['username'] ?? 'H', 0, 1));
$currentMenu = $active_menu ?? '';
?>

<!-- Dynamic Role-Aware Sidebar -->
<aside id="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>index.php" class="sidebar-logo">
            <i class="bi bi-hospital"></i>
            <span>SINALHAN HC</span>
        </a>
    </div>
    
    <ul class="sidebar-menu">
        <!-- Dashboard Link (Role specific routing) -->
        <?php if ($role === 'admin'): ?>
            <li class="menu-item <?= $currentMenu === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>admin/dashboard.php" class="menu-link">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        <?php else: ?>
            <li class="menu-item <?= $currentMenu === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>patients/list.php" class="menu-link">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header">Patient Care</li>
        
        <!-- Patients Menu -->
        <li class="menu-item <?= $currentMenu === 'patients' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>patients/list.php" class="menu-link">
                <i class="bi bi-people"></i>
                <span>Patients List</span>
            </a>
        </li>
        <li class="menu-item <?= $currentMenu === 'patients_register' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>patients/register.php" class="menu-link">
                <i class="bi bi-person-plus"></i>
                <span>Register Patient</span>
            </a>
        </li>

        <!-- Health Records / consultations (BHW has view only, Admin & Staff have CRUD) -->
        <li class="menu-item <?= $currentMenu === 'health_records' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>health_records/list.php" class="menu-link">
                <i class="bi bi-file-earmark-medical"></i>
                <span>Health Records</span>
            </a>
        </li>

        <!-- Appointments Module -->
        <li class="menu-item <?= $currentMenu === 'appointments' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>appointments/list.php" class="menu-link">
                <i class="bi bi-calendar-event"></i>
                <span>Appointments</span>
            </a>
        </li>

        <li class="menu-header">Queue System</li>

        <!-- Queue Management -->
        <?php if ($role === 'admin' || $role === 'staff'): ?>
            <li class="menu-item <?= $currentMenu === 'queue_manage' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>queue/manage.php" class="menu-link">
                    <i class="bi bi-list-ol"></i>
                    <span>Queue Manager</span>
                </a>
            </li>
        <?php endif; ?>
        
        <li class="menu-item <?= $currentMenu === 'queue_assign' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>queue/assign.php" class="menu-link">
                <i class="bi bi-ticket-perforated"></i>
                <span>Assign Ticket</span>
            </a>
        </li>
        
        <li class="menu-item">
            <a href="<?= BASE_URL ?>queue/display.php" target="_blank" class="menu-link">
                <i class="bi bi-display"></i>
                <span>Queue Monitor ↗</span>
            </a>
        </li>

        <?php if ($role === 'admin'): ?>
            <li class="menu-header">Administration</li>
            
            <!-- User Management -->
            <li class="menu-item <?= $currentMenu === 'users' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>admin/users.php" class="menu-link">
                    <i class="bi bi-person-gear"></i>
                    <span>User Accounts</span>
                </a>
            </li>

            <!-- Service Types -->
            <li class="menu-item <?= $currentMenu === 'services' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>admin/service_types.php" class="menu-link">
                    <i class="bi bi-heart-pulse"></i>
                    <span>Service Categories</span>
                </a>
            </li>

            <!-- Archived Records -->
            <li class="menu-item <?= $currentMenu === 'archives' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>admin/archived_records.php" class="menu-link">
                    <i class="bi bi-archive"></i>
                    <span>Archived Records</span>
                </a>
            </li>

            <!-- Reports -->
            <li class="menu-item <?= $currentMenu === 'reports' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>admin/reports.php" class="menu-link">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Reports Generator</span>
                </a>
            </li>

            <!-- Activity Logs -->
            <li class="menu-item <?= $currentMenu === 'activity_log' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>admin/activity_log.php" class="menu-link">
                    <i class="bi bi-journal-text"></i>
                    <span>Audit Trail</span>
                </a>
            </li>
        <?php elseif ($role === 'staff'): ?>
            <li class="menu-header">Reports</li>
            
            <!-- Reports -->
            <li class="menu-item <?= $currentMenu === 'reports' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>admin/reports.php" class="menu-link">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Reports Generator</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header">Account</li>
        <li class="menu-item <?= $currentMenu === 'profile' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>auth/profile.php" class="menu-link">
                <i class="bi bi-person-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="<?= BASE_URL ?>auth/logout.php" class="menu-link text-danger-hover">
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Content Wrapper (Contains navbar and content) -->
<div id="content-wrapper">
    <!-- Top Navbar Include -->
    <header class="top-navbar">
        <button class="toggle-sidebar-btn" id="toggleSidebar" aria-label="Toggle Navigation">
            <i class="bi bi-list"></i>
        </button>

        <div class="d-flex align-items-center gap-3">
            <!-- Notifications indicator (for future) -->
            <div class="position-relative cursor-pointer p-2 rounded-circle hover-bg">
                <i class="bi bi-bell fs-5 text-secondary"></i>
                <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger border border-light" style="padding: 4px;">
                    <span class="visually-hidden">unread alerts</span>
                </span>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <div class="user-profile-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar"><?= $avatarLetter ?></div>
                    <div class="user-info d-none d-md-block">
                        <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
                        <div class="user-role"><?= htmlspecialchars($role) ?></div>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 rounded-3" style="min-width: 200px;">
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>auth/profile.php"><i class="bi bi-person me-2 text-secondary"></i> Profile</a></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>auth/change_password.php"><i class="bi bi-shield-lock me-2 text-secondary"></i> Password Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-left me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

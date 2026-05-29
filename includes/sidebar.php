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
            <!-- Notifications dropdown -->
            <div class="dropdown">
                <div class="position-relative cursor-pointer p-2 rounded-circle hover-bg dropdown-toggle no-arrow" id="notificationBell" data-bs-toggle="dropdown" aria-expanded="false" style="outline: none;">
                    <i class="bi bi-bell fs-5 text-secondary"></i>
                    <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger border border-light d-none" id="notifBadge" style="padding: 4px;">
                        <span id="notifBadgeCount">0</span>
                    </span>
                </div>
                <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-0 rounded-3" aria-labelledby="notificationBell" style="min-width: 320px; max-width: 360px; font-size: 14px; overflow: hidden; z-index: 1050;">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark"><i class="bi bi-bell-fill text-primary me-1"></i> Notifications</span>
                        <button class="btn btn-sm btn-link text-decoration-none p-0" id="markAllReadBtn" style="font-size: 12px; font-weight: 600;">Mark all read</button>
                    </div>
                    <div class="list-group list-group-flush" id="notificationList" style="max-height: 280px; overflow-y: auto;">
                        <div class="text-center py-4 text-secondary">
                            <i class="bi bi-bell-slash fs-3 d-block mb-1"></i>
                            <span class="small">No notifications yet</span>
                        </div>
                    </div>
                    <div class="p-2 bg-light border-top text-center">
                        <small class="text-secondary">Barangay Sinalhan Health Center</small>
                    </div>
                </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const badge = document.getElementById('notifBadge');
    const badgeCount = document.getElementById('notifBadgeCount');
    const listContainer = document.getElementById('notificationList');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    
    let localNotifications = [];

    // Helper to format HTML for a notification item
    function formatNotifItem(item) {
        let iconClass = 'bi-info-circle-fill text-info';
        if (item.type === 'success') iconClass = 'bi-check-circle-fill text-success';
        else if (item.type === 'warning') iconClass = 'bi-exclamation-triangle-fill text-warning';
        else if (item.type === 'danger') iconClass = 'bi-x-circle-fill text-danger';
        else if (item.type === 'security') iconClass = 'bi-shield-fill-check text-primary';

        const isUnread = !item.is_read;
        const bgClass = isUnread ? 'bg-light fw-bold' : '';
        const unreadIndicator = isUnread ? '<span class="badge bg-primary rounded-circle p-1 ms-2" style="width:6px;height:6px;display:inline-block;"><span class="visually-hidden">Unread</span></span>' : '';

        return `
            <div class="list-group-item list-group-item-action d-flex align-items-start gap-3 p-3 ${bgClass} position-relative notif-item" data-id="${item.id}" style="cursor: pointer;">
                <div class="mt-1"><i class="bi ${iconClass} fs-5"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-dark truncate-1 pr-3" style="font-size:13px; font-weight: 600;">${item.title}</span>
                        <small class="text-muted text-nowrap" style="font-size: 11px;">${item.time_ago}</small>
                    </div>
                    <div class="text-secondary truncate-2" style="font-size: 12px; line-height: 1.4;">${item.message}</div>
                </div>
                ${unreadIndicator}
            </div>
        `;
    }

    // Refresh notifications list from DB
    function loadNotifications() {
        fetch('<?= BASE_URL ?>ajax/notifications_feed.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let totalUnread = data.count + localNotifications.filter(n => !n.is_read).length;
                    
                    if (totalUnread > 0) {
                        badge.classList.remove('d-none');
                        badgeCount.textContent = totalUnread;
                    } else {
                        badge.classList.add('d-none');
                    }

                    let combined = [...localNotifications, ...data.notifications];

                    if (combined.length > 0) {
                        listContainer.innerHTML = combined.map(formatNotifItem).join('');
                        bindItemClicks();
                    } else {
                        listContainer.innerHTML = `
                            <div class="text-center py-4 text-secondary">
                                <i class="bi bi-bell-slash fs-3 d-block mb-1"></i>
                                <span class="small">No notifications yet</span>
                            </div>
                        `;
                    }
                }
            })
            .catch(err => console.error("Error loading notifications:", err));
    }

    // Bind click events on loaded items
    function bindItemClicks() {
        document.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', function() {
                const id = parseInt(this.dataset.id);
                // Check if it is a local notification
                const localIdx = localNotifications.findIndex(n => n.id === id);
                if (localIdx !== -1) {
                    localNotifications[localIdx].is_read = true;
                    // Trigger redirect if click is offline sync
                    if (localNotifications[localIdx].title.includes('Pending Sync') || localNotifications[localIdx].title.includes('Offline')) {
                        window.location.href = '<?= BASE_URL ?>patients/list.php';
                        return;
                    }
                    loadNotifications();
                    return;
                }

                // If DB notification
                if (id > 0) {
                    const formData = new FormData();
                    formData.append('action', 'mark_read');
                    formData.append('notification_id', id);
                    formData.append('csrf_token', csrfToken);

                    fetch('<?= BASE_URL ?>ajax/notifications_feed.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            loadNotifications();
                        }
                    })
                    .catch(err => console.error("Error marking read:", err));
                }
            });
        });
    }

    // Mark all as read
    markAllReadBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Mark local ones first
        localNotifications.forEach(n => n.is_read = true);

        const formData = new FormData();
        formData.append('action', 'mark_all_read');
        formData.append('csrf_token', csrfToken);

        fetch('<?= BASE_URL ?>ajax/notifications_feed.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        })
        .catch(err => console.error("Error marking all read:", err));
    });

    // PWA Offline status handling
    window.addEventListener('online', function() {
        localNotifications = localNotifications.filter(n => n.id !== -98);
        localNotifications.unshift({
            id: -99, // negative IDs for local alerts
            title: 'Online Restored',
            message: 'Your connection has been restored successfully.',
            type: 'success',
            is_read: false,
            time_ago: 'Just now'
        });
        loadNotifications();
    });

    window.addEventListener('offline', function() {
        localNotifications = localNotifications.filter(n => n.id !== -99);
        localNotifications.unshift({
            id: -98,
            title: 'Device Offline',
            message: 'You are offline. Offline registrations will be saved locally.',
            type: 'warning',
            is_read: false,
            time_ago: 'Just now'
        });
        loadNotifications();
    });

    // Count unsynced offline records from IndexedDB
    function checkIndexedDBOffline() {
        if (!('indexedDB' in window)) return;
        
        try {
            const req = indexedDB.open('SinalhanOfflineDB', 1);
            req.onsuccess = function(e) {
                const db = e.target.result;
                if (!db.objectStoreNames.contains('pending_patients')) return;
                
                const tx = db.transaction('pending_patients', 'readonly');
                const store = tx.objectStore('pending_patients');
                const countReq = store.count();
                
                countReq.onsuccess = function() {
                    const count = countReq.result;
                    
                    // Filter out old sync notifications
                    localNotifications = localNotifications.filter(n => n.id !== -97);
                    
                    if (count > 0) {
                        localNotifications.unshift({
                            id: -97,
                            title: 'Pending Sync Alerts',
                            message: `You have ${count} patient records stored offline. Tap here to sync.`,
                            type: 'warning',
                            is_read: false,
                            time_ago: 'Pending'
                        });
                    }
                    loadNotifications();
                };
            };
        } catch (err) {
            console.warn("IndexedDB access error in notification check:", err);
        }
    }

    // Initial check
    loadNotifications();
    checkIndexedDBOffline();
    
    // Periodically poll for notifications & offline sync checks every 10 seconds
    setInterval(function() {
        loadNotifications();
        checkIndexedDBOffline();
    }, 10000);
});
</script>

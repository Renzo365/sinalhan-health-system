<?php
// admin/users.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

$page_title = 'User Accounts Management';
$active_menu = 'users';

// Load DataTables styles and scripts via CDN
$extra_css = ['https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css'];
$extra_js = [
    'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js'
];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

try {
    // Select all non-archived user accounts
    $stmt = $pdo->query("SELECT user_id, username, first_name, last_name, email, contact_number, role, is_active FROM users WHERE is_archived = 0 ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Users list fetch failed: " . $e->getMessage());
    $users = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">User Accounts</h2>
            <p class="text-secondary mb-0">Create and manage health center staff credentials and roles.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>admin/user_add.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-person-plus-fill"></i>
                <span>Add User Account</span>
            </a>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-person-lines-fill"></i> Registered User Accounts</h3>
        </div>
        <div class="card-custom-body">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle" id="usersTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Email Address</th>
                            <th>Contact No.</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary">@<?= htmlspecialchars($u['username']) ?></strong>
                                    <?php if ($u['user_id'] === (int)$_SESSION['user_id']): ?>
                                        <span class="badge bg-light text-primary border ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                <td>
                                    <?php
                                        $roleBadge = 'bg-secondary';
                                        if ($u['role'] === 'admin') $roleBadge = 'bg-danger';
                                        elseif ($u['role'] === 'staff') $roleBadge = 'bg-primary';
                                    ?>
                                    <span class="badge <?= $roleBadge ?> text-capitalize"><?= htmlspecialchars($u['role']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($u['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($u['contact_number'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($u['is_active'] == 1): ?>
                                        <span class="badge-custom badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge-custom badge-inactive"><i class="bi bi-dash-circle-fill"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Edit Details -->
                                        <a href="<?= BASE_URL ?>admin/user_edit.php?id=<?= $u['user_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary border-0 p-1" 
                                           title="Edit Details">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </a>
                                        
                                        <!-- Reset Password -->
                                        <button class="btn btn-sm btn-outline-warning border-0 p-1 reset-pass-btn" 
                                                data-id="<?= $u['user_id'] ?>" 
                                                data-username="<?= htmlspecialchars($u['username']) ?>"
                                                title="Reset Password">
                                            <i class="bi bi-key-fill fs-5"></i>
                                        </button>
                                        
                                        <!-- Status Toggle Toggle -->
                                        <?php if ($u['user_id'] !== (int)$_SESSION['user_id']): ?>
                                            <?php if ($u['is_active'] == 1): ?>
                                                <button class="btn btn-sm btn-outline-danger border-0 p-1 toggle-status-btn" 
                                                        data-id="<?= $u['user_id'] ?>" 
                                                        data-username="<?= htmlspecialchars($u['username']) ?>"
                                                        data-status="0"
                                                        title="Deactivate Account">
                                                    <i class="bi bi-person-x-fill fs-5"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-success border-0 p-1 toggle-status-btn" 
                                                        data-id="<?= $u['user_id'] ?>" 
                                                        data-username="<?= htmlspecialchars($u['username']) ?>"
                                                        data-status="1"
                                                        title="Activate Account">
                                                    <i class="bi bi-person-check-fill fs-5"></i>
                                                </button>
                                            <?php endif; ?>
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

<!-- Modal: Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-warning text-dark border-0 py-3" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h5 class="modal-title font-weight-bold" id="resetPasswordModalLabel">
                    <i class="bi bi-shield-lock-fill me-2"></i> Reset User Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/user_process.php" method="POST" id="resetPasswordForm">
                <div class="modal-body p-4">
                    <!-- Form parameters -->
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="user_id" id="modal_user_id" value="">

                    <p class="text-secondary">You are resetting the password for account: <strong id="modal_username_display" class="text-primary"></strong></p>

                    <div class="mb-3 position-relative">
                        <label for="new_password" class="form-label font-weight-bold text-dark mb-1">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                            <input type="password" name="new_password" id="new_password" class="form-control border-start-0" placeholder="Minimum 6 characters" required>
                        </div>
                    </div>
                    
                    <div class="mb-2 position-relative">
                        <label for="confirm_password" class="form-label font-weight-bold text-dark mb-1">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0" placeholder="Repeat new password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                    <button type="button" class="btn btn-secondary py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning py-2 px-4 rounded-3">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Form for status toggling -->
<form action="<?= BASE_URL ?>admin/user_process.php" method="POST" id="toggleStatusForm" style="display: none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="user_id" id="status_user_id" value="">
    <input type="hidden" name="status" id="status_val" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTable
    if ($.fn.DataTable) {
        $('#usersTable').DataTable({
            responsive: true,
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 6 } // Disable sorting on action column
            ],
            order: [[1, 'asc']] // Sort by full name initially
        });
    }

    // 2. Password Reset Modal Triggers
    const resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    
    document.querySelectorAll('.reset-pass-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const username = this.getAttribute('data-username');
            
            document.getElementById('modal_user_id').value = id;
            document.getElementById('modal_username_display').innerText = '@' + username;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            
            resetModal.show();
        });
    });

    // Client-side password validation
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        const pass = document.getElementById('new_password').value;
        const confirm = document.getElementById('confirm_password').value;

        if (pass.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Weak Password',
                text: 'The password must be at least 6 characters long.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        if (pass !== confirm) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Mismatch',
                text: 'Passwords do not match. Please verify.',
                confirmButtonColor: '#0D7377'
            });
        }
    });

    // 3. Status Toggle Trigger
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const username = this.getAttribute('data-username');
            const status = this.getAttribute('data-status'); // 1 = activate, 0 = deactivate
            const actionText = status === '1' ? 'activate' : 'deactivate';
            const buttonColor = status === '1' ? '#28A745' : '#DC3545';

            Swal.fire({
                title: `Are you sure?`,
                text: `You are about to ${actionText} the account for user '@${username}'.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: buttonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${actionText} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('status_user_id').value = id;
                    document.getElementById('status_val').value = status;
                    document.getElementById('toggleStatusForm').submit();
                }
            });
        });
    });
});
</script>

<?php
// Load SweetAlert popups
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

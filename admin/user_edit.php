<?php
// admin/user_edit.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

$page_title = 'Edit User Account';
$active_menu = 'users';

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$userId = (int)($_GET['id'] ?? 0);

if (!$userId) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Missing ID',
        'message' => 'Please select a valid user to edit.'
    ];
    header('Location: ' . BASE_URL . 'admin/users.php');
    exit;
}

try {
    // Select user details
    $stmt = $pdo->prepare("SELECT user_id, username, first_name, last_name, email, contact_number, role, is_active FROM users WHERE user_id = ? AND is_archived = 0");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'User Not Found',
            'message' => 'The requested user account does not exist or has been archived.'
        ];
        header('Location: ' . BASE_URL . 'admin/users.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Edit user fetch failure: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'Failed to load user account details.'
    ];
    header('Location: ' . BASE_URL . 'admin/users.php');
    exit;
}

// Safety constraint flags
$isSelf = ($userId === (int)$_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Modify User Account</h2>
            <p class="text-secondary mb-0">Update staff details and access privileges for <strong>@<?= htmlspecialchars($user['username']) ?></strong>.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Accounts</span>
            </a>
        </div>
    </div>

    <!-- Edit Card -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-person-fill-gear"></i> Account Profile Settings</h3>
        </div>
        <div class="card-custom-body">
            <form action="<?= BASE_URL ?>admin/user_process.php" method="POST" id="editUserForm" class="needs-validation" novalidate>
                <!-- Form Configuration parameters -->
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">

                <div class="row g-4">
                    <!-- Column 1: Identity & Credentials Summary -->
                    <div class="col-lg-6">
                        <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary">System Identity</h4>
                        
                        <!-- Username (Read only) -->
                        <div class="mb-3">
                            <label class="form-label font-weight-bold mb-1">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control bg-light text-secondary" value="@<?= htmlspecialchars($user['username']) ?>" disabled>
                            </div>
                            <small class="text-secondary mt-1 d-block">Usernames are immutable and cannot be changed.</small>
                        </div>

                        <!-- System Role -->
                        <div class="mb-3">
                            <label for="role" class="form-label font-weight-bold mb-1">System Role <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                <?php if ($isSelf): ?>
                                    <!-- Prevent self-role modification by locking select box -->
                                    <input type="hidden" name="role" value="admin">
                                    <select class="form-select bg-light text-secondary" disabled>
                                        <option value="admin" selected>Administrator (Full Access)</option>
                                    </select>
                                <?php else: ?>
                                    <select name="role" id="role" class="form-select" required>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <option value="admin" selected>Administrator (Full Access)</option>
                                        <?php endif; ?>
                                        <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Health Center Staff (Nurses/Midwives)</option>
                                        <option value="bhw" <?= $user['role'] === 'bhw' ? 'selected' : '' ?>>Barangay Health Worker (BHW - Volunteers)</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <?php if ($isSelf): ?>
                                <small class="text-warning mt-1 d-block"><i class="bi bi-exclamation-triangle"></i> You cannot change your own Administrator privileges.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Column 2: Personal details -->
                    <div class="col-lg-6">
                        <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary">Personal Details</h4>

                        <!-- First Name -->
                        <div class="mb-3">
                            <label for="first_name" class="form-label font-weight-bold mb-1">First Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div class="mb-3">
                            <label for="last_name" class="form-label font-weight-bold mb-1">Last Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label font-weight-bold mb-1">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="e.g. user@sinalhan-hc.local">
                            </div>
                        </div>

                        <!-- Contact Number -->
                        <div class="mb-3">
                            <label for="contact_number" class="form-label font-weight-bold mb-1">Contact Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="contact_number" id="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>" placeholder="e.g. 09123456789">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 border-color">

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Update Details</button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editUserForm');

    form.addEventListener('submit', function(e) {
        const contact = document.getElementById('contact_number').value.trim();

        // Contact format validation (if provided)
        if (contact && !/^(09\d{9}|(\+639)\d{9})$/.test(contact)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Contact Number Error',
                text: 'Please enter a valid Philippine mobile number format (e.g., 09123456789).',
                confirmButtonColor: '#0D7377'
            });
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

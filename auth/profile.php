<?php
// auth/profile.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'My Profile';
$active_menu = 'profile';

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$userId = (int)($_SESSION['user_id'] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT username, first_name, last_name, email, contact_number, role, created_at, two_fa_enabled FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();

    if (!$u) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Profile Error',
            'message' => 'Your user account does not exist or has been deactivated.'
        ];
        header('Location: ' . BASE_URL . 'auth/logout.php');
        exit;
    }

    $fullName = htmlspecialchars($u['first_name'] . ' ' . $u['last_name']);
    $avatarLetter = strtoupper(substr($u['username'], 0, 1));

} catch (Exception $e) {
    error_log("Failed to load user profile: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'An error occurred while loading your profile details.'
    ];
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Account Profile Settings</h2>
            <p class="text-secondary mb-0">Update your metadata, contact details, and view your system access level.</p>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="row">
        <!-- Sidebar Widget: Avatar & Metadata -->
        <div class="col-lg-4 mb-4">
            <div class="card-custom text-center py-4">
                <div class="card-custom-body">
                    <!-- Big Avatar -->
                    <div class="user-avatar mx-auto mb-3" style="width: 90px; height: 90px; font-size: 36px; background: linear-gradient(135deg, var(--primary-light), var(--primary-color)); box-shadow: 0 8px 16px rgba(13, 115, 119, 0.2);">
                        <?= $avatarLetter ?>
                    </div>
                    
                    <h3 class="fw-bold mb-1" style="font-size: 20px; color: var(--primary-dark);"><?= $fullName ?></h3>
                    <p class="text-secondary mb-3"><span class="badge bg-light text-primary border text-capitalize"><?= htmlspecialchars($u['role']) ?></span></p>
                    
                    <div class="border-top pt-3 text-start">
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Username:</div>
                            <div class="col-7 text-dark fw-bold">@<?= htmlspecialchars($u['username']) ?></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Status:</div>
                            <div class="col-7 text-dark fw-bold"><span class="badge bg-success">Active</span></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">Member Since:</div>
                            <div class="col-7 text-dark"><?= date('M d, Y', strtotime($u['created_at'])) ?></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-5 text-secondary font-weight-bold" style="font-size: 13px;">2FA Security:</div>
                            <div class="col-7 text-dark fw-bold">
                                <?php if ($u['two_fa_enabled']): ?>
                                    <span class="badge bg-success">Enabled</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Disabled</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3 d-flex flex-column gap-2">
                        <a href="<?= BASE_URL ?>auth/change_password.php" class="btn btn-sm btn-outline-teal w-100">
                            <i class="bi bi-shield-lock-fill"></i> Change Account Password
                        </a>
                        <a href="<?= BASE_URL ?>auth/two_fa.php" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-shield-check"></i> Two-Factor Auth (2FA)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Form: Edit Details -->
        <div class="col-lg-8 mb-4">
            <div class="card-custom h-100">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-person-bounding-box text-primary"></i> Personal Profile Information</h3>
                </div>
                <div class="card-custom-body p-4">
                    <form action="<?= BASE_URL ?>auth/profile_process.php" method="POST" id="profileForm" class="needs-validation" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="row g-3 mb-4">
                            <!-- First Name -->
                            <div class="col-md-6">
                                <label for="first_name" class="form-label font-weight-bold mb-1">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($u['first_name']) ?>" required>
                            </div>
                            <!-- Last Name -->
                            <div class="col-md-6">
                                <label for="last_name" class="form-label font-weight-bold mb-1">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($u['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label font-weight-bold mb-1">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($u['email'] ?? '') ?>" placeholder="e.g. user@gmail.com">
                            </div>
                            <!-- Contact Number -->
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label font-weight-bold mb-1">Contact Number</label>
                                <input type="text" name="contact_number" id="contact_number" class="form-control" value="<?= htmlspecialchars($u['contact_number'] ?? '') ?>" placeholder="e.g. 09123456789">
                                <small class="text-secondary small">Philippine mobile format (e.g. 09123456789)</small>
                            </div>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Action controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <a href="<?= BASE_URL ?>index.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');

    form.addEventListener('submit', function(e) {
        const contact = document.getElementById('contact_number').value.trim();
        
        // Contact format check (if provided)
        if (contact && !/^(09\d{9}|(\+639)\d{9})$/.test(contact)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Contact Number',
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

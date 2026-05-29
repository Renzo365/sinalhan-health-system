<?php
// auth/change_password.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Change Password';
$active_menu = 'profile';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Change Account Password</h2>
            <p class="text-secondary mb-0">Update your credentials to maintain health system account security.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>auth/profile.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Profile</span>
            </a>
        </div>
    </div>

    <!-- Password Settings Form -->
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card-custom">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-shield-lock-fill text-danger"></i> Update Password Credentials</h3>
                </div>
                <div class="card-custom-body">
                    <form action="<?= BASE_URL ?>auth/change_password_process.php" method="POST" id="passwordForm" class="needs-validation" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- Current Password -->
                        <div class="mb-4">
                            <label for="current_password" class="form-label font-weight-bold mb-1">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-key-fill text-secondary"></i></span>
                                <input type="password" name="current_password" id="current_password" class="form-control border-start-0" placeholder="Enter current password" required>
                                <button class="btn btn-outline-secondary toggle-pass-btn" type="button" data-target="current_password"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-4">
                            <label for="new_password" class="form-label font-weight-bold mb-1">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-shield-lock text-secondary"></i></span>
                                <input type="password" name="new_password" id="new_password" class="form-control border-start-0" placeholder="Minimum 6 characters" required>
                                <button class="btn btn-outline-secondary toggle-pass-btn" type="button" data-target="new_password"><i class="bi bi-eye-slash"></i></button>
                            </div>
                            <small class="text-secondary small">Password must be at least 6 characters long.</small>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label font-weight-bold mb-1">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-shield-fill-check text-secondary"></i></span>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0" placeholder="Confirm new password" required>
                                <button class="btn btn-outline-secondary toggle-pass-btn" type="button" data-target="confirm_password"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Action controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <a href="<?= BASE_URL ?>auth/profile.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Password Visibility Toggle
    const toggleButtons = document.querySelectorAll('.toggle-pass-btn');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                targetInput.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    });

    // 2. Client-side matching validations on submit
    const form = document.getElementById('passwordForm');
    form.addEventListener('submit', function(e) {
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;

        if (newPass.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Weak Password',
                text: 'The new password must be at least 6 characters long.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        if (newPass !== confirmPass) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Password Mismatch',
                text: 'The new password and confirmation password do not match.',
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

<?php
// admin/user_add.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

$page_title = 'Create User Account';
$active_menu = 'users';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Add New User Account</h2>
            <p class="text-secondary mb-0">Create new staff profile credentials for system access.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Accounts</span>
            </a>
        </div>
    </div>

    <!-- Add Form Card -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-person-fill-add"></i> Account Details Form</h3>
        </div>
        <div class="card-custom-body">
            <form action="<?= BASE_URL ?>admin/user_process.php" method="POST" id="addUserForm" class="needs-validation" novalidate>
                <!-- Form Configuration parameters -->
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="row g-4">
                    <!-- Column 1: Credentials -->
                    <div class="col-lg-6">
                        <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary">Login Credentials</h4>
                        
                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label font-weight-bold mb-1">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" id="username" class="form-control" placeholder="Enter alphanumeric username" required autocomplete="off">
                            </div>
                            <small class="text-secondary d-block mt-1">Must be 3-30 characters, letters, numbers, or underscores only.</small>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label font-weight-bold mb-1">Temporary Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 6 characters" required autocomplete="new-password">
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label font-weight-bold mb-1">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat temporary password" required autocomplete="new-password">
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="role" class="form-label font-weight-bold mb-1">System Role <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="" disabled selected>-- Select Role --</option>
                                    <option value="staff">Health Center Staff (Nurses/Midwives)</option>
                                    <option value="bhw">Barangay Health Worker (BHW - Volunteers)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Personal Profile -->
                    <div class="col-lg-6">
                        <h4 class="fs-6 fw-bold border-bottom pb-2 mb-3 text-primary">Personal Details</h4>

                        <!-- First Name -->
                        <div class="mb-3">
                            <label for="first_name" class="form-label font-weight-bold mb-1">First Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="text" name="first_name" id="first_name" class="form-control" placeholder="e.g. Maria" required>
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div class="mb-3">
                            <label for="last_name" class="form-label font-weight-bold mb-1">Last Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="text" name="last_name" id="last_name" class="form-control" placeholder="e.g. Santos" required>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label font-weight-bold mb-1">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" class="form-control" placeholder="e.g. user@sinalhan-hc.local">
                            </div>
                        </div>

                        <!-- Contact Number -->
                        <div class="mb-3">
                            <label for="contact_number" class="form-label font-weight-bold mb-1">Contact Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="contact_number" id="contact_number" class="form-control" placeholder="e.g. 09123456789" max-length="15">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 border-color">

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Account</button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addUserForm');

    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const pass = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const role = document.getElementById('role').value;
        const contact = document.getElementById('contact_number').value.trim();

        // 1. Username alphanumeric check
        if (!/^[a-zA-Z0-9_]{3,30}$/.test(username)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Username',
                text: 'Username must be 3-30 characters containing only letters, numbers, or underscores.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // 2. Password Length validation
        if (pass.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Password Too Short',
                text: 'Password must be at least 6 characters long.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // 3. Passwords Match validation
        if (pass !== confirm) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'Passwords do not match. Please verify.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // 4. Contact format validation (if provided)
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

<?php
// auth/two_fa.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/encryption.php';

require_role(['admin', 'staff', 'bhw']);

$page_title = 'Two-Factor Authentication';
$active_menu = 'profile';

$userId = (int)($_SESSION['user_id'] ?? 0);
$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->prepare("SELECT username, two_fa_secret, two_fa_enabled FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Profile Error',
            'message' => 'User account not found.'
        ];
        header('Location: ' . BASE_URL . 'auth/profile.php');
        exit;
    }

    $is2faEnabled = (int)($user['two_fa_enabled'] ?? 0);

} catch (Exception $e) {
    error_log("2FA settings load failed: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'Failed to load authentication settings.'
    ];
    header('Location: ' . BASE_URL . 'auth/profile.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Two-Factor Authentication (2FA)</h2>
            <p class="text-secondary mb-0">Secure your account by requiring a 6-digit Security PIN during login.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>auth/profile.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Profile</span>
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card-custom">
                <div class="card-custom-header">
                    <h3 class="card-custom-title">
                        <i class="bi bi-shield-lock-fill text-primary"></i> 
                        2FA PIN Status: 
                        <?php if ($is2faEnabled): ?>
                            <span class="badge bg-success ms-2">Enabled</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2">Disabled</span>
                        <?php endif; ?>
                    </h3>
                </div>
                
                <div class="card-custom-body p-4">
                    <?php if (!$is2faEnabled): ?>
                        <!-- Setup Flow -->
                        <h5 class="fw-bold mb-3 text-secondary">Set a 6-Digit Security PIN</h5>
                        <p class="text-secondary small mb-4">
                            Please choose a secure 6-digit numeric PIN. You will need to enter this PIN every time you log in to your account.
                        </p>

                        <form action="<?= BASE_URL ?>auth/two_fa_process.php" method="POST" class="needs-validation" novalidate id="setupPinForm">
                            <input type="hidden" name="action" value="enable">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label for="pin" class="form-label fw-semibold">Choose 6-Digit PIN</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" 
                                           name="pin" 
                                           id="pin" 
                                           class="form-control text-center fw-bold" 
                                           placeholder="••••••" 
                                           required 
                                           maxlength="6" 
                                           pattern="\d{6}"
                                           inputmode="numeric"
                                           style="letter-spacing: 6px; font-size: 18px;">
                                </div>
                                <div class="invalid-feedback">Please enter exactly 6 numeric digits.</div>
                            </div>

                            <div class="mb-4">
                                <label for="pin_confirm" class="form-label fw-semibold">Confirm 6-Digit PIN</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-check-circle-fill"></i></span>
                                    <input type="password" 
                                           name="pin_confirm" 
                                           id="pin_confirm" 
                                           class="form-control text-center fw-bold" 
                                           placeholder="••••••" 
                                           required 
                                           maxlength="6" 
                                           pattern="\d{6}"
                                           inputmode="numeric"
                                           style="letter-spacing: 6px; font-size: 18px;">
                                </div>
                                <div class="invalid-feedback">Please confirm your 6-digit PIN.</div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>auth/profile.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                                <button type="submit" class="btn btn-primary px-5 py-2">Enable 2FA PIN</button>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- Disable Flow -->
                        <div class="p-3 bg-light rounded-3 mb-4 border border-warning">
                            <h5 class="fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Warning</h5>
                            <p class="text-secondary small mb-0">
                                Disabling two-factor authentication removes the secondary login security PIN guard. Your account will only be protected by your password.
                            </p>
                        </div>

                        <form action="<?= BASE_URL ?>auth/two_fa_process.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="disable">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-4">
                                <label for="pin" class="form-label fw-semibold">Enter your 6-Digit Security PIN to Disable</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" 
                                           name="code" 
                                           id="pin" 
                                           class="form-control text-center fw-bold" 
                                           placeholder="••••••" 
                                           required 
                                           maxlength="6" 
                                           pattern="\d{6}"
                                           inputmode="numeric"
                                           style="letter-spacing: 6px; font-size: 18px;">
                                </div>
                                <div class="invalid-feedback">Please enter your 6-digit PIN.</div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>auth/profile.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                                <button type="submit" class="btn btn-danger px-5 py-2">Disable 2FA PIN</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Form verification for PIN match
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('setupPinForm');
        if (form) {
            form.addEventListener('submit', function(event) {
                const pin = document.getElementById('pin').value;
                const pinConfirm = document.getElementById('pin_confirm').value;
                
                if (pin !== pinConfirm) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'PIN Match Error',
                        text: 'The selected 6-digit PIN and confirmation PIN do not match.'
                    });
                }
            });
        }
    });
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

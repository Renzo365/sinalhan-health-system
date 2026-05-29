<?php
// auth/two_fa.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';
require_once __DIR__ . '/../includes/totp.php';
require_once __DIR__ . '/../config/database.php';

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

    // If not enabled and no temp secret, generate one
    if (!$is2faEnabled && empty($_SESSION['temp_2fa_secret'])) {
        $_SESSION['temp_2fa_secret'] = TOTP::generateSecret();
    }
    
    $secret = $is2faEnabled ? $user['two_fa_secret'] : $_SESSION['temp_2fa_secret'];
    $qrData = TOTP::getQRUrl($user['username'], $secret);
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrData);

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
            <p class="text-secondary mb-0">Secure your account by requiring an authenticator code during login.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>auth/profile.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Profile</span>
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-custom-header">
                    <h3 class="card-custom-title">
                        <i class="bi bi-shield-lock-fill text-primary"></i> 
                        2FA Configuration Status: 
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
                        <div class="row align-items-center g-4">
                            <div class="col-md-5 text-center border-end">
                                <h5 class="fw-bold mb-3 text-secondary">Step 1: Scan QR Code</h5>
                                <div class="bg-white p-3 d-inline-block rounded-3 border mb-2">
                                    <img src="<?= $qrUrl ?>" alt="2FA QR Code" class="img-fluid" style="width: 200px; height: 200px;">
                                </div>
                                <div class="mt-2">
                                    <small class="text-secondary d-block">Or enter secret key manually:</small>
                                    <code class="fw-bold fs-6 text-teal" style="letter-spacing: 1px;"><?= chunk_split($secret, 4, ' ') ?></code>
                                </div>
                            </div>
                            
                            <div class="col-md-7">
                                <h5 class="fw-bold mb-3 text-secondary">Step 2: Enter Verification Code</h5>
                                <p class="text-secondary small">
                                    Install Google Authenticator, Authy, or Microsoft Authenticator app on your mobile device. Scan the QR code, then enter the 6-digit verification code below to verify setup.
                                </p>

                                <form action="<?= BASE_URL ?>auth/two_fa_process.php" method="POST" class="needs-validation" novalidate>
                                    <input type="hidden" name="action" value="enable">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    
                                    <div class="mb-4">
                                        <label for="code" class="form-label font-weight-bold">6-Digit Verification Code</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                                            <input type="text" 
                                                   name="code" 
                                                   id="code" 
                                                   class="form-control text-center fw-bold" 
                                                   placeholder="000000" 
                                                   required 
                                                   maxlength="6" 
                                                   pattern="\d{6}"
                                                   style="letter-spacing: 4px; font-size: 18px;">
                                        </div>
                                        <small class="text-secondary d-block mt-1">Codes change every 30 seconds.</small>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?= BASE_URL ?>auth/profile.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary px-5 py-2">Enable 2FA</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Disable Flow -->
                        <div class="p-3 bg-light rounded-3 mb-4 border border-warning">
                            <h5 class="fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Warning</h5>
                            <p class="text-secondary small mb-0">
                                Disabling two-factor authentication removes the secondary login security guard. Your account will only be protected by your password.
                            </p>
                        </div>

                        <form action="<?= BASE_URL ?>auth/two_fa_process.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="disable">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-4">
                                <label for="code" class="form-label font-weight-bold">Confirm code to disable 2FA</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="text" 
                                           name="code" 
                                           id="code" 
                                           class="form-control text-center fw-bold" 
                                           placeholder="000000" 
                                           required 
                                           maxlength="6" 
                                           pattern="\d{6}"
                                           style="letter-spacing: 4px; font-size: 18px;">
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>auth/profile.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                                <button type="submit" class="btn btn-danger px-5 py-2">Disable 2FA</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>

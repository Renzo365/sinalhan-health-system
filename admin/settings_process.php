<?php
// admin/settings_process.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log_activity.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/encryption.php';

require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/settings.php');
    if (!defined('TESTING')) exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Security Error',
        'message' => 'CSRF verification failed.'
    ];
    header('Location: ' . BASE_URL . 'admin/settings.php');
    if (!defined('TESTING')) exit;
}

$action = $_POST['action'] ?? '';
$pdo = Database::getInstance()->getConnection();

try {
    if ($action === 'save_clinic_info') {
        $name = trim($_POST['clinic_name'] ?? '');
        $address = trim($_POST['clinic_address'] ?? '');
        $contact = trim($_POST['clinic_contact'] ?? '');
        $email = trim($_POST['clinic_email'] ?? '');

        if (empty($name)) throw new Exception("Clinic name cannot be empty.");

        set_setting($pdo, 'clinic_name', $name);
        set_setting($pdo, 'clinic_address', $address);
        set_setting($pdo, 'clinic_contact', $contact);
        set_setting($pdo, 'clinic_email', $email);

        log_activity($pdo, "Updated clinic profile settings", "Admin");
        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Settings Saved',
            'message' => 'Clinic profile details have been successfully updated.'
        ];

    } elseif ($action === 'upload_logo') {
        if (!isset($_FILES['clinic_logo']) || $_FILES['clinic_logo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Failed to upload file or file slot was empty.");
        }

        $file = $_FILES['clinic_logo'];
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only PNG and JPEG/JPG are allowed.");
        }

        // Limit size to 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception("File is too large. Maximum size is 2MB.");
        }

        // Create images folder if not exists
        $targetDir = __DIR__ . '/../assets/images';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'clinic_logo_' . time() . '.' . $ext;
        $targetPath = $targetDir . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Delete old logo file if exists
            $oldLogo = get_setting($pdo, 'clinic_logo');
            if (!empty($oldLogo)) {
                $oldPath = __DIR__ . '/../' . $oldLogo;
                if (file_exists($oldPath) && is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            set_setting($pdo, 'clinic_logo', 'assets/images/' . $fileName);
            log_activity($pdo, "Uploaded clinic logo image", "Admin");
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Logo Uploaded',
                'message' => 'Clinic logo has been updated successfully.'
            ];
        } else {
            throw new Exception("Failed to save uploaded file to assets folder.");
        }

    } elseif ($action === 'save_queue_settings') {
        $prefixes = $_POST['prefixes'] ?? [];
        
        $pdo->beginTransaction();
        foreach ($prefixes as $serviceId => $prefix) {
            $serviceId = (int)$serviceId;
            $prefix = strtoupper(trim($prefix));

            if (empty($prefix)) {
                throw new Exception("Queue ticket prefixes cannot be empty.");
            }
            if (strlen($prefix) > 10) {
                throw new Exception("Prefixes cannot exceed 10 characters.");
            }

            // Verify prefix is unique among service types
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM service_types WHERE prefix = ? AND service_id != ?");
            $checkStmt->execute([$prefix, $serviceId]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("The prefix '{$prefix}' is already assigned to another service.");
            }

            $updateStmt = $pdo->prepare("UPDATE service_types SET prefix = ? WHERE service_id = ?");
            $updateStmt->execute([$prefix, $serviceId]);
        }
        $pdo->commit();

        log_activity($pdo, "Updated service queue ticket prefixes", "Admin");
        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Prefixes Updated',
            'message' => 'Service ticketing prefixes have been updated successfully.'
        ];

    } elseif ($action === 'save_security_settings') {
        $lifetime = (int)($_POST['session_lifetime_minutes'] ?? 30);
        $require2FA = isset($_POST['require_2fa']) ? '1' : '0';

        if ($lifetime < 5 || $lifetime > 1440) {
            throw new Exception("Session lifetime must be between 5 and 1440 minutes.");
        }

        set_setting($pdo, 'session_lifetime_minutes', $lifetime);
        set_setting($pdo, 'require_2fa', $require2FA);

        log_activity($pdo, "Updated security configuration settings", "Admin");
        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Security Settings Saved',
            'message' => 'Security and HIPAA policies have been successfully saved.'
        ];

    } elseif ($action === 'rotate_keys') {
        // Cryptographic Key Rotation logic
        $newKey = trim($_POST['new_encryption_key'] ?? '');
        if (strlen($newKey) < 16) {
            throw new Exception("New encryption key must be at least 16 characters long.");
        }

        // Helper local functions for rotative mapping
        $encryptWithKey = function($plaintext, $key) {
            if (empty($plaintext)) return $plaintext;
            $key = substr(hash('sha256', $key, true), 0, 32);
            $cipher = 'aes-256-cbc';
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            if ($ciphertext_raw === false) return $plaintext;
            return 'enc::' . base64_encode($iv) . '::' . base64_encode($ciphertext_raw);
        };

        $decryptWithKey = function($ciphertext, $key) {
            if (empty($ciphertext) || strpos($ciphertext, 'enc::') !== 0) return $ciphertext;
            $parts = explode('::', $ciphertext);
            if (count($parts) !== 3) return $ciphertext;
            $iv = base64_decode($parts[1]);
            $ciphertext_raw = base64_decode($parts[2]);
            $key = substr(hash('sha256', $key, true), 0, 32);
            $cipher = 'aes-256-cbc';
            $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            if ($plaintext === false) return $ciphertext;
            return $plaintext;
        };

        $oldKey = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_sinalhan_health_center_key_32_bytes_long_123';

        $pdo->beginTransaction();

        // 1. Process patients demographics
        $patStmt = $pdo->query("SELECT patient_id, medical_history, allergies FROM patients");
        $patUpdate = $pdo->prepare("UPDATE patients SET medical_history = ?, allergies = ? WHERE patient_id = ?");
        while ($p = $patStmt->fetch()) {
            $histDec = $decryptWithKey($p['medical_history'], $oldKey);
            $allerDec = $decryptWithKey($p['allergies'], $oldKey);
            
            $histEnc = $encryptWithKey($histDec, $newKey);
            $allerEnc = $encryptWithKey($allerDec, $newKey);
            
            $patUpdate->execute([$histEnc, $allerEnc, $p['patient_id']]);
        }

        // 2. Process health records
        $recStmt = $pdo->query("SELECT record_id, chief_complaint, diagnosis, treatment, prescription, notes FROM health_records");
        $recUpdate = $pdo->prepare("UPDATE health_records SET chief_complaint = ?, diagnosis = ?, treatment = ?, prescription = ?, notes = ? WHERE record_id = ?");
        while ($r = $recStmt->fetch()) {
            $chiefDec = $decryptWithKey($r['chief_complaint'], $oldKey);
            $diagDec = $decryptWithKey($r['diagnosis'], $oldKey);
            $treatDec = $decryptWithKey($r['treatment'], $oldKey);
            $prescDec = $decryptWithKey($r['prescription'], $oldKey);
            $notesDec = $decryptWithKey($r['notes'], $oldKey);

            $chiefEnc = $encryptWithKey($chiefDec, $newKey);
            $diagEnc = $encryptWithKey($diagDec, $newKey);
            $treatEnc = $encryptWithKey($treatDec, $newKey);
            $prescEnc = $encryptWithKey($prescDec, $newKey);
            $notesEnc = $encryptWithKey($notesDec, $newKey);

            $recUpdate->execute([$chiefEnc, $diagEnc, $treatEnc, $prescEnc, $notesEnc, $r['record_id']]);
        }

        // 3. Update the config/app.php file with the new key definition
        $appPath = __DIR__ . '/../config/app.php';
        if (!file_exists($appPath)) {
            throw new Exception("App configuration file config/app.php not found.");
        }
        $content = file_get_contents($appPath);
        $pattern = "/define\(\s*'ENCRYPTION_KEY'\s*,\s*'.*?'\s*\);/";
        $replacement = "define('ENCRYPTION_KEY', '" . addslashes($newKey) . "');";
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent === null) {
            throw new Exception("Regex match error while updating config/app.php.");
        }

        if (file_put_contents($appPath, $newContent) === false) {
            throw new Exception("Failed to write to config/app.php. Check folder permissions.");
        }

        $pdo->commit();

        log_activity($pdo, "Rotated system master encryption key", "System", null, "Updated patient records demographics & clinical records.");
        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Master Key Rotated',
            'message' => 'Database records successfully re-encrypted with the new key.'
        ];
    } else {
        throw new Exception("Invalid form action.");
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Settings error: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Action Failed',
        'message' => $e->getMessage()
    ];
}

header('Location: ' . BASE_URL . 'admin/settings.php');
if (!defined('TESTING')) exit;

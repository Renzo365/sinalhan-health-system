<?php
// includes/header.php
if (!headers_sent()) {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:;");
}
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- 
      Offline-First Localization (Capstone Defense Documentation):
      All external stylesheet dependencies, hosted fonts, and CDNs are migrated to local paths.
      The Content Security Policy (CSP) header is locked down to 'self' to enforce local resource execution,
      improving system load times, preventing layout breaks offline, and hardening overall clinic portal security.
    -->
    <!-- Local Bootstrap 5 CSS -->
    <link href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Local Bootstrap Icons CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
    
    <!-- Local SweetAlert2 CSS -->
    <link href="<?= BASE_URL ?>assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Main Custom CSS Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <?php $cssUrl = (strpos($css, 'http://') === 0 || strpos($css, 'https://') === 0 || strpos($css, '//') === 0) ? $css : BASE_URL . $css; ?>
            <link rel="stylesheet" href="<?= $cssUrl ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <script>
        (function() {
            const sessionTheme = '<?= $_SESSION['theme'] ?? '' ?>';
            const sessionFontSize = '<?= $_SESSION['font_size'] ?? '' ?>';

            let theme = sessionTheme;
            if (!theme) {
                theme = localStorage.getItem('theme') || 'light';
            }
            if (theme === 'dark') {
                document.body.classList.add('dark-theme');
            } else {
                document.body.classList.remove('dark-theme');
            }

            let fontSize = sessionFontSize;
            if (!fontSize) {
                fontSize = localStorage.getItem('fontSize') || 'normal';
            }
            document.body.classList.remove('font-md', 'font-lg');
            if (fontSize === 'medium') {
                document.body.classList.add('font-md');
            } else if (fontSize === 'large') {
                document.body.classList.add('font-lg');
            }

            // Sync back to localStorage for client consistency
            if (sessionTheme) {
                localStorage.setItem('theme', sessionTheme);
            }
            if (sessionFontSize) {
                localStorage.setItem('fontSize', sessionFontSize);
            }
        })();
    </script>
    <div id="wrapper">

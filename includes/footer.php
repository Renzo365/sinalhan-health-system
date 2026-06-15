<?php
// includes/footer.php
require_once __DIR__ . '/../config/app.php';
?>
        <!-- Footer -->
        <footer class="footer-text">
            <span>&copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. All rights reserved.</span>
        </footer>
    </div> <!-- Close #content-wrapper -->
</div> <!-- Close #wrapper -->

<!-- 
  Offline-First Localization (Capstone Defense Documentation):
  All core external JS dependencies (jQuery, Bootstrap, SweetAlert2, and Chart.js) are loaded from local assets.
  This prevents script rendering delays and blocking errors when the health clinic system is run in offline/intranet environments.
-->
<!-- Local jQuery -->
<script src="<?= BASE_URL ?>assets/vendor/jquery/jquery.min.js"></script>

<!-- Local Bootstrap Bundle with Popper -->
<script src="<?= BASE_URL ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Local SweetAlert2 -->
<script src="<?= BASE_URL ?>assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<!-- Local Chart.js -->
<script src="<?= BASE_URL ?>assets/vendor/chart.js/chart.umd.js"></script>

<!-- Layout Main JS file -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

<?php if (isset($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
        <?php $jsUrl = (strpos($js, 'http://') === 0 || strpos($js, 'https://') === 0 || strpos($js, '//') === 0) ? $js : BASE_URL . $js; ?>
        <script src="<?= $jsUrl ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- PWA Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?= BASE_URL ?>service-worker.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
                console.warn('ServiceWorker registration failed: ', err);
            });
    });
}
</script>

</body>
</html>

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

<!-- core JS libraries CDN -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

<!-- Chart.js (Loaded only when needed or globally for capstone dashboard ease) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

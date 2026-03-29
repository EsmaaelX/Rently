    </main>

    <!-- ─── Footer ─────────────────────────────────────────────── -->
    <footer class="site-footer mt-auto">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-4">
                    <h5 class="fw-bold"><i class="bi bi-house-heart-fill me-2"></i><?= t('app_name') ?></h5>
                    <p class="text-muted small"><?= t('footer_desc') ?></p>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-semibold mb-3"><?= t('platform') ?></h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?= baseUrl() ?>"><?= t('explore') ?></a></li>
                        <li class="mb-2"><a href="<?= baseUrl() ?>index.php?page=register"><?= t('sign_up') ?></a></li>
                        <li class="mb-2"><a href="<?= baseUrl() ?>index.php?page=login"><?= t('login') ?></a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-semibold mb-3"><?= t('categories') ?></h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?= baseUrl() ?>?category=apartment"><?= t('apartment') ?></a></li>
                        <li class="mb-2"><a href="<?= baseUrl() ?>?category=car"><?= t('car') ?></a></li>
                        <li class="mb-2"><a href="<?= baseUrl() ?>?category=sport_venue"><?= t('sport_venue') ?></a></li>
                        <li class="mb-2"><a href="<?= baseUrl() ?>?category=equipment"><?= t('equipment') ?></a></li>
                        <li class="mb-2"><a href="<?= baseUrl() ?>?category=studio"><?= t('studio') ?></a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h6 class="fw-semibold mb-3"><?= t('stay_connected') ?></h6>
                    <div class="d-flex gap-3 fs-4">
                        <a href="https://facebook.com" target="_blank" class="footer-social"><i class="bi bi-facebook"></i></a>
                        <a href="https://twitter.com" target="_blank" class="footer-social"><i class="bi bi-twitter-x"></i></a>
                        <a href="https://instagram.com" target="_blank" class="footer-social"><i class="bi bi-instagram"></i></a>
                        <a href="https://linkedin.com" target="_blank" class="footer-social"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 border-secondary">
            <p class="text-center text-muted small mb-0">
                &copy; <?= date('Y') ?> <?= t('app_name') ?>. <?= t('all_rights') ?>
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js (for Admin) -->
    <?php if (($currentPage ?? '') === 'admin'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <?php endif; ?>

    <!-- Custom JS -->
    <script>
        // Pass config to JS
        window.RENTLY = {
            baseUrl: '<?= baseUrl() ?>',
            lang: '<?= getCurrentLang() ?>',
            theme: '<?= getCurrentTheme() ?>',
            isLoggedIn: <?= isLoggedIn() ? 'true' : 'false' ?>,
            isAdmin: <?= isAdmin() ? 'true' : 'false' ?>,
            translations: <?= json_encode(getLangStrings()) ?>
        };
    </script>
    <script src="<?= baseUrl() ?>assets/js/app.js"></script>
</body>
</html>

    </main>

    <!-- ─── Footer ─────────────────────────────────────────────── -->
    <footer class="site-footer mt-auto">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-4">
                    <h5 class="fw-bold"><i class="bi bi-house-heart-fill me-2"></i>Rently</h5>
                    <p class="text-muted small">
                        The easiest way to rent apartments, cars, and sports venues
                        from real people. Join the sharing economy today.
                    </p>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-semibold mb-3">Platform</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?= baseUrl() ?>">Explore</a></li>
                        <li class="mb-2"><a href="<?= baseUrl() ?>index.php?page=register">Sign Up</a></li>
                        <li class="mb-2"><a href="<?= baseUrl() ?>index.php?page=login">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-semibold mb-3">Categories</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#">Apartments</a></li>
                        <li class="mb-2"><a href="#">Cars</a></li>
                        <li class="mb-2"><a href="#">Sport Venues</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h6 class="fw-semibold mb-3">Stay Connected</h6>
                    <div class="d-flex gap-3 fs-4">
                        <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="footer-social"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="footer-social"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 border-secondary">
            <p class="text-center text-muted small mb-0">
                &copy; <?= date('Y') ?> Rently. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?= baseUrl() ?>assets/js/app.js"></script>
</body>
</html>

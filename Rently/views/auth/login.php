<?php $pageTitle = 'Login'; ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="auth-section d-flex align-items-center min-vh-75">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="glass-card p-5 shadow-lg">
                    <div class="text-center mb-4">
                        <i class="bi bi-house-heart-fill display-4 text-accent"></i>
                        <h2 class="fw-bold mt-2">Welcome Back</h2>
                        <p class="text-muted">Sign in to your Rently account</p>
                    </div>

                    <form method="POST" action="<?= baseUrl() ?>index.php?page=login" id="login-form">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="you@example.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="••••••••" required minlength="6">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-accent w-100 btn-lg mb-3" id="login-btn">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </button>
                    </form>

                    <p class="text-center text-muted mb-0">
                        Don't have an account?
                        <a href="<?= baseUrl() ?>index.php?page=register" class="text-accent fw-semibold">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

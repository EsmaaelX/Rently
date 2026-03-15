<?php $pageTitle = 'Create Account'; ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="auth-section d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="glass-card p-5 shadow-lg">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus-fill display-4 text-accent"></i>
                        <h2 class="fw-bold mt-2">Create Your Account</h2>
                        <p class="text-muted">Join Rently and start renting today</p>
                    </div>

                    <form method="POST" action="<?= baseUrl() ?>index.php?page=register" id="register-form">
                        <div class="mb-3">
                            <label for="full_name" class="form-label fw-semibold">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       placeholder="John Doe" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reg-email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="reg-email" name="email"
                                       placeholder="you@example.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label fw-semibold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                       placeholder="050-123-4567">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="reg-password" class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="reg-password" name="password"
                                           placeholder="••••••••" required minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password"
                                           name="confirm_password" placeholder="••••••••" required minlength="6">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="role" class="form-label fw-semibold">I want to</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="renter">🏠 Rent things (Renter)</option>
                                <option value="owner">📤 List my assets (Owner)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-accent w-100 btn-lg mb-3" id="register-btn">
                            <i class="bi bi-person-plus me-2"></i>Create Account
                        </button>
                    </form>

                    <p class="text-center text-muted mb-0">
                        Already have an account?
                        <a href="<?= baseUrl() ?>index.php?page=login" class="text-accent fw-semibold">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

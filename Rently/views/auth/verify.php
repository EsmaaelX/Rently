<?php $pageTitle = t('verify_email'); ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="auth-section d-flex align-items-center min-vh-75">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="glass-card p-5 shadow-lg">
                    <div class="text-center mb-4">
                        <div style="font-size: 4rem;">📧</div>
                        <h2 class="fw-bold mt-2"><?= t('verify_email') ?></h2>
                        <p class="text-muted">
                            <?= t('verify_subtitle') ?><br>
                            <strong class="text-accent"><?= sanitize($_SESSION['pending_verification_email'] ?? '') ?></strong>
                        </p>
                    </div>

                    <form method="POST" action="<?= baseUrl() ?>index.php?page=verify" id="verify-form">
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-center d-block"><?= t('enter_code') ?></label>
                            <div class="code-input-group" id="code-inputs">
                                <input type="text" maxlength="1" class="code-digit" data-index="0" autofocus>
                                <input type="text" maxlength="1" class="code-digit" data-index="1">
                                <input type="text" maxlength="1" class="code-digit" data-index="2">
                                <input type="text" maxlength="1" class="code-digit" data-index="3">
                                <input type="text" maxlength="1" class="code-digit" data-index="4">
                                <input type="text" maxlength="1" class="code-digit" data-index="5">
                            </div>
                            <input type="hidden" name="verification_code" id="verification-code">
                        </div>

                        <button type="submit" class="btn btn-accent w-100 btn-lg mb-3" id="verify-btn">
                            <i class="bi bi-check-circle me-2"></i><?= t('verify_btn') ?>
                        </button>
                    </form>

                    <p class="text-center text-muted mb-0">
                        <?= t('didnt_receive') ?>
                        <a href="<?= baseUrl() ?>index.php?page=resend-code" class="text-accent fw-semibold"><?= t('resend_code') ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

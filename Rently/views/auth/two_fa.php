<?php $pageTitle = t('twofa_title'); ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="auth-section d-flex align-items-center min-vh-75">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="glass-card p-5 shadow-lg">
                    <div class="text-center mb-4">
                        <div style="font-size: 4rem;">🔐</div>
                        <h2 class="fw-bold mt-2"><?= t('twofa_title') ?></h2>
                        <p class="text-muted"><?= t('twofa_subtitle') ?></p>
                    </div>

                    <form method="POST" action="<?= baseUrl() ?>index.php?page=two-fa" id="twofa-form">
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-center d-block"><?= t('twofa_code') ?></label>
                            <div class="code-input-group" id="twofa-inputs">
                                <input type="text" maxlength="1" class="code-digit" data-index="0" autofocus>
                                <input type="text" maxlength="1" class="code-digit" data-index="1">
                                <input type="text" maxlength="1" class="code-digit" data-index="2">
                                <input type="text" maxlength="1" class="code-digit" data-index="3">
                                <input type="text" maxlength="1" class="code-digit" data-index="4">
                                <input type="text" maxlength="1" class="code-digit" data-index="5">
                            </div>
                            <input type="hidden" name="two_fa_code" id="twofa-code">
                        </div>

                        <button type="submit" class="btn btn-accent w-100 btn-lg mb-3">
                            <i class="bi bi-shield-check me-2"></i><?= t('twofa_verify') ?>
                        </button>
                    </form>

                    <p class="text-center text-muted mb-0">
                        <a href="<?= baseUrl() ?>index.php?page=login" class="text-accent fw-semibold">
                            <i class="bi bi-arrow-left me-1"></i><?= t('back_to_home') ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

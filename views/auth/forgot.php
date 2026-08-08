<div class="auth-wrap">
    <div class="auth-left">
        <div class="brand">
            <?= render_logo('login') ?>
            <div>
                <h5 class="mb-0 text-white">Password Recovery</h5>
                <small style="opacity:.85">Contact your RHU administrator</small>
            </div>
        </div>
        <div style="position:relative;z-index:2">
            <h2>Trouble signing in?</h2>
            <p class="lead">
                Enter your registered email or username to generate a reset code.
                For security reasons, present the code to the RHU administrator
                who will confirm your identity and set a new password for you.
            </p>
        </div>
        <div style="position:relative;z-index:2;font-size:12px;opacity:.85">© <?= date('Y') ?> RHU Makilala</div>
    </div>

    <div class="auth-right">
        <div class="auth-card">
            <h3>Forgot password</h3>
            <p class="lead">We'll generate a one-time reset code.</p>

            <?php $m = flash('forgot_msg'); if ($m): ?>
                <div class="alert alert-<?= e($m['type']) ?> rounded-lg py-2"><?= e($m['msg']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('index.php?url=auth/forgot')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Email or Username</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="text" name="email" class="form-control" required>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Generate reset code</button>
            </form>

            <div class="auth-footer">
                <a href="<?= e(url('index.php?url=auth/login')) ?>" class="link-primary">← Back to sign in</a>
            </div>
        </div>
    </div>
</div>

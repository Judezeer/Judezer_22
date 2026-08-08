<div class="auth-wrap">

    <!-- LEFT SIDE -->
    <div class="auth-left">
        <div class="brand">
            <?= render_logo('login') ?>
            <div>
                <h5 class="mb-0 text-white"><?= e(setting('site_name','RHU Makilala')) ?></h5>
                <small style="opacity:.85">Health Management System</small>
            </div>
        </div>

        <div style="position:relative;z-index:2">
            <h2>Modern healthcare<br>for the community.</h2>
            <p class="lead">
                Unified appointments, health records, medicine inventory and dispensing
                — designed for the Rural Health Unit of Makilala.
            </p>

            <div class="auth-features">
                <div class="feat">
                    <i class="fa-solid fa-calendar-check"></i>
                    <h6>Appointments</h6>
                    <small>Book and manage visits</small>
                </div>
                <div class="feat">
                    <i class="fa-solid fa-notes-medical"></i>
                    <h6>Health Records</h6>
                    <small>Complete medical history</small>
                </div>
                <div class="feat">
                    <i class="fa-solid fa-pills"></i>
                    <h6>Inventory</h6>
                    <small>Batches &amp; expiry tracking</small>
                </div>
                <div class="feat">
                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                    <h6>Dispensing</h6>
                    <small>Automatic stock control</small>
                </div>
            </div>
        </div>

        <div style="position:relative;z-index:2;font-size:12px;opacity:.85">
            © <?= date('Y') ?> Rural Health Unit of Makilala · Cotabato
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="auth-right">
        <div class="auth-card">
            <h3>Welcome back</h3>
            <p class="lead">Please sign in to your account to continue.</p>

            <?php $err = flash('login_error'); if ($err): ?>
                <div class="alert alert-<?= e($err['type']) ?> rounded-lg py-2"><?= e($err['msg']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('index.php?url=auth/login')) ?>" autocomplete="off">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Username or Email</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="identifier" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        Password
                        <a href="<?= e(url('index.php?url=auth/forgot')) ?>" class="link-primary" style="font-weight:500;font-size:.8rem">Forgot?</a>
                    </label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rm" name="remember" value="1">
                        <label class="form-check-label" for="rm">Remember me</label>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In
                </button>
            </form>

            <div class="auth-footer">
                DESIGN BY RALPH OSING
            </div>
        </div>
    </div>

</div>

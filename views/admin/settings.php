<h4 class="fw-bold mb-3">System Settings</h4>

<form method="post" action="<?= e(url('index.php?url=admin/settings')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- Logo card -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="fa-solid fa-image text-primary me-2"></i>Clinic Logo</div>
                <div class="card-body text-center">
                    <?php $currentLogo = setting('clinic_logo'); ?>
                    <div class="mx-auto mb-3" style="width:140px;height:140px;border-radius:24px;overflow:hidden;background:#F8FAFC;border:2px dashed #CBD5E1;display:flex;align-items:center;justify-content:center">
                        <?php if ($currentLogo && is_file(UPLOAD_PATH . $currentLogo)): ?>
                            <img id="logoPreview" src="<?= e(UPLOAD_URL . $currentLogo) ?>" alt="Clinic logo" style="width:100%;height:100%;object-fit:cover">
                        <?php else: ?>
                            <div id="logoPreview" style="color:#94A3B8;font-size:44px"><i class="fa-solid fa-hospital"></i></div>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted small mb-3">
                        Upload your official RHU logo. Recommended: <strong>square image</strong>,
                        at least 200×200px. JPG, PNG, WEBP, or GIF, max 2MB.
                    </p>

                    <div class="mb-2">
                        <input type="file" class="form-control" name="logo" id="logoInput" accept="image/jpeg,image/png,image/webp,image/gif">
                    </div>

                    <?php if ($currentLogo): ?>
                        <div class="form-check d-inline-block mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rm_logo">
                            <label class="form-check-label small text-danger" for="rm_logo">
                                <i class="fa-solid fa-trash me-1"></i>Remove current logo
                            </label>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-light border rounded-lg mt-3 mb-0 small text-start">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                        Your logo will appear on the <strong>sidebar</strong>,
                        <strong>login page</strong>, and printed
                        <strong>dispensing receipts</strong>.
                    </div>
                </div>
            </div>
        </div>

        <!-- Text settings card -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-sliders text-primary me-2"></i>General Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12"><label class="form-label">Site name</label>
                            <input class="form-control" name="site_name" value="<?= e($settings['site_name'] ?? '') ?>"></div>
                        <div class="col-md-12"><label class="form-label">Tagline</label>
                            <input class="form-control" name="site_tagline" value="<?= e($settings['site_tagline'] ?? '') ?>"></div>
                        <div class="col-md-12"><label class="form-label">Clinic address</label>
                            <input class="form-control" name="clinic_address" value="<?= e($settings['clinic_address'] ?? '') ?>"></div>
                        <div class="col-md-12"><label class="form-label">Clinic contact</label>
                            <input class="form-control" name="clinic_contact" value="<?= e($settings['clinic_contact'] ?? '') ?>"></div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="fa-solid fa-shield-halved text-primary me-2"></i>System Preferences</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Session timeout (seconds)</label>
                            <input type="number" class="form-control" name="session_timeout" value="<?= e($settings['session_timeout'] ?? '1800') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Default low-stock threshold</label>
                            <input type="number" class="form-control" name="low_stock_default" value="<?= e($settings['low_stock_default'] ?? '20') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Near-expiry alert (days)</label>
                            <input type="number" class="form-control" name="near_expiry_days" value="<?= e($settings['near_expiry_days'] ?? '60') ?>"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            <button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-2"></i>Save Settings</button>
        </div>

    </div>
</form>

<?php ob_start(); ?>
<script>
// Live preview when a new logo file is chosen
document.getElementById('logoInput')?.addEventListener('change', function(e){
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        Swal.fire('File too large', 'Please choose an image smaller than 2 MB.', 'warning');
        e.target.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(ev){
        const preview = document.getElementById('logoPreview');
        // Replace with an <img> if it was a placeholder div
        if (preview.tagName === 'IMG') {
            preview.src = ev.target.result;
        } else {
            const img = document.createElement('img');
            img.id = 'logoPreview';
            img.src = ev.target.result;
            img.style.cssText = 'width:100%;height:100%;object-fit:cover';
            preview.parentNode.replaceChild(img, preview);
        }
    };
    reader.readAsDataURL(file);
});
</script>
<?php $extra_scripts = ob_get_clean(); ?>

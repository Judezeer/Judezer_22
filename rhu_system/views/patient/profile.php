<h4 class="fw-bold mb-3">My Profile</h4>

<?php if (!$p): ?>
    <div class="alert alert-warning">Your patient profile has not been created yet. Please visit the RHU staff.</div>
<?php else: ?>
<form method="post" action="<?= e(url('index.php?url=patient/profile')) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3" style="width:96px;height:96px;border-radius:32px;background:linear-gradient(135deg,#DCFCE7,#4ADE80);display:flex;align-items:center;justify-content:center;color:#166534;font-weight:700;font-size:32px">
                        <?= e(strtoupper(substr($p['first_name'],0,1) . substr($p['last_name'],0,1))) ?>
                    </div>
                    <h5 class="fw-bold mb-0"><?= e($p['first_name'].' '.$p['last_name']) ?></h5>
                    <small class="text-muted"><?= e($p['patient_code']) ?></small>
                    <hr>
                    <div class="text-start">
                        <div><i class="fa-solid fa-venus-mars text-muted me-2"></i><?= e(ucfirst($p['sex'])) ?></div>
                        <div><i class="fa-solid fa-cake-candles text-muted me-2"></i><?= e(fmt_date($p['birthdate'])) ?> (<?= age_from($p['birthdate']) ?> yrs)</div>
                        <div><i class="fa-solid fa-id-card text-muted me-2"></i>PhilHealth: <?= e($p['philhealth_no'] ?: '—') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Contact &amp; Medical Info</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Contact no.</label><input class="form-control" name="contact_no" value="<?= e($p['contact_no']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e($p['email']) ?>"></div>
                        <div class="col-md-8"><label class="form-label">Address</label><input class="form-control" name="address" value="<?= e($p['address']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Barangay</label><input class="form-control" name="barangay" value="<?= e($p['barangay']) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Blood type</label><input class="form-control" name="blood_type" value="<?= e($p['blood_type']) ?>"></div>
                        <div class="col-md-9"><label class="form-label">Known allergies</label><input class="form-control" name="allergies" value="<?= e($p['allergies']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Emergency contact name</label><input class="form-control" name="emergency_name" value="<?= e($p['emergency_name']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Emergency contact no.</label><input class="form-control" name="emergency_no" value="<?= e($p['emergency_no']) ?>"></div>
                    </div>
                </div>
                <div class="card-footer text-end bg-white border-top">
                    <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

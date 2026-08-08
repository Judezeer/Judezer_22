<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a class="link-primary" href="<?= e(url('index.php?url=nurse/patients')) ?>">Patients</a></li>
        <li class="breadcrumb-item active"><?= e($p['patient_code']) ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mx-auto mb-3" style="width:96px;height:96px;border-radius:32px;background:linear-gradient(135deg,#DCFCE7,#4ADE80);display:flex;align-items:center;justify-content:center;color:#166534;font-weight:700;font-size:32px">
                    <?= e(strtoupper(substr($p['first_name'],0,1) . substr($p['last_name'],0,1))) ?>
                </div>
                <h5 class="mb-0 fw-bold"><?= e($p['first_name'].' '.$p['middle_name'].' '.$p['last_name']) ?></h5>
                <div class="text-muted"><?= e($p['patient_code']) ?></div>
                <div class="d-flex justify-content-center gap-2 mt-2">
                    <span class="badge bg-primary-subtle text-success rounded-pill px-3 py-2"><?= e(ucfirst($p['sex'])) ?></span>
                    <span class="badge bg-info-subtle text-info rounded-pill px-3 py-2"><?= age_from($p['birthdate']) ?> yrs</span>
                    <?php if(!empty($p['blood_type'])): ?>
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2"><?= e($p['blood_type']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="mb-2"><i class="fa-solid fa-cake-candles text-muted me-2"></i><?= e(fmt_date($p['birthdate'])) ?></div>
                <div class="mb-2"><i class="fa-solid fa-phone text-muted me-2"></i><?= e($p['contact_no'] ?: '—') ?></div>
                <div class="mb-2"><i class="fa-solid fa-envelope text-muted me-2"></i><?= e($p['email'] ?: '—') ?></div>
                <div class="mb-2"><i class="fa-solid fa-location-dot text-muted me-2"></i><?= e($p['address']) ?></div>
                <div class="mb-2"><i class="fa-solid fa-tree-city text-muted me-2"></i><?= e($p['barangay'] ?: '—') ?></div>
                <div class="mb-2"><i class="fa-solid fa-id-card text-muted me-2"></i>PhilHealth: <?= e($p['philhealth_no'] ?: '—') ?></div>
                <div class="mb-2"><i class="fa-solid fa-triangle-exclamation text-muted me-2"></i>Allergies: <?= e($p['allergies'] ?: 'None reported') ?></div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-notes-medical text-primary me-2"></i>Medical History</span>
                <small class="text-muted"><?= count($records) ?> records</small>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Type</th><th>Diagnosis</th><th>Treatment</th><th>Attended by</th></tr></thead>
                    <tbody>
                    <?php if (!$records): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No records yet.</td></tr>
                    <?php else: foreach ($records as $r): ?>
                        <tr>
                            <td><?= e(fmt_date($r['visit_date'])) ?></td>
                            <td><span class="badge bg-info-subtle text-info rounded-pill px-3 py-2"><?= e(ucfirst($r['record_type'])) ?></span></td>
                            <td><?= e($r['diagnosis'] ?: '—') ?></td>
                            <td><?= e($r['treatment'] ?: '—') ?></td>
                            <td><small><?= e($r['attended_by_name'] ?? '—') ?></small></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fa-solid fa-calendar text-primary me-2"></i>Appointments</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (!$appts): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No appointments.</td></tr>
                    <?php else: foreach ($appts as $a): ?>
                        <tr>
                            <td><?= e(fmt_date($a['appointment_date'])) ?></td>
                            <td><?= e(date('g:i A', strtotime($a['appointment_time']))) ?></td>
                            <td><?= e($a['purpose']) ?></td>
                            <td><?= status_badge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

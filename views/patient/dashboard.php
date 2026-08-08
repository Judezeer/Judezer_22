<section class="hero">
    <h1>Hello, <?= e(current_user()['full_name']) ?> 👋</h1>
    <p>View your upcoming appointments, medical history and notifications from the RHU.</p>
    <div class="quick">
        <a class="btn" href="<?= e(url('index.php?url=patient/book')) ?>"><i class="fa-solid fa-calendar-plus me-2"></i>Book Appointment</a>
        <a class="btn" href="<?= e(url('index.php?url=patient/records')) ?>"><i class="fa-solid fa-notes-medical me-2"></i>Medical Records</a>
        <a class="btn" href="<?= e(url('index.php?url=patient/profile')) ?>"><i class="fa-solid fa-user me-2"></i>My Profile</a>
    </div>
</section>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-calendar-check text-primary me-2"></i>Upcoming Appointments</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (!$upcoming): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No upcoming appointments.</td></tr>
                    <?php else: foreach ($upcoming as $a): ?>
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

        <div class="card">
            <div class="card-header"><i class="fa-solid fa-notes-medical text-primary me-2"></i>Recent Medical Records</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Type</th><th>Diagnosis</th><th>Treatment</th></tr></thead>
                    <tbody>
                    <?php if (!$records): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No records yet.</td></tr>
                    <?php else: foreach ($records as $r): ?>
                        <tr>
                            <td><?= e(fmt_date($r['visit_date'])) ?></td>
                            <td><?= e(ucfirst($r['record_type'])) ?></td>
                            <td><?= e($r['diagnosis'] ?: '—') ?></td>
                            <td><?= e($r['treatment'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if ($p): ?>
        <div class="card mb-3">
            <div class="card-body text-center">
                <div class="mx-auto mb-3" style="width:80px;height:80px;border-radius:24px;background:linear-gradient(135deg,#DCFCE7,#4ADE80);display:flex;align-items:center;justify-content:center;color:#166534;font-weight:700;font-size:26px">
                    <?= e(strtoupper(substr($p['first_name'],0,1) . substr($p['last_name'],0,1))) ?>
                </div>
                <h5 class="fw-bold mb-0"><?= e($p['first_name'].' '.$p['last_name']) ?></h5>
                <small class="text-muted"><?= e($p['patient_code']) ?></small>
                <div class="d-flex justify-content-center gap-2 mt-2">
                    <span class="badge bg-primary-subtle text-success rounded-pill px-3 py-2"><?= age_from($p['birthdate']) ?> yrs</span>
                    <?php if($p['blood_type']): ?><span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2"><?= e($p['blood_type']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><i class="fa-solid fa-bell text-primary me-2"></i>Notifications</div>
            <ul class="list-unstyled m-0">
                <?php if (!$notifs): ?><li class="text-center text-muted py-3">No notifications.</li>
                <?php else: foreach ($notifs as $n): ?>
                    <li class="p-3 border-bottom">
                        <div class="fw-semibold"><?= e($n['title']) ?></div>
                        <small class="text-muted"><?= e($n['message']) ?></small>
                        <div><small class="text-muted"><?= e(fmt_datetime($n['created_at'])) ?></small></div>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>

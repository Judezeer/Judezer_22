<div class="text-center mb-3">
    <div style="display:inline-block"><?= render_logo('receipt') ?></div>
    <h4 class="mt-2 mb-0 fw-bold"><?= e(setting('site_name','Rural Health Unit of Makilala')) ?></h4>
    <small class="text-muted"><?= e(setting('clinic_address','Makilala, Cotabato')) ?> · <?= e(setting('clinic_contact','')) ?></small>
</div>

<hr>

<div class="row">
    <div class="col-6">
        <div class="text-muted small">Dispensing Receipt</div>
        <h5 class="fw-bold text-success mb-0"><?= e($d['receipt_no']) ?></h5>
    </div>
    <div class="col-6 text-end">
        <div class="text-muted small">Date</div>
        <div class="fw-semibold"><?= e(fmt_datetime($d['dispense_date'])) ?></div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-6">
        <div class="text-muted small">Patient</div>
        <div class="fw-semibold"><?= e($d['first_name'].' '.$d['last_name']) ?></div>
        <small class="text-muted"><?= e($d['patient_code']) ?> · <?= e($d['contact_no'] ?: '') ?></small>
    </div>
    <div class="col-6 text-end">
        <div class="text-muted small">Dispensed by</div>
        <div class="fw-semibold"><?= e($d['dispensed_by_name'] ?: '—') ?></div>
    </div>
</div>

<table class="table mt-4">
    <thead><tr><th>#</th><th>Medicine</th><th>Batch</th><th>Qty</th><th>Dosage</th><th>Instructions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i => $it): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= e($it['medicine_name']) ?><br><small class="text-muted"><?= e($it['medicine_code']) ?></small></td>
            <td><?= e($it['batch_no'] ?: '—') ?></td>
            <td><?= (int)$it['quantity'] ?> <?= e($it['unit']) ?></td>
            <td><?= e($it['dosage'] ?: '—') ?></td>
            <td><?= e($it['instructions'] ?: '—') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($d['notes']): ?>
    <div class="alert alert-light border rounded-lg mt-3"><strong>Notes:</strong> <?= e($d['notes']) ?></div>
<?php endif; ?>

<div class="row mt-5 pt-4">
    <div class="col-6 text-center">
        <div style="border-top:1px solid #94A3B8;padding-top:6px;margin:0 auto;max-width:220px">Patient / Guardian</div>
    </div>
    <div class="col-6 text-center">
        <div style="border-top:1px solid #94A3B8;padding-top:6px;margin:0 auto;max-width:220px">Dispensing Officer</div>
    </div>
</div>

<h4 class="fw-bold mb-3">My Medical Records</h4>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Vitals</th><th>Diagnosis</th><th>Treatment</th><th>Prescription</th></tr></thead>
            <tbody>
            <?php if(!$records): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No records yet.</td></tr>
            <?php else: foreach ($records as $r): ?>
                <tr>
                    <td><?= e(fmt_date($r['visit_date'])) ?></td>
                    <td><span class="badge bg-info-subtle text-info rounded-pill px-3 py-2"><?= e(ucfirst($r['record_type'])) ?></span></td>
                    <td><small>BP <?= e($r['bp']?:'—') ?> · T <?= e($r['temperature']?:'—') ?>°C · P <?= e($r['pulse']?:'—') ?></small></td>
                    <td><?= e($r['diagnosis'] ?: '—') ?></td>
                    <td><?= e($r['treatment'] ?: '—') ?></td>
                    <td><?= e($r['prescription'] ?: '—') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php ob_start(); ?><script>$(function(){ initTable('#tbl',{order:[[0,'desc']]}); });</script><?php $extra_scripts = ob_get_clean(); ?>

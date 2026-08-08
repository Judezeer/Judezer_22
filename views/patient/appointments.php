<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">My Appointments</h4>
    <a class="btn btn-primary" href="<?= e(url('index.php?url=patient/book')) ?>"><i class="fa-solid fa-calendar-plus me-2"></i>Book</a>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr><th>#</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th>Requested</th></tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
                <tr>
                    <td>#<?= (int)$a['id'] ?></td>
                    <td><?= e(fmt_date($a['appointment_date'])) ?></td>
                    <td><?= e(date('g:i A', strtotime($a['appointment_time']))) ?></td>
                    <td><?= e($a['purpose']) ?></td>
                    <td><?= status_badge($a['status']) ?></td>
                    <td><small><?= e(fmt_datetime($a['created_at'])) ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php ob_start(); ?><script>$(function(){ initTable('#tbl',{order:[[1,'desc']]}); });</script><?php $extra_scripts = ob_get_clean(); ?>

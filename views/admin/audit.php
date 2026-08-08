<h4 class="fw-bold mb-3">Audit Logs</h4>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <input type="hidden" name="url" value="admin/audit">
            <div class="col-md-3"><input class="form-control" name="action" placeholder="Action (login/insert/…)" value="<?= e($_GET['action'] ?? '') ?>"></div>
            <div class="col-md-3"><input class="form-control" name="module" placeholder="Module (patients/…)" value="<?= e($_GET['module'] ?? '') ?>"></div>
            <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= e($_GET['from'] ?? '') ?>"></div>
            <div class="col-md-2"><input type="date" class="form-control" name="to" value="<?= e($_GET['to'] ?? '') ?>"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i></button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr>
                <th>When</th><th>User</th><th>Role</th><th>Action</th><th>Module</th><th>Description</th><th>IP</th>
            </tr></thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= e(fmt_datetime($l['created_at'])) ?></td>
                    <td><?= e($l['full_name'] ?? 'System') ?></td>
                    <td><span class="badge bg-primary-subtle text-success rounded-pill px-3 py-2"><?= e($l['role'] ?? '—') ?></span></td>
                    <td><?= e($l['action']) ?></td>
                    <td><?= e($l['module'] ?? '—') ?></td>
                    <td><?= e($l['description'] ?? '') ?></td>
                    <td><small class="text-muted"><?= e($l['ip_address'] ?? '') ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php ob_start(); ?>
<script>$(function(){ initTable('#tbl',{order:[[0,'desc']]}); });</script>
<?php $extra_scripts = ob_get_clean(); ?>

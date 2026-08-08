<h4 class="fw-bold mb-3">Inventory Logs</h4>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <input type="hidden" name="url" value="pharmacist/inventory">
            <div class="col-md-3"><select class="form-select" name="action">
                <option value="">All actions</option>
                <?php foreach (['stock_in','stock_out','dispense','adjust','expire'] as $a): ?>
                    <option value="<?= $a ?>" <?= ($_GET['action'] ?? '')===$a?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$a)) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= e($_GET['from'] ?? '') ?>"></div>
            <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= e($_GET['to'] ?? '') ?>"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i>Filter</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr>
                <th>When</th><th>Medicine</th><th>Batch</th><th>Action</th><th>Qty</th><th>Balance</th><th>Reference</th><th>By</th>
            </tr></thead>
            <tbody>
            <?php foreach ($logs as $l):
                $cls = ['stock_in'=>'success','stock_out'=>'warning','dispense'=>'info','adjust'=>'secondary','expire'=>'danger'][$l['action']] ?? 'secondary';
            ?>
                <tr>
                    <td><?= e(fmt_datetime($l['created_at'])) ?></td>
                    <td><?= e($l['medicine_name']) ?><br><small class="text-muted"><?= e($l['medicine_code']) ?></small></td>
                    <td><?= e($l['batch_no'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $cls ?>-subtle text-<?= $cls ?> rounded-pill px-3 py-2"><?= e(str_replace('_',' ', ucfirst($l['action']))) ?></span></td>
                    <td class="fw-semibold"><?= (int)$l['quantity'] ?></td>
                    <td><?= (int)$l['balance_after'] ?></td>
                    <td><small><?= e($l['reference'] ?? '') ?></small></td>
                    <td><small><?= e($l['performed_by_name'] ?? '—') ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php ob_start(); ?><script>$(function(){ initTable('#tbl',{order:[[0,'desc']]}); });</script><?php $extra_scripts = ob_get_clean(); ?>

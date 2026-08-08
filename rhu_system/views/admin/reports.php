<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-1">Reports</h4><small class="text-muted">Filter, print or export.</small></div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print</button>
        <button class="btn btn-primary" onclick="exportCsv()"><i class="fa-solid fa-file-csv me-1"></i>Export CSV</button>
    </div>
</div>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get" action="<?= e(url('index.php?url=admin/reports')) ?>">
            <input type="hidden" name="url" value="admin/reports">
            <div class="col-md-3"><label class="form-label">Report Type</label>
                <select class="form-select" name="type">
                    <?php foreach (['patients','appointments','inventory','dispensing'] as $t): ?>
                        <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-3"><label class="form-label">From</label><input type="date" class="form-control" name="from" value="<?= e($from) ?>"></div>
            <div class="col-md-3"><label class="form-label">To</label><input type="date" class="form-control" name="to" value="<?= e($to) ?>"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i>Generate</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-file-lines text-primary me-2"></i><?= e(ucfirst($type)) ?> Report</div>
    <div class="card-body">
        <table id="tbl" class="table">
            <?php
            $cols = [
                'patients'     => ['patient_code','first_name','last_name','sex','birthdate','barangay','contact_no','created_at'],
                'appointments' => ['id','patient_code','first_name','last_name','appointment_date','appointment_time','purpose','status'],
                'inventory'    => ['code','name','category','unit','total_stock','reorder_level','nearest_expiry'],
                'dispensing'   => ['receipt_no','patient_code','first_name','last_name','total_items','dispense_date'],
            ];
            $c = $cols[$type];
            ?>
            <thead><tr>
                <?php foreach ($c as $col): ?><th><?= e(str_replace('_',' ', ucfirst($col))) ?></th><?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <?php foreach ($c as $col): $v = $r[$col] ?? ''; ?>
                        <td><?= e(is_string($v)&&preg_match('/date|_at$/',$col)?fmt_date($v):$v) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function(){ initTable('#tbl'); });
function exportCsv(){
    const rows = [];
    $('#tbl thead tr th').each(function(){ rows.push($(this).text()); });
    let csv = rows.join(',') + '\n';
    $('#tbl tbody tr').each(function(){
        const line = [];
        $(this).find('td').each(function(){ line.push('"' + $(this).text().replace(/"/g,'""') + '"'); });
        csv += line.join(',') + '\n';
    });
    const blob = new Blob([csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = '<?= e($type) ?>_report_<?= date('Ymd') ?>.csv';
    a.click();
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-1">Dispense Medicine</h4><small class="text-muted">Automatic FEFO deduction across batches.</small></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-prescription-bottle-medical text-primary me-2"></i>New Dispensing</div>
            <div class="card-body">
                <form id="dForm" onsubmit="return doDispense(event)">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label">Patient *</label>
                            <select class="form-select" name="patient_id" required>
                                <option value="">-- select patient --</option>
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"><?= e($p['patient_code'].' — '.$p['first_name'].' '.$p['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary w-100" onclick="addRow()"><i class="fa-solid fa-plus me-1"></i>Add medicine</button>
                        </div>

                        <div class="col-12">
                            <table class="table" id="itemsTbl">
                                <thead><tr><th style="width:38%">Medicine</th><th>Available</th><th style="width:18%">Qty</th><th>Dosage</th><th>Instructions</th><th></th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="col-12"><label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes for this transaction"></textarea>
                        </div>

                        <div class="col-12 text-end">
                            <button class="btn btn-primary btn-lg"><i class="fa-solid fa-check me-1"></i> Dispense</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>This Month's Dispensing</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Receipt</th><th>Patient</th><th>Date</th><th>Items</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$history): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No dispensing yet.</td></tr>
                    <?php else: foreach ($history as $h): ?>
                        <tr>
                            <td class="fw-semibold text-success"><?= e($h['receipt_no']) ?></td>
                            <td><?= e($h['first_name'].' '.$h['last_name']) ?></td>
                            <td><?= e(fmt_datetime($h['dispense_date'])) ?></td>
                            <td><?= (int)$h['total_items'] ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('index.php?url=pharmacist/receipt/' . (int)$h['id'])) ?>" target="_blank"><i class="fa-solid fa-receipt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<template id="rowTpl">
<tr>
    <td>
        <select class="form-select form-select-sm med" required>
            <option value="">-- medicine --</option>
            <?php foreach ($meds as $m): ?>
                <option value="<?= (int)$m['id'] ?>" data-stock="<?= (int)$m['total_stock'] ?>" data-unit="<?= e($m['unit']) ?>">
                    <?= e($m['name'].' ('.$m['code'].')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td class="avail small text-muted">—</td>
    <td><input type="number" min="1" class="form-control form-control-sm qty" required></td>
    <td><input class="form-control form-control-sm dosage" placeholder="e.g. 1 tab 3x/day"></td>
    <td><input class="form-control form-control-sm instr" placeholder="After meals"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger rm"><i class="fa-solid fa-xmark"></i></button></td>
</tr>
</template>

<?php ob_start(); ?>
<script>
function addRow(){
    const tpl = document.getElementById('rowTpl').content.cloneNode(true);
    document.querySelector('#itemsTbl tbody').appendChild(tpl);
}
addRow();

$(document).on('change','.med', function(){
    const opt = $(this).find('option:selected');
    const s = opt.data('stock') || 0, u = opt.data('unit') || '';
    $(this).closest('tr').find('.avail').text(s + ' ' + u);
});
$(document).on('click','.rm', function(){
    if ($('#itemsTbl tbody tr').length > 1) $(this).closest('tr').remove();
});

function doDispense(ev){
    ev.preventDefault();
    const form = document.getElementById('dForm');
    const data = { _csrf: window.CSRF_TOKEN,
                   patient_id: form.patient_id.value,
                   notes: form.notes.value,
                   items: [] };
    let bad = false;
    $('#itemsTbl tbody tr').each(function(){
        const med = $(this).find('.med').val();
        const qty = parseInt($(this).find('.qty').val() || 0, 10);
        if (!med || qty <= 0) { bad = true; return; }
        data.items.push({
            medicine_id: med, quantity: qty,
            dosage: $(this).find('.dosage').val(),
            instructions: $(this).find('.instr').val()
        });
    });
    if (bad || !data.items.length) { Swal.fire('Missing info','Please complete every medicine row.','warning'); return false; }

    $.ajax({
        url:'<?= e(url("index.php?url=pharmacist/dispense_save")) ?>',
        type:'POST', data:data, traditional:false
    }).done(r=>{
        if(!r.ok) return Swal.fire('Error', r.message,'error');
        Swal.fire({ icon:'success', title:'Dispensed!', text:r.message, showCancelButton:true,
                    confirmButtonText:'View Receipt', cancelButtonText:'Close', confirmButtonColor:'#16A34A' })
            .then(res=>{ if(res.isConfirmed) window.open('<?= e(url("index.php?url=pharmacist/receipt/")) ?>' + r.id, '_blank'); location.reload(); });
    }).fail(x=>Swal.fire('Error', x.responseJSON?.message || 'Failed','error'));
    return false;
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

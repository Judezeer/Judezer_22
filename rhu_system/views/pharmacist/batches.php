<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-1">Batch Management</h4><small class="text-muted">Stock In, Stock Out, expiration tracking (FEFO).</small></div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inModal"><i class="fa-solid fa-arrow-down me-2"></i>Stock In</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr>
                <th>Medicine</th><th>Batch #</th><th>Received</th><th>Expiration</th>
                <th>Initial</th><th>Remaining</th><th>Supplier</th><th></th>
            </tr></thead>
            <tbody>
            <?php $today = date('Y-m-d'); foreach ($batches as $b):
                $expired = $b['expiration_date'] < $today;
                $soon = !$expired && strtotime($b['expiration_date']) < strtotime('+60 days');
            ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($b['name']) ?></div><small class="text-muted"><?= e($b['code']) ?></small></td>
                    <td class="fw-semibold"><?= e($b['batch_no']) ?></td>
                    <td><?= e(fmt_date($b['received_date'])) ?></td>
                    <td>
                        <?= e(fmt_date($b['expiration_date'])) ?>
                        <?php if ($expired): ?><span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1 ms-1">Expired</span>
                        <?php elseif ($soon): ?><span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1 ms-1">Soon</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$b['initial_qty'] ?></td>
                    <td><span class="badge bg-<?= $b['quantity']<=0?'secondary':'success' ?>-subtle text-<?= $b['quantity']<=0?'secondary':'success' ?> rounded-pill px-3 py-2"><?= (int)$b['quantity'] ?> <?= e($b['unit']) ?></span></td>
                    <td><?= e($b['supplier'] ?: '—') ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-danger" onclick='openOut(<?= json_encode($b, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#outModal"><i class="fa-solid fa-arrow-up"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="delB(<?= (int)$b['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Stock In -->
<div class="modal fade" id="inModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="inForm" onsubmit="return doStockIn(event)">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-arrow-down me-2"></i>Stock In</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Medicine *</label>
                <select class="form-select" name="medicine_id" required>
                    <option value="">-- select --</option>
                    <?php foreach ($meds as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= e($m['name'].' ('.$m['code'].')') ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-6"><label class="form-label">Batch # *</label><input class="form-control" name="batch_no" required></div>
            <div class="col-md-4"><label class="form-label">Quantity *</label><input type="number" min="1" class="form-control" name="quantity" required></div>
            <div class="col-md-4"><label class="form-label">Received *</label><input type="date" class="form-control" name="received_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-4"><label class="form-label">Expiration *</label><input type="date" class="form-control" name="expiration_date" required></div>
            <div class="col-md-12"><label class="form-label">Supplier</label><input class="form-control" name="supplier"></div>
            <div class="col-md-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="2"></textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Stock Out -->
<div class="modal fade" id="outModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form id="outForm" onsubmit="return doStockOut(event)">
      <?= csrf_field() ?>
      <input type="hidden" name="batch_id" id="out_batch_id">
      <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-arrow-up me-2"></i>Stock Out</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="text-muted mb-2">Batch: <span id="out_bname" class="fw-semibold"></span> — remaining: <span id="out_bqty"></span></p>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Quantity *</label><input type="number" min="1" class="form-control" name="quantity" required></div>
            <div class="col-md-12"><label class="form-label">Reason *</label>
                <select class="form-select" name="reason" required>
                    <option value="Expired">Expired</option>
                    <option value="Damaged / Contaminated">Damaged / Contaminated</option>
                    <option value="Adjustment">Adjustment</option>
                    <option value="Lost">Lost</option>
                </select>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger" type="submit"><i class="fa-solid fa-arrow-up me-2"></i>Deduct</button>
      </div>
    </form>
  </div></div>
</div>

<?php ob_start(); ?>
<script>
$(function(){ initTable('#tbl',{order:[[3,'asc']]}); });
function openOut(b){
    $('#out_batch_id').val(b.id); $('#out_bname').text(b.name+' — '+b.batch_no); $('#out_bqty').text(b.quantity+' '+b.unit);
}
function doStockIn(ev){
    ev.preventDefault();
    $.post('<?= e(url("index.php?url=pharmacist/stock_in")) ?>', $('#inForm').serialize())
        .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),500);} else Swal.fire('Error',r.message,'error'); });
    return false;
}
function doStockOut(ev){
    ev.preventDefault();
    $.post('<?= e(url("index.php?url=pharmacist/stock_out")) ?>', $('#outForm').serialize())
        .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),500);} else Swal.fire('Error',r.message,'error'); });
    return false;
}
function delB(id){
    confirmDelete('Delete this batch?').then(x=>{ if(!x.isConfirmed) return;
        $.post('<?= e(url("index.php?url=pharmacist/batch_delete")) ?>', {id, _csrf:window.CSRF_TOKEN})
            .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),500);} });
    });
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

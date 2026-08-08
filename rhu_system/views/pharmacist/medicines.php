<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-1">Medicine Inventory</h4><small class="text-muted">Master list — batches are managed under Batch / Stock.</small></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mModal" onclick="editM(null)">
        <i class="fa-solid fa-plus me-2"></i>Add Medicine
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr>
                <th>Code</th><th>Name</th><th>Category</th><th>Form / Strength</th><th>Unit</th>
                <th>Stock</th><th>Reorder</th><th>Nearest Expiry</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($meds as $m):
                $stock = (int)$m['total_stock'];
                $low   = $stock <= (int)$m['reorder_level'];
            ?>
                <tr>
                    <td class="fw-semibold text-success"><?= e($m['code']) ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($m['name']) ?></div>
                        <?php if($m['generic_name']): ?><small class="text-muted"><?= e($m['generic_name']) ?></small><?php endif; ?>
                    </td>
                    <td><?= e($m['category'] ?: '—') ?></td>
                    <td><?= e(trim(($m['dosage_form'] ?? '') . ' ' . ($m['strength'] ?? ''))) ?: '—' ?></td>
                    <td><?= e($m['unit']) ?></td>
                    <td><span class="badge bg-<?= $low?'warning':'success' ?>-subtle text-<?= $low?'warning':'success' ?> rounded-pill px-3 py-2"><?= $stock ?></span></td>
                    <td><?= (int)$m['reorder_level'] ?></td>
                    <td><small><?= e(fmt_date($m['nearest_expiry'] ?? '')) ?></small></td>
                    <td><?= status_badge($m['status']) ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" onclick='editM(<?= json_encode($m, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#mModal"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="delM(<?= (int)$m['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="mModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="mForm" onsubmit="return saveM(event)">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="m_id">
        <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-pills me-2"></i>Medicine</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Code *</label><input class="form-control" name="code" id="m_code" required></div>
            <div class="col-md-8"><label class="form-label">Name *</label><input class="form-control" name="name" id="m_name" required></div>
            <div class="col-md-6"><label class="form-label">Generic name</label><input class="form-control" name="generic_name" id="m_generic_name"></div>
            <div class="col-md-3"><label class="form-label">Category</label><input class="form-control" name="category" id="m_category"></div>
            <div class="col-md-3"><label class="form-label">Unit</label><input class="form-control" name="unit" id="m_unit" value="piece"></div>
            <div class="col-md-3"><label class="form-label">Dosage form</label><input class="form-control" name="dosage_form" id="m_dosage_form" placeholder="tablet/syrup"></div>
            <div class="col-md-3"><label class="form-label">Strength</label><input class="form-control" name="strength" id="m_strength" placeholder="500mg"></div>
            <div class="col-md-3"><label class="form-label">Reorder level</label><input type="number" class="form-control" name="reorder_level" id="m_reorder_level" value="20"></div>
            <div class="col-md-3"><label class="form-label">Status</label>
                <select class="form-select" name="status" id="m_status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <div class="col-md-12"><label class="form-label">Supplier</label><input class="form-control" name="supplier" id="m_supplier"></div>
            <div class="col-md-12"><label class="form-label">Description</label><textarea class="form-control" name="description" id="m_description" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php ob_start(); ?>
<script>
$(function(){ initTable('#tbl'); });
function editM(m){
    const f=['code','name','generic_name','category','unit','dosage_form','strength','reorder_level','status','supplier','description'];
    $('#m_id').val(m?m.id:'');
    f.forEach(k=>$('#m_'+k).val(m?(m[k]||''):(k==='unit'?'piece':(k==='reorder_level'?20:(k==='status'?'active':'')))));
}
function saveM(ev){
    ev.preventDefault();
    $.post('<?= e(url("index.php?url=pharmacist/medicine_save")) ?>', $('#mForm').serialize())
        .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),500); } else Swal.fire('Error',r.message,'error'); });
    return false;
}
function delM(id){
    confirmDelete('Delete this medicine? All batches will be removed.').then(x=>{ if(!x.isConfirmed) return;
        $.post('<?= e(url("index.php?url=pharmacist/medicine_delete")) ?>', {id, _csrf:window.CSRF_TOKEN})
            .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),500); } else Swal.fire('Error',r.message,'error'); });
    });
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

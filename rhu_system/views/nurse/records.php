<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-1">Health Records</h4><small class="text-muted">Consultations, diagnosis, treatments and vaccinations.</small></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rModal" onclick="editR(null)">
        <i class="fa-solid fa-notes-medical me-2"></i>New Record
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr>
                <th>Date</th><th>Patient</th><th>Type</th><th>Vitals</th><th>Diagnosis</th><th>Treatment</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($records as $r): ?>
                <tr>
                    <td><?= e(fmt_date($r['visit_date'])) ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($r['first_name'].' '.$r['last_name']) ?></div>
                        <small class="text-muted"><?= e($r['patient_code']) ?></small>
                    </td>
                    <td><span class="badge bg-info-subtle text-info rounded-pill px-3 py-2"><?= e(ucfirst($r['record_type'])) ?></span></td>
                    <td><small>BP <?= e($r['bp'] ?: '—') ?> · T <?= e($r['temperature'] ?: '—') ?>°C</small></td>
                    <td><?= e(mb_strimwidth($r['diagnosis'] ?? '', 0, 48, '…')) ?></td>
                    <td><?= e(mb_strimwidth($r['treatment'] ?? '', 0, 48, '…')) ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" onclick='editR(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#rModal"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="delR(<?= (int)$r['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="rModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <form id="rForm" onsubmit="return saveR(event)">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="r_id">
        <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-notes-medical me-2"></i>Health Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Patient *</label>
                <select class="form-select" name="patient_id" id="r_patient_id" required>
                    <option value="">-- select --</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= e($p['patient_code'].' — '.$p['first_name'].' '.$p['last_name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-3"><label class="form-label">Type</label>
                <select class="form-select" name="record_type" id="r_record_type">
                    <option value="consultation">Consultation</option>
                    <option value="vaccination">Vaccination</option>
                    <option value="laboratory">Laboratory</option>
                    <option value="followup">Follow-up</option>
                </select></div>
            <div class="col-md-3"><label class="form-label">Visit date</label><input type="date" class="form-control" name="visit_date" id="r_visit_date"></div>

            <div class="col-md-2"><label class="form-label">BP</label><input class="form-control" name="bp" id="r_bp" placeholder="120/80"></div>
            <div class="col-md-2"><label class="form-label">Temp (°C)</label><input class="form-control" name="temperature" id="r_temperature"></div>
            <div class="col-md-2"><label class="form-label">Pulse</label><input class="form-control" name="pulse" id="r_pulse"></div>
            <div class="col-md-2"><label class="form-label">Weight (kg)</label><input class="form-control" name="weight" id="r_weight"></div>
            <div class="col-md-2"><label class="form-label">Height (cm)</label><input class="form-control" name="height" id="r_height"></div>
            <div class="col-md-2"><label class="form-label">Vaccine</label><input class="form-control" name="vaccine" id="r_vaccine"></div>

            <div class="col-md-12"><label class="form-label">Chief complaint</label><textarea class="form-control" name="chief_complaint" id="r_chief_complaint" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Diagnosis</label><textarea class="form-control" name="diagnosis" id="r_diagnosis" rows="3"></textarea></div>
            <div class="col-md-6"><label class="form-label">Treatment</label><textarea class="form-control" name="treatment" id="r_treatment" rows="3"></textarea></div>
            <div class="col-md-12"><label class="form-label">Prescription</label><textarea class="form-control" name="prescription" id="r_prescription" rows="2"></textarea></div>
            <div class="col-md-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" id="r_remarks" rows="2"></textarea></div>
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
$(function(){ initTable('#tbl',{order:[[0,'desc']]}); });
function editR(r){
    const f=['patient_id','record_type','visit_date','bp','temperature','pulse','weight','height',
            'vaccine','chief_complaint','diagnosis','treatment','prescription','remarks'];
    $('#r_id').val(r?r.id:'');
    f.forEach(k => $('#r_'+k).val(r?(r[k]||''):(k==='visit_date' ? new Date().toISOString().slice(0,10) : '')));
    if (!r) $('#r_record_type').val('consultation');
}
function saveR(ev){
    ev.preventDefault();
    $.post('<?= e(url("index.php?url=nurse/record_save")) ?>', $('#rForm').serialize())
        .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),600); } else Swal.fire('Error',r.message,'error'); });
    return false;
}
function delR(id){
    confirmDelete().then(x=>{ if(!x.isConfirmed) return;
        $.post('<?= e(url("index.php?url=nurse/record_delete")) ?>', {id, _csrf:window.CSRF_TOKEN})
            .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),500); } });
    });
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

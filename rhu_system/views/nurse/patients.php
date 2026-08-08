<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-1">Patients</h4><small class="text-muted">Register, edit and search patients.</small></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pModal" onclick="editP(null)">
        <i class="fa-solid fa-user-plus me-2"></i>Register Patient
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr>
                <th>Code</th><th>Name</th><th>Sex</th><th>Age</th><th>Barangay</th><th>Contact</th><th>Registered</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td class="fw-semibold text-success"><?= e($p['patient_code']) ?></td>
                    <td><a class="link-primary fw-semibold" href="<?= e(url('index.php?url=nurse/patient_view/' . (int)$p['id'])) ?>"><?= e($p['first_name'].' '.$p['last_name']) ?></a></td>
                    <td><?= e(ucfirst($p['sex'])) ?></td>
                    <td><?= age_from($p['birthdate']) ?></td>
                    <td><?= e($p['barangay']) ?></td>
                    <td><?= e($p['contact_no']) ?></td>
                    <td><small><?= e(fmt_date($p['created_at'])) ?></small></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('index.php?url=nurse/patient_view/' . (int)$p['id'])) ?>"><i class="fa-solid fa-eye"></i></a>
                        <button class="btn btn-sm btn-outline-primary" onclick='editP(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#pModal"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="delP(<?= (int)$p['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="pModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('index.php?url=nurse/patient_save')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="p_id">
        <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-user-injured me-2"></i>Patient</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">First name *</label><input class="form-control" name="first_name" id="p_first_name" required></div>
            <div class="col-md-4"><label class="form-label">Middle name</label><input class="form-control" name="middle_name" id="p_middle_name"></div>
            <div class="col-md-4"><label class="form-label">Last name *</label><input class="form-control" name="last_name" id="p_last_name" required></div>
            <div class="col-md-3"><label class="form-label">Sex *</label>
                <select class="form-select" name="sex" id="p_sex"><option value="male">Male</option><option value="female">Female</option></select></div>
            <div class="col-md-3"><label class="form-label">Birthdate *</label><input type="date" class="form-control" name="birthdate" id="p_birthdate" required></div>
            <div class="col-md-3"><label class="form-label">Civil status</label>
                <select class="form-select" name="civil_status" id="p_civil_status">
                    <option value="single">Single</option><option value="married">Married</option>
                    <option value="widowed">Widowed</option><option value="separated">Separated</option>
                </select></div>
            <div class="col-md-3"><label class="form-label">Blood type</label><input class="form-control" name="blood_type" id="p_blood_type" placeholder="e.g. O+"></div>
            <div class="col-md-6"><label class="form-label">Contact no.</label><input class="form-control" name="contact_no" id="p_contact_no"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="p_email"></div>
            <div class="col-md-8"><label class="form-label">Address *</label><input class="form-control" name="address" id="p_address" required></div>
            <div class="col-md-4"><label class="form-label">Barangay</label><input class="form-control" name="barangay" id="p_barangay"></div>
            <div class="col-md-6"><label class="form-label">PhilHealth no.</label><input class="form-control" name="philhealth_no" id="p_philhealth_no"></div>
            <div class="col-md-6"><label class="form-label">Photo (JPG/PNG, ≤2MB)</label><input type="file" class="form-control" name="photo" accept="image/*"></div>
            <div class="col-md-12"><label class="form-label">Known allergies</label><textarea class="form-control" name="allergies" id="p_allergies" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Emergency contact name</label><input class="form-control" name="emergency_name" id="p_emergency_name"></div>
            <div class="col-md-6"><label class="form-label">Emergency contact no.</label><input class="form-control" name="emergency_no" id="p_emergency_no"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save Patient</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php ob_start(); ?>
<script>
$(function(){ initTable('#tbl'); });
function editP(p){
    const fields = ['first_name','middle_name','last_name','sex','birthdate','civil_status',
      'blood_type','contact_no','email','address','barangay','philhealth_no',
      'allergies','emergency_name','emergency_no'];
    $('#p_id').val(p?p.id:'');
    fields.forEach(f => $('#p_' + f).val(p ? (p[f]||'') : ''));
}
function delP(id){
    confirmDelete('Delete this patient? Related records will also be removed.').then(r=>{ if(!r.isConfirmed) return;
        $.post('<?= e(url("index.php?url=nurse/patient_delete")) ?>', {id, _csrf: window.CSRF_TOKEN})
            .done(function(r){ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),600);} else Swal.fire('Error',r.message,'error'); });
    });
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

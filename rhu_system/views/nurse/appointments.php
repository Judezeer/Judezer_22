<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-1">Appointments</h4><small class="text-muted">Approve, reschedule and complete patient visits.</small></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#aModal" onclick="editA(null)">
        <i class="fa-solid fa-calendar-plus me-2"></i>New Appointment
    </button>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <input type="hidden" name="url" value="nurse/appointments">
            <div class="col-md-3"><select class="form-select" name="status">
                <option value="">All statuses</option>
                <?php foreach (['pending','approved','completed','rejected','cancelled','rescheduled'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($_GET['status'] ?? '')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="col-md-3"><input type="date" class="form-control" name="date" value="<?= e($_GET['date'] ?? '') ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i>Filter</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table">
            <thead><tr>
                <th>#</th><th>Patient</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
                <tr>
                    <td>#<?= (int)$a['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($a['first_name'].' '.$a['last_name']) ?></div>
                        <small class="text-muted"><?= e($a['patient_code']) ?></small>
                    </td>
                    <td><?= e(fmt_date($a['appointment_date'])) ?></td>
                    <td><?= e(date('g:i A', strtotime($a['appointment_time']))) ?></td>
                    <td><?= e($a['purpose']) ?></td>
                    <td><?= status_badge($a['status']) ?></td>
                    <td class="text-end">
                        <div class="btn-group">
                        <?php if ($a['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success" onclick="setS(<?= (int)$a['id'] ?>,'approved')"><i class="fa-solid fa-check"></i></button>
                            <button class="btn btn-sm btn-danger"  onclick="setS(<?= (int)$a['id'] ?>,'rejected')"><i class="fa-solid fa-xmark"></i></button>
                        <?php endif; ?>
                        <?php if ($a['status'] === 'approved'): ?>
                            <button class="btn btn-sm btn-primary" onclick="setS(<?= (int)$a['id'] ?>,'completed')"><i class="fa-solid fa-flag-checkered"></i></button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-primary" onclick='editA(<?= json_encode($a, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#aModal"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="delA(<?= (int)$a['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="aModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="aForm" onsubmit="return saveA(event)">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="a_id">
        <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-calendar-check me-2"></i>Appointment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-12"><label class="form-label">Patient *</label>
                <select class="form-select" name="patient_id" id="a_patient_id" required>
                    <option value="">-- select --</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= e($p['patient_code'].' — '.$p['first_name'].' '.$p['last_name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-6"><label class="form-label">Date *</label><input type="date" class="form-control" name="appointment_date" id="a_date" required></div>
            <div class="col-md-6"><label class="form-label">Time *</label><input type="time" class="form-control" name="appointment_time" id="a_time" required></div>
            <div class="col-md-8"><label class="form-label">Purpose *</label><input class="form-control" name="purpose" id="a_purpose" required></div>
            <div class="col-md-4"><label class="form-label">Status</label>
                <select class="form-select" name="status" id="a_status">
                    <option value="pending">Pending</option><option value="approved">Approved</option>
                    <option value="completed">Completed</option><option value="cancelled">Cancelled</option>
                    <option value="rescheduled">Rescheduled</option><option value="rejected">Rejected</option>
                </select></div>
            <div class="col-md-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" id="a_notes" rows="2"></textarea></div>
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
$(function(){ initTable('#tbl',{order:[[2,'desc']]}); });
function editA(a){
    $('#a_id').val(a?a.id:'');
    $('#a_patient_id').val(a?a.patient_id:'');
    $('#a_date').val(a?a.appointment_date:'');
    $('#a_time').val(a?a.appointment_time:'');
    $('#a_purpose').val(a?a.purpose:'');
    $('#a_status').val(a?a.status:'pending');
    $('#a_notes').val(a?a.notes:'');
}
function saveA(ev, forceOverride){
    if (ev && ev.preventDefault) ev.preventDefault();

    // Build payload — jQuery's serialize + optional override flag
    let payload = $('#aForm').serialize();
    if (forceOverride) payload += '&override=1';

    $.ajax({
        url: '<?= e(url("index.php?url=nurse/appointment_save")) ?>',
        type: 'POST',
        data: payload,
        dataType: 'json'
    }).done(function(r){
        if (r.ok) {
            toast('success', r.message);
            setTimeout(() => location.reload(), 600);
        } else {
            Swal.fire('Error', r.message, 'error');
        }
    }).fail(function(xhr){
        const r = xhr.responseJSON || {};

        // Handle conflict (HTTP 409) with an override dialog
        if (xhr.status === 409 && r.conflict && r.conflicts) {
            let html = '<div class="text-start">';
            html += '<p class="text-muted mb-2">The following conflicts were found:</p>';
            html += '<ul class="text-start" style="padding-left:18px">';
            r.conflicts.forEach(c => {
                const iconType = c.type === 'same_patient_same_day' ? '👤' : '⏰';
                html += '<li class="mb-2"><strong>' + iconType + '</strong> ' +
                        $('<div>').text(c.message).html() + '</li>';
            });
            html += '</ul>';
            html += '<p class="small text-muted mt-3">You can override and book anyway if this is intentional (e.g. multiple providers, walk-in emergency).</p>';
            html += '</div>';

            Swal.fire({
                icon: 'warning',
                title: 'Scheduling Conflict',
                html: html,
                showCancelButton: true,
                confirmButtonText: 'Book Anyway',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#F59E0B',
                cancelButtonColor: '#64748B',
                width: 560
            }).then(res => {
                if (res.isConfirmed) saveA(null, true); // retry with override
            });
        } else {
            Swal.fire('Error', r.message || 'Save failed', 'error');
        }
    });
    return false;
}
function setS(id,st){
    $.post('<?= e(url("index.php?url=nurse/appt_status")) ?>', {id, status:st, _csrf:window.CSRF_TOKEN})
        .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),500); } else Swal.fire('Error',r.message,'error'); });
}
function delA(id){
    confirmDelete().then(r=>{ if(!r.isConfirmed) return;
        $.post('<?= e(url("index.php?url=nurse/appointment_delete")) ?>', {id, _csrf:window.CSRF_TOKEN})
            .done(r=>{ if(r.ok){ toast('success',r.message); setTimeout(()=>location.reload(),500); } });
    });
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

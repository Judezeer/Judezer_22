<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Manage Users</h4>
        <small class="text-muted">Administrators, nurses, pharmacists and patients.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="editUser(null)">
        <i class="fa-solid fa-plus me-2"></i>New User
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="tbl" class="table align-middle">
            <thead><tr>
                <th>#</th><th>Full Name</th><th>Username</th><th>Email</th>
                <th>Role</th><th>Status</th><th>Last Login</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge bg-primary-subtle text-success rounded-pill px-3 py-2"><?= e(ucfirst($u['role'])) ?></span></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td><?= e(fmt_datetime($u['last_login'] ?? '')) ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#userModal"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="delUser(<?= (int)$u['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="userForm" onsubmit="return saveUser(event)">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="u_id">
        <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-user-gear me-2"></i>User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full Name *</label><input class="form-control" name="full_name" id="u_full_name" required></div>
            <div class="col-md-6"><label class="form-label">Role *</label>
                <select class="form-select" name="role" id="u_role" required>
                    <option value="admin">Administrator</option>
                    <option value="nurse">Nurse</option>
                    <option value="pharmacist">Pharmacist</option>
                    <option value="patient">Patient</option>
                </select></div>
            <div class="col-md-6"><label class="form-label">Username *</label><input class="form-control" name="username" id="u_username" required></div>
            <div class="col-md-6"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" id="u_email" required></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" id="u_phone"></div>
            <div class="col-md-6"><label class="form-label">Status</label>
                <select class="form-select" name="status" id="u_status">
                    <option value="active">Active</option><option value="inactive">Inactive</option>
                </select></div>
            <div class="col-md-12"><label class="form-label">Password <small class="text-muted">(leave blank to keep existing)</small></label>
                <input type="password" class="form-control" name="password" id="u_password"></div>
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

function editUser(u){
    $('#u_id').val(u?u.id:'');
    $('#u_full_name').val(u?u.full_name:'');
    $('#u_username').val(u?u.username:'');
    $('#u_email').val(u?u.email:'');
    $('#u_role').val(u?u.role:'patient');
    $('#u_phone').val(u?u.phone:'');
    $('#u_status').val(u?u.status:'active');
    $('#u_password').val('');
}

function saveUser(ev){
    ev.preventDefault();
    $.post('<?= e(url("index.php?url=admin/user_save")) ?>', $('#userForm').serialize())
        .done(function(r){
            if (r.ok){ toast('success', r.message); setTimeout(()=>location.reload(),700); }
            else Swal.fire('Error', r.message, 'error');
        }).fail(function(x){ Swal.fire('Error', x.responseJSON?.message || 'Save failed', 'error'); });
    return false;
}

function delUser(id){
    confirmDelete('Delete this user?').then(r=>{ if(!r.isConfirmed) return;
        $.post('<?= e(url("index.php?url=admin/user_delete")) ?>', {id, _csrf: window.CSRF_TOKEN})
            .done(function(r){ if(r.ok){ toast('success', r.message); setTimeout(()=>location.reload(),600);} else Swal.fire('Error', r.message,'error'); });
    });
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>

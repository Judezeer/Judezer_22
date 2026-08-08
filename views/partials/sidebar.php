<?php
$user = current_user();
$role = $user['role'];
$menu = [
    'admin' => [
        ['label'=>'Dashboard',     'icon'=>'fa-gauge-high',   'url'=>'admin/dashboard',   'key'=>'dashboard'],
        ['section'=>'Clinical'],
        ['label'=>'Patients',      'icon'=>'fa-user-injured', 'url'=>'admin/patients',    'key'=>'patients'],
        ['label'=>'Appointments',  'icon'=>'fa-calendar-check','url'=>'admin/appointments','key'=>'appointments'],
        ['section'=>'Pharmacy'],
        ['label'=>'Medicines',     'icon'=>'fa-pills',         'url'=>'admin/medicines',   'key'=>'medicines'],
        ['label'=>'Dispensing',    'icon'=>'fa-prescription-bottle-medical','url'=>'admin/dispensing','key'=>'dispensing'],
        ['label'=>'Inventory Logs','icon'=>'fa-clipboard-list','url'=>'admin/inventory',   'key'=>'inventory'],
        ['section'=>'System'],
        ['label'=>'Users',         'icon'=>'fa-users-gear',    'url'=>'admin/users',       'key'=>'users'],
        ['label'=>'Reports',       'icon'=>'fa-chart-line',    'url'=>'admin/reports',     'key'=>'reports'],
        ['label'=>'Audit Logs',    'icon'=>'fa-shield-halved', 'url'=>'admin/audit',       'key'=>'audit'],
        ['label'=>'Settings',      'icon'=>'fa-sliders',       'url'=>'admin/settings',    'key'=>'settings'],
        ['label'=>'Backup DB',     'icon'=>'fa-database',      'url'=>'admin/backup',      'key'=>'backup'],
    ],
    'nurse' => [
        ['label'=>'Dashboard',     'icon'=>'fa-gauge-high',    'url'=>'nurse/dashboard',   'key'=>'dashboard'],
        ['label'=>'Patients',      'icon'=>'fa-user-injured',  'url'=>'nurse/patients',    'key'=>'patients'],
        ['label'=>'Appointments',  'icon'=>'fa-calendar-check','url'=>'nurse/appointments','key'=>'appointments'],
        ['label'=>'Health Records','icon'=>'fa-notes-medical', 'url'=>'nurse/records',     'key'=>'records'],
    ],
    'pharmacist' => [
        ['label'=>'Dashboard',      'icon'=>'fa-gauge-high','url'=>'pharmacist/dashboard','key'=>'dashboard'],
        ['label'=>'Medicines',      'icon'=>'fa-pills','url'=>'pharmacist/medicines','key'=>'medicines'],
        ['label'=>'Batch / Stock',  'icon'=>'fa-box','url'=>'pharmacist/batches','key'=>'batches'],
        ['label'=>'Dispense',       'icon'=>'fa-prescription-bottle-medical','url'=>'pharmacist/dispensing','key'=>'dispensing'],
        ['label'=>'Inventory Logs', 'icon'=>'fa-clipboard-list','url'=>'pharmacist/inventory','key'=>'inventory'],
    ],
    'patient' => [
        ['label'=>'Dashboard',      'icon'=>'fa-gauge-high','url'=>'patient/dashboard','key'=>'dashboard'],
        ['label'=>'Book Appointment','icon'=>'fa-calendar-plus','url'=>'patient/book','key'=>'book'],
        ['label'=>'My Appointments','icon'=>'fa-calendar-check','url'=>'patient/appointments','key'=>'appointments'],
        ['label'=>'Medical Records','icon'=>'fa-notes-medical','url'=>'patient/records','key'=>'records'],
        ['label'=>'Notifications',  'icon'=>'fa-bell','url'=>'patient/notifications','key'=>'notifications'],
        ['label'=>'My Profile',     'icon'=>'fa-user','url'=>'patient/profile','key'=>'profile'],
    ],
];
$items = $menu[$role] ?? [];
?>
<aside class="sidebar">
    <div class="brand">
        <?= render_logo('sidebar') ?>
        <div>
            <div class="title"><?= e(setting('site_name','RHU Makilala')) ?></div>
            <div class="sub">Health Management System</div>
        </div>
    </div>

    <nav class="flex-grow-1 overflow-auto">
        <?php foreach ($items as $it): ?>
            <?php if (isset($it['section'])): ?>
                <div class="nav-section"><?= e($it['section']) ?></div>
            <?php else: ?>
                <a class="nav-link <?= ($active === $it['key']) ? 'active' : '' ?>"
                   href="<?= e(url('index.php?url=' . $it['url'])) ?>">
                    <i class="fa-solid <?= e($it['icon']) ?>"></i>
                    <span><?= e($it['label']) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="user">
        <div class="avatar"><?= e(strtoupper(substr($user['full_name'],0,1))) ?></div>
        <div class="flex-grow-1">
            <div class="fw-semibold text-white" style="font-size:13px"><?= e($user['full_name']) ?></div>
            <div style="font-size:11px;color:#A7F3D0;text-transform:capitalize"><?= e($user['role']) ?></div>
        </div>
        <a class="text-white-50" href="<?= e(url('index.php?url=auth/logout')) ?>" title="Sign out"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
    </div>
</aside>

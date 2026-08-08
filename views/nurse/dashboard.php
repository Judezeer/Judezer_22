<section class="hero">
    <h1>Good day, <?= e(current_user()['full_name']) ?> 👩‍⚕️</h1>
    <p>Manage today's patient visits, appointments and health records.</p>
    <div class="quick">
        <a class="btn" href="<?= e(url('index.php?url=nurse/patients')) ?>"><i class="fa-solid fa-user-plus me-2"></i>Register Patient</a>
        <a class="btn" href="<?= e(url('index.php?url=nurse/appointments')) ?>"><i class="fa-solid fa-calendar-plus me-2"></i>New Appointment</a>
        <a class="btn" href="<?= e(url('index.php?url=nurse/records')) ?>"><i class="fa-solid fa-notes-medical me-2"></i>New Record</a>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-green"><div class="icon"><i class="fa-solid fa-user-injured"></i></div><div class="value"><?= (int)$stats['patients'] ?></div><div class="label">Total Patients</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-blue"><div class="icon"><i class="fa-solid fa-calendar-day"></i></div><div class="value"><?= (int)$stats['today_appts'] ?></div><div class="label">Today's Appointments</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-amber"><div class="icon"><i class="fa-solid fa-hourglass-half"></i></div><div class="value"><?= (int)$stats['pending'] ?></div><div class="label">Pending</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-teal"><div class="icon"><i class="fa-solid fa-circle-check"></i></div><div class="value"><?= (int)$stats['completed'] ?></div><div class="label">Completed</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-chart-line text-primary me-2"></i>New Patients &amp; Appointments (12 mo.)</div>
            <div class="card-body"><canvas id="chart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-calendar-day text-primary me-2"></i>Today's Schedule</div>
            <div class="card-body p-0">
                <ul class="list-unstyled m-0">
                    <?php if (!$today_list): ?>
                        <li class="text-center text-muted py-4">No appointments today.</li>
                    <?php else: foreach ($today_list as $a): ?>
                        <li class="d-flex gap-3 p-3 border-bottom">
                            <div style="width:52px;height:52px;border-radius:14px;background:#DCFCE7;color:#166534;display:flex;align-items:center;justify-content:center;font-weight:700">
                                <?= e(date('g:i', strtotime($a['appointment_time']))) ?><br>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?= e($a['first_name'].' '.$a['last_name']) ?></div>
                                <small class="text-muted"><?= e($a['purpose']) ?></small>
                            </div>
                            <?= status_badge($a['status']) ?>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const p = <?= json_encode($patients_chart) ?>, a = <?= json_encode($appts_chart) ?>;
const map = x => Object.fromEntries((x||[]).map(r=>[r.m,+r.c]));
const pm=map(p), am=map(a); const labels=[], dp=[], da=[]; const now=new Date(); now.setDate(1);
for(let i=11;i>=0;i--){ const dt=new Date(now.getFullYear(),now.getMonth()-i,1);
  const k=dt.getFullYear()+'-'+String(dt.getMonth()+1).padStart(2,'0');
  labels.push(dt.toLocaleString('default',{month:'short'})); dp.push(pm[k]||0); da.push(am[k]||0); }
new Chart(document.getElementById('chart'),{
  type:'bar',
  data:{labels,datasets:[
    {label:'New Patients',data:dp,backgroundColor:'#16A34A',borderRadius:8},
    {label:'Appointments',data:da,backgroundColor:'#4ADE80',borderRadius:8}
  ]},
  options:{plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true}}}
});
</script>
<?php $extra_scripts = ob_get_clean(); ?>

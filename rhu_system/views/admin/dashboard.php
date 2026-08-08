<section class="hero">
    <h1>Welcome, <?= e(current_user()['full_name']) ?> 👋</h1>
    <p>Here's a snapshot of the Rural Health Unit's operations today — patients, appointments, medicine stock and dispensing at a glance.</p>
    <div class="quick">
        <a href="<?= e(url('index.php?url=admin/patients')) ?>" class="btn"><i class="fa-solid fa-user-plus me-2"></i>Add Patient</a>
        <a href="<?= e(url('index.php?url=admin/appointments')) ?>" class="btn"><i class="fa-solid fa-calendar-plus me-2"></i>New Appointment</a>
        <a href="<?= e(url('index.php?url=admin/medicines')) ?>" class="btn"><i class="fa-solid fa-pills me-2"></i>Medicines</a>
        <a href="<?= e(url('index.php?url=admin/reports')) ?>" class="btn"><i class="fa-solid fa-chart-line me-2"></i>Reports</a>
    </div>
</section>

<?php include VIEW_PATH . 'partials' . DIRECTORY_SEPARATOR . 'stock_alerts.php'; ?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['stat-green', 'fa-user-injured','Total Patients',      $stats['patients']],
        ['stat-blue',  'fa-calendar-day','Today\'s Appointments',$stats['today_appts']],
        ['stat-teal',  'fa-pills',       'Available Medicines',  $stats['medicines']],
        ['stat-amber', 'fa-triangle-exclamation','Low Stock',    $stats['low_stock']],
        ['stat-red',   'fa-circle-exclamation','Expired',        $stats['expired']],
        ['stat-purple','fa-prescription-bottle-medical','Dispensed Today', $stats['dispensed_today']],
    ];
    foreach ($cards as [$cls,$icon,$label,$val]): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card <?= e($cls) ?>">
                <div class="icon"><i class="fa-solid <?= e($icon) ?>"></i></div>
                <div class="value"><?= (int)$val ?></div>
                <div class="label"><?= e($label) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-chart-line text-primary me-2"></i>Monthly Trends</span>
                <div class="d-flex gap-2 align-items-center">
                    <span class="legend-dot" style="background:#16A34A"></span><small class="text-muted me-2">Patients</small>
                    <span class="legend-dot" style="background:#0EA5E9"></span><small class="text-muted me-2">Appointments</small>
                    <span class="legend-dot" style="background:#F59E0B"></span><small class="text-muted">Dispensing</small>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-wrap" style="position:relative;height:320px">
                    <canvas id="monthChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Appointments by Status</div>
            <div class="card-body">
                <div class="chart-wrap" style="position:relative;height:260px">
                    <canvas id="statusChart"></canvas>
                </div>
                <div id="statusLegend" class="mt-3"></div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Recent Activity</div>
            <div class="card-body p-0">
                <ul class="list-unstyled m-0">
                    <?php if (!$recent): ?>
                        <li class="text-center text-muted py-4">No activity yet.</li>
                    <?php else: foreach ($recent as $r): ?>
                        <li class="d-flex gap-3 p-3 border-bottom">
                            <div class="flex-shrink-0" style="width:36px;height:36px;border-radius:12px;background:#DCFCE7;color:#166534;display:flex;align-items:center;justify-content:center">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size:13px"><?= e(ucfirst($r['action'])) ?> · <?= e($r['module']) ?></div>
                                <div class="text-muted" style="font-size:12px"><?= e($r['description'] ?? '') ?></div>
                                <small class="text-muted"><?= e(fmt_datetime($r['created_at'])) ?> · <?= e($r['full_name'] ?? 'System') ?></small>
                            </div>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
/* ---------- Monthly Trends: premium area chart ---------- */
const labels = [];
const dPat = [], dAppt = [], dDisp = [];
const months = <?= json_encode($patients_chart) ?>;
const apptM  = <?= json_encode($appts_chart) ?>;
const dispM  = <?= json_encode($disp_chart) ?>;
const now = new Date(); now.setDate(1);
const map = m => Object.fromEntries((m||[]).map(r=>[r.m, +r.c]));
const pMap = map(months), aMap = map(apptM), dMap = map(dispM);
for (let i = 11; i >= 0; i--) {
    const dt = new Date(now.getFullYear(), now.getMonth()-i, 1);
    const key = dt.getFullYear() + '-' + String(dt.getMonth()+1).padStart(2,'0');
    labels.push(dt.toLocaleString('default',{month:'short'}));
    dPat.push(pMap[key]||0); dAppt.push(aMap[key]||0); dDisp.push(dMap[key]||0);
}

// Premium gradient fills
const monthCtx = document.getElementById('monthChart').getContext('2d');
const grad = (color) => {
    const g = monthCtx.createLinearGradient(0, 0, 0, 320);
    g.addColorStop(0, color + '55');
    g.addColorStop(1, color + '00');
    return g;
};

new Chart(monthCtx, {
    type:'line',
    data:{
        labels,
        datasets:[
            {label:'New Patients',   data:dPat,  borderColor:'#16A34A', backgroundColor:grad('#16A34A'), tension:.4, fill:true, borderWidth:3, pointRadius:0, pointHoverRadius:6, pointHoverBackgroundColor:'#16A34A', pointHoverBorderColor:'#fff', pointHoverBorderWidth:3},
            {label:'Appointments',   data:dAppt, borderColor:'#0EA5E9', backgroundColor:grad('#0EA5E9'), tension:.4, fill:true, borderWidth:3, pointRadius:0, pointHoverRadius:6, pointHoverBackgroundColor:'#0EA5E9', pointHoverBorderColor:'#fff', pointHoverBorderWidth:3},
            {label:'Dispensing',     data:dDisp, borderColor:'#F59E0B', backgroundColor:grad('#F59E0B'), tension:.4, fill:true, borderWidth:3, pointRadius:0, pointHoverRadius:6, pointHoverBackgroundColor:'#F59E0B', pointHoverBorderColor:'#fff', pointHoverBorderWidth:3},
        ]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        interaction:{ mode:'index', intersect:false },
        plugins:{
            legend:{ display:false },
            tooltip:{
                backgroundColor:'#0F172A',
                padding:12,
                cornerRadius:10,
                titleFont:{ size:13, weight:'600' },
                bodyFont:{ size:12 },
                boxPadding:6,
                borderColor:'rgba(255,255,255,.1)',
                borderWidth:1
            }
        },
        scales:{
            x:{
                grid:{ display:false },
                ticks:{ color:'#64748B', font:{ size:11, weight:'500' } }
            },
            y:{
                beginAtZero:true,
                border:{ display:false },
                grid:{ color:'rgba(148,163,184,.15)', drawTicks:false },
                ticks:{ color:'#64748B', font:{ size:11 }, padding:8, stepSize:1 }
            }
        }
    }
});

/* ---------- Status doughnut: constrained + custom legend ---------- */
const status = <?= json_encode($appt_status) ?>;
const statusColors = {
    pending:'#F59E0B', approved:'#0EA5E9', rejected:'#DC2626',
    completed:'#16A34A', cancelled:'#64748B', rescheduled:'#8B5CF6'
};
const sLabels = Object.keys(status);
const sData   = Object.values(status);
const sColors = sLabels.map(k => statusColors[k] || '#94A3B8');

new Chart(document.getElementById('statusChart'), {
    type:'doughnut',
    data:{ labels: sLabels, datasets:[{
        data: sData,
        backgroundColor: sColors,
        borderWidth:3,
        borderColor:'#fff',
        hoverOffset:8
    }]},
    options:{
        responsive:true,
        maintainAspectRatio:false,
        cutout:'70%',
        plugins:{
            legend:{ display:false },
            tooltip:{
                backgroundColor:'#0F172A',
                padding:12,
                cornerRadius:10,
                callbacks:{
                    label:(ctx)=>{
                        const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                        const pct = total ? Math.round(ctx.parsed/total*100) : 0;
                        return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                    }
                }
            }
        }
    }
});

// Custom legend below the doughnut
const total = sData.reduce((a,b)=>a+b,0) || 1;
let legendHtml = '';
sLabels.forEach((k,i)=>{
    const pct = Math.round(sData[i]/total*100);
    legendHtml += '<div class="d-flex justify-content-between align-items-center py-1" style="font-size:13px">'
      + '<span><span class="legend-dot" style="background:'+sColors[i]+'"></span>'
      + k.charAt(0).toUpperCase() + k.slice(1) + '</span>'
      + '<span class="text-muted fw-semibold">' + sData[i] + ' <small>('+pct+'%)</small></span>'
      + '</div>';
});
document.getElementById('statusLegend').innerHTML = legendHtml || '<div class="text-muted text-center small">No data</div>';
</script>
<?php $extra_scripts = ob_get_clean(); ?>

<section class="hero">
    <h1>Pharmacy Overview 💊</h1>
    <p>Track stocks, expiring batches and today's dispensing activity.</p>
    <div class="quick">
        <a class="btn" href="<?= e(url('index.php?url=pharmacist/medicines')) ?>"><i class="fa-solid fa-plus me-2"></i>Add Medicine</a>
        <a class="btn" href="<?= e(url('index.php?url=pharmacist/batches')) ?>"><i class="fa-solid fa-box me-2"></i>Stock In</a>
        <a class="btn" href="<?= e(url('index.php?url=pharmacist/dispensing')) ?>"><i class="fa-solid fa-prescription-bottle-medical me-2"></i>Dispense</a>
    </div>
</section>

<?php include VIEW_PATH . 'partials' . DIRECTORY_SEPARATOR . 'stock_alerts.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-green"><div class="icon"><i class="fa-solid fa-pills"></i></div><div class="value"><?= (int)$stats['medicines'] ?></div><div class="label">Available Medicines</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-amber"><div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="value"><?= (int)$stats['low_stock'] ?></div><div class="label">Low Stock</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-red"><div class="icon"><i class="fa-solid fa-circle-exclamation"></i></div><div class="value"><?= (int)$stats['expired'] ?></div><div class="label">Expired</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-purple"><div class="icon"><i class="fa-solid fa-prescription-bottle-medical"></i></div><div class="value"><?= (int)$stats['dispensed_today'] ?></div><div class="label">Dispensed Today</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-chart-line text-primary me-2"></i>Dispensing Trend (12 mo.)</div>
            <div class="card-body"><canvas id="dispChart" height="120"></canvas></div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header text-warning fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Low Stock</div>
            <ul class="list-unstyled m-0">
                <?php if(!$low_list): ?><li class="text-center text-muted py-3">All stocks healthy 🎉</li>
                <?php else: foreach (array_slice($low_list,0,6) as $m): ?>
                    <li class="d-flex justify-content-between p-3 border-bottom">
                        <div><div class="fw-semibold"><?= e($m['name']) ?></div><small class="text-muted"><?= e($m['code']) ?></small></div>
                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2"><?= (int)$m['total_stock'] ?> <?= e($m['unit']) ?></span>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>

        <div class="card">
            <div class="card-header text-danger fw-semibold"><i class="fa-solid fa-circle-exclamation me-2"></i>Expired Batches</div>
            <ul class="list-unstyled m-0">
                <?php if(!$exp_list): ?><li class="text-center text-muted py-3">No expired batches.</li>
                <?php else: foreach (array_slice($exp_list,0,6) as $b): ?>
                    <li class="d-flex justify-content-between p-3 border-bottom">
                        <div><div class="fw-semibold"><?= e($b['name']) ?></div><small class="text-muted">Batch <?= e($b['batch_no']) ?></small></div>
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2"><?= e(fmt_date($b['expiration_date'])) ?></span>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const d = <?= json_encode($disp_chart) ?>;
const map = x => Object.fromEntries((x||[]).map(r=>[r.m,+r.q]));
const dm = map(d); const labels=[], series=[]; const now=new Date(); now.setDate(1);
for(let i=11;i>=0;i--){ const dt=new Date(now.getFullYear(),now.getMonth()-i,1);
  const k=dt.getFullYear()+'-'+String(dt.getMonth()+1).padStart(2,'0');
  labels.push(dt.toLocaleString('default',{month:'short'})); series.push(dm[k]||0); }
new Chart(document.getElementById('dispChart'),{
    type:'line',
    data:{labels,datasets:[{label:'Items Dispensed',data:series,borderColor:'#16A34A',backgroundColor:'rgba(22,163,74,.15)',tension:.4,fill:true}]},
    options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
});
</script>
<?php $extra_scripts = ob_get_clean(); ?>

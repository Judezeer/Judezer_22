<?php /* Main dashboard layout */
$user   = current_user();
$active = $active ?? '';
$title  = $title  ?? 'Dashboard';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?> — <?= e(setting('site_name','RHU Makilala HMIS')) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= e(ASSET_URL) ?>css/app.css">
</head>
<body>
<div class="app-shell">

    <?php include VIEW_PATH . 'partials' . DIRECTORY_SEPARATOR . 'sidebar.php'; ?>

    <div class="main">
        <?php include VIEW_PATH . 'partials' . DIRECTORY_SEPARATOR . 'topbar.php'; ?>
        <div class="page fade-in">
            <?php $ok  = flash('ok');  if ($ok):  ?>
                <div class="alert alert-success rounded-lg"><?= e($ok['msg']) ?></div>
            <?php endif; ?>
            <?php $err = flash('err'); if ($err): ?>
                <div class="alert alert-<?= e($err['type']) ?> rounded-lg"><?= e($err['msg']) ?></div>
            <?php endif; ?>
            <?= $__content ?>
        </div>
    </div>
</div>

<script>window.CSRF_TOKEN = '<?= e(csrf_token()) ?>';
       window.BASE_URL   = '<?= e(BASE_URL) ?>';
       window.NOTIF_URL  = '<?= e(url("index.php?url=api/notifications")) ?>';
       window.SEARCH_URL = '<?= e(url("index.php?url=api/search")) ?>';</script>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(ASSET_URL) ?>js/app.js"></script>
<?php if (!empty($extra_scripts)) echo $extra_scripts; ?>
</body>
</html>

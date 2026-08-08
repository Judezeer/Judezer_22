<?php /* Print-friendly layout (receipts, etc.) */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($title ?? 'Print') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(ASSET_URL) ?>css/app.css">
    <style>body{background:#fff}.receipt{max-width:820px;margin:24px auto;padding:32px}</style>
</head>
<body>
<div class="receipt">
    <?= $__content ?>
    <div class="text-center mt-4 no-print">
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
        <a class="btn btn-outline-primary" href="<?= e(url('index.php?url=pharmacist/dispensing')) ?>">Back</a>
    </div>
</div>
</body>
</html>

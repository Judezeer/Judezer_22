<?php
/**
 * Stock alert banners — auto-hidden when there's nothing to show.
 * Requires: $alert_low (array), $alert_expired (array)
 * Optional: $alert_role ('admin' | 'pharmacist') for the "View all" link target.
 */
$alert_low     = $alert_low     ?? [];
$alert_expired = $alert_expired ?? [];
$alert_role    = $alert_role    ?? (current_user()['role'] ?? 'admin');
$medUrl = 'index.php?url=' . ($alert_role === 'pharmacist' ? 'pharmacist/medicines'  : 'admin/medicines');
$batUrl = 'index.php?url=' . ($alert_role === 'pharmacist' ? 'pharmacist/batches'    : 'admin/inventory');
?>

<?php if (!empty($alert_expired)): ?>
<div class="stock-alert-banner danger">
    <div class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
    <div class="alert-body">
        <div class="alert-title">⚠️ <?= count($alert_expired) ?> Expired Batch<?= count($alert_expired) > 1 ? 'es' : '' ?> Detected</div>
        <div class="alert-msg">These medicines have already expired and should be removed from usable stock immediately.</div>
        <div class="alert-chips">
            <?php foreach (array_slice($alert_expired, 0, 5) as $b): ?>
                <span class="stock-alert-chip">
                    <?= e($b['name']) ?> · Batch <?= e($b['batch_no']) ?> · <?= (int)$b['quantity'] ?> <?= e($b['unit']) ?>
                </span>
            <?php endforeach; ?>
            <?php if (count($alert_expired) > 5): ?>
                <span class="stock-alert-chip">+ <?= count($alert_expired) - 5 ?> more</span>
            <?php endif; ?>
        </div>
    </div>
    <a class="alert-action" href="<?= e(url($batUrl)) ?>">
        Manage Batches <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
</div>
<?php endif; ?>

<?php if (!empty($alert_low)): ?>
<div class="stock-alert-banner warning">
    <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div class="alert-body">
        <div class="alert-title">🔔 <?= count($alert_low) ?> Medicine<?= count($alert_low) > 1 ? 's' : '' ?> Running Low</div>
        <div class="alert-msg">These items are at or below their reorder level. Consider restocking soon.</div>
        <div class="alert-chips">
            <?php foreach (array_slice($alert_low, 0, 5) as $m): ?>
                <span class="stock-alert-chip">
                    <?= e($m['name']) ?> · <?= (int)$m['total_stock'] ?> <?= e($m['unit']) ?> left
                </span>
            <?php endforeach; ?>
            <?php if (count($alert_low) > 5): ?>
                <span class="stock-alert-chip">+ <?= count($alert_low) - 5 ?> more</span>
            <?php endif; ?>
        </div>
    </div>
    <a class="alert-action" href="<?= e(url($medUrl)) ?>">
        Review Stock <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
</div>
<?php endif; ?>

<h4 class="fw-bold mb-3">Notifications</h4>

<div class="card">
    <ul class="list-unstyled m-0">
        <?php if (!$notifs): ?><li class="text-center text-muted py-4">No notifications.</li>
        <?php else: foreach ($notifs as $n): ?>
            <li class="p-3 border-bottom d-flex gap-3">
                <div style="width:42px;height:42px;border-radius:12px;background:#DCFCE7;color:#166534;display:flex;align-items:center;justify-content:center"><i class="fa-solid fa-bell"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= e($n['title']) ?></div>
                    <div class="text-muted"><?= e($n['message']) ?></div>
                    <small class="text-muted"><?= e(fmt_datetime($n['created_at'])) ?></small>
                </div>
                <?php if ($n['link']): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url($n['link'])) ?>">Open</a><?php endif; ?>
            </li>
        <?php endforeach; endif; ?>
    </ul>
</div>

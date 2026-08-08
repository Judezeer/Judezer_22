<?php $user = current_user(); ?>
<div class="topbar">
    <div class="d-flex align-items-center gap-2">
        <button id="sidebarToggle" class="icon-btn d-lg-none"><i class="fa-solid fa-bars"></i></button>
        <div>
            <div class="text-muted" style="font-size:12px;">Welcome back,</div>
            <div class="fw-semibold" style="font-size:15px;"><?= e($user['full_name']) ?> <span class="text-muted">· <?= e(ucfirst($user['role'])) ?></span></div>
        </div>
    </div>

    <?php if (in_array($user['role'], ['admin','nurse','pharmacist'], true)): ?>
    <div class="search d-none d-md-block" id="globalSearchWrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" id="globalSearch" class="form-control"
               placeholder="Search patients, medicines, appointments…" autocomplete="off">
        <div id="globalSearchResults" class="search-results" style="display:none"></div>
    </div>
    <?php else: ?>
    <div class="flex-grow-1"></div>
    <?php endif; ?>

    <div class="actions">
        <div class="dropdown">
            <button class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <i class="fa-solid fa-bell"></i>
                <span id="notifDot" class="dot" style="display:none"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-soft notif-dropdown" style="width:360px;border-radius:16px;padding:8px">
                <div class="d-flex justify-content-between align-items-center px-2 py-2 border-bottom mb-1">
                    <div>
                        <strong>Notifications</strong>
                        <span id="notifCount" class="badge bg-primary-subtle text-success ms-1" style="display:none"></span>
                    </div>
                    <a href="#" id="notifMarkAll" class="text-decoration-none small text-success fw-semibold" style="display:none">
                        <i class="fa-solid fa-check-double me-1"></i>Mark all read
                    </a>
                </div>
                <div id="notifList" style="max-height:400px;overflow:auto">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                        <div class="text-muted small mt-2">Loading…</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dropdown">
            <div class="profile" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar"><?= e(strtoupper(substr($user['full_name'],0,1))) ?></div>
                <div class="d-none d-md-block">
                    <div class="fw-semibold" style="font-size:13px;line-height:1"><?= e($user['full_name']) ?></div>
                    <div class="text-muted" style="font-size:11px;text-transform:capitalize"><?= e($user['role']) ?></div>
                </div>
                <i class="fa-solid fa-chevron-down text-muted" style="font-size:11px"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow-soft" style="border-radius:14px">
                <li class="px-3 py-2 border-bottom">
                    <div class="fw-semibold"><?= e($user['full_name']) ?></div>
                    <small class="text-muted"><?= e($user['email']) ?></small>
                </li>
                <?php if ($user['role'] === 'patient'): ?>
                    <li><a class="dropdown-item" href="<?= e(url('index.php?url=patient/profile')) ?>"><i class="fa-solid fa-user me-2"></i> My Profile</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item text-danger" href="<?= e(url('index.php?url=auth/logout')) ?>"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Sign out</a></li>
            </ul>
        </div>
    </div>
</div>

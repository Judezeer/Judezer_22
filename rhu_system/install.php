<?php
/**
 * Password reset / installer utility.
 *
 * The SQL file already ships with WORKING bcrypt hashes for the default
 * accounts, so you normally do NOT need to run this. Run it only if:
 *   - You imported the SQL on a very old MySQL that mangled the $ characters,
 *   - You changed a password and want to reset it back to the default,
 *   - You want to verify the credentials are working.
 *
 * Visit: http://localhost/rhu-makilala/install.php
 */
require_once __DIR__ . '/config/bootstrap.php';

$db = Database::conn();

$defaults = [
    'admin'      => 'Admin@123',
    'nurse'      => 'Nurse@123',
    'pharmacist' => 'Pharma@123',
    'patient'    => 'Patient@123',
];

$results = [];
foreach ($defaults as $username => $plain) {
    $hash = password_hash($plain, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hash, $username]);

    // Verify by reading it back and running password_verify()
    $check = $db->prepare("SELECT password FROM users WHERE username = ?");
    $check->execute([$username]);
    $stored = $check->fetchColumn();

    $results[$username] = [
        'password' => $plain,
        'ok'       => $stored && password_verify($plain, $stored),
        'updated'  => $stmt->rowCount(),
    ];
}

$allOk = array_reduce($results, fn($carry, $r) => $carry && $r['ok'], true);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>RHU Makilala HMIS — Password Reset</title>
    <style>
        body{font-family:'Segoe UI',Arial,sans-serif;background:#F8FFF8;color:#1F2937;
             display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
        .box{background:#fff;padding:40px 48px;border-radius:20px;
             box-shadow:0 10px 30px rgba(22,101,52,.12);max-width:620px;width:100%}
        h1{color:#166534;margin:0 0 8px}
        .ok{background:#dcfce7;color:#166534;padding:16px 20px;border-radius:12px;margin:16px 0}
        .bad{background:#fee2e2;color:#991b1b;padding:16px 20px;border-radius:12px;margin:16px 0}
        table{width:100%;border-collapse:collapse;margin:16px 0}
        th,td{text-align:left;padding:10px 12px;border-bottom:1px solid #e5e7eb}
        th{background:#f1f5f9;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.5px}
        code{background:#F1F5F9;padding:2px 6px;border-radius:6px;font-family:'Consolas','Monaco',monospace}
        .badge{padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600}
        .badge.ok-b{background:#dcfce7;color:#166534}
        .badge.bad-b{background:#fee2e2;color:#991b1b}
        a.btn{display:inline-block;margin-top:20px;background:#16A34A;color:#fff;
              padding:12px 28px;border-radius:12px;text-decoration:none;font-weight:600}
        a.btn:hover{background:#166534}
        .warn{background:#fef3c7;color:#92400e;padding:14px 18px;border-radius:12px;margin:16px 0;font-size:14px}
    </style>
</head>
<body>
<div class="box">
    <?php if ($allOk): ?>
        <h1>✅ Passwords Reset Successfully</h1>
        <p>All 4 default accounts are now active and their passwords have been verified.</p>
    <?php else: ?>
        <h1 style="color:#991b1b">⚠️ Something went wrong</h1>
        <p>Some passwords could not be set. Check the details below.</p>
    <?php endif; ?>

    <table>
        <thead><tr><th>Username</th><th>Password</th><th>Rows Updated</th><th>Verified</th></tr></thead>
        <tbody>
        <?php foreach ($results as $u => $r): ?>
            <tr>
                <td><code><?= htmlspecialchars($u) ?></code></td>
                <td><code><?= htmlspecialchars($r['password']) ?></code></td>
                <td><?= (int)$r['updated'] ?></td>
                <td>
                    <?php if ($r['ok']): ?>
                        <span class="badge ok-b">✓ Works</span>
                    <?php else: ?>
                        <span class="badge bad-b">✗ Failed</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($allOk): ?>
        <div class="warn">
            🔒 <strong>Security tip:</strong> Delete <code>install.php</code>
            from the project root once you have successfully signed in.
        </div>
        <a class="btn" href="<?= htmlspecialchars(url('index.php?url=auth/login')) ?>">Go to Login →</a>
    <?php else: ?>
        <div class="bad">
            Please make sure your database has the <code>users</code> table and
            that the seed rows exist (import <code>database/rhu_makilala.sql</code>
            first). Then re-run this page.
        </div>
    <?php endif; ?>
</div>
</body>
</html>

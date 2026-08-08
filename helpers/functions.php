<?php
/**
 * Global helper functions – kept small, side-effect free where possible.
 */

/** Escape output for HTML context (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an absolute URL relative to BASE_URL. */
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

/** Redirect helper. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Return a session flash message, or set one. */
function flash(string $key, ?string $msg = null, string $type = 'success')
{
    if ($msg !== null) {
        $_SESSION['_flash'][$key] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

/** Current logged-in user or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Role checks. */
function is_logged_in(): bool { return isset($_SESSION['user']); }
function has_role(string ...$roles): bool
{
    if (!is_logged_in()) return false;
    return in_array($_SESSION['user']['role'], $roles, true);
}

/** CSRF token helpers. */
function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}
function csrf_field(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrf_token()) . '">';
}
function csrf_verify(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

/** JSON API response. */
function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/** Sanitize a string input. */
function clean(?string $s): string
{
    return trim(preg_replace('/\s+/', ' ', (string)$s));
}

/** Format date/time for humans. */
function fmt_date($d, string $fmt = 'M d, Y'): string
{
    if (!$d) return '—';
    $t = is_numeric($d) ? (int)$d : strtotime((string)$d);
    return $t ? date($fmt, $t) : '—';
}
function fmt_datetime($d): string { return fmt_date($d, 'M d, Y g:i A'); }

/** Compute age from birthdate. */
function age_from(?string $bd): string
{
    if (!$bd) return '—';
    try {
        $b = new DateTime($bd);
        $n = new DateTime('today');
        return (string)$b->diff($n)->y;
    } catch (Exception $e) { return '—'; }
}

/** Load a view with layout (unless $partial=true). */
function view(string $path, array $data = [], bool $partial = false): void
{
    extract($data, EXTR_SKIP);
    $__viewFile = VIEW_PATH . str_replace('.', DIRECTORY_SEPARATOR, $path) . '.php';
    if (!file_exists($__viewFile)) {
        die('View not found: ' . e($path));
    }
    if ($partial) { include $__viewFile; return; }

    // Guest / auth pages render standalone; app pages use dashboard layout
    $layout = $data['_layout'] ?? 'shared.layout';
    ob_start();
    include $__viewFile;
    $__content = ob_get_clean();
    include VIEW_PATH . str_replace('.', DIRECTORY_SEPARATOR, $layout) . '.php';
}

/** Generate the next patient code. */
function next_patient_code(PDO $db): string
{
    $row = $db->query("SELECT MAX(id) AS m FROM patients")->fetch();
    $n = ((int)($row['m'] ?? 0)) + 1;
    return 'PT-' . str_pad((string)$n, 6, '0', STR_PAD_LEFT);
}

/** Generate the next receipt number. */
function next_receipt_no(PDO $db): string
{
    $row = $db->query("SELECT MAX(id) AS m FROM dispensing")->fetch();
    $n = ((int)($row['m'] ?? 0)) + 1;
    return 'RX-' . date('Ymd') . '-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

/** Status badge helper. */
function status_badge(string $status): string
{
    $map = [
        'pending'     => ['warning', 'Pending'],
        'approved'    => ['info',    'Approved'],
        'completed'   => ['success', 'Completed'],
        'rejected'    => ['danger',  'Rejected'],
        'cancelled'   => ['secondary','Cancelled'],
        'rescheduled' => ['primary', 'Rescheduled'],
        'active'      => ['success', 'Active'],
        'inactive'    => ['secondary','Inactive'],
    ];
    [$class, $label] = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge bg-' . $class . '-subtle text-' . $class . ' rounded-pill px-3 py-2">' . e($label) . '</span>';
}

/** Get a setting value. */
function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (Database::conn()->query("SELECT skey,svalue FROM settings") as $r) {
            $cache[$r['skey']] = $r['svalue'];
        }
    }
    return $cache[$key] ?? $default;
}

/**
 * Render the clinic logo — uses the uploaded image if one exists in
 * settings, otherwise falls back to a Font Awesome heart-pulse icon.
 *
 * @param string $variant 'sidebar' | 'login' | 'receipt'
 */
function render_logo(string $variant = 'sidebar'): string
{
    $logo = setting('clinic_logo');

    // Sizes per placement
    $sizes = [
        'sidebar' => ['box' => 44, 'icon' => 20, 'radius' => 14],
        'login'   => ['box' => 56, 'icon' => 26, 'radius' => 18],
        'receipt' => ['box' => 72, 'icon' => 30, 'radius' => 20],
    ];
    $s = $sizes[$variant] ?? $sizes['sidebar'];

    // If uploaded logo exists and file is still on disk, use it
    if ($logo && is_file(UPLOAD_PATH . $logo)) {
        $url = UPLOAD_URL . $logo;
        return '<div class="logo logo-img" style="width:' . $s['box'] . 'px;height:' . $s['box'] . 'px;border-radius:' . $s['radius'] . 'px;overflow:hidden;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(74,222,128,.35)">'
             . '<img src="' . e($url) . '" alt="Logo" style="width:100%;height:100%;object-fit:cover">'
             . '</div>';
    }

    // Fallback: gradient icon
    return '<div class="logo" style="width:' . $s['box'] . 'px;height:' . $s['box'] . 'px;border-radius:' . $s['radius'] . 'px;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:#fff;font-size:' . $s['icon'] . 'px;box-shadow:0 4px 14px rgba(74,222,128,.35)">'
         . '<i class="fa-solid fa-heart-pulse"></i>'
         . '</div>';
}

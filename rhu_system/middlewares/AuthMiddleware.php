<?php
/**
 * Authentication / role guards.
 */
class AuthMiddleware
{
    /** Require any logged-in user. Auto-logout on session timeout. */
    public static function requireLogin(): void
    {
        if (!is_logged_in()) {
            flash('login_error', 'Please sign in to continue.', 'warning');
            redirect('index.php?url=auth/login');
        }
        // Idle timeout
        $last = $_SESSION['last_activity'] ?? time();
        if ((time() - $last) > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            session_start();
            flash('login_error', 'Session expired. Please sign in again.', 'warning');
            redirect('index.php?url=auth/login');
        }
        $_SESSION['last_activity'] = time();
    }

    /** Require one of the given roles, else 403. */
    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!has_role(...$roles)) {
            http_response_code(403);
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
                    <h2 style="color:#b91c1c;">403 — Access Denied</h2>
                    <p>You do not have permission to access this page.</p>
                    <a href="' . url('index.php') . '">Back to dashboard</a>
                 </div>');
        }
    }

    /** Verify CSRF token for POST requests. */
    public static function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!csrf_verify($token)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response(['ok' => false, 'message' => 'Invalid CSRF token.'], 419);
            }
            http_response_code(419);
            die('Invalid or expired form token. Please refresh and try again.');
        }
    }
}

<?php
class AuthController
{
    private UserModel $users;
    public function __construct() { $this->users = new UserModel(); }

    /** Show login page (default). */
    public function index(): void { $this->login(); }

    public function login(): void
    {
        // Already logged in? push to dashboard.
        if (is_logged_in()) redirect('index.php');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify($_POST[CSRF_TOKEN_NAME] ?? null)) {
                flash('login_error', 'Session expired. Please try again.', 'danger');
                redirect('index.php?url=auth/login');
            }
            $id  = clean($_POST['identifier'] ?? '');
            $pwd = (string)($_POST['password'] ?? '');

            $u = $this->users->findByUsernameOrEmail($id);
            if (!$u || !password_verify($pwd, $u['password'])) {
                AuditLogger::log('login_failed', 'auth', 'Attempt for: ' . $id);
                flash('login_error', 'Invalid username or password.', 'danger');
                redirect('index.php?url=auth/login');
            }
            if ($u['status'] !== 'active') {
                flash('login_error', 'Your account is inactive. Contact the administrator.', 'warning');
                redirect('index.php?url=auth/login');
            }

            // Regenerate session id (prevent fixation)
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'        => (int)$u['id'],
                'username'  => $u['username'],
                'full_name' => $u['full_name'],
                'email'     => $u['email'],
                'role'      => $u['role'],
                'photo'     => $u['photo'],
            ];
            $_SESSION['last_activity'] = time();
            $this->users->touchLogin((int)$u['id']);
            AuditLogger::log('login', 'auth', 'User signed in.');

            // Heal databases that were imported before seed notifications existed
            Notifier::ensureWelcomeSeeds();

            redirect('index.php'); // router will send to role dashboard
        }

        view('auth.login', ['_layout' => 'shared.blank']);
    }

    public function logout(): void
    {
        AuditLogger::log('logout', 'auth', 'User signed out.');
        session_unset();
        session_destroy();
        redirect('index.php?url=auth/login');
    }

    public function forgot(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = clean($_POST['email'] ?? '');
            // For an offline RHU deployment we don't send actual emails;
            // we generate a short-lived token the admin can hand to the user.
            $u = $this->users->findByUsernameOrEmail($email);
            if ($u) {
                $tok = bin2hex(random_bytes(16));
                $exp = date('Y-m-d H:i:s', time() + 3600);
                Database::conn()->prepare(
                    "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?"
                )->execute([$tok, $exp, $u['id']]);
                AuditLogger::log('password_reset_request', 'auth', 'For ' . $email);
                flash('forgot_msg', 'Reset token generated. Please contact the administrator with this code: ' . $tok, 'info');
            } else {
                flash('forgot_msg', 'If the account exists a reset code was generated. Please contact your administrator.', 'info');
            }
            redirect('index.php?url=auth/forgot');
        }
        view('auth.forgot', ['_layout' => 'shared.blank']);
    }
}

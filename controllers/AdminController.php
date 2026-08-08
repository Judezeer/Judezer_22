<?php
class AdminController
{
    private UserModel $users;
    private PatientModel $patients;
    private AppointmentModel $appts;
    private MedicineModel $meds;
    private DispensingModel $disp;
    private AuditModel $audit;
    private SettingModel $settings;

    public function __construct()
    {
        AuthMiddleware::requireRole('admin');
        $this->users    = new UserModel();
        $this->patients = new PatientModel();
        $this->appts    = new AppointmentModel();
        $this->meds     = new MedicineModel();
        $this->disp     = new DispensingModel();
        $this->audit    = new AuditModel();
        $this->settings = new SettingModel();
    }

    // -------------------- Dashboard --------------------
    public function dashboard(): void
    {
        // Regenerate operational alerts (low stock / expired). Idempotent 24h.
        Notifier::refreshInventoryAlerts();

        $stats = [
            'patients'        => $this->patients->count(),
            'today_appts'     => $this->appts->countToday(),
            'medicines'       => $this->meds->countAvailable(),
            'low_stock'       => $this->meds->countLowStock(),
            'expired'         => $this->meds->countExpired(),
            'dispensed_today' => $this->disp->countToday(),
        ];
        $data = [
            'title'          => 'Admin Dashboard',
            'active'         => 'dashboard',
            'stats'          => $stats,
            'patients_chart' => $this->patients->monthlySeries(),
            'appts_chart'    => $this->appts->monthlySeries(),
            'disp_chart'     => $this->disp->monthlySeries(),
            'appt_status'    => $this->appts->countByStatus(),
            'recent'         => $this->audit->recent(10),
            'alert_low'      => $this->meds->lowStockList(),
            'alert_expired'  => $this->meds->expiredList(),
            'alert_role'     => 'admin',
        ];
        view('admin.dashboard', $data);
    }

    // -------------------- Users --------------------
    public function users(): void
    {
        view('admin.users', [
            'title'  => 'Manage Users',
            'active' => 'users',
            'users'  => $this->users->all(),
        ]);
    }

    public function user_save(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'username'  => clean($_POST['username'] ?? ''),
            'email'     => clean($_POST['email']    ?? ''),
            'full_name' => clean($_POST['full_name']?? ''),
            'role'      => in_array($_POST['role'] ?? '', ['admin','nurse','pharmacist','patient'], true) ? $_POST['role'] : 'patient',
            'phone'     => clean($_POST['phone']    ?? ''),
            'status'    => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];
        if (!empty($_POST['password'])) $d['password'] = (string)$_POST['password'];

        try {
            if ($id > 0) {
                $this->users->update($id, $d);
                AuditLogger::log('update', 'users', 'Updated user #' . $id);
                json_response(['ok'=>true,'message'=>'User updated successfully.']);
            } else {
                if (empty($d['password'])) json_response(['ok'=>false,'message'=>'Password is required for new users.'], 422);
                $newId = $this->users->create($d);
                AuditLogger::log('insert', 'users', 'Created user #' . $newId);
                json_response(['ok'=>true,'message'=>'User created successfully.']);
            }
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>'Save failed: ' . $e->getMessage()], 500);
        }
    }

    public function user_delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)current_user()['id']) {
            json_response(['ok'=>false,'message'=>'You cannot delete your own account.'], 400);
        }
        try {
            $this->users->delete($id);
            AuditLogger::log('delete', 'users', 'Deleted user #' . $id);
            json_response(['ok'=>true,'message'=>'User deleted.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    // -------------------- Reports --------------------
    public function reports(): void
    {
        $type = $_GET['type'] ?? 'patients';
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $rows = [];
        switch ($type) {
            case 'appointments':
                $rows = $this->appts->all(['from'=>$from,'to'=>$to]); break;
            case 'inventory':
                $rows = $this->meds->all(); break;
            case 'dispensing':
                $rows = $this->disp->all(['from'=>$from,'to'=>$to]); break;
            case 'patients':
            default:
                $rows = $this->patients->all(); $type = 'patients';
        }

        view('admin.reports', [
            'title'  => 'Reports',
            'active' => 'reports',
            'type'   => $type,
            'from'   => $from,
            'to'     => $to,
            'rows'   => $rows,
        ]);
    }

    // -------------------- Audit --------------------
    public function audit(): void
    {
        view('admin.audit', [
            'title'  => 'Audit Logs',
            'active' => 'audit',
            'logs'   => $this->audit->all([
                'action' => $_GET['action'] ?? null,
                'module' => $_GET['module'] ?? null,
                'from'   => $_GET['from'] ?? null,
                'to'     => $_GET['to'] ?? null,
            ]),
        ]);
    }

    // -------------------- Backup --------------------
    public function backup(): void
    {
        // Build a plain-text SQL dump of all tables.
        $db = Database::conn();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sql  = "-- RHU Makilala HMIS backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables as $t) {
            $create = $db->query("SHOW CREATE TABLE `$t`")->fetch();
            $sql .= "DROP TABLE IF EXISTS `$t`;\n" . $create['Create Table'] . ";\n\n";
            $rows = $db->query("SELECT * FROM `$t`")->fetchAll();
            foreach ($rows as $r) {
                $cols = array_map(fn($c) => "`$c`", array_keys($r));
                $vals = array_map(function($v) use ($db) {
                    return $v === null ? 'NULL' : $db->quote((string)$v);
                }, array_values($r));
                $sql .= "INSERT INTO `$t` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql .= "\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        AuditLogger::log('backup', 'system', 'Database backup downloaded.');
        $filename = 'rhu_makilala_backup_' . date('Ymd_His') . '.sql';
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $sql;
        exit;
    }

    // -------------------- Settings --------------------
    public function settings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle logo upload first if present
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $uploaded = $this->handleLogoUpload($_FILES['logo']);
                if ($uploaded) {
                    $this->settings->set('clinic_logo', $uploaded);
                } else {
                    flash('err', 'Logo upload failed. Please use JPG, PNG, or WEBP under 2MB.', 'danger');
                    redirect('index.php?url=admin/settings');
                }
            }

            // Handle "remove logo" action
            if (!empty($_POST['remove_logo'])) {
                $current = setting('clinic_logo');
                if ($current) {
                    $fp = UPLOAD_PATH . $current;
                    if (is_file($fp)) @unlink($fp);
                }
                $this->settings->set('clinic_logo', '');
            }

            // Save all other text settings
            $skip = [CSRF_TOKEN_NAME, 'remove_logo', 'clinic_logo'];
            foreach ($_POST as $k => $v) {
                if (in_array($k, $skip, true)) continue;
                $this->settings->set($k, is_array($v) ? json_encode($v) : (string)$v);
            }
            AuditLogger::log('update', 'settings', 'Updated system settings.');
            flash('ok', 'Settings updated successfully.');
            redirect('index.php?url=admin/settings');
        }
        view('admin.settings', [
            'title'    => 'System Settings',
            'active'   => 'settings',
            'settings' => $this->settings->all(),
        ]);
    }

    /**
     * Validate + move an uploaded logo file into assets/uploads/branding/.
     * Returns the relative path (e.g. "branding/logo_xxx.png") or null on failure.
     */
    private function handleLogoUpload(array $file): ?string
    {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $mime = @mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null; // 2MB cap

        $dir = UPLOAD_PATH . 'branding';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext);
        $name = 'logo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
        return 'branding/' . $name;
    }

    // Reuse nurse & pharmacist read/write for admin convenience
    public function patients()      { (new NurseController(true))->patients(); }
    public function patient_save()  { (new NurseController(true))->patient_save(); }
    public function patient_delete(){ (new NurseController(true))->patient_delete(); }
    public function patient_view($id=0){ (new NurseController(true))->patient_view($id); }
    public function appointments()  { (new NurseController(true))->appointments(); }
    public function appt_status()   { (new NurseController(true))->appt_status(); }
    public function medicines()     { (new PharmacistController(true))->medicines(); }
    public function inventory()     { (new PharmacistController(true))->inventory(); }
    public function dispensing()    { (new PharmacistController(true))->dispensing(); }
}

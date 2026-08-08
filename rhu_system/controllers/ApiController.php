<?php
/**
 * Small JSON API used by dropdowns / AJAX widgets.
 */
class ApiController
{
    public function __construct() { AuthMiddleware::requireLogin(); }

    /** GET api/patients?q= — used by dispense autocomplete. */
    public function patients(): void
    {
        $q = clean($_GET['q'] ?? '');
        $rows = (new PatientModel())->all($q ?: null);
        $out = array_map(fn($p) => [
            'id'    => (int)$p['id'],
            'text'  => $p['patient_code'] . ' — ' . $p['first_name'] . ' ' . $p['last_name'],
        ], $rows);
        json_response(['results' => $out]);
    }

    /** GET api/medicines?q= — dispense autocomplete. */
    public function medicines(): void
    {
        $q = clean($_GET['q'] ?? '');
        $rows = (new MedicineModel())->all($q ?: null);
        $out = [];
        foreach ($rows as $m) {
            $out[] = [
                'id'    => (int)$m['id'],
                'code'  => $m['code'],
                'name'  => $m['name'],
                'unit'  => $m['unit'],
                'stock' => (int)$m['total_stock'],
                'text'  => $m['name'] . ' (' . $m['code'] . ') — stock: ' . (int)$m['total_stock'],
            ];
        }
        json_response(['results' => $out]);
    }

    /** GET api/notifications — poll for badge. */
    public function notifications(): void
    {
        $u    = current_user();
        $uid  = (int)$u['id'];
        $role = $u['role'];
        $m    = new NotificationModel();
        json_response([
            'unread' => $m->unreadCount($uid, $role),
            'items'  => $m->forUser($uid, $role, 10),
        ]);
    }

    /** POST api/notif_read — mark one (or all) notification(s) as read. */
    public function notif_read(): void
    {
        $u    = current_user();
        $uid  = (int)$u['id'];
        $role = $u['role'];
        $m    = new NotificationModel();

        if (!empty($_POST['all'])) {
            $m->markAllRead($uid, $role);
            json_response(['ok' => true, 'unread' => 0]);
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) Notifier::markRead($id, $uid, $role);
        json_response(['ok' => true, 'unread' => $m->unreadCount($uid, $role)]);
    }

    /**
     * GET api/search?q=… — global topbar search.
     * Searches patients, medicines and appointments (respecting role).
     */
    public function search(): void
    {
        $q = clean($_GET['q'] ?? '');
        $out = ['patients' => [], 'medicines' => [], 'appointments' => []];

        if (mb_strlen($q) < 2) { json_response($out); }

        $role = current_user()['role'];
        $like = "%{$q}%";
        $db = Database::conn();

        // Patients — visible to admin & nurse (patients see only their own record elsewhere)
        if (in_array($role, ['admin','nurse'], true)) {
            $st = $db->prepare(
                "SELECT id, patient_code, first_name, last_name, barangay, contact_no
                 FROM patients
                 WHERE first_name LIKE ? OR last_name LIKE ?
                    OR patient_code LIKE ? OR barangay LIKE ? OR contact_no LIKE ?
                 ORDER BY last_name ASC LIMIT 6"
            );
            $st->execute([$like,$like,$like,$like,$like]);
            foreach ($st->fetchAll() as $p) {
                $out['patients'][] = [
                    'id'    => (int)$p['id'],
                    'title' => $p['first_name'] . ' ' . $p['last_name'],
                    'sub'   => $p['patient_code'] . ' · ' . ($p['barangay'] ?: '—') . ($p['contact_no'] ? ' · ' . $p['contact_no'] : ''),
                    'link'  => 'index.php?url=' . $role . '/patient_view/' . (int)$p['id'],
                ];
            }
        }

        // Medicines — visible to admin & pharmacist
        if (in_array($role, ['admin','pharmacist'], true)) {
            $st = $db->prepare(
                "SELECT m.id, m.code, m.name, m.generic_name, m.unit,
                        COALESCE(SUM(b.quantity),0) AS stock
                 FROM medicines m
                 LEFT JOIN medicine_batches b ON b.medicine_id = m.id
                 WHERE m.name LIKE ? OR m.generic_name LIKE ? OR m.code LIKE ? OR m.category LIKE ?
                 GROUP BY m.id
                 ORDER BY m.name ASC LIMIT 6"
            );
            $st->execute([$like,$like,$like,$like]);
            foreach ($st->fetchAll() as $m) {
                $out['medicines'][] = [
                    'id'    => (int)$m['id'],
                    'title' => $m['name'] . ($m['generic_name'] ? ' (' . $m['generic_name'] . ')' : ''),
                    'sub'   => $m['code'] . ' · Stock: ' . (int)$m['stock'] . ' ' . $m['unit'],
                    'link'  => 'index.php?url=' . ($role === 'admin' ? 'admin' : 'pharmacist') . '/medicines',
                ];
            }
        }

        // Appointments — visible to admin & nurse
        if (in_array($role, ['admin','nurse'], true)) {
            $st = $db->prepare(
                "SELECT a.id, a.appointment_date, a.appointment_time, a.purpose, a.status,
                        p.first_name, p.last_name, p.patient_code
                 FROM appointments a
                 JOIN patients p ON p.id = a.patient_id
                 WHERE p.first_name LIKE ? OR p.last_name LIKE ?
                    OR p.patient_code LIKE ? OR a.purpose LIKE ?
                 ORDER BY a.appointment_date DESC LIMIT 6"
            );
            $st->execute([$like,$like,$like,$like]);
            foreach ($st->fetchAll() as $a) {
                $out['appointments'][] = [
                    'id'    => (int)$a['id'],
                    'title' => $a['first_name'] . ' ' . $a['last_name'] . ' — ' . $a['purpose'],
                    'sub'   => $a['patient_code'] . ' · ' . fmt_date($a['appointment_date'])
                             . ' ' . date('g:i A', strtotime($a['appointment_time']))
                             . ' · ' . ucfirst($a['status']),
                    'link'  => 'index.php?url=' . $role . '/appointments',
                ];
            }
        }

        json_response($out);
    }
}

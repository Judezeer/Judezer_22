<?php
/**
 * Notification helper.
 * Push in-app notifications targeted to a user or a role.
 */
class Notifier
{
    public static function toUser(int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        $db = Database::conn();
        $db->prepare(
            "INSERT INTO notifications (user_id, role, type, title, message, link)
             VALUES (?, NULL, ?, ?, ?, ?)"
        )->execute([$userId, $type, $title, $message, $link]);
    }

    public static function toRole(string $role, string $type, string $title, string $message, ?string $link = null): void
    {
        $db = Database::conn();
        $ids = $db->prepare("SELECT id FROM users WHERE role = ? AND status='active'");
        $ids->execute([$role]);
        foreach ($ids->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            self::toUser((int)$uid, $type, $title, $message, $link);
        }
    }

    /**
     * Broadcast a notification to every user in a role (single row with user_id=NULL).
     * Cheaper than fanning out; the API surfaces these to any user of that role.
     * Idempotent: won't create duplicates of the same (type,title) unread within
     * the last 24 hours.
     */
    public static function broadcast(string $role, string $type, string $title, string $message, ?string $link = null): void
    {
        $db = Database::conn();
        $chk = $db->prepare(
            "SELECT id FROM notifications
             WHERE role = ? AND type = ? AND title = ?
               AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             LIMIT 1"
        );
        $chk->execute([$role, $type, $title]);
        if ($chk->fetchColumn()) return;

        $db->prepare(
            "INSERT INTO notifications (user_id, role, type, title, message, link, is_read)
             VALUES (NULL, ?, ?, ?, ?, ?, 0)"
        )->execute([$role, $type, $title, $message, $link]);
    }

    /**
     * Regenerate operational alerts (low stock + expired batches).
     * Safe to call from any dashboard – idempotent within 24h via broadcast().
     */
    public static function refreshInventoryAlerts(): void
    {
        try {
            $meds = new MedicineModel();
            $low  = $meds->lowStockList();
            foreach ($low as $m) {
                self::broadcast('admin', 'low_stock',
                    'Low stock: ' . $m['name'],
                    'Only ' . (int)$m['total_stock'] . ' ' . $m['unit'] . ' left (reorder at ' . (int)$m['reorder_level'] . ').',
                    'index.php?url=admin/medicines');
                self::broadcast('pharmacist', 'low_stock',
                    'Low stock: ' . $m['name'],
                    'Only ' . (int)$m['total_stock'] . ' ' . $m['unit'] . ' left (reorder at ' . (int)$m['reorder_level'] . ').',
                    'index.php?url=pharmacist/medicines');
            }
            $exp = $meds->expiredList();
            foreach ($exp as $b) {
                self::broadcast('admin', 'expired',
                    'Expired: ' . $b['name'],
                    'Batch ' . $b['batch_no'] . ' expired on ' . date('M d, Y', strtotime($b['expiration_date'])) . '. ' . (int)$b['quantity'] . ' units on hand.',
                    'index.php?url=admin/inventory');
                self::broadcast('pharmacist', 'expired',
                    'Expired: ' . $b['name'],
                    'Batch ' . $b['batch_no'] . ' expired on ' . date('M d, Y', strtotime($b['expiration_date'])) . '. ' . (int)$b['quantity'] . ' units on hand.',
                    'index.php?url=pharmacist/batches');
            }
            $near = $meds->nearExpiryList((int)(setting('near_expiry_days','60')));
            foreach ($near as $b) {
                self::broadcast('pharmacist', 'near_expiry',
                    'Expiring soon: ' . $b['name'],
                    'Batch ' . $b['batch_no'] . ' expires on ' . date('M d, Y', strtotime($b['expiration_date'])) . ' — ' . (int)$b['quantity'] . ' units.',
                    'index.php?url=pharmacist/batches');
            }
        } catch (Throwable $e) {
            // Never break the dashboard for alerts.
            error_log('Notifier::refreshInventoryAlerts failed: ' . $e->getMessage());
        }
    }

    /**
     * Ensure every role has a welcome notification (idempotent).
     * Runs cheaply from the layout so it heals databases imported before
     * seed notifications existed.
     */
    public static function ensureWelcomeSeeds(): void
    {
        try {
            $db = Database::conn();
            $existing = $db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
            if ((int)$existing > 0) return;

            $seeds = [
                ['admin',      'system', 'Welcome to RHU Makilala HMIS', 'Your health information system is ready. Start by adding users and medicines.', 'index.php?url=admin/users'],
                ['admin',      'system', 'Backup reminder', 'Consider scheduling weekly database backups from the Backup DB menu.', 'index.php?url=admin/backup'],
                ['nurse',      'system', 'Welcome, Nurse!', 'Register your first patient to get started.', 'index.php?url=nurse/patients'],
                ['pharmacist', 'system', 'Welcome, Pharmacist!', 'Add medicines and stock in the first batch to begin dispensing.', 'index.php?url=pharmacist/medicines'],
                ['patient',    'system', 'Welcome to RHU Makilala', 'You can now book appointments and view your medical records online.', 'index.php?url=patient/book'],
            ];
            $stmt = $db->prepare(
                "INSERT INTO notifications (user_id, role, type, title, message, link, is_read)
                 VALUES (NULL, ?, ?, ?, ?, ?, 0)"
            );
            foreach ($seeds as $s) $stmt->execute($s);
        } catch (Throwable $e) {
            error_log('Notifier::ensureWelcomeSeeds failed: ' . $e->getMessage());
        }
    }

    /** Mark a single notification as read (only if it belongs to the user/role). */
    public static function markRead(int $notifId, int $userId, string $role): bool
    {
        try {
            $db = Database::conn();
            $stmt = $db->prepare(
                "UPDATE notifications SET is_read = 1
                 WHERE id = ?
                   AND (user_id = ? OR (user_id IS NULL AND role IN ('all', ?)))"
            );
            $stmt->execute([$notifId, $userId, $role]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}

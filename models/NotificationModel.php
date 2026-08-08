<?php
class NotificationModel extends BaseModel
{
    /**
     * Return notifications targeted at the user OR broadcast to their role
     * (or to 'all'). We cast role to text for the IN() list.
     */
    public function forUser(int $userId, string $role, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications
             WHERE user_id = ?
                OR (user_id IS NULL AND role IN ('all', ?))
             ORDER BY created_at DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute([$userId, $role]);
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId, string $role): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE is_read = 0
               AND (user_id = ?
                    OR (user_id IS NULL AND role IN ('all', ?)))"
        );
        $stmt->execute([$userId, $role]);
        return (int)$stmt->fetchColumn();
    }

    public function markAllRead(int $userId, string $role): void
    {
        $this->db->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE (user_id = ?)
                OR (user_id IS NULL AND role IN ('all', ?))"
        )->execute([$userId, $role]);
    }
}

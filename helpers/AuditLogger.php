<?php
/**
 * Central audit trail writer.
 * Every state-changing controller action should call AuditLogger::log().
 */
class AuditLogger
{
    public static function log(string $action, string $module, string $description = ''): void
    {
        try {
            $db = Database::conn();
            $stmt = $db->prepare(
                "INSERT INTO audit_logs (user_id, action, module, description, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['user']['id'] ?? null,
                $action,
                $module,
                mb_substr($description, 0, 255),
                $_SERVER['REMOTE_ADDR']     ?? null,
                mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (Throwable $e) {
            // Never let audit logging break a request.
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}

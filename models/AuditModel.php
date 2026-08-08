<?php
class AuditModel extends BaseModel
{
    public function all(array $filters = []): array
    {
        $sql = "SELECT a.*, u.full_name, u.role
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id";
        $where=[]; $params=[];
        if (!empty($filters['action'])) { $where[]="a.action = ?"; $params[]=$filters['action']; }
        if (!empty($filters['module'])) { $where[]="a.module = ?"; $params[]=$filters['module']; }
        if (!empty($filters['from']))   { $where[]="DATE(a.created_at) >= ?"; $params[]=$filters['from']; }
        if (!empty($filters['to']))     { $where[]="DATE(a.created_at) <= ?"; $params[]=$filters['to']; }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY a.created_at DESC LIMIT 500";
        $stmt=$this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name, u.role
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

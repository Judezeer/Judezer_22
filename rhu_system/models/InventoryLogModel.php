<?php
class InventoryLogModel extends BaseModel
{
    public function all(array $filters = []): array
    {
        $sql = "SELECT il.*, m.name AS medicine_name, m.code AS medicine_code,
                       b.batch_no, u.full_name AS performed_by_name
                FROM inventory_logs il
                JOIN medicines m ON m.id = il.medicine_id
                LEFT JOIN medicine_batches b ON b.id = il.batch_id
                LEFT JOIN users u ON u.id = il.performed_by";
        $where=[]; $params=[];
        if (!empty($filters['action'])) { $where[]="il.action = ?"; $params[]=$filters['action']; }
        if (!empty($filters['from']))   { $where[]="DATE(il.created_at) >= ?"; $params[]=$filters['from']; }
        if (!empty($filters['to']))     { $where[]="DATE(il.created_at) <= ?"; $params[]=$filters['to']; }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY il.created_at DESC LIMIT 1000";
        $stmt=$this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

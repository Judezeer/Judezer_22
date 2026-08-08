<?php
class MedicineModel extends BaseModel
{
    /** List medicines with aggregated stock and nearest expiry. */
    public function all(?string $search = null): array
    {
        $sql = "SELECT m.*,
                       COALESCE(SUM(b.quantity),0) AS total_stock,
                       MIN(CASE WHEN b.quantity > 0 THEN b.expiration_date END) AS nearest_expiry
                FROM medicines m
                LEFT JOIN medicine_batches b ON b.medicine_id = m.id
                ";
        $params = [];
        if ($search) {
            $sql .= " WHERE m.name LIKE ? OR m.generic_name LIKE ? OR m.code LIKE ? OR m.category LIKE ?";
            $s = "%$search%";
            $params = [$s,$s,$s,$s];
        }
        $sql .= " GROUP BY m.id ORDER BY m.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, COALESCE(SUM(b.quantity),0) AS total_stock
             FROM medicines m
             LEFT JOIN medicine_batches b ON b.medicine_id = m.id
             WHERE m.id = ?
             GROUP BY m.id"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO medicines
             (code,name,generic_name,category,dosage_form,strength,unit,description,
              reorder_level,supplier,status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['code'], $d['name'], $d['generic_name'] ?? null,
            $d['category'] ?? null, $d['dosage_form'] ?? null,
            $d['strength'] ?? null, $d['unit'] ?? 'piece',
            $d['description'] ?? null,
            (int)($d['reorder_level'] ?? 20),
            $d['supplier'] ?? null,
            $d['status'] ?? 'active',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $cols = ['code','name','generic_name','category','dosage_form','strength','unit',
                 'description','reorder_level','supplier','status'];
        $set=[]; $vals=[];
        foreach ($cols as $c) if (array_key_exists($c,$d)) { $set[]="$c = ?"; $vals[]=$d[$c]; }
        if (!$set) return false;
        $vals[]=$id;
        return $this->db->prepare("UPDATE medicines SET " . implode(',', $set) . " WHERE id = ?")->execute($vals);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM medicines WHERE id = ?")->execute([$id]);
    }

    public function countAvailable(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(DISTINCT m.id) FROM medicines m
             LEFT JOIN medicine_batches b ON b.medicine_id = m.id
             WHERE m.status='active'
             GROUP BY m.status HAVING COALESCE(SUM(b.quantity),0) > 0"
        )->fetchColumn() ?: 0;
    }

    public function countLowStock(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM (
                SELECT m.id, m.reorder_level, COALESCE(SUM(b.quantity),0) AS q
                FROM medicines m
                LEFT JOIN medicine_batches b ON b.medicine_id = m.id
                WHERE m.status='active'
                GROUP BY m.id
                HAVING q <= m.reorder_level
             ) t"
        )->fetchColumn();
    }

    public function countExpired(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM medicine_batches
             WHERE expiration_date < CURDATE() AND quantity > 0"
        )->fetchColumn();
    }

    public function lowStockList(): array
    {
        return $this->db->query(
            "SELECT m.*, COALESCE(SUM(b.quantity),0) AS total_stock
             FROM medicines m
             LEFT JOIN medicine_batches b ON b.medicine_id = m.id
             WHERE m.status='active'
             GROUP BY m.id
             HAVING total_stock <= m.reorder_level
             ORDER BY total_stock ASC"
        )->fetchAll();
    }

    public function expiredList(): array
    {
        return $this->db->query(
            "SELECT b.*, m.name, m.code, m.unit
             FROM medicine_batches b
             JOIN medicines m ON m.id = b.medicine_id
             WHERE b.expiration_date < CURDATE() AND b.quantity > 0
             ORDER BY b.expiration_date ASC"
        )->fetchAll();
    }

    public function nearExpiryList(int $days = 60): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, m.name, m.code, m.unit
             FROM medicine_batches b
             JOIN medicines m ON m.id = b.medicine_id
             WHERE b.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                   AND b.quantity > 0
             ORDER BY b.expiration_date ASC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}

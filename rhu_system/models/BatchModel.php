<?php
class BatchModel extends BaseModel
{
    public function forMedicine(int $medicineId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM medicine_batches
             WHERE medicine_id = ?
             ORDER BY expiration_date ASC"
        );
        $stmt->execute([$medicineId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query(
            "SELECT b.*, m.name, m.code, m.unit
             FROM medicine_batches b
             JOIN medicines m ON m.id = b.medicine_id
             ORDER BY b.expiration_date ASC"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, m.name, m.code, m.unit
             FROM medicine_batches b
             JOIN medicines m ON m.id = b.medicine_id
             WHERE b.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Create a new batch (stock-in) and log it. */
    public function stockIn(array $d, int $userId): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO medicine_batches
                 (medicine_id,batch_no,quantity,initial_qty,expiration_date,received_date,supplier,remarks,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $d['medicine_id'], $d['batch_no'],
                (int)$d['quantity'], (int)$d['quantity'],
                $d['expiration_date'], $d['received_date'],
                $d['supplier'] ?? null, $d['remarks'] ?? null,
                $userId,
            ]);
            $batchId = (int)$this->db->lastInsertId();

            $bal = $this->currentStock((int)$d['medicine_id']);
            $this->db->prepare(
                "INSERT INTO inventory_logs
                 (medicine_id,batch_id,action,quantity,balance_after,reference,remarks,performed_by)
                 VALUES (?,?,?,?,?,?,?,?)"
            )->execute([
                $d['medicine_id'], $batchId, 'stock_in',
                (int)$d['quantity'], $bal,
                $d['batch_no'], $d['remarks'] ?? null, $userId,
            ]);

            $this->db->commit();
            return $batchId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Manual stock-out (waste / adjustment) with reason. */
    public function stockOut(int $batchId, int $qty, string $reason, int $userId): void
    {
        $this->db->beginTransaction();
        try {
            $b = $this->find($batchId);
            if (!$b) throw new RuntimeException('Batch not found.');
            if ($qty <= 0 || $qty > (int)$b['quantity']) {
                throw new RuntimeException('Invalid quantity.');
            }
            $this->db->prepare(
                "UPDATE medicine_batches SET quantity = quantity - ? WHERE id = ?"
            )->execute([$qty, $batchId]);

            $bal = $this->currentStock((int)$b['medicine_id']);
            $this->db->prepare(
                "INSERT INTO inventory_logs
                 (medicine_id,batch_id,action,quantity,balance_after,reference,remarks,performed_by)
                 VALUES (?,?,?,?,?,?,?,?)"
            )->execute([
                $b['medicine_id'], $batchId, 'stock_out',
                $qty, $bal, $b['batch_no'], $reason, $userId,
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $d): bool
    {
        $cols = ['batch_no','quantity','expiration_date','received_date','supplier','remarks'];
        $set=[]; $vals=[];
        foreach ($cols as $c) if (array_key_exists($c,$d)) { $set[]="$c = ?"; $vals[]=$d[$c]; }
        if (!$set) return false;
        $vals[]=$id;
        return $this->db->prepare("UPDATE medicine_batches SET " . implode(',', $set) . " WHERE id = ?")->execute($vals);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM medicine_batches WHERE id = ?")->execute([$id]);
    }

    public function currentStock(int $medicineId): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(quantity),0) FROM medicine_batches WHERE medicine_id = ?");
        $stmt->execute([$medicineId]);
        return (int)$stmt->fetchColumn();
    }

    /** Return non-expired, positive-qty batches (FEFO). */
    public function availableBatches(int $medicineId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM medicine_batches
             WHERE medicine_id = ? AND quantity > 0 AND expiration_date >= CURDATE()
             ORDER BY expiration_date ASC"
        );
        $stmt->execute([$medicineId]);
        return $stmt->fetchAll();
    }
}

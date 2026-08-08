<?php
class DispensingModel extends BaseModel
{
    public function all(array $filters = []): array
    {
        $sql = "SELECT d.*, p.first_name, p.last_name, p.patient_code,
                       u.full_name AS dispensed_by_name
                FROM dispensing d
                JOIN patients p ON p.id = d.patient_id
                LEFT JOIN users u ON u.id = d.dispensed_by";
        $where = []; $params = [];
        if (!empty($filters['from'])) { $where[]="DATE(d.dispense_date) >= ?"; $params[]=$filters['from']; }
        if (!empty($filters['to']))   { $where[]="DATE(d.dispense_date) <= ?"; $params[]=$filters['to']; }
        if (!empty($filters['patient_id'])) { $where[]="d.patient_id = ?"; $params[]=$filters['patient_id']; }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY d.dispense_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, p.first_name, p.last_name, p.patient_code, p.contact_no,
                    u.full_name AS dispensed_by_name
             FROM dispensing d
             JOIN patients p ON p.id = d.patient_id
             LEFT JOIN users u ON u.id = d.dispensed_by
             WHERE d.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function items(int $dispensingId): array
    {
        $stmt = $this->db->prepare(
            "SELECT di.*, m.name AS medicine_name, m.code AS medicine_code, m.unit,
                    b.batch_no
             FROM dispensing_items di
             JOIN medicines m ON m.id = di.medicine_id
             LEFT JOIN medicine_batches b ON b.id = di.batch_id
             WHERE di.dispensing_id = ?"
        );
        $stmt->execute([$dispensingId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a dispensing transaction with automatic stock deduction
     * using FEFO (first-expiring first-out) across available batches.
     *
     * @param array $items each: ['medicine_id'=>x,'quantity'=>y,'dosage'=>'','instructions'=>'']
     */
    public function dispense(int $patientId, int $userId, array $items, ?string $notes = null): int
    {
        if (!$items) throw new RuntimeException('No medicines selected.');

        $this->db->beginTransaction();
        try {
            $receipt = next_receipt_no($this->db);
            $this->db->prepare(
                "INSERT INTO dispensing (receipt_no,patient_id,dispensed_by,notes,total_items)
                 VALUES (?,?,?,?,?)"
            )->execute([$receipt, $patientId, $userId, $notes, count($items)]);
            $dispensingId = (int)$this->db->lastInsertId();

            $batchModel = new BatchModel();

            foreach ($items as $it) {
                $medId = (int)$it['medicine_id'];
                $need  = (int)$it['quantity'];
                if ($need <= 0) throw new RuntimeException('Invalid quantity.');

                $available = $batchModel->availableBatches($medId);
                $totalAvail = array_sum(array_column($available, 'quantity'));
                if ($totalAvail < $need) {
                    throw new RuntimeException('Insufficient stock for medicine ID ' . $medId . ' (need ' . $need . ', have ' . $totalAvail . ').');
                }

                foreach ($available as $b) {
                    if ($need <= 0) break;
                    $take = min($need, (int)$b['quantity']);
                    // deduct from batch
                    $this->db->prepare(
                        "UPDATE medicine_batches SET quantity = quantity - ? WHERE id = ?"
                    )->execute([$take, $b['id']]);

                    // insert dispensing_items row
                    $this->db->prepare(
                        "INSERT INTO dispensing_items
                         (dispensing_id,medicine_id,batch_id,quantity,dosage,instructions)
                         VALUES (?,?,?,?,?,?)"
                    )->execute([
                        $dispensingId, $medId, $b['id'], $take,
                        $it['dosage'] ?? null, $it['instructions'] ?? null,
                    ]);

                    // inventory log
                    $bal = $batchModel->currentStock($medId);
                    $this->db->prepare(
                        "INSERT INTO inventory_logs
                         (medicine_id,batch_id,action,quantity,balance_after,reference,remarks,performed_by)
                         VALUES (?,?,?,?,?,?,?,?)"
                    )->execute([
                        $medId, $b['id'], 'dispense', $take, $bal,
                        $receipt, 'Dispensed to patient #' . $patientId, $userId,
                    ]);

                    $need -= $take;
                }
            }

            $this->db->commit();
            return $dispensingId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function countToday(): int
    {
        return (int)$this->db->query(
            "SELECT COALESCE(SUM(total_items),0) FROM dispensing WHERE DATE(dispense_date) = CURDATE()"
        )->fetchColumn();
    }

    public function monthlySeries(): array
    {
        return $this->db->query(
            "SELECT DATE_FORMAT(dispense_date,'%Y-%m') AS m, COUNT(*) AS c, COALESCE(SUM(total_items),0) AS q
             FROM dispensing
             WHERE dispense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY m ORDER BY m"
        )->fetchAll();
    }
}

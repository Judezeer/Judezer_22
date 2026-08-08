<?php
class HealthRecordModel extends BaseModel
{
    public function forPatient(int $patientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT hr.*, u.full_name AS attended_by_name
             FROM health_records hr
             LEFT JOIN users u ON u.id = hr.attended_by
             WHERE hr.patient_id = ?
             ORDER BY hr.visit_date DESC, hr.id DESC"
        );
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query(
            "SELECT hr.*, p.first_name, p.last_name, p.patient_code,
                    u.full_name AS attended_by_name
             FROM health_records hr
             JOIN patients p ON p.id = hr.patient_id
             LEFT JOIN users u ON u.id = hr.attended_by
             ORDER BY hr.visit_date DESC"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT hr.*, p.first_name, p.last_name, p.patient_code
             FROM health_records hr
             JOIN patients p ON p.id = hr.patient_id
             WHERE hr.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO health_records
             (patient_id,appointment_id,record_type,visit_date,bp,temperature,pulse,
              weight,height,chief_complaint,diagnosis,treatment,prescription,vaccine,
              remarks,attended_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['patient_id'], $d['appointment_id'] ?? null,
            $d['record_type'] ?? 'consultation', $d['visit_date'],
            $d['bp'] ?? null, $d['temperature'] ?? null, $d['pulse'] ?? null,
            $d['weight'] ?? null, $d['height'] ?? null,
            $d['chief_complaint'] ?? null, $d['diagnosis'] ?? null,
            $d['treatment'] ?? null, $d['prescription'] ?? null,
            $d['vaccine'] ?? null, $d['remarks'] ?? null,
            $d['attended_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $cols = ['record_type','visit_date','bp','temperature','pulse','weight','height',
                 'chief_complaint','diagnosis','treatment','prescription','vaccine','remarks'];
        $set=[]; $vals=[];
        foreach ($cols as $c) if (array_key_exists($c,$d)) { $set[]="$c = ?"; $vals[]=$d[$c]; }
        if (!$set) return false;
        $vals[]=$id;
        return $this->db->prepare(
            "UPDATE health_records SET " . implode(',', $set) . " WHERE id = ?"
        )->execute($vals);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM health_records WHERE id = ?")->execute([$id]);
    }
}

<?php
class AppointmentModel extends BaseModel
{
    public function all(array $filters = []): array
    {
        $sql = "SELECT a.*, p.first_name, p.last_name, p.patient_code
                FROM appointments a
                JOIN patients p ON p.id = a.patient_id";
        $where = []; $params = [];
        if (!empty($filters['status'])) { $where[] = "a.status = ?"; $params[] = $filters['status']; }
        if (!empty($filters['date']))   { $where[] = "a.appointment_date = ?"; $params[] = $filters['date']; }
        if (!empty($filters['patient_id'])) { $where[] = "a.patient_id = ?"; $params[] = $filters['patient_id']; }
        if (!empty($filters['from']))   { $where[] = "a.appointment_date >= ?"; $params[] = $filters['from']; }
        if (!empty($filters['to']))     { $where[] = "a.appointment_date <= ?"; $params[] = $filters['to']; }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, p.first_name, p.last_name, p.patient_code, p.contact_no
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             WHERE a.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO appointments
             (patient_id,appointment_date,appointment_time,purpose,notes,status,created_by)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['patient_id'], $d['appointment_date'], $d['appointment_time'],
            $d['purpose'], $d['notes'] ?? null,
            $d['status'] ?? 'pending',
            $d['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function setStatus(int $id, string $status, ?int $handledBy = null): bool
    {
        return $this->db->prepare(
            "UPDATE appointments SET status = ?, handled_by = ? WHERE id = ?"
        )->execute([$status, $handledBy, $id]);
    }

    public function update(int $id, array $d): bool
    {
        $cols = ['appointment_date','appointment_time','purpose','notes','status'];
        $set=[]; $vals=[];
        foreach ($cols as $c) if (array_key_exists($c,$d)) { $set[]="$c = ?"; $vals[]=$d[$c]; }
        if (!$set) return false;
        $vals[]=$id;
        return $this->db->prepare("UPDATE appointments SET " . implode(',', $set) . " WHERE id = ?")->execute($vals);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM appointments WHERE id = ?")->execute([$id]);
    }

    public function countToday(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()"
        )->fetchColumn();
    }

    public function countByStatus(): array
    {
        $rows = $this->db->query(
            "SELECT status, COUNT(*) AS c FROM appointments GROUP BY status"
        )->fetchAll();
        $out = [];
        foreach ($rows as $r) $out[$r['status']] = (int)$r['c'];
        return $out;
    }

    public function monthlySeries(): array
    {
        return $this->db->query(
            "SELECT DATE_FORMAT(appointment_date,'%Y-%m') AS m, COUNT(*) AS c
             FROM appointments
             WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY m ORDER BY m"
        )->fetchAll();
    }

    public function upcomingForPatient(int $patientId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM appointments
             WHERE patient_id = ? AND appointment_date >= CURDATE()
                   AND status IN ('pending','approved','rescheduled')
             ORDER BY appointment_date, appointment_time
             LIMIT " . (int)$limit
        );
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Check for scheduling conflicts.
     * Returns an array of conflict descriptions (empty if OK).
     *
     * Rules:
     *  - Any appointment within ±$windowMinutes of the requested time on the
     *    same date is considered a conflict (overlap).
     *  - Same patient booking twice on the same date is always a conflict
     *    (even different times), unless the existing one is cancelled/rejected.
     *  - Cancelled and rejected appointments are ignored.
     *  - If $excludeId is provided (edit mode), that appointment is skipped.
     *
     * @return array<int, array{type:string,message:string,appointment:array}>
     */
    public function findConflicts(
        int $patientId,
        string $date,
        string $time,
        ?int $excludeId = null,
        int $windowMinutes = 15
    ): array {
        $conflicts = [];
        $excludeId = $excludeId ?? 0;

        // 1) Same patient, same day
        $stmt = $this->db->prepare(
            "SELECT a.id, a.appointment_date, a.appointment_time, a.purpose, a.status
             FROM appointments a
             WHERE a.patient_id = ?
               AND a.appointment_date = ?
               AND a.id <> ?
               AND a.status NOT IN ('cancelled','rejected')
             LIMIT 5"
        );
        $stmt->execute([$patientId, $date, $excludeId]);
        foreach ($stmt->fetchAll() as $row) {
            $conflicts[] = [
                'type'    => 'same_patient_same_day',
                'message' => 'This patient already has an appointment on '
                          .  date('M d, Y', strtotime($date))
                          .  ' at ' . date('g:i A', strtotime($row['appointment_time']))
                          .  ' (' . ucfirst($row['status']) . ')',
                'appointment' => $row,
            ];
        }

        // 2) Time-slot overlap (any patient) — ±window minutes
        $stmt = $this->db->prepare(
            "SELECT a.id, a.appointment_time, a.purpose, a.status,
                    p.first_name, p.last_name, p.patient_code
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             WHERE a.appointment_date = ?
               AND a.id <> ?
               AND a.status NOT IN ('cancelled','rejected')
               AND ABS(TIMESTAMPDIFF(MINUTE,
                     CONCAT(?, ' ', ?),
                     CONCAT(a.appointment_date, ' ', a.appointment_time))) < ?
             LIMIT 5"
        );
        $stmt->execute([$date, $excludeId, $date, $time, $windowMinutes]);
        foreach ($stmt->fetchAll() as $row) {
            $conflicts[] = [
                'type'    => 'time_overlap',
                'message' => 'Time-slot overlaps with '
                          .  $row['first_name'] . ' ' . $row['last_name']
                          .  ' (' . $row['patient_code'] . ') at '
                          .  date('g:i A', strtotime($row['appointment_time']))
                          .  ' — ' . $row['purpose']
                          .  ' (' . ucfirst($row['status']) . ')',
                'appointment' => $row,
            ];
        }

        return $conflicts;
    }
}

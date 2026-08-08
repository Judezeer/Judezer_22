<?php
class PatientModel extends BaseModel
{
    public function all(?string $search = null): array
    {
        $sql = "SELECT * FROM patients";
        $params = [];
        if ($search) {
            $sql .= " WHERE first_name LIKE ? OR last_name LIKE ? OR patient_code LIKE ? OR barangay LIKE ?";
            $s = "%$search%";
            $params = [$s,$s,$s,$s];
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM patients WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO patients
             (user_id,patient_code,first_name,middle_name,last_name,sex,birthdate,
              civil_status,contact_no,email,address,barangay,blood_type,allergies,
              philhealth_no,emergency_name,emergency_no,photo,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['user_id'] ?? null,
            $d['patient_code'],
            $d['first_name'], $d['middle_name'] ?? null, $d['last_name'],
            $d['sex'], $d['birthdate'],
            $d['civil_status'] ?? 'single',
            $d['contact_no'] ?? null, $d['email'] ?? null,
            $d['address'], $d['barangay'] ?? null,
            $d['blood_type'] ?? null, $d['allergies'] ?? null,
            $d['philhealth_no'] ?? null,
            $d['emergency_name'] ?? null, $d['emergency_no'] ?? null,
            $d['photo'] ?? null,
            $d['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $cols = ['first_name','middle_name','last_name','sex','birthdate','civil_status',
                 'contact_no','email','address','barangay','blood_type','allergies',
                 'philhealth_no','emergency_name','emergency_no','photo'];
        $set = []; $vals = [];
        foreach ($cols as $c) {
            if (array_key_exists($c, $d)) { $set[] = "$c = ?"; $vals[] = $d[$c]; }
        }
        if (!$set) return false;
        $vals[] = $id;
        return $this->db->prepare(
            "UPDATE patients SET " . implode(',', $set) . " WHERE id = ?"
        )->execute($vals);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM patients WHERE id = ?")->execute([$id]);
    }

    public function count(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    }

    /** Monthly new-patients series for the last 12 months. */
    public function monthlySeries(): array
    {
        $rows = $this->db->query(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, COUNT(*) AS c
             FROM patients
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY m ORDER BY m"
        )->fetchAll();
        return $rows;
    }
}

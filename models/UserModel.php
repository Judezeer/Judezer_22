<?php
class UserModel extends BaseModel
{
    public function findByUsernameOrEmail(string $u): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1"
        );
        $stmt->execute([$u, $u]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function all(?string $role = null): array
    {
        if ($role) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
            $stmt->execute([$role]);
        } else {
            $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        }
        return $stmt->fetchAll();
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username,email,password,full_name,role,phone,status)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['username'], $d['email'],
            password_hash($d['password'], PASSWORD_DEFAULT),
            $d['full_name'], $d['role'],
            $d['phone'] ?? null,
            $d['status'] ?? 'active',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $fields = ['username','email','full_name','role','phone','status'];
        $set = []; $vals = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $d)) { $set[] = "$f = ?"; $vals[] = $d[$f]; }
        }
        if (!empty($d['password'])) {
            $set[] = "password = ?";
            $vals[] = password_hash($d['password'], PASSWORD_DEFAULT);
        }
        if (!$set) return false;
        $vals[] = $id;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(',', $set) . " WHERE id = ?");
        return $stmt->execute($vals);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function touchLogin(int $id): void
    {
        $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$id]);
    }

    public function countByRole(): array
    {
        $rows = $this->db->query(
            "SELECT role, COUNT(*) AS c FROM users GROUP BY role"
        )->fetchAll();
        $out = ['admin'=>0,'nurse'=>0,'pharmacist'=>0,'patient'=>0];
        foreach ($rows as $r) $out[$r['role']] = (int)$r['c'];
        return $out;
    }
}

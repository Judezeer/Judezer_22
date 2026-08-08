<?php
class SettingModel extends BaseModel
{
    public function all(): array
    {
        $rows = $this->db->query("SELECT skey,svalue FROM settings")->fetchAll();
        $out = [];
        foreach ($rows as $r) $out[$r['skey']] = $r['svalue'];
        return $out;
    }

    public function set(string $key, ?string $value): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (skey,svalue) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)"
        );
        $stmt->execute([$key, $value]);
    }
}

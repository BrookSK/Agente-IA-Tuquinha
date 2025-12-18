<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class MenuIcon
{
    public static function allAssoc(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT `key`, label, dark_path, light_path FROM menu_icons');
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        $map = [];
        foreach ($rows as $r) {
            $k = (string)($r['key'] ?? '');
            if ($k === '') {
                continue;
            }
            $map[$k] = [
                'label' => (string)($r['label'] ?? $k),
                'dark_path' => isset($r['dark_path']) ? (string)$r['dark_path'] : null,
                'light_path' => isset($r['light_path']) ? (string)$r['light_path'] : null,
            ];
        }
        return $map;
    }

    public static function upsert(string $key, string $label, ?string $darkPath, ?string $lightPath): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO menu_icons (`key`, label, dark_path, light_path)
            VALUES (:k, :label, :dark, :light)
            ON DUPLICATE KEY UPDATE label = VALUES(label), dark_path = VALUES(dark_path), light_path = VALUES(light_path)');
        $stmt->execute([
            'k' => $key,
            'label' => $label,
            'dark' => $darkPath,
            'light' => $lightPath,
        ]);
    }
}

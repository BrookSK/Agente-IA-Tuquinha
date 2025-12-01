<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Setting
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT `value` FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && array_key_exists('value', $row)) {
            return (string)$row['value'];
        }
        return $default;
    }
}

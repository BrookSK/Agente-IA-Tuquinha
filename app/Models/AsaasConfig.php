<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AsaasConfig
{
    public static function getActive(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM asaas_configs ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

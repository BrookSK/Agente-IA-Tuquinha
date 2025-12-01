<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            global $currentDbConfig;

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $currentDbConfig['host'],
                $currentDbConfig['port'],
                $currentDbConfig['database'],
                $currentDbConfig['charset']
            );

            try {
                self::$connection = new PDO($dsn, $currentDbConfig['username'], $currentDbConfig['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo 'Erro de conexão com o banco de dados.';
                exit;
            }
        }

        return self::$connection;
    }
}

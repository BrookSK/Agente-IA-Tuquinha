<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

/**
 * Rota pública para rodar todas as migrations pendentes.
 *
 * Acesse: GET /migrate/run?key=SUA_CHAVE_SECRETA
 */
class MigrateController
{
    public function run(): void
    {
        $secretKey = defined('MIGRATE_SECRET_KEY') ? MIGRATE_SECRET_KEY : 'tuq-migrate-2026';
        $providedKey = trim((string)($_GET['key'] ?? ''));

        if ($providedKey === '' || $providedKey !== $secretKey) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Acesso negado. Passe ?key=SUA_CHAVE na URL.\n";
            return;
        }

        set_time_limit(300);
        header('Content-Type: text/plain; charset=utf-8');

        $pdo = $this->freshPdo();

        $pdo->exec('CREATE TABLE IF NOT EXISTS _migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_filename (filename)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $stmt = $pdo->query('SELECT filename FROM _migrations');
        $executed = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $executed[$row['filename']] = true;
        }
        $stmt->closeCursor();

        $migrationsDir = __DIR__ . '/../../database/migrations';
        if (!is_dir($migrationsDir)) {
            echo "Pasta de migrations não encontrada.\n";
            return;
        }

        $files = [];
        foreach (scandir($migrationsDir) as $f) {
            if (substr($f, -4) === '.sql') {
                $files[] = $f;
            }
        }
        sort($files, SORT_NATURAL);

        if (empty($files)) {
            echo "Nenhum arquivo de migration encontrado.\n";
            return;
        }

        // Schema base
        $schemaFile = __DIR__ . '/../../database/schema.sql';
        if (is_file($schemaFile) && empty($executed['schema.sql'])) {
            echo "=== schema.sql ===\n";
            $sql = file_get_contents($schemaFile);
            if ($sql !== false && trim($sql) !== '') {
                $this->runSingleMigration('schema.sql', $sql);
            }
        }

        $ran = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($files as $filename) {
            if (!empty($executed[$filename])) {
                $skipped++;
                continue;
            }

            $sql = file_get_contents($migrationsDir . '/' . $filename);
            if ($sql === false || trim($sql) === '') {
                $skipped++;
                continue;
            }

            echo "=== $filename ===\n";
            $result = $this->runSingleMigration($filename, $sql);
            if ($result) {
                $ran++;
            } else {
                $errors++;
            }
        }

        echo "---\n";
        echo "Concluído. Executadas: $ran | Erros: $errors | Já aplicadas: $skipped\n";
        echo "Total de arquivos: " . count($files) . "\n";
    }

    private function runSingleMigration(string $filename, string $sql): bool
    {
        try {
            $pdo = $this->freshPdo();
        } catch (\Throwable $e) {
            echo "ERRO $filename: Sem conexão — " . $e->getMessage() . "\n\n";
            return false;
        }

        $statements = $this->splitStatements($sql);
        $hadError = false;

        foreach ($statements as $stmt_sql) {
            $stmt_sql = trim($stmt_sql);
            if ($stmt_sql === '') {
                continue;
            }

            try {
                $pdo->exec($stmt_sql);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();

                $isIgnorable =
                    stripos($msg, 'Duplicate column') !== false ||
                    stripos($msg, 'Duplicate key name') !== false ||
                    stripos($msg, 'already exists') !== false ||
                    stripos($msg, "Can't DROP") !== false ||
                    stripos($msg, 'check that column/key exists') !== false ||
                    stripos($msg, 'check that it exists') !== false;

                if ($isIgnorable) {
                    $short = strlen($msg) > 120 ? substr($msg, 0, 120) . '...' : $msg;
                    echo "AVISO $filename: (ignorável) $short\n";
                } else {
                    echo "ERRO $filename: $msg\n\n";
                    $hadError = true;
                    // Continua tentando os outros statements do mesmo arquivo
                }
            }
        }

        // Marca como executada
        try {
            $pdo2 = $this->freshPdo();
            $ins = $pdo2->prepare('INSERT IGNORE INTO _migrations (filename) VALUES (:f)');
            $ins->execute(['f' => $filename]);
        } catch (\Throwable $e) {
            // Não fatal
        }

        if (!$hadError) {
            echo "OK: $filename\n\n";
        }

        return !$hadError;
    }

    private function freshPdo(): PDO
    {
        global $currentDbConfig;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $currentDbConfig['host'],
            $currentDbConfig['port'],
            $currentDbConfig['database'],
            $currentDbConfig['charset']
        );

        return new PDO($dsn, $currentDbConfig['username'], $currentDbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
    }

    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if (!$inString && ($char === "'" || $char === '"')) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
                continue;
            }

            if ($inString) {
                $current .= $char;
                if ($char === $stringChar) {
                    if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                        $current .= $sql[$i + 1];
                        $i++;
                    } else {
                        $inString = false;
                    }
                }
                continue;
            }

            if ($char === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                $end = strpos($sql, "\n", $i);
                if ($end === false) { break; }
                $i = $end;
                $current .= "\n";
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}

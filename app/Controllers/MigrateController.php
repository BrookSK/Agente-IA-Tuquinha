<?php

namespace App\Controllers;

use PDO;

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
        $pdo = null;

        $migrationsDir = __DIR__ . '/../../database/migrations';
        if (!is_dir($migrationsDir)) {
            echo "Pasta de migrations não encontrada.\n";
            return;
        }

        $files = [];
        foreach (scandir($migrationsDir) as $f) {
            if (substr($f, -4) === '.sql') { $files[] = $f; }
        }
        sort($files, SORT_NATURAL);

        $schemaFile = __DIR__ . '/../../database/schema.sql';
        if (is_file($schemaFile) && empty($executed['schema.sql'])) {
            echo "=== schema.sql ===\n";
            $this->runSingle('schema.sql', file_get_contents($schemaFile));
        }

        $ran = 0; $errors = 0; $skipped = 0;
        foreach ($files as $f) {
            if (!empty($executed[$f])) { $skipped++; continue; }
            $sql = file_get_contents($migrationsDir . '/' . $f);
            if (!$sql || !trim($sql)) { $skipped++; continue; }
            echo "=== $f ===\n";
            $this->runSingle($f, $sql) ? $ran++ : $errors++;
        }

        echo "---\nConcluído. Executadas: $ran | Erros: $errors | Já aplicadas: $skipped\n";
    }

    private function runSingle(string $filename, string $sql): bool
    {
        // Tenta executar tudo de uma vez (necessário para PREPARE/EXECUTE)
        try {
            $pdo = $this->freshPdo();
            $pdo->exec($sql);
            $this->mark($filename);
            echo "OK: $filename\n\n";
            return true;
        } catch (\Throwable $e) {
            if ($this->isIgnorable($e->getMessage())) {
                $short = strlen($e->getMessage()) > 120 ? substr($e->getMessage(), 0, 120) . '...' : $e->getMessage();
                echo "AVISO (ignorável): $short\n";
                $this->mark($filename);
                echo "OK: $filename\n\n";
                return true;
            }
        }

        // Fallback: statement por statement
        try { $pdo = $this->freshPdo(); } catch (\Throwable $e) {
            echo "ERRO: Sem conexão\n\n";
            return false;
        }

        $hadFatal = false;
        foreach ($this->splitStatements($sql) as $s) {
            $s = trim($s);
            if ($s === '') continue;
            try {
                $pdo->exec($s);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if ($this->isIgnorable($msg)) {
                    $short = strlen($msg) > 120 ? substr($msg, 0, 120) . '...' : $msg;
                    echo "AVISO (ignorável): $short\n";
                } else {
                    echo "ERRO: $msg\n";
                    $hadFatal = true;
                }
                try { $pdo = $this->freshPdo(); } catch (\Throwable $re) {
                    echo "ERRO: Falha ao reconectar\n\n";
                    return false;
                }
            }
        }

        $this->mark($filename);
        echo($hadFatal ? "\n" : "OK: $filename\n\n");
        return !$hadFatal;
    }

    private function mark(string $filename): void
    {
        try {
            $p = $this->freshPdo();
            $p->prepare('INSERT IGNORE INTO _migrations (filename) VALUES (:f)')->execute(['f' => $filename]);
        } catch (\Throwable $e) {}
    }

    private function isIgnorable(string $msg): bool
    {
        return stripos($msg, 'Duplicate column') !== false ||
            stripos($msg, 'Duplicate key name') !== false ||
            stripos($msg, 'already exists') !== false ||
            stripos($msg, "Can't DROP") !== false ||
            stripos($msg, 'check that column/key exists') !== false ||
            stripos($msg, 'Referencing column') !== false ||
            stripos($msg, 'incompatible') !== false ||
            stripos($msg, 'Failed to open the referenced table') !== false ||
            stripos($msg, 'errno: 150') !== false ||
            stripos($msg, 'errno 150') !== false;
    }

    private function freshPdo(): PDO
    {
        global $currentDbConfig;
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $currentDbConfig['host'], $currentDbConfig['port'],
            $currentDbConfig['database'], $currentDbConfig['charset']);
        return new PDO($dsn, $currentDbConfig['username'], $currentDbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
    }

    private function splitStatements(string $sql): array
    {
        $stmts = []; $cur = ''; $inStr = false; $sc = ''; $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if (!$inStr && ($c === "'" || $c === '"')) { $inStr = true; $sc = $c; $cur .= $c; continue; }
            if ($inStr) { $cur .= $c; if ($c === $sc) { if ($i+1 < $len && $sql[$i+1] === $sc) { $cur .= $sql[++$i]; } else { $inStr = false; } } continue; }
            if ($c === '-' && $i+1 < $len && $sql[$i+1] === '-') { $end = strpos($sql, "\n", $i); if ($end === false) break; $i = $end; $cur .= "\n"; continue; }
            if ($c === ';') { $t = trim($cur); if ($t !== '') $stmts[] = $t; $cur = ''; continue; }
            $cur .= $c;
        }
        $t = trim($cur); if ($t !== '') $stmts[] = $t;
        return $stmts;
    }
}

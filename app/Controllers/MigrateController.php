<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

/**
 * Rota pública para rodar todas as migrations pendentes.
 *
 * Acesse: GET /migrate/run?key=SUA_CHAVE_SECRETA
 *
 * A chave é definida no config.php como MIGRATE_SECRET_KEY
 * ou, se não existir, usa uma chave padrão (troque em produção!).
 */
class MigrateController
{
    public function run(): void
    {
        // Chave de segurança simples pra não deixar qualquer um rodar
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

        $pdo = Database::getConnection();

        // 1. Garante que a tabela de controle existe
        $pdo->exec('CREATE TABLE IF NOT EXISTS _migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_filename (filename)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // 2. Busca quais já foram executadas
        $stmt = $pdo->query('SELECT filename FROM _migrations');
        $executed = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $executed[$row['filename']] = true;
        }

        // 3. Lista todos os arquivos .sql na pasta de migrations
        $migrationsDir = __DIR__ . '/../../database/migrations';
        if (!is_dir($migrationsDir)) {
            echo "Pasta de migrations não encontrada: $migrationsDir\n";
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

        // 4. Roda o schema.sql primeiro se nunca foi executado
        $schemaFile = __DIR__ . '/../../database/schema.sql';
        if (is_file($schemaFile) && empty($executed['schema.sql'])) {
            echo "=== Executando schema.sql ===\n";
            $sql = file_get_contents($schemaFile);
            if ($sql !== false && trim($sql) !== '') {
                try {
                    $pdo->exec($sql);
                    $ins = $pdo->prepare('INSERT IGNORE INTO _migrations (filename) VALUES (:f)');
                    $ins->execute(['f' => 'schema.sql']);
                    echo "OK: schema.sql\n\n";
                } catch (\Throwable $e) {
                    echo "ERRO em schema.sql: " . $e->getMessage() . "\n\n";
                }
            }
        }

        // 5. Executa cada migration pendente em ordem
        $ran = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($files as $filename) {
            if (!empty($executed[$filename])) {
                $skipped++;
                continue;
            }

            $filePath = $migrationsDir . '/' . $filename;
            $sql = file_get_contents($filePath);
            if ($sql === false || trim($sql) === '') {
                echo "VAZIO: $filename (pulando)\n";
                $skipped++;
                continue;
            }

            echo "=== Executando $filename ===\n";

            try {
                // Divide por statements pra lidar com múltiplos comandos
                $statements = $this->splitStatements($sql);
                foreach ($statements as $stmt_sql) {
                    $stmt_sql = trim($stmt_sql);
                    if ($stmt_sql === '') {
                        continue;
                    }
                    $pdo->exec($stmt_sql);
                }

                // Marca como executada
                $ins = $pdo->prepare('INSERT IGNORE INTO _migrations (filename) VALUES (:f)');
                $ins->execute(['f' => $filename]);

                echo "OK: $filename\n\n";
                $ran++;
            } catch (\Throwable $e) {
                echo "ERRO em $filename: " . $e->getMessage() . "\n\n";
                $errors++;
                // Continua com as próximas migrations
            }
        }

        echo "---\n";
        echo "Concluído. Executadas: $ran | Erros: $errors | Já aplicadas: $skipped\n";
        echo "Total de arquivos: " . count($files) . "\n";
    }

    /**
     * Divide o SQL em statements individuais, respeitando strings e delimiters.
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            // Detecta início/fim de string
            if (!$inString && ($char === "'" || $char === '"')) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
                continue;
            }

            if ($inString) {
                $current .= $char;
                if ($char === $stringChar) {
                    // Verifica se é escape (\' ou '')
                    if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                        $current .= $sql[$i + 1];
                        $i++;
                    } else {
                        $inString = false;
                    }
                }
                continue;
            }

            // Detecta comentário de linha (-- )
            if ($char === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                $end = strpos($sql, "\n", $i);
                if ($end === false) {
                    break;
                }
                $i = $end;
                $current .= "\n";
                continue;
            }

            // Semicolon fora de string = fim do statement
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

        // Último statement sem semicolon
        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}

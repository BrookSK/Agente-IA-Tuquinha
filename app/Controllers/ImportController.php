<?php

namespace App\Controllers;

use PDO;

/**
 * Importa arquivos SQL grandes da pasta database/imports/
 *
 * Acesse: GET /import/run?key=SUA_CHAVE
 *
 * Usa output buffering com flush progressivo pra evitar timeout.
 * Cada statement é executado individualmente e o progresso é
 * enviado pro navegador em tempo real.
 */
class ImportController
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

        // Sem limite de tempo e memória generosa
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '512M');

        // Headers pra streaming progressivo
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Accel-Buffering: no'); // Nginx
        header('Cache-Control: no-cache');

        // Desliga output buffering do PHP pra flush funcionar
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ob_implicit_flush(true);

        $this->out("╔══════════════════════════════════════════╗");
        $this->out("║       IMPORTAÇÃO DE DADOS SQL            ║");
        $this->out("╚══════════════════════════════════════════╝");
        $this->out("");

        $importsDir = __DIR__ . '/../../database/imports';
        if (!is_dir($importsDir)) {
            $this->out("❌ Pasta database/imports/ não encontrada.");
            return;
        }

        // Lista arquivos .sql em ordem
        $files = [];
        foreach (scandir($importsDir) as $f) {
            if ($f === '.gitkeep' || $f[0] === '.') {
                continue;
            }
            if (substr($f, -4) === '.sql') {
                $files[] = $f;
            }
        }
        sort($files, SORT_NATURAL);

        if (empty($files)) {
            $this->out("ℹ️  Nenhum arquivo .sql encontrado em database/imports/");
            $this->out("   Coloque seus arquivos de dump lá e acesse esta URL novamente.");
            return;
        }

        $this->out("Encontrados " . count($files) . " arquivo(s) para importar:");
        foreach ($files as $f) {
            $size = filesize($importsDir . '/' . $f);
            $this->out("  • $f (" . $this->formatSize($size) . ")");
        }
        $this->out("");

        $totalOk = 0;
        $totalErrors = 0;
        $totalSkipped = 0;

        foreach ($files as $filename) {
            $filePath = $importsDir . '/' . $filename;
            $fileSize = filesize($filePath);

            $this->out("══════════════════════════════════════════");
            $this->out("📄 $filename (" . $this->formatSize($fileSize) . ")");
            $this->out("──────────────────────────────────────────");

            $result = $this->importFile($filePath, $filename);
            $totalOk += $result['ok'];
            $totalErrors += $result['errors'];
            $totalSkipped += $result['skipped'];

            $this->out("");
            $this->out("  Resultado: ✅ {$result['ok']} | ❌ {$result['errors']} | ⏭ {$result['skipped']}");
            $this->out("");
        }

        $this->out("══════════════════════════════════════════");
        $this->out("TOTAL GERAL");
        $this->out("  ✅ Statements OK: $totalOk");
        $this->out("  ❌ Erros: $totalErrors");
        $this->out("  ⏭ Ignorados: $totalSkipped");
        $this->out("══════════════════════════════════════════");

        if ($totalErrors === 0) {
            $this->out("\n🎉 Importação concluída com sucesso!");
        } else {
            $this->out("\n⚠️  Importação concluída com $totalErrors erro(s).");
        }
    }

    private function importFile(string $filePath, string $filename): array
    {
        $ok = 0;
        $errors = 0;
        $skipped = 0;
        $stmtCount = 0;

        // Lê o arquivo linha por linha pra não estourar memória
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $perms = is_file($filePath) ? substr(sprintf('%o', fileperms($filePath)), -4) : 'arquivo não existe';
            $readable = is_readable($filePath) ? 'sim' : 'não';
            $this->out("  ❌ Não foi possível abrir o arquivo.");
            $this->out("     Caminho: $filePath");
            $this->out("     Permissões: $perms | Legível: $readable");
            if (!is_readable($filePath)) {
                @chmod($filePath, 0644);
                $handle = fopen($filePath, 'r');
                if ($handle) {
                    $this->out("  ✅ Permissão corrigida automaticamente, continuando...");
                } else {
                    $this->out("     Execute: chmod 644 " . escapeshellarg($filePath));
                    return ['ok' => 0, 'errors' => 1, 'skipped' => 0];
                }
            } else {
                return ['ok' => 0, 'errors' => 1, 'skipped' => 0];
            }
        }

        $pdo = $this->freshPdo();

        // Desabilita checks pra importação mais rápida
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $pdo->exec('SET UNIQUE_CHECKS = 0');
            $pdo->exec("SET SESSION sql_mode = ''");
        } catch (\Throwable $e) {
        }

        $currentStmt = '';
        $inString = false;
        $stringChar = '';
        $lineNum = 0;
        $batchStart = microtime(true);

        while (($line = fgets($handle)) !== false) {
            $lineNum++;

            // Pula comentários e linhas vazias (fora de strings)
            if (!$inString) {
                $trimmed = ltrim($line);
                if ($trimmed === '' || $trimmed === "\n" || $trimmed === "\r\n") {
                    continue;
                }
                if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '/*')) {
                    // Comentário de bloco simples numa linha
                    if (str_starts_with($trimmed, '/*') && strpos($trimmed, '*/') !== false) {
                        continue;
                    }
                    if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                        continue;
                    }
                }
            }

            // Acumula a linha no statement atual
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $char = $line[$i];

                if (!$inString && ($char === "'" || $char === '"')) {
                    $inString = true;
                    $stringChar = $char;
                    $currentStmt .= $char;
                    continue;
                }

                if ($inString) {
                    $currentStmt .= $char;
                    if ($char === '\\') {
                        // Caractere de escape: pula o próximo caractere
                        if ($i + 1 < $len) {
                            $currentStmt .= $line[$i + 1];
                            $i++;
                        }
                        continue;
                    }
                    if ($char === $stringChar) {
                        // Verifica se é escape por duplicação: '' ou ""
                        if ($i + 1 < $len && $line[$i + 1] === $stringChar) {
                            $currentStmt .= $line[$i + 1];
                            $i++;
                        } else {
                            $inString = false;
                        }
                    }
                    continue;
                }

                if ($char === ';') {
                    $stmt = trim($currentStmt);
                    $currentStmt = '';

                    if ($stmt === '') {
                        continue;
                    }

                    $stmtCount++;

                    // Pula statements que não são dados (SET, LOCK, UNLOCK, etc.)
                    // ou statements órfãos (continuação de INSERT truncado)
                    $upper = strtoupper(substr($stmt, 0, 10));
                    if (str_starts_with($upper, 'LOCK TABLE') || str_starts_with($upper, 'UNLOCK TAB')) {
                        $skipped++;
                        continue;
                    }
                    // Statement que começa com vírgula = continuação órfã de INSERT partido
                    if ($stmt[0] === ',') {
                        $skipped++;
                        continue;
                    }

                    try {
                        // Troca INSERT por REPLACE pra sobrescrever dados existentes
                        $upper = strtoupper(substr($stmt, 0, 12));
                        if (str_starts_with($upper, 'INSERT INTO ')) {
                            $stmt = 'REPLACE INTO ' . substr($stmt, 12);
                        } elseif (str_starts_with($upper, 'INSERT INTO`')) {
                            $stmt = 'REPLACE INTO`' . substr($stmt, 12);
                        }

                        $pdo->exec($stmt);
                        $ok++;
                    } catch (\Throwable $e) {
                        $msg = $e->getMessage();

                        $isIgnorable =
                            stripos($msg, 'Duplicate entry') !== false ||
                            stripos($msg, 'already exists') !== false ||
                            stripos($msg, 'Duplicate column') !== false;

                        if ($isIgnorable) {
                            $skipped++;
                        } else {
                            $errors++;
                            $short = strlen($msg) > 300 ? substr($msg, 0, 300) . '...' : $msg;
                            $stmtPreview = strlen($stmt) > 200 ? substr($stmt, 0, 200) . '...' : $stmt;
                            $this->out("  ❌ Linha ~$lineNum: $short");
                            $this->out("     SQL: $stmtPreview");
                        }

                        // Reconecta se necessário
                        try {
                            $pdo = $this->freshPdo();
                            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                            $pdo->exec('SET UNIQUE_CHECKS = 0');
                        } catch (\Throwable $re) {
                        }
                    }

                    // Progresso a cada 500 statements
                    if ($stmtCount % 500 === 0) {
                        $elapsed = round(microtime(true) - $batchStart, 1);
                        $this->out("  ⏳ $stmtCount statements processados ({$elapsed}s) — ✅ $ok | ❌ $errors | ⏭ $skipped");
                    }

                    continue;
                }

                $currentStmt .= $char;
            }
        }

        // Statement final sem ;
        $stmt = trim($currentStmt);
        if ($stmt !== '') {
            $stmtCount++;
            try {
                $pdo->exec($stmt);
                $ok++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        fclose($handle);

        // Reabilita checks
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            $pdo->exec('SET UNIQUE_CHECKS = 1');
        } catch (\Throwable $e) {
        }

        $elapsed = round(microtime(true) - $batchStart, 1);
        $this->out("  ✅ Concluído em {$elapsed}s — $stmtCount statements processados");

        return ['ok' => $ok, 'errors' => $errors, 'skipped' => $skipped];
    }

    private function out(string $text): void
    {
        echo $text . "\n";
        if (function_exists('flush')) {
            flush();
        }
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

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' bytes';
    }
}

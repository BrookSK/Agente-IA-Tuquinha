<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

/**
 * Rota de inicialização do servidor.
 *
 * Acesse: GET /setup?key=SUA_CHAVE_SECRETA
 *
 * Verifica e instala tudo que o sistema precisa pra funcionar:
 * - Extensões PHP obrigatórias
 * - Permissões de pastas
 * - Conexão com o banco
 * - Migrations pendentes
 * - Dependências do Node (realtime)
 * - Configurações do Apache
 */
class SetupController
{
    private string $output = '';
    private int $errors = 0;
    private int $warnings = 0;
    private int $ok = 0;

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

        set_time_limit(600);
        header('Content-Type: text/plain; charset=utf-8');

        $this->line("╔══════════════════════════════════════════╗");
        $this->line("║     SETUP DO TUQUINHA - INICIALIZAÇÃO    ║");
        $this->line("╚══════════════════════════════════════════╝");
        $this->line("");

        $this->checkPhpVersion();
        $this->checkPhpExtensions();
        $this->checkSystemTools();
        $this->checkDirectories();
        $this->checkDatabaseConnection();
        $this->runMigrations();
        $this->checkNodeDependencies();
        $this->checkApacheModules();
        $this->checkFilePermissions();

        $this->line("");
        $this->line("══════════════════════════════════════════");
        $this->line("RESULTADO FINAL");
        $this->line("  ✅ OK: {$this->ok}");
        $this->line("  ⚠️  Avisos: {$this->warnings}");
        $this->line("  ❌ Erros: {$this->errors}");
        $this->line("══════════════════════════════════════════");

        if ($this->errors === 0) {
            $this->line("\n🎉 Sistema pronto para uso!");
        } else {
            $this->line("\n⚠️  Corrija os erros acima antes de usar o sistema.");
        }

        echo $this->output;
    }

    private function line(string $text): void
    {
        $this->output .= $text . "\n";
    }

    private function pass(string $msg): void
    {
        $this->line("  ✅ $msg");
        $this->ok++;
    }

    private function warn(string $msg): void
    {
        $this->line("  ⚠️  $msg");
        $this->warnings++;
    }

    private function fail(string $msg): void
    {
        $this->line("  ❌ $msg");
        $this->errors++;
    }

    // ─── PHP Version ───────────────────────────────────────
    private function checkPhpVersion(): void
    {
        $this->line("\n[1/8] PHP VERSION");
        $version = PHP_VERSION;
        if (version_compare($version, '8.0.0', '>=')) {
            $this->pass("PHP $version (>= 8.0 necessário)");
        } else {
            $this->fail("PHP $version — necessário >= 8.0");
        }
    }

    // ─── PHP Extensions ────────────────────────────────────
    private function checkPhpExtensions(): void
    {
        $this->line("\n[2/8] EXTENSÕES PHP");

        $required = [
            'pdo'       => 'Conexão com banco de dados',
            'pdo_mysql' => 'Driver MySQL para PDO',
            'curl'      => 'Requisições HTTP (APIs externas, uploads)',
            'json'      => 'Encode/decode JSON',
            'mbstring'  => 'Manipulação de strings UTF-8',
            'session'   => 'Sessões de usuário',
            'fileinfo'  => 'Detecção de tipo de arquivo (uploads)',
            'openssl'   => 'Criptografia e tokens seguros',
        ];

        $optional = [
            'gd'        => 'Manipulação de imagens (certificados)',
            'zip'       => 'Compressão de arquivos',
            'intl'      => 'Internacionalização (datas, formatação)',
            'xml'       => 'Parsing de RSS/feeds de notícias',
            'simplexml' => 'Parsing de XML simplificado',
        ];

        foreach ($required as $ext => $desc) {
            if (extension_loaded($ext)) {
                $this->pass("$ext — $desc");
            } else {
                $this->fail("$ext NÃO INSTALADA — $desc");
                $this->line("         Instale: sudo apt install php-$ext && sudo systemctl restart apache2");
            }
        }

        $this->line("  --- Opcionais ---");
        foreach ($optional as $ext => $desc) {
            if (extension_loaded($ext)) {
                $this->pass("$ext — $desc");
            } else {
                $this->warn("$ext não instalada (opcional) — $desc");
            }
        }
    }

    // ─── System Tools ──────────────────────────────────────
    private function checkSystemTools(): void
    {
        $this->line("\n[3/9] FERRAMENTAS DO SISTEMA (PDF, DOCX, etc.)");

        // pdftotext (poppler-utils) — necessário para extrair texto de PDFs
        $pdftotextVersion = @shell_exec('pdftotext -v 2>&1');
        $hasPdftotext = $pdftotextVersion && (stripos($pdftotextVersion, 'pdftotext') !== false || stripos($pdftotextVersion, 'poppler') !== false);

        if ($hasPdftotext) {
            $this->pass("pdftotext instalado (poppler-utils) — extração de texto de PDFs");
        } else {
            $this->warn("pdftotext NÃO encontrado — PDFs não serão lidos localmente");
            $this->line("         Tentando instalar poppler-utils...");

            $installResult = @shell_exec('sudo apt-get install -y poppler-utils 2>&1');
            $checkAgain = @shell_exec('pdftotext -v 2>&1');
            $installed = $checkAgain && (stripos($checkAgain, 'pdftotext') !== false || stripos($checkAgain, 'poppler') !== false);

            if ($installed) {
                $this->pass("poppler-utils instalado com sucesso");
            } else {
                $this->fail("Não foi possível instalar automaticamente. Execute manualmente:");
                $this->line("         Ubuntu/Debian: sudo apt-get install -y poppler-utils");
                $this->line("         CentOS/RHEL:   sudo yum install -y poppler-utils");
                $this->line("         Alpine:        apk add poppler-utils");
            }
        }

        // ZipArchive (php-zip) — necessário para extrair texto de DOCX
        if (class_exists('ZipArchive')) {
            $this->pass("ZipArchive (php-zip) — extração de texto de DOCX/Word");
        } else {
            $this->warn("ZipArchive NÃO disponível — arquivos DOCX não serão lidos localmente");
            $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
            $this->line("         Tentando instalar php-zip...");

            $installResult = @shell_exec("sudo apt-get install -y php{$phpVersion}-zip 2>&1");
            if ($installResult && stripos($installResult, 'is already') !== false) {
                $this->warn("php-zip pode estar instalado mas não carregado. Reinicie o Apache/PHP-FPM.");
            } else {
                // Tenta reiniciar o Apache pra carregar a extensão
                @shell_exec('sudo systemctl restart apache2 2>&1');
                $this->line("         Instale manualmente se falhou: sudo apt install php{$phpVersion}-zip && sudo systemctl restart apache2");
            }
        }

        // Verifica endpoint externo de extração (fallback)
        try {
            $endpoint = trim((string)\App\Models\Setting::get('text_extraction_endpoint', ''));
            if ($endpoint !== '') {
                $this->pass("Endpoint externo de extração configurado: $endpoint");
            } else {
                if (!$hasPdftotext) {
                    $this->warn("Sem endpoint externo de extração configurado e sem pdftotext — PDFs não serão processados");
                    $this->line("         Configure em: Admin → Configurações → Endpoint de extração de texto");
                } else {
                    $this->line("  ℹ️  Sem endpoint externo (ok, pdftotext será usado localmente)");
                }
            }
        } catch (\Throwable $e) {
            $this->line("  ℹ️  Não foi possível verificar endpoint externo (banco pode não estar pronto ainda)");
        }
    }

    // ─── Directories ───────────────────────────────────────
    private function checkDirectories(): void
    {
        $this->line("\n[4/9] DIRETÓRIOS");

        $base = __DIR__ . '/../..';
        $dirs = [
            'public/uploads'            => "$base/public/uploads",
            'public/uploads/menu-icons' => "$base/public/uploads/menu-icons",
        ];

        foreach ($dirs as $label => $path) {
            if (is_dir($path)) {
                if (is_writable($path)) {
                    $this->pass("$label (existe e tem permissão de escrita)");
                } else {
                    $this->warn("$label existe mas SEM permissão de escrita");
                    @chmod($path, 0775);
                    if (is_writable($path)) {
                        $this->pass("$label — permissão corrigida automaticamente");
                    } else {
                        $this->fail("$label — não foi possível corrigir. Execute: chmod -R 775 $path");
                    }
                }
            } else {
                @mkdir($path, 0775, true);
                if (is_dir($path)) {
                    $this->pass("$label — criado com sucesso");
                } else {
                    $this->fail("$label — não foi possível criar. Execute: mkdir -p $path");
                }
            }
        }
    }

    // ─── Database Connection ───────────────────────────────
    private function checkDatabaseConnection(): void
    {
        $this->line("\n[5/9] CONEXÃO COM O BANCO DE DADOS");

        try {
            $pdo = Database::getConnection();
            $pdo->query('SELECT 1');
            $this->pass("Conexão MySQL OK");

            $stmt = $pdo->query("SELECT VERSION() AS v");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->pass("MySQL versão: " . ($row['v'] ?? 'desconhecida'));

            // Verifica charset
            $stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $charset = $row['Value'] ?? '';
            if (stripos($charset, 'utf8mb4') !== false) {
                $this->pass("Charset do banco: $charset");
            } else {
                $this->warn("Charset do banco: $charset (recomendado: utf8mb4)");
            }
        } catch (\Throwable $e) {
            $this->fail("Não foi possível conectar ao banco: " . $e->getMessage());
            $this->line("         Verifique as credenciais em config/config.php");
        }
    }

    // ─── Migrations ────────────────────────────────────────
    private function runMigrations(): void
    {
        $this->line("\n[6/9] MIGRATIONS");

        try {
            $pdo = Database::getConnection();
        } catch (\Throwable $e) {
            $this->fail("Sem conexão com o banco — migrations não executadas.");
            return;
        }

        // Cria tabela de controle
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

        // Schema base
        $schemaFile = __DIR__ . '/../../database/schema.sql';
        if (is_file($schemaFile) && empty($executed['schema.sql'])) {
            $sql = file_get_contents($schemaFile);
            if ($sql !== false && trim($sql) !== '') {
                try {
                    $this->executeSqlStatements($pdo, $sql);
                    $ins = $pdo->prepare('INSERT IGNORE INTO _migrations (filename) VALUES (:f)');
                    $ins->execute(['f' => 'schema.sql']);
                    $this->pass("schema.sql executado");
                } catch (\Throwable $e) {
                    $this->fail("schema.sql: " . $e->getMessage());
                }
            }
        } elseif (!empty($executed['schema.sql'])) {
            $this->pass("schema.sql (já aplicado)");
        }

        // Migration files
        $migrationsDir = __DIR__ . '/../../database/migrations';
        if (!is_dir($migrationsDir)) {
            $this->warn("Pasta database/migrations não encontrada.");
            return;
        }

        $files = [];
        foreach (scandir($migrationsDir) as $f) {
            if (substr($f, -4) === '.sql') {
                $files[] = $f;
            }
        }
        sort($files, SORT_NATURAL);

        $ran = 0;
        $skipped = 0;
        $migrationErrors = 0;

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

            try {
                $this->executeSqlStatements($pdo, $sql);
                $ins = $pdo->prepare('INSERT IGNORE INTO _migrations (filename) VALUES (:f)');
                $ins->execute(['f' => $filename]);
                $this->pass("$filename executado");
                $ran++;
            } catch (\Throwable $e) {
                $this->fail("$filename: " . $e->getMessage());
                $migrationErrors++;
            }
        }

        if ($skipped > 0) {
            $this->line("  ℹ️  $skipped migrations já aplicadas anteriormente");
        }
        if ($ran > 0) {
            $this->line("  ℹ️  $ran migrations novas executadas");
        }
        if ($ran === 0 && $migrationErrors === 0) {
            $this->pass("Banco de dados atualizado — nenhuma migration pendente");
        }
    }

    // ─── Node Dependencies ─────────────────────────────────
    private function checkNodeDependencies(): void
    {
        $this->line("\n[7/9] NODE.JS (REALTIME SERVER)");

        $realtimeDir = __DIR__ . '/../../realtime';
        if (!is_dir($realtimeDir)) {
            $this->warn("Pasta realtime/ não encontrada — servidor de tempo real não configurado.");
            return;
        }

        // Verifica se Node está instalado
        $nodeVersion = @shell_exec('node --version 2>&1');
        if ($nodeVersion && strpos(trim($nodeVersion), 'v') === 0) {
            $this->pass("Node.js instalado: " . trim($nodeVersion));
        } else {
            $this->warn("Node.js não encontrado. Instale: curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs");
            return;
        }

        // Verifica se npm está instalado
        $npmVersion = @shell_exec('npm --version 2>&1');
        if ($npmVersion && preg_match('/^\d/', trim($npmVersion))) {
            $this->pass("npm instalado: " . trim($npmVersion));
        } else {
            $this->warn("npm não encontrado.");
            return;
        }

        // Verifica se node_modules existe
        $nodeModules = $realtimeDir . '/node_modules';
        if (is_dir($nodeModules)) {
            $this->pass("node_modules já instalado");
        } else {
            $this->line("  ⏳ Instalando dependências do Node (npm install)...");
            $result = @shell_exec('cd ' . escapeshellarg($realtimeDir) . ' && npm install 2>&1');
            if (is_dir($nodeModules)) {
                $this->pass("Dependências do Node instaladas com sucesso");
            } else {
                $this->fail("Falha ao instalar dependências do Node");
                if ($result) {
                    $this->line("         " . trim($result));
                }
            }
        }
    }

    // ─── Apache Modules ────────────────────────────────────
    private function checkApacheModules(): void
    {
        $this->line("\n[8/9] CONFIGURAÇÃO DO SERVIDOR");

        // Verifica mod_rewrite
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            if (in_array('mod_rewrite', $modules)) {
                $this->pass("mod_rewrite ativo");
            } else {
                $this->fail("mod_rewrite NÃO ativo. Execute: sudo a2enmod rewrite && sudo systemctl restart apache2");
            }
        } else {
            $this->warn("Não foi possível verificar módulos do Apache (PHP pode estar rodando como CGI/FPM)");
        }

        // Verifica .htaccess
        $htaccess = __DIR__ . '/../../.htaccess';
        if (is_file($htaccess)) {
            $content = file_get_contents($htaccess);
            if (strpos($content, 'RewriteEngine On') !== false) {
                $this->pass(".htaccess presente com RewriteEngine On");
            } else {
                $this->warn(".htaccess existe mas sem RewriteEngine On");
            }
            if (strpos($content, 'HTTP_AUTHORIZATION') !== false) {
                $this->pass(".htaccess repassa header Authorization (necessário para API)");
            } else {
                $this->warn(".htaccess não repassa Authorization — API pode não funcionar");
            }
        } else {
            $this->fail(".htaccess não encontrado na raiz do projeto");
        }

        // Verifica config.php
        $configFile = __DIR__ . '/../../config/config.php';
        if (is_file($configFile)) {
            $this->pass("config/config.php presente");
        } else {
            $this->fail("config/config.php NÃO encontrado — o sistema não vai funcionar");
        }

        // Verifica se o PHP tem limite de upload razoável
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');
        $this->line("  ℹ️  upload_max_filesize: $uploadMax");
        $this->line("  ℹ️  post_max_size: $postMax");

        $uploadBytes = $this->parseSize($uploadMax);
        if ($uploadBytes < 10 * 1024 * 1024) {
            $this->warn("upload_max_filesize ($uploadMax) é menor que 10M — pode limitar uploads");
        } else {
            $this->pass("upload_max_filesize ($uploadMax) adequado");
        }
    }

    // ─── File Permissions ──────────────────────────────────
    private function checkFilePermissions(): void
    {
        $this->line("\n[9/9] PERMISSÕES DE ARQUIVOS");

        $base = __DIR__ . '/../..';
        $criticalFiles = [
            'config/config.php' => "$base/config/config.php",
            'public/index.php'  => "$base/public/index.php",
            'index.php'         => "$base/index.php",
        ];

        foreach ($criticalFiles as $label => $path) {
            if (is_file($path) && is_readable($path)) {
                $this->pass("$label legível");
            } elseif (is_file($path)) {
                $this->fail("$label existe mas NÃO é legível pelo PHP");
            } else {
                $this->fail("$label NÃO encontrado");
            }
        }

        // Verifica se a pasta de sessions é gravável
        $sessionPath = session_save_path();
        if ($sessionPath === '') {
            $sessionPath = sys_get_temp_dir();
        }
        if (is_writable($sessionPath)) {
            $this->pass("Pasta de sessões gravável ($sessionPath)");
        } else {
            $this->fail("Pasta de sessões NÃO gravável ($sessionPath)");
        }
    }

    // ─── Helpers ───────────────────────────────────────────

    private function executeSqlStatements(PDO $pdo, string $sql): void
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

        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
    }

    private function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower(substr($size, -1));
        $value = (int)$size;
        switch ($last) {
            case 'g': $value *= 1024 * 1024 * 1024; break;
            case 'm': $value *= 1024 * 1024; break;
            case 'k': $value *= 1024; break;
        }
        return $value;
    }
}

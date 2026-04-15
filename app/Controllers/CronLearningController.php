<?php

namespace App\Controllers;

use App\Models\AiPromptSuggestion;
use App\Models\ProjectSuggestionJob;
use App\Models\Setting;
use App\Models\TuquinhaEngine;

/**
 * Processa jobs de aprendizado por projeto.
 *
 * GET /cron/learning/project-suggestions?token=TOKEN&batch=N
 */
class CronLearningController
{
    public function projectSuggestions(): void
    {
        $expectedToken = trim((string)Setting::get('cron_secret_token', ''));
        if ($expectedToken === '') {
            $expectedToken = defined('MIGRATE_SECRET_KEY') ? MIGRATE_SECRET_KEY : 'tuq-migrate-2026';
        }

        $providedToken = trim((string)($_GET['token'] ?? ''));
        if ($providedToken === '' || $providedToken !== $expectedToken) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Token inválido.']);
            return;
        }

        set_time_limit(120);
        header('Content-Type: application/json; charset=utf-8');

        $batchSize = isset($_GET['batch']) ? max(1, min(20, (int)$_GET['batch'])) : 5;
        $jobs = ProjectSuggestionJob::fetchPendingBatch($batchSize);

        if (empty($jobs)) {
            echo json_encode(['ok' => true, 'processed' => 0, 'message' => 'Nenhum job pendente.']);
            return;
        }

        $processed = 0;
        $errors = 0;

        foreach ($jobs as $job) {
            $jobId = (int)($job['id'] ?? 0);
            $projectId = (int)($job['project_id'] ?? 0);
            $conversationId = (int)($job['conversation_id'] ?? 0);
            $userMessage = (string)($job['user_message'] ?? '');
            $assistantReply = (string)($job['assistant_reply'] ?? '');

            if ($jobId <= 0 || $projectId <= 0) {
                ProjectSuggestionJob::markError($jobId, 'Dados inválidos.');
                $errors++;
                continue;
            }

            ProjectSuggestionJob::markRunning($jobId);

            try {
                $engine = new TuquinhaEngine();
                $instruction = "Analise a conversa abaixo entre um usuário e um assistente de IA sobre um projeto.\n"
                    . "Extraia aprendizados úteis que poderiam melhorar respostas futuras sobre este projeto.\n"
                    . "Retorne APENAS JSON válido, sem texto extra, no formato:\n"
                    . "{\"learnings\":[{\"category\":\"...\",\"suggestion\":\"...\",\"rationale\":\"...\"}]}\n\n"
                    . "Regras:\n"
                    . "- Cada suggestion deve ser curta (até 200 chars)\n"
                    . "- category pode ser: 'estilo', 'conteudo', 'regra', 'contexto', 'preferencia'\n"
                    . "- rationale explica por que esse aprendizado é útil\n"
                    . "- Máximo 3 aprendizados por conversa\n"
                    . "- Se não houver nada relevante, retorne {\"learnings\":[]}\n\n"
                    . "CONVERSA:\n"
                    . "Usuário: " . $userMessage . "\n"
                    . "Assistente: " . mb_substr($assistantReply, 0, 2000, 'UTF-8');

                $result = $engine->generateResponseWithContext(
                    [['role' => 'user', 'content' => $instruction]],
                    null, null, null, null
                );

                $text = is_array($result) ? (string)($result['content'] ?? '') : (string)$result;
                $text = trim($text);

                // Remove wrapper ```json ... ``` se presente
                if (str_starts_with($text, '```')) {
                    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
                    $text = preg_replace('/\s*```\s*$/', '', $text);
                    $text = trim($text);
                }

                $json = json_decode($text, true);
                if (is_array($json) && isset($json['learnings']) && is_array($json['learnings'])) {
                    foreach ($json['learnings'] as $learning) {
                        $cat = trim((string)($learning['category'] ?? 'contexto'));
                        $sug = trim((string)($learning['suggestion'] ?? ''));
                        $rat = trim((string)($learning['rationale'] ?? ''));
                        if ($sug !== '') {
                            AiPromptSuggestion::create($cat, $sug, $rat, $projectId, $conversationId);
                        }
                    }
                }

                ProjectSuggestionJob::markDone($jobId);
                $processed++;
            } catch (\Throwable $e) {
                ProjectSuggestionJob::markError($jobId, $e->getMessage());
                $errors++;
            }
        }

        echo json_encode([
            'ok' => true,
            'processed' => $processed,
            'errors' => $errors,
            'total_jobs' => count($jobs),
        ]);
    }
}

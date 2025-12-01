<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Setting;
use App\Models\AsaasConfig;

class AdminConfigController extends Controller
{
    public function index(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
        $openaiKey = Setting::get('openai_api_key', '');
        $defaultModel = Setting::get('openai_default_model', AI_MODEL);
        $transcriptionModel = Setting::get('openai_transcription_model', 'gpt-4o-mini-transcribe');

        $asaas = AsaasConfig::getActive();

        $this->view('admin/config', [
            'pageTitle' => 'Configuração - OpenAI',
            'openaiKey' => $openaiKey,
            'defaultModel' => $defaultModel,
            'transcriptionModel' => $transcriptionModel,
            'asaasEnvironment' => $asaas['environment'] ?? 'sandbox',
            'asaasSandboxKey' => $asaas['sandbox_api_key'] ?? '',
            'asaasProdKey' => $asaas['production_api_key'] ?? '',
            'saved' => false,
        ]);
    }

    public function save(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
        $key = trim($_POST['openai_key'] ?? '');
        $defaultModel = trim($_POST['default_model'] ?? '');
        $transcriptionModel = trim($_POST['transcription_model'] ?? '');
        $asaasEnv = $_POST['asaas_environment'] ?? 'sandbox';
        $asaasSandboxKey = trim($_POST['asaas_sandbox_key'] ?? '');
        $asaasProdKey = trim($_POST['asaas_prod_key'] ?? '');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');

        $settingsToSave = [
            'openai_api_key' => $key,
            'openai_default_model' => $defaultModel !== '' ? $defaultModel : AI_MODEL,
            'openai_transcription_model' => $transcriptionModel !== '' ? $transcriptionModel : 'gpt-4o-mini-transcribe',
        ];

        foreach ($settingsToSave as $sKey => $sValue) {
            $stmt->execute([
                'key' => $sKey,
                'value' => $sValue,
            ]);
        }

        // Salva configuração Asaas (linha única)
        $pdo->exec("INSERT INTO asaas_configs (id, environment, sandbox_api_key, production_api_key)
            VALUES (1, 'sandbox', '', '')
            ON DUPLICATE KEY UPDATE environment = environment");

        $stmtAsaas = $pdo->prepare('UPDATE asaas_configs SET environment = :env, sandbox_api_key = :sandbox, production_api_key = :prod WHERE id = 1');
        $stmtAsaas->execute([
            'env' => $asaasEnv === 'production' ? 'production' : 'sandbox',
            'sandbox' => $asaasSandboxKey,
            'prod' => $asaasProdKey,
        ]);

        $this->view('admin/config', [
            'pageTitle' => 'Configuração - OpenAI',
            'openaiKey' => $key,
            'defaultModel' => $settingsToSave['openai_default_model'],
            'transcriptionModel' => $settingsToSave['openai_transcription_model'],
            'asaasEnvironment' => $asaasEnv === 'production' ? 'production' : 'sandbox',
            'asaasSandboxKey' => $asaasSandboxKey,
            'asaasProdKey' => $asaasProdKey,
            'saved' => true,
        ]);
    }
}

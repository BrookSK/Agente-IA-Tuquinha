<?php
/** @var string $openaiKey */
/** @var string $defaultModel */
/** @var string $anthropicKey */
/** @var string $transcriptionModel */
/** @var string $systemPrompt */
/** @var string $systemPromptExtra */
/** @var int $historyRetentionDays */
/** @var int $freeGlobalLimit */
/** @var int $freeChatLimit */
/** @var string $asaasEnvironment */
/** @var string $asaasSandboxKey */
/** @var string $asaasProdKey */
/** @var string $smtpHost */
/** @var string $smtpPort */
/** @var string $smtpUser */
/** @var string $smtpPassword */
/** @var string $smtpFromEmail */
/** @var string $smtpFromName */
/** @var string $adminErrorEmail */
/** @var string $adminErrorWebhook */
/** @var string $extraTokenPricePer1kGlobal */
/** @var string $googleClientId */
/** @var string $googleClientSecret */
/** @var string $googleRefreshToken */
/** @var string $googleCalendarId */
/** @var string $mediaEndpoint */
/** @var bool $saved */
/** @var bool|null $testEmailStatus */
/** @var string|null $testEmailError */

$knownModels = [
    'gpt-4o-mini',
    'gpt-4o',
    'gpt-4.1',
    'claude-3-5-sonnet-20240620',
    'claude-3-haiku-20240307',
];
?>
<div style="max-width: 720px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 8px; font-weight: 650;">Configurações do sistema</h1>
    <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
        Ajuste aqui a chave de API, os modelos padrão usados pelo Tuquinha e as credenciais de envio de e-mails (SMTP).
    </p>

    <?php if (!empty($saved)): ?>
        <div style="background: #14361f; border-radius: 10px; padding: 10px 12px; color: #c1ffda; font-size: 13px; margin-bottom: 14px; border: 1px solid #2ecc71;">
            Configurações salvas com sucesso.
        </div>
    <?php endif; ?>

    <?php if ($testEmailStatus === true): ?>
        <div style="background: #14361f; border-radius: 10px; padding: 10px 12px; color: #c1ffda; font-size: 13px; margin-bottom: 14px; border: 1px solid #2ecc71;">
            E-mail de teste enviado com sucesso.
        </div>
    <?php elseif ($testEmailStatus === false && $testEmailError !== null): ?>
        <div style="background: #311; border-radius: 10px; padding: 10px 12px; color: #ffbaba; font-size: 13px; margin-bottom: 14px; border: 1px solid #a33;">
            <?= htmlspecialchars($testEmailError) ?>
        </div>
    <?php endif; ?>

    <form action="/admin/config" method="post" style="display: flex; flex-direction: column; gap: 16px;">
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Chave da API OpenAI</label>
            <input name="openai_key" type="password" value="<?= htmlspecialchars($openaiKey) ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="sk-...">
            <small style="color:#777; font-size:11px;">Esta chave será usada para o chat (modelos gpt-*) e para a transcrição de áudio.</small>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Chave da API Anthropic (Claude)</label>
            <input name="anthropic_key" type="password" value="<?= htmlspecialchars($anthropicKey ?? '') ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="sk-ant-...">
            <small style="color:#777; font-size:11px;">Opcional. Necessária apenas se você quiser liberar modelos Claude (claude-3-*) nos planos e no chat.</small>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Endpoint de upload de mídia</label>
            <input name="media_endpoint" value="<?= htmlspecialchars($mediaEndpoint ?? '') ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="https://media.seusite.com/upload.php">
            <small style="color:#777; font-size:11px;">URL do script PHP que recebe uploads (imagens, arquivos e áudios) e retorna JSON com a URL pública.</small>
        </div>

        <div style="margin-top: 8px; padding:10px 12px; border-radius:10px; border:1px solid #272727; background:#0a0a10;">
            <div style="font-size:13px; color:#b0b0b0; margin-bottom:8px;">
                <strong>Google Meet / Calendar (lives)</strong><br>
                Configure abaixo a conta PRO do Google que será usada para criar as lives no Google Meet via API.
            </div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <div>
                    <label style="font-size: 12px; color: #b0b0b0;">Client ID (OAuth 2.0)</label>
                    <input name="google_client_id" value="<?= htmlspecialchars($googleClientId) ?>" style="
                        width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                        background: #050509; color: #f5f5f5; font-size: 13px;
                    " placeholder="ex: 1234567890-abc.apps.googleusercontent.com">
                </div>
                <div>
                    <label style="font-size: 12px; color: #b0b0b0;">Client Secret</label>
                    <input name="google_client_secret" type="password" value="<?= htmlspecialchars($googleClientSecret) ?>" style="
                        width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                        background: #050509; color: #f5f5f5; font-size: 13px;
                    " placeholder="segredo do cliente OAuth 2.0">
                </div>
                <div>
                    <label style="font-size: 12px; color: #b0b0b0;">Refresh Token</label>
                    <input name="google_refresh_token" type="password" value="<?= htmlspecialchars($googleRefreshToken) ?>" style="
                        width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                        background: #050509; color: #f5f5f5; font-size: 13px;
                    " placeholder="token de atualização obtido no OAuth Playground">
                    <small style="color:#777; font-size:11px;">Use o OAuth Playground do Google para gerar este token a partir da conta PRO que será dona das lives.</small>
                </div>
                <div>
                    <label style="font-size: 12px; color: #b0b0b0;">Calendar ID</label>
                    <input name="google_calendar_id" value="<?= htmlspecialchars($googleCalendarId) ?>" style="
                        width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                        background: #050509; color: #f5f5f5; font-size: 13px;
                    " placeholder="primary ou e-mail da conta">
                    <small style="color:#777; font-size:11px;">Normalmente use <strong>primary</strong> para a agenda principal da conta PRO. Você também pode usar o e-mail da conta ou o ID de uma agenda específica.</small>
                </div>
            </div>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Modelo padrão do chat</label>
            <select name="default_model" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            ">
                <?php foreach ($knownModels as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $defaultModel === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
                <?php if ($defaultModel && !in_array($defaultModel, $knownModels, true)): ?>
                    <option value="<?= htmlspecialchars($defaultModel) ?>" selected><?= htmlspecialchars($defaultModel) ?> (atual)</option>
                <?php endif; ?>
            </select>
            <small style="color:#777; font-size:11px;">Pode ser sobrescrito por plano ou pelo usuário na seleção de modelo.</small>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Modelo de transcrição de áudio</label>
            <input name="transcription_model" value="<?= htmlspecialchars($transcriptionModel) ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="ex: whisper-1 ou gpt-4o-mini-transcribe">
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Prompt padrão do Tuquinha (comportamento da IA)</label>
            <textarea name="system_prompt" rows="12" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px; resize: vertical;
            "><?= htmlspecialchars($systemPrompt) ?></textarea>
            <small style="color:#777; font-size:11px;">Esse texto define como o Tuquinha se comporta e responde. Se você apagar tudo e salvar, o sistema volta para o prompt padrão original.</small>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Regras extras (opcional)</label>
            <textarea name="system_prompt_extra" rows="8" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px; resize: vertical;
            "><?= htmlspecialchars($systemPromptExtra) ?></textarea>
            <small style="color:#777; font-size:11px;">Use este campo para complementar o comportamento base, por exemplo com regras específicas para certos tipos de projeto, limites ou orientações internas.</small>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Dias para manter o histórico de conversas</label>
            <input name="history_retention_days" type="number" min="1" value="<?= htmlspecialchars((string)($historyRetentionDays ?? 90)) ?>" style="
                width: 120px; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            ">
            <small style="color:#777; font-size:11px;">Define por quantos dias as conversas ficarão salvas para o usuário antes de serem apagadas automaticamente.</small>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <div style="flex:1; min-width:180px;">
                <label style="font-size: 12px; color: #b0b0b0;">Limite de caracteres globais no plano Free</label>
                <input name="free_global_limit" type="number" min="50" value="<?= htmlspecialchars((string)($freeGlobalLimit ?? 500)) ?>" style="
                    width: 120px; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                    background: #050509; color: #f5f5f5; font-size: 13px;
                ">
                <small style="color:#777; font-size:11px;">Máximo de caracteres considerados nas memórias e regras globais de contas Free.</small>
            </div>
            <div style="flex:1; min-width:180px;">
                <label style="font-size: 12px; color: #b0b0b0;">Limite de caracteres por chat no plano Free</label>
                <input name="free_chat_limit" type="number" min="50" value="<?= htmlspecialchars((string)($freeChatLimit ?? 400)) ?>" style="
                    width: 120px; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                    background: #050509; color: #f5f5f5; font-size: 13px;
                ">
                <small style="color:#777; font-size:11px;">Máximo de caracteres considerados nas memórias e regras específicas de cada chat para contas Free.</small>
            </div>
        </div>

        <div style="margin-top: 8px; padding:10px 12px; border-radius:10px; border:1px solid #272727; background:#0a0a10;">
            <div style="font-size:13px; color:#b0b0b0; margin-bottom:8px;">
                <strong>Preço global de tokens extras</strong><br>
                Defina aqui o valor padrão cobrado por <strong>1.000 tokens extras</strong> quando o usuário comprar mais saldo além do plano.
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
                <div>
                    <label style="font-size: 12px; color: #b0b0b0;">Preço por 1.000 tokens extras (R$)</label>
                    <input name="extra_token_price_per_1k_global" value="<?= $extraTokenPricePer1kGlobal !== '' ? number_format((float)$extraTokenPricePer1kGlobal, 4, ',', '.') : '' ?>" style="
                        width: 160px; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                        background: #050509; color: #f5f5f5; font-size: 13px;
                    " placeholder="ex: 0,0090">
                </div>
                <div style="font-size:11px; color:#777; max-width:360px;">
                    Este valor é usado para calcular o preço das compras avulsas de tokens (pay as you go), independente do plano do usuário.
                </div>
            </div>
        </div>

        <div style="margin-top: 12px; padding:10px 12px; border-radius:10px; border:1px solid #272727; background:#0a0a10;">
            <div style="font-size:13px; color:#b0b0b0; margin-bottom:8px;">
                <strong>Notificações de erros de análise</strong><br>
                Configure para onde o sistema deve avisar quando um usuário relatar erro ao analisar arquivos ou mensagens.
            </div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <div>
                    <label style="font-size: 12px; color: #b0b0b0;">E-mail do administrador para alertas</label>
                    <input name="admin_error_email" value="<?= htmlspecialchars($adminErrorEmail) ?>" style="
                        width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                        background: #050509; color: #f5f5f5; font-size: 13px;
                    " placeholder="ex: suporte@seusite.com">
                    <small style="color:#777; font-size:11px;">Quando um usuário relatar um erro de análise, o Tuquinha envia um resumo para este e-mail.</small>
                </div>
                <div>
                    <label style="font-size: 12px; color: #b0b0b0;">URL de webhook para erros</label>
                    <input name="admin_error_webhook" value="<?= htmlspecialchars($adminErrorWebhook) ?>" style="
                        width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                        background: #050509; color: #f5f5f5; font-size: 13px;
                    " placeholder="https://api.seusistema.com/webhooks/tuquinha-erros">
                    <small style="color:#777; font-size:11px;">Opcional. Se preenchido, o sistema envia um POST em JSON com os detalhes do erro para esta URL.</small>
                </div>
            </div>
        </div>

        <hr style="border:none; border-top:1px solid #272727; margin: 8px 0;">

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Host SMTP</label>
            <input name="smtp_host" value="<?= htmlspecialchars($smtpHost) ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="ex: smtp.seuprovedor.com">
        </div>

        <div style="display:flex; gap:10px;">
            <div style="flex:1;">
                <label style="font-size: 12px; color: #b0b0b0;">Porta SMTP</label>
                <input name="smtp_port" value="<?= htmlspecialchars($smtpPort) ?>" style="
                    width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                    background: #050509; color: #f5f5f5; font-size: 13px;
                " placeholder="587">
            </div>
            <div style="flex:1;">
                <label style="font-size: 12px; color: #b0b0b0;">Usuário SMTP</label>
                <input name="smtp_user" value="<?= htmlspecialchars($smtpUser) ?>" style="
                    width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                    background: #050509; color: #f5f5f5; font-size: 13px;
                " placeholder="usuário ou e-mail">
            </div>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Senha SMTP</label>
            <input name="smtp_password" type="password" value="<?= htmlspecialchars($smtpPassword) ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="senha ou token">
        </div>

        <div style="display:flex; gap:10px;">
            <div style="flex:1;">
                <label style="font-size: 12px; color: #b0b0b0;">E-mail remetente</label>
                <input name="smtp_from_email" value="<?= htmlspecialchars($smtpFromEmail) ?>" style="
                    width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                    background: #050509; color: #f5f5f5; font-size: 13px;
                " placeholder="ex: suporte@seusite.com">
            </div>
            <div style="flex:1;">
                <label style="font-size: 12px; color: #b0b0b0;">Nome remetente</label>
                <input name="smtp_from_name" value="<?= htmlspecialchars($smtpFromName) ?>" style="
                    width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                    background: #050509; color: #f5f5f5; font-size: 13px;
                " placeholder="ex: Tuquinha IA">
            </div>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Ambiente Asaas</label>
            <select name="asaas_environment" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            ">
                <option value="sandbox" <?= $asaasEnvironment === 'sandbox' ? 'selected' : '' ?>>Sandbox (teste)</option>
                <option value="production" <?= $asaasEnvironment === 'production' ? 'selected' : '' ?>>Produção</option>
            </select>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Chave API Asaas - Sandbox</label>
            <input name="asaas_sandbox_key" value="<?= htmlspecialchars($asaasSandboxKey) ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="token sandbox">
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Chave API Asaas - Produção</label>
            <input name="asaas_prod_key" value="<?= htmlspecialchars($asaasProdKey) ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="token produção">
        </div>

        <div style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 8px;">
            <a href="/" style="
                font-size: 13px; color:#b0b0b0; text-decoration:none; padding:8px 12px;
            ">Voltar</a>
            <button type="submit" style="
                border: none; border-radius: 999px; padding: 9px 18px;
                background: linear-gradient(135deg, #e53935, #ff6f60);
                color: #050509; font-weight: 600; font-size: 14px; cursor: pointer;
            ">
                Salvar configurações
            </button>
        </div>
    </form>

    <div style="margin-top:16px; padding:10px 12px; border-radius:10px; border:1px solid #272727; background:#0a0a10;">
        <div style="font-size:13px; color:#b0b0b0; margin-bottom:8px;">
            <strong>Teste rápido de envio de e-mail</strong><br>
            Use este campo para enviar um e-mail de teste e confirmar se as credenciais SMTP / servidor estão funcionando.
        </div>
        <form action="/admin/config/test-email" method="post" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <input name="test_email" type="email" placeholder="E-mail para teste" style="
                flex: 1 1 220px; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            ">
            <button type="submit" style="
                border: none; border-radius: 999px; padding: 8px 14px;
                background: linear-gradient(135deg, #e53935, #ff6f60);
                color: #050509; font-weight: 600; font-size: 13px; cursor: pointer;
            ">
                Enviar e-mail de teste
            </button>
        </form>
    </div>
</div>

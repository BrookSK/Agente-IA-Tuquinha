<?php
/** @var string $openaiKey */
/** @var string $defaultModel */
/** @var string $transcriptionModel */
/** @var string $systemPrompt */
/** @var string $systemPromptExtra */
/** @var int $historyRetentionDays */
/** @var string $asaasEnvironment */
/** @var string $asaasSandboxKey */
/** @var string $asaasProdKey */
/** @var string $smtpHost */
/** @var string $smtpPort */
/** @var string $smtpUser */
/** @var string $smtpPassword */
/** @var string $smtpFromEmail */
/** @var string $smtpFromName */
/** @var bool $saved */
/** @var bool|null $testEmailStatus */
/** @var string|null $testEmailError */

$knownModels = [
    'gpt-4o-mini',
    'gpt-4o',
    'gpt-4.1',
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
            <small style="color:#777; font-size:11px;">Esta chave será usada tanto para o chat quanto para a transcrição de áudio.</small>
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

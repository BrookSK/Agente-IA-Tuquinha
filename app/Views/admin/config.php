<?php
/** @var string $openaiKey */
/** @var string $defaultModel */
/** @var string $transcriptionModel */
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
            <input name="default_model" value="<?= htmlspecialchars($defaultModel) ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="ex: gpt-4o-mini">
            <small style="color:#777; font-size:11px;">Pode ser sobrescrito por plano ou pelo usuário na seleção de modelo.</small>
        </div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Modelo de transcrição de áudio</label>
            <input name="transcription_model" value="<?= htmlspecialchars($transcriptionModel) ?>" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            " placeholder="ex: whisper-1 ou gpt-4o-mini-transcribe">
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
</div>

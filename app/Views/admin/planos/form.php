<?php
/** @var array|null $plan */
$isEdit = !empty($plan);

$knownModels = [
    'gpt-4o-mini',
    'gpt-4o',
    'gpt-4.1',
];

$selectedAllowed = [];
if (!empty($plan['allowed_models'])) {
    $decoded = json_decode((string)$plan['allowed_models'], true);
    if (is_array($decoded)) {
        $selectedAllowed = array_values(array_filter(array_map('strval', $decoded)));
    }
}
$planDefaultModel = $plan['default_model'] ?? '';

// Detecta ciclo atual a partir do slug (para edição)
$billingCycle = 'monthly';
$slugForCycle = (string)($plan['slug'] ?? '');
if ($slugForCycle !== '') {
    if (substr($slugForCycle, -11) === '-semestral') {
        $billingCycle = 'semiannual';
    } elseif (substr($slugForCycle, -6) === '-anual') {
        $billingCycle = 'annual';
    } else {
        $billingCycle = 'monthly';
    }
}
?>
<div style="max-width: 640px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">
        <?= $isEdit ? 'Editar plano' : 'Novo plano' ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:14px;">
        Defina nome, ciclo de cobrança, preço e quais recursos esse plano libera no Tuquinha.
    </p>

    <form action="/admin/planos/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$plan['id'] ?>">
        <?php endif; ?>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Nome do plano</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($plan['name'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Ciclo de cobrança</label>
            <select name="billing_cycle" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px;">
                <option value="monthly" <?= $billingCycle === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                <option value="semiannual" <?= $billingCycle === 'semiannual' ? 'selected' : '' ?>>Semestral</option>
                <option value="annual" <?= $billingCycle === 'annual' ? 'selected' : '' ?>>Anual</option>
            </select>
            <div style="font-size:11px; color:#777; margin-top:3px;">Isso define se a cobrança será mensal, a cada 6 meses ou anual no Asaas.</div>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Slug (técnico)</label>
            <input type="text" name="slug" required value="<?= htmlspecialchars($plan['slug'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">
                Usado nas URLs e integrações (ex: free, pro). O sistema pode adicionar um sufixo automático (<code>-mensal</code>, <code>-semestral</code>, <code>-anual</code>) conforme o ciclo escolhido.
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Preço por período (R$)</label>
            <input type="text" name="price" required value="<?= isset($plan['price_cents']) ? number_format($plan['price_cents']/100, 2, ',', '.') : '0,00' ?>" style="
                width:120px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">
                Informe o valor cobrado em cada ciclo: mensal, semestral ou anual (de acordo com o sufixo do slug).
            </div>
        </div>

        <div style="display:flex; gap:16px; flex-wrap:wrap;">
            <div style="flex:1 1 160px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Limite mensal de tokens</label>
                <input type="number" name="monthly_token_limit" min="0" value="<?= isset($plan['monthly_token_limit']) ? (int)$plan['monthly_token_limit'] : '' ?>" style="
                    width:160px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:13px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Se vazio ou 0, o plano não terá limite mensal rígido de tokens.</div>
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Descrição curta</label>
            <textarea name="description" rows="2" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;">
<?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Benefícios (um por linha)</label>
            <textarea name="benefits" rows="4" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;">
<?= htmlspecialchars($plan['benefits'] ?? '') ?></textarea>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Modelos de IA permitidos neste plano</label>
            <div style="display:flex; flex-wrap:wrap; gap:8px; font-size:13px; color:#ddd;">
                <?php foreach ($knownModels as $m): ?>
                    <label style="display:flex; align-items:center; gap:5px;">
                        <input type="checkbox" name="allowed_models[]" value="<?= htmlspecialchars($m) ?>" <?= in_array($m, $selectedAllowed, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($m) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="font-size:11px; color:#777; margin-top:3px;">Isso controla quais modelos aparecem para o usuário na seleção dentro do chat.</div>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Modelo padrão deste plano</label>
            <select name="default_model" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px;">
                <option value="">Usar modelo padrão global</option>
                <?php foreach ($knownModels as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $planDefaultModel === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Dias para manter o histórico deste plano (opcional)</label>
            <input type="number" name="history_retention_days" min="1" value="<?= isset($plan['history_retention_days']) ? (int)$plan['history_retention_days'] : '' ?>" style="
                width:140px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">Se vazio, usa o valor padrão configurado em Configurações do sistema.</div>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px; font-size:13px; color:#ddd; margin-top:8px;">
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_audio" value="1" <?= !empty($plan['allow_audio']) ? 'checked' : '' ?>>
                <span>Permitir áudios</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_images" value="1" <?= !empty($plan['allow_images']) ? 'checked' : '' ?>>
                <span>Permitir imagens</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_files" value="1" <?= !empty($plan['allow_files']) ? 'checked' : '' ?>>
                <span>Permitir arquivos</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="is_active" value="1" <?= !isset($plan['is_active']) || !empty($plan['is_active']) ? 'checked' : '' ?>>
                <span>Plano ativo</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="is_default_for_users" value="1" <?= !empty($plan['is_default_for_users']) ? 'checked' : '' ?>>
                <span>Plano padrão para novos usuários</span>
            </label>
        </div>
        <div style="font-size:11px; color:#777; margin-top:4px;">
            Apenas um plano será considerado padrão. Se você marcar mais de um, o sistema usa o primeiro pelo ordenamento (sort_order e preço).
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar plano
            </button>
            <a href="/admin/planos" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid #272727; color:#f5f5f5;
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

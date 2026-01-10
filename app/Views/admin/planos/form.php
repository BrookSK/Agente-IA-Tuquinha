<?php
/** @var array|null $plan */
$isEdit = !empty($plan);

$knownModels = [
    'gpt-4o-mini',
    'gpt-4o',
    'gpt-4.1',
    'gpt-5.2-chat-latest',
    'claude-3-5-sonnet-latest',
    'claude-3-haiku-20240307',
    'gemini-2.5-flash-image',
    'gemini-3-pro-image-preview',
];

$selectedAllowed = [];
if (!empty($plan['allowed_models'])) {
    $decoded = json_decode((string)$plan['allowed_models'], true);
    if (is_array($decoded)) {
        $selectedAllowed = array_values(array_filter(array_map('strval', $decoded)));
    }
}
$planDefaultModel = $plan['default_model'] ?? '';
$courseDiscountPercent = $plan['course_discount_percent'] ?? '';

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
    <p style="color:var(--text-secondary); font-size:13px; margin-bottom:14px;">
        Defina nome, ciclo de cobrança, preço e quais recursos esse plano libera no Tuquinha.
    </p>

    <form action="/admin/planos/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$plan['id'] ?>">
        <?php endif; ?>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Nome do plano</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($plan['name'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Ciclo de cobrança</label>
            <select name="billing_cycle" id="plan-billing-cycle" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                <option value="monthly" <?= $billingCycle === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                <option value="semiannual" <?= $billingCycle === 'semiannual' ? 'selected' : '' ?>>Semestral</option>
                <option value="annual" <?= $billingCycle === 'annual' ? 'selected' : '' ?>>Anual</option>
            </select>
            <div style="font-size:11px; color:#777; margin-top:3px;">Isso define se a cobrança será mensal, a cada 6 meses ou anual no Asaas.</div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Slug (técnico)</label>
            <?php $planSlugExisting = (string)($plan['slug'] ?? ''); ?>
            <input type="text" id="plan-slug" value="<?= htmlspecialchars($planSlugExisting) ?>" readonly style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">
                Gerado automaticamente a partir do nome + ciclo (<code>-mensal</code>, <code>-semestral</code>, <code>-anual</code>).
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Preço por período (R$)</label>
            <input type="text" name="price" required value="<?= isset($plan['price_cents']) ? number_format($plan['price_cents']/100, 2, ',', '.') : '0,00' ?>" style="
                width:120px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">
                Informe o valor cobrado em cada ciclo: mensal, semestral ou anual (de acordo com o sufixo do slug).
            </div>
        </div>

        <div style="display:flex; gap:16px; flex-wrap:wrap;">
            <div style="flex:1 1 160px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Limite mensal de tokens</label>
                <input type="number" name="monthly_token_limit" min="0" value="<?= isset($plan['monthly_token_limit']) ? (int)$plan['monthly_token_limit'] : '' ?>" style="
                    width:160px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Se vazio ou 0, o plano não terá limite mensal rígido de tokens.</div>
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Descrição curta</label>
            <textarea name="description" rows="2" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;">
<?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Benefícios (um por linha)</label>
            <textarea name="benefits" rows="4" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;">
<?= htmlspecialchars($plan['benefits'] ?? '') ?></textarea>
        </div>

        <div style="margin-top:6px; padding-top:10px; border-top:1px dashed var(--border-subtle);">
            <div style="font-size:14px; font-weight:600; margin-bottom:6px;">Indique e ganhe (opcional)</div>
            <div style="font-size:12px; color:#777; margin-bottom:8px;">
                Configure o programa de indicação para este plano. O usuário só poderá indicar se estiver assinando este mesmo plano por pelo menos X dias.
            </div>

            <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:var(--text-primary); margin-bottom:8px;">
                <input type="checkbox" name="referral_enabled" value="1" <?= !empty($plan['referral_enabled']) ? 'checked' : '' ?>>
                <span>Ativar Indique e ganhe neste plano</span>
            </label>

            <div style="display:flex; flex-wrap:wrap; gap:12px; font-size:13px; color:var(--text-primary);">
                <div style="flex:1 1 160px;">
                    <label style="display:block; margin-bottom:4px;">Dias mínimos de assinatura para poder indicar</label>
                    <input type="number" name="referral_min_active_days" min="0" value="<?= isset($plan['referral_min_active_days']) ? (int)$plan['referral_min_active_days'] : '' ?>" style="
                        width:120px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    <div style="font-size:11px; color:#777; margin-top:3px;">Ex.: 30 dias. Se vazio ou 0, libera indicações assim que a assinatura estiver ativa.</div>
                </div>

                <div style="flex:1 1 160px;">
                    <label style="display:block; margin-bottom:4px;">Tokens para quem indica</label>
                    <input type="number" name="referral_referrer_tokens" min="0" value="<?= isset($plan['referral_referrer_tokens']) ? (int)$plan['referral_referrer_tokens'] : '' ?>" style="
                        width:140px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                </div>

                <div style="flex:1 1 160px;">
                    <label style="display:block; margin-bottom:4px;">Tokens para quem é indicado</label>
                    <input type="number" name="referral_friend_tokens" min="0" value="<?= isset($plan['referral_friend_tokens']) ? (int)$plan['referral_friend_tokens'] : '' ?>" style="
                        width:140px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                </div>

                <div style="flex:1 1 160px;">
                    <label style="display:block; margin-bottom:4px;">Dias gratuitos deste plano para o indicado</label>
                    <input type="number" name="referral_free_days" min="0" value="<?= isset($plan['referral_free_days']) ? (int)$plan['referral_free_days'] : '' ?>" style="
                        width:140px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    <div style="font-size:11px; color:#777; margin-top:3px;">O primeiro vencimento da assinatura será empurrado para depois desses dias.</div>
                </div>
            </div>

            <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:var(--text-primary); margin-top:8px;">
                <input type="checkbox" name="referral_require_card" value="1" <?= !isset($plan['referral_require_card']) || !empty($plan['referral_require_card']) ? 'checked' : '' ?>>
                <span>Exigir cadastro de cartão de crédito para liberar o bônus</span>
            </label>
            <div style="font-size:11px; color:#777; margin-top:3px;">
                Mesmo com dias grátis, o cartão fica salvo para começar a cobrar automaticamente após o período.</div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Modelos de IA permitidos neste plano</label>
            <div style="display:flex; flex-wrap:wrap; gap:8px; font-size:13px; color:var(--text-secondary);">
                <?php foreach ($knownModels as $m): ?>
                    <?php $label = $m; ?>
                    <?php if ($m === 'gpt-5.2-chat-latest'): ?>
                        <?php $label = 'GPT-5.2 Chat'; ?>
                    <?php endif; ?>
                    <?php if ($m === 'gemini-2.5-flash-image' || $m === 'gemini-3-pro-image-preview'): ?>
                        <?php $label = $m . ' (Nano Banana)'; ?>
                    <?php endif; ?>
                    <label style="display:flex; align-items:center; gap:5px;">
                        <input type="checkbox" name="allowed_models[]" value="<?= htmlspecialchars($m) ?>" <?= in_array($m, $selectedAllowed, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="font-size:11px; color:#777; margin-top:3px;">Isso controla quais modelos aparecem para o usuário na seleção dentro do chat.</div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Modelo padrão deste plano</label>
            <select name="default_model" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                <option value="">Usar modelo padrão global</option>
                <?php foreach ($knownModels as $m): ?>
                    <?php $label = $m; ?>
                    <?php if ($m === 'gpt-5.2-chat-latest'): ?>
                        <?php $label = 'GPT-5.2 Chat'; ?>
                    <?php endif; ?>
                    <?php if ($m === 'gemini-2.5-flash-image' || $m === 'gemini-3-pro-image-preview'): ?>
                        <?php $label = $m . ' (Nano Banana)'; ?>
                    <?php endif; ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $planDefaultModel === $m ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Desconto em todos os cursos (%)</label>
            <input type="number" name="course_discount_percent" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string)$courseDiscountPercent) ?>" style="
                width:160px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">Ex.: 15 para dar 15% de desconto em qualquer curso pago da plataforma. Pode ficar 0.</div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Dias para manter o histórico deste plano (opcional)</label>
            <input type="number" name="history_retention_days" min="1" value="<?= isset($plan['history_retention_days']) ? (int)$plan['history_retention_days'] : '' ?>" style="
                width:140px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">Se vazio, usa o valor padrão configurado em Configurações do sistema.</div>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px; font-size:13px; color:var(--text-secondary); margin-top:8px;">
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
                <input type="checkbox" name="allow_personalities" value="1" <?= !empty($plan['allow_personalities']) ? 'checked' : '' ?>>
                <span>Permitir personalidades</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_courses" value="1" <?= !empty($plan['allow_courses']) ? 'checked' : '' ?>>
                <span>Permitir acesso a cursos</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_projects_access" value="1" <?= !empty($plan['allow_projects_access']) ? 'checked' : '' ?>>
                <span>Permitir acesso a projetos</span>
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
                border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var nameInput = document.querySelector('input[name="name"]');
    var cycleInput = document.getElementById('plan-billing-cycle');
    var slugInput = document.getElementById('plan-slug');
    if (!nameInput || !cycleInput || !slugInput) return;

    var isFree = (slugInput.value || '').trim() === 'free';
    if (isFree) {
        slugInput.value = 'free';
        return;
    }

    function slugify(text) {
        text = (text || '').trim();
        if (!text) return '';
        try {
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        } catch (e) {}
        text = text.toLowerCase();
        text = text.replace(/[^a-z0-9]+/g, '-');
        text = text.replace(/^-+|-+$/g, '');
        return text;
    }

    function computeSlug() {
        var base = slugify(nameInput.value);
        if (!base) base = 'plano';
        var cycle = cycleInput.value || 'monthly';
        var suffix = '-mensal';
        if (cycle === 'semiannual') suffix = '-semestral';
        else if (cycle === 'annual') suffix = '-anual';
        slugInput.value = base + suffix;
    }

    nameInput.addEventListener('input', computeSlug);
    cycleInput.addEventListener('change', computeSlug);
    computeSlug();
});
</script>

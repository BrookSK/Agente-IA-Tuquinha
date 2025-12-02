<?php /** @var array $user */ ?>
<?php /** @var array|null $subscription */ ?>
<?php /** @var array|null $plan */ ?>

<div style="max-width: 800px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 16px;">Detalhes do usuário</h1>

    <a href="/admin/usuarios" style="font-size:12px; color:#ff6f60; text-decoration:none;">⟵ Voltar para lista</a>

    <div style="margin-top:16px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Dados básicos</h2>
        <p style="font-size:13px; margin-bottom:4px;"><strong>Nome:</strong> <?= htmlspecialchars($user['name']) ?></p>
        <p style="font-size:13px; margin-bottom:4px;"><strong>E-mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p style="font-size:13px; margin-bottom:4px;"><strong>Admin:</strong> <?= !empty($user['is_admin']) ? 'Sim' : 'Não' ?></p>
        <p style="font-size:13px; margin-bottom:8px;">
            <strong>Status:</strong>
            <?php $active = isset($user['is_active']) ? (int)$user['is_active'] === 1 : true; ?>
            <span style="padding:2px 8px; border-radius:999px; border:1px solid <?= $active ? '#2e7d32' : '#b71c1c' ?>; color:<?= $active ? '#a5d6a7' : '#ef9a9a' ?>; font-size:11px;">
                <?= $active ? 'Ativo' : 'Inativo' ?>
            </span>
        </p>

        <form method="post" action="/admin/usuarios/toggle" style="margin-top:8px;">
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="value" value="<?= $active ? 0 : 1 ?>">
            <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; font-size:13px; font-weight:600; cursor:pointer; <?= $active ? 'background:#311; color:#ef9a9a; border:1px solid #b71c1c;' : 'background:linear-gradient(135deg,#2e7d32,#66bb6a); color:#050509; border:none;' ?>">
                <?= $active ? 'Desativar usuário' : 'Ativar usuário' ?>
            </button>
        </form>

        <form method="post" action="/admin/usuarios/toggle-admin" style="margin-top:8px;">
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="value" value="<?= !empty($user['is_admin']) ? 0 : 1 ?>">
            <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; font-size:13px; font-weight:600; cursor:pointer; background:#111; color:#ffcc80; border:1px solid #ffb74d;">
                <?= !empty($user['is_admin']) ? 'Remover admin' : 'Tornar admin' ?>
            </button>
        </form>
    </div>

    <div style="margin-top:18px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Dados de cobrança salvos no usuário</h2>
        <p style="font-size:12px; color:#b0b0b0; margin-bottom:8px;">Esses dados vêm direto da tabela de usuários (billing_*). Úteis para conferir o que será enviado no próximo checkout.</p>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:6px; font-size:13px;">
            <div><strong>CPF:</strong> <?= htmlspecialchars($user['billing_cpf'] ?? '') ?></div>
            <div><strong>Nascimento:</strong> <?= htmlspecialchars($user['billing_birthdate'] ?? '') ?></div>
            <div><strong>Telefone:</strong> <?= htmlspecialchars($user['billing_phone'] ?? '') ?></div>
            <div><strong>CEP:</strong> <?= htmlspecialchars($user['billing_postal_code'] ?? '') ?></div>
            <div style="grid-column:1 / -1; margin-top:4px;"><hr style="border:none; border-top:1px solid #272727;"></div>
            <div><strong>Endereço:</strong> <?= htmlspecialchars($user['billing_address'] ?? '') ?></div>
            <div><strong>Número:</strong> <?= htmlspecialchars($user['billing_address_number'] ?? '') ?></div>
            <div><strong>Complemento:</strong> <?= htmlspecialchars($user['billing_complement'] ?? '') ?></div>
            <div><strong>Bairro:</strong> <?= htmlspecialchars($user['billing_province'] ?? '') ?></div>
            <div><strong>Cidade:</strong> <?= htmlspecialchars($user['billing_city'] ?? '') ?></div>
            <div><strong>Estado:</strong> <?= htmlspecialchars($user['billing_state'] ?? '') ?></div>
        </div>
    </div>

    <div style="margin-top:18px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Memórias e regras globais</h2>
        <p style="font-size:13px; margin-bottom:6px;"><strong>Memórias globais:</strong></p>
        <div style="font-size:13px; color:#b0b0b0; white-space:pre-wrap; border-radius:8px; border:1px solid #272727; padding:8px 10px; background:#050509; min-height:40px;">
            <?= nl2br(htmlspecialchars($user['global_memory'] ?? '')) ?: '<span style="color:#555;">(vazio)</span>' ?>
        </div>
        <p style="font-size:13px; margin:10px 0 6px 0;"><strong>Regras globais:</strong></p>
        <div style="font-size:13px; color:#b0b0b0; white-space:pre-wrap; border-radius:8px; border:1px solid #272727; padding:8px 10px; background:#050509; min-height:40px;">
            <?= nl2br(htmlspecialchars($user['global_instructions'] ?? '')) ?: '<span style="color:#555;">(vazio)</span>' ?>
        </div>
    </div>

    <div style="margin-top:18px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Última assinatura</h2>
        <?php if ($subscription): ?>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Status:</strong> <?= htmlspecialchars($subscription['status']) ?></p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Plano:</strong>
                <?= htmlspecialchars($plan['name'] ?? '') ?>
                <?php if (!empty($plan['slug'])): ?>
                    <span style="font-size:11px; color:#b0b0b0;">(<?= htmlspecialchars($plan['slug']) ?>)</span>
                <?php endif; ?>
            </p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Início:</strong> <?= htmlspecialchars($subscription['started_at'] ?? $subscription['created_at'] ?? '') ?></p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>CPF:</strong> <?= htmlspecialchars($subscription['customer_cpf'] ?? '') ?></p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Telefone:</strong> <?= htmlspecialchars($subscription['customer_phone'] ?? '') ?></p>
            <p style="font-size:13px; margin-top:6px;"><strong>Endereço:</strong><br>
                <?= htmlspecialchars($subscription['customer_address'] ?? '') ?>
                <?= htmlspecialchars(' ' . ($subscription['customer_address_number'] ?? '')) ?><br>
                <?= htmlspecialchars($subscription['customer_city'] ?? '') ?> - <?= htmlspecialchars($subscription['customer_state'] ?? '') ?>
                <?= htmlspecialchars($subscription['customer_postal_code'] ?? '') ?><br>
                <?= htmlspecialchars($subscription['customer_province'] ?? '') ?>
            </p>
        <?php else: ?>
            <p style="font-size:13px; color:#b0b0b0;">Nenhuma assinatura encontrada para este usuário.</p>
        <?php endif; ?>
    </div>
</div>

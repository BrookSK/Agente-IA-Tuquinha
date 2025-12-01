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

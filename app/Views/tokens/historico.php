<?php
/** @var array $user */
/** @var array $topups */

// Determina se o usuário possui um plano pago ativo para exibir CTAs de compra
$hasPaidActivePlan = false;
if (!empty($user['email'])) {
    $subscription = \App\Models\Subscription::findLastByEmail($user['email']);
    if ($subscription && !empty($subscription['plan_id'])) {
        $plan = \App\Models\Plan::findById((int)$subscription['plan_id']);
        if ($plan) {
            $slug = (string)($plan['slug'] ?? '');
            $status = strtolower((string)($subscription['status'] ?? ''));
            if ($slug !== 'free' && in_array($status, ['active', 'pending'], true)) {
                $hasPaidActivePlan = true;
            }
        }
    }
}
?>
<div style="max-width:760px; margin:0 auto; padding:16px 8px;">
    <h1 style="font-size:22px; margin:18px 0 8px; font-weight:650;">Histórico de tokens extras</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:14px;">
        Aqui você encontra todos os pedidos de compra de tokens extras já realizados na sua conta.
    </p>

    <?php if (empty($topups)): ?>
        <div style="background:#111118; border-radius:10px; padding:10px 12px; border:1px solid #272727; font-size:13px; color:#b0b0b0;">
            Nenhum pedido de tokens extras foi encontrado para o seu usuário até agora.
        </div>
    <?php else: ?>
        <div style="border-radius:10px; border:1px solid #272727; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead style="background:#101018; color:#b0b0b0;">
                    <tr>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Data</th>
                        <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Tokens</th>
                        <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Valor</th>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topups as $t): ?>
                    <tr style="background:#050509; color:#e0e0e0;">
                        <td style="padding:7px 10px; border-bottom:1px solid #181818;">
                            <?php if (!empty($t['created_at'])): ?>
                                <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td style="padding:7px 10px; border-bottom:1px solid #181818; text-align:right;">
                            <?= number_format((int)$t['tokens'], 0, ',', '.') ?>
                        </td>
                        <td style="padding:7px 10px; border-bottom:1px solid #181818; text-align:right;">
                            R$ <?= number_format(((int)$t['amount_cents']) / 100, 2, ',', '.') ?>
                        </td>
                        <td style="padding:7px 10px; border-bottom:1px solid #181818; text-align:left;">
                            <?php
                            $status = $t['status'] ?? 'pending';
                            if ($status === 'paid') {
                                echo '<span style="color:#8bc34a;">Pago</span>';
                            } elseif ($status === 'pending') {
                                echo '<span style="color:#ffeb3b;">Pendente</span>';
                            } else {
                                echo htmlspecialchars($status);
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top:12px;">
        <a href="/conta" style="font-size:13px; color:#b0b0b0; text-decoration:none; margin-right:10px;">Voltar para minha conta</a>
        <?php if ($hasPaidActivePlan): ?>
            <a href="/tokens/comprar" style="font-size:13px; color:#ff6f60; text-decoration:none;">Comprar mais tokens extras</a>
        <?php endif; ?>
    </div>
</div>

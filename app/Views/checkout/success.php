<?php
/** @var array $plan */
$checkoutPlan = $checkoutPlan ?? $plan;
$referralFreeDays = isset($referralFreeDays) ? (int)$referralFreeDays : 0;
$nextDueDate = isset($nextDueDate) ? (string)$nextDueDate : '';
$requiresCardNow = !empty($requiresCardNow);
$cardVerification = isset($cardVerification) && is_array($cardVerification) ? $cardVerification : null;

$price = number_format($checkoutPlan['price_cents'] / 100, 2, ',', '.');

// Define rótulo do período (mês / semestre / ano) com base no sufixo do slug
$slug = (string)($checkoutPlan['slug'] ?? '');
$periodLabel = 'mês';
if (substr($slug, -11) === '-semestral') {
    $periodLabel = 'semestre';
} elseif (substr($slug, -6) === '-anual') {
    $periodLabel = 'ano';
}
?>
<div style="max-width: 720px; margin: 0 auto; text-align: center;">
    <h1 style="font-size: 26px; margin-bottom: 10px; font-weight: 650;">Assinatura criada com sucesso! 🔥</h1>
    <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
        Seu plano <strong><?= htmlspecialchars($checkoutPlan['name']) ?></strong> foi registrado. Pode levar alguns instantes para o sistema de pagamento confirmar tudo, mas você já está no caminho certo.
    </p>
    <?php if ($referralFreeDays > 0): ?>
        <p style="color: #b0b0b0; margin-bottom: 10px; font-size: 14px;">
            Você entrou com <strong><?= (int)$referralFreeDays ?> dias grátis</strong>.
            <?php if ($requiresCardNow): ?>
                O cadastro do cartão é apenas para garantir que está tudo certo com o meio de pagamento.
                <strong>Nenhuma cobrança do plano será feita agora</strong>.
            <?php else: ?>
                <strong>Nenhuma cobrança do plano será feita agora</strong>.
            <?php endif; ?>
        </p>

        <?php if ($requiresCardNow && $cardVerification && !empty($cardVerification['attempted']) && !empty($cardVerification['value'])): ?>
            <p style="color: #b0b0b0; margin-bottom: 12px; font-size: 13px;">
                Para validar o cartão, fazemos uma <strong>pré-autorização simbólica</strong> de <strong>R$ <?= number_format((float)$cardVerification['value'], 2, ',', '.') ?></strong> e o <strong>estorno é solicitado automaticamente</strong> em seguida.
                O estorno pode levar <strong>até 10 dias úteis</strong> para aparecer na fatura, dependendo do seu banco.
            </p>
        <?php endif; ?>
        <?php if ($nextDueDate !== ''): ?>
            <?php
            $nextDueBr = $nextDueDate;
            try {
                $dt = new \DateTimeImmutable($nextDueDate);
                $nextDueBr = $dt->format('d/m/Y');
            } catch (\Throwable $e) {
                $nextDueBr = $nextDueDate;
            }
            ?>
            <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
                A primeira cobrança do plano está prevista para <strong><?= htmlspecialchars($nextDueBr) ?></strong> (após o período gratuito).
            </p>
        <?php else: ?>
            <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
                A primeira cobrança do plano será feita <strong>após o período gratuito</strong>.
            </p>
        <?php endif; ?>
    <?php else: ?>
        <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
            Valor: <strong>R$ <?= $price ?>/<?= htmlspecialchars($periodLabel) ?></strong>
        </p>
    <?php endif; ?>
    <a href="/chat" style="
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 999px;
        background: linear-gradient(135deg, #e53935, #ff6f60);
        color: #050509;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
    ">
        Voltar para o chat com o Tuquinha
    </a>
</div>

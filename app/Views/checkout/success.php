<?php
/** @var array $plan */
$price = number_format($plan['price_cents'] / 100, 2, ',', '.');
?>
<div style="max-width: 720px; margin: 0 auto; text-align: center;">
    <h1 style="font-size: 26px; margin-bottom: 10px; font-weight: 650;">Assinatura criada com sucesso! 🔥</h1>
    <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
        Seu plano <strong><?= htmlspecialchars($plan['name']) ?></strong> foi registrado. Pode levar alguns instantes para o sistema de pagamento confirmar tudo, mas você já está no caminho certo.
    </p>
    <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
        Valor: <strong>R$ <?= $price ?>/mês</strong>
    </p>
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

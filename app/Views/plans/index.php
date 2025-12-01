<?php
/** @var array $plans */
/** @var array|null $currentPlan */
?>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 26px; margin-bottom: 10px; font-weight: 650;">Escolha um plano para turbinar seu acesso ao Tuquinha</h1>
    <p style="color: #b0b0b0; margin-bottom: 22px; font-size: 14px;">
        Todos os planos são cobrados mensalmente no cartão de crédito via Asaas. Começa pelo gratuito se quiser sentir o fluxo, ou vai direto pro plano que combina com o seu momento.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px;">
        <?php foreach ($plans as $plan): ?>
            <?php
                $price = number_format($plan['price_cents'] / 100, 2, ',', '.');
                $benefits = array_filter(array_map('trim', explode("\n", (string)($plan['benefits'] ?? ''))));
                $isCurrent = $currentPlan && ($currentPlan['id'] ?? null) === ($plan['id'] ?? null);
            ?>
            <div style="background: #111118; border-radius: 16px; padding: 14px; border: 1px solid <?= $isCurrent ? '#e53935' : '#272727' ?>; display: flex; flex-direction: column; justify-content: space-between; box-shadow: <?= $isCurrent ? '0 0 0 1px rgba(229,57,53,0.5)' : 'none' ?>;">
                <div>
                    <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0; margin-bottom: 6px;">
                        <?= htmlspecialchars($plan['slug']) === 'free' ? 'Plano inicial' : 'Plano premium' ?>
                        <?php if ($isCurrent): ?>
                            <span style="margin-left:6px; font-size:10px; padding:2px 6px; border-radius:999px; background:#e53935; color:#050509;">Seu plano atual</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 4px;">
                        <?= htmlspecialchars($plan['name']) ?>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 22px; font-weight: 700; color: #e53935;">R$ <?= $price ?></span>
                        <span style="font-size: 12px; color: #b0b0b0;"> / mês</span>
                    </div>
                    <?php if (!empty($plan['description'])): ?>
                        <div style="font-size: 13px; color: #c0c0c0; margin-bottom: 10px;">
                            <?= nl2br(htmlspecialchars($plan['description'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($benefits): ?>
                        <ul style="list-style: none; padding-left: 0; margin: 0; font-size: 13px; color: #c0c0c0;">
                            <?php foreach ($benefits as $b): ?>
                                <li style="display: flex; gap: 6px; margin-bottom: 4px;">
                                    <span style="color: #e53935;">✔</span>
                                    <span><?= htmlspecialchars($b) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <form action="/checkout" method="get" style="margin-top: 14px;">
                    <input type="hidden" name="plan" value="<?= htmlspecialchars($plan['slug']) ?>">
                    <button type="submit" style="
                        width: 100%;
                        border-radius: 999px;
                        border: none;
                        padding: 9px 14px;
                        background: linear-gradient(135deg, #e53935, #ff6f60);
                        color: #050509;
                        font-weight: 600;
                        font-size: 14px;
                        cursor: pointer;
                        margin-top: 4px;
                    ">
                        Assinar este plano
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

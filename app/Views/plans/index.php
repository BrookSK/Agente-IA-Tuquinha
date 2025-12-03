<?php
/** @var array $plans */
/** @var array|null $currentPlan */
/** @var int $retentionDays */
?>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 26px; margin-bottom: 10px; font-weight: 650;">Escolha um plano para turbinar seu acesso ao Tuquinha</h1>
    <p style="color: #b0b0b0; margin-bottom: 8px; font-size: 14px;">
        Os planos podem ser mensais, semestrais ou anuais, cobrados no cartão de crédito via Asaas. Começa pelo gratuito se quiser sentir o fluxo, ou vai direto pro plano que combina com o seu momento.
    </p>
    <?php
        $hasCurrentPlan = !empty($currentPlan) && is_array($currentPlan);
        $currentSlug = $hasCurrentPlan ? (string)($currentPlan['slug'] ?? '') : '';
        $monthlyLimit = $hasCurrentPlan ? (int)($currentPlan['monthly_token_limit'] ?? 0) : 0;
        $isPaidPlanWithLimit = $hasCurrentPlan && $currentSlug !== 'free' && $monthlyLimit > 0;

        // Separa plano(s) free e planos pagos por ciclo
        $freePlans = [];
        $plansByCycle = [
            'mensal' => [],
            'semestral' => [],
            'anual' => [],
        ];

        foreach ($plans as $plan) {
            $slug = (string)($plan['slug'] ?? '');
            if ($slug === 'free') {
                $freePlans[] = $plan;
                continue;
            }

            $cycleKey = 'mensal';
            $cycleLabel = 'mês';
            if (substr($slug, -11) === '-semestral') {
                $cycleKey = 'semestral';
                $cycleLabel = 'semestre';
            } elseif (substr($slug, -6) === '-anual') {
                $cycleKey = 'anual';
                $cycleLabel = 'ano';
            }

            $plan['_cycle_key'] = $cycleKey;
            $plan['_cycle_label'] = $cycleLabel;
            $plansByCycle[$cycleKey][] = $plan;
        }

        // Define ciclos disponíveis e ciclo selecionado padrão
        $availableCycles = [];
        foreach (['mensal', 'semestral', 'anual'] as $ck) {
            if (!empty($plansByCycle[$ck])) {
                $availableCycles[] = $ck;
            }
        }
        $selectedCycle = in_array('mensal', $availableCycles, true) ? 'mensal' : ($availableCycles[0] ?? 'mensal');
    ?>
    <?php if ($isPaidPlanWithLimit): ?>
        <div style="margin-bottom: 14px; padding:10px 12px; border-radius:12px; background:#111118; border:1px solid #272727; display:flex; flex-wrap:wrap; gap:8px; align-items:center; justify-content:space-between;">
            <div style="font-size:13px; color:#e0e0e0; max-width:70%;">
                Aproveite seu plano atual para ir além do limite mensal: compre <strong>tokens extras</strong> quando precisar, no modelo pré-pago.
            </div>
            <a href="/tokens/comprar" style="
                border:none;
                border-radius:999px;
                padding:7px 14px;
                background:linear-gradient(135deg,#e53935,#ff6f60);
                color:#050509;
                font-size:13px;
                font-weight:600;
                text-decoration:none;
                white-space:nowrap;
            ">
                Comprar tokens extras
            </a>
        </div>
    <?php endif; ?>
    <?php $days = (int)($retentionDays ?? 90); if ($days <= 0) { $days = 90; } ?>
    <p style="color:#777; margin-bottom: 12px; font-size: 12px;">
        <strong>Importante:</strong> o histórico de conversas é mantido por até <strong><?= htmlspecialchars((string)$days) ?> dias</strong>. Após esse período, as conversas mais antigas são removidas automaticamente dos servidores.
    </p>

    <?php if (!empty($availableCycles)): ?>
        <div style="display:flex; gap:8px; margin-bottom: 14px; flex-wrap:wrap;">
            <?php
                $cycleLabels = [
                    'mensal' => 'Mensal',
                    'semestral' => 'Semestral',
                    'anual' => 'Anual',
                ];
            ?>
            <?php foreach ($availableCycles as $ck): ?>
                <button type="button"
                        class="plans-cycle-filter<?= $ck === $selectedCycle ? ' plans-cycle-filter--active' : '' ?>"
                        data-cycle="<?= htmlspecialchars($ck) ?>"
                        style="
                            border-radius:999px;
                            border:1px solid <?= $ck === $selectedCycle ? '#e53935' : '#272727' ?>;
                            padding:6px 12px;
                            background: <?= $ck === $selectedCycle ? 'rgba(229,57,53,0.15)' : 'transparent' ?>;
                            color:#f5f5f5;
                            font-size:13px;
                            cursor:pointer;
                        ">
                    <?= htmlspecialchars($cycleLabels[$ck] ?? ucfirst($ck)) ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($freePlans)): ?>
        <div style="margin-bottom: 12px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px;">
                <?php foreach ($freePlans as $plan): ?>
                    <?php
                        $price = number_format(($plan['price_cents'] ?? 0) / 100, 2, ',', '.');
                        $benefits = array_filter(array_map('trim', explode("\n", (string)($plan['benefits'] ?? ''))));
                        $isCurrent = $currentPlan && ($currentPlan['id'] ?? null) === ($plan['id'] ?? null);
                    ?>
                    <div style="background: #111118; border-radius: 16px; padding: 14px; border: 1px solid <?= $isCurrent ? '#e53935' : '#272727' ?>; display: flex; flex-direction: column; justify-content: space-between; box-shadow: <?= $isCurrent ? '0 0 0 1px rgba(229,57,53,0.5)' : 'none' ?>;">
                        <div>
                            <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0; margin-bottom: 6px;">
                                Plano inicial
                                <?php if ($isCurrent): ?>
                                    <span style="margin-left:6px; font-size:10px; padding:2px 6px; border-radius:999px; background:#e53935; color:#050509;">Seu plano atual</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 18px; font-weight: 600; margin-bottom: 4px;">
                                <?= htmlspecialchars($plan['name']) ?>
                            </div>
                            <div style="margin-bottom: 6px;">
                                <span style="font-size: 22px; font-weight: 700; color: #e53935;">R$ <?= $price ?></span>
                                <span style="font-size: 12px; color: #b0b0b0;"> / mês</span>
                            </div>
                            <div style="font-size: 11px; color:#777; margin-bottom: 8px;">
                                Plano gratuito para experimentar o Tuquinha antes de contratar um plano pago.
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
                            <button type="submit" <?= $isCurrent ? 'disabled' : '' ?> style="
                                width: 100%;
                                border-radius: 999px;
                                border: none;
                                padding: 9px 14px;
                                background: <?= $isCurrent ? 'rgba(255,255,255,0.06)' : 'linear-gradient(135deg, #e53935, #ff6f60)' ?>;
                                color: <?= $isCurrent ? '#b0b0b0' : '#050509' ?>;
                                font-weight: 600;
                                font-size: 14px;
                                cursor: <?= $isCurrent ? 'default' : 'pointer' ?>;
                                margin-top: 4px;
                                opacity: <?= $isCurrent ? '0.7' : '1' ?>;
                            ">
                                <?= $isCurrent ? 'Plano já ativo' : 'Ativar plano gratuito' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px;" id="plans-paid-wrapper">
        <?php foreach (['mensal', 'semestral', 'anual'] as $ck): ?>
            <?php if (empty($plansByCycle[$ck])) continue; ?>
            <div class="plans-cycle-section" data-cycle-section="<?= htmlspecialchars($ck) ?>" style="display: <?= $ck === $selectedCycle ? 'block' : 'none' ?>;">
                <?php foreach ($plansByCycle[$ck] as $plan): ?>
                    <?php
                        $price = number_format(($plan['price_cents'] ?? 0) / 100, 2, ',', '.');
                        $benefits = array_filter(array_map('trim', explode("\n", (string)($plan['benefits'] ?? ''))));
                        $isCurrent = $currentPlan && ($currentPlan['id'] ?? null) === ($plan['id'] ?? null);
                        $cycleLabel = $plan['_cycle_label'] ?? 'mês';
                        $cycleKey = $plan['_cycle_key'] ?? 'mensal';
                    ?>
                    <div style="background: #111118; border-radius: 16px; padding: 14px; margin-bottom:14px; border: 1px solid <?= $isCurrent ? '#e53935' : '#272727' ?>; display: flex; flex-direction: column; justify-content: space-between; box-shadow: <?= $isCurrent ? '0 0 0 1px rgba(229,57,53,0.5)' : 'none' ?>;">
                        <div>
                            <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0; margin-bottom: 6px;">
                                Plano premium
                                <?php if ($isCurrent): ?>
                                    <span style="margin-left:6px; font-size:10px; padding:2px 6px; border-radius:999px; background:#e53935; color:#050509;">Seu plano atual</span>
                                <?php endif; ?>
                                <?php if ($cycleKey === 'anual'): ?>
                                    <span style="margin-left:6px; font-size:10px; padding:2px 6px; border-radius:999px; border:1px solid #4caf50; color:#c8e6c9;">Melhor custo-benefício</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 18px; font-weight: 600; margin-bottom: 4px;">
                                <?= htmlspecialchars($plan['name']) ?>
                            </div>
                            <div style="margin-bottom: 6px;">
                                <span style="font-size: 22px; font-weight: 700; color: #e53935;">R$ <?= $price ?></span>
                                <span style="font-size: 12px; color: #b0b0b0;"> / <?= htmlspecialchars($cycleLabel) ?></span>
                            </div>
                            <div style="font-size: 11px; color:#777; margin-bottom: 8px;">
                                <?php if ($cycleKey === 'mensal'): ?>
                                    Valor cobrado automaticamente todo mês no cartão.
                                <?php elseif ($cycleKey === 'semestral'): ?>
                                    Valor referente a cada semestre de uso. A cobrança é recorrente a cada 6 meses.
                                <?php elseif ($cycleKey === 'anual'): ?>
                                    Valor referente a cada ano de uso. A cobrança é recorrente uma vez por ano, com melhor custo-benefício.
                                <?php else: ?>
                                    Valor recorrente conforme a periodicidade configurada para este plano.
                                <?php endif; ?>
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
                            <button type="submit" <?= $isCurrent ? 'disabled' : '' ?> style="
                                width: 100%;
                                border-radius: 999px;
                                border: none;
                                padding: 9px 14px;
                                background: <?= $isCurrent ? 'rgba(255,255,255,0.06)' : 'linear-gradient(135deg, #e53935, #ff6f60)' ?>;
                                color: <?= $isCurrent ? '#b0b0b0' : '#050509' ?>;
                                font-weight: 600;
                                font-size: 14px;
                                cursor: <?= $isCurrent ? 'default' : 'pointer' ?>;
                                margin-top: 4px;
                                opacity: <?= $isCurrent ? '0.7' : '1' ?>;
                            ">
                                <?= $isCurrent ? 'Plano já ativo' : 'Assinar este plano' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        (function() {
            var buttons = document.querySelectorAll('.plans-cycle-filter');
            var sections = document.querySelectorAll('.plans-cycle-section');

            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var cycle = btn.getAttribute('data-cycle');

                    buttons.forEach(function(other) {
                        other.classList.remove('plans-cycle-filter--active');
                        other.style.borderColor = '#272727';
                        other.style.background = 'transparent';
                    });
                    btn.classList.add('plans-cycle-filter--active');
                    btn.style.borderColor = '#e53935';
                    btn.style.background = 'rgba(229,57,53,0.15)';

                    sections.forEach(function(sec) {
                        if (sec.getAttribute('data-cycle-section') === cycle) {
                            sec.style.display = 'block';
                        } else {
                            sec.style.display = 'none';
                        }
                    });
                });
            });
        })();
    </script>
</div>

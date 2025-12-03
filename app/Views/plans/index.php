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

        // Agrupa planos "irmãos" por slug base, considerando sufixos -mensal, -semestral, -anual
        $groupedPlans = [];
        foreach ($plans as $plan) {
            $slug = (string)($plan['slug'] ?? '');
            $baseSlug = $slug;
            $recurrenceKey = 'default';
            $recurrenceLabel = 'mês';

            if (substr($slug, -7) === '-mensal') {
                $baseSlug = substr($slug, 0, -7);
                $recurrenceKey = 'mensal';
                $recurrenceLabel = 'mês';
            } elseif (substr($slug, -11) === '-semestral') {
                $baseSlug = substr($slug, 0, -11);
                $recurrenceKey = 'semestral';
                $recurrenceLabel = 'semestre';
            } elseif (substr($slug, -6) === '-anual') {
                $baseSlug = substr($slug, 0, -6);
                $recurrenceKey = 'anual';
                $recurrenceLabel = 'ano';
            }

            if (!isset($groupedPlans[$baseSlug])) {
                $groupedPlans[$baseSlug] = [
                    'baseSlug' => $baseSlug,
                    'plans' => [],
                ];
            }

            $plan['_recurrence_key'] = $recurrenceKey;
            $plan['_recurrence_label'] = $recurrenceLabel;
            $groupedPlans[$baseSlug]['plans'][] = $plan;
        }
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
    <p style="color:#777; margin-bottom: 18px; font-size: 12px;">
        <strong>Importante:</strong> o histórico de conversas é mantido por até <strong><?= htmlspecialchars((string)$days) ?> dias</strong>. Após esse período, as conversas mais antigas são removidas automaticamente dos servidores.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px;">
        <?php foreach ($groupedPlans as $group): ?>
            <?php
                $plansInGroup = $group['plans'];
                // Ordena recorrências de forma previsível: mensal, semestral, anual, outros
                usort($plansInGroup, function ($a, $b) {
                    $order = ['mensal' => 1, 'semestral' => 2, 'anual' => 3, 'default' => 4];
                    $ka = $a['_recurrence_key'] ?? 'default';
                    $kb = $b['_recurrence_key'] ?? 'default';
                    $oa = $order[$ka] ?? 99;
                    $ob = $order[$kb] ?? 99;
                    if ($oa === $ob) {
                        return ($a['price_cents'] ?? 0) <=> ($b['price_cents'] ?? 0);
                    }
                    return $oa <=> $ob;
                });

                $selectedPlan = $plansInGroup[0];
                foreach ($plansInGroup as $p) {
                    if ($currentPlan && ($currentPlan['id'] ?? null) === ($p['id'] ?? null)) {
                        $selectedPlan = $p;
                        break;
                    }
                }

                $isCurrent = $currentPlan && ($currentPlan['id'] ?? null) === ($selectedPlan['id'] ?? null);
                $benefits = array_filter(array_map('trim', explode("\n", (string)($selectedPlan['benefits'] ?? ''))));
                $initialPrice = number_format($selectedPlan['price_cents'] / 100, 2, ',', '.');
                $initialRecurrenceLabel = $selectedPlan['_recurrence_label'] ?? 'mês';
                $initialRecurrenceKey = $selectedPlan['_recurrence_key'] ?? 'default';
                $isFreeGroup = count($plansInGroup) === 1 && (string)($selectedPlan['slug'] ?? '') === 'free';

                $hasAnnual = false;
                foreach ($plansInGroup as $pCheck) {
                    if (($pCheck['_recurrence_key'] ?? '') === 'anual') {
                        $hasAnnual = true;
                        break;
                    }
                }
            ?>
            <div style="background: #111118; border-radius: 16px; padding: 14px; border: 1px solid <?= $isCurrent ? '#e53935' : '#272727' ?>; display: flex; flex-direction: column; justify-content: space-between; box-shadow: <?= $isCurrent ? '0 0 0 1px rgba(229,57,53,0.5)' : 'none' ?>;">
                <div>
                    <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0; margin-bottom: 6px;">
                        <?= $isFreeGroup ? 'Plano inicial' : 'Plano premium' ?>
                        <?php if ($isCurrent): ?>
                            <span style="margin-left:6px; font-size:10px; padding:2px 6px; border-radius:999px; background:#e53935; color:#050509;">Seu plano atual</span>
                        <?php endif; ?>
                        <?php if (!$isFreeGroup && $hasAnnual): ?>
                            <span style="margin-left:6px; font-size:10px; padding:2px 6px; border-radius:999px; border:1px solid #4caf50; color:#c8e6c9;">Melhor custo-benefício no anual</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 4px;">
                        <?= htmlspecialchars($selectedPlan['name']) ?>
                    </div>

                    <?php if (!$isFreeGroup && count($plansInGroup) > 1): ?>
                        <div style="display:flex; gap:6px; margin-bottom:8px; font-size:11px;">
                            <?php foreach ($plansInGroup as $p): ?>
                                <?php
                                    $rk = $p['_recurrence_key'] ?? 'default';
                                    $label = 'Mensal';
                                    if ($rk === 'semestral') {
                                        $label = 'Semestral';
                                    } elseif ($rk === 'anual') {
                                        $label = 'Anual';
                                    } elseif ($rk === 'default') {
                                        $label = 'Mensal';
                                    }
                                    $isSelectedVariant = ($p['id'] ?? null) === ($selectedPlan['id'] ?? null);
                                ?>
                                <button type="button"
                                        class="plan-cycle-toggle"
                                        data-plan-slug="<?= htmlspecialchars($p['slug']) ?>"
                                        data-plan-price="<?= htmlspecialchars(number_format($p['price_cents'] / 100, 2, ',', '.')) ?>"
                                        data-plan-label="<?= htmlspecialchars($p['_recurrence_label'] ?? 'mês') ?>"
                                        data-plan-key="<?= htmlspecialchars($rk) ?>"
                                        style="
                                            border-radius:999px;
                                            border:1px solid <?= $isSelectedVariant ? '#e53935' : '#272727' ?>;
                                            padding:4px 8px;
                                            background: <?= $isSelectedVariant ? 'rgba(229,57,53,0.15)' : 'transparent' ?>;
                                            color:#f5f5f5;
                                            cursor:pointer;
                                        ">
                                    <?= htmlspecialchars($label) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-bottom: 6px;">
                        <span class="plan-price" style="font-size: 22px; font-weight: 700; color: #e53935;">R$ <?= $initialPrice ?></span>
                        <span class="plan-price-label" style="font-size: 12px; color: #b0b0b0;"> / <?= htmlspecialchars($initialRecurrenceLabel) ?></span>
                    </div>
                    <div class="plan-price-extra-info" style="font-size: 11px; color:#777; margin-bottom: 8px;">
                        <?php if ($initialRecurrenceKey === 'mensal'): ?>
                            Valor cobrado automaticamente todo mês no cartão.
                        <?php elseif ($initialRecurrenceKey === 'semestral'): ?>
                            Valor referente a cada semestre de uso. A cobrança é recorrente a cada 6 meses.
                        <?php elseif ($initialRecurrenceKey === 'anual'): ?>
                            Valor referente a cada ano de uso. A cobrança é recorrente uma vez por ano, com melhor custo-benefício.
                        <?php else: ?>
                            Valor recorrente conforme a periodicidade configurada para este plano.
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($selectedPlan['description'])): ?>
                        <div style="font-size: 13px; color: #c0c0c0; margin-bottom: 10px;">
                            <?= nl2br(htmlspecialchars($selectedPlan['description'])) ?>
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
                <form action="/checkout" method="get" class="plan-checkout-form" style="margin-top: 14px;">
                    <input type="hidden" name="plan" class="plan-slug-input" value="<?= htmlspecialchars($selectedPlan['slug']) ?>">
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

    <script>
        (function() {
            var cards = document.querySelectorAll('.plan-checkout-form');
            cards.forEach(function(form) {
                var container = form.closest('div');
                if (!container) return;
                var toggles = container.querySelectorAll('.plan-cycle-toggle');
                if (!toggles.length) return;

                var priceEl = container.querySelector('.plan-price');
                var labelEl = container.querySelector('.plan-price-label');
                var slugInput = form.querySelector('.plan-slug-input');
                var extraInfoEl = container.querySelector('.plan-price-extra-info');

                toggles.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var price = btn.getAttribute('data-plan-price');
                        var label = btn.getAttribute('data-plan-label');
                        var slug = btn.getAttribute('data-plan-slug');
                        var key = btn.getAttribute('data-plan-key');

                        if (priceEl) {
                            priceEl.textContent = 'R$ ' + price;
                        }
                        if (labelEl) {
                            labelEl.textContent = ' / ' + label;
                        }
                        if (slugInput) {
                            slugInput.value = slug;
                        }

                        if (extraInfoEl) {
                            if (key === 'mensal') {
                                extraInfoEl.textContent = 'Valor cobrado automaticamente todo mês no cartão.';
                            } else if (key === 'semestral') {
                                extraInfoEl.textContent = 'Valor referente a cada semestre de uso. A cobrança é recorrente a cada 6 meses.';
                            } else if (key === 'anual') {
                                extraInfoEl.textContent = 'Valor referente a cada ano de uso. A cobrança é recorrente uma vez por ano, com melhor custo-benefício.';
                            } else {
                                extraInfoEl.textContent = 'Valor recorrente conforme a periodicidade configurada para este plano.';
                            }
                        }

                        toggles.forEach(function(other) {
                            other.style.borderColor = '#272727';
                            other.style.background = 'transparent';
                        });
                        btn.style.borderColor = '#e53935';
                        btn.style.background = 'rgba(229,57,53,0.15)';
                    });
                });
            });
        })();
    </script>
</div>

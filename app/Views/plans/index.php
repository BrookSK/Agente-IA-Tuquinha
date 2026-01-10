<?php
/** @var array $plans */
/** @var array|null $currentPlan */
/** @var int $retentionDays */
/** @var bool $hasPaidActiveSubscription */
?>
<div style="max-width: 520px; margin: 0 auto; padding: 18px 14px 26px 14px;">
    <?php
        $hasCurrentPlan = !empty($currentPlan) && is_array($currentPlan);
        $currentSlug = $hasCurrentPlan ? (string)($currentPlan['slug'] ?? '') : '';
        $monthlyLimit = $hasCurrentPlan ? (int)($currentPlan['monthly_token_limit'] ?? 0) : 0;

        // Card de tokens extras só aparece se houver assinatura paga ativa com limite mensal
        $isPaidPlanWithLimit = !empty($hasPaidActiveSubscription) && $monthlyLimit > 0;

        $days = (int)($retentionDays ?? 90);
        if ($days <= 0) { $days = 90; }

        // Constrói lista na ordem vinda do banco (sort_order) e identifica destaque (Expert)
        $displayPlans = is_array($plans) ? $plans : [];

        $cycleKeyForSlug = function (string $slug): string {
            if ($slug === 'free') return 'free';
            if (substr($slug, -11) === '-semestral') return 'semestral';
            if (substr($slug, -6) === '-anual') return 'anual';
            return 'mensal';
        };

        $cycleLabelForKey = function (string $key): string {
            if ($key === 'mensal') return 'Mensal';
            if ($key === 'semestral') return 'Semestral';
            if ($key === 'anual') return 'Anual';
            return 'Todos';
        };

        $availableCycles = [];
        foreach ($displayPlans as $p) {
            $slugTmp = (string)($p['slug'] ?? '');
            $isFreeTmp = $slugTmp === 'free' || (int)($p['price_cents'] ?? 0) <= 0;
            if ($isFreeTmp) {
                continue;
            }
            $availableCycles[$cycleKeyForSlug($slugTmp)] = true;
        }

        $cycleTabs = ['todos'];
        foreach (['mensal', 'semestral', 'anual'] as $k) {
            if (!empty($availableCycles[$k])) {
                $cycleTabs[] = $k;
            }
        }
        $defaultCycleTab = in_array('mensal', $cycleTabs, true) ? 'mensal' : ($cycleTabs[1] ?? 'todos');

        $prettyCycle = function (string $slug): string {
            if (substr($slug, -11) === '-semestral') return 'sem';
            if (substr($slug, -6) === '-anual') return 'ano';
            if ($slug === 'free') return '';
            return 'mês';
        };
    ?>

    <div style="text-align:center; margin-bottom: 14px;">
        <div style="font-size: 18px; font-weight: 800; margin-bottom: 6px;">Escolha seu plano</div>
        <div style="color: var(--text-secondary); font-size: 12px; line-height: 1.45;">
            Comece com o gratuito e evolua quando seu negócio crescer.<br>
            Tudo no cartão, via Asaas.
        </div>
    </div>

    <?php if ($isPaidPlanWithLimit): ?>
        <div style="
            margin: 0 auto 16px auto;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 14px 34px rgba(0,0,0,0.42);
        ">
            <div style="font-size: 13px; font-weight: 800; margin-bottom: 6px;">Precisa de mais tokens?</div>
            <div style="color: var(--text-secondary); font-size: 12px; line-height: 1.55; margin-bottom: 10px;">
                Seu plano atual tem limite mensal. Se precisar ir além, você pode comprar tokens extras no modelo pré-pago.
            </div>
            <a href="/tokens/comprar" style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:8px;
                padding: 9px 14px;
                border-radius: 999px;
                border: 1px solid rgba(229,57,53,0.28);
                background: rgba(229,57,53,0.12);
                color: #ff6f60;
                font-weight: 700;
                font-size: 12px;
                text-decoration:none;
            ">
                Ver pacotes de tokens
            </a>
        </div>
    <?php endif; ?>

    <div style="font-size: 12px; font-weight: 800; margin: 14px 0 10px 0; opacity: 0.9;">Planos disponíveis</div>

    <div style="display:flex; justify-content:center; margin: 0 0 12px 0;">
        <div id="plan-cycle-tabs" style="display:inline-flex; gap:8px; padding:6px; border-radius:999px; border:1px solid rgba(255,255,255,0.10); background:rgba(255,255,255,0.04);">
            <?php foreach ($cycleTabs as $tabKey): ?>
                <button
                    type="button"
                    class="plan-cycle-tab"
                    data-cycle="<?= htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8') ?>"
                    style="border:none; border-radius:999px; padding:7px 10px; font-size:12px; font-weight:800; cursor:pointer; background:transparent; color: rgba(255,255,255,0.70);"
                >
                    <?= htmlspecialchars($cycleLabelForKey($tabKey), ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        (function () {
            var root = document.getElementById('plan-cycle-tabs');
            if (!root) return;

            var buttons = Array.prototype.slice.call(root.querySelectorAll('.plan-cycle-tab'));
            var cards = Array.prototype.slice.call(document.querySelectorAll('[data-plan-cycle]'));
            var defaultCycle = <?php echo json_encode($defaultCycleTab); ?>;

            function setActive(cycle) {
                buttons.forEach(function (b) {
                    var isActive = (String(b.getAttribute('data-cycle')) === String(cycle));
                    b.style.background = isActive ? 'linear-gradient(135deg,#e53935,#ff6f60)' : 'transparent';
                    b.style.color = isActive ? '#050509' : 'rgba(255,255,255,0.70)';
                });

                cards.forEach(function (c) {
                    var cardCycle = String(c.getAttribute('data-plan-cycle') || 'mensal');
                    var show = (cycle === 'todos') || (cardCycle === cycle) || (cardCycle === 'free');
                    c.style.display = show ? '' : 'none';
                });
            }

            root.addEventListener('click', function (e) {
                var btn = e.target && e.target.closest ? e.target.closest('.plan-cycle-tab') : null;
                if (!btn) return;
                var cycle = String(btn.getAttribute('data-cycle') || 'todos');
                setActive(cycle);
            });

            setActive(defaultCycle);
        })();
    </script>

    <div style="display:flex; flex-direction:column; gap: 12px;">
        <?php foreach ($displayPlans as $plan): ?>
            <?php
                $slug = (string)($plan['slug'] ?? '');
                $name = (string)($plan['name'] ?? '');
                $benefits = array_filter(array_map('trim', explode("\n", (string)($plan['benefits'] ?? ''))));
                $isCurrent = $currentPlan && ($currentPlan['id'] ?? null) === ($plan['id'] ?? null);
                $isFree = $slug === 'free' || (int)($plan['price_cents'] ?? 0) <= 0;

                $cycleKey = $cycleKeyForSlug($slug);

                $isFeatured = false;
                if (!$isFree) {
                    $isFeatured = stripos($name, 'expert') !== false || stripos($slug, 'expert') !== false;
                }

                $cycleLabel = $prettyCycle($slug);
                $priceNumber = number_format(((int)($plan['price_cents'] ?? 0)) / 100, 2, ',', '.');

                $cardBorder = $isFeatured ? 'rgba(229,57,53,0.55)' : 'rgba(255,255,255,0.08)';
                $cardShadow = $isFeatured ? '0 0 0 1px rgba(229,57,53,0.25), 0 18px 40px rgba(0,0,0,0.55)' : '0 14px 34px rgba(0,0,0,0.42)';
                $ctaBg = $isFeatured ? 'linear-gradient(135deg,#e53935,#ff6f60)' : 'rgba(255,255,255,0.06)';
                $ctaColor = $isFeatured ? '#050509' : 'rgba(255,255,255,0.85)';
                $ctaBorder = $isFeatured ? 'none' : '1px solid rgba(255,255,255,0.10)';

                if ($isCurrent) {
                    $cardBorder = 'rgba(229,57,53,0.70)';
                    $cardShadow = '0 0 0 2px rgba(229,57,53,0.22), 0 18px 44px rgba(0,0,0,0.62)';
                }

                $planIcon = '⭐';
                if ($isFree) {
                    $planIcon = '🌱';
                } elseif (stripos($slug, 'ultimate') !== false || stripos($name, 'ultimate') !== false) {
                    $planIcon = '👑';
                } elseif (stripos($slug, 'expert') !== false || stripos($name, 'expert') !== false) {
                    $planIcon = '💎';
                } elseif (stripos($slug, 'pro') !== false || stripos($name, 'pro') !== false) {
                    $planIcon = '🔥';
                }

                $iconBg = $isFeatured ? 'rgba(229,57,53,0.92)' : 'rgba(255,255,255,0.06)';
                $iconColor = $isFeatured ? '#050509' : 'rgba(255,255,255,0.92)';
            ?>
            <div data-plan-cycle="<?= htmlspecialchars($cycleKey, ENT_QUOTES, 'UTF-8') ?>" style="
                position: relative;
                background: <?= $isCurrent ? 'rgba(229,57,53,0.08)' : 'rgba(255,255,255,0.04)' ?>;
                border: 1px solid <?= $cardBorder ?>;
                border-radius: 16px;
                padding: 18px 14px 14px 14px;
                box-shadow: <?= $cardShadow ?>;
                overflow: visible;
            ">
                <?php if ($isFeatured): ?>
                    <div style="position:absolute; left:50%; top:-12px; transform:translateX(-50%);">
                        <div style="background:#e53935; color:#050509; font-size:11px; font-weight:800; padding:5px 10px; border-radius:999px; box-shadow: 0 10px 24px rgba(229,57,53,0.28);">
                            Mais popular
                        </div>
                    </div>
                <?php endif; ?>

                <div style="display:flex; align-items:center; justify-content:space-between; gap: 10px; margin-bottom: 8px;">
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <div style="
                            width: 38px;
                            height: 38px;
                            border-radius: 14px;
                            background: <?= $iconBg ?>;
                            color: <?= $iconColor ?>;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size: 16px;
                            border: 1px solid rgba(255,255,255,0.10);
                        ">
                            <?= htmlspecialchars((string)$planIcon) ?>
                        </div>
                        <div style="font-size: 13px; font-weight: 800;"><?= htmlspecialchars($name) ?></div>
                    </div>
                    <?php if ($isCurrent): ?>
                        <div style="font-size:10px; padding:3px 9px; border-radius:999px; background:linear-gradient(135deg, rgba(229,57,53,0.35), rgba(255,111,96,0.18)); border:1px solid rgba(229,57,53,0.45); color:#ffd2cd; font-weight:900;">
                            Seu plano atual
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display:flex; align-items:flex-end; gap: 8px; margin-bottom: 8px;">
                    <?php if ($isFree): ?>
                        <div style="font-size: 24px; font-weight: 900;">Grátis</div>
                    <?php else: ?>
                        <div style="font-size: 18px; font-weight: 900; color: #e53935;">R$ <?= htmlspecialchars($priceNumber) ?></div>
                        <div style="font-size: 11px; color: rgba(255,255,255,0.55); padding-bottom: 2px;">/ <?= htmlspecialchars($cycleLabel) ?></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($plan['description'])): ?>
                    <div style="color: var(--text-secondary); font-size: 12px; line-height: 1.55; margin-bottom: 10px;">
                        <?= nl2br(htmlspecialchars((string)$plan['description'])) ?>
                    </div>
                <?php endif; ?>

                <?php if ($benefits): ?>
                    <ul style="list-style: none; padding-left: 0; margin: 0 0 12px 0; font-size: 12px; color: var(--text-secondary);">
                        <?php foreach ($benefits as $b): ?>
                            <li style="display: flex; gap: 8px; margin-bottom: 6px; align-items:flex-start;">
                                <span style="color: #e53935; line-height: 1.2;">✔</span>
                                <span style="line-height: 1.35;"><?= htmlspecialchars($b) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form action="/checkout" method="get">
                    <input type="hidden" name="plan" value="<?= htmlspecialchars($slug) ?>">
                    <button type="submit" <?= $isCurrent ? 'disabled' : '' ?> style="
                        width: 100%;
                        border-radius: 12px;
                        border: <?= $isCurrent ? '1px solid rgba(255,255,255,0.10)' : $ctaBorder ?>;
                        padding: 10px 12px;
                        background: <?= $isCurrent ? 'rgba(255,255,255,0.06)' : $ctaBg ?>;
                        color: <?= $isCurrent ? 'rgba(255,255,255,0.55)' : $ctaColor ?>;
                        font-weight: 900;
                        font-size: 12px;
                        cursor: <?= $isCurrent ? 'default' : 'pointer' ?>;
                        opacity: <?= $isCurrent ? '0.7' : '1' ?>;
                    ">
                        <?php if ($isCurrent): ?>
                            Plano já ativo
                        <?php elseif ($isFree): ?>
                            Plano atual
                        <?php else: ?>
                            <?= $isFeatured ? 'Assinar Expert' : 'Assinar ' . htmlspecialchars($name) ?>
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top: 16px; color: rgba(255,255,255,0.45); font-size: 11px; line-height: 1.5;">
        Você pode cancelar a qualquer momento.
        <br>
        O histórico de conversas é mantido por até <strong><?= htmlspecialchars((string)$days) ?> dias</strong>.
    </div>
</div>

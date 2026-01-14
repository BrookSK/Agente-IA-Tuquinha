<?php
/** @var array $plans */
/** @var array|null $currentPlan */
/** @var int $retentionDays */
/** @var bool $hasPaidActiveSubscription */
?>

<style>
    #plan-cycle-tabs .plan-cycle-tab {
        line-height: 1;
    }
    body[data-theme="light"] #plan-cycle-tabs {
        background: var(--surface-card) !important;
        border-color: var(--border-subtle) !important;
    }
    body[data-theme="light"] #plan-cycle-tabs .plan-cycle-tab {
        color: rgba(15, 23, 42, 0.70) !important;
    }
    body[data-theme="light"] #plan-cycle-tabs .plan-cycle-tab.is-active {
        color: #ffffff !important;
    }

    @media (min-width: 900px) {
        #plans-wrap {
            max-width: 1200px !important;
        }
        #plans-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 14px !important;
            align-items: stretch;
        }
    }

    @media (min-width: 900px) and (max-width: 1180px) {
        #plans-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
</style>
<div id="plans-wrap" style="max-width: 520px; margin: 0 auto; padding: 18px 14px 26px 14px;">
    <?php
        $hasCurrentPlan = !empty($currentPlan) && is_array($currentPlan);
        $currentSlug = $hasCurrentPlan ? (string)($currentPlan['slug'] ?? '') : '';
        $monthlyLimit = $hasCurrentPlan ? (int)($currentPlan['monthly_token_limit'] ?? 0) : 0;

        // Card de tokens extras só aparece se houver assinatura paga ativa com limite mensal
        $isPaidPlanWithLimit = !empty($hasPaidActiveSubscription) && $monthlyLimit > 0;

        $days = (int)($retentionDays ?? 90);
        if ($days <= 0) { $days = 90; }

        // Ordena para exibição: Free primeiro, depois do mais barato pro mais caro.
        $displayPlans = is_array($plans) ? $plans : [];
        usort($displayPlans, function ($a, $b) {
            $a = is_array($a) ? $a : [];
            $b = is_array($b) ? $b : [];
            $aSlug = (string)($a['slug'] ?? '');
            $bSlug = (string)($b['slug'] ?? '');
            $aPrice = (int)($a['price_cents'] ?? 0);
            $bPrice = (int)($b['price_cents'] ?? 0);
            $aIsFree = ($aSlug === 'free') || ($aPrice <= 0);
            $bIsFree = ($bSlug === 'free') || ($bPrice <= 0);

            if ($aIsFree && !$bIsFree) return -1;
            if (!$aIsFree && $bIsFree) return 1;

            if ($aPrice === $bPrice) {
                return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
            }
            return $aPrice < $bPrice ? -1 : 1;
        });

        $cycleKeyForSlug = function (string $slug): string {
            $s = strtolower(trim($slug));
            if ($s === 'free') return 'free';

            // Detecção por sufixos mais comuns
            if (substr($s, -11) === '-semestral') return 'semestral';
            if (substr($s, -6) === '-anual') return 'anual';

            // Detecção por “contains” (slugs variam muito em bases reais)
            if (strpos($s, 'semestral') !== false || strpos($s, 'semiannual') !== false || strpos($s, 'semi-annual') !== false || strpos($s, '6m') !== false || strpos($s, '6-m') !== false) {
                return 'semestral';
            }
            if (strpos($s, 'anual') !== false || strpos($s, 'annual') !== false || strpos($s, 'year') !== false || strpos($s, '12m') !== false || strpos($s, '12-m') !== false) {
                return 'anual';
            }
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
            background: var(--surface-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 14px;
            box-shadow: var(--shadow-card);
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
        <div id="plan-cycle-tabs" style="display:inline-flex; gap:8px; padding:6px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <?php foreach ($cycleTabs as $tabKey): ?>
                <button
                    type="button"
                    class="plan-cycle-tab"
                    data-cycle="<?= htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8') ?>"
                    style="border:none; border-radius:999px; padding:7px 10px; font-size:12px; font-weight:800; cursor:pointer; background:transparent; color: var(--text-secondary);"
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

            var isLight = false;
            try {
                isLight = (document.body && document.body.getAttribute && document.body.getAttribute('data-theme') === 'light');
            } catch (e) { isLight = false; }

            var buttons = Array.prototype.slice.call(root.querySelectorAll('.plan-cycle-tab'));
            var defaultCycle = <?php echo json_encode($defaultCycleTab); ?>;

            var activeText = isLight ? '#ffffff' : '#050509';
            var inactiveText = isLight ? 'rgba(15, 23, 42, 0.70)' : 'rgba(255,255,255,0.70)';

            function setActive(cycle) {
                buttons.forEach(function (b) {
                    var isActive = (String(b.getAttribute('data-cycle')) === String(cycle));
                    if (b.classList) {
                        b.classList.toggle('is-active', isActive);
                    }
                    b.style.background = isActive ? 'linear-gradient(135deg,#e53935,#ff6f60)' : 'transparent';
                    b.style.color = isActive ? activeText : inactiveText;
                });

                var cards = Array.prototype.slice.call(document.querySelectorAll('[data-plan-cycle]'));
                cards.forEach(function (c) {
                    var cardCycle = String(c.getAttribute('data-plan-cycle') || 'mensal');
                    var show = (cycle === 'todos') || (cardCycle === cycle) || (cardCycle === 'free');
                    c.style.display = show ? '' : 'none';
                });
            }

            root.addEventListener('click', function (e) {
                var t = e && e.target ? e.target : null;
                if (t && t.nodeType === 3) {
                    t = t.parentElement;
                }
                var btn = t && t.closest ? t.closest('.plan-cycle-tab') : null;
                if (!btn) return;
                var cycle = String(btn.getAttribute('data-cycle') || 'todos');
                setActive(cycle);
            });

            setActive(defaultCycle);
        })();
    </script>

    <div id="plans-grid" style="display:flex; flex-direction:column; gap: 12px;">
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

                $cardBorder = $isFeatured ? 'rgba(229,57,53,0.55)' : 'var(--border-subtle)';
                $cardShadow = $isFeatured ? '0 0 0 1px rgba(229,57,53,0.25), ' . 'var(--shadow-card-strong)' : 'var(--shadow-card)';
                $ctaBg = $isFeatured ? 'linear-gradient(135deg,#e53935,#ff6f60)' : 'var(--surface-subtle)';
                $ctaColor = $isFeatured ? '#050509' : 'var(--text-primary)';
                $ctaBorder = $isFeatured ? 'none' : '1px solid var(--border-subtle)';

                if ($isCurrent) {
                    $cardBorder = 'rgba(229,57,53,0.70)';
                    $cardShadow = '0 0 0 2px rgba(229,57,53,0.22), ' . 'var(--shadow-card-strong)';
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

                $iconBg = $isFeatured ? 'rgba(229,57,53,0.92)' : 'var(--surface-subtle)';
                $iconColor = $isFeatured ? '#050509' : 'var(--text-primary)';
            ?>
            <div data-plan-cycle="<?= htmlspecialchars($cycleKey, ENT_QUOTES, 'UTF-8') ?>" style="
                position: relative;
                background: <?= $isCurrent ? 'rgba(229,57,53,0.08)' : 'var(--surface-card)' ?>;
                border: 1px solid <?= $isFeatured ? $cardBorder : 'var(--border-subtle)' ?>;
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
                            border: 1px solid var(--border-subtle);
                        ">
                            <?= htmlspecialchars((string)$planIcon) ?>
                        </div>
                        <div style="font-size: 13px; font-weight: 800;"><?= htmlspecialchars($name) ?></div>
                    </div>
                    <?php if ($isCurrent): ?>
                        <div style="font-size:10px; padding:3px 9px; border-radius:999px; background:rgba(229,57,53,0.12); border:1px solid rgba(229,57,53,0.28); color: var(--accent); font-weight:900;">
                            Seu plano atual
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display:flex; align-items:flex-end; gap: 8px; margin-bottom: 8px;">
                    <?php if ($isFree): ?>
                        <div style="font-size: 24px; font-weight: 900;">Grátis</div>
                    <?php else: ?>
                        <div style="font-size: 18px; font-weight: 900; color: #e53935;">R$ <?= htmlspecialchars($priceNumber) ?></div>
                        <div style="font-size: 11px; color: var(--text-secondary); padding-bottom: 2px;">/ <?= htmlspecialchars($cycleLabel) ?></div>
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
                        border: <?= $isCurrent ? '1px solid var(--border-subtle)' : $ctaBorder ?>;
                        padding: 10px 12px;
                        background: <?= $isCurrent ? 'var(--surface-subtle)' : $ctaBg ?>;
                        color: <?= $isCurrent ? 'var(--text-secondary)' : $ctaColor ?>;
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

    <div style="text-align:center; margin-top: 16px; color: var(--text-secondary); font-size: 11px; line-height: 1.5;">
        Você pode cancelar a qualquer momento.
        <br>
        O histórico de conversas é mantido por até <strong><?= htmlspecialchars((string)$days) ?> dias</strong>.
    </div>
</div>

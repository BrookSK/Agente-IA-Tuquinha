<?php

/** @var array $user */
/** @var array|null $subscription */
/** @var array|null $currentPlan */
/** @var bool $hasLimit */
/** @var float $pricePer1k */
/** @var int $tokenBalance */
/** @var string|null $error */
?>
<div style="max-width:640px; margin:0 auto; padding:0 8px;">
    <h1 style="font-size:22px; margin:18px 0 8px; font-weight:650;">Comprar tokens extras</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:14px;">
        Aqui você pode adicionar mais tokens ao seu saldo atual para continuar usando o Tuquinha mesmo depois de atingir o limite do seu plano.
    </p>

    <div style="margin-bottom:12px; padding:10px 12px; border-radius:10px; border:1px solid #272727; background:#0a0a10; font-size:13px; color:#ddd; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <div>
            <div style="font-size:12px; color:#8d8d8d;">Seu saldo atual</div>
            <div style="font-size:16px; font-weight:600;">
                <?= (int)$tokenBalance ?> tokens
            </div>
        </div>
        <?php if ($pricePer1k > 0): ?>
            <div>
                <div style="font-size:12px; color:#8d8d8d;">Preço global por 1.000 tokens extras</div>
                <div style="font-size:16px; font-weight:600;">
                    R$ <?= number_format($pricePer1k, 4, ',', '.') ?>
                </div>
            </div>
        <?php else: ?>
            <div style="font-size:12px; color:#ffbaba; max-width:320px;">
                O preço global por 1.000 tokens extras ainda não foi configurado pelo administrador. Entre em contato com o suporte.
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border-radius:10px; padding:10px 12px; color:#ffbaba; font-size:13px; margin-bottom:14px; border:1px solid #a33;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php
    $hasLimitForView = !empty($hasLimit);
    $limitValue = 0;
    if (!empty($currentPlan) && isset($currentPlan['monthly_token_limit'])) {
        $limitValue = (int)$currentPlan['monthly_token_limit'];
    }
    if ($limitValue <= 0) {
        $hasLimitForView = false;
    }
    ?>

    <?php if (!$hasLimitForView): ?>
        <div style="background:#111118; border-radius:10px; padding:10px 12px; border:1px solid #272727; font-size:13px; color:#b0b0b0; margin-bottom:18px;">
            Seu plano atual não possui limite mensal de tokens (sem limite de uso), então não é necessário comprar tokens extras.
        </div>
        <div style="margin-top:4px; display:flex; gap:8px; align-items:center;">
            <a href="/chat" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; text-decoration:none;">Voltar para o chat</a>
        </div>
    <?php elseif ($pricePer1k > 0 && $subscription): ?>
        <form action="/tokens/comprar" method="post" style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;" id="token-topup-form">
            <div>
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">
                    Quantos tokens extras você quer comprar agora?
                </label>
                <input type="number" name="tokens" id="tokens-input" min="1000" step="1000" value="1000" style="
                    width: 220px; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                    background: #050509; color: #f5f5f5; font-size: 13px;
                ">
                <div id="tokens-helper" style="font-size:11px; color:#777; margin-top:3px;">
                    Você pode repetir compras sempre que precisar.
                </div>
                <div id="tokens-total" style="font-size:12px; color:#e0e0e0; margin-top:4px;"></div>
            </div>

            <div>
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Forma de pagamento</label>
                <div style="display:flex; flex-wrap:wrap; gap:10px; font-size:13px; color:#ddd;">
                    <label style="display:flex; align-items:center; gap:5px;">
                        <input type="radio" name="billing_type" value="PIX" checked>
                        <span>PIX</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:5px;">
                        <input type="radio" name="billing_type" value="BOLETO">
                        <span>Boleto bancário</span>
                    </label>
                </div>
                <div style="font-size:11px; color:#777; margin-top:3px; max-width:420px;">
                    O pagamento é processado pelo mesmo gateway usado nas assinaturas. Assim que o pagamento for confirmado, seus tokens extras serão liberados automaticamente.
                </div>
            </div>

            <div style="margin-top:4px; display:flex; gap:8px; align-items:center;">
                <button type="submit" style="
                    border:none; border-radius:999px; padding:8px 16px;
                    background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                    font-weight:600; font-size:13px; cursor:pointer;">
                    Gerar pagamento
                </button>
                <a href="/chat" style="font-size:13px; color:#b0b0b0; text-decoration:none;">
                    Voltar para o chat
                </a>
            </div>
        </form>
    <?php elseif ($pricePer1k > 0 && !$subscription): ?>
        <div style="background:#111118; border-radius:10px; padding:10px 12px; border:1px solid #272727; font-size:13px; color:#b0b0b0;">
            Para comprar tokens extras, primeiro conclua uma assinatura de plano mensal.
            <div style="margin-top:8px;">
                <a href="/planos" style="
                    display:inline-flex; align-items:center; padding:7px 14px;
                    border-radius:999px; border:1px solid #272727; color:#f5f5f5;
                    font-size:13px; text-decoration:none;">Ver planos</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
    (function() {
        var input = document.getElementById('tokens-input');
        var totalEl = document.getElementById('tokens-total');
        <?php $priceJs = $pricePer1k > 0 ? $pricePer1k : 0; ?>
        var pricePer1k = <?= json_encode($priceJs) ?>;
        var MIN_AMOUNT_REAIS = 5.01;

        if (!input || !totalEl || !pricePer1k) return;

        function updateTotal() {
            var raw = parseInt(input.value || '0', 10);
            if (isNaN(raw) || raw <= 0) {
                raw = 1000;
            }

            var blocks = Math.ceil(raw / 1000);

            // aplica mínimo em reais
            var minBlocks = Math.ceil(MIN_AMOUNT_REAIS / pricePer1k);
            if (blocks < minBlocks) {
                blocks = minBlocks;
            }

            var tokens = blocks * 1000;
            input.value = tokens;

            var amount = (tokens / 1000) * pricePer1k;
            var formatted = amount.toFixed(2).replace('.', ',');
            totalEl.textContent = 'Valor final: R$ ' + formatted + ' para ' +
                tokens.toLocaleString('pt-BR') + ' tokens.';
        }

        input.addEventListener('change', updateTotal);
        input.addEventListener('blur', updateTotal);
        input.addEventListener('keyup', function(e) {
            if (['ArrowUp', 'ArrowDown', 'Tab'].indexOf(e.key) === -1) {
                clearTimeout(window.__tokensTimeout);
                window.__tokensTimeout = setTimeout(updateTotal, 150);
            }
        });

        updateTotal();
    })();
</script>
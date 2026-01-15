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
                    Quanto você quer investir em tokens agora?
                </label>
                <div style="display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="font-size:13px; color:#b0b0b0;">R$</div>
                            <input
                                type="text"
                                inputmode="decimal"
                                autocomplete="off"
                                name="amount_reais"
                                id="amount-input"
                                placeholder="25,00"
                                style="
                                    width: 180px; padding: 10px 12px; border-radius: 10px; border: 1px solid #272727;
                                    background: #050509; color: #f5f5f5; font-size: 16px; font-weight:650;
                                "
                            >
                        </div>
                        <div id="amount-helper" style="font-size:11px; color:#777;">
                            Mínimo por compra: <strong>R$ 25,00</strong>. Cobrança em múltiplos de 1.000 tokens.
                        </div>
                    </div>

                    <div style="
                        flex:1;
                        min-width: 220px;
                        padding: 10px 12px;
                        border-radius: 12px;
                        border: 1px solid #272727;
                        background: #0a0a10;
                        color: #ddd;
                    ">
                        <div style="font-size:11px; color:#8d8d8d; margin-bottom:4px;">Você vai receber</div>
                        <div id="tokens-preview" style="font-size:20px; font-weight:800; line-height:1.1;">— tokens</div>
                        <div id="tokens-total" style="font-size:12px; color:#bdbdbd; margin-top:6px;"></div>
                    </div>
                </div>

                <input type="hidden" name="tokens" id="tokens-hidden" value="0">
                <div id="tokens-helper" style="font-size:11px; color:#777; margin-top:3px;">
                    Dica: digite o valor que deseja pagar e veja ao lado quantos tokens serão liberados.
                </div>
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
                    <label style="display:flex; align-items:center; gap:5px;">
                        <input type="radio" name="billing_type" value="CREDIT_CARD">
                        <span>Cartão de crédito (pela tela do Asaas)</span>
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
        var amountInput = document.getElementById('amount-input');
        var tokensHidden = document.getElementById('tokens-hidden');
        var tokensPreview = document.getElementById('tokens-preview');
        var totalEl = document.getElementById('tokens-total');
        var form = document.getElementById('token-topup-form');
        <?php $priceJs = $pricePer1k > 0 ? $pricePer1k : 0; ?>
        var pricePer1k = <?= json_encode($priceJs) ?>;
        var MIN_AMOUNT_REAIS = 25.0;

        if (!amountInput || !tokensHidden || !tokensPreview || !totalEl || !pricePer1k) return;

        function parseBRL(str) {
            var s = String(str || '').trim();
            if (!s) return 0;
            // remove R$ e espaços
            s = s.replace(/R\$\s?/g, '').replace(/\s/g, '');
            // remove separador de milhar e troca vírgula por ponto
            s = s.replace(/\./g, '').replace(/,/g, '.');
            var n = parseFloat(s);
            return isNaN(n) ? 0 : n;
        }

        function formatBRL(n) {
            try {
                return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } catch (e) {
                var fixed = (Math.round(n * 100) / 100).toFixed(2);
                return fixed.replace('.', ',');
            }
        }

        function compute() {
            var amountDesired = parseBRL(amountInput.value);
            if (amountDesired <= 0) {
                tokensHidden.value = '0';
                tokensPreview.textContent = '— tokens';
                totalEl.textContent = '';
                return;
            }

            var minBlocks = Math.ceil(MIN_AMOUNT_REAIS / pricePer1k);
            var blocks = Math.ceil(amountDesired / pricePer1k);
            if (blocks < minBlocks) blocks = minBlocks;

            var tokens = blocks * 1000;
            var amountFinal = blocks * pricePer1k;

            tokensHidden.value = String(tokens);
            tokensPreview.textContent = tokens.toLocaleString('pt-BR') + ' tokens';
            totalEl.textContent = 'Cobrança: R$ ' + formatBRL(amountFinal) + ' (' + blocks + 'x 1.000 tokens)';
        }

        function enforceMinOnBlur() {
            var amountDesired = parseBRL(amountInput.value);
            if (amountDesired <= 0) return;
            if (amountDesired < MIN_AMOUNT_REAIS) {
                amountDesired = MIN_AMOUNT_REAIS;
            }
            amountInput.value = formatBRL(amountDesired);
        }

        amountInput.addEventListener('input', function () {
            // não força valor enquanto digita (evita “voltar pro mínimo”)
            compute();
        });
        amountInput.addEventListener('blur', function () {
            enforceMinOnBlur();
            compute();
        });

        if (form) {
            form.addEventListener('submit', function (e) {
                compute();
                var tokens = parseInt(tokensHidden.value || '0', 10);
                if (!tokens || tokens <= 0) {
                    e.preventDefault();
                    amountInput.focus();
                }
            });
        }

        // default
        amountInput.value = '25,00';
        compute();
    })();
</script>
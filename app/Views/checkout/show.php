<?php
/** @var array $plan */
/** @var array|null $checkoutPlan */
/** @var array $customer */
/** @var string $birthdate */
/** @var string|null $error */
/** @var bool|null $requiresCardNow */
$checkoutPlan = $checkoutPlan ?? $plan;
$price = number_format($checkoutPlan['price_cents'] / 100, 2, ',', '.');

$hasReferral = !empty($_SESSION['pending_referral'])
    && !empty($_SESSION['pending_plan_slug'])
    && (string)$_SESSION['pending_plan_slug'] === (string)($checkoutPlan['slug'] ?? '');

// Define rótulo do período (mês / semestre / ano) com base no sufixo do slug
$slug = (string)($checkoutPlan['slug'] ?? '');
$periodLabel = 'mês';
if (substr($slug, -11) === '-semestral') {
    $periodLabel = 'semestre';
} elseif (substr($slug, -6) === '-anual') {
    $periodLabel = 'ano';
}
?>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 6px; font-weight: 650;">Finalizar assinatura</h1>
    <?php $requiresCardNow = $requiresCardNow ?? true; ?>
    <?php if ($requiresCardNow): ?>
        <p style="color: #b0b0b0; margin-bottom: 6px; font-size: 14px;">
            Passo 2 de 2 &mdash; Dados do cartão.
        </p>
        <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
            <?php if ($hasReferral): ?>
                Você está finalizando o checkout do plano <strong><?= htmlspecialchars($checkoutPlan['name']) ?></strong> por <strong>R$ <?= $price ?>/<?= htmlspecialchars($periodLabel) ?></strong>, com cobrança recorrente no cartão via Asaas.
            <?php else: ?>
                Você está assinando o plano <strong><?= htmlspecialchars($checkoutPlan['name']) ?></strong> por <strong>R$ <?= $price ?>/<?= htmlspecialchars($periodLabel) ?></strong>, com cobrança recorrente no cartão via Asaas.
            <?php endif; ?>
        </p>
    <?php else: ?>
        <p style="color: #b0b0b0; margin-bottom: 6px; font-size: 14px;">
            Passo 2 de 2 &mdash; Confirme seus dados para ativar o período grátis.
        </p>
        <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
            Você está ativando o plano <strong><?= htmlspecialchars($checkoutPlan['name']) ?></strong> com um período de teste gratuito. Durante esses dias grátis não haverá cobrança imediata. Quando o teste terminar, vamos gerar a cobrança normalmente, usando os dados cadastrados depois disso.
        </p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: #3b1a1a; border-radius: 10px; padding: 10px 12px; color: #ffb3b3; font-size: 13px; margin-bottom: 14px; border: 1px solid #ff6f60;">
            <?= htmlspecialchars($error) ?><br>
            Se o problema continuar, fale com o suporte pelo <a href="/suporte" style="color:#ff6f60; text-decoration:none;">WhatsApp ou e-mail</a>.
        </div>
    <?php endif; ?>

    <form action="/checkout" method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <input type="hidden" name="plan_slug" value="<?= htmlspecialchars($checkoutPlan['slug']) ?>">
        <input type="hidden" name="step" value="2">

        <div style="grid-column: 1 / -1; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0;">Confere se está tudo certo com seus dados</div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Nome completo</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['name'] ?? '') ?>
            </div>
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">E-mail</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['email'] ?? '') ?>
            </div>
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">CPF</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['cpf'] ?? '') ?>
            </div>
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Data de nascimento</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($birthdate) ?>
            </div>
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Telefone</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['phone'] ?? '') ?>
            </div>
        </div>

        <div style="grid-column: 1 / -1; margin-top: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0;">Endereço de cobrança</div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">CEP</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['postal_code'] ?? '') ?>
            </div>
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Endereço</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['address'] ?? '') ?>, <?= htmlspecialchars($customer['address_number'] ?? '') ?>
            </div>
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Complemento</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['complement'] ?? '') ?>
            </div>
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Bairro</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['province'] ?? '') ?>
            </div>
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Cidade / UF</label>
            <div style="font-size:13px; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5;">
                <?= htmlspecialchars($customer['city'] ?? '') ?> - <?= htmlspecialchars($customer['state'] ?? '') ?>
            </div>
        </div>

        <?php if ($requiresCardNow): ?>
            <div style="grid-column: 1 / -1; margin-top: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0;">Dados do cartão</div>

            <div>
                <label style="font-size: 12px; color: #b0b0b0;">Número do cartão*</label>
                <input name="card_number" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
            </div>
            <div>
                <label style="font-size: 12px; color: #b0b0b0;">Nome impresso*</label>
                <input name="card_holder" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px; text-transform: uppercase;">
            </div>
            <div>
                <label style="font-size: 12px; color: #b0b0b0;">Mês validade (MM)*</label>
                <input name="card_exp_month" required maxlength="2" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
            </div>
            <div>
                <label style="font-size: 12px; color: #b0b0b0;">Ano validade (AAAA)*</label>
                <input name="card_exp_year" required maxlength="4" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
            </div>
            <div>
                <label style="font-size: 12px; color: #b0b0b0;">CVV*</label>
                <input name="card_cvv" required maxlength="4" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
            </div>
        <?php endif; ?>

        <div style="grid-column: 1 / -1; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
            <a href="/checkout?plan=<?= urlencode($plan['slug']) ?>" style="
                font-size: 13px; color:#b0b0b0; text-decoration:none; padding:8px 12px;
                border-radius:999px; border:1px solid #272727;
            ">
                Voltar e editar dados
            </a>
            <button type="submit" style="
                border: none;
                border-radius: 999px;
                padding: 10px 20px;
                background: linear-gradient(135deg, #e53935, #ff6f60);
                color: #050509;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
            ">
                <?= $requiresCardNow ? 'Confirmar assinatura' : 'Ativar acesso com dias grátis' ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cpfInput = document.querySelector('input[name="cpf"]');
    const phoneInput = document.querySelector('input[name="phone"]');
    const cepInput = document.querySelector('input[name="postal_code"]');
    const cardNumberInput = document.querySelector('input[name="card_number"]');
    const expMonthInput = document.querySelector('input[name="card_exp_month"]');
    const expYearInput = document.querySelector('input[name="card_exp_year"]');
    const cvvInput = document.querySelector('input[name="card_cvv"]');

    function onlyDigits(value) {
        return value.replace(/\D+/g, '');
    }

    if (cpfInput) {
        cpfInput.addEventListener('input', function () {
            let v = onlyDigits(this.value).slice(0, 11);
            if (v.length > 9) {
                v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2}).*/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                v = v.replace(/(\d{3})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
            } else if (v.length > 3) {
                v = v.replace(/(\d{3})(\d{0,3}).*/, '$1.$2');
            }
            this.value = v;
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            let v = onlyDigits(this.value).slice(0, 11);
            if (v.length > 10) {
                v = v.replace(/(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
            } else if (v.length > 6) {
                v = v.replace(/(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (v.length > 2) {
                v = v.replace(/(\d{2})(\d{0,5}).*/, '($1) $2');
            }
            this.value = v;
        });
    }

    if (cepInput) {
        cepInput.addEventListener('input', function () {
            let v = onlyDigits(this.value).slice(0, 8);
            if (v.length > 5) {
                v = v.replace(/(\d{5})(\d{0,3}).*/, '$1-$2');
            }
            this.value = v;
        });
    }

    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function () {
            let v = onlyDigits(this.value).slice(0, 16);
            v = v.replace(/(\d{4})(?=\d)/g, '$1 ');
            this.value = v.trim();
            if (onlyDigits(this.value).length === 16 && expMonthInput) {
                expMonthInput.focus();
            }
        });
    }

    if (expMonthInput) {
        expMonthInput.addEventListener('input', function () {
            let v = onlyDigits(this.value).slice(0, 2);
            this.value = v;
            if (v.length === 2 && expYearInput) {
                expYearInput.focus();
            }
        });
    }

    if (expYearInput) {
        expYearInput.addEventListener('input', function () {
            let v = onlyDigits(this.value).slice(0, 4);
            this.value = v;
            if (v.length === 4 && cvvInput) {
                cvvInput.focus();
            }
        });
    }

    if (cvvInput) {
        cvvInput.addEventListener('input', function () {
            this.value = onlyDigits(this.value).slice(0, 4);
        });
    }
});
</script>

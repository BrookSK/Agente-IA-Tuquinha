<?php
/** @var array $plan */
/** @var string|null $error */
/** @var array|null $currentUser */
/** @var array|null $savedCustomer */
$price = number_format($plan['price_cents'] / 100, 2, ',', '.');

// Define rótulo do período (mês / semestre / ano) com base no sufixo do slug
$slug = (string)($plan['slug'] ?? '');
$periodLabel = 'mês';
if (substr($slug, -11) === '-semestral') {
    $periodLabel = 'semestre';
} elseif (substr($slug, -6) === '-anual') {
    $periodLabel = 'ano';
}

// Se já existe cliente salvo na sessão, prioriza ele; senão usa dados do usuário logado (incluindo billing_*)
$prefillName = $savedCustomer['name'] ?? ($currentUser['name'] ?? '');
$prefillEmail = $savedCustomer['email'] ?? ($currentUser['email'] ?? '');
$prefillCpf = $savedCustomer['cpf'] ?? ($currentUser['billing_cpf'] ?? '');
$prefillBirthdate = $savedCustomer['birthdate'] ?? ($currentUser['billing_birthdate'] ?? '');
$prefillPhone = $savedCustomer['phone'] ?? ($currentUser['billing_phone'] ?? '');
$prefillPostalCode = $savedCustomer['postal_code'] ?? ($currentUser['billing_postal_code'] ?? '');
$prefillAddress = $savedCustomer['address'] ?? ($currentUser['billing_address'] ?? '');
$prefillAddressNumber = $savedCustomer['address_number'] ?? ($currentUser['billing_address_number'] ?? '');
$prefillComplement = $savedCustomer['complement'] ?? ($currentUser['billing_complement'] ?? '');
$prefillProvince = $savedCustomer['province'] ?? ($currentUser['billing_province'] ?? '');
$prefillCity = $savedCustomer['city'] ?? ($currentUser['billing_city'] ?? '');
$prefillState = $savedCustomer['state'] ?? ($currentUser['billing_state'] ?? '');
?>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 6px; font-weight: 650;">Finalizar assinatura</h1>
    <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
        Passo 1 de 2 &mdash; Dados pessoais. Depois vamos pedir os dados do cartão.<br>
        Você está assinando o plano <strong><?= htmlspecialchars($plan['name']) ?></strong> por <strong>R$ <?= $price ?>/<?= htmlspecialchars($periodLabel) ?></strong>.
    </p>

    <?php if (!empty($error)): ?>
        <div style="background: #3b1a1a; border-radius: 10px; padding: 10px 12px; color: #ffb3b3; font-size: 13px; margin-bottom: 14px; border: 1px solid #ff6f60;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="/checkout" method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <input type="hidden" name="plan_slug" value="<?= htmlspecialchars($plan['slug']) ?>">
        <input type="hidden" name="step" value="1">

        <div style="grid-column: 1 / -1; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0;">Dados pessoais</div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Nome completo*</label>
            <input name="name" required value="<?= htmlspecialchars($prefillName) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">E-mail*</label>
            <input name="email" type="email" required value="<?= htmlspecialchars($prefillEmail) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">CPF*</label>
            <input name="cpf" required value="<?= htmlspecialchars($prefillCpf) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Data de nascimento*</label>
            <input name="birthdate" type="date" required value="<?= htmlspecialchars($prefillBirthdate) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Telefone</label>
            <input name="phone" value="<?= htmlspecialchars($prefillPhone) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>

        <div style="grid-column: 1 / -1; margin-top: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0;">Endereço</div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">CEP*</label>
            <input name="postal_code" required value="<?= htmlspecialchars($prefillPostalCode) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Endereço*</label>
            <input name="address" required value="<?= htmlspecialchars($prefillAddress) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Número*</label>
            <input name="address_number" required value="<?= htmlspecialchars($prefillAddressNumber) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Complemento</label>
            <input name="complement" value="<?= htmlspecialchars($prefillComplement) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Bairro*</label>
            <input name="province" required value="<?= htmlspecialchars($prefillProvince) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Cidade*</label>
            <input name="city" required value="<?= htmlspecialchars($prefillCity) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Estado (UF)*</label>
            <input name="state" maxlength="2" required value="<?= htmlspecialchars($prefillState) ?>" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px; text-transform: uppercase;">
        </div>

        <div style="grid-column: 1 / -1; margin-top: 10px; display: flex; justify-content: flex-end;">
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
                Continuar para pagamento
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cpfInput = document.querySelector('input[name="cpf"]');
    const phoneInput = document.querySelector('input[name="phone"]');
    const cepInput = document.querySelector('input[name="postal_code"]');

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
});
</script>

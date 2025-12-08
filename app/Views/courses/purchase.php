<?php
/** @var array $user */
/** @var array $course */
/** @var array|null $savedCustomer */
/** @var string|null $error */

$courseTitle = trim((string)($course['title'] ?? ''));
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = number_format(max($priceCents, 0) / 100, 2, ',', '.');

$prefillName = $savedCustomer['name'] ?? ($user['name'] ?? '');
$prefillEmail = $savedCustomer['email'] ?? ($user['email'] ?? '');
$prefillCpf = $savedCustomer['cpf'] ?? ($user['billing_cpf'] ?? '');
$prefillBirthdate = $savedCustomer['birthdate'] ?? ($user['billing_birthdate'] ?? '');
$prefillPhone = $savedCustomer['phone'] ?? ($user['billing_phone'] ?? '');
$prefillPostalCode = $savedCustomer['postal_code'] ?? ($user['billing_postal_code'] ?? '');
$prefillAddress = $savedCustomer['address'] ?? ($user['billing_address'] ?? '');
$prefillAddressNumber = $savedCustomer['address_number'] ?? ($user['billing_address_number'] ?? '');
$prefillComplement = $savedCustomer['complement'] ?? ($user['billing_complement'] ?? '');
$prefillProvince = $savedCustomer['province'] ?? ($user['billing_province'] ?? '');
$prefillCity = $savedCustomer['city'] ?? ($user['billing_city'] ?? '');
$prefillState = $savedCustomer['state'] ?? ($user['billing_state'] ?? '');
?>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 6px; font-weight: 650;">Comprar curso: <?= htmlspecialchars($courseTitle) ?></h1>
    <p style="color: #b0b0b0; margin-bottom: 14px; font-size: 14px;">
        Valor da compra avulsa: <strong>R$ <?= $price ?></strong> (pagamento único, sem recorrência).<br>
        Preencha seus dados para gerar o pagamento via PIX, boleto ou cartão de crédito pelo Asaas.
    </p>

    <?php if (!empty($error)): ?>
        <div style="background: #3b1a1a; border-radius: 10px; padding: 10px 12px; color: #ffb3b3; font-size: 13px; margin-bottom: 14px; border: 1px solid #ff6f60;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="/cursos/comprar" method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">

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

        <div style="grid-column: 1 / -1; margin-top: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0;">Forma de pagamento</div>

        <div style="grid-column: 1 / -1;">
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
            <div style="font-size:11px; color:#777; margin-top:3px; max-width:520px;">
                O pagamento é processado pelo Asaas. Vamos abrir a fatura em outra aba para você concluir com segurança. Assim que o pagamento for confirmado, seu acesso ao curso será liberado automaticamente.
            </div>
        </div>

        <div style="grid-column: 1 / -1; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
            <a href="<?= CourseController::buildCourseUrl($course) ?>" style="
                font-size: 13px; color:#b0b0b0; text-decoration:none; padding:8px 12px;
                border-radius:999px; border:1px solid #272727;
            ">
                Voltar para o curso
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
                Gerar pagamento
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

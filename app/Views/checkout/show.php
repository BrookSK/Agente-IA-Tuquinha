<?php
/** @var array $plan */
/** @var string|null $error */
$price = number_format($plan['price_cents'] / 100, 2, ',', '.');
?>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 6px; font-weight: 650;">Finalizar assinatura</h1>
    <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
        Você está assinando o plano <strong><?= htmlspecialchars($plan['name']) ?></strong> por <strong>R$ <?= $price ?>/mês</strong>, com cobrança recorrente no cartão via Asaas.
    </p>

    <?php if (!empty($error)): ?>
        <div style="background: #3b1a1a; border-radius: 10px; padding: 10px 12px; color: #ffb3b3; font-size: 13px; margin-bottom: 14px; border: 1px solid #ff6f60;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="/checkout" method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <input type="hidden" name="plan_slug" value="<?= htmlspecialchars($plan['slug']) ?>">

        <div style="grid-column: 1 / -1; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0;">Dados pessoais</div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Nome completo*</label>
            <input name="name" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">E-mail*</label>
            <input name="email" type="email" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">CPF*</label>
            <input name="cpf" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Data de nascimento*</label>
            <input name="birthdate" type="date" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Telefone</label>
            <input name="phone" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>

        <div style="grid-column: 1 / -1; margin-top: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #b0b0b0;">Endereço</div>

        <div>
            <label style="font-size: 12px; color: #b0b0b0;">CEP*</label>
            <input name="postal_code" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Endereço*</label>
            <input name="address" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Número*</label>
            <input name="address_number" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Complemento</label>
            <input name="complement" style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Bairro*</label>
            <input name="province" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Cidade*</label>
            <input name="city" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px;">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Estado (UF)*</label>
            <input name="state" maxlength="2" required style="width: 100%; padding: 7px 9px; border-radius: 8px; border: 1px solid #272727; background: #050509; color: #f5f5f5; font-size: 13px; text-transform: uppercase;">
        </div>

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
                Confirmar assinatura
            </button>
        </div>
    </form>
</div>

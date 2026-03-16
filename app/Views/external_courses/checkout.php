<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */
/** @var string|null $error */

$courseTitle = trim((string)($course['title'] ?? ''));
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = number_format(max($priceCents, 0) / 100, 2, ',', '.');
?>

<h1 style="font-size:20px; font-weight:900; margin:0 0 8px 0;">Checkout: <?= htmlspecialchars($courseTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
<div class="hint" style="margin-bottom:12px;">Valor: <b>R$ <?= $price ?></b> (pagamento único via Asaas).</div>

<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
<?php endif; ?>

<form action="/curso-externo/checkout" method="post" class="grid">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

    <div style="grid-column: 1 / -1; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary);">Sua conta</div>

    <div>
        <label>Nome completo*</label>
        <input name="name" required>
    </div>
    <div>
        <label>E-mail*</label>
        <input name="email" type="email" required>
    </div>
    <div>
        <label>Senha*</label>
        <input name="password" type="password" minlength="8" required>
        <div class="hint" style="margin-top:6px;">Mínimo 8 caracteres. Enviaremos esta senha por e-mail.</div>
    </div>

    <div style="grid-column: 1 / -1; margin-top:8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary);">Dados pessoais</div>

    <div>
        <label>CPF*</label>
        <input name="cpf" required>
    </div>
    <div>
        <label>Data de nascimento*</label>
        <input name="birthdate" type="date" required>
    </div>
    <div>
        <label>Telefone</label>
        <input name="phone">
    </div>

    <div style="grid-column: 1 / -1; margin-top:8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary);">Endereço</div>

    <div>
        <label>CEP*</label>
        <input name="postal_code" required>
    </div>
    <div>
        <label>Endereço*</label>
        <input name="address" required>
    </div>
    <div>
        <label>Número*</label>
        <input name="address_number" required>
    </div>
    <div>
        <label>Complemento</label>
        <input name="complement">
    </div>
    <div>
        <label>Bairro*</label>
        <input name="province" required>
    </div>
    <div>
        <label>Cidade*</label>
        <input name="city" required>
    </div>
    <div>
        <label>Estado (UF)*</label>
        <input name="state" maxlength="2" required style="text-transform:uppercase;">
    </div>

    <div style="grid-column: 1 / -1; margin-top:8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary);">Forma de pagamento</div>

    <div style="grid-column: 1 / -1; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <label style="display:flex; align-items:center; gap:6px;">
            <input type="radio" name="billing_type" value="PIX" checked>
            <span>PIX</span>
        </label>
        <label style="display:flex; align-items:center; gap:6px;">
            <input type="radio" name="billing_type" value="BOLETO">
            <span>Boleto</span>
        </label>
        <label style="display:flex; align-items:center; gap:6px;">
            <input type="radio" name="billing_type" value="CREDIT_CARD">
            <span>Cartão de crédito</span>
        </label>
    </div>

    <div style="grid-column: 1 / -1; display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:8px;">
        <button type="submit" class="btn">Gerar pagamento</button>
        <a class="btn-outline" href="/curso-externo?token=<?= urlencode($token) ?>">Voltar</a>
    </div>
</form>

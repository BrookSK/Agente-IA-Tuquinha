<?php /** @var string|null $error */ /** @var string|null $success */ ?>
<div style="max-width: 420px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 10px;">Esqueci minha senha</h1>
    <p style="color:#b0b0b0; font-size: 14px; margin-bottom: 16px;">
        Informe o e-mail da sua conta que eu te envio um link seguro para criar uma nova senha.
    </p>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form action="/senha/esqueci" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">E-mail</label>
            <input type="email" name="email" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <button type="submit" style="margin-top:6px; width:100%; border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; cursor:pointer;">
            Enviar link de redefinição
        </button>
    </form>

    <div style="margin-top:10px; font-size:13px; color:#b0b0b0;">
        Lembrou da senha? <a href="/login" style="color:#ff6f60; text-decoration:none;">Voltar para login</a>
    </div>
</div>

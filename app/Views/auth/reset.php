<?php /** @var string $token */ /** @var string|null $error */ ?>
<div style="max-width: 420px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 10px;">Definir nova senha</h1>
    <p style="color:#b0b0b0; font-size: 14px; margin-bottom: 16px;">
        Escolha uma nova senha forte para a sua conta.
    </p>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="/senha/reset" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Nova senha</label>
            <input type="password" name="password" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Confirmar nova senha</label>
            <input type="password" name="password_confirmation" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <button type="submit" style="margin-top:6px; width:100%; border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; cursor:pointer;">
            Salvar nova senha
        </button>
    </form>
</div>

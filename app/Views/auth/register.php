<?php /** @var string|null $error */ ?>
<?php /** @var array|null $referralPlan */ ?>
<div style="max-width: 420px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 10px;">Criar conta</h1>
    <p style="color:#b0b0b0; font-size: 14px; margin-bottom: 16px;">
        Crie sua conta para assinar um plano e ter um histórico organizado com o Tuquinha.
    </p>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($referralPlan)): ?>
        <div style="background:#102312; border:1px solid #2e7d32; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:12px; margin-bottom:12px;">
            <strong>Indicação ativa:</strong>
            você está criando sua conta a partir de uma indicação para o plano
            <strong><?= htmlspecialchars($referralPlan['name'] ?? '') ?></strong>.
            Depois de confirmar o e-mail, vamos te levar direto para ativar esse plano (checkout) com as vantagens da indicação.
        </div>
    <?php endif; ?>

    <form action="/registrar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Nome</label>
            <input type="text" name="name" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">E-mail</label>
            <input type="email" name="email" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Senha</label>
            <input type="password" name="password" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Confirmar senha</label>
            <input type="password" name="password_confirmation" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <button type="submit" style="margin-top:6px; width:100%; border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; cursor:pointer;">
            Criar conta
        </button>
    </form>

    <div style="margin-top:10px; font-size:13px; color:#b0b0b0;">
        Já tem conta? <a href="/login" style="color:#ff6f60; text-decoration:none;">Entrar</a>
    </div>
</div>

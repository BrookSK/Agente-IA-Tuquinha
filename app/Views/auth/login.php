<?php /** @var string|null $error */ ?>
<?php /** @var bool|null $showVerifyLink */ ?>
<div style="max-width: 420px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 10px;">Entrar na sua conta</h1>
    <p style="color:#b0b0b0; font-size: 14px; margin-bottom: 16px;">
        Acesse para gerenciar seus planos e continuar suas conversas com o Tuquinha.
    </p>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="/login" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">E-mail</label>
            <input type="email" name="email" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Senha</label>
            <input type="password" name="password" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <button type="submit" style="margin-top:6px; width:100%; border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; cursor:pointer;">
            Entrar
        </button>
    </form>

    <?php if (!empty($showVerifyLink)): ?>
        <div style="margin-top:8px; font-size:13px; color:#b0b0b0;">
            Já recebeu o código de verificação?
            <a href="/verificar-email" style="color:#ff6f60; text-decoration:none;">Digitar código</a>
        </div>
    <?php endif; ?>

    <div style="margin-top:8px; font-size:13px; color:#b0b0b0; display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
        <span>
            Ainda não tem conta?
            <a href="/registrar" style="color:#ff6f60; text-decoration:none;">Criar conta</a>
        </span>
        <a href="/senha/esqueci" style="color:#ff6f60; text-decoration:none;">Esqueci minha senha</a>
    </div>
</div>

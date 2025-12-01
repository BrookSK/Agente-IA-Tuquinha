<?php
/** @var string|null $error */
?>
<div style="max-width: 420px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 8px; font-weight: 650;">Login do admin</h1>
    <p style="color: #b0b0b0; margin-bottom: 18px; font-size: 14px;">
        Acesse para gerenciar as configurações do sistema.
    </p>

    <?php if (!empty($error)): ?>
        <div style="background: #3b1a1a; border-radius: 10px; padding: 10px 12px; color: #ffb3b3; font-size: 13px; margin-bottom: 14px; border: 1px solid #ff6f60;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="/admin/login" method="post" style="display: flex; flex-direction: column; gap: 12px;">
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Usuário</label>
            <input name="username" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            ">
        </div>
        <div>
            <label style="font-size: 12px; color: #b0b0b0;">Senha</label>
            <input name="password" type="password" style="
                width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #272727;
                background: #050509; color: #f5f5f5; font-size: 13px;
            ">
        </div>
        <div style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 8px;">
            <a href="/" style="
                font-size: 13px; color:#b0b0b0; text-decoration:none; padding:8px 12px;
            ">Voltar</a>
            <button type="submit" style="
                border: none; border-radius: 999px; padding: 9px 18px;
                background: linear-gradient(135deg, #e53935, #ff6f60);
                color: #050509; font-weight: 600; font-size: 14px; cursor: pointer;
            ">
                Entrar
            </button>
        </div>
    </form>
</div>

<?php /** @var string|null $error */ ?>
<?php /** @var bool|null $showVerifyLink */ ?>
<div style="min-height: calc(100vh - 56px); display:flex; align-items:center; justify-content:center; padding: 22px 12px;">
    <div style="width: 100%; max-width: 380px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.08); background: rgba(17,17,24,0.78); box-shadow: 0 18px 60px rgba(0,0,0,0.6); padding: 18px 18px 16px 18px;">
        <div style="text-align:center; margin-bottom: 14px;">
            <div style="font-size: 18px; font-weight: 800;">Entrar na sua conta</div>
            <div style="color: rgba(255,255,255,0.60); font-size: 12px; margin-top: 6px; line-height: 1.45;">
                Acesse para gerenciar seus planos e acessar o Resenha 2.0.
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="post" style="display:flex; flex-direction:column; gap:12px;">
            <div>
                <label style="font-size:12px; font-weight:700; color:#f5f5f5; display:block; margin-bottom:6px;">Email</label>
                <input type="email" name="email" required placeholder="seu@email.com" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.10); background: rgba(0,0,0,0.35); color:#f5f5f5; font-size:13px; outline:none;">
            </div>
            <div>
                <label style="font-size:12px; font-weight:700; color:#f5f5f5; display:block; margin-bottom:6px;">Senha</label>
                <div style="position:relative;">
                    <input id="login-password" type="password" name="password" required placeholder="••••••••" style="width:100%; padding:10px 38px 10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.10); background: rgba(0,0,0,0.35); color:#f5f5f5; font-size:13px; outline:none;">
                    <button type="button" id="login-toggle-password" aria-label="Mostrar senha" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); border:none; background:transparent; color: rgba(255,255,255,0.55); cursor:pointer; padding:6px;">
                        <span aria-hidden="true">👁</span>
                    </button>
                </div>
            </div>

            <div style="margin-top: 2px;">
                <a href="/senha/esqueci" style="color:#ff6f60; text-decoration:none; font-size:12px; font-weight:700;">Esqueci minha senha</a>
            </div>

            <button type="submit" style="margin-top:4px; width:100%; border:none; border-radius:10px; padding:10px 14px; background:#e50914; color:#fff; font-weight:800; cursor:pointer;">
                Entrar
            </button>
        </form>

        <?php if (!empty($showVerifyLink)): ?>
            <div style="margin-top:10px; font-size:12px; color: rgba(255,255,255,0.60); text-align:center;">
                Já recebeu o código de verificação?
                <a href="/verificar-email" style="color:#ff6f60; text-decoration:none; font-weight:700;">Digitar código</a>
            </div>
        <?php endif; ?>

        <div style="margin-top:12px; font-size:12px; color: rgba(255,255,255,0.60); text-align:center;">
            Ainda não tem conta?
            <a href="/registrar" style="color:#ff6f60; text-decoration:none; font-weight:800;">Criar conta</a>
        </div>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('login-password');
        var btn = document.getElementById('login-toggle-password');
        if (!input || !btn) return;
        btn.addEventListener('click', function () {
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
        });
    })();
</script>

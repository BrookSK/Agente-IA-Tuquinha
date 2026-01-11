<?php /** @var string|null $error */ ?>
<?php /** @var array|null $referralPlan */ ?>
<div style="min-height: calc(100vh - 56px); display:flex; align-items:center; justify-content:center; padding: 22px 12px;">
    <div style="width: 100%; max-width: 380px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.08); background: rgba(17,17,24,0.78); box-shadow: 0 18px 60px rgba(0,0,0,0.6); padding: 18px 18px 16px 18px;">
        <div style="text-align:center; margin-bottom: 14px;">
            <div style="font-size: 18px; font-weight: 800;">Criar conta</div>
            <div style="color: rgba(255,255,255,0.60); font-size: 12px; margin-top: 6px; line-height: 1.45;">
                Crie sua conta para assinar um plano e acessar o Resenha 2.0.
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($referralPlan)): ?>
            <div style="background:#102312; border:1px solid #2e7d32; color:#c8ffd4; padding:8px 10px; border-radius:10px; font-size:12px; margin-bottom:12px; line-height:1.45;">
                <strong>Indicação ativa:</strong>
                você está criando sua conta a partir de uma indicação para o plano
                <strong><?= htmlspecialchars($referralPlan['name'] ?? '') ?></strong>.
                Depois de confirmar o e-mail, vamos te levar direto para ativar esse plano (checkout) com as vantagens da indicação.
            </div>
        <?php endif; ?>

        <form action="/registrar" method="post" style="display:flex; flex-direction:column; gap:12px;">
            <div>
                <label style="font-size:12px; font-weight:700; color:#f5f5f5; display:block; margin-bottom:6px;">Nome</label>
                <input type="text" name="name" required placeholder="Seu nome" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.10); background: rgba(0,0,0,0.35); color:#f5f5f5; font-size:13px; outline:none;">
            </div>
            <div>
                <label style="font-size:12px; font-weight:700; color:#f5f5f5; display:block; margin-bottom:6px;">Email</label>
                <input type="email" name="email" required placeholder="seu@email.com" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.10); background: rgba(0,0,0,0.35); color:#f5f5f5; font-size:13px; outline:none;">
            </div>
            <div>
                <label style="font-size:12px; font-weight:700; color:#f5f5f5; display:block; margin-bottom:6px;">Senha</label>
                <div style="position:relative;">
                    <input id="register-password" type="password" name="password" required placeholder="••••••••" style="width:100%; padding:10px 38px 10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.10); background: rgba(0,0,0,0.35); color:#f5f5f5; font-size:13px; outline:none;">
                    <button type="button" id="register-toggle-password" aria-label="Mostrar senha" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); border:none; background:transparent; color: rgba(255,255,255,0.55); cursor:pointer; padding:6px;">
                        <span aria-hidden="true">👁</span>
                    </button>
                </div>
            </div>
            <div>
                <label style="font-size:12px; font-weight:700; color:#f5f5f5; display:block; margin-bottom:6px;">Confirmar senha</label>
                <div style="position:relative;">
                    <input id="register-password-confirm" type="password" name="password_confirmation" required placeholder="••••••••" style="width:100%; padding:10px 38px 10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.10); background: rgba(0,0,0,0.35); color:#f5f5f5; font-size:13px; outline:none;">
                    <button type="button" id="register-toggle-password-confirm" aria-label="Mostrar senha" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); border:none; background:transparent; color: rgba(255,255,255,0.55); cursor:pointer; padding:6px;">
                        <span aria-hidden="true">👁</span>
                    </button>
                </div>
            </div>

            <button type="submit" style="margin-top:4px; width:100%; border:none; border-radius:10px; padding:10px 14px; background:#e50914; color:#fff; font-weight:800; cursor:pointer;">
                Criar conta
            </button>
        </form>

        <div style="margin-top:12px; font-size:12px; color: rgba(255,255,255,0.60); text-align:center;">
            Já tem conta?
            <a href="/login" style="color:#ff6f60; text-decoration:none; font-weight:800;">Entrar</a>
        </div>
    </div>
</div>

<script>
    (function () {
        var p1 = document.getElementById('register-password');
        var b1 = document.getElementById('register-toggle-password');
        if (p1 && b1) {
            b1.addEventListener('click', function () {
                var isPassword = p1.type === 'password';
                p1.type = isPassword ? 'text' : 'password';
                b1.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
            });
        }

        var p2 = document.getElementById('register-password-confirm');
        var b2 = document.getElementById('register-toggle-password-confirm');
        if (p2 && b2) {
            b2.addEventListener('click', function () {
                var isPassword = p2.type === 'password';
                p2.type = isPassword ? 'text' : 'password';
                b2.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
            });
        }
    })();
</script>

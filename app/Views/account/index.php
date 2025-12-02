<?php
/** @var array $user */
/** @var array|null $subscription */
/** @var array|null $plan */
/** @var string|null $error */
/** @var string|null $success */
/** @var string|null $cardLast4 */
/** @var string|null $subscriptionStart */
/** @var string|null $subscriptionNext */

$isFreePlan = empty($plan) || (($plan['slug'] ?? 'free') === 'free');
$freeGlobalLimit = (int)\App\Models\Setting::get('free_memory_global_chars', '500');
if ($freeGlobalLimit <= 0) { $freeGlobalLimit = 500; }
$freeChatLimit = (int)\App\Models\Setting::get('free_memory_chat_chars', '400');
if ($freeChatLimit <= 0) { $freeChatLimit = 400; }
?>
<style>
@media (max-width: 768px) {
    .account-grid {
        display: flex !important;
        flex-direction: column;
        gap: 16px;
    }
}
</style>
<div class="account-grid" style="max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1.5fr); gap: 16px; align-items: flex-start;">
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <div style="background:#111118; border-radius:16px; padding:14px; border:1px solid #272727;">
            <h2 style="font-size:18px; margin-bottom:8px;">Dados da conta</h2>
            <p style="font-size:13px; color:#b0b0b0; margin-bottom:10px;">Ajuste como o Tuquinha te chama e confira seu e-mail de acesso.</p>

            <?php if (!empty($error)): ?>
                <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="/conta" method="post" style="display:flex; flex-direction:column; gap:10px;">
                <div>
                    <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Nome</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
                </div>
                <div>
                    <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Como o Tuquinha deve te chamar?</label>
                    <input type="text" name="preferred_name" value="<?= htmlspecialchars($user['preferred_name'] ?? '') ?>" placeholder="Opcional. Ex: Rafa, Dr. João, você decide." style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
                    <div style="font-size:11px; color:#8d8d8d; margin-top:3px;">Se preencher, o Tuquinha vai usar esse nome nas respostas.</div>
                </div>
                <div>
                    <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">E-mail</label>
                    <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#777; font-size:14px;">
                </div>
                <div style="font-size:12px; color:#b0b0b0;">
                    <?php if (!empty($user['email_verified_at'])): ?>
                        <span style="color:#8bc34a; font-weight:500;">E-mail verificado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($user['email_verified_at']))) ?></span>
                    <?php else: ?>
                        <span style="color:#ffb74d; font-weight:500;">E-mail ainda não verificado.</span>
                        <a href="/verificar-email" style="margin-left:6px; color:#ff6f60; text-decoration:none;">Verificar agora</a>
                    <?php endif; ?>
                </div>
                <div>
                    <label style="font-size:13px; color:#ddd; display:block; margin:10px 0 4px;">Memórias globais sobre você</label>
                    <textarea name="global_memory" rows="3" placeholder="Opcional. Ex: tipo de cliente que você atende, nível de experiência, nicho favorito, informações que o Tuquinha não precisa perguntar toda hora." style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical; min-height:70px;"><?= htmlspecialchars($user['global_memory'] ?? '') ?></textarea>
                </div>
                <div>
                    <label style="font-size:13px; color:#ddd; display:block; margin:6px 0 4px;">Regras globais para o Tuquinha</label>
                    <textarea name="global_instructions" rows="3" placeholder="Opcional. Ex: sempre responder mais direto, evitar certos temas, foco em resultado prático, etc." style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical; min-height:70px;"><?= htmlspecialchars($user['global_instructions'] ?? '') ?></textarea>
                </div>
                <?php if ($isFreePlan): ?>
                    <div style="font-size:11px; color:#8d8d8d; margin-top:4px;">
                        No plano Free o Tuquinha considera até <?= htmlspecialchars((string)$freeGlobalLimit) ?> caracteres das memórias/regras globais. Para textos maiores, apenas o início será usado.
                    </div>
                <?php endif; ?>
                <button type="submit" style="margin-top:6px; align-self:flex-start; border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; cursor:pointer; font-size:13px;">
                    Salvar dados
                </button>
            </form>
        </div>

        <div style="background:#111118; border-radius:16px; padding:14px; border:1px solid #272727;">
            <h2 style="font-size:18px; margin-bottom:8px;">Alterar senha</h2>
            <p style="font-size:13px; color:#b0b0b0; margin-bottom:10px;">Reforce a segurança da sua conta sempre que sentir necessidade.</p>

            <form action="/conta/senha" method="post" style="display:flex; flex-direction:column; gap:10px;">
                <div>
                    <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Senha atual</label>
                    <input type="password" name="current_password" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
                </div>
                <div>
                    <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Nova senha</label>
                    <input type="password" name="new_password" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
                </div>
                <div>
                    <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Confirmar nova senha</label>
                    <input type="password" name="new_password_confirmation" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
                </div>
                <button type="submit" style="margin-top:6px; align-self:flex-start; border:none; border-radius:999px; padding:8px 14px; background:#111118; color:#f5f5f5; font-weight:500; cursor:pointer; font-size:13px; border:1px solid #272727;">
                    Atualizar senha
                </button>
            </form>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px;">
        <div style="background:#111118; border-radius:16px; padding:14px; border:1px solid #272727;">
            <h2 style="font-size:18px; margin-bottom:8px;">Plano atual</h2>
            <?php if ($plan && $subscription): ?>
                <div style="font-size:14px; margin-bottom:6px; font-weight:600;">
                    <?= htmlspecialchars($plan['name']) ?>
                </div>
                <div style="font-size:13px; color:#b0b0b0; margin-bottom:6px;">
                    Status: <strong><?= htmlspecialchars($subscription['status']) ?></strong>
                </div>
                <div style="font-size:12px; color:#b0b0b0; margin-bottom:6px;">
                    Assinatura feita para <strong><?= htmlspecialchars($subscription['customer_name']) ?></strong><br>
                    E-mail: <?= htmlspecialchars($subscription['customer_email']) ?>
                </div>
                <?php if (!empty($cardLast4)): ?>
                    <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px;">
                        Cartão usado: final <strong><?= htmlspecialchars($cardLast4) ?></strong>
                    </div>
                <?php endif; ?>
                <?php if (!empty($subscriptionStart)): ?>
                    <div style="font-size:12px; color:#b0b0b0;">
                        Contratado em: <strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($subscriptionStart))) ?></strong>
                    </div>
                <?php endif; ?>
                <?php if (!empty($subscriptionNext)): ?>
                    <div style="font-size:12px; color:#b0b0b0;">
                        Próxima renovação prevista: <strong><?= htmlspecialchars(date('d/m/Y', strtotime($subscriptionNext))) ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (in_array($subscription['status'], ['active', 'pending'], true)): ?>
                    <?php
                    $nextDisplay = '';
                    if (!empty($subscriptionNext)) {
                        $nextDisplay = date('d/m/Y', strtotime($subscriptionNext));
                    }
                    ?>
                    <form id="cancel-subscription-form" action="/conta/assinatura/cancelar" method="post" style="margin-top:10px;">
                        <button type="submit" data-plan-name="<?= htmlspecialchars($plan['name']) ?>" data-next-date="<?= htmlspecialchars($nextDisplay) ?>" style="border:none; border-radius:999px; padding:6px 12px; font-size:12px; cursor:pointer; background:#311; color:#ffbaba; border:1px solid #a33;">
                            Cancelar assinatura
                        </button>
                        <div style="margin-top:4px; font-size:11px; color:#b0b0b0; max-width:260px;">
                            O cancelamento interrompe novas cobranças, mas o acesso pode continuar até o fim do ciclo já pago, conforme regras do cartão.
                        </div>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p style="font-size:13px; color:#b0b0b0;">
                    Você ainda não tem uma assinatura ativa. Por enquanto está usando o plano Free padrão.
                </p>
                <a href="/planos" style="display:inline-flex; margin-top:8px; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:13px; font-weight:600; text-decoration:none;">
                    Ver planos disponíveis
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="max-width: 900px; margin: 16px auto 0 auto; font-size: 12px; color: #8d8d8d; text-align: right;">
    Precisa de ajuda com sua assinatura ou acesso?
    <a href="/suporte" style="color: #ff6f60; text-decoration: none;">Fale com o suporte</a>.
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('cancel-subscription-form');
    if (!form) return;

    form.addEventListener('submit', function (ev) {
        var btn = form.querySelector('button[type="submit"]');
        var plan = btn ? (btn.getAttribute('data-plan-name') || 'seu plano atual') : 'seu plano atual';
        var nextDate = btn ? (btn.getAttribute('data-next-date') || '') : '';

        var msg = 'Tem certeza que deseja cancelar o plano ' + plan + '?\n\n';
        msg += 'Ao cancelar, você perde os benefícios do plano pago, como mais mensagens, prioridade de uso e outras vantagens exclusivas. ';
        if (nextDate) {
            msg += '\nSua assinatura deve continuar válida até ' + nextDate + ' e depois disso não haverá novas cobranças.\n\n';
        } else {
            msg += '\nDepois do cancelamento, você pode manter o acesso apenas até o fim do ciclo já pago, dependendo do meio de pagamento.\n\n';
        }
        msg += 'Essa ação pode levar alguns minutos para refletir em todos os sistemas.';

        if (!confirm(msg)) {
            ev.preventDefault();
        }
    });
});
</script>

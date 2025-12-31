<?php /** @var array $user */ ?>
<?php /** @var array|null $subscription */ ?>
<?php /** @var array|null $plan */ ?>
<?php /** @var array $timeline */ ?>
<?php /** @var array|null $coursePartner */ ?>
<?php /** @var array|null $asaasSub */ ?>
<?php /** @var int|null $subscriptionAmountCents */ ?>
<?php /** @var int|null $trialDays */ ?>
<?php /** @var string|null $trialEndsAt */ ?>
<?php /** @var string|null $paidStartsAt */ ?>
<?php /** @var array|null $referral */ ?>

<div style="max-width: 800px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 16px;">Detalhes do usuário</h1>

    <a href="/admin/usuarios" style="font-size:12px; color:#ff6f60; text-decoration:none;">⟵ Voltar para lista</a>

    <?php if (!empty($error)): ?>
        <div style="margin-top:12px; background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:10px; font-size:13px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div style="margin-top:12px; background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:10px; font-size:13px;">
            <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php $isProfessor = !empty($coursePartner); ?>

    <div style="margin-top:16px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Dados básicos</h2>
        <p style="font-size:13px; margin-bottom:4px;"><strong>Nome:</strong> <?= htmlspecialchars($user['name']) ?></p>
        <p style="font-size:13px; margin-bottom:4px;"><strong>E-mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p style="font-size:13px; margin-bottom:4px;"><strong>Admin:</strong> <?= !empty($user['is_admin']) ? 'Sim' : 'Não' ?></p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Professor/parceiro de cursos:</strong> <?= $isProfessor ? 'Sim' : 'Não' ?></p>
        <p style="font-size:13px; margin-bottom:8px;">
            <strong>Status:</strong>
            <?php $active = isset($user['is_active']) ? (int)$user['is_active'] === 1 : true; ?>
            <span style="padding:2px 8px; border-radius:999px; border:1px solid <?= $active ? '#2e7d32' : '#b71c1c' ?>; color:<?= $active ? '#a5d6a7' : '#ef9a9a' ?>; font-size:11px;">
                <?= $active ? 'Ativo' : 'Inativo' ?>
            </span>
        </p>

        <form method="post" action="/admin/usuarios/toggle" style="margin-top:8px;">
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="value" value="<?= $active ? 0 : 1 ?>">
            <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; font-size:13px; font-weight:600; cursor:pointer; <?= $active ? 'background:#311; color:#ef9a9a; border:1px solid #b71c1c;' : 'background:linear-gradient(135deg,#2e7d32,#66bb6a); color:#050509; border:none;' ?>">
                <?= $active ? 'Desativar usuário' : 'Ativar usuário' ?>
            </button>
        </form>

        <form method="post" action="/admin/usuarios/toggle-admin" style="margin-top:8px;">
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="value" value="<?= !empty($user['is_admin']) ? 0 : 1 ?>">
            <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; font-size:13px; font-weight:600; cursor:pointer; background:#111; color:#ffcc80; border:1px solid #ffb74d;">
                <?= !empty($user['is_admin']) ? 'Remover admin' : 'Tornar admin' ?>
            </button>
        </form>

        <form method="post" action="/admin/usuarios/toggle-professor" style="margin-top:8px;">
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="value" value="<?= $isProfessor ? 0 : 1 ?>">
            <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; font-size:13px; font-weight:600; cursor:pointer; background:#050509; color:#f5f5f5; border:1px solid #272727;">
                <?= $isProfessor ? 'Remover tag de professor/parceiro' : 'Marcar como professor/parceiro' ?>
            </button>
        </form>
    </div>

    <div style="margin-top:18px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Tokens</h2>
        <?php $tokenBalance = isset($tokenBalance) ? (int)$tokenBalance : (int)($user['token_balance'] ?? 0); ?>
        <p style="font-size:13px; margin-bottom:10px;"><strong>Saldo atual:</strong> <?= (int)$tokenBalance ?> token(s)</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px;">
            <div style="border:1px solid #272727; border-radius:12px; padding:10px 12px; background:#050509;">
                <h3 style="font-size:14px; margin:0 0 8px 0;">Dar tokens</h3>
                <form method="post" action="/admin/usuarios/tokens/adicionar" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="user_id" value="<?= (int)($user['id'] ?? 0) ?>">
                    <input type="number" name="amount" min="1" step="1" placeholder="Quantidade" required style="flex:1; min-width:140px; padding:6px 10px; border-radius:999px; border:1px solid #272727; background:#111118; color:#f5f5f5; font-size:13px;">
                    <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#2e7d32,#66bb6a); color:#050509; font-size:13px; font-weight:600; cursor:pointer;">Dar</button>
                </form>
            </div>

            <div style="border:1px solid #272727; border-radius:12px; padding:10px 12px; background:#050509;">
                <h3 style="font-size:14px; margin:0 0 8px 0;">Remover tokens</h3>
                <div style="font-size:12px; color:#b0b0b0; margin-bottom:8px;">Você só pode remover até o saldo atual (<?= (int)$tokenBalance ?>).</div>
                <form method="post" action="/admin/usuarios/tokens/remover" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="user_id" value="<?= (int)($user['id'] ?? 0) ?>">
                    <input type="number" name="amount" min="1" max="<?= (int)$tokenBalance ?>" step="1" placeholder="Quantidade" required style="flex:1; min-width:140px; padding:6px 10px; border-radius:999px; border:1px solid #272727; background:#111118; color:#f5f5f5; font-size:13px;">
                    <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:#311; color:#ef9a9a; border:1px solid #b71c1c; font-size:13px; font-weight:600; cursor:pointer;">Remover</button>
                </form>
            </div>
        </div>
    </div>

    <div style="margin-top:18px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Dados de cobrança salvos no usuário</h2>
        <p style="font-size:12px; color:#b0b0b0; margin-bottom:8px;">Esses dados vêm direto da tabela de usuários (billing_*). Úteis para conferir o que será enviado no próximo checkout.</p>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:6px; font-size:13px;">
            <div><strong>CPF:</strong> <?= htmlspecialchars($user['billing_cpf'] ?? '') ?></div>
            <div><strong>Nascimento:</strong> <?= htmlspecialchars($user['billing_birthdate'] ?? '') ?></div>
            <div><strong>Telefone:</strong> <?= htmlspecialchars($user['billing_phone'] ?? '') ?></div>
            <div><strong>CEP:</strong> <?= htmlspecialchars($user['billing_postal_code'] ?? '') ?></div>
            <div style="grid-column:1 / -1; margin-top:4px;"><hr style="border:none; border-top:1px solid #272727;"></div>
            <div><strong>Endereço:</strong> <?= htmlspecialchars($user['billing_address'] ?? '') ?></div>
            <div><strong>Número:</strong> <?= htmlspecialchars($user['billing_address_number'] ?? '') ?></div>
            <div><strong>Complemento:</strong> <?= htmlspecialchars($user['billing_complement'] ?? '') ?></div>
            <div><strong>Bairro:</strong> <?= htmlspecialchars($user['billing_province'] ?? '') ?></div>
            <div><strong>Cidade:</strong> <?= htmlspecialchars($user['billing_city'] ?? '') ?></div>
            <div><strong>Estado:</strong> <?= htmlspecialchars($user['billing_state'] ?? '') ?></div>
        </div>
    </div>

    <div style="margin-top:18px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Memórias e regras globais</h2>
        <p style="font-size:13px; margin-bottom:6px;"><strong>Memórias globais:</strong></p>
        <div style="font-size:13px; color:#b0b0b0; white-space:pre-wrap; border-radius:8px; border:1px solid #272727; padding:8px 10px; background:#050509; min-height:40px;">
            <?= nl2br(htmlspecialchars($user['global_memory'] ?? '')) ?: '<span style="color:#555;">(vazio)</span>' ?>
        </div>
        <p style="font-size:13px; margin:10px 0 6px 0;"><strong>Regras globais:</strong></p>
        <div style="font-size:13px; color:#b0b0b0; white-space:pre-wrap; border-radius:8px; border:1px solid #272727; padding:8px 10px; background:#050509; min-height:40px;">
            <?= nl2br(htmlspecialchars($user['global_instructions'] ?? '')) ?: '<span style="color:#555;">(vazio)</span>' ?>
        </div>
    </div>

    <div style="margin-top:18px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Última assinatura</h2>
        <?php if ($subscription): ?>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Status:</strong> <?= htmlspecialchars($subscription['status']) ?></p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Plano:</strong>
                <?= htmlspecialchars($plan['name'] ?? '') ?>
                <?php if (!empty($plan['slug'])): ?>
                    <span style="font-size:11px; color:#b0b0b0;">(<?= htmlspecialchars($plan['slug']) ?>)</span>
                <?php endif; ?>
            </p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Início da assinatura:</strong> <?= htmlspecialchars($subscription['started_at'] ?? $subscription['created_at'] ?? '') ?></p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Último pagamento:</strong> <?= htmlspecialchars($subscription['started_at'] ?? $subscription['created_at'] ?? '') ?></p>
            <?php
                $subscriptionAmountCents = isset($subscriptionAmountCents) ? (int)$subscriptionAmountCents : 0;
                $subscriptionAmountFormatted = $subscriptionAmountCents > 0 ? number_format($subscriptionAmountCents / 100, 2, ',', '.') : '';
            ?>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Valor (plano):</strong>
                <?= $subscriptionAmountFormatted !== '' ? ('R$ ' . htmlspecialchars($subscriptionAmountFormatted)) : '<span style="color:#b0b0b0;">(não disponível)</span>' ?>
            </p>

            <?php if (!empty($trialDays) && !empty($trialEndsAt) && !empty($paidStartsAt)): ?>
                <p style="font-size:13px; margin-bottom:4px;"><strong>Período grátis:</strong> <?= (int)$trialDays ?> dia(s)</p>
                <p style="font-size:13px; margin-bottom:4px;"><strong>Fim do grátis:</strong> <?= htmlspecialchars((string)$trialEndsAt) ?></p>
                <p style="font-size:13px; margin-bottom:4px;"><strong>Início do pago:</strong> <?= htmlspecialchars((string)$paidStartsAt) ?></p>
            <?php endif; ?>

            <?php if (!empty($referral) && is_array($referral)): ?>
                <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #272727;">
                    <div style="font-size:12px; color:#b0b0b0; margin-bottom:6px;">Indicação</div>
                    <p style="font-size:13px; margin-bottom:4px;"><strong>Veio por indicação:</strong> Sim</p>
                    <p style="font-size:13px; margin-bottom:4px;"><strong>Status:</strong> <?= htmlspecialchars((string)($referral['status'] ?? '')) ?></p>
                    <p style="font-size:13px; margin-bottom:4px;"><strong>Data:</strong> <?= htmlspecialchars((string)($referral['created_at'] ?? '')) ?></p>
                    <p style="font-size:13px; margin-bottom:4px;"><strong>Concluída em:</strong> <?= htmlspecialchars((string)($referral['completed_at'] ?? '')) ?></p>
                    <p style="font-size:13px; margin-bottom:0;"><strong>Quem indicou (user_id):</strong> <?= (int)($referral['referrer_user_id'] ?? 0) ?></p>
                </div>
            <?php else: ?>
                <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #272727;">
                    <div style="font-size:12px; color:#b0b0b0; margin-bottom:6px;">Indicação</div>
                    <p style="font-size:13px; margin:0;"><strong>Veio por indicação:</strong> Não</p>
                </div>
            <?php endif; ?>
            <p style="font-size:13px; margin-bottom:4px;"><strong>CPF:</strong> <?= htmlspecialchars($subscription['customer_cpf'] ?? '') ?></p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Telefone:</strong> <?= htmlspecialchars($subscription['customer_phone'] ?? '') ?></p>
            <p style="font-size:13px; margin-top:6px;"><strong>Endereço:</strong><br>
                <?= htmlspecialchars($subscription['customer_address'] ?? '') ?>
                <?= htmlspecialchars(' ' . ($subscription['customer_address_number'] ?? '')) ?><br>
                <?= htmlspecialchars($subscription['customer_city'] ?? '') ?> - <?= htmlspecialchars($subscription['customer_state'] ?? '') ?>
                <?= htmlspecialchars($subscription['customer_postal_code'] ?? '') ?><br>
                <?= htmlspecialchars($subscription['customer_province'] ?? '') ?>
            </p>
        <?php else: ?>
            <p style="font-size:13px; color:#b0b0b0;">Nenhuma assinatura encontrada para este usuário.</p>
        <?php endif; ?>
    </div>

    <div style="margin-top:18px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:10px;">Histórico de planos e créditos de tokens</h2>
        <p style="font-size:12px; color:#b0b0b0; margin-bottom:8px;">Linha do tempo combinando mudanças de plano (assinaturas) e compras avulsas de tokens desse usuário.</p>

        <?php if (!empty($timeline)): ?>
            <div style="border-left:2px solid #272727; margin-left:6px; padding-left:10px; display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($timeline as $item): ?>
                    <?php
                    $type = $item['type'] ?? '';
                    $raw = $item['raw'] ?? [];
                    $date = $item['date'] ?? '';
                    ?>
                    <div style="position:relative;">
                        <?php
                            $dot = '#64b5f6';
                            if ($type === 'subscription') { $dot = '#ff6f60'; }
                            elseif ($type === 'token_tx') { $dot = '#ffcc80'; }
                            elseif ($type === 'referral') { $dot = '#ba68c8'; }
                            elseif ($type === 'trial_end') { $dot = '#8bc34a'; }
                        ?>
                        <div style="position:absolute; left:-12px; top:4px; width:8px; height:8px; border-radius:50%; background:<?= $dot ?>;"></div>
                        <div style="padding:6px 8px; border-radius:10px; background:#050509; border:1px solid #272727; font-size:13px;">
                            <div style="display:flex; justify-content:space-between; gap:10px; margin-bottom:4px;">
                                <span style="font-weight:600;">
                                    <?php if ($type === 'subscription'): ?>
                                        Mudança de plano / Assinatura
                                    <?php elseif ($type === 'topup'): ?>
                                        Crédito de tokens avulsos
                                    <?php elseif ($type === 'token_tx'): ?>
                                        Bônus de tokens (indicação)
                                    <?php elseif ($type === 'referral'): ?>
                                        Indicação registrada
                                    <?php elseif ($type === 'trial_end'): ?>
                                        Fim do grátis / início do pago
                                    <?php else: ?>
                                        Evento
                                    <?php endif; ?>
                                </span>
                                <span style="font-size:11px; color:#b0b0b0;">
                                    <?= htmlspecialchars($date ?: '') ?>
                                </span>
                            </div>

                            <?php if ($type === 'subscription'): ?>
                                <div style="font-size:12px; color:#cccccc;">
                                    <div><strong>Plano:</strong> <?= htmlspecialchars($raw['plan_name'] ?? '') ?> <?php if (!empty($raw['plan_slug'])): ?><span style="font-size:11px; color:#b0b0b0;">(<?= htmlspecialchars($raw['plan_slug']) ?>)</span><?php endif; ?></div>
                                    <div><strong>Status:</strong> <?= htmlspecialchars($raw['status'] ?? '') ?></div>
                                    <div><strong>Início:</strong> <?= htmlspecialchars($raw['started_at'] ?? $raw['created_at'] ?? '') ?></div>
                                </div>
                            <?php elseif ($type === 'topup'): ?>
                                <?php
                                $amountCents = (int)($raw['amount_cents'] ?? 0);
                                $amountFormatted = number_format($amountCents / 100, 2, ',', '.');
                                ?>
                                <div style="font-size:12px; color:#cccccc;">
                                    <div><strong>Tokens:</strong> <?= htmlspecialchars((string)($raw['tokens'] ?? '')) ?></div>
                                    <div><strong>Valor:</strong> R$ <?= htmlspecialchars($amountFormatted) ?></div>
                                    <div><strong>Status:</strong> <?= htmlspecialchars($raw['status'] ?? '') ?></div>
                                    <div><strong>Pago em:</strong> <?= htmlspecialchars($raw['paid_at'] ?? '') ?></div>
                                </div>
                            <?php elseif ($type === 'token_tx'): ?>
                                <?php
                                    $amount = (int)($raw['amount'] ?? 0);
                                    $reason = (string)($raw['reason'] ?? '');
                                    $label = 'Movimentação de tokens';
                                    if (in_array($reason, ['referral_friend_bonus', 'referral_referrer_bonus'], true)) {
                                        $label = 'Bônus de tokens (indicação)';
                                    } elseif ($reason === 'admin_grant') {
                                        $label = 'Tokens adicionados pelo admin';
                                    } elseif ($reason === 'admin_revoke') {
                                        $label = 'Tokens removidos pelo admin';
                                    }
                                ?>
                                <div style="font-size:12px; color:#cccccc;">
                                    <div><strong>Tipo:</strong> <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div><strong>Tokens:</strong> <?= htmlspecialchars((string)$amount) ?></div>
                                    <div><strong>Motivo:</strong> <?= htmlspecialchars($reason) ?></div>
                                </div>
                            <?php elseif ($type === 'referral'): ?>
                                <div style="font-size:12px; color:#cccccc;">
                                    <div><strong>Status:</strong> <?= htmlspecialchars((string)($raw['status'] ?? '')) ?></div>
                                    <div><strong>Referrer user_id:</strong> <?= (int)($raw['referrer_user_id'] ?? 0) ?></div>
                                    <div><strong>Concluída em:</strong> <?= htmlspecialchars((string)($raw['completed_at'] ?? '')) ?></div>
                                </div>
                            <?php elseif ($type === 'trial_end'): ?>
                                <div style="font-size:12px; color:#cccccc;">
                                    <div><strong>Dias grátis:</strong> <?= (int)($raw['trial_days'] ?? 0) ?></div>
                                    <div><strong>Início do pago:</strong> <?= htmlspecialchars((string)($raw['paid_starts_at'] ?? '')) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="font-size:13px; color:#b0b0b0;">Ainda não há histórico de planos ou créditos de tokens para este usuário.</p>
        <?php endif; ?>
    </div>
</div>

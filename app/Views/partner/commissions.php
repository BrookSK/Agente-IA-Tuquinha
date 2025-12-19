<?php
/** @var array $user */
/** @var array|null $partner */
/** @var int $year */
/** @var int $month */
/** @var array $monthData */
/** @var int $owedCents */
/** @var bool $eligible */
/** @var int $minPayoutCents */
/** @var array $payouts */

$minPayout = $minPayoutCents / 100;
?>

<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; margin: 0 0 4px 0;">Minhas comissões</h1>
            <div style="font-size: 13px; color: var(--text-secondary);">Você só recebe quando o acumulado atingir pelo menos <strong>R$ <?= number_format($minPayout, 2, ',', '.') ?></strong>. Se não atingir, fica acumulado para o próximo mês.</div>
        </div>

        <form method="get" action="/parceiro/comissoes" style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
            <div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Ano</div>
                <input type="number" name="year" value="<?= (int)$year ?>" style="width:110px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary);">
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Mês</div>
                <input type="number" min="1" max="12" name="month" value="<?= (int)$month ?>" style="width:110px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary);">
            </div>
            <button type="submit" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:800; cursor:pointer;">Filtrar</button>
        </form>
    </div>

    <?php if (!empty($_SESSION['partner_commissions_success'])): ?>
        <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($_SESSION['partner_commissions_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['partner_commissions_success']); ?>
    <?php endif; ?>

    <?php if (!$partner): ?>
        <div style="background:var(--surface-card); border-radius:12px; border:1px solid var(--border-subtle); padding:10px 12px; font-size:13px; color:var(--text-secondary);">
            Nenhum cadastro de parceiro foi encontrado para seu usuário ainda. Combine com o admin para configurar seu perfil de parceiro e vincular cursos ao seu usuário.
        </div>
        <?php return; ?>
    <?php endif; ?>

    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div style="flex:1 1 220px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Comissão do mês</div>
            <div style="font-size:18px; font-weight:900;">R$ <?= number_format(((int)($monthData['total_commission_cents'] ?? 0))/100, 2, ',', '.') ?></div>
        </div>
        <div style="flex:1 1 220px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Acumulado a receber</div>
            <div style="font-size:18px; font-weight:900; color:var(--text-primary);">R$ <?= number_format(((int)$owedCents)/100, 2, ',', '.') ?></div>
        </div>
        <div style="flex:1 1 320px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Status</div>
            <?php if ($eligible): ?>
                <div style="margin-top:6px; font-size:13px; color:var(--text-primary); font-weight:800;">Apto para pagamento (admin já pode marcar como pago).</div>
            <?php else: ?>
                <div style="margin-top:6px; font-size:13px; color:var(--text-secondary);">Ainda não atingiu o mínimo de R$ <?= number_format($minPayout, 2, ',', '.') ?>. O valor fica acumulado.</div>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">
        <div style="flex:1 1 560px; min-width:300px;">
            <div style="font-size:14px; font-weight:900; margin-bottom:8px;">Detalhamento por curso (mês)</div>
            <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden;">
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table style="width:100%; min-width:680px; border-collapse:collapse; font-size:13px;">
                    <thead style="background:var(--surface-subtle);">
                        <tr>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Curso</th>
                            <th style="text-align:center; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Vendas pagas</th>
                            <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Comissão (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($monthData['by_course'])): ?>
                        <tr><td colspan="3" style="padding:12px; color:var(--text-secondary);">Nenhuma venda paga neste mês.</td></tr>
                    <?php else: ?>
                        <?php foreach ($monthData['by_course'] as $row): ?>
                            <?php $c = $row['course']; ?>
                            <tr style="background:var(--surface-card); border-top:1px solid var(--border-subtle);">
                                <td style="padding:10px 12px;">
                                    <?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars((string)($c['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td style="padding:10px 12px; text-align:center;">
                                    <?= (int)($row['paid_count'] ?? 0) ?>
                                </td>
                                <td style="padding:10px 12px; text-align:right; font-weight:800;">
                                    R$ <?= number_format(((int)($row['commission_cents'] ?? 0))/100, 2, ',', '.') ?>
                                    <div style="font-size:11px; color:var(--text-secondary);"><?= number_format((float)($row['commission_percent'] ?? 0.0), 2, ',', '.') ?>%</div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>

            <div style="font-size:14px; font-weight:900; margin:14px 0 8px 0;">Histórico de pagamentos</div>
            <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden;">
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table style="width:100%; min-width:520px; border-collapse:collapse; font-size:13px;">
                    <thead style="background:var(--surface-subtle);">
                        <tr>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Competência</th>
                            <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Pago (R$)</th>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($payouts)): ?>
                        <tr><td colspan="3" style="padding:12px; color:var(--text-secondary);">Nenhum pagamento registrado ainda.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payouts as $p): ?>
                            <tr style="background:var(--surface-card); border-top:1px solid var(--border-subtle);">
                                <td style="padding:10px 12px;"><?= sprintf('%02d/%04d', (int)($p['period_month'] ?? 0), (int)($p['period_year'] ?? 0)) ?></td>
                                <td style="padding:10px 12px; text-align:right; font-weight:800;">R$ <?= number_format(((int)($p['amount_cents'] ?? 0))/100, 2, ',', '.') ?></td>
                                <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars((string)($p['paid_at'] ?? $p['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="flex:1 1 420px; min-width:280px;">
            <div style="font-size:14px; font-weight:900; margin-bottom:8px;">Meus dados para pagamento</div>
            <div style="background:var(--surface-card); border-radius:14px; border:1px solid var(--border-subtle); padding:12px 12px;">
                <form method="post" action="/parceiro/comissoes/salvar-dados" style="display:flex; flex-direction:column; gap:10px;">
                    <div>
                        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Chave PIX</label>
                        <input type="text" name="pix_key" value="<?= htmlspecialchars((string)($partner['pix_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="CPF/CNPJ, e-mail, telefone ou chave aleatória" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <div style="flex:1 1 180px;">
                            <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Banco</label>
                            <input type="text" name="bank_name" value="<?= htmlspecialchars((string)($partner['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Nubank" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                        </div>
                        <div style="flex:0 0 120px;">
                            <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Agência</label>
                            <input type="text" name="bank_agency" value="<?= htmlspecialchars((string)($partner['bank_agency'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="0001" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <div style="flex:1 1 180px;">
                            <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Conta</label>
                            <input type="text" name="bank_account" value="<?= htmlspecialchars((string)($partner['bank_account'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="12345-6" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                        </div>
                        <div style="flex:1 1 180px;">
                            <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tipo de conta</label>
                            <input type="text" name="bank_account_type" value="<?= htmlspecialchars((string)($partner['bank_account_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Corrente / Poupança" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <div style="flex:1 1 220px;">
                            <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Titular</label>
                            <input type="text" name="bank_holder_name" value="<?= htmlspecialchars((string)($partner['bank_holder_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome completo" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                        </div>
                        <div style="flex:1 1 180px;">
                            <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">CPF/CNPJ</label>
                            <input type="text" name="bank_holder_document" value="<?= htmlspecialchars((string)($partner['bank_holder_document'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Somente números" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                        </div>
                    </div>

                    <button type="submit" style="border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:900; cursor:pointer;">Salvar dados</button>
                    <div style="font-size:12px; color:var(--text-secondary); line-height:1.35;">
                        Dica: informe pelo menos a <strong>chave PIX</strong>. Esses dados ficam visíveis para o admin realizar o pagamento.
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

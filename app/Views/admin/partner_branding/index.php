<?php
/** @var array $partners */
?>

<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 4px 0;">Branding de parceiros</h1>
            <div style="font-size: 13px; color: var(--text-secondary);">Gerencie o branding, subdomínio e status de apontamento de cada parceiro.</div>
        </div>
    </div>

    <?php if (!empty($_SESSION['admin_partner_branding_success'])): ?>
        <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($_SESSION['admin_partner_branding_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['admin_partner_branding_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_partner_branding_error'])): ?>
        <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($_SESSION['admin_partner_branding_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['admin_partner_branding_error']); ?>
    <?php endif; ?>

    <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden;">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table style="width:100%; min-width:900px; border-collapse:collapse; font-size:13px;">
                <thead style="background:var(--surface-subtle);">
                    <tr>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Parceiro</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Empresa / Cores</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Subdomínio</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Status DNS</th>
                        <th style="text-align:center; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Ação</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($partners)): ?>
                    <tr>
                        <td colspan="5" style="padding:12px; color:var(--text-secondary);">Nenhum parceiro encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($partners as $p): ?>
                        <?php
                            $subdomain = (string)($p['branding_subdomain'] ?? '');
                            $subStatus = strtolower(trim((string)($p['branding_subdomain_status'] ?? '')));
                            $companyName = (string)($p['branding_company_name'] ?? '');
                            $primaryColor = (string)($p['branding_primary_color'] ?? '');
                            $secondaryColor = (string)($p['branding_secondary_color'] ?? '');
                            $baseDomain = (string)($p['base_domain'] ?? '');
                            $fullHost = ($subdomain !== '' && $baseDomain !== '') ? ($subdomain . '.' . $baseDomain) : $subdomain;

                            $statusLabel = match($subStatus) {
                                'approved' => 'Aprovado',
                                'pending'  => 'Aguardando aprovação',
                                default    => 'Sem subdomínio',
                            };
                            $statusColor = match($subStatus) {
                                'approved' => '#10b981',
                                'pending'  => '#f59e0b',
                                default    => '#6b7280',
                            };
                        ?>
                        <tr style="background:var(--surface-card); border-top:1px solid var(--border-subtle);">
                            <td style="padding:10px 12px;">
                                <div style="font-weight:700;"><?= htmlspecialchars((string)($p['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars((string)($p['user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td style="padding:10px 12px;">
                                <?php if ($companyName !== ''): ?>
                                    <div style="font-weight:600; margin-bottom:4px;"><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php else: ?>
                                    <div style="color:var(--text-secondary); font-size:12px; margin-bottom:4px;">Sem nome configurado</div>
                                <?php endif; ?>
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <?php if ($primaryColor !== ''): ?>
                                        <span title="Cor base: <?= htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block; width:16px; height:16px; border-radius:4px; background:<?= htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8') ?>; border:1px solid rgba(255,255,255,0.15);"></span>
                                        <span style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if ($secondaryColor !== ''): ?>
                                        <span title="Cor secundária: <?= htmlspecialchars($secondaryColor, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block; width:16px; height:16px; border-radius:4px; background:<?= htmlspecialchars($secondaryColor, ENT_QUOTES, 'UTF-8') ?>; border:1px solid rgba(255,255,255,0.15);"></span>
                                        <span style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars($secondaryColor, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if ($primaryColor === '' && $secondaryColor === ''): ?>
                                        <span style="font-size:11px; color:var(--text-secondary);">Sem cores</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding:10px 12px;">
                                <?php if ($subdomain !== ''): ?>
                                    <div style="font-family:monospace; font-size:12px; color:var(--text-primary);"><?= htmlspecialchars($subdomain, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if ($fullHost !== '' && $subStatus === 'approved'): ?>
                                        <a href="<?= htmlspecialchars('https://' . $fullHost . '/', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="font-size:11px; color:#60a5fa; text-decoration:none;">
                                            <?= htmlspecialchars('https://' . $fullHost . '/', ENT_QUOTES, 'UTF-8') ?> ↗
                                        </a>
                                    <?php elseif ($baseDomain !== ''): ?>
                                        <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars($subdomain . '.' . $baseDomain, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--text-secondary); font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px 12px;">
                                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; background:<?= $statusColor ?>22; color:<?= $statusColor ?>; border:1px solid <?= $statusColor ?>44;">
                                    <span style="width:6px; height:6px; border-radius:50%; background:<?= $statusColor ?>; display:inline-block;"></span>
                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if (!empty($p['branding_subdomain_requested_at'])): ?>
                                    <div style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Solicitado: <?= htmlspecialchars((string)$p['branding_subdomain_requested_at'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($p['branding_subdomain_approved_at'])): ?>
                                    <div style="font-size:11px; color:var(--text-secondary);">Aprovado: <?= htmlspecialchars((string)$p['branding_subdomain_approved_at'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px 12px; text-align:center;">
                                <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                                    <a href="/admin/branding-parceiros/editar?user_id=<?= (int)($p['user_id'] ?? 0) ?>" style="display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none; font-size:12px;">Editar branding</a>
                                    <?php if ($subdomain !== '' && $subStatus === 'pending'): ?>
                                        <form method="post" action="/admin/branding-parceiros/aprovar-subdominio" onsubmit="return confirm('Aprovar subdomínio e notificar o parceiro por e-mail?');" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?= (int)($p['user_id'] ?? 0) ?>">
                                            <button type="submit" style="border:none; border-radius:999px; padding:7px 12px; background:linear-gradient(135deg,#10b981,#34d399); color:#050509; font-weight:800; cursor:pointer; font-size:12px;">✓ Aprovar DNS</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

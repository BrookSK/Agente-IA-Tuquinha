<?php /** @var array $users */ ?>
<?php /** @var string $query */ ?>

<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 16px;">Usuários do sistema</h1>

    <form method="get" action="/admin/usuarios" style="margin-bottom: 14px; display:flex; gap:8px;">
        <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Buscar por nome ou e-mail" style="flex:1; padding:6px 10px; border-radius:999px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
        <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:13px; font-weight:600; cursor:pointer;">Buscar</button>
    </form>

    <table style="width:100%; border-collapse:collapse; font-size:13px; background:#111118; border-radius:12px; overflow:hidden; border:1px solid #272727;">
        <thead>
            <tr style="background:#15151f;">
                <th style="text-align:left; padding:8px 10px;">Nome</th>
                <th style="text-align:left; padding:8px 10px;">E-mail</th>
                <th style="text-align:center; padding:8px 10px;">Admin</th>
                <th style="text-align:center; padding:8px 10px;">Status</th>
                <th style="text-align:left; padding:8px 10px;">Criado em</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="padding:7px 10px;">
                            <a href="/admin/usuarios/ver?id=<?= (int)$u['id'] ?>" style="color:#f5f5f5; text-decoration:none;">
                                <?= htmlspecialchars($u['name']) ?>
                            </a>
                        </td>
                        <td style="padding:7px 10px;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding:7px 10px; text-align:center;">
                            <?= !empty($u['is_admin']) ? '✔' : '' ?>
                        </td>
                        <td style="padding:7px 10px; text-align:center; font-size:11px;">
                            <?php $active = isset($u['is_active']) ? (int)$u['is_active'] === 1 : true; ?>
                            <span style="padding:2px 8px; border-radius:999px; border:1px solid <?= $active ? '#2e7d32' : '#b71c1c' ?>; color:<?= $active ? '#a5d6a7' : '#ef9a9a' ?>;">
                                <?= $active ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td style="padding:7px 10px; font-size:12px; color:#b0b0b0;">
                            <?= htmlspecialchars($u['created_at'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding:10px; color:#b0b0b0;">Nenhum usuário encontrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

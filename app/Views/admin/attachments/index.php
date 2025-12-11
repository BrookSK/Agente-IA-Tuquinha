<?php
$totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
if ($totalPages < 1) {
    $totalPages = 1;
}
$currentType = $typeFilter ?? '';
$currentBefore = $before ?? '';

$nowTs = time();
$quick30 = date('Y-m-d', $nowTs - 30 * 86400);
$quick60 = date('Y-m-d', $nowTs - 60 * 86400);
$quick90 = date('Y-m-d', $nowTs - 90 * 86400);
?>
<div style="max-width: 960px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 8px; font-weight: 650;">Anexos do chat</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px;">
        Aqui você consegue visualizar e limpar anexos enviados no chat (imagens, arquivos e áudios).
        A exclusão remove apenas o registro interno no Tuquinha; os arquivos no servidor de mídia permanecem.
    </p>

    <form action="/admin/anexos" method="get" style="margin-bottom:10px; display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end;">
        <div>
            <label style="font-size:12px; color:#b0b0b0; display:block; margin-bottom:3px;">Tipo</label>
            <select name="type" style="
                min-width: 140px; padding:6px 8px; border-radius:8px;
                border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                <option value="" <?= $currentType === '' ? 'selected' : '' ?>>Todos</option>
                <option value="image" <?= $currentType === 'image' ? 'selected' : '' ?>>Imagens</option>
                <option value="file" <?= $currentType === 'file' ? 'selected' : '' ?>>Arquivos</option>
                <option value="audio" <?= $currentType === 'audio' ? 'selected' : '' ?>>Áudios</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px; color:#b0b0b0; display:block; margin-bottom:3px;">Criados antes de (YYYY-MM-DD)</label>
            <input type="text" name="before" value="<?= htmlspecialchars($currentBefore) ?>" placeholder="ex: 2025-01-01" style="
                width: 150px; padding:6px 8px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px;">
            <div style="margin-top:3px; font-size:11px; color:#777; max-width:260px;">
                Atalhos rápidos:
                <a href="<?= '/admin/anexos?type=' . urlencode($currentType) . '&before=' . urlencode($quick30) ?>" style="color:#ffcc80; text-decoration:none; margin-right:4px;">mais antigos que 30 dias</a>
                <a href="<?= '/admin/anexos?type=' . urlencode($currentType) . '&before=' . urlencode($quick60) ?>" style="color:#ffcc80; text-decoration:none; margin-right:4px;">60 dias</a>
                <a href="<?= '/admin/anexos?type=' . urlencode($currentType) . '&before=' . urlencode($quick90) ?>" style="color:#ffcc80; text-decoration:none;">90 dias</a>
            </div>
        </div>
        <div>
            <button type="submit" style="
                border:none; border-radius:999px; padding:7px 14px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Filtrar
            </button>
        </div>
    </form>

    <?php if (empty($attachments)): ?>
        <div style="background:#111118; border:1px solid #272727; border-radius:10px; padding:10px 12px; font-size:13px; color:#b0b0b0;">
            Nenhum anexo encontrado com os filtros atuais.
        </div>
    <?php else: ?>
        <form action="/admin/anexos/excluir" method="post" onsubmit="return confirm('Tem certeza que deseja excluir os anexos selecionados? Esta ação não pode ser desfeita.');">
            <div style="overflow-x:auto; border-radius:10px; border:1px solid #272727; background:#050509;">
                <table style="width:100%; border-collapse:collapse; font-size:12px; color:#f5f5f5;">
                    <thead>
                        <tr style="background:#111118;">
                            <th style="padding:6px 8px; border-bottom:1px solid #272727; text-align:left; width:32px;"><input type="checkbox" onclick="
                                var cbs = document.querySelectorAll('.att-checkbox');
                                for (var i=0;i<cbs.length;i++){cbs[i].checked = this.checked;}
                            "></th>
                            <th style="padding:6px 8px; border-bottom:1px solid #272727; text-align:left;">ID</th>
                            <th style="padding:6px 8px; border-bottom:1px solid #272727; text-align:left;">Tipo</th>
                            <th style="padding:6px 8px; border-bottom:1px solid #272727; text-align:left;">Nome original</th>
                            <th style="padding:6px 8px; border-bottom:1px solid #272727; text-align:left;">MIME</th>
                            <th style="padding:6px 8px; border-bottom:1px solid #272727; text-align:right;">Tamanho</th>
                            <th style="padding:6px 8px; border-bottom:1px solid #272727; text-align:left;">Criado em</th>
                            <th style="padding:6px 8px; border-bottom:1px solid #272727; text-align:left;">Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attachments as $att): ?>
                            <?php
                            $id = (int)($att['id'] ?? 0);
                            $type = (string)($att['type'] ?? '');
                            $name = (string)($att['original_name'] ?? '');
                            $mime = (string)($att['mime_type'] ?? '');
                            $size = (int)($att['size'] ?? 0);
                            $created = (string)($att['created_at'] ?? '');
                            $path = (string)($att['path'] ?? '');
                            $humanSize = '';
                            if ($size > 0) {
                                if ($size >= 1024 * 1024) {
                                    $humanSize = number_format($size / (1024 * 1024), 2, ',', '.') . ' MB';
                                } elseif ($size >= 1024) {
                                    $humanSize = number_format($size / 1024, 2, ',', '.') . ' KB';
                                } else {
                                    $humanSize = $size . ' B';
                                }
                            }
                            ?>
                            <tr>
                                <td style="padding:5px 6px; border-top:1px solid #272727; text-align:center;">
                                    <input type="checkbox" class="att-checkbox" name="ids[]" value="<?= $id ?>">
                                </td>
                                <td style="padding:5px 6px; border-top:1px solid #272727;">
                                    <?= $id ?>
                                </td>
                                <td style="padding:5px 6px; border-top:1px solid #272727; text-transform:lowercase;">
                                    <?= htmlspecialchars($type) ?>
                                </td>
                                <td style="padding:5px 6px; border-top:1px solid #272727; max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($name) ?>
                                </td>
                                <td style="padding:5px 6px; border-top:1px solid #272727; max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($mime) ?>
                                </td>
                                <td style="padding:5px 6px; border-top:1px solid #272727; text-align:right;">
                                    <?= htmlspecialchars($humanSize) ?>
                                </td>
                                <td style="padding:5px 6px; border-top:1px solid #272727;">
                                    <?= htmlspecialchars($created) ?>
                                </td>
                                <td style="padding:5px 6px; border-top:1px solid #272727; max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?php if ($path !== ''): ?>
                                        <a href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ffcc80; text-decoration:none;">abrir</a>
                                    <?php else: ?>
                                        <span style="color:#777;">(sem URL)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:8px; display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#b0b0b0; flex-wrap:wrap; gap:8px;">
                <div>
                    Total de anexos: <?= (int)$total ?>
                </div>
                <div style="display:flex; gap:4px; align-items:center;">
                    <?php if ($page > 1): ?>
                        <a href="<?= '/admin/anexos?page=' . ($page - 1) . ($currentType !== '' ? '&type=' . urlencode($currentType) : '') . ($currentBefore !== '' ? '&before=' . urlencode($currentBefore) : '') ?>" style="
                            padding:4px 8px; border-radius:999px; border:1px solid #272727; color:#f5f5f5; text-decoration:none;">Anterior</a>
                    <?php endif; ?>
                    <span>Página <?= (int)$page ?> de <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= '/admin/anexos?page=' . ($page + 1) . ($currentType !== '' ? '&type=' . urlencode($currentType) : '') . ($currentBefore !== '' ? '&before=' . urlencode($currentBefore) : '') ?>" style="
                            padding:4px 8px; border-radius:999px; border:1px solid #272727; color:#f5f5f5; text-decoration:none;">Próxima</a>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top:10px;">
                <button type="submit" style="
                    border:none; border-radius:999px; padding:7px 14px;
                    background:#311; color:#ffbaba; font-weight:600; font-size:13px; cursor:pointer;">
                    Excluir anexos selecionados
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

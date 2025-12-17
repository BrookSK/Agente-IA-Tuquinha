<?php /** @var array $project */ ?>
<?php /** @var array $folders */ ?>
<?php /** @var array $baseFiles */ ?>
<?php /** @var array $latestByFileId */ ?>
<?php /** @var string|null $uploadError */ ?>
<?php /** @var string|null $uploadOk */ ?>
<div style="max-width: 980px; margin: 0 auto;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:14px;">
        <div>
            <h1 style="font-size: 24px; margin: 0 0 6px 0;"><?= htmlspecialchars((string)($project['name'] ?? '')) ?></h1>
            <?php if (!empty($project['description'])): ?>
                <div style="color:#b0b0b0; font-size:14px; line-height:1.35;">
                    <?= nl2br(htmlspecialchars((string)$project['description'])) ?>
                </div>
            <?php endif; ?>
        </div>
        <a href="/chat?new=1&project_id=<?= (int)($project['id'] ?? 0) ?>" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none; white-space:nowrap;">Novo chat do projeto</a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
        <div style="background:#111118; border:1px solid #272727; border-radius:14px; padding:14px;">
            <div style="font-weight:650; margin-bottom:8px;">Pastas</div>
            <?php if (empty($folders)): ?>
                <div style="color:#b0b0b0; font-size:13px;">Nenhuma pasta.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($folders as $f): ?>
                        <div style="font-size:13px; color:#f5f5f5; border:1px solid #272727; background:#050509; border-radius:10px; padding:7px 10px;">
                            <?= htmlspecialchars((string)($f['path'] ?? '')) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="background:#111118; border:1px solid #272727; border-radius:14px; padding:14px;">
            <div style="font-weight:650; margin-bottom:8px;">Arquivos base</div>

            <?php if (!empty($uploadError)): ?>
                <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
                    <?= htmlspecialchars($uploadError) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($uploadOk)): ?>
                <div style="background:#102312; border:1px solid #2e7d32; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
                    <?= htmlspecialchars($uploadOk) ?>
                </div>
            <?php endif; ?>

            <form action="/projetos/arquivo-base/upload" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
                <input type="hidden" name="project_id" value="<?= (int)($project['id'] ?? 0) ?>">
                <div style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
                    <div style="flex:1; min-width:180px;">
                        <label style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:4px;">Pasta</label>
                        <select name="folder_path" style="width:100%; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                            <?php foreach (($folders ?? []) as $f): ?>
                                <option value="<?= htmlspecialchars((string)($f['path'] ?? '')) ?>"><?= htmlspecialchars((string)($f['path'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:2; min-width:220px;">
                        <label style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:4px;">Arquivo</label>
                        <input type="file" name="file" required style="width:100%; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                    </div>
                    <div>
                        <button type="submit" style="border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; cursor:pointer;">Enviar</button>
                    </div>
                </div>
                <div style="color:#8d8d8d; font-size:12px; line-height:1.35;">
                    Arquivos de texto/código (txt, md, json, php, js, etc.) serão usados como contexto automaticamente.
                </div>
            </form>

            <?php if (empty($baseFiles)): ?>
                <div style="color:#b0b0b0; font-size:13px;">Nenhum arquivo base ainda.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($baseFiles as $bf): ?>
                        <?php $fid = (int)($bf['id'] ?? 0); ?>
                        <?php $ver = $latestByFileId[$fid] ?? null; ?>
                        <div style="border:1px solid #272727; background:#050509; border-radius:12px; padding:10px 12px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                                <div style="font-size:13px; font-weight:600;">
                                    <?= htmlspecialchars((string)($bf['path'] ?? '')) ?>
                                </div>
                                <div style="font-size:11px; color:#b0b0b0; white-space:nowrap;">
                                    v<?= (int)($ver['version'] ?? 0) ?>
                                </div>
                            </div>
                            <div style="font-size:12px; color:#8d8d8d; margin-top:4px;">
                                <?= htmlspecialchars((string)($bf['mime_type'] ?? '')) ?>
                                <?php if (!empty($ver['extracted_text'])): ?>
                                    · contexto ok
                                <?php else: ?>
                                    · sem texto extraído
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

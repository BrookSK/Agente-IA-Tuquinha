<?php
/** @var array $items */
/** @var array $current */
/** @var string|null $error */
/** @var string|null $success */
?>

<div style="max-width: 980px; margin: 0 auto;">
    <h1 style="font-size:18px; margin-bottom:10px;">Ícones do menu</h1>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">
        Envie 2 ícones por item: um para o tema escuro e outro para o tema claro. Formatos aceitos: PNG, JPG, WEBP, SVG (até 700 KB).
    </div>

    <div style="display:flex; flex-direction:column; gap:10px;">
        <?php foreach ($items as $key => $label): ?>
            <?php
                $row = $current[$key] ?? null;
                $dark = is_array($row) ? ($row['dark_path'] ?? null) : null;
                $light = is_array($row) ? ($row['light_path'] ?? null) : null;
            ?>
            <div style="border-radius:14px; border:1px solid var(--border-subtle); background:var(--surface-card); padding:10px 12px;">
                <form action="/admin/menu-icones/salvar" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:8px;">
                    <input type="hidden" name="key" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="label" value="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">

                    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center;">
                        <div style="font-weight:650; font-size:14px; color:var(--text-primary);">
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            <span style="font-weight:400; font-size:12px; color:var(--text-secondary);">(<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>)</span>
                        </div>
                        <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:650; font-size:12px; cursor:pointer;">Salvar</button>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <div style="flex:1 1 320px; min-width:260px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); padding:8px 10px;">
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Tema escuro</div>
                            <?php if (!empty($dark)): ?>
                                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                                    <img src="<?= htmlspecialchars((string)$dark, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:28px; height:28px; object-fit:contain;">
                                    <div style="font-size:11px; color:var(--text-secondary); word-break:break-all;"><?= htmlspecialchars((string)$dark, ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <label style="font-size:11px; color:var(--text-secondary); display:flex; align-items:center; gap:6px;">
                                    <input type="checkbox" name="clear_dark" value="1" style="accent-color:#e53935;"> Remover ícone escuro
                                </label>
                            <?php endif; ?>
                            <input type="file" name="dark_file" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="width:100%; font-size:12px; color:var(--text-secondary);">
                        </div>

                        <div style="flex:1 1 320px; min-width:260px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); padding:8px 10px;">
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Tema claro</div>
                            <?php if (!empty($light)): ?>
                                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                                    <img src="<?= htmlspecialchars((string)$light, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:28px; height:28px; object-fit:contain;">
                                    <div style="font-size:11px; color:var(--text-secondary); word-break:break-all;"><?= htmlspecialchars((string)$light, ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <label style="font-size:11px; color:var(--text-secondary); display:flex; align-items:center; gap:6px;">
                                    <input type="checkbox" name="clear_light" value="1" style="accent-color:#e53935;"> Remover ícone claro
                                </label>
                            <?php endif; ?>
                            <input type="file" name="light_file" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="width:100%; font-size:12px; color:var(--text-secondary);">
                        </div>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

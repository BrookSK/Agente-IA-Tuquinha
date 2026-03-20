<?php
/** @var array $partner */
/** @var array|null $branding */
/** @var string|null $baseDomain */

$branding = $branding ?? null;
$companyName = trim((string)($branding['company_name'] ?? ''));
$logoUrl = trim((string)($branding['logo_url'] ?? ''));
$faviconUrl = trim((string)($branding['favicon_url'] ?? ''));
$subdomain = trim((string)($branding['subdomain'] ?? ''));
$subdomainStatus = trim((string)($branding['subdomain_status'] ?? ''));
$baseDomain = isset($baseDomain) ? (string)$baseDomain : '';
$fullHost = ($subdomain !== '' && $baseDomain !== '') ? ($subdomain . '.' . $baseDomain) : $subdomain;
$primary = trim((string)($branding['primary_color'] ?? ''));
$secondary = trim((string)($branding['secondary_color'] ?? ''));
$headerImageUrl = trim((string)($branding['header_image_url'] ?? ''));
$heroImageUrl = trim((string)($branding['hero_image_url'] ?? ''));
$footerImageUrl = trim((string)($branding['footer_image_url'] ?? ''));
$backgroundImageUrl = trim((string)($branding['background_image_url'] ?? ''));
?>

<div style="max-width: 860px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Branding do parceiro</div>
            <h1 style="font-size: 22px; font-weight: 700; margin: 0;">
                <?= htmlspecialchars((string)($partner['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <div style="font-size: 13px; color: var(--text-secondary);">
                <?= htmlspecialchars((string)($partner['user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="/admin/branding-parceiros" style="display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none; font-size:12px;">Voltar</a>
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

    <div style="border:1px solid var(--border-subtle); border-radius:14px; background:var(--surface-card); padding:14px 14px; margin-bottom:12px;">
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">
            <div style="flex:1 1 280px;">
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Subdomínio solicitado pelo parceiro</div>
                <div style="font-size:13px; font-weight:800; color:var(--text-primary);">
                    <?= htmlspecialchars($fullHost !== '' ? $fullHost : '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </div>
                <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Status: <strong><?= htmlspecialchars($subdomainStatus !== '' ? $subdomainStatus : 'none', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></div>
            </div>
            <div style="flex:1 1 260px;">
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Preview</div>
                <div style="font-size:13px; color:var(--text-primary);">
                    <?php if ($fullHost !== ''): ?>
                        <a href="<?= htmlspecialchars('https://' . $fullHost . '/', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener" style="color:var(--text-primary); text-decoration:none; border-bottom:1px dashed var(--border-subtle);">
                            <?= htmlspecialchars('https://' . $fullHost . '/', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </a>
                    <?php else: ?>
                        <span style="color:var(--text-secondary);">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="flex:0 0 auto; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <?php if ($subdomain !== '' && $subdomainStatus === 'pending'): ?>
                    <form method="post" action="/admin/branding-parceiros/aprovar-subdominio" onsubmit="return confirm('Aprovar subdomínio e notificar o parceiro por e-mail?');" style="margin:0;">
                        <input type="hidden" name="user_id" value="<?= (int)($partner['user_id'] ?? 0) ?>">
                        <button type="submit" style="border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#10b981,#34d399); color:#050509; font-weight:900; cursor:pointer;">Aprovar subdomínio</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <form method="post" action="/admin/branding-parceiros/salvar" enctype="multipart/form-data" style="border:1px solid var(--border-subtle); border-radius:14px; background:var(--surface-card); padding:14px 14px;">
        <input type="hidden" name="user_id" value="<?= (int)($partner['user_id'] ?? 0) ?>">

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <div style="flex:1 1 280px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Nome da empresa</label>
                <input type="text" name="company_name" value="<?= htmlspecialchars($companyName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:12px;">
            <div style="flex:1 1 200px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Cor base (HEX)</label>
                <input type="text" name="primary_color" value="<?= htmlspecialchars($primary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="#e53935" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
            <div style="flex:1 1 200px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Cor secundária (HEX)</label>
                <input type="text" name="secondary_color" value="<?= htmlspecialchars($secondary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="#ff6f60" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:12px;">
            <div style="flex:1 1 200px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Cor do Texto (HEX)</label>
                <input type="text" name="text_color" value="<?= htmlspecialchars(trim((string)($branding['text_color'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="#ffffff" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
            <div style="flex:1 1 200px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Cor do Texto dos Botões (HEX)</label>
                <input type="text" name="button_text_color" value="<?= htmlspecialchars(trim((string)($branding['button_text_color'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="#ffffff" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:12px;">
            <div style="flex:1 1 200px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Cor dos Links (HEX)</label>
                <input type="text" name="link_color" value="<?= htmlspecialchars(trim((string)($branding['link_color'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="#2d6ef6" style="width:100%; padding:9px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
        </div>

        <div style="margin-top:12px; border-top:1px dashed var(--border-subtle); padding-top:12px;">
            <div style="font-size:13px; font-weight:700; margin-bottom:8px;">Logo</div>

            <?php if ($logoUrl !== ''): ?>
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
                    <div style="width:64px; height:64px; border-radius:14px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="logo" style="width:100%; height:100%; object-fit:cover; display:block;">
                    </div>
                    <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary);">
                        <input type="checkbox" name="remove_logo" value="1">
                        <span>Remover logo atual</span>
                    </label>
                </div>
            <?php endif; ?>

            <div>
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Upload da logo (arquivo)</label>
                <input type="file" name="logo_upload" accept="image/*" style="width:100%; padding:10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">Tamanho recomendado: 200x200px</div>
            </div>
        </div>

        <div style="margin-top:12px; border-top:1px dashed var(--border-subtle); padding-top:12px;">
            <div style="font-size:13px; font-weight:700; margin-bottom:8px;">Favicon</div>

            <?php if ($faviconUrl !== ''): ?>
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
                    <div style="width:40px; height:40px; border-radius:12px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                        <img src="<?= htmlspecialchars($faviconUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="favicon" style="width:100%; height:100%; object-fit:cover; display:block;">
                    </div>
                    <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary);">
                        <input type="checkbox" name="remove_favicon" value="1">
                        <span>Remover favicon atual</span>
                    </label>
                </div>
            <?php endif; ?>

            <div>
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Upload do favicon (arquivo)</label>
                <input type="file" name="favicon_upload" accept="image/*" style="width:100%; padding:10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">Tamanho recomendado: 512x512px (PNG)</div>
            </div>
        </div>

        <div style="margin-top:12px; border-top:1px dashed var(--border-subtle); padding-top:12px;">
            <div style="font-size:13px; font-weight:700; margin-bottom:8px;">Imagens Adicionais</div>
            
            <div style="margin-bottom:12px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Imagem do Header</label>
                <?php if ($headerImageUrl !== ''): ?>
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
                        <div style="width:120px; height:60px; border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                            <img src="<?= htmlspecialchars($headerImageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="header" style="width:100%; height:100%; object-fit:cover; display:block;">
                        </div>
                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary);">
                            <input type="checkbox" name="remove_header_image" value="1">
                            <span>Remover imagem atual</span>
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="header_image_upload" accept="image/*" style="width:100%; padding:10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">Tamanho recomendado: 400x80px</div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Imagem Hero (Destaque)</label>
                <?php if ($heroImageUrl !== ''): ?>
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
                        <div style="width:120px; height:60px; border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                            <img src="<?= htmlspecialchars($heroImageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="hero" style="width:100%; height:100%; object-fit:cover; display:block;">
                        </div>
                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary);">
                            <input type="checkbox" name="remove_hero_image" value="1">
                            <span>Remover imagem atual</span>
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="hero_image_upload" accept="image/*" style="width:100%; padding:10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">Tamanho recomendado: 1200x600px</div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Imagem do Footer</label>
                <?php if ($footerImageUrl !== ''): ?>
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
                        <div style="width:120px; height:60px; border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                            <img src="<?= htmlspecialchars($footerImageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="footer" style="width:100%; height:100%; object-fit:cover; display:block;">
                        </div>
                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary);">
                            <input type="checkbox" name="remove_footer_image" value="1">
                            <span>Remover imagem atual</span>
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="footer_image_upload" accept="image/*" style="width:100%; padding:10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">Tamanho recomendado: 300x150px</div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Imagem de Fundo</label>
                <?php if ($backgroundImageUrl !== ''): ?>
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
                        <div style="width:120px; height:60px; border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                            <img src="<?= htmlspecialchars($backgroundImageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="background" style="width:100%; height:100%; object-fit:cover; display:block;">
                        </div>
                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary);">
                            <input type="checkbox" name="remove_background_image" value="1">
                            <span>Remover imagem atual</span>
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="background_image_upload" accept="image/*" style="width:100%; padding:10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
                <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">Tamanho recomendado: 1920x1080px</div>
            </div>
        </div>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit" style="border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:800; cursor:pointer;">Salvar branding</button>
            <a href="/admin/branding-parceiros" style="display:inline-flex; align-items:center; padding:9px 14px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none; font-weight:700; font-size:13px;">Cancelar</a>
        </div>
    </form>
</div>

<?php
/** @var array $user */
/** @var array|null $branding */
/** @var string|null $baseDomain */

$companyName = $branding['company_name'] ?? '';
$logoUrl = $branding['logo_url'] ?? '';
$faviconUrl = $branding['favicon_url'] ?? '';
$subdomain = $branding['subdomain'] ?? '';
$subdomainStatus = $branding['subdomain_status'] ?? 'none';
$primaryColor = $branding['primary_color'] ?? '';
$secondaryColor = $branding['secondary_color'] ?? '';
$textColor = $branding['text_color'] ?? '';
$buttonTextColor = $branding['button_text_color'] ?? '';
$headerImageUrl = $branding['header_image_url'] ?? '';
$footerImageUrl = $branding['footer_image_url'] ?? '';
$heroImageUrl = $branding['hero_image_url'] ?? '';
$backgroundImageUrl = $branding['background_image_url'] ?? '';

$success = $_SESSION['professional_success'] ?? null;
unset($_SESSION['professional_success']);

$error = $_SESSION['professional_error'] ?? null;
unset($_SESSION['professional_error']);

$baseDomain = isset($baseDomain) ? (string)$baseDomain : '';
?>

<div style="max-width: 900px; margin: 0 auto; padding: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">Configurações de Branding</h1>
    <p style="color: #888; margin-bottom: 2rem;">Personalize a aparência dos seus cursos externos</p>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 10px; padding: 1rem; margin-bottom: 2rem; color: #10b981;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 10px; padding: 1rem; margin-bottom: 2rem; color: #ef4444;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="/profissional/configuracoes/branding" method="post" enctype="multipart/form-data">
        <div style="background: #1a1a2e; border: 1px solid #2a2a3e; border-radius: 14px; padding: 2rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; color: #6366f1;">📋 Informações Básicas</h2>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Subdomínio do seu catálogo</label>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <input type="text" name="subdomain" id="partner-subdomain" value="<?= htmlspecialchars((string)$subdomain, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="empresa" autocomplete="off"
                           style="flex:1 1 240px; padding: 0.75rem; background: #14141f; border: 1px solid #2a2a3e; border-radius: 8px; color: #fff;">
                    <div id="partner-subdomain-status" style="min-width:140px; font-size:12px; color:#888;"></div>
                </div>
                <div style="margin-top:6px; font-size: 0.85rem; color: #888;">
                    URL: <strong id="partner-subdomain-preview"></strong>
                </div>
                <div style="margin-top:6px; font-size: 0.85rem; color: #888;">
                    Status: <strong><?= htmlspecialchars((string)$subdomainStatus, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <small style="color: #888; font-size: 0.85rem;">Após salvar, o admin precisa aprovar e apontar no DNS antes de ficar disponível.</small>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Nome da Empresa</label>
                <input type="text" name="company_name" value="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>" 
                       style="width: 100%; padding: 0.75rem; background: #14141f; border: 1px solid #2a2a3e; border-radius: 8px; color: #fff;">
                <small style="color: #888; font-size: 0.85rem;">Nome que aparecerá no topo do site</small>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Logo Principal</label>
                <?php if ($logoUrl): ?>
                    <div style="margin-bottom: 1rem;">
                        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo atual" style="max-width: 150px; border-radius: 8px; border: 1px solid #2a2a3e;">
                    </div>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="checkbox" name="remove_logo" value="1">
                        <span style="color: #ef4444;">Remover logo atual</span>
                    </label>
                <?php endif; ?>
                <input type="file" name="logo_upload" accept="image/*" 
                       style="width: 100%; padding: 0.75rem; background: #14141f; border: 1px solid #2a2a3e; border-radius: 8px; color: #fff;">
                <small style="color: #888; font-size: 0.85rem;">Tamanho recomendado: <strong>200x200px</strong> (quadrado, PNG com fundo transparente)</small>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Favicon</label>
                <?php if ($faviconUrl): ?>
                    <div style="margin-bottom: 1rem; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                        <img src="<?= htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" style="width:32px; height:32px; border-radius: 8px; border: 1px solid #2a2a3e; background:#14141f;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="remove_favicon" value="1">
                            <span style="color: #ef4444;">Remover favicon</span>
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="favicon_upload" accept="image/*"
                       style="width: 100%; padding: 0.75rem; background: #14141f; border: 1px solid #2a2a3e; border-radius: 8px; color: #fff;">
                <small style="color: #888; font-size: 0.85rem;">Recomendado: <strong>512x512px</strong> (PNG). O navegador gera os tamanhos internos.</small>
            </div>
        </div>

        <div style="background: #1a1a2e; border: 1px solid #2a2a3e; border-radius: 14px; padding: 2rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; color: #6366f1;">🎨 Cores do Tema</h2>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Cor Primária</label>
                    <input type="color" name="primary_color" value="<?= htmlspecialchars($primaryColor ?: '#6366f1', ENT_QUOTES, 'UTF-8') ?>" 
                           style="width: 100%; height: 50px; border: 1px solid #2a2a3e; border-radius: 8px; cursor: pointer;">
                    <small style="color: #888; font-size: 0.85rem;">Cor principal dos botões e destaques</small>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Cor Secundária</label>
                    <input type="color" name="secondary_color" value="<?= htmlspecialchars($secondaryColor ?: '#8b5cf6', ENT_QUOTES, 'UTF-8') ?>" 
                           style="width: 100%; height: 50px; border: 1px solid #2a2a3e; border-radius: 8px; cursor: pointer;">
                    <small style="color: #888; font-size: 0.85rem;">Cor secundária para gradientes</small>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Cor do Texto</label>
                    <input type="color" name="text_color" value="<?= htmlspecialchars($textColor ?: '#ffffff', ENT_QUOTES, 'UTF-8') ?>" 
                           style="width: 100%; height: 50px; border: 1px solid #2a2a3e; border-radius: 8px; cursor: pointer;">
                    <small style="color: #888; font-size: 0.85rem;">Cor do texto principal</small>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Cor do Texto dos Botões</label>
                    <input type="color" name="button_text_color" value="<?= htmlspecialchars($buttonTextColor ?: '#ffffff', ENT_QUOTES, 'UTF-8') ?>" 
                           style="width: 100%; height: 50px; border: 1px solid #2a2a3e; border-radius: 8px; cursor: pointer;">
                    <small style="color: #888; font-size: 0.85rem;">Cor do texto dentro dos botões</small>
                </div>
            </div>
        </div>

        <div style="background: #1a1a2e; border: 1px solid #2a2a3e; border-radius: 14px; padding: 2rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; color: #6366f1;">🖼️ Imagens Personalizadas</h2>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Imagem do Header</label>
                <?php if ($headerImageUrl): ?>
                    <div style="margin-bottom: 0.5rem;">
                        <img src="<?= htmlspecialchars($headerImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Header" style="max-width: 300px; border-radius: 8px; border: 1px solid #2a2a3e;">
                    </div>
                <?php endif; ?>
                <input type="file" name="header_image_upload" accept="image/*" 
                       style="width: 100%; padding: 0.75rem; background: #14141f; border: 1px solid #2a2a3e; border-radius: 8px; color: #fff;">
                <small style="color: #888; font-size: 0.85rem;">Tamanho recomendado: <strong>400x80px</strong> (banner horizontal para o cabeçalho)</small>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Imagem Hero (Destaque)</label>
                <?php if ($heroImageUrl): ?>
                    <div style="margin-bottom: 0.5rem;">
                        <img src="<?= htmlspecialchars($heroImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Hero" style="max-width: 300px; border-radius: 8px; border: 1px solid #2a2a3e;">
                    </div>
                <?php endif; ?>
                <input type="file" name="hero_image_upload" accept="image/*" 
                       style="width: 100%; padding: 0.75rem; background: #14141f; border: 1px solid #2a2a3e; border-radius: 8px; color: #fff;">
                <small style="color: #888; font-size: 0.85rem;">Tamanho recomendado: <strong>1200x600px</strong> (imagem principal da homepage)</small>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Imagem do Footer</label>
                <?php if ($footerImageUrl): ?>
                    <div style="margin-bottom: 0.5rem;">
                        <img src="<?= htmlspecialchars($footerImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Footer" style="max-width: 200px; border-radius: 8px; border: 1px solid #2a2a3e;">
                    </div>
                <?php endif; ?>
                <input type="file" name="footer_image_upload" accept="image/*" 
                       style="width: 100%; padding: 0.75rem; background: #14141f; border: 1px solid #2a2a3e; border-radius: 8px; color: #fff;">
                <small style="color: #888; font-size: 0.85rem;">Tamanho recomendado: <strong>300x150px</strong> (certificações, selos, etc)</small>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Imagem de Fundo</label>
                <?php if ($backgroundImageUrl): ?>
                    <div style="margin-bottom: 0.5rem;">
                        <img src="<?= htmlspecialchars($backgroundImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Background" style="max-width: 300px; border-radius: 8px; border: 1px solid #2a2a3e;">
                    </div>
                <?php endif; ?>
                <input type="file" name="background_image_upload" accept="image/*" 
                       style="width: 100%; padding: 0.75rem; background: #14141f; border: 1px solid #2a2a3e; border-radius: 8px; color: #fff;">
                <small style="color: #888; font-size: 0.85rem;">Tamanho recomendado: <strong>1920x1080px</strong> (padrão ou textura para fundo do site)</small>
            </div>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" style="flex: 1; padding: 1rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 1rem; cursor: pointer;">
                💾 Salvar Configurações
            </button>
            <a href="/profissional" style="padding: 1rem 2rem; background: transparent; color: #888; border: 1px solid #2a2a3e; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
(function () {
    var input = document.getElementById('partner-subdomain');
    var statusEl = document.getElementById('partner-subdomain-status');
    var previewEl = document.getElementById('partner-subdomain-preview');
    if (!input || !statusEl || !previewEl) return;

    var baseDomain = <?= json_encode($baseDomain !== '' ? $baseDomain : '') ?>;

    function normalize(v) {
        v = (v || '').toString().toLowerCase().trim();
        v = v.replace(/\s+/g, '');
        v = v.replace(/[^a-z0-9\-]/g, '');
        v = v.replace(/^-+|-+$/g, '');
        v = v.replace(/\-+/g, '-');
        return v;
    }

    function setPreview(v) {
        if (!v) {
            previewEl.textContent = baseDomain ? ('https://{sub}.' + baseDomain + '/') : 'https://{sub}/';
            return;
        }
        previewEl.textContent = baseDomain ? ('https://' + v + '.' + baseDomain + '/') : ('https://' + v + '/');
    }

    var t = null;
    function check() {
        var v = normalize(input.value);
        setPreview(v);
        if (!v) {
            statusEl.textContent = 'Digite um prefixo';
            statusEl.style.color = '#888';
            return;
        }
        statusEl.textContent = 'Verificando...';
        statusEl.style.color = '#888';

        fetch('/profissional/subdominio/check?value=' + encodeURIComponent(v), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) {
                    statusEl.textContent = (data && data.error) ? data.error : 'Inválido';
                    statusEl.style.color = '#ef4444';
                    return;
                }
                if (data.available) {
                    statusEl.textContent = 'Disponível';
                    statusEl.style.color = '#10b981';
                } else {
                    statusEl.textContent = 'Indisponível';
                    statusEl.style.color = '#ef4444';
                }
            })
            .catch(function () {
                statusEl.textContent = 'Erro ao verificar';
                statusEl.style.color = '#ef4444';
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(check, 350);
    });

    check();
})();
</script>

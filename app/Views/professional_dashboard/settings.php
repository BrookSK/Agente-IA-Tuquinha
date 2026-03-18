<?php
/** @var array $user */
/** @var array|null $branding */

$companyName = $branding['company_name'] ?? '';
$logoUrl = $branding['logo_url'] ?? '';
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
?>

<div style="max-width: 900px; margin: 0 auto; padding: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">Configurações de Branding</h1>
    <p style="color: #888; margin-bottom: 2rem;">Personalize a aparência dos seus cursos externos</p>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 10px; padding: 1rem; margin-bottom: 2rem; color: #10b981;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="/profissional/configuracoes/branding" method="post" enctype="multipart/form-data">
        <div style="background: #1a1a2e; border: 1px solid #2a2a3e; border-radius: 14px; padding: 2rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; color: #6366f1;">📋 Informações Básicas</h2>
            
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

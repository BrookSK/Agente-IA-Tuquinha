<?php
/** @var array $user */
/** @var array $branding */
/** @var array $profile */

$userId = (int)($user['id'] ?? 0);
$avatarPath = isset($profile['avatar_path']) ? trim((string)$profile['avatar_path']) : '';
$userName = (string)($user['name'] ?? '');
$initial = 'U';
if ($userName !== '') {
    $initial = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8');
}

// Branding colors
$primaryColor = !empty($branding['primary_color']) ? $branding['primary_color'] : '#e53935';
$secondaryColor = !empty($branding['secondary_color']) ? $branding['secondary_color'] : '#ff6f60';
$accentColor = !empty($branding['accent_color']) ? $branding['accent_color'] : '#4caf50';
?>

<style>
    .edit-profile-container {
        max-width: 100%;
        margin: 0 auto;
        padding: 48px 24px 80px;
    }
    
    .edit-profile-header {
        max-width: 780px;
        margin: 0 auto 32px;
        display: flex;
        align-items: center;
        gap: 18px;
    }
    
    .edit-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: var(--surface-card);
        border: 1.5px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        font-size: .8rem;
        font-weight: 600;
        color: var(--text-primary);
        text-decoration: none;
        cursor: pointer;
        transition: box-shadow .15s, transform .15s;
    }
    
    .edit-back-btn:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        transform: translateY(-1px);
        color: var(--text-primary);
    }
    
    .edit-page-title {
        font-size: clamp(1.6rem, 4vw, 2.4rem);
        font-weight: 800;
        letter-spacing: -.03em;
        color: var(--text-primary);
        margin: 0;
    }
    
    .edit-form-card {
        max-width: 780px;
        margin: 0 auto;
        background: var(--surface-card);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04);
        overflow: hidden;
        animation: fadeUp .45s ease both;
    }
    
    @keyframes fadeUp {
        from { opacity:0; transform:translateY(16px); }
        to { opacity:1; transform:translateY(0); }
    }
    
    .edit-form-section {
        padding: 28px 36px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    
    .edit-form-section:last-of-type {
        border-bottom: none;
    }
    
    .edit-section-label {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--text-secondary);
        margin-bottom: 20px;
    }
    
    .edit-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    .edit-col-full {
        grid-column: 1 / -1;
    }
    
    .edit-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .edit-field-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-primary);
        letter-spacing: .01em;
    }
    
    .edit-field-hint {
        font-size: .72rem;
        color: var(--text-secondary);
        margin-top: 2px;
    }
    
    .edit-input, .edit-textarea, .edit-select {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        background: var(--surface-subtle);
        font-size: .875rem;
        color: var(--text-primary);
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }
    
    .edit-input:focus, .edit-textarea:focus, .edit-select:focus {
        border-color: <?= $primaryColor ?>;
        box-shadow: 0 0 0 3px rgba(<?= hexdec(substr($primaryColor, 1, 2)) ?>, <?= hexdec(substr($primaryColor, 3, 2)) ?>, <?= hexdec(substr($primaryColor, 5, 2)) ?>, 0.12);
        background: var(--surface-card);
    }
    
    .edit-input::placeholder, .edit-textarea::placeholder {
        color: var(--text-secondary);
    }
    
    .edit-textarea {
        resize: vertical;
        min-height: 100px;
        line-height: 1.6;
    }
    
    .edit-select-wrap {
        position: relative;
    }
    
    .edit-select-wrap svg {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: var(--text-secondary);
    }
    
    .edit-select {
        padding-right: 36px;
        cursor: pointer;
        appearance: none;
    }
    
    .edit-input-icon-wrap {
        position: relative;
    }
    
    .edit-input-icon-wrap .icon-prefix {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        font-size: .85rem;
        font-weight: 500;
        pointer-events: none;
    }
    
    .edit-input-icon-wrap .edit-input {
        padding-left: 34px;
    }
    
    .edit-avatar-section {
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .edit-avatar-preview {
        position: relative;
        width: 80px;
        height: 80px;
        flex-shrink: 0;
    }
    
    .edit-avatar-preview img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--surface-card);
        box-shadow: 0 2px 10px rgba(0,0,0,.12);
    }
    
    .edit-avatar-edit-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
        border: 2px solid var(--surface-card);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 1px 4px rgba(0,0,0,.2);
    }
    
    .edit-upload-area {
        flex: 1;
        min-width: 200px;
    }
    
    .edit-upload-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        background: var(--surface-subtle);
        border: 1.5px dashed rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        font-size: .82rem;
        font-weight: 500;
        color: var(--text-primary);
        cursor: pointer;
        transition: border-color .2s, background .2s;
        width: 100%;
        justify-content: center;
    }
    
    .edit-upload-btn:hover {
        border-color: <?= $primaryColor ?>;
        background: var(--surface-card);
        color: <?= $primaryColor ?>;
    }
    
    .edit-upload-btn input[type=file] {
        display: none;
    }
    
    .edit-cover-upload {
        width: 100%;
        height: 100px;
        border: 1.5px dashed rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        background: var(--surface-subtle);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        font-size: .85rem;
        color: var(--text-secondary);
        font-weight: 500;
        transition: border-color .2s, background .2s;
        overflow: hidden;
        position: relative;
    }
    
    .edit-cover-upload:hover {
        border-color: <?= $primaryColor ?>;
        background: var(--surface-card);
        color: <?= $primaryColor ?>;
    }
    
    .edit-cover-upload input[type=file] {
        display: none;
    }
    
    .edit-form-actions {
        padding: 20px 36px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        background: var(--surface-subtle);
    }
    
    .edit-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 22px;
        border-radius: 10px;
        border: none;
        font-size: .875rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: transform .15s, box-shadow .15s;
    }
    
    .edit-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,.12);
    }
    
    .edit-btn:active {
        transform: translateY(0);
    }
    
    .edit-btn-cancel {
        background: var(--surface-card);
        border: 1.5px solid rgba(255, 255, 255, 0.1);
        color: var(--text-secondary);
    }
    
    .edit-btn-cancel:hover {
        color: #c0392b;
        border-color: rgba(192, 57, 43, 0.3);
        background: rgba(192, 57, 43, 0.05);
    }
    
    .edit-btn-save {
        background: linear-gradient(135deg, <?= $primaryColor ?> 0%, <?= $secondaryColor ?> 100%);
        color: #fff;
    }
    
    @media (max-width: 600px) {
        .edit-profile-container {
            padding: 24px 14px 60px;
        }
        .edit-form-section {
            padding: 22px 18px;
        }
        .edit-form-actions {
            padding: 16px 18px;
        }
        .edit-grid-2 {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="edit-profile-container">
    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="max-width: 780px; margin: 0 auto 20px; background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); color: #4caf50; padding: 14px 18px; border-radius: 10px;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="max-width: 780px; margin: 0 auto 20px; background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); color: #dc3545; padding: 14px 18px; border-radius: 10px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="/painel-externo/perfil/salvar" method="post" enctype="multipart/form-data" class="edit-form-card">
        
        <!-- Foto de Perfil -->
        <div class="edit-form-section">
            <p class="edit-section-label">Foto de Perfil</p>
            <div class="edit-avatar-section">
                <div class="edit-avatar-preview">
                    <?php if ($avatarPath !== ''): ?>
                        <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" id="avatar-img"/>
                    <?php else: ?>
                        <div style="width:80px; height:80px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:700; color:#050509; border: 3px solid var(--surface-card); box-shadow: 0 2px 10px rgba(0,0,0,.12);">
                            <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    <label class="edit-avatar-edit-btn" for="avatar-file" title="Alterar foto">
                        <svg width="12" height="12" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </label>
                </div>
                <div class="edit-upload-area">
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <label class="edit-upload-btn" for="avatar-file" style="flex: 1; min-width: 150px;">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            Escolher ficheiro
                            <input type="file" id="avatar-file" name="avatar_file" accept="image/jpeg,image/png,image/gif"/>
                        </label>
                        <?php if ($avatarPath !== ''): ?>
                            <button type="submit" name="remove_avatar" value="1" class="edit-upload-btn" style="flex: 0 0 auto; border-color: rgba(220, 53, 69, 0.3); color: #dc3545;" onclick="return confirm('Tem certeza que deseja remover a foto de perfil?');">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                                Remover foto
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="edit-field-hint" style="margin-top:8px;">JPG, PNG ou GIF · Até 2 MB</p>
                </div>
            </div>
        </div>

        <!-- Capa -->
        <div class="edit-form-section">
            <p class="edit-section-label">Capa do Perfil</p>
            <label class="edit-cover-upload" for="cover-file">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
                Escolher imagem de capa
                <input type="file" id="cover-file" name="cover_file" accept="image/*"/>
            </label>
            <p class="edit-field-hint" style="margin-top:8px;">Recomendado: imagem larga · Até 4 MB</p>
        </div>

        <!-- Informações Básicas -->
        <div class="edit-form-section">
            <p class="edit-section-label">Informações Básicas</p>
            <div class="edit-grid-2">
                <div class="edit-field">
                    <label for="language" class="edit-field-label">Idioma</label>
                    <div class="edit-select-wrap">
                        <select id="language" name="language" class="edit-select">
                            <?php $lang = (string)($profile['language'] ?? ''); ?>
                            <option value="">Selecione</option>
                            <option value="pt-BR" <?= $lang === 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option>
                            <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>Inglês</option>
                            <option value="es" <?= $lang === 'es' ? 'selected' : '' ?>>Espanhol</option>
                        </select>
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                </div>
                <div class="edit-field">
                    <label for="profile_category" class="edit-field-label">Categoria</label>
                    <input id="profile_category" name="profile_category" type="text" value="<?= htmlspecialchars((string)($profile['profile_category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Designer, Empreendedor, Estudante" class="edit-input">
                </div>
                <div class="edit-field">
                    <label for="age" class="edit-field-label">Idade</label>
                    <input id="age" name="age" type="number" min="0" max="120" value="<?= isset($profile['age']) ? (int)$profile['age'] : '' ?>" class="edit-input">
                </div>
                <div class="edit-field">
                    <label for="birthday" class="edit-field-label">Aniversário</label>
                    <input id="birthday" name="birthday" type="date" value="<?= htmlspecialchars((string)($profile['birthday'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="edit-input">
                </div>
                <div class="edit-field edit-col-full">
                    <label for="relationship_status" class="edit-field-label">Relacionamento</label>
                    <input id="relationship_status" name="relationship_status" type="text" value="<?= htmlspecialchars((string)($profile['relationship_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="edit-input">
                </div>
                <div class="edit-field">
                    <label for="hometown" class="edit-field-label">Cidade Natal</label>
                    <input id="hometown" name="hometown" type="text" value="<?= htmlspecialchars((string)($profile['hometown'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="edit-input">
                </div>
                <div class="edit-field">
                    <label for="location" class="edit-field-label">Onde Mora</label>
                    <input id="location" name="location" type="text" value="<?= htmlspecialchars((string)($profile['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="edit-input">
                </div>
                <div class="edit-field edit-col-full">
                    <label for="website" class="edit-field-label">Site Pessoal</label>
                    <input id="website" name="website" type="url" value="<?= htmlspecialchars((string)($profile['website'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://seusite.com" class="edit-input">
                </div>
                <div class="edit-field">
                    <label for="instagram" class="edit-field-label">Instagram</label>
                    <div class="edit-input-icon-wrap">
                        <span class="icon-prefix">@</span>
                        <input id="instagram" name="instagram" type="text" value="<?= htmlspecialchars((string)($profile['instagram'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="usuario" class="edit-input">
                    </div>
                </div>
                <div class="edit-field">
                    <label for="facebook" class="edit-field-label">Facebook</label>
                    <div class="edit-input-icon-wrap">
                        <span class="icon-prefix">@</span>
                        <input id="facebook" name="facebook" type="text" value="<?= htmlspecialchars((string)($profile['facebook'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="usuario" class="edit-input">
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="edit-form-actions">
            <a href="/painel-externo/perfil?user_id=<?= $userId ?>" class="edit-btn edit-btn-cancel">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                Cancelar
            </a>
            <button type="submit" class="edit-btn edit-btn-save">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Salvar Alterações
            </button>
        </div>
    </form>
</div>

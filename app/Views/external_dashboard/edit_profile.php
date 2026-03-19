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
    .edit-profile-btn-primary {
        background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
        border: none;
        color: #fff;
    }
    .edit-profile-btn-primary:hover {
        opacity: 0.9;
        color: #fff;
    }
    .form-section {
        padding: 16px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .form-section:last-child {
        border-bottom: none;
    }
    .edit-profile-form .form-control,
    .edit-profile-form .form-select {
        background: var(--surface-subtle) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: var(--text-primary) !important;
        padding: 10px 14px !important;
    }
    .edit-profile-form .form-control:focus,
    .edit-profile-form .form-select:focus {
        border-color: <?= $primaryColor ?> !important;
        box-shadow: 0 0 0 0.2rem rgba(<?= hexdec(substr($primaryColor, 1, 2)) ?>, <?= hexdec(substr($primaryColor, 3, 2)) ?>, <?= hexdec(substr($primaryColor, 5, 2)) ?>, 0.25) !important;
    }
</style>

<div class="container-fluid" style="padding: 0; margin: 0; width: 100%;">
    <div style="max-width: 900px; margin: 0 auto; padding: 20px 24px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin: 0;">Editar Meu Perfil</h1>
                <a href="/painel-externo/perfil?user_id=<?= $userId ?>" class="btn btn-outline-secondary" style="padding: 8px 16px; border-radius: 8px; text-decoration: none;">
                    ← Voltar ao Perfil
                </a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); color: #4caf50; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px;">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); color: #dc3545; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="card" style="background: var(--surface-card); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 28px;">
                <form action="/painel-externo/perfil/salvar" method="post" enctype="multipart/form-data" class="edit-profile-form">
                    
                    <!-- Avatar -->
                    <div class="form-section">
                        <label style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Foto de Perfil</label>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <div style="width:80px; height:80px; border-radius:50%; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:700; color:#050509;">
                                <?php if ($avatarPath !== ''): ?>
                                    <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;">
                                <?php else: ?>
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <input type="file" name="avatar_file" accept="image/*" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary);">
                                <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">JPG, PNG ou GIF • Até 2 MB</div>
                            </div>
                        </div>
                    </div>

                    <!-- Cover -->
                    <div class="form-section">
                        <label style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Capa do Perfil</label>
                        <input type="file" name="cover_file" accept="image/*" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary);">
                        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Recomendado: imagem larga • Até 4 MB</div>
                    </div>

                    <!-- Nickname -->
                    <div class="form-section">
                        <label for="nickname" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Nickname</label>
                        <input id="nickname" name="nickname" type="text" value="<?= htmlspecialchars((string)($user['nickname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: joao_silva" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Apenas letras minúsculas, números, _ e -</div>
                    </div>

                    <!-- About Me -->
                    <div class="form-section">
                        <label for="about_me" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Sobre Mim</label>
                        <textarea id="about_me" name="about_me" rows="4" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px; resize: vertical;"><?= htmlspecialchars((string)($profile['about_me'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="language" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Idioma</label>
                            <select id="language" name="language" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                                <?php $lang = (string)($profile['language'] ?? ''); ?>
                                <option value="">Selecione</option>
                                <option value="pt-BR" <?= $lang === 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option>
                                <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>Inglês</option>
                                <option value="es" <?= $lang === 'es' ? 'selected' : '' ?>>Espanhol</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="profile_category" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Categoria</label>
                            <input id="profile_category" name="profile_category" type="text" value="<?= htmlspecialchars((string)($profile['profile_category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Designer, Empreendedor" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label for="age" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Idade</label>
                            <input id="age" name="age" type="number" min="0" max="120" value="<?= isset($profile['age']) ? (int)$profile['age'] : '' ?>" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                        <div class="col-md-4">
                            <label for="birthday" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Aniversário</label>
                            <input id="birthday" name="birthday" type="date" value="<?= htmlspecialchars((string)($profile['birthday'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                        <div class="col-md-4">
                            <label for="relationship_status" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Relacionamento</label>
                            <input id="relationship_status" name="relationship_status" type="text" value="<?= htmlspecialchars((string)($profile['relationship_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="hometown" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Cidade Natal</label>
                            <input id="hometown" name="hometown" type="text" value="<?= htmlspecialchars((string)($profile['hometown'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                        <div class="col-md-6">
                            <label for="location" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Onde Mora</label>
                            <input id="location" name="location" type="text" value="<?= htmlspecialchars((string)($profile['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label for="website" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Site Pessoal</label>
                            <input id="website" name="website" type="text" value="<?= htmlspecialchars((string)($profile['website'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://seusite.com" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                        <div class="col-md-4">
                            <label for="instagram" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Instagram</label>
                            <input id="instagram" name="instagram" type="text" value="<?= htmlspecialchars((string)($profile['instagram'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="@usuario" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                        <div class="col-md-4">
                            <label for="facebook" style="display:block; font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Facebook</label>
                            <input id="facebook" name="facebook" type="text" value="<?= htmlspecialchars((string)($profile['facebook'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="@usuario" class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px;">
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; justify-content:flex-end; padding-top:16px; border-top:1px solid var(--border-subtle); margin-top:8px;">
                        <a href="/painel-externo/perfil?user_id=<?= $userId ?>" class="btn btn-outline-secondary" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; border: 1px solid var(--border-subtle);">
                            Cancelar
                        </a>
                        <button type="submit" class="btn edit-profile-btn-primary" style="padding: 10px 24px; border-radius: 8px; font-weight: 600;">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
    </div>
</div>

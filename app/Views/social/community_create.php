<?php

/** @var array $user */
/** @var array $categories */
/** @var array $old */
/** @var string|null $error */

$name = (string)($old['name'] ?? '');
$description = (string)($old['description'] ?? '');
$language = (string)($old['language'] ?? '');
$category = (string)($old['category'] ?? '');
$communityType = (string)($old['community_type'] ?? 'public');
$postingPolicy = (string)($old['posting_policy'] ?? 'any_member');
$forumType = (string)($old['forum_type'] ?? 'non_anonymous');
$moderatorsEmails = (string)($old['moderators_emails'] ?? '');

?>
<div style="max-width: 980px; margin: 0 auto; display:flex; flex-direction:column; gap:14px;">
    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:10px; font-size:13px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <div>
                <h1 style="font-size:18px; margin-bottom:2px; color:var(--text-primary);">Criar nova comunidade</h1>
                <p style="font-size:12px; color:var(--text-secondary); margin:0;">Monte um espaço para pessoas com os mesmos interesses, no estilo Orkut.</p>
            </div>
            <a href="/comunidades" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar para lista</a>
        </div>

        <form action="/comunidades/criar" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--text-primary);">
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <div style="flex:1 1 220px; min-width:0;">
                    <label for="name" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Nome da comunidade</label>
                    <input id="name" name="name" type="text" maxlength="255" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                </div>
                <div style="flex:0 0 200px;">
                    <label for="language" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Idioma</label>
                    <select id="language" name="language" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        <option value="">Selecione</option>
                        <option value="pt-BR" <?= $language === 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option>
                        <option value="en" <?= $language === 'en' ? 'selected' : '' ?>>Inglês</option>
                        <option value="es" <?= $language === 'es' ? 'selected' : '' ?>>Espanhol</option>
                    </select>
                </div>
                <div style="flex:0 0 220px;">
                    <label for="category" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Categoria</label>
                    <select id="category" name="category" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        <option value="">Selecione</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $c): ?>
                                <?php $cStr = (string)$c; ?>
                                <option value="<?= htmlspecialchars($cStr, ENT_QUOTES, 'UTF-8') ?>" <?= $category === $cStr ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cStr, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div>
                <label for="description" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Descrição</label>
                <textarea id="description" name="description" rows="3" maxlength="4000" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
                <div style="flex:1 1 220px; min-width:0;">
                    <label for="profile_image" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Imagem de perfil</label>
                    <input id="profile_image" type="file" name="profile_image" accept="image/*" style="font-size:12px;">
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Opcional. Dimensões recomendadas: <strong>400×400</strong>.</div>
                </div>
                <div style="flex:1 1 220px; min-width:0;">
                    <label for="cover_image" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Imagem de capa</label>
                    <input id="cover_image" type="file" name="cover_image" accept="image/*" style="font-size:12px;">
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Opcional. Usada na tela da comunidade. Dimensões recomendadas: <strong>1200×300</strong>.</div>
                </div>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:12px;">
                <div style="flex:1 1 200px; min-width:0;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Tipo de comunidade</div>
                    <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--text-secondary); margin-bottom:2px;">
                        <input type="radio" name="community_type" value="public" <?= $communityType !== 'private' ? 'checked' : '' ?> style="accent-color:#e53935;">
                        <span>Pública (qualquer um pode solicitar participar)</span>
                    </label>
                    <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--text-secondary);">
                        <input type="radio" name="community_type" value="private" <?= $communityType === 'private' ? 'checked' : '' ?> style="accent-color:#e53935;">
                        <span>Privada (apenas com convite)</span>
                    </label>
                </div>
                <div style="flex:1 1 220px; min-width:0;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Privacidade do conteúdo</div>
                    <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--text-secondary); margin-bottom:2px;">
                        <input type="radio" name="posting_policy" value="any_member" <?= $postingPolicy !== 'owner_moderators' ? 'checked' : '' ?> style="accent-color:#e53935;">
                        <span>Qualquer membro pode postar</span>
                    </label>
                    <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--text-secondary);">
                        <input type="radio" name="posting_policy" value="owner_moderators" <?= $postingPolicy === 'owner_moderators' ? 'checked' : '' ?> style="accent-color:#e53935;">
                        <span>Apenas dono e moderadores postam</span>
                    </label>
                </div>
                <div style="flex:1 1 200px; min-width:0;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Fórum</div>
                    <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--text-secondary); margin-bottom:2px;">
                        <input type="radio" name="forum_type" value="non_anonymous" <?= $forumType !== 'anonymous' ? 'checked' : '' ?> style="accent-color:#e53935;">
                        <span>Não-anônimo (mostra o nome)</span>
                    </label>
                    <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--text-secondary);">
                        <input type="radio" name="forum_type" value="anonymous" <?= $forumType === 'anonymous' ? 'checked' : '' ?> style="accent-color:#e53935;">
                        <span>Anônimo (apenas para membros)</span>
                    </label>
                </div>
            </div>

            <div>
                <label for="moderators_emails" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Moderadores (e-mail)</label>
                <textarea id="moderators_emails" name="moderators_emails" rows="2" placeholder="Digite e-mails separados por vírgula ou um por linha" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; resize:vertical;"><?= htmlspecialchars($moderatorsEmails, ENT_QUOTES, 'UTF-8') ?></textarea>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Se o e-mail já estiver cadastrado no Tuquinha, a pessoa será adicionada como moderadora automaticamente.</div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:4px;">
                <a href="/comunidades" style="font-size:12px; color:var(--text-secondary); text-decoration:none; padding:5px 10px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle);">Cancelar</a>
                <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">Criar comunidade</button>
            </div>
        </form>
    </section>
</div>

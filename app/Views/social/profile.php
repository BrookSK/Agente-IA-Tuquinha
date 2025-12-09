<?php

$isOwnProfile = (int)($user['id'] ?? 0) === (int)($profileUser['id'] ?? 0);
$displayName = trim((string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? ''));
if ($displayName === '') {
    $displayName = 'Perfil';
}

$baseName = (string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? 'U');
$initial = mb_strtoupper(mb_substr($baseName, 0, 1, 'UTF-8'), 'UTF-8');

$friendsCount = is_array($friends) ? count($friends) : 0;
$scrapsCount = is_array($scraps) ? count($scraps) : 0;
$communitiesCount = is_array($communities) ? count($communities) : 0;

$friendStatus = null;
$requestedById = null;
if (is_array($friendship)) {
    $friendStatus = $friendship['status'] ?? null;
    $requestedById = isset($friendship['requested_by_user_id']) ? (int)$friendship['requested_by_user_id'] : null;
}

$currentId = (int)($user['id'] ?? 0);
$profileId = (int)($profileUser['id'] ?? 0);

?>
<div style="max-width: 980px; margin: 0 auto; display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap;">
    <aside style="flex: 0 0 260px; background:#111118; border-radius:18px; border:1px solid #272727; padding:14px;">
        <div style="display:flex; flex-direction:column; align-items:center; gap:8px; margin-bottom:10px;">
            <div style="width:96px; height:96px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:40px; font-weight:700; color:#050509;">
                <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div style="text-align:center;">
                <div style="font-size:18px; font-weight:650;">
                    <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php if (!empty($profileUser['name']) && $displayName !== $profileUser['name']): ?>
                    <div style="font-size:12px; color:#b0b0b0;">
                        <?= htmlspecialchars($profileUser['name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; gap:6px; margin-bottom:10px;">
            <div style="flex:1; background:#050509; border-radius:12px; padding:6px 8px; border:1px solid #272727; text-align:center;">
                <div style="font-size:11px; color:#b0b0b0; text-transform:uppercase; letter-spacing:0.08em;">Amigos</div>
                <div style="font-size:16px; font-weight:650;">
                    <?= (int)$friendsCount ?>
                </div>
            </div>
            <div style="flex:1; background:#050509; border-radius:12px; padding:6px 8px; border:1px solid #272727; text-align:center;">
                <div style="font-size:11px; color:#b0b0b0; text-transform:uppercase; letter-spacing:0.08em;">Scraps</div>
                <div style="font-size:16px; font-weight:650;">
                    <?= (int)$scrapsCount ?>
                </div>
            </div>
            <div style="flex:1; background:#050509; border-radius:12px; padding:6px 8px; border:1px solid #272727; text-align:center;">
                <div style="font-size:11px; color:#b0b0b0; text-transform:uppercase; letter-spacing:0.08em;">Comun.</div>
                <div style="font-size:16px; font-weight:650;">
                    <?= (int)$communitiesCount ?>
                </div>
            </div>
        </div>

        <?php if (!$isOwnProfile): ?>
            <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:8px;">
                <?php if ($friendStatus === 'accepted'): ?>
                    <div style="font-size:12px; color:#8bc34a; background:#10330f; border-radius:10px; border:1px solid #3aa857; padding:6px 8px; text-align:center;">
                        Vocês são amigos no Orkut do Tuquinha.
                    </div>
                <?php elseif ($friendStatus === 'pending' && $requestedById === $currentId): ?>
                    <div style="font-size:12px; color:#ffb74d; background:#311; border-radius:10px; border:1px solid #a33; padding:6px 8px; text-align:center;">
                        Pedido de amizade enviado. Aguardando resposta.
                    </div>
                <?php elseif ($friendStatus === 'pending' && $requestedById !== $currentId): ?>
                    <form action="/amigos/decidir" method="post" style="display:flex; flex-direction:column; gap:6px;">
                        <div style="font-size:12px; color:#b0b0b0; text-align:center;">Esta pessoa quer ser sua amiga.</div>
                        <input type="hidden" name="user_id" value="<?= (int)$profileId ?>">
                        <div style="display:flex; gap:6px;">
                            <button type="submit" name="decision" value="accepted" style="flex:1; border:none; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#4caf50,#8bc34a); color:#050509;">Aceitar</button>
                            <button type="submit" name="decision" value="rejected" style="flex:1; border:none; border-radius:999px; padding:6px 10px; font-size:12px; cursor:pointer; background:#311; color:#ffbaba; border:1px solid #a33;">Recusar</button>
                        </div>
                    </form>
                <?php else: ?>
                    <form action="/amigos/solicitar" method="post">
                        <input type="hidden" name="user_id" value="<?= (int)$profileId ?>">
                        <button type="submit" style="width:100%; border:none; border-radius:999px; padding:7px 10px; font-size:13px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; margin-bottom:4px;">
                            Adicionar como amigo
                        </button>
                    </form>
                <?php endif; ?>

                <a href="#scraps" style="display:block; text-align:center; font-size:12px; color:#ff6f60; text-decoration:none;">Ir para os scraps</a>
            </div>
        <?php else: ?>
            <div style="font-size:12px; color:#b0b0b0; margin-bottom:8px; text-align:center;">
                Este é o seu perfil social dentro da comunidade do Tuquinha.
            </div>
        <?php endif; ?>

        <?php if (!empty($profile['visits_count'])): ?>
            <div style="font-size:11px; color:#8d8d8d; margin-top:4px; text-align:center;">
                <?= (int)$profile['visits_count'] ?> visita(s) neste perfil.
            </div>
        <?php endif; ?>
    </aside>

    <main style="flex: 1 1 0; min-width: 0; display:flex; flex-direction:column; gap:12px;">
        <?php if (!empty($error)): ?>
            <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:10px; font-size:13px;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:10px; font-size:13px;">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:6px;">Sobre</h2>
            <div style="font-size:13px; color:#b0b0b0;">
                <?php if (!empty($profile['about_me'])): ?>
                    <p style="margin-bottom:6px;"><?= nl2br(htmlspecialchars((string)$profile['about_me'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php else: ?>
                    <p style="margin-bottom:6px;">Nenhuma descrição adicionada ainda.</p>
                <?php endif; ?>
                <?php if (!empty($profileUser['global_memory'])): ?>
                    <p style="margin-bottom:4px; font-size:12px; color:#8d8d8d;">Memórias globais que o Tuquinha usa sobre esta pessoa:</p>
                    <p style="font-size:12px;"><?= nl2br(htmlspecialchars((string)$profileUser['global_memory'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:6px;">Interesses</h2>
            <div style="display:flex; flex-wrap:wrap; gap:6px; font-size:12px; color:#b0b0b0;">
                <?php if (!empty($profile['interests'])): ?>
                    <span style="background:#050509; border-radius:999px; padding:4px 8px; border:1px solid #272727;">Interesses: <?= htmlspecialchars((string)$profile['interests'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['favorite_music'])): ?>
                    <span style="background:#050509; border-radius:999px; padding:4px 8px; border:1px solid #272727;">Músicas: <?= htmlspecialchars((string)$profile['favorite_music'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['favorite_movies'])): ?>
                    <span style="background:#050509; border-radius:999px; padding:4px 8px; border:1px solid #272727;">Filmes: <?= htmlspecialchars((string)$profile['favorite_movies'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['favorite_books'])): ?>
                    <span style="background:#050509; border-radius:999px; padding:4px 8px; border:1px solid #272727;">Livros: <?= htmlspecialchars((string)$profile['favorite_books'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['website'])): ?>
                    <a href="<?= htmlspecialchars((string)$profile['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="background:#050509; border-radius:999px; padding:4px 8px; border:1px solid #272727; color:#ff6f60;">Site pessoal</a>
                <?php endif; ?>
                <?php if (empty($profile['interests']) && empty($profile['favorite_music']) && empty($profile['favorite_movies']) && empty($profile['favorite_books']) && empty($profile['website'])): ?>
                    <span>Nenhum interesse cadastrado ainda.</span>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isOwnProfile): ?>
            <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
                <h2 style="font-size:16px; margin-bottom:6px;">Editar meu perfil social</h2>
                <form action="/perfil/salvar" method="post" style="display:flex; flex-direction:column; gap:8px; font-size:13px; color:#f5f5f5;">
                    <div>
                        <label for="about_me" style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:3px;">Sobre mim</label>
                        <textarea id="about_me" name="about_me" rows="3" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"><?= htmlspecialchars((string)($profile['about_me'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 180px; min-width:0;">
                            <label for="interests" style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:3px;">Interesses</label>
                            <input id="interests" name="interests" type="text" value="<?= htmlspecialchars((string)($profile['interests'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                        </div>
                        <div style="flex:1 1 180px; min-width:0;">
                            <label for="favorite_music" style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:3px;">Músicas favoritas</label>
                            <input id="favorite_music" name="favorite_music" type="text" value="<?= htmlspecialchars((string)($profile['favorite_music'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                        </div>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 180px; min-width:0;">
                            <label for="favorite_movies" style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:3px;">Filmes favoritos</label>
                            <input id="favorite_movies" name="favorite_movies" type="text" value="<?= htmlspecialchars((string)($profile['favorite_movies'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                        </div>
                        <div style="flex:1 1 180px; min-width:0;">
                            <label for="favorite_books" style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:3px;">Livros favoritos</label>
                            <input id="favorite_books" name="favorite_books" type="text" value="<?= htmlspecialchars((string)($profile['favorite_books'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                        </div>
                    </div>
                    <div>
                        <label for="website" style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:3px;">Site pessoal</label>
                        <input id="website" name="website" type="text" value="<?= htmlspecialchars((string)($profile['website'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://seusite.com" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                    </div>
                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">Salvar perfil</button>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section id="scraps" style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <h2 style="font-size:16px;">Scraps</h2>
                <span style="font-size:12px; color:#b0b0b0;">Recados públicos no mural</span>
            </div>

            <?php if (!$isOwnProfile): ?>
                <form action="/perfil/scrap" method="post" style="margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                    <input type="hidden" name="to_user_id" value="<?= (int)$profileId ?>">
                    <textarea name="body" rows="3" placeholder="Escreva um scrap carinhoso, uma dúvida ou um oi nostálgico..." style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"></textarea>
                    <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:12px; cursor:pointer;">Enviar scrap</button>
                </form>
            <?php endif; ?>

            <?php if (empty($scraps)): ?>
                <div style="font-size:13px; color:#b0b0b0;">Nenhum scrap ainda. Seja o primeiro a deixar um recado aqui.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($scraps as $s): ?>
                        <div style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; font-size:12px; color:#b0b0b0;">
                                <div>
                                    <strong>
                                        <a href="/perfil?user_id=<?= (int)($s['from_user_id'] ?? 0) ?>" style="color:#ff6f60; text-decoration:none;">
                                            <?= htmlspecialchars((string)($s['from_user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </strong>
                                </div>
                                <?php if (!empty($s['created_at'])): ?>
                                    <span><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$s['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:13px; color:#f5f5f5;">
                                <?= nl2br(htmlspecialchars((string)($s['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <aside style="flex: 0 0 260px; background:#111118; border-radius:18px; border:1px solid #272727; padding:12px; display:flex; flex-direction:column; gap:10px; min-height:0;">
        <section style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px;">
            <h3 style="font-size:14px; margin-bottom:6px;">Depoimentos</h3>
            <?php if (empty($publicTestimonials)): ?>
                <div style="font-size:12px; color:#b0b0b0;">Nenhum depoimento público ainda.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($publicTestimonials as $t): ?>
                        <div style="border-radius:10px; border:1px solid #272727; padding:6px 8px;">
                            <div style="font-size:12px; color:#b0b0b0; margin-bottom:3px;">
                                <strong><?= htmlspecialchars((string)($t['from_user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($t['created_at'])): ?>
                                    <span style="margin-left:4px;">· <?= htmlspecialchars(date('d/m/Y', strtotime((string)$t['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:13px; color:#f5f5f5;">
                                <?= nl2br(htmlspecialchars((string)($t['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!$isOwnProfile): ?>
            <section style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px;">
                <h3 style="font-size:14px; margin-bottom:6px;">Escrever depoimento</h3>
                <form action="/perfil/depoimento" method="post" style="display:flex; flex-direction:column; gap:6px;">
                    <input type="hidden" name="to_user_id" value="<?= (int)$profileId ?>">
                    <textarea name="body" rows="3" placeholder="Conte algo legal sobre essa pessoa, como no Orkut raiz." style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:12px; resize:vertical;"></textarea>
                    <label style="font-size:11px; color:#b0b0b0; display:flex; align-items:center; gap:4px;">
                        <input type="checkbox" name="is_public" value="1" checked style="accent-color:#e53935;">
                        Tornar depoimento público se a pessoa aceitar
                    </label>
                    <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:5px 10px; background:#111118; border:1px solid #272727; color:#f5f5f5; font-size:12px; cursor:pointer;">Enviar depoimento</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($isOwnProfile && !empty($pendingTestimonials)): ?>
            <section style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px;">
                <h3 style="font-size:14px; margin-bottom:6px;">Depoimentos pendentes</h3>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($pendingTestimonials as $t): ?>
                        <div style="border-radius:10px; border:1px solid #272727; padding:6px 8px; font-size:12px; color:#b0b0b0;">
                            <div style="margin-bottom:3px;">
                                <strong><?= htmlspecialchars((string)($t['from_user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div style="font-size:12px; color:#f5f5f5; margin-bottom:4px;">
                                <?= nl2br(htmlspecialchars((string)($t['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                            <form action="/perfil/depoimento/decidir" method="post" style="display:flex; gap:6px;">
                                <input type="hidden" name="testimonial_id" value="<?= (int)($t['id'] ?? 0) ?>">
                                <button type="submit" name="decision" value="accepted" style="flex:1; border:none; border-radius:999px; padding:4px 8px; background:linear-gradient(135deg,#4caf50,#8bc34a); color:#050509; font-size:11px; cursor:pointer;">Aceitar</button>
                                <button type="submit" name="decision" value="rejected" style="flex:1; border:none; border-radius:999px; padding:4px 8px; background:#311; color:#ffbaba; border:1px solid #a33; font-size:11px; cursor:pointer;">Recusar</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px;">
            <h3 style="font-size:14px; margin-bottom:6px;">Comunidades</h3>
            <?php if (empty($communities)): ?>
                <div style="font-size:12px; color:#b0b0b0;">Nenhuma comunidade listada ainda.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:4px; font-size:12px;">
                    <?php foreach ($communities as $c): ?>
                        <a href="/comunidades/ver?slug=<?= urlencode((string)($c['slug'] ?? '')) ?>" style="display:flex; align-items:center; gap:6px; padding:4px 6px; border-radius:8px; border:1px solid #272727; background:#111118; text-decoration:none;">
                            <div style="width:18px; height:18px; border-radius:4px; background:#e53935;"></div>
                            <span><?= htmlspecialchars((string)($c['name'] ?? 'Comunidade'), ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </aside>
</div>

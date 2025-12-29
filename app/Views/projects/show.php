<?php /** @var array $project */ ?>
<?php /** @var array $folders */ ?>
<?php /** @var array $baseFiles */ ?>
<?php /** @var array $latestByFileId */ ?>
<?php /** @var array $conversations */ ?>
<?php /** @var bool $isFavorite */ ?>
<?php /** @var array $members */ ?>
<?php /** @var array $pendingInvites */ ?>
<?php /** @var array $projectMemoryItems */ ?>
<?php /** @var string|null $uploadError */ ?>
<?php /** @var string|null $uploadOk */ ?>
<?php
    $timeAgo = static function (?string $dt): string {
        if (!$dt) {
            return '';
        }
        try {
            $d = new \DateTimeImmutable($dt);
            $now = new \DateTimeImmutable('now');
            $diff = $now->getTimestamp() - $d->getTimestamp();
            if ($diff < 0) {
                $diff = 0;
            }

            $minute = 60;
            $hour = 60 * $minute;
            $day = 24 * $hour;
            $month = 30 * $day;

            if ($diff < $minute) {
                return 'agora mesmo';
            }
            if ($diff < $hour) {
                $m = (int)floor($diff / $minute);
                return $m === 1 ? 'há 1 minuto' : 'há ' . $m . ' minutos';
            }
            if ($diff < $day) {
                $h = (int)floor($diff / $hour);
                return $h === 1 ? 'há 1 hora' : 'há ' . $h . ' horas';
            }
            if ($diff < $month) {
                $d2 = (int)floor($diff / $day);
                return $d2 === 1 ? 'há 1 dia' : 'há ' . $d2 . ' dias';
            }
            $mo = (int)floor($diff / $month);
            return $mo === 1 ? 'há 1 mês' : 'há ' . $mo . ' meses';
        } catch (\Throwable $e) {
            return '';
        }
    };
?>
<style>
    @media (max-width: 900px) {
        #projectPageGrid {
            grid-template-columns: minmax(0, 1fr) !important;
        }
        #projectHeaderRow {
            flex-wrap: wrap !important;
        }
        #projectHeaderActions {
            width: 100% !important;
            justify-content: flex-end !important;
        }
    }
</style>
<div style="max-width: 1100px; margin: 0 auto;">
    <div id="projectHeaderRow" style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
        <a href="/projetos" style="color:var(--text-secondary); font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
            <span style="font-size:14px;">←</span>
            <span>Todos os projetos</span>
        </a>

        <a href="/chat?new=1&project_id=<?= (int)($project['id'] ?? 0) ?>" style="display:inline-flex; align-items:center; gap:8px; border:1px solid var(--border-subtle); border-radius:10px; padding:8px 12px; background:var(--surface-card); color:var(--text-primary); font-weight:600; font-size:13px; text-decoration:none; white-space:nowrap;">
            <span style="display:inline-flex; width:18px; height:18px; align-items:center; justify-content:center; border-radius:6px; border:1px solid var(--border-subtle); background:var(--surface-subtle);">+</span>
            <span>Novo chat</span>
        </a>
    </div>

<div id="projectMemoryModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); align-items:center; justify-content:center; padding:20px; z-index:60;">
    <div style="width:min(760px, 100%); background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:16px; padding:16px;">
        <div style="font-weight:700; font-size:15px; margin-bottom:6px;">Editar memória do projeto</div>
        <div style="color:var(--text-secondary); font-size:12px; line-height:1.35; margin-bottom:10px;">
            A memória ajuda o Tuquinha a entender o contexto permanente do projeto.
        </div>
        <textarea id="projectMemoryInput" rows="8" style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical; outline:none;"><?= htmlspecialchars((string)($project['description'] ?? '')) ?></textarea>
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:12px;">
            <button type="button" id="cancelProjectMemory" style="border:1px solid var(--border-subtle); border-radius:10px; padding:8px 12px; background:var(--surface-subtle); color:var(--text-primary); font-weight:600; cursor:pointer;">Cancelar</button>
            <button type="button" id="saveProjectMemory" style="border:none; border-radius:10px; padding:8px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; cursor:pointer;">Salvar</button>
        </div>
    </div>
</div>

<div id="projectRenameModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); align-items:center; justify-content:center; padding:20px; z-index:60;">
    <div style="width:min(520px, 100%); background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:16px; padding:16px;">
        <div style="font-weight:700; font-size:15px; margin-bottom:10px;">Renomear projeto</div>
        <input id="projectRenameInput" type="text" value="<?= htmlspecialchars((string)($project['name'] ?? '')) ?>" style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; outline:none;" />
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:12px;">
            <button type="button" id="cancelProjectRename" style="border:1px solid var(--border-subtle); border-radius:10px; padding:8px 12px; background:var(--surface-subtle); color:var(--text-primary); font-weight:600; cursor:pointer;">Cancelar</button>
            <button type="button" id="saveProjectRename" style="border:none; border-radius:10px; padding:8px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; cursor:pointer;">Salvar</button>
        </div>
    </div>
</div>

    <div style="margin-bottom:14px;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
            <div>
                <h1 id="projectTitle" style="font-size: 28px; margin: 0 0 6px 0;"><?= htmlspecialchars((string)($project['name'] ?? '')) ?></h1>
                <?php if (!empty($project['description'])): ?>
                    <div style="color:var(--text-secondary); font-size:13px; line-height:1.35;">
                        <?= nl2br(htmlspecialchars((string)$project['description'])) ?>
                    </div>
                <?php else: ?>
                    <div style="color:var(--text-secondary); font-size:13px; line-height:1.35;">Sem descrição.</div>
                <?php endif; ?>
            </div>

            <div id="projectHeaderActions" style="display:flex; gap:10px; align-items:center; color:var(--text-secondary);">
                <div style="position:relative;">
                    <button type="button" id="projectEllipsisBtn" style="border:none; background:transparent; color:var(--text-secondary); font-size:18px; line-height:1; cursor:pointer; padding:2px 6px;">⋯</button>
                    <div id="projectEllipsisMenu" style="display:none; position:absolute; right:0; top:28px; background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:12px; min-width:220px; padding:6px; z-index:20;">
                        <button type="button" id="projectRenameBtn" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:var(--text-primary); cursor:pointer; border-radius:10px;">Renomear</button>
                        <button type="button" id="projectDeleteBtn" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:#ffbaba; cursor:pointer; border-radius:10px;">Excluir</button>
                    </div>
                </div>
                <button type="button" id="projectFavoriteBtn" aria-pressed="<?= !empty($isFavorite) ? 'true' : 'false' ?>" style="border:none; background:transparent; color:var(--text-secondary); font-size:18px; line-height:1; cursor:pointer; padding:2px 6px;">
                    <?= !empty($isFavorite) ? '★' : '☆' ?>
                </button>
            </div>
        </div>
    </div>

    <div id="projectPageGrid" style="display:grid; grid-template-columns:minmax(0, 1fr) 360px; gap:14px; align-items:start;">
        <div style="min-width:0;">
            <div style="background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:14px; padding:14px;">
                <?php
                    $composerModel = (string)($_SESSION['chat_model'] ?? '');
                    if ($composerModel === '') {
                        try {
                            if (!empty($_SESSION['is_admin'])) {
                                $p = \App\Models\Plan::findTopActive();
                                $composerModel = is_array($p) ? (string)($p['default_model'] ?? '') : '';
                            } else {
                                $userEmail = (string)($_SESSION['user_email'] ?? '');
                                if ($userEmail !== '') {
                                    $sub = \App\Models\Subscription::findLastByEmail($userEmail);
                                    if (is_array($sub) && !empty($sub['plan_id'])) {
                                        $status = strtolower((string)($sub['status'] ?? ''));
                                        $isActive = !in_array($status, ['canceled', 'expired'], true);
                                        if ($isActive) {
                                            $p = \App\Models\Plan::findById((int)$sub['plan_id']);
                                            $composerModel = is_array($p) ? (string)($p['default_model'] ?? '') : '';
                                        }
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                        }
                    }
                    if ($composerModel === '') {
                        $composerModel = (string)\App\Models\Setting::get('openai_default_model', AI_MODEL);
                    }
                ?>
                <form id="projectComposerForm" action="/projetos/chat/criar" method="post" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <input type="hidden" name="project_id" value="<?= (int)($project['id'] ?? 0) ?>">
                    <div style="flex:1; min-width:0; overflow:hidden; background:var(--surface-subtle); border:1px solid var(--border-subtle); border-radius:14px; padding:14px; color:var(--text-secondary); font-size:13px;">
                        <?php if (!empty($planAllowsPersonalities) && !empty($personalities) && is_array($personalities)): ?>
                            <?php
                                $defaultPersonaId = !empty($_SESSION['default_persona_id']) ? (int)$_SESSION['default_persona_id'] : 0;
                            ?>
                            <input type="hidden" name="persona_id" id="projectComposerPersonaId" value="<?= $defaultPersonaId > 0 ? (int)$defaultPersonaId : '' ?>">
                            <div style="margin-bottom:10px;">
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px;">
                                    <div style="font-size:12px; color:var(--text-secondary); white-space:nowrap;">Tuquinha</div>
                                    <a href="/personalidades" style="font-size:12px; color:var(--text-secondary); text-decoration:none; border:1px solid var(--border-subtle); background:var(--surface-card); padding:6px 10px; border-radius:999px;">Ver todos</a>
                                </div>
                                <div id="projectPersonaPicker" style="display:flex; gap:10px; overflow:auto; max-width:100%; min-width:0; padding-bottom:4px; scroll-snap-type:x mandatory;">
                                    <?php foreach ($personalities as $p): ?>
                                        <?php
                                            $pid = (int)($p['id'] ?? 0);
                                            $pname = trim((string)($p['name'] ?? ''));
                                            $parea = trim((string)($p['area'] ?? ''));
                                            $pimg = trim((string)($p['image_path'] ?? ''));
                                            if ($pid <= 0 || $pname === '') { continue; }
                                            $selected = $defaultPersonaId > 0 && $pid === $defaultPersonaId;
                                        ?>
                                        <button type="button" class="projectPersonaCard" data-persona-id="<?= $pid ?>" aria-pressed="<?= $selected ? 'true' : 'false' ?>" style="
                                            flex:0 0 220px;
                                            max-width: 240px;
                                            scroll-snap-align:start;
                                            border:1px solid <?= $selected ? 'var(--border-subtle)' : 'var(--border-subtle)' ?>;
                                            background: <?= $selected ? 'var(--surface-card)' : 'var(--surface-card)' ?>;
                                            border-radius:14px;
                                            padding:10px;
                                            color:var(--text-primary);
                                            cursor:pointer;
                                            text-align:left;
                                            display:flex;
                                            gap:10px;
                                            align-items:center;
                                        ">
                                            <?php if ($pimg !== ''): ?>
                                                <img src="<?= htmlspecialchars($pimg) ?>" alt="<?= htmlspecialchars($pname) ?>" style="width:46px; height:46px; border-radius:12px; object-fit:cover; border:1px solid var(--border-subtle); background:var(--surface-subtle); flex:0 0 auto;" />
                                            <?php else: ?>
                                                <div style="width:46px; height:46px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                                                    <span style="font-size:12px; color:var(--text-secondary); font-weight:700; line-height:1;">T</span>
                                                </div>
                                            <?php endif; ?>
                                            <div style="min-width:0;">
                                                <div style="font-weight:700; font-size:13px; line-height:1.2; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($pname) ?></div>
                                                <?php if ($parea !== ''): ?>
                                                    <div style="font-size:11px; color:var(--text-secondary); line-height:1.2; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($parea) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <textarea name="message" id="projectComposerMessage" placeholder="Responder..." rows="3" style="width:100%; border:none; outline:none; background:transparent; color:var(--text-primary); font-size:13px; resize:none; min-height:46px;"></textarea>
                        <div style="display:flex; justify-content:flex-end; align-items:center; margin-top:10px;">
                            <button type="submit" style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:10px; border:1px solid #2e7d32; background:#102312; color:#c8ffd4; text-decoration:none; font-weight:700; cursor:pointer;">↑</button>
                        </div>
                        <div style="color:var(--text-secondary); font-size:12px; white-space:nowrap; margin-top:8px;"><?= htmlspecialchars($composerModel) ?> ✓</div>
                    </div>
                </form>
            </div>

            <div style="margin-top:12px; background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:14px; padding:0; overflow:hidden;">
                <?php if (empty($conversations)): ?>
                    <div style="padding:14px; color:var(--text-secondary); font-size:13px;">Nenhuma conversa neste projeto ainda.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column;">
                        <?php foreach ($conversations as $c): ?>
                            <?php
                                $title = trim((string)($c['title'] ?? ''));
                                if ($title === '') {
                                    $title = 'Chat sem título';
                                }
                                $lastAt = $c['last_message_at'] ?? ($c['created_at'] ?? null);
                                $ago = $timeAgo(is_string($lastAt) ? $lastAt : null);
                                $personaName = !empty($planAllowsPersonalities) ? trim((string)($c['persona_name'] ?? '')) : '';
                                $displayTitle = $personaName !== '' ? ($title . ' — ' . $personaName) : $title;
                            ?>
                            <a href="/chat?c=<?= (int)($c['id'] ?? 0) ?>" style="display:block; padding:12px 14px; border-top:1px solid var(--border-subtle); text-decoration:none; color:var(--text-primary);">
                                <div style="font-size:13px; font-weight:650; margin-bottom:3px;">
                                    <?= htmlspecialchars($displayTitle) ?>
                                </div>
                                <div style="font-size:11px; color:var(--text-secondary);">
                                    <?= $ago !== '' ? 'Última mensagem ' . htmlspecialchars($ago) : '' ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="min-width:0;">
            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php if (!empty($projectMemoryItems)): ?>
                <div style="background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:14px; padding:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                        <div style="font-weight:650;">Memórias automáticas</div>
                        <div style="color:var(--text-secondary); font-size:12px;">Somente admin</div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach ($projectMemoryItems as $it): ?>
                            <?php $iid = (int)($it['id'] ?? 0); ?>
                            <div style="border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; background:var(--surface-subtle);">
                                <textarea class="pmiText" data-item-id="<?= $iid ?>" style="width:100%; resize:vertical; min-height:44px; padding:10px 12px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px; outline:none;" spellcheck="false"><?= htmlspecialchars((string)($it['content'] ?? '')) ?></textarea>
                                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
                                    <button type="button" class="pmiSave" data-item-id="<?= $iid ?>" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#2e7d32; border-radius:10px; padding:8px 10px; cursor:pointer;">Salvar</button>
                                    <button type="button" class="pmiDelete" data-item-id="<?= $iid ?>" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#ff6b6b; border-radius:10px; padding:8px 10px; cursor:pointer;">Excluir</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:14px; padding:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:6px;">
                        <div style="font-weight:650;">Instruções</div>
                        <a href="#" id="openProjectInstructions" style="color:var(--text-secondary); text-decoration:none;" title="Editar">✎</a>
                    </div>
                    <div style="color:var(--text-secondary); font-size:12px; line-height:1.35;">
                        Configure instruções para orientar as respostas do Tuquinha neste projeto.
                    </div>
                </div>

                <div style="background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:14px; padding:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                        <div style="font-weight:650;">Arquivos</div>
                        <div style="position:relative;">
                            <button type="button" id="filesPlusBtn" style="border:none; border-radius:10px; padding:6px 10px; background:var(--surface-subtle); color:var(--text-primary); font-weight:650; cursor:pointer; border:1px solid var(--border-subtle);">+</button>
                            <div id="filesPlusMenu" style="display:none; position:absolute; right:0; top:40px; background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:12px; min-width:240px; padding:6px; z-index:10;">
                                <button type="button" data-action="upload" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:var(--text-primary); cursor:pointer; border-radius:10px;">Carregar do aparelho</button>
                                <button type="button" data-action="text" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:var(--text-primary); cursor:pointer; border-radius:10px;">Adicionar conteúdo de texto</button>
                                <button type="button" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:var(--text-secondary); cursor:not-allowed; border-radius:10px;" disabled>GitHub</button>
                                <button type="button" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:var(--text-secondary); cursor:not-allowed; border-radius:10px;" disabled>Google Drive</button>
                            </div>
                        </div>
                    </div>

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

            <form id="filesUploadForm" action="/projetos/arquivo-base/upload" method="post" enctype="multipart/form-data" style="display:none; flex-direction:column; gap:8px; margin-bottom:12px;">
                <input type="hidden" name="project_id" value="<?= (int)($project['id'] ?? 0) ?>">
                <input type="hidden" name="folder_path" value="/base">
                <div style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
                    <div style="flex:2; min-width:220px;">
                        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Arquivo</label>
                        <input type="file" name="file" required style="width:100%; padding:7px 9px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    </div>
                    <div>
                        <button type="submit" style="border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; cursor:pointer;">Enviar</button>
                    </div>
                </div>
                <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; font-size:12px; line-height:1.35; color:var(--text-secondary);">
                    Arquivos de texto/código (txt, md, json, php, js, etc.) serão usados como contexto automaticamente.
                </div>
                <div style="background:#1a0c10; border:1px solid #a33; border-radius:12px; padding:10px 12px; font-size:12px; line-height:1.35; color:#ffbaba;">
                    <div style="font-weight:700; margin-bottom:4px;">Atenção (PDF/Word)</div>
                    Se você tem um PDF/Word e quer usar o conteúdo como base, copie o texto do arquivo e depois clique no botão <strong>+</strong> acima e selecione <strong>Adicionar conteúdo de texto</strong>. Aí cole o conteúdo no campo <strong>Texto</strong>.
                </div>
            </form>

            <form id="filesTextForm" action="/projetos/arquivo-base/texto" method="post" style="display:none; flex-direction:column; gap:8px; margin-bottom:14px;">
                <input type="hidden" name="project_id" value="<?= (int)($project['id'] ?? 0) ?>">
                <input type="hidden" name="folder_path" value="/base">
                <div style="background:#1a0c10; border:1px solid #a33; border-radius:12px; padding:10px 12px; color:#ffbaba; font-size:12px; line-height:1.35;">
                    <div style="font-weight:700; margin-bottom:4px;">Cole o conteúdo no campo abaixo</div>
                    Cole no campo <strong>Texto</strong> (mais abaixo) o conteúdo que você quer que a IA use como base.
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <div style="flex:2; min-width:220px;">
                        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Nome do arquivo</label>
                        <input type="text" name="file_name" placeholder="ex: briefing.md" required style="width:100%; padding:7px 9px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Texto</label>
                    <textarea name="content" rows="6" required style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical; min-height:120px;"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" style="border:none; border-radius:999px; padding:9px 14px; background:var(--surface-card); color:var(--text-primary); font-weight:500; font-size:13px; cursor:pointer; border:1px solid var(--border-subtle);">Salvar texto como arquivo base</button>
                </div>
            </form>

            <div class="project-files-grid" style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px;">
                <?php foreach ($baseFiles as $bf): ?>
                    <?php $fid = (int)($bf['id'] ?? 0); ?>
                    <?php $ver = $latestByFileId[$fid] ?? null; ?>
                    <?php $storageUrl = is_array($ver) ? (string)($ver['storage_url'] ?? '') : ''; ?>
                    <div
                        style="border:1px solid var(--border-subtle); background:var(--surface-subtle); border-radius:12px; padding:10px; min-height:90px; display:flex; flex-direction:column; justify-content:space-between;<?= $storageUrl !== '' ? ' cursor:pointer;' : '' ?>"
                        <?= $storageUrl !== '' ? 'role="link" tabindex="0" onclick="window.open(\'' . htmlspecialchars($storageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '\', \"_blank\")" onkeydown="if(event.key===\"Enter\"||event.key===\" \" ){event.preventDefault(); window.open(\'' . htmlspecialchars($storageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '\', \"_blank\")}"' : '' ?>
                    >
                        <div style="font-size:12px; font-weight:650; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; overflow-wrap:anywhere; word-break:break-word; line-height:1.2;">
                            <?= htmlspecialchars((string)($bf['name'] ?? '')) ?>
                        </div>
                        <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">
                            v<?= (int)($ver['version'] ?? 0) ?>
                        </div>
                        <?php if (!empty($canAdmin)): ?>
                            <div style="display:flex; justify-content:flex-end; margin-top:8px;">
                                <form action="/projetos/arquivo-base/remover" method="post" onsubmit="return confirm('Remover este arquivo do projeto?');" style="margin:0;" onclick="event.stopPropagation();">
                                    <input type="hidden" name="project_id" value="<?= (int)($project['id'] ?? 0) ?>">
                                    <input type="hidden" name="file_id" value="<?= $fid ?>">
                                    <button type="submit" style="font-size:11px; color:#ffbaba; text-decoration:none; border:1px solid #3a1f1f; background:#1a0c10; padding:4px 8px; border-radius:999px; cursor:pointer;">Excluir</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php if ($storageUrl !== ''): ?>
                            <div style="display:flex; gap:8px; margin-top:8px;">
                                <a href="<?= htmlspecialchars($storageUrl) ?>" target="_blank" rel="noopener noreferrer" style="font-size:11px; color:#c8ffd4; text-decoration:none; border:1px solid #2e7d32; background:#102312; padding:4px 8px; border-radius:999px;" onclick="event.stopPropagation();">Abrir</a>
                                <a href="<?= htmlspecialchars($storageUrl) ?>" download style="font-size:11px; color:var(--text-primary); text-decoration:none; border:1px solid var(--border-subtle); background:var(--surface-card); padding:4px 8px; border-radius:999px;" onclick="event.stopPropagation();">Baixar</a>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                            <div style="font-size:10px; color:var(--text-secondary); border:1px solid var(--border-subtle); background:var(--surface-card); border-radius:8px; padding:3px 6px; max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= !empty($bf['mime_type']) ? htmlspecialchars((string)$bf['mime_type']) : 'ARQ' ?></div>
                            <div style="font-size:10px; color:<?= !empty($ver['extracted_text']) ? '#2e7d32' : 'var(--text-secondary)' ?>;"><?= !empty($ver['extracted_text']) ? 'ok' : '—' ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

                </div>

                <?php if (!empty($members) || !empty($pendingInvites)): ?>
                <div style="background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:14px; padding:14px; margin-top:12px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                        <div style="font-weight:650;">Compartilhar</div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="color:var(--text-secondary); font-size:12px;">Somente admin</div>
                            <button type="button" id="toggleProjectShare" aria-expanded="false" style="border:none; background:transparent; color:var(--text-secondary); cursor:pointer; font-size:14px; line-height:1; padding:2px 4px;" title="Abrir">✎</button>
                        </div>
                    </div>

                    <div id="projectShareBody" style="display:none;">
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <input id="inviteEmail" type="email" placeholder="Email do colaborador" style="flex:1 1 220px; min-width:180px; max-width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; outline:none;" />
                            <select id="inviteRole" style="flex:0 0 auto; min-width:120px; max-width:100%; padding:10px 10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; outline:none;">
                                <option value="read">Leitura</option>
                                <option value="write">Escrita</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <button type="button" id="sendInviteBtn" style="flex:0 0 auto; max-width:100%; border:none; border-radius:12px; padding:10px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; cursor:pointer;">Convidar</button>
                        </div>
                        <div id="inviteFeedback" style="margin-top:8px; color:var(--text-secondary); font-size:12px; display:none;"></div>

                        <?php if (!empty($pendingInvites)): ?>
                            <div style="margin-top:12px;">
                                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Convites pendentes</div>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <?php foreach ($pendingInvites as $inv): ?>
                                        <?php
                                            $roleLabel = 'Leitura';
                                            $rawRole = (string)($inv['role'] ?? 'read');
                                            if ($rawRole === 'write') { $roleLabel = 'Escrita'; }
                                            if ($rawRole === 'admin') { $roleLabel = 'Administrador'; }
                                        ?>
                                        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; background:var(--surface-subtle); flex-wrap:wrap;">
                                            <div style="min-width:0;">
                                                <div style="font-size:12px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars((string)($inv['invited_email'] ?? '')) ?></div>
                                                <div style="font-size:11px; color:var(--text-secondary);">Permissão: <?= htmlspecialchars($roleLabel) ?></div>
                                            </div>
                                            <button type="button" class="revokeInviteBtn" data-invite-id="<?= (int)($inv['id'] ?? 0) ?>" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#ff6b6b; border-radius:10px; padding:8px 10px; cursor:pointer;">Revogar</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($members)): ?>
                            <div style="margin-top:12px;">
                                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Membros</div>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <?php foreach ($members as $m): ?>
                                        <?php
                                            $label = trim((string)($m['user_preferred_name'] ?? ''));
                                            if ($label === '') { $label = trim((string)($m['user_name'] ?? '')); }
                                            if ($label === '') { $label = (string)($m['user_email'] ?? ''); }
                                            $role = (string)($m['role'] ?? 'read');
                                            $uid = (int)($m['user_id'] ?? 0);
                                        ?>
                                        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; background:var(--surface-subtle);">
                                            <div style="min-width:0;">
                                                <div style="font-size:12px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($label) ?></div>
                                                <div style="font-size:11px; color:var(--text-secondary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars((string)($m['user_email'] ?? '')) ?></div>
                                            </div>
                                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                                <select class="memberRoleSelect" data-user-id="<?= $uid ?>" style="padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px; outline:none;">
                                                    <option value="read" <?= $role === 'read' ? 'selected' : '' ?>>Leitura</option>
                                                    <option value="write" <?= $role === 'write' ? 'selected' : '' ?>>Escrita</option>
                                                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                                </select>
                                                <button type="button" class="removeMemberBtn" data-user-id="<?= $uid ?>" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#ff6b6b; border-radius:10px; padding:8px 10px; cursor:pointer;">Remover</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endif; ?>

            <style>
                @media (max-width: 720px) {
                    .project-files-grid {
                        grid-template-columns:repeat(1, minmax(0, 1fr)) !important;
                    }
                }
            </style>

            <script>
                (function () {
                    var btn = document.getElementById('filesPlusBtn');
                    var menu = document.getElementById('filesPlusMenu');
                    var uploadForm = document.getElementById('filesUploadForm');
                    var textForm = document.getElementById('filesTextForm');

                    function closeMenu() {
                        if (menu) menu.style.display = 'none';
                    }

                    function showUpload() {
                        if (uploadForm) uploadForm.style.display = 'flex';
                        if (textForm) textForm.style.display = 'none';
                    }

                    function showText() {
                        if (textForm) textForm.style.display = 'flex';
                        if (uploadForm) uploadForm.style.display = 'none';
                    }

                    if (btn && menu) {
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            menu.style.display = menu.style.display === 'none' || menu.style.display === '' ? 'block' : 'none';
                        });
                        document.addEventListener('click', function (e) {
                            if (!menu.contains(e.target) && e.target !== btn) {
                                closeMenu();
                            }
                        });
                        menu.addEventListener('click', function (e) {
                            var t = e.target;
                            if (!t || !t.getAttribute) return;
                            var action = t.getAttribute('data-action');
                            if (action === 'upload') {
                                showUpload();
                                closeMenu();
                            }
                            if (action === 'text') {
                                showText();
                                closeMenu();
                            }
                        });
                    }

                    function bindProjectShareToggle() {
                        var toggleBtn = document.getElementById('toggleProjectShare');
                        var body = document.getElementById('projectShareBody');
                        if (!toggleBtn || !body || toggleBtn.dataset.bound) return;
                        toggleBtn.dataset.bound = '1';

                        function setOpen(open) {
                            body.style.display = open ? 'block' : 'none';
                            toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                            toggleBtn.title = open ? 'Fechar' : 'Abrir';
                        }

                        setOpen(false);

                        toggleBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            var isOpen = toggleBtn.getAttribute('aria-expanded') === 'true';
                            setOpen(!isOpen);
                        });
                    }

                    function bindProjectPersonaPicker() {
                        var hidden = document.getElementById('projectComposerPersonaId');
                        var wrap = document.getElementById('projectPersonaPicker');
                        if (!hidden || !wrap || wrap.dataset.bound) return;
                        wrap.dataset.bound = '1';

                        function setSelected(id) {
                            hidden.value = id ? String(id) : '';
                            wrap.querySelectorAll('.projectPersonaCard').forEach(function (btn) {
                                var pid = parseInt(btn.getAttribute('data-persona-id') || '0', 10);
                                var on = id > 0 && pid === id;
                                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                                btn.style.borderColor = on ? '#2e7d32' : 'var(--border-subtle)';
                            });
                        }

                        var initial = parseInt(hidden.value || '0', 10);
                        if (initial > 0) {
                            setSelected(initial);
                        }

                        wrap.addEventListener('click', function (e) {
                            var t = e.target;
                            var btn = null;
                            while (t && t !== wrap) {
                                if (t.classList && t.classList.contains('projectPersonaCard')) {
                                    btn = t;
                                    break;
                                }
                                t = t.parentNode;
                            }
                            if (!btn) return;
                            e.preventDefault();
                            var id = parseInt(btn.getAttribute('data-persona-id') || '0', 10);
                            if (id > 0) setSelected(id);
                        });
                    }

                    function bindProjectInstructionsModal() {
                        var openInstr = document.getElementById('openProjectInstructions');
                        if (openInstr && !openInstr.dataset.bound) {
                            openInstr.dataset.bound = '1';
                            openInstr.addEventListener('click', function (e) {
                                e.preventDefault();
                                var m = document.getElementById('projectInstructionsModal');
                                if (m) m.style.display = 'flex';
                            });
                        }
                        var closeInstr = document.getElementById('closeProjectInstructions');
                        if (closeInstr && !closeInstr.dataset.bound) {
                            closeInstr.dataset.bound = '1';
                            closeInstr.addEventListener('click', function (e) {
                                e.preventDefault();
                                var m = document.getElementById('projectInstructionsModal');
                                if (m) m.style.display = 'none';
                            });
                        }
                        var cancelInstr = document.getElementById('cancelProjectInstructions');
                        if (cancelInstr && !cancelInstr.dataset.bound) {
                            cancelInstr.dataset.bound = '1';
                            cancelInstr.addEventListener('click', function (e) {
                                e.preventDefault();
                                var m = document.getElementById('projectInstructionsModal');
                                if (m) m.style.display = 'none';
                            });
                        }
                    }

                    bindProjectShareToggle();
                    bindProjectPersonaPicker();
                    bindProjectInstructionsModal();
                    document.addEventListener('DOMContentLoaded', bindProjectInstructionsModal);
                    document.addEventListener('DOMContentLoaded', bindProjectShareToggle);
                    document.addEventListener('DOMContentLoaded', bindProjectPersonaPicker);

                    var ellipsisBtn = document.getElementById('projectEllipsisBtn');
                    var ellipsisMenu = document.getElementById('projectEllipsisMenu');
                    function closeEllipsis() {
                        if (ellipsisMenu) ellipsisMenu.style.display = 'none';
                    }
                    if (ellipsisBtn && ellipsisMenu) {
                        ellipsisBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            ellipsisMenu.style.display = ellipsisMenu.style.display === 'none' || ellipsisMenu.style.display === '' ? 'block' : 'none';
                        });
                        document.addEventListener('click', function (e) {
                            if (!ellipsisMenu.contains(e.target) && e.target !== ellipsisBtn) {
                                closeEllipsis();
                            }
                        });
                    }

                    var favBtn = document.getElementById('projectFavoriteBtn');
                    if (favBtn) {
                        favBtn.addEventListener('click', async function (e) {
                            e.preventDefault();
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            try {
                                var res = await fetch('/projetos/favoritar', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json();
                                if (!json || !json.ok) return;
                                var isFav = !!json.favorite;
                                favBtn.textContent = isFav ? '★' : '☆';
                                favBtn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
                            } catch (err) {
                            }
                        });
                    }

                    var openMemory = document.getElementById('openProjectMemory');
                    if (openMemory) {
                        openMemory.addEventListener('click', function (e) {
                            e.preventDefault();
                            var m = document.getElementById('projectMemoryModal');
                            if (m) m.style.display = 'flex';
                        });
                    }
                    var cancelMemory = document.getElementById('cancelProjectMemory');
                    if (cancelMemory) {
                        cancelMemory.addEventListener('click', function (e) {
                            e.preventDefault();
                            var m = document.getElementById('projectMemoryModal');
                            if (m) m.style.display = 'none';
                        });
                    }
                    var saveMemory = document.getElementById('saveProjectMemory');
                    if (saveMemory) {
                        saveMemory.addEventListener('click', async function (e) {
                            e.preventDefault();
                            var textarea = document.getElementById('projectMemoryInput');
                            var m = document.getElementById('projectMemoryModal');
                            if (!textarea) return;
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            fd.append('memory', textarea.value || '');
                            try {
                                var res = await fetch('/projetos/memoria/salvar', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json();
                                if (!json || !json.ok) return;
                                var txt = (json.memory || '').trim();
                                var box = document.getElementById('projectMemoryText');
                                if (box) {
                                    box.innerHTML = txt !== '' ? (txt.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')) : 'Nenhuma memória definida.';
                                }
                                if (m) m.style.display = 'none';
                            } catch (err) {
                            }
                        });
                    }

                    var renameBtn = document.getElementById('projectRenameBtn');
                    if (renameBtn) {
                        renameBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            closeEllipsis();
                            var m = document.getElementById('projectRenameModal');
                            if (m) m.style.display = 'flex';
                        });
                    }
                    var cancelRename = document.getElementById('cancelProjectRename');
                    if (cancelRename) {
                        cancelRename.addEventListener('click', function (e) {
                            e.preventDefault();
                            var m = document.getElementById('projectRenameModal');
                            if (m) m.style.display = 'none';
                        });
                    }
                    var saveRename = document.getElementById('saveProjectRename');
                    if (saveRename) {
                        saveRename.addEventListener('click', async function (e) {
                            e.preventDefault();
                            var input = document.getElementById('projectRenameInput');
                            var m = document.getElementById('projectRenameModal');
                            if (!input) return;
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            fd.append('name', input.value || '');
                            try {
                                var res = await fetch('/projetos/renomear', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json();
                                if (!json || !json.ok) return;
                                var h1 = document.getElementById('projectTitle');
                                if (h1) h1.textContent = json.name || '';
                                if (m) m.style.display = 'none';
                            } catch (err) {
                            }
                        });
                    }

                    var deleteBtn = document.getElementById('projectDeleteBtn');
                    if (deleteBtn) {
                        deleteBtn.addEventListener('click', async function (e) {
                            e.preventDefault();
                            closeEllipsis();
                            if (!confirm('Tem certeza que deseja excluir este projeto?')) return;
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            try {
                                var res = await fetch('/projetos/excluir', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json();
                                if (json && json.ok) {
                                    window.location.href = '/projetos';
                                }
                            } catch (err) {
                            }
                        });
                    }

                    var sendInviteBtn = document.getElementById('sendInviteBtn');
                    if (sendInviteBtn) {
                        sendInviteBtn.addEventListener('click', async function () {
                            var emailEl = document.getElementById('inviteEmail');
                            var roleEl = document.getElementById('inviteRole');
                            var fb = document.getElementById('inviteFeedback');
                            if (!emailEl || !roleEl) return;

                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            fd.append('email', emailEl.value || '');
                            fd.append('role', roleEl.value || 'read');
                            sendInviteBtn.disabled = true;
                            try {
                                var res = await fetch('/projetos/compartilhar/convidar', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json().catch(function(){ return null; });
                                if (fb) {
                                    fb.style.display = 'block';
                                    fb.style.color = (json && json.ok) ? '#c8ffd4' : '#ffbaba';
                                    fb.textContent = (json && json.ok) ? 'Convite enviado.' : ((json && json.error) ? json.error : 'Não foi possível convidar.');
                                }
                                if (json && json.ok) {
                                    emailEl.value = '';
                                    setTimeout(function(){ window.location.reload(); }, 600);
                                }
                            } catch (e) {
                                if (fb) {
                                    fb.style.display = 'block';
                                    fb.style.color = '#ffbaba';
                                    fb.textContent = 'Não foi possível convidar.';
                                }
                            } finally {
                                sendInviteBtn.disabled = false;
                            }
                        });
                    }

                    document.querySelectorAll('.revokeInviteBtn').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            var inviteId = btn.getAttribute('data-invite-id');
                            if (!inviteId) return;
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            fd.append('invite_id', inviteId);
                            btn.disabled = true;
                            try {
                                var res = await fetch('/projetos/compartilhar/revogar', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json().catch(function(){ return null; });
                                if (json && json.ok) {
                                    window.location.reload();
                                }
                            } catch (e) {
                            } finally {
                                btn.disabled = false;
                            }
                        });
                    });

                    document.querySelectorAll('.memberRoleSelect').forEach(function (sel) {
                        sel.addEventListener('change', async function () {
                            var uid = sel.getAttribute('data-user-id');
                            var role = sel.value;
                            if (!uid) return;
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            fd.append('user_id', uid);
                            fd.append('role', role);
                            sel.disabled = true;
                            try {
                                var res = await fetch('/projetos/compartilhar/alterar-role', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json().catch(function(){ return null; });
                                if (!json || !json.ok) {
                                    window.location.reload();
                                }
                            } catch (e) {
                                window.location.reload();
                            }
                        });
                    });

                    document.querySelectorAll('.removeMemberBtn').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            if (!confirm('Remover este membro do projeto?')) return;
                            var uid = btn.getAttribute('data-user-id');
                            if (!uid) return;
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            fd.append('user_id', uid);
                            btn.disabled = true;
                            try {
                                var res = await fetch('/projetos/compartilhar/remover', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json().catch(function(){ return null; });
                                if (json && json.ok) {
                                    window.location.reload();
                                }
                            } catch (e) {
                            } finally {
                                btn.disabled = false;
                            }
                        });
                    });

                    document.querySelectorAll('.pmiSave').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            var id = btn.getAttribute('data-item-id');
                            if (!id) return;
                            var ta = document.querySelector('.pmiText[data-item-id="' + id + '"]');
                            if (!ta) return;
                            var oldText = btn.textContent;
                            var oldBorder = btn.style.borderColor;
                            var oldColor = btn.style.color;
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            fd.append('item_id', id);
                            fd.append('content', ta.value || '');
                            btn.disabled = true;
                            try {
                                var res = await fetch('/projetos/memoria-itens/atualizar', {
                                    method: 'POST',
                                    body: fd,
                                    credentials: 'same-origin',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });

                                var json = await res.json().catch(function(){ return null; });

                                if (res.status === 401) {
                                    window.location.href = '/login';
                                    return;
                                }

                                if (!res.ok || !json || !json.ok) {
                                    var msg = (json && json.error) ? json.error : 'Não foi possível salvar.';
                                    showToast(msg, 'error');
                                    return;
                                }

                                btn.textContent = 'Salvo';
                                btn.style.color = '#c8ffd4';
                                btn.style.borderColor = '#2e7d32';
                                showToast('Salvo com sucesso', 'success');
                                try {
                                    if (btn.dataset && btn.dataset.savedTimeout) {
                                        clearTimeout(parseInt(btn.dataset.savedTimeout, 10));
                                    }
                                } catch (e2) {}
                                var t = window.setTimeout(function () {
                                    btn.textContent = oldText;
                                    btn.style.borderColor = oldBorder;
                                    btn.style.color = oldColor;
                                    try { if (btn.dataset) { delete btn.dataset.savedTimeout; } } catch (e3) {}
                                }, 1500);
                                try { if (btn.dataset) { btn.dataset.savedTimeout = String(t); } } catch (e4) {}
                            } catch (e) {
                                showToast('Não foi possível salvar.', 'error');
                            } finally {
                                btn.disabled = false;
                            }
                        });
                    });

                    document.querySelectorAll('.pmiDelete').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            if (!confirm('Excluir esta memória automática?')) return;
                            var id = btn.getAttribute('data-item-id');
                            if (!id) return;
                            var fd = new FormData();
                            fd.append('project_id', '<?= (int)($project['id'] ?? 0) ?>');
                            fd.append('item_id', id);
                            btn.disabled = true;
                            try {
                                var res = await fetch('/projetos/memoria-itens/excluir', { method: 'POST', body: fd, credentials: 'same-origin' });
                                var json = await res.json().catch(function(){ return null; });
                                if (json && json.ok) {
                                    window.location.reload();
                                }
                            } catch (e) {
                            } finally {
                                btn.disabled = false;
                            }
                        });
                    });
                })();
            </script>

                </div>
            </div>
        </div>
    </div>

    <div id="projectInstructionsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); align-items:center; justify-content:center; padding:20px; z-index:50;">
        <div style="width:min(760px, 100%); background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:16px; padding:16px;">
            <div style="font-weight:700; font-size:15px; margin-bottom:6px;">Criar instruções para o projeto</div>
            <div style="color:var(--text-secondary); font-size:12px; line-height:1.35; margin-bottom:10px;">
                Dê ao Tuquinha instruções e informações relevantes para as conversas dentro deste projeto.
            </div>
            <textarea rows="8" style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical; outline:none;"></textarea>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:12px;">
                <button type="button" id="cancelProjectInstructions" style="border:1px solid var(--border-subtle); border-radius:10px; padding:8px 12px; background:var(--surface-subtle); color:var(--text-primary); font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="button" id="closeProjectInstructions" style="border:none; border-radius:10px; padding:8px 12px; background:var(--surface-subtle); color:var(--text-secondary); font-weight:650; cursor:not-allowed; border:1px solid var(--border-subtle);" disabled>Salvar instruções</button>
            </div>
        </div>
    </div>
</div>

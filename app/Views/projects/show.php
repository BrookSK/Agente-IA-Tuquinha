<?php /** @var array $project */ ?>
<?php /** @var array $folders */ ?>
<?php /** @var array $baseFiles */ ?>
<?php /** @var array $latestByFileId */ ?>
<?php /** @var array $conversations */ ?>
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
<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
        <a href="/projetos" style="color:#b0b0b0; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
            <span style="font-size:14px;">←</span>
            <span>Todos os projetos</span>
        </a>

        <a href="/chat?new=1&project_id=<?= (int)($project['id'] ?? 0) ?>" style="display:inline-flex; align-items:center; gap:8px; border:1px solid #272727; border-radius:10px; padding:8px 12px; background:#111118; color:#f5f5f5; font-weight:600; font-size:13px; text-decoration:none; white-space:nowrap;">
            <span style="display:inline-flex; width:18px; height:18px; align-items:center; justify-content:center; border-radius:6px; border:1px solid #272727; background:#050509;">+</span>
            <span>Novo chat</span>
        </a>
    </div>

    <div style="margin-bottom:14px;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
            <div>
                <h1 style="font-size: 28px; margin: 0 0 6px 0;"><?= htmlspecialchars((string)($project['name'] ?? '')) ?></h1>
                <?php if (!empty($project['description'])): ?>
                    <div style="color:#b0b0b0; font-size:13px; line-height:1.35;">
                        <?= nl2br(htmlspecialchars((string)$project['description'])) ?>
                    </div>
                <?php else: ?>
                    <div style="color:#8d8d8d; font-size:13px; line-height:1.35;">Sem descrição.</div>
                <?php endif; ?>
            </div>

            <div style="display:flex; gap:10px; align-items:center; color:#8d8d8d;">
                <a href="#" style="color:#8d8d8d; text-decoration:none; font-size:18px; line-height:1;" title="Em breve" onclick="return false;">⋯</a>
                <a href="#" style="color:#8d8d8d; text-decoration:none; font-size:18px; line-height:1;" title="Em breve" onclick="return false;">☆</a>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:minmax(0, 1fr) 360px; gap:14px; align-items:start;">
        <div style="min-width:0;">
            <div style="background:#111118; border:1px solid #272727; border-radius:14px; padding:14px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <div style="flex:1; background:#0a0a10; border:1px solid #272727; border-radius:14px; padding:14px; color:#8d8d8d; font-size:13px;">
                        Responder...
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                            <div style="display:flex; gap:10px; align-items:center;">
                                <div style="width:18px; height:18px; border-radius:6px; border:1px solid #272727; display:flex; align-items:center; justify-content:center; color:#8d8d8d;">+</div>
                                <div style="width:18px; height:18px; border-radius:6px; border:1px solid #272727; display:flex; align-items:center; justify-content:center; color:#8d8d8d;">⏱</div>
                            </div>
                            <a href="/chat?new=1&project_id=<?= (int)($project['id'] ?? 0) ?>" style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:10px; border:1px solid #2e7d32; background:#102312; color:#c8ffd4; text-decoration:none; font-weight:700;">↑</a>
                        </div>
                    </div>
                    <div style="color:#8d8d8d; font-size:12px; white-space:nowrap; margin-left:10px;">Sonnet 4.5 ✓</div>
                </div>
            </div>

            <div style="margin-top:12px; background:#111118; border:1px solid #272727; border-radius:14px; padding:0; overflow:hidden;">
                <?php if (empty($conversations)): ?>
                    <div style="padding:14px; color:#b0b0b0; font-size:13px;">Nenhuma conversa neste projeto ainda.</div>
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
                            ?>
                            <a href="/chat?c=<?= (int)($c['id'] ?? 0) ?>" style="display:block; padding:12px 14px; border-top:1px solid #1f1f1f; text-decoration:none; color:#f5f5f5;">
                                <div style="font-size:13px; font-weight:650; margin-bottom:3px;">
                                    <?= htmlspecialchars($title) ?>
                                </div>
                                <div style="font-size:11px; color:#8d8d8d;">
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
                <div style="background:#111118; border:1px solid #272727; border-radius:14px; padding:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:6px;">
                        <div style="font-weight:650;">Memória</div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="font-size:11px; color:#8d8d8d; border:1px solid #272727; border-radius:999px; padding:5px 8px; background:#0a0a10;">Apenas você</div>
                            <a href="#" style="color:#8d8d8d; text-decoration:none;" title="Em breve" onclick="return false;">✎</a>
                        </div>
                    </div>
                    <div style="color:#b0b0b0; font-size:12px; line-height:1.35;">
                        <?= !empty($project['description']) ? nl2br(htmlspecialchars((string)$project['description'])) : 'Nenhuma memória definida.' ?>
                    </div>
                </div>

                <div style="background:#111118; border:1px solid #272727; border-radius:14px; padding:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:6px;">
                        <div style="font-weight:650;">Instruções</div>
                        <a href="#" id="openProjectInstructions" style="color:#8d8d8d; text-decoration:none;" title="Editar">✎</a>
                    </div>
                    <div style="color:#b0b0b0; font-size:12px; line-height:1.35;">
                        Configure instruções para orientar as respostas do Tuquinha neste projeto.
                    </div>
                </div>

                <div style="background:#111118; border:1px solid #272727; border-radius:14px; padding:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                        <div style="font-weight:650;">Arquivos</div>
                        <div style="position:relative;">
                            <button type="button" id="filesPlusBtn" style="border:none; border-radius:10px; padding:6px 10px; background:#0a0a10; color:#f5f5f5; font-weight:650; cursor:pointer; border:1px solid #272727;">+</button>
                            <div id="filesPlusMenu" style="display:none; position:absolute; right:0; top:40px; background:#111118; border:1px solid #272727; border-radius:12px; min-width:240px; padding:6px; z-index:10;">
                                <button type="button" data-action="upload" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:#f5f5f5; cursor:pointer; border-radius:10px;">Carregar do aparelho</button>
                                <button type="button" data-action="text" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:#f5f5f5; cursor:pointer; border-radius:10px;">Adicionar conteúdo de texto</button>
                                <button type="button" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:#8d8d8d; cursor:not-allowed; border-radius:10px;" disabled>GitHub</button>
                                <button type="button" style="width:100%; text-align:left; padding:10px 10px; border:none; background:transparent; color:#8d8d8d; cursor:not-allowed; border-radius:10px;" disabled>Google Drive</button>
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
                <div style="color:#8d8d8d; font-size:12px; line-height:1.35;">
                    Se você tem um PDF/Word e quer usar o conteúdo como base, copie o texto do arquivo e cole no campo de texto abaixo.
                </div>
            </form>

            <form id="filesTextForm" action="/projetos/arquivo-base/texto" method="post" style="display:none; flex-direction:column; gap:8px; margin-bottom:14px;">
                <input type="hidden" name="project_id" value="<?= (int)($project['id'] ?? 0) ?>">
                <div style="background:#0a0a10; border:1px solid #272727; border-radius:10px; padding:10px 12px; color:#b0b0b0; font-size:12px; line-height:1.35;">
                    Cole aqui o conteúdo que você quer que a IA use como base (por exemplo: texto copiado de um PDF/Word).
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:180px;">
                        <label style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:4px;">Pasta</label>
                        <select name="folder_path" style="width:100%; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                            <?php foreach (($folders ?? []) as $f): ?>
                                <option value="<?= htmlspecialchars((string)($f['path'] ?? '')) ?>"><?= htmlspecialchars((string)($f['path'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:2; min-width:220px;">
                        <label style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:4px;">Nome do arquivo</label>
                        <input type="text" name="file_name" placeholder="ex: briefing.md" required style="width:100%; padding:7px 9px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:4px;">Texto</label>
                    <textarea name="content" rows="6" required style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical; min-height:120px;"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" style="border:none; border-radius:999px; padding:9px 14px; background:#111118; color:#f5f5f5; font-weight:500; font-size:13px; cursor:pointer; border:1px solid #272727;">Salvar texto como arquivo base</button>
                </div>
            </form>

            <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px;">
                <?php foreach ($baseFiles as $bf): ?>
                    <?php $fid = (int)($bf['id'] ?? 0); ?>
                    <?php $ver = $latestByFileId[$fid] ?? null; ?>
                    <div style="border:1px solid #272727; background:#050509; border-radius:12px; padding:10px; min-height:90px; display:flex; flex-direction:column; justify-content:space-between;">
                        <div style="font-size:12px; font-weight:650; color:#f5f5f5; overflow:hidden; text-overflow:ellipsis;">
                            <?= htmlspecialchars((string)($bf['name'] ?? '')) ?>
                        </div>
                        <div style="font-size:11px; color:#8d8d8d; margin-top:6px;">
                            v<?= (int)($ver['version'] ?? 0) ?>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                            <div style="font-size:10px; color:#8d8d8d; border:1px solid #272727; background:#0a0a10; border-radius:8px; padding:3px 6px;"><?= !empty($bf['mime_type']) ? htmlspecialchars((string)$bf['mime_type']) : 'ARQ' ?></div>
                            <div style="font-size:10px; color:<?= !empty($ver['extracted_text']) ? '#c8ffd4' : '#8d8d8d' ?>;"><?= !empty($ver['extracted_text']) ? 'ok' : '—' ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

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

                    var openInstr = document.getElementById('openProjectInstructions');
                    if (openInstr) {
                        openInstr.addEventListener('click', function (e) {
                            e.preventDefault();
                            var m = document.getElementById('projectInstructionsModal');
                            if (m) m.style.display = 'flex';
                        });
                    }
                    var closeInstr = document.getElementById('closeProjectInstructions');
                    if (closeInstr) {
                        closeInstr.addEventListener('click', function (e) {
                            e.preventDefault();
                            var m = document.getElementById('projectInstructionsModal');
                            if (m) m.style.display = 'none';
                        });
                    }
                    var cancelInstr = document.getElementById('cancelProjectInstructions');
                    if (cancelInstr) {
                        cancelInstr.addEventListener('click', function (e) {
                            e.preventDefault();
                            var m = document.getElementById('projectInstructionsModal');
                            if (m) m.style.display = 'none';
                        });
                    }
                })();
            </script>

                </div>
            </div>
        </div>
    </div>

    <div id="projectInstructionsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); align-items:center; justify-content:center; padding:20px; z-index:50;">
        <div style="width:min(760px, 100%); background:#111118; border:1px solid #272727; border-radius:16px; padding:16px;">
            <div style="font-weight:700; font-size:15px; margin-bottom:6px;">Criar instruções para o projeto</div>
            <div style="color:#b0b0b0; font-size:12px; line-height:1.35; margin-bottom:10px;">
                Dê ao Tuquinha instruções e informações relevantes para as conversas dentro deste projeto.
            </div>
            <textarea rows="8" style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical; outline:none;"></textarea>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:12px;">
                <button type="button" id="cancelProjectInstructions" style="border:1px solid #272727; border-radius:10px; padding:8px 12px; background:#0a0a10; color:#f5f5f5; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="button" id="closeProjectInstructions" style="border:none; border-radius:10px; padding:8px 12px; background:#2a2a2a; color:#b0b0b0; font-weight:650; cursor:not-allowed;" disabled>Salvar instruções</button>
            </div>
        </div>
    </div>
</div>

<?php /** @var array $projects */ ?>
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

<div style="max-width: 980px; margin: 0 auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px;">
        <h1 style="font-size: 26px; margin: 0;">Projetos</h1>
        <a href="/projetos/novo" style="display:inline-flex; align-items:center; gap:8px; border:1px solid #272727; border-radius:10px; padding:8px 12px; background:#111118; color:#f5f5f5; font-weight:600; font-size:13px; text-decoration:none;">
            <span style="display:inline-flex; width:18px; height:18px; align-items:center; justify-content:center; border-radius:6px; border:1px solid #272727; background:#050509;">+</span>
            <span>Novo projeto</span>
        </a>
    </div>

    <?php $onlyFavorites = !empty($onlyFavorites); ?>
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:12px;">
        <a href="/projetos" style="display:inline-flex; align-items:center; gap:8px; border:1px solid #272727; border-radius:999px; padding:7px 12px; background:<?= $onlyFavorites ? '#111118' : '#1a1a22' ?>; color:#f5f5f5; font-weight:600; font-size:13px; text-decoration:none;">
            <span>📋</span>
            <span>Todos</span>
        </a>
        <a href="/projetos?favoritos=1" style="display:inline-flex; align-items:center; gap:8px; border:1px solid #272727; border-radius:999px; padding:7px 12px; background:<?= $onlyFavorites ? '#1a1a22' : '#111118' ?>; color:#f5f5f5; font-weight:600; font-size:13px; text-decoration:none;">
            <span>⭐</span>
            <span>Favoritos</span>
        </a>
        <?php if ($onlyFavorites): ?>
            <div style="color:#8d8d8d; font-size:12px;">Mostrando apenas projetos marcados com estrela.</div>
        <?php endif; ?>
    </div>

    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:14px;">
        <div style="flex:1; min-width:260px; border:1px solid #2b2b2b; background:#111118; border-radius:12px; padding:10px 12px; display:flex; align-items:center; gap:10px;">
            <span style="color:#8d8d8d; font-size:14px;">🔍</span>
            <input id="projectsSearch" type="text" placeholder="Procurar projetos..." style="width:100%; border:none; outline:none; background:transparent; color:#f5f5f5; font-size:13px;" />
        </div>
        <div style="display:flex; align-items:center; gap:8px; margin-left:auto;">
            <div style="color:#8d8d8d; font-size:12px;">Ordenar por</div>
            <select id="projectsSort" style="padding:8px 10px; border-radius:10px; border:1px solid #272727; background:#111118; color:#f5f5f5; font-size:13px;">
                <option value="activity">Atividade</option>
                <option value="name">Nome</option>
            </select>
        </div>
    </div>

    <?php if (empty($projects)): ?>
        <div style="background:#111118; border:1px solid #272727; border-radius:14px; padding:14px; color:#b0b0b0; font-size:14px;">
            <?= $onlyFavorites ? 'Você ainda não marcou nenhum projeto como favorito.' : 'Você ainda não tem projetos.' ?>
        </div>
    <?php else: ?>
        <div id="projectsGrid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:14px;">
            <?php foreach ($projects as $p): ?>
                <?php
                    $name = (string)($p['name'] ?? '');
                    $desc = (string)($p['description'] ?? '');
                    $updated = (string)($p['updated_at'] ?? ($p['created_at'] ?? ''));
                    $ago = $timeAgo($updated);
                    $isFav = !empty($p['is_favorite']);
                ?>
                <a
                    href="/projetos/ver?id=<?= (int)($p['id'] ?? 0) ?>"
                    class="project-card"
                    data-name="<?= htmlspecialchars(mb_strtolower($name, 'UTF-8')) ?>"
                    data-desc="<?= htmlspecialchars(mb_strtolower($desc, 'UTF-8')) ?>"
                    data-updated="<?= htmlspecialchars($updated) ?>"
                    style="display:block; background:#111118; border:1px solid #272727; border-radius:14px; padding:16px; text-decoration:none; color:#f5f5f5;"
                >
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px;">
                        <div style="font-weight:650; font-size:14px;">
                            <?= htmlspecialchars($name) ?>
                        </div>
                        <?php if ($isFav): ?>
                            <div title="Favorito" style="font-size:14px; line-height:1; color:#ffcc80;">⭐</div>
                        <?php endif; ?>
                    </div>
                    <div style="color:#b0b0b0; font-size:12px; line-height:1.35; min-height:34px;">
                        <?= $desc !== '' ? nl2br(htmlspecialchars($desc)) : 'Sem descrição.' ?>
                    </div>
                    <div style="color:#8d8d8d; font-size:11px; margin-top:12px;">
                        <?= $ago !== '' ? 'Atualizado ' . htmlspecialchars($ago) : '' ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <script>
            (function () {
                var search = document.getElementById('projectsSearch');
                var sort = document.getElementById('projectsSort');
                var grid = document.getElementById('projectsGrid');
                if (!search || !sort || !grid) return;

                function applyFilter() {
                    var q = (search.value || '').toLowerCase().trim();
                    var cards = Array.prototype.slice.call(grid.querySelectorAll('.project-card'));
                    cards.forEach(function (c) {
                        var hay = (c.getAttribute('data-name') || '') + ' ' + (c.getAttribute('data-desc') || '');
                        c.style.display = q === '' || hay.indexOf(q) !== -1 ? 'block' : 'none';
                    });
                }

                function applySort() {
                    var mode = sort.value || 'activity';
                    var cards = Array.prototype.slice.call(grid.querySelectorAll('.project-card'));
                    cards.sort(function (a, b) {
                        if (mode === 'name') {
                            return (a.getAttribute('data-name') || '').localeCompare((b.getAttribute('data-name') || ''));
                        }
                        return (b.getAttribute('data-updated') || '').localeCompare((a.getAttribute('data-updated') || ''));
                    });
                    cards.forEach(function (c) {
                        grid.appendChild(c);
                    });
                }

                search.addEventListener('input', applyFilter);
                sort.addEventListener('change', function () {
                    applySort();
                    applyFilter();
                });

                applySort();
                applyFilter();
            })();
        </script>
    <?php endif; ?>
</div>

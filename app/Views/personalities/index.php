<?php
/** @var array $personalities */
?>
<style>
    .persona-card {
        flex: 0 0 280px;
        max-width: 300px;
        background: var(--surface-card);
        border-radius: 20px;
        border: 1px solid var(--border-subtle);
        overflow: hidden;
        color: var(--text-primary);
        text-decoration: none;
        scroll-snap-align: center;
        box-shadow: 0 18px 35px rgba(0,0,0,0.25);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }
    .persona-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 22px 40px rgba(15,23,42,0.3);
        border-color: var(--accent-soft);
    }
    .persona-card-image {
        width: 100%;
        height: 220px;
        overflow: hidden;
        background: var(--surface-subtle);
    }
    .persona-card-desc {
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.4;
        max-height: 5.4em;
        overflow: hidden;
    }
    .persona-card-muted {
        font-size: 12px;
        color: var(--text-secondary);
    }
</style>
<div style="max-width: 1000px; margin: 0 auto;">
    <h1 style="font-size: 26px; margin-bottom: 10px; font-weight: 650;">Escolha a personalidade do Tuquinha</h1>
    <p style="color:var(--text-secondary); font-size: 14px; margin-bottom: 8px; max-width: 640px;">
        Cada personalidade é um "modo" diferente do Tuquinha, com foco, jeito de falar e especialidade próprios.
        Escolha quem vai te ajudar neste próximo chat.
    </p>

    <?php if (empty($personalities)): ?>
        <div style="background:#111118; border-radius:12px; padding:12px 14px; border:1px solid #272727; font-size:14px; color:#b0b0b0; margin-top:12px;">
            Ainda não há personalidades ativas cadastradas pelo administrador.
            <br><br>
            <a href="/chat?new=1" style="display:inline-flex; align-items:center; gap:6px; margin-top:4px; border-radius:999px; padding:7px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:13px; font-weight:600; text-decoration:none;">
                <span>Ir direto para o chat padrão</span>
                <span>➤</span>
            </a>
        </div>
    <?php else: ?>
        <?php
        $hasMb = function_exists('mb_substr') && function_exists('mb_strlen');
        ?>
        <div style="position:relative; margin-top:16px;">
            <button type="button" id="persona-prev" style="
                position:absolute;
                left:0;
                top:50%;
                transform:translateY(-50%);
                width:32px;
                height:32px;
                border-radius:999px;
                border:1px solid #272727;
                background:rgba(5,5,9,0.9);
                color:#f5f5f5;
                display:flex;
                align-items:center;
                justify-content:center;
                cursor:pointer;
                z-index:2;
            ">‹</button>
            <button type="button" id="persona-next" style="
                position:absolute;
                right:0;
                top:50%;
                transform:translateY(-50%);
                width:32px;
                height:32px;
                border-radius:999px;
                border:1px solid #272727;
                background:rgba(5,5,9,0.9);
                color:#f5f5f5;
                display:flex;
                align-items:center;
                justify-content:center;
                cursor:pointer;
                z-index:2;
            ">›</button>

            <div id="persona-carousel" style="
                display:flex;
                gap:18px;
                overflow-x:auto;
                padding:8px 40px 10px 40px;
                scroll-snap-type:x mandatory;
                scrollbar-width:thin;
            ">
                <?php foreach ($personalities as $persona): ?>
                    <?php
                        $id = (int)($persona['id'] ?? 0);
                        $name = trim((string)($persona['name'] ?? ''));
                        $area = trim((string)($persona['area'] ?? ''));
                        $imagePath = trim((string)($persona['image_path'] ?? ''));
                        $isDefault = !empty($persona['is_default']);
                        $prompt = trim((string)($persona['prompt'] ?? ''));
                        $desc = '';
                        if ($prompt !== '') {
                            // Remove bloco de "Regras principais" do resumo exibido no card
                            $basePrompt = $prompt;
                            $marker = 'Regras principais:';
                            if (function_exists('mb_stripos')) {
                                $posMarker = mb_stripos($basePrompt, $marker, 0, 'UTF-8');
                                if ($posMarker !== false) {
                                    $basePrompt = mb_substr($basePrompt, 0, $posMarker, 'UTF-8');
                                }
                            } else {
                                $posMarker = stripos($basePrompt, $marker);
                                if ($posMarker !== false) {
                                    $basePrompt = substr($basePrompt, 0, $posMarker);
                                }
                            }
                            if ($hasMb) {
                                $maxLen = 220;
                                $desc = mb_substr($basePrompt, 0, $maxLen, 'UTF-8');
                                if (mb_strlen($basePrompt, 'UTF-8') > $maxLen) {
                                    $desc .= '...';
                                }
                            } else {
                                $desc = substr($basePrompt, 0, 220);
                                if (strlen($basePrompt) > 220) {
                                    $desc .= '...';
                                }
                            }
                        }
                        if ($imagePath === '') {
                            $imagePath = '/public/favicon.png';
                        }
                    ?>
                    <a href="/chat?new=1&amp;persona_id=<?= $id ?>" class="persona-card" style="
                        flex:0 0 280px;
                        max-width: 300px;
                        scroll-snap-align:center;
                    ">
                        <div class="persona-card-image">
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($name) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                        </div>
                        <div style="padding:10px 12px 12px 12px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;">
                                <div style="font-size:18px; font-weight:650; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($name) ?>
                                </div>
                                <?php if ($isDefault): ?>
                                    <span style="font-size:9px; text-transform:uppercase; letter-spacing:0.14em; border-radius:999px; padding:2px 7px; background:#201216; color:#ffcc80; border:1px solid #ff6f60;">Principal</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($area !== ''): ?>
                                <div style="font-size:12px; color:#ffcc80; margin-bottom:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($area) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($desc !== ''): ?>
                                <div class="persona-card-desc">
                                    <?= nl2br(htmlspecialchars($desc)) ?>
                                </div>
                            <?php else: ?>
                                <div class="persona-card-muted">
                                    Clique para começar um chat com essa personalidade.
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var track = document.getElementById('persona-carousel');
    if (!track) return;

    var btnPrev = document.getElementById('persona-prev');
    var btnNext = document.getElementById('persona-next');

    function scrollByCard(direction) {
        var card = track.querySelector('.persona-card');
        var delta = card ? (card.offsetWidth + 18) : 260;
        track.scrollBy({ left: delta * direction, behavior: 'smooth' });
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function () { scrollByCard(-1); });
    }
    if (btnNext) {
        btnNext.addEventListener('click', function () { scrollByCard(1); });
    }
});
</script>

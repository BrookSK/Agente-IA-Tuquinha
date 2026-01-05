<?php
/** @var array $personalities */
/** @var int|null $conversationId */
$conversationId = isset($conversationId) ? (int)$conversationId : 0;
?>
<style>
    .persona-card {
        width: 300px;
        background: var(--surface-card);
        border-radius: 20px;
        border: 1px solid var(--border-subtle);
        overflow: hidden;
        color: var(--text-primary);
        text-decoration: none;
        box-shadow: 0 18px 35px rgba(0,0,0,0.25);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, opacity 0.18s ease, filter 0.18s ease;
        opacity: 0.55;
        transform: scale(0.96);
    }
    .persona-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 22px 40px rgba(15,23,42,0.3);
        border-color: var(--accent-soft);
        opacity: 0.95;
    }
    .persona-card.is-selected {
        opacity: 1;
        transform: scale(1);
        border-color: #2e7d32;
        box-shadow: 0 22px 46px rgba(0,0,0,0.35);
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

    .persona-nav-btn {
        position:absolute;
        top:50%;
        transform:translateY(-50%);
        width:56px;
        height:56px;
        border-radius:999px;
        border:1px solid #272727;
        background:rgba(5,5,9,0.9);
        color:#f5f5f5;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        z-index:2;
        font-size:26px;
        line-height:1;
    }

    .persona-stage {
        position: relative;
        margin-top: 16px;
        padding: 8px 40px 10px 40px;
        min-height: 420px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        touch-action: pan-y;
    }
    #persona-carousel {
        position: relative;
        width: 100%;
        height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    #persona-carousel .persona-card {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%) scale(0.96);
        pointer-events: auto;
    }
    #persona-carousel .persona-card.is-center {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.08);
        filter: none;
        z-index: 3;
    }
    #persona-carousel .persona-card.is-left {
        opacity: 0.38;
        transform: translate(calc(-50% - 260px), -50%) scale(0.9);
        filter: grayscale(1);
        z-index: 2;
    }
    #persona-carousel .persona-card.is-right {
        opacity: 0.38;
        transform: translate(calc(-50% + 260px), -50%) scale(0.9);
        filter: grayscale(1);
        z-index: 2;
    }
    #persona-carousel .persona-card.is-hidden {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.85);
        pointer-events: none;
        z-index: 1;
    }

    @media (max-width: 640px) {
        .persona-stage {
            padding: 8px 10px 10px 10px;
            min-height: 410px;
        }
        .persona-nav-btn {
            width: 60px;
            height: 60px;
            background: rgba(5,5,9,0.82);
            font-size:28px;
        }
        #persona-carousel .persona-card.is-left {
            opacity: 0.22;
            transform: translate(calc(-50% - 170px), -50%) scale(0.86);
        }
        #persona-carousel .persona-card.is-right {
            opacity: 0.22;
            transform: translate(calc(-50% + 170px), -50%) scale(0.86);
        }
        #persona-carousel .persona-card.is-center {
            transform: translate(-50%, -50%) scale(1.03);
        }
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
        <div class="persona-stage">
            <button type="button" id="persona-prev" class="persona-nav-btn" style="left:0;" aria-label="Anterior">‹</button>
            <button type="button" id="persona-next" class="persona-nav-btn" style="right:0;" aria-label="Próximo">›</button>

            <div id="persona-carousel" style="
                display:flex;
            ">
                <?php foreach ($personalities as $persona): ?>
                    <?php
                        $id = (int)($persona['id'] ?? 0);
                        $name = trim((string)($persona['name'] ?? ''));
                        $area = trim((string)($persona['area'] ?? ''));
                        $imagePath = trim((string)($persona['image_path'] ?? ''));
                        $isDefault = !empty($persona['is_default']);
                        $isComingSoon = !empty($persona['coming_soon']);
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
                    <?php if ($conversationId > 0): ?>
                        <form action="/chat/persona" method="post" style="margin:0;">
                            <input type="hidden" name="conversation_id" value="<?= (int)$conversationId ?>">
                            <input type="hidden" name="persona_id" value="<?= $id ?>">
                            <button type="submit" class="persona-card" <?= $isComingSoon ? 'disabled' : '' ?> style="
                                width:100%;
                                padding:0;
                                text-align:left;
                                cursor:<?= $isComingSoon ? 'not-allowed' : 'pointer' ?>;
                            ">
                    <?php else: ?>
                        <a href="<?= $isComingSoon ? 'javascript:void(0)' : ('/chat?new=1&amp;persona_id=' . $id) ?>" class="persona-card" style="
                            cursor:<?= $isComingSoon ? 'not-allowed' : 'pointer' ?>;
                            pointer-events:<?= $isComingSoon ? 'none' : 'auto' ?>;
                        ">
                    <?php endif; ?>
                        <div class="persona-card-image">
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($name) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                        </div>
                        <div style="padding:10px 12px 12px 12px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;">
                                <div style="font-size:18px; font-weight:650; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($name) ?>
                                </div>
                                <div style="display:flex; gap:6px; align-items:center; flex-shrink:0;">
                                <?php if ($isComingSoon): ?>
                                    <span style="font-size:9px; text-transform:uppercase; letter-spacing:0.14em; border-radius:999px; padding:2px 7px; background:#201216; color:#ffcc80; border:1px solid #ff6f60;">Em breve</span>
                                <?php endif; ?>
                                <?php if ($isDefault): ?>
                                    <span style="font-size:9px; text-transform:uppercase; letter-spacing:0.14em; border-radius:999px; padding:2px 7px; background:#201216; color:#ffcc80; border:1px solid #ff6f60;">Principal</span>
                                <?php endif; ?>
                                </div>
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
                                    <?= $isComingSoon ? 'Preview disponível. Em breve você poderá usar essa personalidade.' : 'Clique para começar um chat com essa personalidade.' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php if ($conversationId > 0): ?>
                            </button>
                        </form>
                    <?php else: ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var track = document.getElementById('persona-carousel');
    if (!track) return;

    var stage = track.parentElement;

    var btnPrev = document.getElementById('persona-prev');
    var btnNext = document.getElementById('persona-next');

    var cards = track.querySelectorAll('.persona-card');
    if (!cards || cards.length === 0) return;

    var currentIndex = 0;

    function normalizeIndex(i) {
        var len = cards.length;
        if (len <= 0) return 0;
        var x = i % len;
        if (x < 0) x += len;
        return x;
    }

    function applyVisualState() {
        cards.forEach(function (c) {
            c.classList.remove('is-left');
            c.classList.remove('is-center');
            c.classList.remove('is-right');
            c.classList.remove('is-hidden');
        });

        var len = cards.length;
        if (len <= 0) return;

        var center = cards[currentIndex];
        var left = cards[normalizeIndex(currentIndex - 1)];
        var right = cards[normalizeIndex(currentIndex + 1)];

        cards.forEach(function (c) {
            c.classList.add('is-hidden');
        });
        if (left) left.classList.remove('is-hidden');
        if (right) right.classList.remove('is-hidden');
        if (center) center.classList.remove('is-hidden');

        if (left) left.classList.add('is-left');
        if (right) right.classList.add('is-right');
        if (center) center.classList.add('is-center');

        cards.forEach(function (c) {
            c.classList.remove('is-selected');
        });
        if (center) center.classList.add('is-selected');
    }

    function selectIndex(i) {
        currentIndex = normalizeIndex(i);
        applyVisualState();
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function (e) {
            e.preventDefault();
            selectIndex(currentIndex - 1);
        });
    }
    if (btnNext) {
        btnNext.addEventListener('click', function (e) {
            e.preventDefault();
            selectIndex(currentIndex + 1);
        });
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            var idx = 0;
            cards.forEach(function (c, i) {
                if (c === card) idx = i;
            });
            selectIndex(idx);
        });
    });

    if (stage) {
        var startX = 0;
        var startY = 0;
        var tracking = false;

        stage.addEventListener('touchstart', function (e) {
            if (!e.touches || e.touches.length !== 1) return;
            tracking = true;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: true });

        stage.addEventListener('touchend', function (e) {
            if (!tracking) return;
            tracking = false;
            if (!e.changedTouches || e.changedTouches.length !== 1) return;
            var endX = e.changedTouches[0].clientX;
            var endY = e.changedTouches[0].clientY;
            var dx = endX - startX;
            var dy = endY - startY;

            if (Math.abs(dx) < 35) return;
            if (Math.abs(dx) < Math.abs(dy)) return;

            if (dx < 0) {
                selectIndex(currentIndex + 1);
            } else {
                selectIndex(currentIndex - 1);
            }
        }, { passive: true });
    }

    selectIndex(currentIndex);
});
</script>

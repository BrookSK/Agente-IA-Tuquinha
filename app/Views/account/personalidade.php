<?php
/** @var array $user */
/** @var array $plan */
/** @var array $personalities */

$currentDefaultPersonaId = isset($user['default_persona_id']) ? (int)$user['default_persona_id'] : 0;
$successMessage = $success ?? null;
?>
<style>
    .persona-default-card {
        width: 300px;
        background: var(--surface-card);
        border-radius: 20px;
        border: 1px solid var(--border-subtle);
        overflow: hidden;
        color: var(--text-primary);
        font-size: 12px;
        text-align: left;
        cursor: pointer;
        box-shadow: 0 12px 30px rgba(15,23,42,0.25);
        transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease, opacity 0.16s ease, filter 0.16s ease;
        opacity: 0.55;
        transform: scale(0.96);
    }
    .persona-default-card--active {
        border-color: var(--accent-soft);
        box-shadow: 0 0 0 1px rgba(244,114,182,0.4);
        opacity: 1;
        transform: scale(1);
    }
    .persona-default-card-image {
        width: 100%;
        height: 260px;
        overflow: hidden;
        background: var(--surface-subtle);
    }
    .persona-default-card-desc {
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.4;
    }

    .persona-nav-btn {
        position:absolute;
        top:50%;
        transform:translateY(-50%);
        width:56px;
        height:56px;
        border-radius:999px;
        border:1px solid var(--border-subtle);
        background:rgba(5,5,9,0.9);
        color:var(--text-primary);
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
        margin-top: 12px;
        padding: 8px 40px 12px 40px;
        min-height: 440px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        touch-action: pan-y;
    }
    .persona-stage-items {
        position: relative;
        width: 100%;
        height: 420px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    .persona-stage-items .persona-default-card {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%) scale(0.96);
        pointer-events: auto;
    }
    .persona-default-card.is-center {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.08);
        filter: none;
        z-index: 3;
    }
    .persona-default-card.is-left {
        opacity: 0.38;
        transform: translate(calc(-50% - 260px), -50%) scale(0.9);
        filter: grayscale(1);
        z-index: 2;
    }
    .persona-default-card.is-right {
        opacity: 0.38;
        transform: translate(calc(-50% + 260px), -50%) scale(0.9);
        filter: grayscale(1);
        z-index: 2;
    }
    .persona-default-card.is-hidden {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.85);
        pointer-events: none;
        z-index: 1;
    }

    @media (max-width: 640px) {
        .persona-stage {
            padding: 8px 10px 12px 10px;
            min-height: 430px;
        }
        .persona-nav-btn {
            width: 60px;
            height: 60px;
            background: rgba(5,5,9,0.82);
            font-size:28px;
        }
        .persona-default-card.is-left {
            opacity: 0.22;
            transform: translate(calc(-50% - 170px), -50%) scale(0.86);
        }
        .persona-default-card.is-right {
            opacity: 0.22;
            transform: translate(calc(-50% + 170px), -50%) scale(0.86);
        }
        .persona-default-card.is-center {
            transform: translate(-50%, -50%) scale(1.03);
        }
    }
</style>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 6px; font-weight: 650;">Escolha sua personalidade padrão</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px; max-width:600px;">
        Aqui você escolhe qual personalidade o Tuquinha vai usar por padrão na sua conta.
        Quando você definir uma personalidade padrão, todos os novos chats vão começar automaticamente com essa personalidade.
    </p>

    <?php if (!empty($successMessage)): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <div style="font-size:12px; color:#8d8d8d; margin-bottom:10px;">
        Plano atual: <strong><?= htmlspecialchars($plan['name'] ?? '') ?></strong>
    </div>

    <?php if (empty($personalities)): ?>
        <div style="background:var(--surface-card); border-radius:12px; padding:12px 14px; border:1px solid var(--border-subtle); font-size:14px; color:var(--text-secondary);">
            Ainda não há personalidades ativas cadastradas pelo administrador.
        </div>
    <?php else: ?>
        <form action="/conta/personalidade" method="post">
            <input type="hidden" name="default_persona_id" id="default-persona-id" value="<?= $currentDefaultPersonaId ?>">
            <div class="persona-stage">
                <button type="button" id="default-persona-prev" class="persona-nav-btn" style="left:0;" aria-label="Anterior">‹</button>
                <button type="button" id="default-persona-next" class="persona-nav-btn" style="right:0;" aria-label="Próximo">›</button>
                <div id="persona-default-list" class="persona-stage-items">
                <button type="button" class="persona-card-btn persona-default-card<?= $currentDefaultPersonaId === 0 ? ' persona-default-card--active' : '' ?>" data-persona-id="0">
                    <div class="persona-default-card-image">
                        <img src="/public/favicon.png" alt="Padrão do Tuquinha" style="width:100%; height:100%; object-fit:cover; display:block;">
                    </div>
                    <div style="padding:10px 12px 12px 12px;">
                        <div style="font-size:16px; font-weight:650; margin-bottom:4px;">Padrão do Tuquinha</div>
                        <div class="persona-default-card-desc">
                            Deixa o sistema escolher a melhor personalidade global para você.
                        </div>
                    </div>
                </button>
                <?php foreach ($personalities as $persona): ?>
                    <?php
                        $pid = (int)($persona['id'] ?? 0);
                        $pname = trim((string)($persona['name'] ?? ''));
                        $parea = trim((string)($persona['area'] ?? ''));
                        $imagePath = trim((string)($persona['image_path'] ?? ''));
                        if ($imagePath === '') {
                            $imagePath = '/public/favicon.png';
                        }
                    ?>
                    <button type="button" class="persona-card-btn persona-default-card<?= $currentDefaultPersonaId === $pid ? ' persona-default-card--active' : '' ?>" data-persona-id="<?= $pid ?>">
                        <div class="persona-default-card-image">
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($pname) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                        </div>
                        <div style="padding:10px 12px 12px 12px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;">
                                <div style="font-size:16px; font-weight:650; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($pname) ?>
                                </div>
                            </div>
                            <?php if ($parea !== ''): ?>
                                <div style="font-size:12px; color:#ffcc80; margin-bottom:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($parea) ?>
                                </div>
                            <?php endif; ?>
                            <div class="persona-default-card-desc">
                                Clique para usar essa personalidade como padrão em novos chats.
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
                <div style="font-size:11px; color:#8d8d8d; max-width:60%;">
                    Clique em uma opção e depois em "Salvar" para atualizar a personalidade padrão da sua conta.
                </div>
                <button type="submit" style="
                    border:none; border-radius:999px; padding:8px 16px;
                    background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                    font-weight:600; font-size:13px; cursor:pointer;">
                    Salvar personalidade padrão
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var personaList = document.getElementById('persona-default-list');
    var hiddenPersonaInput = document.getElementById('default-persona-id');
    if (personaList && hiddenPersonaInput) {
        var stage = personaList.parentElement;
        var btnPrev = document.getElementById('default-persona-prev');
        var btnNext = document.getElementById('default-persona-next');
        var buttons = personaList.querySelectorAll('.persona-card-btn');

        if (!buttons || buttons.length === 0) return;

        var currentIndex = 0;
        buttons.forEach(function (btn, idx) {
            if (btn.classList.contains('persona-default-card--active')) {
                currentIndex = idx;
            }
        });

        function normalizeIndex(i) {
            var len = buttons.length;
            if (len <= 0) return 0;
            var x = i % len;
            if (x < 0) x += len;
            return x;
        }

        function applyVisualState() {
            buttons.forEach(function (btn) {
                btn.classList.remove('is-left');
                btn.classList.remove('is-center');
                btn.classList.remove('is-right');
                btn.classList.remove('is-hidden');
            });

            var len = buttons.length;
            if (len <= 0) return;

            var center = buttons[currentIndex];
            var left = buttons[normalizeIndex(currentIndex - 1)];
            var right = buttons[normalizeIndex(currentIndex + 1)];

            buttons.forEach(function (btn) {
                btn.classList.add('is-hidden');
            });
            if (left) left.classList.remove('is-hidden');
            if (right) right.classList.remove('is-hidden');
            if (center) center.classList.remove('is-hidden');

            if (left) left.classList.add('is-left');
            if (right) right.classList.add('is-right');
            if (center) center.classList.add('is-center');
        }

        function selectIndex(i) {
            currentIndex = normalizeIndex(i);
            var btn = buttons[currentIndex];
            if (!btn) return;

            var id = btn.getAttribute('data-persona-id') || '0';
            hiddenPersonaInput.value = id;

            buttons.forEach(function (b) {
                b.classList.remove('persona-default-card--active');
            });
            btn.classList.add('persona-default-card--active');
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

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = 0;
                buttons.forEach(function (b, i) {
                    if (b === btn) idx = i;
                });
                selectIndex(idx);
            });
        });

        selectIndex(currentIndex);
    }
});
</script>

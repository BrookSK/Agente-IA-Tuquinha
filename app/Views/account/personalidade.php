<?php
/** @var array $user */
/** @var array $plan */
/** @var array $personalities */

$currentDefaultPersonaId = isset($user['default_persona_id']) ? (int)$user['default_persona_id'] : 0;
$successMessage = $success ?? null;
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 6px; font-weight: 650;">Escolha sua personalidade padrão</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px; max-width:600px;">
        Aqui você escolhe qual personalidade o Tuquinha vai usar por padrão sempre que você abrir um novo chat.
        Você ainda pode trocar a personalidade dentro de cada conversa quando quiser.
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
        <div style="background:#111118; border-radius:12px; padding:12px 14px; border:1px solid #272727; font-size:14px; color:#b0b0b0;">
            Ainda não há personalidades ativas cadastradas pelo administrador.
        </div>
    <?php else: ?>
        <form action="/conta/personalidade" method="post">
            <input type="hidden" name="default_persona_id" id="default-persona-id" value="<?= $currentDefaultPersonaId ?>">
            <div id="persona-default-list" style="
                margin-top:12px;
                display:flex;
                gap:18px;
                overflow-x:auto;
                padding:8px 2px 12px 2px;
                scroll-snap-type:x mandatory;
            ">
                <button type="button" class="persona-card-btn" data-persona-id="0" style="
                    flex:0 0 280px;
                    max-width:300px;
                    background:#050509;
                    border-radius:20px;
                    border:1px solid <?= $currentDefaultPersonaId === 0 ? '#ff6f60' : '#272727' ?>;
                    overflow:hidden;
                    color:#f5f5f5;
                    font-size:12px;
                    text-align:left;
                    cursor:pointer;
                    box-shadow:0 18px 35px rgba(0,0,0,0.55);
                    transition:transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
                    scroll-snap-align:center;
                ">
                    <div style="width:100%; height:140px; background:radial-gradient(circle at top left,#e53935 0,#050509 60%); display:flex; align-items:center; justify-content:center; font-size:26px;">
                        ✨
                    </div>
                    <div style="padding:10px 12px 12px 12px;">
                        <div style="font-size:16px; font-weight:650; margin-bottom:4px;">Padrão do Tuquinha</div>
                        <div style="font-size:12px; color:#b0b0b0; line-height:1.4;">
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
                    <button type="button" class="persona-card-btn" data-persona-id="<?= $pid ?>" style="
                        flex:0 0 280px;
                        max-width:300px;
                        background:#050509;
                        border-radius:20px;
                        border:1px solid <?= $currentDefaultPersonaId === $pid ? '#ff6f60' : '#272727' ?>;
                        overflow:hidden;
                        color:#f5f5f5;
                        font-size:12px;
                        text-align:left;
                        cursor:pointer;
                        box-shadow:0 18px 35px rgba(0,0,0,0.55);
                        transition:transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
                        scroll-snap-align:center;
                    ">
                        <div style="width:100%; height:200px; overflow:hidden; background:#111118;">
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
                            <div style="font-size:12px; color:#b0b0b0; line-height:1.4;">
                                Clique para usar essa personalidade como padrão em novos chats.
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
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
        var buttons = personaList.querySelectorAll('.persona-card-btn');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-persona-id') || '0';
                hiddenPersonaInput.value = id;

                buttons.forEach(function (b) {
                    b.style.borderColor = '#272727';
                    b.style.boxShadow = '';
                });

                btn.style.borderColor = '#ff6f60';
                btn.style.boxShadow = '0 0 0 1px rgba(255,111,96,0.5)';
            });
        });
    }
});
</script>

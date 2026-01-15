<?php
/** @var array $chatHistory */
/** @var array $allowedModels */
/** @var string|null $currentModel */
/** @var array|null $currentPlan */
/** @var string|null $draftMessage */
/** @var string|null $audioError */
/** @var array $attachments */
/** @var string|null $chatError */
/** @var array|null $projectContext */

$hasMediaOrFiles = !empty($currentPlan['allow_audio']) || !empty($currentPlan['allow_images']) || !empty($currentPlan['allow_files']);
$isFreePlan = $currentPlan && (($currentPlan['slug'] ?? '') === 'free');
$freeChatLimit = (int)\App\Models\Setting::get('free_memory_chat_chars', '400');
if ($freeChatLimit <= 0) { $freeChatLimit = 400; }

function render_markdown_safe(string $text): string {
    // Alguns modelos retornam quebras de linha como texto literal "\\n".
    // Normaliza isso antes de escapar HTML para evitar que "\\n" apareça na UI.
    $text = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $text);

    // Escapa HTML primeiro
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // ### Título -> <strong>...</strong>
    $escaped = preg_replace('/^#{3,6}\s*(.+)$/m', '<strong>$1</strong>', $escaped);

    // **negrito** -> <strong>negrito</strong>
    $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);

    $escaped = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $escaped);

    // "- texto" no começo da linha vira bullet visual
    $escaped = preg_replace('/^\-\s+/m', '• ', $escaped);

    // Agrupa blocos separados por linha em branco em parágrafos
    $escaped = str_replace(["\r\n", "\r"], "\n", $escaped);

    // Linha separadora estilo ChatGPT: converte '---' em um token que vira <hr>
    $escaped = preg_replace('/^\s*---+\s*$/m', '[[HR]]', $escaped);

    // Se o modelo mandar o token literal [[HR]], trata como separador também
    $escaped = preg_replace('/^\s*\[\[HR\]\]\s*$/m', '[[HR]]', $escaped);

    // Garante que [[HR]] fique isolado como um bloco (para virar <hr>)
    $escaped = preg_replace('/\n\s*\[\[HR\]\]\s*\n/u', "\n\n[[HR]]\n\n", "\n" . $escaped . "\n");
    $escaped = trim($escaped);

    // Se o modelo mandar tudo com 1 quebra de linha, cria respiros automáticos
    // antes de títulos/listas para ficar mais legível (estilo ChatGPT)
    $escaped = preg_replace("/\n(?=(?:\d+\.|•)\s)/u", "\n\n", $escaped);
    $escaped = preg_replace("/\n(?=<strong>)/u", "\n\n", $escaped);

    // Imagens no formato Markdown: ![alt](url)
    // Segurança: só permite URLs http(s)
    $escaped = preg_replace_callback('/!\[([^\]\n]*)\]\(([^)\s]+)\)/u', function ($m) {
        $alt = (string)$m[1];
        $srcRawEscaped = (string)$m[2];

        $srcRaw = html_entity_decode($srcRawEscaped, ENT_QUOTES, 'UTF-8');
        $srcRaw = trim($srcRaw);
        if ($srcRaw === '') {
            return $m[0];
        }

        $isHttp = (stripos($srcRaw, 'http://') === 0) || (stripos($srcRaw, 'https://') === 0);
        if (!$isHttp) {
            return $m[0];
        }

        $srcAttr = htmlspecialchars($srcRaw, ENT_QUOTES, 'UTF-8');
        $altAttr = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        return '<a href="' . $srcAttr . '" target="_blank" rel="noopener noreferrer" style="display:block; text-decoration:none;">'
            . '<img src="' . $srcAttr . '" alt="' . $altAttr . '" style="display:block; width:100%; max-width:520px; border-radius:12px; border:1px solid var(--border-subtle);">'
            . '</a>';
    }, $escaped);

    // Links no formato Markdown: [texto](url)
    // Segurança: só permite URLs http(s) e links relativos iniciando com '/'
    $escaped = preg_replace_callback('/\[([^\]\n]+)\]\(([^)\s]+)\)/u', function ($m) {
        $label = (string)$m[1];
        $hrefRawEscaped = (string)$m[2];

        $hrefRaw = html_entity_decode($hrefRawEscaped, ENT_QUOTES, 'UTF-8');
        $hrefRaw = trim($hrefRaw);
        if ($hrefRaw === '') {
            return $m[0];
        }

        $isHttp = (stripos($hrefRaw, 'http://') === 0) || (stripos($hrefRaw, 'https://') === 0);
        $isRelative = (strpos($hrefRaw, '/') === 0);
        if (!$isHttp && !$isRelative) {
            return $m[0];
        }

        $hrefAttr = htmlspecialchars($hrefRaw, ENT_QUOTES, 'UTF-8');
        if ($isHttp) {
            return '<a href="' . $hrefAttr . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        }
        return '<a href="' . $hrefAttr . '">' . $label . '</a>';
    }, $escaped);

    $blocks = preg_split("/\n{2,}/", $escaped);
    $out = '';
    foreach ($blocks as $b) {
        $b = trim((string)$b);
        if ($b === '') {
            continue;
        }
        if ($b === '[[HR]]') {
            $out .= '<hr class="tuq-chat-hr">';
            continue;
        }
        $b = nl2br($b);
        $out .= '<p>' . $b . '</p>';
    }
    return '<div class="tuq-chat-md">' . $out . '</div>';
}
?>
<style>
.tuq-chat-md { line-height: 1.6; }
.tuq-chat-md p { margin: 0 0 0.9em 0; }
.tuq-chat-md p:last-child { margin-bottom: 0; }
.tuq-chat-md ul, .tuq-chat-md ol { margin: 0 0 0.9em 1.2em; padding: 0; }
.tuq-chat-md li { margin: 0.15em 0; }
.tuq-chat-md .tuq-chat-hr { border: none; border-top: 1px solid var(--border-subtle); margin: 14px 0; opacity: 0.8; }

 #chat-send-btn {
     flex: 0 0 auto !important;
     height: 36px !important;
     padding: 0 14px !important;
     font-size: 13px !important;
     line-height: 1 !important;
     min-width: 92px !important;
     max-width: 140px !important;
     white-space: nowrap !important;
     display: inline-flex !important;
     align-items: center !important;
     justify-content: center !important;
     gap: 6px !important;
     background: #e53935 !important;
     color: #000 !important;
 }

@media (max-width: 640px) {
    #chat-input-bar {
        flex-direction: column;
        align-items: stretch;
    }
    #chat-input-bar > div:first-child {
        width: 100%;
    }
    #chat-message {
        width: 100%;
        min-height: 72px;
    }
    #chat-send-btn {
        width: 100% !important;
        max-width: none !important;
    }
}

@keyframes tuqDot {
    0% { transform: translateY(0); opacity: 0.5; }
    50% { transform: translateY(-5px); opacity: 1; }
    100% { transform: translateY(0); opacity: 0.5; }
}

 .tuqChatTopbar {
     display:flex;
     align-items:center;
     justify-content:space-between;
     gap:10px;
     margin-top:10px;
     margin-bottom:6px;
 }
 .tuqChatTitleWrap {
     display:flex;
     align-items:center;
     gap:10px;
     min-width:0;
 }
 .tuqChatTitleText {
     font-size:16px;
     font-weight:750;
     min-width:0;
     overflow:hidden;
     text-overflow:ellipsis;
     white-space:nowrap;
 }
 .tuqChatMenuWrap {
     position:relative;
     flex:0 0 auto;
 }
 .tuqChatMenuBtn {
     border:1px solid var(--border-subtle);
     background:var(--surface-subtle);
     color:var(--text-primary);
     border-radius:999px;
     padding:6px 10px;
     font-size:12px;
     cursor:pointer;
     display:inline-flex;
     align-items:center;
     gap:8px;
 }
 .tuqChatMenuPanel {
     position:absolute;
     right:0;
     top: calc(100% + 8px);
     z-index: 50;
     width: 260px;
     background: var(--surface-card);
     border:1px solid var(--border-subtle);
     border-radius:12px;
     padding:8px;
     box-shadow: 0 16px 40px rgba(0,0,0,0.35);
     display:none;
 }
 .tuqChatMenuPanel.is-open { display:block; }
 .tuqChatMenuItem {
     width:100%;
     display:flex;
     justify-content:flex-start;
     align-items:center;
     gap:10px;
     border:none;
     background:transparent;
     color:var(--text-primary);
     padding:10px 10px;
     border-radius:10px;
     cursor:pointer;
     text-align:left;
     font-size:13px;
 }
 .tuqChatMenuItem:hover { background: var(--surface-subtle); }
 .tuqChatMenuItemDanger { color:#ff6b6b; }
 .tuqChatMenuDivider { height:1px; background:var(--border-subtle); margin:6px 0; }
 .tuqChatMenuSelect {
     width:100%;
     padding:8px 10px;
     border-radius:10px;
     border:1px solid var(--border-subtle);
     background:var(--surface-subtle);
     color:var(--text-primary);
     font-size:12px;
     outline:none;
 }
 .tuqChatMenuHint {
     font-size:11px;
     color:var(--text-secondary);
     padding:6px 10px 0 10px;
 }
</style>
<?php
$convSettings = $conversationSettings ?? null;
$canUseConversationSettings = !empty($canUseConversationSettings);
/** @var array|null $currentPersona */
/** @var array|null $personalities */
/** @var bool $planAllowsPersonalities */
/** @var int|null $defaultPersonaId */
$currentPersonaData = $currentPersona ?? null;
$personaOptions = $personalities ?? [];
$planAllowsPersonalitiesFlag = !empty($planAllowsPersonalities);
$defaultPersonaIdValue = isset($defaultPersonaId) ? (int)$defaultPersonaId : 0;

$tuqChatAvatarUrl = '/public/perso_padrao.png';
if ($currentPersonaData) {
    $pImage = trim((string)($currentPersonaData['image_path'] ?? ''));
    if ($pImage !== '') {
        $tuqChatAvatarUrl = $pImage;
    }
}
$tuqChatAvatarUrlSafe = htmlspecialchars($tuqChatAvatarUrl, ENT_QUOTES, 'UTF-8');

$conversationTitleText = trim((string)($conversationTitle ?? ''));
if ($conversationTitleText === '') {
    $conversationTitleText = 'Chat sem título';
}

$conversationProjectIdValue = isset($conversationProjectId) ? (int)$conversationProjectId : 0;
$conversationIsFavoriteValue = !empty($conversationIsFavorite);
$userProjectsList = is_array($userProjects ?? null) ? $userProjects : [];

$planAllowsProjectsAccess = !empty($_SESSION['is_admin']);
if (!$planAllowsProjectsAccess && !empty($currentPlan) && is_array($currentPlan)) {
    $planAllowsProjectsAccess = !empty($currentPlan['allow_projects_access']);
}

$showProjectMenu = !empty($_SESSION['user_id']) && !empty($planAllowsProjectsAccess);

// Determina se o usuário está em um plano pago (não free) para exibir CTA de compra de tokens
$canShowBuyTokensCta = false;
$isAdmin = !empty($_SESSION['is_admin']);
if (!empty($currentPlan) && is_array($currentPlan)) {
    $slug = (string)($currentPlan['slug'] ?? '');
    if ($slug !== 'free' || $isAdmin) {
        $canShowBuyTokensCta = true;
    }
}
?>
<div style="max-width: 900px; width: 100%; margin: 0 auto; padding: 0 8px; display: flex; flex-direction: column; min-height: calc(100vh - 56px - 80px); box-sizing: border-box;">
    <?php if (!empty($conversationId)): ?>
        <div class="tuqChatTopbar">
            <div class="tuqChatTitleWrap">
                <div class="tuqChatTitleText" title="<?= htmlspecialchars($conversationTitleText) ?>">
                    <?= htmlspecialchars($conversationTitleText) ?>
                </div>
                <?php
                    $personaBadgeText = 'Padrão do Tuquinha';
                    if (!empty($currentPersona) && is_array($currentPersona)) {
                        $pName = trim((string)($currentPersona['name'] ?? ''));
                        $pArea = trim((string)($currentPersona['area'] ?? ''));
                        if ($pName !== '') {
                            $personaBadgeText = $pName;
                            if ($pArea !== '') {
                                $personaBadgeText .= ' • ' . $pArea;
                            }
                        }
                    }
                ?>
                <div title="Personalidade do chat" style="
                    border:1px solid var(--border-subtle);
                    background:var(--surface-subtle);
                    color:var(--text-secondary);
                    padding:6px 10px;
                    border-radius:999px;
                    font-size:11px;
                    line-height:1;
                    max-width:260px;
                    white-space:nowrap;
                    overflow:hidden;
                    text-overflow:ellipsis;
                ">
                    <?= htmlspecialchars($personaBadgeText) ?>
                </div>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <form method="post" action="/chat/favoritar" style="margin:0; display:inline;">
                        <input type="hidden" name="conversation_id" value="<?= (int)$conversationId ?>">
                        <input type="hidden" name="redirect" value="/chat?c=<?= (int)$conversationId ?>">
                        <button type="submit" title="<?= !empty($conversationIsFavoriteValue) ? 'Desfavoritar' : 'Favoritar' ?>" style="
                            border:1px solid var(--border-subtle);
                            background:var(--surface-subtle);
                            color:<?= !empty($conversationIsFavoriteValue) ? '#ffd166' : 'var(--text-primary)' ?>;
                            width:32px; height:32px;
                            border-radius:999px;
                            cursor:pointer;
                            font-size:14px;
                            line-height:1;
                        "><?= !empty($conversationIsFavoriteValue) ? '★' : '☆' ?></button>
                    </form>
                <?php endif; ?>

                <button type="button" id="tuqChatRenameBtn" title="Renomear" style="
                    border:1px solid var(--border-subtle);
                    background:var(--surface-subtle);
                    color:var(--text-primary);
                    width:32px; height:32px;
                    border-radius:999px;
                    cursor:pointer;
                    font-size:14px;
                    line-height:1;
                ">✏️</button>

                <form id="tuqChatRenameForm" method="post" action="/chat/renomear" style="margin:0; display:none;">
                    <input type="hidden" name="conversation_id" value="<?= (int)$conversationId ?>">
                    <input type="hidden" name="redirect" value="/chat?c=<?= (int)$conversationId ?>">
                    <input type="hidden" name="title" id="tuqChatRenameTitle" value="">
                </form>
            </div>
            <div class="tuqChatMenuWrap" style="display:flex; align-items:center; gap:8px;">
                <?php if (!empty($showProjectMenu)): ?>
                    <form method="post" action="/chat/projeto" style="margin:0; display:inline;">
                        <input type="hidden" name="conversation_id" value="<?= (int)$conversationId ?>">
                        <input type="hidden" name="redirect" value="/chat?c=<?= (int)$conversationId ?>">
                        <select name="project_id" title="Adicionar ao projeto" onchange="this.form.submit()" style="
                            max-width:220px;
                            padding:7px 10px;
                            border-radius:999px;
                            border:1px solid var(--border-subtle);
                            background:var(--surface-subtle);
                            color:var(--text-primary);
                            font-size:12px;
                        ">
                            <option value="0" <?= $conversationProjectIdValue <= 0 ? 'selected' : '' ?>>Adicionar ao projeto</option>
                            <?php foreach ($userProjectsList as $p): ?>
                                <?php
                                    $pid = (int)($p['id'] ?? 0);
                                    $pname = trim((string)($p['name'] ?? ''));
                                    if ($pid <= 0 || $pname === '') { continue; }
                                ?>
                                <option value="<?= $pid ?>" <?= $conversationProjectIdValue === $pid ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pname) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <form method="post" action="/chat/excluir" style="margin:0; display:inline;">
                    <input type="hidden" name="conversation_id" value="<?= (int)$conversationId ?>">
                    <input type="hidden" name="redirect" value="/historico">
                    <button type="submit" title="Apagar" onclick="return confirm('Excluir este chat? Essa ação não pode ser desfeita.');" style="
                        border:1px solid var(--border-subtle);
                        background:var(--surface-subtle);
                        color:#ff6b6b;
                        width:32px; height:32px;
                        border-radius:999px;
                        cursor:pointer;
                        font-size:14px;
                        line-height:1;
                    ">🗑</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($projectContext) && !empty($projectContext['project']) && !empty($conversationId)): ?>
        <?php
            $p = $projectContext['project'];
            $pName = (string)($p['name'] ?? 'Projeto');
            $pId = (int)($p['id'] ?? 0);
            $total = (int)($projectContext['base_files_total'] ?? 0);
            $withText = (int)($projectContext['base_files_with_text'] ?? 0);
        ?>
        <div style="margin-top:10px; margin-bottom:6px; background:#111118; border:1px solid #272727; border-radius:12px; padding:10px 12px; font-size:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
            <div style="color:#b0b0b0;">
                <span style="color:#f5f5f5; font-weight:600;">Projeto:</span>
                <a href="/projetos/ver?id=<?= (int)$pId ?>" style="color:#ff6f60; text-decoration:none; font-weight:600;">
                    <?= htmlspecialchars($pName) ?>
                </a>
                <span style="margin-left:8px; color:#8d8d8d;">Arquivos base: <?= (int)$withText ?>/<?= (int)$total ?> com texto</span>
            </div>
            <div style="color:#8d8d8d;">
                Dica: cite arquivos assim: <strong>arquivo.md</strong> (ou <strong>arquivo</strong>)
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($conversationId) && !empty($personaOptions) && is_array($personaOptions) && $isFreePlan && empty(($_SESSION['free_persona_confirmed'][(int)$conversationId] ?? null))): ?>
        <style>
            .chat-persona-card {
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
            .chat-persona-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 22px 40px rgba(15,23,42,0.3);
                border-color: var(--accent-soft);
                opacity: 0.95;
            }
            .chat-persona-card.is-selected {
                opacity: 1;
                transform: scale(1);
                border-color: #2e7d32;
                box-shadow: 0 22px 46px rgba(0,0,0,0.35);
            }
            .chat-persona-card-image {
                width: 100%;
                height: 220px;
                overflow: hidden;
                background: var(--surface-subtle);
            }
            .chat-persona-card-desc {
                font-size: 12px;
                color: var(--text-secondary);
                line-height: 1.4;
                max-height: 5.4em;
                overflow: hidden;
            }
            .chat-persona-card-muted {
                font-size: 12px;
                color: var(--text-secondary);
            }

            .chat-persona-nav-btn {
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

            .chat-persona-stage {
                position: relative;
                margin-top: 8px;
                padding: 16px 40px 18px 40px;
                min-height: 420px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow-x: hidden;
                overflow-y: visible;
                touch-action: pan-y;
            }
            #chat-persona-carousel {
                position: relative;
                width: 100%;
                height: 400px;
                display: flex;
                align-items: center;
                justify-content: center;
                pointer-events: none;
            }
            #chat-persona-carousel .chat-persona-card {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%) scale(0.96);
                pointer-events: auto;
            }
            #chat-persona-carousel .chat-persona-card.is-center {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.08);
                filter: none;
                z-index: 3;
            }
            #chat-persona-carousel .chat-persona-card.is-left {
                opacity: 0.38;
                transform: translate(calc(-50% - 260px), -50%) scale(0.9);
                filter: grayscale(1);
                z-index: 2;
            }
            #chat-persona-carousel .chat-persona-card.is-right {
                opacity: 0.38;
                transform: translate(calc(-50% + 260px), -50%) scale(0.9);
                filter: grayscale(1);
                z-index: 2;
            }
            #chat-persona-carousel .chat-persona-card.is-hidden {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.85);
                pointer-events: none;
                z-index: 1;
            }

            @media (max-width: 640px) {
                .chat-persona-stage {
                    padding: 8px 10px 10px 10px;
                    min-height: 410px;
                }
                .chat-persona-nav-btn {
                    width: 60px;
                    height: 60px;
                    background: rgba(5,5,9,0.82);
                    font-size:28px;
                }
                #chat-persona-carousel .chat-persona-card.is-left {
                    opacity: 0.22;
                    transform: translate(calc(-50% - 170px), -50%) scale(0.86);
                }
                #chat-persona-carousel .chat-persona-card.is-right {
                    opacity: 0.22;
                    transform: translate(calc(-50% + 170px), -50%) scale(0.86);
                }
                #chat-persona-carousel .chat-persona-card.is-center {
                    transform: translate(-50%, -50%) scale(1.03);
                }
            }
        </style>

        <div style="margin-top:10px; margin-bottom:10px;">
            <div style="margin-bottom:8px; font-size:12px; color:#b0b0b0;">Escolha a personalidade do Tuquinha (preview):</div>
            <div class="chat-persona-stage">
                <button type="button" id="chat-persona-prev" class="chat-persona-nav-btn" style="left:0;" aria-label="Anterior">‹</button>
                <button type="button" id="chat-persona-next" class="chat-persona-nav-btn" style="right:0;" aria-label="Próximo">›</button>

                <div id="chat-persona-carousel" style="display:flex;">
                    <a href="/chat?c=<?= (int)$conversationId ?>&confirm_default=1" class="chat-persona-card" style="cursor:pointer; display:block; text-align:left; padding:0;">
                        <div class="chat-persona-card-image">
                            <img src="/public/perso_padrao.png" alt="Padrão do Tuquinha" onerror="this.onerror=null;this.src='/public/favicon.png';" style="width:100%; height:100%; object-fit:cover; display:block;">
                        </div>
                        <div style="padding:10px 12px 12px 12px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;">
                                <div style="font-size:18px; font-weight:650; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Padrão do Tuquinha</div>
                            </div>
                            <div class="chat-persona-card-desc">
                                <?= nl2br(htmlspecialchars((string)\App\Models\Setting::get('default_tuquinha_description', 'Deixa o sistema escolher a melhor personalidade global para você.'))) ?>
                            </div>
                        </div>
                    </a>

                    <?php
                        $hasMb = function_exists('mb_substr') && function_exists('mb_strlen');
                    ?>
                    <?php foreach ($personaOptions as $persona): ?>
                        <?php
                            $id = (int)($persona['id'] ?? 0);
                            $name = trim((string)($persona['name'] ?? ''));
                            $area = trim((string)($persona['area'] ?? ''));
                            $imagePath = trim((string)($persona['image_path'] ?? ''));
                            $isDefault = !empty($persona['is_default']);
                            $isComingSoon = !empty($persona['coming_soon']);
                            $defaultPersonaImage = '/public/perso_padrao.png';
                            $prompt = trim((string)($persona['prompt'] ?? ''));
                            $desc = '';
                            if ($prompt !== '') {
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
                                $imagePath = $isDefault ? $defaultPersonaImage : '/public/favicon.png';
                            }
                            if ($id <= 0 || $name === '') { continue; }
                        ?>
                        <a href="javascript:void(0)" class="chat-persona-card" style="cursor:not-allowed;">
                            <div class="chat-persona-card-image">
                                <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($name) ?>" onerror="this.onerror=null;this.src='/public/favicon.png';" style="width:100%; height:100%; object-fit:cover; display:block;">
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
                                    <div class="chat-persona-card-desc">
                                        <?= nl2br(htmlspecialchars($desc)) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="chat-persona-card-muted">
                                        <?= $isComingSoon ? 'Preview disponível. Em breve você poderá usar essa personalidade.' : 'Disponível nos planos pagos.' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="margin-top:-6px; font-size:11px; color:#8d8d8d;">No plano Free você pode ver as personalidades, mas só pode usar a personalidade padrão.</div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var track = document.getElementById('chat-persona-carousel');
            if (!track) return;

            var stage = track.parentElement;

            var btnPrev = document.getElementById('chat-persona-prev');
            var btnNext = document.getElementById('chat-persona-next');

            var cards = track.querySelectorAll('.chat-persona-card');
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
    <?php endif; ?>

    <?php if (!empty($conversationId) && $canUseConversationSettings): ?>
        <div style="margin-top:10px; margin-bottom:6px; display:flex; justify-content:flex-end;">
            <button type="button" id="chat-rules-toggle" style="
                border:none;
                border-radius:999px;
                padding:4px 10px;
                background:#111118;
                color:#f5f5f5;
                font-size:11px;
                border:1px solid #272727;
                cursor:pointer;
            ">
                Regras deste chat
            </button>
        </div>
        <div id="chat-rules-panel" style="display:none; margin-bottom:6px; background:#111118; border-radius:12px; border:1px solid #272727; padding:10px 12px; font-size:12px;">
            <form action="/chat/settings" method="post" style="display:flex; flex-direction:column; gap:6px;">
                <input type="hidden" name="conversation_id" value="<?= (int)$conversationId ?>">
                <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px;">
                    Ajuste regras e memórias só deste chat. O Tuquinha usa isso junto com as preferências globais da sua conta.
                    <?php if ($isFreePlan): ?>
                        <br><span style="font-size:11px; color:#8d8d8d;">No plano Free serão considerados até <?= htmlspecialchars((string)$freeChatLimit) ?> caracteres destas memórias/regras por chat.</span>
                    <?php endif; ?>
                </div>
                <div>
                    <label style="display:block; margin-bottom:3px; color:#ddd;">Memórias específicas deste chat</label>
                    <textarea name="memory_notes" rows="2" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:12px; resize:vertical; min-height:50px;" placeholder="Ex: dados de um projeto, briefing fixo, contexto que vale para toda esta conversa."><?php if (!empty($convSettings['memory_notes'])) { echo htmlspecialchars($convSettings['memory_notes']); } ?></textarea>
                </div>
                <div>
                    <label style="display:block; margin-bottom:3px; color:#ddd;">Regras específicas deste chat</label>
                    <textarea name="custom_instructions" rows="2" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:12px; resize:vertical; min-height:50px;" placeholder="Ex: agir como mentor de precificação, responder ultra direto, evitar exemplos de nichos X."><?php if (!empty($convSettings['custom_instructions'])) { echo htmlspecialchars($convSettings['custom_instructions']); } ?></textarea>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-top:2px;">
                    <div style="font-size:11px; color:#8d8d8d; max-width:70%;">
                        Essas regras valem só para este histórico. Para algo permanente em toda a conta, configure em "Minha conta".
                    </div>
                    <button type="submit" style="border:none; border-radius:999px; padding:5px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:11px; cursor:pointer;">
                        Salvar regras do chat
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    <?php if (!empty($chatError)): ?>
        <div style="margin-bottom:8px; background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; display:flex; justify-content:space-between; gap:8px; align-items:center;">
            <span><?= htmlspecialchars($chatError) ?></span>
            <?php if ($canShowBuyTokensCta): ?>
                <a href="/tokens/comprar" style="
                    border:none; border-radius:999px; padding:6px 12px;
                    background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                    font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap;">
                    Comprar mais tokens
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div id="chat-window" style="flex: 1; overflow-y: auto; padding: 12px 4px 12px 0;">
        <?php
        $attachmentsByMessageId = [];
        $legacyAttachments = [];
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attRow) {
                if (($attRow['type'] ?? '') === 'audio') {
                    continue;
                }
                $mid = isset($attRow['message_id']) ? (int)$attRow['message_id'] : 0;
                if ($mid > 0) {
                    if (!isset($attachmentsByMessageId[$mid])) {
                        $attachmentsByMessageId[$mid] = [];
                    }
                    $attachmentsByMessageId[$mid][] = $attRow;
                } else {
                    $legacyAttachments[] = $attRow;
                }
            }
        }
        ?>
        <?php if (empty($chatHistory)): ?>
            <div id="chat-empty-state" style="text-align: center; margin-top: 40px; color: #b0b0b0; font-size: 14px;">
                <div style="font-size: 18px; margin-bottom: 6px;">Bora começar esse papo? ✨</div>
                <div>Me conta rapidinho: em que fase você tá com seus projetos de marca?</div>
            </div>
        <?php else: ?>
            <?php if (!empty($legacyAttachments)): ?>
                <div style="margin-bottom:8px; display:flex; justify-content:flex-end;">
                    <div style="
                        max-width: 80%;
                        display: flex;
                        flex-wrap: wrap;
                        gap: 6px;
                    ">
                        <?php foreach ($legacyAttachments as $att): ?>
                            <?php
                            $isImage = str_starts_with((string)($att['mime_type'] ?? ''), 'image/');
                            $isCsv = in_array(($att['mime_type'] ?? ''), ['text/csv', 'application/vnd.ms-excel'], true);
                            $isPdf = ($att['mime_type'] ?? '') === 'application/pdf';
                            $size = (int)($att['size'] ?? 0);
                            $humanSize = '';
                            if ($size > 0) {
                                if ($size >= 1024 * 1024) {
                                    $humanSize = number_format($size / (1024 * 1024), 2, ',', '.') . ' MB';
                                } elseif ($size >= 1024) {
                                    $humanSize = number_format($size / 1024, 2, ',', '.') . ' KB';
                                } else {
                                    $humanSize = $size . ' B';
                                }
                            }
                            $label = 'Arquivo';
                            if ($isCsv) { $label = 'CSV'; }
                            elseif ($isPdf) { $label = 'PDF'; }
                            elseif ($isImage) { $label = 'Imagem'; }

                            $path = trim((string)($att['path'] ?? ''));
                            ?>
                            <div style="
                                display:flex;
                                flex-direction:column;
                                padding:6px 10px;
                                border-radius:12px;
                                background: var(--surface-subtle);
                                border:1px solid var(--border-subtle);
                                min-width:160px;
                                max-width:220px;
                            ">
                                <?php if ($isImage && $path !== ''): ?>
                                    <a href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="display:block; margin-bottom:4px; border-radius:8px; overflow:hidden; border:1px solid #272727;">
                                        <img src="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem enviada" style="display:block; width:100%; max-height:140px; object-fit:cover;">
                                    </a>
                                <?php endif; ?>
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                    <span style="font-size:14px;">
                                        <?= $isImage ? '🖼️' : ($isCsv ? '📊' : ($isPdf ? '📄' : '📎')) ?>
                                    </span>
                                    <span style="font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars((string)($att['original_name'] ?? 'arquivo')) ?>
                                    </span>
                                </div>
                                <div style="font-size:11px; color:#b0b0b0; margin-bottom:2px;">
                                    <?= htmlspecialchars(trim($label . ($humanSize ? ' · ' . $humanSize : ''))) ?>
                                </div>
                                <?php if ($path !== ''): ?>
                                    <div style="margin-top:2px; font-size:11px;">
                                        <a href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ffcc80; text-decoration:none;">
                                            Abrir <?= $isImage ? 'imagem' : 'arquivo' ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php foreach ($chatHistory as $message): ?>
                <?php
                $createdAt = isset($message['created_at']) ? strtotime((string)$message['created_at']) : null;
                $createdLabel = $createdAt ? date('d/m/Y H:i', $createdAt) : '';
                $tokensUsed = isset($message['tokens_used']) ? (int)$message['tokens_used'] : 0;
                $messageId = isset($message['id']) ? (int)$message['id'] : 0;
                ?>
                <?php if (($message['role'] ?? '') === 'user'): ?>
                    <?php
                    $rawContent = trim((string)($message['content'] ?? ''));
                    // remove recuo estranho no início de cada linha (inclui espaços, tabs e outros brancos)
                    $rawContent = preg_replace('/^\s+/mu', '', $rawContent);
                    ?>
                    <?php if (str_starts_with($rawContent, 'O usuário enviou os seguintes arquivos nesta mensagem')): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; margin-bottom: 10px;">
                        <div style="
                            max-width: 80%;
                            background: var(--surface-card);
                            border-radius: 16px 16px 4px 16px;
                            padding: 9px 12px;
                            font-size: 14px;
                            word-wrap: break-word;
                            border: 1px solid var(--border-subtle);
                        ">
                            <?php $content = $rawContent; ?>
                            <?= render_markdown_safe($content) ?>
                        </div>
                        <div style="margin-top: 2px; display:flex; align-items:center; gap:6px; font-size:10px; color:#777; max-width:80%; justify-content:flex-end;">
                            <?php if ($createdLabel): ?>
                                <span><?= htmlspecialchars($createdLabel) ?></span>
                            <?php endif; ?>
                            <button type="button" class="copy-message-btn" data-message-text="<?= htmlspecialchars($rawContent) ?>" style="
                                border:none; background:transparent; color:#b0b0b0; font-size:10px; cursor:pointer; padding:0;
                            ">Copiar</button>
                        </div>
                    </div>
                    <?php if ($messageId > 0 && !empty($attachmentsByMessageId[$messageId])): ?>
                        <div style="margin: -2px 0 10px 0; display:flex; justify-content:flex-end;">
                            <div style="max-width: 80%; display:flex; flex-wrap:wrap; gap:6px;">
                                <?php foreach ($attachmentsByMessageId[$messageId] as $att): ?>
                                    <?php
                                    $isImage = str_starts_with((string)($att['mime_type'] ?? ''), 'image/');
                                    $isCsv = in_array(($att['mime_type'] ?? ''), ['text/csv', 'application/vnd.ms-excel'], true);
                                    $isPdf = ($att['mime_type'] ?? '') === 'application/pdf';
                                    $size = (int)($att['size'] ?? 0);
                                    $humanSize = '';
                                    if ($size > 0) {
                                        if ($size >= 1024 * 1024) {
                                            $humanSize = number_format($size / (1024 * 1024), 2, ',', '.') . ' MB';
                                        } elseif ($size >= 1024) {
                                            $humanSize = number_format($size / 1024, 2, ',', '.') . ' KB';
                                        } else {
                                            $humanSize = $size . ' B';
                                        }
                                    }
                                    $label = 'Arquivo';
                                    if ($isCsv) { $label = 'CSV'; }
                                    elseif ($isPdf) { $label = 'PDF'; }
                                    elseif ($isImage) { $label = 'Imagem'; }
                                    $path = trim((string)($att['path'] ?? ''));
                                    ?>
                                    <div style="display:flex; flex-direction:column; padding:6px 10px; border-radius:12px; background: var(--surface-subtle); border:1px solid var(--border-subtle); min-width:160px; max-width:220px;">
                                        <?php if ($isImage && $path !== ''): ?>
                                            <a href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="display:block; margin-bottom:4px; border-radius:8px; overflow:hidden; border:1px solid #272727;">
                                                <img src="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem enviada" style="display:block; width:100%; max-height:140px; object-fit:cover;">
                                            </a>
                                        <?php endif; ?>
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                            <span style="font-size:14px;">
                                                <?= $isImage ? '🖼️' : ($isCsv ? '📊' : ($isPdf ? '📄' : '📎')) ?>
                                            </span>
                                            <span style="font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?= htmlspecialchars((string)($att['original_name'] ?? 'arquivo')) ?>
                                            </span>
                                        </div>
                                        <div style="font-size:11px; color:#b0b0b0; margin-bottom:2px;">
                                            <?= htmlspecialchars(trim($label . ($humanSize ? ' · ' . $humanSize : ''))) ?>
                                        </div>
                                        <?php if ($path !== ''): ?>
                                            <div style="margin-top:2px; font-size:11px;">
                                                <a href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ffcc80; text-decoration:none;">
                                                    Abrir <?= $isImage ? 'imagem' : 'arquivo' ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="display: flex; flex-direction: row; align-items: flex-start; gap: 8px; margin-bottom: 10px;">
                        <div style="
                            width: 28px;
                            height: 28px;
                            border-radius: 50%;
                            overflow: hidden;
                            flex-shrink: 0;
                            background: var(--surface-subtle);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            <img src="<?= $tuqChatAvatarUrlSafe ?>" alt="Tuquinha" onerror="this.onerror=null;this.src='/public/favicon.png';" style="width:100%; height:100%; display:block; object-fit:cover;">
                        </div>
                        <div style="
                            max-width: 80%;
                            background: var(--surface-card);
                            border-radius: 16px 16px 16px 4px;
                            padding: 9px 12px;
                            font-size: 14px;
                            word-wrap: break-word;
                            border: 1px solid var(--border-subtle);
                        ">
                            <?php
                            $content = trim((string)($message['content'] ?? ''));
                            $content = preg_replace('/^\s+/mu', '', $content);
                            ?>
                            <?= render_markdown_safe($content) ?>
                        </div>
                    </div>
                    <div style="margin: -6px 0 6px 36px; display:flex; align-items:center; gap:6px; font-size:10px; color:#777; max-width:80%;">
                        <?php if ($tokensUsed > 0): ?>
                            <span><?= htmlspecialchars($tokensUsed) ?> tokens</span>
                        <?php endif; ?>
                        <?php if ($createdLabel): ?>
                            <span>· <?= htmlspecialchars($createdLabel) ?></span>
                        <?php endif; ?>
                        <button type="button" class="copy-message-btn" data-message-text="<?= htmlspecialchars(trim((string)($message['content'] ?? ''))) ?>" style="
                            border:none; background:transparent; color:#b0b0b0; font-size:10px; cursor:pointer; padding:0;
                        ">Copiar</button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($audioError)): ?>
        <div style="margin-top:8px; background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
            <span><?= htmlspecialchars($audioError) ?></span>
            <button type="button" onclick="window.location.reload();" style="border:none; border-radius:999px; padding:6px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">
                Recarregar chat
            </button>
        </div>
    <?php endif; ?>

    <div id="chat-error-report" style="display:none; margin-top:8px; background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px;">
        <div id="chat-error-text" style="margin-bottom:6px;"></div>
        <div id="chat-error-actions" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <button type="button" id="btn-open-error-report" style="border:none; border-radius:999px; padding:6px 10px; background:#222; color:#ffcc80; font-size:12px; font-weight:600; cursor:pointer; border:1px solid #ffb74d;">
                Relatar problema
            </button>
            <button type="button" id="btn-close-error-report" style="border:none; border-radius:999px; padding:6px 10px; background:transparent; color:#ffbaba; font-size:12px; cursor:pointer;">
                Fechar
            </button>
        </div>
        <form id="chat-error-report-form" style="display:none; margin-top:8px; display:flex; flex-direction:column; gap:6px;">
            <textarea id="error-report-comment" name="user_comment" rows="3" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #a33; background:#050509; color:#f5f5f5; font-size:12px; resize:vertical;" placeholder="Explique rapidamente o que aconteceu (opcional, mas ajuda o suporte a entender)."></textarea>
            <div style="display:flex; gap:8px; justify-content:flex-end; align-items:center;">
                <button type="button" id="btn-cancel-error-report" style="border:none; border-radius:999px; padding:5px 10px; background:transparent; color:#ffbaba; font-size:12px; cursor:pointer;">
                    Cancelar
                </button>
                <button type="button" id="btn-send-error-report" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">
                    Enviar relato
                </button>
            </div>
            <div id="chat-error-report-feedback" style="font-size:11px; color:#c1ffda; display:none;"></div>
        </form>
    </div>

    <form action="/chat/send" method="post" enctype="multipart/form-data" style="margin-top: 12px;">
        <div id="chat-input-bar" style="
            display: flex;
            align-items: stretch;
            gap: 8px;
            background: var(--surface-card);
            border-radius: 18px;
            border: 1px solid var(--border-subtle);
            padding: 8px 10px;
        ">
            <div style="display: flex; flex-direction: column; gap: 6px; margin-right: <?= $hasMediaOrFiles ? '8px' : '0'; ?>;">
                <?php if (!empty($allowedModels)): ?>
                    <select name="model" style="
                        min-width: 150px;
                        background: var(--surface-subtle);
                        color: var(--text-primary);
                        border-radius: 999px;
                        border: 1px solid var(--border-subtle);
                        padding: 4px 9px;
                        font-size: 11px;
                    ">
                        <?php foreach ($allowedModels as $m): ?>
                            <?php $label = $m; ?>
                            <?php if ($m === 'gpt-5.2-chat-latest'): ?>
                                <?php $label = 'GPT-5.2 Chat'; ?>
                            <?php endif; ?>
                            <?php if ($m === 'gemini-2.5-flash-image' || $m === 'gemini-3-pro-image-preview'): ?>
                                <?php $label = $m . ' (Nano Banana)'; ?>
                            <?php endif; ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= $currentModel === $m ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <?php if ($hasMediaOrFiles): ?>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <?php if (!empty($currentPlan['allow_audio'])): ?>
                        <button type="button" id="btn-mic" style="
                            width: 30px;
                            height: 30px;
                            border-radius: 999px;
                            border: 1px solid var(--border-subtle);
                            background: var(--surface-subtle);
                            color: #e53935;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            cursor: pointer;
                            font-size: 16px;
                        " title="Gravar áudio">
                            🎙
                        </button>
                        <div id="audio-wave" style="
                            width: 40px;
                            height: 20px;
                            display: none;
                            align-items: flex-end;
                            gap: 3px;
                        ">
                            <span style="flex:1; background:#e53935; height: 20%; border-radius: 999px; animation: wave 0.6s infinite ease-in-out alternate;"></span>
                            <span style="flex:1; background:#ff6f60; height: 50%; border-radius: 999px; animation: wave 0.6s infinite ease-in-out alternate 0.2s;"></span>
                            <span style="flex:1; background:#e53935; height: 35%; border-radius: 999px; animation: wave 0.6s infinite ease-in-out alternate 0.4s;"></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($currentPlan['allow_images']) || !empty($currentPlan['allow_files'])): ?>
                        <label style="
                            width: 30px;
                            height: 30px;
                            border-radius: 999px;
                            border: 1px solid var(--border-subtle);
                            background: var(--surface-subtle);
                            color: var(--text-primary);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            cursor: pointer;
                            font-size: 16px;
                        " title="Enviar arquivo/imagem">
                            📎
                            <input id="file-input" type="file" name="attachments[]" multiple style="display:none;" accept="image/jpeg,image/png,image/webp,application/pdf,text/plain,text/markdown,text/csv">
                        </label>
                    <?php endif; ?>
                </div>

                <div id="file-list" style="max-width: 260px; font-size: 11px; color: var(--text-secondary); display:flex; flex-wrap:wrap; gap:4px;"></div>
                <?php endif; ?>
            </div>
            <textarea id="chat-message" name="message" rows="2" style="
                flex: 1;
                border: none;
                outline: none;
                resize: none;
                background: transparent;
                color: var(--text-primary);
                font-size: 14px;
                line-height: 1.4;
                padding: 0;
                margin: 0;
                box-sizing: border-box;
                overflow-y: hidden;
                max-height: 140px;
            " placeholder="Pergunte ao Tuquinha!"><?php if (!empty($draftMessage)) { echo htmlspecialchars($draftMessage); } ?></textarea>
            <button id="chat-send-btn" type="submit" style="
                border: none;
                border-radius: 999px;
                font-weight: 600;
                font-size: 13px;
                padding: 8px 14px;
                cursor: pointer;
                display: inline-flex;
            ">
                <span id="send-label">Enviar</span>
                <span>➤</span>
            </button>
        </div>
    </form>
</div>
<script>
    const CURRENT_CONVERSATION_ID = <?= isset($conversationId) ? (int)$conversationId : 0 ?>;
    const TUQ_CHAT_AVATAR_URL = <?= json_encode($tuqChatAvatarUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?> || '/public/perso_padrao.png';

    (function () {
        const renameBtn = document.getElementById('tuqChatRenameBtn');
        const renameForm = document.getElementById('tuqChatRenameForm');
        const renameTitle = document.getElementById('tuqChatRenameTitle');

        if (renameBtn && renameForm && renameTitle) {
            renameBtn.addEventListener('click', () => {
                const current = <?= json_encode($conversationTitleText, JSON_UNESCAPED_UNICODE) ?>;
                const next = window.prompt('Novo nome do chat:', current);
                if (next === null) {
                    return;
                }
                renameTitle.value = String(next).trim();
                if (renameTitle.value === '') {
                    renameTitle.value = 'Chat com o Tuquinha';
                }
                renameForm.submit();
            });
        }
    })();

    const chatWindow = document.getElementById('chat-window');
    if (chatWindow) {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    // Toggle painel de regras do chat
    const rulesToggle = document.getElementById('chat-rules-toggle');
    const rulesPanel = document.getElementById('chat-rules-panel');
    if (rulesToggle && rulesPanel) {
        rulesToggle.addEventListener('click', () => {
            const isOpen = rulesPanel.style.display === 'block';
            rulesPanel.style.display = isOpen ? 'none' : 'block';
        });
    }

    // Copiar conteúdo de mensagens (usuário e Tuquinha)
    document.addEventListener('click', (e) => {
        const btn = e.target && e.target.classList && e.target.classList.contains('copy-message-btn')
            ? e.target
            : (e.target && e.target.closest ? e.target.closest('.copy-message-btn') : null);
        if (!btn) return;

        const text = btn.getAttribute('data-message-text') || '';
        if (!text) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                const original = btn.dataset.originalLabel || btn.textContent;
                btn.dataset.originalLabel = original;
                btn.textContent = 'Copiado';
                btn.style.color = '#ffffff';

                setTimeout(() => {
                    btn.textContent = btn.dataset.originalLabel || 'Copiar';
                    btn.style.color = '#b0b0b0';
                }, 1500);
            }).catch(() => {
                alert('Não consegui copiar o texto. Tente novamente.');
            });
        } else {
            // Fallback simples
            alert('Seu navegador não suporta cópia automática. Selecione e copie manualmente.');
        }
    });

    const fileInput = document.getElementById('file-input');
    const fileList = document.getElementById('file-list');
    if (fileInput && fileList) {
        // torna acessível globalmente para limpeza após envio
        window.fileInput = fileInput;
        window.fileList = fileList;
        const renderFiles = () => {
            const files = Array.from(fileInput.files || []);
            fileList.innerHTML = '';

            if (!files.length) {
                return;
            }

            files.forEach((file, index) => {
                const chip = document.createElement('div');
                chip.style.display = 'inline-flex';
                chip.style.alignItems = 'center';
                chip.style.gap = '4px';
                chip.style.padding = '2px 6px';
                chip.style.borderRadius = '999px';
                chip.style.border = '1px solid #272727';
                chip.style.background = '#050509';

                const nameSpan = document.createElement('span');
                nameSpan.textContent = file.name;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.textContent = '×';
                removeBtn.style.border = 'none';
                removeBtn.style.background = 'transparent';
                removeBtn.style.color = '#ff6f60';
                removeBtn.style.cursor = 'pointer';
                removeBtn.style.fontSize = '11px';

                removeBtn.addEventListener('click', () => {
                    const dt = new DataTransfer();
                    files.forEach((f, i) => {
                        if (i !== index) {
                            dt.items.add(f);
                        }
                    });
                    fileInput.files = dt.files;
                    renderFiles();
                });

                chip.appendChild(nameSpan);
                chip.appendChild(removeBtn);
                fileList.appendChild(chip);
            });
        };

        fileInput.addEventListener('change', renderFiles);
    }

    // Colar/arrastar imagens para anexar automaticamente
    (function () {
        const messageInputEl = document.getElementById('chat-message');
        const inputBarEl = document.getElementById('chat-input-bar');
        if (!fileInput || !fileList || !messageInputEl) {
            return;
        }

        const addFilesToInput = (newFiles) => {
            if (!newFiles || !newFiles.length) return;
            const dt = new DataTransfer();
            const existing = Array.from(fileInput.files || []);
            existing.forEach((f) => dt.items.add(f));
            newFiles.forEach((f) => dt.items.add(f));
            fileInput.files = dt.files;
            try {
                fileInput.dispatchEvent(new Event('change'));
            } catch (e) {
                // fallback
                try { fileList && fileList.innerHTML !== undefined; } catch (e2) {}
            }
        };

        messageInputEl.addEventListener('paste', (e) => {
            try {
                const items = (e.clipboardData && e.clipboardData.items) ? Array.from(e.clipboardData.items) : [];
                const files = [];
                items.forEach((it) => {
                    if (!it) return;
                    if (it.kind === 'file') {
                        const f = it.getAsFile ? it.getAsFile() : null;
                        if (f) files.push(f);
                    }
                });
                if (files.length) {
                    addFilesToInput(files);
                }
            } catch (err) {}
        });

        const handleDropFiles = (fileListLike) => {
            const dropped = Array.from(fileListLike || []);
            if (!dropped.length) return;
            addFilesToInput(dropped);
        };

        const bindDropZone = (el) => {
            if (!el) return;
            if (el.dataset.dropBound) return;
            el.dataset.dropBound = '1';
            el.addEventListener('dragover', (e) => {
                e.preventDefault();
            });
            el.addEventListener('drop', (e) => {
                e.preventDefault();
                try {
                    const files = e.dataTransfer ? e.dataTransfer.files : null;
                    if (files) handleDropFiles(files);
                } catch (err) {}
            });
        };

        bindDropZone(messageInputEl);
        bindDropZone(inputBarEl);
    })();

    let mediaRecorder = null;
    let audioChunks = [];
    let isRecordingAudio = false;
    const btnMic = document.getElementById('btn-mic');
    const wave = document.getElementById('audio-wave');

    if (btnMic && wave) {
        btnMic.addEventListener('click', async () => {
            if (isRecordingAudio) {
                // Já está gravando: parar
                if (mediaRecorder && mediaRecorder.state === 'recording') {
                    mediaRecorder.stop();
                }
                return;
            }

            if (!mediaRecorder) {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);

                    mediaRecorder.ondataavailable = (e) => {
                        if (e.data.size > 0) {
                            audioChunks.push(e.data);
                        }
                    };

                    mediaRecorder.onstop = () => {
                        wave.style.display = 'none';
                        btnMic.textContent = '🎙';
                        const blob = new Blob(audioChunks, { type: 'audio/webm' });
                        audioChunks = [];

                        const formData = new FormData();
                        formData.append('audio', blob, 'gravacao.webm');

                        const messageEl = document.getElementById('chat-message');
                        const formEl = messageEl ? messageEl.closest('form') : null;
                        const submitBtnEl = formEl ? formEl.querySelector('button[type="submit"]') : null;

                        if (messageEl) {
                            messageEl.disabled = true;
                            messageEl.placeholder = 'Transcrevendo áudio...';
                        }
                        if (submitBtnEl) {
                            submitBtnEl.disabled = true;
                        }
                        btnMic.disabled = true;

                        fetch('/chat/audio', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData
                        })
                            .then((res) => res.json().catch(() => null))
                            .then((data) => {
                                if (!data || !data.success) {
                                    const err = data && data.error ? data.error : 'Não consegui transcrever o áudio. Tente novamente.';
                                    alert(err);
                                    if (messageEl) {
                                        messageEl.disabled = false;
                                        messageEl.placeholder = 'Pergunte ao Tuquinha!';
                                    }
                                    if (submitBtnEl) {
                                        submitBtnEl.disabled = false;
                                    }
                                    btnMic.disabled = false;
                                    return;
                                }

                                if (messageEl && typeof data.text === 'string') {
                                    messageEl.value = data.text;
                                    messageEl.disabled = false;
                                    messageEl.placeholder = 'Mensagem transcrita. Você pode revisar e enviar.';
                                    const event = new Event('input');
                                    messageEl.dispatchEvent(event);
                                }
                                if (submitBtnEl) {
                                    submitBtnEl.disabled = false;
                                }
                                btnMic.disabled = false;
                            })
                            .catch(() => {
                                alert('Erro ao enviar o áudio para transcrição. Tente novamente.');
                                if (messageEl) {
                                    messageEl.disabled = false;
                                    messageEl.placeholder = 'Pergunte ao Tuquinha!';
                                }
                                if (submitBtnEl) {
                                    submitBtnEl.disabled = false;
                                }
                                btnMic.disabled = false;
                            })
                            .finally(() => {
                                isRecordingAudio = false;
                            });
                    };
                } catch (e) {
                    alert('Não consegui acessar o microfone. Verifique as permissões do navegador.');
                    return;
                }
            }

            if (mediaRecorder.state === 'inactive') {
                const messageEl = document.getElementById('chat-message');
                const formEl = messageEl ? messageEl.closest('form') : null;
                const submitBtnEl = formEl ? formEl.querySelector('button[type="submit"]') : null;

                if (messageEl) {
                    messageEl.disabled = true;
                    messageEl.placeholder = 'Gravando áudio...';
                }
                if (submitBtnEl) {
                    submitBtnEl.disabled = true;
                }

                audioChunks = [];
                mediaRecorder.start();
                wave.style.display = 'flex';
                btnMic.textContent = '⏹';
                isRecordingAudio = true;
            }
        });
    }

    const messageInput = document.getElementById('chat-message');
    const chatForm = messageInput ? messageInput.closest('form') : null;

    if (messageInput && chatForm) {
        const STORAGE_KEY = 'tuquinha_chat_draft';
        let isSending = false;
        let activeAbortController = null;
        let activeTypingEl = null;

        // Se não veio draft do servidor (ex: áudio), tenta restaurar do localStorage
        <?php if (empty($draftMessage)): ?>
        try {
            const stored = window.localStorage.getItem(STORAGE_KEY);
            if (stored) {
                messageInput.value = stored;
            }
        } catch (e) {}
        <?php endif; ?>

        const autoResize = () => {
            const maxHeight = 140; // mesmo valor do max-height
            messageInput.style.height = '0px';
            const scrollH = messageInput.scrollHeight || 0;
            const newHeight = Math.min(scrollH, maxHeight);
            messageInput.style.height = newHeight + 'px';
            messageInput.style.overflowY = scrollH > maxHeight ? 'auto' : 'hidden';
        };

        autoResize();

        messageInput.addEventListener('input', autoResize);

        messageInput.addEventListener('input', () => {
            try {
                window.localStorage.setItem(STORAGE_KEY, messageInput.value);
            } catch (e) {}
        });

        const submitButton = chatForm.querySelector('button[type="submit"]');
        const inputBar = document.getElementById('chat-input-bar');

        if (inputBar) {
            inputBar.addEventListener('click', (e) => {
                const tag = (e.target && e.target.tagName ? e.target.tagName.toLowerCase() : '');
                if (tag === 'textarea' || tag === 'button' || tag === 'select' || tag === 'input' || tag === 'label') {
                    return;
                }
                messageInput.focus();
            });
        }

        const renderMarkdownSafeJs = (text) => {
            // Alguns modelos retornam quebras de linha como texto literal "\\n".
            // Normaliza isso antes de escapar HTML para evitar que "\\n" apareça na UI.
            text = (text || '').toString().replace(/\\r\\n|\\n|\\r/g, '\n');

            const escapeHtml = (s) => s
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            let out = escapeHtml(text || '');
            // ### títulos -> <strong>
            out = out.replace(/^#{3,6}\s*(.+)$/gm, '<strong>$1</strong>');
            // **negrito** -> <strong>
            out = out.replace(/\*\*([\s\S]+?)\*\*/g, '<strong>$1</strong>');
            out = out.replace(/(^|[^*])\*([^*\n][^*]*?)\*(?!\*)/g, '$1<em>$2</em>');
            // "- " no início da linha -> bullet visual
            out = out.replace(/^\-\s+/gm, '• ');
            out = out.replace(/\r\n|\r/g, '\n');

            // Linha separadora estilo ChatGPT
            out = out.replace(/^\s*---+\s*$/gm, '[[HR]]');
            out = out.replace(/^\s*\[\[HR\]\]\s*$/gm, '[[HR]]');

            // Garante que [[HR]] fique isolado como um bloco (para virar <hr>)
            out = ('\n' + out + '\n').replace(/\n\s*\[\[HR\]\]\s*\n/g, '\n\n[[HR]]\n\n').trim();

            // Cria respiros automáticos antes de títulos/listas
            out = out.replace(/\n(?=(?:\d+\.|•)\s)/g, '\n\n');
            out = out.replace(/\n(?=<strong>)/g, '\n\n');

            // Imagens markdown: ![alt](url) (apenas http/https)
            out = out.replace(/!\[([^\]\n]*)\]\(([^)\s]+)\)/g, (full, alt, urlEscaped) => {
                const urlRaw = (urlEscaped || '').toString().trim();
                const isHttp = urlRaw.toLowerCase().startsWith('http://') || urlRaw.toLowerCase().startsWith('https://');
                if (!isHttp) return full;
                const src = escapeHtml(urlRaw);
                const altSafe = escapeHtml((alt || '').toString());
                return '<a href="' + src + '" target="_blank" rel="noopener noreferrer" style="display:block; text-decoration:none;">'
                    + '<img src="' + src + '" alt="' + altSafe + '" style="display:block; width:100%; max-width:520px; border-radius:12px; border:1px solid var(--border-subtle);">'
                    + '</a>';
            });

            // Links markdown: [texto](url)
            // Segurança: só permite URLs http(s) e links relativos iniciando com '/'
            out = out.replace(/\[([^\]\n]+)\]\(([^)\s]+)\)/g, (full, label, urlEscaped) => {
                const urlRaw = (urlEscaped || '').toString().trim();
                if (!urlRaw) return full;
                const lower = urlRaw.toLowerCase();
                const isHttp = lower.startsWith('http://') || lower.startsWith('https://');
                const isRel = urlRaw.startsWith('/');
                if (!isHttp && !isRel) return full;
                const href = escapeHtml(urlRaw);
                if (isHttp) {
                    return '<a href="' + href + '" target="_blank" rel="noopener noreferrer">' + label + '</a>';
                }
                return '<a href="' + href + '">' + label + '</a>';
            });

            // Blocos separados por linha em branco viram parágrafos
            const blocks = out.split(/\n{2,}/g).map((b) => (b || '').trim()).filter(Boolean);
            const html = blocks.map((b) => {
                if (b === '[[HR]]') {
                    return '<hr class="tuq-chat-hr">';
                }
                return '<p>' + b.replace(/\n/g, '<br>') + '</p>';
            }).join('');
            return '<div class="tuq-chat-md">' + html + '</div>';
        };

        const appendMessageToDom = (role, content, attachmentsOrMeta) => {
            if (!chatWindow) return;

            const emptyState = document.getElementById('chat-empty-state');
            if (emptyState) {
                emptyState.remove();
            }

            const wrapper = document.createElement('div');
            const text = (content || '').toString().trim();

            if (role === 'attachment_summary') {
                // Bloco especial só para exibir cards de anexos
                wrapper.style.display = 'flex';
                wrapper.style.justifyContent = 'flex-end';
                wrapper.style.marginBottom = '10px';

                const container = document.createElement('div');
                container.style.maxWidth = '80%';
                container.style.display = 'flex';
                container.style.flexWrap = 'wrap';
                container.style.gap = '6px';

                const attachments = attachmentsOrMeta;
                if (Array.isArray(attachments)) {
                    attachments.forEach((att) => {
                        const card = document.createElement('div');
                        card.style.display = 'flex';
                        card.style.flexDirection = 'column';
                        card.style.padding = '6px 10px';
                        card.style.borderRadius = '12px';
                        card.style.background = att.is_image ? '#152028' : '#181820';
                        card.style.border = '1px solid #272727';
                        card.style.minWidth = '160px';
                        card.style.maxWidth = '220px';

                        const titleRow = document.createElement('div');
                        titleRow.style.display = 'flex';
                        titleRow.style.alignItems = 'center';
                        titleRow.style.gap = '6px';
                        titleRow.style.marginBottom = '2px';

                        const icon = document.createElement('span');
                        icon.textContent = att.is_image ? '🖼️' : (att.is_csv ? '📊' : (att.is_pdf ? '📄' : '📎'));
                        icon.style.fontSize = '14px';

                        const name = document.createElement('span');
                        name.textContent = att.name || 'arquivo';
                        name.style.fontSize = '12px';
                        name.style.fontWeight = '600';
                        name.style.whiteSpace = 'nowrap';
                        name.style.overflow = 'hidden';
                        name.style.textOverflow = 'ellipsis';

                        titleRow.appendChild(icon);
                        titleRow.appendChild(name);

                        const meta = document.createElement('div');
                        meta.style.fontSize = '11px';
                        meta.style.color = '#b0b0b0';
                        const sizeLabel = typeof att.size_human === 'string' ? att.size_human : '';
                        const typeLabel = att.label || '';
                        meta.textContent = [typeLabel, sizeLabel].filter(Boolean).join(' · ');

                        card.appendChild(titleRow);
                        card.appendChild(meta);
                        container.appendChild(card);
                    });
                }

                wrapper.appendChild(container);
            } else if (role === 'user') {
                const meta = attachmentsOrMeta || {};
                const createdLabel = typeof meta.created_label === 'string' ? meta.created_label : '';

                wrapper.style.display = 'flex';
                wrapper.style.flexDirection = 'column';
                wrapper.style.alignItems = 'flex-end';
                wrapper.style.marginBottom = '10px';

                const bubble = document.createElement('div');
                bubble.style.maxWidth = '80%';
                bubble.style.background = 'var(--surface-card)';
                bubble.style.borderRadius = '16px 16px 4px 16px';
                bubble.style.padding = '9px 12px';
                bubble.style.fontSize = '14px';
                bubble.style.whiteSpace = 'normal';
                bubble.style.wordWrap = 'break-word';
                bubble.style.border = '1px solid var(--border-subtle)';
                bubble.style.color = 'var(--text-primary)';
                bubble.innerHTML = renderMarkdownSafeJs(text);

                wrapper.appendChild(bubble);

                const metaRow = document.createElement('div');
                metaRow.style.marginTop = '2px';
                metaRow.style.display = 'flex';
                metaRow.style.alignItems = 'center';
                metaRow.style.gap = '6px';
                metaRow.style.fontSize = '10px';
                metaRow.style.color = '#777';
                metaRow.style.maxWidth = '80%';
                metaRow.style.justifyContent = 'flex-end';

                if (createdLabel) {
                    const spanDate = document.createElement('span');
                    spanDate.textContent = createdLabel;
                    metaRow.appendChild(spanDate);
                }

                const copyBtn = document.createElement('button');
                copyBtn.type = 'button';
                copyBtn.className = 'copy-message-btn';
                copyBtn.dataset.messageText = text;
                copyBtn.textContent = 'Copiar';
                copyBtn.style.border = 'none';
                copyBtn.style.background = 'transparent';
                copyBtn.style.color = '#b0b0b0';
                copyBtn.style.fontSize = '10px';
                copyBtn.style.cursor = 'pointer';
                copyBtn.style.padding = '0';

                metaRow.appendChild(copyBtn);
                wrapper.appendChild(metaRow);
            } else {
                wrapper.style.display = 'flex';
                wrapper.style.flexDirection = 'column';
                wrapper.style.alignItems = 'flex-start';
                wrapper.style.marginBottom = '10px';

                const meta = attachmentsOrMeta || {};
                const tokensUsed = typeof meta.tokens_used === 'number' ? meta.tokens_used : 0;
                const createdLabel = typeof meta.created_label === 'string' ? meta.created_label : '';

                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.alignItems = 'flex-start';
                row.style.gap = '8px';

                const avatar = document.createElement('div');
                avatar.style.width = '28px';
                avatar.style.height = '28px';
                avatar.style.borderRadius = '50%';
                avatar.style.overflow = 'hidden';
                avatar.style.flexShrink = '0';
                avatar.style.background = 'var(--surface-subtle)';

                var logoImg = document.createElement('img');
                logoImg.src = TUQ_CHAT_AVATAR_URL;
                logoImg.alt = 'Tuquinha';
                logoImg.style.width = '100%';
                logoImg.style.height = '100%';
                logoImg.style.display = 'block';
                logoImg.style.objectFit = 'cover';
                logoImg.onerror = function () { this.onerror = null; this.src = '/public/favicon.png'; };

                avatar.appendChild(logoImg);

                const bubble = document.createElement('div');
                bubble.style.maxWidth = '80%';
                bubble.style.background = 'var(--surface-card)';
                bubble.style.borderRadius = '16px 16px 16px 4px';
                bubble.style.padding = '9px 12px';
                bubble.style.fontSize = '14px';
                bubble.style.whiteSpace = 'normal';
                bubble.style.wordWrap = 'break-word';
                bubble.style.border = '1px solid var(--border-subtle)';
                bubble.style.color = 'var(--text-primary)';
                bubble.innerHTML = renderMarkdownSafeJs(text);

                row.appendChild(avatar);
                row.appendChild(bubble);
                wrapper.appendChild(row);

                // Linha de meta: tokens usados + horário + botão copiar
                const metaRow = document.createElement('div');
                // deixa um pequeno respiro abaixo da bolha, alinhado com o avatar
                metaRow.style.margin = '4px 0 6px 36px';
                metaRow.style.display = 'flex';
                metaRow.style.alignItems = 'center';
                metaRow.style.gap = '6px';
                metaRow.style.fontSize = '10px';
                metaRow.style.color = '#777';
                metaRow.style.maxWidth = '80%';

                if (tokensUsed > 0) {
                    const spanTokens = document.createElement('span');
                    spanTokens.textContent = tokensUsed + ' tokens';
                    metaRow.appendChild(spanTokens);
                }

                if (createdLabel) {
                    const spanDate = document.createElement('span');
                    spanDate.textContent = (tokensUsed > 0 ? ' · ' : '') + createdLabel;
                    metaRow.appendChild(spanDate);
                }

                const copyBtn = document.createElement('button');
                copyBtn.type = 'button';
                copyBtn.className = 'copy-message-btn';
                copyBtn.dataset.messageText = text;
                copyBtn.textContent = 'Copiar';
                copyBtn.style.border = 'none';
                copyBtn.style.background = 'transparent';
                copyBtn.style.color = '#b0b0b0';
                copyBtn.style.fontSize = '10px';
                copyBtn.style.cursor = 'pointer';
                copyBtn.style.padding = '0';

                metaRow.appendChild(copyBtn);

                wrapper.appendChild(metaRow);
            }

            chatWindow.appendChild(wrapper);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        };

        const appendTypingIndicator = () => {
            if (!chatWindow) return null;

            const wrapper = document.createElement('div');
            wrapper.dataset.typingIndicator = '1';
            wrapper.style.display = 'flex';
            wrapper.style.flexDirection = 'column';
            wrapper.style.alignItems = 'flex-start';
            wrapper.style.marginBottom = '10px';

            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.alignItems = 'flex-start';
            row.style.gap = '8px';

            const avatar = document.createElement('div');
            avatar.style.width = '28px';
            avatar.style.height = '28px';
            avatar.style.borderRadius = '50%';
            avatar.style.overflow = 'hidden';
            avatar.style.flexShrink = '0';
            avatar.style.background = 'var(--surface-subtle)';

            const logoImg = document.createElement('img');
            logoImg.src = TUQ_CHAT_AVATAR_URL;
            logoImg.alt = 'Tuquinha';
            logoImg.style.width = '100%';
            logoImg.style.height = '100%';
            logoImg.style.display = 'block';
            logoImg.style.objectFit = 'cover';
            logoImg.onerror = function () { this.onerror = null; this.src = '/public/favicon.png'; };
            avatar.appendChild(logoImg);

            const bubble = document.createElement('div');
            bubble.style.maxWidth = '80%';
            bubble.style.background = 'var(--surface-card)';
            bubble.style.borderRadius = '16px 16px 16px 4px';
            bubble.style.padding = '10px 12px';
            bubble.style.border = '1px solid var(--border-subtle)';
            bubble.style.color = 'var(--text-primary)';

            const dots = document.createElement('div');
            dots.style.display = 'flex';
            dots.style.alignItems = 'center';
            dots.style.gap = '6px';
            dots.style.height = '16px';

            const makeDot = (delay) => {
                const d = document.createElement('span');
                d.style.width = '6px';
                d.style.height = '6px';
                d.style.borderRadius = '50%';
                d.style.background = 'rgba(255,255,255,0.8)';
                d.style.display = 'inline-block';
                d.style.animation = 'tuqDot 0.9s infinite ease-in-out';
                d.style.animationDelay = delay;
                return d;
            };

            dots.appendChild(makeDot('0s'));
            dots.appendChild(makeDot('0.12s'));
            dots.appendChild(makeDot('0.24s'));

            bubble.appendChild(dots);
            row.appendChild(avatar);
            row.appendChild(bubble);
            wrapper.appendChild(row);
            chatWindow.appendChild(wrapper);
            chatWindow.scrollTop = chatWindow.scrollHeight;
            return wrapper;
        };

        let lastErrorMessage = '';
        let lastTokensUsed = 0;

        const showErrorReportBox = (message, debugInfo) => {
            lastErrorMessage = message || '';
            if (debugInfo) {
                lastErrorMessage += "\n\n[DEBUG]\n" + debugInfo;
            }
            const box = document.getElementById('chat-error-report');
            const textEl = document.getElementById('chat-error-text');
            const formEl = document.getElementById('chat-error-report-form');
            const feedbackEl = document.getElementById('chat-error-report-feedback');
            const commentEl = document.getElementById('error-report-comment');
            if (!box || !textEl || !formEl || !feedbackEl || !commentEl) return;

            textEl.textContent = message;
            box.style.display = 'block';
            formEl.style.display = 'none';
            feedbackEl.style.display = 'none';
            feedbackEl.textContent = '';
            commentEl.value = '';

            // Reseta estado do fluxo de reporte (caso já tenha sido enviado antes)
            try {
                const errorActions = document.getElementById('chat-error-actions');
                const btnOpenReport = document.getElementById('btn-open-error-report');
                const btnSendReport = document.getElementById('btn-send-error-report');
                if (errorActions) {
                    errorActions.style.display = '';
                }
                if (btnOpenReport) {
                    btnOpenReport.disabled = false;
                }
                if (btnSendReport) {
                    btnSendReport.disabled = false;
                    btnSendReport.textContent = 'Enviar relato';
                    delete btnSendReport.dataset.sentOnce;
                }
            } catch (e) {}
        };

        const sendViaAjax = () => {
            if (isSending) {
                return;
            }
            const text = messageInput.value.trim();
            if (!text) {
                return;
            }

            // Captura anexos selecionados para renderizar imediatamente na UI
            const selectedFiles = (window.fileInput && window.fileInput.files)
                ? Array.from(window.fileInput.files)
                : [];

            const sizeHuman = (bytes) => {
                const b = Number(bytes || 0);
                if (!b || b <= 0) return '';
                if (b < 1024) return b + ' B';
                const kb = b / 1024;
                if (kb < 1024) return kb.toFixed(1) + ' KB';
                const mb = kb / 1024;
                if (mb < 1024) return mb.toFixed(1) + ' MB';
                const gb = mb / 1024;
                return gb.toFixed(1) + ' GB';
            };

            const optimisticAttachments = selectedFiles.map((f) => {
                const name = (f && f.name) ? String(f.name) : 'arquivo';
                const type = (f && f.type) ? String(f.type) : '';
                const lower = name.toLowerCase();
                const isPdf = (type === 'application/pdf') || lower.endsWith('.pdf');
                const isCsv = (type === 'text/csv') || lower.endsWith('.csv');
                const isImage = (type.indexOf('image/') === 0) || /\.(png|jpe?g|webp|gif|bmp)$/i.test(lower);
                let label = '';
                if (isImage) label = 'Imagem';
                else if (isPdf) label = 'PDF';
                else if (isCsv) label = 'CSV';
                else label = 'Arquivo';
                return {
                    name,
                    label,
                    size_human: sizeHuman(f && f.size ? f.size : 0),
                    is_image: isImage,
                    is_pdf: isPdf,
                    is_csv: isCsv,
                };
            });

            const formData = new FormData(chatForm);

            // Limpa imediatamente os arquivos selecionados (o FormData já capturou o estado atual)
            if (window.fileInput && window.fileList) {
                try {
                    window.fileInput.value = '';
                } catch (e) {}
                window.fileList.innerHTML = '';
            }

            isSending = true;
            activeAbortController = new AbortController();

            appendMessageToDom('user', text, { created_label: '' });

            // Mostra os anexos logo após a mensagem do usuário, enquanto aguarda a resposta
            const hasOptimisticAttachments = Array.isArray(optimisticAttachments) && optimisticAttachments.length > 0;
            if (hasOptimisticAttachments) {
                appendMessageToDom('attachment_summary', '', optimisticAttachments);
            }
            activeTypingEl = appendTypingIndicator();

            // limpa o input imediatamente (a mensagem já foi adicionada no chat)
            messageInput.value = '';
            autoResize();
            try {
                window.localStorage.removeItem(STORAGE_KEY);
            } catch (e) {}

            // bloqueia edição enquanto envia
            messageInput.disabled = true;

            if (submitButton) {
                const sendLabel = document.getElementById('send-label');
                if (sendLabel) {
                    sendLabel.dataset.original = sendLabel.dataset.original || sendLabel.textContent;
                    sendLabel.textContent = 'Parar';
                }
            }

            let lastStatus = 0;

            fetch('/chat/send', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                signal: activeAbortController.signal,
            })
                .then((res) => {
                    lastStatus = res.status || 0;
                    return res.json().catch(() => null);
                })
                .then((data) => {
                    if (!data || !data.success) {
                        const err = data && data.error ? data.error : 'Não foi possível enviar a mensagem. Tente novamente.';
                        const debug = 'status=' + String(lastStatus || 0) + '; payload=' + JSON.stringify(data || {});
                        showErrorReportBox(err, debug);
                        return;
                    }

                    if (typeof data.total_tokens_used === 'number') {
                        lastTokensUsed = data.total_tokens_used;
                    } else {
                        lastTokensUsed = 0;
                    }

                    try {
                        window.localStorage.removeItem(STORAGE_KEY);
                    } catch (e) {}

                    messageInput.value = '';
                    autoResize();

                    // Limpa arquivos selecionados e lista visual
                    if (window.fileInput && window.fileList) {
                        try {
                            window.fileInput.value = '';
                        } catch (e) {}
                        window.fileList.innerHTML = '';
                    }

                    if (Array.isArray(data.messages)) {
                        let skippedUserOnce = false;
                        data.messages.forEach((m) => {
                            if (!skippedUserOnce && m.role === 'user' && (m.content || '').toString().trim() === text) {
                                skippedUserOnce = true;
                                return;
                            }
                            if (hasOptimisticAttachments && m.role === 'attachment_summary') {
                                return;
                            }
                            const thirdArg = m.role === 'attachment_summary'
                                ? (m.attachments || [])
                                : m;
                            appendMessageToDom(m.role, m.content, thirdArg);
                        });
                    }
                })
                .catch((e) => {
                    if (e && (e.name === 'AbortError' || e.code === 20)) {
                        return;
                    }
                    const debug = 'fetch_error=' + (e && e.message ? e.message : 'unknown');
                    showErrorReportBox('Erro ao enviar mensagem. Verifique sua conexão e tente novamente.', debug);
                })
                .finally(() => {
                    if (activeTypingEl && activeTypingEl.parentNode) {
                        activeTypingEl.parentNode.removeChild(activeTypingEl);
                    } else {
                        const el = chatWindow ? chatWindow.querySelector('[data-typing-indicator="1"]') : null;
                        if (el && el.parentNode) {
                            el.parentNode.removeChild(el);
                        }
                    }
                    isSending = false;
                    activeAbortController = null;
                    activeTypingEl = null;
                    messageInput.disabled = false;
                    if (submitButton) {
                        const sendLabel = document.getElementById('send-label');
                        if (sendLabel && sendLabel.dataset.original) {
                            sendLabel.textContent = sendLabel.dataset.original;
                        }
                    }
                });
        };

        if (submitButton) {
            submitButton.addEventListener('click', (e) => {
                if (!isSending) {
                    return;
                }
                e.preventDefault();
                if (activeAbortController) {
                    try {
                        activeAbortController.abort();
                    } catch (err) {}
                }
            });
        }

        messageInput.addEventListener('keydown', (e) => {
            const dropdownOpen = !!(document.getElementById('file-mention-dropdown') && document.getElementById('file-mention-dropdown').style.display === 'block');
            if (e.key === 'Enter' && !e.shiftKey && !dropdownOpen) {
                e.preventDefault();
                sendViaAjax();
            }
        });

        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            sendViaAjax();
        });

        // Autosend: usado quando o usuário inicia o chat vindo de Projetos.
        // Ex: /chat?c=123&autosend=1 (texto vem do draftMessage do servidor)
        try {
            const params = new URLSearchParams(window.location.search || '');
            if (params.get('autosend') === '1') {
                window.setTimeout(() => {
                    if (!messageInput || !messageInput.value || messageInput.value.trim() === '') {
                        return;
                    }
                    sendViaAjax();

                    // Evita reenvio se o usuário der refresh
                    try {
                        params.delete('autosend');
                        const qs = params.toString();
                        const newUrl = window.location.pathname + (qs ? ('?' + qs) : '');
                        window.history.replaceState({}, '', newUrl);
                    } catch (e2) {}
                }, 250);
            }
        } catch (e) {}

        const errorBox = document.getElementById('chat-error-report');
        const btnOpenReport = document.getElementById('btn-open-error-report');
        const btnCloseReport = document.getElementById('btn-close-error-report');
        const btnCancelReport = document.getElementById('btn-cancel-error-report');
        const btnSendReport = document.getElementById('btn-send-error-report');
        const formReport = document.getElementById('chat-error-report-form');
        const feedbackEl = document.getElementById('chat-error-report-feedback');
        const commentEl = document.getElementById('error-report-comment');
        const errorActions = document.getElementById('chat-error-actions');

        if (errorBox && btnOpenReport && btnCloseReport && btnCancelReport && btnSendReport && formReport && feedbackEl && commentEl && errorActions) {
            btnOpenReport.addEventListener('click', () => {
                formReport.style.display = 'flex';
                feedbackEl.style.display = 'none';
                feedbackEl.textContent = '';
                commentEl.focus();
            });

            const closeBox = () => {
                errorBox.style.display = 'none';
                formReport.style.display = 'none';
                feedbackEl.style.display = 'none';
                feedbackEl.textContent = '';
                commentEl.value = '';

                // Restaura ações para o próximo erro
                try {
                    errorActions.style.display = '';
                    btnOpenReport.disabled = false;
                    btnSendReport.disabled = false;
                    btnSendReport.textContent = 'Enviar relato';
                    delete btnSendReport.dataset.sentOnce;
                } catch (e) {}
            };

            btnCloseReport.addEventListener('click', closeBox);
            btnCancelReport.addEventListener('click', closeBox);

            btnSendReport.addEventListener('click', () => {
                const payload = new FormData();
                payload.append('conversation_id', CURRENT_CONVERSATION_ID > 0 ? String(CURRENT_CONVERSATION_ID) : '');
                payload.append('message_id', '');
                payload.append('tokens_used', String(lastTokensUsed || 0));
                payload.append('error_message', lastErrorMessage || '');
                payload.append('user_comment', commentEl.value || '');

                btnSendReport.disabled = true;
                btnSendReport.textContent = 'Enviando...';

                fetch('/erro/reportar', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload,
                })
                    .then((res) => res.json().catch(() => null))
                    .then((data) => {
                        const ok = data && data.success;
                        const msg = (data && data.message) ? data.message : (ok ? 'Seu relato foi enviado para a equipe analisar.' : 'Não consegui enviar o relato agora. Tente novamente em alguns minutos.');

                        feedbackEl.textContent = msg;
                        feedbackEl.style.display = 'block';

                        if (ok) {
                            commentEl.value = '';
                            formReport.style.display = 'none';
                            errorActions.style.display = 'none';
                            btnSendReport.disabled = true;
                            btnOpenReport.disabled = true;
                            btnSendReport.dataset.sentOnce = '1';

                            // Após confirmar, fecha automaticamente para não ficar "preso" na tela
                            window.setTimeout(() => {
                                try { closeBox(); } catch (e) {}
                            }, 2200);
                        }
                    })
                    .catch(() => {
                        feedbackEl.textContent = 'Não consegui enviar o relato agora. Tente novamente em alguns minutos.';
                        feedbackEl.style.display = 'block';
                    })
                    .finally(() => {
                        if (!btnSendReport.dataset.sentOnce) {
                            btnSendReport.disabled = false;
                            btnSendReport.textContent = 'Enviar relato';
                        }
                    });
            });
        }
    }
</script>
<style>
@keyframes wave {
    from { transform: scaleY(0.6); opacity: 0.7; }
    to { transform: scaleY(1.4); opacity: 1; }
}

/* Scrollbar customizado para a área de chat */
#chat-window::-webkit-scrollbar {
    width: 8px;
}

#chat-window::-webkit-scrollbar-track {
    background: transparent;
}

#chat-window::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 999px;
}

#chat-window::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.25);
}

/* Scrollbar customizado para o campo de digitação */
#chat-message::-webkit-scrollbar {
    width: 8px;
}

#chat-message::-webkit-scrollbar-track {
    background: transparent;
}

#chat-message::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 999px;
}

#chat-message::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.28);
}
</style>

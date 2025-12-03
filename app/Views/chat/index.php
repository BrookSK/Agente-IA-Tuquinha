<?php
/** @var array $chatHistory */
/** @var array $allowedModels */
/** @var string|null $currentModel */
/** @var array|null $currentPlan */
/** @var string|null $draftMessage */
/** @var string|null $audioError */
/** @var array $attachments */

$hasMediaOrFiles = !empty($currentPlan['allow_audio']) || !empty($currentPlan['allow_images']) || !empty($currentPlan['allow_files']);
$isFreePlan = $currentPlan && (($currentPlan['slug'] ?? '') === 'free');
$freeChatLimit = (int)\App\Models\Setting::get('free_memory_chat_chars', '400');
if ($freeChatLimit <= 0) { $freeChatLimit = 400; }

function render_markdown_safe(string $text): string {
    // Escapa HTML primeiro
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // ### Título -> <strong>...</strong>
    $escaped = preg_replace('/^#{3,6}\s*(.+)$/m', '<strong>$1</strong>', $escaped);

    // **negrito** -> <strong>negrito</strong>
    $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);

    // "- texto" no começo da linha vira bullet visual
    $escaped = preg_replace('/^\-\s+/m', '• ', $escaped);

    // Quebras de linha para <br>
    return nl2br($escaped);
}
?>
<style>
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
}
</style>
<?php
$convSettings = $conversationSettings ?? null;
$canUseConversationSettings = !empty($canUseConversationSettings);
?>
<div style="max-width: 900px; width: 100%; margin: 0 auto; padding: 0 8px; display: flex; flex-direction: column; min-height: calc(100vh - 56px - 80px); box-sizing: border-box;">
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
    <div id="chat-window" style="flex: 1; overflow-y: auto; padding: 12px 4px 12px 0;">
        <?php if (empty($chatHistory)): ?>
            <div id="chat-empty-state" style="text-align: center; margin-top: 40px; color: #b0b0b0; font-size: 14px;">
                <div style="font-size: 18px; margin-bottom: 6px;">Bora começar esse papo? ✨</div>
                <div>Me conta rapidinho: em que fase você tá com seus projetos de marca?</div>
            </div>
        <?php else: ?>
            <?php if (!empty($attachments)): ?>
                <div style="margin-bottom:8px; display:flex; justify-content:flex-end;">
                    <div style="
                        max-width: 80%;
                        display: flex;
                        flex-wrap: wrap;
                        gap: 6px;
                    ">
                        <?php foreach ($attachments as $att): ?>
                            <?php
                            // não exibe anexos de áudio no histórico (já foram transcritos)
                            if (($att['type'] ?? '') === 'audio') {
                                continue;
                            }
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
                            ?>
                            <div style="
                                display:flex;
                                flex-direction:column;
                                padding:6px 10px;
                                border-radius:12px;
                                background: <?= $isImage ? '\'#152028\'' : '\'#181820\'' ?>;
                                border:1px solid #272727;
                                min-width:160px;
                                max-width:220px;
                            ">
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                    <span style="font-size:14px;">
                                        <?= $isImage ? '🖼️' : ($isCsv ? '📊' : ($isPdf ? '📄' : '📎')) ?>
                                    </span>
                                    <span style="font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars((string)($att['original_name'] ?? 'arquivo')) ?>
                                    </span>
                                </div>
                                <div style="font-size:11px; color:#b0b0b0;">
                                    <?= htmlspecialchars(trim($label . ($humanSize ? ' · ' . $humanSize : ''))) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php foreach ($chatHistory as $message): ?>
                <?php
                $createdAt = isset($message['created_at']) ? strtotime((string)$message['created_at']) : null;
                $createdLabel = $createdAt ? date('d/m/Y H:i', $createdAt) : '';
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
                            background: #1e1e24;
                            border-radius: 16px 16px 4px 16px;
                            padding: 9px 12px;
                            font-size: 14px;
                            white-space: pre-wrap;
                            word-wrap: break-word;
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
                <?php else: ?>
                    <div style="display: flex; flex-direction: row; align-items: flex-start; gap: 8px; margin-bottom: 10px;">
                        <div style="
                            width: 28px;
                            height: 28px;
                            border-radius: 50%;
                            background: radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: 700;
                            font-size: 14px;
                            color: #050509;
                            flex-shrink: 0;
                        ">T</div>
                        <div style="
                            max-width: 80%;
                            background: #111118;
                            border-radius: 16px 16px 16px 4px;
                            padding: 9px 12px;
                            font-size: 14px;
                            white-space: pre-wrap;
                            word-wrap: break-word;
                            border: 1px solid #272727;
                        ">
                            <?php
                            $content = trim((string)($message['content'] ?? ''));
                            $content = preg_replace('/^\s+/mu', '', $content);
                            ?>
                            <?= render_markdown_safe($content) ?>
                        </div>
                    </div>
                    <div style="margin: -6px 0 6px 36px; display:flex; align-items:center; gap:6px; font-size:10px; color:#777; max-width:80%;">
                        <?php if ($createdLabel): ?>
                            <span><?= htmlspecialchars($createdLabel) ?></span>
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
            background: #111118;
            border-radius: 18px;
            border: 1px solid #272727;
            padding: 8px 10px;
        ">
            <div style="display: flex; flex-direction: column; gap: 6px; margin-right: <?= $hasMediaOrFiles ? '8px' : '0'; ?>;">
                <?php if (!empty($allowedModels)): ?>
                    <select name="model" style="
                        min-width: 150px;
                        background: #050509;
                        color: #f5f5f5;
                        border-radius: 999px;
                        border: 1px solid #272727;
                        padding: 4px 9px;
                        font-size: 11px;
                    ">
                        <?php foreach ($allowedModels as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= $currentModel === $m ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
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
                            border: 1px solid #272727;
                            background: #050509;
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
                            border: 1px solid #272727;
                            background: #050509;
                            color: #f5f5f5;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            cursor: pointer;
                            font-size: 16px;
                        " title="Enviar arquivo/imagem">
                            📎
                            <input id="file-input" type="file" name="attachments[]" multiple style="display:none;" accept="image/jpeg,image/png,image/webp,application/pdf,text/csv">
                        </label>
                    <?php endif; ?>
                </div>

                <div id="file-list" style="max-width: 260px; font-size: 11px; color: #b0b0b0; display:flex; flex-wrap:wrap; gap:4px;"></div>
                <?php endif; ?>
            </div>
            <textarea id="chat-message" name="message" rows="1" required style="
                flex: 1;
                resize: none;
                border: none;
                outline: none;
                background: transparent;
                color: #f5f5f5;
                font-size: 14px;
                max-height: 140px;
            " placeholder="Pergunta pro Tuquinha sobre branding, identidade visual, posicionamento..."><?php if (!empty($draftMessage)) { echo htmlspecialchars($draftMessage); } ?></textarea>
            <button type="submit" style="
                border: none;
                border-radius: 999px;
                background: linear-gradient(135deg, #e53935, #ff6f60);
                color: #050509;
                font-weight: 600;
                font-size: 13px;
                padding: 8px 14px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            ">
                <span id="send-label">Enviar</span>
                <span>➤</span>
            </button>
        </div>
    </form>
</div>
<script>
    const CURRENT_CONVERSATION_ID = <?= isset($conversationId) ? (int)$conversationId : 0 ?>;

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
                                        messageEl.placeholder = 'Pergunta pro Tuquinha sobre branding, identidade visual, posicionamento...';
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
                                    messageEl.placeholder = 'Pergunta pro Tuquinha sobre branding, identidade visual, posicionamento...';
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
            messageInput.style.height = 'auto';
            const maxHeight = 140; // mesmo valor do max-height
            const newHeight = Math.min(messageInput.scrollHeight, maxHeight);
            messageInput.style.height = newHeight + 'px';
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
            // "- " no início da linha -> bullet visual
            out = out.replace(/^\-\s+/gm, '• ');
            // Quebras de linha
            out = out.replace(/\n/g, '<br>');
            return out;
        };

        const appendMessageToDom = (role, content, attachments) => {
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
                wrapper.style.display = 'flex';
                wrapper.style.justifyContent = 'flex-end';
                wrapper.style.marginBottom = '10px';

                const bubble = document.createElement('div');
                bubble.style.maxWidth = '80%';
                bubble.style.background = '#1e1e24';
                bubble.style.borderRadius = '16px 16px 4px 16px';
                bubble.style.padding = '9px 12px';
                bubble.style.fontSize = '14px';
                bubble.style.whiteSpace = 'pre-wrap';
                bubble.style.wordWrap = 'break-word';
                bubble.innerHTML = renderMarkdownSafeJs(text);

                wrapper.appendChild(bubble);
            } else {
                wrapper.style.display = 'flex';
                wrapper.style.alignItems = 'flex-start';
                wrapper.style.gap = '8px';
                wrapper.style.marginBottom = '10px';

                const avatar = document.createElement('div');
                avatar.textContent = 'T';
                avatar.style.width = '28px';
                avatar.style.height = '28px';
                avatar.style.borderRadius = '50%';
                avatar.style.background = 'radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%)';
                avatar.style.display = 'flex';
                avatar.style.alignItems = 'center';
                avatar.style.justifyContent = 'center';
                avatar.style.fontWeight = '700';
                avatar.style.fontSize = '14px';
                avatar.style.color = '#050509';
                avatar.style.flexShrink = '0';

                const bubble = document.createElement('div');
                bubble.style.maxWidth = '80%';
                bubble.style.background = '#111118';
                bubble.style.borderRadius = '16px 16px 16px 4px';
                bubble.style.padding = '9px 12px';
                bubble.style.fontSize = '14px';
                bubble.style.whiteSpace = 'pre-wrap';
                bubble.style.wordWrap = 'break-word';
                bubble.style.border = '1px solid #272727';
                bubble.innerHTML = renderMarkdownSafeJs(text);

                wrapper.appendChild(avatar);
                wrapper.appendChild(bubble);
            }

            chatWindow.appendChild(wrapper);
            chatWindow.scrollTop = chatWindow.scrollHeight;
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
        };

        const sendViaAjax = () => {
            if (isSending) {
                return;
            }
            const text = messageInput.value.trim();
            if (!text) {
                return;
            }

            const formData = new FormData(chatForm);

            isSending = true;

            // bloqueia edição enquanto envia
            messageInput.disabled = true;

            if (submitButton) {
                submitButton.disabled = true;
                const sendLabel = document.getElementById('send-label');
                if (sendLabel) {
                    sendLabel.dataset.original = sendLabel.dataset.original || sendLabel.textContent;
                    sendLabel.textContent = 'Enviando...';
                }
            }

            let lastStatus = 0;

            fetch('/chat/send', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
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
                        data.messages.forEach((m) => {
                            appendMessageToDom(m.role, m.content, m.attachments || null);
                        });
                    }
                })
                .catch((e) => {
                    const debug = 'fetch_error=' + (e && e.message ? e.message : 'unknown');
                    showErrorReportBox('Erro ao enviar mensagem. Verifique sua conexão e tente novamente.', debug);
                })
                .finally(() => {
                    isSending = false;
                    messageInput.disabled = false;
                    if (submitButton) {
                        submitButton.disabled = false;
                        const sendLabel = document.getElementById('send-label');
                        if (sendLabel && sendLabel.dataset.original) {
                            sendLabel.textContent = sendLabel.dataset.original;
                        }
                    }
                });
        };

        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendViaAjax();
            }
        });

        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            sendViaAjax();
        });

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

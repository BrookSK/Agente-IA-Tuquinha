<?php
/** @var array $chatHistory */
/** @var array $allowedModels */
/** @var string|null $currentModel */
/** @var array|null $currentPlan */
/** @var string|null $draftMessage */
/** @var string|null $audioError */
?>
<div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; height: calc(100vh - 56px - 48px);">
    <div id="chat-window" style="flex: 1; overflow-y: auto; padding: 12px 4px 12px 0;">
        <?php if (empty($chatHistory)): ?>
            <div style="text-align: center; margin-top: 40px; color: #b0b0b0; font-size: 14px;">
                <div style="font-size: 18px; margin-bottom: 6px;">Bora começar esse papo? ✨</div>
                <div>Me conta rapidinho: em que fase você tá com seus projetos de marca?</div>
            </div>
        <?php else: ?>
            <?php foreach ($chatHistory as $message): ?>
                <?php if (($message['role'] ?? '') === 'user'): ?>
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                        <div style="
                            max-width: 80%;
                            background: #1e1e24;
                            border-radius: 16px 16px 4px 16px;
                            padding: 9px 12px;
                            font-size: 14px;
                            white-space: pre-wrap;
                            word-wrap: break-word;
                        ">
                            <?= nl2br(htmlspecialchars($message['content'] ?? '')) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 10px;">
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
                            <?= nl2br(htmlspecialchars($message['content'] ?? '')) ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($audioError)): ?>
        <div style="margin-top:8px; background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px;">
            <?= htmlspecialchars($audioError) ?>
        </div>
    <?php endif; ?>

    <form action="/chat/send" method="post" enctype="multipart/form-data" style="margin-top: 12px;">
        <div style="
            display: flex;
            align-items: flex-end;
            gap: 8px;
            background: #111118;
            border-radius: 18px;
            border: 1px solid #272727;
            padding: 8px 10px;
        ">
            <div style="display: flex; flex-direction: column; gap: 6px; margin-right: 8px;">
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
            </div>
            <textarea name="message" rows="1" required style="
                flex: 1;
                resize: none;
                border: none;
                outline: none;
                background: transparent;
                color: #f5f5f5;
                font-size: 14px;
                max-height: 120px;
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
                <span>Enviar</span>
                <span>➤</span>
            </button>
        </div>
    </form>
</div>
<script>
    const chatWindow = document.getElementById('chat-window');
    if (chatWindow) {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    const fileInput = document.getElementById('file-input');
    const fileList = document.getElementById('file-list');
    if (fileInput && fileList) {
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
    const btnMic = document.getElementById('btn-mic');
    const wave = document.getElementById('audio-wave');

    if (btnMic && wave) {
        btnMic.addEventListener('click', async () => {
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
                        const blob = new Blob(audioChunks, { type: 'audio/webm' });
                        audioChunks = [];

                        const formData = new FormData();
                        formData.append('audio', blob, 'gravacao.webm');

                        fetch('/chat/audio', {
                            method: 'POST',
                            body: formData
                        }).then(() => {
                            window.location.reload();
                        });
                    };
                } catch (e) {
                    alert('Não consegui acessar o microfone. Verifique as permissões do navegador.');
                    return;
                }
            }

            if (mediaRecorder.state === 'inactive') {
                audioChunks = [];
                mediaRecorder.start();
                wave.style.display = 'flex';
                btnMic.textContent = '⏹';
            } else if (mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                btnMic.textContent = '🎙';
            }
        });
    }
</script>
<style>
@keyframes wave {
    from { transform: scaleY(0.6); opacity: 0.7; }
    to { transform: scaleY(1.4); opacity: 1; }
}
</style>
</script>

<?php

/** @var array $user */
/** @var array $otherUser */
/** @var array $conversation */
/** @var array $messages */

$currentId = (int)($user['id'] ?? 0);
$otherName = (string)($otherUser['name'] ?? 'Amigo');
$conversationId = (int)($conversation['id'] ?? 0);

?>
<div style="max-width: 1040px; margin: 0 auto; display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
    <aside style="flex:0 0 320px; max-width:100%; border-radius:18px; border:1px solid #272727; background:#111118; padding:10px 12px;">
        <div style="font-size:13px; font-weight:600; color:#f5f5f5; margin-bottom:6px;">
            Chamada com <?= htmlspecialchars($otherName, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <div style="background:#000; border-radius:12px; height:160px; overflow:hidden; position:relative;">
                <div id="tuquinha-local-video" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#b0b0b0; font-size:12px;">
                    Sua câmera aparecerá aqui quando a chamada for iniciada.
                </div>
            </div>
            <div style="background:#000; border-radius:12px; height:160px; overflow:hidden; position:relative;">
                <div id="tuquinha-remote-video" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#b0b0b0; font-size:12px;">
                    <span id="tuquinha-call-status">Chamada não iniciada.</span>
                </div>
            </div>
            <div style="display:flex; gap:8px; margin-top:4px; justify-content:center; flex-wrap:wrap;">
                <button type="button" id="btn-start-call" style="border:none; border-radius:999px; padding:6px 12px; font-size:12px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#4caf50,#8bc34a); color:#050509;">
                    Iniciar chamada de vídeo
                </button>
                <button type="button" id="btn-end-call" style="border:none; border-radius:999px; padding:6px 12px; font-size:12px; cursor:pointer; background:#311; color:#ffbaba; border:1px solid #a33;">
                    Encerrar
                </button>
            </div>
        </div>
    </aside>

    <main style="flex:1 1 0; min-width:260px; border-radius:18px; border:1px solid #272727; background:#111118; padding:10px 12px; display:flex; flex-direction:column; max-height:540px;">
        <header style="margin-bottom:6px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
            <div>
                <div style="font-size:11px; color:#b0b0b0;">Conversando com</div>
                <div style="font-size:15px; font-weight:600; color:#f5f5f5;">
                    <?= htmlspecialchars($otherName, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </header>

        <div id="social-chat-messages" style="flex:1 1 auto; overflow-y:auto; padding:6px 4px; display:flex; flex-direction:column; gap:6px; border-radius:10px; background:#050509; border:1px solid #272727;">
            <?php if (empty($messages)): ?>
                <div style="font-size:12px; color:#777; text-align:center; padding:12px 4px;">
                    Nenhuma mensagem ainda. Comece a conversa!
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                        $senderId = (int)($msg['sender_user_id'] ?? 0);
                        $isOwn = $senderId === $currentId;
                        $senderName = (string)($msg['sender_name'] ?? '');
                        $body = (string)($msg['body'] ?? '');
                        $createdAt = $msg['created_at'] ?? '';
                    ?>
                    <div style="display:flex; justify-content:<?= $isOwn ? 'flex-end' : 'flex-start' ?>;">
                        <div style="max-width:78%; padding:6px 8px; border-radius:10px; font-size:12px; line-height:1.4;
                            background:<?= $isOwn ? 'linear-gradient(135deg,#e53935,#ff6f60)' : '#1c1c24' ?>;
                            color:<?= $isOwn ? '#050509' : '#f5f5f5' ?>;">
                            <?php if (!$isOwn): ?>
                                <div style="font-size:11px; font-weight:600; margin-bottom:2px; color:#ffab91;">
                                    <?= htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                            <div><?= nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php if ($createdAt): ?>
                                <div style="font-size:10px; margin-top:2px; opacity:0.8; text-align:right;">
                                    <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form action="/social/chat/enviar" method="post" style="margin-top:8px; display:flex; gap:6px; align-items:flex-end;">
            <input type="hidden" name="conversation_id" value="<?= $conversationId ?>">
            <textarea name="body" rows="2" style="flex:1; resize:vertical; min-height:40px; max-height:120px; padding:6px 8px; border-radius:10px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;"></textarea>
            <button type="submit" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap;">
                Enviar
            </button>
        </form>
    </main>
</div>

<script>
(function () {
    var box = document.getElementById('social-chat-messages');
    if (box) {
        box.scrollTop = box.scrollHeight;
    }

    var startBtn = document.getElementById('btn-start-call');
    var endBtn = document.getElementById('btn-end-call');
    var localContainer = document.getElementById('tuquinha-local-video');
    var statusSpan = document.getElementById('tuquinha-call-status');
    var jitsiApi = null;
    var roomName = 'tuquinha-social-' + <?= $conversationId ?>;

    function setStatus(text) {
        if (statusSpan) {
            statusSpan.textContent = text;
        }
    }

    function ensureJitsiScript(callback) {
        if (window.JitsiMeetExternalAPI) {
            callback();
            return;
        }

        var existing = document.getElementById('jitsi-external-api');
        if (existing) {
            existing.addEventListener('load', function () {
                callback();
            });
            return;
        }

        var script = document.createElement('script');
        script.id = 'jitsi-external-api';
        script.src = 'https://meet.jit.si/external_api.js';
        script.async = true;
        script.onload = function () {
            callback();
        };
        script.onerror = function () {
            setStatus('Não foi possível carregar o serviço de chamada de vídeo. Tente novamente mais tarde.');
        };
        document.head.appendChild(script);
    }

    function startCall() {
        if (!localContainer) {
            return;
        }

        if (jitsiApi) {
            // Já em chamada
            return;
        }

        setStatus('Conectando à chamada...');

        ensureJitsiScript(function () {
            if (!window.JitsiMeetExternalAPI) {
                setStatus('Serviço de chamada de vídeo indisponível.');
                return;
            }

            // Limpa o container e cria o iframe da chamada
            localContainer.innerHTML = '';

            try {
                jitsiApi = new window.JitsiMeetExternalAPI('meet.jit.si', {
                    roomName: roomName,
                    parentNode: localContainer,
                    width: '100%',
                    height: '100%',
                    interfaceConfigOverwrite: {
                        TILE_VIEW_MAX_COLUMNS: 1,
                    },
                });

                setStatus('Chamada em andamento. Peça para seu amigo abrir esta mesma conversa e clicar em "Iniciar chamada de vídeo".');

                jitsiApi.addEventListener('videoConferenceJoined', function () {
                    setStatus('Você entrou na chamada. Aguarde seu amigo.');
                });

                jitsiApi.addEventListener('videoConferenceLeft', function () {
                    setStatus('Chamada encerrada.');
                    endCall();
                });
            } catch (e) {
                setStatus('Não foi possível iniciar a chamada de vídeo.');
                jitsiApi = null;
            }
        });
    }

    function endCall() {
        if (jitsiApi) {
            try {
                jitsiApi.dispose();
            } catch (e) {}
            jitsiApi = null;
        }

        if (localContainer) {
            localContainer.innerHTML = 'Sua câmera aparecerá aqui quando a chamada for iniciada.';
        }

        setStatus('Chamada não iniciada.');
    }

    if (startBtn) {
        startBtn.addEventListener('click', startCall);
    }
    if (endBtn) {
        endBtn.addEventListener('click', endCall);
    }
})();
</script>

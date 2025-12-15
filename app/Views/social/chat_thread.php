<?php

/** @var array $user */
/** @var array $otherUser */
/** @var array $conversation */
/** @var array $messages */
/** @var int $lastMessageId */

$currentId = (int)($user['id'] ?? 0);
$currentName = (string)($user['name'] ?? 'Você');
$otherName = (string)($otherUser['name'] ?? 'Amigo');
$otherId = (int)($otherUser['id'] ?? 0);
$conversationId = (int)($conversation['id'] ?? 0);

?>
<div style="max-width: 1040px; margin: 0 auto; display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
    <aside style="flex:0 0 320px; max-width:100%; border-radius:18px; border:1px solid #272727; background:#111118; padding:10px 12px;">
        <div style="font-size:13px; font-weight:600; color:#f5f5f5; margin-bottom:6px;">
            Chamada com <?= htmlspecialchars($otherName, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <div style="background:#000; border-radius:12px; height:160px; overflow:hidden; position:relative; border:1px solid #272727;">
                <div id="tuquinha-local-video" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#b0b0b0; font-size:12px;">
                    Sua câmera aparecerá aqui quando a chamada for iniciada.
                </div>
            </div>
            <div style="background:#000; border-radius:12px; height:160px; overflow:hidden; position:relative; border:1px solid #272727;">
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
                            color:<?= $isOwn ? '#050509' : '#f5f5f5' ?>;
                            border:1px solid #272727;">
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

        <form action="/social/chat/enviar" method="post" style="margin-top:8px; display:flex; gap:6px; align-items:flex-end;" id="social-chat-form">
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
    var remoteContainer = document.getElementById('tuquinha-remote-video');
    var statusSpan = document.getElementById('tuquinha-call-status');
    var chatForm = document.getElementById('social-chat-form');
    var currentUserName = <?= json_encode($currentName, JSON_UNESCAPED_UNICODE) ?>;
    var currentUserId = <?= (int)$currentId ?>;
    var otherUserId = <?= (int)$otherId ?>;
    var conversationId = <?= (int)$conversationId ?>;
    var lastMessageId = <?= isset($lastMessageId) ? (int)$lastMessageId : 0 ?>;

    var hasFetch = typeof window.fetch === 'function';
    var RTCPeerConnectionCtor = window.RTCPeerConnection || window.webkitRTCPeerConnection || window.mozRTCPeerConnection;

    var pc = null;
    var localStream = null;
    var localVideoEl = null;
    var remoteVideoEl = null;
    var lastSignalId = 0;
    var polling = false;
    var weWantCall = false;
    var remoteReady = false;
    var isCaller = currentUserId < otherUserId;

    function setStatus(text) {
        if (statusSpan) {
            statusSpan.textContent = text;
        }
    }

    function createVideoElement(container) {
        if (!container) return null;
        var video = document.createElement('video');
        video.autoplay = true;
        video.playsInline = true;
        video.muted = (container === localContainer);
        video.style.width = '100%';
        video.style.height = '100%';
        video.style.objectFit = 'cover';
        container.innerHTML = '';
        container.appendChild(video);
        return video;
    }

    function createPeerConnection() {
        if (pc) {
            return pc;
        }

        if (!RTCPeerConnectionCtor) {
            setStatus('Seu navegador não suporta chamadas de vídeo.');
            return null;
        }

        pc = new RTCPeerConnectionCtor({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' }
            ]
        });

        pc.onicecandidate = function (event) {
            if (event.candidate) {
                sendSignal('candidate', event.candidate);
            }
        };

        pc.ontrack = function (event) {
            if (!remoteVideoEl) {
                remoteVideoEl = createVideoElement(remoteContainer);
            }
            if (remoteVideoEl) {
                remoteVideoEl.srcObject = event.streams[0];
            }
        };

        pc.onconnectionstatechange = function () {
            if (!pc) return;
            if (pc.connectionState === 'connected') {
                setStatus('Chamada conectada.');
            } else if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
                setStatus('Conexão perdida.');
            }
        };

        return pc;
    }

    function ensureLocalStream() {
        if (localStream) {
            return Promise.resolve(localStream);
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Seu navegador não suporta chamadas de vídeo.');
            return Promise.reject(new Error('getUserMedia não suportado'));
        }

        return navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(function (stream) {
                localStream = stream;
                if (!localVideoEl) {
                    localVideoEl = createVideoElement(localContainer);
                }
                if (localVideoEl) {
                    localVideoEl.srcObject = stream;
                }

                var pcInstance = createPeerConnection();
                stream.getTracks().forEach(function (track) {
                    pcInstance.addTrack(track, stream);
                });

                return stream;
            })
            .catch(function (err) {
                console.error('Erro ao acessar câmera/microfone:', err);
                setStatus('Não foi possível acessar sua câmera/microfone. Verifique as permissões.');
                throw err;
            });
    }

    function sendSignal(type, data) {
        var formData = new FormData();
        formData.append('conversation_id', String(conversationId));
        formData.append('type', type);
        formData.append('payload', JSON.stringify(data || {}));

        return fetch('/social/chat/sinal', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json && json.ok && json.id && json.id > lastSignalId) {
                    lastSignalId = json.id;
                }
            })
            .catch(function (err) {
                console.error('Erro ao enviar sinal:', err);
            });
    }

    function handleSignal(signal) {
        var fromUserId = parseInt(signal.sender_user_id || signal.senderUserId || 0, 10);
        if (!fromUserId || fromUserId === currentUserId) {
            return; // ignora sinais que nós mesmos enviamos
        }

        var type = signal.type || '';
        var payloadStr = signal.payload || '{}';
        var payload;
        try {
            payload = JSON.parse(payloadStr);
        } catch (e) {
            payload = {};
        }

        if (type === 'ready') {
            remoteReady = true;
            maybeStartNegotiation();
            return;
        }

        if (type === 'offer') {
            ensureLocalStream().then(function () {
                var pcInstance = createPeerConnection();
                var desc = new RTCSessionDescription(payload);
                return pcInstance.setRemoteDescription(desc).then(function () {
                    return pcInstance.createAnswer();
                }).then(function (answer) {
                    return pcInstance.setLocalDescription(answer);
                }).then(function () {
                    sendSignal('answer', pcInstance.localDescription);
                    setStatus('Chamada conectando...');
                });
            }).catch(function () {});
            return;
        }

        if (type === 'answer') {
            if (!pc) {
                pc = createPeerConnection();
            }
            var answerDesc = new RTCSessionDescription(payload);
            pc.setRemoteDescription(answerDesc).catch(function (e) {
                console.error('Erro ao aplicar answer remota:', e);
            });
            return;
        }

        if (type === 'candidate') {
            if (!pc) {
                pc = createPeerConnection();
            }
            try {
                var candidate = new RTCIceCandidate(payload);
                pc.addIceCandidate(candidate).catch(function (e) {
                    console.error('Erro ao adicionar ICE candidate:', e);
                });
            } catch (e) {
                console.error('Erro ao criar ICE candidate:', e);
            }
            return;
        }

        if (type === 'bye') {
            setStatus('Seu amigo encerrou a chamada.');
            shutdownCall();
            return;
        }
    }

    function pollSignals() {
        if (!hasFetch) {
            return;
        }

        // só faz polling enquanto queremos estar em chamada (após clicar em iniciar)
        if (!weWantCall) {
            return;
        }

        if (polling) {
            return;
        }
        polling = true;

        var url = '/social/chat/sinais?conversation_id=' + encodeURIComponent(String(conversationId)) +
            '&after_id=' + encodeURIComponent(String(lastSignalId));

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json && json.ok && Array.isArray(json.signals)) {
                    json.signals.forEach(function (sig) {
                        var id = parseInt(sig.id || 0, 10);
                        if (id && id > lastSignalId) {
                            lastSignalId = id;
                        }
                        handleSignal(sig);
                    });
                }
            })
            .catch(function (err) {
                console.error('Erro ao buscar sinais:', err);
            })
            .finally(function () {
                polling = false;
                setTimeout(pollSignals, 5000);
            });
    }

    function maybeStartNegotiation() {
        if (!weWantCall || !remoteReady) {
            return;
        }
        if (!isCaller) {
            return;
        }

        ensureLocalStream().then(function () {
            var pcInstance = createPeerConnection();
            if (pcInstance.signalingState !== 'stable') {
                return;
            }
            return pcInstance.createOffer().then(function (offer) {
                return pcInstance.setLocalDescription(offer);
            }).then(function () {
                sendSignal('offer', pcInstance.localDescription);
                setStatus('Chamando seu amigo...');
            });
        }).catch(function () {});
    }

    function startCall() {
        if (weWantCall) {
            return;
        }
        weWantCall = true;
        setStatus('Conectando à chamada...');

        if (!RTCPeerConnectionCtor) {
            setStatus('Seu navegador não suporta chamadas de vídeo.');
            weWantCall = false;
            return;
        }

        ensureLocalStream().then(function () {
            sendSignal('ready', { user: currentUserName });
            maybeStartNegotiation();
            if (hasFetch) {
                pollSignals();
            }
        }).catch(function () {
            weWantCall = false;
        });
    }

    function shutdownCall() {
        if (pc) {
            try {
                pc.close();
            } catch (e) {}
            pc = null;
        }

        if (localStream) {
            localStream.getTracks().forEach(function (t) {
                try { t.stop(); } catch (e) {}
            });
            localStream = null;
        }

        if (localContainer) {
            localContainer.innerHTML = 'Sua câmera aparecerá aqui quando a chamada for iniciada.';
        }
        if (remoteContainer) {
            remoteContainer.innerHTML = '<span id="tuquinha-call-status">Chamada não iniciada.</span>';
            statusSpan = document.getElementById('tuquinha-call-status');
        }

        weWantCall = false;
        remoteReady = false;
        setStatus('Chamada não iniciada.');
    }

    function endCall() {
        sendSignal('bye', { user: currentUserName });
        shutdownCall();
    }

    function appendOwnMessage(body, createdAt) {
        var list = document.getElementById('social-chat-messages');
        if (!list) {
            return;
        }

        var wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.justifyContent = 'flex-end';

        var bubble = document.createElement('div');
        bubble.style.maxWidth = '78%';
        bubble.style.padding = '6px 8px';
        bubble.style.borderRadius = '10px';
        bubble.style.fontSize = '12px';
        bubble.style.lineHeight = '1.4';
        bubble.style.background = 'linear-gradient(135deg,#e53935,#ff6f60)';
        bubble.style.color = '#050509';

        var bodyDiv = document.createElement('div');
        bodyDiv.innerText = body;
        bubble.appendChild(bodyDiv);

        if (createdAt) {
            var meta = document.createElement('div');
            meta.style.fontSize = '10px';
            meta.style.marginTop = '2px';
            meta.style.opacity = '0.8';
            meta.style.textAlign = 'right';
            meta.innerText = createdAt;
            bubble.appendChild(meta);
        }

        wrapper.appendChild(bubble);
        list.appendChild(wrapper);

        list.scrollTop = list.scrollHeight;
    }

    function appendOtherMessage(message) {
        var list = document.getElementById('social-chat-messages');
        if (!list) {
            return;
        }

        var senderId = parseInt(message.sender_user_id || 0, 10);
        var senderName = message.sender_name || '';
        var body = message.body || '';
        var createdAt = message.created_at || '';

        var wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.justifyContent = 'flex-start';

        var bubble = document.createElement('div');
        bubble.style.maxWidth = '78%';
        bubble.style.padding = '6px 8px';
        bubble.style.borderRadius = '10px';
        bubble.style.fontSize = '12px';
        bubble.style.lineHeight = '1.4';
        bubble.style.background = '#1c1c24';
        bubble.style.color = '#f5f5f5';
        bubble.style.border = '1px solid #272727';

        if (senderId && senderId !== currentUserId && senderName) {
            var nameDiv = document.createElement('div');
            nameDiv.style.fontSize = '11px';
            nameDiv.style.fontWeight = '600';
            nameDiv.style.marginBottom = '2px';
            nameDiv.style.color = '#ffab91';
            nameDiv.innerText = senderName;
            bubble.appendChild(nameDiv);
        }

        var bodyDiv = document.createElement('div');
        bodyDiv.innerText = body;
        bubble.appendChild(bodyDiv);

        if (createdAt) {
            var meta = document.createElement('div');
            meta.style.fontSize = '10px';
            meta.style.marginTop = '2px';
            meta.style.opacity = '0.8';
            meta.style.textAlign = 'right';
            meta.innerText = createdAt;
            bubble.appendChild(meta);
        }

        wrapper.appendChild(bubble);
        list.appendChild(wrapper);

        list.scrollTop = list.scrollHeight;
    }

    if (startBtn) {
        startBtn.addEventListener('click', startCall);
    }
    if (endBtn) {
        endBtn.addEventListener('click', endCall);
    }

    if (chatForm) {
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var textarea = chatForm.querySelector('textarea[name="body"]');
            if (!textarea) {
                chatForm.submit();
                return;
            }

            var text = textarea.value.trim();
            if (!text) {
                return;
            }

            var submitBtn = chatForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            if (!hasFetch) {
                // navegador sem fetch: faz submit normal
                chatForm.submit();
                return;
            }

            var formData = new FormData(chatForm);
            formData.append('ajax', '1');

            fetch('/social/chat/enviar', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (data && data.ok && data.message) {
                    appendOwnMessage(data.message.body || text, data.message.created_at || '');
                    textarea.value = '';
                    if (data.message.id) {
                        lastMessageId = Math.max(lastMessageId, data.message.id);
                    }
                }
            }).catch(function () {
                // Se der erro no AJAX, faz fallback para submit normal
                chatForm.submit();
            }).finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
        });

        var textarea = chatForm.querySelector('textarea[name="body"]');
        if (textarea) {
            textarea.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    if (e.shiftKey) {
                        // Permite quebra de linha com Shift+Enter
                        return;
                    }
                    e.preventDefault();
                    if (typeof chatForm.requestSubmit === 'function') {
                        chatForm.requestSubmit();
                    } else {
                        chatForm.submit();
                    }
                }
            });
        }
    }

    function pollMessages() {
        return;
        if (!conversationId) {
            return;
        }

        if (!hasFetch) {
            return;
        }

        var url = '/social/chat/mensagens?conversation_id=' + encodeURIComponent(String(conversationId)) +
            '&after_id=' + encodeURIComponent(String(lastMessageId));

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (!data || !data.ok || !Array.isArray(data.messages)) {
                return;
            }

            data.messages.forEach(function (msg) {
                var id = parseInt(msg.id || 0, 10);
                if (id && id > lastMessageId) {
                    lastMessageId = id;
                }

                var senderId = parseInt(msg.sender_user_id || 0, 10);
                if (senderId === currentUserId) {
                    // já mostramos nossa própria mensagem pelo AJAX
                    return;
                }

                appendOtherMessage(msg);
            });
        }).catch(function () {
            // silencioso
        }).finally(function () {
            setTimeout(pollMessages, 5000);
        });
    }

    // inicia apenas o polling de mensagens automaticamente.
    // O polling de sinais só é iniciado quando o usuário clica em "Iniciar chamada de vídeo".
    if (hasFetch) {
        pollMessages();
    }
})();
</script>

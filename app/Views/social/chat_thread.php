<?php

/** @var array $user */
/** @var array $otherUser */
/** @var array $conversation */
/** @var array $messages */

$currentId = (int)($user['id'] ?? 0);
$currentName = (string)($user['name'] ?? 'Você');
$otherName = (string)($otherUser['name'] ?? 'Amigo');
$conversationId = (int)($conversation['id'] ?? 0);

?>
<div style="max-width: 1040px; margin: 0 auto; display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
    <aside style="flex:0 0 320px; max-width:100%; border-radius:18px; border:1px solid #272727; background:#111118; padding:10px 12px;">
        <div style="font-size:13px; font-weight:600; color:#f5f5f5; margin-bottom:6px;">
            Chamada com <?= htmlspecialchars($otherName, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <div style="background:#000; border-radius:12px; height:160px; overflow:hidden; position:relative; border:1px solid #272727;">
                <video id="tuquinhaLocalVideo" autoplay playsinline muted style="width:100%; height:100%; object-fit:cover; display:none;"></video>
                <div id="tuquinha-local-video" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#b0b0b0; font-size:12px;">
                    Sua câmera aparecerá aqui quando a chamada for iniciada.
                </div>
            </div>
            <div style="background:#000; border-radius:12px; height:160px; overflow:hidden; position:relative; border:1px solid #272727;">
                <video id="tuquinhaRemoteVideo" autoplay playsinline style="width:100%; height:100%; object-fit:cover; display:none;"></video>
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
    var localVideo = document.getElementById('tuquinhaLocalVideo');
    var remoteVideo = document.getElementById('tuquinhaRemoteVideo');
    var statusSpan = document.getElementById('tuquinha-call-status');
    var chatForm = document.getElementById('social-chat-form');
    var currentUserName = <?= json_encode($currentName, JSON_UNESCAPED_UNICODE) ?>;
    var currentUserId = <?= (int)$currentId ?>;
    var conversationId = <?= (int)$conversationId ?>;
    var socket = null;
    var pc = null;
    var localStream = null;
    var remoteStream = null;
    var socketUrl = <?= json_encode(defined('SOCKET_IO_URL') ? (string)SOCKET_IO_URL : 'http://localhost:3001', JSON_UNESCAPED_SLASHES) ?>;
    var socketPath = '/socket.io';

    if (typeof socketUrl === 'string' && socketUrl.indexOf('localhost') !== -1) {
        // Em produção, "localhost" no navegador é o PC do visitante. Usamos o mesmo domínio do site (via reverse proxy).
        socketUrl = window.location.origin;
    }

    function setStatus(text) {
        if (statusSpan) {
            statusSpan.textContent = text;
        }
    }

    function ensureSocketIoClient(callback) {
        if (window.io) {
            callback();
            return;
        }
        var existing = document.getElementById('socket-io-client');
        if (existing) {
            existing.addEventListener('load', callback);
            return;
        }
        var s = document.createElement('script');
        s.id = 'socket-io-client';
        s.src = 'https://cdn.socket.io/4.7.5/socket.io.min.js';
        s.async = true;
        s.onload = callback;
        s.onerror = function () {
            // Sem realtime; segue normal
        };
        document.head.appendChild(s);
    }

    function connectRealtime() {
        ensureSocketIoClient(function () {
            fetch('/social/socket/token', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.ok || !data.token || !window.io) return;
                    socket = window.io(socketUrl, {
                        auth: { token: data.token },
                        path: socketPath,
                        transports: ['websocket', 'polling']
                    });

                    socket.on('connect', function () {
                        socket.emit('join', { conversationId: conversationId });
                    });

                    socket.on('chat:message', function (payload) {
                        if (!payload || Number(payload.conversationId) !== conversationId) return;
                        if (!payload.message) return;
                        if (Number(payload.message.sender_user_id) === currentUserId) {
                            return;
                        }
                        appendOtherMessage(payload.message.sender_name || 'Amigo', payload.message.body || '', payload.message.created_at || '');
                    });

                    socket.on('webrtc:offer', async function (payload) {
                        if (!payload || Number(payload.conversationId) !== conversationId) return;
                        if (!payload.offer) return;
                        try {
                            await ensurePeerConnection();
                            await pc.setRemoteDescription(new RTCSessionDescription(payload.offer));
                            var answer = await pc.createAnswer();
                            await pc.setLocalDescription(answer);
                            socket.emit('webrtc:answer', { conversationId: conversationId, answer: pc.localDescription });
                            setStatus('Em chamada.');
                        } catch (e) {
                        }
                    });

                    socket.on('webrtc:answer', async function (payload) {
                        if (!payload || Number(payload.conversationId) !== conversationId) return;
                        if (!payload.answer || !pc) return;
                        try {
                            await pc.setRemoteDescription(new RTCSessionDescription(payload.answer));
                            setStatus('Em chamada.');
                        } catch (e) {
                        }
                    });

                    socket.on('webrtc:ice', async function (payload) {
                        if (!payload || Number(payload.conversationId) !== conversationId) return;
                        if (!payload.candidate || !pc) return;
                        try {
                            await pc.addIceCandidate(new RTCIceCandidate(payload.candidate));
                        } catch (e) {
                        }
                    });

                    socket.on('webrtc:end', function (payload) {
                        if (!payload || Number(payload.conversationId) !== conversationId) return;
                        endCall(false);
                    });
                })
                .catch(function () {
                });
        });
    }

    function showVideoElements() {
        if (localVideo && localContainer) {
            localContainer.style.display = 'none';
            localVideo.style.display = 'block';
        }
        if (remoteVideo && remoteContainer) {
            remoteContainer.style.display = 'none';
            remoteVideo.style.display = 'block';
        }
    }

    function hideVideoElements() {
        if (localVideo && localContainer) {
            localVideo.style.display = 'none';
            localContainer.style.display = 'flex';
        }
        if (remoteVideo && remoteContainer) {
            remoteVideo.style.display = 'none';
            remoteContainer.style.display = 'flex';
        }
    }

    async function ensurePeerConnection() {
        if (pc) return;

        pc = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        });

        pc.onicecandidate = function (ev) {
            if (ev.candidate && socket) {
                socket.emit('webrtc:ice', { conversationId: conversationId, candidate: ev.candidate });
            }
        };

        pc.ontrack = function (ev) {
            if (!remoteStream) {
                remoteStream = new MediaStream();
            }
            remoteStream.addTrack(ev.track);
            if (remoteVideo) {
                remoteVideo.srcObject = remoteStream;
            }
            showVideoElements();
        };

        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        localStream.getTracks().forEach(function (t) {
            pc.addTrack(t, localStream);
        });

        if (localVideo) {
            localVideo.srcObject = localStream;
        }
        showVideoElements();
    }

    async function startCall() {
        if (!socket) {
            setStatus('Realtime indisponível.');
            return;
        }

        try {
            setStatus('Iniciando chamada...');
            await ensurePeerConnection();
            var offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            socket.emit('webrtc:offer', { conversationId: conversationId, offer: pc.localDescription });
            setStatus('Chamando...');
        } catch (e) {
            setStatus('Não foi possível iniciar a chamada.');
        }
    }

    function endCall(emit) {
        if (emit === undefined) emit = true;
        if (emit && socket) {
            socket.emit('webrtc:end', { conversationId: conversationId });
        }
        if (pc) {
            try { pc.close(); } catch (e) {}
            pc = null;
        }
        if (localStream) {
            try {
                localStream.getTracks().forEach(function (t) { t.stop(); });
            } catch (e) {}
            localStream = null;
        }
        remoteStream = null;
        if (localVideo) localVideo.srcObject = null;
        if (remoteVideo) remoteVideo.srcObject = null;
        hideVideoElements();
        setStatus('Chamada não iniciada.');
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

    function appendOtherMessage(senderName, body, createdAt) {
        var list = document.getElementById('social-chat-messages');
        if (!list) {
            return;
        }

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

        var nameDiv = document.createElement('div');
        nameDiv.style.fontSize = '11px';
        nameDiv.style.fontWeight = '600';
        nameDiv.style.marginBottom = '2px';
        nameDiv.style.color = '#ffab91';
        nameDiv.innerText = senderName || 'Amigo';
        bubble.appendChild(nameDiv);

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

    connectRealtime();

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

            if (!socket) {
                // Sem realtime, fallback pro fluxo antigo
                chatForm.submit();
                return;
            }

            socket.emit('chat:send', {
                conversationId: conversationId,
                body: text
            }, function (ack) {
                try {
                    if (ack && ack.ok && ack.message) {
                        appendOwnMessage(ack.message.body || text, ack.message.created_at || '');
                        textarea.value = '';
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
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
                    chatForm.dispatchEvent(new Event('submit', {cancelable: true}));
                }
            });
        }
    }
})();
</script>

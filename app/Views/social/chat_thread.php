<?php

/** @var array $user */
/** @var array $otherUser */
/** @var array $conversation */
/** @var array $messages */

$currentId = (int)($user['id'] ?? 0);
$currentName = (string)($user['name'] ?? 'Você');
$otherName = (string)($otherUser['name'] ?? 'Amigo');
$conversationId = (int)($conversation['id'] ?? 0);
$initialLastMessageId = 0;
if (!empty($messages)) {
    $last = end($messages);
    $initialLastMessageId = (int)($last['id'] ?? 0);
    reset($messages);
}

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
                    <div data-message-id="<?= (int)($msg['id'] ?? 0) ?>" style="display:flex; justify-content:<?= $isOwn ? 'flex-end' : 'flex-start' ?>;">
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
    var pc = null;
    var localStream = null;
    var remoteStream = null;
    var sse = null;
    var lastMessageId = <?= (int)$initialLastMessageId ?>;
    var webrtcSinceId = 0;
    var webrtcPollInFlight = false;
    var pendingIce = [];

    function setStatus(text) {
        if (statusSpan) {
            statusSpan.textContent = text;
        }
    }

    function startSse() {
        try {
            if (typeof window.EventSource === 'undefined') {
                return;
            }
            if (sse) {
                try { sse.close(); } catch (e) {}
            }

            sse = new EventSource('/social/chat/stream?conversation_id=' + encodeURIComponent(conversationId) + '&last_id=' + encodeURIComponent(lastMessageId));
            sse.addEventListener('message', function (ev) {
                try {
                    var msg = JSON.parse(ev.data || '{}');
                    if (!msg || !msg.id) {
                        return;
                    }
                    lastMessageId = Math.max(lastMessageId, Number(msg.id) || 0);
                    if (Number(msg.sender_user_id) === currentUserId) {
                        return;
                    }
                    appendOtherMessage(msg.sender_name || 'Amigo', msg.body || '', msg.created_at || '');
                } catch (e) {
                }
            });

            sse.addEventListener('done', function (ev) {
                try {
                    var data = JSON.parse(ev.data || '{}');
                    if (data && data.last_id) {
                        lastMessageId = Math.max(lastMessageId, Number(data.last_id) || 0);
                    }
                } catch (e) {}

                try { sse.close(); } catch (e) {}
                sse = null;
                setTimeout(startSse, 250);
            });

            sse.addEventListener('ping', function () {
                // noop
            });

            sse.onerror = function () {
                try { sse.close(); } catch (e) {}
                sse = null;
                setTimeout(startSse, 1000);
            };
        } catch (e) {
        }
    }

    function sendSignal(kind, payload) {
        var fd = new FormData();
        fd.append('conversation_id', String(conversationId));
        fd.append('kind', String(kind));
        fd.append('payload', JSON.stringify(payload));
        return fetch('/social/webrtc/send', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.json();
        }).catch(function () {
            return null;
        });
    }

    function pollSignals() {
        if (webrtcPollInFlight) {
            return;
        }
        webrtcPollInFlight = true;

        fetch('/social/webrtc/poll?conversation_id=' + encodeURIComponent(conversationId) + '&since_id=' + encodeURIComponent(webrtcSinceId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.json();
        }).then(function (data) {
            if (!data || !data.ok) {
                return;
            }

            if (data.since_id) {
                webrtcSinceId = Math.max(webrtcSinceId, Number(data.since_id) || 0);
            }

            var events = data.events || [];
            var chain = Promise.resolve();
            events.forEach(function (ev) {
                chain = chain.then(function () {
                    var id = Number(ev.id) || 0;
                    webrtcSinceId = Math.max(webrtcSinceId, id);
                    var kind = String(ev.kind || '');
                    var payload = ev.payload;

                    if (kind === 'end') {
                        endCall(false);
                        return;
                    }

                    if (kind === 'offer' && payload) {
                        return ensurePeerConnection().then(function () {
                            return pc.setRemoteDescription(new RTCSessionDescription(payload));
                        }).then(function () {
                            var list = pendingIce.slice();
                            pendingIce = [];
                            var p = Promise.resolve();
                            list.forEach(function (c) {
                                p = p.then(function () {
                                    if (!pc) return;
                                    return pc.addIceCandidate(new RTCIceCandidate(c)).catch(function () {});
                                });
                            });
                            return p;
                        }).then(function () {
                            return pc.createAnswer();
                        }).then(function (answer) {
                            return pc.setLocalDescription(answer);
                        }).then(function () {
                            return sendSignal('answer', pc.localDescription);
                        }).then(function () {
                            setStatus('Em chamada.');
                        }).catch(function () {});
                    }

                    if (kind === 'answer' && payload && pc) {
                        return pc.setRemoteDescription(new RTCSessionDescription(payload)).then(function () {
                            var list = pendingIce.slice();
                            pendingIce = [];
                            var p = Promise.resolve();
                            list.forEach(function (c) {
                                p = p.then(function () {
                                    if (!pc) return;
                                    return pc.addIceCandidate(new RTCIceCandidate(c)).catch(function () {});
                                });
                            });
                            return p;
                        }).then(function () {
                            setStatus('Em chamada.');
                        }).catch(function () {});
                    }

                    if (kind === 'ice' && payload) {
                        if (!pc) {
                            pendingIce.push(payload);
                            return;
                        }

                        var hasRemoteDesc = false;
                        try {
                            hasRemoteDesc = !!(pc.remoteDescription && pc.remoteDescription.type);
                        } catch (e) {}

                        if (!hasRemoteDesc) {
                            pendingIce.push(payload);
                            return;
                        }

                        return pc.addIceCandidate(new RTCIceCandidate(payload)).catch(function () {});
                    }
                });
            });

            return chain;
        }).catch(function () {
        }).finally(function () {
            webrtcPollInFlight = false;
            setTimeout(pollSignals, 250);
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
            if (ev.candidate) {
                sendSignal('ice', ev.candidate);
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
        try {
            setStatus('Iniciando chamada...');
            await ensurePeerConnection();
            var offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            await sendSignal('offer', pc.localDescription);
            setStatus('Chamando...');
        } catch (e) {
            setStatus('Não foi possível iniciar a chamada.');
        }
    }

    function endCall(emit) {
        if (emit === undefined) emit = true;
        if (emit) {
            sendSignal('end', {});
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

    var existingLast = 0;
    try {
        var existingEls = document.querySelectorAll('#social-chat-messages [data-message-id]');
        for (var i = 0; i < existingEls.length; i++) {
            var id = Number(existingEls[i].getAttribute('data-message-id') || '0') || 0;
            existingLast = Math.max(existingLast, id);
        }
    } catch (e) {}
    lastMessageId = Math.max(lastMessageId, existingLast);
    startSse();
    pollSignals();

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
                        lastMessageId = Math.max(lastMessageId, Number(data.message.id) || 0);
                    }
                }
            }).catch(function () {
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
                    chatForm.dispatchEvent(new Event('submit', {cancelable: true}));
                }
            });
        }
    }
})();
</script>

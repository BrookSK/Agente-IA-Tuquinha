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

        <div id="tuquinha-typing" style="display:none; align-items:center; gap:8px; padding:6px 8px; margin-top:6px; border-radius:10px; background:#0b0b10; border:1px solid #272727; color:#b0b0b0; font-size:12px;">
            <span id="tuquinha-typing-name" style="color:#ffab91; font-weight:600;"></span>
            <span style="opacity:0.9;">está digitando</span>
            <span class="tuquinha-dots" style="display:inline-flex; gap:3px; margin-left:2px;">
                <span class="tuquinha-dot"></span>
                <span class="tuquinha-dot"></span>
                <span class="tuquinha-dot"></span>
            </span>
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
                <button type="button" id="btn-toggle-mic" style="border:none; border-radius:999px; padding:6px 12px; font-size:12px; cursor:pointer; background:#1c1c24; color:#f5f5f5; border:1px solid #272727;">
                    Mutar áudio
                </button>
                <button type="button" id="btn-toggle-cam" style="border:none; border-radius:999px; padding:6px 12px; font-size:12px; cursor:pointer; background:#1c1c24; color:#f5f5f5; border:1px solid #272727;">
                    Desligar câmera
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
                <div id="social-chat-empty" style="font-size:12px; color:#777; text-align:center; padding:12px 4px;">
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
    var typingBox = document.getElementById('tuquinha-typing');
    var typingName = document.getElementById('tuquinha-typing-name');
    var toggleMicBtn = document.getElementById('btn-toggle-mic');
    var toggleCamBtn = document.getElementById('btn-toggle-cam');
    var currentUserName = <?= json_encode($currentName, JSON_UNESCAPED_UNICODE) ?>;
    var currentUserId = <?= (int)$currentId ?>;
    var otherUserId = <?= (int)($otherUser['id'] ?? 0) ?>;
    var conversationId = <?= (int)$conversationId ?>;
    var pc = null;
    var localStream = null;
    var remoteStream = null;
    var sse = null;
    var lastMessageId = <?= (int)$initialLastMessageId ?>;
    var webrtcSinceId = 0;
    var webrtcPollInFlight = false;
    var pendingIce = [];
    var makingOffer = false;
    var ignoreOffer = false;
    var isPolite = false;
    var sendingChat = false;
    var callUiState = 'idle';
    var startBtnOriginalText = startBtn ? startBtn.textContent : '';
    var endBtnOriginalText = endBtn ? endBtn.textContent : '';
    var incomingOffer = null;
    var micMuted = false;
    var camOff = false;
    var typingHideTimer = null;
    var lastTypingSentAt = 0;
    var typingStopTimer = null;
    var callEndedNoticeTimer = null;
    var statusLockUntil = 0;

    function setStatus(text) {
        try {
            if (statusLockUntil && Date.now && Date.now() < statusLockUntil) {
                return;
            }
        } catch (e) {}
        if (statusSpan) {
            statusSpan.textContent = text;
        }
    }

    function setCallUiState(state) {
        callUiState = state;

        if (startBtn) {
            if (state === 'in_call') {
                startBtn.style.display = 'none';
            } else {
                startBtn.style.display = '';
            }

            if (state === 'incoming') {
                startBtn.disabled = false;
                startBtn.textContent = 'Entrar na chamada';
            } else if (state === 'connecting') {
                startBtn.disabled = true;
                startBtn.textContent = 'Conectando...';
            } else if (state === 'in_call') {
                startBtn.disabled = true;
                startBtn.textContent = startBtnOriginalText || 'Iniciar chamada de vídeo';
            } else {
                startBtn.disabled = false;
                startBtn.textContent = startBtnOriginalText || 'Iniciar chamada de vídeo';
            }
        }

        if (endBtn) {
            if (state === 'incoming' || state === 'connecting' || state === 'in_call') {
                endBtn.disabled = false;
                endBtn.textContent = endBtnOriginalText || 'Encerrar';
            } else {
                endBtn.disabled = true;
                endBtn.textContent = endBtnOriginalText || 'Encerrar';
            }
        }

        var showMediaControls = (state === 'in_call');
        var hasLocal = !!(localStream && localStream.getTracks && localStream.getTracks().length);

        if (toggleMicBtn) {
            toggleMicBtn.style.display = showMediaControls ? '' : 'none';
            toggleMicBtn.disabled = !hasLocal;
            toggleMicBtn.style.background = micMuted ? '#311' : '#1c1c24';
            toggleMicBtn.style.color = micMuted ? '#ffbaba' : '#f5f5f5';
            toggleMicBtn.style.border = micMuted ? '1px solid #a33' : '1px solid #272727';
            toggleMicBtn.innerHTML = (micMuted ?
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:-2px; margin-right:6px;"><path d="M19 11a7 7 0 0 1-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 18v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M8 21h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 5a3 3 0 0 1 6 0v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 11a7 7 0 0 0 2 4.9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 4l16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>Áudio mutado'
                :
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:-2px; margin-right:6px;"><path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 11a7 7 0 0 1-14 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 18v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M8 21h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>Mutar áudio');
        }

        if (toggleCamBtn) {
            toggleCamBtn.style.display = showMediaControls ? '' : 'none';
            toggleCamBtn.disabled = !hasLocal;
            toggleCamBtn.style.background = camOff ? '#311' : '#1c1c24';
            toggleCamBtn.style.color = camOff ? '#ffbaba' : '#f5f5f5';
            toggleCamBtn.style.border = camOff ? '1px solid #a33' : '1px solid #272727';
            toggleCamBtn.innerHTML = (camOff ?
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:-2px; margin-right:6px;"><path d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 6a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4l16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>Câmera desligada'
                :
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:-2px; margin-right:6px;"><path d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Desligar câmera');
        }
    }

    function applyLocalTrackStates() {
        if (!localStream) {
            return;
        }
        try {
            var a = localStream.getAudioTracks ? localStream.getAudioTracks() : [];
            for (var i = 0; i < a.length; i++) {
                a[i].enabled = !micMuted;
            }
        } catch (e) {}
        try {
            var v = localStream.getVideoTracks ? localStream.getVideoTracks() : [];
            for (var j = 0; j < v.length; j++) {
                v[j].enabled = !camOff;
            }
        } catch (e) {}
    }

    function toggleMic() {
        micMuted = !micMuted;
        applyLocalTrackStates();
        setCallUiState(callUiState);
    }

    function toggleCam() {
        camOff = !camOff;
        applyLocalTrackStates();
        setCallUiState(callUiState);
    }

    function acceptIncomingCall() {
        if (!incomingOffer) {
            return Promise.resolve();
        }
        var offerPayload = incomingOffer;
        incomingOffer = null;

        setStatus('Abrindo câmera...');
        setCallUiState('connecting');

        return ensurePeerConnection().then(function () {
            if (!pc) return;
            return pc.setRemoteDescription(new RTCSessionDescription(offerPayload));
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
            if (!pc) return;
            return pc.createAnswer();
        }).then(function (answer) {
            if (!pc) return;
            return pc.setLocalDescription(answer);
        }).then(function () {
            if (!pc) return;
            return sendSignal('answer', pc.localDescription);
        }).then(function () {
            setStatus('Conectando...');
        }).catch(function () {
            setStatus('Não foi possível entrar na chamada.');
            setCallUiState('idle');
        });
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
                    clearEmptyPlaceholder();
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

    async function renegotiateWithIceRestart() {
        if (!pc) return;
        if (makingOffer) return;
        try {
            makingOffer = true;
            var offer = await pc.createOffer({ iceRestart: true });
            await pc.setLocalDescription(offer);
            await sendSignal('offer', pc.localDescription);
        } catch (e) {
        } finally {
            makingOffer = false;
        }
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
                        statusLockUntil = (Date.now ? Date.now() : 0) + 4500;
                        if (statusSpan) {
                            statusSpan.textContent = <?= json_encode($otherName, JSON_UNESCAPED_UNICODE) ?> + ' encerrou a chamada de vídeo.';
                        }
                        if (callEndedNoticeTimer) {
                            clearTimeout(callEndedNoticeTimer);
                        }
                        callEndedNoticeTimer = setTimeout(function () {
                            statusLockUntil = 0;
                            if (callUiState === 'idle') {
                                setStatus('Chamada não iniciada.');
                            }
                        }, 4500);
                        return;
                    }

                    if (kind === 'typing') {
                        var isTyping = true;
                        try {
                            if (payload && typeof payload.typing !== 'undefined') {
                                isTyping = !!payload.typing;
                            }
                        } catch (e) {}

                        if (typingBox) {
                            if (isTyping) {
                                if (typingName) typingName.textContent = <?= json_encode($otherName, JSON_UNESCAPED_UNICODE) ?>;
                                typingBox.style.display = 'flex';
                                if (typingHideTimer) {
                                    clearTimeout(typingHideTimer);
                                }
                                typingHideTimer = setTimeout(function () {
                                    try { typingBox.style.display = 'none'; } catch (e) {}
                                }, 2500);
                            } else {
                                typingBox.style.display = 'none';
                            }
                        }
                        return;
                    }

                    if (kind === 'offer' && payload) {
                        if (!pc && callUiState !== 'connecting' && callUiState !== 'in_call') {
                            incomingOffer = payload;
                            setStatus('Seu amigo iniciou uma chamada. Clique em “Entrar na chamada”.');
                            setCallUiState('incoming');
                            return;
                        }

                        return ensurePeerConnection().then(function () {
                            var offerDesc = new RTCSessionDescription(payload);
                            var offerCollision = false;
                            try {
                                offerCollision = makingOffer || (pc && pc.signalingState !== 'stable');
                            } catch (e) {}

                            ignoreOffer = !isPolite && offerCollision;
                            if (ignoreOffer) {
                                return;
                            }

                            var p = Promise.resolve();
                            if (pc && pc.signalingState !== 'stable') {
                                p = p.then(function () {
                                    return pc.setLocalDescription({ type: 'rollback' }).catch(function () {});
                                });
                            }

                            return p.then(function () {
                                return pc.setRemoteDescription(offerDesc);
                            });
                        }).then(function () {
                            if (ignoreOffer) {
                                return;
                            }
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
                            if (ignoreOffer) {
                                return;
                            }
                            return pc.createAnswer();
                        }).then(function (answer) {
                            if (ignoreOffer) {
                                return;
                            }
                            return pc.setLocalDescription(answer);
                        }).then(function () {
                            if (ignoreOffer) {
                                return;
                            }
                            return sendSignal('answer', pc.localDescription);
                        }).then(function () {
                            setStatus('Em chamada.');
                        }).catch(function () {});
                    }

                    if (kind === 'answer' && payload && pc) {
                        var st = '';
                        try { st = String(pc.signalingState || ''); } catch (e) {}
                        if (st && st !== 'have-local-offer') {
                            setStatus('Reconectando...');
                            setCallUiState('connecting');
                            return renegotiateWithIceRestart();
                        }

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
                        if (ignoreOffer) {
                            return;
                        }
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

        isPolite = (Number(currentUserId) || 0) < (Number(otherUserId) || 0);

        pc = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        });

        pc.onnegotiationneeded = async function () {
            try {
                makingOffer = true;
                var offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                await sendSignal('offer', pc.localDescription);
            } catch (e) {
            } finally {
                makingOffer = false;
            }
        };

        pc.onicecandidate = function (ev) {
            if (ev.candidate) {
                sendSignal('ice', ev.candidate);
            }
        };

        pc.oniceconnectionstatechange = function () {
            try {
                if (statusLockUntil && Date.now && Date.now() < statusLockUntil) {
                    return;
                }
                var st = String(pc.iceConnectionState || '');
                if (st === 'checking') {
                    setStatus('Conectando...');
                    setCallUiState('connecting');
                }
                if (st === 'connected' || st === 'completed') {
                    setStatus('Em chamada.');
                    setCallUiState('in_call');
                }
                if (st === 'failed') {
                    setStatus('Falha na conexão.');
                    setCallUiState('idle');
                }
                if (st === 'disconnected') {
                    setStatus('Reconectando...');
                    setCallUiState('connecting');
                }
            } catch (e) {}
        };

        if ('onconnectionstatechange' in pc) {
            pc.onconnectionstatechange = function () {
                try {
                    if (statusLockUntil && Date.now && Date.now() < statusLockUntil) {
                        return;
                    }
                    var st = String(pc.connectionState || '');
                    if (st === 'connecting') {
                        setStatus('Conectando...');
                        setCallUiState('connecting');
                    }
                    if (st === 'connected') {
                        setStatus('Em chamada.');
                        setCallUiState('in_call');
                    }
                    if (st === 'failed') {
                        setStatus('Falha na conexão.');
                        setCallUiState('idle');
                    }
                    if (st === 'disconnected') {
                        setStatus('Reconectando...');
                        setCallUiState('connecting');
                    }
                    if (st === 'closed') {
                        setStatus('Chamada encerrada.');
                        setCallUiState('idle');
                    }
                } catch (e) {}
            };
        }

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
        applyLocalTrackStates();
        localStream.getTracks().forEach(function (t) {
            pc.addTrack(t, localStream);
        });

        if (localVideo) {
            localVideo.srcObject = localStream;
        }
        showVideoElements();
        setCallUiState(callUiState);
    }

    async function startCall() {
        try {
            setStatus('Iniciando chamada...');
            setCallUiState('connecting');
            await ensurePeerConnection();
            setStatus('Conectando...');
            if (pc && pc.signalingState === 'stable') {
                makingOffer = true;
                var offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                await sendSignal('offer', pc.localDescription);
                makingOffer = false;
            }
            setStatus('Chamando...');
        } catch (e) {
            setStatus('Não foi possível iniciar a chamada.');
            setCallUiState('idle');
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
        micMuted = false;
        camOff = false;
        remoteStream = null;
        if (localVideo) localVideo.srcObject = null;
        if (remoteVideo) remoteVideo.srcObject = null;
        hideVideoElements();
        setStatus('Chamada não iniciada.');
        setCallUiState('idle');
    }

    function appendOwnMessage(body, createdAt) {
        var list = document.getElementById('social-chat-messages');
        if (!list) {
            return;
        }

        clearEmptyPlaceholder();

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

    function clearEmptyPlaceholder() {
        try {
            var empty = document.getElementById('social-chat-empty');
            if (empty && empty.parentNode) {
                empty.parentNode.removeChild(empty);
            }
        } catch (e) {}
    }

    function appendOwnPendingMessage(body) {
        var list = document.getElementById('social-chat-messages');
        if (!list) {
            return null;
        }

        clearEmptyPlaceholder();

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
        bubble.style.opacity = '0.75';

        var bodyDiv = document.createElement('div');
        bodyDiv.innerText = body;
        bubble.appendChild(bodyDiv);

        var meta = document.createElement('div');
        meta.style.fontSize = '10px';
        meta.style.marginTop = '2px';
        meta.style.opacity = '0.8';
        meta.style.textAlign = 'right';
        meta.innerText = 'Enviando...';
        bubble.appendChild(meta);

        wrapper.appendChild(bubble);
        list.appendChild(wrapper);
        list.scrollTop = list.scrollHeight;

        return {
            wrapper: wrapper,
            meta: meta,
            bubble: bubble
        };
    }

    function appendOtherMessage(senderName, body, createdAt) {
        var list = document.getElementById('social-chat-messages');
        if (!list) {
            return;
        }

        clearEmptyPlaceholder();

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
        startBtn.addEventListener('click', function () {
            if (callUiState === 'incoming') {
                acceptIncomingCall();
                return;
            }
            startCall();
        });
    }
    if (toggleMicBtn) {
        toggleMicBtn.addEventListener('click', toggleMic);
    }
    if (toggleCamBtn) {
        toggleCamBtn.addEventListener('click', toggleCam);
    }
    if (endBtn) {
        endBtn.addEventListener('click', endCall);
    }

    setCallUiState('idle');

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

            if (sendingChat) {
                return;
            }

            var textarea = chatForm.querySelector('textarea[name="body"]');
            if (!textarea) {
                chatForm.submit();
                return;
            }

            var text = textarea.value.trim();
            if (!text) {
                return;
            }

            if (typingStopTimer) {
                clearTimeout(typingStopTimer);
                typingStopTimer = null;
            }
            sendSignal('typing', { typing: false });

            var submitBtn = chatForm.querySelector('button[type="submit"]');
            var pendingUi = appendOwnPendingMessage(text);

            var formData = new FormData(chatForm);
            formData.set('body', text);
            formData.append('ajax', '1');

            textarea.value = '';

            sendingChat = true;

            textarea.disabled = true;
            if (submitBtn) submitBtn.disabled = true;
            var originalBtnText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) submitBtn.textContent = 'Enviando...';

            fetch('/social/chat/enviar', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (res) {
                return res.text().then(function (txt) {
                    var parsed = null;
                    try {
                        parsed = JSON.parse(txt || '{}');
                    } catch (e) {
                        parsed = null;
                    }
                    return { ok: res.ok, status: res.status, data: parsed };
                });
            }).then(function (result) {
                var data = result ? result.data : null;
                if (data && data.ok && data.message) {
                    if (pendingUi && pendingUi.wrapper && pendingUi.wrapper.parentNode) {
                        pendingUi.wrapper.parentNode.removeChild(pendingUi.wrapper);
                    }
                    appendOwnMessage(data.message.body || text, data.message.created_at || '');
                    if (data.message.id) {
                        lastMessageId = Math.max(lastMessageId, Number(data.message.id) || 0);
                    }
                } else {
                    if (pendingUi && pendingUi.meta) {
                        pendingUi.meta.innerText = 'Falha ao enviar.';
                    }
                    textarea.value = text;
                    if (submitBtn) submitBtn.textContent = 'Tentar novamente';
                }
            }).catch(function () {
                if (pendingUi && pendingUi.meta) {
                    pendingUi.meta.innerText = 'Falha ao enviar.';
                }
                textarea.value = text;
                if (submitBtn) submitBtn.textContent = 'Tentar novamente';
            }).finally(function () {
                textarea.disabled = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (submitBtn.textContent === 'Enviando...') {
                        submitBtn.textContent = originalBtnText || 'Enviar';
                    }
                }
                sendingChat = false;
                try { textarea.focus(); } catch (e) {}
            });
        });

        var textarea = chatForm.querySelector('textarea[name="body"]');
        if (textarea) {
            var styleTag = document.createElement('style');
            styleTag.textContent = '@keyframes tuquinhaDotPulse{0%,80%,100%{transform:translateY(0);opacity:.35}40%{transform:translateY(-3px);opacity:1}}.tuquinha-dot{width:5px;height:5px;border-radius:999px;background:#b0b0b0;display:inline-block;animation:tuquinhaDotPulse 1s infinite}.tuquinha-dot:nth-child(2){animation-delay:.15s}.tuquinha-dot:nth-child(3){animation-delay:.3s}';
            document.head.appendChild(styleTag);

            textarea.addEventListener('input', function () {
                var now = Date.now();
                if ((now - lastTypingSentAt) < 1200) {
                    return;
                }
                lastTypingSentAt = now;
                sendSignal('typing', { typing: true, at: now });

                if (typingStopTimer) {
                    clearTimeout(typingStopTimer);
                }
                typingStopTimer = setTimeout(function () {
                    sendSignal('typing', { typing: false });
                }, 1800);
            });

            textarea.addEventListener('blur', function () {
                sendSignal('typing', { typing: false });
            });

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

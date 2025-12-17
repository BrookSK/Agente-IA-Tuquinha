const http = require('http');
const express = require('express');
const cors = require('cors');
const { Server } = require('socket.io');
const crypto = require('crypto');

const PORT = process.env.SOCKET_IO_PORT ? Number(process.env.SOCKET_IO_PORT) : 3001;
const SECRET = process.env.SOCKET_IO_SECRET || '';

function verifyToken(token) {
  if (!SECRET) return null;
  if (!token || typeof token !== 'string') return null;
  let raw;
  try {
    raw = Buffer.from(token, 'base64').toString('utf8');
  } catch {
    return null;
  }
  const parts = raw.split('|');
  if (parts.length !== 3) return null;
  const userId = Number(parts[0]);
  const exp = Number(parts[1]);
  const sig = parts[2];
  if (!userId || !exp || !sig) return null;
  if (Date.now() / 1000 > exp) return null;
  const payload = `${userId}|${exp}`;
  const expected = crypto.createHmac('sha256', SECRET).update(payload).digest('hex');
  if (expected !== sig) return null;
  return { userId };
}

const app = express();
app.use(cors({ origin: true, credentials: true }));

app.get('/health', (req, res) => {
  res.json({ ok: true });
});

const server = http.createServer(app);

const io = new Server(server, {
  cors: {
    origin: true,
    credentials: true,
    methods: ['GET', 'POST']
  }
});

io.use((socket, next) => {
  const token = socket.handshake.auth && socket.handshake.auth.token;
  const verified = verifyToken(token);
  if (!verified) {
    return next(new Error('unauthorized'));
  }
  socket.data.userId = verified.userId;
  next();
});

io.on('connection', (socket) => {
  socket.on('join', (payload) => {
    const conversationId = payload && Number(payload.conversationId);
    if (!conversationId) return;
    socket.join(`social:${conversationId}`);
  });

  socket.on('chat:message', (payload) => {
    const conversationId = payload && Number(payload.conversationId);
    const message = payload && payload.message;
    if (!conversationId || !message) return;
    socket.to(`social:${conversationId}`).emit('chat:message', {
      conversationId,
      message
    });
  });

  socket.on('webrtc:offer', (payload) => {
    const conversationId = payload && Number(payload.conversationId);
    if (!conversationId || !payload.offer) return;
    socket.to(`social:${conversationId}`).emit('webrtc:offer', {
      conversationId,
      offer: payload.offer
    });
  });

  socket.on('webrtc:answer', (payload) => {
    const conversationId = payload && Number(payload.conversationId);
    if (!conversationId || !payload.answer) return;
    socket.to(`social:${conversationId}`).emit('webrtc:answer', {
      conversationId,
      answer: payload.answer
    });
  });

  socket.on('webrtc:ice', (payload) => {
    const conversationId = payload && Number(payload.conversationId);
    if (!conversationId || !payload.candidate) return;
    socket.to(`social:${conversationId}`).emit('webrtc:ice', {
      conversationId,
      candidate: payload.candidate
    });
  });

  socket.on('webrtc:end', (payload) => {
    const conversationId = payload && Number(payload.conversationId);
    if (!conversationId) return;
    socket.to(`social:${conversationId}`).emit('webrtc:end', { conversationId });
  });
});

server.listen(PORT, () => {
  console.log(`Tuquinha realtime Socket.IO listening on :${PORT}`);
});

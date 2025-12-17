const http = require('http');
const express = require('express');
const cors = require('cors');
const { Server } = require('socket.io');
const crypto = require('crypto');
const mysql = require('mysql2/promise');

const PORT = process.env.SOCKET_IO_PORT ? Number(process.env.SOCKET_IO_PORT) : 3001;
const SECRET = process.env.SOCKET_IO_SECRET || '';

const DB_HOST = process.env.DB_HOST || 'localhost';
const DB_PORT = process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306;
const DB_NAME = process.env.DB_NAME || 'Agente-IA-Tuquinha';
const DB_USER = process.env.DB_USER || 'Agente-IA-Tuquinha';
const DB_PASS = process.env.DB_PASS || '';

let pool;

async function getPool() {
  if (pool) return pool;
  pool = mysql.createPool({
    host: DB_HOST,
    port: DB_PORT,
    database: DB_NAME,
    user: DB_USER,
    password: DB_PASS,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
  });
  return pool;
}

function normalizePair(a, b) {
  return a <= b ? [a, b] : [b, a];
}

async function userCanAccessConversation(userId, conversationId) {
  const p = await getPool();
  const [rows] = await p.query(
    'SELECT id, user1_id, user2_id FROM social_conversations WHERE id = ? LIMIT 1',
    [conversationId]
  );
  if (!rows || rows.length === 0) return { ok: false };
  const conv = rows[0];
  const u1 = Number(conv.user1_id);
  const u2 = Number(conv.user2_id);
  if (userId !== u1 && userId !== u2) return { ok: false };

  const otherUserId = userId === u1 ? u2 : u1;
  const [a, b] = normalizePair(userId, otherUserId);
  const [fr] = await p.query(
    'SELECT status FROM user_friends WHERE user_id = ? AND friend_user_id = ? LIMIT 1',
    [a, b]
  );
  if (!fr || fr.length === 0) return { ok: false };
  if (String(fr[0].status) !== 'accepted') return { ok: false };
  return { ok: true, otherUserId };
}

function formatDateTime(d) {
  const pad = (n) => String(n).padStart(2, '0');
  return (
    d.getFullYear() + '-' +
    pad(d.getMonth() + 1) + '-' +
    pad(d.getDate()) + ' ' +
    pad(d.getHours()) + ':' +
    pad(d.getMinutes()) + ':' +
    pad(d.getSeconds())
  );
}

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
    (async () => {
      const conversationId = payload && Number(payload.conversationId);
      if (!conversationId) return;
      const userId = Number(socket.data.userId);
      if (!userId) return;

      try {
        const allowed = await userCanAccessConversation(userId, conversationId);
        if (!allowed.ok) {
          socket.emit('error', { error: 'forbidden' });
          socket.disconnect(true);
          return;
        }
        socket.join(`social:${conversationId}`);
      } catch {
        socket.emit('error', { error: 'server_error' });
      }
    })();
  });

  socket.on('chat:send', (payload, ack) => {
    (async () => {
      const conversationId = payload && Number(payload.conversationId);
      const body = payload && typeof payload.body === 'string' ? payload.body.trim() : '';
      const userId = Number(socket.data.userId);
      if (!conversationId || !userId || !body) {
        if (typeof ack === 'function') ack({ ok: false, error: 'invalid_payload' });
        return;
      }

      try {
        const allowed = await userCanAccessConversation(userId, conversationId);
        if (!allowed.ok) {
          if (typeof ack === 'function') ack({ ok: false, error: 'forbidden' });
          return;
        }

        const p = await getPool();

        const [urows] = await p.query('SELECT name FROM users WHERE id = ? LIMIT 1', [userId]);
        const senderName = urows && urows[0] ? String(urows[0].name || '') : '';

        const [result] = await p.query(
          'INSERT INTO social_messages (conversation_id, sender_user_id, body, created_at) VALUES (?, ?, ?, NOW())',
          [conversationId, userId, body]
        );
        const messageId = result && result.insertId ? Number(result.insertId) : 0;

        let preview = body;
        if (preview.length > 255) preview = preview.slice(0, 252) + '...';
        await p.query(
          'UPDATE social_conversations SET last_message_at = NOW(), last_message_preview = ? WHERE id = ?',
          [preview || null, conversationId]
        );

        const message = {
          id: messageId,
          conversation_id: conversationId,
          sender_user_id: userId,
          sender_name: senderName,
          body: body,
          created_at: formatDateTime(new Date())
        };

        io.to(`social:${conversationId}`).emit('chat:message', { conversationId, message });
        if (typeof ack === 'function') ack({ ok: true, message });
      } catch (e) {
        if (typeof ack === 'function') ack({ ok: false, error: 'server_error' });
      }
    })();
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

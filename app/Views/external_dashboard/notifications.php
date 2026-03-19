<?php
/** @var array $user */
/** @var array $branding */
/** @var array $notifications */

$primaryColor = !empty($branding['primary_color']) ? $branding['primary_color'] : '#e53935';
$secondaryColor = !empty($branding['secondary_color']) ? $branding['secondary_color'] : '#ff6f60';
$accentColor = !empty($branding['accent_color']) ? $branding['accent_color'] : '#4caf50';
?>

<style>
.notifications-container {
    max-width: 900px;
    margin: 0 auto;
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.notifications-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-primary);
}

.mark-all-read-btn {
    padding: 8px 16px;
    background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
    color: #fff;
    border: none;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.mark-all-read-btn:hover {
    opacity: 0.9;
}

.notification-item {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
}

.notification-item:hover {
    border-color: <?= $primaryColor ?>;
    transform: translateX(4px);
}

.notification-item.unread {
    background: linear-gradient(90deg, rgba(<?= hexdec(substr($primaryColor, 1, 2)) ?>, <?= hexdec(substr($primaryColor, 3, 2)) ?>, <?= hexdec(substr($primaryColor, 5, 2)) ?>, 0.05) 0%, transparent 100%);
    border-left: 3px solid <?= $primaryColor ?>;
}

.notification-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}

.notification-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.notification-message {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 6px;
}

.notification-time {
    font-size: 12px;
    color: var(--text-secondary);
}

.notification-badge {
    width: 10px;
    height: 10px;
    background: <?= $primaryColor ?>;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 6px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-secondary);
}

.empty-state svg {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-state h3 {
    font-size: 18px;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.empty-state p {
    font-size: 14px;
}
</style>

<div class="notifications-container">
    <?php if (!empty($notifications)): ?>
        <div class="notifications-header">
            <form action="/painel-externo/notificacoes/marcar-todas-lidas" method="post" style="margin: 0;">
                <button type="submit" class="mark-all-read-btn">Marcar todas como lidas</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
            <h3>Nenhuma notificação</h3>
            <p>Você não tem notificações no momento.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
            <?php
                $isUnread = empty($notification['is_read']);
                $actorName = trim((string)($notification['actor_preferred_name'] ?? $notification['actor_name'] ?? 'Alguém'));
                $actorInitial = mb_strtoupper(mb_substr($actorName, 0, 1, 'UTF-8'), 'UTF-8');
                $actorAvatar = trim((string)($notification['actor_avatar'] ?? ''));
                $link = trim((string)($notification['link'] ?? '#'));
                $title = htmlspecialchars((string)($notification['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                $message = htmlspecialchars((string)($notification['message'] ?? ''), ENT_QUOTES, 'UTF-8');
                $createdAt = (string)($notification['created_at'] ?? '');
                
                // Formatar tempo relativo
                $timeAgo = '';
                if ($createdAt !== '') {
                    $timestamp = strtotime($createdAt);
                    $diff = time() - $timestamp;
                    if ($diff < 60) {
                        $timeAgo = 'agora mesmo';
                    } elseif ($diff < 3600) {
                        $mins = floor($diff / 60);
                        $timeAgo = $mins . ' minuto' . ($mins > 1 ? 's' : '') . ' atrás';
                    } elseif ($diff < 86400) {
                        $hours = floor($diff / 3600);
                        $timeAgo = $hours . ' hora' . ($hours > 1 ? 's' : '') . ' atrás';
                    } elseif ($diff < 604800) {
                        $days = floor($diff / 86400);
                        $timeAgo = $days . ' dia' . ($days > 1 ? 's' : '') . ' atrás';
                    } else {
                        $timeAgo = date('d/m/Y H:i', $timestamp);
                    }
                }
            ?>
            <a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" 
               class="notification-item <?= $isUnread ? 'unread' : '' ?>"
               onclick="markAsRead(<?= (int)$notification['id'] ?>)">
                <div class="notification-avatar">
                    <?php if ($actorAvatar !== ''): ?>
                        <img src="<?= htmlspecialchars($actorAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($actorName, ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <?= htmlspecialchars($actorInitial, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </div>
                <div class="notification-content">
                    <div class="notification-title"><?= $title ?></div>
                    <?php if ($message !== ''): ?>
                        <div class="notification-message"><?= $message ?></div>
                    <?php endif; ?>
                    <div class="notification-time"><?= htmlspecialchars($timeAgo, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php if ($isUnread): ?>
                    <div class="notification-badge"></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function markAsRead(notificationId) {
    // Marca notificação como lida via AJAX
    fetch('/painel-externo/notificacoes/marcar-lida', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    }).catch(function(err) {
        console.error('Erro ao marcar notificação como lida:', err);
    });
}
</script>

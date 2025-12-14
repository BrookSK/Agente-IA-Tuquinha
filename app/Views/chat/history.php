<?php
/** @var array $conversations */
/** @var string $term */
/** @var int $retentionDays */
?>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 10px; font-weight: 650;">Histórico de conversas</h1>
    <p style="color:var(--text-secondary); font-size: 14px; margin-bottom: 4px;">
        Aqui você encontra os chats recentes com o Tuquinha nesta sessão. Use a busca para localizar pelo título.
    </p>
    <?php $days = (int)($retentionDays ?? 90); if ($days <= 0) { $days = 90; } ?>
    <p style="color:#777; font-size: 12px; margin-bottom: 14px;">
        Os históricos são mantidos por <strong><?= htmlspecialchars((string)$days) ?> dias</strong>. Conversas mais antigas que isso são apagadas automaticamente.
    </p>

    <form method="get" action="/historico" style="margin-bottom: 14px; display:flex; gap:8px;">
        <input type="text" name="q" value="<?= htmlspecialchars($term) ?>" placeholder="Buscar pelo título do chat" style="
            flex:1; padding:8px 10px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
        <button type="submit" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; cursor:pointer;">
            Buscar
        </button>
    </form>

    <?php if (empty($conversations)): ?>
        <p style="color:var(--text-secondary); font-size:14px;">Nenhum histórico encontrado para esta sessão.</p>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($conversations as $conv): ?>
                <?php
                    $title = trim((string)($conv['title'] ?? ''));
                    if ($title === '') {
                        $title = 'Chat sem título';
                    }
                    $created = $conv['created_at'] ?? null;
                ?>
                <div style="background:var(--surface-card); border-radius:12px; padding:10px 12px; border:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; gap:8px;">
                    <div>
                        <div style="font-size:14px; font-weight:500; margin-bottom:4px;">
                            <?= htmlspecialchars($title) ?>
                        </div>
                        <?php if ($created): ?>
                            <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">
                                Iniciado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($created))) ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="/historico/renomear" style="display:flex; gap:4px; align-items:center; font-size:11px; margin-top:2px;">
                            <input type="hidden" name="id" value="<?= (int)$conv['id'] ?>">
                            <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" style="
                                flex:1; min-width:0;
                                background:var(--surface-subtle); border-radius:999px; border:1px solid var(--border-subtle);
                                padding:3px 7px; color:var(--text-primary); font-size:11px;">
                            <button type="submit" style="border:none; border-radius:999px; padding:3px 8px; background:var(--surface-subtle); color:var(--text-secondary); font-size:10px; cursor:pointer; border:1px solid var(--border-subtle);">Salvar</button>
                        </form>
                    </div>
                    <div>
                        <a href="/chat?c=<?= (int)$conv['id'] ?>" style="
                            display:inline-flex; align-items:center; gap:6px;
                            border-radius:999px; padding:6px 12px;
                            border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);
                            font-size:12px; text-decoration:none;
                        ">
                            <span>Abrir chat</span>
                            <span>➜</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

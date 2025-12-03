<?php /** @var array $report */ ?>
<?php /** @var array|null $user */ ?>

<div style="max-width: 800px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px;">Detalhes do relato de erro</h1>

    <a href="/admin/erros" style="font-size:12px; color:#ff6f60; text-decoration:none;">⟵ Voltar para lista</a>

    <div style="margin-top:14px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <p style="font-size:13px; margin-bottom:4px;"><strong>ID do relato:</strong> #<?= (int)$report['id'] ?></p>
        <p style="font-size:13px; margin-bottom:4px;"><strong>Status:</strong> <?= htmlspecialchars((string)$report['status']) ?></p>
        <p style="font-size:13px; margin-bottom:4px;"><strong>Tokens usados (informado):</strong> <?= (int)($report['tokens_used'] ?? 0) ?></p>
        <p style="font-size:13px; margin-bottom:4px;">
            <strong>Criado em:</strong>
            <?php if (!empty($report['created_at'])): ?>
                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$report['created_at']))) ?>
            <?php endif; ?>
        </p>
    </div>

    <div style="margin-top:14px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:8px;">Usuário</h2>
        <?php if ($user): ?>
            <p style="font-size:13px; margin-bottom:4px;"><strong>Nome:</strong> <?= htmlspecialchars($user['name'] ?? '') ?></p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>E-mail:</strong> <?= htmlspecialchars($user['email'] ?? '') ?></p>
            <p style="font-size:13px; margin-bottom:4px;"><strong>ID do usuário:</strong> <?= (int)($user['id'] ?? 0) ?></p>
        <?php else: ?>
            <p style="font-size:13px; color:#b0b0b0;">Usuário não encontrado (pode ter sido removido).</p>
        <?php endif; ?>
    </div>

    <div style="margin-top:14px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:8px;">Contexto técnico</h2>
        <p style="font-size:13px; margin-bottom:4px;"><strong>Conversa ID:</strong> <?= (int)($report['conversation_id'] ?? 0) ?: '-' ?></p>
        <p style="font-size:13px; margin-bottom:4px;"><strong>Mensagem ID:</strong> <?= (int)($report['message_id'] ?? 0) ?: '-' ?></p>

        <p style="font-size:13px; margin:10px 0 4px 0;"><strong>Mensagem de erro técnica:</strong></p>
        <div style="font-size:12px; color:#ffbaba; white-space:pre-wrap; border-radius:8px; border:1px solid #a33; padding:8px 10px; background:#050509; min-height:40px;">
            <?= nl2br(htmlspecialchars($report['error_message'] ?? '')) ?: '<span style="color:#555;">(vazio)</span>' ?>
        </div>
    </div>

    <div style="margin-top:14px; padding:14px 16px; border-radius:12px; background:#111118; border:1px solid #272727;">
        <h2 style="font-size:16px; margin-bottom:8px;">Comentário do usuário</h2>
        <div style="font-size:13px; color:#b0b0b0; white-space:pre-wrap; border-radius:8px; border:1px solid #272727; padding:8px 10px; background:#050509; min-height:40px;">
            <?= nl2br(htmlspecialchars($report['user_comment'] ?? '')) ?: '<span style="color:#555;">(vazio)</span>' ?>
        </div>
    </div>

    <?php
    $status = (string)($report['status'] ?? 'open');
    $canAct = $status === 'open';
    $userId = (int)($report['user_id'] ?? 0);
    $tokensUsed = (int)($report['tokens_used'] ?? 0);
    ?>

    <?php if ($canAct): ?>
        <div style="margin-top:16px; padding:14px 16px; border-radius:12px; background:#110e0e; border:1px solid #342020;">
            <h2 style="font-size:16px; margin-bottom:8px;">Ações do admin</h2>

            <?php if ($userId > 0 && $tokensUsed > 0 && $user): ?>
                <form method="post" action="/admin/erros/estornar" style="display:inline-block; margin-right:8px;">
                    <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
                    <button type="submit" style="cursor:pointer; padding:8px 14px; border-radius:999px; border:1px solid #2ecc71; background:#14361f; color:#c1ffda; font-size:13px;">
                        Estornar <?= $tokensUsed ?> tokens para o usuário
                    </button>
                </form>
            <?php else: ?>
                <p style="font-size:12px; color:#b0b0b0; margin-bottom:8px;">
                    Não é possível estornar tokens automaticamente para este relato (sem usuário ou sem quantidade de tokens).
                </p>
            <?php endif; ?>

            <form method="post" action="/admin/erros/resolver" style="display:inline-block;">
                <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
                <button type="submit" style="cursor:pointer; padding:8px 14px; border-radius:999px; border:1px solid #555; background:#222; color:#eee; font-size:13px;">
                    Marcar como resolvido sem estorno
                </button>
            </form>
        </div>
    <?php else: ?>
        <div style="margin-top:16px; padding:10px 12px; border-radius:10px; background:#111118; border:1px solid #272727; font-size:12px; color:#b0b0b0;">
            Este relato já foi processado (status: <strong><?= htmlspecialchars($status) ?></strong>).
        </div>
    <?php endif; ?>
</div>

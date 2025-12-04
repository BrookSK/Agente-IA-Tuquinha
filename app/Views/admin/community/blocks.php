<?php
/** @var array $blocks */
/** @var string|null $success */
/** @var string|null $error */
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 10px; font-weight: 650;">Bloqueios da comunidade</h1>
    <p style="font-size:13px; color:#b0b0b0; margin-bottom:10px;">Lista de usuários atualmente bloqueados para postar, curtir e comentar na comunidade.</p>

    <?php if (!empty($success)): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($blocks)): ?>
        <div style="font-size:13px; color:#b0b0b0;">Nenhum usuário está bloqueado na comunidade no momento.</div>
    <?php else: ?>
        <div style="border-radius:12px; border:1px solid #272727; background:#111118; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; font-size:12px;">
                <thead>
                    <tr style="background:#050509;">
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Usuário</th>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Motivo</th>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Bloqueado em</th>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Por</th>
                        <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocks as $block): ?>
                        <tr>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top;">
                                <div style="font-weight:600;"><?= htmlspecialchars($block['user_name'] ?? '') ?></div>
                                <div style="font-size:11px; color:#b0b0b0;">
                                    <?= htmlspecialchars($block['user_email'] ?? '') ?>
                                </div>
                            </td>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top; max-width:320px;">
                                <div style="white-space:pre-wrap; color:#d0d0d0;">
                                    <?= nl2br(htmlspecialchars($block['reason'] ?? '')) ?>
                                </div>
                            </td>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top;">
                                <span><?= htmlspecialchars($block['created_at'] ?? '') ?></span>
                            </td>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top;">
                                <span><?= htmlspecialchars($block['blocked_by_name'] ?? 'Desconhecido') ?></span>
                            </td>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top; text-align:right;">
                                <form action="/comunidade/desbloquear-usuario" method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= (int)($block['user_id'] ?? 0) ?>">
                                    <button type="submit" style="
                                        border:none; border-radius:999px; padding:5px 10px;
                                        background:#10330f; color:#c8ffd4; font-size:11px; font-weight:600; cursor:pointer;">
                                        Desbloquear
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

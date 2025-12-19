<?php
/** @var array $user */
/** @var array $course */
/** @var array $badge */
/** @var string|null $success */
/** @var string|null $error */

$courseId = (int)($course['id'] ?? 0);
$title = trim((string)($course['title'] ?? ''));
$badgeUrl = trim((string)($course['badge_image_path'] ?? ''));
$hasCertificate = !empty($badge['certificate_code']);
?>

<div style="max-width: 920px; margin: 0 auto;">
    <?php if (!empty($error)): ?>
        <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div style="border-radius:18px; border:1px solid var(--border-subtle); background:#050509; overflow:hidden;">
        <div style="padding:16px 16px 8px 16px; background:radial-gradient(circle at 30% 20%, #ff8a65 0, #e53935 25%, #050509 70%);">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                <div>
                    <div style="font-size:12px; color:rgba(255,255,255,0.75); letter-spacing:0.12em; text-transform:uppercase;">Parabéns</div>
                    <div style="font-size:22px; font-weight:950; color:#fff; margin-top:2px;">Curso finalizado</div>
                    <div style="font-size:13px; color:rgba(255,255,255,0.80); margin-top:6px; line-height:1.45;">
                        <?= $title !== '' ? 'Você concluiu <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong>.' : 'Você concluiu o curso.' ?>
                    </div>
                </div>

                <a href="/cursos/ver?id=<?= (int)$courseId ?>" style="display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; border:1px solid rgba(255,255,255,0.18); background:rgba(0,0,0,0.22); color:#fff; text-decoration:none; font-size:12px;">
                    Voltar ao curso
                </a>
            </div>
        </div>

        <div style="padding:14px 16px 16px 16px; background:#050509;">
            <div style="display:flex; gap:14px; align-items:center; flex-wrap:wrap;">
                <div class="badge-float" style="width:110px; height:110px; border-radius:22px; overflow:hidden; border:1px solid rgba(255,255,255,0.18); background:rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center;">
                    <?php if ($badgeUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($badgeUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Insígnia" style="width:100%; height:100%; object-fit:cover; display:block;">
                    <?php else: ?>
                        <span style="font-size:44px;">🏅</span>
                    <?php endif; ?>
                </div>

                <div style="flex:1; min-width:220px;">
                    <div style="font-size:14px; font-weight:900; color:#fff;">Insígnia liberada + certificado emitido</div>
                    <div style="font-size:12px; color:rgba(255,255,255,0.75); margin-top:4px; line-height:1.45;">
                        Sua insígnia aparece no seu <strong>perfil social</strong>. E você já pode abrir/baixar seu certificado.
                    </div>
                    <?php if (!empty($success)): ?>
                        <div style="margin-top:8px; font-size:12px; color:rgba(255,255,255,0.75);">
                            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:14px;">
                <a href="/perfil" style="display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:999px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18); color:#fff; text-decoration:none; font-weight:800; font-size:13px; flex:1 1 220px;">
                    Ver insígnia no perfil social
                </a>

                <?php if ($hasCertificate): ?>
                    <a href="/certificados/ver?course_id=<?= (int)$courseId ?>" style="display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:999px; background:linear-gradient(135deg,#ffcc80,#ff8a65); border:1px solid rgba(255,255,255,0.18); color:#050509; text-decoration:none; font-weight:900; font-size:13px; flex:1 1 220px;">
                        Ver certificado
                    </a>
                    <a href="/certificados/ver?course_id=<?= (int)$courseId ?>&print=1" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); border:1px solid rgba(255,255,255,0.18); color:#050509; text-decoration:none; font-weight:900; font-size:13px; flex:1 1 220px;">
                        Baixar / Imprimir certificado
                    </a>
                <?php else: ?>
                    <div style="flex:1 1 320px; font-size:12px; color:rgba(255,255,255,0.75); padding:10px 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.18); background:rgba(255,255,255,0.06);">
                        Certificado ainda não disponível.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes badgeRise {
        0% { transform: translateY(18px) scale(0.98); opacity: 0; filter: blur(1px); }
        55% { transform: translateY(-8px) scale(1.03); opacity: 1; filter: blur(0px); }
        100% { transform: translateY(0px) scale(1); opacity: 1; }
    }

    .badge-float {
        animation: badgeRise 780ms ease-out both;
    }

    body[data-theme="light"] .badge-float {
        border-color: rgba(0,0,0,0.10) !important;
        background: rgba(0,0,0,0.03) !important;
    }

    body[data-theme="light"] div[style*="background:#050509"] {
        background: var(--surface-card) !important;
    }

    body[data-theme="light"] div[style*="color:#fff"],
    body[data-theme="light"] div[style*="color:rgba(255,255,255"] {
        color: var(--text-primary) !important;
    }

    @media (max-width: 520px) {
        .badge-float {
            width: 96px !important;
            height: 96px !important;
            border-radius: 20px !important;
        }
    }
</style>

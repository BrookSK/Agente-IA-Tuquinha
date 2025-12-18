<?php
/** @var array $user */
/** @var array $course */
/** @var string|null $success */
/** @var string|null $error */

$courseId = (int)($course['id'] ?? 0);
$title = trim((string)($course['title'] ?? ''));
$badge = trim((string)($course['badge_image_path'] ?? ''));
?>

<div style="max-width: 720px; margin: 0 auto;">
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

    <div style="border-radius:18px; border:1px solid var(--border-subtle); background:var(--surface-card); padding:16px 16px;">
        <div style="display:flex; gap:14px; align-items:center; flex-wrap:wrap;">
            <div style="flex:0 0 auto;">
                <div style="width:72px; height:72px; border-radius:16px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle); display:flex; align-items:center; justify-content:center;">
                    <?php if ($badge !== ''): ?>
                        <img src="<?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?>" alt="Insígnia" style="width:100%; height:100%; object-fit:cover; display:block;">
                    <?php else: ?>
                        <span style="font-size:28px;">🏅</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="min-width: 0;">
                <div style="font-size:18px; font-weight:700;">Encerrar curso</div>
                <div style="font-size:13px; color:var(--text-secondary);">
                    <?= $title !== '' ? 'Curso: ' . htmlspecialchars($title) : 'Finalize seu curso' ?>
                </div>
            </div>
        </div>

        <div style="margin-top:12px; font-size:13px; color:var(--text-secondary); line-height:1.5;">
            Ao confirmar, você vai ganhar a insígnia deste curso no seu perfil social e não poderá refazer o curso.
        </div>

        <form action="/cursos/encerrar" method="post" style="margin-top:12px; display:flex; flex-direction:column; gap:10px;">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">

            <div>
                <label style="display:block; font-size:13px; margin-bottom:4px;">Comentário / depoimento (opcional)</label>
                <textarea name="testimonial_text" rows="5" style="width:100%; padding:10px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;"></textarea>
            </div>

            <div>
                <label style="display:block; font-size:13px; margin-bottom:4px;">Nota (1 a 5) (opcional)</label>
                <select name="rating" style="width:120px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    <option value="">-</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px;">
                <button type="submit" onclick="return confirm('Tem certeza que deseja encerrar este curso? Depois disso você não poderá refazer nem rever as aulas.');" style="border:none; border-radius:999px; padding:9px 18px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:13px; cursor:pointer;">
                    Confirmar e ganhar insígnia
                </button>
                <a href="/cursos/ver?id=<?= $courseId ?>" style="display:inline-flex; align-items:center; padding:9px 16px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; text-decoration:none;">
                    Voltar ao curso
                </a>
            </div>
        </form>
    </div>
</div>

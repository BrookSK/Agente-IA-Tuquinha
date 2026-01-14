<?php
/** @var array|null $user */
/** @var array|null $plan */
/** @var array $courses */
/** @var string|null $filter */
/** @var string|null $success */
/** @var string|null $error */
?>
<style>
    .course-card {
        flex: 1 1 260px;
        max-width: 300px;
        background: var(--surface-card);
        border-radius: 20px;
        border: 1px solid var(--border-subtle);
        overflow: hidden;
        color: var(--text-primary);
        font-size: 12px;
        text-align: left;
        text-decoration: none;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.25);
        transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    }
    .course-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.3);
        border-color: var(--accent-soft);
    }
    .course-card-image {
        width: 100%;
        height: 180px;
        overflow: hidden;
        background: var(--surface-subtle);
    }
    .course-card-short {
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 6px;
        line-height: 1.4;
        max-height: 3.6em;
        overflow: hidden;
    }
    .course-card-meta {
        font-size: 11px;
        color: var(--text-secondary);
        text-align: right;
    }

    .course-filter-tabs {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px;
        border-radius: 999px;
        border: 1px solid var(--border-subtle);
        background: rgba(255,255,255,0.04);
        box-shadow: var(--shadow-tile);
    }
    .course-filter-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid transparent;
        background: transparent;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 650;
        text-decoration: none;
        line-height: 1;
    }
    .course-filter-tab.is-active {
        background: linear-gradient(135deg, #e53935, #ff6f60);
        color: #050509;
    }
</style>
<?php $filter = isset($filter) ? (string)$filter : 'all'; ?>
<div style="max-width: none; width: 100%; margin: 0;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap: 12px;">
        <div style="min-width: 0;">
            <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">Cursos do Tuquinha</h1>
        </div>
        <div style="flex: 0 0 auto; margin-top: 2px;">
            <div class="course-filter-tabs">
                <a class="course-filter-tab<?= $filter !== 'my' ? ' is-active' : '' ?>" href="/cursos">Todos</a>
                <a class="course-filter-tab<?= $filter === 'my' ? ' is-active' : '' ?>" href="/cursos?f=my">Meus cursos</a>
            </div>
        </div>
    </div>
    <p style="color:var(--text-secondary); font-size:13px; margin-bottom:14px;">
        Aprofunde sua prática de branding com cursos focados em designers de marca. Alguns cursos são liberados pelo seu plano,
        outros podem ser adquiridos de forma avulsa.
    </p>

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

    <?php if (empty($courses)): ?>
        <div style="margin-top:10px; color:var(--text-secondary); font-size:13px;">
            <?php if ($filter === 'my'): ?>
                Você ainda não tem cursos liberados no seu perfil.
            <?php else: ?>
                Ainda não há cursos disponíveis para o seu perfil no momento.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-wrap:wrap; gap:14px; margin-top:6px;">
            <?php foreach ($courses as $course): ?>
                <?php
                    $cid = (int)($course['id'] ?? 0);
                    $title = trim((string)($course['title'] ?? ''));
                    $short = trim((string)($course['short_description'] ?? ''));
                    $image = trim((string)($course['image_path'] ?? ''));
                    $isEnrolled = !empty($course['is_enrolled']);
                    $canAccessByPlan = !empty($course['can_access_by_plan']);
                    $allowPublicPurchase = !empty($course['allow_public_purchase']);
                    $isPaid = !empty($course['is_paid']);
                    $priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
                    $discountPercent = 0.0;
                    if (!empty($plan) && isset($plan['course_discount_percent']) && $plan['course_discount_percent'] !== null && $plan['course_discount_percent'] !== '') {
                        $discountPercent = (float)$plan['course_discount_percent'];
                    }
                    if ($discountPercent < 0) {
                        $discountPercent = 0.0;
                    }
                    if ($discountPercent > 100) {
                        $discountPercent = 100.0;
                    }
                    $finalCents = $priceCents;
                    if ($isPaid && $priceCents > 0 && $discountPercent > 0) {
                        $finalCents = (int)round($priceCents * (1.0 - ($discountPercent / 100.0)));
                        if ($finalCents < 0) {
                            $finalCents = 0;
                        }
                    }
                    $hasDiscount = $isPaid && $priceCents > 0 && $finalCents < $priceCents;
                    $url = \App\Controllers\CourseController::buildCourseUrl($course);
                ?>
                <a href="<?= htmlspecialchars($url) ?>" class="course-card">
                    <div class="course-card-image">
                        <?php if ($image !== ''): ?>
                            <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($title) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                        <?php else: ?>
                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:24px; background:radial-gradient(circle at top left,#e53935 0,#050509 60%);">
                                🎓
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="padding:10px 12px 12px 12px;">
                        <div style="display:flex; justify-content:space-between; gap:6px; align-items:center; margin-bottom:4px;">
                            <div style="font-size:15px; font-weight:650; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?= htmlspecialchars($title) ?>
                            </div>
                            <?php if ($isEnrolled): ?>
                                <span style="font-size:10px; border-radius:999px; padding:2px 8px; border:1px solid #3aa857; color:#c8ffd4;">Inscrito</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($short !== ''): ?>
                            <div class="course-card-short">
                                <?= htmlspecialchars($short) ?>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                            <div style="font-size:11px; color:#ffcc80;">
                                <?php if ($isPaid): ?>
                                    <?php if ($hasDiscount): ?>
                                        R$ <?= number_format(max($finalCents,0)/100, 2, ',', '.') ?>
                                        <span style="opacity:0.75; text-decoration:line-through;">
                                            R$ <?= number_format(max($priceCents,0)/100, 2, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        R$ <?= number_format(max($priceCents,0)/100, 2, ',', '.') ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Gratuito para planos com cursos
                                <?php endif; ?>
                            </div>
                            <div class="course-card-meta">
                                <?php if ($canAccessByPlan): ?>
                                    <div>Disponível pelo seu plano</div>
                                <?php elseif ($allowPublicPurchase): ?>
                                    <div>Disponível para compra avulsa</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

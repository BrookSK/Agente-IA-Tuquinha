<?php
/** @var array|null $user */
/** @var array|null $plan */
/** @var array $courses */
/** @var string|null $success */
/** @var string|null $error */
?>
<div style="max-width: 960px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">Cursos do Tuquinha</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:14px;">
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
        <div style="margin-top:10px; color:#b0b0b0; font-size:13px;">
            Ainda não há cursos disponíveis para o seu perfil no momento.
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
                    $url = \App\Controllers\CourseController::buildCourseUrl($course);
                ?>
                <a href="<?= htmlspecialchars($url) ?>" style="
                    flex:1 1 260px;
                    max-width:300px;
                    background:#050509;
                    border-radius:20px;
                    border:1px solid #272727;
                    overflow:hidden;
                    color:#f5f5f5;
                    font-size:12px;
                    text-align:left;
                    text-decoration:none;
                    box-shadow:0 18px 35px rgba(0,0,0,0.55);
                    transition:transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 22px 45px rgba(0,0,0,0.7)'; this.style.borderColor='#ff6f60';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 18px 35px rgba(0,0,0,0.55)'; this.style.borderColor='#272727';">
                    <div style="width:100%; height:180px; overflow:hidden; background:#111118;">
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
                            <div style="font-size:12px; color:#b0b0b0; margin-bottom:6px; line-height:1.4; max-height:3.6em; overflow:hidden;">
                                <?= htmlspecialchars($short) ?>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                            <div style="font-size:11px; color:#ffcc80;">
                                <?php if ($isPaid): ?>
                                    R$ <?= number_format(max($priceCents,0)/100, 2, ',', '.') ?>
                                <?php else: ?>
                                    Gratuito para planos com cursos
                                <?php endif; ?>
                            </div>
                            <div style="font-size:11px; color:#b0b0b0; text-align:right;">
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

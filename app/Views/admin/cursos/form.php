<?php
/** @var array|null $course */
/** @var float|null $partnerCommissionPercent */
/** @var float|null $partnerDefaultPercent */
/** @var string $partnerEmail */
$isEdit = !empty($course);
$partnerCommissionPercent = $partnerCommissionPercent ?? null;
$partnerDefaultPercent = $partnerDefaultPercent ?? null;
$partnerEmail = $partnerEmail ?? '';

$isExternal = !empty($course['is_external']);
$externalToken = isset($course['external_token']) ? trim((string)$course['external_token']) : '';
$courseSlug = isset($course['slug']) ? trim((string)$course['slug']) : '';
$externalUrl = '';
if ($courseSlug !== '') {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $externalUrl = $scheme . $host . '/curso/' . urlencode($courseSlug);
}
?>
<div style="max-width: 720px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">
        <?= $isEdit ? 'Editar curso' : 'Novo curso' ?>
    </h1>
    <p style="color:var(--text-secondary); font-size:13px; margin-bottom:10px;">
        Defina título, descrição, acesso por plano ou compra avulsa e, se desejar, o parceiro responsável pelo curso.
    </p>

    <?php if (!empty($_SESSION['admin_course_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_error']) ?>
        </div>
        <?php unset($_SESSION['admin_course_error']); ?>
    <?php endif; ?>

    <form action="/admin/cursos/salvar" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$course['id'] ?>">
        <?php endif; ?>

        <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
            <div style="flex:1 1 260px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Título do curso</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($course['title'] ?? '') ?>" style="
                    width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            </div>
            <div style="flex:1 1 220px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Slug (técnico)</label>
                <input type="text" name="slug" required value="<?= htmlspecialchars($course['slug'] ?? '') ?>" placeholder="ex: branding-para-designers" style="
                    width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Usado nas URLs do curso.</div>
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Descrição curta</label>
            <input type="text" name="short_description" value="<?= htmlspecialchars($course['short_description'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Descrição completa</label>
            <textarea name="description" rows="6" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;">
<?= htmlspecialchars($course['description'] ?? '') ?></textarea>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Frase de Destaque (Tagline)</label>
            <input type="text" name="tagline" value="<?= htmlspecialchars($course['tagline'] ?? 'Aprenda Agora.') ?>" placeholder="Ex: Aprenda Agora." style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">Frase exibida abaixo do título na página externa do curso.</div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Imagem do curso</label>
            <input type="text" name="image_path" value="<?= htmlspecialchars($course['image_path'] ?? '') ?>" placeholder="Opcional. Você pode informar uma URL direta ou enviar um arquivo abaixo." style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            <div style="margin-top:6px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <label style="font-size:13px; color:var(--text-primary); display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                    <span>📷</span>
                    <span>Enviar arquivo</span>
                    <input type="file" name="image_upload" accept="image/*" style="display:none;">
                </label>
                <div style="font-size:11px; color:#777;">
                    Se você enviar um arquivo, a imagem será hospedada no servidor de mídia e este campo será preenchido com a URL gerada.
                </div>
            </div>

            <?php if (!empty($course['image_path'])): ?>
                <div style="margin-top:8px; display:flex; flex-direction:column; gap:6px;">
                    <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start;">
                        <div style="font-size:11px; color:#777; min-width:80px;">Imagem atual:</div>
                        <div style="border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle); max-width:200px;">
                            <img src="<?= htmlspecialchars($course['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Imagem atual do curso" style="display:block; width:100%; max-height:150px; object-fit:cover;">
                        </div>
                    </div>
                    <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:#b0b0b0; cursor:pointer; margin-left:2px;">
                        <input type="checkbox" name="remove_image" value="1">
                        <span>Remover imagem atual do curso</span>
                    </label>
                </div>
            <?php endif; ?>

            <div id="course-image-preview-wrapper" style="margin-top:8px; display:none;">
                <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Pré-visualização da nova imagem:</div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start;">
                    <div style="border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle); max-width:200px;">
                        <img id="course-image-preview" src="" alt="Pré-visualização da nova imagem" style="display:block; width:100%; max-height:150px; object-fit:cover;">
                    </div>
                    <div id="course-image-filename" style="font-size:11px; color:var(--text-secondary); max-width:260px; word-break:break-all;"></div>
                </div>
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Imagem da insígnia do curso (badge)</label>
            <input type="text" name="badge_image_path" value="<?= htmlspecialchars($course['badge_image_path'] ?? '') ?>" placeholder="Opcional. URL direta ou envie um arquivo abaixo." style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            <div style="margin-top:6px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <label style="font-size:13px; color:var(--text-primary); display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                    <span>🏅</span>
                    <span>Enviar arquivo</span>
                    <input type="file" name="badge_image_upload" accept="image/*" style="display:none;">
                </label>
                <div style="font-size:11px; color:#777;">
                    A imagem será hospedada no servidor de mídia e este campo será preenchido com a URL gerada.
                </div>
            </div>

            <?php if (!empty($course['badge_image_path'])): ?>
                <div style="margin-top:8px; display:flex; flex-direction:column; gap:6px;">
                    <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start;">
                        <div style="font-size:11px; color:#777; min-width:80px;">Insígnia atual:</div>
                        <div style="border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle); max-width:120px;">
                            <img src="<?= htmlspecialchars($course['badge_image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Imagem atual da insígnia" style="display:block; width:100%; max-height:120px; object-fit:cover;">
                        </div>
                    </div>
                    <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:#b0b0b0; cursor:pointer; margin-left:2px;">
                        <input type="checkbox" name="remove_badge_image" value="1">
                        <span>Remover imagem atual da insígnia</span>
                    </label>
                </div>
            <?php endif; ?>

            <div id="course-badge-preview-wrapper" style="margin-top:8px; display:none;">
                <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Pré-visualização da nova insígnia:</div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start;">
                    <div style="border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle); max-width:120px;">
                        <img id="course-badge-preview" src="" alt="Pré-visualização da nova insígnia" style="display:block; width:100%; max-height:120px; object-fit:cover;">
                    </div>
                    <div id="course-badge-filename" style="font-size:11px; color:var(--text-secondary); max-width:260px; word-break:break-all;"></div>
                </div>
            </div>
        </div>

        <div style="margin-top:4px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
            <div style="font-size:13px; font-weight:650; margin-bottom:6px;">Dados do certificado</div>

            <div style="margin-bottom:10px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Conteúdo programático (tópicos)</label>
                <textarea name="certificate_syllabus" rows="6" placeholder="Ex:&#10;- Introdução ao branding&#10;- Identidade visual&#10;- ..." style="
                    width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-card); color:var(--text-primary); font-size:13px; resize:vertical;">
<?= htmlspecialchars($course['certificate_syllabus'] ?? '') ?></textarea>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div>
                    <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Carga horária (horas)</label>
                    <input type="number" name="certificate_workload_hours" min="0" value="<?= htmlspecialchars((string)($course['certificate_workload_hours'] ?? '')) ?>" style="
                        width:140px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-card); color:var(--text-primary); font-size:13px;">
                </div>
                <div style="flex:1; min-width:220px;">
                    <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Local (opcional)</label>
                    <input type="text" name="certificate_location" value="<?= htmlspecialchars($course['certificate_location'] ?? '') ?>" placeholder="Online / São Paulo - SP" style="
                        width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-card); color:var(--text-primary); font-size:13px;">
                </div>
            </div>
        </div>

        <div style="display:flex; gap:14px; flex-wrap:wrap;">
            <div style="flex:1 1 260px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">E-mail do professor/parceiro (opcional)</label>
                <input type="email" name="partner_email" value="<?= htmlspecialchars($partnerEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="ex: prof@exemplo.com" style="
                    width:100%; max-width:260px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Usado para vincular o curso ao painel de parceiro desse usuário.</div>
            </div>
            <div style="flex:1 1 200px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Preço (R$)</label>
                <input type="text" name="price" value="<?= isset($course['price_cents']) ? number_format($course['price_cents']/100, 2, ',', '.') : '0,00' ?>" style="
                    width:140px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Usado apenas se o curso estiver marcado como pago.</div>
            </div>
            <div style="flex:1 1 220px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Comissão deste curso para o parceiro (%)</label>
                <input type="text" name="partner_commission_percent" value="<?= $partnerCommissionPercent !== null ? number_format($partnerCommissionPercent, 2, ',', '.') : '' ?>" style="
                    width:140px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                <?php if ($partnerDefaultPercent !== null): ?>
                    <div style="font-size:11px; color:#777; margin-top:3px;">Comissão padrão do parceiro: <?= number_format($partnerDefaultPercent, 2, ',', '.') ?>%</div>
                <?php else: ?>
                    <div style="font-size:11px; color:#777; margin-top:3px;">Se houver um parceiro configurado, a comissão padrão dele será usada caso este campo fique em branco.</div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top:14px; padding:12px 14px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
            <div style="font-size:14px; font-weight:650; margin-bottom:10px;">Personalização Visual (Branding)</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:12px;">Configure as cores e logo que aparecerão nos cursos deste parceiro.</div>
            
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
                <div style="flex:1 1 240px;">
                    <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Nome da empresa</label>
                    <input type="text" name="branding_company_name" value="<?= htmlspecialchars($branding['company_name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Ex: Minha Escola" style="
                        width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-card); color:var(--text-primary); font-size:13px;">
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
                <div style="flex:1 1 180px;">
                    <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Cor primária (HEX)</label>
                    <input type="text" name="branding_primary_color" value="<?= htmlspecialchars($branding['primary_color'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="#e53935" style="
                        width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-card); color:var(--text-primary); font-size:13px;">
                </div>
                <div style="flex:1 1 180px;">
                    <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Cor secundária (HEX)</label>
                    <input type="text" name="branding_secondary_color" value="<?= htmlspecialchars($branding['secondary_color'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="#ff6f60" style="
                        width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                        background:var(--surface-card); color:var(--text-primary); font-size:13px;">
                </div>
            </div>

            <div>
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Logo do parceiro</label>
                <?php if (!empty($branding['logo_url'])): ?>
                    <div style="display:flex; gap:10px; align-items:center; margin-bottom:8px;">
                        <div style="width:48px; height:48px; border-radius:8px; overflow:hidden; border:1px solid var(--border-subtle);">
                            <img src="<?= htmlspecialchars($branding['logo_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="Logo" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary); cursor:pointer;">
                            <input type="checkbox" name="remove_branding_logo" value="1">
                            <span>Remover logo atual</span>
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="branding_logo_upload" accept="image/*" style="width:100%; padding:8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px;">
                <div style="font-size:11px; color:#777; margin-top:4px;">Será enviado para o servidor de mídia externo.</div>
            </div>
        </div>

        <div style="margin-top:14px; padding:12px 14px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
            <div style="font-size:14px; font-weight:650; margin-bottom:6px;">Acesso a Comunidades</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:12px;">Permita que alunos deste curso acessem comunidades específicas.</div>
            
            <label style="display:flex; align-items:center; gap:6px; font-size:13px; margin-bottom:10px;">
                <input type="checkbox" name="allow_community_access" value="1" <?= !empty($course['allow_community_access']) ? 'checked' : '' ?> id="allowCommunityCheckbox">
                <span>Permitir acesso a comunidades?</span>
            </label>
            
            <div id="communitySelection" style="<?= empty($course['allow_community_access']) ? 'display:none;' : '' ?> margin-left:20px; padding:10px; border-radius:8px; background:var(--surface-card); border:1px solid var(--border-subtle);">
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;">Selecione as comunidades que os alunos deste curso poderão acessar:</div>
                <div id="communityList">
                    <?php if (!empty($partnerCommunities)): ?>
                        <?php foreach ($partnerCommunities as $comm): ?>
                            <label style="display:flex; align-items:center; gap:6px; font-size:13px; margin-bottom:6px; cursor:pointer;">
                                <input type="checkbox" name="community_ids[]" value="<?= (int)$comm['id'] ?>" <?= in_array((int)$comm['id'], $selectedCommunityIds) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($comm['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div id="noCommunities" style="font-size:12px; color:var(--text-secondary); font-style:italic;">
                            <?php if ($partnerEmail !== ''): ?>
                                Nenhuma comunidade disponível. O parceiro precisa criar comunidades primeiro.
                            <?php else: ?>
                                Salve o curso primeiro com um e-mail de parceiro para poder vincular comunidades.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <div style="display:flex; flex-direction:column; gap:6px; font-size:13px; color:var(--text-secondary); margin-top:14px;">
            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="checkbox" name="is_external" value="1" <?= $isExternal ? 'checked' : '' ?>>
                    <span>Curso externo (acesso e compra apenas via link)</span>
                </label>
            </div>
            <div style="font-size:11px; color:#777; margin-left:20px;">
                Ao marcar, este curso não aparece na vitrine normal e só pode ser acessado por um link externo.
            </div>

            <?php if ($isExternal && $externalUrl !== ''): ?>
                <div style="margin-left:20px; margin-top:6px; border:1px solid var(--border-subtle); background:var(--surface-card); border-radius:12px; padding:10px 12px;">
                    <div style="font-size:11px; color:var(--text-secondary); margin-bottom:6px;">Link externo do curso</div>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <input type="text" id="externalCourseLink" value="<?= htmlspecialchars($externalUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" readonly style="flex:1 1 260px; min-width:240px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px;" />
                        <button type="button" id="copyExternalCourseLink" style="border:none; border-radius:999px; padding:8px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">Copiar</button>
                    </div>
                </div>
            <?php elseif ($isExternal): ?>
                <div style="font-size:11px; color:#ffcc80; margin-left:20px;">
                    Salve o curso para gerar o link externo.
                </div>
            <?php endif; ?>

            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="checkbox" name="is_paid" value="1" <?= !empty($course['is_paid']) ? 'checked' : '' ?>>
                    <span>Curso pago (define um preço avulso para este curso)</span>
                </label>
            </div>
            <div style="font-size:11px; color:#777; margin-left:20px;">
                Se marcado, o curso terá um valor próprio. Quem tiver plano que libera cursos continua com acesso incluído; quem não tiver plano poderá comprar avulso se você também marcar a opção de compra avulsa abaixo.
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="checkbox" name="allow_plan_access_only" value="1" <?= !empty($course['allow_plan_access_only']) ? 'checked' : '' ?>>
                    <span>Disponível para assinantes de planos com cursos</span>
                </label>
            </div>
            <div style="font-size:11px; color:#777; margin-left:20px;">
                Assinantes de planos que liberam cursos sempre veem e podem acessar este curso sem pagar nada a mais. Use esta marcação apenas para sinalizar que o curso faz parte do conteúdo incluído nos planos.
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="checkbox" name="allow_public_purchase" value="1" <?= !empty($course['allow_public_purchase']) ? 'checked' : '' ?>>
                    <span>Mostrar também para quem não tem plano (compra avulsa)</span>
                </label>
            </div>
            <div style="font-size:11px; color:#777; margin-left:20px;">
                Se marcado, usuários sem plano enxergam este curso na vitrine. Se for pago (preço &gt; 0), eles poderão comprar avulso.
                Se o preço estiver em 0, o curso será tratado como gratuito mesmo com a opção de curso pago marcada: nesse caso, os alunos poderão se inscrever diretamente, sem passar por pagamento.
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-top:2px;">
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="checkbox" name="is_active" value="1" <?= !isset($course['is_active']) || !empty($course['is_active']) ? 'checked' : '' ?>>
                    <span>Curso ativo</span>
                </label>
            </div>
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar curso
            </button>
            <a href="/admin/cursos" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var copyBtn = document.getElementById('copyExternalCourseLink');
    var linkInput = document.getElementById('externalCourseLink');
    if (copyBtn && linkInput) {
        copyBtn.addEventListener('click', function () {
            try {
                linkInput.focus();
                linkInput.select();
                document.execCommand('copy');
                copyBtn.textContent = 'Copiado!';
                setTimeout(function () { try { copyBtn.textContent = 'Copiar'; } catch (e) {} }, 1400);
            } catch (e) {
            }
        });
    }

    var allowCommunityCheckbox = document.getElementById('allowCommunityCheckbox');
    var communitySelection = document.getElementById('communitySelection');
    if (allowCommunityCheckbox && communitySelection) {
        allowCommunityCheckbox.addEventListener('change', function () {
            communitySelection.style.display = this.checked ? 'block' : 'none';
        });
    }

    var fileInput = document.querySelector('input[name="image_upload"]');
    var wrapper = document.getElementById('course-image-preview-wrapper');
    var imgEl = document.getElementById('course-image-preview');
    var nameEl = document.getElementById('course-image-filename');

    var badgeFileInput = document.querySelector('input[name="badge_image_upload"]');
    var badgeWrapper = document.getElementById('course-badge-preview-wrapper');
    var badgeImgEl = document.getElementById('course-badge-preview');
    var badgeNameEl = document.getElementById('course-badge-filename');

    if (!fileInput || !wrapper || !imgEl || !nameEl) {
        fileInput = null;
    }

    if (fileInput) fileInput.addEventListener('change', function () {
        var file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) {
            wrapper.style.display = 'none';
            imgEl.removeAttribute('src');
            nameEl.textContent = '';
            return;
        }

        var url = URL.createObjectURL(file);
        imgEl.src = url;
        nameEl.textContent = file.name;
        wrapper.style.display = 'block';
    });

    if (!badgeFileInput || !badgeWrapper || !badgeImgEl || !badgeNameEl) {
        return;
    }

    badgeFileInput.addEventListener('change', function () {
        var file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) {
            badgeWrapper.style.display = 'none';
            badgeImgEl.removeAttribute('src');
            badgeNameEl.textContent = '';
            return;
        }

        var url = URL.createObjectURL(file);
        badgeImgEl.src = url;
        badgeNameEl.textContent = file.name;
        badgeWrapper.style.display = 'block';
    });
});
</script>

<?php
/** @var array $user */
/** @var array $course */
/** @var array $badge */
/** @var string $issuerName */
/** @var string $issuerSignatureImage */
/** @var string $verifyUrl */

$title = trim((string)($course['title'] ?? ''));
$studentName = trim((string)($user['name'] ?? ''));
$syllabus = trim((string)($course['certificate_syllabus'] ?? ''));
$hours = isset($course['certificate_workload_hours']) ? (int)$course['certificate_workload_hours'] : 0;
$location = trim((string)($course['certificate_location'] ?? ''));
$startedAt = $badge['started_at'] ?? null;
$finishedAt = $badge['finished_at'] ?? null;
$code = trim((string)($badge['certificate_code'] ?? ''));

$qrUrl = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($verifyUrl);

$autoPrint = isset($_GET['print']) && (string)$_GET['print'] === '1';
?>

<div style="max-width: 980px; margin: 0 auto;">
    <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
        <div>
            <div style="font-size:18px; font-weight:700;">Certificado</div>
            <div style="font-size:12px; color:var(--text-secondary);">Código: <?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="/certificados" style="display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Voltar</a>
            <button type="button" onclick="window.print()" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">Imprimir / Salvar PDF</button>
        </div>
    </div>

    <div id="certificateSheet" style="border-radius:18px; border:1px solid #272727; background:radial-gradient(circle at top left,#111118 0,#050509 70%); padding:18px 18px;">
        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
            <div style="min-width:260px;">
                <div style="font-size:12px; color:#ffcc80; letter-spacing:0.16em; text-transform:uppercase;">Tuquinha</div>
                <div style="font-size:24px; font-weight:800; margin-top:2px;">Certificado de Conclusão</div>
                <div style="margin-top:12px; font-size:13px; color:#d8d8d8; line-height:1.6;">
                    Certificamos que <strong style="color:#fff;"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></strong>
                    concluiu o curso <strong style="color:#fff;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>.
                </div>

                <div style="margin-top:12px; display:flex; gap:12px; flex-wrap:wrap;">
                    <div style="padding:10px 12px; border-radius:12px; border:1px solid #272727; background:#0b0b10;">
                        <div style="font-size:11px; color:#b0b0b0;">Carga horária</div>
                        <div style="font-size:14px; font-weight:700; color:#fff;"><?= $hours > 0 ? (int)$hours . 'h' : '-' ?></div>
                    </div>
                    <div style="padding:10px 12px; border-radius:12px; border:1px solid #272727; background:#0b0b10;">
                        <div style="font-size:11px; color:#b0b0b0;">Período</div>
                        <div style="font-size:14px; font-weight:700; color:#fff;">
                            <?= $startedAt ? htmlspecialchars((string)$startedAt) : '-' ?>
                            até
                            <?= $finishedAt ? htmlspecialchars((string)$finishedAt) : '-' ?>
                        </div>
                    </div>
                    <div style="padding:10px 12px; border-radius:12px; border:1px solid #272727; background:#0b0b10;">
                        <div style="font-size:11px; color:#b0b0b0;">Local</div>
                        <div style="font-size:14px; font-weight:700; color:#fff;"><?= $location !== '' ? htmlspecialchars($location, ENT_QUOTES, 'UTF-8') : 'Online' ?></div>
                    </div>
                </div>

                <?php if ($syllabus !== ''): ?>
                    <div style="margin-top:14px; padding:12px 12px; border-radius:12px; border:1px solid #272727; background:#0b0b10;">
                        <div style="font-size:12px; color:#ffcc80; font-weight:700; margin-bottom:6px;">Conteúdo programático</div>
                        <div style="white-space:pre-line; font-size:12px; color:#d0d0d0; line-height:1.55;">
                            <?= htmlspecialchars($syllabus, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="flex:0 0 auto; text-align:center;">
                <div style="border-radius:16px; border:1px solid #272727; background:#0b0b10; padding:10px 10px; width:220px;">
                    <div style="font-size:11px; color:#b0b0b0; margin-bottom:6px;">Verificação online</div>
                    <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR Code" style="width:180px; height:180px; display:block; margin:0 auto; border-radius:12px;">
                    <div style="font-size:10px; color:#777; margin-top:6px; word-break:break-all;">
                        <?= htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:16px; display:flex; justify-content:space-between; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <div style="min-width:240px;">
                <div style="font-size:12px; color:#b0b0b0;">Emissor</div>
                <div style="font-size:14px; font-weight:700; color:#fff;"><?= htmlspecialchars($issuerName, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($issuerSignatureImage !== ''): ?>
                    <div style="margin-top:8px;">
                        <img src="<?= htmlspecialchars($issuerSignatureImage, ENT_QUOTES, 'UTF-8') ?>" alt="Assinatura" style="height:54px; max-width:240px; object-fit:contain; display:block;">
                    </div>
                <?php endif; ?>
            </div>

            <div style="text-align:right;">
                <div style="font-size:11px; color:#b0b0b0;">Autenticidade</div>
                <div style="font-size:12px; color:#d0d0d0;">Este certificado pode ser verificado via QR Code.</div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background: #fff !important;
    }
    .no-print {
        display: none !important;
    }
    #certificateSheet {
        border: none !important;
        background: #fff !important;
        color: #000 !important;
    }
}

@media (max-width: 520px) {
    .no-print {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    .no-print > div:last-child {
        width: 100% !important;
    }
    .no-print a,
    .no-print button {
        width: 100% !important;
        justify-content: center !important;
    }
    #certificateSheet {
        padding: 12px 12px !important;
        border-radius: 14px !important;
    }
    #certificateSheet > div:first-child {
        flex-direction: column !important;
    }
    #certificateSheet div[style*="width:220px"] {
        width: 100% !important;
        max-width: 340px !important;
        margin: 0 auto !important;
    }
    #certificateSheet img[alt="QR Code"] {
        width: 160px !important;
        height: 160px !important;
    }
    #certificateSheet div[style*="font-size:24px"] {
        font-size: 20px !important;
    }
}
</style>

<?php if ($autoPrint): ?>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
<?php endif; ?>

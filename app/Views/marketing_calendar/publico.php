<?php
/** @var array $events */
/** @var string $token */
/** @var int $year */
/** @var int $month */

$monthNames = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$typeLabels = ['post'=>'Post','story'=>'Story','reels'=>'Reels','video'=>'Vídeo','email'=>'E-mail','anuncio'=>'Anúncio','outro'=>'Outro'];
$statusLabels = ['planejado'=>'Planejado','produzido'=>'Produzido','postado'=>'Postado'];

$today = date('Y-m-d');
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = (int)date('t', $firstDay);
$startWeekday = (int)date('w', $firstDay);

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$eventsByDay = [];
foreach ($events as $ev) {
    $d = (int)date('j', strtotime($ev['event_date']));
    $eventsByDay[$d][] = $ev;
}
$safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Marketing</title>
    <style>
        :root { --bg:#050509; --card:#111118; --border:#272727; --text:#f5f5f5; --text2:#b0b0b0; --accent:#e53935; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:system-ui,-apple-system,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; padding:24px; }
        .wrap { max-width:1100px; margin:0 auto; }
        h1 { font-size:22px; font-weight:700; margin-bottom:14px; }
        .nav { display:flex; align-items:center; gap:8px; margin-bottom:14px; }
        .nav a { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:999px; border:1px solid var(--border); background:var(--card); color:var(--text); font-size:16px; text-decoration:none; }
        .nav span { font-size:16px; font-weight:600; min-width:180px; text-align:center; }
        .grid { display:grid; grid-template-columns:repeat(7,1fr); gap:1px; background:var(--border); border-radius:14px; overflow:hidden; border:1px solid var(--border); }
        .dh { background:var(--card); padding:8px 4px; text-align:center; font-size:12px; font-weight:600; color:var(--text2); text-transform:uppercase; }
        .day { background:var(--card); min-height:90px; padding:6px; }
        .day.empty { background:var(--bg); opacity:.4; }
        .day-num { font-size:13px; font-weight:600; margin-bottom:4px; color:var(--text2); }
        .day.today .day-num { background:linear-gradient(135deg,#e53935,#ff6f60); color:#fff; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; }
        .ev { font-size:11px; padding:3px 6px; border-radius:6px; margin-bottom:3px; color:#fff; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; cursor:default; }
        @media(max-width:700px) { .day { min-height:60px; padding:3px; } .ev { font-size:10px; } }
    </style>
</head>
<body>
<div class="wrap">
    <h1>📅 Agenda de Marketing</h1>
    <div class="nav">
        <a href="/agenda-marketing/publico?token=<?= $safeToken ?>&year=<?= $prevYear ?>&month=<?= $prevMonth ?>">‹</a>
        <span><?= $monthNames[$month] ?> <?= $year ?></span>
        <a href="/agenda-marketing/publico?token=<?= $safeToken ?>&year=<?= $nextYear ?>&month=<?= $nextMonth ?>">›</a>
    </div>
    <div class="grid">
        <?php foreach ($dayNames as $dn): ?><div class="dh"><?= $dn ?></div><?php endforeach; ?>
        <?php for ($i = 0; $i < $startWeekday; $i++): ?><div class="day empty"></div><?php endfor; ?>
        <?php for ($d = 1; $d <= $daysInMonth; $d++):
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $isToday = ($dateStr === $today);
            $dayEvents = $eventsByDay[$d] ?? [];
        ?>
            <div class="day<?= $isToday ? ' today' : '' ?>">
                <div class="day-num"><?= $d ?></div>
                <?php foreach ($dayEvents as $ev): ?>
                    <div class="ev" style="background:<?= htmlspecialchars($ev['color'] ?? '#e53935') ?>" title="<?= htmlspecialchars($ev['title']) ?> — <?= $typeLabels[$ev['event_type']] ?? $ev['event_type'] ?> (<?= $statusLabels[$ev['status']] ?? $ev['status'] ?>)">
                        <?= htmlspecialchars($ev['title']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
        <?php $total = $startWeekday + $daysInMonth; $rem = (7 - ($total % 7)) % 7; for ($i = 0; $i < $rem; $i++): ?>
            <div class="day empty"></div>
        <?php endfor; ?>
    </div>
</div>
</body>
</html>

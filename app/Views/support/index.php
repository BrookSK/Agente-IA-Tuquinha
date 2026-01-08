<?php
    $supportWhatsapp = (string)\App\Models\Setting::get('support_whatsapp', '5517988093160');
    $supportEmail = (string)\App\Models\Setting::get('support_email', 'contato@lrvweb.com.br');

    $whatsLabel = $supportWhatsapp;
    if (preg_match('/^(55)(\d{2})(\d{4,5})(\d{4})$/', $supportWhatsapp, $m)) {
        $whatsLabel = '(' . $m[2] . ') ' . $m[3] . '-' . $m[4];
    }
?>
<div style="max-width: 600px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 8px; font-weight: 650;">Suporte Tuquinha</h1>
    <p style="color:#b0b0b0; font-size: 14px; margin-bottom: 14px;">
        Se algo travar na assinatura ou no uso do Tuquinha, fala com a gente por um destes canais:
    </p>

    <div style="background:#111118; border-radius:14px; padding:14px; border:1px solid #272727; display:flex; flex-direction:column; gap:10px;">
        <div>
            <strong style="font-size:14px;">WhatsApp</strong><br>
            <a href="https://wa.me/<?= htmlspecialchars($supportWhatsapp, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ff6f60; text-decoration:none; font-size:14px;">
                <?= htmlspecialchars($whatsLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>
        <div>
            <strong style="font-size:14px;">E-mail</strong><br>
            <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>" style="color:#ff6f60; text-decoration:none; font-size:14px;">
                <?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>
    </div>

    <p style="margin-top:12px; font-size:12px; color:#777;">
        Quando falar com a gente, se puder manda um print da tela de erro ou o horário aproximado em que aconteceu, isso ajuda a gente a corrigir mais rápido.
    </p>
</div>

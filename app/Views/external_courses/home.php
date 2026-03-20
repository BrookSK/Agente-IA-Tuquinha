<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */

$title = trim((string)($course['title'] ?? ''));
$desc = trim((string)($course['short_description'] ?? ''));
$long = trim((string)($course['description'] ?? ''));
$tagline = trim((string)($course['tagline'] ?? 'Aprenda Agora.'));
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = number_format(max($priceCents, 0) / 100, 2, ',', '.');
$imagePath = trim((string)($course['image_path'] ?? ''));
$companyName = isset($branding) && is_array($branding) ? trim((string)($branding['company_name'] ?? '')) : '';
$logoUrl = isset($branding) && is_array($branding) && !empty($branding['logo_url']) ? trim((string)$branding['logo_url']) : '';

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$courseHref = '/';
$checkoutHref = '/';
$loginAction = '/login';
$forgotHref = '/senha/esqueci';
$registerAction = '/registrar';
$registerFreeAction = '/registrar';

if ($slug !== '') {
    $courseHref = '/curso/' . urlencode($slug);
    $checkoutHref = '/curso/' . urlencode($slug) . '/checkout';
    $loginAction = '/curso/' . urlencode($slug) . '/login';
    $forgotHref = '/curso/' . urlencode($slug) . '/senha/esqueci';
    $registerAction = '/curso/' . urlencode($slug) . '/checkout';
    $registerFreeAction = '/curso/' . urlencode($slug) . '/registrar';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($companyName ?: $title, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg: #08090d; --surface: #0f1117; --card: #13151e; --border: rgba(255,255,255,.07);
    --accent: <?= htmlspecialchars($branding['primary_color'] ?? '#2d6ef6', ENT_QUOTES, 'UTF-8') ?>;
    --accent2: <?= htmlspecialchars($branding['secondary_color'] ?? '#00c8ff', ENT_QUOTES, 'UTF-8') ?>;
    --gold: #f5c842; --text: #e8eaf2; --muted: #6b7289; --radius: 18px;
    --glow: 0 0 60px rgba(45,110,246,.25);
  }
  html { 
    scroll-behavior: smooth; 
    overflow-x: hidden;
    max-width: 100%;
  }
  body {
    font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text);
    min-height: 100vh; overflow-x: hidden; position: relative;
    max-width: 100vw;
    width: 100%;
  }
  body::before {
    content: ''; position: fixed; inset: 0; z-index: 0;
    background: radial-gradient(ellipse 80% 60% at 15% 10%, rgba(45,110,246,.13) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 85% 80%, rgba(0,200,255,.09) 0%, transparent 55%),
                radial-gradient(ellipse 40% 40% at 50% 50%, rgba(245,200,66,.04) 0%, transparent 60%);
    pointer-events: none;
  }
  body::after {
    content: ''; position: fixed; inset: 0; z-index: 1;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.03'/%3E%3C/svg%3E");
    background-size: 180px; opacity: .4; pointer-events: none;
  }
  body { padding-top: 72px; }
  .site-header {
    position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
    background: rgba(8,9,13,.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,.07);
  }
  .site-header::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 160px);
    height: 4px;
    background: linear-gradient(90deg, #ff6b35 0%, #f7931e 50%, #fdc830 100%);
    border-radius: 4px;
    z-index: -1;
  }
  .header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 80px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
  }
  .header-brand {
    display: flex; align-items: center; gap: 12px;
    font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem;
    letter-spacing: -.5px; color: var(--text); text-decoration: none;
    position: relative;
    z-index: 10;
  }
  .brand-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: grid; place-items: center; font-size: .75rem; font-weight: 800;
    color: #fff; letter-spacing: 0; box-shadow: 0 4px 14px rgba(45,110,246,.4);
    overflow: hidden;
  }
  .brand-icon img { width: 100%; height: 100%; object-fit: cover; }
  .header-nav { 
    display: flex; 
    align-items: center; 
    gap: 1.5rem; 
    position: relative; 
    z-index: 10; 
  }
  .header-nav a:not(.btn) {
    background: none;
    border: none;
    box-shadow: none;
    padding: 0;
    color: var(--muted);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    transition: color 0.2s;
  }
  .header-nav a:not(.btn):hover {
    color: var(--text);
  }
  .btn-ghost {
    background: none; border: none; cursor: pointer; color: var(--muted);
    font-family: inherit; font-size: .875rem; padding: 8px 16px;
    border-radius: 10px; transition: color .2s, background .2s;
  }
  .btn-ghost:hover { color: var(--text); background: rgba(255,255,255,.05); }
  .btn-primary {
    background: var(--accent); border: none; cursor: pointer; color: #ffffff !important;
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: .875rem;
    padding: 10px 22px; border-radius: 12px;
    transition: transform .2s, box-shadow .2s, background .2s;
    box-shadow: 0 4px 18px rgba(45,110,246,.35);
    text-decoration: none; display: inline-block;
  }
  .btn-primary:hover {
    background: #4080ff; transform: translateY(-1px);
    box-shadow: 0 8px 28px rgba(45,110,246,.5);
  }
  .page { 
    position: relative; 
    z-index: 2; 
    max-width: 100vw;
    overflow-x: hidden;
  }
  .hero {
    min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr;
    align-items: center; gap: 0; padding-top: 68px;
    max-width: 100%;
    overflow-x: hidden;
  }
  .hero-left {
    padding: 80px 60px 80px 80px; display: flex; flex-direction: column; gap: 32px;
    animation: fadeUp .8s ease both;
  }
  .badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(45,110,246,.12); border: 1px solid rgba(45,110,246,.3);
    color: var(--accent2); border-radius: 100px; padding: 6px 14px;
    font-size: .78rem; font-weight: 500; width: fit-content; letter-spacing: .5px;
    max-width: 100%;
    text-align: center;
  }
  .badge-dot {
    width: 6px; height: 6px; border-radius: 50%; background: var(--accent2);
    animation: pulse 2s infinite;
  }
  .hero-title {
    font-family: 'Syne', sans-serif; font-weight: 800;
    font-size: clamp(1.8rem, 5vw, 3.6rem); line-height: 1.15;
    letter-spacing: -1.5px; color: var(--text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 100%;
  }
  .hero-title .hl {
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: clamp(1.5rem, 4.5vw, 3rem);
  }
  .hero-sub {
    font-size: 1rem; color: #ffffff; line-height: 1.7; max-width: 420px;
  }
  p {
    color: #ffffff;
  }
  .hero-cta { display: flex; gap: 12px; flex-wrap: wrap; }
  .btn-lg {
    padding: 14px 28px; border-radius: 14px; font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: .95rem; cursor: pointer; border: none;
    transition: all .25s; text-decoration: none; display: inline-block;
  }
  .btn-lg.primary {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #ffffff !important; box-shadow: 0 6px 24px rgba(45,110,246,.4);
  }
  .btn-lg.primary:hover {
    transform: translateY(-2px); box-shadow: 0 12px 32px rgba(45,110,246,.55);
  }
  .btn-lg.outline {
    background: rgba(255,255,255,.04); border: 1px solid var(--border);
    color: var(--text);
  }
  .btn-lg.outline:hover {
    background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.15);
  }
  .hero-right {
    padding: 80px 80px 80px 40px; display: flex; align-items: center;
    justify-content: center; animation: fadeUp .8s .15s ease both;
  }
  .login-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 24px;
    padding: 44px 40px; width: 100%; max-width: 420px;
    box-shadow: 0 24px 80px rgba(0,0,0,.4), 0 0 0 1px rgba(255,255,255,.03);
    position: relative; overflow: hidden;
  }
  .login-card::before {
    content: ''; position: absolute; inset: -2px; border-radius: 24px;
    background: conic-gradient(
      from 0deg,
      transparent 0deg,
      transparent 340deg,
      var(--accent) 350deg,
      var(--accent2) 360deg,
      transparent 360deg
    );
    animation: border-spin 4s linear infinite;
    z-index: -1;
    filter: blur(0.5px);
  }
  .login-card::after {
    content: ''; position: absolute; inset: 2px; border-radius: 22px;
    background: var(--card); z-index: -1;
  }
  @keyframes border-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  .card-head { text-align: center; margin-bottom: 32px; }
  .card-head h2 {
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.5rem;
    color: var(--text); margin-bottom: 6px;
  }
  .card-head p { font-size: .875rem; color: var(--muted); }
  .tabs {
    display: grid; grid-template-columns: 1fr 1fr;
    background: rgba(255,255,255,.04); border-radius: 12px; padding: 4px;
    margin-bottom: 28px;
  }
  .tab {
    padding: 9px; border-radius: 9px; border: none;
    font-family: 'DM Sans', sans-serif; font-size: .875rem; font-weight: 500;
    cursor: pointer; transition: all .2s; color: var(--muted); background: none;
  }
  .tab.active {
    background: var(--accent); color: #fff;
    box-shadow: 0 3px 12px rgba(45,110,246,.4);
  }
  .form-group { margin-bottom: 16px; }
  .form-group label {
    display: block; font-size: .78rem; color: var(--muted);
    margin-bottom: 7px; font-weight: 500; letter-spacing: .3px;
  }
  .input-wrap { position: relative; }
  .input-wrap svg {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: var(--muted); pointer-events: none;
  }
  .form-input {
    width: 100%; background: rgba(255,255,255,.04);
    border: 1px solid var(--border); border-radius: 12px;
    padding: 13px 14px 13px 42px; font-family: 'DM Sans', sans-serif;
    font-size: .9rem; color: var(--text); outline: none;
    transition: border-color .2s, background .2s, box-shadow .2s;
  }
  .form-input::placeholder { color: var(--muted); }
  .form-input:focus {
    border-color: rgba(45,110,246,.6); background: rgba(45,110,246,.05);
    box-shadow: 0 0 0 3px rgba(45,110,246,.12);
  }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .form-footer {
    display: flex; justify-content: flex-end; margin-bottom: 20px;
  }
  .form-footer a {
    font-size: .8rem; color: var(--accent2); text-decoration: none;
    transition: opacity .2s;
  }
  .form-footer a:hover { opacity: .7; }
  .btn-submit {
    width: 100%; padding: 14px;
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
    border: none; border-radius: 13px; cursor: pointer;
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: .95rem;
    color: #fff;
    box-shadow: 0 6px 22px rgba(45,110,246,.4);
    transition: all .25s; position: relative; overflow: hidden;
  }
  .btn-submit::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.1), transparent);
    opacity: 0; transition: opacity .2s;
  }
  .btn-submit:hover {
    transform: translateY(-1px); box-shadow: 0 10px 30px rgba(45,110,246,.55);
  }
  .btn-submit:hover::after { opacity: 1; }
  .section { 
    padding: 100px 80px; 
    position: relative;
    max-width: 100%;
    overflow-x: hidden;
  }
  .section-head {
    text-align: center; margin-bottom: 60px; animation: fadeUp .8s ease both;
  }
  .section-label {
    font-size: .78rem; color: var(--accent2); font-weight: 500;
    letter-spacing: 2px; text-transform: uppercase; margin-bottom: 12px;
  }
  .section-title {
    font-family: 'Syne', sans-serif; font-weight: 800;
    font-size: clamp(2rem, 3.5vw, 2.8rem); letter-spacing: -1px;
    margin-bottom: 14px;
  }
  .section-sub {
    color: var(--muted); font-size: 1rem; max-width: 500px; margin: 0 auto;
  }
  .course-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 20px;
    overflow: hidden; transition: transform .3s, box-shadow .3s, border-color .3s;
    cursor: pointer; position: relative; max-width: 400px; margin: 0 auto;
  }
  .course-card:hover {
    transform: translateY(-6px); box-shadow: 0 20px 60px rgba(0,0,0,.4);
    border-color: rgba(45,110,246,.3);
  }
  .course-thumb {
    height: 220px; position: relative; overflow: hidden;
    background: linear-gradient(135deg, #0d1628, #1a2540);
  }
  .course-thumb img {
    width: 100%; height: 100%; object-fit: cover;
  }
  .course-thumb-label {
    position: absolute; top: 14px; left: 14px;
    background: rgba(45,110,246,.9); color: #fff; font-size: .72rem;
    font-weight: 600; letter-spacing: .5px; padding: 4px 10px;
    border-radius: 100px; text-transform: uppercase;
  }
  .course-body { padding: 24px; }
  .course-title {
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.05rem;
    margin-bottom: 8px; line-height: 1.3;
  }
  .course-desc {
    font-size: .83rem; color: #ffffff; line-height: 1.6; margin-bottom: 20px;
  }
  .course-foot {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 16px; border-top: 1px solid var(--border);
  }
  .course-price {
    font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.2rem;
  }
  .course-price.free { color: var(--accent2); }
  .course-price.paid { color: var(--text); }
  .btn-card {
    padding: 9px 18px; border-radius: 10px; border: none;
    font-family: 'Syne', sans-serif; font-weight: 600; font-size: .82rem;
    cursor: pointer; transition: all .2s; text-decoration: none;
    background: rgba(45,110,246,.15); color: var(--accent2);
    border: 1px solid rgba(45,110,246,.25); display: inline-block;
  }
  .btn-card:hover {
    background: var(--accent); color: #fff; border-color: transparent;
  }
  .cta-banner {
    margin: 0 80px 80px;
    background: linear-gradient(135deg, rgba(45,110,246,.12), rgba(0,200,255,.06));
    border: 1px solid rgba(45,110,246,.2); border-radius: 24px;
    padding: 60px 80px; display: flex; align-items: center;
    justify-content: space-between; gap: 40px; position: relative; overflow: hidden;
  }
  .cta-banner::before {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 280px; height: 280px; border-radius: 50%;
    background: radial-gradient(circle, rgba(45,110,246,.18), transparent 70%);
  }
  .cta-text h2 {
    font-family: 'Syne', sans-serif; font-weight: 800;
    font-size: clamp(1.6rem, 2.5vw, 2.2rem); letter-spacing: -.8px;
    margin-bottom: 8px;
  }
  .cta-text p { color: var(--muted); font-size: .95rem; }
  footer {
    border-top: 1px solid var(--border); padding: 28px 80px;
    display: flex; align-items: center; justify-content: space-between;
    color: var(--muted); font-size: .8rem;
  }
  footer a {
    color: var(--muted); text-decoration: none; transition: color .2s;
  }
  footer a:hover { color: var(--text); }
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(28px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .5; transform: scale(.7); }
  }
  ::-webkit-scrollbar { width: 6px; }
  ::-webkit-scrollbar-track { background: var(--bg); }
  ::-webkit-scrollbar-thumb { background: #2a2d3a; border-radius: 3px; }
  @media (max-width: 1024px) {
    .hero { grid-template-columns: 1fr; }
    .hero-left { padding: 80px 32px 32px; text-align: center; }
    .hero-sub { max-width: 100%; }
    .hero-cta { justify-content: center; }
    .hero-right { padding: 20px 32px 60px; }
    .section { padding: 60px 32px; }
    .cta-banner {
      margin: 0 32px 48px; padding: 32px; flex-direction: column; text-align: center;
    }
    footer { flex-direction: column; gap: 12px; text-align: center; padding: 24px 32px; }
  }
  .mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    color: var(--text);
    z-index: 10000;
  }
  .mobile-menu-toggle svg {
    width: 24px;
    height: 24px;
  }
  .mobile-menu-toggle.active svg line:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
  }
  .mobile-menu-toggle.active svg line:nth-child(2) {
    opacity: 0;
  }
  .mobile-menu-toggle.active svg line:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
  }
  @media (max-width: 640px) {
    html { 
      overflow-x: hidden !important;
      max-width: 100vw !important;
      width: 100% !important;
    }
    body { 
      overflow-x: hidden !important; 
      padding-top: 60px; 
      max-width: 100vw !important;
      width: 100% !important;
    }
    .site-header { padding: 0.75rem; }
    .site-header::after { display: none !important; }
    .header-content {
      padding: 0 16px;
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
    }
    .header-brand img { height: 32px !important; max-width: 140px !important; }
    .brand-icon { width: 32px; height: 32px; font-size: 0.75rem; }
    .header-nav {
      flex-direction: row;
      gap: 0;
      width: auto;
      justify-content: flex-end;
    }
    .header-nav a:not(.btn) { 
      display: none !important;  /* Esconde link "Entrar" no mobile */
    }
    .header-nav .btn { 
      padding: 0.5rem 1rem; 
      font-size: 0.85rem; 
      width: auto;
      text-align: center;
      white-space: nowrap;
    }
    .hero { 
      padding-top: 0; 
      min-height: auto;
      max-width: 100% !important;
      overflow-x: hidden !important;
    }
    .hero-left { 
      padding: 32px 20px 24px; 
      text-align: center; 
      align-items: center;
      display: flex;
      flex-direction: column;
      gap: 20px;
      max-width: 100%;
      overflow-x: hidden;
    }
    .badge { 
      margin: 0 auto;
      font-size: 0.7rem;
      padding: 6px 12px;
      max-width: calc(100% - 40px);
      white-space: normal;
      text-align: center;
    }
    .hero-title { 
      font-size: 1.75rem !important; 
      line-height: 1.2; 
      letter-spacing: -0.5px; 
      text-align: center;
      white-space: normal;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    .hero-title .hl {
      font-size: 1.5rem !important;
      white-space: normal;
      word-wrap: break-word;
      display: inline;
    }
    .hero-sub { 
      font-size: 0.9rem; 
      line-height: 1.6; 
      text-align: center; 
      max-width: 100%; 
    }
    .hero-cta { 
      flex-direction: column; 
      width: 100%; 
      align-items: center;
      display: flex;
      gap: 12px;
    }
    .btn-lg { 
      width: 100% !important; 
      max-width: 100% !important; 
      justify-content: center; 
      padding: 14px 20px; 
      font-size: 0.95rem;
      margin: 0 auto;
      display: block;
      text-align: center;
    }
    .hero-right { 
      padding: 24px 20px 40px;
      display: flex;
      justify-content: center;
      max-width: 100%;
      overflow-x: hidden;
    }
    .login-card { 
      padding: 24px 20px; 
      max-width: 100%; 
      margin: 0 auto;
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .card-head { margin-bottom: 16px; }
    .card-head h2 { font-size: 1.3rem; margin-bottom: 6px; }
    .card-head p { font-size: 0.85rem; }
    .tabs { font-size: 0.9rem; padding: 4px; margin-bottom: 16px; }
    .tab { padding: 10px 16px; }
    .form-group { margin-bottom: 14px; }
    .form-label { font-size: 0.85rem; margin-bottom: 6px; }
    .form-input { padding: 12px 14px; font-size: 0.95rem; }
    .btn-submit { padding: 14px; font-size: 0.95rem; }
    .section { 
      padding: 48px 16px;
      max-width: 100% !important;
      overflow-x: hidden !important;
    }
    .section-head h2 { font-size: 1.75rem; }
    .section-head p { font-size: 0.9rem; }
    .courses-grid { grid-template-columns: 1fr; gap: 16px; }
    .course-card { margin: 0; }
    .course-title { font-size: 1rem; }
    .course-desc { font-size: 0.8rem; }
    .cta-banner { 
      margin: 0 16px 32px; 
      padding: 24px 20px; 
      border-radius: 16px;
      max-width: calc(100% - 32px) !important;
      overflow-x: hidden !important;
    }
    .cta-banner h2 { font-size: 1.5rem; }
    .cta-banner p { font-size: 0.9rem; }
    .form-row { grid-template-columns: 1fr; gap: 12px; }
    footer { padding: 20px 16px; font-size: 0.8rem; }
  }
  .panel { display: none; }
  .panel.active { display: block; }
</style>
</head>
<body>

<header class="site-header">
  <div class="header-content">
    <a href="<?= $courseHref ?>" class="header-brand">
      <?php if ($logoUrl): ?>
        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" style="height: 50px; width: auto; max-width: 250px; object-fit: contain;">
      <?php else: ?>
        <div class="brand-icon">
          <?= htmlspecialchars(strtoupper(substr($companyName ?: 'OC', 0, 2)), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?= htmlspecialchars($companyName ?: 'Curso Online', ENT_QUOTES, 'UTF-8') ?>
      <?php endif; ?>
    </a>
    
    <nav class="header-nav">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="/painel-externo" class="btn" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;">Acessar Painel</a>
      <?php else: ?>
        <a href="#login" onclick="event.preventDefault(); document.getElementById('login-section').scrollIntoView({behavior: 'smooth'}); setTimeout(() => switchTab('login', document.querySelector('[data-tab=login]')), 300);">Entrar</a>
        <?php if ($priceCents > 0): ?>
          <a href="<?= $checkoutHref ?>" class="btn" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;">Comprar por R$ <?= $price ?></a>
        <?php else: ?>
          <a href="#login" class="btn" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;" onclick="event.preventDefault(); document.getElementById('login-section').scrollIntoView({behavior: 'smooth'}); setTimeout(() => switchTab('register', document.querySelector('[data-tab=register]')), 300);">Criar Conta Grátis</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
  </div>
</header>

<div class="page">
  <section class="hero">
    <div class="hero-left">
      <div class="badge">
        <span class="badge-dot"></span>
        Plataforma de Aprendizado
      </div>

      <h1 class="hero-title">
        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
      </h1>
      <h2 class="hero-title" style="margin-top: 8px;">
        <span class="hl"><?= htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8') ?></span>
      </h2>

      <p class="hero-sub">
        <?= htmlspecialchars($desc ?: 'Acesse conteúdo exclusivo e transforme seu conhecimento com especialistas de referência.', ENT_QUOTES, 'UTF-8') ?>
      </p>

      <div class="hero-cta">
        <?php if ($priceCents > 0): ?>
          <a href="<?= $checkoutHref ?>" class="btn-lg primary">
            Comprar por R$ <?= $price ?>
          </a>
        <?php else: ?>
          <a href="<?= $checkoutHref ?>" class="btn-lg primary">
            Cadastrar-se Gratuitamente
          </a>
        <?php endif; ?>
        <?php if (!empty($_SESSION['user_id'])): ?>
          <a href="/painel-externo" class="btn-lg outline">Acessar Meu Painel</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="hero-right">
      <div class="login-card">
        <div class="card-head">
          <h2>Bem-vindo de volta</h2>
          <p>Acesse sua conta para continuar aprendendo</p>
        </div>

        <div class="tabs">
          <button class="tab active" data-tab="login" onclick="switchTab('login', this)">Entrar</button>
          <button class="tab" data-tab="register" onclick="switchTab('register', this)">Cadastrar</button>
        </div>

        <div id="panel-login" class="panel active">
          <form action="<?= $loginAction ?>" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
              <label>E-mail</label>
              <div class="input-wrap">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
                <input class="form-input" type="email" name="email" placeholder="seu@email.com" required>
              </div>
            </div>
            <div class="form-group">
              <label>Senha</label>
              <div class="input-wrap">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input class="form-input" type="password" name="password" placeholder="••••••••" required>
              </div>
            </div>
            <div class="form-footer">
              <a href="<?= $forgotHref ?>">Esqueci minha senha</a>
            </div>
            <button type="submit" class="btn-submit">Entrar na plataforma →</button>
          </form>
        </div>

        <div id="panel-register" class="panel">
          <form action="<?= $registerFreeAction ?>" method="post" id="registerForm">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-row">
              <div class="form-group">
                <label>Nome</label>
                <div class="input-wrap">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <input class="form-input" type="text" name="first_name" id="reg_first_name" placeholder="Seu nome" required>
                </div>
              </div>
              <div class="form-group">
                <label>Sobrenome</label>
                <div class="input-wrap">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <input class="form-input" type="text" name="last_name" id="reg_last_name" placeholder="Sobrenome" required>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>E-mail</label>
              <div class="input-wrap">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
                <input class="form-input" type="email" name="email" id="reg_email" placeholder="seu@email.com" required>
              </div>
            </div>
            <div class="form-group">
              <label>Senha</label>
              <div class="input-wrap">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input class="form-input" type="password" name="password" id="reg_password" placeholder="Mínimo 8 caracteres" required>
              </div>
            </div>
            <input type="hidden" name="phone" value="">
            <input type="hidden" name="cpf" value="">
            <button type="submit" class="btn-submit">Criar conta gratuita →</button>
          </form>
          <?php if ($priceCents > 0): ?>
          <p style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: var(--muted);">
            Quer comprar o curso? <a href="<?= $checkoutHref ?>" style="color: var(--accent2); font-weight: 600; text-decoration: none;">Ir para checkout</a>
          </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if ($imagePath): ?>
  <section class="section" style="padding-top: 0;">
    <div class="section-head">
      <div class="section-label">Conteúdo</div>
      <h2 class="section-title">O Que Você Vai Aprender</h2>
    </div>

    <div class="course-card">
      <div class="course-thumb">
        <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
        <span class="course-thumb-label"><?= $priceCents > 0 ? 'Premium' : 'Gratuito' ?></span>
      </div>
      <div class="course-body">
        <h3 class="course-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
        <p class="course-desc"><?= htmlspecialchars($desc ?: $long, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="course-foot">
          <span class="course-price <?= $priceCents > 0 ? 'paid' : 'free' ?>">
            <?= $priceCents > 0 ? 'R$ ' . $price : 'Grátis' ?>
          </span>
          <a href="<?= $checkoutHref ?>" class="btn-card">Acessar</a>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($long): ?>
  <section class="section" style="padding-top: 40px;">
    <div class="section-head">
      <div class="section-label">Detalhes</div>
      <h2 class="section-title">Sobre o Curso</h2>
    </div>
    <div style="max-width: 700px; margin: 0 auto; color: var(--muted); font-size: 1rem; line-height: 1.8; white-space: pre-line;">
      <?= htmlspecialchars($long, ENT_QUOTES, 'UTF-8') ?>
    </div>
  </section>
  <?php endif; ?>

  <div class="cta-banner">
    <div class="cta-text">
      <h2>Pronto para transformar<br>seu aprendizado?</h2>
      <p><?= $priceCents > 0 ? 'Adquira acesso completo ao curso agora mesmo.' : 'Crie sua conta gratuitamente e comece a aprender hoje.' ?></p>
    </div>
    <a href="<?= $checkoutHref ?>" class="btn-lg primary">
      <?= $priceCents > 0 ? 'Comprar Agora - R$ ' . $price : 'Começar Gratuitamente' ?> →
    </a>
  </div>
</div>

<script>
  function switchTab(tab, el) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
  }

  // Scroll suave para seção de login
  document.addEventListener('DOMContentLoaded', function() {
    const loginLinks = document.querySelectorAll('a[href="#login"]');
    loginLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const loginSection = document.getElementById('login-section');
        if (loginSection) {
          loginSection.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.style.animation = 'fadeUp .7s ease forwards';
        observer.unobserve(e.target);
      }
    });
  }, { threshold: .1 });

  document.querySelectorAll('.course-card, .section-head').forEach(el => {
    el.style.opacity = '0';
    observer.observe(el);
  });
</script>
</body>
</html>

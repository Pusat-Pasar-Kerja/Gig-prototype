<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/user-avatars.php';

$isLoggedIn = !empty($_SESSION['siapkerja_email']) || !empty($_SESSION['username']);

$displayName = (string)($_SESSION['siapkerja_name'] ?? $_SESSION['username'] ?? 'Pengguna');
$userInitials = strtoupper(substr(preg_replace('/\s+/', '', $displayName), 0, 2));
if (strlen($userInitials) < 2) {
    $userInitials = 'US';
}

$siapkerjaEmail = strtolower((string)($_SESSION['siapkerja_email'] ?? ''));
require_once __DIR__ . '/includes/admin-session.php';
gig_admin_session_normalize();
$isAdminAccount = $isLoggedIn && (($_SESSION['role'] ?? '') === 'admin');
$isEmployerAccount = $isLoggedIn && !$isAdminAccount && (
    ($_SESSION['role'] ?? '') === 'employer'
    || in_array($siapkerjaEmail, ['employer@pasker.id', 'calon.employer@pasker.id'], true)
);
$isWorkerAccount = $isLoggedIn && !$isAdminAccount && !$isEmployerAccount && (
    ($_SESSION['role'] ?? '') === 'worker'
    || in_array($siapkerjaEmail, ['theressaz@pasker.id', 'tessa'], true)
);
$showDasborPengelola = $isEmployerAccount || $isAdminAccount;
$profileEmail = (string)($_SESSION['siapkerja_email'] ?? '');
if ($profileEmail === '' && $isLoggedIn) {
    $profileEmail = strtolower(str_replace(' ', '', $displayName)) . '@pasker.id';
}
$profileAvatarRole = ($isEmployerAccount || $isAdminAccount) ? 'employer' : 'worker';
$profileAvatarUrl = $isLoggedIn
    ? (($profileAvatarRole === 'worker')
        ? gig_worker_display_photo()
        : gig_resolve_user_avatar(null, 'employer'))
    : 'https://api.dicebear.com/9.x/notionists/svg?seed=Guest&backgroundColor=dbeafe';

require_once __DIR__ . '/includes/project-vacancies.php';

$activeProjectVacancies = function_exists('gig_project_vacancies') ? gig_project_vacancies() : [];
$gigTrendingJobs = [];
foreach ($activeProjectVacancies as $pv) {
    if (($pv['status'] ?? '') === 'active') {
        $detailTarget = ($role === 'worker')
            ? ('worker-project-detail.php?id=' . urlencode((string)$pv['id']))
            : ('worker-register.php?project_id=' . urlencode((string)$pv['id']));

        $gigTrendingJobs[] = [
            'title' => (string)$pv['title'],
            'company' => (string)($pv['employer'] ?? ($pv['client'] ?? 'Perusahaan Mitra')),
            'loc' => (string)($pv['location'] ?? 'Jakarta Selatan'),
            'pay' => 'Nilai: ' . (string)($pv['budget'] ?? 'Rp 5.000.000'),
            'logo' => strtoupper(substr((string)($pv['employer'] ?? ($pv['client'] ?? 'PR')), 0, 2)),
            'is_gig' => true,
            'url' => $detailTarget,
        ];
    }
}

$trendingJobs = [
    ['title' => 'Accountant', 'company' => 'PT. Surya Indah', 'loc' => 'Jakarta', 'pay' => 'Rp 8–12 jt', 'logo' => 'SI'],
    ['title' => 'Graphic Designer', 'company' => 'CV. Kreatif Digital', 'loc' => 'Bandung', 'pay' => 'Rp 6–9 jt', 'logo' => 'KD'],
    ['title' => 'Software Engineer', 'company' => 'PT. Nusantara Tech', 'loc' => 'Surabaya', 'pay' => 'Rp 12–18 jt', 'logo' => 'NT'],
    ['title' => 'HR Specialist', 'company' => 'PT. Talenta Prima', 'loc' => 'Jakarta', 'pay' => 'Rp 7–10 jt', 'logo' => 'TP'],
];

if (!empty($gigTrendingJobs)) {
    $trendingJobs = array_merge($gigTrendingJobs, $trendingJobs);
}

$categories = [
    ['name' => 'Pengadaan', 'count' => '344', 'icon' => '📦'],
    ['name' => 'Pemasaran', 'count' => '340', 'icon' => '📣'],
    ['name' => 'Akuntansi', 'count' => '450', 'icon' => '📊'],
    ['name' => 'Manufaktur', 'count' => '1.120', 'icon' => '🏭'],
    ['name' => 'TI & Software', 'count' => '860', 'icon' => '💻'],
    ['name' => 'Desain & Kreatif', 'count' => '520', 'icon' => '🎨'],
    ['name' => 'Konstruksi', 'count' => '680', 'icon' => '🏗️'],
    ['name' => 'Logistik', 'count' => '410', 'icon' => '🚚'],
    ['name' => 'Perhotelan', 'count' => '290', 'icon' => '🏨'],
    ['name' => 'Kesehatan', 'count' => '375', 'icon' => '⚕️'],
];

$testimonials = [
    ['name' => 'Ariffudin', 'role' => 'Software Developer', 'text' => 'Karirhub memudahkan saya menemukan lowongan yang sesuai kompetensi dan lokasi.'],
    ['name' => 'Siti Khodijah', 'role' => 'HR Officer', 'text' => 'Proses lamaran jelas dan cepat. Saya bisa memantau status dari satu platform.'],
    ['name' => 'Budi Santoso', 'role' => 'Marketing Lead', 'text' => 'Antarmuka modern dan banyak pilihan perusahaan terpercaya di dalam negeri.'],
    ['name' => 'Dewi Lestari', 'role' => 'UI/UX Designer', 'text' => 'Fitur pencarian lokasi sangat membantu untuk pekerja remote dan hybrid.'],
    ['name' => 'Rizky Pratama', 'role' => 'Data Analyst', 'text' => 'Rekomendasi lowongan trending relevan dengan profil SIAPkerja saya.'],
    ['name' => 'Maya Anggraini', 'role' => 'Admin Operasional', 'text' => 'Platform rekrutmen terbaik dari ekosistem Kemnaker yang saya percaya.'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Karirhub · Beranda</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    :root {
      --blue: #1657c1;
      --blue-dark: #0f3d8c;
      --blue-light: #e8f0fe;
      --sky: #18b5ea;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
      --bg: #f8fafc;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: var(--text); background: #fff; line-height: 1.5; }
    button:disabled,
    input[type="button"]:disabled,
    input[type="submit"]:disabled,
    [role="button"][aria-disabled="true"] {
      background: #cbd5e1 !important;
      color: #64748b !important;
      border-color: #cbd5e1 !important;
      box-shadow: none !important;
      cursor: not-allowed !important;
      opacity: 1 !important;
      pointer-events: none;
    }
    a { color: inherit; text-decoration: none; }
    .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

    /* Navbar — match Karirhub reference */
    .topbar {
      position: sticky; top: 0; z-index: 50;
      background: #fff;
    }
    .nav-inner {
      height: 72px; display: flex; align-items: center; justify-content: space-between; gap: 20px;
      max-width: 1320px; margin: 0 auto; padding: 0 28px;
    }
    .nav-left { display: flex; align-items: center; gap: 20px; flex: 1; min-width: 0; }
    .nav-hamburger {
      width: 40px; height: 40px; border: none; background: transparent; cursor: pointer;
      display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; padding: 0;
    }
    .nav-hamburger span {
      display: block; width: 22px; height: 2px; background: #1e293b; border-radius: 2px;
    }
    .brand { display: flex; align-items: center; gap: 10px; }
    .brand-icon { width: 40px; height: 40px; flex-shrink: 0; }
    .brand-text { line-height: 1.15; }
    .brand-text .name {
      font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.03em;
    }
    .brand-text .sub { font-size: 0.68rem; font-weight: 600; color: #64748b; margin-top: 1px; }

    .nav-links {
      display: flex; align-items: center; gap: 24px; flex-shrink: 0;
    }
    .nav-links a {
      font-size: 0.95rem; font-weight: 600; color: #334155; white-space: nowrap;
      display: inline-flex; align-items: center; gap: 4px;
    }
    .nav-links a.nav-jobs { color: #1657c1; }
    .nav-links a:hover { color: var(--blue); }
    .nav-caret { color: #94a3b8; flex-shrink: 0; }

    .nav-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .btn-nav-outline {
      padding: 10px 22px; border-radius: 999px; border: 1px solid #cbd5e1;
      background: #fff; color: #0f172a; font-size: 0.9rem; font-weight: 700; font-family: inherit;
      cursor: pointer; line-height: 1;
    }
    .btn-nav-outline:hover { background: #f8fafc; }
    .btn-nav-solid {
      display: inline-flex; align-items: center; justify-content: center;
      padding: 10px 26px; border-radius: 999px; border: none;
      background: #0ea5e9; color: #fff; font-size: 0.9rem; font-weight: 700; font-family: inherit;
      cursor: pointer; line-height: 1; min-width: 88px; text-decoration: none;
    }
    .btn-nav-solid:hover { background: #0284c7; color: #fff; }
    .btn-nav-solid.is-user { padding: 10px 18px; letter-spacing: 0.02em; cursor: default; }

    .nav-user-zone {
      position: relative;
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .dasbor-trigger {
      border: none;
      background: transparent;
      font: inherit;
      font-size: 0.92rem;
      font-weight: 600;
      color: #334155;
      cursor: pointer;
      padding: 6px 0;
    }
    .dasbor-trigger:hover { color: var(--blue); }
    .nav-avatar-btn {
      border: none;
      padding: 0;
      background: transparent;
      cursor: pointer;
      border-radius: 50%;
      line-height: 0;
    }
    .nav-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #e2e8f0;
      background: #f1f5f9;
      display: block;
    }
    .profile-panel {
      display: none;
      position: absolute;
      top: calc(100% + 14px);
      right: 0;
      width: min(320px, calc(100vw - 32px));
      max-height: min(440px, calc(100vh - 96px));
      background: #fff;
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 20px 50px rgba(15, 23, 42, 0.14);
      z-index: 110;
      overflow: hidden;
    }
    .profile-panel.open {
      display: flex;
      flex-direction: column;
    }
    .profile-panel-head {
      display: flex;
      gap: 12px;
      align-items: center;
      padding: 16px;
      border-bottom: 1px solid #f1f5f9;
      flex-shrink: 0;
      background: #fff;
    }
    .profile-panel-head img {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      border: 1px solid #e2e8f0;
    }
    .profile-panel-head .name {
      font-size: 0.88rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.35;
    }
    .profile-panel-head .email {
      font-size: 0.78rem;
      color: #64748b;
      margin-top: 2px;
      word-break: break-word;
    }
    .profile-menu {
      list-style: none;
      padding: 6px 0;
      margin: 0;
      overflow-y: auto;
      max-height: min(350px, calc(100vh - 180px));
      overscroll-behavior: contain;
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
    }
    .profile-menu::-webkit-scrollbar {
      width: 6px;
    }
    .profile-menu::-webkit-scrollbar-track {
      background: transparent;
    }
    .profile-menu::-webkit-scrollbar-thumb {
      background-color: #cbd5e1;
      border-radius: 999px;
    }
    .profile-menu::-webkit-scrollbar-thumb:hover {
      background-color: #94a3b8;
    }
    .profile-menu li { border-bottom: 1px solid #f1f5f9; }
    .profile-menu li:last-child { border-bottom: none; }
    .profile-menu a, .profile-menu span {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      font-size: 0.88rem;
      font-weight: 600;
      color: #334155;
    }
    .profile-menu span { cursor: default; opacity: 0.85; }
    .profile-menu a:hover { background: #f8fafc; color: #0f172a; }
    .profile-menu .menu-ico {
      width: 18px;
      display: inline-flex;
      justify-content: center;
      color: #475569;
      flex-shrink: 0;
    }
    .profile-menu a.logout { color: #0f172a; }
    .dasbor-panel {
      display: none;
      position: absolute;
      top: calc(100% + 14px);
      right: 0;
      width: min(400px, calc(100vw - 32px));
      max-height: calc(100vh - 96px);
      overflow-y: auto;
      overscroll-behavior: contain;
      background: #fff;
      border-radius: 16px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 20px 50px rgba(15, 23, 42, 0.14);
      padding: 14px;
      z-index: 120;
    }
    .dasbor-panel.open { display: block; }
    .dasbor-card {
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
    }
    .dasbor-card:last-child { margin-bottom: 0; }
    .dasbor-card-head {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      margin-bottom: 10px;
    }
    .dasbor-card-icon {
      width: 28px;
      height: 28px;
      border-radius: 8px;
      background: #e0f2fe;
      color: #0284c7;
      display: grid;
      place-items: center;
      flex-shrink: 0;
    }
    .dasbor-card h3 {
      font-size: 0.92rem;
      font-weight: 800;
      line-height: 1.35;
      margin-bottom: 6px;
      color: #0f172a;
    }
    .dasbor-card p {
      font-size: 0.78rem;
      color: #64748b;
      line-height: 1.5;
      margin-bottom: 12px;
    }
    .dasbor-card-btn {
      display: inline-block;
      background: #0ea5e9;
      color: #fff;
      font-size: 0.82rem;
      font-weight: 700;
      padding: 8px 14px;
      border-radius: 8px;
      text-decoration: none;
      position: relative;
      z-index: 1;
    }
    .dasbor-card-btn:hover { background: #0284c7; color: #fff; }

    /* Hero — CSS blobs only, no photos */
    .hero {
      position: relative; overflow: hidden; background: #fff;
      padding: 28px 0 56px; min-height: 340px;
    }
    .hero-blob {
      position: absolute; pointer-events: none; z-index: 0;
    }
    .hero-blob--left {
      left: -120px; top: 20px; width: 340px; height: 380px;
      background: linear-gradient(145deg, #7dd3fc 0%, #38bdf8 35%, #0ea5e9 70%, #0369a1 100%);
      border-radius: 42% 58% 55% 45% / 48% 42% 58% 52%;
      transform: rotate(-8deg);
    }
    .hero-blob--right {
      right: -100px; top: 30px; width: 320px; height: 360px;
      background: linear-gradient(200deg, #67e8f9 0%, #22d3ee 30%, #0891b2 65%, #155e75 100%);
      border-radius: 58% 42% 45% 55% / 52% 48% 52% 48%;
      transform: rotate(12deg);
    }
    .hero-inner {
      position: relative; z-index: 1; text-align: center; padding-top: 8px;
    }
    .hero-badge {
      display: inline-block; margin-bottom: 20px;
      padding: 6px 16px; border-radius: 999px;
      background: #e0f2fe; color: #0369a1;
      font-size: 0.82rem; font-weight: 700;
    }
    .hero-inner h1 {
      font-size: clamp(1.75rem, 3.2vw, 2.5rem); font-weight: 800; line-height: 1.25;
      color: #0f172a; letter-spacing: -0.03em; max-width: 640px; margin: 0 auto 36px;
    }
    .search-shell {
      max-width: 920px; margin: 0 auto; padding: 0 16px;
    }
    .search-bar {
      display: flex; align-items: stretch; background: #fff;
      border-radius: 16px; box-shadow: 0 8px 32px rgba(15, 23, 42, 0.1);
      border: 1px solid #f1f5f9; overflow: hidden; min-height: 64px;
    }
    .search-segment {
      flex: 1; display: flex; align-items: center; gap: 12px;
      padding: 0 20px; min-width: 0;
    }
    .search-segment--job { flex: 1.35; }
    .search-segment--loc { flex: 0.85; border-left: 1px solid #e2e8f0; }
    .search-segment input {
      border: none; outline: none; width: 100%; font: inherit; font-size: 0.92rem;
      color: #334155; background: transparent;
    }
    .search-segment input::placeholder { color: #94a3b8; font-weight: 500; }
    .search-icon { flex-shrink: 0; color: #64748b; }
    .search-btn {
      flex-shrink: 0; align-self: center; margin: 8px 8px 8px 0;
      background: #7dd3fc; color: #fff; border: none; border-radius: 12px;
      padding: 0 28px; min-height: 48px; font-weight: 700; font-size: 0.95rem;
      cursor: pointer; font-family: inherit; white-space: nowrap;
    }
    .search-btn:hover { background: #38bdf8; }

    /* Trending */
    .trending {
      background: linear-gradient(135deg, #1d6fd8, #1657c1);
      color: #fff; border-radius: 24px 24px 0 0; margin-top: 0; padding: 36px 0 40px;
    }
    .trending-grid { display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: start; }
    .trending h2 { font-size: 1.45rem; font-weight: 800; margin-bottom: 10px; }
    .trending p { opacity: .92; font-size: 0.9rem; margin-bottom: 18px; }
    .btn-white {
      display: inline-block; background: #fff; color: var(--blue); font-weight: 700;
      padding: 10px 16px; border-radius: 10px; font-size: 0.88rem;
    }
    .job-scroll { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 8px; scroll-snap-type: x mandatory; }
    .job-card {
      flex: 0 0 240px; scroll-snap-align: start; background: #fff; color: var(--text);
      border-radius: 14px; padding: 16px; box-shadow: 0 8px 24px rgba(0,0,0,.12);
    }
    .job-logo {
      width: 40px; height: 40px; border-radius: 10px; background: var(--blue-light);
      color: var(--blue); display: grid; place-items: center; font-weight: 800; font-size: 0.75rem; margin-bottom: 10px;
    }
    .job-card h3 { font-size: 0.95rem; margin-bottom: 4px; }
    .job-card .meta { font-size: 0.78rem; color: var(--muted); margin-bottom: 8px; }
    .job-card .pay { font-size: 0.82rem; font-weight: 700; color: var(--blue); margin-bottom: 12px; }
    .job-card .detail {
      display: inline-block; font-size: 0.78rem; font-weight: 700; color: var(--blue);
      border: 1px solid #bfdbfe; padding: 6px 10px; border-radius: 8px;
    }

    /* Sections */
    section.block { padding: 48px 0; }
    section.block.alt { background: var(--bg); }
    .section-title { font-size: 1.35rem; font-weight: 800; margin-bottom: 20px; text-align: center; }
    .cat-grid {
      display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;
    }
    .cat-card {
      background: #fff; border: 1px solid var(--border); border-radius: 12px;
      padding: 16px 12px; text-align: center; transition: box-shadow .2s;
    }
    .cat-card:hover { box-shadow: var(--shadow, 0 8px 20px rgba(15,23,42,.06)); }
    .cat-card .ico { font-size: 1.4rem; margin-bottom: 8px; }
    .cat-card .name { font-size: 0.82rem; font-weight: 700; margin-bottom: 4px; }
    .cat-card .count { font-size: 0.72rem; color: var(--muted); }
    .section-links { text-align: center; margin-top: 18px; font-size: 0.88rem; }
    .section-links a { color: var(--blue); font-weight: 700; margin: 0 8px; }

    .split {
      display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: center;
    }
    .split img {
      width: 100%; border-radius: 16px; aspect-ratio: 4/3; object-fit: cover;
      background: #e2e8f0;
    }
    .eyebrow { color: var(--blue); font-weight: 700; font-size: 0.82rem; margin-bottom: 8px; }
    .split h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: 12px; line-height: 1.25; }
    .split p { color: var(--muted); margin-bottom: 18px; font-size: 0.92rem; }
    .btn-primary {
      display: inline-block; background: var(--blue); color: #fff; font-weight: 700;
      padding: 11px 18px; border-radius: 10px; font-size: 0.88rem;
    }
    .features { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: center; }
    .feature-list { display: grid; gap: 14px; }
    .feature-item { display: flex; gap: 12px; align-items: flex-start; }
    .feature-item .dot {
      width: 36px; height: 36px; border-radius: 10px; background: var(--blue-light);
      color: var(--blue); display: grid; place-items: center; font-weight: 800; flex-shrink: 0;
    }
    .loc-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .loc-card {
      background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 24px;
    }
    .loc-card h3 { font-size: 1.05rem; font-weight: 800; margin-bottom: 12px; line-height: 1.35; }
    .test-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .test-card {
      background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 18px;
    }
    .test-card strong { display: block; margin-bottom: 2px; }
    .test-card span { font-size: 0.78rem; color: var(--muted); display: block; margin-bottom: 8px; }
    .test-card p { font-size: 0.85rem; color: #334155; }

    .cta-band {
      background: linear-gradient(135deg, #1a5ec4, #2777f2);
      color: #fff; border-radius: 20px; padding: 36px 24px; text-align: center; margin: 24px 0 0;
    }
    .cta-band h2 { font-size: 1.4rem; margin-bottom: 14px; }
    .cta-band .btn-white { color: var(--blue-dark); }

    footer.site-footer {
      background: #fff; border-top: 1px solid var(--border); padding: 40px 0 24px; margin-top: 40px;
    }
    .footer-grid {
      display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 24px; margin-bottom: 24px;
    }
    .footer-grid h4 { font-size: 0.9rem; margin-bottom: 10px; }
    .footer-grid p, .footer-grid li { font-size: 0.82rem; color: var(--muted); list-style: none; }
    .store-btns { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
    .store-btns span {
      border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 0.75rem; font-weight: 700;
    }
    .social { display: flex; gap: 8px; margin-top: 10px; }
    .social a {
      width: 32px; height: 32px; border-radius: 8px; background: var(--bg);
      display: grid; place-items: center; font-size: 0.7rem; font-weight: 800; color: var(--muted);
    }
    .copyright { text-align: center; font-size: 0.78rem; color: var(--muted); padding-top: 16px; border-top: 1px solid var(--border); }

    @media (max-width: 1024px) {
      .nav-links { display: none; }
      .hero-blob--left { width: 200px; left: -80px; opacity: 0.85; }
      .hero-blob--right { width: 180px; right: -70px; opacity: 0.85; }
      .search-bar { flex-direction: column; border-radius: 16px; }
      .search-segment--loc { border-left: none; border-top: 1px solid #e2e8f0; }
      .search-btn { margin: 0 8px 8px; width: calc(100% - 16px); }
    }
    @media (max-width: 960px) {
      .trending-grid { grid-template-columns: 1fr; }
      .cat-grid { grid-template-columns: repeat(2, 1fr); }
      .split, .features, .loc-cards, .test-grid, .footer-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <header class="topbar">
    <div class="nav-inner">
      <div class="nav-left">
        <button type="button" class="nav-hamburger" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
        <a href="karirhub-home.php" class="brand">
          <svg class="brand-icon" viewBox="0 0 40 40" fill="none" aria-hidden="true">
            <path d="M8 8 H22 Q28 8 28 14 V20 L16 32 H8 Z" fill="#18b5ea"/>
            <path d="M22 8 L32 8 L32 18 L24 26 H16 L22 18 Z" fill="#0ea5e9" opacity="0.85"/>
            <circle cx="30" cy="10" r="4" fill="#38bdf8"/>
          </svg>
          <div class="brand-text">
            <div class="name">Karirhub</div>
            <div class="sub">oleh Kemnaker</div>
          </div>
        </a>
        <nav class="nav-links" aria-label="Navigasi utama">
          <a href="#lowongan" class="nav-jobs">Lowongan Pekerjaan <svg class="nav-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></a>
          <a href="gig-workers-go.php">Gig Workers</a>
          <a href="pilih-jenis-pemberi-kerja.php">Pemberi Kerja</a>
          <a href="#lowongan">Jobfair</a>
        </nav>
      </div>
      <div class="nav-actions">
        <?php if (!$isLoggedIn): ?>
          <a href="pilih-pendaftaran.php" class="btn-nav-outline">Daftar</a>
          <a href="siapkerja-login.php?redirect=karirhub-home" class="btn-nav-solid">Masuk</a>
        <?php else: ?>
          <div class="nav-user-zone" id="navUserZone">
            <?php if ($showDasborPengelola): ?>
              <button type="button" class="dasbor-trigger" id="dasborTrigger" aria-expanded="false" aria-controls="dasborPanel">
                Dasbor Pengelola
              </button>
              <div class="dasbor-panel" id="dasborPanel" role="dialog" aria-label="Pilih dasbor pengelola">
                <?php if ($isAdminAccount): ?>
                <article class="dasbor-card">
                  <div class="dasbor-card-head">
                    <span class="dasbor-card-icon" aria-hidden="true">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </span>
                    <div>
                      <h3>Dasbor Admin</h3>
                      <p>Verifikasi pendaftaran Gig Worker, pemberi kerja, dan pengajuan lowongan proyek.</p>
                      <a href="admin-dashboard-go.php" class="dasbor-card-btn">Akses Dasbor</a>
                    </div>
                  </div>
                </article>
                <?php endif; ?>
                <?php if ($isEmployerAccount): ?>
                <article class="dasbor-card">
                  <div class="dasbor-card-head">
                    <span class="dasbor-card-icon" aria-hidden="true">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </span>
                    <div>
                      <h3>Dasbor Pemberi Kerja</h3>
                      <p>Akses untuk mengatur dan mengelola lowongan pada semua perusahaan yang terdaftar.</p>
                      <a href="dashboard-pemberi-kerja.php" class="dasbor-card-btn">Akses Dasbor</a>
                    </div>
                  </div>
                </article>
                <article class="dasbor-card">
                  <div class="dasbor-card-head">
                    <span class="dasbor-card-icon" aria-hidden="true">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    </span>
                    <div>
                      <h3>Dasbor Pemberi Kerja Gig Workers</h3>
                      <p>Pengelolaan lowongan, dan manajemen tenaga kerja pada perusahaan Anda.</p>
                      <a href="employer-gig-dashboard-go.php" class="dasbor-card-btn">Akses Dasbor</a>
                    </div>
                  </div>
                </article>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <button type="button" class="nav-avatar-btn" id="profileTrigger" aria-expanded="false" aria-controls="profilePanel" aria-label="Menu profil">
              <div class="nav-avatar" style="width:40px;height:40px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;flex-shrink:0;">
                <?php echo htmlspecialchars(strtoupper(substr($displayName !== '' ? $displayName : 'T', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
              </div>
            </button>
            <div class="profile-panel" id="profilePanel" role="dialog" aria-label="Menu profil pengguna">
              <div class="profile-panel-head">
                <div style="width:48px;height:48px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.15rem;flex-shrink:0;">
                  <?php echo htmlspecialchars(strtoupper(substr($displayName !== '' ? $displayName : 'T', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div>
                  <div class="name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="email"><?php echo htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
              <ul class="profile-menu">
                <?php if ($isWorkerAccount || ($_SESSION['role'] ?? '') === 'worker'): ?>
                  <li><a href="worker-bursa.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; width: 100%;"><span class="menu-ico">🔍</span> Cari Proyek</a></li>
                <?php endif; ?>
                <li><span><span class="menu-ico">💼</span> Lamaran Kerja</span></li>
                <li><span><span class="menu-ico">🎓</span> Pelatihan Saya</span></li>
                <li><span><span class="menu-ico">🛡</span> Sertifikasi</span></li>
                <?php if ($isWorkerAccount): ?>
                  <li><a href="worker-profile.php?id=tessa" style="color: inherit; text-decoration: none; display: flex; align-items: center; width: 100%;"><span class="menu-ico">👤</span> Profil Gig Worker</a></li>
                <?php else: ?>
                  <li><span><span class="menu-ico">👤</span> Profil</span></li>
                <?php endif; ?>
                <li><a href="profil.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; width: 100%;"><span class="menu-ico">👤</span> Profil SIAPKerja</a></li>
                <li><span><span class="menu-ico">⚙</span> Pengaturan</span></li>
                <li><span><span class="menu-ico">?</span> Bantuan</span></li>
                <li><a class="logout" href="karirhub-logout.php"><span class="menu-ico">⎋</span> Keluar</a></li>
              </ul>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <section class="hero">
    <div class="hero-blob hero-blob--left" aria-hidden="true"></div>
    <div class="hero-blob hero-blob--right" aria-hidden="true"></div>
    <div class="hero-inner">
      <span class="hero-badge">Karirhub oleh Kemnaker</span>
      <h1>Dapatkan pekerjaan dari seluruh dunia</h1>
      <div class="search-shell">
        <form class="search-bar" action="#lowongan" method="get">
          <label class="search-segment search-segment--job">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" placeholder="Cari pekerjaan yang kamu inginkan" />
            <svg class="nav-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </label>
          <label class="search-segment search-segment--loc">
            <input type="text" name="loc" placeholder="Pilih Lokasi" />
            <svg class="nav-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="7 11 12 6 17 11"/><polyline points="7 13 12 18 17 13"/></svg>
          </label>
          <button type="submit" class="search-btn">Cari Lowongan</button>
        </form>
      </div>
    </div>
  </section>

  <section class="trending" id="lowongan">
    <div class="container trending-grid">
      <div>
        <h2>Lowongan kerja yang sedang trending</h2>
        <p>Temukan peluang karier terbaru dari perusahaan terpercaya di seluruh Indonesia.</p>
        <a class="btn-white" href="#lowongan">Lihat selengkapnya</a>
      </div>
      <div class="job-scroll">
        <?php foreach ($trendingJobs as $job): ?>
          <?php
            $cardUrl = !empty($job['url']) ? $job['url'] : '#lowongan';
          ?>
          <article class="job-card" onclick="window.location.href='<?php echo htmlspecialchars($cardUrl, ENT_QUOTES, 'UTF-8'); ?>'" style="cursor:pointer;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
              <div class="job-logo"><?php echo htmlspecialchars($job['logo'], ENT_QUOTES, 'UTF-8'); ?></div>
              <?php if (!empty($job['is_gig'])): ?>
                <span style="font-size:0.68rem;font-weight:800;color:#2563eb;background:#eff6ff;border:1px solid #bfdbfe;padding:2px 8px;border-radius:10px;">Proyek / Gig</span>
              <?php endif; ?>
            </div>
            <h3 style="margin-top:8px;"><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="meta"><?php echo htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($job['loc'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="pay" style="<?php echo !empty($job['is_gig']) ? 'color:#059669;font-weight:800;' : ''; ?>"><?php echo htmlspecialchars($job['pay'], ENT_QUOTES, 'UTF-8'); ?></div>
            <a href="<?php echo htmlspecialchars($cardUrl, ENT_QUOTES, 'UTF-8'); ?>" class="detail" style="text-decoration:none;display:inline-block;">Lihat Detail &rarr;</a>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="block alt">
    <div class="container">
      <h2 class="section-title">Bidang pekerjaan terpopuler</h2>
      <div class="cat-grid">
        <?php foreach ($categories as $cat): ?>
          <a href="#lowongan" class="cat-card">
            <div class="ico"><?php echo $cat['icon']; ?></div>
            <div class="name"><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="count"><?php echo htmlspecialchars($cat['count'], ENT_QUOTES, 'UTF-8'); ?> lowongan</div>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="section-links">
        <a href="#lowongan">Lihat semua bidang pekerjaan</a>
        ·
        <a href="#lowongan">Mulai pencarian</a>
      </div>
    </div>
  </section>

  <section class="block">
    <div class="container split">
      <img src="https://images.unsplash.com/photo-1521737711867-e3b97375c902?w=800&h=600&fit=crop" alt="" />
      <div>
        <div class="eyebrow">Info Hubungan Industrial</div>
        <h2>Hadiri job fair kami dan temukan peluang karirmu!</h2>
        <p>Bertemu langsung dengan perusahaan mitra Kemnaker dan dapatkan informasi rekrutmen terkini.</p>
        <a class="btn-primary" href="#lowongan">Lihat Jadwal Job Fair</a>
      </div>
    </div>
  </section>

  <section class="block alt">
    <div class="container features">
      <div>
        <h2 class="section-title" style="text-align:left;margin-bottom:12px;">Platform rekrutmen terbaik</h2>
        <p style="color:var(--muted);font-size:0.92rem;">Karirhub terhubung dengan ekosistem SIAPkerja untuk mempermudah pencarian kerja dan rekrutmen yang aman.</p>
        <a class="btn-primary" href="pilih-pendaftaran.php" style="margin-top:16px;">Daftar Karirhub sekarang</a>
      </div>
      <div class="feature-list">
        <div class="feature-item"><span class="dot">1</span><div><strong>Proses cepat & transparan</strong><p style="font-size:0.85rem;color:var(--muted);">Lamar lowongan dengan profil SIAPkerja yang terverifikasi.</p></div></div>
        <div class="feature-item"><span class="dot">2</span><div><strong>Pilihan perusahaan beragam</strong><p style="font-size:0.85rem;color:var(--muted);">Dari UMKM hingga korporasi besar di berbagai sektor.</p></div></div>
        <div class="feature-item"><span class="dot">3</span><div><strong>Sistem terintegrasi & aman</strong><p style="font-size:0.85rem;color:var(--muted);">Data pelamar dilindungi sesuai standar Kemnaker.</p></div></div>
      </div>
    </div>
  </section>

  <section class="block">
    <div class="container loc-cards">
      <article class="loc-card">
        <h3>Temukan banyak kesempatan karir di dalam negeri</h3>
        <a class="btn-primary" href="#lowongan">Mulai mencari</a>
      </article>
      <article class="loc-card">
        <h3>Jelajahi dunia dan temukan karir impianmu di luar negeri</h3>
        <a class="btn-primary" href="#lowongan">Mulai mencari</a>
      </article>
    </div>
  </section>

  <section class="block alt">
    <div class="container">
      <h2 class="section-title">Yang mereka katakan tentang Karirhub</h2>
      <div class="test-grid">
        <?php foreach ($testimonials as $t): ?>
          <article class="test-card">
            <strong><?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span><?php echo htmlspecialchars($t['role'], ENT_QUOTES, 'UTF-8'); ?></span>
            <p><?php echo htmlspecialchars($t['text'], ENT_QUOTES, 'UTF-8'); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="block">
    <div class="container">
      <div class="cta-band">
        <h2>Temukan pekerjaan yang diinginkan</h2>
        <a class="btn-white" href="#lowongan">Cari lowongan sekarang</a>
      </div>
    </div>
  </section>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <h4>Unduh aplikasi dan jangan lewatkan</h4>
          <div class="store-btns">
            <span>App Store</span>
            <span>Google Play</span>
          </div>
        </div>
        <div>
          <h4>Kementerian Ketenagakerjaan RI</h4>
          <p>Jl. Jenderal Gatot Subroto Kav. 4, Jakarta 12710</p>
          <p>Telp: (021) 5255737 · Email: call120@kemnaker.go.id</p>
        </div>
        <div>
          <h4>Ikuti kami</h4>
          <div class="social">
            <a href="#">YT</a><a href="#">IG</a><a href="#">FB</a><a href="#">X</a>
          </div>
        </div>
      </div>
      <p class="copyright">© <?php echo date('Y'); ?> Karirhub · Ekosistem SIAPkerja Kemnaker</p>
    </div>
  </footer>

  <script>
    (function () {
      var zone = document.getElementById('navUserZone');
      if (!zone) return;

      var dasborTrigger = document.getElementById('dasborTrigger');
      var dasborPanel = document.getElementById('dasborPanel');
      var profileTrigger = document.getElementById('profileTrigger');
      var profilePanel = document.getElementById('profilePanel');

      function closeAll() {
        if (dasborPanel) {
          dasborPanel.classList.remove('open');
          if (dasborTrigger) dasborTrigger.setAttribute('aria-expanded', 'false');
        }
        if (profilePanel) {
          profilePanel.classList.remove('open');
          if (profileTrigger) profileTrigger.setAttribute('aria-expanded', 'false');
        }
      }

      if (dasborTrigger && dasborPanel) {
        dasborTrigger.addEventListener('click', function (e) {
          e.stopPropagation();
          var willOpen = !dasborPanel.classList.contains('open');
          closeAll();
          if (willOpen) {
            dasborPanel.classList.add('open');
            dasborTrigger.setAttribute('aria-expanded', 'true');
          }
        });
      }

      if (profileTrigger && profilePanel) {
        profileTrigger.addEventListener('click', function (e) {
          e.stopPropagation();
          var willOpen = !profilePanel.classList.contains('open');
          closeAll();
          if (willOpen) {
            profilePanel.classList.add('open');
            profileTrigger.setAttribute('aria-expanded', 'true');
          }
        });
      }

      document.addEventListener('click', function (e) {
        if (!zone.contains(e.target)) closeAll();
      });
    })();
  </script>
</body>
</html>

<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/user-avatars.php';
require_once __DIR__ . '/includes/katalog-store.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-offers.php';

$id = trim((string)($_GET['id'] ?? ''));
$service = null;

if (!empty($id)) {
    $service = gig_katalog_find($id);
}

// Fallback to first available seed if not found
if (!$service) {
    $all = gig_katalog_load_all();
    $service = $all[0] ?? null;
}

if (!$service) {
    echo "Katalog jasa tidak ditemukan.";
    exit;
}

// Find actual worker ID in the system
$workerId = 'tessa';
if (!empty($service['worker_id'])) {
    $found = gig_find_worker((string)$service['worker_id']);
    if ($found) {
        $workerId = (string)$found['id'];
    } elseif (!empty($service['worker_name'])) {
        $foundByName = gig_find_worker((string)$service['worker_name']);
        if ($foundByName) {
            $workerId = (string)$foundByName['id'];
        }
    }
}

// Role and active vacancies detection for Company / Employer
$role = (string)($_SESSION['role'] ?? '');
$employerUser = (string)($_SESSION['employer_user'] ?? '');
$isEmployer = ($role === 'employer' || !empty($employerUser));

$activeVacancies = [];
$vacancyOfferCounts = [];
$alreadyOfferedMap = [];
if ($isEmployer) {
    $vacancies = gig_project_vacancies();
    $activeVacancies = array_values(array_filter($vacancies, fn($v) => $v['status'] === 'active'));
    foreach ($activeVacancies as $v) {
        $vacancyOfferCounts[$v['id']] = gig_count_vacancy_offers($v['id']);
    }
    $employerKey = !empty($employerUser) ? $employerUser : ($_SESSION['username'] ?? '');
    $alreadyOfferedMap = gig_employer_offered_map($employerKey);
}

// Load related services (excluding current)
$allServices = gig_katalog_load_all();
$relatedServices = array_values(array_filter($allServices, function ($s) use ($service) {
    return $s['id'] !== $service['id'];
}));
$relatedServices = array_slice($relatedServices, 0, 4);


$gallery = $service['gallery'] ?? [];
if (empty($gallery)) {
    $gallery = [$service['thumbnail'] ?: 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80'];
}

// Sample Reviews from Companies / Project Clients
$sampleReviews = [
    [
        'name' => 'PT Sinar Mulia Retail (Hendro W.)',
        'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&auto=format&fit=crop&q=80',
        'rating' => 5,
        'date' => '3 hari yang lalu',
        'comment' => 'Pengerjaan deliverables proyek sangat cepat dan rapi sesuai arahan brand guideline perusahaan kami. Hasil master file vector langsung siap digunakan oleh tim marketing. Sangat memuaskan!'
    ],
    [
        'name' => 'Nusantara Creative Labs (Siti N.)',
        'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&auto=format&fit=crop&q=80',
        'rating' => 5,
        'date' => '1 minggu yang lalu',
        'comment' => 'Deliverables Brand Guidelines Book yang diserahkan sangat komprehensif. Mulai dari sistem warna korporat hingga standar aplikasi mockup. Kerja terstruktur dan tepat waktu.'
    ],
    [
        'name' => 'PT Mandiri Agro Lestari (Bambang P.)',
        'avatar' => 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=100&auto=format&fit=crop&q=80',
        'rating' => 5,
        'date' => '2 minggu yang lalu',
        'comment' => 'Kualitas pengerjaan sangat profesional dengan standar industri. Berkas diserahkan lengkap di dashboard dan siap untuk diaplikasikan ke kemasan produk resmi kami.'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?> | KarirHub Jasa</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary-blue: #0284c7;
      --brand-blue: #1657c1;
      --accent-yellow: #f59e0b;
      --bg-page: #f8fafc;
      --border-color: #e2e8f0;
      --text-main: #0f172a;
      --text-muted: #64748b;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      background: var(--bg-page);
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      color: var(--text-main);
      line-height: 1.6;
    }

    /* Top Navbar */
    .fw-navbar {
      background: #ffffff;
      border-bottom: 1px solid var(--border-color);
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    }
    .fw-nav-inner {
      max-width: 1320px;
      margin: 0 auto;
      padding: 12px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .fw-brand {
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      color: var(--brand-blue);
      font-weight: 800;
      font-size: 1.25rem;
    }
    .fw-nav-links {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .fw-nav-link {
      font-size: 0.86rem;
      font-weight: 600;
      color: #475569;
      text-decoration: none;
      padding: 6px 10px;
      border-radius: 8px;
    }
    .fw-nav-link:hover {
      color: var(--brand-blue);
    }

    /* Container */
    .detail-container {
      max-width: 1320px;
      margin: 20px auto 70px;
      padding: 0 24px;
    }

    /* Breadcrumbs */
    .detail-breadcrumbs {
      font-size: 0.84rem;
      color: #64748b;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .detail-breadcrumbs a {
      color: #64748b;
      text-decoration: none;
    }
    .detail-breadcrumbs a:hover {
      color: #0284c7;
    }

    /* 2 Columns Layout */
    .detail-layout {
      display: grid;
      grid-template-columns: 1fr 410px;
      gap: 32px;
      align-items: start;
    }
    @media (max-width: 980px) {
      .detail-layout {
        grid-template-columns: 1fr;
      }
    }

    /* Left Column */
    .left-col {
      display: flex;
      flex-direction: column;
      gap: 28px;
    }

    /* Gallery Slider (as in Image 2) */
    .gallery-showcase-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    .gallery-main-wrap {
      position: relative;
      width: 100%;
      height: 440px;
      background: #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .gallery-main-img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      transition: opacity 0.25s ease;
    }
    .gallery-badge-overlay {
      position: absolute;
      top: 14px;
      left: 14px;
      background: rgba(15, 23, 42, 0.8);
      backdrop-filter: blur(6px);
      color: #fff;
      font-size: 0.76rem;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .gallery-thumbs-row {
      display: flex;
      gap: 12px;
      padding: 14px 18px;
      background: #f8fafc;
      border-top: 1px solid var(--border-color);
      overflow-x: auto;
    }
    .thumb-item {
      width: 86px;
      height: 60px;
      border-radius: 8px;
      overflow: hidden;
      border: 2px solid transparent;
      cursor: pointer;
      flex-shrink: 0;
      transition: all 0.2s;
    }
    .thumb-item.active, .thumb-item:hover {
      border-color: #0284c7;
      transform: scale(1.04);
      box-shadow: 0 2px 8px rgba(2, 132, 199, 0.3);
    }
    .thumb-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    /* Service Title & Meta */
    .service-header-box {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    .service-main-title {
      font-size: 1.65rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.3;
      margin-bottom: 12px;
      letter-spacing: -0.3px;
    }
    .service-meta-stats {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      font-size: 0.86rem;
      color: #64748b;
      padding-top: 12px;
      border-top: 1px solid #f1f5f9;
    }
    .meta-stat-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    /* Content Anchor Tabs */
    .content-nav-tabs {
      display: flex;
      gap: 10px;
      border-bottom: 2px solid #e2e8f0;
      margin-bottom: 16px;
      overflow-x: auto;
      scrollbar-width: none;
    }
    .content-nav-tabs::-webkit-scrollbar { display: none; }
    .content-tab-btn {
      padding: 10px 18px;
      font-size: 0.9rem;
      font-weight: 700;
      color: #64748b;
      text-decoration: none;
      border-bottom: 3px solid transparent;
      margin-bottom: -2px;
      white-space: nowrap;
      transition: all 0.2s;
    }
    .content-tab-btn:hover, .content-tab-btn.active {
      color: #0284c7;
      border-bottom-color: #0284c7;
    }

    /* Detail Sections Card */
    .detail-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 24px 28px;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    .card-heading {
      font-size: 1.25rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 16px;
      letter-spacing: -0.2px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Package Tier Comparison Grid */
    .pkg-compare-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-top: 16px;
    }
    @media (max-width: 768px) {
      .pkg-compare-grid { grid-template-columns: 1fr; }
    }
    .pkg-compare-col {
      border: 1.5px solid #e2e8f0;
      border-radius: 14px;
      padding: 20px 18px;
      display: flex;
      flex-direction: column;
      transition: all 0.2s;
      background: #fff;
    }
    .pkg-compare-col.popular {
      border-color: #0284c7;
      box-shadow: 0 4px 18px rgba(2, 132, 199, 0.12);
      position: relative;
    }
    .popular-tag {
      position: absolute;
      top: -12px;
      left: 50%;
      transform: translateX(-50%);
      background: #0284c7;
      color: #fff;
      font-size: 0.72rem;
      font-weight: 800;
      padding: 3px 12px;
      border-radius: 20px;
    }
    .pkg-col-name {
      font-size: 1rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 6px;
    }
    .pkg-col-desc {
      font-size: 0.8rem;
      color: #64748b;
      margin-bottom: 14px;
      min-height: 40px;
    }
    .pkg-features-list {
      list-style: none;
      padding: 0;
      margin: 0 0 18px 0;
      font-size: 0.82rem;
      display: flex;
      flex-direction: column;
      gap: 8px;
      flex: 1;
    }
    .pkg-features-list li {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      color: #334155;
    }
    .pkg-features-list li svg {
      color: #10b981;
      flex-shrink: 0;
      margin-top: 3px;
    }
    .btn-select-pkg {
      width: 100%;
      padding: 10px;
      font-size: 0.85rem;
      font-weight: 700;
      border-radius: 8px;
      border: 1.5px solid #cbd5e1;
      background: #f8fafc;
      color: #334155;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-select-pkg:hover, .pkg-compare-col.selected .btn-select-pkg {
      background: #0284c7;
      color: #fff;
      border-color: #0284c7;
    }

    /* Seller Bio Profile Card (as in Image 2) */
    .seller-profile-card {
      display: flex;
      gap: 20px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 20px;
      margin-top: 12px;
    }
    .seller-avatar-large {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
      border: 3px solid #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .seller-info-details {
      flex: 1;
    }
    .seller-name-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 4px;
    }
    .seller-full-name {
      font-size: 1.15rem;
      font-weight: 800;
      color: #0f172a;
    }
    .seller-meta-row {
      display: flex;
      align-items: center;
      gap: 16px;
      font-size: 0.82rem;
      color: #64748b;
      margin-bottom: 10px;
    }

    /* Reviews Section */
    .reviews-score-hero {
      display: flex;
      align-items: center;
      gap: 32px;
      padding: 20px 24px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      margin-bottom: 24px;
    }
    .big-score-val {
      font-size: 3rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1;
      margin-bottom: 4px;
    }
    .review-item-card {
      padding: 18px 0;
      border-bottom: 1px solid #f1f5f9;
    }
    .review-item-card:last-child {
      border-bottom: none;
    }
    .reviewer-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
    }

    /* Right Sticky Sidebar (as in Image 2) */
    .right-sticky-wrap {
      position: sticky;
      top: 86px;
    }
    .purchase-box-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
    }
    .purchase-box-tabs {
      display: flex;
      background: #f8fafc;
      border-bottom: 1px solid var(--border-color);
    }
    .p-tab-btn {
      flex: 1;
      text-align: center;
      padding: 14px 10px;
      font-size: 0.86rem;
      font-weight: 700;
      color: #64748b;
      border: none;
      background: transparent;
      border-bottom: 3px solid transparent;
      cursor: pointer;
      transition: all 0.2s;
    }
    .p-tab-btn.active {
      color: #0284c7;
      background: #ffffff;
      border-bottom-color: #0284c7;
    }
    .purchase-box-body {
      padding: 24px;
    }
    .btn-order-primary {
      background: #0284c7;
      color: #ffffff;
      border: none;
      width: 100%;
      padding: 14px 20px;
      font-size: 1rem;
      font-weight: 800;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
      transition: all 0.2s;
      margin-bottom: 10px;
    }
    .btn-order-primary:hover {
      background: #0369a1;
      transform: translateY(-1px);
    }
    .btn-action-profile-secondary {
      background: #ffffff;
      color: #334155;
      border: 1.5px solid #cbd5e1;
      width: 100%;
      padding: 12px 20px;
      font-size: 0.9rem;
      font-weight: 700;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-action-profile-secondary:hover {
      background: #f8fafc;
      border-color: #94a3b8;
      color: #0f172a;
    }
    .btn-hire {
      background: #2563eb;
      color: #ffffff;
      border: none;
      padding: 7px 16px;
      font-size: 0.82rem;
      font-weight: 700;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-hire:hover {
      background: #1d4ed8;
    }
    .modal-window {
      background: #ffffff;
      border-radius: 16px;
      max-width: 560px;
      width: 100%;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      animation: modalPop 0.25s ease;
      overflow: hidden;
    }
    .modal-header {
      padding: 18px 24px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .modal-close-btn {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #94a3b8;
      cursor: pointer;
    }
    .modal-footer {
      padding: 14px 24px;
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      background: #f8fafc;
    }
    .btn-secondary {
      background: #e2e8f0;
      color: #334155;
      border: none;
      padding: 8px 18px;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
    }
    .escrow-trust-box {
      margin-top: 18px;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-radius: 12px;
      padding: 14px 16px;
      font-size: 0.8rem;
      color: #166534;
      line-height: 1.45;
    }

    /* Modal Styles */
    .modal-backdrop {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(4px);
      z-index: 999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-backdrop.open {
      display: flex;
    }
    .modal-content-card {
      background: #ffffff;
      border-radius: 16px;
      max-width: 520px;
      width: 100%;
      padding: 26px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      animation: modalPop 0.25s ease;
    }
    @keyframes modalPop {
      from { transform: scale(0.95); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
  </style>
</head>
<body>

  <!-- Top Navigation Header -->
  <header class="fw-navbar">
    <div class="fw-nav-inner">
      <div style="display:flex;align-items:center;gap:18px;">
        <a href="katalog-jasa.php" class="fw-brand">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span>KarirHub Jasa</span>
        </a>
      </div>
      <div class="fw-nav-links">
        <?php if ($isEmployer): ?>
          <a href="employer-cari-mitra.php" class="fw-nav-link">Cari Mitra Kerja</a>
          <a href="katalog-jasa.php" class="fw-nav-link" style="color:#0284c7;font-weight:700;">Bursa Jasa</a>
          <a href="employer-lowongan.php" class="fw-nav-link" style="background:#eff6ff;color:#1d4ed8;padding:7px 12px;border-radius:8px;font-weight:700;border:1px solid #bfdbfe;">Kelola Lowongan</a>
        <?php else: ?>
          <a href="worker-bursa.php" class="fw-nav-link">Bursa Proyek</a>
          <a href="katalog-jasa.php" class="fw-nav-link" style="color:#0284c7;font-weight:700;">Bursa Jasa</a>
          <a href="worker-katalog-saya.php" class="fw-nav-link" style="background:#eff6ff;color:#1d4ed8;padding:7px 12px;border-radius:8px;font-weight:700;border:1px solid #bfdbfe;">Katalog Saya</a>
          <a href="worker-katalog-tambah.php" class="fw-nav-link" style="background:#0284c7;color:#fff;padding:7px 14px;border-radius:8px;font-weight:800;">+ Pasang Jasa</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="detail-container">
    
    <!-- Breadcrumbs -->
    <div class="detail-breadcrumbs">
      <a href="katalog-jasa.php">Beranda</a>
      <span>&rsaquo;</span>
      <a href="katalog-jasa.php?category=<?php echo urlencode($service['category'] ?? 'Desain Grafis'); ?>"><?php echo htmlspecialchars($service['category'] ?? 'Desain Grafis', ENT_QUOTES, 'UTF-8'); ?></a>
      <span>&rsaquo;</span>
      <a href="katalog-jasa.php?category=<?php echo urlencode($service['category'] ?? 'Desain Grafis'); ?>&subcat=<?php echo urlencode($service['subcategory'] ?? 'Desain Logo'); ?>"><?php echo htmlspecialchars($service['subcategory'] ?? 'Desain Logo', ENT_QUOTES, 'UTF-8'); ?></a>
      <span>&rsaquo;</span>
      <span style="color:#0f172a;font-weight:700;"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <!-- 2 Column Layout (Left Showcase & Details, Right Sticky Pricing Card) -->
    <div class="detail-layout">

      <!-- LEFT COLUMN -->
      <div class="left-col">

        <!-- Title & Meta Card -->
        <div class="service-header-box">
          <h1 class="service-main-title"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <div class="service-meta-stats">
            <span class="meta-stat-pill" style="color:#f59e0b;font-weight:800;">
              ★ <?php echo number_format((float)($service['rating'] ?? 5.0), 1); ?>
              <span style="color:#64748b;font-weight:600;">(<?php echo (int)($service['reviews_count'] ?? 148); ?> ulasan)</span>
            </span>
            <span>•</span>
            <span class="meta-stat-pill">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
              <?php echo (int)($service['orders_count'] ?? 312); ?> Proyek Selesai
            </span>
            <span>•</span>
            <span class="meta-stat-pill">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
              Mitra Terverifikasi KarirHub
            </span>
          </div>
        </div>

        <!-- Hero Gallery Showcase (as in Image 2) -->
        <div class="gallery-showcase-card">
          <div class="gallery-main-wrap">
            <div class="gallery-badge-overlay">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Terverifikasi KarirHub Escrow
            </div>
            <img src="<?php echo htmlspecialchars($gallery[0], ENT_QUOTES, 'UTF-8'); ?>" id="mainGalleryImg" class="gallery-main-img" alt="Foto Karya Portofolio" />
          </div>
          <!-- Clickable Thumbnails Row -->
          <div class="gallery-thumbs-row">
            <?php foreach ($gallery as $idx => $imgUrl): ?>
              <div class="thumb-item <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="switchGalleryImg('<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>', this)">
                <img src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Thumbnail <?php echo $idx + 1; ?>" />
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Section Navigation Anchor Tabs -->
        <nav class="content-nav-tabs">
          <a href="#deskripsi" class="content-tab-btn active">Deskripsi Layanan</a>
          <a href="#lingkup" class="content-tab-btn">Cakupan Deliverables</a>
          <a href="#profil" class="content-tab-btn">Tentang Gig Worker</a>
          <a href="#ulasan" class="content-tab-btn">Penilaian Perusahaan (<?php echo (int)($service['reviews_count'] ?? 148); ?>)</a>
          <a href="#faq" class="content-tab-btn">Syarat & Ketentuan</a>
        </nav>

        <!-- SECTION 1: Deskripsi Layanan -->
        <section class="detail-card" id="deskripsi">
          <h2 class="card-heading">Deskripsi Layanan & Alur Kerja</h2>
          <div style="font-size:0.92rem;color:#334155;white-space:pre-line;line-height:1.7;">
            <?php echo htmlspecialchars($service['description'] ?? 'Layanan profesional dengan jaminan kepuasan.', ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </section>

        <!-- SECTION 2: Cakupan & Output Layanan -->
        <section class="detail-card" id="lingkup">
          <h2 class="card-heading">Cakupan & Output Layanan</h2>
          <p style="font-size:0.86rem;color:#64748b;margin-bottom:16px;">Berikut rincian hasil kerja dan format berkas deliverables yang akan diserahkan kepada perusahaan pemberi kerja:</p>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div style="border:1.5px solid #e2e8f0;border-radius:12px;padding:18px;background:#f8fafc;">
              <div style="font-size:0.95rem;font-weight:800;color:#0f172a;margin-bottom:10px;">📦 Berkas & Hasil Akhir (Deliverables)</div>
              <ul class="pkg-features-list" style="margin:0;">
                <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Konsep desain original sesuai identitas merek</span></li>
                <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>File master vektor skala penuh (AI, EPS, SVG, PDF)</span></li>
                <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Gambar resolusi tinggi siap cetak (300 DPI) & PNG transparan</span></li>
                <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Mockup visual realistis untuk presentasi</span></li>
              </ul>
            </div>

            <div style="border:1.5px solid #e2e8f0;border-radius:12px;padding:18px;background:#f8fafc;">
              <div style="font-size:0.95rem;font-weight:800;color:#0f172a;margin-bottom:10px;">🤝 Standar Kerjasama Proyek Perusahaan</div>
              <ul class="pkg-features-list" style="margin:0;">
                <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Penugasan proyek resmi melalui fitur Tawarkan Proyek KarirHub</span></li>
                <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Hak cipta komersial penuh langsung menjadi aset perusahaan</span></li>
                <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Revisi terakomodasi sesuai kesepakatan brief kerja penugasan</span></li>
                <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Pelaporan berkas dan serah terima resmi di dashboard KarirHub</span></li>
              </ul>
            </div>
          </div>
        </section>

        <!-- SECTION 3: Profil Gig Worker -->
        <section class="detail-card" id="profil">
          <h2 class="card-heading">Tentang Gig Worker</h2>
          <div class="seller-profile-card">
            <?php echo gig_katalog_render_avatar($service['worker_name'] ?? 'Worker', 72, '1.5rem', 'border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.1);'); ?>
            <div class="seller-info-details">
              <div class="seller-name-row">
                <span class="seller-full-name"><?php echo htmlspecialchars($service['worker_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span style="background:#e0f2fe;color:#0284c7;font-size:0.72rem;font-weight:800;padding:2px 8px;border-radius:6px;"><?php echo htmlspecialchars($service['worker_badge'] ?? 'Super Seller', ENT_QUOTES, 'UTF-8'); ?></span>
                <span style="background:#ecfdf5;color:#059669;font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:6px;">Status: Siap Ditugaskan</span>
              </div>
              <div class="seller-meta-row">
                <span>📍 Indonesia</span>
                <span>•</span>
                <span>⭐ Rating 4.95 / 5.0</span>
                <span>•</span>
                <span><?php echo (int)($service['orders_count'] ?? 312); ?> Proyek Selesai</span>
              </div>
              <p style="font-size:0.86rem;color:#475569;margin-bottom:14px;line-height:1.5;">Gig Worker profesional berdedikasi dengan spesialisasi deliverables kreatif dan teknis berstandar tinggi. Berkomitmen menyelesaikan penugasan proyek perusahaan secara tepat waktu dan berkualitas.</p>
              <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="button" class="btn-hire" style="padding:10px 20px;font-size:0.86rem;display:inline-flex;align-items:center;gap:6px;" onclick="triggerOfferProject('<?php echo htmlspecialchars($workerId, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($service['worker_name']), ENT_QUOTES, 'UTF-8'); ?>')">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Tawarkan Proyek ke Mitra
                </button>
                <a href="worker-profile.php?id=<?php echo urlencode($workerId); ?>" target="_blank" style="padding:9px 18px;font-size:0.86rem;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;text-decoration:none;color:#334155;font-weight:700;display:inline-flex;align-items:center;">
                  Lihat Profil Portofolio Lengkap &rarr;
                </a>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 4: Ulasan & Penilaian Perusahaan -->
        <section class="detail-card" id="ulasan">
          <h2 class="card-heading">Ulasan & Penilaian Perusahaan Pemberi Kerja</h2>
          
          <div class="reviews-score-hero">
            <div>
              <div class="big-score-val">4.95</div>
              <div style="color:#f59e0b;font-size:1.1rem;font-weight:800;letter-spacing:2px;margin-bottom:4px;">★★★★★</div>
              <div style="font-size:0.82rem;color:#64748b;"><?php echo (int)($service['reviews_count'] ?? 148); ?> penilaian proyek selesai</div>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;gap:6px;font-size:0.8rem;color:#475569;">
              <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:70px;">5 Bintang</span>
                <div style="flex:1;height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;"><div style="width:94%;height:100%;background:#f59e0b;"></div></div>
                <span style="width:36px;text-align:right;font-weight:700;">94%</span>
              </div>
              <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:70px;">4 Bintang</span>
                <div style="flex:1;height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;"><div style="width:5%;height:100%;background:#f59e0b;"></div></div>
                <span style="width:36px;text-align:right;font-weight:700;">5%</span>
              </div>
              <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:70px;">3 Bintang</span>
                <div style="flex:1;height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;"><div style="width:1%;height:100%;background:#f59e0b;"></div></div>
                <span style="width:36px;text-align:right;font-weight:700;">1%</span>
              </div>
            </div>
          </div>

          <!-- Reviews List -->
          <div>
            <?php foreach ($sampleReviews as $rev): ?>
              <div class="review-item-card">
                <div class="reviewer-header">
                  <div style="display:flex;align-items:center;gap:10px;">
                    <?php echo gig_katalog_render_avatar($rev['name'], 36, '0.85rem'); ?>
                    <div>
                      <div style="font-size:0.88rem;font-weight:700;color:#0f172a;"><?php echo htmlspecialchars($rev['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                      <div style="font-size:0.75rem;color:#94a3b8;"><?php echo htmlspecialchars($rev['date'], ENT_QUOTES, 'UTF-8'); ?> • <span style="color:#10b981;font-weight:600;">Proyek Selesai</span></div>
                    </div>
                  </div>
                  <div style="color:#f59e0b;font-size:0.9rem;font-weight:800;">★★★★★</div>
                </div>
                <p style="font-size:0.88rem;color:#334155;line-height:1.55;margin:0;"><?php echo htmlspecialchars($rev['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- SECTION 5: Kebutuhan Proyek & FAQ -->
        <section class="detail-card" id="faq">
          <h2 class="card-heading">Spesifikasi Brief & Ketentuan Pengerjaan</h2>
          
          <div style="margin-bottom:20px;">
            <div style="font-size:0.92rem;font-weight:700;color:#0f172a;margin-bottom:8px;">Yang Perlu Disiapkan Perusahaan Sebelum Menawarkan Proyek:</div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;font-size:0.86rem;color:#475569;white-space:pre-line;">
              <?php echo htmlspecialchars($service['client_requirements'] ?? "1. Profil perusahaan & nama brand\n2. Bidang industri dan target audiens proyek\n3. Dokumen brief proyek dan panduan brand (jika ada)", ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>

          <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach (($service['faqs'] ?? []) as $faq): ?>
              <div style="border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                <div style="font-size:0.88rem;font-weight:800;color:#0f172a;margin-bottom:4px;">Q: <?php echo htmlspecialchars($faq['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                <div style="font-size:0.84rem;color:#475569;line-height:1.5;">A: <?php echo htmlspecialchars($faq['a'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- SECTION 6: Jasa Serupa Lainnya -->
        <?php if (!empty($relatedServices)): ?>
          <section style="margin-top:10px;">
            <h2 style="font-size:1.25rem;font-weight:800;color:#0f172a;margin-bottom:14px;">Katalog Jasa Lainnya untuk Anda</h2>
            <div style="display:grid;grid-template-columns:repeat(2, 1fr);gap:16px;">
              <?php foreach ($relatedServices as $rel): ?>
                <a href="katalog-jasa-detail.php?id=<?php echo urlencode($rel['id']); ?>" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;display:flex;text-decoration:none;color:inherit;transition:all 0.2s;" onmouseover="this.style.borderColor='#0284c7'" onmouseout="this.style.borderColor='#e2e8f0'">
                  <img src="<?php echo htmlspecialchars($rel['thumbnail'] ?: 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80', ENT_QUOTES, 'UTF-8'); ?>" style="width:110px;height:100px;object-fit:cover;" alt="Thumb" />
                  <div style="padding:10px 14px;flex:1;display:flex;flex-direction:column;justify-content:center;">
                    <div style="font-size:0.84rem;font-weight:700;color:#0f172a;line-height:1.3;margin-bottom:4px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?php echo htmlspecialchars($rel['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-size:0.8rem;font-weight:700;color:#0284c7;">Lihat Detail Jasa &rarr;</div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

      </div>

      <!-- RIGHT STICKY SIDEBAR -->
      <aside class="right-sticky-wrap">
        <div class="purchase-box-card">
          <div style="background:#f8fafc;padding:16px 20px;border-bottom:1px solid var(--border-color);">
            <div style="font-size:1.05rem;font-weight:800;color:#0f172a;">Tugaskan Mitra untuk Proyek</div>
            <div style="font-size:0.82rem;color:#64748b;margin-top:2px;">Tawarkan lowongan proyek aktif perusahaan Anda langsung ke Gig Worker ini.</div>
          </div>

          <div class="purchase-box-body">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:12px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;">
              <?php echo gig_katalog_render_avatar($service['worker_name'] ?? 'Worker', 40, '0.95rem'); ?>
              <div>
                <div style="font-size:0.9rem;font-weight:800;color:#0f172a;"><?php echo htmlspecialchars($service['worker_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div style="font-size:0.75rem;color:#0369a1;font-weight:600;">Status: Siap Ditugaskan Perusahaan</div>
              </div>
            </div>

            <!-- Deliverables summary -->
            <ul class="pkg-features-list" style="margin-bottom:20px;">
              <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Konsep desain original & terstandarisasi</span></li>
              <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>File master vektor & format resolusi tinggi lengkap</span></li>
              <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Hak cipta komersial penuh milik perusahaan</span></li>
              <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Penugasan & serah-terima resmi via KarirHub</span></li>
            </ul>

            <!-- Primary Action Button: Tawarkan Proyek Perusahaan -->
            <button type="button" class="btn-order-primary" onclick="triggerOfferProject('<?php echo htmlspecialchars($workerId, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($service['worker_name']), ENT_QUOTES, 'UTF-8'); ?>')">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Tawarkan Proyek Perusahaan
            </button>

            <!-- Secondary Action Button: Lihat Profil Lengkap -->
            <a href="worker-profile.php?id=<?php echo urlencode($workerId); ?>" target="_blank" class="btn-action-profile-secondary" style="margin-top:8px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Lihat Profil Portofolio Lengkap
            </a>

            <!-- Trust Box -->
            <div class="escrow-trust-box" style="margin-top:16px;">
              <div style="font-weight:800;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Kerjasama Resmi KarirHub
              </div>
              <p style="margin:0;">Seluruh penawaran lowongan, kesepakatan cakupan kerja, dan serah-terima berkas deliverables dicatat resmi di dashboard KarirHub.</p>
            </div>

          </div>
        </div>
      </aside>

    </div>
  </div>

  <!-- OFFER PROJECT MODAL (FOR EMPLOYER) -->
  <div class="modal-backdrop" id="offerProjectModal" onclick="if(event.target.id==='offerProjectModal') closeOfferModal();">
    <div class="modal-window">
      <div class="modal-header">
        <h3 style="font-size:1.15rem;font-weight:800;color:#0f172a;margin:0;">Tawarkan Proyek ke <span id="offer-worker-name"><?php echo htmlspecialchars($service['worker_name'], ENT_QUOTES, 'UTF-8'); ?></span></h3>
        <button class="modal-close-btn" type="button" onclick="closeOfferModal()">&times;</button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <?php if (!$isEmployer): ?>
          <div style="text-align:center;padding:20px 0;">
            <div style="font-size:2rem;margin-bottom:12px;">🏢</div>
            <div style="font-weight:800;font-size:1rem;color:#0f172a;margin-bottom:6px;">Khusus Akun Perusahaan (Pemberi Kerja)</div>
            <p style="font-size:0.86rem;color:#64748b;line-height:1.5;margin-bottom:16px;">Fitur Tawarkan Proyek digunakan oleh Perusahaan untuk menugaskan Gig Worker pada lowongan aktif. Silakan masuk dengan akun Perusahaan Anda.</p>
            <div style="display:flex;gap:10px;justify-content:center;">
              <a href="employer-login.php?redirect=<?php echo urlencode('katalog-jasa-detail.php?id=' . $service['id']); ?>" class="btn-hire" style="text-decoration:none;display:inline-block;padding:10px 20px;">Masuk Akun Perusahaan</a>
              <a href="employer-register.php" style="display:inline-block;padding:10px 18px;background:#f1f5f9;color:#475569;border-radius:8px;font-size:0.85rem;font-weight:700;text-decoration:none;border:1px solid #cbd5e1;">Daftar Perusahaan</a>
            </div>
          </div>
        <?php elseif (count($activeVacancies) === 0): ?>
          <div style="text-align:center;padding:24px 0;">
            <div style="font-size:2rem;margin-bottom:12px;">📋</div>
            <div style="font-weight:700;font-size:0.95rem;color:#1e293b;margin-bottom:6px;">Belum Ada Lowongan Aktif</div>
            <p style="font-size:0.85rem;color:#64748b;line-height:1.5;margin-bottom:16px;">Perusahaan Anda belum memiliki lowongan aktif yang siap ditawarkan. Ajukan lowongan baru terlebih dahulu.</p>
            <a href="employer-lowongan.php" class="btn-hire" style="display:inline-block;text-decoration:none;padding:8px 20px;border-radius:8px;font-size:0.86rem;">Kelola Lowongan Perusahaan &rarr;</a>
          </div>
        <?php else: ?>
          <p style="font-size:0.86rem;color:#64748b;margin-bottom:14px;">Pilih proyek aktif perusahaan Anda yang ingin ditawarkan kepada mitra ini (maksimal 3 penawaran per lowongan):</p>
          <div style="display:flex;flex-direction:column;gap:10px;" id="offer-vacancy-list">
            <?php foreach ($activeVacancies as $v): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;"
                 data-vacancy-id="<?php echo htmlspecialchars($v['id'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-offer-count="<?php echo (int)($vacancyOfferCounts[$v['id']] ?? 0); ?>">
              <div>
                <div style="font-size:0.88rem;font-weight:700;color:#1e293b;"><?php echo htmlspecialchars($v['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div style="font-size:0.74rem;color:#64748b;margin-top:2px;">
                  <?php echo htmlspecialchars($v['budget'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($v['duration'], ENT_QUOTES, 'UTF-8'); ?>
                  · <span style="color:#059669;font-weight:700;">1 Penempatan Gig Worker</span>
                </div>
              </div>
              <button type="button" class="btn-hire offer-send-btn"
                onclick="sendOffer('<?php echo htmlspecialchars($v['id'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($v['title']), ENT_QUOTES, 'UTF-8'); ?>')">
                Kirim Tawaran
              </button>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="closeOfferModal()">Tutup</button>
      </div>
    </div>
  </div>

  <script>
    let currentOfferWorkerId = '<?php echo htmlspecialchars($workerId, ENT_QUOTES, 'UTF-8'); ?>';
    let currentOfferWorkerName = '<?php echo htmlspecialchars(addslashes($service['worker_name']), ENT_QUOTES, 'UTF-8'); ?>';
    let isEmployer = <?php echo $isEmployer ? 'true' : 'false'; ?>;
    let offerCounts = <?php echo json_encode($vacancyOfferCounts, JSON_UNESCAPED_UNICODE); ?> || {};
    const alreadyOffered = <?php echo json_encode($alreadyOfferedMap, JSON_UNESCAPED_UNICODE); ?> || {};

    function switchGalleryImg(url, el) {
      document.getElementById('mainGalleryImg').src = url;
      document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
      el.classList.add('active');
    }

    function triggerOfferProject(wId, wName) {
      currentOfferWorkerId = wId || currentOfferWorkerId;
      currentOfferWorkerName = wName || currentOfferWorkerName;
      document.getElementById('offer-worker-name').innerText = currentOfferWorkerName;
      if (isEmployer) {
        refreshOfferButtons();
      }
      document.getElementById('offerProjectModal').classList.add('open');
    }

    function closeOfferModal() {
      document.getElementById('offerProjectModal').classList.remove('open');
    }

    function setOfferButtonState(container, vacancyId, sentToCurrent) {
      const btn = container ? container.querySelector('.offer-send-btn') : null;
      if (!btn) return;
      const count = offerCounts[vacancyId] || 0;
      if (sentToCurrent) {
        btn.disabled = true;
        btn.style.background = '#94a3b8';
        btn.style.cursor = 'not-allowed';
        btn.innerText = 'Sudah Ditawarkan';
        return;
      }
      if (count >= 3) {
        btn.disabled = true;
        btn.style.background = '#94a3b8';
        btn.style.cursor = 'not-allowed';
        btn.innerText = 'Penawaran Penuh (3/3)';
        return;
      }
      btn.disabled = false;
      btn.style.background = '';
      btn.style.cursor = 'pointer';
      btn.innerText = 'Kirim Tawaran';
    }

    function refreshOfferButtons() {
      const sent = alreadyOffered[currentOfferWorkerId] || [];
      document.querySelectorAll('#offer-vacancy-list [data-vacancy-id]').forEach(function (row) {
        const vacancyId = row.getAttribute('data-vacancy-id');
        setOfferButtonState(row, vacancyId, sent.indexOf(vacancyId) !== -1);
      });
    }

    function sendOffer(vacancyId, vacancyTitle) {
      if (!currentOfferWorkerId) {
        alert('Data Gig Worker tidak valid.');
        return;
      }

      const container = document.querySelector('[data-vacancy-id="' + vacancyId + '"]');
      const btn = container ? container.querySelector('.offer-send-btn') : null;
      if (btn) btn.disabled = true;

      fetch('offer-save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          worker_id: currentOfferWorkerId,
          vacancy_id: vacancyId
        })
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data || !data.ok) {
            alert((data && data.error) ? data.error : 'Penawaran gagal dikirim.');
            if (btn) btn.disabled = false;
            refreshOfferButtons();
            return;
          }

          offerCounts[vacancyId] = data.offer_count || ((offerCounts[vacancyId] || 0) + 1);
          if (!alreadyOffered[currentOfferWorkerId]) alreadyOffered[currentOfferWorkerId] = [];
          if (alreadyOffered[currentOfferWorkerId].indexOf(vacancyId) === -1) {
            alreadyOffered[currentOfferWorkerId].push(vacancyId);
          }

          alert('Penawaran lowongan "' + vacancyTitle + '" berhasil dikirim ke ' + currentOfferWorkerName + '. Gig Worker dapat meninjau detail penugasan pada dashboard mereka.');
          refreshOfferButtons();
          closeOfferModal();
        })
        .catch(function () {
          alert('Koneksi terputus. Silakan coba lagi.');
          if (btn) btn.disabled = false;
        });
    }
  </script>
</body>
</html>


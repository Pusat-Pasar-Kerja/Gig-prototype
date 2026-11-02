<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/user-avatars.php';
require_once __DIR__ . '/includes/katalog-store.php';

$allServices = gig_katalog_load_all();

$role = (string)($_SESSION['role'] ?? '');
$employerUser = (string)($_SESSION['employer_user'] ?? '');
$isEmployer = ($role === 'employer' || !empty($employerUser));

// Filter parameters
$searchQ = trim((string)($_GET['q'] ?? ''));
$selectedCategory = trim((string)($_GET['category'] ?? 'Semua'));
$selectedSubcat = trim((string)($_GET['subcat'] ?? 'Semua'));
$selectedTag = trim((string)($_GET['tag'] ?? 'Semua'));
$sort = trim((string)($_GET['sort'] ?? 'recommended'));
$filterVerified = !empty($_GET['verified']);
$filterFast = !empty($_GET['fast']);

$services = array_filter($allServices, function ($s) {
    return ($s['status'] ?? 'active') === 'active';
});

// Category filtering
if ($selectedCategory !== 'Semua' && $selectedCategory !== '') {
    $services = array_filter($services, function ($s) use ($selectedCategory) {
        return strcasecmp($s['category'] ?? '', $selectedCategory) === 0;
    });
}

// Tag filtering
if ($selectedTag !== 'Semua' && $selectedTag !== '') {
    $services = array_filter($services, function ($s) use ($selectedTag) {
        $tag = $s['tag'] ?? '';
        return stripos($tag, $selectedTag) !== false;
    });
}

// Search keyword
if ($searchQ !== '') {
    $services = array_filter($services, function ($s) use ($searchQ) {
        $hay = strtolower(($s['title'] ?? '') . ' ' . ($s['description'] ?? '') . ' ' . ($s['worker_name'] ?? '') . ' ' . ($s['category'] ?? '') . ' ' . ($s['tag'] ?? ''));
        return str_contains($hay, strtolower($searchQ));
    });
}

// Fast delivery filter (< 3 days)
if ($filterFast) {
    $services = array_filter($services, function ($s) {
        return (int)($s['delivery_days'] ?? 2) <= 2;
    });
}

// Sorting
usort($services, function ($a, $b) use ($sort) {
    if ($sort === 'rating') {
        return ($b['rating'] ?? 5.0) <=> ($a['rating'] ?? 5.0);
    }
    if ($sort === 'popular') {
        return ($b['orders_count'] ?? 0) <=> ($a['orders_count'] ?? 0);
    }
    // Default recommended
    return ($b['reviews_count'] ?? 0) <=> ($a['reviews_count'] ?? 0);
});

// Current user state
$username = (string)($_SESSION['username'] ?? $_SESSION['siapkerja_name'] ?? 'theressaz@pasker.id');
$workerObj = gig_find_worker($username) ?? gig_find_worker('tessa');
$displayName = $workerObj['name'] ?? ($_SESSION['siapkerja_name'] ?? 'Theressa Zaratrusha');
$workerAvatarUrl = gig_worker_display_photo((string)($workerObj['id'] ?? 'tessa'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Jasa Desain & Layanan Digital Freelance | KarirHub Gig Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary-blue: #0284c7;
      --primary-dark: #0369a1;
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
      line-height: 1.5;
    }

    /* Fastwork-Style Top Navigation */
    .fw-navbar {
      background: #ffffff;
      border-bottom: 1px solid var(--border-color);
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    }
    .fw-nav-inner {
      max-width: 1360px;
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
      letter-spacing: -0.4px;
    }
    .fw-brand-badge {
      background: #0284c7;
      color: #fff;
      font-size: 0.72rem;
      font-weight: 800;
      padding: 2px 8px;
      border-radius: 6px;
      text-transform: uppercase;
    }
    .fw-search-wrap {
      flex: 1;
      max-width: 580px;
      position: relative;
    }
    .fw-search-input-box {
      display: flex;
      align-items: center;
      background: #f1f5f9;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      padding: 6px 14px;
      transition: all 0.2s;
    }
    .fw-search-input-box:focus-within {
      background: #fff;
      border-color: #0284c7;
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .fw-search-input-box input {
      border: none;
      background: transparent;
      outline: none;
      width: 100%;
      font-size: 0.88rem;
      font-family: inherit;
      color: #0f172a;
      margin-left: 8px;
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
      transition: all 0.2s;
    }
    .fw-nav-link:hover {
      color: var(--brand-blue);
      background: #f1f5f9;
    }
    .fw-btn-sell {
      background: #eff6ff;
      border: 1.5px solid #bfdbfe;
      color: var(--brand-blue);
      font-weight: 800;
      font-size: 0.85rem;
      padding: 8px 16px;
      border-radius: 10px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .fw-btn-sell:hover {
      background: var(--brand-blue);
      color: #fff;
      border-color: var(--brand-blue);
    }
    .fw-btn-upload {
      background: #0284c7;
      color: #fff;
      font-weight: 800;
      font-size: 0.85rem;
      padding: 8px 16px;
      border-radius: 10px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
      transition: all 0.2s;
    }
    .fw-btn-upload:hover {
      background: #0369a1;
      transform: translateY(-1px);
    }

    /* Category Sub-Bar */
    .fw-cat-subbar {
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      padding: 0 24px;
    }
    .fw-cat-inner {
      max-width: 1360px;
      margin: 0 auto;
      display: flex;
      gap: 24px;
      overflow-x: auto;
      scrollbar-width: none;
    }
    .fw-cat-inner::-webkit-scrollbar {
      display: none;
    }
    .fw-cat-item {
      padding: 12px 0;
      font-size: 0.86rem;
      font-weight: 600;
      color: #64748b;
      text-decoration: none;
      white-space: nowrap;
      border-bottom: 2.5px solid transparent;
      transition: all 0.2s;
    }
    .fw-cat-item:hover, .fw-cat-item.active {
      color: #0284c7;
      border-bottom-color: #0284c7;
      font-weight: 700;
    }

    /* Main Content Container */
    .fw-main {
      max-width: 1360px;
      margin: 20px auto 60px;
      padding: 0 24px;
    }

    /* Breadcrumbs & Title */
    .fw-breadcrumbs {
      font-size: 0.82rem;
      color: #64748b;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .fw-breadcrumbs a {
      color: #64748b;
      text-decoration: none;
    }
    .fw-breadcrumbs a:hover {
      color: #0284c7;
    }
    .fw-headline-row {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      margin-bottom: 16px;
    }
    .fw-page-title {
      font-size: 1.65rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.4px;
    }

    /* Filter Pills (Tags) */
    .fw-tag-pills {
      display: flex;
      gap: 10px;
      overflow-x: auto;
      padding-bottom: 12px;
      margin-bottom: 18px;
    }
    .fw-tag-pill {
      background: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 20px;
      padding: 7px 18px;
      font-size: 0.84rem;
      font-weight: 600;
      color: #334155;
      text-decoration: none;
      white-space: nowrap;
      transition: all 0.2s;
    }
    .fw-tag-pill:hover {
      border-color: #0284c7;
      color: #0284c7;
      background: #f0f9ff;
    }
    .fw-tag-pill.active {
      background: #0284c7;
      color: #ffffff;
      border-color: #0284c7;
      font-weight: 700;
    }

    /* Filter Toolbar */
    .fw-toolbar {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 12px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .fw-toolbar-left {
      display: flex;
      align-items: center;
      gap: 16px;
      font-size: 0.86rem;
      color: #64748b;
    }
    .fw-toggle-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.82rem;
      font-weight: 700;
      color: #334155;
      text-decoration: none;
      padding: 6px 12px;
      border-radius: 8px;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      transition: all 0.2s;
    }
    .fw-toggle-btn.active {
      background: #ecfdf5;
      color: #059669;
      border-color: #a7f3d0;
    }
    .fw-sort-select {
      border: 1.5px solid #cbd5e1;
      border-radius: 8px;
      padding: 6px 12px;
      font-size: 0.85rem;
      font-family: inherit;
      background: #fff;
      color: #0f172a;
      outline: none;
      font-weight: 600;
    }

    /* Grid of Service Cards (4 Columns like Image 1) */
    .fw-services-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }
    @media (max-width: 1200px) {
      .fw-services-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 860px) {
      .fw-services-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 540px) {
      .fw-services-grid { grid-template-columns: 1fr; }
    }

    /* Individual Card */
    .fw-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      text-decoration: none;
      color: inherit;
      transition: all 0.25s ease;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .fw-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
      border-color: #94a3b8;
    }
    .fw-card-thumb-wrap {
      position: relative;
      width: 100%;
      height: 180px;
      background: #e2e8f0;
      overflow: hidden;
    }
    .fw-card-thumb {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.35s ease;
    }
    .fw-card:hover .fw-card-thumb {
      transform: scale(1.05);
    }
    .fw-card-badge-top {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(4px);
      color: #fff;
      font-size: 0.7rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 6px;
    }
    .fw-card-body {
      padding: 14px 16px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .fw-card-seller {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
    }
    .fw-card-avatar {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      object-fit: cover;
    }
    .fw-card-seller-name {
      font-size: 0.78rem;
      font-weight: 700;
      color: #334155;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .fw-card-title {
      font-size: 0.92rem;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.35;
      margin-bottom: 8px;
      height: 38px;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }
    .fw-card-tag-badge {
      font-size: 0.72rem;
      font-weight: 700;
      color: #2563eb;
      background: #eff6ff;
      padding: 2px 8px;
      border-radius: 4px;
      align-self: flex-start;
      margin-bottom: 12px;
    }
    .fw-card-footer {
      margin-top: auto;
      padding-top: 10px;
      border-top: 1px solid #f1f5f9;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
    }
    .fw-card-rating {
      font-size: 0.78rem;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .fw-card-rating-star {
      color: #f59e0b;
      font-weight: 800;
    }

    /* Worker Promo Banner */
    .fw-worker-banner {
      background: linear-gradient(135deg, #0369a1 0%, #0f172a 100%);
      color: #ffffff;
      padding: 30px 36px;
      border-radius: 16px;
      margin-top: 48px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <header class="fw-navbar">
    <div class="fw-nav-inner">
      <div style="display:flex;align-items:center;gap:18px;">
        <a href="katalog-jasa.php" class="fw-brand">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span>KarirHub Jasa</span>
          <span class="fw-brand-badge">Marketplace</span>
        </a>
      </div>

      <!-- Search Bar -->
      <div class="fw-search-wrap">
        <form action="katalog-jasa.php" method="GET" class="fw-search-input-box">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Cari jasa desain logo, website, video..." value="<?php echo htmlspecialchars($searchQ, ENT_QUOTES, 'UTF-8'); ?>" />
          <?php if (!empty($selectedCategory) && $selectedCategory !== 'Semua'): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8'); ?>" />
          <?php endif; ?>
        </form>
      </div>

      <!-- Nav Links -->
      <div class="fw-nav-links">
        <?php if ($isEmployer): ?>
          <a href="employer-cari-mitra.php" class="fw-nav-link">Cari Mitra Kerja</a>
          <a href="employer-lowongan.php" class="fw-btn-sell">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
            Kelola Lowongan
          </a>
          <a href="employer-proyek-aktif.php" class="fw-btn-upload">
            Proyek Aktif
          </a>
        <?php else: ?>
          <a href="worker-bursa.php" class="fw-nav-link">Bursa Proyek</a>
          <a href="worker-katalog-saya.php" class="fw-btn-sell">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
            Katalog Jasa Saya
          </a>
          <a href="worker-katalog-tambah.php" class="fw-btn-upload">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            + Pasang Jasa
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Category Sub-Bar -->
  <nav class="fw-cat-subbar">
    <div class="fw-cat-inner">
      <a href="katalog-jasa.php" class="fw-cat-item <?php echo ($selectedCategory === 'Semua' || empty($selectedCategory)) ? 'active' : ''; ?>">Semua Kategori</a>
      <a href="katalog-jasa.php?category=Desain+Grafis" class="fw-cat-item <?php echo $selectedCategory === 'Desain Grafis' ? 'active' : ''; ?>">Desain & Grafis</a>
      <a href="katalog-jasa.php?category=Web+%26+Pemrograman" class="fw-cat-item <?php echo $selectedCategory === 'Web & Pemrograman' ? 'active' : ''; ?>">Web & Pemrograman</a>
      <a href="katalog-jasa.php?category=Video+%26+Animasi" class="fw-cat-item <?php echo $selectedCategory === 'Video & Animasi' ? 'active' : ''; ?>">Video & Animasi</a>
      <a href="katalog-jasa.php?category=Penulisan+%26+Terjemahan" class="fw-cat-item <?php echo $selectedCategory === 'Penulisan & Terjemahan' ? 'active' : ''; ?>">Penulisan & Terjemahan</a>
      <a href="katalog-jasa.php?category=Pemasaran+Digital" class="fw-cat-item <?php echo $selectedCategory === 'Pemasaran Digital' ? 'active' : ''; ?>">Pemasaran Digital</a>
    </div>
  </nav>

  <!-- Main Section -->
  <main class="fw-main">
    <!-- Breadcrumbs -->
    <div class="fw-breadcrumbs">
      <a href="worker-bursa.php">Beranda</a>
      <span>&rsaquo;</span>
      <a href="katalog-jasa.php">Semua Kategori</a>
      <span>&rsaquo;</span>
      <span style="color:#0f172a;font-weight:700;"><?php echo htmlspecialchars($selectedCategory === 'Semua' ? 'Desain Grafis & Digital' : $selectedCategory, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <!-- Page Title Headline (as in Image 1: Jasa Desain Logo Online untuk Berbagai Kebutuhan) -->
    <div class="fw-headline-row">
      <div>
        <h1 class="fw-page-title">
          <?php if (!empty($searchQ)): ?>
            Hasil Pencarian "<?php echo htmlspecialchars($searchQ, ENT_QUOTES, 'UTF-8'); ?>"
          <?php elseif ($selectedCategory === 'Desain Grafis'): ?>
            Jasa Desain Logo & Branding Online untuk Berbagai Kebutuhan
          <?php elseif ($selectedCategory === 'Web & Pemrograman'): ?>
            Jasa Pembuatan Website & Aplikasi Online Terpercaya
          <?php elseif ($selectedCategory === 'Video & Animasi'): ?>
            Jasa Video Editing, Motion & Animasi Kreatif
          <?php else: ?>
            Jasa Profesional Online untuk Berbagai Kebutuhan Bisnis & UMKM
          <?php endif; ?>
        </h1>
        <p style="color:#64748b;font-size:0.88rem;margin-top:4px;">Katalog keahlian &amp; output deliverables dari talenta Gig Workers terverifikasi untuk kebutuhan proyek perusahaan.</p>
      </div>
    </div>

    <!-- Tag Filter Pills (as in Image 1: Minimalis, Vintage, Mascot, Typography, Modern) -->
    <div class="fw-tag-pills">
      <a href="katalog-jasa.php<?php echo !empty($selectedCategory) && $selectedCategory !== 'Semua' ? '?category=' . urlencode($selectedCategory) : ''; ?>" class="fw-tag-pill <?php echo ($selectedTag === 'Semua' || empty($selectedTag)) ? 'active' : ''; ?>">Semua</a>
      <a href="katalog-jasa.php?category=Desain+Grafis&tag=Minimalis" class="fw-tag-pill <?php echo $selectedTag === 'Minimalis' ? 'active' : ''; ?>">Minimalis</a>
      <a href="katalog-jasa.php?category=Desain+Grafis&tag=Modern" class="fw-tag-pill <?php echo $selectedTag === 'Modern' ? 'active' : ''; ?>">Modern</a>
      <a href="katalog-jasa.php?category=Desain+Grafis&tag=Mascot" class="fw-tag-pill <?php echo str_contains($selectedTag, 'Mascot') ? 'active' : ''; ?>">Mascot / Karakter</a>
      <a href="katalog-jasa.php?category=Desain+Grafis&tag=Vintage" class="fw-tag-pill <?php echo str_contains($selectedTag, 'Vintage') ? 'active' : ''; ?>">Vintage / Retro</a>
      <a href="katalog-jasa.php?category=Desain+Grafis&tag=Kemasan" class="fw-tag-pill <?php echo str_contains($selectedTag, 'Kemasan') ? 'active' : ''; ?>">Desain Kemasan</a>
      <a href="katalog-jasa.php?category=Web+%26+Pemrograman&tag=Landing+Page" class="fw-tag-pill <?php echo str_contains($selectedTag, 'Landing') ? 'active' : ''; ?>">Landing Page</a>
      <a href="katalog-jasa.php?category=Video+%26+Animasi&tag=Shorts" class="fw-tag-pill <?php echo str_contains($selectedTag, 'Shorts') ? 'active' : ''; ?>">Video TikTok / Reels</a>
    </div>

    <!-- Filter & Sort Toolbar -->
    <div class="fw-toolbar">
      <div class="fw-toolbar-left">
        <span>Menampilkan <strong><?php echo count($services); ?></strong> katalog jasa</span>
      </div>

      <div style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:0.84rem;color:#64748b;font-weight:600;">Urutkan:</span>
        <form method="GET" action="katalog-jasa.php" style="margin:0;">
          <?php if (!empty($selectedCategory)): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8'); ?>" /><?php endif; ?>
          <?php if (!empty($selectedTag)): ?><input type="hidden" name="tag" value="<?php echo htmlspecialchars($selectedTag, ENT_QUOTES, 'UTF-8'); ?>" /><?php endif; ?>
          <?php if (!empty($searchQ)): ?><input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQ, ENT_QUOTES, 'UTF-8'); ?>" /><?php endif; ?>
          <select name="sort" class="fw-sort-select" onchange="this.form.submit()">
            <option value="recommended" <?php echo $sort === 'recommended' ? 'selected' : ''; ?>>Paling Sesuai</option>
            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Terpopuler</option>
            <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating Tertinggi</option>
          </select>
        </form>
      </div>
    </div>

    <!-- Services Grid (Fastwork Style Card Grid) -->
    <?php if (empty($services)): ?>
      <div style="background:#ffffff;border:1px dashed #cbd5e1;border-radius:16px;padding:60px 20px;text-align:center;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:12px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <h3 style="margin:0 0 6px 0;font-size:1.15rem;color:#0f172a;">Tidak ditemukan katalog jasa yang cocok</h3>
        <p style="margin:0 0 16px 0;color:#64748b;font-size:0.88rem;">Coba gunakan kata kunci lain atau bersihkan filter pencarian.</p>
        <a href="katalog-jasa.php" class="fw-btn-upload">Tampilkan Semua Jasa</a>
      </div>
    <?php else: ?>
      <div class="fw-services-grid">
        <?php foreach ($services as $item): ?>
          <a href="katalog-jasa-detail.php?id=<?php echo urlencode($item['id']); ?>" class="fw-card">
            <div class="fw-card-thumb-wrap">
              <img src="<?php echo htmlspecialchars($item['thumbnail'] ?: 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" class="fw-card-thumb" loading="lazy" />
            </div>
            <div class="fw-card-body">
              <div class="fw-card-seller">
                <?php echo gig_katalog_render_avatar($item['worker_name'] ?? 'Worker', 26, '0.72rem'); ?>
                <span class="fw-card-seller-name"><?php echo htmlspecialchars($item['worker_name'], ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <h2 class="fw-card-title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
              <?php if (!empty($item['tag'])): ?>
                <span class="fw-card-tag-badge"><?php echo htmlspecialchars($item['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
              <div class="fw-card-footer">
                <div class="fw-card-rating">
                  <span class="fw-card-rating-star">★ <?php echo number_format((float)($item['rating'] ?? 5.0), 1); ?></span>
                  <span>(<?php echo (int)($item['reviews_count'] ?? 0); ?> ulasan)</span>
                </div>
                <div style="font-size:0.82rem;font-weight:700;color:#0284c7;display:flex;align-items:center;gap:4px;">
                  <span>Lihat Jasa &rarr;</span>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Worker Promo Bottom Banner -->
    <div class="fw-worker-banner">
      <div>
        <h2 style="margin:0 0 6px 0;font-size:1.4rem;font-weight:800;">Apakah Anda Talenta Gig Worker Berbakat?</h2>
        <p style="margin:0;font-size:0.92rem;color:#e0f2fe;max-width:640px;">Buka katalog jasa dan portofolio Anda di KarirHub Gig Workers secara gratis. Tampilkan sampel deliverables terbaik Anda agar perusahaan pemberi kerja dapat langsung menawarkan proyek aktif kepada Anda.</p>
      </div>
      <a href="worker-katalog-tambah.php" class="fw-btn-upload" style="background:#fff;color:#0284c7;font-weight:800;padding:12px 24px;font-size:0.95rem;box-shadow:0 4px 14px rgba(0,0,0,0.15);">
        + Pasang Katalog Jasa Anda
      </a>
    </div>

  </main>

</body>
</html>


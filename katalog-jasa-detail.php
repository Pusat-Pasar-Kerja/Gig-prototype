<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/user-avatars.php';
require_once __DIR__ . '/includes/katalog-store.php';

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

// Load related services (excluding current)
$allServices = gig_katalog_load_all();
$relatedServices = array_values(array_filter($allServices, function ($s) use ($service) {
    return $s['id'] !== $service['id'];
}));
$relatedServices = array_slice($relatedServices, 0, 4);

$packages = $service['packages'] ?? [];
$basicPkg = $packages['basic'] ?? [
    'name' => 'Paket Basic',
    'price' => $service['price_starting'] ?? 150000,
    'delivery_days' => $service['delivery_days'] ?? 2,
    'revisions' => $service['revisions'] ?? '2x Revisi',
    'desc' => 'Paket hemat esensial',
    'features' => ['Konsep Desain Awal', 'File HD JPG & PNG Transparan', 'Hak Cipta Penggunaan Komersial']
];
$standardPkg = $packages['standard'] ?? [
    'name' => 'Paket Standar',
    'price' => ($service['price_starting'] ?? 150000) * 2,
    'delivery_days' => ($service['delivery_days'] ?? 2) + 1,
    'revisions' => '5x Revisi',
    'desc' => 'Paket terpopuler untuk bisnis & UMKM',
    'features' => ['3 Konsep Desain Awal', 'File Master Vektor AI/EPS/PDF', 'Mockup 3D', 'Revisi 5x', 'Hak Cipta Komersial']
];
$premiumPkg = $packages['premium'] ?? [
    'name' => 'Paket Premium',
    'price' => ($service['price_starting'] ?? 150000) * 4,
    'delivery_days' => ($service['delivery_days'] ?? 2) + 3,
    'revisions' => 'Bebas Revisi',
    'desc' => 'Solusi lengkap korporat all-in-one',
    'features' => ['5 Konsep Desain Eksklusif', 'Bebas Revisi Sepuasnya', 'Buku Brand Guidelines PDF', 'Stationery Kit', 'Prioritas Antrian']
];

$gallery = $service['gallery'] ?? [];
if (empty($gallery)) {
    $gallery = [$service['thumbnail'] ?: 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80'];
}

// Sample Reviews
$sampleReviews = [
    [
        'name' => 'Hendro Wijaya',
        'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&auto=format&fit=crop&q=80',
        'rating' => 5,
        'date' => '3 hari yang lalu',
        'package' => 'Paket Standar (Bisnis UMKM)',
        'comment' => 'Luar biasa cepat dan hasilnya sangat elegan! Konsep pertama yang dikirim sudah langsung cocok dengan filosofi kedai kopi kami. Revisi minor warna dilayani dengan sangat ramah dan profesional. Sangat recommended!'
    ],
    [
        'name' => 'Siti Nurhaliza',
        'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&auto=format&fit=crop&q=80',
        'rating' => 5,
        'date' => '1 minggu yang lalu',
        'package' => 'Paket Premium (Korporat Suite)',
        'comment' => 'Puas sekali dengan Brand Guidelines Book yang diberikan. Sangat detail dan rapi, dari palet warna hingga aturan peletakan logo di berbagai media promosi. Kerja cepat dan komunikatif.'
    ],
    [
        'name' => 'Bambang Pratama',
        'avatar' => 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=100&auto=format&fit=crop&q=80',
        'rating' => 5,
        'date' => '2 minggu yang lalu',
        'package' => 'Paket Basic (Startup)',
        'comment' => 'Harga sangat bersahabat untuk kualitas bintang lima. Dalam 24 jam sudah menerima file resolusi tinggi yang siap dipasang di kemasan produk kami. Terima kasih banyak!'
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
    .pkg-col-price {
      font-size: 1.35rem;
      font-weight: 800;
      color: #0284c7;
      margin-bottom: 12px;
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
    .p-price-display {
      font-size: 2rem;
      font-weight: 800;
      color: #0284c7;
      margin-bottom: 8px;
      letter-spacing: -0.5px;
    }
    .p-pkg-title {
      font-size: 1.05rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 6px;
    }
    .p-pkg-desc {
      font-size: 0.84rem;
      color: #64748b;
      margin-bottom: 16px;
      line-height: 1.45;
    }
    .p-meta-pills {
      display: flex;
      align-items: center;
      gap: 16px;
      font-size: 0.85rem;
      font-weight: 700;
      color: #334155;
      margin-bottom: 18px;
      padding-bottom: 14px;
      border-bottom: 1px solid #f1f5f9;
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
    .btn-chat-seller {
      background: #f1f5f9;
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
      transition: all 0.2s;
    }
    .btn-chat-seller:hover {
      background: #e2e8f0;
      color: #0f172a;
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
        <a href="katalog-jasa.php" class="fw-nav-link">&larr; Kembali ke Bursa Jasa</a>
        <a href="worker-katalog-saya.php" class="fw-nav-link">Katalog Saya</a>
        <a href="worker-katalog-tambah.php" class="fw-nav-link" style="color:#0284c7;font-weight:800;">+ Pasang Jasa</a>
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
              <?php echo (int)($service['orders_count'] ?? 312); ?> Pesanan Selesai
            </span>
            <span>•</span>
            <span class="meta-stat-pill">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
              Balas dalam <?php echo htmlspecialchars($service['worker_response_time'] ?? '< 15 Menit', ENT_QUOTES, 'UTF-8'); ?>
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
          <a href="#deskripsi" class="content-tab-btn active">Deskripsi Jasa</a>
          <a href="#paket" class="content-tab-btn">Pilihan Paket</a>
          <a href="#profil" class="content-tab-btn">Tentang Freelancer</a>
          <a href="#ulasan" class="content-tab-btn">Ulasan Pembeli (<?php echo (int)($service['reviews_count'] ?? 148); ?>)</a>
          <a href="#faq" class="content-tab-btn">Syarat & FAQ</a>
        </nav>

        <!-- SECTION 1: Deskripsi Layanan -->
        <section class="detail-card" id="deskripsi">
          <h2 class="card-heading">Deskripsi Layanan & Alur Kerja</h2>
          <div style="font-size:0.92rem;color:#334155;white-space:pre-line;line-height:1.7;">
            <?php echo htmlspecialchars($service['description'] ?? 'Layanan profesional dengan jaminan kepuasan.', ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </section>

        <!-- SECTION 2: Komparasi 3 Paket (as in Image 2) -->
        <section class="detail-card" id="paket">
          <h2 class="card-heading">Perbandingan Pilihan Paket</h2>
          <p style="font-size:0.86rem;color:#64748b;margin-bottom:16px;">Pilih tingkatan paket yang paling sesuai dengan target proyek dan skala anggaran Anda:</p>

          <div class="pkg-compare-grid">
            
            <!-- Basic Column -->
            <div class="pkg-compare-col" id="cardPkgBasic">
              <div class="pkg-col-name"><?php echo htmlspecialchars($basicPkg['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="pkg-col-price"><?php echo gig_format_rupiah((int)$basicPkg['price']); ?></div>
              <div class="pkg-col-desc"><?php echo htmlspecialchars($basicPkg['desc'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div style="font-size:0.78rem;font-weight:700;color:#0f172a;margin-bottom:10px;">⚡ <?php echo (int)$basicPkg['delivery_days']; ?> Hari • 🔄 <?php echo htmlspecialchars((string)$basicPkg['revisions'], ENT_QUOTES, 'UTF-8'); ?></div>
              <ul class="pkg-features-list">
                <?php foreach (($basicPkg['features'] ?? []) as $feat): ?>
                  <li>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span><?php echo htmlspecialchars($feat, ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <button type="button" class="btn-select-pkg" onclick="selectPackageFromCompare('basic')">Pilih Paket Basic</button>
            </div>

            <!-- Standard Column (Popular) -->
            <div class="pkg-compare-col popular selected" id="cardPkgStandard">
              <div class="popular-tag">PALING POPULER</div>
              <div class="pkg-col-name"><?php echo htmlspecialchars($standardPkg['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="pkg-col-price"><?php echo gig_format_rupiah((int)$standardPkg['price']); ?></div>
              <div class="pkg-col-desc"><?php echo htmlspecialchars($standardPkg['desc'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div style="font-size:0.78rem;font-weight:700;color:#0f172a;margin-bottom:10px;">⚡ <?php echo (int)$standardPkg['delivery_days']; ?> Hari • 🔄 <?php echo htmlspecialchars((string)$standardPkg['revisions'], ENT_QUOTES, 'UTF-8'); ?></div>
              <ul class="pkg-features-list">
                <?php foreach (($standardPkg['features'] ?? []) as $feat): ?>
                  <li>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span><?php echo htmlspecialchars($feat, ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <button type="button" class="btn-select-pkg" onclick="selectPackageFromCompare('standard')">Pilih Paket Standar</button>
            </div>

            <!-- Premium Column -->
            <div class="pkg-compare-col" id="cardPkgPremium">
              <div class="pkg-col-name"><?php echo htmlspecialchars($premiumPkg['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="pkg-col-price"><?php echo gig_format_rupiah((int)$premiumPkg['price']); ?></div>
              <div class="pkg-col-desc"><?php echo htmlspecialchars($premiumPkg['desc'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div style="font-size:0.78rem;font-weight:700;color:#0f172a;margin-bottom:10px;">⚡ <?php echo (int)$premiumPkg['delivery_days']; ?> Hari • 🔄 <?php echo htmlspecialchars((string)$premiumPkg['revisions'], ENT_QUOTES, 'UTF-8'); ?></div>
              <ul class="pkg-features-list">
                <?php foreach (($premiumPkg['features'] ?? []) as $feat): ?>
                  <li>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span><?php echo htmlspecialchars($feat, ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <button type="button" class="btn-select-pkg" onclick="selectPackageFromCompare('premium')">Pilih Paket Premium</button>
            </div>

          </div>
        </section>

        <!-- SECTION 3: Profil Freelancer (as in Image 2) -->
        <section class="detail-card" id="profil">
          <h2 class="card-heading">Tentang Freelancer</h2>
          <div class="seller-profile-card">
            <img src="<?php echo htmlspecialchars($service['worker_avatar'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80', ENT_QUOTES, 'UTF-8'); ?>" class="seller-avatar-large" alt="Avatar Freelancer" />
            <div class="seller-info-details">
              <div class="seller-name-row">
                <span class="seller-full-name"><?php echo htmlspecialchars($service['worker_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span style="background:#e0f2fe;color:#0284c7;font-size:0.72rem;font-weight:800;padding:2px 8px;border-radius:6px;"><?php echo htmlspecialchars($service['worker_badge'] ?? 'Super Seller', ENT_QUOTES, 'UTF-8'); ?></span>
                <span style="background:#ecfdf5;color:#059669;font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:6px;">Online Sekarang</span>
              </div>
              <div class="seller-meta-row">
                <span>📍 Indonesia</span>
                <span>•</span>
                <span>⭐ Rating 4.95 / 5.0</span>
                <span>•</span>
                <span>⚡ Kecepatan Respon: <?php echo htmlspecialchars($service['worker_response_time'] ?? '< 15 Menit', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <p style="font-size:0.86rem;color:#475569;margin-bottom:14px;line-height:1.5;">Desainer grafis & creative brand specialist berdedikasi dengan fokus pada hasil estetis, fungsional, dan orisinal. Berkomitmen memberikan kepuasan maksimal bagi setiap klien.</p>
              <div style="display:flex;gap:12px;">
                <button type="button" class="btn-chat-seller" style="width:auto;padding:8px 16px;font-size:0.82rem;" onclick="openChatModal()">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                  Hubungi Freelancer
                </button>
                <a href="worker-profile.php?id=tessa" target="_blank" style="padding:8px 16px;font-size:0.82rem;background:#fff;border:1px solid #cbd5e1;border-radius:10px;text-decoration:none;color:#334155;font-weight:700;display:inline-flex;align-items:center;">
                  Lihat Profil Lengkap &rarr;
                </a>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 4: Ulasan Klien (as in Image 2) -->
        <section class="detail-card" id="ulasan">
          <h2 class="card-heading">Ulasan & Penilaian Pembeli</h2>
          
          <div class="reviews-score-hero">
            <div>
              <div class="big-score-val">4.95</div>
              <div style="color:#f59e0b;font-size:1.1rem;font-weight:800;letter-spacing:2px;margin-bottom:4px;">★★★★★</div>
              <div style="font-size:0.82rem;color:#64748b;"><?php echo (int)($service['reviews_count'] ?? 148); ?> penilaian terverifikasi</div>
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
                    <img src="<?php echo htmlspecialchars($rev['avatar'], ENT_QUOTES, 'UTF-8'); ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt="Avatar Pembeli" />
                    <div>
                      <div style="font-size:0.88rem;font-weight:700;color:#0f172a;"><?php echo htmlspecialchars($rev['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                      <div style="font-size:0.75rem;color:#94a3b8;"><?php echo htmlspecialchars($rev['date'], ENT_QUOTES, 'UTF-8'); ?> • <span style="color:#2563eb;font-weight:600;"><?php echo htmlspecialchars($rev['package'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                  </div>
                  <div style="color:#f59e0b;font-size:0.9rem;font-weight:800;">★★★★★</div>
                </div>
                <p style="font-size:0.88rem;color:#334155;line-height:1.55;margin:0;"><?php echo htmlspecialchars($rev['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- SECTION 5: Kebutuhan Klien & FAQ -->
        <section class="detail-card" id="faq">
          <h2 class="card-heading">Syarat Pemesanan & Tanya Jawab (FAQ)</h2>
          
          <div style="margin-bottom:20px;">
            <div style="font-size:0.92rem;font-weight:700;color:#0f172a;margin-bottom:8px;">Yang Perlu Disiapkan Sebelum Memesan:</div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;font-size:0.86rem;color:#475569;white-space:pre-line;">
              <?php echo htmlspecialchars($service['client_requirements'] ?? "1. Nama brand/bisnis Anda\n2. Bidang usaha dan target pasar\n3. Preferensi gaya & warna yang disukai", ENT_QUOTES, 'UTF-8'); ?>
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
                    <div style="font-size:0.9rem;font-weight:800;color:#0284c7;"><?php echo gig_format_rupiah((int)($rel['price_starting'] ?? 150000)); ?></div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

      </div>

      <!-- RIGHT STICKY SIDEBAR (Purchase Card as in Image 2) -->
      <aside class="right-sticky-wrap">
        <div class="purchase-box-card">
          <!-- Package Tabs Header -->
          <div class="purchase-box-tabs">
            <button type="button" class="p-tab-btn" id="tabBtnBasic" onclick="switchSidebarPkg('basic')">Basic</button>
            <button type="button" class="p-tab-btn active" id="tabBtnStandard" onclick="switchSidebarPkg('standard')">Standar</button>
            <button type="button" class="p-tab-btn" id="tabBtnPremium" onclick="switchSidebarPkg('premium')">Premium</button>
          </div>

          <div class="purchase-box-body">
            <div class="p-price-display" id="sidePrice"><?php echo gig_format_rupiah((int)$standardPkg['price']); ?></div>
            <div class="p-pkg-title" id="sideTitle"><?php echo htmlspecialchars($standardPkg['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="p-pkg-desc" id="sideDesc"><?php echo htmlspecialchars($standardPkg['desc'], ENT_QUOTES, 'UTF-8'); ?></div>

            <div class="p-meta-pills">
              <span style="display:flex;align-items:center;gap:4px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                <span id="sideDays">⚡ <?php echo (int)$standardPkg['delivery_days']; ?> Hari Pengerjaan</span>
              </span>
              <span style="display:flex;align-items:center;gap:4px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                <span id="sideRevisions">🔄 <?php echo htmlspecialchars((string)$standardPkg['revisions'], ENT_QUOTES, 'UTF-8'); ?></span>
              </span>
            </div>

            <!-- Features Checklist -->
            <ul class="pkg-features-list" id="sideFeatures" style="margin-bottom:20px;">
              <?php foreach (($standardPkg['features'] ?? []) as $feat): ?>
                <li>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  <span><?php echo htmlspecialchars($feat, ENT_QUOTES, 'UTF-8'); ?></span>
                </li>
              <?php endforeach; ?>
            </ul>

            <!-- Order Button -->
            <button type="button" class="btn-order-primary" onclick="openOrderModal()">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
              Pesan Jasa Ini Sekarang
            </button>

            <!-- Chat Button -->
            <button type="button" class="btn-chat-seller" onclick="openChatModal()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              Chat & Diskusi dengan Freelancer
            </button>

            <!-- Escrow Trust Box -->
            <div class="escrow-trust-box">
              <div style="font-weight:800;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Garansi Pembayaran KarirHub Escrow
              </div>
              <p style="margin:0;">Dana Anda ditampung secara aman. Pembayaran baru diteruskan kepada freelancer setelah hasil akhir diserahkan dan disetujui oleh Anda.</p>
            </div>

          </div>
        </div>
      </aside>

    </div>
  </div>

  <!-- ORDER MODAL (Checkout Simulation) -->
  <div class="modal-backdrop" id="orderModal">
    <div class="modal-content-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="margin:0;font-size:1.2rem;font-weight:800;color:#0f172a;">Konfirmasi Pemesanan Jasa</h3>
        <button type="button" onclick="closeOrderModal()" style="background:none;border:none;font-size:1.5rem;color:#94a3b8;cursor:pointer;">&times;</button>
      </div>
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:16px;">
        <div style="font-size:0.82rem;color:#64748b;">Paket Dipilih:</div>
        <div style="font-size:1rem;font-weight:800;color:#0f172a;" id="modalPkgName">Paket Standar (Bisnis UMKM)</div>
        <div style="font-size:1.25rem;font-weight:800;color:#0284c7;margin-top:4px;" id="modalPkgPrice">Rp 350.000</div>
      </div>
      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:0.84rem;font-weight:700;color:#1e293b;margin-bottom:6px;">Catatan Singkat / Brief untuk Freelancer:</label>
        <textarea id="orderBrief" style="width:100%;height:80px;padding:10px;border:1.5px solid #cbd5e1;border-radius:10px;font-family:inherit;font-size:0.86rem;outline:none;" placeholder="Tuliskan nama brand, ide, atau instruksi awal..."></textarea>
      </div>
      <div style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:10px;padding:10px 14px;font-size:0.78rem;color:#065f46;margin-bottom:18px;">
        🛡️ Pembayaran menggunakan sistem Rekening Bersama KarirHub. Aman & Bergaransi.
      </div>
      <button type="button" class="btn-order-primary" onclick="submitSimulatedOrder()">
        Lanjutkan Pembayaran Escrow
      </button>
    </div>
  </div>

  <!-- CHAT INQUIRY MODAL -->
  <div class="modal-backdrop" id="chatModal">
    <div class="modal-content-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="margin:0;font-size:1.2rem;font-weight:800;color:#0f172a;">Kirim Pesan ke <?php echo htmlspecialchars($service['worker_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <button type="button" onclick="closeChatModal()" style="background:none;border:none;font-size:1.5rem;color:#94a3b8;cursor:pointer;">&times;</button>
      </div>
      <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:16px;">
        <img src="<?php echo htmlspecialchars($service['worker_avatar'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80', ENT_QUOTES, 'UTF-8'); ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt="Avatar" />
        <div>
          <div style="font-size:0.88rem;font-weight:700;color:#0f172a;"><?php echo htmlspecialchars($service['worker_name'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div style="font-size:0.74rem;color:#10b981;font-weight:700;">● Online (Respon &lt; 15 menit)</div>
        </div>
      </div>
      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:0.84rem;font-weight:700;color:#1e293b;margin-bottom:6px;">Pesan Anda:</label>
        <textarea id="chatMsgInput" style="width:100%;height:100px;padding:10px;border:1.5px solid #cbd5e1;border-radius:10px;font-family:inherit;font-size:0.86rem;outline:none;">Halo kak <?php echo htmlspecialchars(explode(' ', $service['worker_name'])[0], ENT_QUOTES, 'UTF-8'); ?>, saya tertarik dengan layanan "<?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?>". Apakah bisa konsultasi terlebih dahulu mengenai konsep proyek saya?</textarea>
      </div>
      <button type="button" class="btn-order-primary" onclick="sendSimulatedChat()">
        Kirim Pesan Sekarang
      </button>
    </div>
  </div>

  <script>
    const packageData = {
      basic: {
        name: <?php echo json_encode($basicPkg['name']); ?>,
        price: <?php echo json_encode(gig_format_rupiah((int)$basicPkg['price'])); ?>,
        desc: <?php echo json_encode($basicPkg['desc']); ?>,
        days: <?php echo (int)$basicPkg['delivery_days']; ?>,
        revisions: <?php echo json_encode($basicPkg['revisions']); ?>,
        features: <?php echo json_encode($basicPkg['features'] ?? []); ?>
      },
      standard: {
        name: <?php echo json_encode($standardPkg['name']); ?>,
        price: <?php echo json_encode(gig_format_rupiah((int)$standardPkg['price'])); ?>,
        desc: <?php echo json_encode($standardPkg['desc']); ?>,
        days: <?php echo (int)$standardPkg['delivery_days']; ?>,
        revisions: <?php echo json_encode($standardPkg['revisions']); ?>,
        features: <?php echo json_encode($standardPkg['features'] ?? []); ?>
      },
      premium: {
        name: <?php echo json_encode($premiumPkg['name']); ?>,
        price: <?php echo json_encode(gig_format_rupiah((int)$premiumPkg['price'])); ?>,
        desc: <?php echo json_encode($premiumPkg['desc']); ?>,
        days: <?php echo (int)$premiumPkg['delivery_days']; ?>,
        revisions: <?php echo json_encode($premiumPkg['revisions']); ?>,
        features: <?php echo json_encode($premiumPkg['features'] ?? []); ?>
      }
    };

    let activePkgKey = 'standard';

    function switchGalleryImg(url, el) {
      document.getElementById('mainGalleryImg').src = url;
      document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
      el.classList.add('active');
    }

    function switchSidebarPkg(key) {
      activePkgKey = key;
      const pkg = packageData[key];
      if (!pkg) return;

      document.querySelectorAll('.p-tab-btn').forEach(btn => btn.classList.remove('active'));
      const activeBtn = document.getElementById('tabBtn' + key.charAt(0).toUpperCase() + key.slice(1));
      if (activeBtn) activeBtn.classList.add('active');

      document.getElementById('sidePrice').innerText = pkg.price;
      document.getElementById('sideTitle').innerText = pkg.name;
      document.getElementById('sideDesc').innerText = pkg.desc;
      document.getElementById('sideDays').innerText = '⚡ ' + pkg.days + ' Hari Pengerjaan';
      document.getElementById('sideRevisions').innerText = '🔄 ' + pkg.revisions;

      const featList = document.getElementById('sideFeatures');
      featList.innerHTML = '';
      pkg.features.forEach(f => {
        const li = document.createElement('li');
        li.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>' + f + '</span>';
        featList.appendChild(li);
      });

      // Sync Compare Columns
      document.querySelectorAll('.pkg-compare-col').forEach(c => c.classList.remove('selected'));
      const compareCol = document.getElementById('cardPkg' + key.charAt(0).toUpperCase() + key.slice(1));
      if (compareCol) compareCol.classList.add('selected');
    }

    function selectPackageFromCompare(key) {
      switchSidebarPkg(key);
      document.querySelector('.right-sticky-wrap').scrollIntoView({ behavior: 'smooth' });
    }

    function openOrderModal() {
      const pkg = packageData[activePkgKey];
      document.getElementById('modalPkgName').innerText = pkg.name;
      document.getElementById('modalPkgPrice').innerText = pkg.price;
      document.getElementById('orderModal').classList.add('open');
    }

    function closeOrderModal() {
      document.getElementById('orderModal').classList.remove('open');
    }

    function submitSimulatedOrder() {
      alert('Pemesanan berhasil diproses! Dana telah diamankan di Escrow KarirHub dan notifikasi telah dikirimkan ke freelancer.');
      closeOrderModal();
    }

    function openChatModal() {
      document.getElementById('chatModal').classList.add('open');
    }

    function closeChatModal() {
      document.getElementById('chatModal').classList.remove('open');
    }

    function sendSimulatedChat() {
      alert('Pesan Anda berhasil terkirim ke freelancer! Freelancer akan membalas melalui ruang pesan KarirHub.');
      closeChatModal();
    }
  </script>
</body>
</html>


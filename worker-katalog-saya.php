<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/includes/worker-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/user-avatars.php';
require_once __DIR__ . '/includes/katalog-store.php';

$username = (string)($_SESSION['username'] ?? $_SESSION['siapkerja_name'] ?? 'theressaz@pasker.id');
$workerObj = gig_find_worker($username) ?? gig_find_worker('tessa');
$displayName = $workerObj['name'] ?? ($_SESSION['siapkerja_name'] ?? 'Theressa Zaratrusha');
$userInitials = $workerObj['initials'] ?? 'TH';
$workerAvatarUrl = gig_worker_display_photo((string)($workerObj['id'] ?? 'tessa'));

// Handle Delete or Status Toggle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_katalog' && !empty($_POST['id'])) {
        gig_katalog_delete((string)$_POST['id']);
        $_SESSION['flash_success'] = 'Katalog jasa berhasil dihapus.';
        header('Location: worker-katalog-saya.php');
        exit;
    }
}

$myServices = gig_katalog_by_worker($username);
// Fallback if none found for current session id, grab services for tessa or first 2 items
if (empty($myServices)) {
    $myServices = gig_katalog_by_worker('theressaz@pasker.id');
}

$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$totalViews = 1420;
$totalOrders = 0;
$ratingSum = 0;
foreach ($myServices as $s) {
    $totalOrders += (int)($s['orders_count'] ?? 0);
    $ratingSum += (float)($s['rating'] ?? 5.0);
}
$avgRating = count($myServices) > 0 ? round($ratingSum / count($myServices), 1) : 5.0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Katalog Jasa Saya | KarirHub Gig Workers</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/employer.css" />
  <link rel="stylesheet" href="assets/worker.css" />
  <style>
    :root {
      --primary-blue: #1657c1;
      --primary-dark: #0e3b87;
      --primary-light: #eff6ff;
      --accent-orange: #f59e0b;
      --border-subtle: #e2e8f0;
      --text-dark: #0f172a;
      --text-muted: #64748b;
    }
    body {
      background-color: #f8fafc;
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: #1e293b;
      margin: 0;
      padding: 0;
    }
    .katalog-header-nav {
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      padding: 14px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 50;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .brand-section {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .brand-badge {
      background: #0284c7;
      color: #fff;
      font-weight: 800;
      border-radius: 8px;
      padding: 6px 12px;
      font-size: 0.95rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .nav-breadcrumbs {
      font-size: 0.85rem;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .nav-breadcrumbs a {
      color: #2563eb;
      text-decoration: none;
      font-weight: 600;
    }
    .nav-breadcrumbs span.active {
      color: #0f172a;
      font-weight: 700;
    }
    .main-wrap {
      max-width: 1240px;
      margin: 28px auto 60px;
      padding: 0 20px;
    }
    /* Stats Bar */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 18px 20px;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .stat-label {
      font-size: 0.82rem;
      color: #64748b;
      font-weight: 600;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .stat-value {
      font-size: 1.65rem;
      font-weight: 800;
      color: #0f172a;
    }
    /* Banner Header */
    .dashboard-banner {
      background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
      color: #fff;
      padding: 26px 30px;
      border-radius: 16px;
      margin-bottom: 26px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .btn-create-new {
      background: #2563eb;
      color: #fff;
      font-weight: 800;
      padding: 12px 22px;
      border-radius: 10px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
      transition: all 0.2s;
      white-space: nowrap;
    }
    .btn-create-new:hover {
      background: #1d4ed8;
      transform: translateY(-2px);
    }
    .btn-bursa-view {
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.3);
      color: #fff;
      font-weight: 700;
      padding: 12px 20px;
      border-radius: 10px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
      white-space: nowrap;
    }
    .btn-bursa-view:hover {
      background: rgba(255, 255, 255, 0.25);
    }
    /* Service List Cards */
    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
      gap: 20px;
    }
    .service-item-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
      display: flex;
      flex-direction: column;
      transition: all 0.2s ease;
    }
    .service-item-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
      border-color: #cbd5e1;
    }
    .card-thumb-wrap {
      position: relative;
      height: 190px;
      background: #e2e8f0;
      overflow: hidden;
    }
    .card-thumb-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s;
    }
    .service-item-card:hover .card-thumb-img {
      transform: scale(1.03);
    }
    .card-status-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      background: #10b981;
      color: #fff;
      font-size: 0.72rem;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .card-body-content {
      padding: 18px 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .card-category-tag {
      font-size: 0.76rem;
      font-weight: 700;
      color: #2563eb;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }
    .card-title-text {
      font-size: 1.05rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.35;
      margin: 0 0 10px 0;
      height: 48px;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }
    .card-stats-mini {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.82rem;
      color: #64748b;
      margin-bottom: 14px;
    }
    .card-actions-row {
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
      padding: 12px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .btn-action-view {
      background: #eff6ff;
      color: #2563eb;
      font-weight: 700;
      font-size: 0.84rem;
      padding: 8px 14px;
      border-radius: 8px;
      text-decoration: none;
      border: 1px solid #bfdbfe;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .btn-action-view:hover {
      background: #2563eb;
      color: #fff;
    }
    .btn-action-del {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
      font-size: 0.82rem;
      font-weight: 700;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.2s;
    }
    .btn-action-del:hover {
      background: #dc2626;
      color: #fff;
    }
    /* Success Alert */
    .alert-success {
      background: #ecfdf5;
      border: 1.5px solid #6ee7b7;
      color: #065f46;
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 22px;
      font-weight: 700;
      font-size: 0.92rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }
  </style>
</head>
<body>

  <!-- Top Header Navigation -->
  <header class="katalog-header-nav">
    <div class="brand-section">
      <a href="katalog-jasa.php" class="brand-badge" title="Bursa Katalog Jasa">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        KarirHub Jasa
      </a>
      <div class="nav-breadcrumbs">
        <a href="worker-bursa.php">Bursa Proyek</a>
        <span>&rsaquo;</span>
        <span class="active">Katalog Jasa Saya</span>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="worker-bursa.php" style="font-size:0.86rem;color:#475569;text-decoration:none;font-weight:600;padding:6px 12px;">Bursa Proyek</a>
      <a href="katalog-jasa.php" style="font-size:0.86rem;color:#0284c7;text-decoration:none;font-weight:700;padding:6px 12px;background:#f0f9ff;border-radius:8px;border:1px solid #bae6fd;">Bursa Jasa</a>
      <a href="worker-katalog-tambah.php" style="font-size:0.86rem;color:#ffffff;text-decoration:none;font-weight:800;padding:6px 14px;background:#0284c7;border-radius:8px;box-shadow:0 2px 6px rgba(2,132,199,0.25);">+ Upload Jasa</a>
      <a href="worker-profile.php?id=tessa" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#0f172a;font-weight:700;font-size:0.86rem;padding:4px 10px;background:#f1f5f9;border-radius:20px;">
        <?php echo gig_katalog_render_avatar($displayName, 24, '0.68rem'); ?>
        <span><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
      </a>
    </div>
  </header>

  <div class="main-wrap">

    <?php if (!empty($flashSuccess)): ?>
      <div class="alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>

    <!-- Banner -->
    <div class="dashboard-banner">
      <div>
        <h1 style="margin:0 0 8px 0;font-size:1.6rem;font-weight:800;letter-spacing:-0.3px;">Kelola Katalog Jasa Saya</h1>
        <p style="margin:0;font-size:0.92rem;color:#cbd5e1;max-width:650px;">Tampilkan portofolio deliverables dan cakupan keahlian jasa Anda agar perusahaan pemberi kerja dapat langsung menawarkan proyek aktif.</p>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <a href="katalog-jasa.php" class="btn-bursa-view">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Lihat di Bursa Marketplace
        </a>
        <a href="worker-katalog-tambah.php" class="btn-create-new">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          + Upload Jasa Baru
        </a>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">
          <span>Total Katalog Aktif</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
        </div>
        <div class="stat-value"><?php echo count($myServices); ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">
          <span>Dilihat Perusahaan</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
        </div>
        <div class="stat-value"><?php echo number_format($totalViews, 0, ',', '.'); ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">
          <span>Total Pesanan Selesai</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="stat-value"><?php echo $totalOrders; ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">
          <span>Rating Pelayanan</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <div class="stat-value">★ <?php echo number_format($avgRating, 1); ?> <span style="font-size:0.85rem;color:#64748b;font-weight:600;">/ 5.0</span></div>
      </div>
    </div>

    <!-- Catalogue List -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
      <h2 style="margin:0;font-size:1.25rem;font-weight:800;color:#0f172a;">Daftar Katalog Jasa Terbit (<?php echo count($myServices); ?>)</h2>
      <span style="font-size:0.82rem;color:#64748b;">Semua katalog terhubung langsung ke marketplace KarirHub</span>
    </div>

    <?php if (empty($myServices)): ?>
      <div style="background:#fff;border:1px dashed #cbd5e1;border-radius:16px;padding:48px 24px;text-align:center;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:12px;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        <h3 style="margin:0 0 6px 0;font-size:1.1rem;color:#0f172a;">Belum ada katalog jasa yang diunggah</h3>
        <p style="margin:0 0 18px 0;color:#64748b;font-size:0.88rem;">Mulai tawarkan keahlian Anda sekarang dengan membuat penawaran jasa pertama Anda.</p>
        <a href="worker-katalog-tambah.php" class="btn-create-new" style="display:inline-flex;">+ Upload Katalog Jasa Baru</a>
      </div>
    <?php else: ?>
      <div class="services-grid">
        <?php foreach ($myServices as $item): ?>
          <div class="service-item-card">
            <div class="card-thumb-wrap">
              <img src="<?php echo htmlspecialchars($item['thumbnail'] ?: 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" class="card-thumb-img" />
              <span class="card-status-badge">Tayang Aktif</span>
            </div>
            <div class="card-body-content">
              <div class="card-category-tag"><?php echo htmlspecialchars($item['category'] . ' • ' . ($item['subcategory'] ?? 'Layanan'), ENT_QUOTES, 'UTF-8'); ?></div>
              <h3 class="card-title-text"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <div class="card-stats-mini">
                <span style="color:#f59e0b;font-weight:800;">★ <?php echo number_format((float)($item['rating'] ?? 5.0), 1); ?></span>
                <span>(<?php echo (int)($item['reviews_count'] ?? 0); ?> ulasan)</span>
                <span>•</span>
                <span><?php echo (int)($item['orders_count'] ?? 0); ?> pesanan</span>
                <span>•</span>
                <span style="color:#64748b;font-weight:600;"><?php echo (int)($item['delivery_days'] ?? 2); ?> Hari</span>
              </div>
              <div class="card-status-info-row" style="margin-top:auto;padding-top:14px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:0.8rem;color:#64748b;font-weight:600;">Status Katalog:</span>
                <span style="font-size:0.82rem;font-weight:700;color:#0369a1;background:#e0f2fe;padding:3px 10px;border-radius:6px;">Aktif & Siap Ditugaskan</span>
              </div>
            </div>
            <div class="card-actions-row">
              <a href="katalog-jasa-detail.php?id=<?php echo urlencode($item['id']); ?>" target="_blank" class="btn-action-view" title="Buka Halaman Detail Layanan">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                Lihat di Marketplace
              </a>
              <form action="worker-katalog-saya.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus katalog jasa ini?');" style="margin:0;">
                <input type="hidden" name="action" value="delete_katalog" />
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>" />
                <button type="submit" class="btn-action-del" title="Hapus Jasa">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  Hapus
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>

</body>
</html>


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

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_katalog' && !empty($_POST['id'])) {
        gig_katalog_delete((string)$_POST['id']);
        $_SESSION['flash_success'] = 'Katalog jasa berhasil dihapus.';
        header('Location: worker-katalog-saya.php');
        exit;
    }
}

$myServices = gig_katalog_by_worker($username);
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

$pageTitle = 'Katalog Jasa Saya';
$pageKey = 'katalog';
$breadcrumbCurrent = 'Katalog Jasa Saya';
require __DIR__ . '/includes/worker-layout-start.php';
?>

<style>
  .katalog-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 20px;
    margin-top: 24px;
  }
  .katalog-item-card {
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }
  .katalog-item-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: #93c5fd;
  }
  .katalog-thumb-box {
    position: relative;
    width: 100%;
    height: 175px;
    background: #0f172a;
    overflow: hidden;
  }
  .katalog-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }
  .katalog-item-card:hover .katalog-thumb-img {
    transform: scale(1.04);
  }
  .katalog-badge-status {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #10b981;
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  }
  .katalog-card-body {
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
  }
  .katalog-cat-pill {
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--primary-blue);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
  }
  .katalog-card-title {
    font-size: 1rem;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.4;
    margin: 0 0 10px 0;
    height: 44px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
  .katalog-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 14px;
  }
  .katalog-card-footer {
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .katalog-actions-bar {
    background: #f8fafc;
    border-top: 1px solid var(--border-subtle);
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .btn-katalog-view {
    background: #eff6ff;
    color: var(--primary-blue);
    font-weight: 700;
    font-size: 0.82rem;
    padding: 7px 12px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
  }
  .btn-katalog-view:hover {
    background: #dbeafe;
    color: #1d4ed8;
  }
  .btn-katalog-del {
    background: none;
    border: none;
    color: #ef4444;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 7px 10px;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
  .btn-katalog-del:hover {
    background: #fef2f2;
  }
</style>

<div class="page-toolbar">
  <div>
    <h1>Katalog Jasa Saya</h1>
    <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">Kelola sampel karya dan cakupan deliverables keahlian Anda untuk menarik penugasan proyek dari perusahaan.</p>
  </div>
  <div style="display:flex;align-items:center;gap:10px;">
    <a href="katalog-jasa.php" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:10px;font-size:0.85rem;font-weight:700;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Bursa Katalog Jasa
    </a>
    <a href="worker-katalog-tambah.php" class="btn-primary-add" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:10px;font-size:0.85rem;font-weight:700;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      + Upload Katalog Jasa
    </a>
  </div>
</div>

<?php if (!empty($flashSuccess)): ?>
  <div class="notice-bar" style="margin-bottom:20px;background:#f0fdf4;border-color:#bbf7d0;color:#166534;">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></span>
  </div>
<?php endif; ?>

<!-- Stats Grid matching Worker Dashboard -->
<section class="stats-grid" style="margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">TOTAL KATALOG AKTIF</span></div>
    <div class="stat-number"><?php echo count($myServices); ?></div>
    <div class="stat-caption">Tayang di Bursa Katalog Jasa</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">DILIHAT PERUSAHAAN</span></div>
    <div class="stat-number"><?php echo number_format($totalViews, 0, ',', '.'); ?></div>
    <div class="stat-caption">Total impresi profil jasa</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">PROYEK SELESAI</span></div>
    <div class="stat-number"><?php echo (int)$totalOrders; ?></div>
    <div class="stat-caption">Penugasan dari perusahaan</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">RATA-RATA PENILAIAN</span></div>
    <div class="stat-number">⭐ <?php echo number_format($avgRating, 1); ?></div>
    <div class="stat-caption">Dari ulasan perusahaan mitra</div>
  </div>
</section>

<!-- List of Catalogues -->
<?php if (empty($myServices)): ?>
  <div style="background:#ffffff;border:1px dashed var(--border-subtle);border-radius:var(--radius-lg);padding:50px 20px;text-align:center;">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:12px;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    <h3 style="margin:0 0 6px 0;font-size:1.1rem;color:var(--text-main);">Belum ada katalog jasa yang diunggah</h3>
    <p style="margin:0 0 18px 0;color:var(--text-muted);font-size:0.88rem;">Tampilkan keahlian dan sampel hasil kerja Anda agar perusahaan pemberi kerja dapat langsung menawarkan proyek aktif.</p>
    <a href="worker-katalog-tambah.php" class="btn-primary-add" style="display:inline-flex;text-decoration:none;">+ Upload Katalog Jasa Baru</a>
  </div>
<?php else: ?>
  <div class="katalog-services-grid">
    <?php foreach ($myServices as $item): ?>
      <div class="katalog-item-card">
        <div class="katalog-thumb-box">
          <img src="<?php echo htmlspecialchars($item['thumbnail'] ?: 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" class="katalog-thumb-img" />
          <span class="katalog-badge-status">Tayang Aktif</span>
        </div>
        <div class="katalog-card-body">
          <div class="katalog-cat-pill"><?php echo htmlspecialchars($item['category'] . ' • ' . ($item['subcategory'] ?? 'Layanan'), ENT_QUOTES, 'UTF-8'); ?></div>
          <h3 class="katalog-card-title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <div class="katalog-meta-row">
            <span style="color:#f59e0b;font-weight:800;">★ <?php echo number_format((float)($item['rating'] ?? 5.0), 1); ?></span>
            <span>(<?php echo (int)($item['reviews_count'] ?? 0); ?> ulasan)</span>
            <span>•</span>
            <span style="color:var(--text-muted);font-weight:600;"><?php echo (int)($item['delivery_days'] ?? 2); ?> Hari Kerja</span>
          </div>
          <div class="katalog-card-footer">
            <span style="font-size:0.8rem;color:var(--text-muted);font-weight:600;">Status Penugasan:</span>
            <span style="font-size:0.78rem;font-weight:700;color:#0369a1;background:#e0f2fe;padding:3px 8px;border-radius:6px;">Siap Ditugaskan</span>
          </div>
        </div>
        <div class="katalog-actions-bar">
          <a href="katalog-jasa-detail.php?id=<?php echo urlencode($item['id']); ?>" target="_blank" class="btn-katalog-view" title="Buka Detail di Marketplace">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Buka Halaman Detail
          </a>
          <form action="worker-katalog-saya.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus katalog jasa ini?');" style="margin:0;">
            <input type="hidden" name="action" value="delete_katalog" />
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>" />
            <button type="submit" class="btn-katalog-del" title="Hapus Katalog">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              Hapus
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>

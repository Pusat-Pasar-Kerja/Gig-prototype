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

$role = (string)($_SESSION['role'] ?? 'worker');
$employerUser = (string)($_SESSION['employer_user'] ?? '');
$isEmployer = ($role === 'employer' || !empty($employerUser));

// Filter parameters
$searchQ = trim((string)($_GET['q'] ?? ''));
$selectedCategory = trim((string)($_GET['category'] ?? 'Semua'));
$selectedSubcat = trim((string)($_GET['subcat'] ?? 'Semua'));
$selectedTag = trim((string)($_GET['tag'] ?? 'Semua'));
$sort = trim((string)($_GET['sort'] ?? 'recommended'));

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

// Sorting
$services = array_values($services);
if ($sort === 'popular') {
    usort($services, fn($a, $b) => ($b['orders_count'] ?? 0) <=> ($a['orders_count'] ?? 0));
} elseif ($sort === 'rating') {
    usort($services, fn($a, $b) => ($b['rating'] ?? 5.0) <=> ($a['rating'] ?? 5.0));
}

$pageTitle = 'Bursa Katalog Jasa';
$pageKey = 'katalog';
$breadcrumbCurrent = 'Bursa Katalog Jasa';

if ($isEmployer) {
    require __DIR__ . '/includes/employer-layout-start.php';
} else {
    require __DIR__ . '/includes/worker-layout-start.php';
}
?>

<style>
  /* Filter Card Toolbar */
  .bursa-filter-card {
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 18px 20px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
  }
  .bursa-search-row {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 14px;
  }
  .bursa-search-input-box {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8fafc;
    border: 1.5px solid var(--border-subtle);
    border-radius: 12px;
    padding: 8px 14px;
    transition: all 0.2s;
  }
  .bursa-search-input-box:focus-within {
    border-color: var(--primary-blue);
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(22, 87, 193, 0.1);
  }
  .bursa-search-input-box input {
    border: none;
    background: transparent;
    width: 100%;
    outline: none;
    font-size: 0.9rem;
    font-family: inherit;
    color: var(--text-main);
  }
  .bursa-cat-pills {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 12px;
  }
  .bursa-pill-btn {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
    color: var(--text-muted);
    background: #f1f5f9;
    border: 1px solid transparent;
    transition: all 0.2s;
  }
  .bursa-pill-btn:hover {
    background: #e2e8f0;
    color: var(--text-main);
  }
  .bursa-pill-btn.active {
    background: #eff6ff;
    color: var(--primary-blue);
    border-color: #bfdbfe;
  }
  .bursa-tag-pills {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    padding-top: 10px;
    border-top: 1px solid var(--border-subtle);
  }
  .bursa-tag-btn {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.76rem;
    font-weight: 600;
    text-decoration: none;
    color: #475569;
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    transition: all 0.15s;
  }
  .bursa-tag-btn:hover {
    border-color: #93c5fd;
    color: var(--primary-blue);
  }
  .bursa-tag-btn.active {
    background: var(--primary-blue);
    color: #ffffff;
    border-color: var(--primary-blue);
  }
  
  /* Services Grid */
  .fw-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
  }
  .fw-card {
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }
  .fw-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: #93c5fd;
  }
  .fw-card-thumb-wrap {
    position: relative;
    width: 100%;
    height: 180px;
    background: #0f172a;
    overflow: hidden;
  }
  .fw-card-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }
  .fw-card:hover .fw-card-thumb {
    transform: scale(1.04);
  }
  .fw-card-body {
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
  }
  .fw-card-seller {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
  }
  .fw-card-seller-name {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-main);
  }
  .fw-card-title {
    font-size: 0.96rem;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.4;
    margin: 0 0 8px 0;
    height: 42px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
  .fw-card-tag-badge {
    align-self: flex-start;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--primary-blue);
    background: #eff6ff;
    padding: 2px 8px;
    border-radius: 4px;
    margin-bottom: 12px;
  }
  .fw-card-footer {
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .fw-card-rating {
    font-size: 0.8rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 4px;
  }
  .fw-card-rating-star {
    color: #f59e0b;
    font-weight: 800;
  }
  
  /* Worker Promo Banner */
  .bursa-promo-banner {
    background: linear-gradient(135deg, #092c4c 0%, #1657c1 100%);
    color: #ffffff;
    padding: 28px 32px;
    border-radius: var(--radius-xl);
    margin-top: 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: var(--shadow-md);
  }
</style>

<div class="page-toolbar">
  <div>
    <h1>Bursa Katalog Jasa & Deliverables</h1>
    <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">Jelajahi sampel karya dan cakupan deliverables dari talenta Gig Workers untuk kebutuhan proyek perusahaan.</p>
  </div>
  <?php if (!$isEmployer): ?>
    <div>
      <a href="worker-katalog-tambah.php" class="btn-primary-add" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:10px;font-size:0.85rem;font-weight:700;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        + Pasang Katalog Jasa
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- Filter Toolbar Card -->
<div class="bursa-filter-card">
  <!-- Search & Sort Row -->
  <div class="bursa-search-row">
    <form action="katalog-jasa.php" method="GET" class="bursa-search-input-box" style="margin:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="q" placeholder="Cari jasa desain logo, brand identity, website, video..." value="<?php echo htmlspecialchars($searchQ, ENT_QUOTES, 'UTF-8'); ?>" />
      <?php if (!empty($selectedCategory) && $selectedCategory !== 'Semua'): ?>
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8'); ?>" />
      <?php endif; ?>
      <?php if (!empty($selectedTag) && $selectedTag !== 'Semua'): ?>
        <input type="hidden" name="tag" value="<?php echo htmlspecialchars($selectedTag, ENT_QUOTES, 'UTF-8'); ?>" />
      <?php endif; ?>
    </form>

    <div style="display:flex;align-items:center;gap:8px;">
      <span style="font-size:0.82rem;color:var(--text-muted);font-weight:700;">Urutan:</span>
      <form method="GET" action="katalog-jasa.php" style="margin:0;">
        <?php if (!empty($selectedCategory)): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8'); ?>" /><?php endif; ?>
        <?php if (!empty($selectedTag)): ?><input type="hidden" name="tag" value="<?php echo htmlspecialchars($selectedTag, ENT_QUOTES, 'UTF-8'); ?>" /><?php endif; ?>
        <?php if (!empty($searchQ)): ?><input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQ, ENT_QUOTES, 'UTF-8'); ?>" /><?php endif; ?>
        <select name="sort" class="form-control" style="padding:6px 12px;font-size:0.82rem;font-weight:700;width:auto;" onchange="this.form.submit()">
          <option value="recommended" <?php echo $sort === 'recommended' ? 'selected' : ''; ?>>Paling Sesuai</option>
          <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Terpopuler</option>
          <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating Tertinggi</option>
        </select>
      </form>
    </div>
  </div>

  <!-- Category Pills -->
  <div class="bursa-cat-pills">
    <a href="katalog-jasa.php" class="bursa-pill-btn <?php echo ($selectedCategory === 'Semua' || empty($selectedCategory)) ? 'active' : ''; ?>">Semua Kategori</a>
    <a href="katalog-jasa.php?category=Desain+Grafis" class="bursa-pill-btn <?php echo $selectedCategory === 'Desain Grafis' ? 'active' : ''; ?>">Desain & Grafis</a>
    <a href="katalog-jasa.php?category=Web+%26+Pemrograman" class="bursa-pill-btn <?php echo $selectedCategory === 'Web & Pemrograman' ? 'active' : ''; ?>">Web & Pemrograman</a>
    <a href="katalog-jasa.php?category=Video+%26+Animasi" class="bursa-pill-btn <?php echo $selectedCategory === 'Video & Animasi' ? 'active' : ''; ?>">Video & Animasi</a>
    <a href="katalog-jasa.php?category=Penulisan+%26+Terjemahan" class="bursa-pill-btn <?php echo $selectedCategory === 'Penulisan & Terjemahan' ? 'active' : ''; ?>">Penulisan & Terjemahan</a>
    <a href="katalog-jasa.php?category=Pemasaran+Digital" class="bursa-pill-btn <?php echo $selectedCategory === 'Pemasaran Digital' ? 'active' : ''; ?>">Pemasaran Digital</a>
  </div>

  <!-- Tag Pills -->
  <div class="bursa-tag-pills">
    <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);margin-right:4px;">Gaya:</span>
    <a href="katalog-jasa.php<?php echo !empty($selectedCategory) && $selectedCategory !== 'Semua' ? '?category=' . urlencode($selectedCategory) : ''; ?>" class="bursa-tag-btn <?php echo ($selectedTag === 'Semua' || empty($selectedTag)) ? 'active' : ''; ?>">Semua</a>
    <a href="katalog-jasa.php?category=Desain+Grafis&tag=Minimalis" class="bursa-tag-btn <?php echo $selectedTag === 'Minimalis' ? 'active' : ''; ?>">Minimalis</a>
    <a href="katalog-jasa.php?category=Desain+Grafis&tag=Modern" class="bursa-tag-btn <?php echo $selectedTag === 'Modern' ? 'active' : ''; ?>">Modern</a>
    <a href="katalog-jasa.php?category=Desain+Grafis&tag=Mascot" class="bursa-tag-btn <?php echo str_contains($selectedTag, 'Mascot') ? 'active' : ''; ?>">Mascot / Karakter</a>
    <a href="katalog-jasa.php?category=Desain+Grafis&tag=Vintage" class="bursa-tag-btn <?php echo str_contains($selectedTag, 'Vintage') ? 'active' : ''; ?>">Vintage / Retro</a>
    <a href="katalog-jasa.php?category=Desain+Grafis&tag=Kemasan" class="bursa-tag-btn <?php echo str_contains($selectedTag, 'Kemasan') ? 'active' : ''; ?>">Desain Kemasan</a>
    <a href="katalog-jasa.php?category=Web+%26+Pemrograman&tag=Landing+Page" class="bursa-tag-btn <?php echo str_contains($selectedTag, 'Landing') ? 'active' : ''; ?>">Landing Page</a>
    <a href="katalog-jasa.php?category=Video+%26+Animasi&tag=Shorts" class="bursa-tag-btn <?php echo str_contains($selectedTag, 'Shorts') ? 'active' : ''; ?>">Video TikTok / Reels</a>
  </div>
</div>

<!-- Services Grid -->
<?php if (empty($services)): ?>
  <div style="background:#ffffff;border:1px dashed var(--border-subtle);border-radius:var(--radius-lg);padding:60px 20px;text-align:center;">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:12px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <h3 style="margin:0 0 6px 0;font-size:1.15rem;color:var(--text-main);">Tidak ditemukan katalog jasa yang cocok</h3>
    <p style="margin:0 0 16px 0;color:var(--text-muted);font-size:0.88rem;">Coba gunakan kata kunci lain atau bersihkan filter pencarian.</p>
    <a href="katalog-jasa.php" class="btn-primary-add" style="display:inline-flex;text-decoration:none;">Tampilkan Semua Jasa</a>
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
            <div style="font-size:0.82rem;font-weight:700;color:var(--primary-blue);display:flex;align-items:center;gap:4px;">
              <span>Lihat Jasa &rarr;</span>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Worker Promo Bottom Banner -->
<?php if (!$isEmployer): ?>
  <div class="bursa-promo-banner">
    <div>
      <h2 style="margin:0 0 6px 0;font-size:1.35rem;font-weight:800;">Apakah Anda Talenta Gig Worker Berbakat?</h2>
      <p style="margin:0;font-size:0.88rem;color:#e0f2fe;max-width:620px;">Buka katalog jasa dan portofolio Anda di KarirHub Gig Workers secara gratis. Tampilkan sampel deliverables terbaik Anda agar perusahaan pemberi kerja dapat langsung menawarkan proyek aktif kepada Anda.</p>
    </div>
    <a href="worker-katalog-tambah.php" class="btn-primary-add" style="background:#ffffff;color:var(--primary-blue);font-weight:800;padding:12px 24px;font-size:0.9rem;text-decoration:none;border-radius:10px;">
      + Pasang Katalog Jasa Anda
    </a>
  </div>
<?php endif; ?>

<?php
if ($isEmployer) {
    require __DIR__ . '/includes/employer-layout-end.php';
} else {
    require __DIR__ . '/includes/worker-layout-end.php';
}
?>

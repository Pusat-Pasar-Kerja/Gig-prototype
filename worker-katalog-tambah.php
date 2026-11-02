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

$successMsg = '';
$errorMsg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_katalog') {
    $title = trim((string)($_POST['title'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'Desain Grafis'));
    $subcategory = trim((string)($_POST['subcategory'] ?? 'Desain Logo'));
    $tag = trim((string)($_POST['tag'] ?? 'Minimalis'));
    $thumbnail = trim((string)($_POST['thumbnail'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $clientRequirements = trim((string)($_POST['client_requirements'] ?? ''));
    $deliveryDays = max(1, (int)($_POST['delivery_days'] ?? 2));
    $revisions = trim((string)($_POST['revisions'] ?? 'Revisi fleksibel'));
    $scopeSummary = trim((string)($_POST['scope_summary'] ?? 'Layanan komprehensif dari konsep hingga file final'));
    $deliverablesRaw = trim((string)($_POST['deliverables'] ?? ''));
    $deliverablesList = array_values(array_filter(array_map('trim', explode("\n", $deliverablesRaw))));
    if (empty($deliverablesList)) {
        $deliverablesList = [
            'Konsep desain orisinal sesuai brief',
            'File resolusi tinggi (JPG & PNG HD 300 DPI)',
            'File master vektor (AI, EPS, SVG, PDF) siap cetak',
            'Mockup visual representatif',
            'Lisensi hak cipta komersial penuh'
        ];
    }

    // Gallery images
    $gallery = [];
    if (!empty($thumbnail)) {
        $gallery[] = $thumbnail;
    }
    if (!empty($_POST['gallery_extra']) && is_array($_POST['gallery_extra'])) {
        foreach ($_POST['gallery_extra'] as $imgUrl) {
            $imgUrl = trim((string)$imgUrl);
            if (!empty($imgUrl) && !in_array($imgUrl, $gallery, true)) {
                $gallery[] = $imgUrl;
            }
        }
    }
    if (empty($gallery)) {
        $gallery[] = 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80';
        $thumbnail = $gallery[0];
    }

    // FAQs construct
    $faqs = [];
    if (!empty($_POST['faq_q']) && is_array($_POST['faq_q'])) {
        foreach ($_POST['faq_q'] as $idx => $q) {
            $q = trim((string)$q);
            $a = trim((string)($_POST['faq_a'][$idx] ?? ''));
            if (!empty($q) && !empty($a)) {
                $faqs[] = ['q' => $q, 'a' => $a];
            }
        }
    }
    if (empty($faqs)) {
        $faqs = [
            ['q' => 'Apakah hasil karya dijamin original?', 'a' => 'Ya, kami menjamin 100% orisinalitas tanpa menggunakan template curian atau clipart gratisan.'],
            ['q' => 'Bagaimana jika perusahaan memerlukan revisi?', 'a' => 'Catatan revisi disampaikan melalui menu pelaporan dan serah terima proyek di dashboard KarirHub sesuai kesepakatan penugasan.']
        ];
    }

    if (empty($title)) {
        $errorMsg = 'Judul katalog jasa wajib diisi.';
    } else {
        $newService = [
            'id' => 'jasa-' . bin2hex(random_bytes(5)),
            'worker_id' => $username,
            'worker_name' => $displayName,
            'worker_avatar' => $workerAvatarUrl ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Super Seller',
            'worker_response_time' => '< 15 Menit',
            'category' => $category,
            'subcategory' => $subcategory,
            'tag' => $tag,
            'title' => $title,
            'thumbnail' => $thumbnail,
            'gallery' => $gallery,
            'rating' => 5.0,
            'reviews_count' => 0,
            'orders_count' => 0,
            'price_starting' => 0,
            'delivery_days' => $deliveryDays,
            'revisions' => $revisions,
            'scope_summary' => $scopeSummary,
            'deliverables' => $deliverablesList,
            'description' => $description,
            'packages' => [],
            'client_requirements' => $clientRequirements,
            'faqs' => $faqs,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        if (gig_katalog_save($newService)) {
            $_SESSION['flash_success'] = 'Selamat! Katalog Jasa berhasil diterbitkan dan langsung tampil di Bursa Katalog KarirHub!';
            header('Location: worker-katalog-saya.php?created=1');
            exit;
        } else {
            $errorMsg = 'Gagal menyimpan katalog jasa. Silakan coba kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Upload Katalog Jasa Baru | KarirHub Gig Workers</title>
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
    .top-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .btn-secondary-link {
      background: #f1f5f9;
      color: #334155;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      border: 1px solid #cbd5e1;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .btn-secondary-link:hover {
      background: #e2e8f0;
      color: #0f172a;
    }
    .main-container {
      max-width: 1280px;
      margin: 24px auto 60px;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 28px;
      align-items: start;
    }
    .page-intro-card {
      grid-column: 1 / -1;
      background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
      color: #ffffff;
      padding: 24px 28px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.1);
    }
    .page-intro-title {
      margin: 0 0 6px 0;
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: -0.3px;
    }
    .page-intro-desc {
      margin: 0;
      font-size: 0.9rem;
      color: #cbd5e1;
      max-width: 720px;
      line-height: 1.5;
    }
    .form-section-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 22px 24px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .section-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 18px;
      padding-bottom: 12px;
      border-bottom: 1px solid #f1f5f9;
    }
    .section-step-num {
      width: 28px;
      height: 28px;
      background: #2563eb;
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 0.85rem;
      flex-shrink: 0;
    }
    .section-title {
      margin: 0;
      font-size: 1.12rem;
      font-weight: 800;
      color: #0f172a;
    }
    .section-subtitle {
      margin: 2px 0 0 0;
      font-size: 0.82rem;
      color: #64748b;
    }
    .form-group {
      margin-bottom: 16px;
    }
    .form-label {
      display: block;
      font-size: 0.86rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 6px;
    }
    .form-hint {
      font-size: 0.76rem;
      color: #64748b;
      margin-top: 4px;
    }
    .form-control {
      width: 100%;
      padding: 10px 14px;
      font-size: 0.9rem;
      font-family: inherit;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      outline: none;
      background: #f8fafc;
      transition: all 0.2s ease;
      box-sizing: border-box;
    }
    .form-control:focus {
      background: #fff;
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    textarea.form-control {
      resize: vertical;
      min-height: 100px;
      line-height: 1.5;
    }
    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .grid-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 16px;
    }
    /* Preset Image Pickers */
    .preset-picker-wrap {
      margin-top: 10px;
      padding: 12px;
      background: #f8fafc;
      border: 1px dashed #cbd5e1;
      border-radius: 10px;
    }
    .preset-picker-label {
      font-size: 0.8rem;
      font-weight: 700;
      color: #475569;
      margin-bottom: 8px;
    }
    .preset-thumbs {
      display: flex;
      gap: 10px;
      overflow-x: auto;
      padding-bottom: 6px;
    }
    .preset-thumb-item {
      cursor: pointer;
      border: 2px solid transparent;
      border-radius: 8px;
      overflow: hidden;
      flex-shrink: 0;
      width: 76px;
      height: 54px;
      transition: all 0.2s;
    }
    .preset-thumb-item:hover, .preset-thumb-item.selected {
      border-color: #2563eb;
      transform: scale(1.05);
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    }
    .preset-thumb-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    /* Package Card Builder Tabs */
    .pkg-tabs {
      display: flex;
      gap: 8px;
      border-bottom: 1.5px solid #e2e8f0;
      margin-bottom: 16px;
    }
    .pkg-tab-btn {
      padding: 9px 16px;
      font-size: 0.85rem;
      font-weight: 700;
      color: #64748b;
      background: transparent;
      border: none;
      border-bottom: 2.5px solid transparent;
      cursor: pointer;
      transition: all 0.2s;
    }
    .pkg-tab-btn.active {
      color: #2563eb;
      border-bottom-color: #2563eb;
      background: #eff6ff;
      border-radius: 6px 6px 0 0;
    }
    .pkg-panel {
      display: none;
      animation: fadeIn 0.2s ease;
    }
    .pkg-panel.active {
      display: block;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(4px); }
      to { opacity: 1; transform: translateY(0); }
    }
    /* Live Preview Sticky Sidebar */
    .preview-sticky {
      position: sticky;
      top: 86px;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 18px;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
    }
    .preview-badge-top {
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #0284c7;
      background: #e0f2fe;
      padding: 4px 8px;
      border-radius: 6px;
      display: inline-block;
      margin-bottom: 10px;
    }
    .preview-card-wrap {
      border: 1px solid #cbd5e1;
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      transition: all 0.2s;
    }
    .preview-card-img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      background: #e2e8f0;
      display: block;
    }
    .preview-card-body {
      padding: 14px;
    }
    .preview-seller-info {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
    }
    .preview-seller-avatar {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      object-fit: cover;
    }
    .preview-seller-name {
      font-size: 0.8rem;
      font-weight: 700;
      color: #334155;
    }
    .preview-card-title {
      font-size: 0.92rem;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.35;
      height: 40px;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      margin-bottom: 10px;
    }
    .preview-card-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.78rem;
      color: #64748b;
      padding-top: 8px;
      border-top: 1px solid #f1f5f9;
    }
    .btn-submit-main {
      background: #2563eb;
      color: #ffffff;
      border: none;
      padding: 13px 24px;
      font-size: 0.95rem;
      font-weight: 800;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      margin-top: 18px;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
      transition: all 0.2s;
    }
    .btn-submit-main:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
    }
  </style>
</head>
<body>

  <!-- Top Navigation Header -->
  <header class="katalog-header-nav">
    <div class="brand-section">
      <a href="katalog-jasa.php" class="brand-badge" title="Bursa Katalog Jasa">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        KarirHub Jasa
      </a>
      <div class="nav-breadcrumbs">
        <a href="worker-bursa.php">Bursa Proyek</a>
        <span>&rsaquo;</span>
        <a href="worker-katalog-saya.php">Katalog Jasa Saya</a>
        <span>&rsaquo;</span>
        <span class="active">Upload Jasa Baru</span>
      </div>
    </div>
    <div class="top-actions">
      <a href="worker-bursa.php" class="btn-secondary-link">
        Bursa Proyek
      </a>
      <a href="worker-katalog-saya.php" class="btn-secondary-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Katalog Saya
      </a>
      <a href="katalog-jasa.php" class="btn-secondary-link" style="background:#0284c7;color:#fff;border-color:#0284c7;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Bursa Jasa
      </a>
    </div>
  </header>

  <div class="main-container">

    <!-- Hero Intro Card -->
    <div class="page-intro-card">
      <div>
        <h1 class="page-intro-title">Upload & Pasang Katalog Jasa Baru</h1>
        <p class="page-intro-desc">Tampilkan penawaran keahlian dan jasa terbaik Anda secara profesional (seperti Fastwork/Fiverr). Pajang portofolio karya, tentukan cakupan layanan, dan waktu pengerjaan untuk langsung menerima order dari pemberi kerja.</p>
      </div>
      <div style="text-align:right;">
        <span style="background:rgba(255,255,255,0.15);padding:6px 14px;border-radius:20px;font-size:0.8rem;font-weight:700;display:inline-block;">Status: Profil Worker Terverifikasi ✔</span>
      </div>
    </div>

    <!-- Left Form Section -->
    <form action="worker-katalog-tambah.php" method="POST" id="katalogForm">
      <input type="hidden" name="action" value="save_katalog" />

      <?php if (!empty($errorMsg)): ?>
        <div style="background:#fee2e2;border:1px solid #f87171;color:#b91c1c;padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:0.88rem;font-weight:600;">
          <?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <!-- STEP 1: Basic Info -->
      <div class="form-section-card">
        <div class="section-header">
          <div class="section-step-num">1</div>
          <div>
            <h2 class="section-title">Informasi Dasar Layanan Jasa</h2>
            <p class="section-subtitle">Buat judul menarik dan pilih kategori yang tepat agar mudah ditemukan pencari jasa.</p>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Judul Katalog Jasa <span style="color:#ef4444;">*</span></label>
          <input type="text" name="title" id="inputTitle" class="form-control" placeholder="Contoh: Jasa Desain Logo Bisnis & Brand Identity Eksklusif Bebas Revisi" value="<?php echo htmlspecialchars((string)($_POST['title'] ?? 'Jasa Desain Logo Minimalis & Modern untuk Bisnis UMKM'), ENT_QUOTES, 'UTF-8'); ?>" required />
          <div class="form-hint">Tip: Gunakan kalimat jelas dan sertakan keunggulan utama (contoh: Bebas Revisi, Cepat, Bergaransi).</div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Kategori Utama <span style="color:#ef4444;">*</span></label>
            <select name="category" id="inputCategory" class="form-control" required>
              <option value="Desain Grafis" selected>Desain Grafis & Kreatif</option>
              <option value="Web & Pemrograman">Web & Pemrograman</option>
              <option value="Video & Animasi">Video & Animasi</option>
              <option value="Penulisan & Terjemahan">Penulisan & Terjemahan</option>
              <option value="Pemasaran Digital">Pemasaran Digital & Media Sosial</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Subkategori Spesifik <span style="color:#ef4444;">*</span></label>
            <input type="text" name="subcategory" id="inputSubcategory" class="form-control" placeholder="Contoh: Desain Logo, UI/UX, Landing Page" value="Desain Logo" required />
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Tag Gaya / Kata Kunci Populer</label>
            <select name="tag" id="inputTag" class="form-control">
              <option value="Minimalis" selected>Minimalis & Elegan</option>
              <option value="Modern">Modern & Tech</option>
              <option value="Mascot / Karakter">Mascot / Karakter Ilustrasi</option>
              <option value="Vintage / Retro">Vintage / Retro Classic</option>
              <option value="Typography">Typography & Hand Lettering</option>
              <option value="Corporate Identity">Corporate & Brand Guidelines</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Estimasi Waktu Pengerjaan Minimum (Hari)</label>
            <input type="number" name="delivery_days_basic" id="inputDeliveryDays" class="form-control" value="2" min="1" max="30" />
          </div>
        </div>
      </div>

      <!-- STEP 2: Media & Gallery -->
      <div class="form-section-card">
        <div class="section-header">
          <div class="section-step-num">2</div>
          <div>
            <h2 class="section-title">Foto Sampul & Galeri Portofolio</h2>
            <p class="section-subtitle">Visual berkualitas tinggi meningkatkan minat pemesan hingga 3x lipat.</p>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">URL Foto Sampul Utama (Thumbnail)</label>
          <input type="url" name="thumbnail" id="inputThumbnail" class="form-control" placeholder="https://..." value="https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80" />
          
          <!-- Quick Presets -->
          <div class="preset-picker-wrap">
            <div class="preset-picker-label">🎨 Atau klik cepat foto sampel di bawah ini:</div>
            <div class="preset-thumbs">
              <div class="preset-thumb-item selected" onclick="selectPreset('https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1626785774573-4b799315345d?w=150&auto=format&fit=crop&q=80" alt="Logo Minimalis" title="Logo Minimalis" />
              </div>
              <div class="preset-thumb-item" onclick="selectPreset('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=150&auto=format&fit=crop&q=80" alt="Tech Corporate" title="Corporate Tech" />
              </div>
              <div class="preset-thumb-item" onclick="selectPreset('https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1563089145-599997674d42?w=150&auto=format&fit=crop&q=80" alt="Mascot Art" title="Mascot Art" />
              </div>
              <div class="preset-thumb-item" onclick="selectPreset('https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=150&auto=format&fit=crop&q=80" alt="Landing Page" title="Web & Landing Page" />
              </div>
              <div class="preset-thumb-item" onclick="selectPreset('https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?w=150&auto=format&fit=crop&q=80" alt="Video Editing" title="Video Editing" />
              </div>
              <div class="preset-thumb-item" onclick="selectPreset('https://images.unsplash.com/photo-1544816155-12df9643f363?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1544816155-12df9643f363?w=150&auto=format&fit=crop&q=80" alt="Packaging Box" title="Packaging Box" />
              </div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Galeri Foto Portofolio Tambahan (Opsional, hingga 3 foto)</label>
          <div style="display:grid;gap:8px;">
            <input type="url" name="gallery_extra[]" class="form-control" placeholder="URL Foto Galeri 2: https://..." value="https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=800&auto=format&fit=crop&q=80" />
            <input type="url" name="gallery_extra[]" class="form-control" placeholder="URL Foto Galeri 3: https://..." value="https://images.unsplash.com/photo-1572044162444-ad60f128bdea?w=800&auto=format&fit=crop&q=80" />
          </div>
        </div>
      </div>

      <!-- STEP 3: Cakupan & Output Layanan -->
      <div class="form-section-card">
        <div class="section-header">
          <div class="section-step-num">3</div>
          <div>
            <h2 class="section-title">Cakupan Layanan & Output Hasil Kerja</h2>
            <p class="section-subtitle">Tentukan estimasi waktu pengerjaan, ketentuan revisi, dan berkas output deliverables yang diterima perusahaan pemberi kerja.</p>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Estimasi Waktu Pengerjaan (Hari) <span style="color:#ef4444;">*</span></label>
            <input type="number" name="delivery_days" id="inputDeliveryDays" class="form-control" value="2" min="1" required />
            <div style="font-size:0.75rem;color:#64748b;margin-top:4px;">Rata-rata waktu penyelesaian draft atau hasil pertama.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Ketentuan Revisi</label>
            <input type="text" name="revisions" class="form-control" value="Revisi fleksibel hingga hasil sesuai brief proyek" />
            <div style="font-size:0.75rem;color:#64748b;margin-top:4px;">Contoh: 3x Revisi, atau Sesuai kesepakatan timeline proyek.</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Ringkasan Singkat Cakupan Layanan</label>
          <input type="text" name="scope_summary" class="form-control" value="Layanan terpadu mulai dari eksplorasi konsep hingga penyerahan file master final." />
        </div>

        <div class="form-group">
          <label class="form-label">Daftar Output Deliverables yang Diterima Perusahaan (Satu per baris) <span style="color:#ef4444;">*</span></label>
          <textarea name="deliverables" class="form-control" rows="5" placeholder="Contoh:&#10;Konsep desain orisinal sesuai brief&#10;File resolusi tinggi JPG & PNG HD&#10;File master vektor AI/EPS/SVG&#10;Lisensi hak cipta komersial penuh">Konsep desain orisinal sesuai brief proyek
File resolusi tinggi (JPG & PNG HD 300 DPI)
File master vektor (AI, EPS, SVG, PDF) siap cetak
Mockup visual representatif
Lisensi hak cipta komersial penuh untuk perusahaan
Dukungan asistensi teknis pengerjaan deliverables</textarea>
          <div style="font-size:0.75rem;color:#64748b;margin-top:4px;">Tuliskan setiap deliverable atau berkas output pada baris baru.</div>
        </div>
      </div>

      <!-- STEP 4: Description & Requirements -->
      <div class="form-section-card">
        <div class="section-header">
          <div class="section-step-num">4</div>
          <div>
            <h2 class="section-title">Deskripsi Lengkap & Kebutuhan Brief Proyek</h2>
            <p class="section-subtitle">Jelaskan keahlian Anda, alur pengerjaan deliverables, dan apa yang harus disiapkan perusahaan pemberi kerja.</p>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Deskripsi Layanan & Alasan Memilih Anda</label>
          <textarea name="description" class="form-control" rows="6" placeholder="Jelaskan secara rinci tentang keahlian Anda, alur pengerjaan, dan jaminan kepuasan...">Halo! Saya Gig Worker profesional bidang desain siap membantu perusahaan atau brand Anda memiliki identitas visual yang profesional dan kredibel.

Mengapa Memilih Kami?
• Desain 100% orisinal dan dikerjakan terstruktur dari sketsa konsep
• Pengerjaan tepat waktu sesuai timeline proyek perusahaan
• File siap cetak (300 DPI) dan siap aplikasi digital korporat
• Hak cipta komersial penuh langsung menjadi aset perusahaan</textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Kebutuhan / Brief Proyek dari Perusahaan (Apa yang harus disiapkan pemberi kerja?)</label>
          <textarea name="client_requirements" class="form-control" rows="4" placeholder="Contoh: Profil perusahaan, bidang industri, panduan merek/warna...">1. Profil perusahaan/bisnis dan nama brand/tagline
2. Bidang industri dan target audiens utama
3. Arahan warna korporat atau panduan merek (jika ada)
4. Dokumen brief proyek dan referensi visual yang diharapkan</textarea>
        </div>
      </div>

      <!-- Submit Actions -->
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:40px;">
        <button type="submit" class="btn-submit-main" style="flex:1;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Terbitkan Katalog Jasa Sekarang
        </button>
        <a href="worker-katalog-saya.php" style="padding:13px 20px;border-radius:10px;background:#f1f5f9;color:#475569;text-decoration:none;font-weight:700;font-size:0.92rem;border:1px solid #cbd5e1;">Batal</a>
      </div>

    </form>

    <!-- Right Live Preview Sticky Column -->
    <aside class="preview-sticky">
      <div class="preview-badge-top">Pratinjau Tampilan Marketplace</div>
      <div style="font-size:0.8rem;color:#64748b;margin-bottom:12px;">Beginilah kartu jasa Anda akan tampil di halaman bursa pencarian Fastwork-style:</div>

      <div class="preview-card-wrap">
        <img src="https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80" id="previewImg" class="preview-card-img" alt="Pratinjau Jasa" />
        <div class="preview-card-body">
          <div class="preview-seller-info">
            <?php echo gig_katalog_render_avatar($displayName, 26, '0.72rem'); ?>
            <span class="preview-seller-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="preview-card-title" id="previewTitle">Jasa Desain Logo Minimalis & Modern untuk Bisnis UMKM</div>
          <div style="display:flex;align-items:center;gap:4px;margin-bottom:8px;font-size:0.78rem;">
            <span style="color:#f59e0b;font-weight:800;">★ 5.0</span>
            <span style="color:#94a3b8;">(Baru)</span>
            <span style="color:#cbd5e1;">•</span>
            <span style="color:#64748b;font-weight:600;" id="previewDays">2 Hari Pengerjaan</span>
          </div>
          <div class="preview-card-meta">
            <span style="font-size:0.75rem;color:#0369a1;font-weight:700;background:#e0f2fe;padding:3px 8px;border-radius:6px;">Siap Terima Proyek</span>
            <span style="font-size:0.82rem;font-weight:800;color:#0284c7;">Lihat Jasa &rarr;</span>
          </div>
        </div>
      </div>

      <div style="margin-top:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px;font-size:0.78rem;color:#166534;line-height:1.4;">
        <strong>🛡️ Penugasan Resmi Perusahaan & KarirHub</strong>
        <p style="margin:4px 0 0 0;">Perusahaan pemberi kerja dapat langsung menawarkan proyek aktif berdasarkan deliverables katalog Anda. Semua kontrak dan penyerahan karya tercatat resmi di dashboard KarirHub.</p>
      </div>
    </aside>

  </div>

  <script>
    function selectPreset(url, element) {
      document.getElementById('inputThumbnail').value = url;
      document.getElementById('previewImg').src = url;
      document.querySelectorAll('.preset-thumb-item').forEach(el => el.classList.remove('selected'));
      element.classList.add('selected');
    }

    function updateLivePreview() {
      const title = document.getElementById('inputTitle').value.trim();
      const days = document.getElementById('inputDeliveryDays').value.trim();
      const thumb = document.getElementById('inputThumbnail').value.trim();

      if (title) document.getElementById('previewTitle').innerText = title;
      if (days) document.getElementById('previewDays').innerText = days + ' Hari Pengerjaan';
      if (thumb) document.getElementById('previewImg').src = thumb;
    }

    document.getElementById('inputTitle').addEventListener('input', updateLivePreview);
    document.getElementById('inputDeliveryDays').addEventListener('input', updateLivePreview);
    document.getElementById('inputThumbnail').addEventListener('input', updateLivePreview);
  </script>
</body>
</html>


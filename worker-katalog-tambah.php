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
            'Lisensi hak cipta komersial penuh untuk perusahaan'
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
            ['q' => 'Apakah hasil karya dijamin original?', 'a' => 'Ya, seluruh konsep dikerjakan original tanpa menggunakan template curian.'],
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
            'worker_badge' => 'Mitra Terverifikasi',
            'worker_response_time' => 'Siap Proyek Baru',
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

$pageTitle = 'Upload Katalog Jasa Baru';
$pageKey = 'katalog';
$breadcrumbCurrent = 'Upload Katalog Jasa';
require __DIR__ . '/includes/worker-layout-start.php';
?>

<style>
  .katalog-editor-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: flex-start;
  }
  @media (max-width: 992px) {
    .katalog-editor-layout {
      grid-template-columns: 1fr;
    }
  }
  .form-section-card {
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
  }
  .section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--border-subtle);
  }
  .section-step-num {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary-blue);
    color: #ffffff;
    font-weight: 800;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .section-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
  }
  .section-subtitle {
    font-size: 0.82rem;
    color: var(--text-muted);
    margin-top: 2px;
  }
  .form-group {
    margin-bottom: 18px;
  }
  .form-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 6px;
  }
  .form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid var(--border-subtle);
    border-radius: 10px;
    font-size: 0.88rem;
    color: var(--text-main);
    background: #ffffff;
    outline: none;
    transition: border-color 0.2s;
    font-family: inherit;
    box-sizing: border-box;
  }
  .form-control:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(22, 87, 193, 0.1);
  }
  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  @media (max-width: 600px) {
    .grid-2 { grid-template-columns: 1fr; }
  }
  .preset-thumbs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-top: 8px;
  }
  .preset-thumb-item {
    height: 68px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    background: #f1f5f9;
  }
  .preset-thumb-item:hover {
    transform: translateY(-2px);
    border-color: #93c5fd;
  }
  .preset-thumb-item.selected {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(22, 87, 193, 0.2);
  }
  .preset-thumb-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  /* Right Live Preview Sticky */
  .preview-sticky {
    position: sticky;
    top: 20px;
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 20px;
    box-shadow: var(--shadow-sm);
  }
  .preview-badge-top {
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--primary-blue);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
  }
  .preview-card-wrap {
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    overflow: hidden;
    background: #ffffff;
    box-shadow: var(--shadow-xs);
  }
  .preview-card-img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    background: #0f172a;
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
  .preview-seller-name {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-main);
  }
  .preview-card-title {
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.35;
    margin-bottom: 8px;
    height: 40px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
</style>

<div class="page-toolbar">
  <div>
    <h1>Upload & Pasang Katalog Jasa Baru</h1>
    <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">Tampilkan sampel hasil kerja dan cakupan deliverables Anda agar perusahaan dapat langsung menugaskan proyek aktif.</p>
  </div>
  <div>
    <a href="worker-katalog-saya.php" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:10px;font-size:0.85rem;font-weight:700;">
      &larr; Kembali ke Katalog Saya
    </a>
  </div>
</div>

<?php if (!empty($errorMsg)): ?>
  <div class="notice-bar" style="margin-bottom:20px;background:#fef2f2;border-color:#fca5a5;color:#b91c1c;">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></span>
  </div>
<?php endif; ?>

<form action="worker-katalog-tambah.php" method="POST" id="katalogForm">
  <input type="hidden" name="action" value="save_katalog" />

  <div class="katalog-editor-layout">

    <!-- Left Column: Step-by-Step Form -->
    <div>

      <!-- STEP 1: Basic Info -->
      <div class="form-section-card">
        <div class="section-header">
          <div class="section-step-num">1</div>
          <div>
            <h2 class="section-title">Informasi Dasar Layanan</h2>
            <p class="section-subtitle">Tentukan judul katalog yang menarik dan kategori yang relevan.</p>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Judul Katalog Jasa <span style="color:#ef4444;">*</span></label>
          <input type="text" name="title" id="inputTitle" class="form-control" placeholder="Contoh: Jasa Desain Logo Bisnis & Identitas Visual Lengkap" value="Jasa Desain Logo Bisnis & Identitas Visual Lengkap" required />
          <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Gunakan judul yang jelas, padat, dan mencerminkan hasil deliverables Anda.</div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Kategori Utama <span style="color:#ef4444;">*</span></label>
            <select name="category" class="form-control" required>
              <option value="Desain Grafis" selected>Desain & Grafis</option>
              <option value="Web & Pemrograman">Web & Pemrograman</option>
              <option value="Video & Animasi">Video & Animasi</option>
              <option value="Penulisan & Terjemahan">Penulisan & Terjemahan</option>
              <option value="Pemasaran Digital">Pemasaran Digital</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Sub-Kategori / Spesialisasi</label>
            <input type="text" name="subcategory" class="form-control" value="Desain Logo & Branding" />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Tag Gaya / Kata Kunci</label>
          <input type="text" name="tag" class="form-control" value="Minimalis & Korporat" placeholder="Contoh: Minimalis, Vektor, Modern, Responsive" />
        </div>
      </div>

      <!-- STEP 2: Portfolio & Visuals -->
      <div class="form-section-card">
        <div class="section-header">
          <div class="section-step-num">2</div>
          <div>
            <h2 class="section-title">Foto Sampel Portofolio & Thumbnail</h2>
            <p class="section-subtitle">Pilih atau unggah URL foto representatif karya Anda.</p>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">URL Foto Utama / Sampel Karya (Wajib) <span style="color:#ef4444;">*</span></label>
          <input type="url" name="thumbnail" id="inputThumbnail" class="form-control" value="https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80" required />
          
          <div style="margin-top:10px;">
            <span style="font-size:0.75rem;color:var(--text-muted);font-weight:700;">Atau pilih sampel cepat portofolio desain:</span>
            <div class="preset-thumbs">
              <div class="preset-thumb-item selected" onclick="selectPreset('https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1626785774573-4b799315345d?w=150&auto=format&fit=crop&q=80" alt="Preset 1" />
              </div>
              <div class="preset-thumb-item" onclick="selectPreset('https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=150&auto=format&fit=crop&q=80" alt="Preset 2" />
              </div>
              <div class="preset-thumb-item" onclick="selectPreset('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=150&auto=format&fit=crop&q=80" alt="Preset 3" />
              </div>
              <div class="preset-thumb-item" onclick="selectPreset('https://images.unsplash.com/photo-1544816155-12df9643f363?w=150&auto=format&fit=crop&q=80', this)">
                <img src="https://images.unsplash.com/photo-1544816155-12df9643f363?w=150&auto=format&fit=crop&q=80" alt="Preset 4" />
              </div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Galeri Foto Portofolio Tambahan (Opsional)</label>
          <div style="display:grid;gap:8px;">
            <input type="url" name="gallery_extra[]" class="form-control" placeholder="URL Foto Galeri 2: https://..." value="https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=800&auto=format&fit=crop&q=80" />
            <input type="url" name="gallery_extra[]" class="form-control" placeholder="URL Foto Galeri 3: https://..." value="https://images.unsplash.com/photo-1572044162444-ad60f128bdea?w=800&auto=format&fit=crop&q=80" />
          </div>
        </div>
      </div>

      <!-- STEP 3: Scope & Deliverables -->
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
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Rata-rata waktu penyelesaian draft atau hasil pertama.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Ketentuan Revisi</label>
            <input type="text" name="revisions" class="form-control" value="Revisi fleksibel hingga hasil sesuai brief proyek" />
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Contoh: 3x Revisi, atau Sesuai kesepakatan timeline proyek.</div>
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
          <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Tuliskan setiap deliverable atau berkas output pada baris baru.</div>
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
          <textarea name="description" class="form-control" rows="6" placeholder="Jelaskan secara rinci tentang keahlian Anda, alur pengerjaan, dan standar kualitas...">Halo! Saya Gig Worker profesional bidang desain siap membantu perusahaan atau brand Anda memiliki identitas visual yang profesional dan kredibel.

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
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:30px;">
        <button type="submit" class="btn-primary-add" style="border:none;cursor:pointer;padding:12px 28px;font-size:0.92rem;font-weight:800;border-radius:10px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Terbitkan Katalog Jasa Sekarang
        </button>
        <a href="worker-katalog-saya.php" class="btn-secondary" style="text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:700;font-size:0.9rem;">
          Batal
        </a>
      </div>

    </div>

    <!-- Right Column: Live Marketplace Preview Card -->
    <aside class="preview-sticky">
      <div class="preview-badge-top">Pratinjau Kartu di Marketplace</div>
      <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:14px;">Beginilah kartu jasa Anda akan tampil di halaman bursa pencarian KarirHub:</div>

      <div class="preview-card-wrap">
        <img src="https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80" id="previewImg" class="preview-card-img" alt="Pratinjau Jasa" />
        <div class="preview-card-body">
          <div class="preview-seller-info">
            <?php echo gig_katalog_render_avatar($displayName, 26, '0.72rem'); ?>
            <span class="preview-seller-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="preview-card-title" id="previewTitle">Jasa Desain Logo Bisnis & Identitas Visual Lengkap</div>
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;font-size:0.78rem;">
            <span style="color:#f59e0b;font-weight:800;">★ 5.0</span>
            <span style="color:#94a3b8;">(Baru)</span>
            <span style="color:#cbd5e1;">•</span>
            <span style="color:var(--text-muted);font-weight:600;" id="previewDays">2 Hari Pengerjaan</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid var(--border-subtle);">
            <span style="font-size:0.75rem;color:#0369a1;font-weight:700;background:#e0f2fe;padding:3px 8px;border-radius:6px;">Siap Terima Proyek</span>
            <span style="font-size:0.82rem;font-weight:800;color:var(--primary-blue);">Lihat Jasa &rarr;</span>
          </div>
        </div>
      </div>

      <div style="margin-top:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px;font-size:0.78rem;color:#166534;line-height:1.45;">
        <strong>🛡️ Penugasan Resmi Perusahaan & KarirHub</strong>
        <p style="margin:4px 0 0 0;">Perusahaan pemberi kerja dapat langsung menawarkan proyek aktif berdasarkan deliverables katalog Anda. Semua kontrak dan penyerahan karya tercatat resmi di dashboard KarirHub.</p>
      </div>
    </aside>

  </div>
</form>

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

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>

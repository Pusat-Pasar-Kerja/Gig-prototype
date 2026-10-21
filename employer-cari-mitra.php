<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-offers.php';

$workerProfiles = gig_worker_profiles();
$vacancies = gig_project_vacancies();
$searchQuery = trim((string)($_GET['q'] ?? ''));

// Only posted/active vacancies can be offered
$activeVacancies = array_values(array_filter($vacancies, fn($v) => $v['status'] === 'active'));
$vacancyOfferCounts = [];
foreach ($activeVacancies as $v) {
    $vacancyOfferCounts[$v['id']] = gig_count_vacancy_offers($v['id']);
}
$alreadyOfferedMap = gig_employer_offered_map($username);

$pageTitle = 'Cari Mitra Gig Workers';
$pageKey = 'cari-mitra';
$breadcrumbCurrent = 'Cari Mitra Gig Workers';
require __DIR__ . '/includes/employer-layout-start.php';
?>

<style>
  /* Sticky Marketplace Search Toolbar */
  .marketplace-toolbar-container {
    position: sticky;
    top: 0;
    z-index: 35;
    background: rgba(244, 246, 250, 0.94);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    padding: 8px 0 16px 0;
    margin-bottom: 8px;
  }

  .marketplace-toolbar-card {
    background: #ffffff;
    padding: 16px 20px;
    border-radius: 16px;
    border: 1px solid var(--border-subtle);
    box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  /* Big Prominent Search Bar */
  .mitra-search-bar-wrap {
    width: 100%;
    position: relative;
  }

  .mitra-search-box-large {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    padding: 10px 16px;
    transition: all 0.2s ease;
  }

  .mitra-search-box-large:focus-within {
    border-color: var(--primary-blue);
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(22, 87, 193, 0.12), 0 2px 8px rgba(22, 87, 193, 0.08);
  }

  .mitra-search-box-large .search-icon {
    color: #64748b;
    flex-shrink: 0;
    transition: color 0.2s ease;
  }

  .mitra-search-box-large:focus-within .search-icon {
    color: var(--primary-blue);
  }

  .mitra-search-box-large input {
    border: none;
    outline: none;
    background: transparent;
    font-size: 0.92rem;
    color: var(--text-main);
    width: 100%;
    font-weight: 500;
  }

  .mitra-search-box-large input::placeholder {
    color: #94a3b8;
    font-size: 0.86rem;
  }

  .search-clear-btn {
    background: #e2e8f0;
    border: none;
    color: #475569;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.15s ease;
    padding: 0;
    line-height: 1;
  }

  .search-clear-btn:hover {
    background: #cbd5e1;
    color: #0f172a;
    transform: scale(1.08);
  }

  .search-kbd-hint {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 2px 7px;
    font-size: 0.72rem;
    color: #64748b;
    font-weight: 700;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    user-select: none;
  }

  .search-kbd-hint kbd {
    font-family: inherit;
  }

  /* Second row: Categories & Scope */
  .marketplace-toolbar-sub {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }

  .marketplace-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .search-scope-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.74rem;
    font-weight: 700;
    color: #0369a1;
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    border-radius: 9999px;
    padding: 4px 10px;
  }

  /* Third row: Quick suggestion keywords */
  .quick-keywords-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    padding-top: 4px;
    border-top: 1px dashed #f1f5f9;
  }

  .quick-keywords-label {
    font-size: 0.74rem;
    font-weight: 700;
    color: #64748b;
    margin-right: 4px;
  }

  .quick-kw-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    padding: 3px 10px;
    font-size: 0.73rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .quick-kw-pill:hover {
    background: var(--primary-blue-light);
    border-color: #93c5fd;
    color: var(--primary-blue);
    transform: translateY(-1px);
  }

  /* Match Snippet on Cards */
  .mitra-match-pill {
    margin-bottom: 10px;
    background: #ecfeff;
    border: 1px solid #a5f3fc;
    border-radius: 8px;
    padding: 6px 9px;
    font-size: 0.73rem;
    color: #0e7490;
    display: flex;
    align-items: flex-start;
    gap: 5px;
    line-height: 1.35;
    animation: fadeInMatch 0.2s ease;
  }
  .mitra-match-pill .pill-spark {
    flex-shrink: 0;
    font-size: 0.8rem;
    line-height: 1.2;
  }
  .mitra-match-pill .pill-type {
    font-weight: 800;
    color: #0891b2;
    white-space: nowrap;
  }
  .mitra-match-pill .pill-text {
    color: #0f172a;
    font-weight: 500;
    word-break: break-word;
  }
  .mitra-match-pill mark.kw-hl {
    background: #fef08a;
    color: #854d0e;
    font-weight: 800;
    padding: 0 3px;
    border-radius: 3px;
  }

  @keyframes fadeInMatch {
    from { opacity: 0; transform: translateY(-3px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .search-results-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    padding: 0 2px;
    flex-wrap: wrap;
    gap: 10px;
  }

  .mitra-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 22px;
    margin-bottom: 36px;
  }

  .mitra-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s ease, border-color 0.22s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    position: relative;
  }

  .mitra-box:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 28px -6px rgba(15, 23, 42, 0.12);
    border-color: #cbd5e1;
  }

  .mitra-box-cover {
    height: 90px;
    position: relative;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: flex-end;
    padding: 0 16px;
  }

  .mitra-category-pill {
    position: absolute;
    top: 10px;
    right: 12px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(4px);
    font-size: 0.7rem;
    font-weight: 700;
    color: #1e293b;
    padding: 3px 9px;
    border-radius: 999px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  }

  .mitra-avatar-wrap {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    position: relative;
    margin-bottom: -32px;
    flex-shrink: 0;
    background: #ffffff;
  }

  .mitra-avatar-wrap img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    display: block;
  }

  .mitra-verified-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #0284c7;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
  }

  .mitra-box-body {
    padding: 40px 18px 16px 18px;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .mitra-name-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 3px;
  }

  .mitra-name {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
  }

  .mitra-role {
    font-size: 0.82rem;
    color: #475569;
    font-weight: 600;
    margin-bottom: 8px;
  }

  .mitra-location {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 12px;
  }

  .mitra-rating-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
  }

  .mitra-stars {
    color: #f59e0b;
    font-size: 0.88rem;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
  }

  .mitra-rating-num {
    font-weight: 800;
    font-size: 0.84rem;
    color: #0f172a;
  }

  .mitra-reviews-num {
    font-size: 0.75rem;
    color: #64748b;
  }

  .mitra-skills-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 14px;
  }

  .mitra-skill-badge {
    background: #f1f5f9;
    color: #334155;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
  }

  .mitra-stats-badge {
    margin-top: auto;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
    font-size: 0.74rem;
    font-weight: 700;
    padding: 5px 10px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .mitra-box-footer {
    padding: 12px 18px 16px 18px;
    border-top: 1px solid #f1f5f9;
    background: #ffffff;
  }

  .btn-tawarkan-card {
    width: 100%;
    padding: 9px 14px;
    background: var(--primary-blue);
    color: #ffffff;
    font-size: 0.85rem;
    font-weight: 700;
    border: none;
    border-radius: 9px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: background 0.18s ease, transform 0.12s ease;
  }

  .btn-tawarkan-card:hover {
    background: var(--primary-blue-hover);
    transform: translateY(-1px);
  }

  .btn-tawarkan-card:active {
    transform: translateY(0);
  }

  .btn-tawarkan-card svg {
    flex-shrink: 0;
  }

  /* Empty state */
  .mitra-empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 48px 20px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
  }
</style>

    <div class="page-toolbar">
      <div>
        <h1>Cari Mitra Gig Workers</h1>
        <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">Jelajahi profil keahlian Gig Workers dan tawarkan lowongan proyek aktif Anda langsung melalui profil detail. Klik kartu untuk melihat profil portofolio lengkap.</p>
      </div>
    </div>

    <!-- Sticky Toolbar Filters & Search Bar -->
    <div class="marketplace-toolbar-container">
      <div class="marketplace-toolbar-card">
        <!-- Top Row: Big Prominent Search Bar -->
        <div class="mitra-search-bar-wrap">
          <div class="mitra-search-box-large">
            <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input 
              type="text" 
              placeholder="Cari kata kunci apapun (nama, keahlian lengkap, proyek, portofolio, klien, file deliverable, ulasan, domisili)..." 
              value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
              oninput="searchWorkers(this.value)"
              id="workerSearchInput" 
              autocomplete="off"
              aria-label="Cari Gig Workers"
            />
            <button 
              type="button" 
              class="search-clear-btn" 
              id="workerSearchClearBtn" 
              onclick="clearWorkerSearch()" 
              title="Hapus pencarian"
              style="<?php echo $searchQuery !== '' ? 'display:inline-flex;' : 'display:none;'; ?>"
            >
              &times;
            </button>
            <span class="search-kbd-hint" title="Tekan '/' untuk fokus pencarian">
              <kbd>/</kbd>
            </span>
          </div>
        </div>

        <!-- Second Row: Bidang Category Filters & Scope Badge -->
        <div class="marketplace-toolbar-sub">
          <div class="marketplace-filter-group">
            <button class="filter-btn-pill active" type="button" onclick="filterWorkers('all', this)">Semua Bidang (<?php echo count($workerProfiles); ?>)</button>
            <button class="filter-btn-pill" type="button" onclick="filterWorkers('ui-ux', this)">UI/UX &amp; Desain</button>
            <button class="filter-btn-pill" type="button" onclick="filterWorkers('backend', this)">Backend &amp; API</button>
            <button class="filter-btn-pill" type="button" onclick="filterWorkers('marketing', this)">Pemasaran</button>
          </div>
          <div class="search-scope-badge" title="Pencarian memeriksa seluruh data profil Gig Worker tanpa kecuali">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Mencari di seluruh isi profil</span>
          </div>
        </div>

        <!-- Third Row: Quick Suggestion Tags (Popular Searches) -->
        <div class="quick-keywords-row">
          <span class="quick-keywords-label">Saran kata kunci:</span>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('Figma')">Figma</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('REST API')">REST API</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('Laravel')">Laravel</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('Kemnaker')">Kemnaker</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('Redis')">Redis</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('BUMN')">BUMN</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('Copywriting')">Copywriting</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('Node.js')">Node.js</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('Microservices')">Microservices</button>
          <button type="button" class="quick-kw-pill" onclick="setQuickSearch('komunikatif')">Komunikatif</button>
        </div>
      </div>
    </div>

    <!-- Live Results Bar -->
    <div class="search-results-bar">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <p style="font-size:0.84rem;color:var(--text-muted);margin:0;">
          Menampilkan <strong id="worker-count" style="color:var(--text-main);font-size:0.95rem;"><?php echo count($workerProfiles); ?></strong> Gig Workers siap kerja
        </p>
        <div id="active-query-indicator" style="display:none;align-items:center;gap:6px;background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;padding:3px 10px;border-radius:9999px;font-size:0.76rem;font-weight:700;">
          <span>Kata kunci: &ldquo;<strong id="active-query-text"></strong>&rdquo;</span>
          <button type="button" onclick="clearWorkerSearch()" style="background:none;border:none;color:#1d4ed8;cursor:pointer;font-weight:800;font-size:0.85rem;line-height:1;padding:0 2px;" title="Hapus kata kunci">&times;</button>
        </div>
      </div>
      <span style="font-size:0.75rem;color:#64748b;background:#f1f5f9;padding:4px 10px;border-radius:6px;">
        💡 Maks. 3 penawaran per lowongan aktif
      </span>
    </div>

    <!-- Marketplace Grid of Gig Workers Boxes -->
    <div class="mitra-grid" id="workers-grid">
      <?php foreach ($workerProfiles as $w): 
        $searchMeta = gig_worker_search_metadata($w);
        // Compute cover banner gradient based on profile color
        $cardBgColor = !empty($w['color']) ? $w['color'] : '#2563eb';
        $categoryLabel = match($w['category'] ?? '') {
          'ui-ux' => 'UI/UX & Desain',
          'backend' => 'Backend & API',
          'marketing' => 'Digital Marketing',
          default => 'Freelancer'
        };
      ?>
      <div class="mitra-box" 
           data-category="<?php echo htmlspecialchars($w['category'], ENT_QUOTES, 'UTF-8'); ?>"
           data-search-corpus="<?php echo htmlspecialchars($searchMeta['full_text'], ENT_QUOTES, 'UTF-8'); ?>"
           data-search-facets="<?php echo htmlspecialchars(json_encode($searchMeta['facets'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
           onclick="window.location.href='worker-profile.php?id=<?php echo urlencode($w['id']); ?>&from=cari-mitra'">
        
        <!-- Top Cover Banner -->
        <div class="mitra-box-cover" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($cardBgColor, ENT_QUOTES, 'UTF-8'); ?>cc 0%, <?php echo htmlspecialchars($cardBgColor, ENT_QUOTES, 'UTF-8'); ?> 100%);">
          <span class="mitra-category-pill"><?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?></span>
          <div class="mitra-avatar-wrap">
            <div style="width:56px;height:56px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.4rem;border:3px solid #ffffff;box-shadow:0 4px 10px rgba(0,0,0,0.12);">
              <?php echo htmlspecialchars(strtoupper(substr((string)$w['name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>
        </div>

        <!-- Box Body Content -->
        <div class="mitra-box-body">
          <div class="mitra-name-row">
            <div class="mitra-name"><?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <div class="mitra-role"><?php echo htmlspecialchars($w['title'], ENT_QUOTES, 'UTF-8'); ?></div>
          
          <!-- Dynamic Match Badge Snippet (shows where keyword matched) -->
          <div class="mitra-match-pill" style="display:none;"></div>

          <div class="mitra-location">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?php echo htmlspecialchars($w['location'], ENT_QUOTES, 'UTF-8'); ?>
          </div>

          <!-- Rating Stars -->
          <div class="mitra-rating-row">
            <div class="mitra-stars">
              <?php
                $ratingVal = (float)$w['rating'];
                $fullStars = (int)floor($ratingVal);
                for ($i = 0; $i < 5; $i++) {
                  echo $i < $fullStars ? '★' : '☆';
                }
              ?>
            </div>
            <span class="mitra-rating-num"><?php echo number_format((float)$w['rating'], 1); ?></span>
            <span class="mitra-reviews-num">(<?php echo (int)$w['reviews_count']; ?> ulasan)</span>
          </div>

          <!-- Skills / Expertise -->
          <div class="mitra-skills-list">
            <?php 
              $wSkills = gig_normalize_skills($w['skills'] ?? []);
              $skillsSlice = array_slice($wSkills, 0, 3);
              $extraCount = count($wSkills) - count($skillsSlice);
            ?>
            <?php foreach ($skillsSlice as $skillTag): ?>
              <?php
                $sTagNm = gig_skill_name($skillTag);
                $sTagLvl = gig_skill_level($skillTag);
                $sTagSt = gig_skill_level_badge_style($sTagLvl);
              ?>
              <span class="mitra-skill-badge" style="display:inline-flex;align-items:center;gap:4px;">
                <span><?php echo htmlspecialchars($sTagNm, ENT_QUOTES, 'UTF-8'); ?></span>
                <span style="font-size:0.65rem;font-weight:700;color:<?php echo $sTagSt['color']; ?>;background:<?php echo $sTagSt['bg']; ?>;padding:1px 4px;border-radius:4px;border:1px solid <?php echo $sTagSt['border']; ?>;"><?php echo htmlspecialchars($sTagLvl, ENT_QUOTES, 'UTF-8'); ?></span>
              </span>
            <?php endforeach; ?>
            <?php if ($extraCount > 0): ?>
              <span class="mitra-skill-badge" style="background:#e2e8f0;color:#475569;">+<?php echo $extraCount; ?></span>
            <?php endif; ?>
          </div>

          <!-- Project Stats: Selesai dari Total -->
          <div class="mitra-stats-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?php echo (int)$w['completed_projects']; ?> dari <?php echo (int)($w['total_projects'] ?? $w['completed_projects']); ?> proyek selesai</span>
          </div>
        </div>

        <!-- Box Footer with Lihat Profil Button -->
        <div class="mitra-box-footer">
          <div class="btn-tawarkan-card" style="background:#f8fafc;color:#1d4ed8;border:1px solid #bfdbfe;font-weight:700;">
            Lihat Profil Detail →
          </div>
        </div>

      </div>
      <?php endforeach; ?>

      <div id="no-mitra-match" class="mitra-empty-state" style="display:none;">
        <div style="font-size:2.4rem;margin-bottom:10px;">🔍</div>
        <div style="font-weight:800;font-size:1.05rem;color:#1e293b;margin-bottom:6px;">
          Gig Workers Tidak Ditemukan <span id="no-mitra-query-wrap" style="display:none;">untuk &ldquo;<span id="no-mitra-query"></span>&rdquo;</span>
        </div>
        <p style="font-size:0.86rem;color:var(--text-muted);max-width:520px;margin:0 auto 16px auto;line-height:1.5;">
          Tidak ada profil Gig Worker yang memuat kata kunci tersebut dalam nama, keahlian, riwayat proyek, deliverable portofolio, ulasan pemberi kerja, maupun data registrasinya.
        </p>
        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
          <button type="button" class="btn-primary" style="padding:8px 18px;font-size:0.84rem;border-radius:8px;background:var(--primary-blue);color:#fff;border:none;cursor:pointer;font-weight:700;" onclick="clearWorkerSearch()">
            ✕ Reset Pencarian
          </button>
          <button type="button" class="btn-secondary" style="padding:8px 16px;font-size:0.84rem;border-radius:8px;cursor:pointer;" onclick="setQuickSearch('Figma')">
            Cari &ldquo;Figma&rdquo;
          </button>
          <button type="button" class="btn-secondary" style="padding:8px 16px;font-size:0.84rem;border-radius:8px;cursor:pointer;" onclick="setQuickSearch('REST API')">
            Cari &ldquo;REST API&rdquo;
          </button>
          <button type="button" class="btn-secondary" style="padding:8px 16px;font-size:0.84rem;border-radius:8px;cursor:pointer;" onclick="setQuickSearch('Laravel')">
            Cari &ldquo;Laravel&rdquo;
          </button>
        </div>
      </div>
    </div>

    <!-- Offer Project Modal -->
    <div class="modal-backdrop" id="offerProjectModal" onclick="if(event.target.id==='offerProjectModal') closeOfferModal();">
      <div class="modal-window" style="max-width:560px;">
        <div class="modal-header">
          <h3>Tawarkan Proyek ke <span id="offer-worker-name"></span></h3>
          <button class="modal-close-btn" type="button" onclick="closeOfferModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:20px;">
          <?php if (count($activeVacancies) === 0): ?>
            <div style="text-align:center;padding:24px 0;">
              <div style="font-size:2rem;margin-bottom:12px;">📋</div>
              <div style="font-weight:700;font-size:0.95rem;color:#1e293b;margin-bottom:6px;">Belum Ada Lowongan Aktif</div>
              <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.5;margin-bottom:16px;">Anda belum memiliki lowongan yang sudah tayang. Ajukan lowongan terlebih dahulu dan tunggu persetujuan Admin sebelum dapat menawarkan proyek ke Gig Worker.</p>
              <a href="employer-lowongan.php" class="btn-primary" style="display:inline-block;text-decoration:none;padding:8px 20px;border-radius:8px;font-size:0.86rem;">Kelola Lowongan →</a>
            </div>
          <?php else: ?>
            <p style="font-size:0.86rem;color:var(--text-muted);margin-bottom:14px;">Pilih proyek aktif yang ingin Anda tawarkan. Maksimal <strong>3 penawaran</strong> per lowongan.</p>
            <div style="display:flex;flex-direction:column;gap:10px;" id="offer-vacancy-list">
              <?php foreach ($activeVacancies as $v): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;"
                   data-vacancy-id="<?php echo htmlspecialchars($v['id'], ENT_QUOTES, 'UTF-8'); ?>"
                   data-offer-count="<?php echo (int)($vacancyOfferCounts[$v['id']] ?? 0); ?>">
                <div>
                  <div style="font-size:0.88rem;font-weight:700;color:#1e293b;"><?php echo htmlspecialchars($v['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <div style="font-size:0.74rem;color:var(--text-muted);margin-top:2px;">
                    <?php echo htmlspecialchars($v['budget'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($v['duration'], ENT_QUOTES, 'UTF-8'); ?>
                    · <span style="color:#059669;font-weight:700;">Penempatan 1 Gig Worker</span>
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
      let currentOfferWorkerId = '';
      let currentOfferWorkerName = '';
      let offerCounts = <?php echo json_encode($vacancyOfferCounts, JSON_UNESCAPED_UNICODE); ?> || {};
      const alreadyOffered = <?php echo json_encode($alreadyOfferedMap, JSON_UNESCAPED_UNICODE); ?> || {};
      let currentCategory = 'all';

      function filterWorkers(cat, btn) {
        currentCategory = cat;
        document.querySelectorAll('.marketplace-filter-group .filter-btn-pill').forEach(function(b) { b.classList.remove('active'); });
        if (btn) btn.classList.add('active');
        applyFilters();
      }

      function searchWorkers(query) {
        applyFilters();
      }

      function clearWorkerSearch() {
        const input = document.getElementById('workerSearchInput');
        if (input) {
          input.value = '';
          input.focus();
        }
        applyFilters();
      }

      function setQuickSearch(term) {
        const input = document.getElementById('workerSearchInput');
        if (input) {
          input.value = term;
          input.focus();
        }
        applyFilters();
      }

      function escapeHtml(str) {
        return String(str || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function escapeRegExp(string) {
        return String(string).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      }

      function highlightSnippet(text, term, maxLen = 65) {
        if (!text) return '';
        const lowerText = text.toLowerCase();
        const lowerTerm = term.toLowerCase();
        const idx = lowerText.indexOf(lowerTerm);
        if (idx === -1) {
          return escapeHtml(text.length > maxLen ? text.substring(0, maxLen) + '...' : text);
        }
        const start = Math.max(0, idx - 18);
        const end = Math.min(text.length, idx + term.length + 32);
        let snippet = text.substring(start, end);
        if (start > 0) snippet = '...' + snippet;
        if (end < text.length) snippet = snippet + '...';
        
        const escaped = escapeHtml(snippet);
        const regex = new RegExp('(' + escapeRegExp(escapeHtml(term)) + ')', 'gi');
        return escaped.replace(regex, '<mark class="kw-hl">$1</mark>');
      }

      function findFacetMatchSnippet(facetJson, terms, fullQuery) {
        if (!facetJson) return null;
        let facets;
        try {
          facets = JSON.parse(facetJson);
        } catch (e) {
          return null;
        }

        const primaryTerm = terms[0] || fullQuery;
        if (!primaryTerm) return null;

        // 1. Portfolios (projects, clients, deliverables, files)
        if (Array.isArray(facets.portfolios)) {
          for (let p of facets.portfolios) {
            const pComb = ((p.title || '') + ' ' + (p.client || '') + ' ' + (p.deliverable || '') + ' ' + (p.files || '') + ' ' + (p.type || '')).toLowerCase();
            if (pComb.includes(primaryTerm)) {
              let snippet = p.title || 'Proyek Portofolio';
              if (p.client) snippet += ' (' + p.client + ')';
              if (p.deliverable && p.deliverable.toLowerCase().includes(primaryTerm)) {
                snippet += ' · ' + highlightSnippet(p.deliverable, primaryTerm);
              } else if (p.files && p.files.toLowerCase().includes(primaryTerm)) {
                snippet += ' · Berkas: ' + highlightSnippet(p.files, primaryTerm);
              }
              return { type: 'Portofolio', text: snippet };
            }
          }
        }

        // 2. Experiences (roles, companies, summaries, outputs)
        if (Array.isArray(facets.experiences)) {
          for (let exp of facets.experiences) {
            const expComb = ((exp.role || '') + ' ' + (exp.project || '') + ' ' + (exp.summary || '') + ' ' + (exp.output_title || '') + ' ' + (exp.files || '')).toLowerCase();
            if (expComb.includes(primaryTerm)) {
              let snippet = (exp.role || 'Pengalaman') + (exp.project ? ' · ' + exp.project : '');
              if (exp.summary && exp.summary.toLowerCase().includes(primaryTerm)) {
                snippet += ': ' + highlightSnippet(exp.summary, primaryTerm);
              } else if (exp.output_title && exp.output_title.toLowerCase().includes(primaryTerm)) {
                snippet += ' · Output: ' + highlightSnippet(exp.output_title, primaryTerm);
              }
              return { type: 'Pengalaman', text: snippet };
            }
          }
        }

        // 3. Complete Skills list
        if (Array.isArray(facets.skills)) {
          for (let sk of facets.skills) {
            const skName = (sk.name || '').toLowerCase();
            const skLabel = (sk.label || '').toLowerCase();
            if (skName.includes(primaryTerm) || skLabel.includes(primaryTerm)) {
              return { type: 'Keahlian Lengkap', text: sk.label || sk.name };
            }
          }
        }

        // 4. Employer Reviews & Feedback
        if (Array.isArray(facets.reviews)) {
          for (let r of facets.reviews) {
            const rComb = ((r.employer || '') + ' ' + (r.project || '') + ' ' + (r.comment || '') + ' ' + (r.badges || '')).toLowerCase();
            if (rComb.includes(primaryTerm)) {
              let snippet = r.employer || 'Pemberi Kerja';
              if (r.comment && r.comment.toLowerCase().includes(primaryTerm)) {
                snippet += ': "' + highlightSnippet(r.comment, primaryTerm) + '"';
              } else if (r.badges && r.badges.toLowerCase().includes(primaryTerm)) {
                snippet += ' · Lencana: ' + r.badges;
              }
              return { type: 'Ulasan Klien', text: snippet };
            }
          }
        }

        // 5. Bio / Proposal / Summary
        if (facets.bio && facets.bio.toLowerCase().includes(primaryTerm)) {
          return { type: 'Deskripsi Profil', text: highlightSnippet(facets.bio, primaryTerm) };
        }

        // 6. Location / Domisili
        if (facets.location && facets.location.toLowerCase().includes(primaryTerm)) {
          return { type: 'Domisili', text: facets.location };
        }

        // 7. Social Media
        if (Array.isArray(facets.social)) {
          for (let s of facets.social) {
            if (s.toLowerCase().includes(primaryTerm)) {
              return { type: 'Media Sosial', text: s };
            }
          }
        }

        // 8. Contact Details
        if (facets.contact && facets.contact.toLowerCase().includes(primaryTerm)) {
          return { type: 'Kontak', text: facets.contact };
        }

        // 9. Job Headline
        if (facets.title && facets.title.toLowerCase().includes(primaryTerm)) {
          return { type: 'Jabatan / Headline', text: facets.title };
        }

        // 10. Worker Name
        if (facets.name && facets.name.toLowerCase().includes(primaryTerm)) {
          return { type: 'Nama Gig Worker', text: facets.name };
        }

        return null;
      }

      function applyFilters() {
        const inputEl = document.getElementById('workerSearchInput');
        const rawQuery = (inputEl ? inputEl.value : '') || '';
        const query = rawQuery.toLowerCase().trim();
        
        // Show/hide clear button
        const clearBtn = document.getElementById('workerSearchClearBtn');
        if (clearBtn) {
          clearBtn.style.display = query.length > 0 ? 'inline-flex' : 'none';
        }

        // Split query into terms to support multi-word search (AND logic)
        const terms = query.split(/\s+/).filter(Boolean);

        let count = 0;
        const boxes = document.querySelectorAll('#workers-grid .mitra-box');
        
        boxes.forEach(function(card) {
          const categoryMatch = currentCategory === 'all' || card.getAttribute('data-category') === currentCategory;
          
          // Exhaustive profile corpus
          const corpus = (card.getAttribute('data-search-corpus') || '').toLowerCase();
          
          // Check if every search term exists in profile corpus
          let searchMatch = true;
          if (terms.length > 0) {
            for (let i = 0; i < terms.length; i++) {
              if (corpus.indexOf(terms[i]) === -1) {
                searchMatch = false;
                break;
              }
            }
          }

          const show = categoryMatch && searchMatch;
          card.style.display = show ? 'flex' : 'none';
          if (show) count++;

          // Dynamic Match Highlight Snippet
          const matchPill = card.querySelector('.mitra-match-pill');
          if (matchPill) {
            if (show && terms.length > 0) {
              const facetJson = card.getAttribute('data-search-facets');
              const matchInfo = findFacetMatchSnippet(facetJson, terms, query);
              if (matchInfo) {
                matchPill.innerHTML = '<span class="pill-spark">✨</span> <span class="pill-type">' + escapeHtml(matchInfo.type) + ':</span> <span class="pill-text">' + matchInfo.text + '</span>';
                matchPill.style.display = 'flex';
              } else {
                matchPill.style.display = 'none';
              }
            } else {
              matchPill.style.display = 'none';
            }
          }
        });

        // Update count numbers
        const countEl = document.getElementById('worker-count');
        if (countEl) countEl.innerText = String(count);

        // Active query indicator
        const activeQueryBox = document.getElementById('active-query-indicator');
        const activeQueryText = document.getElementById('active-query-text');
        if (activeQueryBox && activeQueryText) {
          if (query.length > 0) {
            activeQueryText.innerText = rawQuery;
            activeQueryBox.style.display = 'inline-flex';
          } else {
            activeQueryBox.style.display = 'none';
          }
        }

        // Empty state
        const emptyEl = document.getElementById('no-mitra-match');
        const emptyWrap = document.getElementById('no-mitra-query-wrap');
        const emptyQuery = document.getElementById('no-mitra-query');
        if (emptyEl) {
          emptyEl.style.display = count === 0 ? 'block' : 'none';
          if (emptyWrap && emptyQuery) {
            if (query.length > 0) {
              emptyQuery.innerText = rawQuery;
              emptyWrap.style.display = 'inline';
            } else {
              emptyWrap.style.display = 'none';
            }
          }
        }
      }

      // Keyboard shortcuts: '/' to focus, 'Escape' to clear
      document.addEventListener('keydown', function(e) {
        const input = document.getElementById('workerSearchInput');
        if (!input) return;
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        if (e.key === '/' && activeTag !== 'input' && activeTag !== 'textarea') {
          e.preventDefault();
          input.focus();
          input.select();
        } else if (e.key === 'Escape' && document.activeElement === input) {
          clearWorkerSearch();
        }
      });

      // Initialize filter on load (applies pre-filled query from ?q= if present)
      document.addEventListener('DOMContentLoaded', function() {
        applyFilters();
      });

      function toastOffer(message) {
        if (typeof showToast === 'function') showToast(message);
        else alert(message);
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

      function openOfferModal(workerId, workerName) {
        currentOfferWorkerId = workerId;
        currentOfferWorkerName = workerName;
        document.getElementById('offer-worker-name').innerText = workerName;
        refreshOfferButtons();
        document.getElementById('offerProjectModal').classList.add('open');
      }

      function closeOfferModal() {
        document.getElementById('offerProjectModal').classList.remove('open');
      }

      function sendOffer(vacancyId, vacancyTitle) {
        if (!currentOfferWorkerId) {
          toastOffer('Pilih Gig Worker terlebih dahulu.');
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
              toastOffer((data && data.error) ? data.error : 'Penawaran gagal dikirim.');
              if (btn) btn.disabled = false;
              refreshOfferButtons();
              return;
            }

            offerCounts[vacancyId] = data.offer_count || ((offerCounts[vacancyId] || 0) + 1);
            if (!alreadyOffered[currentOfferWorkerId]) alreadyOffered[currentOfferWorkerId] = [];
            if (alreadyOffered[currentOfferWorkerId].indexOf(vacancyId) === -1) {
              alreadyOffered[currentOfferWorkerId].push(vacancyId);
            }

            toastOffer('Penawaran "' + vacancyTitle + '" dikirim ke ' + currentOfferWorkerName + '. Gig Worker dapat membuka detail proyek dari halaman Penawaran.');
            refreshOfferButtons();
            closeOfferModal();
          })
          .catch(function () {
            toastOffer('Koneksi terputus. Coba kirim penawaran lagi.');
            if (btn) btn.disabled = false;
          });
      }
    </script>

<?php require __DIR__ . '/includes/employer-layout-end.php'; ?>

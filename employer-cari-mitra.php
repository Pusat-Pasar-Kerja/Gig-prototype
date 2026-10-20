<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-offers.php';

$workerProfiles = gig_worker_profiles();
$vacancies = gig_project_vacancies();

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
  /* Marketplace Catalogue Grid Styling */
  .marketplace-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
    background: #ffffff;
    padding: 16px 20px;
    border-radius: 14px;
    border: 1px solid var(--border-subtle);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  }

  .marketplace-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .mitra-search-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid var(--border-subtle);
    border-radius: 9999px;
    padding: 8px 16px;
    min-width: 280px;
    transition: border-color 0.2s, background-color 0.2s;
  }

  .mitra-search-box:focus-within {
    border-color: var(--primary-blue);
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(22, 87, 193, 0.12);
  }

  .mitra-search-box input {
    border: none;
    outline: none;
    background: transparent;
    font-size: 0.85rem;
    color: var(--text-main);
    width: 100%;
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

    <!-- Toolbar Filters & Search -->
    <div class="marketplace-toolbar">
      <div class="marketplace-filter-group">
        <button class="filter-btn-pill active" type="button" onclick="filterWorkers('all', this)">Semua Bidang (<?php echo count($workerProfiles); ?>)</button>
        <button class="filter-btn-pill" type="button" onclick="filterWorkers('ui-ux', this)">UI/UX & Desain</button>
        <button class="filter-btn-pill" type="button" onclick="filterWorkers('backend', this)">Backend & API</button>
        <button class="filter-btn-pill" type="button" onclick="filterWorkers('marketing', this)">Pemasaran</button>
      </div>
      <div class="mitra-search-box">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Cari nama, keahlian, atau bidang..." onkeyup="searchWorkers(this.value)" id="workerSearchInput" />
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding:0 2px;">
      <p style="font-size:0.82rem;color:var(--text-muted);">
        Menampilkan <strong id="worker-count" style="color:var(--text-main);"><?php echo count($workerProfiles); ?></strong> Gig Workers siap kerja
      </p>
      <span style="font-size:0.75rem;color:#64748b;background:#f1f5f9;padding:4px 10px;border-radius:6px;">
        💡 Maks. 3 penawaran per lowongan aktif
      </span>
    </div>

    <!-- Marketplace Grid of Gig Workers Boxes -->
    <div class="mitra-grid" id="workers-grid">
      <?php foreach ($workerProfiles as $w): 
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
        <div style="font-size:2.2rem;margin-bottom:8px;">🔍</div>
        <div style="font-weight:700;font-size:0.95rem;color:#1e293b;margin-bottom:4px;">Gig Workers Tidak Ditemukan</div>
        <p style="font-size:0.84rem;color:var(--text-muted);">Coba ubah kata kunci pencarian atau pilih kategori bidang lainnya.</p>
      </div>
    </div>    </div>

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

      function applyFilters() {
        const query = (document.getElementById('workerSearchInput').value || '').toLowerCase().trim();
        let count = 0;
        const boxes = document.querySelectorAll('#workers-grid .mitra-box');
        
        boxes.forEach(function(card) {
          const categoryMatch = currentCategory === 'all' || card.getAttribute('data-category') === currentCategory;
          const searchMatch = !query || card.innerText.toLowerCase().includes(query);
          const show = categoryMatch && searchMatch;
          
          card.style.display = show ? 'flex' : 'none';
          if (show) count++;
        });

        const countEl = document.getElementById('worker-count');
        if (countEl) countEl.innerText = String(count);

        const emptyEl = document.getElementById('no-mitra-match');
        if (emptyEl) {
          emptyEl.style.display = count === 0 ? 'block' : 'none';
        }
      }

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

<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/user-avatars.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-applications.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-offers.php';

$role = $_SESSION['role'] ?? 'worker';
$username = $_SESSION['username'] ?? $_SESSION['siapkerja_name'] ?? 'Theressa Zaratrusha';

$workerId = trim((string)($_GET['id'] ?? ''));
if ($workerId === '') {
    $workerId = 'tessa';
}

$worker = gig_find_worker($workerId);
if ($worker === null) {
    $worker = gig_find_worker('tessa');
}
if ($role === 'worker' && !empty($_SESSION['siapkerja_email'])) {
    $ownId = gig_avatar_worker_profile_id(gig_avatar_account_key());
    $viewId = (string)($worker['id'] ?? $workerId);
    if ($viewId === $ownId) {
        $worker['photo'] = gig_worker_display_photo($ownId);
    }
}

$isActive = !empty($_GET['active']);
$contactUnlocked = !empty($worker['agreed']) || $isActive || ($role === 'worker');
if ($role === 'employer' && !$contactUnlocked) {
    foreach (gig_get_applications_for_worker((string)($worker['id'] ?? $workerId)) as $hiredApp) {
        if (gig_is_hired_status((string)($hiredApp['status'] ?? ''))) {
            $contactUnlocked = true;
            break;
        }
    }
}

$activeVacancies = [];
$vacancyOfferCounts = [];
$alreadyOfferedMap = [];
if ($role === 'employer') {
    $vacancies = gig_project_vacancies();
    $activeVacancies = array_values(array_filter($vacancies, fn($v) => $v['status'] === 'active'));
    foreach ($activeVacancies as $v) {
        $vacancyOfferCounts[$v['id']] = gig_count_vacancy_offers($v['id']);
    }
    $alreadyOfferedMap = gig_employer_offered_map($username);
}

$from = (string)($_GET['from'] ?? '');
$pageTitle = $worker['name'] . ' · Profil Gig Workers';
$pageKey = ($role === 'employer' && $from === 'cari-mitra') ? 'cari-mitra' : ($role === 'worker' ? 'profil' : 'pelamar');
$breadcrumbCurrent = $worker['name'];

if ($role === 'worker') {
    require __DIR__ . '/includes/worker-layout-start.php';
} else {
    require __DIR__ . '/includes/employer-layout-start.php';
}
?>

    <div class="page-toolbar">
      <h1><?php echo htmlspecialchars($worker['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
      <div style="display:flex;gap:10px;align-items:center;">
        <?php if ($role === 'worker'): ?>
          <a class="btn-primary-add" href="worker-edit-profile.php">✏️ Edit Profil</a>
        <?php endif; ?>
        <?php 
          $backUrl = $from === 'cari-mitra' ? 'employer-cari-mitra.php' : ($from === 'kandidat' ? 'employer-pelamar.php' : 'javascript:history.back()');
          $backLabel = $from === 'cari-mitra' ? '← Kembali ke Cari Mitra' : ($from === 'kandidat' ? '← Kembali ke Kandidat' : '← Kembali');
        ?>
        <a class="btn-action-sm" href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?></a>
      </div>
    </div>

    <?php if (!empty($_GET['updated'])): ?>
      <div class="privacy-banner unlocked" style="background:#ecfdf5;color:#065f46;border-color:#a7f3d0;font-weight:700;">
        ✓ Profil Gig Workers Anda telah berhasil diperbarui.
      </div>
    <?php endif; ?>

    <?php if ($role === 'worker'): ?>
      <div class="privacy-banner unlocked">Ini adalah tampilan profil publik Gig Workers Anda yang dapat dilihat oleh calon Pemberi Kerja.</div>
    <?php elseif ($contactUnlocked): ?>
      <div class="privacy-banner unlocked">Kerja sama aktif. Informasi kontak dapat dilihat di bawah.</div>
    <?php endif; ?>

    <section class="profile-hero">
      <div class="profile-photo-wrap">
        <div class="profile-photo" style="width:80px;height:80px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:2.2rem;box-shadow:0 4px 14px rgba(37,99,235,0.3);flex-shrink:0;"><?php echo htmlspecialchars(strtoupper(substr((string)$worker['name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <div class="profile-id" style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:center;width:100%;flex-wrap:wrap;gap:12px;">
          <div class="worker-display-name"><?php echo htmlspecialchars($worker['name'], ENT_QUOTES, 'UTF-8'); ?></div>
          <?php if ($role === 'employer'): ?>
            <button type="button" class="btn-primary-add" style="background:var(--primary-blue);color:#ffffff;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-weight:700;padding:8px 18px;border-radius:10px;box-shadow:0 4px 12px rgba(37,99,235,0.25);" onclick="openOfferModal('<?php echo htmlspecialchars((string)$worker['id'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes((string)$worker['name']), ENT_QUOTES, 'UTF-8'); ?>')">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Tawarkan Proyek
            </button>
          <?php endif; ?>
        </div>
        <div class="profile-title"><?php echo htmlspecialchars($worker['title'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($worker['location'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="profile-meta">
          <span class="stars"><?php echo gig_stars($worker['rating']); ?></span>
          <strong><?php echo number_format((float)$worker['rating'], 1); ?></strong>
          <span class="chip gold"><?php echo (int)$worker['reviews_count']; ?> ulasan pemberi kerja</span>
          <span class="chip"><?php echo (int)$worker['completed_projects']; ?> dari <?php echo (int)($worker['total_projects'] ?? $worker['completed_projects']); ?> proyek selesai</span>
        </div>
      </div>
    </section>

    <?php
      $regData = gig_get_worker_registration((string)($worker['id'] ?? '')) ?? [];
      $rawVideoStr = !empty($regData['video_url']) ? $regData['video_url'] : ($worker['video_url'] ?? '');
      $videoUrlsList = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', (string)$rawVideoStr)), static fn($u) => $u !== ''));
      $socialMediaList = [];
      if (!empty($regData['social_media']) && is_array($regData['social_media'])) {
          $socialMediaList = $regData['social_media'];
      } elseif (!empty($worker['social_media']) && is_array($worker['social_media'])) {
          $socialMediaList = $worker['social_media'];
      }
      $bidangValue = trim((string)($regData['bidang_keahlian'] ?? ($worker['title'] ?? '')));
      $skillsList = gig_normalize_skills(!empty($regData['skills']) ? $regData['skills'] : ($worker['skills'] ?? []));
    ?>

    <!-- SEGMENT 2: MEDIA SOSIAL GIG WORKER -->
    <?php if ($socialMediaList !== []): ?>
      <section class="section-card">
        <h2>Media Sosial &amp; Jejak Profesional</h2>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
          <?php foreach ($socialMediaList as $sm): ?>
            <?php
              $smPlatform = trim((string)($sm['platform'] ?? 'Media Sosial'));
              $smUrl = trim((string)($sm['url'] ?? ''));
              if ($smPlatform === '' && $smUrl === '') {
                  continue;
              }
            ?>
            <?php if ($smUrl !== ''): ?>
              <a href="<?php echo htmlspecialchars($smUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:999px;padding:8px 14px;text-decoration:none;color:#0f172a;font-size:0.82rem;font-weight:700;">
                🌐 <?php echo htmlspecialchars($smPlatform, ENT_QUOTES, 'UTF-8'); ?> ↗
              </a>
            <?php else: ?>
              <span style="display:inline-flex;align-items:center;background:#f8fafc;border:1px solid #cbd5e1;border-radius:999px;padding:8px 14px;color:#0f172a;font-size:0.82rem;font-weight:700;">
                🌐 <?php echo htmlspecialchars($smPlatform, ENT_QUOTES, 'UTF-8'); ?>
              </span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- SEGMENT 3: BIDANG KEAHLIAN & SKILL SPESIFIK -->
    <section class="section-card">
      <h2>Bidang Keahlian &amp; Skill Spesifik</h2>
      <?php if ($bidangValue !== ''): ?>
        <div style="margin-bottom:18px;">
          <div style="font-size:0.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Bidang Keahlian Utama</div>
          <span class="chip gold" style="font-size:0.88rem;padding:6px 14px;"><?php echo htmlspecialchars($bidangValue, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      <?php endif; ?>
      <?php if ($skillsList !== []): ?>
        <div>
          <div style="font-size:0.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Daftar Keahlian &amp; Tingkat Penguasaan</div>
          <div class="skill-row" style="display:flex;flex-wrap:wrap;gap:10px;">
            <?php foreach ($skillsList as $skill): ?>
              <?php
                $sName = gig_skill_name($skill);
                $sLvl = gig_skill_level($skill);
                $sStyle = gig_skill_level_badge_style($sLvl);
              ?>
              <div class="skill-tag" style="display:inline-flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:6px 12px;">
                <span style="font-weight:700;color:#0f172a;font-size:0.86rem;"><?php echo htmlspecialchars($sName, ENT_QUOTES, 'UTF-8'); ?></span>
                <span style="background:<?php echo $sStyle['bg']; ?>;color:<?php echo $sStyle['color']; ?>;border:1px solid <?php echo $sStyle['border']; ?>;padding:2px 8px;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;">
                  <?php echo htmlspecialchars($sLvl, ENT_QUOTES, 'UTF-8'); ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <!-- SEGMENT 4: PENGALAMAN -->
    <section class="section-card">
      <h2>Pengalaman</h2>
      <div class="timeline">
        <?php 
          if (!empty($worker['experience']) && is_array($worker['experience'])) {
              gig_sort_experience_timeline($worker['experience']);
          }
          foreach ($worker['experience'] as $item): 
            $outTitle = !empty($item['output_title']) ? $item['output_title'] : ('Output Proyek - ' . ($item['project'] ?? 'Deliverables Proyek'));
            $outFiles = !empty($item['files']) && is_array($item['files']) ? $item['files'] : [
                ['name' => 'Tautan Output Proyek (' . ($item['project'] ?? 'Hasil Karya') . ')', 'url' => 'https://figma.com/@gigworker/' . urlencode(strtolower(str_replace([' ', '(', ')', '/'], ['-', '', '', '-'], $item['project'] ?? 'output-proyek')))]
            ];
        ?>
          <article class="timeline-item" style="margin-bottom:18px;">
            <strong style="font-size:0.98rem;color:#0f172a;"><?php echo htmlspecialchars($item['role'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span class="muted" style="display:block;margin-top:2px;font-size:0.83rem;color:#64748b;"><?php echo htmlspecialchars($item['project'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8'); ?></span>
            <p style="margin-top:6px;font-size:0.88rem;color:#334155;line-height:1.5;"><?php echo htmlspecialchars($item['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
            
            <div style="margin-top:12px;padding:12px 14px;border:1px solid #bfdbfe;background:#f0f9ff;border-radius:10px;">
              <div style="font-size:0.82rem;font-weight:700;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <span>📁 Output Proyek:</span>
                <span><?php echo htmlspecialchars((string)$outTitle, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($outFiles as $f): ?>
                  <?php 
                    $fUrl = trim((string)($f['url'] ?? '')); 
                    if ($fUrl === '' || $fUrl === '#') {
                        $fUrl = 'https://figma.com/@gigworker/' . urlencode(strtolower(str_replace([' ', '(', ')', '/'], ['-', '', '', '-'], $item['project'] ?? 'output')));
                    }
                  ?>
                  <a href="<?php echo htmlspecialchars($fUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;background:#ffffff;color:#1d4ed8;border:1px solid #93c5fd;padding:6px 12px;border-radius:8px;font-size:0.78rem;font-weight:700;text-decoration:none;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    🔗 <?php echo htmlspecialchars((string)($f['name'] ?? 'Lihat Tautan Output Proyek'), ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- SEGMENT 5: LINK VIDEO PROFIL GIG WORKERS -->
    <?php if ($videoUrlsList !== []): ?>
      <section class="section-card">
        <h2>Video Profil Gig Workers</h2>
        <p style="font-size:0.84rem; color: var(--text-muted); margin-bottom: 12px;">Perkenalan singkat dan paparan keahlian dari Gig Workers.</p>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach ($videoUrlsList as $vIdx => $vUrlItem): ?>
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
              <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: #ef4444; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                  ▶
                </div>
                <div>
                  <strong style="font-size: 0.9rem; color: #0f172a;">Video Perkenalan &amp; Demo Portofolio <?php echo count($videoUrlsList) > 1 ? '#' . ($vIdx + 1) : ''; ?></strong>
                  <div style="font-size: 0.78rem; color: #64748b;"><?php echo htmlspecialchars($vUrlItem, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
              <a href="<?php echo htmlspecialchars($vUrlItem, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" style="background: #2563eb; color: #fff; padding: 8px 16px; border-radius: 9999px; text-decoration: none; font-size: 0.82rem; font-weight: 700; flex-shrink: 0;">
                Putar Video ↗
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="section-card">
      <h2>Rating &amp; Ulasan Pemberi Kerja</h2>
      <div class="review-list">
        <?php foreach ($worker['reviews'] as $review): ?>
          <article class="review-card">
            <div class="review-top">
              <div>
                <strong><?php echo htmlspecialchars($review['employer'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <div class="muted"><?php echo htmlspecialchars($review['project'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($review['date'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div>
                <span class="stars"><?php echo gig_stars($review['rating']); ?></span>
                <strong><?php echo (int)$review['rating']; ?>/5</strong>
              </div>
            </div>
            
            <?php if (!empty($review['badges']) && is_array($review['badges'])): ?>
              <div style="display:flex;gap:6px;flex-wrap:wrap;margin:8px 0;">
                <?php foreach ($review['badges'] as $b): ?>
                  <span style="font-size:0.72rem;background:#eff6ff;color:#1e40af;padding:2px 8px;border-radius:9999px;font-weight:700;border:1px solid #bfdbfe;">
                    <?php echo htmlspecialchars((string)$b, ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <p style="margin-top:4px;"><?php echo htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section-card">
      <h2>Informasi Kontak</h2>
      <?php if ($contactUnlocked): ?>
        <div class="contact-box">
          <span class="contact-tag">WhatsApp: <?php echo htmlspecialchars($worker['contact']['wa'], ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="contact-tag">Email: <?php echo htmlspecialchars($worker['contact']['email'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      <?php else: ?>
        <div class="locked-box">
          Kontak belum dapat dibuka. Setelah Anda menerima lamaran <?php echo htmlspecialchars($worker['name'], ENT_QUOTES, 'UTF-8'); ?>, atau setelah Gig Workers menerima penawaran langsung, WhatsApp dan email akan ditampilkan di sini.
        </div>
      <?php endif; ?>
    </section>

    <!-- Deliverable Modal -->
    <div class="modal-backdrop" id="deliverableModal" onclick="if(event.target.id==='deliverableModal') closeDeliverableModal();">
      <div class="modal-window" style="max-width:620px;">
        <div class="modal-header">
          <h3 id="deliv-modal-title">Deliverable Portofolio</h3>
          <button class="modal-close-btn" type="button" onclick="closeDeliverableModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:20px;">
          <div style="font-size:0.8rem;color:var(--text-muted);" id="deliv-modal-meta">Client · Year</div>
          <p style="margin:10px 0 16px 0;font-size:0.9rem;line-height:1.5;color:var(--text-dark);" id="deliv-modal-desc"></p>
          
          <h4 style="font-size:0.88rem;font-weight:700;margin-bottom:10px;">Deliverable yang Diunggah Gig Workers:</h4>
          <div id="deliv-modal-files" style="display:flex;flex-direction:column;gap:10px;">
            <!-- Rendered by JS -->
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeDeliverableModal()">Tutup</button>
        </div>
      </div>
    </div>

    <?php if ($role === 'employer'): ?>
    <!-- Offer Project Modal -->
    <div class="modal-backdrop" id="offerProjectModal" onclick="if(event.target.id==='offerProjectModal') closeOfferModal();">
      <div class="modal-window" style="max-width:560px;">
        <div class="modal-header">
          <h3>Tawarkan Proyek ke <span id="offer-worker-name"><?php echo htmlspecialchars($worker['name'], ENT_QUOTES, 'UTF-8'); ?></span></h3>
          <button class="modal-close-btn" type="button" onclick="closeOfferModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:20px;">
          <?php if (count($activeVacancies) === 0): ?>
            <div style="text-align:center;padding:24px 0;">
              <div style="font-size:2rem;margin-bottom:12px;">📋</div>
              <div style="font-weight:700;font-size:0.95rem;color:#1e293b;margin-bottom:6px;">Belum Ada Lowongan Aktif</div>
              <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.5;margin-bottom:16px;">Anda belum memiliki lowongan yang sudah tayang. Ajukan lowongan terlebih dahulu dan tunggu persetujuan Admin sebelum dapat menawarkan proyek ke Gig Workers.</p>
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
                    · <span style="color:#059669;font-weight:700;">Penempatan 1 Gig Workers</span>
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
    <?php endif; ?>

    <script>
      const workerPortfolioData = <?php echo json_encode($worker['portfolio']); ?>;

      function openDeliverableModal(id) {
        const item = workerPortfolioData.find(p => p.id === id);
        if (!item) return;
        
        document.getElementById('deliv-modal-title').innerText = item.title + ' (' + item.type + ')';
        document.getElementById('deliv-modal-meta').innerText = 'Pemberi Kerja: ' + item.client + ' · Tahun: ' + item.year;
        document.getElementById('deliv-modal-desc').innerText = item.deliverable;
        
        const filesContainer = document.getElementById('deliv-modal-files');
        filesContainer.innerHTML = '';
        
        if (item.files && item.files.length > 0) {
          item.files.forEach(f => {
            const fileRow = document.createElement('div');
            fileRow.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;';
            const actionBtn = (f.url && f.url !== '#')
              ? `<a href="${f.url}" target="_blank" rel="noopener noreferrer" style="background:#2563eb;color:#fff;padding:6px 12px;border-radius:6px;text-decoration:none;font-size:0.78rem;font-weight:700;flex-shrink:0;">Buka Berkas ↗</a>`
              : `<span style="font-size:0.75rem;color:#94a3b8;">Tersimpan</span>`;
            fileRow.innerHTML = `
              <span style="font-size:1.4rem;line-height:1;">${getFileIcon(f.type)}</span>
              <div style="flex:1;min-width:0;">
                <div style="font-size:0.86rem;font-weight:700;color:#1e293b;word-break:break-all;">${f.name}</div>
                <div style="font-size:0.74rem;color:#64748b;margin-top:2px;">${f.type} &bull; ${f.size}</div>
              </div>
              ${actionBtn}
            `;
            filesContainer.appendChild(fileRow);
          });
        } else {
          filesContainer.innerHTML = '<div style="font-size:0.82rem;color:var(--text-muted);">Tidak ada lampiran file fisik.</div>';
        }
        
        document.getElementById('deliverableModal').classList.add('open');
      }

      function getFileIcon(type) {
        if (!type) return '📄';
        const t = type.toLowerCase();
        if (t.includes('figma')) return '🎨';
        if (t.includes('pdf') || t.includes('dokumen')) return '📄';
        if (t.includes('zip') || t.includes('arsip')) return '📦';
        if (t.includes('excel') || t.includes('xlsx') || t.includes('lembar')) return '📊';
        if (t.includes('api') || t.includes('spec') || t.includes('yaml') || t.includes('json')) return '⚙️';
        if (t.includes('code') || t.includes('php') || t.includes('source')) return '💻';
        if (t.includes('laporan') || t.includes('report')) return '📋';
        if (t.includes('aset') || t.includes('gambar') || t.includes('visual')) return '🖼️';
        return '📄';
      }

      function closeDeliverableModal() {
        document.getElementById('deliverableModal').classList.remove('open');
      }

      <?php if ($role === 'employer'): ?>
      let currentOfferWorkerId = '<?php echo htmlspecialchars((string)$worker['id'], ENT_QUOTES, 'UTF-8'); ?>';
      let currentOfferWorkerName = '<?php echo htmlspecialchars(addslashes((string)$worker['name']), ENT_QUOTES, 'UTF-8'); ?>';
      let offerCounts = <?php echo json_encode($vacancyOfferCounts, JSON_UNESCAPED_UNICODE); ?> || {};
      const alreadyOffered = <?php echo json_encode($alreadyOfferedMap, JSON_UNESCAPED_UNICODE); ?> || {};

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
        if (workerId) currentOfferWorkerId = workerId;
        if (workerName) currentOfferWorkerName = workerName;
        const nameEl = document.getElementById('offer-worker-name');
        if (nameEl) nameEl.innerText = currentOfferWorkerName;
        refreshOfferButtons();
        const modal = document.getElementById('offerProjectModal');
        if (modal) modal.classList.add('open');
      }

      function closeOfferModal() {
        const modal = document.getElementById('offerProjectModal');
        if (modal) modal.classList.remove('open');
      }

      function sendOffer(vacancyId, vacancyTitle) {
        if (!currentOfferWorkerId) {
          toastOffer('Pilih Gig Workers terlebih dahulu.');
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

            toastOffer('Penawaran "' + vacancyTitle + '" dikirim ke ' + currentOfferWorkerName + '. Gig Workers dapat membuka detail proyek dari halaman Penawaran.');
            refreshOfferButtons();
            closeOfferModal();
          })
          .catch(function () {
            toastOffer('Koneksi terputus. Coba kirim penawaran lagi.');
            if (btn) btn.disabled = false;
          });
      }
      <?php endif; ?>
    </script>

<?php 
if ($role === 'worker') {
    require __DIR__ . '/includes/worker-layout-end.php';
} else {
    require __DIR__ . '/includes/employer-layout-end.php';
}
?>

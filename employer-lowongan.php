<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/project-vacancies.php';

$vacancies = gig_project_vacancies();
$draftCount = 0;
$reviewCount = 0;
$revisionCount = 0;
$rejectedCount = 0;
$activeCount = 0;

foreach ($vacancies as $vacancy) {
    // Only active vacancies can have applicants — enforce this
    if (in_array($vacancy['status'], ['draft', 'review', 'revision', 'rejected'], true)) {
        $draftCount += ($vacancy['status'] === 'draft') ? 1 : 0;
        $reviewCount += ($vacancy['status'] === 'review') ? 1 : 0;
        $revisionCount += ($vacancy['status'] === 'revision') ? 1 : 0;
        $rejectedCount += ($vacancy['status'] === 'rejected') ? 1 : 0;
    } else {
        $activeCount++;
    }
}

$pageTitle = 'Lowongan Proyek';
$pageKey = 'lowongan';
$breadcrumbCurrent = 'Lowongan';
require __DIR__ . '/includes/employer-layout-start.php';

$colors = ['#2563eb', '#0891b2', '#7c3aed', '#059669', '#ea580c', '#db2777'];
?>

    <div class="page-toolbar">
      <h1>Lowongan Proyek</h1>
      <button class="btn-primary-add" type="button" onclick="openPostProjectModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah
      </button>
    </div>

    <section class="mini-stats">
      <article class="mini-stat">
        <div>
          <div class="lbl">Draft</div>
          <div class="num"><?php echo $draftCount; ?></div>
          <div class="sub">Belum diajukan</div>
        </div>
        <div class="ico">📄</div>
      </article>
      <article class="mini-stat">
        <div>
          <div class="lbl">Menunggu Verifikasi</div>
          <div class="num"><?php echo $reviewCount; ?></div>
          <div class="sub">Dalam proses verifikasi</div>
        </div>
        <div class="ico">⏱️</div>
      </article>
      <article class="mini-stat">
        <div>
          <div class="lbl">Perlu Direvisi</div>
          <div class="num"><?php echo $revisionCount; ?></div>
          <div class="sub">Perlu perbaikan</div>
        </div>
        <div class="ico">⚠️</div>
      </article>
      <article class="mini-stat">
        <div>
          <div class="lbl">Ditolak</div>
          <div class="num"><?php echo $rejectedCount; ?></div>
          <div class="sub">Tidak disetujui Admin</div>
        </div>
        <div class="ico">❌</div>
      </article>
      <article class="mini-stat">
        <div>
          <div class="lbl">Lowongan Aktif</div>
          <div class="num"><?php echo $activeCount; ?></div>
          <div class="sub">Sedang tayang</div>
        </div>
        <div class="ico">✓</div>
      </article>
    </section>

    <div class="toolbar-filter">
      <button class="filter-btn-pill active" type="button" onclick="filterVacancyRows('all', this)">Semua Status</button>
      <button class="filter-btn-pill" type="button" onclick="filterVacancyRows('active', this)">Aktif (<?php echo $activeCount; ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterVacancyRows('review', this)">Menunggu Verifikasi (<?php echo $reviewCount; ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterVacancyRows('revision', this)">Perlu Revisi (<?php echo $revisionCount; ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterVacancyRows('rejected', this)">Ditolak (<?php echo $rejectedCount; ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterVacancyRows('draft', this)">Draft (<?php echo $draftCount; ?>)</button>
      <div class="search-input-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Cari berdasarkan judul" onkeyup="searchVacancies(this.value)" />
      </div>
    </div>

    <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:10px;">Menampilkan <?php echo count($vacancies); ?> lowongan proyek (Klik judul lowongan untuk rincian)</p>

    <div class="vacancy-table-wrap">
      <table class="vacancy-table">
        <thead>
          <tr>
            <th>Lowongan</th>
            <th>Lokasi &amp; Mode</th>
            <th>Status Rekrutmen</th>
            <th>Kandidat</th>
            <th>Status Verifikasi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="vacancy-table-body">
          <?php foreach ($vacancies as $i => $job): ?>
          <tr data-status="<?php echo htmlspecialchars($job['status'], ENT_QUOTES, 'UTF-8'); ?>">
            <td>
              <div class="job-cell">
                <div class="job-avatar" style="background:<?php echo $colors[$i % count($colors)]; ?>">
                  <?php echo strtoupper(substr($job['title'], 0, 2)); ?>
                </div>
                <div>
                  <a class="job-title" href="employer-detail-lowongan.php?id=<?php echo urlencode($job['id']); ?>" style="color:inherit;text-decoration:none;font-weight:700;">
                    <?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                  <div class="job-sub">Dibuat <?php echo htmlspecialchars($job['posted'], ENT_QUOTES, 'UTF-8'); ?> · ID: <?php echo htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-weight:700;color:#0f172a;margin-bottom:4px;"><?php echo htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8'); ?></div>
              <?php if (gig_is_remote_work($job)): ?>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.72rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">🌐 Remote</span>
              <?php else: ?>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:0.72rem;font-weight:700;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;">🏢 On-site</span>
              <?php endif; ?>
            </td>
            <td><?php echo (int)$job['acceptedCount'] > 0 ? 'Sudah terisi' : 'Belum terisi'; ?></td>
            <td>
              <?php if ($job['status'] === 'active' && (int)$job['applicantsCount'] > 0): ?>
                <a href="employer-cari-mitra.php" style="color:var(--primary-blue);font-weight:700;text-decoration:none;">
                  <?php echo (int)$job['applicantsCount']; ?> kandidat
                </a>
              <?php elseif ($job['status'] === 'active'): ?>
                <span style="color:var(--text-muted);font-size:0.84rem;">0 kandidat</span>
              <?php else: ?>
                <span style="color:#cbd5e1;font-size:0.84rem;">— belum tayang</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($job['status'] === 'active'): ?>
                <span class="badge-status active" style="white-space:nowrap;">Tayang Aktif</span>
              <?php elseif ($job['status'] === 'review'): ?>
                <span style="display:inline-flex;align-items:center;white-space:nowrap;padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;">Menunggu Verifikasi</span>
              <?php elseif ($job['status'] === 'revision'): ?>
                <span style="display:inline-flex;align-items:center;white-space:nowrap;padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;">Perlu Revisi</span>
              <?php elseif ($job['status'] === 'rejected'): ?>
                <span style="display:inline-flex;align-items:center;white-space:nowrap;padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;">Ditolak</span>
              <?php else: ?>
                <span style="display:inline-flex;align-items:center;white-space:nowrap;padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;">Draft</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn-action-sm" href="employer-detail-lowongan.php?id=<?php echo urlencode($job['id']); ?>" title="Lihat Detail Lowongan" style="text-decoration:none;">Detail →</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

<?php require __DIR__ . '/includes/employer-layout-end.php'; ?>

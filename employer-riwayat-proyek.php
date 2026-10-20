<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-applications.php';
require_once __DIR__ . '/includes/project-history.php';

$pageTitle = 'Riwayat Proyek';
$pageKey = 'riwayat';
$breadcrumbCurrent = 'Riwayat Proyek';
require __DIR__ . '/includes/employer-layout-start.php';

$historyProjects = gig_get_history_for_employer($username);

$completedCount = 0;
$cancelledCount = 0;
foreach ($historyProjects as $p) {
    if ($p['statusCode'] === 'completed') {
        $completedCount++;
    } elseif ($p['statusCode'] === 'cancelled') {
        $cancelledCount++;
    }
}
?>

    <div class="page-toolbar">
      <div>
        <h1>Riwayat Proyek</h1>
        <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">Proyek yang sudah selesai atau tidak dilanjutkan. Proyek yang masih berjalan ada di Proyek Aktif.</p>
      </div>
    </div>

    <div class="toolbar-filter">
      <button class="filter-btn-pill active" type="button" onclick="filterHistory('all', this)">Semua (<?php echo count($historyProjects); ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterHistory('completed', this)">Selesai (<?php echo $completedCount; ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterHistory('cancelled', this)">Tidak Selesai (<?php echo $cancelledCount; ?>)</button>
      <div class="search-input-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Cari judul proyek atau freelancer..." onkeyup="searchHistory(this.value)" />
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;" id="history-list">
      <?php if (count($historyProjects) === 0): ?>
      <div class="white-card" style="text-align:center;padding:48px 24px;">
        <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:6px;">Belum ada riwayat proyek</h3>
        <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:16px;">Proyek yang masih berjalan tampil di Proyek Aktif. Setelah selesai, proyek pindah ke halaman ini.</p>
        <a class="btn-create-post" href="employer-proyek-aktif.php" style="text-decoration:none;display:inline-flex;">Lihat Proyek Aktif</a>
      </div>
      <?php endif; ?>
      <?php foreach ($historyProjects as $item): ?>
      <div class="white-card history-card" data-status="<?php echo $item['statusCode']; ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;padding-bottom:12px;border-bottom:1px solid var(--border-light);">
          <div>
            <div style="display:flex;align-items:center;gap:10px;">
              <h3 style="font-size:1.05rem;font-weight:700;margin:0;"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <?php if ($item['statusCode'] === 'completed'): ?>
                <span style="display:inline-block;padding:3px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#d1fae5;color:#047857;">Selesai</span>
              <?php else: ?>
                <span style="display:inline-block;padding:3px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#fee2e2;color:#b91c1c;">Tidak Selesai</span>
              <?php endif; ?>
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;">
              Periode: <strong><?php echo htmlspecialchars($item['startDate'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($item['endDate'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:0.74rem;color:var(--text-muted);">Gaji</div>
            <div style="font-size:1.1rem;font-weight:800;color:var(--primary-blue);"><?php echo htmlspecialchars($item['budget'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:14px;align-items:center;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:42px;height:42px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.05rem;flex-shrink:0;"><?php echo htmlspecialchars(strtoupper(substr((string)$item['worker'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
            <div>
              <div style="font-size:0.9rem;font-weight:700;">
                <a href="worker-profile.php?id=<?php echo urlencode($item['workerId']); ?>" style="color:inherit;text-decoration:none;"><?php echo htmlspecialchars($item['worker'], ENT_QUOTES, 'UTF-8'); ?></a>
              </div>
              <div style="font-size:0.76rem;color:var(--text-muted);"><?php echo htmlspecialchars($item['workerRole'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>

          <div>
            <div style="font-size:0.78rem;color:var(--text-muted);font-weight:600;">Ringkasan Pekerjaan</div>
            <div style="font-size:0.84rem;color:var(--text-dark);margin-top:2px;"><?php echo htmlspecialchars($item['summary'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        </div>

        <?php if ($item['statusCode'] === 'completed' && $item['ratingGiven'] !== null): ?>
          <div style="margin-top:12px;padding:12px 16px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;font-size:0.84rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
              <div style="display:flex;align-items:center;gap:8px;">
                <span style="color:#f59e0b;font-weight:800;font-size:1rem;"><?php echo gig_stars($item['ratingGiven']); ?></span>
                <strong style="color:#065f46;">Penilaian Diberikan: <?php echo (int)$item['ratingGiven']; ?> / 5</strong>
              </div>
              <a href="worker-profile.php?id=<?php echo urlencode($item['workerId']); ?>" style="font-size:0.78rem;color:var(--primary-blue);text-decoration:none;font-weight:700;">
                Lihat di Profil <?php echo htmlspecialchars($item['worker'], ENT_QUOTES, 'UTF-8'); ?> →
              </a>
            </div>
            <?php if (!empty($item['reviewGiven'])): ?>
              <p style="margin:6px 0 0 0;color:#1e293b;font-style:italic;">"<?php echo htmlspecialchars($item['reviewGiven'], ENT_QUOTES, 'UTF-8'); ?>"</p>
            <?php endif; ?>
          </div>
        <?php elseif ($item['statusCode'] === 'cancelled'): ?>
          <div style="margin-top:12px;padding:10px 14px;background:#fff1f2;border-radius:8px;border:1px solid #fecdd3;font-size:0.8rem;color:#9f1239;">
            Status Pembatalan: Kontrak dihentikan dengan pengembalian dana penuh / kesepakatan pembatalan mutual.
          </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <script>
      function filterHistory(status, btn) {
        document.querySelectorAll('.toolbar-filter .filter-btn-pill').forEach(function(b) { b.classList.remove('active'); });
        if (btn) btn.classList.add('active');
        document.querySelectorAll('#history-list .history-card').forEach(function(card) {
          const s = card.getAttribute('data-status');
          card.style.display = (status === 'all' || s === status) ? 'block' : 'none';
        });
      }
      function searchHistory(query) {
        const q = (query || '').toLowerCase();
        document.querySelectorAll('#history-list .history-card').forEach(function(card) {
          card.style.display = card.innerText.toLowerCase().includes(q) ? 'block' : 'none';
        });
      }
    </script>

<?php require __DIR__ . '/includes/employer-layout-end.php'; ?>

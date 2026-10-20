<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/worker-auth.php';
require_once __DIR__ . '/includes/project-applications.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/vacancy-store.php';

/**
 * Collect worker applications using all known identity keys
 * (username, profile id, SIAPKerja email, profile email, display name),
 * so historical records remain visible even if IDs differ.
 */
$workerProfile = gig_find_worker($username) ?? gig_find_worker('tessa');
$identityKeys = array_values(array_unique(array_filter(array_map(
    static fn($v) => strtolower(trim((string)$v)),
    [
        $username,
        $_SESSION['username'] ?? '',
        $_SESSION['siapkerja_email'] ?? '',
        $_SESSION['siapkerja_name'] ?? '',
        $workerProfile['id'] ?? '',
        $workerProfile['name'] ?? '',
        $workerProfile['contact']['email'] ?? '',
    ]
), static fn($v) => $v !== '')));

$applications = [];
foreach (gig_get_all_applications() as $app) {
    $wid = strtolower(trim((string)($app['worker_id'] ?? '')));
    $wname = strtolower(trim((string)($app['worker_name'] ?? '')));
    if (in_array($wid, $identityKeys, true) || in_array($wname, $identityKeys, true)) {
        $applications[] = $app;
    }
}
usort($applications, static function (array $a, array $b): int {
    return strtotime((string)($b['updated_at'] ?? '')) <=> strtotime((string)($a['updated_at'] ?? ''));
});

$statusMeta = [
    'applied' => ['label' => 'Lamaran Masuk', 'group' => 'incoming', 'chipBg' => '#fff7ed', 'chipColor' => '#c2410c', 'border' => '#ffedd5'],
    'reviewing' => ['label' => 'Lamaran Masuk', 'group' => 'incoming', 'chipBg' => '#fff7ed', 'chipColor' => '#c2410c', 'border' => '#ffedd5'],
    'interview' => ['label' => 'Lamaran Masuk', 'group' => 'incoming', 'chipBg' => '#fff7ed', 'chipColor' => '#c2410c', 'border' => '#ffedd5'],
    'confirmed_by_worker' => ['label' => 'Diterima', 'group' => 'accepted', 'chipBg' => '#ecfdf5', 'chipColor' => '#047857', 'border' => '#d1fae5'],
    'accepted_by_employer' => ['label' => 'Diterima', 'group' => 'accepted', 'chipBg' => '#ecfdf5', 'chipColor' => '#047857', 'border' => '#d1fae5'],
    'rejected_by_employer' => ['label' => 'Ditolak', 'group' => 'rejected', 'chipBg' => '#fef2f2', 'chipColor' => '#b91c1c', 'border' => '#fee2e2'],
    'declined_by_worker' => ['label' => 'Ditolak', 'group' => 'rejected', 'chipBg' => '#fef2f2', 'chipColor' => '#b91c1c', 'border' => '#fee2e2'],
];

$counts = ['all' => count($applications), 'incoming' => 0, 'accepted' => 0, 'rejected' => 0];
foreach ($applications as $row) {
    $st = (string)($row['status'] ?? 'applied');
    $meta = $statusMeta[$st] ?? $statusMeta['applied'];
    $grp = (string)$meta['group'];
    if (isset($counts[$grp])) {
        $counts[$grp]++;
    }
}

$pageTitle = 'Lamaran Saya';
$pageKey = 'lamaran';
$breadcrumbCurrent = 'Lamaran Saya';
require __DIR__ . '/includes/worker-layout-start.php';
?>

<div class="page-toolbar">
  <div>
    <h1 style="font-size:1.7rem;font-weight:800;color:var(--text-main);margin:0;letter-spacing:-0.03em;">Lamaran Saya</h1>
    <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">Pantau status seluruh lamaran proyek Anda secara real-time.</p>
  </div>
</div>

<div class="toolbar-filter" style="margin-bottom: 20px; flex-wrap: wrap;">
  <button class="filter-btn-pill active" type="button" onclick="filterLamaran('all', this)">Semua (<?php echo (int)$counts['all']; ?>)</button>
  <button class="filter-btn-pill" type="button" onclick="filterLamaran('incoming', this)">Lamaran Masuk (<?php echo (int)$counts['incoming']; ?>)</button>
  <button class="filter-btn-pill" type="button" onclick="filterLamaran('accepted', this)">Diterima (<?php echo (int)$counts['accepted']; ?>)</button>
  <button class="filter-btn-pill" type="button" onclick="filterLamaran('rejected', this)">Ditolak (<?php echo (int)$counts['rejected']; ?>)</button>
  
  <div class="search-input-box" style="margin-left: auto;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="lamaranSearchInput" placeholder="Cari judul proyek atau pemberi kerja..." onkeyup="searchLamaran(this.value)" />
  </div>
</div>

<?php if ($applications === []): ?>
  <div class="white-card" style="text-align:center;padding:48px 24px;">
    <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:6px;">Belum ada lamaran</h3>
    <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:16px;">Mulai lamar proyek dari halaman Cari Proyek.</p>
    <a class="btn-primary-add" href="worker-bursa.php" style="display:inline-flex;text-decoration:none;">Cari Proyek</a>
  </div>
<?php else: ?>
  <div style="display:flex;flex-direction:column;gap:16px;" id="lamaranList">
    <?php foreach ($applications as $app): ?>
      <?php
        $status = (string)($app['status'] ?? 'applied');
        $meta = $statusMeta[$status] ?? $statusMeta['applied'];
        $group = (string)$meta['group'];
        $vacancy = gig_find_vacancy((string)($app['vacancy_id'] ?? ''));
        $title = (string)($vacancy['title'] ?? ('Proyek #' . (string)($app['vacancy_id'] ?? '-')));
        $company = (string)($vacancy['employer'] ?? $app['employer_username'] ?? 'Pemberi Kerja');
        $rawBid = (string)($app['bid_amount'] ?? ($vacancy['budget'] ?? ''));
        $cleanBid = gig_vacancy_budget_range($rawBid);
        $detailUrl = 'worker-project-detail.php?id=' . urlencode((string)($app['vacancy_id'] ?? ''));
        $searchText = strtolower($title . ' ' . $company);
      ?>
      <div class="white-card lamaran-card-item" data-group="<?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;padding-bottom:14px;border-bottom:1px solid var(--border-light);">
          <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <h3 style="font-size:1.1rem;font-weight:800;color:var(--text-main);margin:0;"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
              <span style="display:inline-block;padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:800;background:<?php echo htmlspecialchars((string)$meta['chipBg'], ENT_QUOTES, 'UTF-8'); ?>;color:<?php echo htmlspecialchars((string)$meta['chipColor'], ENT_QUOTES, 'UTF-8'); ?>;border:1px solid <?php echo htmlspecialchars((string)$meta['border'], ENT_QUOTES, 'UTF-8'); ?>;">
                <?php echo htmlspecialchars((string)$meta['label'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
            <div style="font-size:0.84rem;color:var(--text-muted);font-weight:600;margin-top:4px;">
              🏢 <?php echo htmlspecialchars($company, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>
          <div>
            <a href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;border-radius:8px;padding:7px 16px;font-weight:700;font-size:0.82rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s ease;" onmouseover="this.style.background='#0284c7';this.style.color='#fff';this.style.borderColor='#0284c7';" onmouseout="this.style.background='#f1f5f9';this.style.color='#334155';this.style.borderColor='#cbd5e1';">
              Lihat Detail Proyek
            </a>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-top:14px;">
          <div>
            <div style="font-size:0.74rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.4px;">Tanggal Lamar</div>
            <div style="font-size:0.9rem;font-weight:700;color:var(--text-main);margin-top:2px;"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime((string)($app['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <div>
            <div style="font-size:0.74rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.4px;">Terakhir Update</div>
            <div style="font-size:0.9rem;font-weight:700;color:var(--text-main);margin-top:2px;"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime((string)($app['updated_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <div>
            <div style="font-size:0.74rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.4px;">Gaji</div>
            <div style="font-size:0.9rem;font-weight:800;color:#2563eb;margin-top:2px;"><?php echo htmlspecialchars($cleanBid, ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        </div>

        <?php if ($status === 'declined_by_worker' && !empty($app['rejection_reason'])): ?>
          <div style="margin-top:12px;padding:8px 12px;background:#fef2f2;border-radius:8px;border:1px solid #fee2e2;font-size:0.8rem;color:#991b1b;">
            <strong>Alasan Penolakan:</strong> &ldquo;<?php echo htmlspecialchars((string)$app['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?>&rdquo;
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
let currentGroup = 'all';

function filterLamaran(group, btn) {
  currentGroup = group;
  document.querySelectorAll('.filter-btn-pill').forEach(function(el) { el.classList.remove('active'); });
  if (btn) btn.classList.add('active');
  applyLamaranFilters();
}

function searchLamaran(val) {
  applyLamaranFilters();
}

function applyLamaranFilters() {
  const query = (document.getElementById('lamaranSearchInput')?.value || '').toLowerCase().trim();
  document.querySelectorAll('#lamaranList .lamaran-card-item').forEach(function(card) {
    const g = card.getAttribute('data-group') || '';
    const s = card.getAttribute('data-search') || '';
    const matchesGroup = (currentGroup === 'all' || g === currentGroup);
    const matchesSearch = (!query || s.includes(query));
    card.style.display = (matchesGroup && matchesSearch) ? '' : 'none';
  });
}
</script>

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>

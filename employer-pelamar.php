<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-applications.php';

$flashMsg = '';
$flashErr = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_action'])) {
    $appId = trim((string)($_POST['app_id'] ?? ''));
    $act   = trim((string)($_POST['app_action'] ?? ''));
    if ($appId !== '' && in_array($act, ['accept', 'reject'], true)) {
        $res = gig_employer_respond_application($appId, $act, $username);
        if ($res['ok']) {
            $flashMsg = ($act === 'accept')
                ? 'Lamaran diterima. Gig Worker resmi direkrut dan proyek kini aktif.'
                : 'Lamaran kandidat telah ditolak.';
            $flashErr = false;
        } else {
            $flashMsg = (string)($res['error'] ?? 'Gagal memproses aksi kandidat.');
            $flashErr = true;
        }
    }
}

$allApps = gig_get_all_applications();
$appMapByWorker = [];
foreach ($allApps as $ap) {
    $wKey = strtolower(trim((string)$ap['worker_id']));
    $appMapByWorker[$wKey] = $ap;
}

// Candidates list: filter out candidates who are already accepted/hired
$rawProfiles = gig_worker_profiles();
$workerProfiles = array_filter($rawProfiles, function($applicant) use ($appMapByWorker) {
    $wKey = strtolower(trim((string)$applicant['id']));
    $appData = $appMapByWorker[$wKey] ?? null;
    $status = $appData['status'] ?? 'applied';
    return !gig_is_hired_status($status);
});

$uiUxCount = 0;
$backendCount = 0;
$marketingCount = 0;
foreach ($workerProfiles as $w) {
    $cat = strtolower((string)($w['category'] ?? ''));
    if ($cat === 'ui-ux') {
        $uiUxCount++;
    } elseif ($cat === 'backend') {
        $backendCount++;
    } elseif ($cat === 'marketing') {
        $marketingCount++;
    }
}

$searchQ = trim((string)($_GET['q'] ?? ''));
$pageTitle = 'Kandidat Proyek';
$pageKey = 'pelamar';
$breadcrumbCurrent = 'Kandidat';
require __DIR__ . '/includes/employer-layout-start.php';
?>

    <div class="page-toolbar">
      <div>
        <h1>Kandidat Proyek</h1>
        <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">Kelola kandidat yang melamar proyek Anda. Menerima lamaran langsung merekrut Gig Worker dan mengaktifkan proyek.</p>
      </div>
      <div style="font-size:0.82rem;color:var(--text-muted);">
        Menampilkan <strong id="applicants-visible-count"><?php echo count($workerProfiles); ?></strong> kandidat
      </div>
    </div>

    <?php if ($flashMsg !== ''): ?>
      <div style="background:<?php echo $flashErr ? '#fef2f2' : '#f0fdf4'; ?>;border:1px solid <?php echo $flashErr ? '#fecaca' : '#bbf7d0'; ?>;color:<?php echo $flashErr ? '#991b1b' : '#166534'; ?>;padding:12px 16px;border-radius:10px;font-size:0.86rem;margin-bottom:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
        <span><?php echo $flashErr ? '!' : '✓'; ?></span> <?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <div class="privacy-lock-note">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Klik <strong>Terima</strong> untuk merekrut Gig Worker. Kontak resmi langsung terbuka dan proyek masuk ke Proyek Aktif.
    </div>

    <div class="toolbar-filter">
      <button class="filter-btn-pill active" type="button" onclick="filterApplicants('all', this)">Semua Kandidat (<?php echo count($workerProfiles); ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterApplicants('ui-ux', this)">UI/UX (<?php echo $uiUxCount; ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterApplicants('backend', this)">Backend &amp; API (<?php echo $backendCount; ?>)</button>
      <button class="filter-btn-pill" type="button" onclick="filterApplicants('marketing', this)">Pemasaran (<?php echo $marketingCount; ?>)</button>
      <div class="search-input-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="search-applicant-input" placeholder="Cari nama atau keterampilan..." value="<?php echo htmlspecialchars($searchQ, ENT_QUOTES, 'UTF-8'); ?>" onkeyup="searchApplicants(this.value)" />
      </div>
    </div>

    <?php if (count($workerProfiles) === 0): ?>
      <div class="white-card" style="text-align:center;padding:48px 24px;border-radius:14px;background:#ffffff;border:1px solid #e2e8f0;margin-top:16px;">
        <div style="font-size:2.5rem;margin-bottom:12px;">🎉</div>
        <h3 style="font-size:1.1rem;font-weight:800;color:#0f172a;margin-bottom:6px;">Tidak ada kandidat pending</h3>
        <p style="font-size:0.88rem;color:#64748b;margin-bottom:16px;">Semua kandidat yang telah direkrut kini berada di Proyek Aktif.</p>
        <a class="btn-create-post" href="employer-proyek-aktif.php" style="text-decoration:none;display:inline-flex;padding:8px 18px;font-size:0.85rem;background:#2563eb;">Buka Proyek Aktif &rarr;</a>
      </div>
    <?php else: ?>
      <div class="applicants-grid">
        <?php foreach ($workerProfiles as $applicant): 
          $wKey = strtolower(trim((string)$applicant['id']));
          $appData = $appMapByWorker[$wKey] ?? null;
          $status = $appData['status'] ?? 'applied';
          $appId = $appData['id'] ?? ('APP-' . $applicant['id']);
          $searchMeta = gig_worker_search_metadata($applicant);
        ?>
        <article class="applicant-card" 
                 data-category="<?php echo htmlspecialchars($applicant['category'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-search-corpus="<?php echo htmlspecialchars($searchMeta['full_text'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="applicant-left-info">
            <a class="applicant-avatar" href="worker-profile.php?id=<?php echo urlencode($applicant['id']); ?>" style="background:<?php echo htmlspecialchars($applicant['color'], ENT_QUOTES, 'UTF-8'); ?>;display:flex;align-items:center;justify-content:center;color:#ffffff;font-weight:800;font-size:1.2rem;text-decoration:none;">
              <?php echo htmlspecialchars(strtoupper(substr((string)$applicant['name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <div class="applicant-details">
              <div class="applicant-name-row">
                <a class="applicant-name" href="worker-profile.php?id=<?php echo urlencode($applicant['id']); ?>"><?php echo htmlspecialchars($applicant['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                <span class="fl-rating-badge">★ <?php echo number_format((float)$applicant['rating'], 1); ?> (<?php echo (int)$applicant['reviews_count']; ?> ulasan)</span>
              </div>
              <div class="applicant-applied-role">Proyek yang dilamar: <strong><?php echo htmlspecialchars($applicant['applied_project'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
              <div class="project-skill-tags">
                <?php foreach (array_slice(gig_normalize_skills($applicant['skills'] ?? []), 0, 3) as $skillTag): ?>
                  <?php
                    $sNm = gig_skill_name($skillTag);
                    $sLvl = gig_skill_level($skillTag);
                    $sSt = gig_skill_level_badge_style($sLvl);
                  ?>
                  <span class="skill-tag-item" style="display:inline-flex;align-items:center;gap:4px;">
                    <span><?php echo htmlspecialchars($sNm, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span style="font-size:0.65rem;font-weight:700;color:<?php echo $sSt['color']; ?>;background:<?php echo $sSt['bg']; ?>;padding:1px 4px;border-radius:4px;border:1px solid <?php echo $sSt['border']; ?>;"><?php echo htmlspecialchars($sLvl, ENT_QUOTES, 'UTF-8'); ?></span>
                  </span>
                <?php endforeach; ?>
              </div>
              <div class="applicant-lock-hint">
                <span>Kontak dikunci sampai Anda menerima lamaran kandidat</span>
              </div>
            </div>
          </div>
          <div class="applicant-center-meta">
            <span style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Penawaran / Gaji</span>
            <span class="bid-amount"><?php echo htmlspecialchars($applicant['bid'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="bid-time"><?php echo htmlspecialchars($applicant['eta'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="applicant-right-actions" style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
            <a class="btn-outline-blue" href="worker-profile.php?id=<?php echo urlencode($applicant['id']); ?>" style="padding:4px 12px;font-size:0.8rem;">Lihat Profil</a>
            
            <?php if ($status === 'declined_by_worker'): ?>
              <span style="padding:6px 14px;border-radius:9999px;background:#fef2f2;color:#b91c1c;font-weight:700;font-size:0.8rem;border:1px solid #fecaca;">✕ Ditolak oleh Worker</span>
              <?php if (!empty($app['rejection_reason'])): ?>
                <div style="font-size:0.75rem;color:#7f1d1d;max-width:240px;text-align:right;margin-top:2px;font-style:italic;">
                  Alasan: &ldquo;<?php echo htmlspecialchars((string)$app['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?>&rdquo;
                </div>
              <?php endif; ?>
            <?php elseif ($status === 'rejected_by_employer'): ?>
              <span style="padding:6px 14px;border-radius:9999px;background:#fef2f2;color:#b91c1c;font-weight:700;font-size:0.8rem;">✕ Lamaran Ditolak</span>
            <?php else: ?>
              <form method="post" action="" style="display:flex;gap:6px;flex-wrap:wrap;">
                <input type="hidden" name="app_id" value="<?php echo htmlspecialchars($appId, ENT_QUOTES, 'UTF-8'); ?>" />
                <button type="submit" name="app_action" value="accept" class="btn-hire" style="background:#16a34a;border-color:#15803d;padding:6px 12px;font-size:0.8rem;">
                  ✓ Terima
                </button>
                <button type="submit" name="app_action" value="reject" class="btn-action-sm" style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;padding:6px 10px;font-size:0.8rem;" onclick="return confirm('Tolak lamaran kandidat ini?')">
                  ✕ Tolak
                </button>
              </form>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($searchQ !== ''): ?>
    <script>document.addEventListener('DOMContentLoaded', function () { searchApplicants(<?php echo json_encode($searchQ); ?>); });</script>
    <?php endif; ?>

<?php require __DIR__ . '/includes/employer-layout-end.php'; ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-applications.php';

function gig_update_application_pipeline_status(string $appId, string $status): array
{
    $allowed = ['applied', 'reviewing', 'interview'];
    if (!in_array($status, $allowed, true)) {
        return ['ok' => false, 'error' => 'Status tidak valid.'];
    }
    $app = gig_get_application_by_id($appId);
    if (!$app) {
        return ['ok' => false, 'error' => 'Lamaran tidak ditemukan.'];
    }

    $now = date('Y-m-d H:i:s');
    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $stmt = $pdo->prepare("UPDATE `project_applications` SET `status` = :st, `updated_at` = NOW() WHERE `id` = :id");
            $stmt->execute([':st' => $status, ':id' => $appId]);
        } catch (Throwable $ignored) {}
    }

    gig_apps_session_start();
    foreach ($_SESSION['gig_applications'] as $key => $row) {
        if ((string)($row['id'] ?? '') === $appId) {
            $_SESSION['gig_applications'][$key]['status'] = $status;
            $_SESSION['gig_applications'][$key]['updated_at'] = $now;
            break;
        }
    }

    return ['ok' => true];
}

$jobId = trim((string)($_GET['id'] ?? 'GIG-2026-09-001'));
$job = gig_find_vacancy($jobId);
if ($job === null) {
    header('Location: employer-lowongan.php');
    exit;
}

$flash = $_SESSION['employer_lowongan_flash'] ?? null;
unset($_SESSION['employer_lowongan_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application_status'], $_POST['app_id'], $_POST['new_status'])) {
    $appId = trim((string)$_POST['app_id']);
    $newStatus = trim((string)$_POST['new_status']);
    $result = ['ok' => false, 'error' => 'Data tidak lengkap.'];

    if ($appId !== '' && in_array($newStatus, ['applied', 'reviewing', 'interview', 'accepted', 'rejected'], true)) {
        if ($newStatus === 'accepted' || $newStatus === 'rejected') {
            $result = gig_employer_respond_application(
                $appId,
                $newStatus === 'accepted' ? 'accept' : 'reject',
                (string)($_SESSION['username'] ?? '')
            );
        } else {
            $result = gig_update_application_pipeline_status($appId, $newStatus);
        }
    }

    $_SESSION['employer_lowongan_flash'] = [
        'ok' => !empty($result['ok']),
        'message' => !empty($result['ok'])
            ? 'Status lamaran berhasil diperbarui.'
            : ((string)($result['error'] ?? 'Gagal memperbarui status lamaran.')),
    ];
    header('Location: employer-detail-lowongan.php?id=' . urlencode($jobId));
    exit;
}

$workers = gig_worker_profiles();
$workerById = [];
$workerByEmail = [];
$workerByName = [];
foreach ($workers as $w) {
    $idKey = strtolower(trim((string)($w['id'] ?? '')));
    if ($idKey !== '') {
        $workerById[$idKey] = $w;
    }
    $emailKey = strtolower(trim((string)($w['contact']['email'] ?? '')));
    if ($emailKey !== '') {
        $workerByEmail[$emailKey] = $w;
    }
    $nameKey = strtolower(trim((string)($w['name'] ?? '')));
    if ($nameKey !== '') {
        $workerByName[$nameKey] = $w;
    }
}

$jobApplications = [];
foreach (gig_get_all_applications() as $app) {
    if (strcasecmp((string)($app['vacancy_id'] ?? ''), (string)$job['id']) === 0) {
        $jobApplications[] = $app;
    }
}

usort($jobApplications, static function(array $a, array $b): int {
    return strtotime((string)($b['updated_at'] ?? '')) <=> strtotime((string)($a['updated_at'] ?? ''));
});

$statusToLane = [
    'applied' => 'incoming',
    'reviewing' => 'reviewed',
    'interview' => 'interview',
    'confirmed_by_worker' => 'accepted',
    'accepted_by_employer' => 'accepted',
    'rejected_by_employer' => 'rejected',
    'declined_by_worker' => 'rejected',
];

$laneMeta = [
    'incoming' => ['label' => 'Lamaran Masuk', 'dot' => '#f59e0b'],
    'reviewed' => ['label' => 'Sedang Dipelajari', 'dot' => '#fb923c'],
    'interview' => ['label' => 'Wawancara', 'dot' => '#3b82f6'],
    'accepted' => ['label' => 'Diterima', 'dot' => '#22c55e'],
    'rejected' => ['label' => 'Ditolak', 'dot' => '#ef4444'],
];

$lanes = [
    'incoming' => [],
    'reviewed' => [],
    'interview' => [],
    'accepted' => [],
    'rejected' => [],
];

foreach ($jobApplications as $app) {
    $status = (string)($app['status'] ?? 'applied');
    $lane = $statusToLane[$status] ?? 'incoming';
    $workerIdRaw = trim((string)($app['worker_id'] ?? ''));
    $workerName = strtolower(trim((string)($app['worker_name'] ?? '')));
    
    $profile = gig_find_worker($workerIdRaw)
        ?? $workerById[strtolower($workerIdRaw)]
        ?? $workerByEmail[strtolower($workerIdRaw)]
        ?? $workerByName[$workerName]
        ?? null;

    if ($profile === null) {
        $profile = [
            'id' => $workerIdRaw !== '' ? $workerIdRaw : 'tessa',
            'name' => (string)($app['worker_name'] ?? 'Gig Worker'),
            'title' => 'Gig Worker',
            'location' => 'Lokasi belum diisi',
            'rating' => 5.0,
            'reviews_count' => 0,
            'completed_projects' => 0,
            'skills' => [],
            'proposal' => 'Gig Worker belum menambahkan deskripsi diri.',
            'contact' => ['wa' => '', 'email' => ''],
            'experience' => [],
            'portfolio' => [],
            'reviews' => [],
            'category' => 'general',
        ];
    }

    $statusUi = match ($status) {
        'reviewing' => 'reviewing',
        'interview' => 'interview',
        'rejected_by_employer', 'declined_by_worker' => 'rejected',
        'confirmed_by_worker', 'accepted_by_employer' => 'accepted',
        default => 'applied',
    };

    $statusText = match ($statusUi) {
        'reviewing' => 'Sedang Dipelajari',
        'interview' => 'Wawancara',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        default => 'Lamaran Masuk',
    };
    $appliedAt = trim((string)($app['created_at'] ?? ''));
    $appliedLabel = $appliedAt !== '' ? date('d M Y', strtotime($appliedAt)) : '-';
    $contactUnlocked = gig_is_hired_status($status);

    $lanes[$lane][] = [
        'id' => (string)($app['id'] ?? ''),
        'worker_id' => (string)($app['worker_id'] ?? ''),
        'name' => (string)($profile['name'] ?? $app['worker_name'] ?? 'Gig Worker'),
        'title' => (string)($profile['title'] ?? 'Gig Worker'),
        'location' => (string)($profile['location'] ?? 'Lokasi belum diisi'),
        'rating' => (float)($profile['rating'] ?? 0),
        'status' => $status,
        'status_ui' => $statusUi,
        'status_text' => $statusText,
        'applied_label' => $appliedLabel,
        'contact_unlocked' => $contactUnlocked ? '1' : '0',
        'profile' => $profile,
    ];
}

$rawJobStatus = (string)($job['status'] ?? 'draft');
$isTayang = ($rawJobStatus === 'active');

$lockedMessageMap = [
    'draft' => 'Proses seleksi belum dapat dilakukan karena lowongan belum berstatus Tayang. Lowongan masih dalam tahap penyusunan dan belum diajukan untuk diverifikasi.',
    'review' => 'Proses seleksi belum dapat dilakukan karena lowongan belum berstatus Tayang. Lowongan saat ini sedang dalam proses verifikasi oleh Admin KarirHub.',
    'revision' => 'Proses seleksi belum dapat dilakukan karena lowongan belum berstatus Tayang. Lowongan memerlukan perbaikan sesuai catatan revisi dari Admin.',
    'rejected' => 'Proses seleksi tidak dapat dilakukan karena lowongan ditolak oleh Admin KarirHub.',
];
$lockedMessage = $lockedMessageMap[$rawJobStatus] ?? 'Proses seleksi belum dapat dilakukan karena lowongan belum berstatus Tayang. Lowongan masih dalam tahap penyusunan dan belum diajukan untuk diverifikasi.';

$statusLabel = (string)($job['statusLabel'] ?? 'Draft');
$pageTitle = 'Detail Lowongan · ' . $job['title'];
$pageKey = 'lowongan';
$breadcrumbCurrent = 'Detail Lowongan';
require __DIR__ . '/includes/employer-layout-start.php';
?>

<style>
  .jobd-shell { display:flex; flex-direction:column; gap:16px; }
  .jobd-head { background:#fff; border:1px solid var(--border-subtle); border-radius:14px; padding:18px 20px; box-shadow:var(--shadow-sm); }
  .jobd-head-top { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap; }
  .jobd-title { font-size:2rem; font-weight:800; color:#0f172a; line-height:1.1; margin:0 0 8px 0; }
  .jobd-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:0.82rem; color:#64748b; }
  .jobd-pill { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:9999px; padding:4px 10px; font-weight:700; color:#334155; }
  .jobd-kpi { display:grid; grid-template-columns:repeat(3,minmax(140px,1fr)); gap:14px; margin-top:14px; border-top:1px solid #eef2ff; padding-top:14px; }
  .jobd-kpi .lbl { font-size:0.76rem; color:#64748b; margin-bottom:2px; display:block; }
  .jobd-kpi .val { font-size:0.9rem; font-weight:800; color:#0f172a; }
  .jobd-tabs { display:flex; gap:16px; border-bottom:1px solid #e2e8f0; margin-top:8px; }
  .jobd-tab { border:none; background:none; padding:10px 2px; font-size:0.9rem; color:#64748b; font-weight:700; border-bottom:2px solid transparent; cursor:pointer; }
  .jobd-tab.active { color:#0284c7; border-bottom-color:#0ea5e9; }
  .jobd-tools { display:flex; justify-content:space-between; gap:10px; margin-top:14px; flex-wrap:wrap; }
  .jobd-search { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:8px 10px; min-width:260px; }
  .jobd-search input { border:none; outline:none; width:100%; font-size:0.86rem; }
  .jobd-board { display:grid; grid-template-columns:repeat(5,minmax(220px,1fr)); gap:12px; overflow-x:auto; padding-bottom:4px; }
  .jobd-col { min-width:220px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; display:flex; flex-direction:column; max-height:540px; }
  .jobd-col-head { padding:10px 12px; border-bottom:1px solid #eef2ff; display:flex; justify-content:space-between; align-items:center; font-size:0.84rem; font-weight:800; color:#1e293b; }
  .jobd-dot { width:7px; height:7px; border-radius:9999px; display:inline-block; margin-right:7px; vertical-align:middle; }
  .jobd-col-body { padding:10px; overflow:auto; display:flex; flex-direction:column; gap:8px; }
  .jobd-card { border:1px solid #e2e8f0; border-radius:10px; padding:10px; background:#f8fafc; cursor:pointer; transition:all .15s ease; }
  .jobd-card:hover { border-color:#93c5fd; box-shadow:0 4px 14px rgba(37,99,235,.12); transform:translateY(-1px); }
  .jobd-card-name { font-size:0.84rem; font-weight:800; color:#0f172a; margin-bottom:2px; }
  .jobd-card-sub { font-size:0.74rem; color:#64748b; margin-bottom:6px; }
  .jobd-card-meta { font-size:0.72rem; color:#475569; display:flex; gap:8px; flex-wrap:wrap; margin-bottom:6px; }
  .jobd-empty { font-size:0.8rem; color:#94a3b8; text-align:center; padding:24px 10px; }
  .jobd-info { display:none; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px; }
  .jobd-info.active { display:block; }
  .cand-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55); backdrop-filter:blur(2px); z-index:1300; opacity:0; pointer-events:none; transition:opacity .2s ease; }
  .cand-overlay.open { opacity:1; pointer-events:auto; }
  .cand-drawer { position:fixed; top:12px; right:12px; bottom:12px; width:min(520px,calc(100vw - 24px)); background:#fff; border:1px solid #dbe7ff; border-radius:16px; box-shadow:0 22px 50px rgba(15,23,42,.34); transform:translateX(110%); transition:transform .22s ease; z-index:1310; display:flex; flex-direction:column; overflow:hidden; }
  .cand-drawer.open { transform:translateX(0); }
  .cand-head { padding:14px 16px; border-bottom:1px solid #eef2ff; display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
  .cand-close { width:30px; height:30px; border-radius:9999px; border:1px solid #e2e8f0; background:#fff; color:#475569; cursor:pointer; font-size:1rem; }
  .cand-body { padding:14px 16px; overflow:auto; display:flex; flex-direction:column; gap:14px; }
  .cand-row { display:grid; grid-template-columns:160px 1fr; gap:10px; font-size:0.84rem; }
  .cand-label { color:#64748b; display:flex; align-items:center; gap:6px; }
  .cand-value { color:#0f172a; font-weight:600; }
  .cand-tabline { display:flex; gap:16px; border-bottom:1px solid #eef2ff; padding-bottom:8px; }
  .cand-tabline span { font-size:.86rem; font-weight:700; color:#64748b; }
  .cand-tabline span.active { color:#0284c7; border-bottom:2px solid #0ea5e9; padding-bottom:6px; }
  .cand-foot { margin-top:auto; border-top:1px solid #eef2ff; padding:12px 16px; background:#fff; }
  .cand-status-form { display:flex; align-items:center; gap:8px; }
  .cand-status-form select { flex:1; padding:8px 10px; border:1px solid #dbe3f0; border-radius:10px; font-size:.84rem; }
  .cand-status-form button { padding:8px 12px; border:none; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; cursor:pointer; font-size:.82rem; }
</style>

<div class="jobd-shell">
  <div class="jobd-head">
    <div class="jobd-head-top">
      <div>
        <a class="btn-action-sm" href="employer-lowongan.php" style="margin-bottom:10px;display:inline-flex;">← Kembali ke Lowongan</a>
        <h1 class="jobd-title"><?php echo htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="jobd-meta">
          <?php if (gig_is_remote_work($job)): ?>
            <span class="jobd-pill" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-weight:700;">🌐 Project Remote</span>
          <?php else: ?>
            <span class="jobd-pill" style="background:#f8fafc;color:#475569;border-color:#e2e8f0;font-weight:700;">🏢 On-site / Hybrid</span>
          <?php endif; ?>
          <span>•</span>
          <span>Dibuat <?php echo htmlspecialchars((string)$job['posted'], ENT_QUOTES, 'UTF-8'); ?></span>
          <span>•</span>
          <span>Status: <strong><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></strong></span>
        </div>
      </div>
    </div>
    <div class="jobd-kpi" style="grid-template-columns:repeat(4,minmax(130px,1fr));">
      <div><span class="lbl">Tayang</span><span class="val"><?php echo htmlspecialchars((string)$job['posted'], ENT_QUOTES, 'UTF-8'); ?></span></div>
      <div><span class="lbl">Kadaluarsa</span><span class="val"><?php echo htmlspecialchars((string)$job['deadline'], ENT_QUOTES, 'UTF-8'); ?></span></div>
      <div><span class="lbl">Lokasi</span><span class="val"><?php echo htmlspecialchars((string)$job['location'], ENT_QUOTES, 'UTF-8'); ?></span></div>
      <div><span class="lbl">Mode Pengerjaan</span><span class="val" style="color:<?php echo gig_is_remote_work($job) ? '#1d4ed8' : '#0f172a'; ?>;"><?php echo gig_is_remote_work($job) ? '🌐 Remote' : '🏢 On-site / Hybrid'; ?></span></div>
    </div>
  </div>

  <div class="jobd-tabs">
    <button class="jobd-tab active" type="button" data-tab="lamaran">Lamaran</button>
    <button class="jobd-tab" type="button" data-tab="detail">Detail Lowongan</button>
  </div>

  <section id="tab-lamaran" class="jobd-pane">
    <?php if (!empty($flash['message'])): ?>
      <div style="margin-bottom:8px;padding:10px 12px;border-radius:10px;border:1px solid <?php echo !empty($flash['ok']) ? '#bbf7d0' : '#fecaca'; ?>;background:<?php echo !empty($flash['ok']) ? '#f0fdf4' : '#fef2f2'; ?>;color:<?php echo !empty($flash['ok']) ? '#166534' : '#991b1b'; ?>;font-size:0.84rem;font-weight:700;">
        <?php echo htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <div style="position:relative; min-height:460px; border-radius:14px; overflow:hidden;">
      <?php if (!$isTayang): ?>
        <div style="position:absolute; inset:0; background:rgba(148, 163, 184, 0.45); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); z-index:20; display:flex; align-items:center; justify-content:center; padding:24px;">
          <div style="background:#ffffff; border-radius:18px; padding:38px 36px; max-width:480px; width:100%; text-align:center; box-shadow:0 20px 45px rgba(15,23,42,0.18);">
            <div style="font-size:2.5rem; color:#1e293b; margin-bottom:14px; display:flex; justify-content:center; align-items:center;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
            <h2 style="font-size:1.35rem; font-weight:800; color:#0f172a; margin:0 0 10px 0; letter-spacing:-0.01em;">Proses seleksi terkunci</h2>
            <p style="font-size:0.86rem; color:#64748b; line-height:1.55; margin:0;">
              <?php echo htmlspecialchars($lockedMessage, ENT_QUOTES, 'UTF-8'); ?>
            </p>
          </div>
        </div>
      <?php endif; ?>

      <div class="jobd-tools" style="<?php echo !$isTayang ? 'filter:blur(3px); opacity:0.45; pointer-events:none;' : ''; ?>">
        <div class="jobd-search">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input id="candidateSearchInput" type="text" placeholder="Cari pelamar..." <?php echo !$isTayang ? 'disabled' : ''; ?> />
        </div>
        <div style="display:flex;gap:8px;">
          <button class="filter-btn-pill" type="button" <?php echo !$isTayang ? 'disabled' : ''; ?>>Filter</button>
        </div>
      </div>

      <div class="jobd-board" id="kanbanBoard" style="margin-top:10px; <?php echo !$isTayang ? 'filter:blur(3px); opacity:0.45; pointer-events:none;' : ''; ?>">
        <?php foreach ($lanes as $key => $cards): ?>
          <div class="jobd-col" data-lane="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="jobd-col-head">
              <span><span class="jobd-dot" style="background:<?php echo htmlspecialchars($laneMeta[$key]['dot'], ENT_QUOTES, 'UTF-8'); ?>"></span><?php echo htmlspecialchars($laneMeta[$key]['label'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span><?php echo count($cards); ?></span>
            </div>
            <div class="jobd-col-body">
              <?php if (count($cards) === 0): ?>
                <div class="jobd-empty">Tidak ada data.</div>
              <?php endif; ?>
              <?php foreach ($cards as $c): 
                $candSearchCorpus = function_exists('gig_worker_search_metadata') 
                  ? gig_worker_search_metadata($c['profile'])['full_text'] 
                  : strtolower($c['name'] . ' ' . $c['title']);
              ?>
                <div
                  class="jobd-card candidate-card"
                  data-search="<?php echo htmlspecialchars($candSearchCorpus, ENT_QUOTES, 'UTF-8'); ?>"
                  data-app-id="<?php echo htmlspecialchars($c['id'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-worker-id="<?php echo htmlspecialchars($c['worker_id'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-name="<?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-title="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-status-text="<?php echo htmlspecialchars($c['status_text'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-status-ui="<?php echo htmlspecialchars($c['status_ui'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-applied="<?php echo htmlspecialchars($c['applied_label'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-contact-unlocked="<?php echo htmlspecialchars($c['contact_unlocked'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-profile-json="<?php echo htmlspecialchars(json_encode($c['profile'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
                >
                  <div class="jobd-card-name"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="jobd-card-sub"><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="jobd-card-meta">
                    <span>★ <?php echo number_format((float)$c['rating'], 1); ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="tab-detail" class="jobd-info">
    <h2 style="font-size:1rem;font-weight:800;color:#0f172a;margin:0 0 10px 0;">Detail Lowongan</h2>
    <p style="font-size:0.9rem;line-height:1.6;color:#334155;"><?php echo htmlspecialchars((string)$job['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
    <div class="skill-row" style="margin-top:12px;">
      <?php foreach (($job['skills'] ?? []) as $skill): ?>
        <span class="skill-tag"><?php echo htmlspecialchars((string)$skill, ENT_QUOTES, 'UTF-8'); ?></span>
      <?php endforeach; ?>
    </div>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(160px,1fr));gap:12px;margin-top:16px;">
      <div><span style="font-size:0.74rem;color:#64748b;display:block;">Gaji / Budget</span><strong><?php echo htmlspecialchars(gig_vacancy_budget_range((string)($job['budget'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></strong></div>
      <div><span style="font-size:0.74rem;color:#64748b;display:block;">Durasi Target</span><strong><?php echo htmlspecialchars((string)$job['duration'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
      <div><span style="font-size:0.74rem;color:#64748b;display:block;">Lokasi</span><strong><?php echo htmlspecialchars((string)$job['location'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
      <div><span style="font-size:0.74rem;color:#64748b;display:block;">Mode Pengerjaan</span><strong><?php echo gig_is_remote_work($job) ? '🌐 Remote (Kerja Jarak Jauh)' : '🏢 On-site / Hybrid'; ?></strong></div>
      <div><span style="font-size:0.74rem;color:#64748b;display:block;">Status Verifikasi</span><strong><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></strong></div>
      <div><span style="font-size:0.74rem;color:#64748b;display:block;">Tipe Pekerjaan</span><strong><?php echo htmlspecialchars((string)($job['workType'] ?? 'Full time / Milestone-based'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
    </div>
    <?php if (!empty($job['deliverables'])): ?>
    <div style="margin-top:16px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
      <span style="font-size:0.74rem;color:#64748b;font-weight:700;display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.04em;">Target / Deliverables Proyek</span>
      <div style="font-size:0.88rem;color:#1e293b;font-weight:600;line-height:1.5;"><?php echo nl2br(htmlspecialchars((string)$job['deliverables'], ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php endif; ?>
  </section>
</div>

<div id="candidateOverlay" class="cand-overlay" onclick="closeCandidateDrawer()"></div>
<aside id="candidateDrawer" class="cand-drawer" aria-hidden="true">
  <div class="cand-head">
    <div style="display:flex;gap:10px;align-items:center;">
      <div id="candAvatar" style="width:44px;height:44px;border-radius:9999px;background:#2563eb;color:#fff;font-weight:800;font-size:1.1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">G</div>
      <div>
        <div id="candName" style="font-size:1rem;font-weight:800;color:#0f172a;">Nama Gig Worker</div>
        <div style="font-size:0.78rem;color:#64748b;">Mendaftar: <span id="candApplied">-</span> · Status: <span id="candStatusText" style="font-weight:800;color:#ea580c;">Lamaran Masuk</span></div>
      </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <a id="candFullProfileBtn" href="#" target="_blank" style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:8px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;font-size:0.76rem;font-weight:700;text-decoration:none;">
        <span>Profil Lengkap</span> ↗
      </a>
      <button type="button" class="cand-close" onclick="closeCandidateDrawer()">✕</button>
    </div>
  </div>

  <div class="cand-body">
    <div class="cand-row"><div class="cand-label">📍 Lokasi</div><div id="candLocation" class="cand-value">-</div></div>
    <div class="cand-row"><div class="cand-label">🏷️ Bidang Keahlian</div><div id="candSkillsBadges" class="cand-value" style="display:flex;gap:6px;flex-wrap:wrap;">-</div></div>
    <div class="cand-row"><div class="cand-label">🧩 Bidang</div><div id="candBidang" class="cand-value">-</div></div>
    <div class="cand-row"><div class="cand-label">ℹ️ Tentang</div><div id="candAbout" class="cand-value" style="font-weight:500;line-height:1.45;">-</div></div>

    <div class="cand-tabline" style="margin-top:8px;">
      <span id="candTabProfileBtn" class="active" style="cursor:pointer;">Profil Overview</span>
      <span id="candTabExpBtn" style="cursor:pointer;">Pengalaman</span>
      <span id="candTabPortBtn" style="cursor:pointer;">Portofolio</span>
      <span id="candTabRevBtn" style="cursor:pointer;">Ulasan</span>
    </div>

    <!-- Panel 1: Profil Overview -->
    <div id="candProfilePanel">
      <div class="cand-row" style="margin-top:10px;">
        <div class="cand-label">👤 Informasi Profil</div>
        <div class="cand-value" style="font-weight:500;">
          <div style="margin-bottom:6px;color:#0f172a;"><strong id="candTitleInfo">Gig Worker</strong></div>
          <div style="margin-bottom:8px;color:#64748b;"><span id="candProjectsInfo">0 proyek selesai</span> · <span id="candReviewsInfo">0 ulasan</span></div>
          <div style="color:#64748b;margin-bottom:4px;">Kontak email kandidat: <strong id="candContactEmail">tidak tersedia</strong></div>
          <div style="color:#64748b;">WhatsApp kandidat: <strong id="candContactWa">tidak tersedia</strong></div>
        </div>
      </div>
    </div>

    <!-- Panel 2: Pengalaman -->
    <div id="candExpPanel" style="display:none;">
      <div id="candExperienceList" style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">
        <!-- Rendered by JS -->
      </div>
    </div>

    <!-- Panel 3: Portofolio -->
    <div id="candPortPanel" style="display:none;">
      <div id="candPortfolioList" style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">
        <!-- Rendered by JS -->
      </div>
    </div>

    <!-- Panel 4: Ulasan -->
    <div id="candRevPanel" style="display:none;">
      <div id="candReviewList" style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">
        <!-- Rendered by JS -->
      </div>
    </div>
  </div>

  <div class="cand-foot">
    <form method="post" action="" class="cand-status-form">
      <input type="hidden" name="app_id" id="candAppIdInput" value="">
      <select name="new_status" id="candStatusSelect" required>
        <option value="applied">Lamaran Masuk</option>
        <option value="reviewing">Sedang Dipelajari</option>
        <option value="interview">Wawancara</option>
        <option value="accepted">Diterima</option>
        <option value="rejected">Ditolak</option>
      </select>
      <button type="submit" name="update_application_status" value="1">Simpan</button>
    </form>
  </div>
</aside>

<script>
  (function() {
    const tabs = document.querySelectorAll('.jobd-tab');
    const lamaranPane = document.getElementById('tab-lamaran');
    const detailPane = document.getElementById('tab-detail');
    tabs.forEach(function(btn) {
      btn.addEventListener('click', function() {
        tabs.forEach(function(x) { x.classList.remove('active'); });
        btn.classList.add('active');
        const isLamaran = btn.getAttribute('data-tab') === 'lamaran';
        lamaranPane.style.display = isLamaran ? 'block' : 'none';
        detailPane.classList.toggle('active', !isLamaran);
      });
    });

    const searchInput = document.getElementById('candidateSearchInput');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const q = (searchInput.value || '').toLowerCase().trim();
        document.querySelectorAll('.candidate-card').forEach(function(card) {
          const hay = card.getAttribute('data-search') || '';
          card.style.display = (!q || hay.indexOf(q) !== -1) ? 'block' : 'none';
        });
      });
    }

    function escapeHtml(str) {
      return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    const overlay = document.getElementById('candidateOverlay');
    const drawer = document.getElementById('candidateDrawer');
    const appIdInput = document.getElementById('candAppIdInput');
    const statusSelect = document.getElementById('candStatusSelect');
    const fullProfileBtn = document.getElementById('candFullProfileBtn');

    const fields = {
      avatar: document.getElementById('candAvatar'),
      name: document.getElementById('candName'),
      applied: document.getElementById('candApplied'),
      statusText: document.getElementById('candStatusText'),
      location: document.getElementById('candLocation'),
      skillsBadges: document.getElementById('candSkillsBadges'),
      bidang: document.getElementById('candBidang'),
      about: document.getElementById('candAbout'),
      contactEmail: document.getElementById('candContactEmail'),
      contactWa: document.getElementById('candContactWa'),
      titleInfo: document.getElementById('candTitleInfo'),
      projectsInfo: document.getElementById('candProjectsInfo'),
      reviewsInfo: document.getElementById('candReviewsInfo'),
      experienceList: document.getElementById('candExperienceList'),
      portfolioList: document.getElementById('candPortfolioList'),
      reviewList: document.getElementById('candReviewList'),
    };

    const tabBtns = {
      profile: document.getElementById('candTabProfileBtn'),
      exp: document.getElementById('candTabExpBtn'),
      port: document.getElementById('candTabPortBtn'),
      rev: document.getElementById('candTabRevBtn'),
    };
    const panels = {
      profile: document.getElementById('candProfilePanel'),
      exp: document.getElementById('candExpPanel'),
      port: document.getElementById('candPortPanel'),
      rev: document.getElementById('candRevPanel'),
    };

    function setDrawerTab(activeTab) {
      Object.keys(tabBtns).forEach(function(key) {
        if (tabBtns[key]) tabBtns[key].classList.toggle('active', key === activeTab);
        if (panels[key]) panels[key].style.display = (key === activeTab) ? 'block' : 'none';
      });
    }

    const bidangMap = {
      'ui-ux': 'UI/UX & Desain',
      'backend': 'IT & Pemrograman',
      'marketing': 'Pemasaran & Konten',
      'general': 'Gig Worker Professional',
    };

    function openCandidateDrawer(card) {
      if (!card || !overlay || !drawer) return;
      
      let profile = {};
      try {
        profile = JSON.parse(card.getAttribute('data-profile-json') || '{}');
      } catch (e) {
        profile = {};
      }

      const name = profile.name || card.getAttribute('data-name') || 'Gig Worker';
      const workerId = profile.id || card.getAttribute('data-worker-id') || 'tessa';
      const statusText = card.getAttribute('data-status-text') || 'Lamaran Masuk';

      if (fields.avatar) fields.avatar.textContent = (name || 'G').trim().charAt(0).toUpperCase();
      if (fields.name) fields.name.textContent = name;
      if (fields.applied) fields.applied.textContent = card.getAttribute('data-applied') || '-';
      if (fields.statusText) fields.statusText.textContent = statusText;
      if (fields.location) fields.location.textContent = profile.location || 'Lokasi belum diisi';
      if (fields.bidang) fields.bidang.textContent = bidangMap[profile.category] || profile.title || 'Gig Worker Professional';
      if (fields.about) fields.about.textContent = profile.proposal || 'Gig Worker belum menambahkan deskripsi diri.';

      if (fullProfileBtn) {
        fullProfileBtn.setAttribute('href', 'worker-profile.php?id=' + encodeURIComponent(workerId) + '&from=kandidat');
      }

      // Skills badges
      if (fields.skillsBadges) {
        if (Array.isArray(profile.skills) && profile.skills.length > 0) {
          fields.skillsBadges.innerHTML = profile.skills.map(function(s) {
            return '<span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:9999px;padding:3px 10px;font-size:0.78rem;font-weight:700;">' + escapeHtml(s) + '</span>';
          }).join('');
        } else {
          fields.skillsBadges.textContent = 'Belum ada keahlian.';
        }
      }

      const contactUnlocked = (card.getAttribute('data-contact-unlocked') || '0') === '1';
      if (fields.contactEmail) {
        fields.contactEmail.textContent = contactUnlocked
          ? (profile.contact && profile.contact.email ? profile.contact.email : 'tidak tersedia')
          : 'Terkunci sampai kandidat diterima';
      }
      if (fields.contactWa) {
        fields.contactWa.textContent = contactUnlocked
          ? (profile.contact && profile.contact.wa ? profile.contact.wa : 'tidak tersedia')
          : 'Terkunci sampai kandidat diterima';
      }

      if (fields.titleInfo) fields.titleInfo.textContent = profile.title || 'Gig Worker';
      if (fields.projectsInfo) fields.projectsInfo.textContent = (profile.completed_projects || 0) + ' proyek selesai';
      if (fields.reviewsInfo) fields.reviewsInfo.textContent = (profile.reviews_count || (profile.reviews ? profile.reviews.length : 0)) + ' ulasan';

      // Experience timeline
      if (fields.experienceList) {
        if (Array.isArray(profile.experience) && profile.experience.length > 0) {
          fields.experienceList.innerHTML = profile.experience.map(function(exp) {
            let outTitle = exp.output_title || ('Output Proyek - ' + (exp.project || 'Deliverables Proyek'));
            let files = (Array.isArray(exp.files) && exp.files.length > 0) ? exp.files : [
              { name: 'Tautan Output Proyek (' + (exp.project || 'Hasil Karya') + ')', url: 'https://figma.com/@gigworker/' + encodeURIComponent((exp.project || 'output-proyek').toLowerCase().replace(/ /g, '-')) }
            ];

            let filesHtml = files.map(function(f) {
              let url = f.url && f.url !== '#' ? f.url : ('https://figma.com/@gigworker/' + encodeURIComponent((exp.project || 'output-proyek').toLowerCase().replace(/ /g, '-')));
              return '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;background:#ffffff;color:#1d4ed8;border:1px solid #93c5fd;padding:6px 12px;border-radius:8px;font-size:0.78rem;font-weight:700;text-decoration:none;box-shadow:0 1px 2px rgba(0,0,0,0.05);">' +
                '🔗 ' + escapeHtml(f.name || 'Lihat Output Proyek') +
                '</a>';
            }).join(' ');

            return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:12px;">' +
              '<div style="font-weight:800;font-size:0.92rem;color:#0f172a;">' + escapeHtml(exp.role || '') + '</div>' +
              '<div style="font-size:0.8rem;color:#64748b;margin:2px 0 6px 0;">' + escapeHtml(exp.project || exp.institution || '') + ' · ' + escapeHtml(exp.period || '') + '</div>' +
              '<div style="font-size:0.84rem;color:#334155;line-height:1.4;margin-bottom:10px;">' + escapeHtml(exp.summary || '') + '</div>' +
              '<div style="padding:10px 12px;background:#eff6ff;border:1px solid #dbeafe;border-radius:8px;">' +
                '<div style="font-size:0.8rem;font-weight:700;color:#1e40af;margin-bottom:6px;">📁 Output Proyek: ' + escapeHtml(outTitle) + '</div>' +
                '<div style="display:flex;flex-wrap:wrap;gap:8px;">' + filesHtml + '</div>' +
              '</div>' +
              '</div>';
          }).join('');
        } else {
          fields.experienceList.innerHTML = '<div style="color:#94a3b8;font-size:0.84rem;">Belum ada data pengalaman.</div>';
        }
      }

      // Portfolio list
      if (fields.portfolioList) {
        if (Array.isArray(profile.portfolio) && profile.portfolio.length > 0) {
          fields.portfolioList.innerHTML = profile.portfolio.map(function(port) {
            let filesHtml = '';
            if (Array.isArray(port.files) && port.files.length > 0) {
              filesHtml = '<div style="margin-top:8px;padding-top:8px;border-top:1px dashed #e2e8f0;">' +
                '<div style="font-size:0.75rem;font-weight:700;color:#64748b;margin-bottom:4px;">Berkas Deliverable:</div>' +
                port.files.map(function(f) {
                  return '<div style="font-size:0.78rem;color:#2563eb;display:flex;align-items:center;gap:6px;margin-bottom:3px;">' +
                    '<span>📎 ' + escapeHtml(f.name || '') + ' (' + escapeHtml(f.size || '') + ')</span>' +
                    '</div>';
                }).join('') +
                '</div>';
            }
            return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">' +
              '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">' +
              '<div style="font-weight:800;font-size:0.88rem;color:#0f172a;">' + escapeHtml(port.title || '') + '</div>' +
              '<span style="font-size:0.72rem;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:9999px;font-weight:700;flex-shrink:0;">' + escapeHtml(port.type || '') + '</span>' +
              '</div>' +
              '<div style="font-size:0.78rem;color:#64748b;margin:2px 0 6px 0;">' + escapeHtml(port.client || '') + ' · ' + escapeHtml(port.year || '') + '</div>' +
              '<div style="font-size:0.82rem;color:#334155;line-height:1.4;">' + escapeHtml(port.deliverable || '') + '</div>' +
              filesHtml +
              '</div>';
          }).join('');
        } else {
          fields.portfolioList.innerHTML = '<div style="color:#94a3b8;font-size:0.84rem;">Belum ada data portofolio.</div>';
        }
      }

      // Reviews list
      if (fields.reviewList) {
        if (Array.isArray(profile.reviews) && profile.reviews.length > 0) {
          fields.reviewList.innerHTML = profile.reviews.map(function(rev) {
            return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">' +
              '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">' +
              '<div>' +
              '<div style="font-weight:800;font-size:0.86rem;color:#0f172a;">' + escapeHtml(rev.employer || '') + '</div>' +
              '<div style="font-size:0.78rem;color:#64748b;">' + escapeHtml(rev.project || '') + ' · ' + escapeHtml(rev.date || '') + '</div>' +
              '</div>' +
              '<div style="color:#f59e0b;font-weight:800;font-size:0.84rem;flex-shrink:0;">★ ' + escapeHtml(String(rev.rating || 5)) + '.0</div>' +
              '</div>' +
              '<div style="font-size:0.82rem;color:#334155;line-height:1.4;margin-top:6px;">"' + escapeHtml(rev.comment || '') + '"</div>' +
              '</div>';
          }).join('');
        } else {
          fields.reviewList.innerHTML = '<div style="color:#94a3b8;font-size:0.84rem;">Belum ada ulasan pemberi kerja.</div>';
        }
      }

      if (appIdInput) appIdInput.value = card.getAttribute('data-app-id') || '';
      if (statusSelect) statusSelect.value = card.getAttribute('data-status-ui') || 'applied';

      setDrawerTab('profile');
      overlay.classList.add('open');
      drawer.classList.add('open');
      drawer.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    document.querySelectorAll('.candidate-card').forEach(function(card) {
      card.addEventListener('click', function() {
        if (!<?php echo json_encode($isTayang); ?>) return;
        openCandidateDrawer(card);
      });
    });

    window.closeCandidateDrawer = function() {
      if (!overlay || !drawer) return;
      overlay.classList.remove('open');
      drawer.classList.remove('open');
      drawer.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };

    Object.keys(tabBtns).forEach(function(key) {
      if (tabBtns[key]) {
        tabBtns[key].addEventListener('click', function() { setDrawerTab(key); });
      }
    });
  })();
</script>

<?php require __DIR__ . '/includes/employer-layout-end.php'; ?>

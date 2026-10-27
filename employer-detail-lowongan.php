<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-applications.php';

function gig_get_stage_rank(string $status): int
{
    return match ($status) {
        'applied' => 1,
        'reviewing' => 2,
        'interview' => 3,
        'accepted', 'confirmed_by_worker', 'accepted_by_employer' => 4,
        'rejected', 'rejected_by_employer', 'declined_by_worker' => 4,
        default => 1,
    };
}

function gig_update_application_pipeline_status(string $appId, string $status): array
{
    $allowed = ['applied', 'reviewing', 'interview', 'accepted', 'rejected', 'confirmed_by_worker', 'rejected_by_employer'];
    if (!in_array($status, $allowed, true)) {
        return ['ok' => false, 'error' => 'Status tidak valid.'];
    }
    $app = gig_get_application_by_id($appId);
    if (!$app) {
        return ['ok' => false, 'error' => 'Lamaran tidak ditemukan.'];
    }

    $currentStatus = (string)($app['status'] ?? 'applied');
    $currentRank = gig_get_stage_rank($currentStatus);
    $targetRank = gig_get_stage_rank($status);

    if ($currentRank >= 4) {
        return ['ok' => false, 'error' => 'Keputusan pelamar sudah final dan tidak dapat diubah lagi.'];
    }

    if ($targetRank <= $currentRank) {
        return ['ok' => false, 'error' => 'Lamaran yang sudah diproses tidak dapat dikembalikan ke tahapan sebelumnya karena keputusan bersifat final.'];
    }

    $dbStatus = match ($status) {
        'accepted' => 'confirmed_by_worker',
        'rejected' => 'rejected_by_employer',
        default => $status,
    };

    $now = date('Y-m-d H:i:s');
    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $stmt = $pdo->prepare("UPDATE `project_applications` SET `status` = :st, `updated_at` = NOW() WHERE `id` = :id");
            $stmt->execute([':st' => $dbStatus, ':id' => $appId]);
        } catch (Throwable $ignored) {}
    }

    gig_apps_session_start();
    if (isset($_SESSION['gig_applications'][$appId])) {
        $_SESSION['gig_applications'][$appId]['status'] = $dbStatus;
        $_SESSION['gig_applications'][$appId]['updated_at'] = $now;
    }
    foreach ($_SESSION['gig_applications'] as $key => $row) {
        if ((string)($row['id'] ?? '') === $appId) {
            $_SESSION['gig_applications'][$key]['status'] = $dbStatus;
            $_SESSION['gig_applications'][$key]['updated_at'] = $now;
            break;
        }
    }

    return ['ok' => true, 'status' => $dbStatus];
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

    $app = gig_get_application_by_id($appId);
    if (!$app) {
        $result = ['ok' => false, 'error' => 'Lamaran tidak ditemukan.'];
    } else {
        $currentStatus = (string)($app['status'] ?? 'applied');
        $currentRank = gig_get_stage_rank($currentStatus);
        $targetRank = gig_get_stage_rank($newStatus);

        if ($currentRank >= 4) {
            $result = ['ok' => false, 'error' => 'Keputusan pelamar sudah final dan tidak dapat diubah lagi.'];
        } elseif ($targetRank <= $currentRank) {
            $result = ['ok' => false, 'error' => 'Lamaran yang sudah diproses tidak dapat dikembalikan ke tahapan sebelumnya karena keputusan bersifat final.'];
        } elseif ($appId !== '' && in_array($newStatus, ['applied', 'reviewing', 'interview', 'accepted', 'rejected'], true)) {
            if ($newStatus === 'accepted' || $newStatus === 'rejected') {
                $result = gig_employer_respond_application(
                    $appId,
                    $newStatus === 'accepted' ? 'accept' : 'reject',
                    (string)($_SESSION['username'] ?? '')
                );
                if (!$result['ok'] && $newStatus === 'accepted') {
                    $result = gig_update_application_pipeline_status($appId, 'confirmed_by_worker');
                }
            } else {
                $result = gig_update_application_pipeline_status($appId, $newStatus);
            }
        }
    }

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (!empty($_POST['ajax']))
              || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => !empty($result['ok']),
            'message' => !empty($result['ok'])
                ? 'Status lamaran berhasil diperbarui.'
                : ((string)($result['error'] ?? 'Gagal memperbarui status lamaran.')),
            'app_id' => $appId,
            'new_status' => $newStatus,
            'contact_unlocked' => ($newStatus === 'accepted'),
        ]);
        exit;
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
    $contactUnlocked = gig_is_hired_status($status) || $lane === 'accepted';

    $prevExp = (string)($profile['prev_experience'] ?? '');
    if ($prevExp === '' && !empty($profile['experience'][0])) {
        $firstExp = $profile['experience'][0];
        $prevExp = ($firstExp['role'] ?? '') . ' di ' . ($firstExp['project'] ?? $firstExp['institution'] ?? '');
    }
    if ($prevExp === '') {
        $prevExp = (string)($profile['title'] ?? 'Profesional Gig Worker');
    }

    $lanes[$lane][] = [
        'id' => (string)($app['id'] ?? ''),
        'worker_id' => (string)($app['worker_id'] ?? ''),
        'name' => (string)($profile['name'] ?? $app['worker_name'] ?? 'Gig Worker'),
        'title' => (string)($profile['title'] ?? 'Gig Worker'),
        'location' => (string)($profile['location'] ?? 'Lokasi belum diisi'),
        'rating' => (float)($profile['rating'] ?? 5.0),
        'reviews_count' => (int)($profile['reviews_count'] ?? 0),
        'photo' => (string)($profile['photo'] ?? ''),
        'initials' => (string)($profile['initials'] ?? strtoupper(substr((string)($profile['name'] ?? 'G'), 0, 2))),
        'color' => (string)($profile['color'] ?? '#2563eb'),
        'status_tag' => (string)($profile['status_tag'] ?? 'Ter-PHK'),
        'prev_experience' => $prevExp,
        'contact_wa' => (string)($profile['contact']['wa'] ?? ''),
        'contact_email' => (string)($profile['contact']['email'] ?? ''),
        'status' => $status,
        'status_ui' => $statusUi,
        'status_text' => $statusText,
        'applied_label' => $appliedLabel,
        'contact_unlocked' => $contactUnlocked ? '1' : '0',
        'lane_rank' => gig_get_stage_rank($status),
        'is_final' => (gig_get_stage_rank($status) >= 4) ? '1' : '0',
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
$jobCode = '#' . substr(md5((string)$job['id']), 0, 8);
$pageTitle = 'Detail Lowongan · ' . $job['title'];
$pageKey = 'lowongan';
$breadcrumbCurrent = 'Lowongan > ' . $jobCode;
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
  .jobd-tabs { display:flex; gap:20px; border-bottom:1px solid #e2e8f0; margin-top:8px; }
  .jobd-tab { border:none; background:none; padding:10px 4px; font-size:0.92rem; color:#64748b; font-weight:700; border-bottom:2.5px solid transparent; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:color .15s ease, border-color .15s ease; }
  .jobd-tab.active { color:#0284c7; border-bottom-color:#0284c7; }
  .jobd-tools { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:14px; flex-wrap:wrap; }
  .jobd-search { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:8px 12px; min-width:280px; box-shadow:0 1px 2px rgba(0,0,0,0.03); }
  .jobd-search input { border:none; outline:none; width:100%; font-size:0.86rem; color:#0f172a; background:transparent; }
  .jobd-tools-right { display:flex; align-items:center; gap:8px; }
  .tool-view-btn { width:34px; height:34px; border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#64748b; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s ease; }
  .tool-view-btn:hover, .tool-view-btn.active { background:#f1f5f9; color:#0284c7; border-color:#cbd5e1; }
  
  /* Kanban Board */
  .jobd-board { display:grid; grid-template-columns:repeat(5, minmax(240px, 1fr)); gap:12px; overflow-x:auto; padding-bottom:12px; }
  .jobd-col { min-width:240px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; display:flex; flex-direction:column; max-height:720px; transition:background .15s ease, border-color .15s ease, box-shadow .15s ease; }
  .jobd-col.drag-over { background:#f0f9ff !important; border:2px dashed #0284c7 !important; box-shadow:0 0 0 4px rgba(2, 132, 199, 0.15) !important; }
  .jobd-col-head { padding:12px 14px; border-bottom:1px solid #eef2ff; display:flex; justify-content:space-between; align-items:center; font-size:0.84rem; font-weight:800; color:#1e293b; background:#fff; border-radius:14px 14px 0 0; }
  .jobd-col-head .col-count-pill { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:9999px; padding:2px 8px; font-size:0.75rem; font-weight:700; color:#475569; min-width:22px; text-align:center; }
  .jobd-dot { width:8px; height:8px; border-radius:9999px; display:inline-block; margin-right:8px; vertical-align:middle; }
  .jobd-col-body { padding:10px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; flex:1; min-height:160px; }
  .jobd-empty { font-size:0.82rem; color:#94a3b8; text-align:center; padding:32px 10px; font-weight:500; }
  
  /* Candidate Card */
  .jobd-card { border:1px solid #e2e8f0; border-radius:12px; padding:12px 13px; background:#ffffff; cursor:grab; user-select:none; transition:box-shadow .15s ease, transform .15s ease, border-color .15s ease; position:relative; box-shadow:0 1px 3px rgba(15,23,42,0.04); }
  .jobd-card:hover { border-color:#93c5fd; box-shadow:0 6px 18px rgba(37,99,235,.12); transform:translateY(-2px); }
  .jobd-card:active { cursor:grabbing; }
  .jobd-card.is-dragging { opacity:0.35 !important; transform:scale(0.97) rotate(1.5deg); border:2px dashed #0284c7 !important; box-shadow:0 16px 32px rgba(15,23,42,0.2) !important; }
  
  .cand-card-top { display:flex; gap:10px; align-items:flex-start; margin-bottom:8px; }
  .cand-card-avatar { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.95rem; color:#fff; flex-shrink:0; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.08); }
  .cand-card-avatar img { width:100%; height:100%; object-fit:cover; }
  .cand-card-info { flex:1; min-width:0; }
  .cand-card-name { font-size:0.86rem; font-weight:800; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-transform:uppercase; letter-spacing:-0.01em; margin-bottom:2px; }
  .cand-card-sub { font-size:0.74rem; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .cand-card-pin { border:none; background:none; font-size:0.85rem; color:#94a3b8; cursor:pointer; padding:2px; line-height:1; }
  .cand-card-pin:hover { color:#0284c7; }
  
  .cand-card-badges { display:flex; gap:6px; flex-wrap:wrap; margin:6px 0 8px 0; }
  .cand-pill-status { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; padding:2px 7px; font-size:0.72rem; font-weight:700; color:#475569; display:inline-flex; align-items:center; gap:4px; }
  
  .cand-meta-row { display:flex; align-items:center; gap:6px; font-size:0.75rem; color:#475569; margin-bottom:4px; line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .cand-meta-row .ico { font-size:0.82rem; flex-shrink:0; opacity:0.85; }
  .cand-rating-row { color:#f59e0b; font-weight:700; margin-top:6px; font-size:0.76rem; }
  .cand-rating-reviews { color:#64748b; font-weight:500; font-size:0.72rem; margin-left:2px; }
  
  /* Contact Information Section on Card */
  .cand-contact-unlocked-box {
    background:linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border:1px solid #86efac;
    border-radius:10px;
    padding:9px 10px;
    margin-top:10px;
    display:flex;
    flex-direction:column;
    gap:5px;
    box-shadow:0 1px 3px rgba(16,185,129,0.08);
  }
  .cand-contact-hdr { display:flex; align-items:center; justify-content:space-between; gap:6px; }
  .cand-unlocked-badge { background:#059669; color:#ffffff; border-radius:9999px; padding:2px 8px; font-size:0.68rem; font-weight:800; display:inline-flex; align-items:center; gap:3px; }
  .btn-copy-chip { background:#ffffff; border:1px solid #bbf7d0; color:#047857; border-radius:6px; padding:2px 7px; font-size:0.68rem; font-weight:700; cursor:pointer; transition:background .15s ease; }
  .btn-copy-chip:hover { background:#dcfce7; }
  
  .cand-contact-item { display:flex; align-items:center; gap:6px; font-size:0.75rem; font-weight:700; }
  .cand-contact-item a { color:#047857; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .cand-contact-item a:hover { text-decoration:underline; }
  
  .cand-contact-actions { display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-top:4px; }
  .cand-btn-wa { background:#16a34a; color:#ffffff !important; border-radius:7px; padding:5px 6px; font-size:0.72rem; font-weight:700; text-align:center; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:4px; box-shadow:0 1px 2px rgba(22,163,74,0.2); }
  .cand-btn-wa:hover { background:#15803d; }
  .cand-btn-email { background:#2563eb; color:#ffffff !important; border-radius:7px; padding:5px 6px; font-size:0.72rem; font-weight:700; text-align:center; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:4px; box-shadow:0 1px 2px rgba(37,99,235,0.2); }
  .cand-btn-email:hover { background:#1d4ed8; }
  
  .cand-contact-locked-box {
    background:#f8fafc;
    border:1px dashed #cbd5e1;
    border-radius:8px;
    padding:6px 9px;
    margin-top:8px;
    font-size:0.71rem;
    color:#64748b;
    display:flex;
    align-items:center;
    gap:6px;
    line-height:1.3;
  }
  
  /* Floating Kanban Drag Toast */
  .kanban-drag-toast {
    position:fixed;
    bottom:24px;
    left:50%;
    transform:translateX(-50%) translateY(100px);
    background:#0f172a;
    color:#ffffff;
    padding:10px 22px;
    border-radius:9999px;
    box-shadow:0 12px 36px rgba(15,23,42,0.48);
    z-index:2500;
    display:flex;
    align-items:center;
    gap:10px;
    font-size:0.85rem;
    font-weight:700;
    pointer-events:none;
    opacity:0;
    transition:transform .22s cubic-bezier(0.16, 1, 0.3, 1), opacity .22s ease;
  }
  .kanban-drag-toast.visible {
    opacity:1;
    transform:translateX(-50%) translateY(0);
  }
  .toast-dot { width:8px; height:8px; border-radius:50%; display:inline-block; vertical-align:middle; }
  
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

  /* Final Decision State for Cards */
  .candidate-card.cand-card-final {
    cursor: pointer !important;
  }
  .cand-pill-final {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 9999px;
  }
  .cand-pill-final-accepted {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }
  .cand-pill-final-rejected {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
  }

  /* Blocked drag drop zone (cannot move backward) */
  .jobd-col.drag-blocked {
    background: #fef2f2 !important;
    border: 2px dashed #f87171 !important;
    cursor: not-allowed !important;
  }

  /* Confirmation Modal (KarirHub / Kemnaker visual replica) */
  .pipeline-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    z-index: 3000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
  }
  .pipeline-confirm-overlay.open {
    opacity: 1;
    pointer-events: auto;
  }
  .pipeline-confirm-modal {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 470px;
    box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.25), 0 0 1px rgba(0,0,0,0.15);
    padding: 24px 26px 22px 26px;
    transform: scale(0.94);
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .pipeline-confirm-overlay.open .pipeline-confirm-modal {
    transform: scale(1);
  }
  .pipeline-confirm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
  }
  .pipeline-confirm-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
  }
  .pipeline-confirm-close {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 1.15rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.15s ease;
  }
  .pipeline-confirm-close:hover {
    color: #0f172a;
    background: #f1f5f9;
  }
  .pipeline-confirm-body {
    margin-bottom: 24px;
  }
  .pipeline-confirm-desc {
    font-size: 0.88rem;
    line-height: 1.6;
    color: #334155;
    margin: 0;
    font-weight: 500;
  }
  .pipeline-confirm-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
  }
  .pipeline-btn-cancel {
    background: #ffffff;
    border: 1.5px solid #0f172a;
    color: #0f172a;
    padding: 7px 22px;
    border-radius: 9999px;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .pipeline-btn-cancel:hover {
    background: #f8fafc;
    border-color: #000000;
  }
  .pipeline-btn-proceed {
    background: #00a2e8; /* Kemnaker Cyan */
    border: none;
    color: #ffffff;
    padding: 8.5px 26px;
    border-radius: 9999px;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0, 162, 232, 0.35);
    transition: all 0.15s ease;
  }
  .pipeline-btn-proceed:hover {
    background: #0284c7;
    box-shadow: 0 4px 18px rgba(0, 162, 232, 0.45);
  }
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
          <span>•</span>
          <span>Kode: <strong style="font-family:monospace;"><?php echo htmlspecialchars($jobCode, ENT_QUOTES, 'UTF-8'); ?></strong></span>
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
    <button class="jobd-tab active" type="button" data-tab="lamaran">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Lamaran
    </button>
    <button class="jobd-tab" type="button" data-tab="detail">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      Detail Lowongan
    </button>
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
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <div class="jobd-search">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="candidateSearchInput" type="text" placeholder="Cari pelamar..." <?php echo !$isTayang ? 'disabled' : ''; ?> />
          </div>
          <button class="filter-btn-pill" type="button" id="btnFilterMarked" onclick="toggleFilterMarked(this)" <?php echo !$isTayang ? 'disabled' : ''; ?> style="display:inline-flex;align-items:center;gap:4px;">
            <span>🔖</span> Ditandai ▾
          </button>
          <button class="filter-btn-pill" type="button" <?php echo !$isTayang ? 'disabled' : ''; ?> style="display:inline-flex;align-items:center;gap:5px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
          </button>
        </div>
        
        <div class="jobd-tools-right">
          <button class="tool-view-btn" type="button" title="Tampilan Daftar" onclick="setViewMode('list')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          </button>
          <button class="tool-view-btn active" type="button" title="Tampilan Kanban / Kolom" onclick="setViewMode('kanban')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="10" rx="1"/></svg>
          </button>
          <button class="tool-view-btn" type="button" title="Tampilan Grid" onclick="setViewMode('grid')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          </button>
        </div>
      </div>

      <div class="jobd-board" id="kanbanBoard" style="margin-top:14px; <?php echo !$isTayang ? 'filter:blur(3px); opacity:0.45; pointer-events:none;' : ''; ?>">
        <?php foreach ($lanes as $key => $cards): ?>
          <div class="jobd-col" data-lane="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" id="lane-<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="jobd-col-head">
              <span>
                <span class="jobd-dot" style="background:<?php echo htmlspecialchars($laneMeta[$key]['dot'], ENT_QUOTES, 'UTF-8'); ?>"></span>
                <?php echo htmlspecialchars($laneMeta[$key]['label'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
              <span class="col-count-pill" id="count-<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo count($cards); ?></span>
            </div>
            <div class="jobd-col-body" data-lane-body="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
              <div class="jobd-empty" style="<?php echo count($cards) === 0 ? '' : 'display:none;'; ?>">Tidak ada data.</div>
              <?php foreach ($cards as $c): 
                $candSearchCorpus = function_exists('gig_worker_search_metadata') 
                  ? gig_worker_search_metadata($c['profile'])['full_text'] 
                  : strtolower($c['name'] . ' ' . $c['title'] . ' ' . $c['contact_email']);
                $isUnlocked = ($c['contact_unlocked'] === '1' || $key === 'accepted');
                $waNum = !empty($c['contact_wa']) ? $c['contact_wa'] : '0812-3456-7890';
                $cleanWa = preg_replace('/[^0-9]/', '', $waNum);
                if (str_starts_with($cleanWa, '0')) {
                    $cleanWa = '62' . substr($cleanWa, 1);
                }
                $emailVal = !empty($c['contact_email']) ? $c['contact_email'] : 'kandidat@email.com';
                $waText = urlencode("Halo " . $c['name'] . ", kami dari perusahaan telah menerima lamaran Anda untuk proyek '" . $job['title'] . "'. Mari berkoordinasi untuk langkah berikutnya.");
                $emailSubject = urlencode("Penerimaan Proyek: " . $job['title']);
              ?>
              <?php 
                $isFinal = ($c['is_final'] === '1');
              ?>
                <div
                  class="jobd-card candidate-card <?php echo $isFinal ? 'cand-card-final' : ''; ?>"
                  draggable="<?php echo $isFinal ? 'false' : 'true'; ?>"
                  data-search="<?php echo htmlspecialchars($candSearchCorpus, ENT_QUOTES, 'UTF-8'); ?>"
                  data-app-id="<?php echo htmlspecialchars($c['id'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-worker-id="<?php echo htmlspecialchars($c['worker_id'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-name="<?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-title="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-lane="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                  data-lane-rank="<?php echo (int)$c['lane_rank']; ?>"
                  data-is-final="<?php echo $isFinal ? '1' : '0'; ?>"
                  data-status-text="<?php echo htmlspecialchars($c['status_text'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-status-ui="<?php echo htmlspecialchars($c['status_ui'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-applied="<?php echo htmlspecialchars($c['applied_label'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-contact-unlocked="<?php echo $isUnlocked ? '1' : '0'; ?>"
                  data-contact-wa="<?php echo htmlspecialchars($waNum, ENT_QUOTES, 'UTF-8'); ?>"
                  data-contact-email="<?php echo htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8'); ?>"
                  data-profile-json="<?php echo htmlspecialchars(json_encode($c['profile'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
                >
                  <div class="cand-card-top">
                    <div class="cand-card-avatar" style="background:<?php echo htmlspecialchars($c['color'], ENT_QUOTES, 'UTF-8'); ?>;">
                      <?php if (!empty($c['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($c['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>" />
                      <?php else: ?>
                        <span><?php echo htmlspecialchars($c['initials'], ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="cand-card-info">
                      <div class="cand-card-name" title="<?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="cand-card-sub" title="<?php echo htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8'); ?>
                      </div>
                    </div>
                    <button type="button" class="cand-card-pin" title="Tandai pelamar" onclick="togglePinCard(event, this)">📌</button>
                  </div>

                  <div class="cand-card-badges">
                    <span class="cand-pill-status">
                      <span>🏢</span> <?php echo htmlspecialchars($c['status_tag'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <?php if ($key === 'accepted'): ?>
                      <span class="cand-pill-final cand-pill-final-accepted">✓ Final: Diterima</span>
                    <?php elseif ($key === 'rejected'): ?>
                      <span class="cand-pill-final cand-pill-final-rejected">✕ Final: Ditolak</span>
                    <?php endif; ?>
                  </div>

                  <div class="cand-meta-row" title="<?php echo htmlspecialchars($c['prev_experience'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="ico">💼</span>
                    <span style="overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($c['prev_experience'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>

                  <div class="cand-meta-row" title="<?php echo htmlspecialchars($c['location'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="ico">📍</span>
                    <span style="overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($c['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>

                  <div class="cand-meta-row cand-rating-row">
                    <span>★ <?php echo number_format((float)$c['rating'], 1); ?></span>
                    <span class="cand-rating-reviews">(<?php echo (int)$c['reviews_count']; ?> ulasan)</span>
                  </div>

                  <!-- Dynamic Contact Section -->
                  <div class="card-contact-box-container">
                    <?php if ($isUnlocked): ?>
                      <div class="cand-contact-unlocked-box">
                        <div class="cand-contact-hdr">
                          <span class="cand-unlocked-badge">✓ Kontak Terbuka</span>
                          <button type="button" class="btn-copy-chip" onclick="copyWorkerContact(event, '<?php echo htmlspecialchars(addslashes($waNum), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($emailVal), ENT_QUOTES, 'UTF-8'); ?>')">📋 Salin</button>
                        </div>
                        <div class="cand-contact-item">
                          <span>💬</span>
                          <a href="https://wa.me/<?php echo $cleanWa; ?>?text=<?php echo $waText; ?>" target="_blank" onclick="event.stopPropagation()">
                            <?php echo htmlspecialchars($waNum, ENT_QUOTES, 'UTF-8'); ?>
                          </a>
                        </div>
                        <div class="cand-contact-item">
                          <span>✉️</span>
                          <a href="mailto:<?php echo htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8'); ?>?subject=<?php echo $emailSubject; ?>" onclick="event.stopPropagation()">
                            <?php echo htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8'); ?>
                          </a>
                        </div>
                        <div class="cand-contact-actions">
                          <a href="https://wa.me/<?php echo $cleanWa; ?>?text=<?php echo $waText; ?>" target="_blank" class="cand-btn-wa" onclick="event.stopPropagation()">Chat WA ↗</a>
                          <a href="mailto:<?php echo htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8'); ?>?subject=<?php echo $emailSubject; ?>" class="cand-btn-email" onclick="event.stopPropagation()">Email ↗</a>
                        </div>
                      </div>
                    <?php else: ?>
                      <div class="cand-contact-locked-box">
                        <span>🔒</span>
                        <span>Kontak terkunci · Tarik ke <strong>Diterima</strong> untuk membuka</span>
                      </div>
                    <?php endif; ?>
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

<!-- Floating Pill Toast during Drag and on Drop -->
<div id="kanbanDragToast" class="kanban-drag-toast">
  <span id="toastCandName" style="color:#ffffff;">KANDIDAT</span>
  <span id="toastFromLane" style="color:#cbd5e1;">Lamaran Masuk</span>
  <span style="color:#94a3b8;margin:0 2px;">➔</span>
  <span id="toastTargetDot" class="toast-dot" style="background:#fb923c;"></span>
  <span id="toastTargetLane" style="color:#ffffff;">Sedang Dipelajari</span>
</div>

<!-- Confirmation Modal (KarirHub / Kemnaker visual replica) -->
<div id="pipelineConfirmOverlay" class="pipeline-confirm-overlay" aria-hidden="true">
  <div class="pipeline-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
    <div class="pipeline-confirm-header">
      <h3 id="confirmModalTitle" class="pipeline-confirm-title">Konfirmasi</h3>
      <button type="button" class="pipeline-confirm-close" id="btnConfirmClose" aria-label="Tutup modal">✕</button>
    </div>
    <div class="pipeline-confirm-body">
      <p id="confirmModalText" class="pipeline-confirm-desc">
        Anda akan menandai 1 lamaran sebagai sedang dipelajari. Lamaran yang sudah dipelajari tidak dapat dikembalikan ke lamaran masuk lagi. Untuk meneruskan aksi silahkan klik tombol konfirmasi dibawah ini.
      </p>
    </div>
    <div class="pipeline-confirm-footer">
      <button type="button" class="pipeline-btn-cancel" id="btnConfirmCancel">Batalkan</button>
      <button type="button" class="pipeline-btn-proceed" id="btnConfirmProceed">Lanjutkan</button>
    </div>
  </div>
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
      <!-- Unlocked Contact Alert (visible if Diterima) -->
      <div id="candUnlockedAlert" style="display:none;background:linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);border:1px solid #86efac;border-radius:10px;padding:12px 14px;margin-top:8px;">
        <div style="font-weight:800;color:#065f46;font-size:0.86rem;display:flex;align-items:center;gap:6px;margin-bottom:3px;">
          <span>🎉</span> Kontak Resmi Gig Worker Terbuka
        </div>
        <div style="font-size:0.8rem;color:#047857;line-height:1.4;">Kandidat berstatus <strong>Diterima</strong>. Anda dapat langsung berkoordinasi via WhatsApp atau Email resmi di bawah.</div>
      </div>

      <!-- Locked Contact Alert (visible if not Diterima) -->
      <div id="candLockedAlert" style="display:none;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;padding:10px 12px;margin-top:8px;font-size:0.8rem;color:#64748b;line-height:1.4;">
        🔒 Kontak resmi terkunci. Geser kandidat ke kolom <strong>Diterima</strong> untuk membuka nomor WhatsApp dan email resmi kandidat.
      </div>

      <div class="cand-row" style="margin-top:12px;">
        <div class="cand-label">👤 Informasi Profil</div>
        <div class="cand-value" style="font-weight:500;">
          <div style="margin-bottom:6px;color:#0f172a;"><strong id="candTitleInfo">Gig Worker</strong></div>
          <div style="margin-bottom:10px;color:#64748b;"><span id="candProjectsInfo">0 proyek selesai</span> · <span id="candReviewsInfo">0 ulasan</span></div>
          
          <div style="margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
            <div style="color:#64748b;">WhatsApp: <strong id="candContactWa" style="color:#0f172a;">tidak tersedia</strong></div>
            <a id="candWaActionBtn" href="#" target="_blank" style="display:none;padding:3px 10px;background:#16a34a;color:#fff;border-radius:6px;font-size:0.75rem;font-weight:700;text-decoration:none;">Chat WA ↗</a>
          </div>

          <div style="margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
            <div style="color:#64748b;">Email: <strong id="candContactEmail" style="color:#0f172a;">tidak tersedia</strong></div>
            <a id="candEmailActionBtn" href="#" style="display:none;padding:3px 10px;background:#2563eb;color:#fff;border-radius:6px;font-size:0.75rem;font-weight:700;text-decoration:none;">Kirim Email ↗</a>
          </div>

          <div id="candCopyBtnWrap" style="display:none;margin-top:10px;">
            <button type="button" id="candCopyFullBtn" style="padding:6px 12px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:0.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">📋 Salin Kontak Lengkap</button>
          </div>
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
    <form method="post" action="" class="cand-status-form" id="drawerStatusForm">
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

    /* ─── TOAST NOTIFICATION ─── */
    const dragToast = document.getElementById('kanbanDragToast');
    const toastName = document.getElementById('toastCandName');
    const toastFrom = document.getElementById('toastFromLane');
    const toastDot = document.getElementById('toastTargetDot');
    const toastTarget = document.getElementById('toastTargetLane');
    let toastTimeout = null;

    const laneLabels = {
      'incoming': 'Lamaran Masuk',
      'reviewed': 'Sedang Dipelajari',
      'interview': 'Wawancara',
      'accepted': 'Diterima',
      'rejected': 'Ditolak'
    };
    const laneDots = {
      'incoming': '#f59e0b',
      'reviewed': '#fb923c',
      'interview': '#3b82f6',
      'accepted': '#22c55e',
      'rejected': '#ef4444'
    };

    function showDragToast(name, fromLaneKey, toLaneKey) {
      if (!dragToast) return;
      if (toastTimeout) clearTimeout(toastTimeout);
      if (toastName) toastName.textContent = (name || '').toUpperCase();
      if (toastFrom) toastFrom.textContent = laneLabels[fromLaneKey] || fromLaneKey;
      if (toastTarget) toastTarget.textContent = laneLabels[toLaneKey] || toLaneKey;
      if (toastDot) toastDot.style.background = laneDots[toLaneKey] || '#3b82f6';
      dragToast.classList.add('visible');
    }

    function showSuccessToast(message) {
      if (!dragToast) return;
      if (toastTimeout) clearTimeout(toastTimeout);
      dragToast.innerHTML = '<span style="color:#22c55e;font-size:1.05rem;">✓</span> <span>' + escapeHtml(message) + '</span>';
      dragToast.classList.add('visible');
      toastTimeout = setTimeout(function() {
        dragToast.classList.remove('visible');
      }, 2800);
    }

    function showWarningToast(message) {
      if (!dragToast) return;
      if (toastTimeout) clearTimeout(toastTimeout);
      dragToast.innerHTML = '<span style="color:#f59e0b;font-size:1.05rem;">⚠️</span> <span style="color:#ffffff;">' + escapeHtml(message) + '</span>';
      dragToast.classList.add('visible');
      toastTimeout = setTimeout(function() {
        dragToast.classList.remove('visible');
      }, 3500);
    }

    function hideDragToast() {
      if (!dragToast) return;
      dragToast.classList.remove('visible');
    }

    /* ─── CONFIRMATION MODAL CONTROLLER ─── */
    const confirmOverlay = document.getElementById('pipelineConfirmOverlay');
    const confirmTitle = document.getElementById('confirmModalTitle');
    const confirmText = document.getElementById('confirmModalText');
    const btnConfirmClose = document.getElementById('btnConfirmClose');
    const btnConfirmCancel = document.getElementById('btnConfirmCancel');
    const btnConfirmProceed = document.getElementById('btnConfirmProceed');

    let currentConfirmCallback = null;
    let currentCancelCallback = null;

    const confirmMessages = {
      'reviewed': 'Anda akan menandai 1 lamaran sebagai sedang dipelajari. Lamaran yang sudah dipelajari tidak dapat dikembalikan ke lamaran masuk lagi. Untuk meneruskan aksi silahkan klik tombol konfirmasi dibawah ini.',
      'interview': 'Anda akan memindahkan 1 lamaran ke tahap wawancara. Lamaran yang sudah dipindahkan ke tahap wawancara tidak dapat dikembalikan ke tahapan sebelumnya. Untuk meneruskan aksi silahkan klik tombol konfirmasi dibawah ini.',
      'accepted': 'Anda akan menandai 1 lamaran sebagai diterima untuk proyek ini. Kontak resmi Gig Worker akan terbuka. Keputusan ini bersifat final dan tidak dapat dikembalikan ke tahapan sebelumnya. Untuk meneruskan aksi silahkan klik tombol konfirmasi dibawah ini.',
      'rejected': 'Anda akan menandai 1 lamaran sebagai ditolak. Keputusan penolakan bersifat final dan tidak dapat dikembalikan ke tahapan sebelumnya. Untuk meneruskan aksi silahkan klik tombol konfirmasi dibawah ini.'
    };

    function openConfirmModal(targetLaneKey, onConfirm, onCancel) {
      if (!confirmOverlay || !confirmText) {
        if (typeof onConfirm === 'function') onConfirm();
        return;
      }
      const msg = confirmMessages[targetLaneKey] || 'Lamaran yang sudah dipindahkan tidak dapat dikembalikan ke tahapan sebelumnya. Untuk meneruskan aksi silahkan klik tombol konfirmasi dibawah ini.';
      confirmText.textContent = msg;
      currentConfirmCallback = onConfirm;
      currentCancelCallback = onCancel;
      confirmOverlay.classList.add('open');
      confirmOverlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeConfirmModal() {
      if (!confirmOverlay) return;
      confirmOverlay.classList.remove('open');
      confirmOverlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      currentConfirmCallback = null;
      currentCancelCallback = null;
    }

    if (btnConfirmCancel) {
      btnConfirmCancel.addEventListener('click', function() {
        const cb = currentCancelCallback;
        closeConfirmModal();
        if (typeof cb === 'function') cb();
      });
    }
    if (btnConfirmClose) {
      btnConfirmClose.addEventListener('click', function() {
        const cb = currentCancelCallback;
        closeConfirmModal();
        if (typeof cb === 'function') cb();
      });
    }
    if (confirmOverlay) {
      confirmOverlay.addEventListener('click', function(e) {
        if (e.target === confirmOverlay) {
          const cb = currentCancelCallback;
          closeConfirmModal();
          if (typeof cb === 'function') cb();
        }
      });
    }
    if (btnConfirmProceed) {
      btnConfirmProceed.addEventListener('click', function() {
        const cb = currentConfirmCallback;
        closeConfirmModal();
        if (typeof cb === 'function') cb();
      });
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && confirmOverlay && confirmOverlay.classList.contains('open')) {
        const cb = currentCancelCallback;
        closeConfirmModal();
        if (typeof cb === 'function') cb();
      }
    });

    window.copyWorkerContact = function(e, wa, email) {
      if (e) e.stopPropagation();
      const txt = (wa ? 'WhatsApp: ' + wa + '\n' : '') + (email ? 'Email: ' + email : '');
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(txt.trim()).then(function() {
          showSuccessToast('Kontak ' + (wa || email) + ' berhasil disalin!');
        }).catch(function() {
          showSuccessToast('Kontak: ' + (wa || email));
        });
      } else {
        showSuccessToast('Kontak: ' + (wa || email));
      }
    };

    window.togglePinCard = function(e, btn) {
      if (e) e.stopPropagation();
      btn.classList.toggle('pinned');
      btn.style.color = btn.classList.contains('pinned') ? '#0284c7' : '#94a3b8';
      showSuccessToast(btn.classList.contains('pinned') ? 'Pelamar ditandai 📌' : 'Tanda pelamar dilepas');
    };

    window.toggleFilterMarked = function(btn) {
      btn.classList.toggle('active');
      const onlyMarked = btn.classList.contains('active');
      document.querySelectorAll('.candidate-card').forEach(function(card) {
        const pin = card.querySelector('.cand-card-pin');
        const isPinned = pin && pin.classList.contains('pinned');
        card.style.display = (!onlyMarked || isPinned) ? 'block' : 'none';
      });
    };

    window.setViewMode = function(mode) {
      document.querySelectorAll('.tool-view-btn').forEach(function(b) { b.classList.remove('active'); });
      const activeBtn = document.querySelector('.tool-view-btn[onclick*="' + mode + '"]');
      if (activeBtn) activeBtn.classList.add('active');
      const board = document.getElementById('kanbanBoard');
      if (!board) return;
      if (mode === 'list') {
        board.style.display = 'flex';
        board.style.flexDirection = 'column';
      } else if (mode === 'grid') {
        board.style.display = 'grid';
        board.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
      } else {
        board.style.display = 'grid';
        board.style.gridTemplateColumns = 'repeat(5, minmax(240px, 1fr))';
      }
    };

    /* ─── DRAG & DROP IMPLEMENTATION (IRREVERSIBLE PIPELINE) ─── */
    const laneRanks = {
      'incoming': 1,
      'reviewed': 2,
      'interview': 3,
      'accepted': 4,
      'rejected': 4
    };

    let draggedCard = null;
    let sourceLaneKey = null;
    let isDraggingActive = false;

    function buildUnlockedContactHtml(waNum, emailVal, candName) {
      const cleanWa = (waNum || '').replace(/[^0-9]/g, '').replace(/^0/, '62');
      const waText = encodeURIComponent("Halo " + (candName || 'Gig Worker') + ", kami dari perusahaan telah menerima lamaran Anda untuk proyek '<?php echo addslashes($job['title']); ?>'. Mari berkoordinasi untuk langkah berikutnya.");
      const emailSubject = encodeURIComponent("Penerimaan Proyek: <?php echo addslashes($job['title']); ?>");

      return '<div class="cand-contact-unlocked-box">' +
        '<div class="cand-contact-hdr">' +
          '<span class="cand-unlocked-badge">✓ Kontak Terbuka</span>' +
          '<button type="button" class="btn-copy-chip" onclick="copyWorkerContact(event, \'' + escapeHtml(waNum) + '\', \'' + escapeHtml(emailVal) + '\')">📋 Salin</button>' +
        '</div>' +
        '<div class="cand-contact-item">' +
          '<span>💬</span>' +
          '<a href="https://wa.me/' + cleanWa + '?text=' + waText + '" target="_blank" onclick="event.stopPropagation()">' + escapeHtml(waNum || '0812-3456-7890') + '</a>' +
        '</div>' +
        '<div class="cand-contact-item">' +
          '<span>✉️</span>' +
          '<a href="mailto:' + escapeHtml(emailVal) + '?subject=' + emailSubject + '" onclick="event.stopPropagation()">' + escapeHtml(emailVal || 'kandidat@email.com') + '</a>' +
        '</div>' +
        '<div class="cand-contact-actions">' +
          '<a href="https://wa.me/' + cleanWa + '?text=' + waText + '" target="_blank" class="cand-btn-wa" onclick="event.stopPropagation()">Chat WA ↗</a>' +
          '<a href="mailto:' + escapeHtml(emailVal) + '?subject=' + emailSubject + '" class="cand-btn-email" onclick="event.stopPropagation()">Email ↗</a>' +
        '</div>' +
      '</div>';
    }

    function buildLockedContactHtml() {
      return '<div class="cand-contact-locked-box">' +
        '<span>🔒</span>' +
        '<span>Kontak terkunci · Tarik ke <strong>Diterima</strong> untuk membuka</span>' +
      '</div>';
    }

    function updateLaneEmptyState(col) {
      if (!col) return;
      const body = col.querySelector('.jobd-col-body');
      const emptyEl = body ? body.querySelector('.jobd-empty') : null;
      const countEl = col.querySelector('.col-count-pill');
      if (!body) return;
      const cards = body.querySelectorAll('.candidate-card');
      if (countEl) countEl.textContent = cards.length;
      if (emptyEl) emptyEl.style.display = (cards.length === 0) ? 'block' : 'none';
    }

    function setupDragAndDrop() {
      const cards = document.querySelectorAll('.candidate-card');
      const columns = document.querySelectorAll('.jobd-col');

      cards.forEach(function(card) {
        const isFinal = card.getAttribute('data-is-final') === '1' || parseInt(card.getAttribute('data-lane-rank') || '1', 10) >= 4;
        card.setAttribute('draggable', isFinal ? 'false' : 'true');

        card.addEventListener('dragstart', function(e) {
          const isCardFinal = card.getAttribute('data-is-final') === '1' || parseInt(card.getAttribute('data-lane-rank') || '1', 10) >= 4;
          if (isCardFinal) {
            e.preventDefault();
            showWarningToast('Keputusan pelamar sudah final dan tidak dapat diubah lagi.');
            return false;
          }

          draggedCard = card;
          isDraggingActive = true;
          const parentCol = card.closest('.jobd-col');
          sourceLaneKey = parentCol ? parentCol.getAttribute('data-lane') : (card.getAttribute('data-lane') || 'incoming');
          card.classList.add('is-dragging');
          e.dataTransfer.setData('text/plain', card.getAttribute('data-app-id') || '');
          e.dataTransfer.effectAllowed = 'move';

          const candName = card.getAttribute('data-name') || 'Kandidat';
          showDragToast(candName, sourceLaneKey, sourceLaneKey);
        });

        card.addEventListener('dragend', function() {
          card.classList.remove('is-dragging');
          columns.forEach(function(c) {
            c.classList.remove('drag-over');
            c.classList.remove('drag-blocked');
          });
          setTimeout(function() {
            isDraggingActive = false;
            draggedCard = null;
          }, 150);
          if (toastTimeout === null) {
            hideDragToast();
          }
        });
      });

      columns.forEach(function(col) {
        col.addEventListener('dragover', function(e) {
          e.preventDefault();
          if (!draggedCard) return;

          const targetLaneKey = col.getAttribute('data-lane') || 'incoming';
          const sRank = laneRanks[sourceLaneKey] || 1;
          const tRank = laneRanks[targetLaneKey] || 1;

          if (tRank <= sRank) {
            // Irreversible pipeline: cannot move backward or stay in same lane
            e.dataTransfer.dropEffect = 'none';
            col.classList.remove('drag-over');
            if (targetLaneKey !== sourceLaneKey) {
              col.classList.add('drag-blocked');
            }
          } else {
            // Valid forward move
            e.dataTransfer.dropEffect = 'move';
            col.classList.remove('drag-blocked');
            col.classList.add('drag-over');
            const candName = draggedCard.getAttribute('data-name') || 'Kandidat';
            showDragToast(candName, sourceLaneKey, targetLaneKey);
          }
        });

        col.addEventListener('dragenter', function(e) {
          e.preventDefault();
          if (!draggedCard) return;
          const targetLaneKey = col.getAttribute('data-lane') || 'incoming';
          const sRank = laneRanks[sourceLaneKey] || 1;
          const tRank = laneRanks[targetLaneKey] || 1;

          if (tRank > sRank) {
            col.classList.add('drag-over');
          } else if (targetLaneKey !== sourceLaneKey) {
            col.classList.add('drag-blocked');
          }
        });

        col.addEventListener('dragleave', function(e) {
          if (!col.contains(e.relatedTarget)) {
            col.classList.remove('drag-over');
            col.classList.remove('drag-blocked');
          }
        });

        col.addEventListener('drop', function(e) {
          e.preventDefault();
          col.classList.remove('drag-over');
          col.classList.remove('drag-blocked');
          if (!draggedCard) return;

          const targetLaneKey = col.getAttribute('data-lane') || 'incoming';
          const cardToMove = draggedCard;
          const sRank = laneRanks[sourceLaneKey] || 1;
          const tRank = laneRanks[targetLaneKey] || 1;
          const appId = cardToMove.getAttribute('data-app-id') || '';
          const candName = cardToMove.getAttribute('data-name') || 'Kandidat';
          const prevParentCol = cardToMove.closest('.jobd-col');

          hideDragToast();

          if (targetLaneKey === sourceLaneKey) {
            return;
          }

          // Strict block on moving backwards
          if (tRank <= sRank) {
            showWarningToast('Lamaran yang sudah diproses tidak dapat dikembalikan ke tahapan sebelumnya. Keputusan bersifat final.');
            return;
          }

          // Valid forward move: Prompt Confirmation Modal
          openConfirmModal(
            targetLaneKey,
            function onConfirm() {
              // Move DOM element to target column
              const targetBody = col.querySelector('.jobd-col-body');
              if (targetBody) {
                targetBody.appendChild(cardToMove);
              }

              // Update column counts and empty indicators
              updateLaneEmptyState(prevParentCol);
              updateLaneEmptyState(col);

              // Update card attributes
              const isNowAccepted = (targetLaneKey === 'accepted');
              const isNowFinal = (tRank >= 4);

              cardToMove.setAttribute('data-lane', targetLaneKey);
              cardToMove.setAttribute('data-lane-rank', String(tRank));
              cardToMove.setAttribute('data-is-final', isNowFinal ? '1' : '0');
              cardToMove.setAttribute('draggable', isNowFinal ? 'false' : 'true');
              if (isNowFinal) {
                cardToMove.classList.add('cand-card-final');
                const badgesWrap = cardToMove.querySelector('.cand-card-badges');
                if (badgesWrap && !badgesWrap.querySelector('.cand-pill-final')) {
                  const finalBadge = document.createElement('span');
                  finalBadge.className = 'cand-pill-final ' + (isNowAccepted ? 'cand-pill-final-accepted' : 'cand-pill-final-rejected');
                  finalBadge.textContent = isNowAccepted ? '✓ Final: Diterima' : '✕ Final: Ditolak';
                  badgesWrap.appendChild(finalBadge);
                }
              }

              cardToMove.setAttribute('data-contact-unlocked', isNowAccepted ? '1' : '0');
              cardToMove.setAttribute('data-status-text', laneLabels[targetLaneKey] || targetLaneKey);
              cardToMove.setAttribute('data-status-ui', isNowAccepted ? 'accepted' : (targetLaneKey === 'reviewed' ? 'reviewing' : (targetLaneKey === 'incoming' ? 'applied' : targetLaneKey)));

              // Update card contact box
              const contactContainer = cardToMove.querySelector('.card-contact-box-container');
              if (contactContainer) {
                const waNum = cardToMove.getAttribute('data-contact-wa') || '0812-3456-7890';
                const emailVal = cardToMove.getAttribute('data-contact-email') || 'kandidat@email.com';
                if (isNowAccepted) {
                  contactContainer.innerHTML = buildUnlockedContactHtml(waNum, emailVal, candName);
                } else {
                  contactContainer.innerHTML = buildLockedContactHtml();
                }
              }

              // Show confirmation toast
              const targetLabel = laneLabels[targetLaneKey] || targetLaneKey;
              showSuccessToast(candName + ' berhasil dipindahkan ke ' + targetLabel + (isNowAccepted ? ' (Kontak Terbuka!)' : ''));

              // Map lane to backend status
              let backendStatus = 'applied';
              if (targetLaneKey === 'accepted') backendStatus = 'accepted';
              else if (targetLaneKey === 'rejected') backendStatus = 'rejected';
              else if (targetLaneKey === 'reviewed') backendStatus = 'reviewing';
              else if (targetLaneKey === 'interview') backendStatus = 'interview';

              // Asynchronously persist change to server
              const formData = new FormData();
              formData.append('update_application_status', '1');
              formData.append('app_id', appId);
              formData.append('new_status', backendStatus);
              formData.append('ajax', '1');

              fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                }
              })
              .then(function(res) { return res.json(); })
              .then(function(data) {
                if (!data.ok) {
                  showWarningToast('Peringatan: ' + (data.message || 'Gagal menyimpan ke server'));
                }
              })
              .catch(function(err) {
                console.warn('Pipeline status update sync:', err);
              });
            },
            function onCancel() {
              showWarningToast('Pemindahan ' + candName + ' dibatalkan.');
            }
          );
        });
      });
    }

    setupDragAndDrop();

    /* ─── DRAWER LOGIC ─── */
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
      unlockedAlert: document.getElementById('candUnlockedAlert'),
      lockedAlert: document.getElementById('candLockedAlert'),
      waActionBtn: document.getElementById('candWaActionBtn'),
      emailActionBtn: document.getElementById('candEmailActionBtn'),
      copyBtnWrap: document.getElementById('candCopyBtnWrap'),
      copyFullBtn: document.getElementById('candCopyFullBtn'),
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
            const sName = (typeof s === 'object' && s !== null) ? (s.name || '') : String(s);
            return '<span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:9999px;padding:3px 10px;font-size:0.78rem;font-weight:700;">' + escapeHtml(sName) + '</span>';
          }).join('');
        } else {
          fields.skillsBadges.textContent = 'Belum ada keahlian.';
        }
      }

      const contactUnlocked = (card.getAttribute('data-contact-unlocked') || '0') === '1';
      const waVal = (profile.contact && profile.contact.wa) ? profile.contact.wa : (card.getAttribute('data-contact-wa') || '');
      const emailVal = (profile.contact && profile.contact.email) ? profile.contact.email : (card.getAttribute('data-contact-email') || '');
      const cleanWa = waVal.replace(/[^0-9]/g, '').replace(/^0/, '62');
      const waText = encodeURIComponent("Halo " + name + ", kami dari perusahaan telah menerima lamaran Anda untuk proyek '<?php echo addslashes($job['title']); ?>'.");

      if (fields.unlockedAlert) fields.unlockedAlert.style.display = contactUnlocked ? 'block' : 'none';
      if (fields.lockedAlert) fields.lockedAlert.style.display = contactUnlocked ? 'none' : 'block';

      if (fields.contactEmail) {
        fields.contactEmail.textContent = contactUnlocked ? (emailVal || 'tidak tersedia') : 'Terkunci sampai kandidat diterima';
        fields.contactEmail.style.color = contactUnlocked ? '#2563eb' : '#64748b';
      }
      if (fields.contactWa) {
        fields.contactWa.textContent = contactUnlocked ? (waVal || 'tidak tersedia') : 'Terkunci sampai kandidat diterima';
        fields.contactWa.style.color = contactUnlocked ? '#16a34a' : '#64748b';
      }

      if (fields.waActionBtn) {
        fields.waActionBtn.style.display = (contactUnlocked && waVal) ? 'inline-flex' : 'none';
        fields.waActionBtn.setAttribute('href', 'https://wa.me/' + cleanWa + '?text=' + waText);
      }
      if (fields.emailActionBtn) {
        fields.emailActionBtn.style.display = (contactUnlocked && emailVal) ? 'inline-flex' : 'none';
        fields.emailActionBtn.setAttribute('href', 'mailto:' + encodeURIComponent(emailVal) + '?subject=' + encodeURIComponent("Penerimaan Proyek: <?php echo addslashes($job['title']); ?>"));
      }
      if (fields.copyBtnWrap) {
        fields.copyBtnWrap.style.display = contactUnlocked ? 'block' : 'none';
      }
      if (fields.copyFullBtn) {
        fields.copyFullBtn.onclick = function(e) {
          copyWorkerContact(e, waVal, emailVal);
        };
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
      
      const candRank = parseInt(card.getAttribute('data-lane-rank') || '1', 10);
      const candIsFinal = card.getAttribute('data-is-final') === '1' || candRank >= 4;
      const drawerForm = document.getElementById('drawerStatusForm');
      const saveBtn = drawerForm ? drawerForm.querySelector('button[type="submit"]') : null;

      if (statusSelect) {
        statusSelect.value = card.getAttribute('data-status-ui') || 'applied';

        const statusOptions = statusSelect.querySelectorAll('option');
        statusOptions.forEach(function(opt) {
          const val = opt.value;
          const optRank = (val === 'applied' ? 1 : (val === 'reviewing' ? 2 : (val === 'interview' ? 3 : 4)));
          if (optRank <= candRank) {
            opt.disabled = true;
          } else {
            opt.disabled = false;
          }
        });

        if (candIsFinal) {
          statusSelect.disabled = true;
          if (saveBtn) saveBtn.style.display = 'none';
          let finalNotice = drawerForm.querySelector('.drawer-final-notice');
          if (!finalNotice) {
            finalNotice = document.createElement('div');
            finalNotice.className = 'drawer-final-notice';
            finalNotice.style.cssText = 'padding:6px 12px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;font-size:0.8rem;color:#475569;font-weight:700;display:flex;align-items:center;gap:6px;';
            finalNotice.innerHTML = '🔒 Keputusan sudah final (' + escapeHtml(card.getAttribute('data-status-text') || '') + ')';
            drawerForm.appendChild(finalNotice);
          } else {
            finalNotice.innerHTML = '🔒 Keputusan sudah final (' + escapeHtml(card.getAttribute('data-status-text') || '') + ')';
            finalNotice.style.display = 'flex';
          }
        } else {
          statusSelect.disabled = false;
          if (saveBtn) saveBtn.style.display = 'inline-block';
          const finalNotice = drawerForm ? drawerForm.querySelector('.drawer-final-notice') : null;
          if (finalNotice) finalNotice.style.display = 'none';
        }
      }

      setDrawerTab('profile');
      overlay.classList.add('open');
      drawer.classList.add('open');
      drawer.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    const drawerForm = document.getElementById('drawerStatusForm');
    if (drawerForm) {
      drawerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const newStatus = statusSelect ? statusSelect.value : '';
        const targetLaneKey = (newStatus === 'reviewing' ? 'reviewed' : (newStatus === 'accepted' ? 'accepted' : (newStatus === 'rejected' ? 'rejected' : (newStatus === 'interview' ? 'interview' : 'incoming'))));

        openConfirmModal(
          targetLaneKey,
          function onConfirm() {
            drawerForm.submit();
          },
          function onCancel() {
            showWarningToast('Pembaruan status dibatalkan.');
          }
        );
      });
    }

    document.querySelectorAll('.candidate-card').forEach(function(card) {
      card.addEventListener('click', function(e) {
        if (isDraggingActive) return;
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

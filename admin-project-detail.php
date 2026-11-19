<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/admin-store.php';
require_once __DIR__ . '/includes/vacancy-store.php';

$vacancyId = trim((string)($_GET['id'] ?? ''));
$flash = (string)($_GET['msg'] ?? '');
$flashType = (string)($_GET['type'] ?? 'success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $note = trim((string)($_POST['admin_note'] ?? ''));
    if (!empty($_POST['compliance_reasons']) && is_array($_POST['compliance_reasons'])) {
        $reasons = array_filter(array_map(static fn($r) => trim((string)$r), $_POST['compliance_reasons']));
        if ($reasons !== []) {
            $reasonsText = 'Ketidakpatuhan: ' . implode(', ', $reasons);
            $note = $note !== '' ? $reasonsText . '. ' . $note : $reasonsText;
        }
    }
    if ($action === 'vacancy_decision') {
        $decision = (string)($_POST['decision'] ?? '');
        $targetId = trim((string)($_POST['vacancy_id'] ?? $vacancyId));
        if ($decision === 'approve') {
            $ok = gig_vacancy_set_status($targetId, 'active', $note !== '' ? $note : 'Disetujui Admin KarirHub.');
            $flash = $ok ? 'Lowongan disetujui dan ditayangkan.' : 'Gagal menyetujui lowongan.';
        } elseif ($decision === 'reject') {
            if ($note === '') {
                $note = 'Lowongan tidak memenuhi Syarat & Ketentuan KarirHub.';
            }
            $ok = gig_vacancy_set_status($targetId, 'rejected', $note);
            $flash = $ok ? 'Lowongan ditolak.' : 'Gagal menolak lowongan.';
        } else {
            if ($note === '') {
                $note = 'Harap perbaiki detail lowongan sesuai catatan Admin.';
            }
            $ok = gig_vacancy_set_status($targetId, 'revision', $note);
            $flash = $ok ? 'Lowongan dikembalikan untuk revisi.' : 'Gagal mengirim catatan revisi.';
        }
        $flashType = !empty($ok) ? 'success' : 'error';
        $params = ['id' => $targetId, 'msg' => $flash, 'type' => $flashType];
        header('Location: admin-project-detail.php?' . http_build_query($params));
        exit;
    }
    if ($action === 'admin_toggle_special_req_visibility') {
        $targetId = trim((string)($_POST['vacancy_id'] ?? $vacancyId));
        $targetVacancy = gig_find_vacancy($targetId);
        $sReq = is_array($targetVacancy['special_requirement'] ?? null) ? $targetVacancy['special_requirement'] : null;
        if ($sReq !== null && !empty($sReq['label'])) {
            $currentIsPublic = !isset($sReq['is_public']) || !empty($sReq['is_public']);
            $newIsPublic = !$currentIsPublic;
            $sReq['is_public'] = $newIsPublic;
            $auditNote = 'Admin mengubah visibilitas kualifikasi khusus menjadi: ' . ($newIsPublic ? 'Ditampilkan ke Publik' : 'Disembunyikan dari Publik');
            $ok = gig_vacancy_update_special_requirement($targetId, $sReq, $auditNote);
            $flash = $ok 
                ? ('Status visibilitas kualifikasi khusus berhasil diubah: ' . ($newIsPublic ? 'Sekarang DITAMPILKAN KE PUBLIK.' : 'Sekarang DISEMBUNYIKAN DARI PUBLIK.'))
                : 'Gagal mengubah status visibilitas.';
            $flashType = $ok ? 'success' : 'error';
        } else {
            $flash = 'Lowongan ini tidak memiliki kualifikasi khusus.';
            $flashType = 'error';
        }
        header('Location: admin-project-detail.php?id=' . urlencode($targetId) . '&msg=' . urlencode($flash) . '&type=' . urlencode($flashType));
        exit;
    }

    if ($action === 'admin_update_special_req') {
        $targetId = trim((string)($_POST['vacancy_id'] ?? $vacancyId));
        $enabled = !empty($_POST['special_req_enabled']);
        $reqPayload = null;
        if ($enabled) {
            $type = trim((string)($_POST['special_req_type'] ?? 'certificate'));
            $customType = trim((string)($_POST['special_req_custom_type'] ?? ''));
            $label = trim((string)($_POST['special_req_label'] ?? ''));
            $inputType = trim((string)($_POST['special_req_input_type'] ?? 'pdf'));
            if ($type === 'other' && $customType === '') {
                $flash = 'Nama jenis kualifikasi (lainnya) wajib diisi.';
                $flashType = 'error';
            } elseif ($label === '') {
                $flash = 'Detail / nama kualifikasi yang diminta wajib diisi.';
                $flashType = 'error';
            } else {
                $rawPub = $_POST['special_req_is_public'] ?? '1';
                $isPublic = ($rawPub === '1' || $rawPub === 1 || $rawPub === true || $rawPub === 'true');
                $reqPayload = [
                    'type' => $type,
                    'custom_type' => $customType,
                    'label' => $label,
                    'input_type' => $inputType,
                    'is_public' => $isPublic,
                ];
            }
        }
        if ($flashType !== 'error') {
            $adminNote = trim((string)($_POST['admin_note'] ?? ''));
            $auditNote = 'Admin memperbarui kualifikasi khusus lowongan';
            if ($adminNote !== '') {
                $auditNote .= ': ' . $adminNote;
            }
            $ok = gig_vacancy_update_special_requirement($targetId, $reqPayload, $auditNote);
            $flash = $ok ? 'Kualifikasi khusus lowongan berhasil diperbarui oleh Admin.' : 'Gagal memperbarui kualifikasi khusus.';
            $flashType = $ok ? 'success' : 'error';
        }
        header('Location: admin-project-detail.php?id=' . urlencode($targetId) . '&msg=' . urlencode($flash) . '&type=' . urlencode($flashType));
        exit;
    }
}

$vacancy = $vacancyId !== '' ? gig_find_vacancy($vacancyId) : null;

$dbMeta = [];
$submittedAt = '';
$reviewedAt = '';
if ($vacancy !== null && function_exists('gig_db')) {
    $db = gig_db();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT `created_at`, `reviewed_at`, `status`, `admin_note` FROM `project_vacancies` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => (string)$vacancy['id']]);
            $dbMeta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $submittedAt = trim((string)($dbMeta['created_at'] ?? ''));
            $reviewedAt = trim((string)($dbMeta['reviewed_at'] ?? ''));
        } catch (Throwable $e) {
        }
    }
}

if ($vacancy === null) {
    $adminTab = 'projects';
    $pageTitle = 'Detail Verifikasi Lowongan';
    $breadcrumbCurrent = 'Detail Verifikasi Lowongan';
    require __DIR__ . '/includes/admin-layout-start.php';
    echo '<div class="empty-state">Data lowongan tidak ditemukan. <a href="dashboard-admin.php?tab=projects">Kembali ke verifikasi lowongan</a>.</div>';
    require __DIR__ . '/includes/admin-layout-end.php';
    exit;
}

$status = (string)($vacancy['status'] ?? 'review');
$statusLabel = (string)($vacancy['statusLabel'] ?? 'Menunggu Verifikasi');
$statusBadgeClass = 'is-blue';
if ($status === 'active') {
    $statusLabel = 'Disetujui';
    $statusBadgeClass = 'is-green';
} elseif ($status === 'revision') {
    $statusLabel = 'Revisi';
    $statusBadgeClass = 'is-amber';
} elseif ($status === 'rejected') {
    $statusLabel = 'Ditolak';
    $statusBadgeClass = 'is-red';
}

$postedStamp = $submittedAt !== '' ? strtotime($submittedAt) : strtotime((string)($vacancy['posted'] ?? ''));
if (!$postedStamp) {
    $postedStamp = time();
}
$verifyDeadlineTs = strtotime('+3 days', $postedStamp);
$daysLeft = (int)ceil(($verifyDeadlineTs - time()) / 86400);
$slaLabel = $daysLeft > 0 ? $daysLeft . ' hari lagi' : ($daysLeft === 0 ? 'Hari ini' : 'Lewat tenggat');
$blacklistStatus = 'Tidak terdeteksi';

$auditLogs = [];
$auditLogs[] = [
    'time' => date('d M Y, H:i', $postedStamp),
    'title' => 'Pengajuan lowongan dibuat',
    'detail' => 'Auto-booked ke verifier.',
];
if ($reviewedAt !== '') {
    $reviewStatus = $status === 'active' ? 'disetujui' : ($status === 'rejected' ? 'ditolak' : 'dikembalikan untuk revisi');
    $auditLogs[] = [
        'time' => date('d M Y, H:i', strtotime($reviewedAt)),
        'title' => 'Lowongan ' . $reviewStatus,
        'detail' => trim((string)($vacancy['adminNote'] ?? '')) !== '' ? (string)$vacancy['adminNote'] : 'Keputusan admin tercatat.',
    ];
}

$employerName = (string)($vacancy['employer'] ?? 'Perusahaan');
$skills = is_array($vacancy['skills'] ?? null) ? $vacancy['skills'] : [];
$workType = trim((string)($vacancy['work_type'] ?? ''));
$projectLocation = trim((string)($vacancy['location'] ?? ''));
if ($projectLocation === '' || strcasecmp($projectLocation, 'Lokasi belum diisi') === 0) {
    $projectLocation = gig_random_location((string)($vacancy['id'] ?? ''));
}
$phone = trim((string)($vacancy['employer_phone'] ?? '-'));
$email = trim((string)($vacancy['employer_email'] ?? '-'));
$adminTab = 'projects';
$pageTitle = 'Detail Verifikasi Lowongan';
$breadcrumbCurrent = 'Detail Verifikasi Lowongan';
require __DIR__ . '/includes/admin-layout-start.php';
?>

<style>
  .req-back-link { display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:#334155;margin-bottom:10px; }
  .req-page { display:grid;gap:12px; }
  .req-hero { background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;display:flex;justify-content:space-between;gap:14px;align-items:flex-start; }
  .req-kicker { font-size:.7rem;color:#64748b;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin-bottom:4px; }
  .req-title { margin:0 0 4px 0;font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1.2; }
  .req-meta { display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:.8rem;color:#64748b; }
  .req-badge { display:inline-flex;align-items:center;padding:4px 12px;border-radius:999px;font-size:.72rem;font-weight:800;white-space:nowrap; }
  .req-badge.is-blue { background:#dbeafe;color:#1d4ed8; }
  .req-badge.is-green { background:#dcfce7;color:#166534; }
  .req-badge.is-red { background:#fee2e2;color:#991b1b; }
  .req-badge.is-amber { background:#fef3c7;color:#92400e; }
  .req-btn { border:1px solid #0ea5e9;background:#0ea5e9;color:#fff;border-radius:9px;padding:8px 12px;font-weight:700;font-size:.78rem;cursor:pointer;white-space:nowrap; }
  .req-grid-top { display:grid;grid-template-columns:2fr 1fr;gap:12px; }
  .req-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px; }
  .req-card h3 { margin:0 0 10px 0;font-size:.9rem;font-weight:800;color:#0f172a; }
  .req-summary-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px 16px; }
  .req-label { font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em; }
  .req-value { font-size:.8rem;font-weight:600;color:#0f172a;line-height:1.4;margin-top:2px; }
  .req-audit-item { border-left:2px solid #bfdbfe;padding-left:9px;margin-bottom:10px; }
  .req-audit-item:last-child { margin-bottom:0; }
  .req-audit-time { font-size:.7rem;font-weight:700;color:#64748b; }
  .req-audit-title { font-size:.8rem;font-weight:700;color:#0f172a;margin-top:2px; }
  .req-audit-detail { font-size:.76rem;color:#475569;line-height:1.45;margin-top:2px; }
  .req-main { display:grid;gap:12px; }
  .req-checklist-box { margin-top:10px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;padding:18px;text-align:center;color:#64748b; }
  .req-check-icon { width:32px;height:32px;border-radius:999px;background:#e2e8f0;color:#64748b;display:inline-flex;align-items:center;justify-content:center;font-weight:800;margin-bottom:8px; }
  .req-lowongan-head { display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px; }
  .req-lowongan-title { margin:0;font-size:1.06rem;font-weight:800;color:#0f172a; }
  .req-company { margin:2px 0 10px 0;font-size:.8rem;color:#64748b; }
  .req-mini-kpi { display:grid;grid-template-columns:repeat(3,1fr);gap:10px;border-top:1px solid #eef2f7;border-bottom:1px solid #eef2f7;padding:10px 0;margin-bottom:10px; }
  .req-mini-kpi .item-label { font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.03em; }
  .req-mini-kpi .item-value { font-size:.79rem;color:#0f172a;font-weight:700;margin-top:2px; }
  .req-section-title { margin:10px 0 4px 0;font-size:.8rem;font-weight:700;color:#334155; }
  .req-paragraph { font-size:.8rem;color:#334155;line-height:1.55;margin:0; }
  .req-detail-grid { display:grid;grid-template-columns:1fr 1fr;gap:9px 14px;margin-top:10px; }
  .req-skill-chip { display:inline-flex;padding:4px 10px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-size:.74rem;font-weight:700;margin:2px 6px 2px 0; }
  .req-contact { margin-top:10px;padding-top:10px;border-top:1px solid #eef2f7;font-size:.78rem;color:#334155;display:grid;gap:4px; }
  @media (max-width: 980px) {
    .req-hero { flex-direction:column; }
    .req-grid-top { grid-template-columns:1fr; }
    .req-summary-grid, .req-mini-kpi, .req-detail-grid { grid-template-columns:1fr; }
  }
</style>

<a href="dashboard-admin.php?tab=projects" class="req-back-link">&larr; Kembali</a>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php
  $specialReq = is_array($vacancy['special_requirement'] ?? null) ? $vacancy['special_requirement'] : null;
  $reqTypesCatalog = function_exists('gig_special_requirement_types') ? gig_special_requirement_types() : [];
  $reqTypeKey = $specialReq !== null ? (string)($specialReq['type'] ?? '') : '';
  $reqTypeLabel = $specialReq !== null ? (string)($specialReq['type_label'] ?? ($reqTypesCatalog[$reqTypeKey]['label'] ?? $reqTypeKey)) : '';
  $reqFormatHint = $specialReq !== null ? (string)(
      !empty($specialReq['file_type_label']) ? $specialReq['file_type_label'] : (
          !empty($specialReq['format_hint']) ? $specialReq['format_hint'] : (
              $reqTypesCatalog[$reqTypeKey]['format_hint'] ?? (
                  ($specialReq['input_mode'] ?? '') === 'file' ? 'PDF' :
                  (($specialReq['input_mode'] ?? '') === 'url' ? 'Link / URL' : 'Text')
              )
          )
      )
  ) : '';
?>
<section class="req-page">
  <article class="req-hero">
    <div>
      <div class="req-kicker">Detail Pengajuan Verifikasi Lowongan</div>
      <h1 class="req-title"><?php echo htmlspecialchars((string)$vacancy['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
      <div class="req-meta">
        <span><?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></span>
        <span>Perusahaan</span>
        <span>Diajukan <?php echo htmlspecialchars(date('d M Y, H:i', $postedStamp), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="req-badge <?php echo htmlspecialchars($statusBadgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>
    <button type="button" class="req-btn" onclick="openAdminDecisionModal({entityType:'vacancy', entityName:'lowongan proyek', vacancyId:<?php echo json_encode((string)$vacancy['id']); ?>, action:'vacancy_decision'})">Ambil Keputusan</button>
  </article>

  <div class="req-grid-top">
    <article class="req-card">
      <h3>Ringkasan Pengajuan</h3>
      <div class="req-summary-grid">
        <div><div class="req-label">Jenis Lowongan</div><div class="req-value">Lowongan Kerja Proyek</div></div>
        <div><div class="req-label">Tanggal Pengajuan</div><div class="req-value"><?php echo htmlspecialchars(date('d M Y, H:i', $postedStamp), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Wilayah</div><div class="req-value"><?php echo htmlspecialchars($projectLocation, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Blacklist</div><div class="req-value" style="color:#065f46;"><?php echo htmlspecialchars($blacklistStatus, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">SLA Verifikasi</div><div class="req-value"><?php echo htmlspecialchars($slaLabel, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
            <div class="req-label">Kualifikasi Khusus Pelamar</div>
            <button type="button" onclick="openAdminEditSpecialReqModal()" style="background:none;border:none;color:#0284c7;font-size:0.72rem;font-weight:700;cursor:pointer;padding:0;text-decoration:underline;">✏️ Edit</button>
          </div>
          <?php if ($specialReq !== null && !empty($specialReq['label'])): 
            $topIsPub = !isset($specialReq['is_public']) || !empty($specialReq['is_public']);
          ?>
            <div class="req-value" style="color:#1e40af;"><?php echo htmlspecialchars($reqTypeLabel, ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="font-size:0.74rem;color:#334155;margin-top:2px;font-weight:600;"><?php echo htmlspecialchars((string)$specialReq['label'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="font-size:0.7rem;color:#64748b;margin-top:2px;"><?php echo htmlspecialchars($reqFormatHint, ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="margin-top:6px;">
              <?php if ($topIsPub): ?>
                <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.68rem;font-weight:700;color:#15803d;background:#dcfce7;border:1px solid #86efac;padding:2px 8px;border-radius:6px;" title="Syarat ini tampil terbuka di lowongan dan bursa kerja">
                  🌐 Publik (Terlihat)
                </span>
              <?php else: ?>
                <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.68rem;font-weight:700;color:#475569;background:#f1f5f9;border:1px solid #cbd5e1;padding:2px 8px;border-radius:6px;" title="Syarat ini disembunyikan dari publik, hanya diminta saat melamar">
                  🔒 Tersembunyi (Saat Melamar)
                </span>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="req-value" style="color:#64748b;">Tidak ada</div>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <article class="req-card">
      <h3>Aktivitas &amp; Audit Log</h3>
      <?php foreach ($auditLogs as $log): ?>
        <div class="req-audit-item">
          <div class="req-audit-time"><?php echo htmlspecialchars((string)$log['time'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="req-audit-title"><?php echo htmlspecialchars((string)$log['title'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="req-audit-detail"><?php echo htmlspecialchars((string)$log['detail'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endforeach; ?>
    </article>
  </div>

  <div class="req-main">
    <article class="req-card">
      <h3>Informasi Keputusan dan Verifikasi</h3>
      <div class="req-summary-grid">
        <div><div class="req-label">Deadline Verifikasi</div><div class="req-value"><?php echo in_array((string)($vacancy['status'] ?? ''), ['active', 'approved', 'rejected'], true) ? '-' : htmlspecialchars(date('d M Y, H:i', $verifyDeadlineTs), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Pemeriksaan pada</div><div class="req-value"><?php echo htmlspecialchars(date('d M Y, H:i', $postedStamp), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Nama Petugas</div><div class="req-value"><?php echo htmlspecialchars((string)($adminName ?? 'Admin KarirHub'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Email Petugas</div><div class="req-value"><?php echo htmlspecialchars((string)($_SESSION['admin_email'] ?? 'admin@kemnaker.go.id'), ENT_QUOTES, 'UTF-8'); ?></div></div>
      </div>
      <div class="req-checklist-box">
        <div class="req-check-icon">&#10003;</div>
        <div style="font-size:.78rem;font-weight:700;color:#334155;">Checklist verifikasi tidak tersedia</div>
        <div style="font-size:.74rem;color:#64748b;margin-top:4px;">Checklist akan muncul setelah admin mengambil keputusan verifikasi.</div>
      </div>
    </article>

    <article class="req-card">
      <h3>Informasi Lowongan</h3>
      <div class="req-lowongan-head">
        <h4 class="req-lowongan-title"><?php echo htmlspecialchars((string)$vacancy['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
      </div>
      <div class="req-company"><?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></div>

      <div class="req-mini-kpi">
        <div>
          <div class="item-label">Jenis Entitas</div>
          <div class="item-value">Perusahaan</div>
        </div>
        <div>
          <div class="item-label">Wilayah</div>
          <div class="item-value"><?php echo htmlspecialchars($projectLocation, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div>
          <div class="item-label">Status Lowongan</div>
          <div class="item-value"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>

      <div class="req-section-title">Deskripsi</div>
      <p class="req-paragraph"><?php echo nl2br(htmlspecialchars((string)($vacancy['desc'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></p>

      <div class="req-section-title">Persyaratan Khusus</div>
      <p class="req-paragraph"><?php echo nl2br(htmlspecialchars((string)($vacancy['qualifications'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></p>

      <div style="margin-top:20px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
          <div>
            <div class="req-section-title" style="margin:0;display:flex;align-items:center;gap:6px;">
              <span>📎</span> Permintaan Kualifikasi Khusus Pelamar
            </div>
            <div style="font-size:0.77rem;color:#64748b;margin-top:2px;">
              Admin dapat memutuskan apakah syarat ini ditampilkan secara publik atau hanya diminta saat pelamar melamar.
            </div>
          </div>
          <button type="button" class="req-btn" style="background:#0284c7;border-color:#0284c7;padding:7px 14px;font-size:0.78rem;font-weight:700;border-radius:8px;display:inline-flex;align-items:center;gap:6px;" onclick="openAdminEditSpecialReqModal()">
            ✏️ Edit
          </button>
        </div>

        <?php if ($specialReq !== null && !empty($specialReq['label'])): 
          $isPublicVal = !isset($specialReq['is_public']) || !empty($specialReq['is_public']);
        ?>
          <div style="background:#ffffff;border:1.5px solid <?php echo $isPublicVal ? '#bbf7d0' : '#e2e8f0'; ?>;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <!-- Status Visibilitas Banner (Super Ramah Pengguna Awam) -->
            <div style="padding:12px 16px;border-radius:10px;background:<?php echo $isPublicVal ? '#f0fdf4' : '#f8fafc'; ?>;border:1px solid <?php echo $isPublicVal ? '#86efac' : '#cbd5e1'; ?>;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
              <div style="display:flex;align-items:flex-start;gap:12px;">
                <div style="font-size:1.6rem;line-height:1;margin-top:2px;">
                  <?php echo $isPublicVal ? '🌐' : '🔒'; ?>
                </div>
                <div>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:0.88rem;font-weight:800;color:<?php echo $isPublicVal ? '#15803d' : '#334155'; ?>;">
                      Status Tampilan: <?php echo $isPublicVal ? 'Ditampilkan ke Publik' : 'Disembunyikan dari Publik'; ?>
                    </span>
                    <span style="font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:9999px;background:<?php echo $isPublicVal ? '#dcfce7;color:#166534;border:1px solid #86efac' : '#e2e8f0;color:#475569;border:1px solid #cbd5e1'; ?>;">
                      <?php echo $isPublicVal ? 'Terlihat oleh Pelamar' : 'Hanya Saat Melamar'; ?>
                    </span>
                  </div>
                  <div style="font-size:0.78rem;color:<?php echo $isPublicVal ? '#166534' : '#64748b'; ?>;margin-top:4px;line-height:1.45;">
                    <?php if ($isPublicVal): ?>
                      Syarat ini <strong>tampil terbuka</strong> pada halaman lowongan dan bursa kerja. Calon pelamar dapat langsung mengetahui syarat ini sebelum melamar.
                    <?php else: ?>
                      Syarat ini <strong>disembunyikan</strong> dari rincian lowongan publik. Calon pelamar baru akan diminta melampirkan berkas ini saat menekan tombol "Lamar Proyek".
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- 1-Click Quick Toggle for Non-Tech-Savvy Admin -->
              <form method="post" style="margin:0;">
                <input type="hidden" name="action" value="admin_toggle_special_req_visibility" />
                <input type="hidden" name="vacancy_id" value="<?php echo htmlspecialchars((string)$vacancy['id'], ENT_QUOTES, 'UTF-8'); ?>" />
                <?php if ($isPublicVal): ?>
                  <button type="submit" onclick="return confirm('Sembunyikan kualifikasi khusus ini dari rincian lowongan publik?')" style="background:#ffffff;border:1.5px solid #cbd5e1;color:#475569;font-weight:700;font-size:0.80rem;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">
                    🔒 Sembunyikan
                  </button>
                <?php else: ?>
                  <button type="submit" onclick="return confirm('Tampilkan kualifikasi khusus ini secara publik di halaman lowongan & bursa kerja?')" style="background:#16a34a;border:1px solid #15803d;color:#ffffff;font-weight:700;font-size:0.80rem;padding:8px 16px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 1px 2px rgba(0,0,0,0.1);transition:all 0.15s;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                    🌐 Tampilkan
                  </button>
                <?php endif; ?>
              </form>
            </div>

            <div class="req-detail-grid" style="margin-top:0;">
              <div>
                <div class="req-label">Jenis Kualifikasi</div>
                <div class="req-value" style="color:#1e40af;font-weight:700;"><?php echo htmlspecialchars($reqTypeLabel, ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div>
                <div class="req-label">Format Input Pelamar</div>
                <div class="req-value" style="font-weight:600;"><?php echo htmlspecialchars($reqFormatHint, ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
            <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0;">
              <div class="req-label">Detail / Nama Kualifikasi yang Diminta</div>
              <div class="req-value" style="font-weight:700;color:#0f172a;font-size:0.92rem;margin-top:2px;"><?php echo htmlspecialchars((string)$specialReq['label'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>
        <?php else: ?>
          <div style="background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:12px;padding:16px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
              <div style="font-weight:700;color:#475569;font-size:0.86rem;">Tidak Ada Kualifikasi Khusus</div>
              <div style="font-size:0.76rem;color:#64748b;margin-top:2px;">Pemberi kerja hanya memerlukan berkas lamaran standar. Admin dapat menambahkan syarat jika diperlukan.</div>
            </div>
            <button type="button" class="req-btn" style="background:#0284c7;border-color:#0284c7;padding:7px 14px;font-size:0.78rem;font-weight:700;" onclick="openAdminEditSpecialReqModal()">+ Tambah</button>
          </div>
        <?php endif; ?>
      </div>

      <div class="req-section-title">Detail Lowongan</div>
      <div class="req-detail-grid">
        <div><div class="req-label">Bidang</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['category'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Mode Kerja</div><div class="req-value"><?php echo htmlspecialchars($workType !== '' ? $workType : '-', ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Durasi Proyek</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['duration'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Batas Lamaran</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['deadline'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Nilai Proyek</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['budget'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Visibilitas</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['visibility'] ?? 'public'), ENT_QUOTES, 'UTF-8'); ?></div></div>
      </div>

      <div class="req-section-title">Keahlian yang Dibutuhkan</div>
      <div>
        <?php if ($skills === []): ?>
          <span class="req-value">-</span>
        <?php else: ?>
          <?php foreach ($skills as $skill): ?>
            <span class="req-skill-chip"><?php echo htmlspecialchars((string)$skill, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="req-contact">
        <div><strong>Kontak Lowongan:</strong> <?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Email:</strong> <?php echo htmlspecialchars($email !== '' ? $email : '-', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Telepon:</strong> <?php echo htmlspecialchars($phone !== '' ? $phone : '-', ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </article>
  </div>
</section>


<!-- ADMIN EDIT SPECIAL REQUIREMENT MODAL -->
<div id="adminEditSpecialReqModal" onclick="handleAdminEditBackdrop(event)" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;width:100vw;height:100vh;background:rgba(15,23,42,0.65);backdrop-filter:blur(3px);z-index:99999;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;">
  <div onclick="event.stopPropagation();" style="max-width:540px;width:100%;background:#ffffff;border-radius:16px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);overflow:hidden;display:flex;flex-direction:column;max-height:92vh;position:relative;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;">
      <div>
        <div style="font-size:1.05rem;font-weight:800;color:#0f172a;">Edit Kualifikasi Khusus</div>
        <div style="font-size:0.78rem;color:#64748b;margin-top:2px;">Ubah syarat kualifikasi atau atur visibilitasnya.</div>
      </div>
      <button type="button" onclick="closeAdminEditSpecialReqModal()" style="border:none;background:none;font-size:1.4rem;line-height:1;color:#64748b;cursor:pointer;padding:0 4px;" aria-label="Tutup">&times;</button>
    </div>

    <form method="post" id="adminEditSpecialReqForm" style="display:flex;flex-direction:column;flex:1;overflow-y:auto;padding:20px;gap:14px;">
      <input type="hidden" name="action" value="admin_update_special_req" />
      <input type="hidden" name="vacancy_id" value="<?php echo htmlspecialchars((string)$vacancy['id'], ENT_QUOTES, 'UTF-8'); ?>" />

      <!-- Enable / Disable Card (Ramah & Jelas) -->
      <?php $isEnabled = ($specialReq !== null && !empty($specialReq['label'])); ?>
      <div style="padding:14px 16px;background:#f8fafc;border:1.5px solid <?php echo $isEnabled ? '#38bdf8' : '#cbd5e1'; ?>;border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:0.92rem;font-weight:800;color:#0f172a;">Wajibkan Kualifikasi Khusus</span>
            <span id="admin_toggle_status_badge" style="font-size:0.72rem;font-weight:800;padding:2px 8px;border-radius:9999px;background:<?php echo $isEnabled ? '#dcfce7;color:#15803d;border:1px solid #86efac' : '#f1f5f9;color:#64748b;border:1px solid #cbd5e1'; ?>;">
              <?php echo $isEnabled ? 'Aktif (Wajib)' : 'Nonaktif'; ?>
            </span>
          </div>
          <div style="font-size:0.77rem;color:#64748b;margin-top:3px;">
            Jika dimatikan, lowongan hanya memerlukan berkas lamaran standar tanpa syarat khusus.
          </div>
        </div>
        <label style="position:relative;display:inline-block;width:50px;height:28px;flex-shrink:0;cursor:pointer;">
          <input type="checkbox" name="special_req_enabled" id="admin_req_enabled" value="1" <?php echo $isEnabled ? 'checked' : ''; ?> onchange="toggleAdminReqFields(this.checked)" style="opacity:0;width:0;height:0;" />
          <span style="position:absolute;inset:0;background-color:<?php echo $isEnabled ? '#0ea5e9' : '#cbd5e1'; ?>;border-radius:24px;transition:.2s;" id="admin_switch_slider"></span>
        </label>
      </div>

      <div id="adminReqFieldsWrap" style="<?php echo $isEnabled ? '' : 'display:none;'; ?>">
        <!-- Visibilitas Publik Selection Cards (Sangat Mudah Dipahami Orang Awam) -->
        <?php $modalIsPub = (!isset($specialReq['is_public']) || !empty($specialReq['is_public'])); ?>
        <div style="margin-bottom:14px;background:#f8fafc;padding:12px 14px;border:1px solid #e2e8f0;border-radius:10px;">
          <label style="display:block;font-size:0.80rem;font-weight:800;color:#1e293b;margin-bottom:4px;">
            Keputusan Tampilan Publik (Apakah syarat ini terlihat oleh pelamar sebelum melamar?) *
          </label>
          <div style="font-size:0.74rem;color:#64748b;margin-bottom:10px;">
            Pilih salah satu opsi di bawah ini. Anda dapat mengubah keputusan ini kapan saja.
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <!-- Option 1: Publik -->
            <div id="admin_opt_public_card" onclick="selectAdminVisibility(1)" style="border:2px solid <?php echo $modalIsPub ? '#0284c7' : '#cbd5e1'; ?>;background:<?php echo $modalIsPub ? '#f0f9ff' : '#ffffff'; ?>;border-radius:10px;padding:12px;cursor:pointer;display:flex;flex-direction:column;gap:6px;transition:all 0.15s;position:relative;">
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:0.86rem;font-weight:800;color:#0369a1;display:flex;align-items:center;gap:6px;">
                  <span>🌐</span> Ditampilkan ke Publik
                </span>
                <input type="radio" name="special_req_is_public" id="admin_rad_pub" value="1" <?php echo $modalIsPub ? 'checked' : ''; ?> style="accent-color:#0284c7;width:16px;height:16px;" />
              </div>
              <div style="font-size:0.74rem;color:#475569;line-height:1.4;">
                <span style="color:#0369a1;font-weight:700;">Direkomendasikan.</span> Pelamar dapat membaca syarat ini di bursa kerja dan halaman proyek sebelum melamar.
              </div>
            </div>

            <!-- Option 2: Tersembunyi -->
            <div id="admin_opt_private_card" onclick="selectAdminVisibility(0)" style="border:2px solid <?php echo !$modalIsPub ? '#0284c7' : '#cbd5e1'; ?>;background:<?php echo !$modalIsPub ? '#f0f9ff' : '#ffffff'; ?>;border-radius:10px;padding:12px;cursor:pointer;display:flex;flex-direction:column;gap:6px;transition:all 0.15s;position:relative;">
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:0.86rem;font-weight:800;color:#475569;display:flex;align-items:center;gap:6px;">
                  <span>🔒</span> Disembunyikan
                </span>
                <input type="radio" name="special_req_is_public" id="admin_rad_priv" value="0" <?php echo !$modalIsPub ? 'checked' : ''; ?> style="accent-color:#0284c7;width:16px;height:16px;" />
              </div>
              <div style="font-size:0.74rem;color:#64748b;line-height:1.4;">
                Syarat dirahasiakan dari publik; pelamar baru akan diminta melampirkan saat mengisi formulir lamaran.
              </div>
            </div>
          </div>
        </div>

        <!-- Jenis Kualifikasi -->
        <div style="margin-bottom:12px;">
          <label for="admin_req_type" style="display:block;font-size:0.80rem;font-weight:700;color:#334155;margin-bottom:4px;">Jenis Kualifikasi *</label>
          <select name="special_req_type" id="admin_req_type" onchange="syncAdminReqType(this.value)" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.84rem;">
            <?php foreach ($reqTypesCatalog as $rk => $rm): ?>
              <option value="<?php echo htmlspecialchars($rk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $reqTypeKey === $rk ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($rm['label'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Custom Type Input (for 'other') -->
        <div id="adminReqCustomTypeRow" style="margin-bottom:12px;display:<?php echo $reqTypeKey === 'other' ? 'block' : 'none'; ?>;">
          <label for="admin_req_custom_type" style="display:block;font-size:0.80rem;font-weight:700;color:#334155;margin-bottom:4px;">Nama Jenis Kualifikasi (Lainnya) *</label>
          <input type="text" name="special_req_custom_type" id="admin_req_custom_type" value="<?php echo htmlspecialchars((string)($specialReq['custom_type'] ?? ($reqTypeKey === 'other' ? $reqTypeLabel : '')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Surat Izin Mengemudi (SIM B2) / SKCK / Sertifikat TOEFL" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.84rem;" />
        </div>

        <!-- Detail / Nama Kualifikasi -->
        <div style="margin-bottom:12px;">
          <label for="admin_req_label" style="display:block;font-size:0.80rem;font-weight:700;color:#334155;margin-bottom:4px;">Detail / Nama Kualifikasi yang Diminta *</label>
          <input type="text" name="special_req_label" id="admin_req_label" value="<?php echo htmlspecialchars((string)($specialReq['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Sertifikat AWS Cloud Practitioner / BNSP" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.84rem;" />
        </div>

        <!-- Format Berkas / Input Mode -->
        <div style="margin-bottom:12px;">
          <label for="admin_req_input_type" style="display:block;font-size:0.80rem;font-weight:700;color:#334155;margin-bottom:4px;">Tipe Input / Format Berkas untuk Pelamar *</label>
          <?php
            $currInputType = strtolower((string)($specialReq['input_type'] ?? ($specialReq['input_mode'] ?? 'document')));
            if ($currInputType === 'file') {
                $currInputType = (string)($specialReq['file_type'] ?? 'document');
            }
          ?>
          <select name="special_req_input_type" id="admin_req_input_type" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.84rem;">
            <option value="document" <?php echo in_array($currInputType, ['document', 'pdf', 'doc', 'docx'], true) ? 'selected' : ''; ?>>Dokumen (PDF, DOC, DOCX, maks. 5 MB)</option>
            <option value="image" <?php echo in_array($currInputType, ['image', 'jpg', 'jpeg', 'png'], true) ? 'selected' : ''; ?>>Gambar (JPG, JPEG, PNG, maks. 5 MB)</option>
            <option value="archive" <?php echo in_array($currInputType, ['archive', 'compressed', 'zip', 'rar', '7z'], true) ? 'selected' : ''; ?>>Berkas Kompresi (ZIP, RAR, 7Z, maks. 10 MB)</option>
            <option value="url" <?php echo $currInputType === 'url' ? 'selected' : ''; ?>>Link / URL</option>
            <option value="text" <?php echo in_array($currInputType, ['text', 'textarea'], true) ? 'selected' : ''; ?>>Text</option>
          </select>
        </div>

        <!-- Catatan Admin -->
        <div style="margin-bottom:4px;">
          <label for="admin_req_note" style="display:block;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:4px;">Catatan Perubahan Admin (Opsional)</label>
          <input type="text" name="admin_note" id="admin_req_note" placeholder="Contoh: Menyesuaikan format berkas ke PDF sesuai regulasi" style="width:100%;padding:9px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.84rem;" />
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;padding-top:14px;border-top:1px solid #e2e8f0;margin-top:6px;">
        <button type="button" onclick="closeAdminEditSpecialReqModal()" style="padding:9px 16px;border-radius:8px;border:1px solid #cbd5e1;background:#ffffff;font-size:0.82rem;font-weight:700;color:#475569;cursor:pointer;">Batal</button>
        <button type="submit" style="padding:9px 20px;border-radius:8px;border:none;background:#0ea5e9;color:#ffffff;font-size:0.82rem;font-weight:700;cursor:pointer;">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openAdminEditSpecialReqModal() {
    var modal = document.getElementById('adminEditSpecialReqModal');
    if (modal) {
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      var chk = document.getElementById('admin_req_enabled');
      toggleAdminReqFields(chk ? chk.checked : true);
    }
  }
  function closeAdminEditSpecialReqModal() {
    var modal = document.getElementById('adminEditSpecialReqModal');
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
  }
  function handleAdminEditBackdrop(e) {
    if (e.target.id === 'adminEditSpecialReqModal') {
      closeAdminEditSpecialReqModal();
    }
  }
  function toggleAdminReqFields(enabled) {
    var wrap = document.getElementById('adminReqFieldsWrap');
    var slider = document.getElementById('admin_switch_slider');
    var badge = document.getElementById('admin_toggle_status_badge');
    if (wrap) wrap.style.display = enabled ? 'block' : 'none';
    if (slider) {
      slider.style.backgroundColor = enabled ? '#0ea5e9' : '#cbd5e1';
    }
    if (badge) {
      badge.textContent = enabled ? 'Aktif (Wajib)' : 'Nonaktif';
      badge.style.background = enabled ? '#dcfce7' : '#f1f5f9';
      badge.style.color = enabled ? '#15803d' : '#64748b';
      badge.style.borderColor = enabled ? '#86efac' : '#cbd5e1';
    }
  }

  function selectAdminVisibility(val) {
    var rad1 = document.getElementById('admin_rad_pub');
    var rad0 = document.getElementById('admin_rad_priv');
    var card1 = document.getElementById('admin_opt_public_card');
    var card0 = document.getElementById('admin_opt_private_card');
    if (val === 1) {
      if (rad1) rad1.checked = true;
      if (card1) { card1.style.borderColor = '#0284c7'; card1.style.background = '#f0f9ff'; }
      if (card0) { card0.style.borderColor = '#cbd5e1'; card0.style.background = '#ffffff'; }
    } else {
      if (rad0) rad0.checked = true;
      if (card0) { card0.style.borderColor = '#0284c7'; card0.style.background = '#f0f9ff'; }
      if (card1) { card1.style.borderColor = '#cbd5e1'; card1.style.background = '#ffffff'; }
    }
  }

  function syncAdminReqType(val) {
    var customRow = document.getElementById('adminReqCustomTypeRow');
    if (customRow) {
      customRow.style.display = (val === 'other') ? 'block' : 'none';
    }
  }
</script>

<?php require __DIR__ . '/includes/admin-layout-end.php'; ?>
